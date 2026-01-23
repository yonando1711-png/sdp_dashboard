<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Pairs - Main & Replacement Vehicles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        .pair-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }
        .pair-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .vehicle-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            height: 100%;
        }
        .vehicle-main {
            border-left: 4px solid #10b981;
        }
        .vehicle-replacement {
            border-left: 4px solid #f97316;
        }
        .role-badge-main {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            font-size: 0.75rem;
            padding: 0.25em 0.75em;
            border-radius: 20px;
        }
        .role-badge-replacement {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            font-size: 0.75rem;
            padding: 0.25em 0.75em;
            border-radius: 20px;
        }
        .rental-id-badge {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            font-size: 0.85rem;
            padding: 0.35em 0.85em;
            border-radius: 6px;
        }
        .arrow-icon {
            font-size: 1.5rem;
            color: #94a3b8;
        }
        .plate-number {
            font-weight: 600;
            font-size: 1.1rem;
            color: #1e293b;
        }
        .vehicle-info {
            font-size: 0.85rem;
            color: #64748b;
        }
        .location-badge {
            font-size: 0.7rem;
            padding: 0.2em 0.5em;
            border-radius: 4px;
        }
        .loc-customer { background: #fef3c7; color: #92400e; }
        .loc-external { background: #fee2e2; color: #991b1b; }
        .loc-internal { background: #e0f2fe; color: #0369a1; }
        .loc-stock { background: #dcfce7; color: #166534; }
        .loc-other { background: #f1f5f9; color: #475569; }
        
        .summary-stat {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>SDP DASHBOARD</a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h3 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Rental Pairs</h3>
                <p class="text-muted mb-0">Vehicles with Main & Replacement relationship</p>
            </div>
            <div class="col-md-4">
                <div class="summary-stat text-center">
                    <div class="stat-number">{{ $pairsCount }}</div>
                    <div>Active Rental Pairs</div>
                </div>
            </div>
        </div>

        @if($pairsCount > 0)
        <!-- Search Box -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="searchPairs" class="form-control" placeholder="Search by Rental ID, Plate Number, or Product...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="filterRole" class="form-select">
                            <option value="">All Pairs</option>
                            <option value="customer">Replacement with Customer</option>
                            <option value="service">Main in Service</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="badge bg-secondary" id="pairCount">{{ $pairsCount }} pairs</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pairs List -->
        <div id="pairsList">
            @foreach($rentalPairs as $pair)
            @php
                $main = $pair['main_vehicle'];
                $replacements = $pair['replacement_vehicles'];
            @endphp
            <div class="pair-card p-3" data-rental-id="{{ strtolower($pair['rental_id']) }}" 
                 data-search="{{ strtolower($pair['rental_id'] . ' ' . ($main['lot_number'] ?? '') . ' ' . ($main['product'] ?? '') . ' ' . implode(' ', array_column($replacements, 'lot_number')) . ' ' . implode(' ', array_column($replacements, 'product'))) }}">
                <div class="d-flex align-items-center mb-2">
                    <span class="rental-id-badge me-3">{{ $pair['rental_id'] }}</span>
                    <small class="text-muted">{{ count($pair['vehicles']) }} vehicles linked</small>
                </div>
                
                <div class="row g-3 align-items-stretch">
                    <!-- Main Vehicle -->
                    <div class="col-md-5">
                        @if($main)
                        <div class="vehicle-card vehicle-main">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="role-badge-main"><i class="bi bi-star-fill me-1"></i>Main</span>
                                @php
                                    $loc = $main['location'] ?? '';
                                    $locClass = 'loc-other';
                                    if (stripos($loc, 'Partners/Customers') !== false) $locClass = 'loc-customer';
                                    elseif (stripos($loc, 'Partners/Vendors/Service') !== false) $locClass = 'loc-external';
                                    elseif (stripos($loc, 'Physical Locations/Service') !== false) $locClass = 'loc-internal';
                                    elseif (stripos($loc, 'STOCK') !== false) $locClass = 'loc-stock';
                                    
                                    $locShort = $loc;
                                    if ($loc === 'Partners/Customers/Rental') $locShort = 'Customer';
                                    elseif (stripos($loc, 'Partners/Vendors/Service') === 0) $locShort = 'Ext. Service';
                                    elseif ($loc === 'Physical Locations/Service') $locShort = 'Int. Service';
                                    elseif (preg_match('/^SD([A-Z]{2,3})\\//', $loc, $m)) $locShort = $m[1] . ' Stock';
                                @endphp
                                <span class="location-badge {{ $locClass }}">{{ $locShort }}</span>
                            </div>
                            <div class="plate-number mb-1">{{ $main['lot_number'] }}</div>
                            <div class="vehicle-info text-truncate" title="{{ $main['product'] }}">{{ $main['product'] }}</div>
                        </div>
                        @else
                        <div class="vehicle-card text-center text-muted py-4">
                            <i class="bi bi-question-circle fs-3"></i>
                            <div>Main vehicle not identified</div>
                        </div>
                        @endif
                    </div>
                    
                    <!-- Arrow -->
                    <div class="col-md-2 d-flex align-items-center justify-content-center">
                        <span class="arrow-icon"><i class="bi bi-arrow-left-right"></i></span>
                    </div>
                    
                    <!-- Replacement Vehicle(s) -->
                    <div class="col-md-5">
                        @foreach($replacements as $replacement)
                        <div class="vehicle-card vehicle-replacement @if(!$loop->last) mb-2 @endif">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="role-badge-replacement"><i class="bi bi-arrow-repeat me-1"></i>Replacement</span>
                                @php
                                    $loc = $replacement['location'] ?? '';
                                    $locClass = 'loc-other';
                                    if (stripos($loc, 'Partners/Customers') !== false) $locClass = 'loc-customer';
                                    elseif (stripos($loc, 'Partners/Vendors/Service') !== false) $locClass = 'loc-external';
                                    elseif (stripos($loc, 'Physical Locations/Service') !== false) $locClass = 'loc-internal';
                                    elseif (stripos($loc, 'STOCK') !== false) $locClass = 'loc-stock';
                                    
                                    $locShort = $loc;
                                    if ($loc === 'Partners/Customers/Rental') $locShort = 'Customer';
                                    elseif (stripos($loc, 'Partners/Vendors/Service') === 0) $locShort = 'Ext. Service';
                                    elseif ($loc === 'Physical Locations/Service') $locShort = 'Int. Service';
                                    elseif (preg_match('/^SD([A-Z]{2,3})\\//', $loc, $m)) $locShort = $m[1] . ' Stock';
                                @endphp
                                <span class="location-badge {{ $locClass }}">{{ $locShort }}</span>
                            </div>
                            <div class="plate-number mb-1">{{ $replacement['lot_number'] }}</div>
                            <div class="vehicle-info text-truncate" title="{{ $replacement['product'] }}">{{ $replacement['product'] }}</div>
                        </div>
                        @endforeach
                        @if(count($replacements) == 0)
                        <div class="vehicle-card text-center text-muted py-4">
                            <i class="bi bi-question-circle fs-3"></i>
                            <div>Replacement not identified</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <h5 class="mt-3">No Rental Pairs Found</h5>
                <p class="text-muted">There are no main/replacement vehicle pairs in the current data.</p>
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Back to Dashboard</a>
            </div>
        </div>
        @endif
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Search functionality
            $('#searchPairs').on('keyup', function() {
                var searchText = $(this).val().toLowerCase();
                filterPairs();
            });
            
            $('#filterRole').on('change', function() {
                filterPairs();
            });
            
            function filterPairs() {
                var searchText = $('#searchPairs').val().toLowerCase();
                var roleFilter = $('#filterRole').val();
                var visibleCount = 0;
                
                $('#pairsList .pair-card').each(function() {
                    var searchData = $(this).data('search');
                    var matchesSearch = searchText === '' || searchData.indexOf(searchText) > -1;
                    var matchesRole = true;
                    
                    if (roleFilter === 'customer') {
                        // Replacement is with customer
                        matchesRole = $(this).find('.vehicle-replacement .loc-customer').length > 0;
                    } else if (roleFilter === 'service') {
                        // Main is in service
                        matchesRole = $(this).find('.vehicle-main .loc-external, .vehicle-main .loc-internal').length > 0;
                    }
                    
                    if (matchesSearch && matchesRole) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                    }
                });
                
                $('#pairCount').text(visibleCount + ' pairs');
            }
        });
    </script>
</body>
</html>
