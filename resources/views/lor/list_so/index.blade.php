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
            <button type="button" @click="activeTab = 'queue'"
                class="px-4 py-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2"
                :class="activeTab === 'queue' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <span>Antrean Cetak (Unprinted)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                      :class="totalUnprinted > 0 ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                    {{ $totalUnprinted }}
                </span>
            </button>

            <button type="button" @click="activeTab = 'history'"
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
        @if($grouped['terminate']->isNotEmpty())
        @php $termIds = $grouped['terminate']->pluck('id')->toArray(); @endphp
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
                    {{ $grouped['terminate']->count() }} SO
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
                        @foreach($grouped['terminate'] as $item)
                        @php
                            $actualMonths = ($item->start_date && $item->actual_end_rental_date)
                                ? max(0, \Carbon\Carbon::parse($item->start_date)->diffInMonths(\Carbon\Carbon::parse($item->actual_end_rental_date))) . ' Bln'
                                : '-';
                            $sisaMonths = ($item->actual_end_rental_date && $item->end_date)
                                ? max(0, \Carbon\Carbon::parse($item->actual_end_rental_date)->diffInMonths(\Carbon\Carbon::parse($item->end_date))) . ' Bln'
                                : '-';
                            preg_match('/20\d\d/', $item->product ?? '', $yearMatches);
                            $tahunKend = !empty($yearMatches[0]) ? $yearMatches[0] : '-';
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40" :class="selectedItems.includes({{ $item->id }}) ? 'bg-rose-50/30 dark:bg-rose-950/20' : ''">
                            <td class="py-2.5 px-3 text-center">
                                <input type="checkbox" :value="{{ $item->id }}" x-model="selectedItems"
                                    class="w-4 h-4 text-rose-600 rounded border-slate-300 dark:border-slate-700 focus:ring-rose-500">
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="py-2.5 px-3 font-bold text-rose-600 dark:text-rose-400">{{ $item->so_number }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">
                                {{ $item->actual_end_rental_date ? $item->actual_end_rental_date->format('d/m/Y') : ($item->updated_at ? $item->updated_at->format('d/m/Y') : '-') }}
                            </td>
                            <td class="py-2.5 px-3 font-semibold text-slate-800 dark:text-slate-200">{{ $item->customer_name }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">{{ $item->end_date ? $item->end_date->format('d/m/Y') : '-' }}</td>
                            <td class="py-2.5 px-3 text-center font-bold text-rose-600 dark:text-rose-400 bg-rose-50/50 dark:bg-rose-950/20">
                                {{ $item->actual_end_rental_date ? $item->actual_end_rental_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $item->product ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700 dark:text-slate-300">{{ $item->reserved_lot ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500">{{ $tahunKend }}</td>
                            <td class="py-2.5 px-3 text-center font-semibold text-slate-700 dark:text-slate-300">{{ $actualMonths }}</td>
                            <td class="py-2.5 px-3 text-center font-semibold text-slate-700 dark:text-slate-300">{{ $sisaMonths }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500 text-[11px]">{{ $item->source_doc ?? '-' }}</td>
                        </tr>
                        @endforeach
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($grouped['extend_and_update_price'] as $item)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40" :class="selectedItems.includes({{ $item->id }}) ? 'bg-emerald-50/30 dark:bg-emerald-950/20' : ''">
                            <td class="py-2.5 px-3 text-center">
                                <input type="checkbox" :value="{{ $item->id }}" x-model="selectedItems"
                                    class="w-4 h-4 text-emerald-600 rounded border-slate-300 dark:border-slate-700 focus:ring-emerald-500">
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="py-2.5 px-3 font-bold text-emerald-600 dark:text-emerald-400">{{ $item->so_number }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">{{ $item->updated_at ? $item->updated_at->format('d/m/Y') : '-' }}</td>
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
                                @if($item->previous_duration_price)
                                    Rp {{ number_format($item->previous_duration_price, 0, ',', '.') }}
                                    @if((float)$item->previous_duration_price === (float)$item->duration_price)
                                        <span class="text-[10px] text-slate-400 font-normal italic block">(Tetap)</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700 dark:text-slate-300">{{ $item->reserved_lot ?? '-' }}</td>
                        </tr>
                        @endforeach
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
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">{{ $item->updated_at ? $item->updated_at->format('d/m/Y') : '-' }}</td>
                            <td class="py-2.5 px-3 font-semibold text-slate-800 dark:text-slate-200">{{ $item->customer_name }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">{{ $item->start_date ? $item->start_date->format('d/m/Y') : '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-400">{{ $item->end_date ? $item->end_date->format('d/m/Y') : '-' }}</td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700 dark:text-slate-300">{{ $item->reserved_lot ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center font-bold text-slate-700 dark:text-slate-300">{{ $item->previous_so ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-slate-500">{{ $item->previous_customer ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-500">{{ $item->previous_end_date_cust ? $item->previous_end_date_cust->format('d/m/Y') : '-' }}</td>
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
                                <a href="{{ route('lor.list-so.batch.download', $batch->id) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 font-semibold text-xs transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    @if(($batch->print_count ?? 1) <= 0)
                                        <span>Cetak Ulang (Asli)</span>
                                    @else
                                        <span>Cetak Ulang (Duplikat {{ $batch->print_count ?? 1 }})</span>
                                    @endif
                                </a>
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
                    
                    <form method="POST" action="{{ route('lor.list-so.print') }}" @submit="executeDownload()">
                        @csrf
                        <input type="hidden" name="sales_manager" value="{{ $selectedManager }}">
                        <input type="hidden" name="item_ids" :value="selectedItems.join(',')">
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-500/20 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            <span>Download PDF (<span x-text="selectedItems.length"></span>)</span>
                        </button>
                    </form>
                </div>
            </div>
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
        alertMessage: null,
        alertType: 'success',
        showPreviewModal: false,
        previewUrl: '',
        selectedItems: [...initialIds],
        allItemIds: [...initialIds],

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

        executeDownload() {
            this.showPreviewModal = false;
            setTimeout(() => {
                window.location.href = "{{ route('lor.list-so') }}?tab=history&sales_manager={{ $selectedManager }}";
            }, 2500);
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
                        target_date: '{{ date('Y-m-d') }}'
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
