@extends('layouts.app')

@section('title', 'Stock Nasional - Laporan Harian Jumlah Mobil')

@section('content')
@php
    $totals = $report['totals'] ?? [];
    $emptyAudit = $report['empty_audit'] ?? [];
    $buckets = $report['buckets'] ?? [];
    $emptyBuckets = $emptyAudit['buckets'] ?? [];
    $reportDate = $report['report_date_formatted'] ?? date('d F Y');
    $fmtInt = fn($v) => number_format((int)$v, 0, ',', '.');
    $fmtPct = fn($v) => number_format((float)$v, 2, ',', '.') . '%';

    // Deduplicate all units into a single pool so JSON payload is tiny (~900KB instead of 7.5MB)
    $unitsPool = [];
    $idToIdx = [];
    $bucketIndices = [];

    $registerBucket = function (string $key, array $list) use (&$unitsPool, &$idToIdx, &$bucketIndices) {
        $indices = [];
        foreach ($list as $item) {
            $uid = $item['id'] ?? ($item['lot_number'] . '|' . $item['location']);
            if (!isset($idToIdx[$uid])) {
                $idToIdx[$uid] = count($unitsPool);
                $unitsPool[] = $item;
            }
            $indices[] = $idToIdx[$uid];
        }
        $bucketIndices[$key] = $indices;
    };

    foreach ($buckets as $bk => $blist) {
        $registerBucket($bk, $blist);
    }
    foreach ($emptyBuckets as $ek => $elist) {
        $registerBucket('empty_' . $ek, $elist);
    }
@endphp

