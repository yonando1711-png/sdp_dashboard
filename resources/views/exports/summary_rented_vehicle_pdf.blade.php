<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Summary of Rented Vehicle - PDF</title>
    <style>
        @page {
            margin: 20px 25px 25px 25px;
            size: {{ count($monthKeys) > 6 ? 'A3' : 'A4' }} landscape;
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
            font-size: 7.5px;
            border: 1px solid #334155;
        }
        table.data-table th.col-customer {
            text-align: left;
            width: 25%;
        }
        table.data-table th.col-qty {
            width: {{ count($monthKeys) > 4 ? '4%' : '5%' }};
        }
        table.data-table th.col-val {
            width: {{ count($monthKeys) > 4 ? '8%' : '10%' }};
        }
        table.data-table td {
            padding: 3.5px 4px;
            border: 1px solid #e2e8f0;
            font-size: 7.5px;
            vertical-align: middle;
        }
        table.data-table td.customer-name {
            text-align: left;
            font-weight: 500;
            color: #0f172a;
            word-wrap: break-word;
        }
        table.data-table td.number {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        table.data-table tr.cust-row td {
            background-color: #f1f5f9;
            font-weight: bold;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
        }
        table.data-table tr.veh-row td {
            background-color: #ffffff;
            font-size: 6.8px;
            color: #475569;
            border-color: #f1f5f9;
            padding: 2px 4px;
        }
        table.data-table tr.veh-row:nth-child(even) td {
            background-color: #fafafa;
        }
        table.data-table tfoot tr td {
            background-color: #e2e8f0;
            font-weight: bold;
            font-size: 8px;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            padding: 5px 4px;
        }
    </style>
</head>
<body>

    <!-- Header Block -->
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="company-name">PT. SURYA DARMA PERKASA</div>
                    <div class="report-title">Summary of Rented Vehicle (Untaxed){{ !empty($includeVehicles) ? ' — Detailed Fleet Breakdown' : ' — Customer Summary' }}</div>
                    @if($changesOnly)
                        <div class="badge-filter">Filtered: Period & Price Changes Only</div>
                    @endif
                    @if($search)
                        <div class="badge-filter">Search: "{{ $search }}"</div>
                    @endif
                </td>
                <td class="meta-info">
                    <div><strong>Period Range:</strong> {{ \Carbon\Carbon::parse($startMonth . '-01')->format('M Y') }} to {{ \Carbon\Carbon::parse($endMonth . '-01')->format('M Y') }}</div>
                    <div><strong>Generated:</strong> {{ now()->format('d M Y H:i') }}</div>
                    <div><strong>Currency:</strong> IDR (Normalized Monthly Rates)</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Summary KPI Cards -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Total Customers</div>
                <div class="kpi-value">{{ number_format(count($customers)) }} Customers</div>
            </td>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Active Period Months</div>
                <div class="kpi-value">{{ count($monthKeys) }} Months</div>
            </td>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Grand Total Revenue</div>
                <div class="kpi-value">Rp {{ number_format($totals['grand_total_value'] ?? 0, 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-customer">Customer</th>
                @foreach ($monthKeys as $mKey)
                    @php $label = $monthLabels[$mKey] ?? $mKey; @endphp
                    <th class="col-qty">{{ $label }} Qty</th>
                    <th class="col-val">{{ $label }} Value</th>
                @endforeach
                <th class="col-qty">Max Qty</th>
                <th class="col-val">Total Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $c)
                <tr @if(!empty($includeVehicles)) class="cust-row" @endif>
                    <td class="customer-name" @if(!empty($includeVehicles)) style="font-weight: bold;" @endif>{{ $c['customer_key'] ?? $c['customer_name'] }}</td>
                    @foreach ($monthKeys as $mKey)
                        @php $mInfo = $c['months'][$mKey] ?? ['qty' => 0, 'value' => 0]; @endphp
                        <td class="number">{{ number_format($mInfo['qty'] ?? 0) }}</td>
                        <td class="number">Rp {{ number_format($mInfo['value'] ?? 0, 0, ',', '.') }}</td>
                    @endforeach
                    <td class="number" style="font-weight: bold;">{{ number_format($c['max_qty'] ?? 0) }}</td>
                    <td class="number" style="font-weight: bold;">Rp {{ number_format($c['total_value'] ?? 0, 0, ',', '.') }}</td>
                </tr>

                @if(!empty($includeVehicles) && !empty($c['vehicles']))
                    @foreach ($c['vehicles'] as $v)
                        <tr class="veh-row">
                            <td style="padding-left: 12px;">
                                ↳ <strong>{{ $v['nopol'] ?? '-' }}</strong>
                                @if(!empty($v['so'])) <span style="color: #64748b;">[{{ $v['so'] }}]</span> @endif
                                @if(!empty($v['product'])) <span style="color: #475569;">{{ $v['product'] }}</span> @endif
                            </td>
                            @foreach ($monthKeys as $mKey)
                                @php
                                    $mU = $v['months'][$mKey] ?? null;
                                    $active = !empty($mU['active']);
                                    $rate = $active ? ($mU['monthly_rate'] ?? 0) : 0;
                                @endphp
                                <td class="number" style="{{ $active ? 'font-weight: 600;' : 'color: #94a3b8;' }}">{{ $active ? 1 : 0 }}</td>
                                <td class="number" style="{{ $active ? 'color: #1e293b;' : 'color: #94a3b8;' }}">
                                    {{ $active ? 'Rp ' . number_format($rate, 0, ',', '.') : '-' }}
                                </td>
                            @endforeach
                            <td class="number" style="font-weight: 500;">{{ ($v['total_value'] ?? 0) > 0 ? 1 : 0 }}</td>
                            <td class="number" style="font-weight: 500;">Rp {{ number_format($v['total_value'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endif
            @empty
                <tr>
                    <td colspan="{{ 1 + (count($monthKeys) * 2) + 2 }}" style="text-align: center; padding: 15px; color: #64748b;">
                        No customer rental data found for the selected period range and filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if (!empty($customers))
            <tfoot>
                <tr>
                    <td style="text-align: left;">Grand Total</td>
                    @foreach ($monthKeys as $mKey)
                        @php $mTot = $totals['months'][$mKey] ?? ['qty' => 0, 'value' => 0]; @endphp
                        <td class="number">{{ number_format($mTot['qty'] ?? 0) }}</td>
                        <td class="number">Rp {{ number_format($mTot['value'] ?? 0, 0, ',', '.') }}</td>
                    @endforeach
                    <td class="number">-</td>
                    <td class="number">Rp {{ number_format($totals['grand_total_value'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- DomPDF Page Number Scriptlet -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $size = 7.5;
            $font = $fontMetrics->getFont("Helvetica");
            $width = $fontMetrics->get_text_width($text, $font, $size);
            $pdf->page_text($pdf->get_width() - $width - 25, $pdf->get_height() - 18, $text, $font, $size, array(0.4, 0.4, 0.4));
            $pdf->page_text(25, $pdf->get_height() - 18, "Confidential - PT. Surya Darma Perkasa | Summary of Rented Vehicle", $font, $size, array(0.45, 0.45, 0.45));
        }
    </script>
</body>
</html>
