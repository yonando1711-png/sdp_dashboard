<?php

namespace App\Http\Controllers;

use App\Models\LorSmdSoBatch;
use App\Models\LorSmdSoItem;
use App\Models\Item;
use App\Services\LorListSoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LorListSoController extends Controller
{
    protected LorListSoService $service;

    public function __construct(LorListSoService $service)
    {
        $this->service = $service;
    }

    /**
     * Display the main List SO page (Unprinted Queue & Batch History)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user->canViewListSo()) {
            abort(403, 'Unauthorized access to List SO.');
        }

        $salesManager = $request->input('sales_manager', $request->input('sales_team'));
        $activeTab = $request->input('tab', 'queue');

        // Fetch unprinted items grouped by the 6 categories (filtered by Sales Manager)
        $grouped = $this->service->getUnprintedGrouped($salesManager, $user);

        // Sales Managers list
        $salesManagers = LorListSoService::getSalesManagers();

        // Fetch past print batches for History Tab
        $batchQuery = LorSmdSoBatch::with('printedBy')->latest('printed_at');
        if (!empty($salesManager) && $salesManager !== 'all') {
            $batchQuery->where(function($q) use ($salesManager) {
                $q->where('sales_team', 'like', $salesManager . '%')->orWhere('sales_team', 'ALL TEAMS');
            });
        }
        $batches = $batchQuery->paginate(15);

        return view('lor.list_so.index', [
            'grouped'         => $grouped,
            'batches'         => $batches,
            'salesManagers'   => $salesManagers,
            'selectedManager' => $salesManager,
            'activeTab'       => $activeTab,
            'totalUnprinted'  => $grouped['total_count'],
        ]);
    }

    /**
     * Trigger manual Odoo synchronization
     */
    public function sync(Request $request)
    {
        $user = auth()->user();
        if (!$user->canViewListSo()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $targetDate = $request->input('target_date', date('Y-m-d'));
        $dateFrom = $request->input('date_from', $targetDate);
        $dateTo = $request->input('date_to', $targetDate);
        $salesManager = $request->input('sales_manager', $request->input('sales_team'));

        try {
            $res = $this->service->syncFromOdoo($dateFrom, $dateTo, $salesManager);
            return response()->json([
                'status'  => 'success',
                'message' => sprintf(
                    'Sync complete! %d orders retrieved (%d new, %d updated).',
                    $res['synced'],
                    $res['new'],
                    $res['updated']
                ),
                'details' => $res,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview PDF of current unprinted items without printing or modifying database
     */
    public function previewPdf(Request $request)
    {
        $user = auth()->user();
        if (!$user->canViewListSo()) {
            abort(403, 'Unauthorized access to List SO.');
        }

        $salesManager = $request->input('sales_manager', $request->input('sales_team'));
        $rawItemIds = $request->input('item_ids');
        $itemIds = null;
        if (!empty($rawItemIds)) {
            $itemIds = is_array($rawItemIds) ? $rawItemIds : explode(',', $rawItemIds);
            $itemIds = array_filter(array_map('intval', $itemIds));
        }

        try {
            if ($request->input('format') === 'pdf') {
                $pdfOutput = $this->service->generatePreviewPdf($salesManager, $user, $itemIds);
                return response($pdfOutput, 200, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="PREVIEW_LIST_SO.pdf"',
                ]);
            }

            // Direct clean HTML preview: immune to Adobe Acrobat extension & browser PDF toolbars
            $htmlOutput = $this->service->generatePreviewHtml($salesManager, $user, $itemIds);
            return response($htmlOutput, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        } catch (\Exception $e) {
            return response('Gagal membuat preview: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate PDF and mark selected unprinted items as PRINTED in a new Batch
     */
    public function printBatch(Request $request)
    {
        $user = auth()->user();
        if (!$user->canViewListSo()) {
            abort(403, 'Unauthorized.');
        }

        $salesManager = $request->input('sales_manager', $request->input('sales_team'));
        $rawItemIds = $request->input('item_ids');
        $itemIds = [];
        if (!empty($rawItemIds)) {
            $itemIds = is_array($rawItemIds) ? $rawItemIds : explode(',', $rawItemIds);
            $itemIds = array_filter(array_map('intval', $itemIds));
        }

        // If no explicit item IDs provided, take all currently unprinted items for this manager
        if (empty($itemIds)) {
            $unprinted = $this->service->getUnprintedGrouped($salesManager, $user);
            $itemIds = $unprinted['all_items']->pluck('id')->toArray();
        }

        if (empty($itemIds)) {
            return redirect()->back()->with('error', 'Tidak ada antrean SO untuk dicetak.');
        }

        try {
            $result = $this->service->createPrintBatch((array)$itemIds, $user, $salesManager);

            // Stream / Download PDF directly
            return response($result['pdf_output'], 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $result['filename']),
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat dokumen PDF: ' . $e->getMessage());
        }
    }

    /**
     * Download past batch PDF from history with DUPLIKAT stamp
     */
    public function downloadBatchPdf($id)
    {
        $user = auth()->user();
        if (!$user->canViewListSo()) {
            abort(403, 'Unauthorized.');
        }

        $batch = LorSmdSoBatch::findOrFail($id);

        try {
            $result = $this->service->reprintBatchPdf($id, $user);

            return response($result['pdf_output'], 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $result['filename']),
            ]);
        } catch (\Exception $e) {
            if ($batch->pdf_path && Storage::disk('public')->exists($batch->pdf_path)) {
                return Storage::disk('public')->download($batch->pdf_path, sprintf('%s.pdf', $batch->batch_no));
            }
            abort(404, 'PDF file not found in storage: ' . $e->getMessage());
        }
    }
}
