@extends('layouts.app')

@section('title', 'SK Report - Surat Kuasa')

@section('content')
<div class="w-full space-y-6" x-data="skReportApp()">

    @if(!$authenticated)
    <!-- UNLOCK PAGE MODAL / PROMPT -->
    <div class="min-h-[60vh] flex items-center justify-center py-12">
        <div class="w-full max-w-md bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 rounded-3xl p-8 shadow-2xl space-y-6 text-center">
            
            <div class="w-16 h-16 rounded-3xl bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center mx-auto text-white shadow-lg shadow-indigo-500/30">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>

            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Surat Kuasa Protected Access</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 font-medium">Enter secondary password to access SK Report</p>
            </div>

            <form action="{{ route('surat-kuasa.auth') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1 text-left">
                    <label class="text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Secondary Password</label>
                    <input type="password" name="password" required autofocus placeholder="Enter PIN / Password" class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-300 dark:border-slate-700/80 text-slate-900 dark:text-white text-sm font-semibold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-all">
                </div>
                <button type="submit" class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-700 hover:to-cyan-700 text-white font-extrabold text-sm shadow-lg shadow-indigo-500/25 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    Unlock SK Report
                </button>
            </form>
        </div>
    </div>
    @else

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xl">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <div>
                <h1 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Surat Kuasa Generation Report</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Audit log of all generated Surat Kuasa documents across vehicle units</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if(auth()->check() && auth()->user()->isItAdmin() && $logs->total() > 0)
                <button type="button" @click="clearAllLogs()" class="px-4 py-2.5 rounded-2xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-bold border border-rose-500/20 transition-all flex items-center gap-1.5" title="Clear all generated log records">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Clear All Logs
                </button>
            @endif

            <!-- Search Bar -->
            <form method="GET" action="{{ route('surat-kuasa.report') }}" class="flex items-center gap-2">
                <div class="relative min-w-[240px]">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search Doc No, Lot, Product, Customer..." class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-200 dark:border-slate-800 text-xs font-medium text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <button type="submit" class="px-4 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-colors">Search</button>
                @if($search)
                    <a href="{{ route('surat-kuasa.report') }}" class="px-3 py-2.5 rounded-2xl bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold hover:bg-slate-300">Clear</a>
                @endif
            </form>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-[#050913] border-b border-slate-200 dark:border-slate-800 text-slate-500 uppercase tracking-wider font-extrabold text-[10px]">
                    <tr>
                        <th class="py-4 px-5">#</th>
                        <th class="py-4 px-5">Nomor Surat Kuasa</th>
                        <th class="py-4 px-5">Vehicle Lot / Serial</th>
                        <th class="py-4 px-5">Product Details</th>
                        <th class="py-4 px-5">Penerima Kuasa</th>
                        <th class="py-4 px-5">Date Generated</th>
                        <th class="py-4 px-5">Generated By</th>
                        <th class="py-4 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                    @forelse($logs as $index => $log)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-5 text-slate-400 font-mono">{{ $logs->firstItem() + $index }}</td>
                            <td class="py-4 px-5 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $log->doc_no }}
                            </td>
                            <td class="py-4 px-5">
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $log->lot_number }}</span>
                            </td>
                            <td class="py-4 px-5">
                                <div class="font-semibold text-slate-900 dark:text-white max-w-xs truncate" title="{{ $log->product }}">
                                    {{ \App\Http\Controllers\SuratKuasaController::cleanProductName($log->product) }}
                                </div>
                                <div class="text-[10px] text-slate-400">{{ $log->customer }}</div>
                            </td>
                            <td class="py-4 px-5">
                                <div class="font-bold text-slate-800 dark:text-slate-200">{{ $log->penerima_nama ?: '-' }}</div>
                                <div class="text-[10px] text-slate-400 max-w-xs truncate">{{ $log->penerima_alamat ?: '-' }}</div>
                            </td>
                            <td class="py-4 px-5 text-slate-500 font-medium">
                                {{ $log->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="py-4 px-5">
                                <span class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-300 text-[10px]">
                                    {{ $log->generated_by_name ?: 'System' }}
                                </span>
                            </td>
                            <td class="py-4 px-5 text-right whitespace-nowrap space-x-1.5">
                                <!-- Preview Document -->
                                <button type="button" @click="openPreviewModal({{ json_encode($log) }})" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    Preview
                                </button>
                                <!-- Download Word -->
                                <a href="{{ route('surat-kuasa.download-docx', $log->item_id) }}?doc_no={{ urlencode($log->doc_no) }}&penerima_nama={{ urlencode($log->penerima_nama) }}&penerima_alamat={{ urlencode($log->penerima_alamat) }}&jenis_model={{ urlencode($log->jenis_model) }}&reprint=1" class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs inline-flex items-center gap-1 shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Word (.docx)
                                </a>
                                @if(auth()->check() && auth()->user()->isItAdmin())
                                    <!-- Delete Single Log (IT Admin) -->
                                    <button type="button" @click="deleteSingleLog({{ $log->id }}, '{{ $log->doc_no }}')" class="px-2.5 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 font-bold text-xs inline-flex items-center gap-1 border border-rose-500/20" title="Delete Log Record">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p class="text-sm font-bold">No generated Surat Kuasa records found</p>
                                <p class="text-xs text-slate-500 mt-1">Generate a Surat Kuasa from the main Surat Kuasa menu.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

    <!-- PREVIEW MODAL -->
    <div x-show="showPreview" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-md overflow-y-auto" x-transition>
        <div @click.away="showPreview = false" class="bg-white text-black p-8 rounded-2xl w-full max-w-3xl shadow-2xl space-y-6 my-8 font-serif">
            <div class="flex justify-between items-center border-b pb-3 font-sans">
                <span class="text-xs font-bold text-slate-500">Document Preview</span>
                <button type="button" @click="showPreview = false" class="text-slate-400 hover:text-black font-bold">✕ Close</button>
            </div>

            <!-- DOCUMENT PREVIEW PAPER CONTENT -->
            <div class="space-y-4 text-xs leading-relaxed text-black">
                <div class="text-center space-y-1">
                    <h2 class="text-base font-bold tracking-wider">SURAT KUASA</h2>
                    <p class="font-normal" x-text="activeLog.doc_no"></p>
                </div>

                <div class="pt-2">Yang bertanda tangan dibawah ini:</div>
                <table class="w-full text-xs">
                    <tr><td class="w-32 py-0.5">Nama</td><td class="w-4">:</td><td>{{ $settings['pemberi_1_nama'] ?? 'Suzanna Caroline' }}</td></tr>
                    <tr><td class="py-0.5">Jabatan</td><td>:</td><td>{{ $settings['pemberi_1_jabatan'] ?? 'General Manager' }}</td></tr>
                    <tr><td class="py-0.5">Nama</td><td>:</td><td>{{ $settings['pemberi_2_nama'] ?? 'Aldian Prayoga Darwis' }}</td></tr>
                    <tr><td class="py-0.5">Jabatan</td><td>:</td><td>{{ $settings['pemberi_2_jabatan'] ?? 'Fleet Operation Manager' }}</td></tr>
                    <tr><td class="py-0.5">Alamat</td><td>:</td><td>{{ $settings['pemberi_alamat'] ?? 'Jl. Daan Mogot KM 1 No. 99 Jakarta Barat 11510' }}</td></tr>
                </table>

                <div class="pt-4">Dengan ini memberi kuasa kepada :</div>
                <table class="w-full text-xs">
                    <tr><td class="w-32 py-0.5">Nama</td><td class="w-4">:</td><td x-text="activeLog.penerima_nama || '....................................................'"></td></tr>
                    <tr><td class="py-0.5">Alamat</td><td>:</td><td x-text="activeLog.penerima_alamat || '....................................................'"></td></tr>
                </table>

                <div class="pt-4 font-bold">Untuk mengurus Surat Tanda Nomor Kendaraan ( STNK ) &amp; BPKB</div>

                <table class="w-full text-xs">
                    <tr><td class="w-32 py-0.5 font-bold">Nama Pemilik</td><td class="w-4">:</td><td class="font-bold">{{ $settings['pemilik_nama'] ?? 'PT Surya Darma Perkasa' }}</td></tr>
                    @if(!empty($settings['pemilik_alamat']))
                    <tr><td class="py-0.5">Alamat</td><td>:</td><td>{{ $settings['pemilik_alamat'] }}</td></tr>
                    @endif
                    <tr><td class="py-0.5">Merk/Type</td><td>:</td><td x-text="activeLog.clean_product || activeLog.product"></td></tr>
                    <tr><td class="py-0.5">Jenis / Model</td><td>:</td><td x-text="activeLog.jenis_model"></td></tr>
                    <tr><td class="py-0.5">Tahun</td><td>:</td><td x-text="activeLog.tahun"></td></tr>
                    <tr><td class="py-0.5">No. Rangka</td><td>:</td><td x-text="activeLog.no_rangka"></td></tr>
                    <tr><td class="py-0.5">No. Mesin</td><td>:</td><td x-text="activeLog.no_mesin"></td></tr>
                    <tr><td class="py-0.5">Warna</td><td>:</td><td x-text="activeLog.warna"></td></tr>
                </table>

                <div class="pt-2">Demikian Surat Kuasa ini kami buat untuk dipergunakan sebagaimana mestinya.</div>

                <div class="pt-6 font-sans">
                    <div class="flex justify-between">
                        <div>
                            <div>Jakarta , <span x-text="activeLog.print_date || '{{ \App\Http\Controllers\SuratKuasaController::formatIndonesianDate() }}'"></span></div>
                            <div class="font-bold mt-1">Pemberi Kuasa</div>
                            <div class="mt-20 flex gap-8">
                                <div>
                                    <div class="font-bold underline">{{ $settings['pemberi_1_nama'] ?? 'Suzanna Caroline' }}</div>
                                    <div class="text-[10px]">{{ $settings['pemberi_1_jabatan'] ?? 'General Manager' }}</div>
                                </div>
                                <div>
                                    <div class="font-bold underline">{{ $settings['pemberi_2_nama'] ?? 'Aldian Prayoga Darwis' }}</div>
                                    <div class="text-[10px]">{{ $settings['pemberi_2_jabatan'] ?? 'Fleet Operation Manager' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="opacity-0">Jakarta ,</div>
                            <div class="font-bold mt-1">Penerima Kuasa</div>
                            <div class="mt-20 whitespace-pre">( <span x-text="activeLog.penerima_nama || '                                          '"></span> )</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t font-sans">
                <button type="button" @click="showPreview = false" class="px-5 py-2 rounded-xl bg-slate-200 text-slate-800 font-bold text-xs hover:bg-slate-300">Close Preview</button>
            </div>
        </div>
    </div>

    @endif
</div>

<script>
function skReportApp() {
    return {
        showPreview: false,
        activeLog: {},

        openPreviewModal(log) {
            this.activeLog = { ...log };
            if (this.activeLog.product) {
                this.activeLog.clean_product = this.activeLog.product.replace(/^\s*\[[^\]]*\]\s*/, '');
            }
            this.showPreview = true;
        },

        async deleteSingleLog(id, docNo) {
            if (!confirm(`Are you sure you want to delete log record for "${docNo}"?`)) return;
            try {
                const res = await fetch(`{{ url('/surat-kuasa/report') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to delete');
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        },

        async clearAllLogs() {
            if (!confirm('Are you sure you want to CLEAR ALL generated Surat Kuasa logs? This will wipe all test records.')) return;
            try {
                const res = await fetch('{{ route('surat-kuasa.report.clear-all') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to clear logs');
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }
    }
}
</script>
@endsection
