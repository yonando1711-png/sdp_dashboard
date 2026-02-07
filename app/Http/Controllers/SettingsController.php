<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Show settings page
     */
    public function index()
    {
        // Get all chart target settings
        $targets = [
            'target_in_stock' => (int) Setting::get('target_in_stock', 500),
            'target_rented' => (int) Setting::get('target_rented', 2500),
            'target_in_service' => (int) Setting::get('target_in_service', 100),
            'target_subscription' => (int) Setting::get('target_subscription', 1500),
            'target_regular' => (int) Setting::get('target_regular', 1000),
        ];

        // Get Odoo settings
        $odoo = Setting::getOdooConfig();
        $odoo['schedule_enabled'] = Setting::get('odoo_schedule_enabled', 'false') === 'true';
        $odoo['schedule_interval'] = Setting::get('odoo_schedule_interval', '60');

        return view('settings', compact('targets', 'odoo'));
    }

    /**
     * Update chart target settings
     */
    public function updateTargets(Request $request)
    {
        $request->validate([
            'target_in_stock' => 'required|integer|min:0',
            'target_rented' => 'required|integer|min:0',
            'target_in_service' => 'required|integer|min:0',
            'target_subscription' => 'required|integer|min:0',
            'target_regular' => 'required|integer|min:0',
        ]);

        Setting::set('target_in_stock', $request->target_in_stock);
        Setting::set('target_rented', $request->target_rented);
        Setting::set('target_in_service', $request->target_in_service);
        Setting::set('target_subscription', $request->target_subscription);
        Setting::set('target_regular', $request->target_regular);

        return redirect()->route('settings')->with('success', 'Chart targets updated successfully!');
    }

    /**
     * Update Odoo connection settings
     */
    public function updateOdoo(Request $request)
    {
        $request->validate([
            'odoo_url' => 'required|url',
            'odoo_db' => 'required|string',
            'odoo_user' => 'required|string',
            'odoo_password' => 'nullable|string',
        ]);

        Setting::set('odoo_url', $request->odoo_url);
        Setting::set('odoo_db', $request->odoo_db);
        Setting::set('odoo_user', $request->odoo_user);
        
        // Only update password if provided
        if ($request->filled('odoo_password')) {
            Setting::set('odoo_password', $request->odoo_password);
        }

        return redirect()->route('settings')->with('success', 'Odoo connection settings updated!');
    }

    /**
     * Get target values as JSON for dashboard chart
     */
    public function getTargets()
    {
        return response()->json([
            'in_stock' => (int) Setting::get('target_in_stock', 500),
            'rented' => (int) Setting::get('target_rented', 2500),
            'in_service' => (int) Setting::get('target_in_service', 100),
            'subscription' => (int) Setting::get('target_subscription', 1500),
            'regular' => (int) Setting::get('target_regular', 1000),
        ]);
    }
}
