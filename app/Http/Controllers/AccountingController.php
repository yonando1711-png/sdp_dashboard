<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OdooService;
use App\Exports\SummaryRentedVehicleExport;
use App\Exports\SummaryRentedVehicleHierarchicalExport;
use App\Exports\SummaryRentedVehicleMultiTabExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class AccountingController extends Controller
{
    protected OdooService $odooService;

    public function __construct(OdooService $odooService)
    {
        $this->odooService = $odooService;
    }

    /**
     * Display Multi-Month Summary of Rented Vehicles (Untaxed)
     */
    public function summaryRentedVehicle(Request $request)
    {
        if (!auth()->user()->canViewSummaryRentedVehicle()) {
            abort(403, 'Access Denied: You do not have permission to view the Summary of Rented Vehicle report.');
        }

        $startMonth = $request->input('start_month', now()->format('Y-m'));
        $endMonth = $request->input('end_month', now()->addMonth()->format('Y-m'));
        $year = (int) $request->input('year', substr($startMonth, 0, 4) ?: now()->year);
        $search = trim((string) $request->input('search', ''));
        $changesOnly = $request->boolean('changes_only', false);
        $excludeOthersLt = true; // Always excluded per business rule

        $syncType = $request->input('sync_type');
        $syncMessage = null;

        if ($syncType === 'fast') {
            $masterData = $this->odooService->fetchAccountingSubscriptionMaster($year, forceFull: false, incremental: true);
            $syncMessage = $masterData['sync_message'] ?? 'Fast Sync complete.';
        } elseif ($syncType === 'full' || $request->boolean('refresh')) {
            $masterData = $this->odooService->fetchAccountingSubscriptionMaster($year, forceFull: true, incremental: false);
            $syncMessage = $masterData['sync_message'] ?? 'Full Sync complete.';
        }

        $cacheKey = "accounting_subscription_master_{$year}";
        $isYearCached = \Illuminate\Support\Facades\Cache::has($cacheKey);

        $hasQuery = $request->filled('start_month') || $request->filled('end_month') || $request->boolean('generate') || $syncType !== null;
        $reportData = null;
        $lastSyncedAt = null;
        $lastSyncFormatted = 'Not synced';

        if ($isYearCached) {
            $masterData = $this->odooService->fetchAccountingSubscriptionMaster($year);
            $lastSyncedAt = $masterData['last_synced_at'] ?? null;
            $lastSyncFormatted = $lastSyncedAt ? \Carbon\Carbon::parse($lastSyncedAt, 'UTC')->diffForHumans() : 'Never';

            $reportData = $this->odooService->computeSummaryRentedVehiclePivot(
                $masterData,
                $startMonth,
                $endMonth,
                $excludeOthersLt,
                $search !== '' ? $search : null
            );

            if ($changesOnly && !empty($reportData['customers'])) {
                $filteredCustomers = array_filter($reportData['customers'], function ($c) {
                    return !empty($c['has_any_period_change']) || !empty($c['has_any_price_change']);
                });
                $reportData['customers'] = array_values($filteredCustomers);

                // Recalculate totals for filtered subset
                $newMonthTotals = [];
                foreach ($reportData['month_keys'] as $mk) {
                    $newMonthTotals[$mk] = ['qty' => 0, 'value' => 0];
                }
                $newGrandTotal = 0;
                foreach ($reportData['customers'] as $c) {
                    foreach ($reportData['month_keys'] as $mk) {
                        $newMonthTotals[$mk]['qty'] += $c['months'][$mk]['qty'] ?? 0;
                        $newMonthTotals[$mk]['value'] += $c['months'][$mk]['value'] ?? 0;
                    }
                    $newGrandTotal += $c['total_value'] ?? 0;
                }
                $reportData['totals']['months'] = $newMonthTotals;
                $reportData['totals']['grand_total_value'] = $newGrandTotal;
            }
        }

        return view('accounting.summary_rented_vehicle', [
            'hasQuery' => $hasQuery || $isYearCached,
            'reportData' => $reportData,
            'startMonth' => $startMonth,
            'endMonth' => $endMonth,
            'year' => $year,
            'isYearCached' => $isYearCached,
            'lastSyncedAt' => $lastSyncedAt,
            'lastSyncFormatted' => $lastSyncFormatted,
            'syncMessage' => $syncMessage,
            'search' => $search,
            'changesOnly' => $changesOnly,
        ]);
    }

    /**
     * Trigger synchronization with live progress reporting for Accounting Report
     */
    public function triggerSync(Request $request)
    {
        $year = (int)$request->input('year', now()->year);
        $syncType = $request->input('sync_type', 'fast'); // 'fast' or 'full'
        $progressKey = "accounting_sync_progress_{$year}";

        // Prevent concurrent sync jobs for the same year
        $currentProgress = \Illuminate\Support\Facades\Cache::get($progressKey);
        if ($currentProgress && ($currentProgress['status'] ?? '') === 'running') {
            $startedAt = $currentProgress['started_timestamp'] ?? 0;
            if (time() - $startedAt < 600) {
                return response()->json([
                    'status' => 'running',
                    'message' => 'Sync is already running for year ' . $year,
                    'percent' => $currentProgress['percent'] ?? 0,
                    'stage' => $currentProgress['stage'] ?? 'running',
                    'records' => $currentProgress['records'] ?? 0,
                    'total' => $currentProgress['total'] ?? 0,
                ]);
            }
        }

        // Initialize progress state
        \Illuminate\Support\Facades\Cache::put($progressKey, [
            'status' => 'running',
            'percent' => 5,
            'stage' => 'init',
            'message' => 'Initiating sync with Odoo for fiscal year ' . $year . '...',
            'records' => 0,
            'total' => 0,
            'started_timestamp' => time(),
            'updated_at' => microtime(true),
        ], 600);

        try {
            $onProgress = function (string $stage, int $percent, int $records, int $total, string $message) use ($progressKey) {
                \Illuminate\Support\Facades\Cache::put($progressKey, [
                    'status' => $percent >= 100 ? 'completed' : 'running',
                    'percent' => $percent,
                    'stage' => $stage,
                    'records' => $records,
                    'total' => $total,
                    'message' => $message,
                    'updated_at' => microtime(true),
                ], 600);
            };

            $isFull = ($syncType === 'full');
            $masterData = $this->odooService->fetchAccountingSubscriptionMaster(
                $year,
                forceFull: $isFull,
                incremental: !$isFull,
                onProgress: $onProgress
            );

            \Illuminate\Support\Facades\Cache::put($progressKey, [
                'status' => 'completed',
                'percent' => 100,
                'stage' => 'completed',
                'records' => count($masterData['orders'] ?? []),
                'total' => count($masterData['orders'] ?? []),
                'message' => $masterData['sync_message'] ?? 'Sync completed successfully.',
                'updated_at' => microtime(true),
            ], 600);

            return response()->json([
                'status' => 'completed',
                'message' => $masterData['sync_message'] ?? 'Sync completed successfully.',
                'total_orders' => count($masterData['orders'] ?? []),
                'last_synced_at' => $masterData['last_synced_at'] ?? null,
                'last_sync_formatted' => !empty($masterData['last_synced_at']) ? \Carbon\Carbon::parse($masterData['last_synced_at'], 'UTC')->diffForHumans() : 'Just now',
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Accounting sync error for year {$year}: " . $e->getMessage());

            \Illuminate\Support\Facades\Cache::put($progressKey, [
                'status' => 'error',
                'percent' => 0,
                'stage' => 'error',
                'records' => 0,
                'total' => 0,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'updated_at' => microtime(true),
            ], 600);

            return response()->json([
                'status' => 'error',
                'message' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get real-time sync progress for Accounting Report
     */
    public function getSyncProgress(Request $request)
    {
        $year = (int)$request->input('year', now()->year);
        $progressKey = "accounting_sync_progress_{$year}";

        $progress = \Illuminate\Support\Facades\Cache::get($progressKey, [
            'status' => 'idle',
            'percent' => 0,
            'stage' => 'idle',
            'records' => 0,
            'total' => 0,
            'message' => 'No active synchronization.',
        ]);

        return response()->json($progress);
    }

    /**
     * Export Summary of Rented Vehicles to Excel (.xlsx)
     */
    public function exportSummaryRentedVehicle(Request $request)
    {
        if (!auth()->user()->canViewSummaryRentedVehicle()) {
            abort(403, 'Access Denied: You do not have permission to export this report.');
        }

        $startMonth = $request->input('start_month', now()->format('Y-m'));
        $endMonth = $request->input('end_month', now()->addMonth()->format('Y-m'));
        $year = (int) $request->input('year', substr($startMonth, 0, 4) ?: now()->year);
        $excludeOthersLt = $request->boolean('exclude_others_lt', true);
        $search = trim((string) $request->input('search', ''));
        $changesOnly = $request->boolean('changes_only', false);

        // Retrieve from cached year master data and compute pivot instantly
        $masterData = $this->odooService->fetchAccountingSubscriptionMaster($year);
        $reportData = $this->odooService->computeSummaryRentedVehiclePivot(
            $masterData,
            $startMonth,
            $endMonth,
            $excludeOthersLt,
            $search !== '' ? $search : null
        );

        if ($changesOnly && !empty($reportData['customers'])) {
            $filteredCustomers = array_filter($reportData['customers'], function ($c) {
                return !empty($c['has_any_period_change']) || !empty($c['has_any_price_change']);
            });
            $reportData['customers'] = array_values($filteredCustomers);

            $newMonthTotals = [];
            foreach ($reportData['month_keys'] as $mk) {
                $newMonthTotals[$mk] = ['qty' => 0, 'value' => 0];
            }
            $newGrandTotal = 0;
            foreach ($reportData['customers'] as $c) {
                foreach ($reportData['month_keys'] as $mk) {
                    $newMonthTotals[$mk]['qty'] += $c['months'][$mk]['qty'] ?? 0;
                    $newMonthTotals[$mk]['value'] += $c['months'][$mk]['value'] ?? 0;
                }
                $newGrandTotal += $c['total_value'] ?? 0;
            }
            $reportData['totals']['months'] = $newMonthTotals;
            $reportData['totals']['grand_total_value'] = $newGrandTotal;
        }

        $format = $request->input('format', 'multitab');

        if ($format === 'hierarchical') {
            $fileName = sprintf('Summary_Rented_Vehicle_Hierarchical_%s_to_%s.xlsx', $startMonth, $endMonth);
            return Excel::download(
                new SummaryRentedVehicleHierarchicalExport($reportData, $startMonth, $endMonth, $excludeOthersLt),
                $fileName
            );
        }

        if ($format === 'classic') {
            $fileName = sprintf('Summary_Rented_Vehicle_%s_to_%s.xlsx', $startMonth, $endMonth);
            return Excel::download(
                new SummaryRentedVehicleExport($reportData, $startMonth, $endMonth, $excludeOthersLt),
                $fileName
            );
        }

        // Default: Multi-Tab (Option B: 2 Sheets - Customer Summary + Flat Vehicle Details)
        $fileName = sprintf('Summary_Rented_Vehicle_MultiTab_%s_to_%s.xlsx', $startMonth, $endMonth);
        return Excel::download(
            new SummaryRentedVehicleMultiTabExport($reportData, $startMonth, $endMonth, $excludeOthersLt),
            $fileName
        );
    }

    /**
     * Export Summary of Rented Vehicles to PDF (.pdf)
     */
    public function exportSummaryRentedVehiclePdf(Request $request)
    {
        if (!auth()->user()->canViewSummaryRentedVehicle()) {
            abort(403, 'Access Denied: You do not have permission to export this report.');
        }

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $startMonth = $request->input('start_month', now()->format('Y-m'));
        $endMonth = $request->input('end_month', now()->addMonth()->format('Y-m'));
        $year = (int) $request->input('year', substr($startMonth, 0, 4) ?: now()->year);
        $excludeOthersLt = true;
        $search = trim((string) $request->input('search', ''));
        $changesOnly = $request->boolean('changes_only', false);

        // Retrieve from cached year master data and compute pivot instantly
        $masterData = $this->odooService->fetchAccountingSubscriptionMaster($year);
        $reportData = $this->odooService->computeSummaryRentedVehiclePivot(
            $masterData,
            $startMonth,
            $endMonth,
            $excludeOthersLt,
            $search !== '' ? $search : null
        );

        if ($changesOnly && !empty($reportData['customers'])) {
            $filteredCustomers = array_filter($reportData['customers'], function ($c) {
                return !empty($c['has_any_period_change']) || !empty($c['has_any_price_change']);
            });
            $reportData['customers'] = array_values($filteredCustomers);

            $newMonthTotals = [];
            foreach ($reportData['month_keys'] as $mk) {
                $newMonthTotals[$mk] = ['qty' => 0, 'value' => 0];
            }
            $newGrandTotal = 0;
            foreach ($reportData['customers'] as $c) {
                foreach ($reportData['month_keys'] as $mk) {
                    $newMonthTotals[$mk]['qty'] += $c['months'][$mk]['qty'] ?? 0;
                    $newMonthTotals[$mk]['value'] += $c['months'][$mk]['value'] ?? 0;
                }
                $newGrandTotal += $c['total_value'] ?? 0;
            }
            $reportData['totals']['months'] = $newMonthTotals;
            $reportData['totals']['grand_total_value'] = $newGrandTotal;
        }

        $type = $request->input('type', 'summary');
        $includeVehicles = ($type === 'detailed') || ($request->has('details') && $request->boolean('details'));

        // Guard against memory exhaustion for unfiltered full fleet (3,450+ vehicles) in PDF
        if ($includeVehicles && count($reportData['customers'] ?? []) > 60) {
            return redirect()->route('accounting.summary-rented-vehicle', $request->all())
                ->with('error', 'The complete fleet contains 3,450+ vehicles across ' . count($reportData['customers']) . ' customers, which exceeds browser PDF memory limits (>1 GB). Please use the "Export Excel" option (which instantly exports all 3,450+ vehicles in Hierarchical or Multi-Tab format), or filter by Customer Search / "Changes Only" before exporting to Detailed PDF.');
        }

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);

        $html = view('exports.summary_rented_vehicle_pdf', [
            'customers' => $reportData['customers'] ?? [],
            'totals' => $reportData['totals'] ?? [],
            'monthKeys' => $reportData['month_keys'] ?? [],
            'monthLabels' => $reportData['month_labels'] ?? [],
            'startMonth' => $startMonth,
            'endMonth' => $endMonth,
            'search' => $search,
            'changesOnly' => $changesOnly,
            'includeVehicles' => $includeVehicles,
        ])->render();

        $dompdf->loadHtml($html);
        $paperSize = count($reportData['month_keys'] ?? []) > 6 ? 'A3' : 'A4';
        $dompdf->setPaper($paperSize, 'landscape');
        $dompdf->render();

        $fileName = sprintf(
            'Summary_Rented_Vehicle_%s_%s_to_%s.pdf',
            $includeVehicles ? 'Detailed' : 'Summary',
            $startMonth,
            $endMonth
        );

        return response()->streamDownload(
            fn () => print($dompdf->output()),
            $fileName,
            ['Content-Type' => 'application/pdf']
        );
    }
}
