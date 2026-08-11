@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ 
    showModal: false, 
    editMode: false, 
    form: { id: null, name: '', email: '', password: '', branch: 'ALL', role: 'branch_user', menu_permissions: [], can_view_lor_smd: false, allowed_salespersons: [], allowed_sales_teams: [] }, 
    salespersonTeamsMap: {{ json_encode($salespersonTeamsMap ?? []) }}, 
    allSalesTeams: {{ json_encode($allSalesTeams ?? []) }},
    get availableTeams() {
        if (!this.form.allowed_salespersons || this.form.allowed_salespersons.length === 0) {
            return this.allSalesTeams;
        }
        let teams = [];
        this.form.allowed_salespersons.forEach(sp => {
            if (this.salespersonTeamsMap[sp]) {
                teams = teams.concat(this.salespersonTeamsMap[sp]);
            }
        });
        return [...new Set(teams)].sort();
    }
}">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900/80 p-6 rounded-3xl border border-slate-200 dark:border-slate-800/80 backdrop-blur-xl shadow-lg dark:shadow-xl transition-all">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500/10 to-cyan-500/10 dark:from-indigo-500/20 dark:to-cyan-500/20 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 dark:border-indigo-500/30 flex items-center justify-center shrink-0 shadow-sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">User & Branch Management</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium leading-normal">Manage user accounts, assign branch scoping, and configure menu access controls</p>
            </div>
        </div>

        <button type="button" @click="editMode = false; form = { id: null, name: '', email: '', password: '', branch: 'ALL', role: 'branch_user', menu_permissions: [], can_view_lor_smd: false, allowed_salespersons: [], allowed_sales_teams: [] }; showModal = true" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-indigo-500 to-cyan-500 hover:from-indigo-600 hover:to-cyan-600 text-white text-xs font-extrabold shadow-lg shadow-indigo-500/25 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New User
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Users Table Card -->
    <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden backdrop-blur-xl shadow-lg dark:shadow-2xl transition-all">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                        <th class="py-4.5 px-6">User Name</th>
                        <th class="py-4.5 px-6">Email</th>
                        <th class="py-4.5 px-6">Role</th>
                        <th class="py-4.5 px-6">Branch Scoping</th>
                        <th class="py-4.5 px-6">Menu Access</th>
                        <th class="py-4.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs font-medium text-slate-700 dark:text-slate-300">
                    @foreach($users as $u)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-6 font-bold text-slate-900 dark:text-white flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500/10 to-cyan-500/10 dark:from-indigo-500/20 dark:to-cyan-500/20 border border-indigo-500/20 dark:border-indigo-500/30 flex items-center justify-center font-bold text-indigo-600 dark:text-indigo-400 shadow-sm">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $u->name }}</div>
                                    <div class="text-[10px] font-medium text-slate-500 dark:text-slate-400 sm:hidden">{{ $u->email }}</div>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-slate-600 dark:text-slate-300 font-medium">{{ $u->email }}</td>
                            <td class="py-4 px-6">
                                @if($u->isItAdmin())
                                    <span class="px-3 py-1 rounded-full text-[10px] font-extrabold bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/30 inline-flex items-center gap-1 shadow-sm">👑 IT Admin</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 inline-flex items-center gap-1">📍 Branch User</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @if($u->isNationwide())
                                    <span class="px-3 py-1 rounded-full text-[10px] font-extrabold bg-cyan-50 dark:bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-500/30 inline-flex items-center gap-1 shadow-sm">🌐 Nationwide (All Branches)</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30 inline-flex items-center gap-1 shadow-sm">🏢 {{ $u->branch }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex flex-wrap gap-1.5">
                                    @if($u->hasMenuPermission('dashboard')) <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-[10px] font-semibold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">Dashboard</span> @endif
                                    @if($u->hasMenuPermission('total-stock')) <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-[10px] font-semibold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">Total Stock</span> @endif
                                    @if($u->hasMenuPermission('rental-pairs')) <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-[10px] font-semibold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">Rental Pairs</span> @endif
                                    @if($u->hasMenuPermission('in-stock')) <span class="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/80 text-[10px] font-semibold text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700/80">In Stock</span> @endif
                                    @if($u->hasMenuPermission('active-rentals')) <span class="px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/80 text-[10px] font-semibold text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-700/80">Active Rental</span> @endif
                                    @if($u->hasMenuPermission('in-service')) <span class="px-2.5 py-1 rounded-lg bg-rose-50 dark:bg-rose-950/80 text-[10px] font-semibold text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-700/80">In Service</span> @endif
                                    @if($u->hasMenuPermission('lor')) <span class="px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/80 text-[10px] font-semibold text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700/80">LoR</span> @endif
                                    @if($u->hasMenuPermission('crm')) <span class="px-2.5 py-1 rounded-lg bg-purple-50 dark:bg-purple-950/80 text-[10px] font-semibold text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-700/80">CRM</span> @endif
                                    @if($u->hasMenuPermission('surat-kuasa')) <span class="px-2.5 py-1 rounded-lg bg-cyan-50 dark:bg-cyan-950/80 text-[10px] font-semibold text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-700/80">Surat Kuasa</span> @endif
                                    @if($u->canAccessSmd()) <span class="px-2.5 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-950/80 text-[10px] font-semibold text-indigo-700 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-700/80">LoR (SMD)</span> @endif
                                </div>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" @click="editMode = true; form = { id: {{ $u->id }}, name: '{{ addslashes($u->name) }}', email: '{{ addslashes($u->email) }}', password: '', branch: '{{ addslashes($u->branch) }}', role: '{{ addslashes($u->role) }}', menu_permissions: {{ json_encode($u->menu_permissions ?? ['dashboard', 'total-stock', 'rental-pairs', 'in-stock', 'active-rentals', 'in-service']) }}, can_view_lor_smd: {{ $u->can_view_lor_smd ? 'true' : 'false' }}, allowed_salespersons: {{ json_encode($u->getAllowedSalespersons()) }}, allowed_sales_teams: {{ json_encode($u->getAllowedSalesTeams()) }} }; showModal = true" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-[11px] font-bold transition-all shadow-sm">
                                        Edit
                                    </button>
                                    @if(auth()->id() != $u->id)
                                        <form action="{{ route('users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-500/10 dark:hover:bg-rose-500/20 border border-rose-200 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 text-[11px] font-bold transition-all shadow-sm">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
       <!-- Create / Edit User Modal (Spacious Wide Layout) -->
    <div x-show="showModal" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 md:p-8 bg-slate-900/40 dark:bg-slate-950/90 backdrop-blur-md dark:backdrop-blur-xl" x-transition>
        <div @click.away="showModal = false" class="bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-700/80 rounded-3xl p-7 md:p-8 w-full max-w-4xl shadow-2xl dark:shadow-[0_0_60px_rgba(0,0,0,0.95)] relative text-slate-900 dark:text-slate-100 transition-all space-y-5">
            
            <!-- Modal Title Bar -->
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800/80 pb-4 shrink-0">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500/10 to-cyan-500/10 dark:from-indigo-500/20 dark:to-cyan-500/20 border border-indigo-500/20 dark:border-indigo-500/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-inner shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-white tracking-tight" x-text="editMode ? 'Edit User Account' : 'Create New User Account'"></h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Configure credentials, role branch scope, and menu permissions</p>
                    </div>
                </div>
                <button type="button" @click="showModal = false" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Form Body -->
            <form :action="editMode ? '{{ url('/settings/users') }}/' + form.id : '{{ route('users.store') }}'" method="POST" class="space-y-4.5">
                @csrf
                <template x-if="editMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- Row 1: Username & Email (2 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- User Name Field -->
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <span>User Name</span>
                        </label>
                        <input type="text" name="name" x-model="form.name" required placeholder="e.g. Surabaya Operation Team" class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-300 dark:border-slate-700/80 text-slate-900 dark:text-white text-xs font-semibold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-all placeholder:text-slate-400 dark:placeholder:text-slate-600">
                    </div>

                    <!-- Email Address Field -->
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-cyan-500 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span>Email Address</span>
                        </label>
                        <input type="email" name="email" x-model="form.email" required placeholder="name@harent.co.id" class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-300 dark:border-slate-700/80 text-slate-900 dark:text-white text-xs font-semibold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-all placeholder:text-slate-400 dark:placeholder:text-slate-600">
                    </div>
                </div>

                <!-- Row 2: Password, Role Type, Branch Scoping (3 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Password Field -->
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 justify-between">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-emerald-500 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                Password
                            </span>
                        </label>
                        <input type="password" name="password" :required="!editMode" placeholder="••••••••" class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-300 dark:border-slate-700/80 text-slate-900 dark:text-white text-xs font-semibold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-all placeholder:text-slate-400 dark:placeholder:text-slate-600">
                    </div>

                    <!-- Role Type Field -->
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <span>Role Type</span>
                        </label>
                        <select name="role" x-model="form.role" class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-300 dark:border-slate-700/80 text-slate-900 dark:text-white text-xs font-semibold focus:border-indigo-500 focus:outline-none cursor-pointer">
                            <option value="branch_user">📍 Branch Account</option>
                            <option value="it_admin">👑 IT Admin (Superuser)</option>
                        </select>
                    </div>

                    <!-- Branch Scoping Field -->
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                            <span>Branch Scoping</span>
                        </label>
                        <select name="branch" x-model="form.branch" class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-300 dark:border-slate-700/80 text-slate-900 dark:text-white text-xs font-semibold focus:border-indigo-500 focus:outline-none cursor-pointer">
                            <option value="ALL">🌐 ALL (Nationwide / HQ)</option>
                            <option value="JKT">🏢 JAKARTA (Nationwide View)</option>
                            <option value="SUB">📍 SURABAYA</option>
                            <option value="SMG">📍 SEMARANG</option>
                            <option value="DPS">📍 BALI / DENPASAR</option>
                            @foreach($odooBranches as $b)
                                @if(!in_array($b, ['JAKARTA', 'SURABAYA', 'SEMARANG', 'DENPASAR', 'BALI']))
                                    <option value="{{ $b }}">📍 {{ $b }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Row 3: Allowed Navigation Menus (Wide 4-Column Grid) -->
                <div class="space-y-2 pt-1">
                    <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        <span>Allowed Navigation Menus</span>
                    </label>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs p-3.5 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-200 dark:border-slate-800">
                        <label class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-200/50 dark:hover:bg-slate-900 cursor-pointer transition-colors text-slate-800 dark:text-slate-200">
                            <input type="checkbox" name="menu_permissions[]" value="dashboard" x-model="form.menu_permissions" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="font-semibold">Dashboard</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-200/50 dark:hover:bg-slate-900 cursor-pointer transition-colors text-slate-800 dark:text-slate-200">
                            <input type="checkbox" name="menu_permissions[]" value="total-stock" x-model="form.menu_permissions" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="font-semibold">Total Stock</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-200/50 dark:hover:bg-slate-900 cursor-pointer transition-colors text-slate-800 dark:text-slate-200">
                            <input type="checkbox" name="menu_permissions[]" value="rental-pairs" x-model="form.menu_permissions" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="font-semibold">Rental Pairs</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-200/50 dark:hover:bg-slate-900 cursor-pointer transition-colors text-slate-800 dark:text-slate-200">
                            <input type="checkbox" name="menu_permissions[]" value="in-stock" x-model="form.menu_permissions" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="font-semibold">In Stock</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-200/50 dark:hover:bg-slate-900 cursor-pointer transition-colors text-slate-800 dark:text-slate-200">
                            <input type="checkbox" name="menu_permissions[]" value="active-rentals" x-model="form.menu_permissions" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="font-semibold">Active Rental</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-200/50 dark:hover:bg-slate-900 cursor-pointer transition-colors text-slate-800 dark:text-slate-200">
                            <input type="checkbox" name="menu_permissions[]" value="in-service" x-model="form.menu_permissions" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="font-semibold">In Service</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-200/50 dark:hover:bg-slate-900 cursor-pointer transition-colors text-slate-800 dark:text-slate-300">
                            <input type="checkbox" name="menu_permissions[]" value="lor" x-model="form.menu_permissions" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <div class="flex items-center justify-between w-full">
                                <span class="font-semibold">LoR</span>
                                <span class="text-[9px] font-bold px-1 py-0.5 rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">JKT/IT</span>
                            </div>
                        </label>
                        <!-- LoR (SMD) Navigation Item Checkbox -->
                        <label class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-200/50 dark:hover:bg-slate-900 cursor-pointer transition-colors text-slate-800 dark:text-slate-300 border border-indigo-500/20 bg-indigo-500/5">
                            <input type="checkbox" name="can_view_lor_smd" value="1" x-model="form.can_view_lor_smd" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <div class="flex items-center justify-between w-full">
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">LoR (SMD)</span>
                                <span class="text-[9px] font-bold px-1 py-0.5 rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">SMD</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-200/50 dark:hover:bg-slate-900 cursor-pointer transition-colors text-slate-800 dark:text-slate-300">
                            <input type="checkbox" name="menu_permissions[]" value="crm" x-model="form.menu_permissions" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <div class="flex items-center justify-between w-full">
                                <span class="font-semibold">CRM</span>
                                <span class="text-[9px] font-bold px-1 py-0.5 rounded bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300">JKT/IT</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-200/50 dark:hover:bg-slate-900 cursor-pointer transition-colors text-slate-800 dark:text-slate-300">
                            <input type="checkbox" name="menu_permissions[]" value="surat-kuasa" x-model="form.menu_permissions" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <div class="flex items-center justify-between w-full">
                                <span class="font-semibold">Surat Kuasa</span>
                                <span class="text-[9px] font-bold px-1 py-0.5 rounded bg-cyan-100 text-cyan-700 dark:bg-cyan-500/20 dark:text-cyan-300">JKT/IT</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Row 4: Scoped Salesperson & Team Selection (2-Column Grid when LoR (SMD) is checked) -->
                <div x-show="form.can_view_lor_smd" x-transition class="p-4 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-indigo-500/20">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Custom Interactive Multi-Select Dropdown for Salespersons -->
                        <div class="space-y-1.5 relative" x-data="{ open: false }">
                            <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    Allowed Salespersons (Odoo)
                                </span>
                                <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold" x-text="form.allowed_salespersons.length + ' selected'"></span>
                            </label>

                            <!-- Hidden Inputs for Form Submission -->
                            <template x-for="sp in form.allowed_salespersons" :key="'hidden-sp-' + sp">
                                <input type="hidden" name="allowed_salespersons[]" :value="sp">
                            </template>

                            <!-- Clickable Trigger Box -->
                            <div @click="open = !open" class="w-full px-3.5 py-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700/80 text-slate-900 dark:text-white text-xs font-semibold cursor-pointer flex items-center justify-between min-h-[44px] transition-all hover:border-indigo-500 shadow-sm">
                                <div class="flex flex-wrap gap-1.5 items-center">
                                    <template x-if="!form.allowed_salespersons || form.allowed_salespersons.length === 0">
                                        <span class="text-slate-400 dark:text-slate-500 font-normal">Select Salesperson(s)...</span>
                                    </template>
                                    <template x-for="sp in form.allowed_salespersons" :key="'badge-sp-' + sp">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-xl bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700 text-[11px] font-bold shadow-sm">
                                            <span x-text="'👤 ' + sp"></span>
                                            <button type="button" @click.stop="form.allowed_salespersons = form.allowed_salespersons.filter(s => s !== sp)" class="hover:text-rose-500 font-bold ml-1 transition-colors">✕</button>
                                        </span>
                                    </template>
                                </div>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform ml-2" :class="open ? 'rotate-180 text-indigo-500' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>

                            <!-- Dropdown Popup Panel (Opens UPWARDS into open space) -->
                            <div x-show="open" @click.away="open = false" x-transition class="absolute z-50 left-0 right-0 bottom-full mb-1.5 p-2 bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl max-h-52 overflow-y-auto space-y-1">
                                @foreach($odooSalespersons as $sp)
                                    <label class="flex items-center justify-between p-2 rounded-xl hover:bg-indigo-50 dark:hover:bg-slate-800/80 cursor-pointer text-xs transition-colors">
                                        <span class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-2.5">
                                            <input type="checkbox" value="{{ $sp }}" x-model="form.allowed_salespersons" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                                            <span>👤 {{ $sp }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Custom Interactive Multi-Select Dropdown for Scoped Sales Teams -->
                        <div class="space-y-1.5 relative" x-data="{ open: false }">
                            <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-cyan-500 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    Allowed Sales Teams (Scoped)
                                </span>
                                <span class="text-[10px] text-cyan-600 dark:text-cyan-400 font-bold" x-text="form.allowed_sales_teams.length + ' selected'"></span>
                            </label>

                            <!-- Hidden Inputs for Form Submission -->
                            <template x-for="team in form.allowed_sales_teams" :key="'hidden-team-' + team">
                                <input type="hidden" name="allowed_sales_teams[]" :value="team">
                            </template>

                            <!-- Clickable Trigger Box -->
                            <div @click="open = !open" class="w-full px-3.5 py-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700/80 text-slate-900 dark:text-white text-xs font-semibold cursor-pointer flex items-center justify-between min-h-[44px] transition-all hover:border-cyan-500 shadow-sm">
                                <div class="flex flex-wrap gap-1.5 items-center">
                                    <template x-if="!form.allowed_sales_teams || form.allowed_sales_teams.length === 0">
                                        <span class="text-slate-400 dark:text-slate-500 font-normal">Select Sales Team(s)...</span>
                                    </template>
                                    <template x-for="team in form.allowed_sales_teams" :key="'badge-team-' + team">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-xl bg-cyan-100 dark:bg-cyan-900/60 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-700 text-[11px] font-bold shadow-sm">
                                            <span x-text="'🚩 ' + team"></span>
                                            <button type="button" @click.stop="form.allowed_sales_teams = form.allowed_sales_teams.filter(t => t !== team)" class="hover:text-rose-500 font-bold ml-1 transition-colors">✕</button>
                                        </span>
                                    </template>
                                </div>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform ml-2" :class="open ? 'rotate-180 text-cyan-500' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>

                            <!-- Dropdown Popup Panel (Opens UPWARDS into open space) -->
                            <div x-show="open" @click.away="open = false" x-transition class="absolute z-50 left-0 right-0 bottom-full mb-1.5 p-2 bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl max-h-52 overflow-y-auto space-y-1">
                                <template x-for="team in availableTeams" :key="'opt-team-' + team">
                                    <label class="flex items-center justify-between p-2 rounded-xl hover:bg-cyan-50 dark:hover:bg-slate-800/80 cursor-pointer text-xs transition-colors">
                                        <span class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-2.5">
                                            <input type="checkbox" :value="team" x-model="form.allowed_sales_teams" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-cyan-600 focus:ring-cyan-500">
                                            <span x-text="'🚩 ' + team"></span>
                                        </span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 dark:border-slate-800/80 pt-4 mt-4 shrink-0">
                    <button type="button" @click="showModal = false" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-cyan-500 hover:from-indigo-600 hover:to-cyan-600 text-white text-xs font-extrabold shadow-lg shadow-indigo-500/25 transition-all hover:scale-[1.02] active:scale-[0.98]" x-text="editMode ? 'Save Changes' : 'Create Account'">
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
