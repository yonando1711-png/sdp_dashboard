<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OdooService;
use App\Exports\SummaryRentedVehicleExport;
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

        // Only query Odoo if the user explicitly submitted a month range or clicked a preset
        $hasQuery = $request->filled('start_month') || $request->filled('end_month') || $request->boolean('generate');

        $startMonth = $request->input('start_month', now()->format('Y-m'));
        $endMonth = $request->input('end_month', now()->addMonth()->format('Y-m'));
        $search = trim((string) $request->input('search', ''));
        $changesOnly = $request->boolean('changes_only', false);
        $excludeOthersLt = true; // Always excluded per business rule

        $reportData = null;

        if ($hasQuery) {
            $cacheKey = "accounting_srv_{$startMonth}_{$endMonth}_" . md5($search);

            // If user clicked Re-fetch, bust cache
            if ($request->boolean('refresh')) {
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
            }

            $reportData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 1800, function () use ($startMonth, $endMonth, $excludeOthersLt, $search) {
                return $this->odooService->fetchSummaryRentedVehicle(
                    $startMonth,
                    $endMonth,
                    $excludeOthersLt,
                    $search !== '' ? $search : null
                );
            });

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
            'hasQuery' => $hasQuery,
            'reportData' => $reportData,
            'startMonth' => $startMonth,
            'endMonth' => $endMonth,
            'search' => $search,
            'changesOnly' => $changesOnly,
        ]);
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
        $excludeOthersLt = $request->boolean('exclude_others_lt', true);
        $search = trim((string) $request->input('search', ''));
        $changesOnly = $request->boolean('changes_only', false);

        $reportData = $this->odooService->fetchSummaryRentedVehicle(
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

        $fileName = sprintf('Summary_Rented_Vehicle_%s_to_%s.xlsx', $startMonth, $endMonth);

        return Excel::download(
            new SummaryRentedVehicleExport($reportData, $startMonth, $endMonth, $excludeOthersLt),
            $fileName
        );
    }
}