<div class="space-y-6" x-data="stockNasionalApp()">

    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white dark:bg-slate-900/90 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-lg">
        <div class="flex items-start sm:items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-cyan-500/15 to-indigo-500/15 dark:from-cyan-500/20 dark:to-indigo-500/20 border border-cyan-500/25 flex items-center justify-center text-cyan-600 dark:text-cyan-400 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Stock Nasional</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-100 text-cyan-800 dark:bg-cyan-950/80 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800">
                        🌐 Nationwide Scope
                    </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">
                    Laporan Harian Jumlah Mobil (Per Tanggal <strong class="text-slate-700 dark:text-slate-200">{{ $reportDate }}</strong>)
                    @if(!empty($report['fetched_at']))
                        • Synced {{ \Carbon\Carbon::parse($report['fetched_at'])->format('d M Y H:i') }} WIB
                    @endif
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('stock-nasional', ['refresh' => 1]) }}"
               @click="isRefreshing = true"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all shadow-sm">
                <svg class="w-4 h-4" :class="isRefreshing ? 'animate-spin text-indigo-500' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span x-text="isRefreshing ? 'Refreshing...' : 'Refresh Odoo'"></span>
            </a>

            <a href="{{ route('stock-nasional.export-excel') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-md shadow-emerald-600/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Export Excel</span>
            </a>

            <a href="{{ route('stock-nasional.export-pdf') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-all shadow-md shadow-rose-600/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <span>Export PDF</span>
            </a>
        </div>
    </div>

    @if(!($report['success'] ?? false))
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-sm font-semibold">
            Failed to load Stock Nasional data from Odoo: {{ $report['message'] ?? 'Unknown error' }}
        </div>
    @endif

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <button type="button" @click="openDrilldown('grand_total', 'Jumlah Mobil Keseluruhan')" class="text-left p-5 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 text-white shadow-lg hover:scale-[1.01] transition-transform cursor-pointer">
            <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-100">Jumlah Mobil SDP</div>
            <div class="text-3xl font-black mt-1">{{ $fmtInt($totals['jumlah_mobil_sdp'] ?? 0) }}</div>
            <div class="text-[11px] text-indigo-100 mt-1.5 font-medium">
                Keseluruhan: {{ $fmtInt($totals['jumlah_keseluruhan'] ?? 0) }} • External: {{ $fmtInt($totals['mobil_sewa_external'] ?? 0) }}
            </div>
        </button>

        <button type="button" @click="openDrilldown('group_1', '1. Mobil Tersewa')" class="text-left p-5 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-sm hover:border-emerald-400 dark:hover:border-emerald-600 transition-colors cursor-pointer">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">1. Mobil Tersewa</div>
            <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $fmtInt($totals['total_tersewa'] ?? 0) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1.5 font-semibold">
                {{ $totals['jumlah_keseluruhan'] > 0 ? $fmtPct(($totals['total_tersewa'] / $totals['jumlah_keseluruhan']) * 100) : '0,00%' }} dari Keseluruhan
            </div>
        </button>

        <button type="button" @click="openDrilldown('group_2', '2. Mobil Stock Sewa & Replace')" class="text-left p-5 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-sm hover:border-indigo-400 dark:hover:border-indigo-600 transition-colors cursor-pointer">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">2. Stock Sewa &amp; Replace</div>
            <div class="text-3xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ $fmtInt($totals['total_stock_sewa_replace'] ?? 0) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1.5 font-semibold">
                {{ $fmtPct($totals['pct_stock_vs_tersewa'] ?? 0) }} Tersewa • {{ $fmtPct($totals['pct_stock_vs_total'] ?? 0) }} Total
            </div>
        </button>

        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">3. Opr &amp; 4. Stock Jual</div>
            <div class="flex items-baseline gap-3 mt-1">
                <button type="button" @click="openDrilldown('group_3', '3. Mobil Operasional')" class="text-2xl font-black text-cyan-600 dark:text-cyan-400 hover:underline cursor-pointer">
                    {{ $fmtInt($totals['total_operasional'] ?? 0) }} <span class="text-xs font-bold">Opr</span>
                </button>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <button type="button" @click="openDrilldown('group_4', '4. Mobil Stock Jual')" class="text-2xl font-black text-purple-600 dark:text-purple-400 hover:underline cursor-pointer">
                    {{ $fmtInt($totals['total_stock_jual'] ?? 0) }} <span class="text-xs font-bold">Jual</span>
                </button>
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 font-medium">
                Click numbers to inspect plates
            </div>
        </div>

        <button type="button" @click="openDrilldown('empty_all', 'Audit: Status Kendaraan Empty (All)')" class="text-left p-5 rounded-2xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/70 shadow-sm hover:border-amber-400 transition-colors cursor-pointer">
            <div class="text-[11px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-300">Audit: Status Empty</div>
            <div class="text-3xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ $fmtInt($emptyAudit['total_empty'] ?? 0) }}</div>
            <div class="text-[11px] text-amber-700/90 dark:text-amber-300/80 mt-1.5 font-semibold">
                Non-Rental Untagged: {{ $fmtInt($emptyAudit['untagged_non_rented'] ?? 0) }} unit
            </div>
        </button>
    </div>

    <!-- Main Content: Report Table (Left 8 cols) + Empty Audit & Odoo Reconciliation (Right 4 cols) -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">

        <!-- Official Laporan Harian Jumlah Mobil Table -->
        <div class="xl:col-span-8 bg-white dark:bg-slate-900/90 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 bg-slate-50/70 dark:bg-slate-950/60">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wide">PT. Surya Darma Perkasa — Laporan Harian Jumlah Mobil</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Click any count to inspect exact license plates &amp; Odoo details</p>
                </div>
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Per {{ $reportDate }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-700 dark:text-slate-300 uppercase text-[11px] font-bold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th rowspan="2" class="py-3 px-4 border-r border-slate-200 dark:border-slate-800">Keterangan</th>
                            <th rowspan="2" class="py-3 px-4 text-right border-r border-slate-200 dark:border-slate-800 w-24">Total</th>
                            <th class="py-2 px-3 text-center border-r border-slate-200 dark:border-slate-800">Persentase Dari</th>
                            <th class="py-2 px-3 text-center">Persentase Dari</th>
                        </tr>
                        <tr class="border-t border-slate-200 dark:border-slate-800 bg-slate-100/70 dark:bg-slate-950/70">
                            <th class="py-2 px-3 text-center border-r border-slate-200 dark:border-slate-800 text-[10px]">Total Unit Tersewa</th>
                            <th class="py-2 px-3 text-center text-[10px]">Total Unit Keseluruhan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach($report['rows'] ?? [] as $r)
                            @php
                                $isSection = $r['type'] === 'section';
                                $isGroup = $r['type'] === 'group';
                                $isSubtotal = $r['type'] === 'subtotal';
                                $isItem = $r['type'] === 'item';
                                $rowBg = $isSection
                                    ? 'bg-indigo-50/70 dark:bg-indigo-950/40 font-extrabold text-indigo-950 dark:text-indigo-200'
                                    : ($isSubtotal
                                        ? 'bg-slate-100 dark:bg-slate-800/90 font-extrabold text-slate-900 dark:text-white'
                                        : ($isGroup
                                            ? 'bg-slate-50/60 dark:bg-slate-900 font-bold text-slate-800 dark:text-slate-100'
                                            : 'hover:bg-slate-50/80 dark:hover:bg-slate-800/40 text-slate-700 dark:text-slate-300'));
                                $cnt = $r['total'];
                                $canClick = ($r['bucket'] !== null && $cnt > 0);
                            @endphp
                            <tr class="{{ $rowBg }} transition-colors">
                                <td class="py-2 px-4 border-r border-slate-200 dark:border-slate-800 {{ $isItem ? 'pl-8' : '' }} {{ $isSubtotal ? 'text-center' : '' }}">
                                    {{ $r['label'] }}
                                </td>
                                <td class="py-2 px-4 text-right font-mono border-r border-slate-200 dark:border-slate-800">
                                    @if($cnt !== null)
                                        @if($canClick)
                                            <button type="button"
                                                    @click="openDrilldown('{{ $r['bucket'] }}', @js(trim($r['label'])))"
                                                    class="px-2 py-0.5 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold underline decoration-dotted underline-offset-2 cursor-pointer transition-colors">
                                                {{ $fmtInt($cnt) }}
                                            </button>
                                        @else
                                            <span class="{{ $cnt === 0 ? 'text-slate-400 dark:text-slate-600' : 'font-bold' }}">
                                                {{ $fmtInt($cnt) }}
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                <td class="py-2 px-4 text-center font-mono border-r border-slate-200 dark:border-slate-800">
                                    {{ $r['pct_rented'] !== null ? $fmtPct($r['pct_rented']) : '' }}
                                </td>
                                <td class="py-2 px-4 text-center font-mono">
                                    {{ $r['pct_total'] !== null ? $fmtPct($r['pct_total']) : '' }}
                                </td>
                            </tr>
                        @endforeach

                        <!-- Bottom Summary Rows -->
                        <tr class="bg-slate-900 text-white dark:bg-slate-950 font-extrabold border-t-2 border-slate-400 dark:border-slate-700">
                            <td class="py-2.5 px-4 text-center border-r border-slate-700">Jumlah Mobil Keseluruhan</td>
                            <td class="py-2.5 px-4 text-right font-mono border-r border-slate-700">
                                <button type="button" @click="openDrilldown('grand_total', 'Jumlah Mobil Keseluruhan')" class="hover:underline cursor-pointer">
                                    {{ $fmtInt($totals['jumlah_keseluruhan'] ?? 0) }}
                                </button>
                            </td>
                            <td class="py-2.5 px-4 text-center border-r border-slate-700"></td>
                            <td class="py-2.5 px-4 text-center font-mono">100,00%</td>
                        </tr>
                        <tr class="bg-slate-50 dark:bg-slate-900 font-medium text-slate-700 dark:text-slate-300">
                            <td class="py-2 px-4 text-center border-r border-slate-200 dark:border-slate-800">Mobil Sewa External</td>
                            <td class="py-2 px-4 text-right font-mono border-r border-slate-200 dark:border-slate-800">
                                @if(($totals['mobil_sewa_external'] ?? 0) > 0)
                                    <button type="button" @click="openDrilldown('sewa_external', 'Mobil Sewa External')" class="px-2 py-0.5 rounded hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold underline decoration-dotted cursor-pointer">
                                        {{ $fmtInt($totals['mobil_sewa_external']) }}
                                    </button>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>
                            <td class="py-2 px-4 border-r border-slate-200 dark:border-slate-800"></td>
                            <td class="py-2 px-4"></td>
                        </tr>
                        <tr class="bg-indigo-600/10 dark:bg-indigo-950/60 font-extrabold text-indigo-950 dark:text-indigo-200">
                            <td class="py-2.5 px-4 text-center border-r border-slate-200 dark:border-slate-800">Jumlah Mobil SDP</td>
                            <td class="py-2.5 px-4 text-right font-mono border-r border-slate-200 dark:border-slate-800">
                                {{ $fmtInt($totals['jumlah_mobil_sdp'] ?? 0) }}
                            </td>
                            <td class="py-2.5 px-4 border-r border-slate-200 dark:border-slate-800"></td>
                            <td class="py-2.5 px-4"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Column: Audit Status Kendaraan Empty + Odoo Physical Reconciliation -->
        <div class="xl:col-span-4 space-y-5">

            <!-- Separate Audit Info Card: Empty Status Kendaraan -->
            <div class="bg-white dark:bg-slate-900/90 rounded-2xl shadow-lg border border-amber-200 dark:border-amber-800/60 overflow-hidden">
                <div class="px-5 py-4 bg-amber-50/90 dark:bg-amber-950/40 border-b border-amber-200 dark:border-amber-800/60 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                            <span>Audit: Status Kendaraan = Empty</span>
                        </h3>
                        <p class="text-[11px] text-amber-700/80 dark:text-amber-300/70 mt-0.5">
                            Info terpisah di luar tabel PDF resmi (unit dengan field <em>Status Kendaraan</em> masih kosong di Odoo).
                        </p>
                    </div>
                </div>

                <div class="p-5 space-y-3 text-xs">
                    <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                        <span class="font-bold text-slate-800 dark:text-slate-100">Total Unit Status Empty</span>
                        <button type="button" @click="openDrilldown('empty_all', 'Audit: Semua Unit Status Kendaraan Empty')"
                                class="px-2.5 py-1 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200 font-mono font-extrabold hover:bg-amber-200 dark:hover:bg-amber-800 cursor-pointer transition-colors">
                            {{ $fmtInt($emptyAudit['total_empty'] ?? 0) }}
                        </button>
                    </div>

                    <div class="space-y-2 text-[11px]">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400">• Active Rental @ Customer <span class="text-[10px] text-emerald-600 dark:text-emerald-400">(di Sec. 1)</span></span>
                            <button type="button" @click="openDrilldown('empty_at_customer', 'Empty Status: Active Rental @ Customer')" class="font-mono font-bold text-slate-800 dark:text-slate-200 hover:text-indigo-500 underline decoration-dotted cursor-pointer">
                                {{ $fmtInt($emptyAudit['at_customer'] ?? 0) }}
                            </button>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400">• Active Rental @ Insurance/Service <span class="text-[10px] text-emerald-600 dark:text-emerald-400">(di Sec. 1)</span></span>
                            <button type="button" @click="openDrilldown('empty_rented_service_ins', 'Empty Status: Active Rental @ Insurance/Service')" class="font-mono font-bold text-slate-800 dark:text-slate-200 hover:text-indigo-500 underline decoration-dotted cursor-pointer">
                                {{ $fmtInt($emptyAudit['rented_service_ins'] ?? 0) }}
                            </button>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400">• Active Rental @ Stock Car <span class="text-[10px] text-emerald-600 dark:text-emerald-400">(di Sec. 1)</span></span>
                            <button type="button" @click="openDrilldown('empty_rented_stock_car', 'Empty Status: Active Rental @ Stock Car')" class="font-mono font-bold text-slate-800 dark:text-slate-200 hover:text-indigo-500 underline decoration-dotted cursor-pointer">
                                {{ $fmtInt($emptyAudit['rented_stock_car'] ?? 0) }}
                            </button>
                        </div>
                        <div class="flex items-center justify-between p-2 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200/80 dark:border-rose-800/50 mt-2">
                            <span class="font-bold text-rose-700 dark:text-rose-300">• Non-Rental / Belum Di-tag Ops</span>
                            <button type="button" @click="openDrilldown('empty_untagged_non_rented', 'Audit: Non-Rental Empty Status Kendaraan')" class="px-2 py-0.5 rounded bg-rose-100 dark:bg-rose-900/60 font-mono font-extrabold text-rose-700 dark:text-rose-200 hover:bg-rose-200 cursor-pointer">
                                {{ $fmtInt($emptyAudit['untagged_non_rented'] ?? 0) }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Odoo Total Lots Reconciliation -->
            <div class="bg-white dark:bg-slate-900/90 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-800 p-5 space-y-3 text-xs">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                    Reconcile Odoo Live Stock Lots
                </h3>
                <div class="space-y-2 text-[11px]">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 dark:text-slate-400">Masuk Kategori Laporan PDF (Sec. 1–4)</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $fmtInt($totals['jumlah_keseluruhan'] ?? 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 dark:text-slate-400">Non-Rental Status Empty (Belum Di-tag)</span>
                        <button type="button" @click="openDrilldown('untagged_non_rented', 'Non-Rental Status Empty (Belum Di-tag)')" class="font-mono font-bold text-rose-600 dark:text-rose-400 underline decoration-dotted cursor-pointer">
                            {{ $fmtInt($totals['untagged_non_rented'] ?? 0) }}
                        </button>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 dark:text-slate-400">Sewa External Standby (Non-Rented)</span>
                        <button type="button" @click="openDrilldown('external_standby', 'Sewa External Standby (Non-Rented)')" class="font-mono font-bold text-slate-800 dark:text-slate-200 underline decoration-dotted cursor-pointer">
                            {{ $fmtInt($totals['external_standby'] ?? 0) }}
                        </button>
                    </div>
                    <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between font-extrabold text-slate-900 dark:text-white">
                        <span>Total Unit Aktif di Odoo (qty &gt; 0)</span>
                        <span class="font-mono">{{ $fmtInt($totals['total_active_odoo_lots'] ?? 0) }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Interactive Vehicle Drill-Down Modal -->
    <div x-show="modal.open" style="display: none;" @keydown.escape.window="modal.open = false"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/70 dark:bg-slate-950/90 backdrop-blur-sm" x-transition>
        <div @click.away="modal.open = false"
             class="bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-700 rounded-3xl w-full max-w-6xl max-h-[88vh] flex flex-col shadow-2xl overflow-hidden">

            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shrink-0 bg-slate-50 dark:bg-slate-950/70">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white" x-text="modal.title"></h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Showing <span class="font-bold text-indigo-600 dark:text-indigo-400" x-text="filteredItems.length"></span> units
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative">
                        <input type="text" x-model="modal.search" @input="modal.page = 1"
                               placeholder="Search plate, chassis, customer, location..."
                               class="w-64 sm:w-72 pl-3 pr-8 py-2 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <button type="button" @click="modal.open = false" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>

            <!-- Modal Table Body -->
            <div class="flex-1 overflow-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="sticky top-0 z-10 bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 font-bold uppercase text-[10px] border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-2.5 px-3 w-10">#</th>
                            <th class="py-2.5 px-3 whitespace-nowrap">No. Polisi / Lot</th>
                            <th class="py-2.5 px-3 whitespace-nowrap">Chassis</th>
                            <th class="py-2.5 px-3">Unit / Product</th>
                            <th class="py-2.5 px-3">Location (Odoo)</th>
                            <th class="py-2.5 px-3 text-center">Status Kendaraan</th>
                            <th class="py-2.5 px-3 whitespace-nowrap">Job Order (Confirmed)</th>
                            <th class="py-2.5 px-3">Cabang</th>
                            <th class="py-2.5 px-3 whitespace-nowrap">Rental ID</th>
                            <th class="py-2.5 px-3">Customer</th>
                            <th class="py-2.5 px-3 whitespace-nowrap">Reserved Lot</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-[11px] text-slate-700 dark:text-slate-300">
                        <template x-for="(item, idx) in pagedItems" :key="item.lot_number + '-' + idx">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="py-2 px-3 text-slate-400 font-mono" x-text="(modal.page - 1) * modal.pageSize + idx + 1"></td>
                                <td class="py-2 px-3 font-mono font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap" x-text="item.lot_number"></td>
                                <td class="py-2 px-3 font-mono text-[10px] text-slate-500 whitespace-nowrap" x-text="item.chassis"></td>
                                <td class="py-2 px-3 font-medium" x-text="item.product"></td>
                                <td class="py-2 px-3 whitespace-nowrap font-medium" x-text="item.location"></td>
                                <td class="py-2 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                          :class="item.vehicle_status === 'Empty' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300'"
                                          x-text="item.vehicle_status"></span>
                                </td>
                                <td class="py-2 px-3 font-mono whitespace-nowrap">
                                    <template x-if="item.confirmed_jo && item.confirmed_jo !== '-'">
                                        <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200 font-semibold text-[10px]" x-text="item.confirmed_jo"></span>
                                    </template>
                                    <template x-if="!item.confirmed_jo || item.confirmed_jo === '-'">
                                        <span class="text-slate-400">-</span>
                                    </template>
                                </td>
                                <td class="py-2 px-3 whitespace-nowrap" x-text="item.branch_condition"></td>
                                <td class="py-2 px-3 font-mono whitespace-nowrap" x-text="item.rental_id"></td>
                                <td class="py-2 px-3" x-text="item.customer"></td>
                                <td class="py-2 px-3 font-mono whitespace-nowrap" x-text="item.reserved_lot"></td>
                            </tr>
                        </template>
                        <template x-if="pagedItems.length === 0">
                            <tr>
                                <td colspan="11" class="py-10 text-center text-slate-400 font-medium">No matching units found.</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Modal Pagination Footer -->
            <div class="px-6 py-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs bg-slate-50 dark:bg-slate-950/70 shrink-0">
                <span class="text-slate-500 dark:text-slate-400">
                    Page <strong x-text="modal.page"></strong> of <strong x-text="totalPages"></strong>
                </span>
                <div class="flex items-center gap-2">
                    <button type="button" @click="modal.page = Math.max(1, modal.page - 1)" :disabled="modal.page <= 1"
                            class="px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 disabled:opacity-40 font-semibold">
                        Prev
                    </button>
                    <button type="button" @click="modal.page = Math.min(totalPages, modal.page + 1)" :disabled="modal.page >= totalPages"
                            class="px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 disabled:opacity-40 font-semibold">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function stockNasionalApp() {
    const unitsPool = @json($unitsPool);
    const bucketIndices = @json($bucketIndices);

    return {
        isRefreshing: false,
        modal: {
            open: false,
            title: '',
            items: [],
            search: '',
            page: 1,
            pageSize: 100
        },
        openDrilldown(bucketKey, title) {
            this.modal.title = title;
            const idxs = bucketIndices[bucketKey] || [];
            this.modal.items = idxs.map(i => unitsPool[i]);
            this.modal.search = '';
            this.modal.page = 1;
            this.modal.open = true;
        },
        get filteredItems() {
            const q = this.modal.search.trim().toLowerCase();
            if (!q) return this.modal.items;
            return this.modal.items.filter(item => {
                return (item.lot_number || '').toLowerCase().includes(q)
                    || (item.chassis || '').toLowerCase().includes(q)
                    || (item.product || '').toLowerCase().includes(q)
                    || (item.location || '').toLowerCase().includes(q)
                    || (item.customer || '').toLowerCase().includes(q)
                    || (item.rental_id || '').toLowerCase().includes(q)
                    || (item.branch_condition || '').toLowerCase().includes(q)
                    || (item.vehicle_status || '').toLowerCase().includes(q)
                    || (item.confirmed_jo || '').toLowerCase().includes(q);
            });
        },
        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredItems.length / this.modal.pageSize));
        },
        get pagedItems() {
            const start = (this.modal.page - 1) * this.modal.pageSize;
            return this.filteredItems.slice(start, start + this.modal.pageSize);
        }
    };
}
</script>
@endsection

