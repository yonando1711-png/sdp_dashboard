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

        return view('settings.users', [
            'users' => $users,
            'odooBranches' => $odooBranches,
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
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'branch' => strtoupper(trim($validated['branch'])),
            'role' => $validated['role'],
            'menu_permissions' => $request->has('menu_permissions') ? (array)$request->input('menu_permissions') : ['dashboard', 'total-stock', 'rental-pairs', 'in-stock', 'active-rentals', 'in-service'],
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
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->branch = strtoupper(trim($validated['branch']));
        $user->role = $validated['role'];
        $user->menu_permissions = $request->has('menu_permissions') ? (array)$request->input('menu_permissions') : [];

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
