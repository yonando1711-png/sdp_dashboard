<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LotSerial Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .summary-table th, .summary-table td {
            vertical-align: middle;
        }
        .indent-1 { padding-left: 20px; }
        .indent-2 { padding-left: 40px; }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h1 class="mb-4">LotSerial Summary Generator</h1>

        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('summary.generate') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="file" class="form-label">Upload Excel File (LotSerial Summary)</label>
                        <input class="form-control" type="file" id="file" name="file" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Generate Summary</button>
                </form>
            </div>
        </div>

        @if(isset($summary))
        <div class="card">
            <div class="card-header">
                Summary Result
            </div>
            <div class="card-body">
                <table class="table table-bordered summary-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Vendor Rent -->
                        <tr>
                            <td><strong>Vendor Rent</strong></td>
                            <td></td>
                            <td>{{ $summary['vendor_rent'] }}</td>
                        </tr>
                        
                        <!-- SDP Stock -->
                        <tr class="table-primary">
                            <td><strong>SDP Stock</strong></td>
                            <td>Total On Hand Quantity</td>
                            <td>{{ $summary['sdp_stock'] }}</td>
                        </tr>

                        <!-- In Stock -->
                        <tr>
                            <td class="indent-1">In Stock</td>
                            <td></td>
                            <td>{{ $summary['in_stock']['total'] }}</td>
                        </tr>
                        @if(isset($summary['in_stock']['details']['SDP/OPERATION']))
                        <tr>
                             <td class="indent-2">SDP/OPERATION</td>
                             <td>Operation</td>
                             <td>{{ $summary['in_stock']['details']['SDP/OPERATION']['count'] }}</td>
                        </tr>
                        @endif
                        @if(isset($summary['in_stock']['details']['SDP/STOCK SOLD']))
                            <tr>
                                <td class="indent-2">SDP/STOCK SOLD</td>
                                <td>Stock for Sold (Jakarta, etc)</td>
                            </tr>
                            @foreach($summary['in_stock']['details']['SDP/STOCK SOLD'] as $loc => $val)
                            @if($val > 0)
                            <tr>
                                <td class="indent-2"></td>
                                <td>{{ $loc }}</td>
                                <td>{{ $val }}</td>
                            </tr>
                            @endif
                            @endforeach
                        @endif

                        <!-- Rented in Customer -->
                        <tr>
                            <td class="indent-1">Rented in Customer</td>
                            <td></td>
                            <td>{{ $summary['rented_in_customer']['total'] }}</td>
                        </tr>
                        @foreach($summary['rented_in_customer']['details'] as $desc => $val)
                        <tr>
                            <td class="indent-2"></td>
                            <td>{{ $desc }}</td>
                            <td>{{ $val }}</td>
                        </tr>
                        @endforeach

                        <!-- External Service -->
                         <tr>
                            <td class="indent-1">Stock in External Service</td>
                            <td></td>
                            <td>{{ $summary['stock_external_service']['total'] }}</td>
                        </tr>
                        @foreach($summary['stock_external_service']['details'] as $desc => $val)
                        <tr>
                            <td class="indent-2"></td>
                            <td>{{ $desc }}</td>
                            <td>{{ $val }}</td>
                        </tr>
                        @endforeach

                        <!-- Internal Service -->
                         <tr>
                            <td class="indent-1">Stock in internal service</td>
                            <td></td>
                            <td>{{ $summary['stock_internal_service']['total'] }}</td>
                        </tr>
                        @foreach($summary['stock_internal_service']['details'] as $desc => $val)
                        <tr>
                            <td class="indent-2"></td>
                            <td>{{ $desc }}</td>
                            <td>{{ $val }}</td>
                        </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</body>
</html>
