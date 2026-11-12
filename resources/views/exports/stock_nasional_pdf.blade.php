<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Harian Jumlah Mobil - Stock Nasional</title>
    <style>
        @page {
            margin: 22px 28px 22px 28px;
            size: A4 portrait;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }
        .header-box {
            width: 48%;
            border: 1.5px solid #0f172a;
            padding: 6px 10px;
            margin-bottom: 12px;
        }
        .header-box .title {
            font-size: 10px;
            font-weight: bold;
            line-height: 1.35;
        }
        .header-box .date {
            font-size: 9px;
            margin-top: 2px;
        }
        table.report-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.report-table th,
        table.report-table td {
            border: 1px solid #475569;
            padding: 2.5px 6px;
            font-size: 8.5px;
            line-height: 1.25;
        }
        table.report-table th {
            background-color: #f8fafc;
            color: #0f172a;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        .col-ket { width: 42%; text-align: left; }
        .col-tot { width: 10%; text-align: right; }
        .col-pct1 { width: 24%; text-align: center; }
        .col-pct2 { width: 24%; text-align: center; }

        .row-section td {
            font-weight: bold;
            background-color: #f8fafc;
        }
        .row-group td {
            font-weight: bold;
        }
        .row-subtotal td {
            font-weight: bold;
            background-color: #f1f5f9;
        }
        .row-subtotal td.col-ket {
            text-align: center;
        }
        .indent-item {
            padding-left: 14px !important;
        }
        .muted-zero {
            color: #94a3b8;
        }

        .bottom-totals {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .bottom-totals td {
            padding: 2.5px 6px;
            font-size: 9px;
        }
        .bottom-totals .bt-label {
            width: 42%;
            text-align: center;
        }
        .bottom-totals .bt-val {
            width: 10%;
            text-align: right;
            font-weight: bold;
        }
        .bottom-totals .bt-empty { width: 24%; }
        .bottom-totals .bt-pct {
            width: 24%;
            text-align: center;
            font-weight: bold;
        }

        .signature-block {
            margin-top: 22px;
            width: 100%;
            font-size: 9px;
        }
        .signature-block td {
            vertical-align: top;
        }
        .sig-space {
            height: 44px;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
        }
        .sig-role {
            font-weight: bold;
        }
    </style>
</head>
<body>
    @php
        $reportDate = $report['report_date_formatted'] ?? date('d F Y');
        $totals = $report['totals'] ?? [];
        $fmtInt = fn($v) => number_format((int)$v, 0, ',', '.');
        $fmtPct = fn($v) => number_format((float)$v, 2, ',', '.') . '%';
    @endphp

    <div class="header-box">
        <div class="title">PT. SURYA DARMA PERKASA</div>
        <div class="title">LAPORAN HARIAN JUMLAH MOBIL</div>
        <div class="date">Per Tanggal {{ $reportDate }}</div>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th rowspan="2" class="col-ket">KETERANGAN</th>
                <th rowspan="2" class="col-tot">TOTAL</th>
                <th class="col-pct1">PERSENTASE DARI</th>
                <th class="col-pct2">PERSENTASE DARI</th>
            </tr>
            <tr>
                <th class="col-pct1">TOTAL UNIT TERSEWA</th>
                <th class="col-pct2">TOTAL UNIT KESELURUHAN</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['rows'] ?? [] as $r)
                @php
                    $rowClass = match($r['type']) {
                        'section' => 'row-section',
                        'group' => 'row-group',
                        'subtotal' => 'row-subtotal',
                        default => 'row-item',
                    };
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="col-ket {{ $r['type'] === 'item' ? 'indent-item' : '' }}">
                        {{ $r['label'] }}
                    </td>
                    <td class="col-tot {{ ($r['total'] === 0 && $r['type'] === 'item') ? 'muted-zero' : '' }}">
                        {{ $r['total'] !== null ? $fmtInt($r['total']) : '' }}
                    </td>
                    <td class="col-pct1">
                        {{ $r['pct_rented'] !== null ? $fmtPct($r['pct_rented']) : '' }}
                    </td>
                    <td class="col-pct2">
                        {{ $r['pct_total'] !== null ? $fmtPct($r['pct_total']) : '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="bottom-totals">
        <tr>
            <td class="bt-label" style="font-weight: bold;">Jumlah Mobil Keseluruhan</td>
            <td class="bt-val">{{ $fmtInt($totals['jumlah_keseluruhan'] ?? 0) }}</td>
            <td class="bt-empty"></td>
            <td class="bt-pct">100,00%</td>
        </tr>
        <tr>
            <td class="bt-label">Mobil Sewa External</td>
            <td class="bt-val" style="font-weight: normal;">{{ $fmtInt($totals['mobil_sewa_external'] ?? 0) }}</td>
            <td class="bt-empty"></td>
            <td class="bt-empty"></td>
        </tr>
        <tr>
            <td class="bt-label" style="font-weight: bold;">Jumlah Mobil SDP</td>
            <td class="bt-val">{{ $fmtInt($totals['jumlah_mobil_sdp'] ?? 0) }}</td>
            <td class="bt-empty"></td>
            <td class="bt-empty"></td>
        </tr>
    </table>

    <table class="signature-block">
        <tr>
            <td style="width: 45%;">
                <div style="font-weight: bold;">Jakarta, {{ $reportDate }}</div>
                <div style="font-weight: bold; margin-top: 2px;">Dibuat oleh,</div>
                <div class="sig-space"></div>
                <div class="sig-role">STOCK CAR</div>
            </td>
            <td style="width: 55%;">
                <div>&nbsp;</div>
                <div style="font-weight: bold; margin-top: 2px;">Disetujui oleh,</div>
                <div class="sig-space"></div>
                <div class="sig-name">Aldian Prayoga Darwis</div>
                <div class="sig-role">FLEET OPERATIONS MANAGER</div>
            </td>
        </tr>
    </table>
</body>
</html>
