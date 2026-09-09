<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Setting;
use App\Models\Item;
use App\Services\OdooService;
use App\Exports\DisposalExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class DisposalController extends Controller
{
    /**
     * Display Disposal Fleet Lifecycle Dashboard & Table
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasMenuPermission('disposal')) {
            abort(403, 'Unauthorized access to Disposal module.');
        }

        if (!$this->checkDisposalSession()) {
            return view('disposal.index', [
                'authenticated' => false,
                'session_expired' => session('session_expired', false),
            ]);
        }

        $search = trim((string) $request->input('search', ''));
        $statusFilter = trim((string) $request->input('status', 'all'));
        $sentAsFilter = trim((string) $request->input('sent_as', 'all'));
        $branchFilter = trim((string) $request->input('branch', ''));

        $query = Item::forUserBranch();

        // Branch scope for nationwide users
        if ($branchFilter && auth()->user()->isNationwide() && $branchFilter !== 'ALL') {
            $warehouses = auth()->user()->getBranchWarehouses($branchFilter);
            if ($warehouses) {
                $query->whereIn('warehouse', $warehouses);
            }
        }

        // Text Search
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('lot_number', 'like', "%{$search}%")
                    ->orWhere('product', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('first_rental_id', 'like', "%{$search}%")
                    ->orWhere('first_customer_name', 'like', "%{$search}%")
                    ->orWhere('rental_id', 'like', "%{$search}%")
                    ->orWhere('current_customer', 'like', "%{$search}%");
            });
        }

        // Sent As Filter
        if (in_array($sentAsFilter, ['ORIGINAL', 'RBO'], true)) {
            $query->where('first_sent_as', $sentAsFilter);
        }

        // Common condition definitions
        $fiveYearsAgo = now()->subYears(5)->toDateString();
        $fourAndHalfYearsAgo = now()->subMonths(54)->toDateString(); // 4.5 years = 54 months

        $isDisposed = function ($q) {
            $q->where('is_sold', true)
                ->orWhere('location', 'like', '%SOLD%')
                ->orWhere('location', 'like', '%DISPOSAL%');
        };

        $notDisposed = function ($q) {
            $q->where(function ($sub) {
                $sub->whereNull('is_sold')->orWhere('is_sold', false);
            })->where(function ($sub) {
                $sub->whereNull('location')
                    ->orWhere(function ($loc) {
                        $loc->where('location', 'not like', '%SOLD%')
                            ->where('location', 'not like', '%DISPOSAL%');
                    });
            });
        };

        // KPI Counts before applying status filter
        $kpiBase = clone $query;
        $kpis = [
            'total' => (clone $kpiBase)->count(),
            'due' => (clone $kpiBase)->where($notDisposed)->whereNotNull('first_start_sewa_date')->where('first_start_sewa_date', '<=', $fiveYearsAgo)->count(),
            'approaching' => (clone $kpiBase)->where($notDisposed)->whereNotNull('first_start_sewa_date')->where('first_start_sewa_date', '<=', $fourAndHalfYearsAgo)->where('first_start_sewa_date', '>', $fiveYearsAgo)->count(),
            'active' => (clone $kpiBase)->where($notDisposed)->whereNotNull('first_start_sewa_date')->where('first_start_sewa_date', '>', $fourAndHalfYearsAgo)->count(),
            'disposed' => (clone $kpiBase)->where($isDisposed)->count(),
            'never_rented' => (clone $kpiBase)->where($notDisposed)->whereNull('first_start_sewa_date')->count(),
        ];

        // Apply Status Filter
        if ($statusFilter === 'due') {
            $query->where($notDisposed)->whereNotNull('first_start_sewa_date')->where('first_start_sewa_date', '<=', $fiveYearsAgo);
        } elseif ($statusFilter === 'approaching') {
            $query->where($notDisposed)->whereNotNull('first_start_sewa_date')->where('first_start_sewa_date', '<=', $fourAndHalfYearsAgo)->where('first_start_sewa_date', '>', $fiveYearsAgo);
        } elseif ($statusFilter === 'active') {
            $query->where($notDisposed)->whereNotNull('first_start_sewa_date')->where('first_start_sewa_date', '>', $fourAndHalfYearsAgo);
        } elseif ($statusFilter === 'disposed') {
            $query->where($isDisposed);
        } elseif ($statusFilter === 'never_rented') {
            $query->where($notDisposed)->whereNull('first_start_sewa_date');
        }

        $items = $query->orderByRaw('CASE WHEN first_start_sewa_date IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('first_start_sewa_date', 'asc')
            ->orderBy('lot_number', 'asc')
            ->paginate(50)
            ->withQueryString();

        $pendingSyncCount = Item::forUserBranch()
            ->whereNotNull('lot_number')
            ->where('lot_number', '!=', '')
            ->whereNull('first_sent_as')
            ->count();

        return view('disposal.index', [
            'authenticated' => true,
            'items' => $items,
            'kpis' => $kpis,
            'pendingSyncCount' => $pendingSyncCount,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'sentAsFilter' => $sentAsFilter,
            'branchFilter' => $branchFilter,
        ]);
    }

    /**
     * Authenticate for Disposal page (PIN/Password gate)
     */
    public function authenticate(Request $request)
    {
        $password = (string) $request->input('password');
        $storedPassword = (string) Setting::get('disposal_password', 'admin');

        $isBcrypt = str_starts_with($storedPassword, '$2y$') || str_starts_with($storedPassword, '$2a$') || str_starts_with($storedPassword, '$2b$');

        if ($isBcrypt) {
            $isMatch = Hash::check($password, $storedPassword);
        } else {
            $isMatch = ($password === $storedPassword);
            if ($isMatch) {
                // Auto-upgrade legacy plaintext to Bcrypt hash
                Setting::set('disposal_password', Hash::make($password));
            }
        }

        if ($isMatch) {
            session([
                'disposal_authenticated' => true,
                'disposal_authenticated_at' => now()->timestamp,
            ]);
            return redirect()->route('disposal.index')->with('success', 'Disposal module unlocked successfully.');
        }

        return redirect()->back()->with('error', 'Incorrect secondary password.');
    }

    /**
     * Export Disposal fleet records to Excel
     */
    public function export(Request $request)
    {
        if (!auth()->user()->hasMenuPermission('disposal')) {
            abort(403, 'Unauthorized');
        }

        if (!$this->checkDisposalSession()) {
            abort(401, 'Session expired. Please unlock again.');
        }

        $search = trim((string) $request->input('search', ''));
        $statusFilter = trim((string) $request->input('status', 'all'));
        $sentAsFilter = trim((string) $request->input('sent_as', 'all'));
        $branchFilter = trim((string) $request->input('branch', ''));

        $query = Item::forUserBranch();

        if ($branchFilter && auth()->user()->isNationwide() && $branchFilter !== 'ALL') {
            $warehouses = auth()->user()->getBranchWarehouses($branchFilter);
            if ($warehouses) {
                $query->whereIn('warehouse', $warehouses);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('lot_number', 'like', "%{$search}%")
                    ->orWhere('product', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('first_rental_id', 'like', "%{$search}%")
                    ->orWhere('first_customer_name', 'like', "%{$search}%")
                    ->orWhere('rental_id', 'like', "%{$search}%")
                    ->orWhere('current_customer', 'like', "%{$search}%");
            });
        }

        if (in_array($sentAsFilter, ['ORIGINAL', 'RBO'], true)) {
            $query->where('first_sent_as', $sentAsFilter);
        }

        $fiveYearsAgo = now()->subYears(5)->toDateString();
        $fourAndHalfYearsAgo = now()->subMonths(54)->toDateString();

        $isDisposed = function ($q) {
            $q->where('is_sold', true)
                ->orWhere('location', 'like', '%SOLD%')
                ->orWhere('location', 'like', '%DISPOSAL%');
        };

        $notDisposed = function ($q) {
            $q->where(function ($sub) {
                $sub->whereNull('is_sold')->orWhere('is_sold', false);
            })->where(function ($sub) {
                $sub->whereNull('location')
                    ->orWhere(function ($loc) {
                        $loc->where('location', 'not like', '%SOLD%')
                            ->where('location', 'not like', '%DISPOSAL%');
                    });
            });
        };

        if ($statusFilter === 'due') {
            $query->where($notDisposed)->whereNotNull('first_start_sewa_date')->where('first_start_sewa_date', '<=', $fiveYearsAgo);
        } elseif ($statusFilter === 'approaching') {
            $query->where($notDisposed)->whereNotNull('first_start_sewa_date')->where('first_start_sewa_date', '<=', $fourAndHalfYearsAgo)->where('first_start_sewa_date', '>', $fiveYearsAgo);
        } elseif ($statusFilter === 'active') {
            $query->where($notDisposed)->whereNotNull('first_start_sewa_date')->where('first_start_sewa_date', '>', $fourAndHalfYearsAgo);
        } elseif ($statusFilter === 'disposed') {
            $query->where($isDisposed);
        } elseif ($statusFilter === 'never_rented') {
            $query->where($notDisposed)->whereNull('first_start_sewa_date');
        }

        $items = $query->orderByRaw('CASE WHEN first_start_sewa_date IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('first_start_sewa_date', 'asc')
            ->orderBy('lot_number', 'asc')
            ->get();

        $filename = 'Disposal_Fleet_Lifecycle_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new DisposalExport($items), $filename);
    }

    /**
     * Synchronize disposal movement data from Odoo
     */
    public function sync(Request $request)
    {
        if (!auth()->user()->hasMenuPermission('disposal')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$this->checkDisposalSession()) {
            return response()->json(['error' => 'Session expired. Please reload and unlock.'], 401);
        }

        set_time_limit(300);
        ini_set('memory_limit', '1024M');

        try {
            $odoo = app(OdooService::class);
            $force = (bool) $request->input('force', false);
            $lotNumber = $request->input('lot');
            $batchSize = max(10, min(200, (int) $request->input('batch_size', 100)));

            if ($lotNumber) {
                $lots = [$lotNumber];
                $totalPending = 1;
            } else {
                $query = Item::forUserBranch()
                    ->whereNotNull('lot_number')
                    ->where('lot_number', '!=', '');

                if (!$force) {
                    $query->whereNull('first_sent_as');
                }

                $totalPending = (clone $query)->count();
                $lots = $query->limit($batchSize)->pluck('lot_number')->toArray();
            }

            if (empty($lots)) {
                return response()->json([
                    'success' => true,
                    'message' => 'All vehicles are completely up to date.',
                    'updated' => 0,
                    'remaining' => 0,
                    'total_pending' => 0,
                    'done' => true,
                ]);
            }

            $results = $odoo->fetchFirstRentalMovements($lots);
            $updated = 0;
            $rentedCount = 0;

            foreach ($lots as $lot) {
                if (isset($results[$lot])) {
                    $data = $results[$lot];
                    Item::where('lot_number', $lot)->update([
                        'first_rental_id' => $data['rental_id'],
                        'first_start_sewa_date' => $data['date'],
                        'first_customer_name' => $data['customer'],
                        'first_sent_as' => $data['sent_as'],
                    ]);
                    $rentedCount++;
                } else {
                    Item::where('lot_number', $lot)->update([
                        'first_sent_as' => 'NONE',
                    ]);
                }
                $updated++;
            }

            $remainingCount = max(0, $totalPending - $updated);

            return response()->json([
                'success' => true,
                'message' => "Synchronized batch of {$updated} vehicles.",
                'updated' => $updated,
                'rented_in_batch' => $rentedCount,
                'remaining' => $remainingCount,
                'total_pending' => $totalPending,
                'done' => $remainingCount === 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update Disposal password in Settings
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:4'
        ]);

        Setting::set('disposal_password', Hash::make($request->input('password')));

        return redirect()->to(url('/import#disposal'))->with('success', 'Disposal password updated successfully.');
    }

    /**
     * Verify secondary session authentication (8 hour TTL)
     */
    private function checkDisposalSession(): bool
    {
        if (!session('disposal_authenticated')) {
            return false;
        }

        $lastAuth = session('disposal_authenticated_at');
        $timeoutSeconds = 8 * 3600; // 8 hours TTL per implementation plan

        if (!$lastAuth || (now()->timestamp - (int) $lastAuth) > $timeoutSeconds) {
            session()->forget(['disposal_authenticated', 'disposal_authenticated_at']);
            session()->flash('session_expired', true);
            return false;
        }

        session(['disposal_authenticated_at' => now()->timestamp]);
        return true;
    }
}
