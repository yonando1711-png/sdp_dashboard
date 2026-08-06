<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Setting;
use App\Services\OdooService;

class CrmController extends Controller
{
    /**
     * Display the CRM page or password prompt
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasMenuPermission('crm')) {
            abort(403, 'Unauthorized access to CRM.');
        }

        // Check if user is authenticated for CRM and session is active
        if (!$this->checkCrmSession()) {
            return view('crm.index', ['authenticated' => false, 'session_expired' => session('session_expired', false)]);
        }

        // Get unique customers that have rentals
        $customers = \App\Models\Item::whereNotNull('rental_id')
            ->whereNotNull('current_customer')
            ->where('current_customer', '!=', '')
            ->where('current_customer', '!=', '-')
            ->where('is_company', true)
            ->select('current_customer as customer', 'pic_name', 'pic_email')
            ->distinct()
            ->orderBy('current_customer')
            ->get();

        // For each customer, get their rentals
        foreach ($customers as $c) {
            $c->rentals = \App\Models\Item::where('current_customer', $c->customer)
                ->whereNotNull('rental_id')
                ->select('rental_id', 'product', 'reserved_lot', 'rental_period_start', 'rental_period_end', 'lot_number')
                ->orderBy('rental_id')
                ->get();
        }

        return view('crm.index', [
            'authenticated' => true,
            'customers' => $customers
        ]);
    }

    /**
     * Authenticate for CRM page
     */
    public function authenticate(Request $request)
    {
        $password = (string) $request->input('password');
        $storedPassword = (string) Setting::get('crm_password', env('CRM_DEFAULT_PASSWORD', 'admin'));

        $isBcrypt = str_starts_with($storedPassword, '$2y$') || str_starts_with($storedPassword, '$2a$') || str_starts_with($storedPassword, '$2b$');

        if ($isBcrypt) {
            $isMatch = Hash::check($password, $storedPassword);
        } else {
            $isMatch = ($password === $storedPassword);
            if ($isMatch) {
                // Auto-upgrade legacy plaintext to Bcrypt hash
                Setting::set('crm_password', Hash::make($password));
            }
        }

        if ($isMatch) {
            session([
                'crm_authenticated' => true,
                'crm_authenticated_at' => now()->timestamp,
            ]);
            return redirect()->route('crm.index')->with('success', 'CRM unlocked successfully.');
        }

        return redirect()->back()->with('error', 'Incorrect password.');
    }

    /**
     * Display CRM Settings page
     */
    public function settings()
    {
        $isPasswordSet = Setting::get('crm_password') !== null;
        return view('crm.settings', compact('isPasswordSet'));
    }

    /**
     * Update CRM password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:4'
        ]);

        Setting::set('crm_password', Hash::make($request->input('password')));

        return redirect()->back()->with('success', 'CRM password updated successfully.');
    }

    /**
     * Check if current CRM secondary authentication is valid (under 15 minutes of inactivity).
     */
    private function checkCrmSession(): bool
    {
        if (!session('crm_authenticated')) {
            return false;
        }

        $lastAuth = session('crm_authenticated_at');
        $timeoutSeconds = 15 * 60; // 15 minutes

        if (!$lastAuth || (now()->timestamp - (int)$lastAuth) > $timeoutSeconds) {
            session()->forget(['crm_authenticated', 'crm_authenticated_at']);
            session()->flash('session_expired', true);
            return false;
        }

        session(['crm_authenticated_at' => now()->timestamp]);
        return true;
    }
}
