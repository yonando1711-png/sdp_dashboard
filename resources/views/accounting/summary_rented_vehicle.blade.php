@extends('layouts.app')

@section('content')
<div class="p-4 md:p-6 w-full space-y-5" x-data="summaryRentedVehiclePage()">

    <!-- Header & Action Bar -->
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl md:text-2xl font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">Summary of Rented Vehicle</h1>
                <span class="px-2 py-0.5 text-[11px] font-bold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-full">
                    Untaxed
                </span>
                <span class="px-2 py-0.5 text-[11px] font-bold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 rounded-full">
                    Accounting Report
                </span>
                @if($isYearCached)
                    <span class="px-2 py-0.5 text-[11px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-full flex items-center gap-1.5" 
                          title="Synced at: {{ $lastSyncedAt ? \Carbon\Carbon::parse($lastSyncedAt, 'UTC')->setTimezone(config('app.timezone', 'Asia/Jakarta'))->format('d M Y H:i:s T') : '-' }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Synced: {{ $lastSyncFormatted }}</span>
                    </span>
                @else
                    <span class="px-2 py-0.5 text-[11px] font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/30 rounded-full flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span>Not Synced</span>
                    </span>
                @endif
            </div>
            <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Multi-month revenue pivot normalized by billing period with automatic change detection & vehicle fleet breakdown.
            </p>
        </div>

        <!-- Controls & Actions (Single row with clean divider) -->
        <div class="flex items-center gap-2 shrink-0 flex-wrap sm:flex-nowrap">
            <!-- Year Selector Dropdown -->
            <div class="relative" x-data="{ openYear: false }" @click.outside="openYear = false">
                <button type="button" @click="openYear = !openYear"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 transition-all whitespace-nowrap">
                    <span>📅 Year: {{ $year }}</span>
                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openYear" x-cloak style="display: none;"
                     class="absolute left-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-2 z-50 text-xs divide-y divide-slate-100 dark:divide-slate-700/60">
                    <div class="px-2 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Switch Fiscal Year</div>
                    <div class="py-1 space-y-1">
                        @foreach(range(max((int)$year, now()->year), 2024) as $yOpt)
                            <a href="{{ route('accounting.summary-rented-vehicle', array_merge(request()->except(['year', 'sync_type']), ['year' => $yOpt])) }}"
                               class="flex items-center justify-between px-2.5 py-1.5 rounded-lg {{ $yOpt == $year ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 font-bold' : 'hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200' }}">
                                <span>{{ $yOpt }}{{ $yOpt == now()->year ? ' (Current)' : '' }}</span>
                                @if(\Illuminate\Support\Facades\Cache::has("accounting_subscription_master_{$yOpt}"))
                                    <span class="w-2 h-2 rounded-full bg-emerald-500" title="Synced in Cache"></span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Sync Controls Dropdown -->
            <div class="relative" x-data="{ openSync: false }" @click.outside="openSync = false">
                <button type="button" @click="openSync = !openSync"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold text-xs rounded-xl border border-indigo-200 dark:border-indigo-800 shadow-xs transition-all whitespace-nowrap">
                    <span>⚡ Sync</span>
                    <svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openSync" x-cloak style="display: none;"
                     class="absolute left-0 mt-2 w-72 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-2 z-50 divide-y divide-slate-100 dark:divide-slate-700/60">
                    <div class="p-1 space-y-1">
                        <!-- Fast Sync -->
                        <button type="button" @click="openSync = false; startSync('fast', {{ $year }})"
                                class="w-full text-left flex items-start gap-2.5 p-2 rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-950/40 text-slate-800 dark:text-slate-100 transition-colors cursor-pointer">
                            <span class="text-base leading-none mt-0.5">⚡</span>
                            <div>
                                <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400">Fast Incremental Sync</div>
                                <p class="text-[10px] text-slate-400 mt-0.5 leading-snug">Checks Odoo write_date for modified contracts in 1–2s.</p>
                            </div>
                        </button>
                        <!-- Full Re-fetch -->
                        <button type="button" @click="openSync = false; startSync('full', {{ $year }})"
                                class="w-full text-left flex items-start gap-2.5 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 text-slate-800 dark:text-slate-100 transition-colors cursor-pointer">
                            <span class="text-base leading-none mt-0.5">🔄</span>
                            <div>
                                <div class="text-xs font-bold text-slate-700 dark:text-slate-200">Full Re-fetch Year {{ $year }}</div>
                                <p class="text-[10px] text-slate-400 mt-0.5 leading-snug">Batched 500 records at once. Guaranteed zero timeouts.</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            @if($isYearCached)
                <!-- Divider -->
                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700 hidden sm:block mx-0.5"></div>

                <!-- Export to Excel Dropdown -->
                <div class="relative" x-data="{ openExport: false }" @click.outside="openExport = false">
                    <button type="button" 
                            @click="openExport = !openExport"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-xs rounded-xl shadow-sm transition-all whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Export Excel</span>
                        <svg class="w-3 h-3 text-emerald-100 transition-transform duration-200" :class="{ 'rotate-180': openExport }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="openExport"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                         x-cloak
                         style="display: none;"
                         class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-2 z-50 divide-y divide-slate-100 dark:divide-slate-700/60">
                        
                        <div class="px-3 py-2 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                            Choose Export Format
                        </div>

                        <div class="py-1 space-y-1">
                            <!-- Option B: Multi-Tab (Recommended) -->
                            <a href="{{ route('accounting.summary-rented-vehicle.export', array_merge(request()->all(), ['format' => 'multitab', 'year' => $year])) }}"
                               @click="openExport = false"
                                class="group flex items-start gap-3 p-2.5 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition-colors">
                                <div class="mt-0.5 p-2 bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 rounded-lg group-hover:bg-emerald-200 dark:group-hover:bg-emerald-800 transition-colors shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-100 group-hover:text-emerald-700 dark:group-hover:text-emerald-400">Multi-Tab Excel (2 Sheets)</span>
                                        <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-800 dark:bg-emerald-900/80 dark:text-emerald-300 text-[9px] font-bold rounded">Recommended</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">
                                        <strong>Sheet 1:</strong> Customer Summary<br>
                                        <strong>Sheet 2:</strong> Flat Vehicle Details (Pivot-ready)
                                    </p>
                                </div>
                            </a>

                            <!-- Option A: Hierarchical -->
                            <a href="{{ route('accounting.summary-rented-vehicle.export', array_merge(request()->all(), ['format' => 'hierarchical', 'year' => $year])) }}"
                               @click="openExport = false"
                                class="group flex items-start gap-3 p-2.5 rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors">
                                <div class="mt-0.5 p-2 bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 rounded-lg group-hover:bg-indigo-200 dark:group-hover:bg-indigo-800 transition-colors shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-100 group-hover:text-indigo-700 dark:group-hover:text-indigo-400">Hierarchical Excel (1 Sheet)</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">
                                        Single sheet with collapsible vehicle rows (+ / - groupings) nested under customers.
                                    </p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Export to PDF Dropdown -->
                <div class="relative" x-data="{ openPdf: false }" @click.outside="openPdf = false">
                    <button type="button" 
                            @click="openPdf = !openPdf"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-rose-600 hover:bg-rose-700 active:scale-95 text-white font-bold text-xs rounded-xl shadow-sm transition-all whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <span>Export PDF</span>
                        <svg class="w-3 h-3 text-rose-100 transition-transform duration-200" :class="{ 'rotate-180': openPdf }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- PDF Dropdown Menu -->
                    <div x-show="openPdf"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                         x-cloak
                         style="display: none;"
                         class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-2 z-50 divide-y divide-slate-100 dark:divide-slate-700/60">
                        
                        <div class="px-3 py-2 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                            Choose PDF Format
                        </div>

                        <div class="py-1 space-y-1">
                            <!-- Option 1: Detailed PDF (with Vehicle Breakdown) -->
                            <a href="{{ route('accounting.summary-rented-vehicle.export-pdf', array_merge(request()->all(), ['type' => 'detailed', 'year' => $year])) }}"
                               target="_blank"
                               @click="openPdf = false"
                               class="group flex items-start gap-3 p-2.5 rounded-xl hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors">
                                <div class="mt-0.5 p-2 bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300 rounded-lg group-hover:bg-rose-200 dark:group-hover:bg-rose-800 transition-colors shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-100 group-hover:text-rose-700 dark:group-hover:text-rose-400">Detailed PDF (with Vehicles)</span>
                                        <span class="px-1.5 py-0.5 bg-rose-100 text-rose-800 dark:bg-rose-900/80 dark:text-rose-300 text-[9px] font-bold rounded">Full Breakdown</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">
                                        Hierarchical view with vehicle rows. <em>Recommended for filtered search or &le; 50 customers</em> (for full 3,400+ fleet, use Excel).
                                    </p>
                                </div>
                            </a>

                            <!-- Option 2: Summary PDF (Customer Overview) -->
                            <a href="{{ route('accounting.summary-rented-vehicle.export-pdf', array_merge(request()->all(), ['type' => 'summary', 'year' => $year])) }}"
                               target="_blank"
                               @click="openPdf = false"
                               class="group flex items-start gap-3 p-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
                                <div class="mt-0.5 p-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg group-hover:bg-slate-200 dark:group-hover:bg-slate-600 transition-colors shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-100 group-hover:text-slate-900 dark:group-hover:text-white">Summary PDF (Overview)</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">
                                        Executive overview: Customer total quantities and contract values only (faster).
                                    </p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if(!empty($syncMessage))
        <div class="p-3.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/80 rounded-2xl flex items-center gap-2.5 text-emerald-800 dark:text-emerald-300 text-xs shadow-xs">
            <svg class="w-4 h-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span class="font-semibold">{{ $syncMessage }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/80 rounded-2xl flex items-start gap-3 text-rose-700 dark:text-rose-300 text-xs shadow-sm">
            <svg class="w-5 h-5 shrink-0 text-rose-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1 font-medium leading-relaxed">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Filters Bar (Always visible) -->
    <div class="bg-white dark:bg-slate-800/90 rounded-2xl p-4 shadow-sm border border-slate-200 dark:border-slate-700/80">
        <form id="filterForm" method="GET" action="{{ route('accounting.summary-rented-vehicle') }}" class="flex flex-wrap items-center justify-between gap-3">
            <input type="hidden" name="generate" value="1">
            <input type="hidden" name="year" value="{{ $year }}">

            <div class="flex flex-wrap items-center gap-3 flex-1">
                <!-- Search Input -->
                <div class="relative min-w-[220px] flex-1">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search Customer Name or Ref Code..." 
                           class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                {{-- Date Range Pickers (Universal Cross-Browser Month Pickers) --}}
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    {{-- From Month Picker --}}
                    <div x-data="monthPicker('start_month', '{{ $startMonth }}')" class="relative">
                        <input type="hidden" name="start_month" :value="value">
                        <button type="button" @click="toggle()" class="flex items-center gap-1.5 cursor-pointer select-none py-0.5 px-1 rounded-lg hover:bg-slate-200/50 dark:hover:bg-slate-800/50 transition-colors">
                            <span class="text-[11px] font-bold text-slate-500 uppercase">From:</span>
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-100" x-text="displayText"></span>
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </button>

                        {{-- Month Dropdown Modal --}}
                        <div x-show="open" 
                             @click.outside="open = false" 
                             x-transition 
                             x-cloak
                             class="absolute z-50 top-full mt-2 left-0 w-64 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl p-3 text-slate-800 dark:text-slate-100">
                            <!-- Year Selector -->
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100 dark:border-slate-800">
                                <button type="button" @click="year--" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <span class="font-bold text-sm tracking-wide" x-text="year"></span>
                                <button type="button" @click="year++" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                            <!-- 12 Months Grid -->
                            <div class="grid grid-cols-4 gap-1.5 py-1 text-xs">
                                <template x-for="(mName, idx) in monthNames" :key="idx">
                                    <button type="button" 
                                            @click="selectMonth(idx + 1)"
                                            :class="isSelected(idx + 1) ? 'bg-indigo-600 text-white font-bold shadow-sm' : (isCurrent(idx + 1) ? 'border border-indigo-500/50 text-indigo-500 dark:text-indigo-300 hover:bg-slate-100 dark:hover:bg-slate-800' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white')"
                                            class="py-2 rounded-xl text-center transition-all cursor-pointer font-medium"
                                            x-text="mName">
                                    </button>
                                </template>
                            </div>
                            <!-- Footer Actions -->
                            <div class="flex items-center justify-between pt-2 mt-2 border-t border-slate-100 dark:border-slate-800 text-[11px]">
                                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">Cancel</button>
                                <button type="button" @click="selectThisMonth()" class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">This month</button>
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

                        {{-- Month Dropdown Modal --}}
                        <div x-show="open" 
                             @click.outside="open = false" 
                             x-transition 
                             x-cloak
                             class="absolute z-50 top-full mt-2 right-0 w-64 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl p-3 text-slate-800 dark:text-slate-100">
                            <!-- Year Selector -->
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100 dark:border-slate-800">
                                <button type="button" @click="year--" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <span class="font-bold text-sm tracking-wide" x-text="year"></span>
                                <button type="button" @click="year++" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                            <!-- 12 Months Grid -->
                            <div class="grid grid-cols-4 gap-1.5 py-1 text-xs">
                                <template x-for="(mName, idx) in monthNames" :key="idx">
                                    <button type="button" 
                                            @click="selectMonth(idx + 1)"
                                            :class="isSelected(idx + 1) ? 'bg-indigo-600 text-white font-bold shadow-sm' : (isCurrent(idx + 1) ? 'border border-indigo-500/50 text-indigo-500 dark:text-indigo-300 hover:bg-slate-100 dark:hover:bg-slate-800' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white')"
                                            class="py-2 rounded-xl text-center transition-all cursor-pointer font-medium"
                                            x-text="mName">
                                    </button>
                                </template>
                            </div>
                            <!-- Footer Actions -->
                            <div class="flex items-center justify-between pt-2 mt-2 border-t border-slate-100 dark:border-slate-800 text-[11px]">
                                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">Cancel</button>
                                <button type="button" @click="selectThisMonth()" class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">This month</button>
                            </div>
                        </div>
                    </div>
                </div>

                @if($hasQuery)
                    <!-- Show Changes Only Toggle -->
                    <label class="flex items-center gap-2 text-xs font-medium text-amber-700 dark:text-amber-400 cursor-pointer select-none bg-amber-500/10 px-3 py-2 rounded-xl border border-amber-500/30">
                        <input type="checkbox" name="changes_only" value="1" {{ $changesOnly ? 'checked' : '' }} onchange="this.form.submit()"
                               class="rounded text-amber-600 focus:ring-amber-500 dark:bg-slate-800 border-amber-300">
                        <span>⚡ Changes Only</span>
                    </label>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    <span>{{ $hasQuery ? 'Apply Filter' : 'Generate Report' }}</span>
                </button>
                @if($hasQuery)
                    <a href="{{ route('accounting.summary-rented-vehicle', ['year' => $year]) }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold rounded-xl transition-all">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if(!$isYearCached)
        <!-- Year Sync Selection Card (When requested year is not cached yet) -->
        <div class="bg-gradient-to-br from-indigo-50/80 via-white to-blue-50/50 dark:from-slate-800/90 dark:via-slate-800/60 dark:to-indigo-950/40 rounded-3xl p-8 border border-indigo-100 dark:border-indigo-900/50 shadow-sm text-center space-y-5">
            <div class="w-16 h-16 rounded-2xl bg-indigo-600 text-white mx-auto flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </div>
            <div class="space-y-2 max-w-md mx-auto">
                <h2 class="text-xl font-extrabold text-slate-800 dark:text-slate-100">Sync Fiscal Year {{ $year }} from Odoo</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Data for Year <strong>{{ $year }}</strong> has not been cached yet. Click below to start the batched sync (500 records per batch) to load all subscription contracts and invoice periods.
                </p>
            </div>

            <!-- Year Selection Buttons -->
            <div class="max-w-md mx-auto space-y-4">
                <div class="flex items-center justify-center gap-2 flex-wrap">
                    @foreach(range(max((int)$year, now()->year), 2024) as $yOpt)
                        <a href="{{ route('accounting.summary-rented-vehicle', ['year' => $yOpt]) }}"
                           class="flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border select-none text-xs font-bold transition-all {{ $yOpt == $year ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-500/20' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                            <span>{{ $yOpt }}{{ $yOpt == now()->year ? ' (Current Year)' : '' }}</span>
                        </a>
                    @endforeach
                </div>

                <div>
                    <button type="button" @click="startSync('full', {{ $year }})" class="w-full py-3 px-6 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-500/25 transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Sync Year {{ $year }} from Odoo</span>
                    </button>
                </div>
            </div>
        </div>

    @elseif(!$hasQuery)
        <!-- Prompt State (When user just opens the page without query) -->
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl p-12 text-center border border-slate-200 dark:border-slate-700/80 shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100">Ready to View Summary of Rented Vehicle</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1.5 leading-relaxed">
                Year <strong>{{ $year }}</strong> master data is loaded and cached. Select your desired Month range above and click <span class="font-bold text-indigo-600 dark:text-indigo-400">"Apply Filter"</span> to display the report instantly.
            </p>
        </div>

    @else
        <!-- KPI Metric Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
            <!-- Card 1: Active Customers -->
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/80 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Customers</span>
                    <div class="p-2 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                </div>
                <p class="text-2xl font-black text-slate-800 dark:text-slate-100 mt-2">
                    {{ number_format(count($reportData['customers'] ?? [])) }}
                </p>
                <p class="text-[11px] text-slate-400 mt-1">Customers with active subscriptions</p>
            </div>

            <!-- Card 2: Period Total Revenue -->
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/80 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Period Value</span>
                    <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <p class="text-xl md:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2 truncate">
                    Rp {{ number_format($reportData['totals']['grand_total_value'] ?? 0) }}
                </p>
                <p class="text-[11px] text-slate-400 mt-1">Normalized untaxed monthly sum</p>
            </div>

            <!-- Card 3: Period Changes Detected -->
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/80 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Period Adjustments</span>
                    <div class="p-2 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </div>
                </div>
                <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-2">
                    {{ number_format($reportData['summary']['has_period_changes'] ?? 0) }}
                </p>
                <p class="text-[11px] text-slate-400 mt-1">E.g., Monthly &rarr; Quarterly/Yearly</p>
            </div>

            <!-- Card 4: Price Changes Detected -->
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/80 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Rate Adjustments</span>
                    <div class="p-2 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>
                </div>
                <p class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-2">
                    {{ number_format($reportData['summary']['has_price_changes'] ?? 0) }}
                </p>
                <p class="text-[11px] text-slate-400 mt-1">Price increases or renegotiations</p>
            </div>
        </div>

        <!-- Data Pivot Table -->
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700/80 overflow-hidden">
            <div class="overflow-x-auto max-h-[72vh] relative">
                <table class="w-full text-left text-xs border-collapse">
                    <!-- Table Header -->
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
                                <th class="py-1.5 px-2 text-center border-r border-slate-700/50 min-w-[50px] bg-slate-800/90 font-medium">Qty.</th>
                                <th class="py-1.5 px-2 text-right border-r border-slate-700 min-w-[115px] bg-slate-800 font-medium">Value (IDR)</th>
                            @endforeach
                            <th class="py-1.5 px-2 text-center border-r border-slate-700 min-w-[60px] bg-slate-900 font-medium">Max Qty</th>
                            <th class="py-1.5 px-2 text-right min-w-[130px] bg-slate-900 font-medium">Total Value</th>
                        </tr>
                    </thead>

                    <!-- Table Body -->
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 text-slate-700 dark:text-slate-200">
                        @forelse($reportData['customers'] as $index => $customer)
                            @php
                                $cKey = $customer['customer_key'];
                                $hasChanges = !empty($customer['has_any_period_change']) || !empty($customer['has_any_price_change']);
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/40 transition-colors {{ $hasChanges ? 'bg-amber-500/5' : '' }}">
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
                                        <!-- Row Expand Toggle for Vehicle Details -->
                                        <button type="button" @click="expandedRows['{{ $index }}'] = !expandedRows['{{ $index }}']"
                                                class="p-1 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-all">
                                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-90': expandedRows['{{ $index }}'] }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>

                                <!-- Monthly Qty & Value Columns -->
                                @foreach($reportData['month_keys'] as $mKey)
                                    @php
                                        $mInfo = $customer['months'][$mKey] ?? ['qty' => 0, 'value' => 0, 'units' => [], 'has_period_change' => false, 'has_price_change' => false];
                                    @endphp
                                    <td class="py-2 px-2 text-center border-r border-slate-100 dark:border-slate-700/50 font-semibold {{ $mInfo['qty'] > 0 ? 'text-slate-800 dark:text-slate-200' : 'text-slate-300 dark:text-slate-600' }}">
                                        {{ $mInfo['qty'] > 0 ? $mInfo['qty'] : '-' }}
                                    </td>
                                    <td class="py-2 px-2 text-right border-r border-slate-200 dark:border-slate-700 font-mono {{ $mInfo['value'] > 0 ? 'text-slate-800 dark:text-slate-100' : 'text-slate-300 dark:text-slate-600' }}">
                                        @if($mInfo['value'] > 0)
                                            <div class="flex items-center justify-end gap-1">
                                                @if($mInfo['has_period_change'])
                                                    <span class="inline-flex items-center px-1 py-0.2 text-[9px] font-extrabold bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded border border-amber-500/30" title="Invoice billing period changed this month">
                                                        🔄
                                                    </span>
                                                @endif
                                                @if($mInfo['has_price_change'])
                                                    <span class="inline-flex items-center px-1 py-0.2 text-[9px] font-extrabold bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded border border-rose-500/30" title="Normalized monthly rate adjusted this month">
                                                        💲
                                                    </span>
                                                @endif
                                                <span>{{ number_format($mInfo['value']) }}</span>
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endforeach

                                <!-- Period Totals -->
                                <td class="py-2 px-2 text-center border-r border-slate-200 dark:border-slate-700 font-bold bg-slate-50/50 dark:bg-slate-800/50">
                                    {{ $customer['max_qty'] }}
                                </td>
                                <td class="py-2 px-2 text-right font-mono font-black text-indigo-600 dark:text-indigo-400 bg-slate-50/50 dark:bg-slate-800/50">
                                    Rp {{ number_format($customer['total_value']) }}
                                </td>
                            </tr>

                            <!-- Active Contract Vehicles Banner Row (when expanded) -->
                            <tr x-show="expandedRows['{{ $index }}']" x-cloak class="bg-indigo-50/70 dark:bg-indigo-950/40 border-y border-indigo-100 dark:border-indigo-900/50">
                                <td :colspan="{{ count($reportData['month_keys']) * 2 + 3 }}" class="py-2 px-3 pl-8 text-xs font-bold text-indigo-700 dark:text-indigo-300">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="p-1 rounded bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                            </div>
                                            <span>Active Contract Vehicles ({{ count($customer['vehicles'] ?? []) }} Units)</span>
                                        </div>
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-normal">
                                            Normalized monthly rate: <span class="font-medium text-slate-700 dark:text-slate-200">Yearly ÷ 12, Quarterly ÷ 3, Bi-monthly ÷ 2</span>
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Each Vehicle as a Direct Row in the Main Table for Perfect Column Alignment -->
                            @foreach($customer['vehicles'] ?? [] as $v)
                                <tr x-show="expandedRows['{{ $index }}']" x-cloak class="bg-slate-50/40 dark:bg-slate-900/40 hover:bg-indigo-50/30 dark:hover:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 transition-colors">
                                    <!-- Customer Column: Sticky Left (Nopol, SO, Vehicle Model) -->
                                    <td class="py-2 px-3 sticky left-0 z-10 bg-slate-50 dark:bg-slate-900 border-r border-slate-200 dark:border-slate-700 pl-8 shadow-sm">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-mono font-black text-slate-800 dark:text-slate-100 text-[11px] whitespace-nowrap">{{ $v['nopol'] }}</span>
                                            <span class="text-[9px] font-mono text-indigo-600 dark:text-indigo-400 bg-indigo-500/10 px-1 py-0.5 rounded whitespace-nowrap font-semibold">{{ $v['so'] }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 truncate max-w-[210px] mt-0.5" title="{{ $v['product'] }}">
                                            {{ $v['product'] }}
                                        </div>
                                    </td>

                                    <!-- Monthly Qty & Value Columns (EXACT same columns as parent table) -->
                                    @foreach($reportData['month_keys'] as $mKey)
                                        @php $mUnit = $v['months'][$mKey] ?? null; @endphp
                                        <!-- Qty Column (directly aligns under QTY header) -->
                                        <td class="py-2 px-2 text-center border-r border-slate-100 dark:border-slate-700/50 font-mono text-[11px] {{ $mUnit && !empty($mUnit['active']) ? 'text-slate-700 dark:text-slate-300 font-semibold' : 'text-slate-300 dark:text-slate-600' }}">
                                            {{ $mUnit && !empty($mUnit['active']) ? '1' : '-' }}
                                        </td>
                                        <!-- Value Column (directly aligns under VALUE (IDR) header) -->
                                        <td class="py-2 px-2 text-right border-r border-slate-200 dark:border-slate-700 font-mono text-[11px]">
                                            @if($mUnit && !empty($mUnit['active']))
                                                <div class="flex flex-col items-end">
                                                    <span class="font-bold text-slate-800 dark:text-slate-100">
                                                        {{ number_format($mUnit['monthly_rate']) }}
                                                    </span>
                                                    <div class="inline-flex items-center gap-1 mt-0.5 px-2 py-0.5 rounded-full text-[9px] font-semibold border shadow-2xs {{ $mUnit['period'] === 'Yearly' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' : ($mUnit['period'] === 'Quarterly' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-500/30' : ($mUnit['period'] === 'Bi-monthly' ? 'bg-cyan-50 dark:bg-cyan-950/40 text-cyan-700 dark:text-cyan-300 border-cyan-500/30' : 'bg-slate-100 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-600')) }}">
                                                        @if(!empty($mUnit['period_change']))
                                                            <span title="{{ $mUnit['period_change'] }}" class="cursor-help text-[10px] leading-none" role="img" aria-label="Period change">🔄</span>
                                                        @endif
                                                        @if(!empty($mUnit['price_change']))
                                                            <span title="{{ $mUnit['price_change'] }}" class="cursor-help text-[10px] leading-none" role="img" aria-label="Price change">💲</span>
                                                        @endif
                                                        <span>{{ $mUnit['period'] }}{{ $mUnit['rental_qty'] > 1 ? ' (÷'.(int)$mUnit['rental_qty'].')' : '' }}</span>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-slate-300 dark:text-slate-600 font-mono">-</span>
                                            @endif
                                        </td>
                                    @endforeach

                                    <!-- Period Total Max Qty (directly aligns under Max Qty header) -->
                                    <td class="py-2 px-2 text-center border-r border-slate-200 dark:border-slate-700 font-mono text-[11px] text-slate-400 bg-slate-50/30 dark:bg-slate-800/30">
                                        {{ $v['total_value'] > 0 ? '1' : '-' }}
                                    </td>
                                    <!-- Period Total Value (directly aligns under Total Value header) -->
                                    <td class="py-2 px-2 text-right font-mono font-bold text-indigo-600 dark:text-indigo-400 text-[11px] bg-slate-50/30 dark:bg-slate-800/30">
                                        Rp {{ number_format($v['total_value']) }}
                                    </td>
                                </tr>
                            @endforeach

                            <!-- Customer Subtotal Row (at the bottom of vehicles list) -->
                            <tr x-show="expandedRows['{{ $index }}']" x-cloak class="border-b-2 border-indigo-200 dark:border-indigo-900/60 bg-indigo-50/30 dark:bg-slate-800/70 text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                <td class="py-2 px-3 sticky left-0 z-10 bg-indigo-50/80 dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 pl-8 uppercase tracking-wider text-[10px] text-slate-500 dark:text-slate-400 shadow-sm">
                                    ↳ Subtotal ({{ count($customer['vehicles'] ?? []) }} Units)
                                </td>
                                @foreach($reportData['month_keys'] as $mKey)
                                    <td class="py-2 px-2 text-center border-r border-slate-100 dark:border-slate-700/50 font-mono">
                                        {{ $customer['months'][$mKey]['qty'] > 0 ? $customer['months'][$mKey]['qty'] : '-' }}
                                    </td>
                                    <td class="py-2 px-2 text-right border-r border-slate-200 dark:border-slate-700 font-mono">
                                        {{ $customer['months'][$mKey]['value'] > 0 ? number_format($customer['months'][$mKey]['value']) : '-' }}
                                    </td>
                                @endforeach
                                <td class="py-2 px-2 text-center border-r border-slate-200 dark:border-slate-700 font-mono">
                                    {{ $customer['max_qty'] }}
                                </td>
                                <td class="py-2 px-2 text-right font-mono text-indigo-600 dark:text-indigo-400">
                                    Rp {{ number_format($customer['total_value']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td :colspan="{{ count($reportData['month_keys']) * 2 + 3 }}" class="py-12 text-center text-slate-400">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <p class="text-sm font-semibold">No rental contracts found for the selected period.</p>
                                    <p class="text-xs text-slate-500 mt-1">Try expanding the date range or clearing filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <!-- Table Sticky Footer Grand Totals -->
                    @if(!empty($reportData['customers']))
                        <tfoot class="sticky bottom-0 z-20 bg-slate-900 text-white font-extrabold text-[11px] border-t-2 border-slate-700 shadow-lg">
                            <tr>
                                <td class="py-3 px-3 sticky left-0 z-30 bg-slate-900 border-r border-slate-700 uppercase tracking-wider">
                                    Grand Total
                                </td>
                                @foreach($reportData['month_keys'] as $mKey)
                                    @php
                                        $mTot = $reportData['totals']['months'][$mKey] ?? ['qty' => 0, 'value' => 0];
                                    @endphp
                                    <td class="py-2 px-2 text-center border-r border-slate-700/80 bg-slate-900">
                                        {{ number_format($mTot['qty']) }}
                                    </td>
                                    <td class="py-2 px-2 text-right border-r border-slate-700 font-mono bg-slate-900">
                                        {{ number_format($mTot['value']) }}
                                    </td>
                                @endforeach
                                <td class="py-2 px-2 text-center border-r border-slate-700 bg-slate-900">-</td>
                                <td class="py-2 px-2 text-right font-mono font-black text-emerald-400 bg-slate-900">
                                    Rp {{ number_format($reportData['totals']['grand_total_value'] ?? 0) }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif

    <!-- Live Synchronization Progress Modal -->
    <div x-show="syncModal.show" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transition-all transform p-6 sm:p-7 relative space-y-6"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
             @click.outside="if (syncModal.status === 'completed' || syncModal.status === 'error') closeModal()">

            <!-- Modal Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 transition-colors"
                         :class="{
                             'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400': syncModal.status === 'running',
                             'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400': syncModal.status === 'completed',
                             'bg-rose-500/10 text-rose-600 dark:text-rose-400': syncModal.status === 'error'
                         }">
                        <!-- Running Spinner -->
                        <template x-if="syncModal.status === 'running'">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <!-- Completed Icon -->
                        <template x-if="syncModal.status === 'completed'">
                            <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </template>
                        <!-- Error Icon -->
                        <template x-if="syncModal.status === 'error'">
                            <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </template>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            <span x-text="syncModal.status === 'completed' ? 'Synchronization Complete' : (syncModal.status === 'error' ? 'Sync Encountered An Error' : 'Syncing with Odoo')"></span>
                            <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-md uppercase tracking-wider"
                                  :class="syncModal.type === 'fast' ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300' : 'bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300'"
                                  x-text="syncModal.type + ' sync'"></span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Fiscal Year <span class="font-bold text-slate-700 dark:text-slate-300" x-text="syncModal.year"></span> &bull; 
                            <span x-show="syncModal.status === 'running'">Elapsed: <span class="font-mono font-semibold" x-text="formatTime(syncModal.secondsElapsed)"></span></span>
                            <span x-show="syncModal.status === 'completed'">Took <span class="font-semibold" x-text="syncModal.secondsElapsed + 's'"></span></span>
                        </p>
                    </div>
                </div>

                <!-- Close Button (available when finished or error) -->
                <button type="button" 
                        x-show="syncModal.status === 'completed' || syncModal.status === 'error'"
                        @click="closeModal()" 
                        class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Progress Bar & Percentage -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs font-semibold">
                    <span class="text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full" 
                              :class="{
                                  'bg-indigo-500 animate-pulse': syncModal.status === 'running',
                                  'bg-emerald-500': syncModal.status === 'completed',
                                  'bg-rose-500': syncModal.status === 'error'
                              }"></span>
                        <span class="capitalize" x-text="syncModal.stage || 'Processing'"></span>
                    </span>
                    <span class="font-bold text-sm font-mono" 
                          :class="syncModal.status === 'completed' ? 'text-emerald-600 dark:text-emerald-400' : 'text-indigo-600 dark:text-indigo-400'"
                          x-text="syncModal.percent + '%'"></span>
                </div>

                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-3.5 overflow-hidden p-0.5 border border-slate-200 dark:border-slate-700/80 shadow-inner">
                    <div class="h-full rounded-full transition-all duration-300 ease-out"
                         :class="{
                             'bg-gradient-to-r from-indigo-500 via-indigo-600 to-emerald-500': syncModal.status === 'running',
                             'bg-emerald-500': syncModal.status === 'completed',
                             'bg-rose-500': syncModal.status === 'error'
                         }"
                         :style="'width: ' + Math.min(100, Math.max(5, syncModal.percent)) + '%'">
                    </div>
                </div>
            </div>

            <!-- Status Details Card -->
            <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-700/60 space-y-2">
                <div class="flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-xs text-slate-700 dark:text-slate-200 leading-relaxed font-medium break-words" x-text="syncModal.message || 'Processing records...'"></p>
                </div>

                <!-- Record counter badge if total is known -->
                <div x-show="syncModal.total > 0" class="pt-2 border-t border-slate-200/60 dark:border-slate-700/40 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Records processed:</span>
                    <span class="font-mono font-bold text-slate-700 dark:text-slate-200">
                        <span x-text="syncModal.records"></span> / <span x-text="syncModal.total"></span>
                    </span>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" 
                        x-show="syncModal.status === 'error'"
                        @click="closeModal()" 
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl transition-all cursor-pointer">
                    Dismiss
                </button>

                <button type="button" 
                        x-show="syncModal.status === 'completed'"
                        @click="reloadPage()" 
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-500/25 transition-all flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span>View Updated Report</span>
                </button>
            </div>

        </div>
    </div>

</div>

<script>
function summaryRentedVehiclePage() {
    return {
        expandedRows: {},
        syncModal: {
            show: false,
            year: {{ (int)$year }},
            type: 'fast',
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
            this.syncModal.type = type;
            this.syncModal.status = 'running';
            this.syncModal.percent = 6;
            this.syncModal.stage = 'Connecting';
            this.syncModal.message = 'Connecting to Odoo server...';
            this.syncModal.records = 0;
            this.syncModal.total = 0;
            this.syncModal.secondsElapsed = 0;
            this.syncModal.show = true;

            // Timer & Smooth Realistic Progress Milestones Animation
            if (this.syncModal.timerInterval) clearInterval(this.syncModal.timerInterval);
            this.syncModal.timerInterval = setInterval(() => {
                this.syncModal.secondsElapsed++;
                const s = this.syncModal.secondsElapsed;

                if (this.syncModal.status !== 'running') return;

                if (this.syncModal.type === 'fast') {
                    // Fast incremental sync estimation (completes in 1-2s)
                    if (s === 1) {
                        this.syncModal.percent = Math.max(this.syncModal.percent, 45);
                        this.syncModal.stage = 'Checking';
                        this.syncModal.message = 'Checking modified contracts and periods in Odoo...';
                    } else if (s === 2) {
                        this.syncModal.percent = Math.max(this.syncModal.percent, 85);
                        this.syncModal.stage = 'Updating';
                        this.syncModal.message = 'Synchronizing modified records...';
                    }
                } else {
                    // Full sync realistic progressive milestones (completes in ~35-40s)
                    if (s <= 3) {
                        const target = Math.min(15, 6 + s * 3);
                        this.syncModal.percent = Math.max(this.syncModal.percent, target);
                        this.syncModal.stage = 'Connecting';
                        this.syncModal.message = 'Connecting to Odoo and querying contracts count...';
                    } else if (s <= 14) {
                        const ratio = (s - 3) / (14 - 3);
                        const target = Math.round(15 + ratio * 30); // 15% -> 45%
                        this.syncModal.percent = Math.max(this.syncModal.percent, target);
                        this.syncModal.stage = 'Contracts';
                        const approxOrders = Math.round(ratio * 4033);
                        this.syncModal.records = Math.max(this.syncModal.records, approxOrders);
                        this.syncModal.total = 4033;
                        this.syncModal.message = `Fetching active contracts in 500-item batches (${this.syncModal.records.toLocaleString()} / 4,033)...`;
                    } else if (s <= 32) {
                        const ratio = (s - 14) / (32 - 14);
                        const target = Math.round(45 + ratio * 40); // 45% -> 85%
                        this.syncModal.percent = Math.max(this.syncModal.percent, target);
                        this.syncModal.stage = 'Periods';
                        const approxPeriods = Math.round(ratio * 36698);
                        this.syncModal.records = Math.max(this.syncModal.records, approxPeriods);
                        this.syncModal.total = 36698;
                        this.syncModal.message = `Fetching invoice periods for ${this.syncModal.year} (${this.syncModal.records.toLocaleString()} / 36,698)...`;
                    } else if (s <= 36) {
                        const ratio = (s - 32) / (36 - 32);
                        const target = Math.round(85 + ratio * 10); // 85% -> 95%
                        this.syncModal.percent = Math.max(this.syncModal.percent, target);
                        this.syncModal.stage = 'Partners';
                        this.syncModal.message = 'Resolving customer names and details...';
                    } else {
                        // Asymptotic crawl toward 98% while waiting for cache compile
                        if (this.syncModal.percent < 98) {
                            this.syncModal.percent += 1;
                        }
                        this.syncModal.stage = 'Finalizing';
                        this.syncModal.message = 'Compiling and saving master dataset in cache...';
                    }
                }
            }, 1000);

            // Background Polling (active if multi-worker web server)
            if (this.syncModal.pollInterval) clearInterval(this.syncModal.pollInterval);
            this.syncModal.pollInterval = setInterval(() => {
                this.pollProgress();
            }, 750);

            // Send trigger POST request
            fetch("{{ route('accounting.summary-rented-vehicle.sync') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    year: this.syncModal.year,
                    sync_type: this.syncModal.type
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'completed') {
                    this.syncModal.percent = 100;
                    this.syncModal.status = 'completed';
                    this.syncModal.stage = 'completed';
                    this.syncModal.records = data.total_orders || 4033;
                    this.syncModal.total = data.total_orders || 4033;
                    this.syncModal.message = data.message || 'Sync completed successfully!';
                    this.stopPolling();
                } else if (data.status === 'error') {
                    this.syncModal.status = 'error';
                    this.syncModal.message = data.message || 'An error occurred during synchronization.';
                    this.stopPolling();
                }
            })
            .catch(err => {
                console.error(err);
                this.syncModal.status = 'error';
                this.syncModal.message = err.message || 'Network connection failed.';
                this.stopPolling();
            });
        },
        pollProgress() {
            fetch("{{ route('accounting.summary-rented-vehicle.sync-progress') }}?year=" + this.syncModal.year, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.status) {
                    if (data.percent !== undefined && data.percent > this.syncModal.percent) {
                        this.syncModal.percent = data.percent;
                    }
                    if (data.stage) this.syncModal.stage = data.stage;
                    if (data.message) this.syncModal.message = data.message;
                    if (data.records !== undefined && data.records > this.syncModal.records) this.syncModal.records = data.records;
                    if (data.total !== undefined && data.total > this.syncModal.total) this.syncModal.total = data.total;

                    if (data.status === 'completed') {
                        this.syncModal.percent = 100;
                        this.syncModal.status = 'completed';
                        this.syncModal.stage = 'completed';
                        this.stopPolling();
                    } else if (data.status === 'error') {
                        this.syncModal.status = 'error';
                        this.stopPolling();
                    }
                }
            })
            .catch(e => console.warn('Progress poll tick failed:', e));
        },
        stopPolling() {
            if (this.syncModal.timerInterval) {
                clearInterval(this.syncModal.timerInterval);
                this.syncModal.timerInterval = null;
            }
            if (this.syncModal.pollInterval) {
                clearInterval(this.syncModal.pollInterval);
                this.syncModal.pollInterval = null;
            }
        },
        formatTime(seconds) {
            const m = Math.floor(seconds / 60).toString().padStart(2, '0');
            const s = (seconds % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        },
        reloadPage() {
            const url = new URL(window.location.href);
            url.searchParams.delete('sync_type');
            url.searchParams.set('year', this.syncModal.year);
            window.location.href = url.toString();
        },
        closeModal() {
            this.stopPolling();
            this.syncModal.show = false;
        }
    };
}

