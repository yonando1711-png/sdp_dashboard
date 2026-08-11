@extends('layouts.app')

@section('content')
<div class="p-4 md:p-6 w-full space-y-4">
    <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100">List of Rented (SMD)</h1>
                    <span class="px-3 py-1 text-xs font-bold bg-indigo-500/10 text-indigo-500 dark:text-indigo-400 border border-indigo-500/20 rounded-full">
                        Sales Management Division
                    </span>
                    <span class="px-3 py-1 text-xs font-bold bg-rose-500/10 text-rose-500 dark:text-rose-400 border border-rose-500/20 rounded-full flex items-center gap-1">
                        🔒 Read-Only (No Export)
                    </span>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Scoped view grouped by assigned Salespersons and Sales Teams</p>
            </div>
        </div>

        <!-- Filters Bar (No Export Buttons) -->
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl p-4 shadow-sm border border-slate-200 dark:border-slate-700/80">
            <form method="GET" action="{{ route('lor.smd') }}" class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3 flex-1">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[240px]">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search Rental ID, Contract, Customer, Unit, Salesperson..." 
                               class="w-full pl-10 pr-4 py-2.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>

                    <!-- Salesperson Filter (Only shown if user has access to > 1 salesperson) -->
                    @if(count($filterSalespersons) > 1)
                    <select name="salesperson" onchange="this.form.submit()" class="px-3.5 py-2.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-medium">
                        <option value="">👤 All Salespersons</option>
                        @foreach($filterSalespersons as $sp)
                            <option value="{{ $sp }}" {{ $salespersonFilter === $sp ? 'selected' : '' }}>👤 {{ $sp }}</option>
                        @endforeach
                    </select>
                    @endif

                    <!-- Sales Team Filter (Only shown if user has access to > 1 sales team) -->
                    @if(count($filterSalesTeams) > 1)
                    <select name="sales_team" onchange="this.form.submit()" class="px-3.5 py-2.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-medium">
                        <option value="">🚩 All Sales Teams</option>
                        @foreach($filterSalesTeams as $st)
                            <option value="{{ $st }}" {{ $salesTeamFilter === $st ? 'selected' : '' }}>🚩 {{ $st }}</option>
                        @endforeach
                    </select>
                    @endif

                    <!-- Status Filter -->
                    <select name="status" onchange="this.form.submit()" class="px-3.5 py-2.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-medium">
                        <option value="">All Statuses</option>
                        <option value="Pickedup" {{ $statusFilter === 'Pickedup' ? 'selected' : '' }}>Pickedup</option>
                        <option value="Reserved" {{ $statusFilter === 'Reserved' ? 'selected' : '' }}>Reserved</option>
                        <option value="Returned" {{ $statusFilter === 'Returned' ? 'selected' : '' }}>Returned</option>
                        <option value="Quotation" {{ $statusFilter === 'Quotation' ? 'selected' : '' }}>Quotation</option>
                        <option value="Cancelled" {{ $statusFilter === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>

                    <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl transition-colors">
                        Filter
                    </button>
                    @if($search || $salespersonFilter || $salesTeamFilter || $statusFilter)
                        <a href="{{ route('lor.smd') }}" class="px-3 py-2.5 text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        <!-- LoR SMD Table Container with Frozen Sticky Header -->
        <div class="bg-white dark:bg-slate-900/90 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-800 overflow-hidden">
            <div class="max-h-[calc(100vh-270px)] overflow-y-auto overflow-x-auto relative">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="sticky top-0 z-20 bg-slate-100/95 dark:bg-slate-950/95 text-slate-600 dark:text-slate-400 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 backdrop-blur-md shadow-sm">
                        <tr>
                            <th class="py-3.5 px-4 whitespace-nowrap">RENTAL ID</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">SALESPERSON</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">SALES TEAM</th>
                            <th class="py-3.5 px-4 whitespace-nowrap min-w-[200px]">CUSTOMER</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">UNIT / LOT</th>
                            <th class="py-3.5 px-4 whitespace-nowrap min-w-[200px]">PRODUCT</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">START SEWA</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">END SEWA</th>
                            <th class="py-3.5 px-4 text-right whitespace-nowrap">TOTAL HARGA</th>
                            <th class="py-3.5 px-4 text-center whitespace-nowrap">STATUS</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">NOMOR KONTRAK</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">TYPE</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300 font-medium">
                        @forelse($currentRentals as $rental)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                                    {{ $rental->rental_id }}
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ $rental->salesperson ?: '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ $rental->sales_team ?: '-' }}
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-slate-100" title="{{ $rental->current_customer }}">
                                    {{ $rental->current_customer ?: '-' }}
                                </td>
                                <td class="py-3.5 px-4 font-mono whitespace-nowrap">
                                    {{ $rental->lot_number ?: '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-800 dark:text-slate-200" title="{{ $rental->product }}">
                                    {{ $rental->product ?: '-' }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    {{ $rental->actual_start_rental ? \Carbon\Carbon::parse($rental->actual_start_rental)->format('d M Y') : '-' }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    {{ $rental->actual_end_rental ? \Carbon\Carbon::parse($rental->actual_end_rental)->format('d M Y') : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    {{ $rental->amount_total ? 'Rp ' . number_format($rental->amount_total, 0, ',', '.') : ($rental->total_price ? 'Rp ' . number_format($rental->total_price, 0, ',', '.') : '-') }}
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    @php
                                        $statusClasses = [
                                            'Pickedup' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                                            'Reserved' => 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-500/20',
                                            'Returned' => 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20',
                                            'Quotation' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                                            'Cancelled' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
                                        ];
                                        $cls = $statusClasses[$rental->status] ?? 'bg-slate-500/10 text-slate-600 border-slate-500/20';
                                    @endphp
                                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-full border {{ $cls }}">
                                        {{ $rental->status ?: 'Unknown' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    {{ $rental->contract_ref ?: '-' }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ $rental->rental_type ?: 'Regular' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="p-8 text-center text-slate-500 dark:text-slate-400">
                                    No rental contracts found matching your assigned Salesperson / Sales Team scope.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/80">
                {{ $currentRentals->links() }}
            </div>
        </div>
</div>
@endsection
