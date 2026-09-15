<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>List of Rented (SMD) Export</title>
    <style>
        @page {
            margin: 15px;
            size: A4 landscape;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 7.5px;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 10px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 8px;
        }
        .header-title {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
        }
        .header-sub {
            font-size: 8px;
            color: #64748b;
            margin-top: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 4px 3px;
            font-size: 7px;
            border: 1px solid #1e293b;
            word-wrap: break-word;
            text-transform: uppercase;
        }
        td {
            padding: 3.5px 3px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
            word-wrap: break-word;
            font-size: 7px;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', monospace; }
        
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-pickedup { background: #fef08a; color: #854d0e; }
        .badge-returned { background: #fecaca; color: #991b1b; }
        .badge-reserved { background: #bbf7d0; color: #166534; }
        .badge-quotation { background: #bfdbfe; color: #1e40af; }
        .badge-cancelled { background: #e2e8f0; color: #475569; }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 7px;
            color: #94a3b8;
            text-align: right;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table style="border: none; margin: 0; width: 100%;">
            <tr style="background: none;">
                <td style="border: none; padding: 0;">
                    <h1 class="header-title">List of Rented (SMD) &mdash; Sales Management Division</h1>
                    <div class="header-sub">
                        Export Date: {{ date('d M Y H:i') }} | Total Records: {{ count($rentals) }} units
                        @if(!empty($salespersonFilter)) | Salesperson: {{ $salespersonFilter }} @endif
                        @if(!empty($salesTeamFilter)) | Sales Team: {{ $salesTeamFilter }} @endif
                        @if(!empty($statusFilter)) | Status: {{ $statusFilter }} @endif
                        @if(!empty($search)) | Search: "{{ $search }}" @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 7%;">Rental ID</th>
                <th style="width: 7.5%;">Salesperson</th>
                <th style="width: 7.5%;">Sales Team</th>
                <th style="width: 12%;">Customer</th>
                <th style="width: 6.5%;">Unit/Lot</th>
                <th style="width: 13%;">Product</th>
                <th style="width: 6.5%;">Lokasi Pemakaian</th>
                <th style="width: 6%;">Start Sewa</th>
                <th style="width: 6%;">End Sewa</th>
                @if($canViewLastInvoiceDate)
                    <th style="width: 6%;">Last Invoice</th>
                @endif
                <th style="width: 7%;" class="text-right">Harga / Bln</th>
                <th style="width: 7.5%;" class="text-right">Total Harga</th>
                <th style="width: 5.5%;" class="text-center">Status</th>
                <th style="width: 7%;">Kontrak</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rentals as $rental)
                @php
                    $statusClass = strtolower($rental->status ?? '');
                @endphp
                <tr>
                    <td class="font-bold font-mono" style="color: #4338ca;">{{ $rental->rental_id }}</td>
                    <td class="font-bold">{{ $rental->salesperson ?: '-' }}</td>
                    <td style="color: #64748b;">{{ $rental->sales_team ?: '-' }}</td>
                    <td class="font-bold">{{ $rental->current_customer ?: '-' }}</td>
                    <td class="font-mono">{{ $rental->lot_number ?: '-' }}</td>
                    <td>{{ $rental->product ?: '-' }}</td>
                    <td class="font-bold" style="color: #0f172a;">{{ $rental->city ?: '-' }}</td>
                    <td class="font-mono">{{ $rental->actual_start_rental ? \Carbon\Carbon::parse($rental->actual_start_rental)->format('d-m-Y') : '-' }}</td>
                    <td class="font-mono">{{ $rental->actual_end_rental ? \Carbon\Carbon::parse($rental->actual_end_rental)->format('d-m-Y') : '-' }}</td>
                    @if($canViewLastInvoiceDate)
                        <td class="font-mono" style="color: #0891b2;">{{ $rental->last_invoice_date ? \Carbon\Carbon::parse($rental->last_invoice_date)->format('d-m-Y') : '-' }}</td>
                    @endif
                    <td class="text-right font-mono font-bold" style="color: #1e293b;">
                        {{ $rental->price ? 'Rp ' . number_format($rental->price, 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-right font-mono font-bold" style="color: #059669;">
                        {{ $rental->amount_total ? 'Rp ' . number_format($rental->amount_total, 0, ',', '.') : ($rental->total_price ? 'Rp ' . number_format($rental->total_price, 0, ',', '.') : '-') }}
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $statusClass }}">{{ $rental->status ?: 'Unknown' }}</span>
                    </td>
                    <td class="font-mono">{{ $rental->contract_ref ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canViewLastInvoiceDate ? 14 : 13 }}" class="text-center" style="padding: 15px; color: #64748b;">
                        No rental records found matching the specified criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        SDP Dashboard &bull; List of Rented (SMD) &bull; Page printed automatically on {{ date('d/m/Y H:i') }}
    </div>
</body>
</html>
