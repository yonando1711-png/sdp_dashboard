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
        // Get all chart target settings (percentage-based)
        $targets = [
            'target_in_stock_pct' => (float) Setting::get('target_in_stock_pct', 10),
            'target_active_rental_pct' => (float) Setting::get('target_active_rental_pct', 82),
            'target_in_service_pct' => (float) Setting::get('target_in_service_pct', 8),
            'dashboard_layout' => Setting::get('dashboard_layout', 'kpi_progress'),
            'target_subscription' => (int) Setting::get('target_subscription', 1500),
            'target_regular' => (int) Setting::get('target_regular', 1000),
            'dashboard_show_history' => Setting::get('dashboard_show_history', 'true') === 'true',
        ];

        // Get Odoo settings
        $odoo = Setting::getOdooConfig();
        $odoo['schedule_enabled'] = Setting::get('odoo_schedule_enabled', 'false') === 'true';
        $odoo['schedule_interval'] = Setting::get('odoo_schedule_interval', '60');

        // Get Surat Kuasa settings
        $suratKuasa = [
            'pemberi_1_nama' => Setting::get('surat_kuasa_pemberi_1_nama', 'Suzanna Caroline'),
            'pemberi_1_jabatan' => Setting::get('surat_kuasa_pemberi_1_jabatan', 'General Manager'),
            'pemberi_2_nama' => Setting::get('surat_kuasa_pemberi_2_nama', 'Aldian Prayoga Darwis'),
            'pemberi_2_jabatan' => Setting::get('surat_kuasa_pemberi_2_jabatan', 'Fleet Operation Manager'),
            'pemberi_alamat' => Setting::get('surat_kuasa_pemberi_alamat', 'Jl. Daan Mogot KM 1 No. 99 Jakarta Barat 11510'),
            'pemilik_nama' => Setting::get('surat_kuasa_pemilik_nama', 'PT Surya Darma Perkasa'),
            'pemilik_alamat' => Setting::get('surat_kuasa_pemilik_alamat', 'Kel. Duri Kepa Kec. Kebon Jeruk Kota Jakarta Barat'),
            'default_recipient_email' => Setting::get('surat_kuasa_default_recipient_email', ''),
            'password' => Setting::get('surat_kuasa_password') ? '********' : 'admin (Default)',
        ];

        return view('settings', compact('targets', 'odoo', 'suratKuasa'));
    }

    /**
     * Update Surat Kuasa configuration settings
     */
    public function updateSuratKuasaSettings(Request $request)
    {
        $request->validate([
            'pemberi_1_nama' => 'required|string|max:255',
            'pemberi_1_jabatan' => 'required|string|max:255',
            'pemberi_2_nama' => 'required|string|max:255',
            'pemberi_2_jabatan' => 'required|string|max:255',
            'pemberi_alamat' => 'required|string|max:500',
            'pemilik_nama' => 'required|string|max:255',
            'pemilik_alamat' => 'nullable|string|max:500',
            'default_recipient_email' => 'nullable|string|max:500',
        ]);

        Setting::set('surat_kuasa_pemberi_1_nama', $request->pemberi_1_nama);
        Setting::set('surat_kuasa_pemberi_1_jabatan', $request->pemberi_1_jabatan);
        Setting::set('surat_kuasa_pemberi_2_nama', $request->pemberi_2_nama);
        Setting::set('surat_kuasa_pemberi_2_jabatan', $request->pemberi_2_jabatan);
        Setting::set('surat_kuasa_pemberi_alamat', $request->pemberi_alamat);
        Setting::set('surat_kuasa_pemilik_nama', $request->pemilik_nama);
        Setting::set('surat_kuasa_pemilik_alamat', $request->pemilik_alamat ?? '');
        Setting::set('surat_kuasa_default_recipient_email', $request->default_recipient_email ?? '');

        return redirect()->route('settings')->with('success', 'Surat Kuasa settings updated successfully!');
    }

    /**
     * Update chart target settings
     */
    public function updateTargets(Request $request)
    {
        $request->validate([
            'target_in_stock_pct' => 'required|numeric|min:0|max:100',
            'target_active_rental_pct' => 'required|numeric|min:0|max:100',
            'target_in_service_pct' => 'required|numeric|min:0|max:100',
            'dashboard_layout' => 'required|in:kpi_progress,simple_stats',
            'target_subscription' => 'required|integer|min:0',
            'target_regular' => 'required|integer|min:0',
        ]);

        Setting::set('target_in_stock_pct', $request->target_in_stock_pct);
        Setting::set('target_active_rental_pct', $request->target_active_rental_pct);
        Setting::set('target_in_service_pct', $request->target_in_service_pct);
        Setting::set('dashboard_layout', $request->dashboard_layout);
        Setting::set('target_subscription', $request->target_subscription);
        Setting::set('target_regular', $request->target_regular);
        Setting::set('dashboard_show_history', $request->has('dashboard_show_history') ? 'true' : 'false');

        return redirect()->route('settings')->with('success', 'KPI targets updated successfully!');
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
            Setting::set('odoo_password', \Illuminate\Support\Facades\Crypt::encryptString($request->odoo_password));
        }

        return redirect()->route('settings')->with('success', 'Odoo connection settings updated!');
    }

    /**
     * Get target values as JSON for dashboard chart
     */
    public function getTargets()
    {
        return response()->json([
            // Percentage-based targets
            'in_stock_pct' => (float) Setting::get('target_in_stock_pct', 10),
            'active_rental_pct' => (float) Setting::get('target_active_rental_pct', 82),
            'in_service_pct' => (float) Setting::get('target_in_service_pct', 8),
            // Fixed targets
            'subscription' => (int) Setting::get('target_subscription', 1500),
            'regular' => (int) Setting::get('target_regular', 1000),
        ]);
    }
}
