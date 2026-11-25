@extends('layouts.app')

@section('title', 'Database Backups')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-3">
                <span class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                        </path>
                    </svg>
                </span>
                Database Backups
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Pencadangan database SQLite aman dengan snapshot atomik <code>VACUUM INTO</code> terkompresi <code>.sqlite.gz</code> tanpa mengunci tabel.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                </svg>
                {{ $backups->count() }} File Tersimpan
            </span>
        </div>
    </div>

    <!-- Notification Alerts -->
    @if (session('success'))
        <div class="flex items-center gap-3 p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-800 dark:text-emerald-300 text-sm">
            <svg class="w-5 h-5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="font-medium">{{ session('success') }}</div>
        </div>
    @endif

    @if (session('error'))
        <div class="flex items-center gap-3 p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-xl text-rose-800 dark:text-rose-300 text-sm">
            <svg class="w-5 h-5 flex-shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="font-medium">{{ session('error') }}</div>
        </div>
    @endif

    <!-- 2 Cards: Instant Backup & Automatic Schedule -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Card 1: Buat Backup Manual (Instan) -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col justify-between">
            <div class="p-5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                    <h2 class="text-base font-bold text-slate-800 dark:text-slate-100">Buat Backup Manual (Instan)</h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Snapshot seluruh database SQLite saat ini langsung ke arsip terkompresi <code>.sqlite.gz</code>.
                </p>
            </div>

            <div class="p-5 flex-1 flex flex-col justify-between">
                <div class="rounded-xl p-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 mb-4 text-xs text-slate-600 dark:text-slate-300 space-y-1">
                    <div class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Keamanan Snapshot
                    </div>
                    <div>• Menyalin seluruh tabel (Item, Surat Kuasa, Settings, LoR, Odoo, dll).</div>
                    <div>• Menggunakan <code>VACUUM INTO</code> sehingga bebas korupsi meskipun ada proses sync.</div>
                </div>

                <form method="POST" action="{{ route('backups.create') }}" class="space-y-4" onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').innerHTML='Memproses Backup...';">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Catatan / Remark (Opsional)</label>
                        <input type="text" name="remark" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Contoh: Sebelum update modul / Akhir bulan">
                    </div>
                    <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Create Backup Now
                    </button>
                </form>
            </div>
        </div>

        <!-- Card 2: Pengaturan Jadwal Otomatis (Scheduler) -->
        <div class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col justify-between">
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full {{ $schedule->enabled ? 'bg-indigo-500' : 'bg-slate-400' }}"></div>
                    <h2 class="text-base font-bold text-slate-800 dark:text-slate-100">Jadwal Backup Otomatis (Scheduler)</h2>
                </div>
                <span class="px-2.5 py-1 text-[11px] font-bold rounded-full {{ $schedule->enabled ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                    {{ $schedule->enabled ? 'ACTIVE' : 'DISABLED' }}
                </span>
            </div>

            <form method="POST" action="{{ route('backups.schedule') }}" class="p-5 flex-1 flex flex-col justify-between space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Status Scheduler</label>
                        <select name="enabled" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="1" {{ $schedule->enabled ? 'selected' : '' }}>Aktif (Enable)</option>
                            <option value="0" {{ !$schedule->enabled ? 'selected' : '' }}>Nonaktif (Disable)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Frekuensi</label>
                        <select name="frequency" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="daily" {{ $schedule->frequency === 'daily' ? 'selected' : '' }}>Daily (Harian)</option>
                            <option value="weekly" {{ $schedule->frequency === 'weekly' ? 'selected' : '' }}>Weekly (Mingguan)</option>
                            <option value="monthly" {{ $schedule->frequency === 'monthly' ? 'selected' : '' }}>Monthly (Bulanan)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Jam Eksekusi (WIB)</label>
                        <input type="time" name="time" value="{{ $schedule->time ?? '02:00' }}" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <!-- Retensi / Auto Prune Settings -->
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="prune_enabled" value="1" {{ $schedule->prune_enabled ? 'checked' : '' }} class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Auto-delete backup lama (Pembersihan retensi agar disk server tidak penuh)
                        </span>
                    </label>

                    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 pl-6">
                        <span>Pertahankan</span>
                        <input type="number" name="keep_daily" value="{{ $schedule->keep_daily ?? 7 }}" min="1" max="365" class="w-16 px-2 py-1 text-xs text-center rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <span>hari terakhir (backup lebih lama dari periode ini otomatis dibersihkan).</span>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Dijalankan otomatis oleh Laravel background scheduler.
                    </span>
                    <button type="submit" class="py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        Save Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Card 3: Riwayat & File Backup -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                    Riwayat File Backup (.sqlite.gz)
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    File cadangan tersimpan di penyimpanan lokal server dan siap diunduh sewaktu-waktu.
                </p>
            </div>

            @if ($schedule->prune_enabled && $backups->count() > 0)
                <form method="POST" action="{{ route('backups.prune') }}" onsubmit="return confirm('Jalankan pembersihan file backup lama sesuai aturan retensi?')">
                    @csrf
                    <button type="submit" class="py-1.5 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold transition-colors flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Prune Old Backups
                    </button>
                </form>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <th class="py-3 px-4">No</th>
                        <th class="py-3 px-4">Nama File</th>
                        <th class="py-3 px-4">Catatan / Remark</th>
                        <th class="py-3 px-4">Ukuran File</th>
                        <th class="py-3 px-4">Dibuat Oleh</th>
                        <th class="py-3 px-4">Tanggal Dibuat</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($backups as $index => $b)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4 text-slate-400 font-medium">{{ $index + 1 }}</td>
                            <td class="py-3 px-4 font-mono font-semibold text-slate-800 dark:text-slate-200">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    {{ $b->filename }}
                                </div>
                            </td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-300">
                                {{ $b->remark ?: '-' }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                    {{ $b->formatted_size }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                                <span class="inline-flex items-center gap-1">
                                    @if(str_contains($b->created_by, 'System'))
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                    @else
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    @endif
                                    {{ $b->created_by }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                {{ $b->created_at->format('d/m/Y H:i:s') }} WIB
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('backups.download', $b->filename) }}" class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900 border border-indigo-200 dark:border-indigo-800 transition-colors" title="Download File (.sqlite.gz)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('backups.delete', $b->filename) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus backup {{ $b->filename }}?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900 border border-rose-200 dark:border-rose-800 transition-colors" title="Hapus File Backup">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-slate-300 dark:text-slate-700 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                    </svg>
                                    <p class="font-medium text-xs">Belum ada file backup database.</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Klik tombol "Create Backup Now" di atas untuk membuat backup perdana.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
