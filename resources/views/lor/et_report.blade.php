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
                    <div class="flex items-center gap-2 bg-slate-50 dark:bg-[#050913] px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700/80 cursor-pointer hover:border-indigo-500/50 transition-colors group"
                         onclick="if (!window.fpFrom) document.getElementById('date_from').showPicker();">
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">From</span>
                        <input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}" 
                               onclick="this.showPicker()"
                               class="bg-transparent text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none cursor-pointer [color-scheme:light] dark:[color-scheme:dark] w-28">
                        <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400 flex-shrink-0 cursor-pointer group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="flex items-center gap-2 bg-slate-50 dark:bg-[#050913] px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700/80 cursor-pointer hover:border-indigo-500/50 transition-colors group"
                         onclick="if (!window.fpTo) document.getElementById('date_to').showPicker();">
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">To</span>
                        <input type="date" name="date_to" id="date_to" value="{{ $dateTo }}" 
                               onclick="this.showPicker()"
                               class="bg-transparent text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none cursor-pointer [color-scheme:light] dark:[color-scheme:dark] w-28">
                        <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400 flex-shrink-0 cursor-pointer group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>

                    <!-- Quick Preset Buttons -->
                    <div class="hidden sm:flex items-center gap-1.5 pl-1">
                        <button type="button" 
                                onclick="setDatePreset('{{ now()->startOfMonth()->format('Y-m-d') }}', '{{ now()->endOfMonth()->format('Y-m-d') }}')"
                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                            This Month
                        </button>
                        <button type="button" 
                                onclick="setDatePreset('{{ now()->subMonth()->startOfMonth()->format('Y-m-d') }}', '{{ now()->subMonth()->endOfMonth()->format('Y-m-d') }}')"
                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                            Last Month
                        </button>
                        <button type="button" 
                                onclick="setDatePreset('{{ now()->startOfYear()->format('Y-m-d') }}', '{{ now()->endOfYear()->format('Y-m-d') }}')"
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

    <!-- Salesperson Tables (Each Salesperson in a Dedicated Card) -->
    <div class="space-y-6">
        @forelse($grouped as $teamCode => $team)
            @foreach($team['salespersons'] as $spName => $sp)
                @php
                    $spSearchString = strtolower($teamCode . ' ' . $spName . ' ' . implode(' ', array_map(function($c) {
                        return $c['customer'] . ' ' . implode(' ', array_map(function($it) {
                            return $it['tipe_unit'] . ' ' . $it['tahun_kendaraan'] . ' ' . $it['nopol'] . ' ' . $it['order_name'];
                        }, $c['items']));
                    }, $sp['customers'])));
                @endphp

                <div class="bg-white dark:bg-[#0d1322] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden transition-all"
                     x-show="!searchQuery || '{{ $spSearchString }}'.includes(searchQuery.toLowerCase())">
                    
                    <!-- Salesperson Card Header -->
                    <div class="px-6 py-4 bg-slate-50/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800/80 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-indigo-500/10 dark:bg-indigo-500/20 border border-indigo-500/20 flex items-center justify-center text-indigo-500 font-black text-xl shadow-xs">
                                👤
                            </div>
                            <div>
                                <div class="flex items-center gap-2.5">
                                    <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight">{{ $spName }}</h3>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-extrabold text-xs border border-indigo-500/20">
                                        <span class="text-[10px] uppercase text-indigo-400 font-medium">Team</span>
                                        <span>{{ $teamCode }}</span>
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                                    {{ $team['team_full'] }} &bull; <span class="font-semibold text-slate-700 dark:text-slate-300">{{ count($sp['customers']) }} Corporate Customer{{ count($sp['customers']) > 1 ? 's' : '' }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Right Side Badge: Total ET Units -->
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Total ET</span>
                                <span class="text-lg font-black text-rose-600 dark:text-rose-400">{{ $sp['total_units'] }} Unit{{ $sp['total_units'] > 1 ? 's' : '' }}</span>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-rose-500/10 dark:bg-rose-500/20 border border-rose-500/20 flex items-center justify-center text-rose-500 font-bold">
                                🚗
                            </div>
                        </div>
                    </div>

                    <!-- Table for this Salesperson -->
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[980px] table-fixed text-left text-xs border-collapse">
                            <colgroup>
                                <col style="width: 25%;">
                                <col style="width: 7%;">
                                <col style="width: 26%;">
                                <col style="width: 6%;">
                                <col style="width: 9%;">
                                <col style="width: 9%;">
                                <col style="width: 9%;">
                                <col style="width: 9%;">
                            </colgroup>
                            <thead>
                                <tr class="bg-slate-900 text-white dark:bg-slate-950 font-bold uppercase tracking-wider text-[11px] border-b border-slate-800">
                                    <th class="py-3 px-4 border-r border-slate-800/80">Nama Customer</th>
                                    <th class="py-3 px-3 text-center border-r border-slate-800/80">ET / Cust</th>
                                    <th class="py-3 px-4 border-r border-slate-800/80">Tipe Unit Kendaraan</th>
                                    <th class="py-3 px-3 text-center border-r border-slate-800/80">Tahun</th>
                                    <th class="py-3 px-3 text-center border-r border-slate-800/80">Tgl ET</th>
                                    <th class="py-3 px-3 text-center border-r border-slate-800/80">Masa Sewa</th>
                                    <th class="py-3 px-3 text-center border-r border-slate-800/80">Sewa Sdh Berjalan</th>
                                    <th class="py-3 px-3 text-center">Sisa Masa Sewa</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                                @foreach($sp['customers'] as $custName => $cust)
                                    @php
                                        $custTotalUnits = $cust['total_units'];
                                        $custFirstRow = true;
                                    @endphp

                                    @foreach($cust['items'] as $item)
                                        <tr class="hover:bg-indigo-50/40 dark:hover:bg-slate-800/40 transition-colors"
                                            x-show="!searchQuery || '{{ strtolower($custName . ' ' . $item['tipe_unit'] . ' ' . $item['tahun_kendaraan'] . ' ' . $item['nopol'] . ' ' . $item['order_name']) }}'.includes(searchQuery.toLowerCase())">
                                            
                                            <!-- Customer Column (Rendered once per customer with rowspan) -->
                                            @if($custFirstRow)
                                                <td rowspan="{{ $custTotalUnits }}" class="py-3 px-4 font-bold text-slate-800 dark:text-slate-200 border-r border-slate-200 dark:border-slate-800 align-middle bg-white dark:bg-[#0d1322]">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-indigo-500 text-sm flex-shrink-0">🏢</span>
                                                        <span class="font-bold leading-snug break-words">{{ $custName }}</span>
                                                    </div>
                                                </td>
                                                <td rowspan="{{ $custTotalUnits }}" class="py-3 px-3 text-center font-bold text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 align-middle bg-white dark:bg-[#0d1322]">
                                                    <span class="px-2.5 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 font-extrabold text-xs">
                                                        {{ $custTotalUnits }}
                                                    </span>
                                                </td>
                                                @php $custFirstRow = false; @endphp
                                            @endif

                                            <!-- Vehicle Unit & Durations -->
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
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-50 dark:bg-slate-900/60 font-bold text-slate-800 dark:text-slate-200 text-xs border-t border-slate-200 dark:border-slate-800">
                                    <td class="py-2.5 px-4 font-black uppercase text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                        Subtotal {{ $spName }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-black text-rose-600 dark:text-rose-400">
                                        {{ $sp['total_units'] }}
                                    </td>
                                    <td colspan="6" class="py-2.5 px-4 text-right text-[11px] text-slate-400 dark:text-slate-500 font-medium truncate">
                                        {{ count($sp['customers']) }} customer{{ count($sp['customers']) > 1 ? 's' : '' }} &bull; {{ $sp['total_units'] }} unit{{ $sp['total_units'] > 1 ? 's' : '' }} handled by {{ $spName }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endforeach
        @empty
            <!-- Empty State Card -->
            <div class="bg-white dark:bg-[#0d1322] rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center text-slate-400 dark:text-slate-500 shadow-sm">
                <div class="flex flex-col items-center justify-center gap-3">
                    <div class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-slate-800/60 flex items-center justify-center text-3xl">
                        📋
                    </div>
                    <h4 class="font-bold text-slate-700 dark:text-slate-300 text-sm">No Early Termination records found</h4>
                    <p class="text-xs text-slate-400 dark:text-slate-500 max-w-sm">
                        There are no terminated rental contracts recorded in Odoo for the selected date range and filter criteria.
                    </p>
                </div>
            </div>
        @endforelse

        <!-- Grand Total Summary Banner (if items exist) -->
        @if(!empty($grouped))
            <div class="bg-white dark:bg-[#0d1322] rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-lg flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 dark:bg-indigo-500/20 border border-indigo-500/20 flex items-center justify-center text-indigo-500 font-black text-xl">
                        📊
                    </div>
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Grand Total Early Termination</h4>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-2xl font-black text-rose-600 dark:text-rose-400">{{ number_format($summary['total_units'] ?? 0) }} Units</span>
                            <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $summary['total_salespersons'] ?? 0 }} Salespersons</span>
                            <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $summary['total_customers'] ?? 0 }} Customers</span>
                        </div>
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 italic">
                    Generated from live Odoo rental contracts &bull; SDP Dashboard
                </p>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<!-- Flatpickr CSS & JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Custom Flatpickr styling tailored to Dashboard Dark/Light theme */
    .flatpickr-calendar {
        border-radius: 1rem !important;
        font-family: inherit !important;
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
    }
    .dark .flatpickr-calendar {
        background: #0d1322 !important;
        border: 1px solid #1e293b !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6) !important;
    }
    .dark .flatpickr-calendar .flatpickr-day {
        color: #cbd5e1 !important;
        border-radius: 0.5rem !important;
    }
    .dark .flatpickr-calendar .flatpickr-day:hover {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    .dark .flatpickr-calendar .flatpickr-day.selected {
        background: #4f46e5 !important;
        border-color: #4f46e5 !important;
        color: #ffffff !important;
        font-weight: 700 !important;
    }
    .dark .flatpickr-calendar .flatpickr-day.today {
        border-color: #6366f1 !important;
    }
    .dark .flatpickr-current-month span.cur-month, 
    .dark .flatpickr-current-month input.cur-year {
        color: #f8fafc !important;
        font-weight: 700 !important;
    }
    .dark .flatpickr-months .flatpickr-prev-month svg,
    .dark .flatpickr-months .flatpickr-next-month svg {
        fill: #94a3b8 !important;
    }
    .dark .flatpickr-weekdays span.flatpickr-weekday {
        color: #64748b !important;
        font-weight: 700 !important;
    }
    /* Fallback native date inputs */
    input[type="date"] {
        cursor: pointer;
    }
    .dark input[type="date"] {
        color-scheme: dark;
    }
    input[type="date"]::-webkit-calendar-picker-indicator {
        cursor: pointer;
        opacity: 0.8;
    }
    .dark input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(0.85);
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    function setDatePreset(from, to) {
        if (window.fpFrom && window.fpTo) {
            window.fpFrom.setDate(from);
            window.fpTo.setDate(to);
        } else {
            const elFrom = document.getElementById('date_from');
            const elTo = document.getElementById('date_to');
            if (elFrom) elFrom.value = from;
            if (elTo) elTo.value = to;
        }
        document.getElementById('etFilterForm').submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof flatpickr !== 'undefined') {
            const commonConfig = {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd M Y',
                altInputClass: 'bg-transparent text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none cursor-pointer w-24',
                disableMobile: true,
                allowInput: false
            };

            window.fpFrom = flatpickr("#date_from", commonConfig);
            window.fpTo = flatpickr("#date_to", commonConfig);
        }
    });
</script>
@endsection
