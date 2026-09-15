@extends('layouts.app')

@section('content')
<div class="p-4 md:p-6 w-full space-y-5" x-data="{ searchQuery: '' }">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100">ET Report</h1>
                <span class="px-3 py-1 text-xs font-bold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 rounded-full">
                    Early Termination
                </span>
                <span class="px-3 py-1 text-xs font-bold bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 rounded-full">
                    LoR (SMD)
                </span>
                <span class="px-2.5 py-0.5 text-[11px] font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-full flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live Odoo
                </span>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Overview and duration breakdown of early-terminated contracts grouped by Sales Team and Customer
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('lor.et-report.export', request()->all()) }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-600/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Export Excel</span>
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900/80 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Unit ET</p>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ number_format($summary['total_units'] ?? 0) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-500/10 dark:bg-rose-500/20 border border-rose-500/20 flex items-center justify-center text-rose-500 text-xl font-bold">
                    🚗
                </div>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">Terminated vehicle units in period</p>
        </div>

        <div class="bg-white dark:bg-slate-900/80 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Impacted Customers</p>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ number_format($summary['total_customers'] ?? 0) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 dark:bg-amber-500/20 border border-amber-500/20 flex items-center justify-center text-amber-500 text-xl font-bold">
                    🏢
                </div>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">Distinct corporate clients</p>
        </div>

        <div class="bg-white dark:bg-slate-900/80 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Teams</p>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ number_format($summary['total_teams'] ?? 0) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 dark:bg-indigo-500/20 border border-indigo-500/20 flex items-center justify-center text-indigo-500 text-xl font-bold">
                    🚩
                </div>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">Sales teams handling ET</p>
        </div>

        <div class="bg-white dark:bg-slate-900/80 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Period Range</p>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-1.5">
                        {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}
                    </h3>
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">
                        to {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 dark:bg-cyan-500/20 border border-cyan-500/20 flex items-center justify-center text-cyan-500 text-xl font-bold">
                    📅
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Control Bar -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl p-4.5 border border-slate-200 dark:border-slate-800 shadow-sm">
        <form method="GET" action="{{ route('lor.et-report') }}" id="etFilterForm" class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                
                <!-- Date Filters -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-2 bg-slate-50 dark:bg-[#050913] px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700/80">
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">From</span>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="bg-transparent text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none">
                    </div>
                    <div class="flex items-center gap-2 bg-slate-50 dark:bg-[#050913] px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700/80">
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">To</span>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="bg-transparent text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none">
                    </div>

                    <!-- Quick Preset Buttons -->
                    <div class="hidden sm:flex items-center gap-1.5 pl-1">
                        <button type="button" 
                                onclick="document.querySelector('[name=date_from]').value='{{ now()->startOfMonth()->format('Y-m-d') }}'; document.querySelector('[name=date_to]').value='{{ now()->endOfMonth()->format('Y-m-d') }}'; document.getElementById('etFilterForm').submit();"
                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                            This Month
                        </button>
                        <button type="button" 
                                onclick="document.querySelector('[name=date_from]').value='{{ now()->subMonth()->startOfMonth()->format('Y-m-d') }}'; document.querySelector('[name=date_to]').value='{{ now()->subMonth()->endOfMonth()->format('Y-m-d') }}'; document.getElementById('etFilterForm').submit();"
                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                            Last Month
                        </button>
                        <button type="button" 
                                onclick="document.querySelector('[name=date_from]').value='{{ now()->startOfYear()->format('Y-m-d') }}'; document.querySelector('[name=date_to]').value='{{ now()->endOfYear()->format('Y-m-d') }}'; document.getElementById('etFilterForm').submit();"
                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                            This Year
                        </button>
                    </div>
                </div>

                <!-- Salesperson & Team Scoped Dropdowns -->
                <div class="flex flex-wrap items-center gap-2 flex-1 justify-end">
                    @if(count($availableSalespersons) > 0)
                    <select name="salesperson" onchange="this.form.submit()" class="px-3 py-2 text-xs bg-slate-50 dark:bg-[#050913] border border-slate-200 dark:border-slate-700/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-medium">
                        <option value="">👤 All Salespersons</option>
                        @foreach($availableSalespersons as $sp)
                            <option value="{{ $sp }}" {{ $salespersonFilter === $sp ? 'selected' : '' }}>👤 {{ $sp }}</option>
                        @endforeach
                    </select>
                    @endif

                    @if(count($availableSalesTeams) > 0)
                    <select name="sales_team" onchange="this.form.submit()" class="px-3 py-2 text-xs bg-slate-50 dark:bg-[#050913] border border-slate-200 dark:border-slate-700/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-medium">
                        <option value="">🚩 All Sales Teams</option>
                        @foreach($availableSalesTeams as $st)
                            <option value="{{ $st }}" {{ $salesTeamFilter === $st ? 'selected' : '' }}>🚩 {{ $st }}</option>
                        @endforeach
                    </select>
                    @endif

                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all hover:scale-[1.02]">
                        Apply Filter
                    </button>

                    @if($salespersonFilter || $salesTeamFilter || request('date_from') || request('date_to'))
                    <a href="{{ route('lor.et-report') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-semibold text-xs rounded-xl transition-colors">
                        Reset
                    </a>
                    @endif
                </div>
            </div>

            <!-- Instant Search Input -->
            <div class="pt-2 border-t border-slate-100 dark:border-slate-800/80">
                <div class="relative w-full">
                    <input type="text" x-model="searchQuery" placeholder="Quick search customer, vehicle model, year, license plate, or rental ID..." 
                           class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 dark:bg-[#050913] border border-slate-200 dark:border-slate-700/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 placeholder:text-slate-400">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
        </form>
    </div>

    <!-- Grouped Table Card -->
    <div class="bg-white dark:bg-[#0d1322] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <!-- Table Head -->
                <thead>
                    <tr class="bg-slate-900 text-white dark:bg-slate-950 font-bold uppercase tracking-wider text-[11px] border-b border-slate-800">
                        <th class="py-3.5 px-4 text-center border-r border-slate-800/80 w-24">Team</th>
                        <th class="py-3.5 px-3 text-center border-r border-slate-800/80 w-28">Total Unit ET</th>
                        <th class="py-3.5 px-4 border-r border-slate-800/80 min-w-[220px]">Nama Customer</th>
                        <th class="py-3.5 px-3 text-center border-r border-slate-800/80 w-24">ET / Cust</th>
                        <th class="py-3.5 px-4 border-r border-slate-800/80 min-w-[240px]">Tipe Unit Kendaraan</th>
                        <th class="py-3.5 px-3 text-center border-r border-slate-800/80 w-24">Tahun</th>
                        <th class="py-3.5 px-3 text-center border-r border-slate-800/80 w-28">Tgl ET</th>
                        <th class="py-3.5 px-3 text-center border-r border-slate-800/80 w-28">Masa Sewa</th>
                        <th class="py-3.5 px-3 text-center border-r border-slate-800/80 w-36">Sewa Sdh Berjalan</th>
                        <th class="py-3.5 px-3 text-center w-32">Sisa Masa Sewa</th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    @forelse($grouped as $teamCode => $team)
                        @php
                            $teamTotalUnits = $team['total_units'];
                            $teamFirstRow = true;
                        @endphp

                        @foreach($team['customers'] as $custName => $cust)
                            @php
                                $custTotalUnits = $cust['total_units'];
                                $custFirstRow = true;
                            @endphp

                            @foreach($cust['items'] as $item)
                                <tr class="hover:bg-indigo-50/40 dark:hover:bg-slate-800/40 transition-colors"
                                    x-show="!searchQuery || '{{ strtolower($teamCode . ' ' . $custName . ' ' . $item['tipe_unit'] . ' ' . $item['tahun_kendaraan'] . ' ' . $item['nopol'] . ' ' . $item['order_name']) }}'.includes(searchQuery.toLowerCase())">
                                    
                                    <!-- Team Column (Rendered only on first row of team) -->
                                    @if($teamFirstRow)
                                        <td rowspan="{{ $teamTotalUnits }}" class="py-3 px-4 text-center font-extrabold text-slate-800 dark:text-slate-100 bg-slate-50/70 dark:bg-slate-900/50 border-r border-slate-200 dark:border-slate-800 align-middle">
                                            <span class="inline-block px-2.5 py-1 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-black text-sm shadow-xs border border-indigo-500/20">
                                                {{ $teamCode }}
                                            </span>
                                            <div class="text-[10px] text-slate-400 font-normal mt-1">{{ $team['team_full'] }}</div>
                                        </td>
                                        <td rowspan="{{ $teamTotalUnits }}" class="py-3 px-3 text-center font-black text-sm text-slate-900 dark:text-white bg-slate-50/70 dark:bg-slate-900/50 border-r border-slate-200 dark:border-slate-800 align-middle">
                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-100 shadow-inner">
                                                {{ $teamTotalUnits }}
                                            </span>
                                        </td>
                                        @php $teamFirstRow = false; @endphp
                                    @endif

                                    <!-- Customer Column (Rendered only on first row of customer) -->
                                    @if($custFirstRow)
                                        <td rowspan="{{ $custTotalUnits }}" class="py-3 px-4 font-bold text-slate-800 dark:text-slate-200 border-r border-slate-200 dark:border-slate-800 align-middle bg-white/50 dark:bg-slate-900/30">
                                            <div class="flex items-center gap-2">
                                                <span class="text-indigo-500">🏢</span>
                                                <span class="font-bold leading-snug">{{ $custName }}</span>
                                            </div>
                                        </td>
                                        <td rowspan="{{ $custTotalUnits }}" class="py-3 px-3 text-center font-bold text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 align-middle bg-white/50 dark:bg-slate-900/30">
                                            <span class="px-2.5 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 font-extrabold text-xs">
                                                {{ $custTotalUnits }}
                                            </span>
                                        </td>
                                        @php $custFirstRow = false; @endphp
                                    @endif

                                    <!-- Vehicle Unit & Durations (Row by Row) -->
                                    <td class="py-3 px-4 font-semibold text-slate-800 dark:text-slate-200 border-r border-slate-200 dark:border-slate-800">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $item['tipe_unit'] }}</div>
                                        <div class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                                            @if($item['nopol'] && $item['nopol'] !== '-')
                                                <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold border border-slate-200 dark:border-slate-700">
                                                    {{ $item['nopol'] }}
                                                </span>
                                            @endif
                                            <span>SO: {{ $item['order_name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-center font-medium text-slate-600 dark:text-slate-400 border-r border-slate-200 dark:border-slate-800 font-mono">
                                        {{ $item['tahun_kendaraan'] }}
                                    </td>
                                    <td class="py-3 px-3 text-center font-bold text-rose-600 dark:text-rose-400 border-r border-slate-200 dark:border-slate-800 font-mono">
                                        {{ $item['tgl_et'] }}
                                    </td>
                                    <td class="py-3 px-3 text-center font-bold text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800">
                                        {{ $item['masa_sewa'] }}
                                    </td>
                                    <td class="py-3 px-3 text-center font-semibold text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800">
                                        {{ $item['sewa_sdh_berjalan'] }}
                                    </td>
                                    <td class="py-3 px-3 text-center font-extrabold text-amber-600 dark:text-amber-400">
                                        {{ $item['sisa_masa_sewa'] }}
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <div class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-slate-800/60 flex items-center justify-center text-3xl">
                                        📋
                                    </div>
                                    <h4 class="font-bold text-slate-700 dark:text-slate-300 text-sm">No Early Termination records found</h4>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 max-w-sm">
                                        There are no terminated rental contracts recorded in Odoo for the selected date range and filter criteria.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <!-- Table Footer (Total Summary) -->
                @if(!empty($grouped))
                <tfoot>
                    <tr class="bg-slate-100 dark:bg-slate-900/90 font-black text-slate-900 dark:text-white border-t-2 border-slate-300 dark:border-slate-700 text-xs">
                        <td class="py-3.5 px-4 text-center border-r border-slate-300 dark:border-slate-700 uppercase tracking-wider font-extrabold">
                            TOTAL
                        </td>
                        <td class="py-3.5 px-3 text-center border-r border-slate-300 dark:border-slate-700 text-sm font-black text-rose-600 dark:text-rose-400">
                            {{ number_format($summary['total_units'] ?? 0) }}
                        </td>
                        <td class="py-3.5 px-4 border-r border-slate-300 dark:border-slate-700 font-bold text-slate-500 dark:text-slate-400">
                            {{ $summary['total_customers'] ?? 0 }} Customers Impacted
                        </td>
                        <td class="py-3.5 px-3 text-center border-r border-slate-300 dark:border-slate-700 text-sm font-black text-rose-600 dark:text-rose-400">
                            {{ number_format($summary['total_units'] ?? 0) }}
                        </td>
                        <td colspan="6" class="py-3.5 px-4 text-right text-[11px] text-slate-400 dark:text-slate-500 font-normal italic">
                            Generated from live Odoo rental contracts &bull; SDP Dashboard
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
