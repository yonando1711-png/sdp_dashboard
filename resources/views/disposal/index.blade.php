@extends('layouts.app')

@section('title', 'Disposal Fleet Lifecycle - SDP Dashboard')

@section('content')
<div class="w-full space-y-6" x-data="disposalApp()">

    <!-- Session Expired Alert -->
    @if(session('session_expired'))
    <div class="p-4 bg-amber-500/10 border border-amber-500/30 rounded-2xl flex items-center justify-between text-amber-600 dark:text-amber-400">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="text-sm font-semibold">Your session expired after 8 hours of inactivity. Please re-authenticate.</span>
        </div>
    </div>
    @endif

    @if(session('success'))
    <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-600 dark:text-emerald-400 text-sm font-semibold flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-600 dark:text-rose-400 text-sm font-semibold flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        {{ session('error') }}
    </div>
    @endif

    @if(!$authenticated)
    <!-- UNLOCK PAGE MODAL / PROMPT -->
    <div class="min-h-[60vh] flex items-center justify-center py-12">
        <div class="w-full max-w-md bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 rounded-3xl p-8 shadow-2xl dark:shadow-[0_0_50px_rgba(0,0,0,0.8)] space-y-6 text-center">
            
            <div class="w-16 h-16 rounded-3xl bg-gradient-to-br from-amber-500 via-rose-500 to-indigo-600 flex items-center justify-center mx-auto text-white shadow-lg shadow-rose-500/30">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>

            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Disposal Module Protected Access</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 font-medium">Enter secondary password to view fleet lifecycle & disposal due data</p>
            </div>

            <form action="{{ route('disposal.auth') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1 text-left">
                    <label class="text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Secondary Password</label>
                    <input type="password" name="password" required autofocus placeholder="Enter PIN / Password" class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-300 dark:border-slate-700/80 text-slate-900 dark:text-white text-sm font-semibold focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 focus:outline-none transition-all">
                </div>
                <button type="submit" class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-rose-600 via-indigo-600 to-cyan-600 hover:from-rose-700 hover:to-cyan-700 text-white font-extrabold text-sm shadow-lg shadow-rose-500/25 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    Unlock Disposal Module
                </button>
            </form>
        </div>
    </div>
    @else

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xl">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-rose-500 via-amber-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-rose-500/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Fleet Lifecycle & Disposal</h1>
                    <span class="px-2.5 py-0.5 text-[11px] font-bold rounded-full bg-rose-500/10 text-rose-500 border border-rose-500/20">5-Year Due Tracking</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Tracks vehicle initial dispatch, Sent As (ORIGINAL vs RBO), 5-year due date, and service age</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-3">
            <button @click="openSyncModal()" :disabled="isSyncing" class="group relative inline-flex items-center gap-2.5 px-5 py-2.5 rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-600 hover:from-indigo-500 hover:to-purple-500 disabled:opacity-50 text-white font-bold text-xs shadow-lg shadow-indigo-600/25 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                <div class="relative">
                    <svg class="w-4 h-4 text-white" :class="{ 'animate-spin': isSyncing }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    @if(($pendingSyncCount ?? 0) > 0)
                    <span class="absolute -top-1 -right-1 flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-400"></span>
                    </span>
                    @endif
                </div>
                <span x-text="isSyncing ? 'Auto-Syncing Odoo...' : 'Sync Odoo Data'">Sync Odoo Data</span>
                @if(($pendingSyncCount ?? 0) > 0)
                <span class="px-2 py-0.5 rounded-full bg-white/20 text-white text-[10px] font-extrabold tracking-wide">
                    {{ number_format($pendingSyncCount) }} pending
                </span>
                @else
                <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-200 text-[10px] font-extrabold">
                    All Synced ✓
                </span>
                @endif
            </button>

            @if(auth()->user()->canExportDisposal())
            <a href="{{ route('disposal.export', request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-lg shadow-emerald-600/25 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Export Excel</span>
            </a>
            @endif
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- Total Fleet -->
        <a href="{{ route('disposal.index', array_merge(request()->except(['status', 'page']), ['status' => 'all'])) }}" class="block p-4 rounded-3xl bg-white dark:bg-[#0d1322] border {{ $statusFilter === 'all' ? 'border-indigo-500 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-800' }} shadow-md hover:border-indigo-400 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Fleet</span>
                <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-2">{{ number_format($kpis['total'] ?? 0) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Physical fleet units</div>
        </a>

        <!-- Due for Disposal -->
        <a href="{{ route('disposal.index', array_merge(request()->except(['status', 'page']), ['status' => 'due'])) }}" class="block p-4 rounded-3xl bg-white dark:bg-[#0d1322] border {{ $statusFilter === 'due' ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-slate-200 dark:border-slate-800' }} shadow-md hover:border-rose-400 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-rose-500 uppercase tracking-wider">Due (≥5 Thn)</span>
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
            </div>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-2">{{ number_format($kpis['due'] ?? 0) }}</div>
            <div class="text-[10px] text-rose-500/80 mt-0.5">Disposal Due Date reached</div>
        </a>

        <!-- Approaching 5 Years -->
        <a href="{{ route('disposal.index', array_merge(request()->except(['status', 'page']), ['status' => 'approaching'])) }}" class="block p-4 rounded-3xl bg-white dark:bg-[#0d1322] border {{ $statusFilter === 'approaching' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200 dark:border-slate-800' }} shadow-md hover:border-amber-400 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-500 uppercase tracking-wider">Approaching</span>
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            </div>
            <div class="text-2xl font-black text-amber-500 mt-2">{{ number_format($kpis['approaching'] ?? 0) }}</div>
            <div class="text-[10px] text-amber-500/80 mt-0.5">Within 6 months of 5 yrs</div>
        </a>

        <!-- Active in Service -->
        <a href="{{ route('disposal.index', array_merge(request()->except(['status', 'page']), ['status' => 'active'])) }}" class="block p-4 rounded-3xl bg-white dark:bg-[#0d1322] border {{ $statusFilter === 'active' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200 dark:border-slate-800' }} shadow-md hover:border-emerald-400 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-500 uppercase tracking-wider">Active</span>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            </div>
            <div class="text-2xl font-black text-emerald-500 mt-2">{{ number_format($kpis['active'] ?? 0) }}</div>
            <div class="text-[10px] text-emerald-500/80 mt-0.5">Age &lt; 4.5 Years</div>
        </a>

        <!-- Disposed -->
        <a href="{{ route('disposal.index', array_merge(request()->except(['status', 'page']), ['status' => 'disposed'])) }}" class="block p-4 rounded-3xl bg-white dark:bg-[#0d1322] border {{ $statusFilter === 'disposed' ? 'border-slate-400 ring-2 ring-slate-400/20' : 'border-slate-200 dark:border-slate-800' }} shadow-md hover:border-slate-400 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Disposed / Sold</span>
                <span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span>
            </div>
            <div class="text-2xl font-black text-slate-400 mt-2">{{ number_format($kpis['disposed'] ?? 0) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Sold or Disposed location</div>
        </a>

        <!-- Never Rented / In Stock -->
        <a href="{{ route('disposal.index', array_merge(request()->except(['status', 'page']), ['status' => 'never_rented'])) }}" class="block p-4 rounded-3xl bg-white dark:bg-[#0d1322] border {{ $statusFilter === 'never_rented' ? 'border-cyan-500 ring-2 ring-cyan-500/20' : 'border-slate-200 dark:border-slate-800' }} shadow-md hover:border-cyan-400 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-cyan-500 uppercase tracking-wider">Never Rented</span>
                <span class="w-2.5 h-2.5 rounded-full bg-cyan-400"></span>
            </div>
            <div class="text-2xl font-black text-cyan-500 mt-2">{{ number_format($kpis['never_rented'] ?? 0) }}</div>
            <div class="text-[10px] text-cyan-500/80 mt-0.5">No rental dispatch history</div>
        </a>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 p-5 rounded-3xl shadow-lg">
        <form action="{{ route('disposal.index') }}" method="GET" class="flex flex-col lg:flex-row items-center gap-4">
            <!-- Search input -->
            <div class="relative flex-1 w-full">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search No. Polisi, Model, SO, Customer..." class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-300 dark:border-slate-700/80 text-slate-900 dark:text-white text-xs font-semibold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-all">
            </div>

            <!-- Sent As Filter Dropdown -->
            <div class="w-full lg:w-52">
                <select name="sent_as" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-300 dark:border-slate-700/80 text-slate-900 dark:text-white text-xs font-semibold focus:border-indigo-500 focus:outline-none transition-all">
                    <option value="all" {{ $sentAsFilter === 'all' ? 'selected' : '' }}>Sent As (1st SO): All</option>
                    <option value="ORIGINAL" {{ $sentAsFilter === 'ORIGINAL' ? 'selected' : '' }}>Sent As: ORIGINAL</option>
                    <option value="RBO" {{ $sentAsFilter === 'RBO' ? 'selected' : '' }}>Sent As: RBO</option>
                </select>
            </div>

            <!-- Status Filter Dropdown -->
            <div class="w-full lg:w-52">
                <select name="status" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-300 dark:border-slate-700/80 text-slate-900 dark:text-white text-xs font-semibold focus:border-indigo-500 focus:outline-none transition-all">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Status: All Status</option>
                    <option value="due" {{ $statusFilter === 'due' ? 'selected' : '' }}>Status: Due (≥ 5 Thn)</option>
                    <option value="approaching" {{ $statusFilter === 'approaching' ? 'selected' : '' }}>Status: Approaching (&le; 6 Bln)</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Status: Active (&lt; 4.5 Thn)</option>
                    <option value="disposed" {{ $statusFilter === 'disposed' ? 'selected' : '' }}>Status: Disposed / Sold</option>
                    <option value="never_rented" {{ $statusFilter === 'never_rented' ? 'selected' : '' }}>Status: Never Rented</option>
                </select>
            </div>

            <!-- Branch filter for nationwide -->
            @if(auth()->user()->isNationwide())
            <div class="w-full lg:w-44">
                <select name="branch" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-300 dark:border-slate-700/80 text-slate-900 dark:text-white text-xs font-semibold focus:border-indigo-500 focus:outline-none transition-all">
                    <option value="ALL" {{ ($branchFilter === 'ALL' || empty($branchFilter)) ? 'selected' : '' }}>Branch: Nationwide</option>
                    <option value="JKT" {{ $branchFilter === 'JKT' ? 'selected' : '' }}>Branch: Jakarta</option>
                    <option value="SUB" {{ $branchFilter === 'SUB' ? 'selected' : '' }}>Branch: Surabaya</option>
                    <option value="SMG" {{ $branchFilter === 'SMG' ? 'selected' : '' }}>Branch: Semarang</option>
                    <option value="DPS" {{ $branchFilter === 'DPS' ? 'selected' : '' }}>Branch: Denpasar</option>
                    <option value="BPN" {{ $branchFilter === 'BPN' ? 'selected' : '' }}>Branch: Balikpapan</option>
                    <option value="BDG" {{ $branchFilter === 'BDG' ? 'selected' : '' }}>Branch: Bandung</option>
                    <option value="MDN" {{ $branchFilter === 'MDN' ? 'selected' : '' }}>Branch: Medan</option>
                    <option value="MKS" {{ $branchFilter === 'MKS' ? 'selected' : '' }}>Branch: Makassar</option>
                    <option value="BTM" {{ $branchFilter === 'BTM' ? 'selected' : '' }}>Branch: Batam</option>
                    <option value="MNI" {{ $branchFilter === 'MNI' ? 'selected' : '' }}>Branch: Manado</option>
                </select>
            </div>
            @endif

            <div class="flex items-center gap-2 w-full lg:w-auto">
                <button type="submit" class="px-5 py-2.5 rounded-2xl bg-slate-900 dark:bg-slate-700 hover:bg-black text-white text-xs font-bold transition-all">
                    Filter
                </button>
                <a href="{{ route('disposal.index') }}" class="px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold transition-all">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs min-w-[1150px]">
                <thead class="bg-slate-50 dark:bg-[#050913] border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-extrabold text-[11px]">
                    <tr>
                        <th class="py-4 px-5 whitespace-nowrap">No. Polisi / Unit</th>
                        <th class="py-4 px-4 whitespace-nowrap">First Rental SO</th>
                        <th class="py-4 px-4 text-center whitespace-nowrap" title="Vehicle role upon initial dispatch (ORIGINAL vs RBO)">Sent As</th>
                        <th class="py-4 px-4 whitespace-nowrap">First Start Sewa</th>
                        <th class="py-4 px-4 whitespace-nowrap">Disposal Due Date</th>
                        <th class="py-4 px-4 text-center whitespace-nowrap">Service Age</th>
                        <th class="py-4 px-4 text-center whitespace-nowrap">Lifecycle Status</th>
                        <th class="py-4 px-5 whitespace-nowrap">Current Location & Customer</th>
                        <th class="py-4 px-4 text-center whitespace-nowrap">Sync</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($items as $item)
                    @php
                        $status = $item->disposal_status;
                        $sentBadge = $item->first_sent_as_badge;
                    @endphp
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                        <!-- Unit Info -->
                        <td class="py-4 px-5">
                            <div class="font-extrabold text-slate-900 dark:text-white">{{ $item->lot_number }}</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5">{{ $item->product }}</div>
                            @if($item->warehouse)
                            <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-bold rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">{{ $item->warehouse }}</span>
                            @endif
                        </td>

                        <!-- First Rental SO -->
                        <td class="py-4 px-4 whitespace-nowrap">
                            @if($item->first_rental_id)
                            <div class="font-bold text-indigo-600 dark:text-indigo-400">{{ $item->first_rental_id }}</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5">{{ $item->first_customer_name ?? '-' }}</div>
                            @else
                            <span class="text-slate-400 italic">No rental record</span>
                            @endif
                        </td>

                        <!-- Sent As -->
                        <td class="py-4 px-4 text-center whitespace-nowrap">
                            @if($item->first_sent_as === 'ORIGINAL')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-blue-500/10 text-blue-500 border border-blue-500/20">
                                ORIGINAL
                            </span>
                            @elseif($item->first_sent_as === 'RBO')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                RBO
                            </span>
                            @else
                            <span class="text-slate-400 text-xs">-</span>
                            @endif
                        </td>

                        <!-- First Start Sewa -->
                        <td class="py-4 px-4 whitespace-nowrap">
                            @if($item->first_start_sewa_date)
                            <div class="font-bold text-slate-900 dark:text-white">{{ $item->first_start_sewa_date->format('d M Y') }}</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ $item->first_start_sewa_date->diffForHumans() }}</div>
                            @else
                            <span class="text-slate-400 italic">-</span>
                            @endif
                        </td>

                        <!-- Disposal Due Date (+5 Years) -->
                        <td class="py-4 px-4 whitespace-nowrap">
                            @if($item->disposal_due_date)
                            <div class="font-bold {{ $status === 'due' ? 'text-rose-600 dark:text-rose-400' : ($status === 'approaching' ? 'text-amber-500' : 'text-slate-900 dark:text-white') }}">
                                {{ $item->disposal_due_date->format('d M Y') }}
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                @if($status === 'due')
                                <span class="font-semibold text-rose-500">Overdue (5+ yrs)</span>
                                @elseif($status === 'approaching')
                                <span class="font-semibold text-amber-500">&le; 6 mos left</span>
                                @else
                                <span>5 yrs from start</span>
                                @endif
                            </div>
                            @else
                            <span class="text-slate-400 italic">-</span>
                            @endif
                        </td>

                        <!-- Service Age -->
                        <td class="py-4 px-4 text-center whitespace-nowrap">
                            <span class="font-bold text-slate-900 dark:text-white">{{ $item->service_age_string }}</span>
                        </td>

                        <!-- Status Badge -->
                        <td class="py-4 px-4 text-center whitespace-nowrap">
                            @if($status === 'due')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-rose-500/10 text-rose-500 border border-rose-500/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                DUE (≥5 THN)
                            </span>
                            @elseif($status === 'approaching')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-amber-500/10 text-amber-500 border border-amber-500/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                APPROACHING (&le;6 BLN)
                            </span>
                            @elseif($status === 'active')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-emerald-500/10 text-emerald-500 border border-emerald-500/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                ACTIVE
                            </span>
                            @elseif($status === 'disposed')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-500/10 text-slate-400 border border-slate-500/30">
                                DISPOSED / SOLD
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/30">
                                NEVER RENTED
                            </span>
                            @endif
                        </td>

                        <!-- Current Location & Customer -->
                        <td class="py-4 px-5">
                            <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $item->location ?? '-' }}</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5">{{ $item->current_customer ?? ($item->rental_id ?? '-') }}</div>
                        </td>

                        <!-- Quick Single Unit Sync -->
                        <td class="py-4 px-4 text-center whitespace-nowrap">
                            <button @click="syncSingleUnit('{{ $item->lot_number }}')" :disabled="syncingUnits['{{ $item->lot_number }}']" class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all inline-flex items-center justify-center disabled:opacity-50" title="Sync this vehicle with Odoo">
                                <svg class="w-4 h-4" :class="{ 'animate-spin text-indigo-500': syncingUnits['{{ $item->lot_number }}'] }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-slate-400">
                            <svg class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="font-semibold text-sm">No vehicles found matching the specified filters.</p>
                            <a href="{{ route('disposal.index') }}" class="text-xs text-indigo-500 hover:underline mt-2 inline-block font-semibold">Clear filters</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $items->links() }}
        </div>
        @endif
    </div>

    <!-- Continuous Sync Progress Modal -->
    <div x-show="showSyncModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm" x-transition>
        <div @click.away="if (!isSyncing) showSyncModal = false" class="w-full max-w-xl bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-2xl space-y-5 text-left">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center shadow-md shadow-indigo-500/20">
                        <svg class="w-5 h-5" :class="{ 'animate-spin': isSyncing }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Continuous Odoo Fleet Sync</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Safely syncs initial rental SO & Sent As role without browser timeouts</p>
                    </div>
                </div>
                <button x-show="!isSyncing" @click="showSyncModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Configuration Options (Visible before starting or when paused) -->
            <div x-show="!isSyncing && !syncComplete" class="p-4 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-200 dark:border-slate-800/80 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Batch Size per Request</label>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">100 vehicles per batch balances speed and server response time.</p>
                    </div>
                    <select x-model="batchSize" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-white dark:bg-[#0d1322] border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        <option value="50">50 units / batch (Faster requests)</option>
                        <option value="100">100 units / batch (Recommended)</option>
                        <option value="150">150 units / batch (High throughput)</option>
                    </select>
                </div>

                <div class="pt-2 border-t border-slate-200 dark:border-slate-800/60 flex items-center gap-2">
                    <input type="checkbox" id="forceModeCheck" x-model="forceMode" @change="onToggleForceMode()" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500/20">
                    <label for="forceModeCheck" class="text-xs text-slate-600 dark:text-slate-400 font-medium cursor-pointer">
                        Force re-evaluate all vehicles (including previously synced units)
                    </label>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-center">
                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-200 dark:border-slate-800">
                    <div class="text-[10px] font-bold text-slate-400 uppercase">Processed</div>
                    <div class="text-lg font-black text-slate-900 dark:text-white mt-1" x-text="stats.processed">0</div>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-200 dark:border-slate-800">
                    <div class="text-[10px] font-bold text-emerald-500 uppercase">Rented</div>
                    <div class="text-lg font-black text-emerald-500 mt-1" x-text="stats.rentedFound">0</div>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-200 dark:border-slate-800">
                    <div class="text-[10px] font-bold text-cyan-500 uppercase">In Stock</div>
                    <div class="text-lg font-black text-cyan-500 mt-1" x-text="stats.neverRented">0</div>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-200 dark:border-slate-800">
                    <div class="text-[10px] font-bold text-amber-500 uppercase">Remaining</div>
                    <div class="text-lg font-black text-amber-500 mt-1" x-text="stats.remaining">{{ number_format($pendingSyncCount ?? 0) }}</div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="space-y-2">
                <div class="flex justify-between text-xs font-semibold text-slate-600 dark:text-slate-400">
                    <span class="truncate max-w-[80%]" x-text="statusMessage">Ready to start</span>
                    <span class="font-mono font-bold" x-text="progressPercent + '%'">0%</span>
                </div>
                <div class="w-full h-3.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden p-0.5 border border-slate-200 dark:border-slate-700/60">
                    <div class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-emerald-500 transition-all duration-300 rounded-full" :style="'width: ' + progressPercent + '%'"></div>
                </div>
            </div>

            <!-- Live Activity Log -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    <span>Live Activity Console</span>
                    <span x-text="logs.length + ' events'">0 events</span>
                </div>
                <div class="h-28 overflow-y-auto p-3 rounded-2xl bg-slate-900 text-slate-200 font-mono text-[11px] space-y-1 select-text border border-slate-800 shadow-inner">
                    <template x-if="logs.length === 0">
                        <div class="text-slate-500 italic">No batches run yet. Click "Start Continuous Auto-Sync" below.</div>
                    </template>
                    <template x-for="(log, idx) in logs" :key="idx">
                        <div class="flex items-start gap-2">
                            <span class="text-slate-500 flex-shrink-0" x-text="'[' + log.time + ']'"></span>
                            <span :class="log.isError ? 'text-rose-400 font-bold' : (log.isComplete ? 'text-emerald-400 font-bold' : 'text-slate-300')" x-text="log.msg"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Controls -->
            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                <template x-if="!isSyncing && !syncComplete">
                    <button @click="startContinuousSync()" class="px-5 py-2.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/25 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                        <span x-text="stats.processed > 0 ? 'Resume Auto-Sync' : 'Start Continuous Auto-Sync'"></span>
                    </button>
                </template>

                <template x-if="isSyncing">
                    <button @click="stopSync()" class="px-5 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs shadow-lg shadow-amber-500/25 transition-all">
                        Pause After Current Batch
                    </button>
                </template>

                <template x-if="syncComplete || (!isSyncing && stats.processed > 0)">
                    <button @click="finishAndReload()" class="px-5 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-lg shadow-emerald-600/25 transition-all">
                        Done & Refresh Page
                    </button>
                </template>

                <button x-show="!isSyncing" @click="showSyncModal = false" class="px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-semibold text-xs transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>

    @endif

</div>

<script>
function disposalApp() {
    return {
        showSyncModal: false,
        isSyncing: false,
        shouldStop: false,
        syncComplete: false,
        batchSize: 100,
        forceMode: false,
        totalFleetCount: {{ (int) ($kpis['total'] ?? 0) }},
        pendingCount: {{ (int) ($pendingSyncCount ?? 0) }},
        totalInitial: {{ (int) ($pendingSyncCount ?? 0) }},
        statusMessage: 'Ready to start continuous synchronization.',
        logs: [],
        syncingUnits: {},
        stats: {
            processed: 0,
            rentedFound: 0,
            neverRented: 0,
            remaining: {{ (int) ($pendingSyncCount ?? 0) }},
        },
        onToggleForceMode() {
            if (this.forceMode) {
                this.totalInitial = this.totalFleetCount;
                this.stats.remaining = this.totalFleetCount;
            } else {
                this.totalInitial = this.pendingCount;
                this.stats.remaining = this.pendingCount;
            }
        },
        get progressPercent() {
            if (!this.totalInitial || this.totalInitial === 0) return 100;
            const pct = Math.min(100, Math.round((this.stats.processed / this.totalInitial) * 100));
            return isNaN(pct) ? 0 : pct;
        },
        openSyncModal() {
            this.showSyncModal = true;
            this.onToggleForceMode();
        },
        addLog(msg, isError = false, isComplete = false) {
            const time = new Date().toLocaleTimeString('en-GB');
            this.logs.unshift({ time, msg, isError, isComplete });
            if (this.logs.length > 50) this.logs.pop();
        },
        async startContinuousSync() {
            this.isSyncing = true;
            this.shouldStop = false;
            this.syncComplete = false;
            let batch = 0;
            let currentOffset = 0;

            if (this.forceMode) {
                this.totalInitial = this.totalFleetCount;
                this.stats.remaining = this.totalFleetCount;
            }

            this.addLog(`Started auto-sync with batch size ${this.batchSize}` + (this.forceMode ? ' (Force Mode - Entire Fleet)' : ''));

            while (!this.shouldStop) {
                batch++;
                this.statusMessage = `Processing batch #${batch} (${this.batchSize} vehicles)...`;

                try {
                    const response = await fetch("{{ route('disposal.sync') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            batch_size: parseInt(this.batchSize),
                            force: this.forceMode,
                            offset: this.forceMode ? currentOffset : 0
                        })
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        const errMsg = data.error || 'Server error';
                        this.statusMessage = 'Batch failed: ' + errMsg;
                        this.addLog(`Batch #${batch} error: ${errMsg}`, true);
                        break;
                    }

                    if (batch === 1 && data.total_pending) {
                        this.totalInitial = data.total_pending;
                    }

                    const rentedInBatch = data.rented_in_batch || 0;
                    const batchUpdated = data.updated || 0;
                    const neverRentedInBatch = Math.max(0, batchUpdated - rentedInBatch);

                    this.stats.processed += batchUpdated;
                    this.stats.rentedFound += rentedInBatch;
                    this.stats.neverRented += neverRentedInBatch;
                    this.stats.remaining = data.remaining;
                    if (this.forceMode) {
                        currentOffset = data.next_offset || (currentOffset + batchUpdated);
                    }

                    this.addLog(`Batch #${batch} finished: ${batchUpdated} evaluated (${rentedInBatch} rented, ${neverRentedInBatch} in stock). ${data.remaining} remaining.`);

                    if (data.done || data.remaining === 0 || batchUpdated === 0) {
                        this.syncComplete = true;
                        this.statusMessage = `Completed! All fleet vehicles successfully synchronized.`;
                        this.addLog(`Synchronization complete! Total units processed: ${this.stats.processed}`, false, true);
                        break;
                    }

                    this.statusMessage = `Batch #${batch} finished. Continuing...`;
                } catch (err) {
                    this.statusMessage = 'Network error: ' + err.message;
                    this.addLog(`Network error: ${err.message}`, true);
                    break;
                }
            }

            this.isSyncing = false;
            if (this.shouldStop) {
                this.statusMessage = `Synchronization paused. Processed ${this.stats.processed} vehicles.`;
                this.addLog(`Sync paused by user. Processed ${this.stats.processed} vehicles.`);
            }
        },
        stopSync() {
            this.shouldStop = true;
            this.statusMessage = 'Stopping after current batch completes...';
            this.addLog('Pause requested. Finishing active batch...');
        },
        finishAndReload() {
            window.location.reload();
        },
        async syncSingleUnit(lotNumber) {
            if (this.syncingUnits[lotNumber]) return;
            this.syncingUnits[lotNumber] = true;

            try {
                const response = await fetch("{{ route('disposal.sync') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ lot: lotNumber, force: true })
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    window.location.reload();
                } else {
                    alert('Sync failed for ' + lotNumber + ': ' + (data.error || 'Unknown error'));
                }
            } catch (err) {
                alert('Error syncing ' + lotNumber + ': ' + err.message);
            } finally {
                this.syncingUnits[lotNumber] = false;
            }
        }
    };
}
</script>

@if($authenticated)
<script>
// 15-Minute Auto-Prompt Inactivity Watchdog (matches Surat Kuasa & LoR security standards)
(function() {
    const INACTIVITY_TIMEOUT = 15 * 60 * 1000; // 15 minutes
    let timeoutId;

    function resetTimer() {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(function() {
            // When 15 minutes of inactivity passes, reload to trigger password prompt
            window.location.reload();
        }, INACTIVITY_TIMEOUT);
    }

    const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
    events.forEach(function(evt) {
        window.addEventListener(evt, resetTimer, { passive: true });
    });

    resetTimer();
})();
</script>
@endif
@endsection
