@extends('layouts.app')

@section('title', 'Settings - SDP Stock')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('dashboard') }}" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Settings</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Manage application configuration</p>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
    <div class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-700 dark:text-emerald-400 text-sm font-medium flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        {{ session('success') }}
    </div>
    @endif

    <!-- Error Message -->
    @if($errors->any())
    <div class="mb-6 p-4 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 rounded-xl text-rose-700 dark:text-rose-400 text-sm font-medium">
        <div class="flex items-center gap-2 font-bold mb-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Terdapat kesalahan saat menyimpan pengaturan:
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-xs">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="space-y-8">
        <!-- KPI Percentage Targets -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">KPI Percentage Targets</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Set targets as percentage of total stock for progress tracking</p>
                    </div>
                </div>
            </div>
            
            <form action="{{ route('settings.targets') }}" method="POST" class="p-6">
                @csrf
                
                <!-- Dashboard Layout -->
                <div class="mb-8">
                    <h3 class="text-sm font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-4">Dashboard Layout</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- KPI Progress Layout -->
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="dashboard_layout" value="kpi_progress" class="peer sr-only" {{ ($targets['dashboard_layout'] ?? 'kpi_progress') === 'kpi_progress' ? 'checked' : '' }}>
                            <div class="p-4 rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 transition-all">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-bold text-slate-700 dark:text-slate-200 peer-checked:text-indigo-700 dark:peer-checked:text-indigo-300">KPI Progress Cards</span>
                                    <svg class="w-5 h-5 text-indigo-500 opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Visual progress bars with percentage targets and status indicators.</p>
                            </div>
                        </label>

                        <!-- Simple Stats Layout -->
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="dashboard_layout" value="simple_stats" class="peer sr-only" {{ ($targets['dashboard_layout'] ?? 'kpi_progress') === 'simple_stats' ? 'checked' : '' }}>
                            <div class="p-4 rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 transition-all">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-bold text-slate-700 dark:text-slate-200 peer-checked:text-indigo-700 dark:peer-checked:text-indigo-300">Simple Stats Grid</span>
                                    <svg class="w-5 h-5 text-indigo-500 opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Clean, simple cards showing counts with basic status labels.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Dashboard Visibility -->
                <div class="mb-8 p-4 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">Show Historical Trends</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Toggle visibility of the historical trend chart on dashboard</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="dashboard_show_history" value="1" class="sr-only peer" {{ ($targets['dashboard_show_history'] ?? true) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Info Banner -->
                <div class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-100 dark:border-indigo-800">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div class="text-sm text-indigo-700 dark:text-indigo-300">
                            <p class="font-medium mb-1">How percentage targets work:</p>
                            <ul class="list-disc list-inside space-y-1 text-indigo-600 dark:text-indigo-400">
                                <li><strong>In Stock</strong> & <strong>Active Rental</strong>: Target to <span class="text-emerald-600 dark:text-emerald-400 font-bold">meet or exceed</span> (higher is better)</li>
                                <li><strong>In Service</strong>: Target to <span class="text-red-600 dark:text-red-400 font-bold">stay under</span> (lower is better)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- In Stock Target -->
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-5 border border-emerald-100 dark:border-emerald-800">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-1.5 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg">
                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            </div>
                            <span class="text-sm font-bold text-emerald-700 dark:text-emerald-400">In Stock</span>
                            <span class="ml-auto text-xs bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 px-2 py-0.5 rounded-full font-medium">Meet/Exceed ↑</span>
                        </div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-2">Target % of Total</label>
                        <div class="relative">
                            <input type="number" name="target_in_stock_pct" value="{{ $targets['target_in_stock_pct'] ?? 10 }}" min="0" max="100" step="0.1"
                                class="w-full px-4 py-3 pr-12 bg-white dark:bg-slate-800 border border-emerald-200 dark:border-emerald-700 rounded-xl text-slate-700 dark:text-slate-200 text-lg font-bold focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-emerald-600 dark:text-emerald-400 font-bold">%</div>
                        </div>
                    </div>

                    <!-- Active Rental Target -->
                    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-5 border border-amber-100 dark:border-amber-800">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-1.5 bg-amber-100 dark:bg-amber-900/50 rounded-lg">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <span class="text-sm font-bold text-amber-700 dark:text-amber-400">Active Rental</span>
                            <span class="ml-auto text-xs bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 px-2 py-0.5 rounded-full font-medium">Meet/Exceed ↑</span>
                        </div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-2">Target % of Total</label>
                        <div class="relative">
                            <input type="number" name="target_active_rental_pct" value="{{ $targets['target_active_rental_pct'] ?? 82 }}" min="0" max="100" step="0.1"
                                class="w-full px-4 py-3 pr-12 bg-white dark:bg-slate-800 border border-amber-200 dark:border-amber-700 rounded-xl text-slate-700 dark:text-slate-200 text-lg font-bold focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-amber-600 dark:text-amber-400 font-bold">%</div>
                        </div>
                    </div>

                    <!-- In Service Target -->
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-5 border border-red-100 dark:border-red-800">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-1.5 bg-red-100 dark:bg-red-900/50 rounded-lg">
                                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <span class="text-sm font-bold text-red-700 dark:text-red-400">In Service</span>
                            <span class="ml-auto text-xs bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 px-2 py-0.5 rounded-full font-medium">Stay Under ↓</span>
                        </div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-2">Target % of Total</label>
                        <div class="relative">
                            <input type="number" name="target_in_service_pct" value="{{ $targets['target_in_service_pct'] ?? 8 }}" min="0" max="100" step="0.1"
                                class="w-full px-4 py-3 pr-12 bg-white dark:bg-slate-800 border border-red-200 dark:border-red-700 rounded-xl text-slate-700 dark:text-slate-200 text-lg font-bold focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-red-600 dark:text-red-400 font-bold">%</div>
                        </div>
                    </div>
                </div>

                <!-- Rental Type Fixed Targets (kept for backward compatibility) -->
                <div class="pt-6 border-t border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-4">Fixed Targets (Optional)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Subscription Target (units)</label>
                            <input type="number" name="target_subscription" value="{{ $targets['target_subscription'] ?? 1500 }}" min="0"
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Regular Target (units)</label>
                            <input type="number" name="target_regular" value="{{ $targets['target_regular'] ?? 1000 }}" min="0"
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-200 dark:shadow-none flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Save Targets
                    </button>
                </div>
            </form>
        </div>

        <!-- Surat Kuasa Settings Card -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-cyan-500 to-indigo-500 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Surat Kuasa Configuration</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Configure Pemberi Kuasa signatories, owner address fallbacks, and access password</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('settings.surat-kuasa') }}" method="POST" class="p-6 space-y-6">
                @csrf
                
                <!-- Pemberi Kuasa 1 & 2 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Pemberi Kuasa 1 -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 space-y-4">
                        <h3 class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Pemberi Kuasa 1</h3>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama</label>
                            <input type="text" name="pemberi_1_nama" value="{{ $suratKuasa['pemberi_1_nama'] }}" required class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Jabatan</label>
                            <input type="text" name="pemberi_1_jabatan" value="{{ $suratKuasa['pemberi_1_jabatan'] }}" required class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100">
                        </div>
                    </div>

                    <!-- Pemberi Kuasa 2 -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 space-y-4">
                        <h3 class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Pemberi Kuasa 2</h3>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama</label>
                            <input type="text" name="pemberi_2_nama" value="{{ $suratKuasa['pemberi_2_nama'] }}" required class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Jabatan</label>
                            <input type="text" name="pemberi_2_jabatan" value="{{ $suratKuasa['pemberi_2_jabatan'] }}" required class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100">
                        </div>
                    </div>
                </div>

                <!-- Addresses & Owner -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Alamat Pemberi Kuasa</label>
                        <textarea name="pemberi_alamat" required rows="2" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100">{{ $suratKuasa['pemberi_alamat'] }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Pemilik Kendaraan</label>
                        <input type="text" name="pemilik_nama" value="{{ $suratKuasa['pemilik_nama'] }}" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100">
                    </div>
                </div>

                <!-- Nomor Surat Kuasa Auto-numbering Configuration -->
                <div class="p-4 bg-indigo-50/50 dark:bg-indigo-950/20 rounded-xl border border-indigo-200 dark:border-indigo-800/60 space-y-4"
                     x-data="{
                         prefix: '{{ $suratKuasa['doc_prefix'] ?? 'HRCJ/FOD' }}',
                         lastSeq: {{ (int) ($suratKuasa['last_sequence'] ?? 1545) }},
                         romanMonth: '{{ \App\Http\Controllers\SuratKuasaController::getRomanMonth((int) date('n')) }}',
                         year2: '{{ date('y') }}',
                         get nextPreview() {
                             const next = (parseInt(this.lastSeq) || 0) + 1;
                             const padded = String(next).padStart(4, '0');
                             return padded + '/' + (this.prefix || 'HRCJ/FOD') + '/' + this.romanMonth + '/' + this.year2;
                         }
                     }">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-indigo-100 dark:border-indigo-800/40 pb-3">
                        <div>
                            <h3 class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path></svg>
                                Penomoran Otomatis Surat Kuasa
                            </h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Format: [Nomor Urut 4-Digit]/[Prefix]/[Bulan Romawi]/[2 Digit Tahun]</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">Preview Next:</span>
                            <span class="px-3 py-1 bg-indigo-600 text-white text-xs font-mono font-bold rounded-lg shadow-sm" x-text="nextPreview"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Prefix Nomor Surat (e.g. HRCJ/FOD)</label>
                            <input type="text" name="doc_prefix" x-model="prefix" required class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nomor Urut Terakhir (Last Sequence)</label>
                            <input type="number" name="last_sequence" x-model="lastSeq" min="0" required class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100 font-mono">
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Masukkan <strong>0</strong> jika ingin nomor SK berikutnya mulai dari <strong>0001</strong>.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Alamat Pemilik Kendaraan <span class="text-slate-400 font-normal">(Nullable / Optional)</span></label>
                        <textarea name="pemilik_alamat" rows="2" placeholder="Leave empty or enter address fallback" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100">{{ $suratKuasa['pemilik_alamat'] }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Default Recipient Email Address(es) <span class="text-slate-400 font-normal">(Comma-separated for multiple)</span></label>
                        <input type="text" name="default_recipient_email" value="{{ $suratKuasa['default_recipient_email'] ?? '' }}" placeholder="e.g. client@company.com, manager@company.com" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100">
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-200 dark:shadow-none flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Save Surat Kuasa Settings
                    </button>
                </div>
            </form>
        </div>




        <!-- Auto Surat Kuasa Configuration Card -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden"
             x-data="{ autoEnabled: {{ $suratKuasaAuto['auto_enabled'] ? 'true' : 'false' }} }">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2M12 3v4"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Auto Surat Kuasa — Automation Settings</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Automatically generate & email SK when No. Rangka + No. Mesin are both filled in Odoo</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span x-show="autoEnabled" class="px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold rounded-full flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> AUTO ON
                        </span>
                        <span x-show="!autoEnabled" style="display: none;" class="px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-bold rounded-full">
                            AUTO OFF
                        </span>
                    </div>
                </div>
            </div>

            <form action="{{ route('settings.surat-kuasa-auto') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <!-- Enable Toggle + Interval -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-indigo-50/50 dark:bg-indigo-950/20 rounded-xl border border-indigo-200 dark:border-indigo-800/50">
                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="auto_enabled" value="true" class="sr-only peer" {{ $suratKuasaAuto['auto_enabled'] ? 'checked' : '' }} id="auto-sk-toggle" @change="autoEnabled = $event.target.checked">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                        <div>
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300">Enable Auto-Generate</div>
                            <div class="text-[11px] text-slate-500" x-text="autoEnabled ? 'Status: Active (Will process ready units)' : 'Status: Disabled'"></div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Check Interval</label>
                        <select name="auto_interval" class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100">
                            <option value="every_30_min" {{ $suratKuasaAuto['auto_interval'] === 'every_30_min' ? 'selected' : '' }}>Every 30 Minutes</option>
                            <option value="hourly"        {{ $suratKuasaAuto['auto_interval'] === 'hourly' ? 'selected' : '' }}>Every Hour</option>
                            <option value="every_2_hours" {{ $suratKuasaAuto['auto_interval'] === 'every_2_hours' ? 'selected' : '' }}>Every 2 Hours</option>
                            <option value="every_4_hours" {{ $suratKuasaAuto['auto_interval'] === 'every_4_hours' ? 'selected' : '' }}>Every 4 Hours</option>
                            <option value="every_6_hours" {{ $suratKuasaAuto['auto_interval'] === 'every_6_hours' ? 'selected' : '' }}>Every 6 Hours</option>
                            <option value="daily"         {{ $suratKuasaAuto['auto_interval'] === 'daily' ? 'selected' : '' }}>Once Daily</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Document Format</label>
                        <select name="auto_format" class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100">
                            <option value="docx" {{ $suratKuasaAuto['auto_format'] === 'docx' ? 'selected' : '' }}>Word (.docx)</option>
                            <option value="pdf"  {{ $suratKuasaAuto['auto_format'] === 'pdf'  ? 'selected' : '' }}>PDF (.pdf)</option>
                        </select>
                    </div>
                </div>

                <!-- How it works info box -->
                <div class="p-4 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/40 rounded-xl space-y-2">
                    <div class="flex items-center gap-2 text-amber-700 dark:text-amber-400">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="text-xs font-bold">How Auto-Generate Works</span>
                    </div>
                    <ul class="text-[11px] text-amber-700 dark:text-amber-400 space-y-1 ml-6 list-disc">
                        <li>Runs on schedule (check interval above), separate from regular Odoo sync</li>
                        <li>Automatically detects units where <strong>No. Rangka + No. Mesin</strong> are both filled in Odoo</li>
                        <li>Generates SK document & sends email to the <strong>Default Recipient Email</strong> in SK Configuration above</li>
                        <li>Each unit is processed <strong>only once</strong> — marked with a timestamp to prevent duplicate sending</li>
                        <li>IT Admin can reset the auto-flag from the SK dashboard to allow re-processing</li>
                        <li>All auto-generated SKs appear in <strong>SK Report</strong> with <em>Generated By: System (Auto)</em></li>
                    </ul>
                </div>

                <!-- Optional auto Penerima defaults -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Auto Penerima Kuasa Nama
                            <span class="text-slate-400 font-normal">(Optional — leave blank for dotted lines)</span>
                        </label>
                        <input type="text" name="auto_penerima_nama" value="{{ $suratKuasaAuto['auto_penerima_nama'] }}" placeholder="Leave blank = dotted lines in document" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Auto Penerima Kuasa Alamat
                            <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <input type="text" name="auto_penerima_alamat" value="{{ $suratKuasaAuto['auto_penerima_alamat'] }}" placeholder="Leave blank = dotted lines in document" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100">
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-violet-600 text-white rounded-xl font-bold hover:bg-violet-700 transition-colors shadow-lg shadow-violet-200 dark:shadow-none flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Save Auto SK Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- About Section -->
        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-6 text-center">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                <span class="font-bold text-slate-700 dark:text-slate-300">SDP Stock Dashboard</span> &bull; 
                Version 1.0 &bull; 
                Built with Laravel & Alpine.js
            </p>
        </div>
    </div>
</div>
@endsection
