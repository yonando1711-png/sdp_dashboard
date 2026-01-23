<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Summary - SDP Stock</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; font-size: 12px; background: #fff; }
        
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
        .header h1 { font-size: 22px; margin-bottom: 5px; }
        .header .timestamp { color: rgba(255,255,255,0.8); font-size: 11px; }
        
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
        .summary-box { 
            padding: 15px; 
            text-align: center; 
            border-radius: 10px;
            border-left: 4px solid;
        }
        .summary-box.primary { background: #f0f4ff; border-color: #667eea; }
        .summary-box.success { background: #ecfdf5; border-color: #10b981; }
        .summary-box.warning { background: #fffbeb; border-color: #f59e0b; }
        .summary-box.danger { background: #fef2f2; border-color: #ef4444; }
        .summary-box.info { background: #ecfeff; border-color: #06b6d4; }
        
        .summary-box .label { font-size: 10px; color: #666; text-transform: uppercase; font-weight: 600; }
        .summary-box .value { font-size: 24px; font-weight: bold; color: #1e293b; }
        
        .section { margin-bottom: 20px; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .section-title { 
            font-weight: bold; 
            font-size: 14px; 
            padding: 12px 15px;
            color: white;
        }
        .section-title.success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .section-title.warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .section-title.danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .section-title.info { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 15px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-size: 10px; text-transform: uppercase; color: #6b7280; }
        tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }
        .badge { 
            display: inline-block; 
            padding: 3px 10px; 
            border-radius: 50px; 
            font-size: 11px; 
            font-weight: 600;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #cffafe; color: #155e75; }
        
        .print-btn { 
            position: fixed; 
            top: 15px; 
            right: 15px; 
            padding: 10px 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff; 
            border: none; 
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(102,126,234,0.4);
        }
        .print-btn:hover { transform: translateY(-1px); }
        
        .footer { text-align: center; color: #9ca3af; font-size: 10px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #e5e7eb; }
        
        @media print {
            .print-btn { display: none; }
            body { padding: 10px; }
            .section { box-shadow: none; border: 1px solid #e5e7eb; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Print Summary</button>
    
    <div class="header">
        <h1>SDP Stock Summary Report</h1>
        @if(isset($metadata['imported_at']))
        <div class="timestamp">Data as of: {{ \Carbon\Carbon::parse($metadata['imported_at'])->format('F d, Y \a\t g:i A') }}</div>
        @endif
    </div>

    <div class="summary-grid">
        <div class="summary-box primary">
            <div class="label">Total Active Stock</div>
            <div class="value">{{ number_format($summary['sdp_stock']) }}</div>
        </div>
        <div class="summary-box success">
            <div class="label">In Stock</div>
            <div class="value">{{ number_format($summary['in_stock']['total']) }}</div>
        </div>
        <div class="summary-box warning">
            <div class="label">Rented</div>
            <div class="value">{{ number_format($summary['rented_in_customer']['total']) }}</div>
        </div>
        <div class="summary-box danger">
            <div class="label">In Service</div>
            <div class="value">{{ number_format($summary['stock_external_service']['total'] + $summary['stock_internal_service']['total'] + ($summary['stock_insurance']['total'] ?? 0)) }}</div>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-box info">
            <div class="label">SDP Owned</div>
            <div class="value">{{ number_format($summary['sdp_stock'] - $summary['vendor_rent']) }}</div>
        </div>
        <div class="summary-box info">
            <div class="label">Vendor Rent</div>
            <div class="value">{{ number_format($summary['vendor_rent']) }}</div>
        </div>
        <div class="summary-box danger">
            <div class="label">External Service</div>
            <div class="value">{{ number_format($summary['stock_external_service']['total']) }}</div>
        </div>
        <div class="summary-box info">
            <div class="label">Internal Service</div>
            <div class="value">{{ number_format($summary['stock_internal_service']['total']) }}</div>
        </div>
        <div class="summary-box warning" style="border-color: #8b5cf6;">
            <div class="label">Insurance</div>
            <div class="value">{{ number_format($summary['stock_insurance']['total'] ?? 0) }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title success">In Stock Breakdown</div>
        <table>
            <tr><th>Location</th><th class="text-right">Count</th></tr>
            @if(isset($summary['in_stock']['details']['SDP/OPERATION']))
            <tr><td>SDP/OPERATION</td><td class="text-right"><span class="badge badge-success">{{ $summary['in_stock']['details']['SDP/OPERATION']['count'] }}</span></td></tr>
            @endif
            @if(isset($summary['in_stock']['details']['locations']))
                @foreach($summary['in_stock']['details']['locations'] as $loc => $val)
                <tr><td>{{ $loc }}</td><td class="text-right"><span class="badge badge-success">{{ $val }}</span></td></tr>
                @endforeach
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title warning">Rented Breakdown</div>
        <table>
            <tr><th>Category</th><th class="text-right">Count</th></tr>
            @foreach($summary['rented_in_customer']['details'] as $desc => $val)
            <tr><td>{{ $desc }}</td><td class="text-right"><span class="badge badge-warning">{{ $val }}</span></td></tr>
            @endforeach
        </table>
    </div>

    <div class="section">
        <div class="section-title danger">External Service Breakdown</div>
        <table>
            <tr><th>Provider</th><th class="text-right">Count</th></tr>
            @foreach($summary['stock_external_service']['details'] as $desc => $val)
            <tr><td>{{ $desc }}</td><td class="text-right"><span class="badge badge-danger">{{ $val }}</span></td></tr>
            @endforeach
        </table>
    </div>

    <div class="section">
        <div class="section-title info">Internal Service Breakdown</div>
        <table>
            <tr><th>Category</th><th class="text-right">Count</th></tr>
            @foreach($summary['stock_internal_service']['details'] as $desc => $val)
            <tr><td>{{ $desc }}</td><td class="text-right"><span class="badge badge-info">{{ $val }}</span></td></tr>
            @endforeach
        </table>
    </div>

    <div class="section">
        <div class="section-title" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">Insurance Breakdown</div>
        <table>
            <tr><th>Category</th><th class="text-right">Count</th></tr>
            @foreach(($summary['stock_insurance']['details'] ?? []) as $desc => $val)
            <tr><td>{{ $desc }}</td><td class="text-right"><span class="badge" style="background: #ede9fe; color: #5b21b6;">{{ $val }}</span></td></tr>
            @endforeach
        </table>
    </div>

    <div class="footer">
        Generated by SDP DASHBOARD
    </div>
</body>
</html>
