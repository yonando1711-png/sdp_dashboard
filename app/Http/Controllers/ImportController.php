<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Services\OdooService;
use App\Services\SummaryGenerator;

class ImportController extends Controller
{
    /**
     * Show import data page
     */
    public function index()
    {
        $odooConfig = Setting::getOdooConfig();
        return view('import', compact('odooConfig'));
    }

    /**
     * Handle Excel file upload (existing functionality)
     */
    public function uploadExcel(Request $request, SummaryGenerator $generator)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');
        
        try {
            $result = $generator->generate($file);
            $generator->saveToDatabase($result['items'], $result['summary']);
            
            return redirect()->back()->with('success', 'Excel data imported successfully! ' . count($result['items']) . ' items processed.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Save Odoo configuration
     */
    public function saveOdooConfig(Request $request)
    {
        $request->validate([
            'odoo_url' => 'required|url',
            'odoo_db' => 'required|string',
            'odoo_user' => 'required|string',
            'odoo_password' => 'required|string',
        ]);

        Setting::set('odoo_url', $request->input('odoo_url'));
        Setting::set('odoo_db', $request->input('odoo_db'));
        Setting::set('odoo_user', $request->input('odoo_user'));
        Setting::set('odoo_password', $request->input('odoo_password'));

        return response()->json(['success' => true, 'message' => 'Configuration saved successfully.']);
    }

    /**
     * Test Odoo connection
     */
    public function testOdooConnection()
    {
        $odoo = new OdooService();
        $result = $odoo->testConnection();
        
        return response()->json($result);
    }

    /**
     * Sync data from Odoo
     */
    public function syncOdoo(SummaryGenerator $generator)
    {
        try {
            $odoo = new OdooService();
            
            // Use stock.quant as default model (can be made configurable)
            $model = \App\Models\Setting::get('odoo_model', 'stock.quant');
            
            $result = $odoo->syncAndSave($model);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ]);
        }
    }
}
