@extends('layouts.app')

@section('content')
<div class="p-6 md:p-8 w-full max-w-[100vw] mx-auto" x-data="{ 
    isUnlocked: {{ $authenticated ? 'true' : 'false' }},
    passwordInput: '',
    passwordError: false,
    expandedRow: null,
    syncing: false,
    
    showFullHistory: false,
    fullHistoryData: [],
    loadingHistory: false,
    historySearch: '',
    displayLimit: 50,
    
    detailsModalOpen: false,
    detailsLoading: false,
    detailsData: [],
    currentDetailTitle: '',

    exportModalOpen: false,
    exportStatuses: [
        { name: 'Pickedup', selected: true },
        { name: 'Returned', selected: false },
        { name: 'Reserved', selected: false },
        { name: 'Quotation', selected: false },
        { name: 'Cancelled', selected: false }
    ],
    exportIncludeNopol: false,
    exportTaxMode: 'original',

    openExportModal() {
        console.log('openExportModal called');
        this.exportModalOpen = true;
    },
    
    async openDetails(rentalId, lotNumber) {
        this.currentDetailTitle = rentalId + (lotNumber ? ' - ' + lotNumber : '');
        this.detailsData = [];
        this.detailsModalOpen = true;
        this.detailsLoading = true;
        try {
            const url = '{{ route('lor.rental-details') }}?rental_id=' + encodeURIComponent(rentalId) + '&lot_number=' + encodeURIComponent(lotNumber);
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) {
                const text = await response.text();
                let msg = 'Server returned error ' + response.status;
                try {
                    const jsonErr = JSON.parse(text);
                    msg = jsonErr.message || jsonErr.error || msg;
                } catch(err) {}
                alert('Failed to load details: ' + msg);
                this.detailsModalOpen = false;
                return;
            }
            const result = await response.json();
            if (result.success) {
                this.detailsData = result.data;
            } else {
                alert('Failed to load details: ' + (result.message || 'Unknown error'));
                this.detailsModalOpen = false;
            }
        } catch (e) {
            alert('Error loading details: ' + e.message);
            this.detailsModalOpen = false;
        } finally {
            this.detailsLoading = false;
        }
    },

    get filteredHistoryData() {
        if (!this.historySearch) return this.fullHistoryData;
        const q = this.historySearch.toLowerCase();
        return this.fullHistoryData.filter(item => item.rental_id.toLowerCase().includes(q));
    },
    
    get displayedHistoryData() {
        return this.filteredHistoryData.slice(0, this.displayLimit);
    },

    async loadFullHistory() {
        this.displayLimit = 50;
        this.showFullHistory = true;
        if (this.fullHistoryData.length > 0) return;
        this.loadingHistory = true;
        try {
            const response = await fetch('{{ route('lor.full-history') }}');
            this.fullHistoryData = await response.json();
        } catch (e) {
            alert('Failed to load history: ' + e.message);
        } finally {
            this.loadingHistory = false;
        }
    },
    
    async syncData() {
        if (this.syncing) return;
        this.syncing = true;
        try {
            const response = await fetch('{{ route('import.odoo.sync') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();
            if (result.success) {
                window.location.reload();
            } else {
                alert('Sync failed: ' + result.message);
            }
        } catch (e) {
            alert('Sync failed: ' + e.message);
        } finally {
            this.syncing = false;
        }
    },
    
    checkPassword() {
        if (!this.passwordInput) return;
        this.$el.closest('form').submit();
    },
    
    toggleExpand(rentalId) {
        this.expandedRow = this.expandedRow === rentalId ? null : rentalId;
    }
}">

    @if(!$authenticated)
    <!-- Password Modal -->
    <div x-show="!isUnlocked" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-8 w-full max-w-md mx-4 border border-slate-200 dark:border-slate-700">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Access Protected</h2>
                <p class="text-slate-500 dark:text-slate-400 mt-2">Enter password to access List of Rented</p>
            </div>

            <form action="{{ route('lor.auth') }}" method="POST" @submit.prevent="checkPassword()">
                @csrf
                <div class="mb-4">
                    <input type="password" name="password" x-model="passwordInput" placeholder="Enter password" 
                           class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 border-slate-200 dark:border-slate-700 transition-all text-center text-lg tracking-widest" autofocus>
                    @if(!empty($session_expired) || session('session_expired'))
                        <div class="mb-3 p-3 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-xl text-sm text-center border border-amber-200 dark:border-amber-800">
                            ⏱️ Your LoR session timed out after 15 minutes of inactivity. Please re-enter your password.
                        </div>
                    @endif
                    @if(session('error'))
                        <p class="text-red-500 text-sm mt-2 text-center">{{ session('error') }}</p>
                    @endif
                </div>
                <button type="submit" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                    Unlock
                </button>
            </form>
            <div class="mt-6 text-center">
                <a href="{{ route('dashboard') }}" class="text-slate-500 hover:text-indigo-600 transition-colors">← Back to Dashboard</a>
            </div>
        </div>
    </div>
    @else
    
    <!-- Main Content -->
    <div x-show="isUnlocked" x-transition class="w-full">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 gap-4 px-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-3">
                    <div class="p-2.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    </div>
                    List of Rented (LoR)
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-2 ml-14">View all active rentals and their history</p>
            </div>
            
            <!-- Search Box & Sync Button -->
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                <div class="relative z-40" x-data="{ 
                    showNotifications: false,
                    readUpdates: JSON.parse(localStorage.getItem('readUpdates') || '[]'),
                    allUpdates: {{ json_encode(collect($recentUpdatesList)->pluck('key')) }},
                    get unreadCount() {
                        return this.allUpdates.filter(id => !this.readUpdates.includes(id)).length;
                    },
                    markAsRead(key) {
                        if (!this.readUpdates.includes(key)) {
                            this.readUpdates.push(key);
                            localStorage.setItem('readUpdates', JSON.stringify(this.readUpdates));
                        }
                    },
                    markAllAsRead() {
                        this.allUpdates.forEach(id => {
                            if (!this.readUpdates.includes(id)) {
                                this.readUpdates.push(id);
                            }
                        });
                        localStorage.setItem('readUpdates', JSON.stringify(this.readUpdates));
                    }
                }" @click.away="showNotifications = false">
                    <button @click="showNotifications = !showNotifications" type="button" class="relative inline-flex items-center justify-center p-2.5 text-slate-500 hover:text-indigo-600 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700 rounded-xl transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        <span class="absolute top-0 right-0 -mt-1 -mr-1 flex h-4 w-4" x-show="unreadCount > 0" style="display: none;">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-4 w-4 bg-indigo-500 text-[10px] text-white font-bold items-center justify-center" x-text="unreadCount"></span>
                        </span>
                    </button>
                    
                    <div x-show="showNotifications" x-transition style="display: none;" class="absolute right-0 sm:right-auto sm:left-0 mt-3 w-80 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden text-left">
                        <div class="p-4 bg-slate-50 dark:bg-slate-900 border-b border-slate-100 dark:border-slate-700">
                            <h3 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                Recent Sync Updates
                            </h3>
                            <div class="flex justify-between items-center mt-1">
                                <p class="text-xs text-slate-500">Changes detected in the last sync.</p>
                                <button @click="markAllAsRead()" x-show="unreadCount > 0" type="button" class="text-xs text-indigo-600 hover:text-indigo-800 dark:hover:text-indigo-400 font-medium transition-colors">Mark all as read</button>
                            </div>
                        </div>
                        <div class="max-h-64 overflow-y-auto custom-scrollbar p-2">
                            @forelse($recentUpdatesList as $update)
                                <div @mouseenter="markAsRead('{{ $update['key'] }}')" 
                                     class="p-3 rounded-xl transition-all mb-1 border-l-2 cursor-default"
                                     :class="readUpdates.includes('{{ $update['key'] }}') ? 'bg-white dark:bg-slate-800 border-transparent opacity-75 hover:bg-slate-50 dark:hover:bg-slate-700/50' : 'bg-indigo-50/50 dark:bg-indigo-900/20 border-indigo-500 shadow-sm'">
                                    <div class="font-semibold text-sm text-slate-800 dark:text-slate-200">{{ $update['rental_id'] }}</div>
                                    <div class="text-xs text-slate-600 dark:text-slate-400 mt-1 space-y-1">
                                        @foreach($update['changes'] as $label => $values)
                                            <div>
                                                <span class="font-medium">{{ $label }}:</span> 
                                                <span class="line-through opacity-70">{{ $values['old'] ?: 'None' }}</span> &rarr; 
                                                <span class="text-indigo-600 dark:text-indigo-400 font-medium">{{ $values['new'] ?: 'None' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-6">
                                    <svg class="w-8 h-8 mx-auto text-slate-300 dark:text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">No new changes detected.</p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Everything is up to date!</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <form action="{{ route('lor.index') }}" method="GET" class="w-full sm:w-80">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search Rental ID, PO, Contract, Customer..." class="block w-full pl-10 pr-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl leading-5 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                </form>
                
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="button" @click="openExportModal()" class="inline-flex w-full items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium bg-emerald-500 hover:bg-emerald-600 text-white transition-colors shadow-sm justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Export Data
                    </button>
                </div>

                <button @click="loadFullHistory()" type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm w-full sm:w-auto justify-center">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Full Sync History
                </button>
                
                <button @click="syncData()" :disabled="syncing" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 disabled:cursor-not-allowed text-white transition-colors shadow-sm w-full sm:w-auto justify-center">
                    <svg x-show="!syncing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <svg x-show="syncing" class="w-4 h-4 animate-spin" style="display:none;" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="syncing ? 'Syncing...' : 'Sync Odoo Data'">Sync Odoo Data</span>
                </button>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden mx-4">
            <div class="overflow-auto custom-scrollbar max-h-[75vh]" style="max-width: 100%;">
                <table class="w-full text-sm text-left whitespace-nowrap relative">
                    <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-20 shadow-[0_1px_2px_0_rgba(0,0,0,0.05)]">
                        <tr>
                            <th class="px-4 py-4 font-semibold w-8"></th>
                            <th class="px-4 py-4 font-semibold">Rental ID</th>
                            <th class="px-4 py-4 font-semibold">Nomor Kontrak</th>
                            <th class="px-4 py-4 font-semibold">Type</th>
                            <th class="px-4 py-4 font-semibold">Police-No</th>
                            <th class="px-4 py-4 font-semibold">Tahun Kendaraan</th>
                            <th class="px-4 py-4 font-semibold">CITY/Lokasi Pemakaian</th>
                            <th class="px-4 py-4 font-semibold">Customer</th>
                            <th class="px-4 py-4 font-semibold">PO</th>
                            <th class="px-4 py-4 font-semibold">Status</th>
                            <th class="px-4 py-4 font-semibold">Start Sewa</th>
                            <th class="px-4 py-4 font-semibold">End Sewa</th>
                            <th class="px-4 py-4 font-semibold">Harga</th>
                            <th class="px-4 py-4 font-semibold">Total Harga</th>
                            <th class="px-4 py-4 font-semibold">COP/Driver</th>
                            <th class="px-4 py-4 font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @php
                            $currentCustomerGroup = null;
                            $currentStatusGroup = null;
                        @endphp
                        @forelse($currentRentals as $rental)
                        @php
                            $itemHistories = $histories->get($rental->rental_id, collect());
                            
                            $plateRenewalHistories = collect();
                            if ($itemHistories->count() > 0) {
                                // Histories are ordered by created_at desc. We compare sequentially to find plate renewals.
                                $sortedDesc = $itemHistories->sortByDesc('created_at')->values();
                                
                                $lastNopol = $rental->lot_number;
                                $lastMoveCount = $rental->product_movement_count;
                                
                                foreach ($sortedDesc as $h) {
                                    if ($h->lot_number != $lastNopol) {
                                        // SMART FILTER: If lot_number changed but movement count is the same, it's a 5-year plate renewal
                                        if ($h->product_movement_count == $lastMoveCount) {
                                            $plateRenewalHistories->push($h);
                                        }
                                    }
                                    $lastNopol = $h->lot_number;
                                    $lastMoveCount = $h->product_movement_count;
                                }
                            }
                            $hasNopolChange = $plateRenewalHistories->count() > 0;
                            
                            $isNewCustomerGroup = $currentCustomerGroup !== $rental->current_customer;
                            $isNewStatusGroup = $isNewCustomerGroup || $currentStatusGroup !== $rental->status;
                            
                            if ($isNewCustomerGroup) {
                                $currentCustomerGroup = $rental->current_customer;
                            }
                            if ($isNewStatusGroup) {
                                $currentStatusGroup = $rental->status;
                            }
                        @endphp
                        
                        @if($isNewCustomerGroup)
                        <tr class="bg-indigo-100 dark:bg-indigo-900/60 border-y border-indigo-200 dark:border-indigo-700 shadow-sm">
                            <td colspan="16" class="px-5 py-4 font-bold text-indigo-800 dark:text-indigo-200 sticky left-0 text-lg">
                                <div class="flex items-center gap-2 text-base">
                                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                    {{ $currentCustomerGroup ?: 'Unassigned / No Customer' }}
                                </div>
                            </td>
                        </tr>
                        @endif

                        @if($isNewStatusGroup)
                        @php
                            $groupTextColors = [
                                'Quotation' => 'text-blue-600 dark:text-blue-400',
                                'Quotation Sent' => 'text-blue-600 dark:text-blue-400',
                                'Reserved' => 'text-green-600 dark:text-green-400',
                                'Pickedup' => 'text-yellow-600 dark:text-yellow-400',
                                'Returned' => 'text-red-600 dark:text-red-400',
                                'Cancelled' => 'text-gray-500 dark:text-gray-400',
                            ];
                            $groupTextColor = $groupTextColors[$currentStatusGroup] ?? 'text-slate-500 dark:text-slate-400';
                        @endphp
                        <tr class="bg-slate-100 dark:bg-slate-800 border-y border-slate-200 dark:border-slate-700 shadow-sm">
                            <td colspan="16" class="px-5 py-2 font-semibold text-slate-700 dark:text-slate-300 sticky left-0 text-sm pl-8">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-current opacity-80 {{ $groupTextColor }}"></div>
                                    <span class="text-slate-500 dark:text-slate-400">Status:</span>
                                    <span class="{{ $groupTextColor }} font-bold">
                                        {{ $currentStatusGroup ?: 'Unknown' }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @endif
                        
                        @php
                            $isUpdated = isset($updatedKeys) && in_array($rental->rental_id, $updatedKeys);
                        @endphp
                        
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors font-medium {{ $isUpdated ? 'bg-emerald-50/50 dark:bg-emerald-900/20 text-emerald-900 dark:text-emerald-100' : 'text-slate-800 dark:text-slate-200' }}" :class="expandedRow === '{{ $rental->rental_id }}_{{ $rental->lot_number }}' ? 'bg-indigo-50/30 dark:bg-indigo-900/10' : ''">
                            <td class="px-4 py-3 {{ $isUpdated ? 'border-l-4 border-l-emerald-400' : '' }}">
                                @if($hasNopolChange)
                                <button type="button" @click="expandedRow = expandedRow === '{{ $rental->rental_id }}_{{ $rental->lot_number }}' ? null : '{{ $rental->rental_id }}_{{ $rental->lot_number }}'" class="p-1 rounded-md text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/50 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <svg class="w-5 h-5 transition-transform duration-200" :class="expandedRow === '{{ $rental->rental_id }}_{{ $rental->lot_number }}' ? 'rotate-180 text-indigo-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-bold flex items-center gap-2">
                                    {{ $rental->rental_id ?: '-' }}
                                    @if($isUpdated)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400 uppercase tracking-wider shadow-sm">Updated</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ $rental->contract_ref ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $rental->product ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @if(!empty($rental->reserved_lot) && $rental->lot_number !== $rental->reserved_lot)
                                    <div class="flex flex-col">
                                        <span class="font-bold text-amber-700 dark:text-amber-500">
                                            {{ $rental->lot_number }}
                                            @if($rental->product_movement_count > 1)
                                                (SWITCH UNIT)
                                            @else
                                                (RBO)
                                            @endif
                                        </span>
                                        <span class="text-xs text-slate-500">Original: {{ $rental->reserved_lot }}</span>
                                    </div>
                                @else
                                    {{ $rental->lot_number ?: '-' }}
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $rental->year ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $rental->city ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $rental->current_customer ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $rental->po ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'Quotation' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 border-blue-200 dark:border-blue-700',
                                        'Quotation Sent' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 border-blue-200 dark:border-blue-700',
                                        'Reserved' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 border-green-200 dark:border-green-700',
                                        'Pickedup' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300 border-yellow-200 dark:border-yellow-700',
                                        'Returned' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 border-red-200 dark:border-red-700',
                                        'Cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300 border-gray-200 dark:border-gray-700',
                                    ];
                                    $statusColor = $statusColors[$rental->status] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-900/50 dark:text-slate-300 border-slate-200 dark:border-slate-700';
                                @endphp
                                @if($rental->status)
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full border {{ $statusColor }} whitespace-nowrap">
                                        {{ $rental->status }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $rental->actual_start_rental ? \Carbon\Carbon::parse($rental->actual_start_rental)->format('d M Y') : '-' }}</td>
                            <td class="px-4 py-3">{{ $rental->actual_end_rental ? \Carbon\Carbon::parse($rental->actual_end_rental)->format('d M Y') : '-' }}</td>
                            <td class="px-4 py-3">{{ $rental->price ? 'Rp ' . number_format($rental->price, 0, ',', '.') : '-' }}</td>
                            <td class="px-4 py-3">{{ $rental->total_price ? 'Rp ' . number_format($rental->total_price, 0, ',', '.') : '-' }}</td>
                            <td class="px-4 py-3">{{ $rental->driver ?: '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <button type="button" @click="openDetails('{{ addslashes($rental->rental_id) }}', '{{ addslashes($rental->lot_number) }}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm whitespace-nowrap">
                                    <svg x-show="detailsLoading && currentDetailTitle === '{{ addslashes($rental->rental_id) }}' + ('{{ addslashes($rental->lot_number) }}' ? ' - {{ addslashes($rental->lot_number) }}' : '')" style="display: none;" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <svg x-show="!(detailsLoading && currentDetailTitle === '{{ addslashes($rental->rental_id) }}' + ('{{ addslashes($rental->lot_number) }}' ? ' - {{ addslashes($rental->lot_number) }}' : ''))" class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    Details
                                </button>
                            </td>
                        </tr>
                        
                        @if($hasNopolChange)
                        <template x-if="expandedRow === '{{ $rental->rental_id }}_{{ $rental->lot_number }}'">
                            @foreach($plateRenewalHistories as $h)
                                @php
                                    // SMART FILTER: Only show history rows that represent a 5-year plate renewal
                                    // A plate renewal is when lot_number differs from the current active car,
                                    // BUT the product_movement_count is exactly the same!
                                    // Wait, $itemHistories are the snapshots. 
                                    // Actually, if we just want to display the history, we can show it just like before.
                                @endphp
                                <tr class="bg-indigo-900/10 hover:bg-indigo-900/30 border-y border-slate-100 dark:border-slate-800 transition-colors font-medium text-slate-600 dark:text-slate-300 shadow-inner">
                                    <td class="px-4 py-2 text-xs opacity-75 border-l-4 border-indigo-400 font-bold whitespace-nowrap pl-6">
                                        {{ \Carbon\Carbon::parse($h->created_at)->format('d/m/y') }}
                                    </td>
                                    <td class="px-4 py-2">{{ $h->rental_id ?: '-' }}</td>
                                    <td class="px-4 py-2">{{ $h->contract_ref ?: '-' }}</td>
                                    <td class="px-4 py-2">{{ $h->product ?: '-' }}</td>
                                    <td class="px-4 py-2 text-indigo-700 dark:text-indigo-300 font-bold">
                                        {{ $h->lot_number ?: '-' }}
                                    </td>
                                    <td class="px-4 py-2">{{ $h->year ?: '-' }}</td>
                                    <td class="px-4 py-2">{{ $h->city ?: '-' }}</td>
                                    <td class="px-4 py-2">{{ $h->current_customer ?: '-' }}</td>
                                    <td class="px-4 py-2">{{ $h->po ?: '-' }}</td>
                                    <td class="px-4 py-2">
                                        @if($h->status)
                                            <span class="px-2.5 py-1 text-[10px] font-medium rounded-full bg-slate-800 text-slate-300 border border-slate-700 whitespace-nowrap">
                                                {{ $h->status }}
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 text-[10px] font-medium rounded-full bg-slate-800 text-amber-500 border border-amber-900 whitespace-nowrap">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">{{ $h->actual_start_rental ? \Carbon\Carbon::parse($h->actual_start_rental)->format('d M Y') : '-' }}</td>
                                    <td class="px-4 py-2">{{ $h->actual_end_rental ? \Carbon\Carbon::parse($h->actual_end_rental)->format('d M Y') : '-' }}</td>
                                    <td class="px-4 py-2">{{ $h->price ? 'Rp ' . number_format((float)$h->price, 0, ',', '.') : '-' }}</td>
                                    <td class="px-4 py-2">{{ $h->driver ?: '-' }}</td>
                                    <td class="px-4 py-2 text-center"></td>
                                </tr>
                            @endforeach
                        </template>
                        @endif
                        @empty
                        <tr>
                            <td colspan="15" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                No rentals found matching your criteria.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($currentRentals->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                {{ $currentRentals->links() }}
            </div>
            @endif
        </div>
        </div>
        
        <!-- Export Modal -->
        <div x-show="exportModalOpen" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div x-show="exportModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="exportModalOpen = false" aria-hidden="true"></div>

            <!-- Modal Panel -->
            <div x-show="exportModalOpen" 
                 x-transition.opacity
                 class="relative z-10 bg-white dark:bg-slate-900 rounded-2xl text-left shadow-2xl w-full max-w-lg border border-slate-200 dark:border-slate-700 flex flex-col overflow-hidden">
                
                <!-- Header -->
                <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        </div>
                        <h3 class="text-lg leading-6 font-bold text-slate-900 dark:text-white">Export Options</h3>
                    </div>
                    <button @click="exportModalOpen = false" type="button" class="text-slate-400 hover:text-slate-500 focus:outline-none transition-colors rounded-lg p-1 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <!-- Body -->
                <form action="{{ route('lor.export') }}" method="GET" @submit="exportModalOpen = false">
                    <input type="hidden" name="search" value="{{ $search }}">
                    <div class="p-6 space-y-6">
                        <!-- Statuses -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Include Statuses</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <template x-for="(status, index) in exportStatuses" :key="index">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" :name="'statuses['+status.name+']'" x-model="exportStatuses[index].selected" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-slate-800 dark:border-slate-600">
                                        <span class="text-sm text-slate-700 dark:text-slate-300" x-text="status.name"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <!-- History Options -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Additional Data</h4>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="include_nopol" value="1" x-model="exportIncludeNopol" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-slate-800 dark:border-slate-600">
                                <span class="text-sm text-slate-700 dark:text-slate-300">Include NOPOL (Plate) Change History</span>
                            </label>
                        </div>

                        <!-- Tax Mode -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Price Detail Display</h4>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="tax_mode" value="original" x-model="exportTaxMode" class="border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-800 dark:border-slate-600">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">Original (As recorded in Odoo)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="tax_mode" value="include" x-model="exportTaxMode" class="border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-800 dark:border-slate-600">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">Force Include 11% Tax</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="tax_mode" value="exclude" x-model="exportTaxMode" class="border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-800 dark:border-slate-600">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">Force Exclude 11% Tax</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3 rounded-b-2xl">
                        <button type="submit" name="format" value="pdf" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium bg-rose-500 hover:bg-rose-600 text-white transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Export PDF
                        </button>
                        <button type="submit" name="format" value="excel" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium bg-emerald-500 hover:bg-emerald-600 text-white transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Export Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Full Sync History Modal -->
        <div x-show="showFullHistory" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div x-show="showFullHistory" x-transition.opacity class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="showFullHistory = false" aria-hidden="true"></div>

            <!-- Modal Panel -->
            <div x-show="showFullHistory" 
                 x-transition.opacity
                 x-transition:enter.duration.200ms
                 x-transition:leave.duration.150ms
                 class="relative z-10 bg-white dark:bg-slate-900 rounded-2xl text-left shadow-2xl w-full max-w-4xl border border-slate-200 dark:border-slate-700 flex flex-col max-h-[90vh]">
                
                <!-- Header -->
                <div class="shrink-0 px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50 rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-lg leading-6 font-bold text-slate-900 dark:text-white" id="modal-title">Full Sync History</h3>
                    </div>
                    <button @click="showFullHistory = false" type="button" class="text-slate-400 hover:text-slate-500 focus:outline-none transition-colors rounded-lg p-1 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <!-- Search Box -->
                <div class="shrink-0 px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" x-model="historySearch" placeholder="Filter by Rental ID..." class="block w-full pl-10 pr-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl leading-5 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-900 sm:text-sm transition-all">
                    </div>
                </div>

                <!-- Body / Scrollable Area -->
                <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
                    <div x-show="loadingHistory" class="flex justify-center items-center py-12">
                        <svg class="w-8 h-8 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>
                    
                    <div x-show="!loadingHistory && filteredHistoryData.length === 0" class="text-center py-12" style="display: none;">
                        <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="text-slate-500 dark:text-slate-400">No historical changes found matching your filter.</p>
                    </div>

                    <div x-show="!loadingHistory && filteredHistoryData.length > 0" class="space-y-6" style="display: none;">
                        <template x-for="item in displayedHistoryData" :key="item.key + '_' + item.change_timestamp">
                            <div class="relative pl-6 sm:pl-8 border-l-2 border-slate-200 dark:border-slate-700 pb-2 last:border-0 last:pb-0">
                                <div class="absolute left-[-9px] top-1 h-4 w-4 rounded-full bg-white dark:bg-slate-900 border-2 border-indigo-500 shadow-sm"></div>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-2 gap-2">
                                    <div class="font-bold text-lg text-slate-800 dark:text-slate-100" x-text="item.rental_id"></div>
                                    <div class="text-xs font-medium text-slate-500 bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-full w-max" x-text="item.change_time"></div>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/50 shadow-sm">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                                        <template x-for="(values, label) in item.changes">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1" x-text="label"></span>
                                                <div class="flex items-center flex-wrap gap-2 text-sm">
                                                    <span class="text-slate-500 line-through decoration-slate-300 dark:decoration-slate-600" x-text="values.old || 'None'"></span>
                                                    <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                                    <span class="font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded-md" x-text="values.new || 'None'"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <div x-show="filteredHistoryData.length > displayedHistoryData.length" class="pt-4 text-center">
                            <button @click="displayLimit += 50" type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-indigo-600 dark:text-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm">
                                Load More History
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Invoice Period Details Modal -->
        <div x-show="detailsModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4" style="display: none;">
            <div @click.away="detailsModalOpen = false" 
                 x-show="detailsModalOpen" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 scale-95" 
                 x-transition:enter-end="opacity-100 scale-100" 
                 x-transition:leave="transition ease-in duration-200" 
                 x-transition:leave-start="opacity-100 scale-100" 
                 x-transition:leave-end="opacity-0 scale-95" 
                 class="relative z-10 bg-white dark:bg-slate-900 rounded-2xl text-left shadow-2xl w-full max-w-4xl border border-slate-200 dark:border-slate-700 flex flex-col overflow-hidden max-h-[90vh]">
                
                <!-- Header -->
                <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg leading-6 font-bold text-slate-900 dark:text-white">Price History Details</h3>
                            <p class="text-xs text-slate-500 font-medium mt-0.5" x-text="currentDetailTitle"></p>
                        </div>
                    </div>
                    <button @click="detailsModalOpen = false" type="button" class="text-slate-400 hover:text-slate-500 focus:outline-none transition-colors rounded-lg p-1 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <!-- Body -->
                <div class="flex-1 overflow-y-auto p-6 bg-slate-50/30 dark:bg-slate-900/30">
                    <div x-show="detailsData.length === 0" class="text-center py-12">
                        <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="text-slate-500 dark:text-slate-400">No invoice period history found for this rental.</p>
                    </div>

                    <div x-show="detailsData.length > 0" style="display: none;">
                        <div class="space-y-4">
                            <template x-for="(group, index) in detailsData" :key="index">
                                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                                    <!-- Group Header -->
                                    <div class="bg-slate-100/50 dark:bg-slate-800/80 px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-400 text-xs font-bold" x-text="index + 1"></span>
                                            <span class="font-bold text-slate-700 dark:text-slate-200">Price Block</span>
                                        </div>
                                        <div class="font-mono text-sm text-slate-500" x-text="group.product || 'Unknown Product'"></div>
                                    </div>
                                    
                                    <!-- Group Details -->
                                    <div class="p-5">
                                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                            <div>
                                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Price</div>
                                                <div class="font-bold text-lg text-indigo-600 dark:text-indigo-400">
                                                    Rp <span x-text="new Intl.NumberFormat('id-ID').format(group.price)"></span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Harga</div>
                                                <div class="font-bold text-lg text-emerald-600 dark:text-emerald-400">
                                                    Rp <span x-text="new Intl.NumberFormat('id-ID').format(group.total_price || group.price)"></span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Start Period</div>
                                                <div class="font-medium text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    <span x-text="group.start_date"></span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">End Period</div>
                                                <div class="font-medium text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    <span x-text="group.end_date || 'Ongoing'"></span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Tax</div>
                                                <div class="font-medium">
                                                    <span class="px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700" x-text="group.tax"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <!-- Full Sync History Modal -->
    @endif
</div>
@endsection
