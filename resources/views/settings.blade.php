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

    <div class="space-y-8">
        <!-- Chart Target Settings -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Chart Target Values</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Set target lines for the Historical Trends chart</p>
                    </div>
                </div>
            </div>
            
            <form action="{{ route('settings.targets') }}" method="POST" class="p-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Overview Targets -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Overview Targets</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">In Stock Target</label>
                            <div class="relative">
                                <input type="number" name="target_in_stock" value="{{ $targets['target_in_stock'] }}" min="0" 
                                    class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <span class="text-xs font-bold text-emerald-500 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded">Stock</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Rented Target</label>
                            <div class="relative">
                                <input type="number" name="target_rented" value="{{ $targets['target_rented'] }}" min="0"
                                    class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <span class="text-xs font-bold text-amber-500 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 rounded">Rented</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">In Service Target</label>
                            <div class="relative">
                                <input type="number" name="target_in_service" value="{{ $targets['target_in_service'] }}" min="0"
                                    class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <span class="text-xs font-bold text-red-500 bg-red-50 dark:bg-red-900/30 px-2 py-1 rounded">Service</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rental Type Targets -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Rental Type Targets</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Subscription Target</label>
                            <div class="relative">
                                <input type="number" name="target_subscription" value="{{ $targets['target_subscription'] }}" min="0"
                                    class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <span class="text-xs font-bold text-purple-500 bg-purple-50 dark:bg-purple-900/30 px-2 py-1 rounded">Sub</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Regular Target</label>
                            <div class="relative">
                                <input type="number" name="target_regular" value="{{ $targets['target_regular'] }}" min="0"
                                    class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <span class="text-xs font-bold text-blue-500 bg-blue-50 dark:bg-blue-900/30 px-2 py-1 rounded">Reg</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-200 dark:shadow-none flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Save Targets
                    </button>
                </div>
            </form>
        </div>

        <!-- Odoo Connection Settings -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/50 rounded-lg">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Odoo Connection</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Configure Odoo API connection settings</p>
                    </div>
                </div>
            </div>
            
            <form action="{{ route('settings.odoo') }}" method="POST" class="p-6">
                @csrf
                
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Odoo URL</label>
                        <input type="url" name="odoo_url" value="{{ $odoo['url'] }}" placeholder="https://your-odoo.com"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Database</label>
                            <input type="text" name="odoo_db" value="{{ $odoo['db'] }}" placeholder="database_name"
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Username</label>
                            <input type="text" name="odoo_user" value="{{ $odoo['user'] }}" placeholder="admin@example.com"
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">API Key / Password</label>
                        <input type="password" name="odoo_password" placeholder="Enter new password to update"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Leave blank to keep current password</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-purple-600 text-white rounded-xl font-medium hover:bg-purple-700 transition-colors shadow-lg shadow-purple-200 dark:shadow-none flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Save Odoo Settings
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