function monthPicker(name, initialValue) {
    const fullMonths = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    let now = new Date();
    let initYear = now.getFullYear();
    let initMonth = now.getMonth() + 1;
    
    if (initialValue && typeof initialValue === 'string' && initialValue.includes('-')) {
        const parts = initialValue.split('-');
        initYear = parseInt(parts[0], 10) || initYear;
        initMonth = parseInt(parts[1], 10) || initMonth;
    }

    return {
        open: false,
        name: name,
        value: initialValue || `${initYear}-${String(initMonth).padStart(2, '0')}`,
        year: initYear,
        selectedYear: initYear,
        selectedMonth: initMonth,
        monthNames: shortMonths,
        
        get displayText() {
            if (!this.value || !this.value.includes('-')) return this.value;
            const [y, m] = this.value.split('-');
            const mIdx = parseInt(m, 10) - 1;
            return (fullMonths[mIdx] || '') + ' ' + y;
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.year = this.selectedYear;
            }
        },
        isSelected(m) {
            return this.year === this.selectedYear && m === this.selectedMonth;
        },
        isCurrent(m) {
            const today = new Date();
            return this.year === today.getFullYear() && m === (today.getMonth() + 1);
        },
        selectMonth(m) {
            this.selectedYear = this.year;
            this.selectedMonth = m;
            this.value = `${this.year}-${String(m).padStart(2, '0')}`;
            this.open = false;
        },
        selectThisMonth() {
            const today = new Date();
            this.year = today.getFullYear();
            this.selectMonth(today.getMonth() + 1);
        }
    };
}
</script>
@endsection
