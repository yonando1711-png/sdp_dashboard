<?php

namespace App\Services;

use App\Models\LorSmdSoBatch;
use App\Models\LorSmdSoItem;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LorListSoService
{
    protected OdooService $odooService;

    public function __construct(OdooService $odooService)
    {
        $this->odooService = $odooService;
    }

    /**
     * Master list of Sales Managers and their labels
     */
    public static function getSalesManagers(): array
    {
        return [
            'Adhit'             => 'Adhit (Semua Tim Adhit)',
            'Bambang S'         => 'Bambang S (Semua Tim Bambang S)',
            'Fuji'              => 'Fuji (Semua Tim Fuji)',
            'Kiki'              => 'Kiki (Semua Tim Kiki)',
            'Sofwan'            => 'Sofwan',
            'Suryanti'          => 'Suryanti (Semua Tim Suryanti)',
            'Wahyu Iskandar'    => 'Wahyu Iskandar (Semua Tim Wahyu Iskandar)',
            'Wahyu Waraka (WW)' => 'Wahyu Waraka / WW (Semua Tim Wahyu W)',
            'Sales'             => 'Sales (General)',
        ];
    }

    /**
     * Sync latest SO movements and confirmations from Odoo for a specific date (default: today).
     * Catches both newly confirmed SOs (date_order) and updated/modified SOs (write_date).
     */
    public function syncFromOdoo(?string $dateFrom = null, ?string $dateTo = null, ?string $salesTeam = null): array
    {
        $todayStr = Carbon::today()->format('Y-m-d');
        $dateFrom = $dateFrom ?: $todayStr;
        $dateTo = $dateTo ?: $todayStr;

        // Convert local Jakarta date to UTC timestamps for Odoo queries
        $fromUtc = Carbon::parse($dateFrom . ' 00:00:00', 'Asia/Jakarta')->setTimezone('UTC')->format('Y-m-d H:i:s');
        $toUtc = Carbon::parse($dateTo . ' 23:59:59', 'Asia/Jakarta')->setTimezone('UTC')->format('Y-m-d H:i:s');

        // 1. Fetch Chatter Tracking Values for today on sale.order (Status, Return Date, Price, Terminate)
        $chatterTracks = [];
        try {
            $chatterTracks = $this->odooService->execute('mail.tracking.value', 'search_read', [
                [
                    ['mail_message_id.model', '=', 'sale.order'],
                    ['create_date', '>=', $fromUtc],
                    ['create_date', '<=', $toUtc],
                ]
            ], [
                'fields' => ['mail_message_id', 'field_id', 'old_value_char', 'new_value_char', 'old_value_datetime', 'new_value_datetime', 'old_value_float', 'new_value_float', 'create_date']
            ]);
        } catch (\Exception $e) {
            Log::warning('LorListSoService tracking values query failed: ' . $e->getMessage());
        }

        // Map tracking message to SO ID
        $msgIds = [];
        foreach ($chatterTracks as $t) {
            if (!empty($t['mail_message_id'][0])) {
                $msgIds[] = $t['mail_message_id'][0];
            }
        }

        $msgToSoId = [];
        if (!empty($msgIds)) {
            try {
                $msgs = $this->odooService->execute('mail.message', 'read', [array_values(array_unique($msgIds))], ['fields' => ['id', 'res_id']]);
                foreach ($msgs as $m) {
                    $msgToSoId[$m['id']] = $m['res_id'];
                }
            } catch (\Exception $e) {
                Log::warning('LorListSoService message mapping failed: ' . $e->getMessage());
            }
        }

        // Group chatter mutations by SO ID
        $soMutations = [];
        foreach ($chatterTracks as $t) {
            $mid = $t['mail_message_id'][0] ?? null;
            $soId = $msgToSoId[$mid] ?? null;
            if (!$soId) continue;
            $fieldName = is_array($t['field_id']) ? $t['field_id'][1] : $t['field_id'];
            $soMutations[$soId][] = [
                'field'     => $fieldName,
                'old_char'  => $t['old_value_char'] ?? null,
                'new_char'  => $t['new_value_char'] ?? null,
                'old_dt'    => $t['old_value_datetime'] ?? null,
                'new_dt'    => $t['new_value_datetime'] ?? null,
                'old_float' => $t['old_value_float'] ?? null,
                'new_float' => $t['new_value_float'] ?? null,
            ];
        }

        // 2. Fetch all target SO IDs: Confirmed today OR had Chatter mutations today
        $soIdsFromChatter = array_keys($soMutations);
        $domain = [
            ['name', 'like', 'R/'],
            ['state', 'in', ['sale', 'done', 'cancel']],
            '|',
            '&', ['date_order', '>=', $fromUtc], ['date_order', '<=', $toUtc],
            ['id', 'in', !empty($soIdsFromChatter) ? $soIdsFromChatter : [0]],
        ];

        // Safe fields confirmed to exist on sale.order
        $fields = [
            'id', 'name', 'client_order_ref', 'partner_id', 'date_order',
            'user_id', 'team_id', 'rental_type', 'rental_status',
            'actual_start_rental', 'actual_end_rental', 'rental_start_date',
            'rental_return_date', 'initial_end_date', 'is_terminate',
            'order_line', 'amount_total', 'origin', 'write_date',
            'previous_so_id', 'previous_customer_id'
        ];

        try {
            $orders = $this->odooService->execute('sale.order', 'search_read', [$domain], [
                'fields' => $fields,
                'limit'  => 500,
                'order'  => 'date_order desc',
            ]);
        } catch (\Exception $e) {
            Log::error('LorListSoService sync failed: ' . $e->getMessage());
            throw new \Exception('Failed to connect to Odoo: ' . $e->getMessage());
        }

        if (empty($orders)) {
            return ['synced' => 0, 'new' => 0, 'updated' => 0];
        }

        // Collect partner IDs for city lookup
        $partnerIds = [];
        $allLineIds = [];
        foreach ($orders as $order) {
            if (!empty($order['partner_id']) && is_array($order['partner_id'])) {
                $partnerIds[] = $order['partner_id'][0];
            }
            if (!empty($order['order_line'])) {
                $allLineIds = array_merge($allLineIds, (array)$order['order_line']);
            }
        }

        // Fetch partner cities in batch
        $partnerCities = [];
        if (!empty($partnerIds)) {
            try {
                $partners = $this->odooService->execute('res.partner', 'read', [
                    array_values(array_unique($partnerIds)),
                    ['id', 'city']
                ]);
                foreach ($partners as $p) {
                    $partnerCities[$p['id']] = $p['city'] ?? null;
                }
            } catch (\Exception $e) {
                Log::warning('LorListSoService partner city fetch failed: ' . $e->getMessage());
            }
        }

        // Fetch lines in chunks of 300
        $linesById = [];
        $lotIds = [];
        $lineChunks = array_chunk(array_unique($allLineIds), 300);
        $lineFields = [
            'id', 'order_id', 'product_id', 'name', 'reserved_lot_ids', 'pickedup_lot_ids',
            'duration_price', 'price_unit', 'price_subtotal', 'is_original'
        ];

        foreach ($lineChunks as $chunk) {
            try {
                $fetchedLines = $this->odooService->execute('sale.order.line', 'read', [$chunk], [
                    'fields' => $lineFields,
                ]);
                foreach ($fetchedLines as $l) {
                    $linesById[$l['id']] = $l;
                    foreach (['reserved_lot_ids', 'pickedup_lot_ids'] as $lf) {
                        if (!empty($l[$lf]) && is_array($l[$lf])) {
                            foreach ($l[$lf] as $lid) {
                                $lotIds[] = $lid;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('LorListSoService lines fetch failed for chunk: ' . $e->getMessage());
            }
        }

        // Batch resolve lot names (police number / unit code) from stock.lot
        $lotNames = [];
        if (!empty($lotIds)) {
            try {
                $lotChunks = array_chunk(array_values(array_unique($lotIds)), 300);
                foreach ($lotChunks as $lChunk) {
                    $lots = $this->odooService->execute('stock.lot', 'read', [$lChunk, ['id', 'name']]);
                    foreach ($lots as $lot) {
                        $lotNames[$lot['id']] = $lot['name'] ?? null;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('LorListSoService lot names fetch failed: ' . $e->getMessage());
            }
        }

        // Batch resolve dispatched move lines to detect RBO
        $allSoNames = array_filter(array_column($orders, 'name'));
        $dispatchedLotsBySo = [];
        if (!empty($allSoNames)) {
            try {
                $soChunks = array_chunk($allSoNames, 100);
                foreach ($soChunks as $soChunk) {
                    $moveLines = $this->odooService->execute('stock.move.line', 'search_read', [
                        [
                            ['origin', 'in', $soChunk],
                            ['state', '=', 'done'],
                            ['location_dest_id.complete_name', 'ilike', 'Rental'],
                        ]
                    ], [
                        'fields' => ['origin', 'lot_id', 'date'],
                        'order'  => 'date asc, id asc',
                    ]);
                    foreach ($moveLines as $ml) {
                        $orig = $ml['origin'];
                        if (!isset($dispatchedLotsBySo[$orig]) && !empty($ml['lot_id'])) {
                            $dispatchedLotsBySo[$orig] = [
                                'lot_id'   => is_array($ml['lot_id']) ? $ml['lot_id'][0] : $ml['lot_id'],
                                'lot_name' => is_array($ml['lot_id']) ? $ml['lot_id'][1] : $ml['lot_id'],
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('LorListSoService RBO move lines query failed: ' . $e->getMessage());
            }
        }

        $newCount = 0;
        $updatedCount = 0;

        foreach ($orders as $order) {
            $soName = $order['name'] ?? null;
            if (!$soName) continue;

            $teamName = is_array($order['team_id'] ?? null) ? $order['team_id'][1] : null;
            $salesperson = is_array($order['user_id'] ?? null) ? $order['user_id'][1] : null;
            $customerName = is_array($order['partner_id'] ?? null) ? $order['partner_id'][1] : null;
            $partnerId = is_array($order['partner_id'] ?? null) ? $order['partner_id'][0] : null;
            $customerCode = $order['client_order_ref'] ?? null;
            $area = $partnerId ? ($partnerCities[$partnerId] ?? null) : null;
            $rType = strtoupper((string)($order['rental_type'] ?? 'SUBSCRIPTION'));
            if (in_array($rType, ['REGULER', 'REGULAR'])) {
                $rentalType = 'REGULER';
            } else {
                $rentalType = 'SUBSCRIPTION';
            }

            // Find main vehicle line
            $orderLines = [];
            if (!empty($order['order_line'])) {
                foreach ((array)$order['order_line'] as $lid) {
                    if (isset($linesById[$lid])) {
                        $orderLines[] = $linesById[$lid];
                    }
                }
            }

            // Filter for vehicle line (exclude CADANGAN BIAYA or fee-only lines if vehicle exists)
            $mainLine = null;
            foreach ($orderLines as $l) {
                $pName = is_array($l['product_id'] ?? null) ? $l['product_id'][1] : '';
                if (stripos($pName, 'CADANGAN BIAYA') === false && stripos($pName, 'BIAYA ADMIN') === false) {
                    $mainLine = $l;
                    break;
                }
            }
            if (!$mainLine && !empty($orderLines)) {
                $mainLine = $orderLines[0];
            }

            // Extract vehicle details
            $productName = is_array($mainLine['product_id'] ?? null) ? $mainLine['product_id'][1] : null;
            $reservedLot = null;
            if (!empty($mainLine['reserved_lot_ids']) && is_array($mainLine['reserved_lot_ids'])) {
                $firstLotId = $mainLine['reserved_lot_ids'][0];
                $reservedLot = $lotNames[$firstLotId] ?? $firstLotId;
            } elseif (!empty($mainLine['pickedup_lot_ids']) && is_array($mainLine['pickedup_lot_ids'])) {
                $firstLotId = $mainLine['pickedup_lot_ids'][0];
                $reservedLot = $lotNames[$firstLotId] ?? $firstLotId;
            }

            $durationPrice = (float)($mainLine['duration_price'] ?? $mainLine['price_unit'] ?? 0);
            $totalPrice = (float)($order['amount_total'] ?? $mainLine['price_subtotal'] ?? 0);
            
            // RBO Detection: Check if unit dispatched differs from contracted reserved lot, or is_original is false
            $rbo = '-';
            $isOriginal = $mainLine['is_original'] ?? true;
            $reservedLotId = !empty($mainLine['reserved_lot_ids'][0]) ? $mainLine['reserved_lot_ids'][0] : null;

            if (isset($dispatchedLotsBySo[$soName])) {
                $dispatched = $dispatchedLotsBySo[$soName];
                if (($reservedLotId && $dispatched['lot_id'] != $reservedLotId) || !$isOriginal) {
                    $rbo = $dispatched['lot_name'];
                }
            } elseif (!$isOriginal) {
                if (!empty($mainLine['pickedup_lot_ids'][0])) {
                    $pickedLotId = $mainLine['pickedup_lot_ids'][0];
                    if ($pickedLotId != $reservedLotId) {
                        $rbo = $lotNames[$pickedLotId] ?? (string)$pickedLotId;
                    }
                }
            }

            // Order Date & Source Document
            $orderDate = !empty($order['date_order']) ? substr($order['date_order'], 0, 10) : null;
            $sourceDoc = !empty($order['origin']) ? $order['origin'] : ($order['client_order_ref'] ?? null);
            $prevSoFromOdoo = is_array($order['previous_so_id'] ?? null) ? $order['previous_so_id'][1] : ($order['previous_so_id'] ?? null);
            $prevCustFromOdoo = is_array($order['previous_customer_id'] ?? null) ? $order['previous_customer_id'][1] : ($order['previous_customer_id'] ?? null);

            // Effective Start Date
            $startDate = !empty($order['actual_start_rental']) 
                ? substr($order['actual_start_rental'], 0, 10) 
                : (!empty($order['rental_start_date']) ? substr($order['rental_start_date'], 0, 10) : null);

            // Effective End Date
            $endDate = !empty($order['rental_return_date']) 
                ? substr($order['rental_return_date'], 0, 10) 
                : (!empty($order['initial_end_date']) ? substr($order['initial_end_date'], 0, 10) : null);

            $actualEnd = !empty($order['actual_end_rental']) ? substr($order['actual_end_rental'], 0, 10) : null;

            // Duration in months (Subscription) or hours/days (Reguler)
            $durationMonths = null;
            if ($rentalType === 'REGULER') {
                if ($startDate && $endDate) {
                    try {
                        $startDt = Carbon::parse($order['actual_start_rental'] ?? $order['rental_start_date'] ?? $startDate);
                        $endDt = Carbon::parse($order['actual_end_rental'] ?? $order['rental_return_date'] ?? $endDate);
                        $diffHours = $startDt->diffInHours($endDt);
                        $diffDays = $startDt->diffInDays($endDt);
                        if ($diffHours < 24 && $diffHours > 0) {
                            $durationMonths = "{$diffHours} jam";
                        } elseif ($diffDays > 0) {
                            $durationMonths = "{$diffDays} hari";
                        } else {
                            $durationMonths = "1 hari";
                        }
                    } catch (\Exception $e) {
                        $durationMonths = '-';
                    }
                }
            } else {
                if ($startDate && $endDate) {
                    try {
                        $sCarbon = Carbon::parse($startDate);
                        $eCarbon = Carbon::parse($endDate)->addDay();
                        $diff = $sCarbon->diff($eCarbon);
                        $m = $diff->y * 12 + $diff->m;
                        $durationMonths = ($diff->d > 0 && $diff->d < 28) ? "{$m} MONTHS {$diff->d} DAYS" : "{$m} MONTHS";
                    } catch (\Exception $e) {
                        $durationMonths = null;
                    }
                }
            }

            // Inspect Chatter mutations for this specific SO
            $soId = $order['id'];
            $mutations = $soMutations[$soId] ?? [];
            $chatterExtend = null;
            $chatterUntaxed = null;
            $chatterPrice = null;
            $chatterTerminate = false;
            $chatterConfirmed = false;

            foreach ($mutations as $m) {
                // Return date extend
                if ((stripos($m['field'], 'Rental Return Date') !== false || stripos($m['field'], 'Return Date') !== false) && !empty($m['new_dt'])) {
                    $chatterExtend = [
                        'old_end' => !empty($m['old_dt']) ? substr($m['old_dt'], 0, 10) : null,
                        'new_end' => substr($m['new_dt'], 0, 10),
                    ];
                }

                // Untaxed Amount / Total tracking (Odoo tracking when price or duration changes)
                if ((stripos($m['field'], 'Untaxed') !== false || stripos($m['field'], 'amount_untaxed') !== false) && !empty($m['old_float']) && !empty($m['new_float'])) {
                    $chatterUntaxed = [
                        'old_untaxed' => (float)$m['old_float'],
                        'new_untaxed' => (float)$m['new_float'],
                    ];
                }

                // Direct Unit Price / Duration Price field tracking (if any)
                $isUnitPriceField = (stripos($m['field'], 'Duration Price') !== false 
                                 || stripos($m['field'], 'Price Unit') !== false 
                                 || stripos($m['field'], 'price_unit') !== false 
                                 || stripos($m['field'], 'Rental Price') !== false)
                                 && stripos($m['field'], 'Total') === false 
                                 && stripos($m['field'], 'Untaxed') === false;

                if ($isUnitPriceField && !empty($m['old_float']) && !empty($m['new_float'])) {
                    $chatterPrice = [
                        'old_price' => (float)$m['old_float'],
                        'new_price' => (float)$m['new_float'],
                    ];
                }

                // Termination / Cancel
                if (stripos($m['field'], 'is_terminate') !== false || ($m['new_char'] === 'Cancelled' || $m['new_char'] === 'cancel')) {
                    $chatterTerminate = true;
                }
                // Status confirmation
                if ((stripos($m['field'], 'Status') !== false || stripos($m['field'], 'state') !== false) 
                    && in_array(strtolower((string)$m['new_char']), ['sales order', 'sale', 'done', 'reserved'])) {
                    $chatterConfirmed = true;
                }
            }

            // Derive monthly price mutation if untaxed amount changed in chatter
            if (!$chatterPrice && $chatterUntaxed && $chatterUntaxed['new_untaxed'] > 0 && $durationPrice > 0) {
                $oldPrice = round($durationPrice * ($chatterUntaxed['old_untaxed'] / $chatterUntaxed['new_untaxed']));
                if ($oldPrice > 0 && $oldPrice !== (float)$durationPrice) {
                    $chatterPrice = [
                        'old_price' => $oldPrice,
                        'new_price' => $durationPrice,
                    ];
                }
            }

            $isConfirmedToday = $chatterConfirmed || (!empty($order['date_order']) && $order['date_order'] >= $fromUtc && $order['date_order'] <= $toUtc);
            $isTerminatedToday = $chatterTerminate || (!empty($order['is_terminate']) && !empty($order['actual_end_rental']) && $order['actual_end_rental'] >= $fromUtc && $order['actual_end_rental'] <= $toUtc);

            // Collect all matching events for today independently
            $eventsToSave = [];

            // 1. Newly Confirmed Today -> RESERVED (Category 1: Subscription or Category 2: Reguler)
            if ($isConfirmedToday) {
                $eventsToSave[] = [
                    'transaction_type'       => 'RESERVED',
                    'order_date'             => $orderDate,
                    'start_date'             => $startDate,
                    'end_date'               => $endDate,
                    'previous_end_date'      => null,
                    'duration_price'         => $durationPrice,
                    'previous_duration_price'=> null,
                    'actual_end_rental_date' => null,
                    'previous_so'            => $prevSoFromOdoo,
                    'previous_customer'      => null,
                    'previous_end_date_cust' => null,
                    'rbo'                    => $rbo,
                ];

                // Check if vehicle switched customer (Category 5: SWITCH_CUSTOMER)
                if ($rentalType === 'SUBSCRIPTION' && $reservedLot) {
                    $prevItem = LorSmdSoItem::where('reserved_lot', $reservedLot)
                                            ->where('so_number', '!=', $soName)
                                            ->latest()
                                            ->first();
                    if ($prevItem && $prevItem->customer_name && $prevItem->customer_name !== $customerName) {
                        $eventsToSave[] = [
                            'transaction_type'       => 'SWITCH_CUSTOMER',
                            'order_date'             => $orderDate,
                            'start_date'             => $startDate,
                            'end_date'               => $endDate,
                            'previous_end_date'      => null,
                            'duration_price'         => $durationPrice,
                            'previous_duration_price'=> null,
                            'actual_end_rental_date' => null,
                            'previous_so'            => $prevItem->so_number ?: $prevSoFromOdoo,
                            'previous_customer'      => $prevItem->customer_name ?: $prevCustFromOdoo,
                            'previous_end_date_cust' => $prevItem->end_date,
                        ];
                    }
                }
            }

            // 2. Extended Today -> EXTEND (Category 4: EXTEND & UPDATE PRICE)
            if ($chatterExtend) {
                $eventsToSave[] = [
                    'transaction_type'       => 'EXTEND',
                    'order_date'             => $orderDate,
                    'start_date'             => $startDate,
                    'end_date'               => $chatterExtend['new_end'] ?: $endDate,
                    'previous_end_date'      => $chatterExtend['old_end'] ?: ($order['initial_end_date'] ?? null),
                    'duration_price'         => $durationPrice,
                    'previous_duration_price'=> $chatterPrice['old_price'] ?? $durationPrice,
                    'actual_end_rental_date' => null,
                    'previous_so'            => $prevSoFromOdoo,
                    'previous_customer'      => null,
                    'previous_end_date_cust' => null,
                    'rbo'                    => $rbo,
                ];
            }

            // 3. Price Updated Today -> UPDATE_PRICE (Only if not already handled by EXTEND)
            if ($chatterPrice && !$chatterExtend) {
                $eventsToSave[] = [
                    'transaction_type'       => 'UPDATE_PRICE',
                    'order_date'             => $orderDate,
                    'start_date'             => $startDate,
                    'end_date'               => $endDate,
                    'previous_end_date'      => null,
                    'duration_price'         => $chatterPrice['new_price'],
                    'previous_duration_price'=> $chatterPrice['old_price'],
                    'actual_end_rental_date' => null,
                    'previous_so'            => $prevSoFromOdoo,
                    'previous_customer'      => null,
                    'previous_end_date_cust' => null,
                    'rbo'                    => $rbo,
                ];
            }

            // 4. Terminated or Force Closed Today -> TERMINATE (Category 3)
            if ($isTerminatedToday && !$isConfirmedToday) {
                $eventsToSave[] = [
                    'transaction_type'       => 'TERMINATE',
                    'order_date'             => $orderDate,
                    'start_date'             => $startDate,
                    'end_date'               => $endDate,
                    'previous_end_date'      => null,
                    'duration_price'         => $durationPrice,
                    'previous_duration_price'=> null,
                    'actual_end_rental_date' => $actualEnd ?: Carbon::today()->format('Y-m-d'),
                    'previous_so'            => $prevSoFromOdoo,
                    'previous_customer'      => null,
                    'previous_end_date_cust' => null,
                    'rbo'                    => $rbo,
                ];
            }

            foreach ($eventsToSave as $event) {
                $transType = $event['transaction_type'];

                // Check if an unprinted record for this exact SO and mutation values already exists
                $existingQuery = LorSmdSoItem::where('so_number', $soName)
                                            ->where('transaction_type', $transType);

                if (!empty($event['previous_end_date'])) {
                    $existingQuery->where('previous_end_date', $event['previous_end_date']);
                }
                if (!empty($event['end_date'])) {
                    $existingQuery->where('end_date', $event['end_date']);
                }
                if (!empty($event['actual_end_rental_date'])) {
                    $existingQuery->where('actual_end_rental_date', $event['actual_end_rental_date']);
                }
                if (!empty($event['previous_duration_price'])) {
                    $existingQuery->where('previous_duration_price', $event['previous_duration_price']);
                }

                $existing = $existingQuery->latest()->first();

                if ($existing) {
                    if ($existing->is_printed) {
                        // This exact mutation was already printed in a prior batch, do not duplicate
                        continue;
                    }

                    $existing->update([
                        'rental_type'            => $rentalType,
                        'transaction_type'       => $transType,
                        'sales_team'             => $teamName,
                        'salesperson'            => $salesperson,
                        'customer_code'          => $customerCode,
                        'customer_name'          => $customerName,
                        'duration_months'        => $durationMonths,
                        'order_date'             => $event['order_date'] ?? $orderDate,
                        'start_date'             => $event['start_date'],
                        'end_date'               => $event['end_date'],
                        'previous_end_date'      => $event['previous_end_date'] ?? $existing->previous_end_date,
                        'actual_end_rental_date' => $event['actual_end_rental_date'] ?? $existing->actual_end_rental_date,
                        'product'                => $productName,
                        'reserved_lot'           => $reservedLot,
                        'duration_price'         => $event['duration_price'],
                        'previous_duration_price'=> $event['previous_duration_price'] ?? $existing->previous_duration_price,
                        'total_price'            => $totalPrice,
                        'previous_so'            => $event['previous_so'] ?? $prevSoFromOdoo ?? $existing->previous_so,
                        'previous_customer'      => $event['previous_customer'] ?? $prevCustFromOdoo ?? $existing->previous_customer,
                        'previous_end_date_cust' => $event['previous_end_date_cust'] ?? $existing->previous_end_date_cust,
                        'area'                   => $area,
                        'rbo'                    => ($rbo !== '-') ? $rbo : $existing->rbo,
                        'source_doc'             => $sourceDoc,
                    ]);
                    $updatedCount++;
                } else {
                    // Create a distinct new mutation row with its own timestamp
                    LorSmdSoItem::create([
                        'so_number'              => $soName,
                        'rental_type'            => $rentalType,
                        'transaction_type'       => $transType,
                        'sales_team'             => $teamName,
                        'salesperson'            => $salesperson,
                        'customer_code'          => $customerCode,
                        'customer_name'          => $customerName,
                        'duration_months'        => $durationMonths,
                        'order_date'             => $event['order_date'] ?? $orderDate,
                        'start_date'             => $event['start_date'],
                        'end_date'               => $event['end_date'],
                        'previous_end_date'      => $event['previous_end_date'],
                        'actual_end_rental_date' => $event['actual_end_rental_date'],
                        'product'                => $productName,
                        'reserved_lot'           => $reservedLot,
                        'rbo'                    => $event['rbo'] ?? $rbo,
                        'previous_so'            => $event['previous_so'] ?? $prevSoFromOdoo,
                        'previous_customer'      => $event['previous_customer'] ?? $prevCustFromOdoo,
                        'previous_end_date_cust' => $event['previous_end_date_cust'],
                        'area'                   => $area,
                        'duration_price'         => $event['duration_price'],
                        'previous_duration_price'=> $event['previous_duration_price'],
                        'total_price'            => $totalPrice,
                        'komisi'                 => 0,
                        'opsi_beli'              => '-',
                        'tags'                   => $salesperson,
                        'source_doc'             => $sourceDoc,
                        'is_printed'             => false,
                    ]);
                    $newCount++;
                }
            }
        }

        return [
            'synced'  => count($orders),
            'new'     => $newCount,
            'updated' => $updatedCount,
        ];
    }

    /**
     * Get unprinted items grouped by the 6 Excel transaction categories.
     * Supports filtering by Sales Manager (which includes all teams under that manager).
     */
    public function getUnprintedGrouped(?string $salesManager = null, ?User $user = null): array
    {
        $query = LorSmdSoItem::unprinted();

        if (!empty($salesManager) && $salesManager !== 'all') {
            if ($salesManager === 'Wahyu Waraka (WW)' || $salesManager === 'WW' || stripos($salesManager, 'Wahyu W') !== false) {
                $query->where(function($q) {
                    $q->where('sales_team', 'like', 'Wahyu W%')->orWhere('sales_team', 'WW');
                });
            } elseif ($salesManager === 'Sofwan') {
                $query->where('sales_team', 'like', '%Sofwan%');
            } elseif ($salesManager === 'Wahyu Iskandar') {
                $query->where('sales_team', 'like', '%Wahyu Iskandar%');
            } elseif ($salesManager === 'Sales') {
                $query->where('sales_team', 'like', 'Sales%');
            } else {
                $query->where('sales_team', 'like', $salesManager . '%');
            }
        }

        // Apply User Scope if not admin
        if ($user && !$user->isItAdmin()) {
            $allowedTeams = $user->getAllowedSalesTeams();
            $allowedPersons = $user->getAllowedSalespersons();
            if (!empty($allowedTeams) && !empty($allowedPersons)) {
                $query->whereIn('sales_team', $allowedTeams)->whereIn('salesperson', $allowedPersons);
            } elseif (!empty($allowedTeams)) {
                $query->whereIn('sales_team', $allowedTeams);
            } elseif (!empty($allowedPersons)) {
                $query->whereIn('salesperson', $allowedPersons);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $items = $query->orderBy('start_date', 'asc')->get();

        return [
            'reserved_subscription'   => $items->where('transaction_type', 'RESERVED')->where('rental_type', 'SUBSCRIPTION')->values(),
            'reserved_reguler'        => $items->where('transaction_type', 'RESERVED')->where('rental_type', 'REGULER')->values(),
            'terminate'               => $items->where('transaction_type', 'TERMINATE')->values(),
            'extend_and_update_price' => $items->whereIn('transaction_type', ['EXTEND', 'UPDATE_PRICE'])->values(),
            'extend'                  => $items->where('transaction_type', 'EXTEND')->values(),
            'update_price'            => $items->where('transaction_type', 'UPDATE_PRICE')->values(),
            'switch_customer'         => $items->where('transaction_type', 'SWITCH_CUSTOMER')->values(),
            'total_count'             => $items->count(),
            'all_items'               => $items,
        ];
    }

    /**
     * Generate PDF Preview string without creating a batch or updating is_printed status.
     * Optionally filtered by specific selected item IDs.
     */
    public function generatePreviewPdf(?string $salesManager = null, ?User $user = null, ?array $itemIds = null): string
    {
        $query = LorSmdSoItem::unprinted();

        if (!empty($itemIds)) {
            $query->whereIn('id', $itemIds);
        }

        if (!empty($salesManager) && $salesManager !== 'all') {
            if ($salesManager === 'Wahyu Waraka (WW)' || $salesManager === 'WW' || stripos($salesManager, 'Wahyu W') !== false) {
                $query->where(function($q) {
                    $q->where('sales_team', 'like', 'Wahyu W%')->orWhere('sales_team', 'WW');
                });
            } elseif ($salesManager === 'Sofwan') {
                $query->where('sales_team', 'like', '%Sofwan%');
            } elseif ($salesManager === 'Wahyu Iskandar') {
                $query->where('sales_team', 'like', '%Wahyu Iskandar%');
            } elseif ($salesManager === 'Sales') {
                $query->where('sales_team', 'like', 'Sales%');
            } else {
                $query->where('sales_team', 'like', $salesManager . '%');
            }
        }

        if ($user && !$user->isItAdmin()) {
            $allowedTeams = $user->getAllowedSalesTeams();
            $allowedPersons = $user->getAllowedSalespersons();
            if (!empty($allowedTeams) && !empty($allowedPersons)) {
                $query->whereIn('sales_team', $allowedTeams)->whereIn('salesperson', $allowedPersons);
            } elseif (!empty($allowedTeams)) {
                $query->whereIn('sales_team', $allowedTeams);
            } elseif (!empty($allowedPersons)) {
                $query->whereIn('salesperson', $allowedPersons);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $items = $query->orderBy('start_date', 'asc')->get();
        $teamName = $salesManager && $salesManager !== 'all' ? $salesManager : 'ALL TEAMS';

        $batch = (object)[
            'batch_no'        => 'DRAFT-PREVIEW',
            'printed_by_name' => $user ? $user->name : 'System Preview',
            'printed_at'      => Carbon::now(),
            'total_so'        => $items->count(),
            'print_count'     => 1,
        ];

        $viewData = [
            'reserved_subscription'   => $items->where('transaction_type', 'RESERVED')->where('rental_type', 'SUBSCRIPTION')->values(),
            'reserved_reguler'        => $items->where('transaction_type', 'RESERVED')->where('rental_type', 'REGULER')->values(),
            'terminate'               => $items->where('transaction_type', 'TERMINATE')->values(),
            'extend_and_update_price' => $items->whereIn('transaction_type', ['EXTEND', 'UPDATE_PRICE'])->values(),
            'extend'                  => $items->where('transaction_type', 'EXTEND')->values(),
            'update_price'            => $items->where('transaction_type', 'UPDATE_PRICE')->values(),
            'switch_customer'         => $items->where('transaction_type', 'SWITCH_CUSTOMER')->values(),
            'batch'                   => $batch,
            'sales_team'              => $teamName,
            'printed_at'              => Carbon::now()->format('d/m/Y H:i'),
            'duplicate_count'         => 0,
            'is_duplicate'            => false,
        ];

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);

        $html = view('lor.list_so.pdf', $viewData)->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Generate HTML Preview string without PDF encoding, perfectly immune to browser toolbars.
     */
    public function generatePreviewHtml(?string $salesManager = null, ?User $user = null, ?array $itemIds = null): string
    {
        $query = LorSmdSoItem::unprinted();

        if (!empty($itemIds)) {
            $query->whereIn('id', $itemIds);
        }

        if (!empty($salesManager) && $salesManager !== 'all') {
            if ($salesManager === 'Wahyu Waraka (WW)' || $salesManager === 'WW' || stripos($salesManager, 'Wahyu W') !== false) {
                $query->where(function($q) {
                    $q->where('sales_team', 'like', 'Wahyu W%')->orWhere('sales_team', 'WW');
                });
            } elseif ($salesManager === 'Sofwan') {
                $query->where('sales_team', 'like', '%Sofwan%');
            } elseif ($salesManager === 'Wahyu Iskandar') {
                $query->where('sales_team', 'like', '%Wahyu Iskandar%');
            } elseif ($salesManager === 'Sales') {
                $query->where('sales_team', 'like', 'Sales%');
            } else {
                $query->where('sales_team', 'like', $salesManager . '%');
            }
        }

        if ($user && !$user->isItAdmin()) {
            $allowedTeams = $user->getAllowedSalesTeams();
            $allowedPersons = $user->getAllowedSalespersons();
            if (!empty($allowedTeams) && !empty($allowedPersons)) {
                $query->whereIn('sales_team', $allowedTeams)->whereIn('salesperson', $allowedPersons);
            } elseif (!empty($allowedTeams)) {
                $query->whereIn('sales_team', $allowedTeams);
            } elseif (!empty($allowedPersons)) {
                $query->whereIn('salesperson', $allowedPersons);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $items = $query->orderBy('start_date', 'asc')->get();
        $teamName = $salesManager && $salesManager !== 'all' ? $salesManager : 'ALL TEAMS';

        $batch = (object)[
            'batch_no'        => 'DRAFT-PREVIEW',
            'printed_by_name' => $user ? $user->name : 'System Preview',
            'printed_at'      => Carbon::now(),
            'total_so'        => $items->count(),
            'print_count'     => 1,
        ];

        $viewData = [
            'reserved_subscription'   => $items->where('transaction_type', 'RESERVED')->where('rental_type', 'SUBSCRIPTION')->values(),
            'reserved_reguler'        => $items->where('transaction_type', 'RESERVED')->where('rental_type', 'REGULER')->values(),
            'terminate'               => $items->where('transaction_type', 'TERMINATE')->values(),
            'extend_and_update_price' => $items->whereIn('transaction_type', ['EXTEND', 'UPDATE_PRICE'])->values(),
            'extend'                  => $items->where('transaction_type', 'EXTEND')->values(),
            'update_price'            => $items->where('transaction_type', 'UPDATE_PRICE')->values(),
            'switch_customer'         => $items->where('transaction_type', 'SWITCH_CUSTOMER')->values(),
            'batch'                   => $batch,
            'sales_team'              => $teamName,
            'printed_at'              => Carbon::now()->format('d/m/Y H:i'),
            'duplicate_count'         => 0,
            'is_duplicate'            => false,
        ];

        return view('lor.list_so.pdf', $viewData)->render();
    }

    /**
     * Generate PDF and mark all selected items as PRINTED in a new Batch.
     */
    public function createPrintBatch(array $itemIds, User $user, ?string $salesTeam = null): array
    {
        $items = LorSmdSoItem::whereIn('id', $itemIds)->where('is_printed', false)->get();
        if ($items->isEmpty()) {
            throw new \Exception('Tidak ada antrean SO terpilih untuk dicetak.');
        }

        $todayStr = Carbon::now()->format('Ymd');
        $batchCountToday = LorSmdSoBatch::whereDate('printed_at', Carbon::today())->count() + 1;
        $batchNo = sprintf('SO-BATCH-%s-%03d', $todayStr, $batchCountToday);

        $teamName = $salesTeam && $salesTeam !== 'all' ? $salesTeam : 'ALL TEAMS';

        // 1. Create Batch (Initial Original Print -> print_count = 1)
        $batch = LorSmdSoBatch::create([
            'batch_no'        => $batchNo,
            'sales_team'      => $teamName,
            'printed_by_id'   => $user->id,
            'printed_by_name' => $user->name,
            'printed_at'      => Carbon::now(),
            'total_so'        => $items->count(),
            'print_count'     => 1,
        ]);

        // 2. Mark items as printed
        LorSmdSoItem::whereIn('id', $items->pluck('id'))->update([
            'is_printed' => true,
            'batch_id'   => $batch->id,
        ]);

        // 3. Group for PDF template
        $grouped = [
            'reserved_subscription'   => $items->where('transaction_type', 'RESERVED')->where('rental_type', 'SUBSCRIPTION')->values(),
            'reserved_reguler'        => $items->where('transaction_type', 'RESERVED')->where('rental_type', 'REGULER')->values(),
            'terminate'               => $items->where('transaction_type', 'TERMINATE')->values(),
            'extend_and_update_price' => $items->whereIn('transaction_type', ['EXTEND', 'UPDATE_PRICE'])->values(),
            'extend'                  => $items->where('transaction_type', 'EXTEND')->values(),
            'update_price'            => $items->where('transaction_type', 'UPDATE_PRICE')->values(),
            'switch_customer'         => $items->where('transaction_type', 'SWITCH_CUSTOMER')->values(),
            'batch'                   => $batch,
            'sales_team'              => $teamName,
            'printed_at'              => Carbon::now()->format('d/m/Y H:i'),
            'duplicate_count'         => 0,
            'is_duplicate'            => false,
        ];

        // 4. Render PDF with Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);

        $html = view('lor.list_so.pdf', $grouped)->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $pdfOutput = $dompdf->output();

        // 5. Save PDF in storage
        $filename = sprintf('list_so_batches/%s.pdf', $batchNo);
        Storage::disk('public')->put($filename, $pdfOutput);

        $batch->update(['pdf_path' => $filename]);

        return [
            'batch'      => $batch,
            'pdf_output' => $pdfOutput,
            'filename'   => sprintf('%s.pdf', $batchNo),
        ];
    }

    /**
     * Reprint past batch PDF with DUPLIKAT increment stamp.
     */
    public function reprintBatchPdf(int $batchId, User $user): array
    {
        $batch = LorSmdSoBatch::with('items')->findOrFail($batchId);
        $items = $batch->items()->orderBy('start_date', 'asc')->get();

        if (($batch->print_count ?? 1) <= 0) {
            $duplicateCount = 0;
            $isDuplicate = false;
            $batch->print_count = 1;
            $batch->save();
        } else {
            // Increment print count
            $duplicateCount = $batch->print_count; // e.g. print_count 1 -> next reprint is DUPLIKAT 1
            $batch->print_count = $batch->print_count + 1;
            $batch->save();
            $isDuplicate = true;
        }

        $grouped = [
            'reserved_subscription'   => $items->where('transaction_type', 'RESERVED')->where('rental_type', 'SUBSCRIPTION')->values(),
            'reserved_reguler'        => $items->where('transaction_type', 'RESERVED')->where('rental_type', 'REGULER')->values(),
            'terminate'               => $items->where('transaction_type', 'TERMINATE')->values(),
            'extend_and_update_price' => $items->whereIn('transaction_type', ['EXTEND', 'UPDATE_PRICE'])->values(),
            'extend'                  => $items->where('transaction_type', 'EXTEND')->values(),
            'update_price'            => $items->where('transaction_type', 'UPDATE_PRICE')->values(),
            'switch_customer'         => $items->where('transaction_type', 'SWITCH_CUSTOMER')->values(),
            'batch'                   => $batch,
            'sales_team'              => $batch->sales_team ?? 'ALL TEAMS',
            'printed_at'              => Carbon::parse($batch->printed_at)->format('d/m/Y H:i'),
            'duplicate_count'         => $duplicateCount,
            'is_duplicate'            => $isDuplicate,
        ];

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);

        $html = view('lor.list_so.pdf', $grouped)->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $pdfOutput = $dompdf->output();

        return [
            'batch'           => $batch,
            'pdf_output'      => $pdfOutput,
            'filename'        => $duplicateCount > 0 ? sprintf('%s_DUPLIKAT_%d.pdf', $batch->batch_no, $duplicateCount) : sprintf('%s.pdf', $batch->batch_no),
            'duplicate_count' => $duplicateCount,
        ];
    }
}
