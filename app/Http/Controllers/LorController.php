<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        if (!session('lor_authenticated')) {
            return view('lor.index', ['authenticated' => false]);
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
     * Authenticate for LoR page
     */
    public function authenticate(Request $request)
    {
        $password = $request->input('password');
        $storedPassword = Setting::get('lor_password', env('LOR_DEFAULT_PASSWORD', 'admin'));

        if ($password === $storedPassword) {
            session(['lor_authenticated' => true]);
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

        Setting::set('lor_password', $request->input('password'));

        return redirect()->back()->with('success', 'LoR password updated successfully.');
    }

    /**
     * Export LoR data to Excel
     */
    public function export(Request $request)
    {
        if (!session('lor_authenticated')) {
            return redirect()->route('lor.index');
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

        $currentRentals = $query->orderBy('current_customer')->orderBy('status')->orderBy('rental_id')->get();
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
        $filename = 'list_of_rented' . $customerSuffix . '_' . date('Y-m-d');

        if ($format === 'pdf') {
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            
            $dompdf = new \Dompdf\Dompdf($options);
            $html = view('exports.lor_pdf', [
                'rentals' => $currentRentals,
                'priceHistories' => $priceHistories,
                'includeNopol' => $includeNopol,
                'nopolHistories' => collect($nopolHistories),
                'taxMode' => $taxMode
            ])->render();
            
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
        $rentalId = $request->query('rental_id');
        $lotNumber = $request->query('lot_number');
        
        if (!$rentalId) {
            return response()->json(['success' => false, 'message' => 'Missing rental_id'], 400);
        }

        $odooService = app(\App\Services\OdooService::class);
        $summary = $odooService->fetchInvoicePeriodSummary(urldecode($rentalId), $lotNumber);

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}
