<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SALES ORDER LIST - {{ $batch->batch_no ?? 'DRAFT' }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 10mm 12mm 10mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8px;
            color: #111;
            margin: 0;
            padding: 14px 18px;
            line-height: 1.15;
            background-color: #ffffff;
        }
        .page-break {
            page-break-before: always;
        }
        @media screen {
            .page-break {
                margin-top: 25px;
                padding-top: 10px;
            }
        }
        .header-title-box {
            position: relative;
            text-align: center;
            margin-bottom: 6px;
        }
        .header-title {
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
            letter-spacing: 0.5px;
        }
        .duplikat-badge {
            position: absolute;
            right: 0;
            top: -2px;
            border: 2px solid #b91c1c;
            color: #b91c1c;
            font-size: 11px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 4px;
            letter-spacing: 1px;
            background-color: #fef2f2;
        }
        .meta-info {
            margin-bottom: 8px;
            font-size: 8.5px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .section-header {
            background-color: #e5e7eb;
            font-weight: bold;
            font-size: 8.5px;
            padding: 4px 6px;
            border: 1px solid #9ca3af;
            margin-top: 4px;
            margin-bottom: 3px;
            text-transform: uppercase;
            page-break-after: avoid;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #9ca3af;
            padding: 3px 4px;
            text-align: left;
        }
        table.data-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            text-align: center;
            font-size: 7.5px;
        }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .signatures {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signatures td {
            border: 1px solid #9ca3af;
            text-align: center;
            vertical-align: top;
            padding: 4px;
            width: 33.33%;
        }
        .sig-header {
            font-weight: bold;
            background-color: #f3f4f6;
            font-size: 8.5px;
            padding-bottom: 3px;
            border-bottom: 1px solid #9ca3af;
        }
        .sig-role {
            font-weight: bold;
            font-size: 8px;
            margin-top: 3px;
        }
        .sig-space {
            height: 48px;
        }
        .sig-name {
            font-size: 7.5px;
            border-top: 1px dotted #9ca3af;
            padding-top: 3px;
            color: #555;
        }
    </style>
</head>
<body>

@php $renderedSections = 0; @endphp

    {{-- ======================================================== --}}
    {{-- 1. SO CONFIRM SUBSCRIPTION                               --}}
    {{-- ======================================================== --}}
    @if($reserved_subscription->isNotEmpty())
    @php $renderedSections++; @endphp
    <div class="section-page">
        <div class="header-title-box">
            <span class="header-title">SALES ORDER LIST</span>
            @if(!empty($duplicate_count) && $duplicate_count > 0)
                <div class="duplikat-badge">DUPLIKAT {{ $duplicate_count }}</div>
            @endif
        </div>

        <div class="meta-info">
            <table class="meta-table">
                <tr>
                    <td style="width: 130px;"><strong>SALES TEAM</strong></td>
                    <td style="width: 280px;">: {{ $sales_team ?? 'ALL TEAMS' }}</td>
                    <td style="width: 130px;"><strong>BATCH NO</strong></td>
                    <td>: {{ $batch->batch_no ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>TANGGAL CETAK</strong></td>
                    <td>: {{ $printed_at ?? date('d/m/Y H:i') }} WIB</td>
                    <td><strong>DICETAK OLEH</strong></td>
                    <td>: {{ $batch->printed_by_name ?? auth()->user()->name ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="section-header">JENIS TRANSAKSI: SO CONFIRM SUBSCRIPTION</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 22px;">NO</th>
                    <th style="width: 85px;">NO SO</th>
                    <th style="width: 70px;">ORDER DATE</th>
                    <th>CUSTOMER</th>
                    <th style="width: 75px;">DURATION (MONTHS)</th>
                    <th style="width: 70px;">ACTUAL START</th>
                    <th style="width: 75px;">RESERVED LOT</th>
                    <th style="width: 55px;">RBO</th>
                    <th style="width: 80px;">PREVIOUS SO</th>
                    <th style="width: 100px;">SOURCE DOKUMEN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reserved_subscription as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td style="font-weight: bold; color: #1e3a8a;">{{ $item->so_number }}</td>
                    <td class="text-center">{{ $item->order_date ? \Carbon\Carbon::parse($item->order_date)->format('d/m/Y') : ($item->created_at ? $item->created_at->format('d/m/Y') : '-') }}</td>
                    <td>{{ $item->customer_name }}</td>
                    <td class="text-center">{{ $item->duration_months ?? '-' }}</td>
                    <td class="text-center">{{ $item->start_date ? \Carbon\Carbon::parse($item->start_date)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">{{ $item->reserved_lot ?? '-' }}</td>
                    <td class="text-center">{{ $item->rbo ?? '-' }}</td>
                    <td class="text-center">{{ $item->previous_so ?? '-' }}</td>
                    <td class="text-center">{{ $item->source_doc ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ======================================================== --}}
    {{-- 2. SO CONFIRM REGULER                                    --}}
    {{-- ======================================================== --}}
    @if($reserved_reguler->isNotEmpty())
    <div class="section-page {{ $renderedSections > 0 ? 'page-break' : '' }}">
        @php $renderedSections++; @endphp
        <div class="header-title-box">
            <span class="header-title">SALES ORDER LIST</span>
            @if(!empty($duplicate_count) && $duplicate_count > 0)
                <div class="duplikat-badge">DUPLIKAT {{ $duplicate_count }}</div>
            @endif
        </div>

        <div class="meta-info">
            <table class="meta-table">
                <tr>
                    <td style="width: 130px;"><strong>SALES TEAM</strong></td>
                    <td style="width: 280px;">: {{ $sales_team ?? 'ALL TEAMS' }}</td>
                    <td style="width: 130px;"><strong>BATCH NO</strong></td>
                    <td>: {{ $batch->batch_no ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>TANGGAL CETAK</strong></td>
                    <td>: {{ $printed_at ?? date('d/m/Y H:i') }} WIB</td>
                    <td><strong>DICETAK OLEH</strong></td>
                    <td>: {{ $batch->printed_by_name ?? auth()->user()->name ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="section-header">JENIS TRANSAKSI: SO CONFIRM REGULER</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 22px;">NO</th>
                    <th style="width: 90px;">NO SO</th>
                    <th style="width: 75px;">ORDER DATE</th>
                    <th>CUSTOMER</th>
                    <th style="width: 110px;">DURATION (HOURLY / DAYS)</th>
                    <th style="width: 75px;">ACTUAL START</th>
                    <th style="width: 85px;">RESERVED LOT</th>
                    <th style="width: 120px;">SOURCE DOKUMEN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reserved_reguler as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td style="font-weight: bold; color: #1e3a8a;">{{ $item->so_number }}</td>
                    <td class="text-center">{{ $item->order_date ? \Carbon\Carbon::parse($item->order_date)->format('d/m/Y') : ($item->created_at ? $item->created_at->format('d/m/Y') : '-') }}</td>
                    <td>{{ $item->customer_name }}</td>
                    <td class="text-center">{{ $item->duration_months ?? '-' }}</td>
                    <td class="text-center">{{ $item->start_date ? \Carbon\Carbon::parse($item->start_date)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">{{ $item->reserved_lot ?? '-' }}</td>
                    <td class="text-center">{{ $item->source_doc ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ======================================================== --}}
    {{-- 3. TERMINATE & FORCE CLOSED                              --}}
    {{-- ======================================================== --}}
    @if(!empty($terminate_and_force_close) && $terminate_and_force_close->isNotEmpty())
    <div class="section-page {{ $renderedSections > 0 ? 'page-break' : '' }}">
        @php $renderedSections++; @endphp
        <div class="header-title-box">
            <span class="header-title">SALES ORDER LIST</span>
            @if(!empty($duplicate_count) && $duplicate_count > 0)
                <div class="duplikat-badge">DUPLIKAT {{ $duplicate_count }}</div>
            @endif
        </div>

        <div class="meta-info">
            <table class="meta-table">
                <tr>
                    <td style="width: 130px;"><strong>SALES TEAM</strong></td>
                    <td style="width: 280px;">: {{ $sales_team ?? 'ALL TEAMS' }}</td>
                    <td style="width: 130px;"><strong>BATCH NO</strong></td>
                    <td>: {{ $batch->batch_no ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>TANGGAL CETAK</strong></td>
                    <td>: {{ $printed_at ?? date('d/m/Y H:i') }} WIB</td>
                    <td><strong>DICETAK OLEH</strong></td>
                    <td>: {{ $batch->printed_by_name ?? auth()->user()->name ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="section-header">JENIS TRANSAKSI: TERMINATE &amp; FORCE CLOSED</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20px;">NO</th>
                    <th style="width: 75px;">NO SO</th>
                    <th style="width: 60px;">UPDATE DATE</th>
                    <th>CUSTOMER</th>
                    <th style="width: 65px;">END RENTAL PERIOD</th>
                    <th style="width: 60px;">ACTUAL END</th>
                    <th style="width: 75px;">PRODUK</th>
                    <th style="width: 60px;">RESERVED LOT</th>
                    <th style="width: 45px;">TAHUN KEND</th>
                    <th style="width: 65px;">ACTUAL MASA SEWA (BLN)</th>
                    <th style="width: 65px;">SISA MASA SEWA (BLN)</th>
                    <th style="width: 80px;">SOURCE DOKUMEN</th>
                </tr>
            </thead>
            <tbody>
                {{-- SUB-HEADER A: TERMINATE --}}
                @if(!empty($terminate) && $terminate->isNotEmpty())
                <tr style="background-color: #fee2e2; font-weight: bold;">
                    <td colspan="12" style="padding: 3px 6px; font-size: 7.5px; color: #991b1b; text-align: left;">
                        ▶ TERMINATE ({{ $terminate->count() }} SO)
                    </td>
                </tr>
                @foreach($terminate as $item)
                @php
                    $actualMonths = ($item->start_date && $item->actual_end_rental_date)
                        ? max(0, (int) round(\Carbon\Carbon::parse($item->start_date)->diffInMonths(\Carbon\Carbon::parse($item->actual_end_rental_date)))) . ' BLN'
                        : '-';
                    $sisaMonths = ($item->actual_end_rental_date && $item->end_date)
                        ? max(0, (int) round(\Carbon\Carbon::parse($item->actual_end_rental_date)->diffInMonths(\Carbon\Carbon::parse($item->end_date)))) . ' BLN'
                        : '-';
                    preg_match('/20\d\d/', $item->product ?? '', $yearMatches);
                    $tahunKend = $item->vehicle_year ?? (!empty($yearMatches[0]) ? $yearMatches[0] : '-');
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td style="font-weight: bold; color: #1e3a8a;">{{ $item->so_number }}</td>
                    <td class="text-center">{{ $item->order_date ? \Carbon\Carbon::parse($item->order_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $item->customer_name }}</td>
                    <td class="text-center">{{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center" style="font-weight: bold; color: #b91c1c;">{{ $item->actual_end_rental_date ? \Carbon\Carbon::parse($item->actual_end_rental_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $item->product ?? '-' }}</td>
                    <td class="text-center">{{ $item->reserved_lot ?? '-' }}</td>
                    <td class="text-center">{{ $tahunKend }}</td>
                    <td class="text-center">{{ $actualMonths }}</td>
                    <td class="text-center">{{ $sisaMonths }}</td>
                    <td class="text-center">{{ $item->source_doc ?? '-' }}</td>
                </tr>
                @endforeach
                @endif

                {{-- SUB-HEADER B: FORCE CLOSED --}}
                @if(!empty($force_close) && $force_close->isNotEmpty())
                <tr style="background-color: #fef3c7; font-weight: bold;">
                    <td colspan="12" style="padding: 3px 6px; font-size: 7.5px; color: #92400e; text-align: left;">
                        ▶ FORCE CLOSED ({{ $force_close->count() }} SO)
                    </td>
                </tr>
                @foreach($force_close as $item)
                @php
                    $actualMonths = ($item->start_date && $item->actual_end_rental_date)
                        ? max(0, (int) round(\Carbon\Carbon::parse($item->start_date)->diffInMonths(\Carbon\Carbon::parse($item->actual_end_rental_date)))) . ' BLN'
                        : '-';
                    $sisaMonths = ($item->actual_end_rental_date && $item->end_date)
                        ? max(0, (int) round(\Carbon\Carbon::parse($item->actual_end_rental_date)->diffInMonths(\Carbon\Carbon::parse($item->end_date)))) . ' BLN'
                        : '-';
                    preg_match('/20\d\d/', $item->product ?? '', $yearMatches);
                    $tahunKend = $item->vehicle_year ?? (!empty($yearMatches[0]) ? $yearMatches[0] : '-');
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td style="font-weight: bold; color: #1e3a8a;">{{ $item->so_number }}</td>
                    <td class="text-center">{{ $item->order_date ? \Carbon\Carbon::parse($item->order_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $item->customer_name }}</td>
                    <td class="text-center">{{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center" style="font-weight: bold; color: #b91c1c;">{{ $item->actual_end_rental_date ? \Carbon\Carbon::parse($item->actual_end_rental_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $item->product ?? '-' }}</td>
                    <td class="text-center">{{ $item->reserved_lot ?? '-' }}</td>
                    <td class="text-center">{{ $tahunKend }}</td>
                    <td class="text-center">{{ $actualMonths }}</td>
                    <td class="text-center">{{ $sisaMonths }}</td>
                    <td class="text-center">{{ $item->source_doc ?? '-' }}</td>
                </tr>
                @endforeach
                @endif
            </tbody>
        </table>
    </div>
    @endif

    {{-- ======================================================== --}}
    {{-- 4. EXTEND & UPDATE PRICE                                 --}}
    {{-- ======================================================== --}}
    @if(!empty($extend_and_update_price) && $extend_and_update_price->isNotEmpty())
    <div class="section-page {{ $renderedSections > 0 ? 'page-break' : '' }}">
        @php $renderedSections++; @endphp
        <div class="header-title-box">
            <span class="header-title">SALES ORDER LIST</span>
            @if(!empty($duplicate_count) && $duplicate_count > 0)
                <div class="duplikat-badge">DUPLIKAT {{ $duplicate_count }}</div>
            @endif
        </div>

        <div class="meta-info">
            <table class="meta-table">
                <tr>
                    <td style="width: 130px;"><strong>SALES TEAM</strong></td>
                    <td style="width: 280px;">: {{ $sales_team ?? 'ALL TEAMS' }}</td>
                    <td style="width: 130px;"><strong>BATCH NO</strong></td>
                    <td>: {{ $batch->batch_no ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>TANGGAL CETAK</strong></td>
                    <td>: {{ $printed_at ?? date('d/m/Y H:i') }} WIB</td>
                    <td><strong>DICETAK OLEH</strong></td>
                    <td>: {{ $batch->printed_by_name ?? auth()->user()->name ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="section-header">JENIS TRANSAKSI: EXTEND &amp; UPDATE PRICE</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20px;">NO</th>
                    <th style="width: 80px;">NO SO</th>
                    <th style="width: 65px;">UPDATE DATE</th>
                    <th>CUSTOMER</th>
                    <th style="width: 70px;">ACTUAL END</th>
                    <th style="width: 80px;">TGL END SBLMNYA</th>
                    <th style="width: 85px;">DURATION PRICE</th>
                    <th style="width: 90px;">DURATION PRICE SBLMNYA</th>
                    <th style="width: 65px;">RESERVED LOT</th>
                    <th style="width: 80px;">SOURCE DOKUMEN</th>
                </tr>
            </thead>
            <tbody>
                {{-- SUB-HEADER A: EXTEND ONLY --}}
                @if(!empty($extend_only) && $extend_only->isNotEmpty())
                <tr style="background-color: #d1fae5; font-weight: bold;">
                    <td colspan="10" style="padding: 3px 6px; font-size: 7.5px; color: #065f46; text-align: left;">
                        ▶ EXTEND ONLY (Perpanjangan Saja) ({{ $extend_only->count() }} SO)
                    </td>
                </tr>
                @foreach($extend_only as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td style="font-weight: bold; color: #1e3a8a;">{{ $item->so_number }}</td>
                    <td class="text-center">{{ $item->order_date ? \Carbon\Carbon::parse($item->order_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $item->customer_name }}</td>
                    <td class="text-center" style="font-weight: bold; color: #047857;">{{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center" style="color: #6b7280; text-decoration: line-through;">{{ $item->previous_end_date ? \Carbon\Carbon::parse($item->previous_end_date)->format('d/m/Y') : '-' }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ $item->duration_price ? 'Rp ' . number_format($item->duration_price, 0, ',', '.') : '-' }}</td>
                    <td class="text-right" style="color: #6b7280;">
                        @if($item->previous_duration_price && (float)$item->previous_duration_price !== (float)$item->duration_price)
                            Rp {{ number_format($item->previous_duration_price, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">{{ $item->reserved_lot ?? '-' }}</td>
                    <td class="text-center">{{ $item->source_doc ?? '-' }}</td>
                </tr>
                @endforeach
                @endif

                {{-- SUB-HEADER B: UPDATE PRICE ONLY --}}
                @if(!empty($update_price_only) && $update_price_only->isNotEmpty())
                <tr style="background-color: #e0f2fe; font-weight: bold;">
                    <td colspan="10" style="padding: 3px 6px; font-size: 7.5px; color: #075985; text-align: left;">
                        ▶ UPDATE PRICE ONLY (Perubahan Harga Saja) ({{ $update_price_only->count() }} SO)
                    </td>
                </tr>
                @foreach($update_price_only as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td style="font-weight: bold; color: #1e3a8a;">{{ $item->so_number }}</td>
                    <td class="text-center">{{ $item->order_date ? \Carbon\Carbon::parse($item->order_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $item->customer_name }}</td>
                    <td class="text-center" style="font-weight: bold; color: #0284c7;">{{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center" style="color: #6b7280;">{{ $item->previous_end_date ? \Carbon\Carbon::parse($item->previous_end_date)->format('d/m/Y') : '-' }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ $item->duration_price ? 'Rp ' . number_format($item->duration_price, 0, ',', '.') : '-' }}</td>
                    <td class="text-right" style="color: #6b7280;">
                        @if($item->previous_duration_price && (float)$item->previous_duration_price !== (float)$item->duration_price)
                            Rp {{ number_format($item->previous_duration_price, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">{{ $item->reserved_lot ?? '-' }}</td>
                    <td class="text-center">{{ $item->source_doc ?? '-' }}</td>
                </tr>
                @endforeach
                @endif

                {{-- SUB-HEADER C: EXTEND & UPDATE PRICE --}}
                @if(!empty($extend_and_update_both) && $extend_and_update_both->isNotEmpty())
                <tr style="background-color: #ccfbf1; font-weight: bold;">
                    <td colspan="10" style="padding: 3px 6px; font-size: 7.5px; color: #115e59; text-align: left;">
                        ▶ EXTEND &amp; UPDATE PRICE (Perpanjangan &amp; Perubahan Harga) ({{ $extend_and_update_both->count() }} SO)
                    </td>
                </tr>
                @foreach($extend_and_update_both as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td style="font-weight: bold; color: #1e3a8a;">{{ $item->so_number }}</td>
                    <td class="text-center">{{ $item->order_date ? \Carbon\Carbon::parse($item->order_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $item->customer_name }}</td>
                    <td class="text-center" style="font-weight: bold; color: #047857;">{{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center" style="color: #6b7280; text-decoration: line-through;">{{ $item->previous_end_date ? \Carbon\Carbon::parse($item->previous_end_date)->format('d/m/Y') : '-' }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ $item->duration_price ? 'Rp ' . number_format($item->duration_price, 0, ',', '.') : '-' }}</td>
                    <td class="text-right" style="color: #6b7280;">
                        @if($item->previous_duration_price && (float)$item->previous_duration_price !== (float)$item->duration_price)
                            Rp {{ number_format($item->previous_duration_price, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">{{ $item->reserved_lot ?? '-' }}</td>
                    <td class="text-center">{{ $item->source_doc ?? '-' }}</td>
                </tr>
                @endforeach
                @endif
            </tbody>
        </table>
    </div>
    @endif

    {{-- ======================================================== --}}
    {{-- 5. SWITCH CUSTOMER                                       --}}
    {{-- ======================================================== --}}
    @if($switch_customer->isNotEmpty())
    <div class="section-page {{ $renderedSections > 0 ? 'page-break' : '' }}">
        @php $renderedSections++; @endphp
        <div class="header-title-box">
            <span class="header-title">SALES ORDER LIST</span>
            @if(!empty($duplicate_count) && $duplicate_count > 0)
                <div class="duplikat-badge">DUPLIKAT {{ $duplicate_count }}</div>
            @endif
        </div>

        <div class="meta-info">
            <table class="meta-table">
                <tr>
                    <td style="width: 130px;"><strong>SALES TEAM</strong></td>
                    <td style="width: 280px;">: {{ $sales_team ?? 'ALL TEAMS' }}</td>
                    <td style="width: 130px;"><strong>BATCH NO</strong></td>
                    <td>: {{ $batch->batch_no ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>TANGGAL CETAK</strong></td>
                    <td>: {{ $printed_at ?? date('d/m/Y H:i') }} WIB</td>
                    <td><strong>DICETAK OLEH</strong></td>
                    <td>: {{ $batch->printed_by_name ?? auth()->user()->name ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="section-header">JENIS TRANSAKSI: SWITCH CUSTOMER</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 22px;">NO</th>
                    <th style="width: 80px;">NO SO</th>
                    <th style="width: 65px;">UPDATE DATE</th>
                    <th>CUSTOMER</th>
                    <th style="width: 65px;">TGL START</th>
                    <th style="width: 65px;">TGL END</th>
                    <th style="width: 70px;">RESERVED LOT</th>
                    <th style="width: 85px;">SUPERSEEDED SO</th>
                    <th>PREVIOUS CUSTOMER</th>
                    <th style="width: 80px;">TGL END PREV CUST</th>
                    <th style="width: 80px;">SOURCE DOKUMEN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($switch_customer as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td style="font-weight: bold; color: #1e3a8a;">{{ $item->so_number }}</td>
                    <td class="text-center">{{ $item->order_date ? \Carbon\Carbon::parse($item->order_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $item->customer_name }}</td>
                    <td class="text-center">{{ $item->start_date ? \Carbon\Carbon::parse($item->start_date)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">{{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">{{ $item->reserved_lot ?? '-' }}</td>
                    <td class="text-center" style="font-weight: bold;">{{ $item->previous_so ?? '-' }}</td>
                    <td>{{ $item->previous_customer ?? '-' }}</td>
                    <td class="text-center">{{ $item->previous_end_date_cust ? \Carbon\Carbon::parse($item->previous_end_date_cust)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">{{ $item->source_doc ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Fallback empty state if no sections have data --}}
    @if($renderedSections === 0)
    <div class="section-page">
        <div class="header-title-box">
            <span class="header-title">SALES ORDER LIST</span>
        </div>
        <div class="meta-info">
            <table class="meta-table">
                <tr>
                    <td style="width: 130px;"><strong>SALES TEAM</strong></td>
                    <td style="width: 280px;">: {{ $sales_team ?? 'ALL TEAMS' }}</td>
                    <td style="width: 130px;"><strong>BATCH NO</strong></td>
                    <td>: {{ $batch->batch_no ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>TANGGAL CETAK</strong></td>
                    <td>: {{ $printed_at ?? date('d/m/Y H:i') }} WIB</td>
                    <td><strong>DICETAK OLEH</strong></td>
                    <td>: {{ $batch->printed_by_name ?? auth()->user()->name ?? '-' }}</td>
                </tr>
            </table>
        </div>
        <p style="text-align: center; color: #6b7280; padding: 20px;">Tidak ada data SO yang dipilih untuk dicetak.</p>
    </div>
    @endif

    {{-- ======================================================== --}}
    {{-- OFFICIAL SIGNATURE BLOCK (Last Page / Bottom of Doc)    --}}
    {{-- Form Rev: DIBUAT OLEH | DIPERIKSA OLEH | DISETUJUI OLEH  --}}
    {{-- Roles:    SA OFFICER  | SA MANAGER     | GENERAL MANAGER --}}
    {{-- ======================================================== --}}
    <table class="signatures">
        <tr>
            <td class="sig-header">DIBUAT OLEH</td>
            <td class="sig-header">DIPERIKSA OLEH</td>
            <td class="sig-header">DISETUJUI OLEH</td>
        </tr>
        <tr>
            <td class="sig-role">SA OFFICER</td>
            <td class="sig-role">SA MANAGER</td>
            <td class="sig-role">GENERAL MANAGER</td>
        </tr>
        <tr>
            <td class="sig-space"></td>
            <td class="sig-space"></td>
            <td class="sig-space"></td>
        </tr>
        <tr>
            <td class="sig-name">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</td>
            <td class="sig-name">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</td>
            <td class="sig-name">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</td>
        </tr>
    </table>

</body>
</html>
