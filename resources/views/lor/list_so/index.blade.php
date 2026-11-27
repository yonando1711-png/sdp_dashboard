@extends('layouts.app')

@section('title', 'List SO - LoR (SMD)')

@section('content')
<div x-data="listSoApp()" class="space-y-6">

    {{-- TOP TITLE & ACTION BAR --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">List Sales Order (LoR SMD)</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Laporan Mutasi &amp; Antrean Cetak Resmi Harian Sales Order</p>
                </div>
            </div>
        </div>

        {{-- FILTER & ACTION BUTTONS --}}
        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Team / Sales Manager Filter --}}
            <form method="GET" action="{{ route('lor.list-so') }}" id="teamFilterForm" class="flex items-center gap-2">
                <input type="hidden" name="tab" :value="activeTab">
                <select name="sales_manager" onchange="document.getElementById('teamFilterForm').submit()"
                    class="text-xs font-semibold rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 px-3 py-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Sales Manager (All Managers)</option>
                    @foreach($salesManagers as $key => $label)
                        <option value="{{ $key }}" {{ $selectedManager === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </form>

            {{-- Date Range for Sync --}}
            <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-slate-800/80 p-1 rounded-xl border border-slate-200 dark:border-slate-800 text-xs">
                <input type="date" x-model="syncDateFrom" class="text-xs py-1.5 px-2 bg-transparent border-0 text-slate-700 dark:text-slate-200 focus:ring-0" title="Tanggal Mulai Sync">
                <span class="text-slate-400 text-[10px]">s/d</span>
                <input type="date" x-model="syncDateTo" class="text-xs py-1.5 px-2 bg-transparent border-0 text-slate-700 dark:text-slate-200 focus:ring-0" title="Tanggal Akhir Sync">
                <div class="flex items-center gap-1 pl-1 border-l border-slate-200 dark:border-slate-700">
                    <button type="button" @click="setSyncToday()" class="px-2 py-1 text-[10px] font-bold rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300">Hari Ini</button>
                    <button type="button" @click="setSyncYesterday()" class="px-2 py-1 text-[10px] font-bold rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300">Kemarin</button>
                </div>
            </div>

            {{-- Manual Sync Button --}}
            <button type="button" @click="syncOdoo()" :disabled="syncing"
                class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all disabled:opacity-50">
                <svg x-show="!syncing" class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <svg x-show="syncing" class="w-4 h-4 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span x-text="syncing ? 'Syncing...' : 'Sync Odoo'"></span>
            </button>

            {{-- Download PDF Button (Opens Preview Modal first) --}}
            <button type="button" @click="openPreview()" :disabled="selectedItems.length === 0"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-500/20 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Download PDF (<span x-text="selectedItems.length"></span>)</span>
            </button>
        </div>
    </div>

    {{-- ALERT TOAST --}}
    <template x-if="alertMessage">
        <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between"
             :class="alertType === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800'">
            <div class="flex items-center gap-2">
                <svg x-show="alertType === 'success'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <svg x-show="alertType === 'error'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <span x-text="alertMessage"></span>
            </div>
            <button @click="alertMessage = null" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
    </template>

    {{-- TABS NAVIGATION & QUICK ACTIONS --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-1">
        <div class="flex items-center gap-2">
            <button type="button" @click="switchTab('queue')"
                class="px-4 py-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2"
                :class="activeTab === 'queue' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <span>Antrean Cetak (Unprinted)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                      :class="totalUnprinted > 0 ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                    {{ $totalUnprinted }}
                </span>
            </button>

            <button type="button" @click="switchTab('history')"
                class="px-4 py-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2"
                :class="activeTab === 'history' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Riwayat Cetak (Print Logs)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500">
                    {{ $batches->total() }}
                </span>
            </button>
        </div>

        {{-- Selection Helper Buttons in Queue Tab --}}
        <div x-show="activeTab === 'queue' && totalUnprinted > 0" class="flex items-center gap-2 text-xs">
            <span class="text-slate-400 text-[11px]">Terpilih: <strong class="text-indigo-600 dark:text-indigo-400" x-text="selectedItems.length"></strong> dari {{ $totalUnprinted }} SO</span>
            <span class="text-slate-300 dark:text-slate-700">|</span>
            <button type="button" @click="toggleSelectAll(true)" class="text-[11px] font-semibold text-indigo-600 hover:underline">
                Pilih Semua
            </button>
            <span class="text-slate-300 dark:text-slate-700">|</span>
            <button type="button" @click="toggleSelectAll(false)" class="text-[11px] font-semibold text-slate-500 hover:text-slate-700">
                Kosongkan
            </button>
        </div>
    </div>

    {{-- TAB 1: UNPRINTED QUEUE --}}
    <div x-show="activeTab === 'queue'" class="space-y-6">

        @if($totalUnprinted === 0)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 mx-auto flex items-center justify-center mb-3">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Antrean Bersih: Semua SO Sudah Dicetak</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                Tidak ada data baru yang menunggu cetak. Dokumen yang sudah dicetak dapat di-download ulang di tab <strong>Riwayat Cetak</strong>.
            </p>
            <button type="button" @click="syncOdoo()"
                class="mt-4 inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span>Cek SO Baru dari Odoo</span>
            </button>
        </div>
        @else

        {{-- 1. SO CONFIRM SUBSCRIPTION --}}
        @if($grouped['reserved_subscription']->isNotEmpty())
        @php $subIds = $grouped['reserved_subscription']->pluck('id')->toArray(); @endphp
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="px-5 py-3.5 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <input type="checkbox"
                           @change="toggleSection({{ json_encode($subIds) }}, $event.target.checked)"
                           :checked="isSectionAllSelected({{ json_encode($subIds) }})"
                           class="w-4 h-4 text-indigo-600 rounded border-slate-300 dark:border-slate-700 focus:ring-indigo-500">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">1. SO Confirm Subscription</h2>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400">
                    {{ $grouped['reserved_subscription']->count() }} SO
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/30 text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800 text-[11px] uppercase">
                        <tr>
                            <th class="py-2.5 px-3 text-center w-10">Pilih</th>
                            <th class="py-2.5 px-3 font-semibold text-center w-10">No</th>
                            <th class="py-2.5 px-3 font-semibold">No SO</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Order Date</th>
                            <th class="py-2.5 px-3 font-semibold">Customer</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Duration (Bln)</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Actual Start</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Reserved Lot</th>
                            <th class="py-2.5 px-3 font-semibold text-center">RBO</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Previous SO</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Source Dokumen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($grouped['reserved_subscription'] as $item)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40" :class="selectedItems.includes({{ $item->id }}) ? 'bg-indigo-50/30 dark:bg-indigo-950/20' : ''">
                            <td class="py-2.5 px-3 text-center">
                                <input type="checkbox" :value="{{ $item->id }}" x-model="selectedItems"
                                    class="w-4 h-4 text-indigo-600 rounded border-slate-300 dark:border-slate-700 focus:ring-indigo-500">
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="py-2.5 px-3 font-bold text-indigo-600 dark:text-indigo-400">{{ $item->so_number }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">{{ $item->order_date ? $item->order_date->format('d/m/Y') : ($item->created_at ? $item->created_at->format('d/m/Y') : '-') }}</td>
                            <td class="py-2.5 px-3 font-semibold text-slate-800 dark:text-slate-200">{{ $item->customer_name }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500">{{ $item->duration_months ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">{{ $item->start_date ? $item->start_date->format('d/m/Y') : '-' }}</td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700 dark:text-slate-300">{{ $item->reserved_lot ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500">{{ $item->rbo ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500">{{ $item->previous_so ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500 text-[11px]">{{ $item->source_doc ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- 2. SO CONFIRM REGULER --}}
        @if($grouped['reserved_reguler']->isNotEmpty())
        @php $regIds = $grouped['reserved_reguler']->pluck('id')->toArray(); @endphp
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="px-5 py-3.5 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <input type="checkbox"
                           @change="toggleSection({{ json_encode($regIds) }}, $event.target.checked)"
                           :checked="isSectionAllSelected({{ json_encode($regIds) }})"
                           class="w-4 h-4 text-blue-600 rounded border-slate-300 dark:border-slate-700 focus:ring-blue-500">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">2. SO Confirm Reguler</h2>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400">
                    {{ $grouped['reserved_reguler']->count() }} SO
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/30 text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800 text-[11px] uppercase">
                        <tr>
                            <th class="py-2.5 px-3 text-center w-10">Pilih</th>
                            <th class="py-2.5 px-3 font-semibold text-center w-10">No</th>
                            <th class="py-2.5 px-3 font-semibold">No SO</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Order Date</th>
                            <th class="py-2.5 px-3 font-semibold">Customer</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Durasi (Jam / Hari)</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Actual Start</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Reserved Lot</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Source Dokumen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($grouped['reserved_reguler'] as $item)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40" :class="selectedItems.includes({{ $item->id }}) ? 'bg-blue-50/30 dark:bg-blue-950/20' : ''">
                            <td class="py-2.5 px-3 text-center">
                                <input type="checkbox" :value="{{ $item->id }}" x-model="selectedItems"
                                    class="w-4 h-4 text-blue-600 rounded border-slate-300 dark:border-slate-700 focus:ring-blue-500">
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="py-2.5 px-3 font-bold text-blue-600 dark:text-blue-400">{{ $item->so_number }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">{{ $item->order_date ? $item->order_date->format('d/m/Y') : ($item->created_at ? $item->created_at->format('d/m/Y') : '-') }}</td>
                            <td class="py-2.5 px-3 font-semibold text-slate-800 dark:text-slate-200">{{ $item->customer_name }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500">{{ $item->duration_months ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">{{ $item->start_date ? $item->start_date->format('d/m/Y') : '-' }}</td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700 dark:text-slate-300">{{ $item->reserved_lot ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500 text-[11px]">{{ $item->source_doc ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- 3. TERMINATE & FORCE CLOSED --}}
        @if(!empty($grouped['terminate_and_force_close']) && $grouped['terminate_and_force_close']->isNotEmpty())
        @php $termIds = $grouped['terminate_and_force_close']->pluck('id')->toArray(); @endphp
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="px-5 py-3.5 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <input type="checkbox"
                           @change="toggleSection({{ json_encode($termIds) }}, $event.target.checked)"
                           :checked="isSectionAllSelected({{ json_encode($termIds) }})"
                           class="w-4 h-4 text-rose-600 rounded border-slate-300 dark:border-slate-700 focus:ring-rose-500">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">3. Terminate &amp; Force Closed</h2>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400">
                    {{ $grouped['terminate_and_force_close']->count() }} SO
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/30 text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800 text-[11px] uppercase">
                        <tr>
                            <th class="py-2.5 px-3 text-center w-10">Pilih</th>
                            <th class="py-2.5 px-3 font-semibold text-center w-10">No</th>
                            <th class="py-2.5 px-3 font-semibold">No SO</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Update Date</th>
                            <th class="py-2.5 px-3 font-semibold">Customer</th>
                            <th class="py-2.5 px-3 font-semibold text-center">End Rental</th>
                            <th class="py-2.5 px-3 font-semibold text-center text-rose-600">Actual End</th>
                            <th class="py-2.5 px-3 font-semibold">Produk</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Reserved Lot</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Tahun</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Actual Sewa</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Sisa Sewa</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Source Dok</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        {{-- SUB-HEADER A: TERMINATE --}}
                        @if($grouped['terminate']->isNotEmpty())
                        <tr class="bg-rose-50/70 dark:bg-rose-950/40 border-y border-rose-200 dark:border-rose-900 font-bold text-rose-800 dark:text-rose-300">
                            <td colspan="13" class="py-2 px-3 text-[11px] tracking-wide">
                                ▶ TERMINATE ({{ $grouped['terminate']->count() }} SO)
                            </td>
                        </tr>
                        @foreach($grouped['terminate'] as $item)
                        @php
                            $actualMonths = ($item->start_date && $item->actual_end_rental_date)
                                ? max(0, (int) round(\Carbon\Carbon::parse($item->start_date)->diffInMonths(\Carbon\Carbon::parse($item->actual_end_rental_date)))) . ' Bln'
                                : '-';
                            $sisaMonths = ($item->actual_end_rental_date && $item->end_date)
                                ? max(0, (int) round(\Carbon\Carbon::parse($item->actual_end_rental_date)->diffInMonths(\Carbon\Carbon::parse($item->end_date)))) . ' Bln'
                                : '-';
                            preg_match('/20\d\d/', $item->product ?? '', $yearMatches);
                            $tahunKend = $item->vehicle_year ?? (!empty($yearMatches[0]) ? $yearMatches[0] : '-');
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40" :class="selectedItems.includes({{ $item->id }}) ? 'bg-rose-50/30 dark:bg-rose-950/20' : ''">
                            <td class="py-2.5 px-3 text-center">
                                <input type="checkbox" :value="{{ $item->id }}" x-model="selectedItems"
                                    class="w-4 h-4 text-rose-600 rounded border-slate-300 dark:border-slate-700 focus:ring-rose-500">
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="py-2.5 px-3 font-bold text-rose-600 dark:text-rose-400">{{ $item->so_number }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">
                                {{ $item->order_date ? $item->order_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 font-semibold text-slate-800 dark:text-slate-200">{{ $item->customer_name }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">{{ $item->end_date ? $item->end_date->format('d/m/Y') : '-' }}</td>
                            <td class="py-2.5 px-3 text-center font-bold text-rose-600 dark:text-rose-400 bg-rose-50/50 dark:bg-rose-950/20">
                                {{ $item->actual_end_rental_date ? $item->actual_end_rental_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $item->product ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700 dark:text-slate-300">{{ $item->reserved_lot ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500">{{ $tahunKend }}</td>
                            <td class="py-2.5 px-3 text-center font-semibold text-slate-700 dark:text-slate-300" title="{{ $item->start_date && $item->actual_end_rental_date ? $item->start_date->format('d/m/Y') . ' s.d ' . $item->actual_end_rental_date->format('d/m/Y') : '' }}">{{ $actualMonths }}</td>
                            <td class="py-2.5 px-3 text-center font-semibold text-slate-700 dark:text-slate-300" title="{{ $item->actual_end_rental_date && $item->end_date ? $item->actual_end_rental_date->format('d/m/Y') . ' s.d ' . $item->end_date->format('d/m/Y') : '' }}">{{ $sisaMonths }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500 text-[11px]">{{ $item->source_doc ?? '-' }}</td>
                        </tr>
                        @endforeach
                        @endif

                        {{-- SUB-HEADER B: FORCE CLOSED --}}
                        @if($grouped['force_close']->isNotEmpty())
                        <tr class="bg-amber-50/70 dark:bg-amber-950/40 border-y border-amber-200 dark:border-amber-900 font-bold text-amber-800 dark:text-amber-300">
                            <td colspan="13" class="py-2 px-3 text-[11px] tracking-wide">
                                ▶ FORCE CLOSED ({{ $grouped['force_close']->count() }} SO)
                            </td>
                        </tr>
                        @foreach($grouped['force_close'] as $item)
                        @php
                            $actualMonths = ($item->start_date && $item->actual_end_rental_date)
                                ? max(0, (int) round(\Carbon\Carbon::parse($item->start_date)->diffInMonths(\Carbon\Carbon::parse($item->actual_end_rental_date)))) . ' Bln'
                                : '-';
                            $sisaMonths = ($item->actual_end_rental_date && $item->end_date)
                                ? max(0, (int) round(\Carbon\Carbon::parse($item->actual_end_rental_date)->diffInMonths(\Carbon\Carbon::parse($item->end_date)))) . ' Bln'
                                : '-';
                            preg_match('/20\d\d/', $item->product ?? '', $yearMatches);
                            $tahunKend = $item->vehicle_year ?? (!empty($yearMatches[0]) ? $yearMatches[0] : '-');
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40" :class="selectedItems.includes({{ $item->id }}) ? 'bg-amber-50/30 dark:bg-amber-950/20' : ''">
                            <td class="py-2.5 px-3 text-center">
                                <input type="checkbox" :value="{{ $item->id }}" x-model="selectedItems"
                                    class="w-4 h-4 text-amber-600 rounded border-slate-300 dark:border-slate-700 focus:ring-amber-500">
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="py-2.5 px-3 font-bold text-amber-600 dark:text-amber-400">{{ $item->so_number }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">
                                {{ $item->order_date ? $item->order_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 font-semibold text-slate-800 dark:text-slate-200">{{ $item->customer_name }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">{{ $item->end_date ? $item->end_date->format('d/m/Y') : '-' }}</td>
                            <td class="py-2.5 px-3 text-center font-bold text-amber-600 dark:text-amber-400 bg-amber-50/50 dark:bg-amber-950/20">
                                {{ $item->actual_end_rental_date ? $item->actual_end_rental_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $item->product ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700 dark:text-slate-300">{{ $item->reserved_lot ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500">{{ $tahunKend }}</td>
                            <td class="py-2.5 px-3 text-center font-semibold text-slate-700 dark:text-slate-300" title="{{ $item->start_date && $item->actual_end_rental_date ? $item->start_date->format('d/m/Y') . ' s.d ' . $item->actual_end_rental_date->format('d/m/Y') : '' }}">{{ $actualMonths }}</td>
                            <td class="py-2.5 px-3 text-center font-semibold text-slate-700 dark:text-slate-300" title="{{ $item->actual_end_rental_date && $item->end_date ? $item->actual_end_rental_date->format('d/m/Y') . ' s.d ' . $item->end_date->format('d/m/Y') : '' }}">{{ $sisaMonths }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500 text-[11px]">{{ $item->source_doc ?? '-' }}</td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- 4. EXTEND & UPDATE PRICE --}}
        @if(!empty($grouped['extend_and_update_price']) && $grouped['extend_and_update_price']->isNotEmpty())
        @php $extPriceIds = $grouped['extend_and_update_price']->pluck('id')->toArray(); @endphp
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="px-5 py-3.5 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <input type="checkbox"
                           @change="toggleSection({{ json_encode($extPriceIds) }}, $event.target.checked)"
                           :checked="isSectionAllSelected({{ json_encode($extPriceIds) }})"
                           class="w-4 h-4 text-emerald-600 rounded border-slate-300 dark:border-slate-700 focus:ring-emerald-500">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">4. Extend &amp; Update Price</h2>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400">
                    {{ $grouped['extend_and_update_price']->count() }} SO
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/30 text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800 text-[11px] uppercase">
                        <tr>
                            <th class="py-2.5 px-3 text-center w-10">Pilih</th>
                            <th class="py-2.5 px-3 font-semibold text-center w-10">No</th>
                            <th class="py-2.5 px-3 font-semibold">No SO</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Update Date</th>
                            <th class="py-2.5 px-3 font-semibold">Customer</th>
                            <th class="py-2.5 px-3 font-semibold text-center text-emerald-600">Actual End</th>
                            <th class="py-2.5 px-3 font-semibold text-center text-slate-400">Tgl End Sblmnya</th>
                            <th class="py-2.5 px-3 font-semibold text-right text-emerald-600">Duration Price</th>
                            <th class="py-2.5 px-3 font-semibold text-right text-slate-400">Duration Price Sblmnya</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Reserved Lot</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Source Dokumen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        {{-- SUB-HEADER A: EXTEND ONLY --}}
                        @if($grouped['extend_only']->isNotEmpty())
                        <tr class="bg-emerald-50/70 dark:bg-emerald-950/40 border-y border-emerald-200 dark:border-emerald-900 font-bold text-emerald-800 dark:text-emerald-300">
                            <td colspan="11" class="py-2 px-3 text-[11px] tracking-wide">
                                ▶ EXTEND ONLY (Perpanjangan Saja) ({{ $grouped['extend_only']->count() }} SO)
                            </td>
                        </tr>
                        @foreach($grouped['extend_only'] as $item)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40" :class="selectedItems.includes({{ $item->id }}) ? 'bg-emerald-50/30 dark:bg-emerald-950/20' : ''">
                            <td class="py-2.5 px-3 text-center">
                                <input type="checkbox" :value="{{ $item->id }}" x-model="selectedItems"
                                    class="w-4 h-4 text-emerald-600 rounded border-slate-300 dark:border-slate-700 focus:ring-emerald-500">
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="py-2.5 px-3 font-bold text-emerald-600 dark:text-emerald-400">{{ $item->so_number }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">
                                {{ $item->order_date ? $item->order_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 font-semibold text-slate-800 dark:text-slate-200">{{ $item->customer_name }}</td>
                            <td class="py-2.5 px-3 text-center font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-950/20">
                                {{ $item->end_date ? $item->end_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-400 line-through">
                                {{ $item->previous_end_date ? $item->previous_end_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $item->duration_price ? 'Rp ' . number_format($item->duration_price, 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-400">
                                @if($item->previous_duration_price && (float)$item->previous_duration_price !== (float)$item->duration_price)
                                    Rp {{ number_format($item->previous_duration_price, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700 dark:text-slate-300">{{ $item->reserved_lot ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500 text-[11px]">{{ $item->source_doc ?? '-' }}</td>
                        </tr>
                        @endforeach
                        @endif

                        {{-- SUB-HEADER B: UPDATE PRICE ONLY --}}
                        @if($grouped['update_price_only']->isNotEmpty())
                        <tr class="bg-sky-50/70 dark:bg-sky-950/40 border-y border-sky-200 dark:border-sky-900 font-bold text-sky-800 dark:text-sky-300">
                            <td colspan="11" class="py-2 px-3 text-[11px] tracking-wide">
                                ▶ UPDATE PRICE ONLY (Perubahan Harga Saja) ({{ $grouped['update_price_only']->count() }} SO)
                            </td>
                        </tr>
                        @foreach($grouped['update_price_only'] as $item)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40" :class="selectedItems.includes({{ $item->id }}) ? 'bg-sky-50/30 dark:bg-sky-950/20' : ''">
                            <td class="py-2.5 px-3 text-center">
                                <input type="checkbox" :value="{{ $item->id }}" x-model="selectedItems"
                                    class="w-4 h-4 text-sky-600 rounded border-slate-300 dark:border-slate-700 focus:ring-sky-500">
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="py-2.5 px-3 font-bold text-sky-600 dark:text-sky-400">{{ $item->so_number }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">
                                {{ $item->order_date ? $item->order_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 font-semibold text-slate-800 dark:text-slate-200">{{ $item->customer_name }}</td>
                            <td class="py-2.5 px-3 text-center font-bold text-sky-600 dark:text-sky-400 bg-sky-50/50 dark:bg-sky-950/20">
                                {{ $item->end_date ? $item->end_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-400">
                                {{ $item->previous_end_date ? $item->previous_end_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-sky-600 dark:text-sky-400">
                                {{ $item->duration_price ? 'Rp ' . number_format($item->duration_price, 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-400">
                                @if($item->previous_duration_price && (float)$item->previous_duration_price !== (float)$item->duration_price)
                                    Rp {{ number_format($item->previous_duration_price, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700 dark:text-slate-300">{{ $item->reserved_lot ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500 text-[11px]">{{ $item->source_doc ?? '-' }}</td>
                        </tr>
                        @endforeach
                        @endif

                        {{-- SUB-HEADER C: EXTEND & UPDATE PRICE --}}
                        @if($grouped['extend_and_update_both']->isNotEmpty())
                        <tr class="bg-teal-50/70 dark:bg-teal-950/40 border-y border-teal-200 dark:border-teal-900 font-bold text-teal-800 dark:text-teal-300">
                            <td colspan="11" class="py-2 px-3 text-[11px] tracking-wide">
                                ▶ EXTEND &amp; UPDATE PRICE (Perpanjangan &amp; Perubahan Harga) ({{ $grouped['extend_and_update_both']->count() }} SO)
                            </td>
                        </tr>
                        @foreach($grouped['extend_and_update_both'] as $item)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40" :class="selectedItems.includes({{ $item->id }}) ? 'bg-teal-50/30 dark:bg-teal-950/20' : ''">
                            <td class="py-2.5 px-3 text-center">
                                <input type="checkbox" :value="{{ $item->id }}" x-model="selectedItems"
                                    class="w-4 h-4 text-teal-600 rounded border-slate-300 dark:border-slate-700 focus:ring-teal-500">
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="py-2.5 px-3 font-bold text-teal-600 dark:text-teal-400">{{ $item->so_number }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">
                                {{ $item->order_date ? $item->order_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 font-semibold text-slate-800 dark:text-slate-200">{{ $item->customer_name }}</td>
                            <td class="py-2.5 px-3 text-center font-bold text-teal-600 dark:text-teal-400 bg-teal-50/50 dark:bg-teal-950/20">
                                {{ $item->end_date ? $item->end_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-400 line-through">
                                {{ $item->previous_end_date ? $item->previous_end_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-teal-600 dark:text-teal-400">
                                {{ $item->duration_price ? 'Rp ' . number_format($item->duration_price, 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-400">
                                @if($item->previous_duration_price && (float)$item->previous_duration_price !== (float)$item->duration_price)
                                    Rp {{ number_format($item->previous_duration_price, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700 dark:text-slate-300">{{ $item->reserved_lot ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500 text-[11px]">{{ $item->source_doc ?? '-' }}</td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- 5. SWITCH CUSTOMER --}}
        @if($grouped['switch_customer']->isNotEmpty())
        @php $switchIds = $grouped['switch_customer']->pluck('id')->toArray(); @endphp
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="px-5 py-3.5 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <input type="checkbox"
                           @change="toggleSection({{ json_encode($switchIds) }}, $event.target.checked)"
                           :checked="isSectionAllSelected({{ json_encode($switchIds) }})"
                           class="w-4 h-4 text-purple-600 rounded border-slate-300 dark:border-slate-700 focus:ring-purple-500">
                    <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">5. Switch Customer</h2>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 dark:bg-purple-950 text-purple-600 dark:text-purple-400">
                    {{ $grouped['switch_customer']->count() }} SO
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/30 text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800 text-[11px] uppercase">
                        <tr>
                            <th class="py-2.5 px-3 text-center w-10">Pilih</th>
                            <th class="py-2.5 px-3 font-semibold text-center w-10">No</th>
                            <th class="py-2.5 px-3 font-semibold">No SO</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Update Date</th>
                            <th class="py-2.5 px-3 font-semibold">Customer</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Tgl Start</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Tgl End</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Reserved Lot</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Superseeded SO</th>
                            <th class="py-2.5 px-3 font-semibold">Previous Customer</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Tgl End Prev Cust</th>
                            <th class="py-2.5 px-3 font-semibold text-center">Source Dokumen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($grouped['switch_customer'] as $item)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40" :class="selectedItems.includes({{ $item->id }}) ? 'bg-purple-50/30 dark:bg-purple-950/20' : ''">
                            <td class="py-2.5 px-3 text-center">
                                <input type="checkbox" :value="{{ $item->id }}" x-model="selectedItems"
                                    class="w-4 h-4 text-purple-600 rounded border-slate-300 dark:border-slate-700 focus:ring-purple-500">
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="py-2.5 px-3 font-bold text-purple-600 dark:text-purple-400">{{ $item->so_number }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">{{ $item->order_date ? $item->order_date->format('d/m/Y') : '-' }}</td>
                            <td class="py-2.5 px-3 font-semibold text-slate-800 dark:text-slate-200">{{ $item->customer_name }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">{{ $item->start_date ? $item->start_date->format('d/m/Y') : '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">{{ $item->end_date ? $item->end_date->format('d/m/Y') : '-' }}</td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700 dark:text-slate-300">{{ $item->reserved_lot ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center font-bold text-slate-700 dark:text-slate-300">{{ $item->previous_so ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-slate-500">{{ $item->previous_customer ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500">{{ $item->previous_end_date_cust ? $item->previous_end_date_cust->format('d/m/Y') : '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500 text-[11px]">{{ $item->source_doc ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @endif
    </div>

    {{-- TAB 2: PRINT HISTORY & LOGS --}}
    <div x-show="activeTab === 'history'" class="space-y-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="px-5 py-3.5 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">Arsip &amp; Riwayat Cetak Resmi (Print Logs)</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">Semua dokumen batch yang sudah dicetak. Cetak ulang otomatis bertanda DUPLIKAT.</p>
                </div>
                <span class="text-xs text-slate-400">Total: <strong>{{ $batches->total() }}</strong> Dokumen Batch</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/30 text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800 text-[11px] uppercase">
                        <tr>
                            <th class="py-3 px-4 font-semibold">No Batch</th>
                            <th class="py-3 px-4 font-semibold">Tim / Sales Manager</th>
                            <th class="py-3 px-4 font-semibold text-center">Waktu Cetak Pertama</th>
                            <th class="py-3 px-4 font-semibold">Dicetak Oleh</th>
                            <th class="py-3 px-4 font-semibold text-center">Total SO</th>
                            <th class="py-3 px-4 font-semibold text-center">Status Cetakan</th>
                            <th class="py-3 px-4 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($batches as $batch)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                            <td class="py-3 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $batch->batch_no }}
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">
                                {{ $batch->sales_team ?? 'ALL TEAMS' }}
                            </td>
                            <td class="py-3 px-4 text-center text-slate-600 dark:text-slate-400">
                                {{ $batch->printed_at ? $batch->printed_at->format('d/m/Y H:i') : '-' }} WIB
                            </td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                                {{ $batch->printed_by_name ?? ($batch->printedBy->name ?? '-') }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400">
                                    {{ $batch->total_so }} SO
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if(($batch->print_count ?? 1) > 1)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                                        <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2"></path></svg>
                                        Duplikat {{ $batch->print_count - 1 }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                        <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        Asli (Original)
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button"
                                            @click="openBatchPreview('{{ route('lor.list-so.batch.preview', $batch->id) }}', '{{ $batch->batch_no }}', '{{ $batch->id }}', {{ $batch->total_so }}, '{{ route('lor.list-so.batch.reset', $batch->id) }}', '{{ route('lor.list-so.batch.download', $batch->id) }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 font-semibold text-xs transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        <span>Preview Dokumen</span>
                                    </button>

                                    @if(auth()->user()->canViewListSo() || auth()->user()->isItAdmin())
                                    <button type="button"
                                            @click="openResetModal('{{ $batch->batch_no }}', {{ $batch->total_so }}, '{{ route('lor.list-so.batch.reset', $batch->id) }}')"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 font-semibold text-xs transition-colors"
                                            title="Reset Batch ke Antrean Cetak">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        <span>Reset Batch</span>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                Belum ada riwayat cetak batch.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($batches->hasPages())
            <div class="p-4 border-t border-slate-200/80 dark:border-slate-800">
                {{ $batches->appends(['tab' => 'history', 'sales_manager' => $selectedManager])->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- LIVE PREVIEW & CONFIRMATION MODAL                        --}}
    {{-- ======================================================== --}}
    <div x-show="showPreviewModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-900/70 backdrop-blur-sm transition-opacity"
         @keydown.escape.window="showPreviewModal = false">
        
        <div class="bg-white dark:bg-slate-900 w-full max-w-6xl rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[92vh] overflow-hidden">
            
            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-slate-50/60 dark:bg-slate-800/40">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Live Preview Dokumen PDF Resmi</h3>
                        <p class="text-[11px] text-slate-500">Pratinjau dokumen sebelum dicetak dan masuk ke log arsip</p>
                    </div>
                </div>
                <button type="button" @click="showPreviewModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold p-1">
                    &times;
                </button>
            </div>

            {{-- Modal Body: PDF Live Preview in iframe --}}
            <div class="flex-1 p-2 sm:p-4 bg-slate-100 dark:bg-slate-950 overflow-hidden flex flex-col">
                <template x-if="showPreviewModal">
                    <iframe :src="previewUrl" class="w-full h-[68vh] rounded-xl border border-slate-200 dark:border-slate-800 shadow-inner bg-white"></iframe>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-4 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-slate-900">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Akan dicetak: <strong class="text-slate-800 dark:text-slate-100"><span x-text="selectedItems.length"></span> SO Terpilih</strong></span>
                    <span class="text-slate-300 dark:text-slate-700">|</span>
                    <span>Sales Manager: <strong class="text-indigo-600 dark:text-indigo-400">{{ $selectedManager ? ($salesManagers[$selectedManager] ?? $selectedManager) : 'Semua Tim' }}</strong></span>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" @click="showPreviewModal = false"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors">
                        Tutup / Batal
                    </button>
                    
                    <form method="POST" action="{{ route('lor.list-so.print') }}" @submit.prevent="executeDownload($event)">
                        @csrf
                        <input type="hidden" name="sales_manager" value="{{ $selectedManager }}">
                        <input type="hidden" name="item_ids" :value="selectedItems.join(',')">
                        <button type="submit" :disabled="downloading || selectedItems.length === 0"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-500/20 transition-all disabled:opacity-50">
                            <svg x-show="!downloading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            <svg x-show="downloading" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span x-text="downloading ? 'Memproses PDF...' : 'Download PDF (' + selectedItems.length + ')'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- BATCH HISTORY PREVIEW MODAL                              --}}
    {{-- ======================================================== --}}
    <div x-show="showBatchPreviewModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-900/70 backdrop-blur-sm transition-opacity"
         @keydown.escape.window="showBatchPreviewModal = false">
        
        <div class="bg-white dark:bg-slate-900 w-full max-w-6xl rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[92vh] overflow-hidden">
            
            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-slate-50/60 dark:bg-slate-800/40">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <span>Pratinjau Dokumen Batch:</span>
                            <span class="font-mono text-indigo-600 dark:text-indigo-400" x-text="batchPreviewNo"></span>
                        </h3>
                        <p class="text-[11px] text-slate-500">Periksa isi dokumen batch sebelum memutuskan untuk me-reset atau mencetak ulang</p>
                    </div>
                </div>
                <button type="button" @click="showBatchPreviewModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold p-1">
                    &times;
                </button>
            </div>

            {{-- Modal Body: Batch Preview in iframe --}}
            <div class="flex-1 p-2 sm:p-4 bg-slate-100 dark:bg-slate-950 overflow-hidden flex flex-col">
                <template x-if="showBatchPreviewModal">
                    <iframe :src="batchPreviewUrl" class="w-full h-[68vh] rounded-xl border border-slate-200 dark:border-slate-800 shadow-inner bg-white"></iframe>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-4 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-slate-900">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <span>Total Isi: <strong class="text-slate-800 dark:text-slate-100"><span x-text="batchPreviewTotalSo"></span> SO</strong></span>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" @click="showBatchPreviewModal = false"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors">
                        Tutup
                    </button>
                    
                    <a :href="batchPreviewDownloadUrl"
                       class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 font-semibold text-xs transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span>Unduh PDF</span>
                    </a>

                    <button type="button" @click="openResetFromPreview()"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md shadow-rose-500/20 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span>Reset Batch Ini</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- RESET BATCH CONFIRMATION GUARD MODAL                     --}}
    {{-- ======================================================== --}}
    <div x-show="showResetModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm transition-opacity"
         @keydown.escape.window="showResetModal = false">
        
        <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 flex flex-col gap-4"
             @click.away="showResetModal = false">
            
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950 flex items-center justify-center text-rose-600 dark:text-rose-400 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">Konfirmasi Reset Batch</h3>
                    <p class="text-xs text-slate-500 font-mono mt-0.5" x-text="resetBatchNo"></p>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-rose-50/70 dark:bg-rose-950/30 border border-rose-200/60 dark:border-rose-900/40 text-xs text-rose-900 dark:text-rose-200 space-y-1.5">
                <div class="font-bold flex items-center gap-1.5 text-rose-700 dark:text-rose-300">
                    <span>Apakah Anda yakin ingin me-reset batch ini?</span>
                </div>
                <ul class="list-disc pl-4 space-y-1 text-[11px] leading-relaxed text-slate-600 dark:text-slate-300">
                    <li>Seluruh <strong><span x-text="resetBatchTotalSo"></span> SO</strong> di batch ini akan <strong>dikembalikan ke Antrean Cetak (Unprinted)</strong>.</li>
                    <li>Dokumen batch ini akan <strong>dihapus</strong> dari Riwayat Cetak.</li>
                    <li>Setelah di-reset, Anda dapat melakukan <strong>Sync Odoo ulang</strong> atau menambahkan SO baru sebelum mencetak kembali.</li>
                </ul>
            </div>

            <label class="flex items-start gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 cursor-pointer select-none text-xs text-slate-700 dark:text-slate-300">
                <input type="checkbox" x-model="resetConfirmed"
                       class="mt-0.5 w-4 h-4 text-rose-600 rounded border-slate-300 dark:border-slate-600 focus:ring-rose-500">
                <span class="text-[11px] leading-snug">
                    Saya sudah memeriksa isi dokumen batch ini dan yakin ingin me-reset seluruh SO ke antrean cetak.
                </span>
            </label>

            <form method="POST" :action="resetBatchActionUrl" class="flex items-center justify-end gap-3 pt-2">
                @csrf
                <button type="button" @click="showResetModal = false"
                        class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors">
                    Batal
                </button>
                <button type="submit" :disabled="!resetConfirmed"
                        class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md shadow-rose-500/20 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    <span>Ya, Reset Batch</span>
                </button>
            </form>
        </div>
    </div>

</div>

<script>
function listSoApp() {
    const initialIds = [
        @foreach($grouped['all_items'] as $it)
            {{ $it->id }},
        @endforeach
    ];

    return {
        activeTab: '{{ $activeTab }}',
        totalUnprinted: {{ $totalUnprinted }},
        syncing: false,
        downloading: false,
        syncDateFrom: '{{ date('Y-m-d') }}',
        syncDateTo: '{{ date('Y-m-d') }}',
        alertMessage: null,
        alertType: 'success',
        showPreviewModal: false,
        previewUrl: '',
        selectedItems: [...initialIds],
        allItemIds: [...initialIds],

        // Batch History Preview
        showBatchPreviewModal: false,
        batchPreviewUrl: '',
        batchPreviewNo: '',
        batchPreviewId: null,
        batchPreviewTotalSo: 0,
        batchPreviewResetUrl: '',
        batchPreviewDownloadUrl: '',

        // Reset Batch Guard Modal
        showResetModal: false,
        resetBatchNo: '',
        resetBatchTotalSo: 0,
        resetBatchActionUrl: '',
        resetConfirmed: false,

        openBatchPreview(previewUrl, batchNo, batchId, totalSo, resetUrl, downloadUrl) {
            this.batchPreviewUrl = previewUrl + '?t=' + Date.now();
            this.batchPreviewNo = batchNo;
            this.batchPreviewId = batchId;
            this.batchPreviewTotalSo = totalSo;
            this.batchPreviewResetUrl = resetUrl;
            this.batchPreviewDownloadUrl = downloadUrl;
            this.showBatchPreviewModal = true;
        },

        openResetModal(batchNo, totalSo, actionUrl) {
            this.resetBatchNo = batchNo;
            this.resetBatchTotalSo = totalSo;
            this.resetBatchActionUrl = actionUrl;
            this.resetConfirmed = false;
            this.showResetModal = true;
        },

        openResetFromPreview() {
            this.showBatchPreviewModal = false;
            this.openResetModal(this.batchPreviewNo, this.batchPreviewTotalSo, this.batchPreviewResetUrl);
        },

        setSyncToday() {
            const today = '{{ date('Y-m-d') }}';
            this.syncDateFrom = today;
            this.syncDateTo = today;
        },

        setSyncYesterday() {
            const y = new Date();
            y.setDate(y.getDate() - 1);
            const pad = (n) => String(n).padStart(2, '0');
            const yesterday = `${y.getFullYear()}-${pad(y.getMonth() + 1)}-${pad(y.getDate())}`;
            this.syncDateFrom = yesterday;
            this.syncDateTo = yesterday;
        },

        switchTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location.href);
            if (tab === 'queue') {
                url.searchParams.delete('tab');
            } else {
                url.searchParams.set('tab', tab);
            }
            window.history.replaceState({}, '', url.pathname + (url.search ? url.search : ''));
        },

        isAllSelected() {
            return this.allItemIds.length > 0 && this.selectedItems.length === this.allItemIds.length;
        },

        toggleSelectAll(checked) {
            if (checked) {
                this.selectedItems = [...this.allItemIds];
            } else {
                this.selectedItems = [];
            }
        },

        toggleSection(sectionIds, checked) {
            if (checked) {
                const set = new Set(this.selectedItems);
                sectionIds.forEach(id => set.add(id));
                this.selectedItems = Array.from(set);
            } else {
                const set = new Set(sectionIds);
                this.selectedItems = this.selectedItems.filter(id => !set.has(id));
            }
        },

        isSectionAllSelected(sectionIds) {
            if (!sectionIds || sectionIds.length === 0) return false;
            return sectionIds.every(id => this.selectedItems.includes(id));
        },

        openPreview() {
            if (this.selectedItems.length === 0) {
                alert('Pilih minimal 1 baris SO untuk dicetak.');
                return;
            }
            const baseUrl = "{{ route('lor.list-so.preview') }}";
            const params = new URLSearchParams({
                sales_manager: '{{ $selectedManager }}',
                item_ids: this.selectedItems.join(','),
                t: Date.now()
            });
            this.previewUrl = `${baseUrl}?${params.toString()}#toolbar=0&navpanes=0`;
            this.showPreviewModal = true;
        },

        async executeDownload(e) {
            this.downloading = true;
            this.alertMessage = null;

            const form = e.target;
            const formData = new FormData(form);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/pdf, application/json, text/html'
                    }
                });

                if (!response.ok) {
                    throw new Error('Gagal mencetak dokumen (Server Error ' + response.status + ')');
                }

                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/pdf')) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;

                    const disposition = response.headers.get('content-disposition');
                    let filename = 'LIST_SO.pdf';
                    if (disposition && disposition.indexOf('filename=') !== -1) {
                        const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                        if (matches != null && matches[1]) {
                            filename = matches[1].replace(/['"]/g, '');
                        }
                    }
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();
                }

                this.showPreviewModal = false;
                window.location.href = "{{ route('lor.list-so') }}{{ $selectedManager ? '?sales_manager=' . $selectedManager : '' }}";
            } catch (err) {
                this.alertType = 'error';
                this.alertMessage = err.message || 'Terjadi kesalahan saat mengunduh PDF.';
            } finally {
                this.downloading = false;
            }
        },

        async syncOdoo() {
            this.syncing = true;
            this.alertMessage = null;

            try {
                const response = await fetch("{{ route('lor.list-so.sync') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        sales_manager: '{{ $selectedManager }}',
                        date_from: this.syncDateFrom,
                        date_to: this.syncDateTo,
                        target_date: this.syncDateFrom
                    })
                });

                const data = await response.json();
                if (data.status === 'success') {
                    this.alertType = 'success';
                    this.alertMessage = data.message;
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.alertType = 'error';
                    this.alertMessage = data.message || 'Sync failed.';
                }
            } catch (err) {
                this.alertType = 'error';
                this.alertMessage = 'Error connecting to server: ' + err.message;
            } finally {
                this.syncing = false;
            }
        }
    }
}
</script>
@endsection
