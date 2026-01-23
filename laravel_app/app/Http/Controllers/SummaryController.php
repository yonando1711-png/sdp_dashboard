<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SummaryGenerator;

use App\Repositories\JsonItemRepository;

class SummaryController extends Controller
{
    public function index(JsonItemRepository $repo)
    {
        // Calculate summary from saved items if exists?
        // Or just redirect to dashboard?
        // For now, let's keep index as upload form? 
        // User wants "make dashboard as main interface".
        // So index should be the dashboard.
        return redirect()->route('dashboard');
    }

    public function generate(Request $request, SummaryGenerator $generator, JsonItemRepository $repo)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        $result = $generator->generate($file);
        
        // Save items and summary
        $repo->saveAll($result['items']);
        $repo->saveSummary($result['summary']);

        // Save metadata with import timestamp
        $repo->saveMetadata([
            'imported_at' => now()->toIso8601String(),
            'filename' => $file->getClientOriginalName(),
        ]);

        // Add to history for trends
        $repo->addToHistory([
            'date' => now()->toDateString(),
            'timestamp' => now()->toIso8601String(),
            'sdp_stock' => $result['summary']['sdp_stock'],
            'in_stock' => $result['summary']['in_stock']['total'],
            'rented' => $result['summary']['rented_in_customer']['total'],
            'in_service' => $result['summary']['stock_external_service']['total'] + $result['summary']['stock_internal_service']['total'] + $result['summary']['stock_insurance']['total'],
            'vendor_rent' => $result['summary']['vendor_rent'],
        ]);

        // Redirect to dashboard
        return redirect()->route('dashboard')->with('success', 'Data imported successfully!');
    }
}
