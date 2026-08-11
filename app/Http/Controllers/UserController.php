<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Item;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display list of users & branch permissions (IT Admin only)
     */
    public function index()
    {
        $users = User::orderBy('id', 'asc')->get();

        // Auto-detect all active warehouses/branches synced from Odoo
        $odooBranches = Item::withoutGlobalScope('exclude_order_only')
            ->whereNotNull('warehouse')
            ->where('warehouse', '!=', '')
            ->distinct()
            ->pluck('warehouse')
            ->sort()
            ->values();

        // Default Odoo Team Leader -> Sales Teams fallback matrix
        $defaultTeamsMap = [
            'Wahyu Waraka' => ['WW - WW - Inge', 'WW - WW - Nicko', 'WW - Fahmi - Aprilia', 'WW - Fahmi - Rofi'],
            'Adhitya' => ['AD - AD - Denny', 'AD - AD - Wira', 'AD - AD - Hakim'],
            'Kiki' => ['KI - Irwan - Ravellino', 'KI - Irwan - Irwan', 'KI - Erza - Nizam', 'KI - Erza - Erza'],
            'Fuji' => ['FJ - FJ - Hanna', 'FJ - FJ -Vico', 'FJ - FJ - Andreas'],
            'Suryanti' => ['SY - Ridho - Ridho', 'SY - Ridho - Kelvin', 'SY - Ridho - Keyzia', 'SY - Rizal - Rifai', 'SY - Rizal - Fikri'],
            'Bambang Sumantri' => ['BS-BS-Aldy', 'BS-BS-BS'],
            'Ahmad Sofwan Qudsy' => ['Tim Sofwan'],
            'Wahyu Iskandar' => ['Tim Wahyu Iskandar'],
        ];

        // Auto-detect all active Salespersons synced from Odoo
        $syncedSalespersons = Item::withoutGlobalScope('exclude_order_only')
            ->whereNotNull('salesperson')
            ->where('salesperson', '!=', '')
            ->distinct()
            ->pluck('salesperson')
            ->toArray();

        $odooSalespersons = collect(array_unique(array_merge(array_keys($defaultTeamsMap), $syncedSalespersons)))->sort()->values();

        // Auto-detect Salesperson -> Sales Teams mapping matrix
        $syncedMap = Item::withoutGlobalScope('exclude_order_only')
            ->whereNotNull('salesperson')
            ->where('salesperson', '!=', '')
            ->whereNotNull('sales_team')
            ->where('sales_team', '!=', '')
            ->select('salesperson', 'sales_team')
            ->distinct()
            ->get()
            ->groupBy('salesperson')
            ->map(fn($group) => $group->pluck('sales_team')->unique()->sort()->values()->toArray())
            ->toArray();

        $salespersonTeamsMap = collect($defaultTeamsMap)->map(function($teams, $sp) use ($syncedMap) {
            $merged = array_unique(array_merge($teams, $syncedMap[$sp] ?? []));
            sort($merged);
            return $merged;
        });

        // Add any additional synced salespersons not in default map
        foreach ($syncedMap as $sp => $teams) {
            if (!isset($salespersonTeamsMap[$sp])) {
                $salespersonTeamsMap[$sp] = $teams;
            }
        }

        // All distinct sales teams
        $syncedTeams = Item::withoutGlobalScope('exclude_order_only')
            ->whereNotNull('sales_team')
            ->where('sales_team', '!=', '')
            ->distinct()
            ->pluck('sales_team')
            ->toArray();

        $allDefaultTeams = [];
        foreach ($defaultTeamsMap as $teams) {
            $allDefaultTeams = array_merge($allDefaultTeams, $teams);
        }

        $allSalesTeams = collect(array_unique(array_merge($allDefaultTeams, $syncedTeams)))->sort()->values();

        return view('settings.users', [
            'users' => $users,
            'odooBranches' => $odooBranches,
            'odooSalespersons' => $odooSalespersons,
            'salespersonTeamsMap' => $salespersonTeamsMap,
            'allSalesTeams' => $allSalesTeams,
        ]);
    }

    /**
     * Create a new user account
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'branch' => 'required|string',
            'role' => 'required|in:it_admin,branch_user',
            'menu_permissions' => 'nullable|array',
            'can_view_lor_smd' => 'nullable|boolean',
            'allowed_salespersons' => 'nullable|array',
            'allowed_sales_teams' => 'nullable|array',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'branch' => strtoupper(trim($validated['branch'])),
            'role' => $validated['role'],
            'menu_permissions' => $request->has('menu_permissions') ? array_values(array_filter((array)$request->input('menu_permissions'))) : [],
            'can_view_lor_smd' => $request->has('can_view_lor_smd'),
            'allowed_salespersons' => $request->has('allowed_salespersons') ? array_values(array_filter($request->input('allowed_salespersons'))) : [],
            'allowed_sales_teams' => $request->has('allowed_sales_teams') ? array_values(array_filter($request->input('allowed_sales_teams'))) : [],
        ]);

        return redirect()->route('users.index')->with('success', 'User account created successfully!');
    }

    /**
     * Update an existing user account
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'branch' => 'required|string',
            'role' => 'required|in:it_admin,branch_user',
            'menu_permissions' => 'nullable|array',
            'can_view_lor_smd' => 'nullable|boolean',
            'allowed_salespersons' => 'nullable|array',
            'allowed_sales_teams' => 'nullable|array',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->branch = strtoupper(trim($validated['branch']));
        $user->role = $validated['role'];
        $user->menu_permissions = $request->has('menu_permissions') ? array_values(array_filter((array)$request->input('menu_permissions'))) : [];
        $user->can_view_lor_smd = $request->has('can_view_lor_smd');
        $user->allowed_salespersons = $request->has('allowed_salespersons') ? array_values(array_filter($request->input('allowed_salespersons'))) : [];
        $user->allowed_sales_teams = $request->has('allowed_sales_teams') ? array_values(array_filter($request->input('allowed_sales_teams'))) : [];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', "User {$user->name} updated successfully!");
    }

    /**
     * Delete user account
     */
    public function destroy($id)
    {
        if (auth()->id() == $id) {
            return back()->with('error', 'You cannot delete your own logged-in IT Admin account!');
        }

        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Switch active HQ branch filter (for IT Admin navbar switcher)
     */
    public function setHqBranch(Request $request)
    {
        $branch = $request->input('branch', 'ALL');
        session(['active_hq_branch' => strtoupper($branch)]);

        return back()->with('success', "Branch view switched to: {$branch}");
    }
}
