@extends('layouts.app')

@section('title', 'Surat Kuasa - SDP Dashboard')

@section('content')
<div class="w-full space-y-6" x-data="suratKuasaApp()">

    <!-- Session Expired Alert -->
    @if(session('session_expired'))
    <div class="p-4 bg-amber-500/10 border border-amber-500/30 rounded-2xl flex items-center justify-between text-amber-600 dark:text-amber-400">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="text-sm font-semibold">Your session expired after 15 minutes of inactivity. Please re-authenticate.</span>
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
            
            <div class="w-16 h-16 rounded-3xl bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center mx-auto text-white shadow-lg shadow-indigo-500/30">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>

            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Surat Kuasa Protected Access</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 font-medium">Enter secondary password to access Surat Kuasa generator</p>
            </div>

            <form action="{{ route('surat-kuasa.auth') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1 text-left">
                    <label class="text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Secondary Password</label>
                    <input type="password" name="password" required autofocus placeholder="Enter PIN / Password" class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-300 dark:border-slate-700/80 text-slate-900 dark:text-white text-sm font-semibold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-all">
                </div>
                <button type="submit" class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-700 hover:to-cyan-700 text-white font-extrabold text-sm shadow-lg shadow-indigo-500/25 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    Unlock Surat Kuasa
                </button>
            </form>
        </div>
    </div>
    @else

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xl">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <div>
                <h1 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Surat Kuasa Vehicle Generator</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Select vehicle unit (Qty = 0, Excl. Vendor Rent) to generate Surat Kuasa</p>
            </div>
        </div>

        <!-- Action Controls & Search -->
        <div class="flex flex-wrap items-center gap-3">
            <!-- Notification Bell Icon & Recent Sync Updates Dropdown -->
            <div class="relative" x-data="{
                showNotifications: false,
                readUpdates: JSON.parse(localStorage.getItem('readSkUpdates') || '[]'),
                get unreadCount() {
                    return notificationsList.filter(u => !this.readUpdates.includes(u.key)).length;
                },
                markAsRead(key) {
                    if (!this.readUpdates.includes(key)) {
                        this.readUpdates.push(key);
                        localStorage.setItem('readSkUpdates', JSON.stringify(this.readUpdates));
                    }
                },
                markAllAsRead() {
                    notificationsList.forEach(u => {
                        if (!this.readUpdates.includes(u.key)) {
                            this.readUpdates.push(u.key);
                        }
                    });
                    localStorage.setItem('readSkUpdates', JSON.stringify(this.readUpdates));
                }
            }" @click.away="showNotifications = false">
                
                <button @click="showNotifications = !showNotifications" type="button" class="relative inline-flex items-center justify-center p-2.5 text-slate-400 hover:text-indigo-400 bg-slate-50 dark:bg-[#050913] border border-slate-200 dark:border-slate-800 rounded-2xl transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20" title="Recent Surat Kuasa Sync Updates">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    <span class="absolute top-0 right-0 -mt-1 -mr-1 flex h-4 w-4" x-show="unreadCount > 0" style="display: none;">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-indigo-600 text-[10px] text-white font-extrabold items-center justify-center" x-text="unreadCount"></span>
                    </span>
                </button>

                <div x-show="showNotifications" x-transition style="display: none;" class="absolute right-0 sm:right-auto sm:left-0 mt-3 w-80 md:w-96 bg-white dark:bg-[#0d1322] rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden text-left z-50">
                    <div class="p-4 bg-slate-50 dark:bg-[#050913] border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="font-extrabold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                Recent Sync Updates
                            </h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">Changes detected in the last sync.</p>
                        </div>
                        <button @click="markAllAsRead()" x-show="unreadCount > 0" type="button" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-bold transition-colors">Mark all as read</button>
                    </div>

                    <div class="max-h-80 overflow-y-auto p-2 space-y-1">
                        <template x-for="item in notificationsList" :key="item.key">
                            <div @mouseenter="markAsRead(item.key)" 
                                 class="p-3 rounded-2xl transition-all border-l-4 cursor-default text-xs"
                                 :class="readUpdates.includes(item.key) ? 'bg-slate-50/50 dark:bg-slate-800/20 border-slate-300 dark:border-slate-700 opacity-80' : 'bg-indigo-50/70 dark:bg-indigo-950/40 border-indigo-500 shadow-sm'">
                                <div class="flex items-center justify-between font-bold text-slate-900 dark:text-slate-100 font-mono">
                                    <span x-text="item.lot_number"></span>
                                    <span :class="item.is_ready ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-amber-500/10 text-amber-500 border-amber-500/20'" class="px-2 py-0.5 rounded-lg text-[10px] font-extrabold border" x-text="item.status_label"></span>
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium" x-text="item.product"></div>
                                <div class="mt-1.5 space-y-0.5">
                                    <template x-for="change in item.changes">
                                        <div class="text-[11px] text-slate-700 dark:text-slate-300 font-medium flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 inline-block"></span>
                                            <span x-text="change"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="notificationsList.length === 0">
                            <div class="text-center py-8 px-4">
                                <svg class="w-8 h-8 mx-auto text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <p class="text-xs font-bold text-slate-700 dark:text-slate-300">No new changes detected.</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Everything is up to date!</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('surat-kuasa.index') }}" class="flex items-center gap-2">
                <div class="relative min-w-[240px]">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search Lot, Product, Frame/Engine..." class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-200 dark:border-slate-800 text-xs font-medium text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <button type="submit" class="px-4 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-colors">Search</button>
                @if($search)
                    <a href="{{ route('surat-kuasa.index') }}" class="px-3 py-2.5 rounded-2xl bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold hover:bg-slate-300">Clear</a>
                @endif
            </form>

            <!-- Button 1: Sync Odoo Data (Fetch missing units from Odoo) -->
            <button type="button" @click="syncOdooData()" :disabled="isSyncing" class="px-4 py-2.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md transition-all flex items-center gap-2">
                <svg x-show="!isSyncing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <svg x-show="isSyncing" class="w-4 h-4 animate-spin" style="display:none;" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span x-text="isSyncing ? 'Syncing...' : 'Sync Odoo Data'">Sync Odoo Data</span>
            </button>

            <!-- Button 2: Fast Sync Odoo (Detect updates in No Rangka & No Mesin) -->
            <button type="button" @click="fastSync()" :disabled="isSyncing" class="px-4 py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white text-xs font-bold shadow-md transition-all flex items-center gap-2">
                <svg x-show="!isSyncing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <svg x-show="isSyncing" class="w-4 h-4 animate-spin" style="display:none;" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span x-text="isSyncing ? 'Syncing...' : 'Fast Sync Odoo'">Fast Sync Odoo</span>
            </button>

            <!-- Button 3: Test Email -->
            <button type="button" @click="testEmail()" :disabled="isTestingEmail" class="px-4 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-md transition-all flex items-center gap-2" title="Test SMTP Email Settings">
                <svg x-show="!isTestingEmail" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                <svg x-show="isTestingEmail" class="w-4 h-4 animate-spin" style="display:none;" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span x-text="isTestingEmail ? 'Testing...' : 'Test Email'">Test Email</span>
            </button>
        </div>
    </div>

    <!-- Table List -->
    <div class="bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-[#050913] border-b border-slate-200 dark:border-slate-800 text-slate-500 uppercase tracking-wider font-extrabold text-[10px]">
                    <tr>
                        <th class="py-4 px-5">#</th>
                        <th class="py-4 px-5">LOT SERIAL / UNIT</th>
                        <th class="py-4 px-5">MERK / TYPE (PRODUCT)</th>
                        <th class="py-4 px-5">NO. RANGKA</th>
                        <th class="py-4 px-5">NO. MESIN</th>
                        <th class="py-4 px-5">WARNA & TAHUN</th>
                        <th class="py-4 px-5">CUSTOMER / LOCATION</th>
                        <th class="py-4 px-5 text-center">STATUS</th>
                        <th class="py-4 px-5 text-right">ACTION</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                    @php
                        $isItAdmin = auth()->check() && auth()->user()->isItAdmin();
                    @endphp
                    @forelse($items as $index => $item)
                        @php
                            $noRangka = trim((string)$item->internal_reference);
                            $noMesin = trim((string)$item->engine_number);
                            $isReadyToPrint = !empty($noRangka) && !empty($noMesin);
                            $canGenerate = $isReadyToPrint || $isItAdmin;
                            $hasGeneratedLog = in_array($item->id, $generatedItemIds);
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-5 text-slate-400 font-mono">{{ $items->firstItem() + $index }}</td>
                            <td class="py-4 px-5 font-bold text-slate-900 dark:text-white font-mono">
                                {{ $item->lot_number }}
                            </td>
                            <td class="py-4 px-5">
                                <div class="font-semibold text-slate-900 dark:text-slate-100 max-w-xs truncate">{{ $item->product }}</div>
                                <div class="text-[10px] text-slate-400">Rental: {{ $item->rental_id ?: '-' }}</div>
                            </td>
                            <td class="py-4 px-5">
                                @if(!empty($noRangka))
                                    <span class="font-mono font-semibold text-slate-800 dark:text-slate-200">{{ $noRangka }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-500/10 text-amber-500 text-[11px] font-bold border border-amber-500/20">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        Empty in Odoo
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-5">
                                @if(!empty($noMesin))
                                    <span class="font-mono font-semibold text-slate-800 dark:text-slate-200">{{ $noMesin }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-500/10 text-amber-500 text-[11px] font-bold border border-amber-500/20">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        Empty in Odoo
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-5">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 font-mono font-bold text-slate-700 dark:text-slate-300 text-[10px]">
                                        {{ $item->year ?: date('Y') }}
                                    </span>
                                    <span class="text-slate-700 dark:text-slate-300 font-medium">
                                        {{ $item->color ?: 'Putih' }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-4 px-5 text-slate-700 dark:text-slate-300">
                                {{ $item->current_customer ?: '-' }}
                            </td>
                            <td class="py-4 px-5 text-center">
                                @if($isReadyToPrint)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-500/10 text-emerald-500 font-bold border border-emerald-500/20" title="Ready to print">
                                        ✓
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-rose-500/10 text-rose-500 font-bold border border-rose-500/20" title="Chassis or Engine number empty in Odoo">
                                        ✕
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-5 align-middle text-right whitespace-nowrap">
                                @if($hasGeneratedLog && !$isItAdmin)
                                    <!-- Standard User Lock: SK already generated -->
                                    <button type="button" disabled class="px-4 py-2 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 text-xs font-bold border border-purple-500/30 cursor-not-allowed inline-flex items-center gap-1.5 ml-auto opacity-80" title="Surat Kuasa already generated. Standard users cannot regenerate.">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v4a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                        SK Generated (Locked)
                                    </button>
                                @elseif($canGenerate)
                                    <button type="button" @click="openGeneratorModal({{ json_encode([
                                        'id' => $item->id,
                                        'product' => $item->product,
                                        'noRangka' => !empty($noRangka) ? $noRangka : '[EMPTY - IT ADMIN TEST]',
                                        'noMesin' => !empty($noMesin) ? $noMesin : '[EMPTY - IT ADMIN TEST]',
                                        'color' => $item->color ?: 'Putih',
                                        'year' => $item->year ?: date('Y'),
                                        'customer' => $item->current_customer
                                    ]) }})" class="px-4 py-2 rounded-xl bg-gradient-to-r from-indigo-500 to-cyan-500 hover:from-indigo-600 hover:to-cyan-600 text-white text-xs font-extrabold shadow-md shadow-indigo-500/20 transition-all hover:scale-105 active:scale-95 inline-flex items-center gap-1.5 ml-auto">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        @if($hasGeneratedLog)
                                            Regenerate SK (IT Admin)
                                        @else
                                            Generate Surat Kuasa
                                        @endif
                                        @if(!$isReadyToPrint && $isItAdmin)
                                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-amber-400 text-slate-900 font-extrabold ml-1 uppercase">IT Admin Test</span>
                                        @endif
                                    </button>
                                @else
                                    <button type="button" disabled class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800/50 text-slate-400 dark:text-slate-500 text-xs font-bold border border-slate-200 dark:border-slate-800/80 cursor-not-allowed inline-flex items-center gap-1.5 ml-auto opacity-70" title="Print disabled until both No Rangka and No Mesin are populated in Odoo">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v4a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                        Action Disabled
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p class="text-sm font-bold">No vehicles matching Surat Kuasa criteria (qty = 0)</p>
                                <p class="text-xs text-slate-500 mt-1">Try running Fast Sync Odoo or clear search filter.</p>
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

    <!-- STEP 1: GENERATE SURAT KUASA PARAMETERS MODAL -->
    <div x-show="showGeneratorModal" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/90 backdrop-blur-md" x-transition>
        <div @click.away="showGeneratorModal = false" class="bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-700/80 rounded-3xl p-7 w-full max-w-2xl shadow-2xl space-y-6 relative text-slate-900 dark:text-slate-100">
            
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800/80 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500/20 to-cyan-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">Generate Surat Kuasa Document</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Verify parameters and input Penerima Kuasa details before previewing</p>
                    </div>
                </div>
                <button type="button" @click="showGeneratorModal = false" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Form -->
            <div class="space-y-4">
                
                <!-- Document Number -->
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Nomor Surat Kuasa</label>
                    <input type="text" x-model="modalData.docNo" required class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-300 dark:border-slate-700 text-xs font-bold text-indigo-600 dark:text-indigo-400">
                </div>

                <!-- Penerima Kuasa Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200 dark:border-slate-700/60">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Nama Penerima Kuasa</label>
                        <input type="text" x-model="modalData.penerimaNama" placeholder="Full Name of Authorized Person" class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-[#050913] border border-slate-300 dark:border-slate-700 text-xs font-semibold">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Alamat Penerima Kuasa</label>
                        <input type="text" x-model="modalData.penerimaAlamat" placeholder="Residential Address" class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-[#050913] border border-slate-300 dark:border-slate-700 text-xs font-semibold">
                    </div>
                </div>

                <!-- Vehicle Auto-filled parameters -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Jenis / Model</label>
                        <select x-model="modalData.jenisModel" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-[#050913] border border-slate-300 dark:border-slate-700 text-xs font-semibold">
                            <option value="Mobil Barang">Mobil Barang</option>
                            <option value="Mobil Penumpang">Mobil Penumpang</option>
                            <option value="Mobil Bus">Mobil Bus</option>
                            <option value="Sepeda Motor">Sepeda Motor</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Warna (Auto-filled from Odoo)</label>
                        <div class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700/80" x-text="modalData.color || 'Putih'"></div>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tahun (Auto-filled from Odoo)</label>
                        <div class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700/80" x-text="modalData.year || '-'"></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">No. Rangka (Auto-filled from Odoo)</label>
                        <div class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-mono font-bold text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700/80" x-text="modalData.noRangka"></div>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">No. Mesin (Auto-filled from Odoo)</label>
                        <div class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-mono font-bold text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700/80" x-text="modalData.noMesin"></div>
                    </div>
                </div>

                <!-- Action Footer: Only Word and Email options -->
                <div class="flex items-center justify-between gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="showGeneratorModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                        Cancel
                    </button>

                    <div class="flex items-center gap-2">
                        <!-- Option 1: Word (.docx) Preview -->
                        <button type="button" @click="goToPreview('word')" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold shadow-md transition-all hover:scale-[1.02] flex items-center gap-1.5" title="Preview Word Document">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Word (.docx)
                        </button>

                        <!-- Option 2: Email Preview -->
                        <button type="button" @click="goToPreview('email')" class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-extrabold shadow-md transition-all hover:scale-[1.02] flex items-center gap-1.5" title="Preview Email SK">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            Email
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- STEP 2: DOCUMENT PREVIEW MODAL (PICTURE 2 REPLICA) -->
    <div x-show="showPreviewModal" style="display: none;" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-md overflow-y-auto" x-transition>
        <div @click.away="showPreviewModal = false" class="bg-white text-black p-8 rounded-2xl w-full max-w-3xl shadow-2xl space-y-6 my-8 font-serif">
            
            <div class="flex justify-between items-center border-b pb-3 font-sans">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                    <span class="text-xs font-bold text-slate-700">Document Layout Preview (Before Generation)</span>
                </div>
                <button type="button" @click="showPreviewModal = false" class="text-slate-400 hover:text-black font-bold text-sm">✕ Close</button>
            </div>

            <!-- DOCUMENT PAPER CONTENT matching Picture 2 -->
            <div class="space-y-4 text-xs leading-relaxed text-black bg-white p-4">
                <div class="text-center space-y-1">
                    <h2 class="text-base font-bold underline tracking-wider">SURAT KUASA</h2>
                    <p class="font-normal" x-text="modalData.docNo"></p>
                </div>

                <div class="pt-2">Yang bertanda tangan dibawah ini:</div>
                <table class="w-full text-xs">
                    <tr><td class="w-32 py-0.5">Nama</td><td class="w-4">:</td><td>Suzanna Caroline</td></tr>
                    <tr><td class="py-0.5">Jabatan</td><td>:</td><td>General Manager</td></tr>
                    <tr><td class="py-0.5">Nama</td><td>:</td><td>Aldian Prayoga Darwis</td></tr>
                    <tr><td class="py-0.5">Jabatan</td><td>:</td><td>Fleet Operation Manager</td></tr>
                    <tr><td class="py-0.5">Alamat</td><td>:</td><td>Jl. Daan Mogot KM 1 No. 99 Jakarta Barat 11510</td></tr>
                </table>

                <div class="pt-4">Dengan ini memberi kuasa kepada :</div>
                <table class="w-full text-xs">
                    <tr><td class="w-32 py-0.5">Nama</td><td class="w-4">:</td><td x-text="modalData.penerimaNama || '....................................................................................'"></td></tr>
                    <tr><td class="py-0.5">Alamat</td><td>:</td><td x-text="modalData.penerimaAlamat || '....................................................................................'"></td></tr>
                </table>

                <div class="pt-4 font-bold">Untuk mengurus Surat Tanda Nomor Kendaraan ( STNK ) & BPKB</div>

                <table class="w-full text-xs">
                    <tr><td class="w-32 py-0.5 font-bold">Nama Pemilik</td><td class="w-4">:</td><td class="font-bold">PT Surya Darma Perkasa</td></tr>
                    <tr><td class="py-0.5">Alamat</td><td>:</td><td>Kel. Duri Kepa Kec. Kebon Jeruk Kota Jakarta Barat</td></tr>
                    <tr><td class="py-0.5">Merk/Type</td><td>:</td><td x-text="modalData.product"></td></tr>
                    <tr><td class="py-0.5">Jenis / Model</td><td>:</td><td x-text="modalData.jenisModel"></td></tr>
                    <tr><td class="py-0.5">Tahun</td><td>:</td><td x-text="modalData.year"></td></tr>
                    <tr><td class="py-0.5">No. Rangka</td><td>:</td><td x-text="modalData.noRangka"></td></tr>
                    <tr><td class="py-0.5">No. Mesin</td><td>:</td><td x-text="modalData.noMesin"></td></tr>
                    <tr><td class="py-0.5">Warna</td><td>:</td><td x-text="modalData.color"></td></tr>
                </table>

                <div class="pt-2">Demikian Surat Kuasa ini kami buat untuk dipergunakan sebagaimana mestinya.</div>

                <div class="pt-6 font-sans">
                    <div class="flex justify-between">
                        <div>
                            <div>Jakarta , {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                            <div class="font-bold mt-1">Pemberi Kuasa</div>
                            <div class="mt-20 flex gap-8">
                                <div>
                                    <div class="font-bold underline">Suzanna Caroline</div>
                                    <div class="text-[10px]">General Manager</div>
                                </div>
                                <div>
                                    <div class="font-bold underline">Aldian Prayoga Darwis</div>
                                    <div class="text-[10px]">Fleet Operation Manager</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="opacity-0">Jakarta ,</div>
                            <div class="font-bold mt-1">Penerima Kuasa</div>
                            <div class="mt-20 whitespace-pre">( <span :class="modalData.penerimaNama ? 'font-bold underline' : ''" x-text="modalData.penerimaNama || '                                          '"></span> )</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Final Modal Action Footer -->
            <div class="flex items-center justify-between pt-4 border-t font-sans">
                <button type="button" @click="showPreviewModal = false; showGeneratorModal = true;" class="px-4 py-2 rounded-xl bg-slate-200 text-slate-800 font-bold text-xs hover:bg-slate-300">
                    ← Edit Parameters
                </button>

                <button type="button" @click="confirmGenerateSK()" :disabled="isGenerating" class="px-6 py-2.5 rounded-xl text-white font-extrabold text-xs shadow-lg transition-all flex items-center gap-2" :class="pendingAction === 'email' ? 'bg-purple-600 hover:bg-purple-700' : 'bg-emerald-600 hover:bg-emerald-700'">
                    <svg x-show="!isGenerating" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <svg x-show="isGenerating" class="w-4 h-4 animate-spin" style="display:none;" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="isGenerating ? 'Processing...' : (pendingAction === 'email' ? 'Generate & Send Email' : 'Generate SK (.docx)')">Generate SK</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- STEP 3: SYNC NOTIFICATION & CHANGE DETAILS MODAL -->
    <div x-show="showSyncNotifModal" style="display: none;" class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-md" x-transition>
        <div @click.away="closeSyncNotifModal()" class="bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 w-full max-w-3xl shadow-2xl space-y-5 text-slate-900 dark:text-slate-100 max-h-[90vh] flex flex-col">
            
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-500 font-bold text-lg">
                        ✓
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Odoo Sync Completed</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium" x-text="syncNotifData.message"></p>
                    </div>
                </div>
                <button type="button" @click="closeSyncNotifModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Stats Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/60">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total SK Units</div>
                    <div class="text-lg font-extrabold text-slate-900 dark:text-white mt-0.5" x-text="syncNotifData.total_sk">0</div>
                </div>
                <div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20">
                    <div class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Ready to Generate</div>
                    <div class="text-lg font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5" x-text="syncNotifData.ready_sk">0</div>
                </div>
                <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/20">
                    <div class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Awaiting Data</div>
                    <div class="text-lg font-extrabold text-amber-600 dark:text-amber-400 mt-0.5" x-text="syncNotifData.pending_sk">0</div>
                </div>
                <div class="p-3.5 rounded-2xl bg-indigo-500/10 border border-indigo-500/20">
                    <div class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Units Updated</div>
                    <div class="text-lg font-extrabold text-indigo-600 dark:text-indigo-400 mt-0.5" x-text="syncNotifData.updated_count">0</div>
                </div>
            </div>

            <!-- Changes Detail Table -->
            <div class="flex-1 overflow-y-auto min-h-0 space-y-2 pr-1">
                <div class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Changed & Updated Lot Numbers Summary</div>
                
                <template x-if="syncNotifData.changes && syncNotifData.changes.length > 0">
                    <div class="space-y-2">
                        <template x-for="item in syncNotifData.changes" :key="item.lot_number">
                            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60 flex flex-col md:flex-row md:items-center justify-between gap-2 text-xs">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900 dark:text-white font-mono" x-text="item.lot_number"></span>
                                        <span x-show="item.is_new" class="px-2 py-0.5 rounded-full bg-cyan-500/10 text-cyan-500 text-[10px] font-bold border border-cyan-500/20">NEW</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5" x-text="item.product"></div>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                        <template x-for="c in item.changes">
                                            <span class="px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 text-[10px] font-semibold" x-text="c"></span>
                                        </template>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    <span :class="item.is_ready ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-amber-500/10 text-amber-500 border-amber-500/20'" class="px-2.5 py-1 rounded-xl text-[11px] font-extrabold border inline-flex items-center gap-1" x-text="item.status_label">
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!syncNotifData.changes || syncNotifData.changes.length === 0">
                    <div class="p-6 text-center text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800/30 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <svg class="w-8 h-8 mx-auto text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="text-xs font-bold">All units are up-to-date.</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">No chassis or engine numbers were changed in Odoo during this sync.</p>
                    </div>
                </template>
            </div>

            <!-- Footer Action -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-slate-800">
                <button type="button" @click="closeSyncNotifModal()" class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                    Dismiss
                </button>
                <button type="button" @click="applyAndReload()" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-700 hover:to-cyan-700 text-white text-xs font-extrabold shadow-md transition-all">
                    Refresh Table & Apply Changes
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function suratKuasaApp() {
    return {
        isSyncing: false,
        showGeneratorModal: false,
        showPreviewModal: false,
        showSyncNotifModal: false,
        pendingAction: 'word', // 'word' or 'email'
        isGenerating: false,
        isTestingEmail: false,

        notificationsList: {!! json_encode($recentNotifications ?? []) !!},

        syncNotifData: {
            message: '',
            total_sk: 0,
            ready_sk: 0,
            pending_sk: 0,
            updated_count: 0,
            changes: []
        },

        closeSyncNotifModal() {
            this.showSyncNotifModal = false;
        },

        applyAndReload() {
            window.location.reload();
        },

        emailForm: {
            recipientEmail: {!! json_encode($settings['default_recipient_email'] ?? '') !!},
            subject: '',
            message: '',
            fileFormat: 'docx'
        },

        modalData: {
            id: null,
            product: '',
            noRangka: '',
            noMesin: '',
            color: 'Putih',
            year: '{{ date('Y') }}',
            docNo: '1545/HRCJ/FOD/{{ date('m/Y') }}',
            penerimaNama: '',
            penerimaAlamat: '',
            jenisModel: 'Mobil Barang'
        },

        openGeneratorModal(data) {
            this.modalData = {
                id: data.id,
                product: data.product,
                noRangka: data.noRangka,
                noMesin: data.noMesin,
                color: data.color || 'Putih',
                year: data.year || '{{ date('Y') }}',
                docNo: '1545/HRCJ/FOD/' + ('0' + (new Date().getMonth() + 1)).slice(-2) + '/' + new Date().getFullYear(),
                penerimaNama: '',
                penerimaAlamat: '',
                jenisModel: 'Mobil Barang'
            };
            this.emailForm.subject = 'Surat Kuasa Document - ' + (data.product || 'Vehicle');
            this.emailForm.message = 'Please find attached the Surat Kuasa document for vehicle unit (' + data.noRangka + ').';
            this.showGeneratorModal = true;
            this.showPreviewModal = false;
        },

        goToPreview(action) {
            this.pendingAction = action;
            this.showGeneratorModal = false;
            this.showPreviewModal = true;
        },

        async confirmGenerateSK() {
            this.isGenerating = true;

            if (this.pendingAction === 'email') {
                if (!this.emailForm.recipientEmail) {
                    alert('Please configure Default Recipient Email Address(es) in Settings (UTILITIES -> Settings -> Surat Kuasa Configuration) first.');
                    this.isGenerating = false;
                    return;
                }

                try {
                    const response = await fetch('{{ url('/surat-kuasa/email') }}/' + this.modalData.id, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            recipient_email: this.emailForm.recipientEmail,
                            subject: this.emailForm.subject,
                            message: this.emailForm.message,
                            file_format: 'docx',
                            doc_no: this.modalData.docNo,
                            penerima_nama: this.modalData.penerimaNama,
                            penerima_alamat: this.modalData.penerimaAlamat,
                            jenis_model: this.modalData.jenisModel
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        alert(result.message);
                        this.showPreviewModal = false;
                        window.location.reload();
                    } else {
                        alert('Email error: ' + (result.message || 'Unknown error'));
                    }
                } catch (e) {
                    alert('Email failed: ' + e.message);
                } finally {
                    this.isGenerating = false;
                }
            } else {
                // Word download action
                const params = new URLSearchParams({
                    doc_no: this.modalData.docNo,
                    penerima_nama: this.modalData.penerimaNama,
                    penerima_alamat: this.modalData.penerimaAlamat,
                    jenis_model: this.modalData.jenisModel
                });
                window.location.href = '{{ url('/surat-kuasa/download-docx') }}/' + this.modalData.id + '?' + params.toString();
                
                setTimeout(() => {
                    this.isGenerating = false;
                    this.showPreviewModal = false;
                    window.location.reload();
                }, 2000);
            }
        },

        async syncOdooData() {
            if (this.isSyncing) return;
            this.isSyncing = true;

            try {
                const response = await fetch('{{ route('surat-kuasa.sync-odoo') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    this.syncNotifData = {
                        message: result.message,
                        total_sk: result.total_sk || 0,
                        ready_sk: result.ready_sk || 0,
                        pending_sk: result.pending_sk || 0,
                        updated_count: result.updated_count || 0,
                        changes: result.changes || []
                    };
                    this.showSyncNotifModal = true;
                } else {
                    alert('Sync error: ' + result.message);
                }
            } catch (e) {
                alert('Sync failed: ' + e.message);
            } finally {
                this.isSyncing = false;
            }
        },

        async fastSync() {
            if (this.isSyncing) return;
            this.isSyncing = true;

            try {
                const response = await fetch('{{ route('surat-kuasa.fast-sync') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    this.syncNotifData = {
                        message: result.message,
                        total_sk: result.total_sk || 0,
                        ready_sk: result.ready_sk || 0,
                        pending_sk: result.pending_sk || 0,
                        updated_count: result.updated_count || 0,
                        changes: result.changes || []
                    };
                    this.showSyncNotifModal = true;
                } else {
                    alert('Fast Sync error: ' + result.message);
                }
            } catch (e) {
                alert('Fast Sync failed: ' + e.message);
            } finally {
                this.isSyncing = false;
            }
        },

        async testEmail() {
            if (this.isTestingEmail) return;
            this.isTestingEmail = true;

            try {
                const response = await fetch('{{ route('surat-kuasa.test-email') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                } else {
                    alert('Test Email error: ' + (result.message || 'Unknown error'));
                }
            } catch (e) {
                alert('Test Email failed: ' + e.message);
            } finally {
                this.isTestingEmail = false;
            }
        }
    };
}
</script>
@endsection
