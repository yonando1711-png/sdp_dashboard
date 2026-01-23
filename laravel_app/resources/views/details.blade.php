<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Details - {{ ucfirst(str_replace('_', ' ', $category ?? 'All')) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReorder.bootstrap5.min.css">
    <style>
        .dt-buttons .btn { margin-right: 5px; }
        .filter-row select { 
            font-size: 0.7rem; 
            padding: 0.2rem 1.5rem 0.2rem 0.4rem; /* more padding on right for arrow */
            min-width: 95px;
            height: auto;
            line-height: 1.2;
            background-position: right 0.3rem center;
            background-size: 10px;
        }
        .filter-row th { padding: 0.4rem 0.3rem !important; background: #f8f9fa; }
        
        /* Location badge colors */
        .loc-operation { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .loc-jakarta { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
        .loc-surabaya { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: white; }
        .loc-semarang { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; }
        .loc-bandung { background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); color: white; }
        .loc-yogya { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: white; }
        .loc-transit { background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%); color: #333; }
        .loc-customer { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
        .loc-external { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
        .loc-internal { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white; }
        .loc-insurance { background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%); color: white; }
        .loc-other { background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: white; }
        .loc-sold { background: linear-gradient(135deg, #1f2937 0%, #111827 100%); color: white; }
        
        .location-badge { font-size: 0.75rem; padding: 0.35em 0.65em; border-radius: 0.375rem; font-weight: 500; }
        
        /* Role badges */
        .role-badge-main { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; font-size: 0.7rem; padding: 0.25em 0.5em; border-radius: 4px; }
        .role-badge-replacement { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: white; font-size: 0.7rem; padding: 0.25em 0.5em; border-radius: 4px; }
        .linked-vehicle-link { color: #3b82f6; text-decoration: none; font-weight: 500; }
        .linked-vehicle-link:hover { text-decoration: underline; }
        
        /* Table container with visible scrollbar */
        .table-scroll-container {
            overflow-x: auto;
            overflow-y: visible;
            max-width: 100%;
            padding-bottom: 15px;
            margin-bottom: -15px;
        }
        .table-scroll-container::-webkit-scrollbar {
            height: 10px;
        }
        .table-scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 5px;
        }
        .table-scroll-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 5px;
        }
        .table-scroll-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46a1 100%);
        }
        
        /* Compact table styling */
        #detailsTable { width: 100% !important; min-width: 100%; font-size: 0.8rem; border-collapse: collapse; }
        #detailsTable th { padding: 0.5rem 0.75rem !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #detailsTable td { padding: 0.4rem 0.75rem !important; white-space: nowrap; vertical-align: middle; }
        #detailsTable td:first-child { white-space: normal; min-width: 150px; }
        
        /* Draggable and Resizable styles */
        table.dataTable thead th { position: relative; }
        .dt-colresizable-handle { position: absolute; right: 0; top: 0; bottom: 0; width: 5px; cursor: col-resize; z-index: 10; }
        .dt-colresizable-handle:hover { background-color: #0d6efd; opacity: 0.5; }

        /* Highlight important columns */
        #detailsTable th:nth-child(2), #detailsTable td:nth-child(2) { font-weight: 600; color: #1e40af; } /* Lot Number */
        
        /* DataTables wrapper */
        .dataTables_wrapper { width: 100%; }
        .dataTables_scrollHead { overflow: visible !important; }
        
        /* Scroll indicator */
        .scroll-hint {
            text-align: center;
            padding: 5px;
            background: linear-gradient(90deg, transparent 0%, #e2e8f0 50%, transparent 100%);
            color: #64748b;
            font-size: 0.75rem;
            margin-bottom: 5px;
        }
        
        /* Column Visibility Dropdown Styling */
        .dt-button-collection { width: auto !important; }
        .dt-button-collection .dropdown-menu { display: block; position: static; }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar omitted for brevity, same as before -->
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>SDP DASHBOARD</a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0">{{ ucfirst(str_replace('_', ' ', $category ?? 'All Items')) }}</h3>
                @if($sub)<p class="text-muted mb-0">Filter: {{ $sub }}</p>@endif
            </div>
            <div>
                <span class="badge bg-dark">{{ count($items) }} items</span>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="scroll-hint"><i class="bi bi-arrows-expand"></i> Drag column headers to reorder • Use 'Columns' button to show/hide</div>
                <div class="table-scroll-container">
                <table id="detailsTable" class="table table-striped table-hover" style="width:100%">
                    <thead class="table-dark">
                        <tr>
                            <th>Product</th>
                            <th>Lot Number</th>
                            <th>Internal Ref</th>
                            <th>Location</th>
                            <th>Role</th>
                            <th>Linked Vehicle</th>
                            <th>On Hand Qty</th>
                            <th>Rental ID</th>
                            <th>Rental Type</th>
                            <th>Actual Start</th>
                            <th>Actual End</th>
                            <th>Vendor Rent</th>
                            <th>In Stock</th>
                        </tr>
                        <tr class="filter-row">
                            <th></th>
                            <th></th>
                            <th></th>
                            <th><select id="filterLocation" class="form-select form-select-sm"><option value="">All Locations</option></select></th>
                            <th><select id="filterRole" class="form-select form-select-sm"><option value="">All Roles</option><option value="Main">Main</option><option value="Replacement">Replacement</option></select></th>
                            <th><select id="filterLinked" class="form-select form-select-sm"><option value="">All</option><option value="Linked">Linked</option><option value="Not Linked">Not Linked</option></select></th>
                            <th></th>
                            <th></th>
                            <th><select id="filterRentalType" class="form-select form-select-sm"><option value="">All Types</option><option value="Subscription">Subscription</option><option value="Regular">Regular</option></select></th>
                            <th></th>
                            <th></th>
                            <th><select id="filterVendorRent" class="form-select form-select-sm"><option value="">All</option><option value="Yes">Yes</option><option value="No">No</option></select></th>
                            <th><select id="filterInStock" class="form-select form-select-sm"><option value="">All</option><option value="Yes">Yes</option><option value="No">No</option></select></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        @php
                            $loc = $item['location'] ?? '';
                            $locClass = 'loc-other';
                            if (stripos($loc, 'Operation') !== false || stripos($loc, 'SDP/OP') !== false) $locClass = 'loc-operation';
                            elseif (stripos($loc, 'JKT') !== false || stripos($loc, 'Jakarta') !== false) $locClass = 'loc-jakarta';
                            elseif (stripos($loc, 'SUB') !== false || stripos($loc, 'SBY') !== false || stripos($loc, 'Surabaya') !== false) $locClass = 'loc-surabaya';
                            elseif (stripos($loc, 'SMG') !== false || stripos($loc, 'Semarang') !== false) $locClass = 'loc-semarang';
                            elseif (stripos($loc, 'BDG') !== false || stripos($loc, 'Bandung') !== false) $locClass = 'loc-bandung';
                            elseif (stripos($loc, 'YOG') !== false || stripos($loc, 'Yogya') !== false) $locClass = 'loc-yogya';
                            elseif (stripos($loc, 'Transit') !== false) $locClass = 'loc-transit';
                            elseif (stripos($loc, 'Partners/Customers') !== false) $locClass = 'loc-customer';
                            elseif (stripos($loc, 'Partners/Vendors/Service') !== false) $locClass = 'loc-external';
                            elseif (stripos($loc, 'Partners/Vendors/Insurance') !== false) $locClass = 'loc-insurance';
                            elseif (stripos($loc, 'Physical Locations/Service') !== false) $locClass = 'loc-internal';
                            elseif (stripos($loc, 'SOLD') !== false) $locClass = 'loc-sold';
                            
                            // Short display name for locations
                            $locShort = $loc;
                            if ($loc === 'Partners/Customers/Rental') $locShort = 'Customer Rental';
                            elseif (stripos($loc, 'Partners/Vendors/Service') === 0) $locShort = 'Ext Service';
                            elseif (stripos($loc, 'Partners/Vendors/Insurance') === 0) $locShort = 'Insurance';
                            elseif ($loc === 'Physical Locations/Service') $locShort = 'Int Service';
                            elseif (stripos($loc, 'SDP/OPERATION') === 0) $locShort = 'Operation';
                            elseif (preg_match('/^SD([A-Z]{2,3})\//', $loc, $m)) $locShort = $m[1]; // Extract city code
                            elseif (stripos($loc, '/') !== false) $locShort = basename(str_replace('/', DIRECTORY_SEPARATOR, $loc));
                        @endphp
                        <tr>
                            <td>{{ $item['product'] }}</td>
                            <td>{{ $item['lot_number'] }}</td>
                            <td>{{ $item['internal_reference'] ?? '' }}</td>
                            <td><span class="location-badge {{ $locClass }}" title="{{ $item['location'] }}">{{ $locShort }}</span></td>
                            <td>
                                @if(!empty($item['vehicle_role']))
                                    <span class="{{ $item['vehicle_role'] == 'Main' ? 'role-badge-main' : 'role-badge-replacement' }}">{{ $item['vehicle_role'] }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($item['linked_vehicle']))
                                    <a href="{{ route('details', ['category' => 'search', 'q' => $item['linked_vehicle']]) }}" class="linked-vehicle-link" title="View linked vehicle">{{ $item['linked_vehicle'] }}</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $item['on_hand_quantity'] }}</td>
                            <td>{{ $item['rental_id'] }}</td>
                            <td>
                                @if(!empty($item['rental_type']))
                                    <span class="badge {{ $item['rental_type'] == 'Subscription' ? 'bg-primary' : 'bg-info' }}">{{ $item['rental_type'] }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($item['rental_id']) && !empty($item['actual_start_rental']))
                                    {{ is_numeric($item['actual_start_rental']) ? \Carbon\Carbon::createFromTimestamp(($item['actual_start_rental'] - 25569) * 86400)->format('Y-m-d') : $item['actual_start_rental'] }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($item['rental_id']) && !empty($item['actual_end_rental']))
                                    {{ is_numeric($item['actual_end_rental']) ? \Carbon\Carbon::createFromTimestamp(($item['actual_end_rental'] - 25569) * 86400)->format('Y-m-d') : $item['actual_end_rental'] }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $item['is_vendor_rent'] ? 'bg-info' : 'bg-secondary' }}">{{ $item['is_vendor_rent'] ? 'Yes' : 'No' }}</span></td>
                            <td><span class="badge {{ $item['in_stock'] ? 'bg-success' : 'bg-warning text-dark' }}">{{ $item['in_stock'] ? 'Yes' : 'No' }}</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="13" class="text-center">No items found for this category.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                </div><!-- end table-scroll-container -->
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <!-- ColReorder -->
    <script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>
    <!-- ColResizable -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/colresizable/1.6.0/colResizable-1.6.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#detailsTable').DataTable({
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"l><"col-md-6"p>>i',
                stateSave: true, // Enable state saving (cookies/localStorage)
                colReorder: true, // Enable Drag & Drop Reordering
                buttons: [
                    { 
                        extend: 'colvis', 
                        className: 'btn btn-info btn-sm text-white', 
                        text: '<i class="bi bi-layout-three-columns me-1"></i> Columns',
                        columns: ':not(.noVis)' // Exclude columns with class noVis from toggle
                    },
                    { extend: 'excel', className: 'btn btn-success btn-sm', text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel' },
                    { extend: 'csv', className: 'btn btn-primary btn-sm', text: '<i class="bi bi-filetype-csv me-1"></i> CSV' },
                    { extend: 'print', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-printer me-1"></i> Print' }
                ],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[0, 'asc']],
                orderCellsTop: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ items"
                },
                initComplete: function() {
                    // Enable colResizable after Table init
                    // Note: colResizable and DataTables scrolling/auto-width can conflict.
                    // We disable DataTables autoWidth (it is false by default in BS5 config usually)
                    // and use colResizable on the table.
                    $('#detailsTable').colResizable({
                        liveDrag: true,
                        gripInnerHtml: "<div class='dt-colresizable-handle'></div>",
                        draggingClass: "dragging",
                        resizeMode: 'fit'
                    });
                }
            });

            // Populate location filter from unique values in column 3
            var locationColumn = table.column(3);
            var locations = [];
            
            // Note when using StateSave, column visibility might change indexes.
            // Using name or fixed content is better.
            
            locationColumn.nodes().each(function(node) {
                var text = $(node).text().trim();
                if (text && locations.indexOf(text) === -1) {
                    locations.push(text);
                }
            });
            locations.sort().forEach(function(loc) {
                $('#filterLocation').append('<option value="' + loc + '">' + loc + '</option>');
            });

            // Column filters - Need to be careful with indexing if columns are reordered.
            // DataTables handles this internally if using API.
            
            $('#filterLocation').on('change', function() {
                var val = $(this).val();
                if (val) {
                    table.column(3).search(val, false, false).draw();
                } else {
                    table.column(3).search('').draw();
                }
            });

            $('#filterRole').on('change', function() {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                table.column(4).search(val ? '^' + val + '$' : '', true, false).draw();
            });

            // Linked Vehicle Filter (Column 5)
            $('#filterLinked').on('change', function() {
                var val = $(this).val();
                if (val === 'Linked') {
                     // Regex: Match any character that isn't a dash or empty
                     // Actually, if we use text content, it's usually the vehicle name or "-"
                     // Let's assume if it contains "-" it is Not Linked, or if it has text it is Linked.
                     // A safer regex for "Not Linked" is "^-$|^\s*$"
                     // For "Linked", we want inverse of "Not Linked".
                     // DataTables regex search doesn't support "NOT" directly easily without lookahead?
                     // Easiest is to search for specific negation if possible.
                     // Or, since we only have two states, matching "Linked" means "Not -"
                     table.column(5).search('^[^\\-]+.*$', true, false).draw(); 
                } else if (val === 'Not Linked') {
                     table.column(5).search('^-$|^\\s*$', true, false).draw();
                } else {
                     table.column(5).search('').draw();
                }
            });

            $('#filterRentalType').on('change', function() {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                table.column(8).search(val ? val : '', true, false).draw();
            });

            $('#filterVendorRent').on('change', function() {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                table.column(11).search(val ? '^' + val + '$' : '', true, false).draw();
            });

            $('#filterInStock').on('change', function() {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                table.column(12).search(val ? '^' + val + '$' : '', true, false).draw();
            });
        });
    </script>
</body>
</html>

