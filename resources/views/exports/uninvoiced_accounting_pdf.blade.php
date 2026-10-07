<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Uninvoiced Accounting Report - PDF</title>
    <style>
        @page {
            margin: 20px 25px 25px 25px;
            size: {{ ($type === 'detailed' || count($monthKeys ?? []) > 6) ? 'A3' : 'A4' }} landscape;
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
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .report-title {
            font-size: 12px;
            font-weight: bold;
            color: #334155;
            margin-top: 2px;
        }
        .meta-info {
            font-size: 8px;
            color: #64748b;
            text-align: right;
            vertical-align: bottom;
        }
        .badge-filter {
            display: inline-block;
            background: #e0e7ff;
            color: #3730a3;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 7.5px;
            font-weight: bold;
            margin-top: 3px;
        }

        /* Summary KPI Cards */
        .kpi-table {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: separate;
            border-spacing: 6px 0;
        }
        .kpi-card {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 5px 8px;
            text-align: left;
        }
        .kpi-label {
            font-size: 7px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }
        .kpi-value {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 1px;
        }

        /* Main Data Table */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.data-table thead {
            display: table-header-group;
        }
        table.data-table tr {
            page-break-inside: avoid;
        }
        table.data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            text-align: right;
            padding: 5px 4px;
            border: 1px solid #0f172a;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        table.data-table th.col-left {
            text-align: left;
        }
        table.data-table th.col-center {
            text-align: center;
        }
        table.data-table td {
            padding: 4px 4px;
            border: 1px solid #cbd5e1;
            font-size: 7.5px;
            text-align: right;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        table.data-table td.col-left {
            text-align: left;
        }
        table.data-table td.col-center {
            text-align: center;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6.5px;
            font-weight: bold;
        }
        .status-uninvoiced { background-color: #ffe4e6; color: #be123c; border: 1px solid #fecdd3; }
        .status-post-cutoff { background-color: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .status-draft { background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .status-reversed { background-color: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }

        /* Total Row */
        table.data-table tr.total-row td {
            background-color: #e2e8f0;
            font-weight: bold;
            color: #0f172a;
            border-top: 1.5px solid #0f172a;
            border-bottom: 2px double #0f172a;
            font-size: 8px;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 15px;
            font-size: 7.5px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
        .footer table {
            width: 100%;
        }
    </style>
</head>
<body>
@php
    $kpi = $kpis ?? $kpi ?? [];
    $monthTotals = $monthTotals ?? ($totals['months'] ?? $totals ?? []);
    $grandUnits = $kpi['total_pending_units'] ?? ($totals['grand_total_units'] ?? count($items ?? []));
    $grandVal = $kpi['total_unbilled_value'] ?? ($totals['grand_total_value'] ?? 0);
@endphp
    {{-- Header --}}
    <div class="header">
        <table>
            <tr>
                <td style="width: 60%;">
                    <div class="company-name">PT. SURYA DARMA PERKASA</div>
                    <div class="report-title">
                        UNINVOICED ACCOUNTING REPORT &mdash; {{ $type === 'detailed' ? 'DETAILED AUDIT' : 'CUSTOMER SUMMARY' }}
                    </div>
                </td>
                <td class="meta-info" style="width: 40%;">
                    <div><strong>As-of Cutoff Date:</strong> {{ \Carbon\Carbon::parse($cutoffDate)->format('d F Y') }}</div>
                    <div><strong>Period Range:</strong> {{ \Carbon\Carbon::parse($startMonth . '-01')->format('M Y') }} &mdash; {{ \Carbon\Carbon::parse($endMonth . '-01')->format('M Y') }}</div>
                    @if(!empty($search))
                        <div class="badge-filter">Search: "{{ $search }}"</div>
                    @endif
                    @if($statusFilter !== 'all')
                        <div class="badge-filter">Status: {{ ucfirst(str_replace('_', ' ', $statusFilter)) }}</div>
                    @endif
                    <div>Generated: {{ now()->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Executive Summary KPI Cards --}}
    <table class="kpi-table">
        <tr>
            <td class="kpi-card" style="border-left: 3px solid #0f172a;">
                <div class="kpi-label">Total Unbilled Value</div>
                <div class="kpi-value" style="color: #be123c;">Rp {{ number_format($kpi['total_unbilled_value'] ?? 0, 0, ',', '.') }}</div>
            </td>
            <td class="kpi-card" style="border-left: 3px solid #6366f1;">
                <div class="kpi-label">Total Pending Units</div>
                <div class="kpi-value">{{ number_format($grandUnits, 0) }} Units</div>
            </td>
            <td class="kpi-card" style="border-left: 3px solid #f43f5e;">
                <div class="kpi-label">Belum Ada Invoice</div>
                <div class="kpi-value" style="color: #be123c;">{{ $kpi['uninvoiced_units'] ?? 0 }} <span style="font-size: 7.5px; font-weight: normal; color: #64748b;">(Rp {{ number_format($kpi['uninvoiced_value'] ?? 0, 0, ',', '.') }})</span></div>
            </td>
            <td class="kpi-card" style="border-left: 3px solid #3b82f6;">
                <div class="kpi-label">Dicetak Pasca-Cutoff</div>
                <div class="kpi-value" style="color: #1d4ed8;">{{ $kpi['post_cutoff_units'] ?? 0 }} <span style="font-size: 7.5px; font-weight: normal; color: #64748b;">(Rp {{ number_format($kpi['post_cutoff_value'] ?? 0, 0, ',', '.') }})</span></div>
            </td>
            <td class="kpi-card" style="border-left: 3px solid #f59e0b;">
                <div class="kpi-label">Draft Belum Posted</div>
                <div class="kpi-value" style="color: #b45309;">{{ $kpi['draft_units'] ?? 0 }} <span style="font-size: 7.5px; font-weight: normal; color: #64748b;">(Rp {{ number_format($kpi['draft_value'] ?? 0, 0, ',', '.') }})</span></div>
            </td>
        </tr>
    </table>

    {{-- View Mode: Detailed Line-Item Audit Table (22 Columns) --}}
    @if($type === 'detailed')
        <table class="data-table" style="font-size: 6.5px;">
            <thead>
                <tr>
                    <th class="col-center" style="width: 20px;">No.</th>
                    <th class="col-left" style="width: 48px;">Kode Cust</th>
                    <th class="col-left" style="width: 85px;">Nama Customer</th>
                    <th class="col-left" style="width: 60px;">Nomor SO</th>
                    <th class="col-left" style="width: 55px;">Nomor PO</th>
                    <th class="col-left" style="width: 60px;">Nomor Kontrak</th>
                    <th class="col-center" style="width: 45px;">Nopol</th>
                    <th class="col-left" style="width: 75px;">No. Rangka</th>
                    <th class="col-left" style="width: 75px;">Model Kendaraan</th>
                    <th class="col-center" style="width: 25px;">Thn</th>
                    <th class="col-center" style="width: 42px;">Actual Start</th>
                    <th class="col-center" style="width: 42px;">Actual End</th>
                    <th class="col-center" style="width: 70px;">Status per Cutoff</th>
                    <th class="col-center" style="width: 60px;">No. Inv Odoo</th>
                    <th class="col-center" style="width: 42px;">Tgl Inv</th>
                    <th style="width: 58px;">Total</th>
                    <th class="col-center" style="width: 25px;">Dur</th>
                    <th style="width: 58px;">Duration Price</th>
                    <th class="col-center" style="width: 42px;">Inv Period</th>
                    <th class="col-center" style="width: 45px;">Rental Status</th>
                    <th class="col-left" style="width: 50px;">Area</th>
                    <th class="col-center" style="width: 45px;">Invoice PIC</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $sumGross = 0;
                    $sumDurPrice = 0;
                @endphp
                @forelse($items as $idx => $item)
                    @php 
                        $totalVal = (float)($item['total'] ?? $item['price_unit'] ?? 0);
                        $durPriceVal = (float)($item['duration_price'] ?? 0);
                        $sumGross += $totalVal;
                        $sumDurPrice += $durPriceVal;
                    @endphp
                    <tr>
                        <td class="col-center">{{ $item['no'] }}</td>
                        <td class="col-left" style="font-weight: bold;">{{ $item['kode_cust'] }}</td>
                        <td class="col-left">{{ $item['nama_customer'] }}</td>
                        <td class="col-left">{{ $item['nomor_so'] }}</td>
                        <td class="col-left">{{ $item['nomor_po'] ?: '-' }}</td>
                        <td class="col-left">{{ $item['nomor_kontrak'] ?: '-' }}</td>
                        <td class="col-center" style="font-weight: bold; font-family: monospace;">{{ $item['nopol'] }}</td>
                        <td class="col-left" style="font-family: monospace; font-size: 6px;">{{ $item['chassis'] ?: '-' }}</td>
                        <td class="col-left">{{ $item['model'] }}</td>
                        <td class="col-center">{{ $item['tahun'] ?: '-' }}</td>
                        <td class="col-center">{{ $item['actual_start'] ?? $item['start_period_formatted'] }}</td>
                        <td class="col-center">{{ $item['actual_end'] ?? $item['end_period_formatted'] }}</td>
                        <td class="col-center">
                            @if($item['status'] === 'post_cutoff')
                                <span class="status-badge status-post-cutoff">{{ $item['status_label'] }}</span>
                            @elseif($item['status'] === 'draft')
                                <span class="status-badge status-draft">{{ $item['status_label'] }}</span>
                            @elseif($item['status'] === 'reversed')
                                <span class="status-badge status-reversed">{{ $item['status_label'] }}</span>
                            @else
                                <span class="status-badge status-uninvoiced">{{ $item['status_label'] }}</span>
                            @endif
                        </td>
                        <td class="col-center" style="font-family: monospace;">{{ $item['invoice_number'] ?: '-' }}</td>
                        <td class="col-center">{{ $item['invoice_date'] ?: '-' }}</td>
                        <td style="font-weight: bold;">{{ number_format($totalVal, 0, ',', '.') }}</td>
                        <td class="col-center">{{ number_format((float)($item['duration'] ?? 1), 2) }}</td>
                        <td>{{ number_format($durPriceVal, 0, ',', '.') }}</td>
                        <td class="col-center">{{ $item['invoice_period'] ?: '-' }}</td>
                        <td class="col-center">{{ $item['rental_status'] ?: '-' }}</td>
                        <td class="col-left">{{ $item['area_pemakaian'] ?: '-' }}</td>
                        <td class="col-center">{{ $item['invoice_pic'] ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="22" class="col-center" style="padding: 20px; color: #94a3b8;">
                            No uninvoiced accounting data matches the selected criteria.
                        </td>
                    </tr>
                @endforelse
                <tr class="total-row">
                    <td colspan="15" class="col-left">GRAND TOTAL ({{ count($items) }} Lines)</td>
                    <td>Rp {{ number_format($sumGross, 0, ',', '.') }}</td>
                    <td class="col-center">-</td>
                    <td>Rp {{ number_format($sumDurPrice, 0, ',', '.') }}</td>
                    <td colspan="4"></td>
                </tr>
            </tbody>
        </table>

    {{-- View Mode: Customer Monthly Summary (Pivot) --}}
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-center" style="width: 25px;">No.</th>
                    <th class="col-left" style="width: 65px;">Kode</th>
                    <th class="col-left" style="width: 160px;">Nama Customer</th>
                    @foreach($monthKeys as $mKey)
                        <th class="col-center" style="width: 35px;">{{ $monthLabels[$mKey] ?? $mKey }} Qty</th>
                        <th style="width: 75px;">{{ $monthLabels[$mKey] ?? $mKey }} Value</th>
                    @endforeach
                    <th class="col-center" style="width: 45px;">Units</th>
                    <th style="width: 90px;">Total Value (IDR)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pivotCustomers as $idx => $c)
                    <tr>
                        <td class="col-center">{{ $idx + 1 }}</td>
                        <td class="col-left" style="font-weight: bold;">{{ $c['customer_ref'] }}</td>
                        <td class="col-left">{{ $c['customer_name'] }}</td>
                        @foreach($monthKeys as $mKey)
                            @php $mInfo = $c['months'][$mKey] ?? ['qty' => 0, 'value' => 0]; @endphp
                            <td class="col-center">{{ $mInfo['qty'] > 0 ? $mInfo['qty'] : '-' }}</td>
                            <td>{{ $mInfo['value'] > 0 ? number_format($mInfo['value'], 0, ',', '.') : '-' }}</td>
                        @endforeach
                        <td class="col-center" style="font-weight: bold;">{{ $c['total_qty'] }}</td>
                        <td style="font-weight: bold;">Rp {{ number_format($c['total_value'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 4 + (count($monthKeys) * 2) }}" class="col-center" style="padding: 20px; color: #94a3b8;">
                            Tidak ada data uninvoiced accounting yang sesuai kriteria.
                        </td>
                    </tr>
                @endforelse
                <tr class="total-row">
                    <td colspan="3" class="col-left">GRAND TOTAL ({{ count($pivotCustomers) }} Customers)</td>
                    @foreach($monthKeys as $mKey)
                        @php $mTot = $monthTotals[$mKey] ?? ['qty' => 0, 'value' => 0]; @endphp
                        <td class="col-center">{{ ($mTot['qty'] ?? 0) > 0 ? $mTot['qty'] : '-' }}</td>
                        <td>{{ ($mTot['value'] ?? 0) > 0 ? number_format($mTot['value'], 0, ',', '.') : '-' }}</td>
                    @endforeach
                    <td class="col-center">{{ $grandUnits }}</td>
                    <td>Rp {{ number_format($grandVal, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    {{-- Footer --}}
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page " . $PAGE_NUM . " of " . $PAGE_COUNT;
            $size = 7;
            $font = $fontMetrics->getFont("Helvetica");
            $width = $fontMetrics->get_text_width($text, $font, $size);
            $x = $pdf->get_width() - 40 - $width;
            $y = $pdf->get_height() - 20;
            $pdf->page_text($x, $y, $text, $font, $size, array(0.5, 0.5, 0.5));
            $pdf->page_text(25, $y, "PT. Surya Darma Perkasa - Uninvoiced Accounting Report (Strictly Confidential)", $font, $size, array(0.5, 0.5, 0.5));
        }
    </script>
</body>
</html>
