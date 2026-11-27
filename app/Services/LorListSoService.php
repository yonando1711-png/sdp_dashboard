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

        // Permanently purge any non-rental SOs (e.g. S/... Sold Out)
        LorSmdSoItem::where('so_number', 'not like', 'R/%')->delete();

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
            $trackingDate = !empty($t['create_date']) 
                ? Carbon::parse($t['create_date'], 'UTC')->setTimezone('Asia/Jakarta')->format('Y-m-d')
                : null;
            $soMutations[$soId][] = [
                'field'     => $fieldName,
                'old_char'  => $t['old_value_char'] ?? null,
                'new_char'  => $t['new_value_char'] ?? null,
                'old_dt'    => $t['old_value_datetime'] ?? null,
                'new_dt'    => $t['new_value_datetime'] ?? null,
                'old_float' => $t['old_value_float'] ?? null,
                'new_float' => $t['new_value_float'] ?? null,
                'date'      => $trackingDate,
            ];
        }

        // 2. Fetch all target SO IDs from Odoo:
        $targetSoIds = [];

        // A. Confirmed in target date range
        try {
            $confirmedIds = $this->odooService->execute('sale.order', 'search', [[
                ['name', 'like', 'R/'],
                ['state', 'in', ['sale', 'done', 'cancel']],
                ['date_order', '>=', $fromUtc],
                ['date_order', '<=', $toUtc],
            ]]);
            $targetSoIds = array_merge($targetSoIds, (array)$confirmedIds);
        } catch (\Exception $e) {
            Log::warning('Search confirmed SOs failed: ' . $e->getMessage());
        }

        // B. Terminated or Force Closed in date range with safeguard against ancient historical terminations
        $targetMonthStart = Carbon::parse($dateFrom)->startOfMonth()->format('Y-m-d');
        try {
            $terminatedIds = $this->odooService->execute('sale.order', 'search', [[
                ['name', 'like', 'R/'],
                ['state', 'in', ['sale', 'done', 'cancel']],
                ['write_date', '>=', $fromUtc],
                ['write_date', '<=', $toUtc],
                '|',
                ['is_terminate', '=', true],
                ['is_force_close', '=', true],
                ['actual_end_rental', '>=', $targetMonthStart . ' 00:00:00'],
            ]]);
            $targetSoIds = array_merge($targetSoIds, (array)$terminatedIds);
        } catch (\Exception $e) {
            Log::warning('Search terminated SOs failed: ' . $e->getMessage());
        }

        // B2. Also fetch SO IDs where Chatter message mentioning ET/termination was created in date range
        try {
            $etMessages = $this->odooService->execute('mail.message', 'search_read', [
                [
                    ['model', '=', 'sale.order'],
                    ['date', '>=', $fromUtc],
                    ['date', '<=', $toUtc],
                    '|',
                    ['body', 'ilike', 'ET'],
                    ['body', 'ilike', 'terminate'],
                ]
            ], [
                'fields' => ['id', 'res_id', 'date', 'body']
            ]);
            foreach ($etMessages as $em) {
                if (!empty($em['res_id'])) {
                    $targetSoIds[] = $em['res_id'];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Search ET messages failed: ' . $e->getMessage());
        }

        // C. Chatter mutations SO IDs (Return Date extensions, Untaxed Amount price updates, Customer switches)
        $targetSoIds = array_merge($targetSoIds, array_keys($soMutations));
        $targetSoIds = array_values(array_unique(array_filter($targetSoIds)));

        if (empty($targetSoIds)) {
            return ['synced' => 0, 'new' => 0, 'updated' => 0];
        }

        // Safe fields confirmed to exist on sale.order
        $fields = [
            'id', 'name', 'client_order_ref', 'partner_id', 'date_order',
            'user_id', 'team_id', 'rental_type', 'rental_status',
            'actual_start_rental', 'actual_end_rental', 'rental_start_date',
            'rental_return_date', 'initial_end_date', 'is_terminate', 'is_force_close', 'is_extended', 'is_tender',
            'order_line', 'amount_total', 'origin', 'write_date',
            'previous_so_id', 'previous_customer_id'
        ];

        try {
            $orders = [];
            foreach (array_chunk($targetSoIds, 200) as $chunkIds) {
                $chunkOrders = $this->odooService->execute('sale.order', 'read', [$chunkIds], [
                    'fields' => $fields,
                ]);
                $orders = array_merge($orders, (array)$chunkOrders);
            }
        } catch (\Exception $e) {
            Log::error('LorListSoService sync read failed: ' . $e->getMessage());
            throw new \Exception('Failed to read SOs from Odoo: ' . $e->getMessage());
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

        // Batch resolve lot names (police number / unit code) and vehicle_year from stock.lot
        $lotNames = [];
        $lotYears = [];
        if (!empty($lotIds)) {
            try {
                $lotChunks = array_chunk(array_values(array_unique($lotIds)), 300);
                foreach ($lotChunks as $lChunk) {
                    $lots = $this->odooService->execute('stock.lot', 'read', [$lChunk, ['id', 'name', 'vehicle_year']]);
                    foreach ($lots as $lot) {
                        $lotNames[$lot['id']] = $lot['name'] ?? null;
                        $lotYears[$lot['id']] = $lot['vehicle_year'] ?? null;
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

        // Batch resolve Chatter messages (mail.message) to accurately detect the date user wrote ET / Amandemen memos
        $etMsgMap = [];
        $targetSoIdsAll = array_filter(array_column($orders, 'id'));
        if (!empty($targetSoIdsAll)) {
            try {
                $soIdChunks = array_chunk($targetSoIdsAll, 100);
                foreach ($soIdChunks as $sChunk) {
                    $allEtMsgs = $this->odooService->execute('mail.message', 'search_read', [
                        [
                            ['res_id', 'in', $sChunk],
                            ['model', '=', 'sale.order'],
                            ['message_type', 'in', ['comment', 'notification']],
                        ]
                    ], [
                        'fields' => ['res_id', 'date', 'body'],
                        'order'  => 'date desc',
                    ]);
                    foreach ($allEtMsgs as $em) {
                        $soId = $em['res_id'];
                        if (isset($etMsgMap[$soId])) continue; // Keep the latest matching memo
                        $clean = trim(strip_tags($em['body']));
                        if (preg_match('/(ET|terminate|CL|tgl\.tarik|amandemen|kerusakan|accident)/i', $clean) && stripos($clean, 'Invoice') === false) {
                            $etMsgMap[$soId] = Carbon::parse($em['date'], 'UTC')->setTimezone('Asia/Jakarta')->format('Y-m-d');
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Batch ET message read failed: ' . $e->getMessage());
            }
        }

        $newCount = 0;
        $updatedCount = 0;

        foreach ($orders as $order) {
            $soName = $order['name'] ?? null;
            if (!$soName) continue;

            // Strictly exclude non-rental SOs (e.g. S/... - Sold Out / unit sales)
            if (!str_starts_with($soName, 'R/')) {
                continue;
            }
            if (($order['rental_type'] ?? '') === 'none') {
                continue;
            }

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
            $vehicleYear = null;
            if (!empty($mainLine['reserved_lot_ids']) && is_array($mainLine['reserved_lot_ids'])) {
                $firstLotId = $mainLine['reserved_lot_ids'][0];
                $reservedLot = $lotNames[$firstLotId] ?? $firstLotId;
                $vehicleYear = $lotYears[$firstLotId] ?? null;
            } elseif (!empty($mainLine['pickedup_lot_ids']) && is_array($mainLine['pickedup_lot_ids'])) {
                $firstLotId = $mainLine['pickedup_lot_ids'][0];
                $reservedLot = $lotNames[$firstLotId] ?? $firstLotId;
                $vehicleYear = $lotYears[$firstLotId] ?? null;
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
            $orderDate = !empty($order['date_order']) ? $this->parseOdooDate($order['date_order']) : null;
            $sourceDoc = !empty($order['origin']) ? $order['origin'] : ($order['client_order_ref'] ?? null);
            $prevSoFromOdoo = is_array($order['previous_so_id'] ?? null) ? $order['previous_so_id'][1] : ($order['previous_so_id'] ?? null);
            $prevCustFromOdoo = is_array($order['previous_customer_id'] ?? null) ? $order['previous_customer_id'][1] : ($order['previous_customer_id'] ?? null);

            // Effective Start Date (Converted from UTC to Asia/Jakarta)
            $startDate = !empty($order['actual_start_rental']) 
                ? $this->parseOdooDate($order['actual_start_rental']) 
                : (!empty($order['rental_start_date']) ? $this->parseOdooDate($order['rental_start_date']) : null);

            // Effective End Date (Converted from UTC to Asia/Jakarta)
            $endDate = !empty($order['rental_return_date']) 
                ? $this->parseOdooDate($order['rental_return_date']) 
                : (!empty($order['initial_end_date']) ? $this->parseOdooDate($order['initial_end_date']) : null);

            $actualEnd = !empty($order['actual_end_rental']) ? $this->parseOdooDate($order['actual_end_rental']) : null;

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
            $chatterPrice = null;
            $chatterTerminate = false;
            $chatterConfirmed = false;
            $chatterCustomerSwitch = null;

            foreach ($mutations as $m) {
                // Customer mutation (Switch Customer in same SO)
                if (stripos($m['field'], 'Customer') !== false && !empty($m['old_char']) && !empty($m['new_char'])) {
                    $chatterCustomerSwitch = [
                        'old_customer' => $m['old_char'],
                        'new_customer' => $m['new_char'],
                        'date'         => $m['date'],
                    ];
                }

                // Return date extend
                if ((stripos($m['field'], 'Rental Return Date') !== false || stripos($m['field'], 'Return Date') !== false) && !empty($m['new_dt'])) {
                    $chatterExtend = [
                        'old_end' => !empty($m['old_dt']) ? $this->parseOdooDate($m['old_dt']) : null,
                        'new_end' => $this->parseOdooDate($m['new_dt']),
                        'date'    => $m['date'],
                    ];
                }

                // Untaxed Amount tracking (Detects price changes in order lines)
                if (stripos($m['field'], 'Untaxed Amount') !== false && !empty($m['old_float']) && !empty($m['new_float'])) {
                    $chatterPrice = [
                        'old_untaxed' => (float)$m['old_float'],
                        'new_untaxed' => (float)$m['new_float'],
                        'date'        => $m['date'],
                    ];
                }

                // Termination / Cancel
                if (stripos($m['field'], 'is_terminate') !== false || ($m['new_char'] === 'Cancelled' || $m['new_char'] === 'cancel')) {
                    $chatterTerminate = [
                        'date' => $m['date'],
                    ];
                }
                // Status confirmation
                if ((stripos($m['field'], 'Status') !== false || stripos($m['field'], 'state') !== false) 
                    && in_array(strtolower((string)$m['new_char']), ['sales order', 'sale', 'done', 'reserved'])) {
                    $chatterConfirmed = [
                        'date' => $m['date'],
                    ];
                }
            }

            $isConfirmedInRange = !empty($chatterConfirmed) || (!empty($order['date_order']) && $order['date_order'] >= $fromUtc && $order['date_order'] <= $toUtc);
            $isWriteDateInRange = !empty($order['write_date']) && $order['write_date'] >= $fromUtc && $order['write_date'] <= $toUtc;
            $isActualEndInRange = !empty($order['actual_end_rental']) && $order['actual_end_rental'] >= $fromUtc && $order['actual_end_rental'] <= $toUtc;

            $isTerminateFlag = !empty($order['is_terminate']);
            $isForceCloseFlag = !empty($order['is_force_close']);
            $isExtendedFlag = !empty($order['is_extended']);

            $updateDate = !empty($order['write_date']) 
                ? Carbon::parse($order['write_date'])->setTimezone('Asia/Jakarta')->format('Y-m-d') 
                : $orderDate;

            // Collect all matching events for target date independently
            $eventsToSave = [];

            // 1. SWITCH CUSTOMER:
            // Case A: Customer chatter mutation occurred on target date (Edit on same SO)
            if (!empty($chatterCustomerSwitch)) {
                $eventsToSave[] = [
                    'transaction_type'       => 'SWITCH_CUSTOMER',
                    'order_date'             => $chatterCustomerSwitch['date'] ?? $updateDate,
                    'start_date'             => $startDate,
                    'end_date'               => $endDate,
                    'previous_end_date'      => null,
                    'duration_price'         => $durationPrice,
                    'previous_duration_price'=> null,
                    'actual_end_rental_date' => null,
                    'previous_so'            => $prevSoFromOdoo,
                    'previous_customer'      => $chatterCustomerSwitch['old_customer'],
                    'previous_end_date_cust' => null,
                    'rbo'                    => $rbo,
                ];
            } elseif ($isConfirmedInRange && !empty($prevCustFromOdoo) && $prevCustFromOdoo !== $customerName) {
                // Case B: Newly confirmed SO on target date with different previous customer
                $eventsToSave[] = [
                    'transaction_type'       => 'SWITCH_CUSTOMER',
                    'order_date'             => (is_array($chatterConfirmed) ? $chatterConfirmed['date'] : null) ?? $orderDate,
                    'start_date'             => $startDate,
                    'end_date'               => $endDate,
                    'previous_end_date'      => null,
                    'duration_price'         => $durationPrice,
                    'previous_duration_price'=> null,
                    'actual_end_rental_date' => null,
                    'previous_so'            => $prevSoFromOdoo,
                    'previous_customer'      => $prevCustFromOdoo,
                    'previous_end_date_cust' => null,
                    'rbo'                    => $rbo,
                ];
            }

            // 2. TERMINATE & FORCE CLOSE:
            // Must currently be terminated, force closed, or in cancelled state in Odoo
            $isCurrentlyTerminated = $isTerminateFlag || $isForceCloseFlag || (($order['state'] ?? '') === 'cancel');
            if (!$isCurrentlyTerminated) {
                // If SO is currently active, ensure no stale/false unprinted termination record remains
                LorSmdSoItem::where('so_number', $soName)
                    ->whereIn('transaction_type', ['TERMINATE', 'FORCE_CLOSE'])
                    ->where('is_printed', false)
                    ->delete();
            }
            $isEtMessage = isset($etMsgMap[$order['id']]);
            if ($isCurrentlyTerminated && ($isWriteDateInRange || $isActualEndInRange || !empty($chatterTerminate) || $isEtMessage)) {
                $termDate = $etMsgMap[$order['id']]
                    ?? (is_array($chatterTerminate) ? $chatterTerminate['date'] : null)
                    ?? $updateDate;

                // Ensure the termination event actually occurred within the selected date range
                if ($termDate >= $dateFrom && $termDate <= $dateTo) {
                    $transType = $isForceCloseFlag ? 'FORCE_CLOSE' : 'TERMINATE';
                    $eventsToSave[] = [
                        'transaction_type'       => $transType,
                        'order_date'             => $termDate,
                        'start_date'             => $startDate,
                        'end_date'               => $endDate,
                        'previous_end_date'      => null,
                        'duration_price'         => $durationPrice,
                        'previous_duration_price'=> null,
                        'actual_end_rental_date' => $actualEnd ?: $this->parseOdooDate($order['write_date']),
                        'previous_so'            => $prevSoFromOdoo,
                        'previous_customer'      => null,
                        'previous_end_date_cust' => null,
                        'rbo'                    => $rbo,
                        'vehicle_year'           => $vehicleYear,
                    ];
                }
            }

            // 3. EXTEND & UPDATE PRICE:
            $hasExtendEvent = !empty($chatterExtend);
            $hasUntaxedChangeEvent = !empty($chatterPrice) && ($chatterPrice['old_untaxed'] != $chatterPrice['new_untaxed']);

            if ($hasExtendEvent && $hasUntaxedChangeEvent) {
                // Check if monthly rate changed or remained constant
                $sCarbon = Carbon::parse($startDate);
                $oldECarbon = Carbon::parse($chatterExtend['old_end'] ?? $this->parseOdooDate($order['initial_end_date']) ?? $endDate)->addDay();
                $newECarbon = Carbon::parse($chatterExtend['new_end'] ?? $endDate)->addDay();
                $oldM = max(1, (int) round($sCarbon->diffInMonths($oldECarbon)));
                $newM = max(1, (int) round($sCarbon->diffInMonths($newECarbon)));

                $calcOldRate = round($chatterPrice['old_untaxed'] / $oldM);
                $calcNewRate = round($chatterPrice['new_untaxed'] / $newM);

                // If monthly rates differ by more than 1%, it is EXTEND & UPDATE PRICE
                if (abs($calcOldRate - $calcNewRate) > ($calcNewRate * 0.01)) {
                    $eventsToSave[] = [
                        'transaction_type'       => 'EXTEND_UPDATE_PRICE',
                        'order_date'             => $chatterExtend['date'] ?? $chatterPrice['date'] ?? $updateDate,
                        'start_date'             => $startDate,
                        'end_date'               => $chatterExtend['new_end'] ?? $endDate,
                        'previous_end_date'      => $chatterExtend['old_end'] ?? $this->parseOdooDate($order['initial_end_date']) ?? null,
                        'duration_price'         => $durationPrice,
                        'previous_duration_price'=> $calcOldRate,
                        'actual_end_rental_date' => null,
                        'previous_so'            => $prevSoFromOdoo,
                        'previous_customer'      => null,
                        'previous_end_date_cust' => null,
                        'rbo'                    => $rbo,
                        'vehicle_year'           => $vehicleYear,
                    ];
                } else {
                    // EXTEND ONLY (Monthly rate stayed constant, like SO 846!)
                    $eventsToSave[] = [
                        'transaction_type'       => 'EXTEND',
                        'order_date'             => $chatterExtend['date'] ?? $updateDate,
                        'start_date'             => $startDate,
                        'end_date'               => $chatterExtend['new_end'] ?? $endDate,
                        'previous_end_date'      => $chatterExtend['old_end'] ?? $this->parseOdooDate($order['initial_end_date']) ?? null,
                        'duration_price'         => $durationPrice,
                        'previous_duration_price'=> null, // Unchanged!
                        'actual_end_rental_date' => null,
                        'previous_so'            => $prevSoFromOdoo,
                        'previous_customer'      => null,
                        'previous_end_date_cust' => null,
                        'rbo'                    => $rbo,
                        'vehicle_year'           => $vehicleYear,
                    ];
                }
            } elseif ($hasExtendEvent && !$hasUntaxedChangeEvent) {
                // EXTEND ONLY
                $eventsToSave[] = [
                    'transaction_type'       => 'EXTEND',
                    'order_date'             => $chatterExtend['date'] ?? $updateDate,
                    'start_date'             => $startDate,
                    'end_date'               => $chatterExtend['new_end'] ?? $endDate,
                    'previous_end_date'      => $chatterExtend['old_end'] ?? $this->parseOdooDate($order['initial_end_date']) ?? null,
                    'duration_price'         => $durationPrice,
                    'previous_duration_price'=> null,
                    'actual_end_rental_date' => null,
                    'previous_so'            => $prevSoFromOdoo,
                    'previous_customer'      => null,
                    'previous_end_date_cust' => null,
                    'rbo'                    => $rbo,
                    'vehicle_year'           => $vehicleYear,
                ];
            } elseif (!$hasExtendEvent && $hasUntaxedChangeEvent) {
                // UPDATE PRICE ONLY (Return date did not change, but untaxed amount changed!)
                $calcOldPrice = ($chatterPrice['new_untaxed'] > 0)
                    ? round($durationPrice * ($chatterPrice['old_untaxed'] / $chatterPrice['new_untaxed']))
                    : null;

                $eventsToSave[] = [
                    'transaction_type'       => 'UPDATE_PRICE',
                    'order_date'             => $chatterPrice['date'] ?? $updateDate,
                    'start_date'             => $startDate,
                    'end_date'               => $endDate,
                    'previous_end_date'      => null,
                    'duration_price'         => $durationPrice,
                    'previous_duration_price'=> $calcOldPrice,
                    'actual_end_rental_date' => null,
                    'previous_so'            => $prevSoFromOdoo,
                    'previous_customer'      => null,
                    'previous_end_date_cust' => null,
                    'rbo'                    => $rbo,
                ];
            }

            // 4. RESERVED (Subscription or Reguler):
            if ($isConfirmedInRange && !$isTerminateFlag && !$isForceCloseFlag) {
                $eventsToSave[] = [
                    'transaction_type'       => 'RESERVED',
                    'order_date'             => (is_array($chatterConfirmed) ? $chatterConfirmed['date'] : null) ?? $orderDate,
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
            }

            foreach ($eventsToSave as $event) {
                $transType = $event['transaction_type'];

                // Check if an unprinted record for this exact SO and mutation values already exists
                $existingQuery = LorSmdSoItem::where('so_number', $soName)
                                            ->where('transaction_type', $transType);

                // Find unprinted record for this SO and transaction type to update with latest Odoo data
                $unprinted = (clone $existingQuery)->where('is_printed', false)->latest()->first();
                $existing = $unprinted ?: $existingQuery->latest()->first();

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
                        'previous_duration_price'=> $event['previous_duration_price'],
                        'total_price'            => $totalPrice,
                        'previous_so'            => $event['previous_so'] ?? $prevSoFromOdoo ?? $existing->previous_so,
                        'previous_customer'      => $event['previous_customer'] ?? $prevCustFromOdoo ?? $existing->previous_customer,
                        'previous_end_date_cust' => $event['previous_end_date_cust'] ?? $existing->previous_end_date_cust,
                        'area'                   => $area,
                        'rbo'                    => ($rbo !== '-') ? $rbo : $existing->rbo,
                        'source_doc'             => $sourceDoc,
                        'raw_odoo_data'          => array_merge($existing->raw_odoo_data ?? [], [
                            'vehicle_year' => $event['vehicle_year'] ?? $vehicleYear,
                        ]),
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
                        'raw_odoo_data'          => [
                            'vehicle_year' => $event['vehicle_year'] ?? $vehicleYear,
                        ],
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

        return $this->formatGroupedItems($items);
    }

    /**
     * Unified helper to group items into categories, including subcategories for Terminate & Extend.
     */
    public function formatGroupedItems($items, array $extra = []): array
    {
        $base = [
            'reserved_subscription'     => $items->where('transaction_type', 'RESERVED')->where('rental_type', 'SUBSCRIPTION')->values(),
            'reserved_reguler'          => $items->where('transaction_type', 'RESERVED')->where('rental_type', 'REGULER')->values(),
            'terminate_and_force_close' => $items->whereIn('transaction_type', ['TERMINATE', 'FORCE_CLOSE'])->values(),
            'terminate'                 => $items->where('transaction_type', 'TERMINATE')->values(),
            'force_close'               => $items->where('transaction_type', 'FORCE_CLOSE')->values(),
            'extend_and_update_price'   => $items->whereIn('transaction_type', ['EXTEND', 'UPDATE_PRICE', 'EXTEND_UPDATE_PRICE'])->values(),
            'extend_only'               => $items->where('transaction_type', 'EXTEND')->values(),
            'update_price_only'         => $items->where('transaction_type', 'UPDATE_PRICE')->values(),
            'extend_and_update_both'    => $items->where('transaction_type', 'EXTEND_UPDATE_PRICE')->values(),
            'extend'                    => $items->where('transaction_type', 'EXTEND')->values(),
            'update_price'              => $items->where('transaction_type', 'UPDATE_PRICE')->values(),
            'switch_customer'           => $items->where('transaction_type', 'SWITCH_CUSTOMER')->values(),
            'total_count'               => $items->count(),
            'all_items'                 => $items,
        ];

        return array_merge($base, $extra);
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

        $viewData = $this->formatGroupedItems($items, [
            'batch'           => $batch,
            'sales_team'      => $teamName,
            'printed_at'      => Carbon::now()->format('d/m/Y H:i'),
            'duplicate_count' => 0,
            'is_duplicate'    => false,
        ]);

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

        $viewData = $this->formatGroupedItems($items, [
            'batch'           => $batch,
            'sales_team'      => $teamName,
            'printed_at'      => Carbon::now()->format('d/m/Y H:i'),
            'duplicate_count' => 0,
            'is_duplicate'    => false,
        ]);

        return view('lor.list_so.pdf', $viewData)->render();
    }

    /**
     * Generate HTML preview for an existing printed batch from history.
     */
    public function generateBatchPreviewHtml(int $batchId): string
    {
        $batch = LorSmdSoBatch::with('items')->findOrFail($batchId);
        $items = $batch->items()->orderBy('start_date', 'asc')->get();

        $viewData = $this->formatGroupedItems($items, [
            'batch'           => $batch,
            'sales_team'      => $batch->sales_team ?? 'ALL TEAMS',
            'printed_at'      => Carbon::parse($batch->printed_at)->format('d/m/Y H:i'),
            'duplicate_count' => ($batch->print_count ?? 1) > 1 ? ($batch->print_count - 1) : 0,
            'is_duplicate'    => ($batch->print_count ?? 1) > 1,
        ]);

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
        $grouped = $this->formatGroupedItems($items, [
            'batch'           => $batch,
            'sales_team'      => $teamName,
            'printed_at'      => Carbon::now()->format('d/m/Y H:i'),
            'duplicate_count' => 0,
            'is_duplicate'    => false,
        ]);

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

        $grouped = $this->formatGroupedItems($items, [
            'batch'           => $batch,
            'sales_team'      => $batch->sales_team ?? 'ALL TEAMS',
            'printed_at'      => Carbon::parse($batch->printed_at)->format('d/m/Y H:i'),
            'duplicate_count' => $duplicateCount,
            'is_duplicate'    => $isDuplicate,
        ]);

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

    /**
     * Parse Odoo UTC datetime string and convert to Asia/Jakarta date string (Y-m-d).
     */
    public function parseOdooDate(?string $dtStr): ?string
    {
        if (empty($dtStr)) {
            return null;
        }
        try {
            return Carbon::parse($dtStr, 'UTC')->setTimezone('Asia/Jakarta')->format('Y-m-d');
        } catch (\Exception $e) {
            return substr($dtStr, 0, 10);
        }
    }
}
