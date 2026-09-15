<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Setting;
use App\Models\Item;
use App\Models\LorHistory;

class LorController extends Controller
{
    /**
     * Display the LoR page or password prompt
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasMenuPermission('lor')) {
            abort(403, 'Unauthorized access to LoR.');
        }

        if (!$this->checkLorSession()) {
            return view('lor.index', ['authenticated' => false, 'session_expired' => session('session_expired', false)]);
        }

        $search = $request->input('search');

        // Query active rentals from items table
        $query = Item::withoutGlobalScope('exclude_order_only')
                     ->forUserBranch()
                     ->whereNotNull('rental_id')
                     ->where('rental_id', '!=', '');
                     
        // Prevent duplicate rows for RBO: if there are multiple cars for this rental_id,
        // we only want to show the car that is currently at the customer (in_stock = false).
        // When the Original car is sent, it will become in_stock = false, and the dashboard will naturally switch to it.
        $query->where(function($q) {
            $q->where('rental_id_count', '<=', 1)
              ->orWhere('in_stock', false);
        });
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('rental_id', 'like', "%{$search}%")
                  ->orWhere('lot_number', 'like', "%{$search}%")
                  ->orWhere('current_customer', 'like', "%{$search}%")
                  ->orWhere('contract_ref', 'like', "%{$search}%");
            });
        }

        $currentRentals = $query->orderBy('current_customer')->orderBy('status')->orderBy('rental_id')->paginate(50);

        // Fetch history for these rentals
        $rentalIds = $currentRentals->pluck('rental_id')->toArray();
        
        // Group history by rental_id to track Nopol changes inline
        $histories = LorHistory::whereIn('rental_id', $rentalIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function($item) {
                return $item->rental_id;
            });

        // Get recent changes from the latest sync
        $latestSync = \App\Models\ImportLog::where('status', 'success')->latest()->first();
        $recentChanges = collect();
        $updatedKeys = [];
        $recentUpdatesList = collect();
        
        if ($latestSync) {
            // Query by import_log_id directly — no fragile time window needed
            $recentChanges = LorHistory::where('import_log_id', $latestSync->id)->get();
                
            $updatedKeys = $recentChanges->map(function($history) {
                return $history->rental_id;
            })->toArray();
            
            if ($recentChanges->count() > 0) {
                $currentItems = Item::withoutGlobalScope('exclude_order_only')
                    ->whereIn('rental_id', $recentChanges->pluck('rental_id'))
                    ->where(function($q) {
                        $q->where('rental_id_count', '<=', 1)
                          ->orWhere('in_stock', false);
                    })
                    ->get()
                    ->keyBy(function($i) { return $i->rental_id; });
                    
                foreach ($recentChanges as $history) {
                    $key = $history->rental_id;
                    $current = $currentItems->get($key);
                    $changedFields = [];
                    
                    if ($current) {
                        $fieldsToCheck = [
                            'lot_number' => 'Police-No',
                            'status' => 'Status',
                            'current_customer' => 'Customer',
                            'price' => 'Price',
                            'city' => 'Location',
                            'po' => 'PO',
                            'contract_ref' => 'Contract',
                            'actual_start_rental' => 'Start Date',
                            'actual_end_rental' => 'End Date',
                            'driver' => 'Driver'
                        ];
                        
                        foreach ($fieldsToCheck as $f => $label) {
                            $oldValRaw = $history->$f;
                            $newValRaw = $current->$f;
                            
                            $oldValCompare = $oldValRaw;
                            $newValCompare = $newValRaw;
                            
                            if (in_array($f, ['actual_start_rental', 'actual_end_rental'])) {
                                $oldValCompare = $oldValRaw ? \Carbon\Carbon::parse($oldValRaw)->toDateString() : null;
                                $newValCompare = $newValRaw ? \Carbon\Carbon::parse($newValRaw)->toDateString() : null;
                            }
                            
                            if ((string)$oldValCompare !== (string)$newValCompare) {
                                $dispOld = $oldValRaw;
                                $dispNew = $newValRaw;
                                
                                if (in_array($f, ['actual_start_rental', 'actual_end_rental'])) {
                                    $dispOld = $oldValRaw ? \Carbon\Carbon::parse($oldValRaw)->format('d M Y') : 'None';
                                    $dispNew = $newValRaw ? \Carbon\Carbon::parse($newValRaw)->format('d M Y') : 'None';
                                } elseif ($f === 'price') {
                                    $dispOld = $oldValRaw ? 'Rp ' . number_format((float)$oldValRaw, 0, ',', '.') : 'None';
                                    $dispNew = $newValRaw ? 'Rp ' . number_format((float)$newValRaw, 0, ',', '.') : 'None';
                                }
                                
                                $changedFields[$label] = [
                                    'old' => $dispOld,
                                    'new' => $dispNew
                                ];
                            }
                        }
                    }
                    
                    if (!empty($changedFields)) {
                        $recentUpdatesList->push([
                            'rental_id' => $history->rental_id,
                            'key' => $key,
                            'changes' => $changedFields
                        ]);
                    }
                }
            }
        }

        return view('lor.index', [
            'authenticated' => true,
            'currentRentals' => $currentRentals,
            'histories' => $histories,
            'search' => $search,
            'recentChanges' => $recentChanges,
            'recentUpdatesList' => $recentUpdatesList,
            'updatedKeys' => $updatedKeys
        ]);
    }

    /**
     * Display the LoR (SMD) page tab
     */
    public function indexSmd(Request $request)
    {
        $user = auth()->user();

        if (!$user->canAccessSmd()) {
            abort(403, 'Unauthorized access to LoR (SMD).');
        }

        $search = $request->input('search');
        $salespersonFilter = $request->input('salesperson');
        $salesTeamFilter = $request->input('sales_team');
        $customerFilter = $request->input('customer');
        $statusFilter = $request->input('status');
        $sortBy = $request->input('sort_by');
        $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $sortableColumns = [
            'rental_id' => 'rental_id',
            'salesperson' => 'salesperson',
            'sales_team' => 'sales_team',
            'customer' => 'current_customer',
            'unit' => 'lot_number',
            'product' => 'product',
            'lokasi' => 'city',
            'start_sewa' => 'actual_start_rental',
            'end_sewa' => 'actual_end_rental',
            'last_invoice' => 'last_invoice_date',
            'harga' => 'price',
            'total_harga' => 'total_price',
            'status' => 'status',
            'contract' => 'contract_ref',
            'type' => 'rental_type',
        ];

        $query = Item::withoutGlobalScope('exclude_order_only')
                     ->forUserBranch()
                     ->whereNotNull('rental_id')
                     ->where('rental_id', '!=', '');

        $query->where(function($q) {
            $q->where('rental_id_count', '<=', 1)
              ->orWhere('in_stock', false);
        });

        // Apply User Access Scoping (Strict AND logic when both Salesperson & Sales Team are specified)
        $allowedSalespersons = $user->getAllowedSalespersons();
        $allowedSalesTeams = $user->getAllowedSalesTeams();

        if (!$user->isItAdmin()) {
            if (!empty($allowedSalespersons) && !empty($allowedSalesTeams)) {
                $query->whereIn('salesperson', $allowedSalespersons)
                      ->whereIn('sales_team', $allowedSalesTeams);
            } elseif (!empty($allowedSalespersons)) {
                $query->whereIn('salesperson', $allowedSalespersons);
            } elseif (!empty($allowedSalesTeams)) {
                $query->whereIn('sales_team', $allowedSalesTeams);
            } else {
                // Non-admin user with NO scoping assigned must see 0 records
                $query->whereRaw('1 = 0');
            }
        } elseif (!empty($allowedSalespersons) || !empty($allowedSalesTeams)) {
            if (!empty($allowedSalespersons) && !empty($allowedSalesTeams)) {
                $query->whereIn('salesperson', $allowedSalespersons)
                      ->whereIn('sales_team', $allowedSalesTeams);
            } elseif (!empty($allowedSalespersons)) {
                $query->whereIn('salesperson', $allowedSalespersons);
            } elseif (!empty($allowedSalesTeams)) {
                $query->whereIn('sales_team', $allowedSalesTeams);
            }
        }

        // Apply Request Filters
        if ($salespersonFilter) {
            $query->where('salesperson', $salespersonFilter);
        }

        if ($salesTeamFilter) {
            $query->where('sales_team', $salesTeamFilter);
        }

        if ($customerFilter) {
            $query->where('current_customer', $customerFilter);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('rental_id', 'like', "%{$search}%")
                  ->orWhere('lot_number', 'like', "%{$search}%")
                  ->orWhere('current_customer', 'like', "%{$search}%")
                  ->orWhere('contract_ref', 'like', "%{$search}%")
                  ->orWhere('product', 'like', "%{$search}%")
                  ->orWhere('salesperson', 'like', "%{$search}%")
                  ->orWhere('sales_team', 'like', "%{$search}%");
            });
        }

        // Apply Sorting
        if ($sortBy && isset($sortableColumns[$sortBy])) {
            $query->orderBy($sortableColumns[$sortBy], $sortDir);
            if ($sortableColumns[$sortBy] !== 'rental_id') {
                $query->orderBy('rental_id', 'asc');
            }
        } else {
            $query->orderBy('salesperson')->orderBy('sales_team')->orderBy('rental_id');
        }

        $currentRentals = $query->paginate(50)->withQueryString();

        // Fetch unique filter dropdown options restricted strictly to user's allowed scope
        $filterSpQuery = Item::withoutGlobalScope('exclude_order_only')
            ->forUserBranch()
            ->whereNotNull('salesperson')->where('salesperson', '!=', '');
            
        $filterTeamQuery = Item::withoutGlobalScope('exclude_order_only')
            ->forUserBranch()
            ->whereNotNull('sales_team')->where('sales_team', '!=', '');

        $filterCustQuery = Item::withoutGlobalScope('exclude_order_only')
            ->forUserBranch()
            ->whereNotNull('current_customer')->where('current_customer', '!=', '');

        if (!$user->isItAdmin()) {
            if (!empty($allowedSalespersons)) {
                $filterSpQuery->whereIn('salesperson', $allowedSalespersons);
                $filterCustQuery->whereIn('salesperson', $allowedSalespersons);
            } else {
                $filterSpQuery->whereRaw('1 = 0');
                $filterCustQuery->whereRaw('1 = 0');
            }

            if (!empty($allowedSalesTeams)) {
                $filterTeamQuery->whereIn('sales_team', $allowedSalesTeams);
                $filterCustQuery->whereIn('sales_team', $allowedSalesTeams);
            } elseif (!empty($allowedSalespersons)) {
                $filterTeamQuery->whereIn('salesperson', $allowedSalespersons);
            } else {
                $filterTeamQuery->whereRaw('1 = 0');
            }
        } else {
            if (!empty($allowedSalespersons)) {
                $filterSpQuery->whereIn('salesperson', $allowedSalespersons);
                $filterTeamQuery->whereIn('salesperson', $allowedSalespersons);
                $filterCustQuery->whereIn('salesperson', $allowedSalespersons);
            }
            if (!empty($allowedSalesTeams)) {
                $filterSpQuery->whereIn('sales_team', $allowedSalesTeams);
                $filterTeamQuery->whereIn('sales_team', $allowedSalesTeams);
                $filterCustQuery->whereIn('sales_team', $allowedSalesTeams);
            }
        }

        $filterSalespersons = $filterSpQuery->distinct()->pluck('salesperson')->sort()->values();
        $filterSalesTeams = $filterTeamQuery->distinct()->pluck('sales_team')->sort()->values();
        $filterCustomers = $filterCustQuery->distinct()->pluck('current_customer')->sort()->values();

        return view('lor.smd', [
            'authenticated' => true,
            'currentRentals' => $currentRentals,
            'filterSalespersons' => $filterSalespersons,
            'filterSalesTeams' => $filterSalesTeams,
            'filterCustomers' => $filterCustomers,
            'salespersonFilter' => $salespersonFilter,
            'salesTeamFilter' => $salesTeamFilter,
            'customerFilter' => $customerFilter,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
        ]);
    }

    /**
     * Authenticate for LoR page
     */
    public function authenticate(Request $request)
    {
        $password = (string) $request->input('password');
        $storedPassword = (string) Setting::get('lor_password', env('LOR_DEFAULT_PASSWORD', 'admin'));

        $isBcrypt = str_starts_with($storedPassword, '$2y$') || str_starts_with($storedPassword, '$2a$') || str_starts_with($storedPassword, '$2b$');

        if ($isBcrypt) {
            $isMatch = Hash::check($password, $storedPassword);
        } else {
            $isMatch = ($password === $storedPassword);
            if ($isMatch) {
                // Auto-upgrade legacy plaintext to Bcrypt hash
                Setting::set('lor_password', Hash::make($password));
            }
        }

        if ($isMatch) {
            session([
                'lor_authenticated' => true,
                'lor_authenticated_at' => now()->timestamp,
            ]);
            return redirect()->route('lor.index')->with('success', 'LoR unlocked successfully.');
        }

        return redirect()->back()->with('error', 'Incorrect password.');
    }

    /**
     * Update LoR password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:4'
        ]);

        Setting::set('lor_password', Hash::make($request->input('password')));

        return redirect()->back()->with('success', 'LoR password updated successfully.');
    }

    /**
     * Export LoR data to Excel
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        $isSmd = ($request->input('source') === 'smd');

        if ($isSmd) {
            if (!$user || !$user->canExportSmd()) {
                abort(403, 'You do not have permission to export LoR (SMD) data.');
            }
        } else {
            if (!$this->checkLorSession()) {
                return redirect()->route('lor.index');
            }
        }

        $search = $request->input('search');
        $format = $request->input('format');
        
        $statuses = $request->input('statuses', []);
        $selectedStatuses = array_keys(array_filter($statuses));
        
        $includeNopol = $request->input('include_nopol') == '1';
        $taxMode = $request->input('tax_mode', 'original');

        $query = Item::withoutGlobalScope('exclude_order_only')
                     ->forUserBranch()
                     ->whereNotNull('rental_id')
                     ->where('rental_id', '!=', '');
                     
        $query->where(function($q) {
            $q->where('rental_id_count', '<=', 1)
              ->orWhere('in_stock', false);
        });

        if ($isSmd && $user) {
            $allowedSalespersons = $user->getAllowedSalespersons();
            $allowedSalesTeams = $user->getAllowedSalesTeams();
            if (!$user->isItAdmin()) {
                if (!empty($allowedSalespersons) && !empty($allowedSalesTeams)) {
                    $query->whereIn('salesperson', $allowedSalespersons)
                          ->whereIn('sales_team', $allowedSalesTeams);
                } elseif (!empty($allowedSalespersons)) {
                    $query->whereIn('salesperson', $allowedSalespersons);
                } elseif (!empty($allowedSalesTeams)) {
                    $query->whereIn('sales_team', $allowedSalesTeams);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } elseif (!empty($allowedSalespersons) || !empty($allowedSalesTeams)) {
                if (!empty($allowedSalespersons) && !empty($allowedSalesTeams)) {
                    $query->whereIn('salesperson', $allowedSalespersons)
                          ->whereIn('sales_team', $allowedSalesTeams);
                } elseif (!empty($allowedSalespersons)) {
                    $query->whereIn('salesperson', $allowedSalespersons);
                } elseif (!empty($allowedSalesTeams)) {
                    $query->whereIn('sales_team', $allowedSalesTeams);
                }
            }

            // Apply SMD Request Filters
            if ($request->filled('salesperson')) {
                $query->where('salesperson', $request->input('salesperson'));
            }
            if ($request->filled('sales_team')) {
                $query->where('sales_team', $request->input('sales_team'));
            }
            if ($request->filled('customer')) {
                $query->where('current_customer', $request->input('customer'));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
        }
        
        if (!empty($selectedStatuses)) {
            $query->whereIn('status', $selectedStatuses);
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('rental_id', 'like', "%{$search}%")
                  ->orWhere('lot_number', 'like', "%{$search}%")
                  ->orWhere('current_customer', 'like', "%{$search}%")
                  ->orWhere('contract_ref', 'like', "%{$search}%");
            });
        }

        if ($isSmd && $request->filled('sort_by')) {
            $sortableColumns = [
                'rental_id' => 'rental_id',
                'salesperson' => 'salesperson',
                'sales_team' => 'sales_team',
                'customer' => 'current_customer',
                'unit' => 'lot_number',
                'product' => 'product',
                'lokasi' => 'city',
                'start_sewa' => 'actual_start_rental',
                'end_sewa' => 'actual_end_rental',
                'last_invoice' => 'last_invoice_date',
                'harga' => 'price',
                'total_harga' => 'total_price',
                'status' => 'status',
                'contract' => 'contract_ref',
                'type' => 'rental_type',
            ];
            $sortColKey = $request->input('sort_by');
            if (isset($sortableColumns[$sortColKey])) {
                $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
                $query->orderBy($sortableColumns[$sortColKey], $sortDir);
                if ($sortableColumns[$sortColKey] !== 'rental_id') {
                    $query->orderBy('rental_id', 'asc');
                }
            } else {
                $query->orderBy('current_customer')->orderBy('status')->orderBy('rental_id');
            }
        } else {
            $query->orderBy('current_customer')->orderBy('status')->orderBy('rental_id');
        }

        $currentRentals = $query->get();
        $rentalIds = $currentRentals->pluck('rental_id')->toArray();
        
        // Fetch bulk price histories only for active rentals (not Returned)
        $activeRentalIds = $currentRentals->where('status', '!=', 'Returned')->pluck('rental_id')->toArray();
        
        $odooService = app(\App\Services\OdooService::class);
        $priceHistories = $odooService->fetchBulkInvoicePeriodSummary($activeRentalIds);
        
        $nopolHistories = [];
        if ($includeNopol) {
            $nopolHistories = \App\Models\LorHistory::whereIn('rental_id', $rentalIds)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('rental_id');
        }

        $export = new \App\Exports\LorExport($currentRentals, $priceHistories, $includeNopol, $nopolHistories, $taxMode);

        $customerSuffix = '';
        if ($currentRentals->count() > 0) {
            $uniqueCustomers = $currentRentals->pluck('current_customer')->filter()->unique();
            if ($uniqueCustomers->count() === 1) {
                $rawCust = $uniqueCustomers->first();
                if (preg_match('/\[(.*?)\]/', $rawCust, $m)) {
                    $customerSuffix = '_' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $m[1]));
                } else {
                    $cleanName = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', trim($rawCust)));
                    $customerSuffix = '_' . trim($cleanName, '_');
                }
            } elseif ($search) {
                $customerSuffix = '_' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $search));
            }
        }
        $filename = ($isSmd ? 'list_of_rented_smd' : 'list_of_rented') . $customerSuffix . '_' . date('Y-m-d');

        if ($format === 'pdf') {
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            
            $dompdf = new \Dompdf\Dompdf($options);

            if ($isSmd) {
                $html = view('exports.lor_smd_pdf', [
                    'rentals' => $currentRentals,
                    'canViewLastInvoiceDate' => (bool) $user?->canViewSmdLastInvoiceDate(),
                    'search' => $search,
                    'salespersonFilter' => $request->input('salesperson'),
                    'salesTeamFilter' => $request->input('sales_team'),
                    'customerFilter' => $request->input('customer'),
                    'statusFilter' => $request->input('status'),
                ])->render();
            } else {
                $html = view('exports.lor_pdf', [
                    'rentals' => $currentRentals,
                    'priceHistories' => $priceHistories,
                    'includeNopol' => $includeNopol,
                    'nopolHistories' => collect($nopolHistories),
                    'taxMode' => $taxMode
                ])->render();
            }
            
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            
            return response()->streamDownload(
                fn () => print($dompdf->output()),
                $filename . '.pdf',
                ['Content-Type' => 'application/pdf']
            );
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            $export,
            $filename . '.xlsx'
        );
    }

    /**
     * Get full sync history for all rentals
     */
    public function getFullHistory()
    {
        if (!$this->checkLorSession()) {
            return response()->json(['error' => 'Unauthenticated or session expired'], 401);
        }
        $histories = \App\Models\LorHistory::orderBy('created_at', 'asc')->get()->groupBy(function($h) {
            return $h->rental_id;
        });
        
        $rentalIds = $histories->keys()->toArray();
        $currentItems = \App\Models\Item::withoutGlobalScope('exclude_order_only')
            ->whereIn('rental_id', $rentalIds)
            // Filter to only active rentals if there are multiple cars
            ->where(function($q) {
                $q->where('rental_id_count', '<=', 1)
                  ->orWhere('in_stock', false);
            })
            ->get()
            ->keyBy(function($i) { return $i->rental_id; });
            
        $fullHistory = collect();
        $fieldsToCheck = [
            'status' => 'Status',
            'current_customer' => 'Customer',
            'price' => 'Price',
            'city' => 'Location',
            'po' => 'PO',
            'contract_ref' => 'Contract',
            'actual_start_rental' => 'Start Date',
            'actual_end_rental' => 'End Date',
            'driver' => 'Driver',
            'lot_number' => 'Police-No'
        ];
        
        foreach ($histories as $key => $historyChain) {
            $states = $historyChain->values()->all();
            
            // Append current item as the final state if it exists
            $current = $currentItems->get($key);
            if ($current) {
                $states[] = $current;
            }
            
            // We need at least 2 states to compare
            if (count($states) < 2) continue;
            
            for ($i = 0; $i < count($states) - 1; $i++) {
                $oldState = $states[$i];
                $newState = $states[$i + 1];
                $changeTime = \Carbon\Carbon::parse($oldState->created_at);
                
                $changedFields = [];
                
                foreach ($fieldsToCheck as $f => $label) {
                    $oldValRaw = $oldState->$f;
                    $newValRaw = $newState->$f;
                    
                    $oldValCompare = $oldValRaw;
                    $newValCompare = $newValRaw;
                    
                    if (in_array($f, ['actual_start_rental', 'actual_end_rental'])) {
                        $oldValCompare = $oldValRaw ? \Carbon\Carbon::parse($oldValRaw)->toDateString() : null;
                        $newValCompare = $newValRaw ? \Carbon\Carbon::parse($newValRaw)->toDateString() : null;
                    }
                    
                    if ((string)$oldValCompare !== (string)$newValCompare) {
                        
                        // SMART FILTER: If Police-No changed, verify it was a text edit (5-year renewal) 
                        // and not a physical car swap (Switch Unit).
                        if ($f === 'lot_number') {
                            $oldMoveCount = $oldState->product_movement_count ?? 0;
                            $newMoveCount = $newState->product_movement_count ?? 0;
                            // If movement count changed, it's a car swap, so we skip logging it as a plate renewal.
                            if ($oldMoveCount != $newMoveCount) {
                                continue;
                            }
                        }

                        $dispOld = $oldValRaw;
                        $dispNew = $newValRaw;
                        
                        if (in_array($f, ['actual_start_rental', 'actual_end_rental'])) {
                            $dispOld = $oldValRaw ? \Carbon\Carbon::parse($oldValRaw)->format('d M Y') : 'None';
                            $dispNew = $newValRaw ? \Carbon\Carbon::parse($newValRaw)->format('d M Y') : 'None';
                        } elseif ($f === 'price') {
                            $dispOld = $oldValRaw ? 'Rp ' . number_format((float)$oldValRaw, 0, ',', '.') : 'None';
                            $dispNew = $newValRaw ? 'Rp ' . number_format((float)$newValRaw, 0, ',', '.') : 'None';
                        }
                        
                        $changedFields[$label] = [
                            'old' => $dispOld,
                            'new' => $dispNew
                        ];
                    }
                }
                
                if (!empty($changedFields)) {
                    $fullHistory->push([
                        'rental_id' => $oldState->rental_id,
                        'key' => $key,
                        // Use oldState->created_at for accurate historical time, 
                        // except if it's the very first sync, which we don't have.
                        'change_time' => $changeTime->format('d M Y, H:i'),
                        'change_timestamp' => $changeTime->timestamp,
                        'changes' => $changedFields
                    ]);
                }
            }
        }
        
        // Sort by most recent change first
        $sortedHistory = $fullHistory->sortByDesc('change_timestamp')->values()->all();
        
        return response()->json($sortedHistory);
    }

    /**
     * Get Rental Details (Invoice Periods Price History) from Odoo
     */
    public function getRentalDetails(Request $request)
    {
        if (!$this->checkLorSession()) {
            return response()->json(['error' => 'Unauthenticated or session expired'], 401);
        }
        $rentalId = $request->query('rental_id');
        $lotNumber = $request->query('lot_number');
        
        if (!$rentalId) {
            return response()->json(['success' => false, 'message' => 'Missing rental_id'], 400);
        }

        try {
            $odooService = app(\App\Services\OdooService::class);
            $summary = $odooService->fetchInvoicePeriodSummary(urldecode($rentalId), $lotNumber);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to load rental details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load details from Odoo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if current LoR secondary authentication is valid (under 15 minutes of inactivity).
     */
    private function checkLorSession(): bool
    {
        if (!session('lor_authenticated')) {
            return false;
        }

        $lastAuth = session('lor_authenticated_at');
        $timeoutSeconds = 15 * 60; // 15 minutes

        if (!$lastAuth || (now()->timestamp - (int)$lastAuth) > $timeoutSeconds) {
            session()->forget(['lor_authenticated', 'lor_authenticated_at']);
            session()->flash('session_expired', true);
            return false;
        }

        session(['lor_authenticated_at' => now()->timestamp]);
        return true;
    }

    /**
     * Display Early Termination (ET) Report under LoR (SMD)
     */
    public function etReport(Request $request)
    {
        $user = auth()->user();
        if (!$user->canViewEtReport()) {
            abort(403, 'Access Denied: Your account does not have permission to view ET Report.');
        }

        // Default date range: 1st of current month to end of month
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $salespersonFilter = $request->input('salesperson');
        $salesTeamFilter = $request->input('sales_team');

        $allowedSalespersons = $user->getAllowedSalespersons();
        $allowedSalesTeams = $user->getAllowedSalesTeams();

        $odooService = app(\App\Services\OdooService::class);
        $reportData = $odooService->fetchEarlyTerminationReport(
            $dateFrom,
            $dateTo,
            $allowedSalespersons,
            $allowedSalesTeams,
            $salespersonFilter,
            $salesTeamFilter,
            $user->isItAdmin()
        );

        // Filter dropdown options strictly matching user's scoping permissions
        $filterSpQuery = Item::withoutGlobalScope('exclude_order_only')
            ->forUserBranch()
            ->whereNotNull('salesperson')->where('salesperson', '!=', '');
            
        $filterTeamQuery = Item::withoutGlobalScope('exclude_order_only')
            ->forUserBranch()
            ->whereNotNull('sales_team')->where('sales_team', '!=', '');

        if (!$user->isItAdmin()) {
            if (!empty($allowedSalespersons)) {
                $filterSpQuery->whereIn('salesperson', $allowedSalespersons);
            } else {
                $filterSpQuery->whereRaw('1 = 0');
            }

            if (!empty($allowedSalesTeams)) {
                $filterTeamQuery->whereIn('sales_team', $allowedSalesTeams);
            } elseif (!empty($allowedSalespersons)) {
                $filterTeamQuery->whereIn('salesperson', $allowedSalespersons);
            } else {
                $filterTeamQuery->whereRaw('1 = 0');
            }
        }

        $availableSalespersons = $filterSpQuery->distinct()->pluck('salesperson')->sort()->values();
        $availableSalesTeams = $filterTeamQuery->distinct()->pluck('sales_team')->sort()->values();

        return view('lor.et_report', [
            'grouped' => $reportData['grouped'] ?? [],
            'rawItems' => $reportData['raw_items'] ?? [],
            'summary' => $reportData['summary'] ?? ['total_units' => 0, 'total_customers' => 0, 'total_teams' => 0],
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'salespersonFilter' => $salespersonFilter,
            'salesTeamFilter' => $salesTeamFilter,
            'availableSalespersons' => $availableSalespersons,
            'availableSalesTeams' => $availableSalesTeams,
        ]);
    }

    /**
     * Export Early Termination (ET) Report as Excel
     */
    public function exportEtReport(Request $request)
    {
        $user = auth()->user();
        if (!$user->canViewEtReport()) {
            abort(403, 'Access Denied: Your account does not have permission to view ET Report.');
        }

        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $salespersonFilter = $request->input('salesperson');
        $salesTeamFilter = $request->input('sales_team');

        $allowedSalespersons = $user->getAllowedSalespersons();
        $allowedSalesTeams = $user->getAllowedSalesTeams();

        $odooService = app(\App\Services\OdooService::class);
        $reportData = $odooService->fetchEarlyTerminationReport(
            $dateFrom,
            $dateTo,
            $allowedSalespersons,
            $allowedSalesTeams,
            $salespersonFilter,
            $salesTeamFilter,
            $user->isItAdmin()
        );

        $filename = 'ET_Report_' . $dateFrom . '_to_' . $dateTo . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\EtReportExport(
                $reportData['grouped'] ?? [], 
                $reportData['summary'] ?? ['total_units' => 0],
                $dateFrom,
                $dateTo
            ),
            $filename
        );
    }
}
