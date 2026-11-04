@extends('layouts.app')

@section('content')
<div class="p-4 md:p-6 w-full space-y-4">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100">Report Cadangan Biaya</h1>
                <span class="px-3 py-1 text-xs font-bold bg-indigo-500/10 text-indigo-500 dark:text-indigo-400 border border-indigo-500/20 rounded-full">
                    LoR (SMD)
                </span>
                <span class="px-3 py-1 text-xs font-bold bg-emerald-500/10 text-emerald-500 dark:text-emerald-400 border border-emerald-500/20 rounded-full flex items-center gap-1">
                    📊 Cadangan Biaya Flattened
                </span>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Rental ID Subscriptions with CADANGAN BIAYA product mapped into a dedicated single-row column</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('lor.cadangan-biaya', array_merge(request()->except('page'), ['refresh' => 1])) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm transition-all" title="Refresh live cache from Odoo">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span>Refresh Odoo</span>
            </a>
            <a href="{{ route('lor.cadangan-biaya.export', request()->all()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Export Excel (.xlsx)</span>
            </a>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Units</div>
            <div class="text-xl font-extrabold text-slate-800 dark:text-slate-100 mt-1">
                {{ number_format($summary['total_units'] ?? 0) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">Matching Subscriptions</div>
        </div>
        <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-4 border border-indigo-500/20 bg-indigo-50/20 dark:bg-indigo-950/20 shadow-sm">
            <div class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Total Cadangan Biaya</div>
            <div class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400 mt-1">
                Rp {{ number_format($summary['total_cadangan_biaya'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-indigo-500/80 mt-0.5">Allocated Reserve Total</div>
        </div>
        <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Rental Value</div>
            <div class="text-xl font-extrabold text-slate-800 dark:text-slate-100 mt-1">
                Rp {{ number_format($summary['total_unit_price'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">Untaxed Contract Value</div>
        </div>
        <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Customers</div>
            <div class="text-xl font-extrabold text-slate-800 dark:text-slate-100 mt-1">
                {{ number_format($summary['total_customers'] ?? 0) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">Distinct Corporate Clients</div>
        </div>
    </div>

    <!-- Filters Bar (Identical to Picture 1) -->
    <div class="bg-white dark:bg-slate-800/90 rounded-2xl p-4 shadow-sm border border-slate-200 dark:border-slate-700/80">
        <form method="GET" action="{{ route('lor.cadangan-biaya') }}" class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3 flex-1">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[240px]">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search Rental ID, Contract, Customer, Unit, Salesperson..." 
                            class="w-full pl-10 pr-4 py-2.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <!-- Salesperson Filter -->
                @if(count($filterSalespersons) > 1)
                <select name="salesperson" onchange="this.form.submit()" class="px-3.5 py-2.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-medium">
                    <option value="">👤 All Salespersons</option>
                    @foreach($filterSalespersons as $sp)
                        <option value="{{ $sp }}" {{ $salespersonFilter === $sp ? 'selected' : '' }}>👤 {{ $sp }}</option>
                    @endforeach
                </select>
                @endif

                <!-- Sales Team Filter -->
                @if(count($filterSalesTeams) > 1)
                <select name="sales_team" onchange="this.form.submit()" class="px-3.5 py-2.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-medium">
                    <option value="">🚩 All Sales Teams</option>
                    @foreach($filterSalesTeams as $st)
                        <option value="{{ $st }}" {{ $salesTeamFilter === $st ? 'selected' : '' }}>🚩 {{ $st }}</option>
                    @endforeach
                </select>
                @endif

                <!-- Customer Filter -->
                @if(count($filterCustomers) > 1)
                <select name="customer" onchange="this.form.submit()" class="px-3.5 py-2.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-medium max-w-[220px] truncate">
                    <option value="">🏢 All Customers</option>
                    @foreach($filterCustomers as $cust)
                        <option value="{{ $cust }}" {{ $customerFilter === $cust ? 'selected' : '' }}>🏢 {{ $cust }}</option>
                    @endforeach
                </select>
                @endif

                <!-- Status Filter -->
                <select name="status" onchange="this.form.submit()" class="px-3.5 py-2.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-medium">
                    <option value="">All Statuses</option>
                    @foreach($filterStatuses as $st)
                        <option value="{{ $st }}" {{ $statusFilter === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>

                @if($sortBy)
                    <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                    <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                @endif

                <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl transition-colors">
                    Filter
                </button>
                @if($search || $salespersonFilter || $salesTeamFilter || $customerFilter || $statusFilter || $sortBy)
                    <a href="{{ route('lor.cadangan-biaya') }}" class="px-3 py-2.5 text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium">Clear</a>
                @endif
            </div>
        </form>
    </div>

    @php
        $getSortUrl = function($col) use ($sortBy, $sortDir) {
            $nextDir = ($sortBy === $col && $sortDir === 'asc') ? 'desc' : 'asc';
            return route('lor.cadangan-biaya', array_merge(request()->all(), ['sort_by' => $col, 'sort_dir' => $nextDir]));
        };
    @endphp

    <!-- Cadangan Biaya Table with Sticky Header -->
    <div class="bg-white dark:bg-slate-900/90 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="max-h-[calc(100vh-320px)] overflow-y-auto overflow-x-auto relative">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="sticky top-0 z-20 bg-slate-100/95 dark:bg-slate-950/95 text-slate-600 dark:text-slate-400 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 backdrop-blur-md shadow-sm">
                    <tr>
                        <th class="py-3.5 px-4 whitespace-nowrap min-w-[220px]">
                            <a href="{{ $getSortUrl('customer') }}" class="flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group">
                                <span>CUSTOMER</span>
                                @if($sortBy === 'customer')
                                    <span class="text-indigo-600 dark:text-indigo-400">{{ $sortDir === 'desc' ? '▼' : '▲' }}</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600 group-hover:text-slate-400 text-[10px]">⇅</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3.5 px-4 whitespace-nowrap">
                            <a href="{{ $getSortUrl('order_reference') }}" class="flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group">
                                <span>ORDER REFERENCE</span>
                                @if($sortBy === 'order_reference')
                                    <span class="text-indigo-600 dark:text-indigo-400">{{ $sortDir === 'desc' ? '▼' : '▲' }}</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600 group-hover:text-slate-400 text-[10px]">⇅</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3.5 px-4 whitespace-nowrap">
                            <a href="{{ $getSortUrl('creation_date') }}" class="flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group">
                                <span>CREATION DATE</span>
                                @if($sortBy === 'creation_date')
                                    <span class="text-indigo-600 dark:text-indigo-400">{{ $sortDir === 'desc' ? '▼' : '▲' }}</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600 group-hover:text-slate-400 text-[10px]">⇅</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3.5 px-4 whitespace-nowrap">
                            <a href="{{ $getSortUrl('actual_start_rental') }}" class="flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group">
                                <span>START SEWA</span>
                                @if($sortBy === 'actual_start_rental')
                                    <span class="text-indigo-600 dark:text-indigo-400">{{ $sortDir === 'desc' ? '▼' : '▲' }}</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600 group-hover:text-slate-400 text-[10px]">⇅</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3.5 px-4 whitespace-nowrap">
                            <a href="{{ $getSortUrl('actual_end_rental') }}" class="flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group">
                                <span>END SEWA</span>
                                @if($sortBy === 'actual_end_rental')
                                    <span class="text-indigo-600 dark:text-indigo-400">{{ $sortDir === 'desc' ? '▼' : '▲' }}</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600 group-hover:text-slate-400 text-[10px]">⇅</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">
                            <a href="{{ $getSortUrl('duration_months') }}" class="flex items-center justify-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group">
                                <span>DURATION (BLN)</span>
                                @if($sortBy === 'duration_months')
                                    <span class="text-indigo-600 dark:text-indigo-400">{{ $sortDir === 'desc' ? '▼' : '▲' }}</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600 group-hover:text-slate-400 text-[10px]">⇅</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3.5 px-4 whitespace-nowrap">
                            <a href="{{ $getSortUrl('lot_number') }}" class="flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group">
                                <span>UNIT / LOT</span>
                                @if($sortBy === 'lot_number')
                                    <span class="text-indigo-600 dark:text-indigo-400">{{ $sortDir === 'desc' ? '▼' : '▲' }}</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600 group-hover:text-slate-400 text-[10px]">⇅</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3.5 px-4 text-right whitespace-nowrap">
                            <a href="{{ $getSortUrl('duration_price') }}" class="flex items-center justify-end gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group">
                                <span>HARGA SEWA / BLN</span>
                                @if($sortBy === 'duration_price')
                                    <span class="text-indigo-600 dark:text-indigo-400">{{ $sortDir === 'desc' ? '▼' : '▲' }}</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600 group-hover:text-slate-400 text-[10px]">⇅</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3.5 px-4 text-right whitespace-nowrap">
                            <a href="{{ $getSortUrl('unit_price') }}" class="flex items-center justify-end gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group">
                                <span>ORDER UNIT PRICE</span>
                                @if($sortBy === 'unit_price')
                                    <span class="text-indigo-600 dark:text-indigo-400">{{ $sortDir === 'desc' ? '▼' : '▲' }}</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600 group-hover:text-slate-400 text-[10px]">⇅</span>
                                @endif
                            </a>
                        </th>
                        <!-- Dedicated CADANGAN BIAYA Column (Highlighted) -->
                        <th class="py-3.5 px-4 text-right whitespace-nowrap bg-indigo-50/80 dark:bg-indigo-950/70 border-x border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 font-extrabold">
                            <a href="{{ $getSortUrl('cadangan_biaya') }}" class="flex items-center justify-end gap-1.5 hover:text-indigo-900 dark:hover:text-indigo-200 transition-colors group">
                                <span>CADANGAN BIAYA</span>
                                @if($sortBy === 'cadangan_biaya')
                                    <span class="text-indigo-600 dark:text-indigo-400">{{ $sortDir === 'desc' ? '▼' : '▲' }}</span>
                                @else
                                    <span class="text-indigo-300 dark:text-indigo-600 group-hover:text-indigo-400 text-[10px]">⇅</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">TENDER</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">
                            <a href="{{ $getSortUrl('rental_status') }}" class="flex items-center justify-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group">
                                <span>STATUS</span>
                                @if($sortBy === 'rental_status')
                                    <span class="text-indigo-600 dark:text-indigo-400">{{ $sortDir === 'desc' ? '▼' : '▲' }}</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600 group-hover:text-slate-400 text-[10px]">⇅</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">COP</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">DRIVER</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">
                            <a href="{{ $getSortUrl('salesperson') }}" class="flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group">
                                <span>SALESPERSON</span>
                                @if($sortBy === 'salesperson')
                                    <span class="text-indigo-600 dark:text-indigo-400">{{ $sortDir === 'desc' ? '▼' : '▲' }}</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600 group-hover:text-slate-400 text-[10px]">⇅</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3.5 px-4 whitespace-nowrap">
                            <a href="{{ $getSortUrl('sales_team') }}" class="flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group">
                                <span>SALES TEAM</span>
                                @if($sortBy === 'sales_team')
                                    <span class="text-indigo-600 dark:text-indigo-400">{{ $sortDir === 'desc' ? '▼' : '▲' }}</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600 group-hover:text-slate-400 text-[10px]">⇅</span>
                                @endif
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-[11px] font-medium text-slate-700 dark:text-slate-300">
                    @forelse($records as $r)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="py-3 px-4 font-semibold text-slate-800 dark:text-slate-200">
                                {{ $r['customer'] }}
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                                {{ $r['order_reference'] }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-slate-500 dark:text-slate-400">
                                {{ $r['creation_date'] ? \Carbon\Carbon::parse($r['creation_date'])->format('d M Y H:i') : '-' }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                {{ $r['actual_start_rental'] ? \Carbon\Carbon::parse($r['actual_start_rental'])->format('d M Y') : '-' }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                {{ $r['actual_end_rental'] ? \Carbon\Carbon::parse($r['actual_end_rental'])->format('d M Y') : '-' }}
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-slate-800 dark:text-slate-200">
                                {{ $r['duration_months'] }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap font-bold text-slate-900 dark:text-white">
                                {{ $r['lot_number'] }}
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono text-slate-700 dark:text-slate-300">
                                Rp {{ number_format($r['duration_price'], 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono text-slate-900 dark:text-slate-100 font-semibold">
                                Rp {{ number_format($r['unit_price'], 0, ',', '.') }}
                            </td>
                            <!-- Highlighted CADANGAN BIAYA Data Cell (Same Row) -->
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-extrabold text-indigo-700 dark:text-indigo-300 bg-indigo-50/60 dark:bg-indigo-950/40 border-x border-indigo-200 dark:border-indigo-800">
                                <span class="px-2 py-0.5 rounded-lg bg-indigo-100 dark:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-700">
                                    Rp {{ number_format((float)($r['cadangan_biaya'] ?? 0), 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($r['is_tender'])
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">Yes</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @php
                                    $badgeClass = match(strtolower($r['rental_status'])) {
                                        'pickedup' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-200 dark:border-emerald-700/80',
                                        'reserved' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300 border-amber-200 dark:border-amber-700/80',
                                        'returned' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                                        'quotation', 'quotation sent' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/80 dark:text-blue-300 border-blue-200 dark:border-blue-700/80',
                                        default => 'bg-slate-50 text-slate-600 dark:bg-slate-900 dark:text-slate-400 border-slate-200 dark:border-slate-800'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                    {{ $r['rental_status'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($r['is_cop'])
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">COP</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($r['driver'])
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-700 border border-cyan-200">Yes</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                {{ $r['salesperson'] }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-slate-500 dark:text-slate-400">
                                {{ $r['sales_team'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="16" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span class="text-sm font-bold">No records found matching the selected filters.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

