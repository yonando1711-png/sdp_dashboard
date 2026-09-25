@extends('layouts.app')

@section('title', 'Uninvoiced Accounting - Accounting Report')

@section('content')
<div x-data="uninvoicedPage()" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 font-bold text-[11px] border border-emerald-200 dark:border-emerald-700/80">
                    Accounting Report
                </span>
                <span class="text-xs text-slate-400">/</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Uninvoiced Accounting</span>
            </div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-slate-100 tracking-tight mt-1">
                Uninvoiced Accounting
            </h1>
            <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Point-in-Time Cutoff Audit & Accrued Unbilled Revenue Reconciliation across fiscal periods.
            </p>
        </div>

        <!-- Header Controls & Year Switcher -->
        <div class="flex items-center gap-2 shrink-0 flex-wrap sm:flex-nowrap">
            <!-- Year Selector Dropdown -->
            <div class="relative" x-data="{ openYear: false }" @click.outside="openYear = false">
                <button type="button" @click="openYear = !openYear"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 transition-all whitespace-nowrap">
                    <span>📅 Year: {{ $year }}</span>
                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openYear" x-cloak style="display: none;"
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-2 z-50 text-xs divide-y divide-slate-100 dark:divide-slate-700/60">
                    <div class="px-2 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Switch Fiscal Year</div>
                    <div class="py-1 space-y-1">
                        @foreach(range(max((int)$year, now()->year), 2024) as $yOpt)
                            <a href="{{ route('accounting.uninvoiced', array_merge(request()->except(['year']), ['year' => $yOpt])) }}"
                               class="flex items-center justify-between px-2.5 py-1.5 rounded-lg {{ $yOpt == $year ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 font-bold' : 'hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200' }}">
                                <span>{{ $yOpt }}{{ $yOpt == now()->year ? ' (Current)' : '' }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Fast Sync Button -->
            <button type="button" @click="startSync('full', {{ $year }})"
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-xs rounded-xl shadow-xs transition-all whitespace-nowrap cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span>Sync from Odoo</span>
            </button>

            <!-- Export to CSV Button -->
            @if($isYearCached && $reportData && !empty($reportData['items']))
                <a href="{{ route('accounting.uninvoiced.export', request()->all()) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-white font-bold text-xs rounded-xl shadow-xs transition-all whitespace-nowrap">
                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Export CSV (17 Cols)</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
        <form id="filterForm" method="GET" action="{{ route('accounting.uninvoiced') }}" class="flex flex-wrap items-center justify-between gap-3">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="tab" :value="activeTab">

            <div class="flex flex-wrap items-center gap-3 flex-1">
                <!-- Search Input -->
                <div class="relative min-w-[200px] flex-1">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search Customer, SO, PO, Nopol, Chassis..." 
                           class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                {{-- Month Range Pickers (Universal Cross-Browser Month Pickers) --}}
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    {{-- From Month Picker --}}
                    <div x-data="monthPicker('start_month', '{{ $startMonth }}')" class="relative">
                        <input type="hidden" name="start_month" :value="value">
                        <button type="button" @click="toggle()" class="flex items-center gap-1.5 cursor-pointer select-none py-0.5 px-1 rounded-lg hover:bg-slate-200/50 dark:hover:bg-slate-800/50 transition-colors">
                            <span class="text-[11px] font-bold text-slate-500 uppercase">From:</span>
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-100" x-text="displayText"></span>
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-transition x-cloak
                             class="absolute z-50 top-full mt-2 left-0 w-64 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl p-3 text-slate-800 dark:text-slate-100">
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100 dark:border-slate-800">
                                <button type="button" @click="year--" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <span class="font-bold text-sm tracking-wide" x-text="year"></span>
                                <button type="button" @click="year++" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                            <div class="grid grid-cols-4 gap-1.5 py-1 text-xs">
                                <template x-for="(mName, idx) in monthNames" :key="idx">
                                    <button type="button" @click="selectMonth(idx + 1)"
                                            :class="isSelected(idx + 1) ? 'bg-indigo-600 text-white font-bold shadow-sm' : (isCurrent(idx + 1) ? 'border border-indigo-500/50 text-indigo-500 dark:text-indigo-300 hover:bg-slate-100 dark:hover:bg-slate-800' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800')"
                                            class="py-2 rounded-xl text-center transition-all cursor-pointer font-medium"
                                            x-text="mName">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <span class="text-slate-400 text-xs px-0.5 select-none">&rarr;</span>

                    {{-- To Month Picker --}}
                    <div x-data="monthPicker('end_month', '{{ $endMonth }}')" class="relative">
                        <input type="hidden" name="end_month" :value="value">
                        <button type="button" @click="toggle()" class="flex items-center gap-1.5 cursor-pointer select-none py-0.5 px-1 rounded-lg hover:bg-slate-200/50 dark:hover:bg-slate-800/50 transition-colors">
                            <span class="text-[11px] font-bold text-slate-500 uppercase">To:</span>
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-100" x-text="displayText"></span>
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-transition x-cloak
                             class="absolute z-50 top-full mt-2 right-0 w-64 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl p-3 text-slate-800 dark:text-slate-100">
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100 dark:border-slate-800">
                                <button type="button" @click="year--" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <span class="font-bold text-sm tracking-wide" x-text="year"></span>
                                <button type="button" @click="year++" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                            <div class="grid grid-cols-4 gap-1.5 py-1 text-xs">
                                <template x-for="(mName, idx) in monthNames" :key="idx">
                                    <button type="button" @click="selectMonth(idx + 1)"
                                            :class="isSelected(idx + 1) ? 'bg-indigo-600 text-white font-bold shadow-sm' : (isCurrent(idx + 1) ? 'border border-indigo-500/50 text-indigo-500 dark:text-indigo-300 hover:bg-slate-100 dark:hover:bg-slate-800' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800')"
                                            class="py-2 rounded-xl text-center transition-all cursor-pointer font-medium"
                                            x-text="mName">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Cutoff Date Picker (Point-in-Time Cutoff Selector - No Hour Needed) --}}
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-900 px-3 py-1.5 rounded-xl border border-indigo-300/80 dark:border-indigo-700/80 shadow-xs group cursor-pointer hover:border-indigo-500 transition-colors"
                     onclick="document.getElementById('cutoff_date_input').showPicker()">
                    <span class="text-[11px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-wider whitespace-nowrap">Cut off Date:</span>
                    <input type="date" name="cutoff_date" id="cutoff_date_input" value="{{ $cutoffDate }}"
                           onclick="this.showPicker()"
                           class="bg-transparent text-xs font-bold text-slate-800 dark:text-slate-100 focus:outline-none cursor-pointer [color-scheme:light] dark:[color-scheme:dark] w-28">
                    <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400 flex-shrink-0 cursor-pointer group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>

                {{-- Status Filter Dropdown --}}
                <div class="relative">
                    <select name="status" class="py-2 px-3 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 cursor-pointer font-medium">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Status</option>
                        <option value="uninvoiced" {{ $status === 'uninvoiced' ? 'selected' : '' }}>Belum Ada Invoice</option>
                        <option value="post_cutoff" {{ $status === 'post_cutoff' ? 'selected' : '' }}>Dicetak Pasca-Cutoff</option>
                        <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft Belum Posted</option>
                        <option value="reversed" {{ $status === 'reversed' ? 'selected' : '' }}>Reversed (Perlu Cetak Ulang)</option>
                    </select>
                </div>
            </div>

            <!-- Submit and Reset Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-indigo-600/20 cursor-pointer flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    <span>Apply Filter</span>
                </button>
                <a href="{{ route('accounting.uninvoiced', ['year' => $year]) }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold rounded-xl transition-all">
                    Reset
                </a>
            </div>
        </form>
    </div>

    @if(!$isYearCached)
        <!-- Prompt State (When year master data is not yet cached) -->
        <div class="bg-gradient-to-br from-indigo-50/80 via-white to-blue-50/50 dark:from-slate-800/90 dark:via-slate-800/60 dark:to-indigo-950/40 rounded-3xl p-8 border border-indigo-100 dark:border-indigo-900/50 shadow-sm text-center space-y-5">
            <div class="w-16 h-16 rounded-2xl bg-indigo-600 text-white mx-auto flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </div>
            <div class="space-y-2 max-w-md mx-auto">
                <h2 class="text-xl font-extrabold text-slate-800 dark:text-slate-100">Load Uninvoiced Contracts for {{ $year }}</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Data for Year <strong>{{ $year }}</strong> has not been loaded into the local cache. Click below to initiate the master sync from Odoo.
                </p>
            </div>

            <div class="max-w-xs mx-auto">
                <button type="button" @click="startSync('full', {{ $year }})" class="w-full py-3 px-6 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-500/25 transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Sync Year {{ $year }} from Odoo</span>
                </button>
            </div>
        </div>

    @elseif($reportData)

        <!-- Executive KPI Metric Cards (5 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
            <!-- Card 1: Total Unbilled Value -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Unbilled Value</span>
                    <span class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </span>
                </div>
                <p class="text-xl font-black text-slate-800 dark:text-slate-100 tracking-tight mt-2">
                    Rp {{ number_format($reportData['kpis']['total_unbilled_value'], 0, ',', '.') }}
                </p>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                    <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ count($reportData['items']) }}</span>
                    <span>unbilled period cycles</span>
                </div>
            </div>

            <!-- Card 2: Total Pending Units -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700 shadow-xs relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pending Fleet Units</span>
                    <span class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
                    </span>
                </div>
                <p class="text-xl font-black text-slate-800 dark:text-slate-100 tracking-tight mt-2">
                    {{ number_format($reportData['kpis']['total_pending_units']) }} <span class="text-xs font-medium text-slate-400">Units</span>
                </p>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Across {{ count($reportData['pivot_customers']) }} customers</span>
                </div>
            </div>

            <!-- Card 3: Draft Belum Posted -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-amber-200/80 dark:border-amber-800/80 shadow-xs relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Draft Invoices</span>
                    <span class="p-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </span>
                </div>
                <p class="text-xl font-black text-amber-600 dark:text-amber-400 tracking-tight mt-2">
                    {{ number_format($reportData['kpis']['draft_units']) }} <span class="text-xs font-medium text-slate-400">Units</span>
                </p>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-amber-700 dark:text-amber-300">
                    <span>Rp {{ number_format($reportData['kpis']['draft_value'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Card 4: Dicetak Pasca-Cutoff -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-blue-200/80 dark:border-blue-800/80 shadow-xs relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Invoiced Post-Cutoff</span>
                    <span class="p-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </span>
                </div>
                <p class="text-xl font-black text-blue-600 dark:text-blue-400 tracking-tight mt-2">
                    {{ number_format($reportData['kpis']['post_cutoff_units']) }} <span class="text-xs font-medium text-slate-400">Units</span>
                </p>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-blue-700 dark:text-blue-300">
                    <span>Rp {{ number_format($reportData['kpis']['post_cutoff_value'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Card 5: Returned Unbilled -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-rose-200/80 dark:border-rose-800/80 shadow-xs relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Returned (Unbilled)</span>
                    <span class="p-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path></svg>
                    </span>
                </div>
                <p class="text-xl font-black text-rose-600 dark:text-rose-400 tracking-tight mt-2">
                    {{ number_format($reportData['kpis']['returned_unbilled_units']) }} <span class="text-xs font-medium text-slate-400">Units</span>
                </p>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-rose-700 dark:text-rose-300">
                    <span>Rp {{ number_format($reportData['kpis']['returned_unbilled_value'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs & View Container -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <!-- Tabs Bar -->
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 px-4 py-2.5 bg-slate-50/50 dark:bg-slate-900/40">
                <div class="flex items-center gap-2">
                    <button type="button" @click="activeTab = 'detailed'"
                            :class="activeTab === 'detailed' ? 'bg-indigo-600 text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-700 font-semibold'"
                            class="px-3 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                        <span>Detailed Audit Table (17 Columns)</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $activeTab === 'detailed' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                            {{ count($reportData['items']) }}
                        </span>
                    </button>

                    <button type="button" @click="activeTab = 'pivot'"
                            :class="activeTab === 'pivot' ? 'bg-indigo-600 text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-700 font-semibold'"
                            class="px-3 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                        <span>Customer Monthly Pivot View</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $activeTab === 'pivot' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                            {{ count($reportData['pivot_customers']) }}
                        </span>
                    </button>
                </div>

                <!-- Active Cutoff Pill -->
                <div class="hidden md:flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-[11px] font-bold text-indigo-700 dark:text-indigo-300">
                    <span>📅 Cutoff Date:</span>
                    <span>{{ $reportData['cutoff_date_formatted'] }}</span>
                </div>
            </div>

            <!-- TAB 1: DETAILED LINE-ITEM AUDIT TABLE (17 COLUMNS) -->
            <div x-show="activeTab === 'detailed'" class="overflow-x-auto max-h-[72vh] relative">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="sticky top-0 z-20 bg-slate-900 text-white text-[11px] uppercase tracking-wider font-bold">
                        <tr class="divide-x divide-slate-700/80">
                            <th class="py-2.5 px-2 text-center w-12 bg-slate-900">No.</th>
                            <th class="py-2.5 px-2.5 min-w-[80px] bg-slate-900">Kode Cust</th>
                            <th class="py-2.5 px-3 min-w-[200px] bg-slate-900 sticky left-0 z-30 shadow-sm">Nama Customer</th>
                            <th class="py-2.5 px-2.5 min-w-[110px] bg-slate-800">Nomor SO</th>
                            <th class="py-2.5 px-2.5 min-w-[120px] bg-slate-800">Nomor PO / Kontrak</th>
                            <th class="py-2.5 px-2.5 min-w-[95px] bg-slate-800">Nopol</th>
                            <th class="py-2.5 px-2.5 min-w-[150px] bg-slate-800">No. Rangka (Chassis)</th>
                            <th class="py-2.5 px-3 min-w-[180px] bg-slate-800">Model Kendaraan</th>
                            <th class="py-2.5 px-2 text-center min-w-[65px] bg-slate-800">Tahun</th>
                            <th class="py-2.5 px-2.5 text-center min-w-[95px] bg-slate-800">Start Period</th>
                            <th class="py-2.5 px-2.5 text-center min-w-[95px] bg-slate-800">End Period</th>
                            <th class="py-2.5 px-3 min-w-[160px] bg-slate-900">Status per Cutoff</th>
                            <th class="py-2.5 px-2.5 min-w-[120px] bg-slate-800">No. Invoice Odoo</th>
                            <th class="py-2.5 px-2.5 text-center min-w-[100px] bg-slate-800">Tgl Invoice</th>
                            <th class="py-2.5 px-3 text-right min-w-[120px] bg-slate-900">Nilai Sewa (IDR)</th>
                            <th class="py-2.5 px-2.5 text-center min-w-[95px] bg-slate-800">Rental Status</th>
                            <th class="py-2.5 px-2.5 min-w-[120px] bg-slate-800">Area Pemakaian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 text-slate-700 dark:text-slate-200">
                        @forelse($reportData['items'] as $item)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/40 transition-colors">
                                <td class="py-2 px-2 text-center text-slate-400 font-mono text-[11px]">{{ $item['no'] }}</td>
                                <td class="py-2 px-2.5 font-mono text-indigo-600 dark:text-indigo-400 font-semibold">{{ $item['kode_cust'] ?: '-' }}</td>
                                <td class="py-2 px-3 font-bold text-slate-800 dark:text-slate-100 sticky left-0 z-10 bg-white dark:bg-slate-800 shadow-sm truncate max-w-[220px]" title="{{ $item['nama_customer'] }}">
                                    {{ $item['nama_customer'] }}
                                </td>
                                <td class="py-2 px-2.5 font-mono text-[11px] font-semibold text-slate-700 dark:text-slate-300">{{ $item['nomor_so'] }}</td>
                                <td class="py-2 px-2.5 text-slate-500 dark:text-slate-400 text-[11px] truncate max-w-[130px]" title="{{ $item['nomor_po'] }}">{{ $item['nomor_po'] ?: '-' }}</td>
                                <td class="py-2 px-2.5 font-mono font-black text-slate-800 dark:text-slate-100 whitespace-nowrap">{{ $item['nopol'] }}</td>
                                <td class="py-2 px-2.5 font-mono text-[11px] text-slate-600 dark:text-slate-300 truncate max-w-[150px]" title="{{ $item['chassis'] }}">{{ $item['chassis'] ?: '-' }}</td>
                                <td class="py-2 px-3 text-[11px] text-slate-600 dark:text-slate-300 truncate max-w-[180px]" title="{{ $item['model'] }}">{{ $item['model'] }}</td>
                                <td class="py-2 px-2 text-center text-slate-400 font-mono text-[11px]">{{ $item['tahun'] ?: '-' }}</td>
                                <td class="py-2 px-2.5 text-center font-mono text-[11px] text-slate-600 dark:text-slate-300">{{ $item['start_period_formatted'] }}</td>
                                <td class="py-2 px-2.5 text-center font-mono text-[11px] text-slate-600 dark:text-slate-300">{{ $item['end_period_formatted'] ?: '-' }}</td>
                                <td class="py-2 px-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $item['status_badge_class'] }}">
                                        {{ $item['status_label'] }}
                                    </span>
                                </td>
                                <td class="py-2 px-2.5 font-mono text-[11px] text-slate-600 dark:text-slate-300">{{ $item['invoice_number'] }}</td>
                                <td class="py-2 px-2.5 text-center font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ $item['invoice_date'] }}</td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-slate-800 dark:text-slate-100">
                                    {{ $item['price_unit_formatted'] }}
                                </td>
                                <td class="py-2 px-2.5 text-center">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $item['rental_status'] === 'Returned' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' }}">
                                        {{ $item['rental_status'] }}
                                    </span>
                                </td>
                                <td class="py-2 px-2.5 text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-[120px]">{{ $item['area_pemakaian'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="17" class="py-12 text-center text-slate-400">
                                    <svg class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <p class="font-bold text-sm text-slate-700 dark:text-slate-200">No Uninvoiced Records Found</p>
                                    <p class="text-xs text-slate-400 mt-0.5">All rental periods starting on or before {{ $reportData['cutoff_date_formatted'] }} have valid posted invoices!</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- TAB 2: CUSTOMER MONTHLY PIVOT VIEW -->
            <div x-show="activeTab === 'pivot'" class="overflow-x-auto max-h-[72vh] relative" style="display: none;">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="sticky top-0 z-20 bg-slate-900 text-white text-[11px] uppercase tracking-wider font-bold">
                        <tr>
                            <th rowspan="2" class="py-3 px-3 sticky left-0 z-30 bg-slate-900 border-r border-slate-700 min-w-[260px] shadow-sm">
                                Customer
                            </th>
                            @foreach($reportData['month_keys'] as $mKey)
                                <th colspan="2" class="py-2 px-2 text-center border-r border-slate-700/80 bg-slate-800">
                                    {{ $reportData['month_labels'][$mKey] ?? $mKey }}
                                </th>
                            @endforeach
                            <th colspan="2" class="py-2 px-2 text-center bg-slate-900 min-w-[170px]">
                                Period Total
                            </th>
                        </tr>
                        <tr class="border-b border-slate-700 text-[10px] text-slate-300">
                            @foreach($reportData['month_keys'] as $mKey)
                                <th class="py-1.5 px-2 text-center border-r border-slate-700/50 min-w-[50px] bg-slate-800/90 font-medium">Qty</th>
                                <th class="py-1.5 px-2 text-right border-r border-slate-700 min-w-[115px] bg-slate-800 font-medium">Value (IDR)</th>
                            @endforeach
                            <th class="py-1.5 px-2 text-center border-r border-slate-700 min-w-[60px] bg-slate-900 font-medium">Total Qty</th>
                            <th class="py-1.5 px-2 text-right min-w-[130px] bg-slate-900 font-medium">Total Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 text-slate-700 dark:text-slate-200">
                        @forelse($reportData['pivot_customers'] as $index => $customer)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/40 transition-colors">
                                <!-- Sticky Customer Name -->
                                <td class="py-2.5 px-3 sticky left-0 z-10 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 font-medium shadow-sm">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="truncate max-w-[220px]" title="{{ $customer['customer_name'] }}">
                                            @if($customer['customer_ref'])
                                                <span class="text-[10px] font-mono text-indigo-600 dark:text-indigo-400 bg-indigo-500/10 px-1 py-0.5 rounded">
                                                    {{ $customer['customer_ref'] }}
                                                </span>
                                            @endif
                                            <span class="font-bold text-slate-800 dark:text-slate-100">{{ $customer['customer_name'] }}</span>
                                        </div>
                                        <button type="button" @click="expandedRows['{{ $index }}'] = !expandedRows['{{ $index }}']"
                                                class="p-1 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-all cursor-pointer">
                                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-90': expandedRows['{{ $index }}'] }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>

                                <!-- Monthly Qty & Value Columns -->
                                @foreach($reportData['month_keys'] as $mKey)
                                    @php $mInfo = $customer['months'][$mKey] ?? ['qty' => 0, 'value' => 0]; @endphp
                                    <td class="py-2 px-2 text-center border-r border-slate-100 dark:border-slate-700/50 font-mono text-[11px] {{ $mInfo['qty'] > 0 ? 'font-bold text-rose-600 dark:text-rose-400' : 'text-slate-300 dark:text-slate-600' }}">
                                        {{ $mInfo['qty'] > 0 ? $mInfo['qty'] : '-' }}
                                    </td>
                                    <td class="py-2 px-2 text-right border-r border-slate-200 dark:border-slate-700 font-mono text-[11px] {{ $mInfo['value'] > 0 ? 'font-bold text-slate-800 dark:text-slate-100' : 'text-slate-300 dark:text-slate-600' }}">
                                        {{ $mInfo['value'] > 0 ? number_format($mInfo['value']) : '-' }}
                                    </td>
                                @endforeach

                                <!-- Customer Totals -->
                                <td class="py-2 px-2 text-center border-r border-slate-200 dark:border-slate-700 font-mono font-bold text-slate-700 dark:text-slate-300 bg-slate-50/50 dark:bg-slate-900/30">
                                    {{ $customer['total_qty'] }}
                                </td>
                                <td class="py-2 px-2 text-right font-mono font-black text-indigo-600 dark:text-indigo-400 bg-slate-50/50 dark:bg-slate-900/30">
                                    Rp {{ number_format($customer['total_value']) }}
                                </td>
                            </tr>

                            <!-- Vehicle Details Rows (Collapsible) -->
                            @foreach($customer['vehicles'] ?? [] as $v)
                                <tr x-show="expandedRows['{{ $index }}']" x-cloak class="bg-slate-50/40 dark:bg-slate-900/40 hover:bg-indigo-50/30 dark:hover:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 transition-colors">
                                    <td class="py-2 px-3 sticky left-0 z-10 bg-slate-50 dark:bg-slate-900 border-r border-slate-200 dark:border-slate-700 pl-8 shadow-sm">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-mono font-black text-slate-800 dark:text-slate-100 text-[11px] whitespace-nowrap">{{ $v['nopol'] }}</span>
                                            <span class="text-[9px] font-mono text-indigo-600 dark:text-indigo-400 bg-indigo-500/10 px-1 py-0.5 rounded whitespace-nowrap font-semibold">{{ $v['so'] }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 truncate max-w-[210px] mt-0.5" title="{{ $v['model'] }} | Chassis: {{ $v['chassis'] }}">
                                            {{ $v['model'] }}
                                        </div>
                                    </td>

                                    @foreach($reportData['month_keys'] as $mKey)
                                        @php $vm = $v['months'][$mKey] ?? ['qty' => 0, 'value' => 0]; @endphp
                                        <td class="py-2 px-2 text-center border-r border-slate-100 dark:border-slate-700/50 font-mono text-[11px] {{ $vm['qty'] > 0 ? 'text-slate-700 dark:text-slate-300 font-semibold' : 'text-slate-300 dark:text-slate-600' }}">
                                            {{ $vm['qty'] > 0 ? '1' : '-' }}
                                        </td>
                                        <td class="py-2 px-2 text-right border-r border-slate-200 dark:border-slate-700 font-mono text-[11px]">
                                            @if($vm['value'] > 0)
                                                <span class="font-bold text-slate-800 dark:text-slate-100">{{ number_format($vm['value']) }}</span>
                                            @else
                                                <span class="text-slate-300 dark:text-slate-600">-</span>
                                            @endif
                                        </td>
                                    @endforeach

                                    <td class="py-2 px-2 text-center border-r border-slate-200 dark:border-slate-700 font-mono text-[11px] text-slate-400 bg-slate-50/30 dark:bg-slate-800/30">
                                        1
                                    </td>
                                    <td class="py-2 px-2 text-right font-mono font-bold text-indigo-600 dark:text-indigo-400 text-[11px] bg-slate-50/30 dark:bg-slate-800/30">
                                        Rp {{ number_format($v['total_value']) }}
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="{{ count($reportData['month_keys']) * 2 + 3 }}" class="py-12 text-center text-slate-400">
                                    No uninvoiced customer summaries found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <!-- Grand Total Footer Row -->
                    @if(!empty($reportData['pivot_customers']))
                        <tfoot class="sticky bottom-0 z-20 bg-slate-900 text-white font-bold text-[11px]">
                            <tr class="border-t-2 border-indigo-500">
                                <td class="py-3 px-3 sticky left-0 z-30 bg-slate-900 border-r border-slate-700 uppercase tracking-wider">
                                    Grand Total (Uninvoiced)
                                </td>
                                @foreach($reportData['month_keys'] as $mKey)
                                    <td class="py-2.5 px-2 text-center border-r border-slate-700 font-mono text-rose-400">
                                        {{ $reportData['month_totals'][$mKey]['qty'] > 0 ? $reportData['month_totals'][$mKey]['qty'] : '-' }}
                                    </td>
                                    <td class="py-2.5 px-2 text-right border-r border-slate-700 font-mono text-emerald-400">
                                        {{ $reportData['month_totals'][$mKey]['value'] > 0 ? number_format($reportData['month_totals'][$mKey]['value']) : '-' }}
                                    </td>
                                @endforeach
                                <td class="py-2.5 px-2 text-center border-r border-slate-700 font-mono text-indigo-300">
                                    {{ $reportData['kpis']['total_pending_units'] }}
                                </td>
                                <td class="py-2.5 px-2 text-right font-mono text-emerald-300">
                                    Rp {{ number_format($reportData['kpis']['total_unbilled_value']) }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

        </div>

    @endif

    <!-- Sync Progress Modal (Strictly Isolated) -->
    <div x-show="syncModal.show" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
        
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-2xl border border-slate-200 dark:border-slate-700 max-w-md w-full space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full" :class="syncModal.status === 'completed' ? 'bg-emerald-500' : (syncModal.status === 'error' ? 'bg-rose-500' : 'bg-indigo-500 animate-ping')"></span>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">
                        <span x-show="syncModal.status === 'running'">Synchronizing Uninvoiced Data...</span>
                        <span x-show="syncModal.status === 'completed'">Sync Complete!</span>
                        <span x-show="syncModal.status === 'error'">Sync Failed</span>
                    </h3>
                </div>
                <span class="text-[11px] font-mono font-bold text-indigo-600 dark:text-indigo-400" x-text="syncModal.percent + '%'"></span>
            </div>

            <!-- Progress Bar -->
            <div class="space-y-1.5">
                <div class="w-full bg-slate-100 dark:bg-slate-700 h-2.5 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full transition-all duration-300"
                         :style="'width: ' + syncModal.percent + '%'"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-400">
                    <span x-text="syncModal.stage"></span>
                    <span x-text="syncModal.secondsElapsed + 's elapsed'"></span>
                </div>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed" x-text="syncModal.message"></p>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-700/60">
                <button type="button" x-show="syncModal.status === 'error'" @click="syncModal.show = false"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition-all cursor-pointer">
                    Dismiss
                </button>
                <button type="button" x-show="syncModal.status === 'completed'" @click="window.location.reload()"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all cursor-pointer shadow-md shadow-emerald-600/20">
                    View Updated Report
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function uninvoicedPage() {
    return {
        activeTab: '{{ $activeTab }}',
        expandedRows: {},
        syncModal: {
            show: false,
            year: {{ (int)$year }},
            status: 'idle', // 'idle' | 'running' | 'completed' | 'error'
            percent: 0,
            stage: '',
            message: '',
            records: 0,
            total: 0,
            secondsElapsed: 0,
            timerInterval: null,
            pollInterval: null,
        },
        startSync(type, year) {
            this.syncModal.year = year || {{ (int)$year }};
            this.syncModal.status = 'running';
            this.syncModal.percent = 5;
            this.syncModal.stage = 'Connecting';
            this.syncModal.message = 'Connecting to Odoo server...';
            this.syncModal.secondsElapsed = 0;
            this.syncModal.show = true;

            if (this.syncModal.timerInterval) clearInterval(this.syncModal.timerInterval);
            this.syncModal.timerInterval = setInterval(() => {
                this.syncModal.secondsElapsed++;
            }, 1000);

            // Trigger isolated sync via POST
            fetch('{{ route('accounting.uninvoiced.sync') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ year: this.syncModal.year })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'completed') {
                    this.syncModal.status = 'completed';
                    this.syncModal.percent = 100;
                    this.syncModal.stage = 'Complete';
                    this.syncModal.message = data.message || 'Synchronization completed successfully!';
                    if (this.syncModal.pollInterval) clearInterval(this.syncModal.pollInterval);
                    if (this.syncModal.timerInterval) clearInterval(this.syncModal.timerInterval);
                } else if (data.status === 'error') {
                    this.syncModal.status = 'error';
                    this.syncModal.message = data.message || 'Sync failed.';
                    if (this.syncModal.pollInterval) clearInterval(this.syncModal.pollInterval);
                    if (this.syncModal.timerInterval) clearInterval(this.syncModal.timerInterval);
                }
            })
            .catch(err => {
                this.syncModal.status = 'error';
                this.syncModal.message = 'Network error during sync: ' + err;
                if (this.syncModal.pollInterval) clearInterval(this.syncModal.pollInterval);
                if (this.syncModal.timerInterval) clearInterval(this.syncModal.timerInterval);
            });

            // Poll progress
            if (this.syncModal.pollInterval) clearInterval(this.syncModal.pollInterval);
            this.syncModal.pollInterval = setInterval(() => {
                if (this.syncModal.status !== 'running') return;
                fetch('{{ route('accounting.uninvoiced.sync-progress') }}?year=' + this.syncModal.year)
                .then(r => r.json())
                .then(p => {
                    if (p && p.percent) {
                        this.syncModal.percent = p.percent;
                        this.syncModal.stage = p.stage;
                        this.syncModal.message = p.message;
                        if (p.status === 'completed') {
                            this.syncModal.status = 'completed';
                            clearInterval(this.syncModal.pollInterval);
                            clearInterval(this.syncModal.timerInterval);
                        } else if (p.status === 'error') {
                            this.syncModal.status = 'error';
                            clearInterval(this.syncModal.pollInterval);
                            clearInterval(this.syncModal.timerInterval);
                        }
                    }
                })
                .catch(() => {});
            }, 1000);
        }
    };
}

function monthPicker(name, initialValue) {
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    let now = new Date();
    let initYear = now.getFullYear();
    let initMonth = now.getMonth() + 1;
    
    if (initialValue && typeof initialValue === 'string' && initialValue.includes('-')) {
        const parts = initialValue.split('-');
        initYear = parseInt(parts[0], 10) || initYear;
        initMonth = parseInt(parts[1], 10) || initMonth;
    }

    return {
        name: name,
        open: false,
        year: initYear,
        selectedMonth: initMonth,
        monthNames: monthNames,
        get value() {
            return this.year + '-' + String(this.selectedMonth).padStart(2, '0');
        },
        get displayText() {
            return this.monthNames[this.selectedMonth - 1] + ' ' + this.year;
        },
        toggle() {
            this.open = !this.open;
        },
        selectMonth(m) {
            this.selectedMonth = m;
            this.open = false;
        },
        isSelected(m) {
            return this.selectedMonth === m;
        },
        isCurrent(m) {
            return (now.getMonth() + 1) === m && now.getFullYear() === this.year;
        }
    };
}
</script>
@endsection
