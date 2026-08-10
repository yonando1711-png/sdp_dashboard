<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>List of Rented Export</title>
    <style>
        @page {
            margin: 15px;
            size: A4 landscape;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8px;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 12px;
        }
        .header h2 {
            margin: 0;
            font-size: 16px;
            color: #0f172a;
        }
        .header p {
            margin: 2px 0 0 0;
            font-size: 9px;
            color: #64748b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 5px 4px;
            font-size: 8px;
            border: 1px solid #334155;
            word-wrap: break-word;
        }
        td {
            padding: 4px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
            word-wrap: break-word;
            font-size: 7.5px;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-pickedup { background: #fef08a; color: #854d0e; }
        .badge-returned { background: #fecaca; color: #991b1b; }
        .badge-reserved { background: #bbf7d0; color: #166534; }
        .badge-quotation { background: #bfdbfe; color: #1e40af; }
        .badge-cancelled { background: #e2e8f0; color: #475569; }
        
        .price-block {
            font-size: 7px;
            line-height: 1.2;
            color: #334155;
            white-space: pre-line;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>List of Rented (LoR)</h2>
        <p>Export Date: {{ date('d M Y') }} | Total Records: {{ count($rentals) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 7%;">Rental ID</th>
                <th style="width: 8%;">Contract Ref</th>
                <th style="width: 11%;">Type</th>
                <th style="width: 6%;">Police-No</th>
                <th style="width: 4%;">Year</th>
                <th style="width: 7%;">CITY/Lokasi Pemakaian</th>
                <th style="width: 10%;">Customer</th>
                <th style="width: 7%;">PO</th>
                <th style="width: 6%;">Status</th>
                <th style="width: 6%;">Start</th>
                <th style="width: 6%;">End</th>
                <th style="width: 6.5%;">
                    @if($taxMode === 'include')
                        Harga (11% INCL)
                    @elseif($taxMode === 'exclude')
                        Harga (11% EXCL)
                    @else
                        Harga
                    @endif
                </th>
                <th style="width: 6.5%;">Total Harga</th>
                <th style="width: 5%;">Driver</th>
                <th style="width: {{ $includeNopol ? '10%' : '13%' }};">Price History Details</th>
                @if($includeNopol)
                    <th style="width: 3%;">Plate History</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($rentals as $rental)
                @php
                    $mainHargaRaw = $rental->price;
                    if ($mainHargaRaw) {
                        if ($taxMode === 'include') {
                            $mainHargaRaw = $mainHargaRaw * 1.11;
                        } elseif ($taxMode === 'exclude') {
                            $mainHargaRaw = $mainHargaRaw / 1.11;
                        }
                    }

                    $totalHargaRaw = $rental->total_price;
                    if ($totalHargaRaw) {
                        if ($taxMode === 'include') {
                            $totalHargaRaw = $totalHargaRaw * 1.11;
                        } elseif ($taxMode === 'exclude') {
                            $totalHargaRaw = $totalHargaRaw / 1.11;
                        }
                    }

                    $priceDetails = $priceHistories[$rental->rental_id] ?? [];
                    $priceStr = '';
                    if ($rental->status !== 'Returned' && !empty($priceDetails)) {
                        $blocks = [];
                        foreach ($priceDetails as $i => $block) {
                            $bNum = $i + 1;
                            $p = 'Rp ' . number_format($block['price'], 0, ',', '.');
                            $tp = 'Rp ' . number_format($block['total_price'] ?? $block['price'], 0, ',', '.');
                            $s = $block['start_date'] ? \Carbon\Carbon::parse($block['start_date'])->format('d M Y') : '-';
                            $e = $block['end_date'] ? \Carbon\Carbon::parse($block['end_date'])->format('d M Y') : '-';
                            $t = $block['tax'] ?? '-';
                            $blocks[] = "[#{$bNum}] Monthly: {$p} | Total: {$tp} ({$s} - {$e}) | {$t}";
                        }
                        $priceStr = implode("\n", $blocks);
                    }

                    $statusClass = strtolower($rental->status);
                @endphp
                <tr>
                    <td style="font-weight: bold;">{{ $rental->rental_id }}</td>
                    <td>{{ $rental->contract_ref ?: '-' }}</td>
                    <td>{{ $rental->product ?: '-' }}</td>
                    <td>{{ $rental->lot_number ?: '-' }}</td>
                    <td>{{ $rental->year ?: '-' }}</td>
                    <td>{{ $rental->city ?: '-' }}</td>
                    <td>{{ $rental->current_customer ?: '-' }}</td>
                    <td>{{ $rental->po ?: '-' }}</td>
                    <td>
                        <span class="badge badge-{{ $statusClass }}">{{ $rental->status }}</span>
                    </td>
                    <td>{{ $rental->actual_start_rental ? \Carbon\Carbon::parse($rental->actual_start_rental)->format('d-m-Y') : '-' }}</td>
                    <td>{{ $rental->actual_end_rental ? \Carbon\Carbon::parse($rental->actual_end_rental)->format('d-m-Y') : '-' }}</td>
                    <td style="font-weight: bold;">{{ $mainHargaRaw ? 'Rp ' . number_format($mainHargaRaw, 0, ',', '.') : '-' }}</td>
                    <td style="font-weight: bold;">{{ $totalHargaRaw ? 'Rp ' . number_format($totalHargaRaw, 0, ',', '.') : '-' }}</td>
                    <td>{{ $rental->driver ?: '-' }}</td>
                    <td class="price-block">{{ $priceStr ?: '-' }}</td>
                    @if($includeNopol)
                        @php
                            $historyStr = '';
                            $itemHists = $nopolHistories->get($rental->rental_id, collect());
                            if ($itemHists->count() > 0) {
                                $plateChanges = [];
                                $sortedHistories = $itemHists->sortBy('created_at')->values();
                                
                                $states = [];
                                foreach ($sortedHistories as $h) {
                                    $states[] = $h;
                                }
                                $states[] = $rental;
                                
                                for ($i = 0; $i < count($states) - 1; $i++) {
                                    $prev = $states[$i];
                                    $next = $states[$i + 1];
                                    
                                    if ($prev->lot_number != $next->lot_number) {
                                        $prevMove = $prev->product_movement_count ?? 0;
                                        $nextMove = $next->product_movement_count ?? 0;
                                        
                                        if ($prevMove == $nextMove) {
                                            $changeDate = $prev->created_at ?? ($next->created_at ?? null);
                                            $dateStr = $changeDate ? \Carbon\Carbon::parse($changeDate)->format('d M Y') : '-';
                                            $plateChanges[] = "{$prev->lot_number} -> {$next->lot_number} ({$dateStr})";
                                        }
                                    }
                                }
                                
                                $plateChanges = array_reverse($plateChanges);
                                $historyStr = implode("\n", $plateChanges);
                            }
                        @endphp
                        <td class="price-block">{!! nl2br(e($historyStr ?: '-')) !!}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
