@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ showModal: false, editMode: false, form: { id: null, name: '', email: '', password: '', branch: 'SURABAYA', role: 'branch_user', menu_permissions: ['dashboard', 'total-stock', 'rental-pairs', 'in-stock', 'active-rentals', 'in-service'] } }">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900/80 p-6 rounded-3xl border border-slate-800/80 backdrop-blur-xl shadow-xl">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500/20 to-cyan-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center shrink-0 shadow-sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-extrabold text-white tracking-tight">User & Branch Management</h1>
                <p class="text-xs text-slate-400 mt-1 font-medium leading-normal">Manage user accounts, assign branch scoping, and configure menu access controls</p>
            </div>
        </div>

        <button type="button" @click="editMode = false; form = { id: null, name: '', email: '', password: '', branch: 'SURABAYA', role: 'branch_user', menu_permissions: ['dashboard', 'total-stock', 'rental-pairs', 'in-stock', 'active-rentals', 'in-service'] }; showModal = true" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-indigo-500 to-cyan-500 hover:from-indigo-600 hover:to-cyan-600 text-white text-xs font-extrabold shadow-lg shadow-indigo-500/25 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New User
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Users Table Card -->
    <div class="bg-slate-900/70 border border-slate-800 rounded-3xl overflow-hidden backdrop-blur-xl shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-950/80 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-4.5 px-6">User Name</th>
                        <th class="py-4.5 px-6">Email</th>
                        <th class="py-4.5 px-6">Role</th>
                        <th class="py-4.5 px-6">Branch Scoping</th>
                        <th class="py-4.5 px-6">Menu Access</th>
                        <th class="py-4.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-xs font-medium text-slate-300">
                    @foreach($users as $u)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-6 font-bold text-white flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500/20 to-cyan-500/20 border border-indigo-500/30 flex items-center justify-center font-bold text-indigo-400 shadow-sm">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-white">{{ $u->name }}</div>
                                    <div class="text-[10px] font-medium text-slate-400 sm:hidden">{{ $u->email }}</div>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-slate-300 font-medium">{{ $u->email }}</td>
                            <td class="py-4 px-6">
                                @if($u->isItAdmin())
                                    <span class="px-3 py-1 rounded-full text-[10px] font-extrabold bg-indigo-500/10 text-indigo-400 border border-indigo-500/30 inline-flex items-center gap-1 shadow-sm">👑 IT Admin</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-[10px] font-extrabold bg-slate-800 text-slate-300 border border-slate-700 inline-flex items-center gap-1">📍 Branch User</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @if($u->isNationwide())
                                    <span class="px-3 py-1 rounded-full text-[10px] font-extrabold bg-cyan-500/10 text-cyan-400 border border-cyan-500/30 inline-flex items-center gap-1 shadow-sm">🌐 Nationwide (All Branches)</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-[10px] font-extrabold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 inline-flex items-center gap-1 shadow-sm">🏢 {{ $u->branch }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex flex-wrap gap-1.5">
                                    @if($u->hasMenuPermission('dashboard')) <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-[10px] font-semibold text-slate-300 border border-slate-700">Dashboard</span> @endif
                                    @if($u->hasMenuPermission('total-stock')) <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-[10px] font-semibold text-slate-300 border border-slate-700">Total Stock</span> @endif
                                    @if($u->hasMenuPermission('rental-pairs')) <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-[10px] font-semibold text-slate-300 border border-slate-700">Rental Pairs</span> @endif
                                    @if($u->hasMenuPermission('in-stock')) <span class="px-2.5 py-1 rounded-lg bg-emerald-950/80 text-[10px] font-semibold text-emerald-300 border border-emerald-700/80">In Stock</span> @endif
                                    @if($u->hasMenuPermission('active-rentals')) <span class="px-2.5 py-1 rounded-lg bg-amber-950/80 text-[10px] font-semibold text-amber-300 border border-amber-700/80">Active Rental</span> @endif
                                    @if($u->hasMenuPermission('in-service')) <span class="px-2.5 py-1 rounded-lg bg-rose-950/80 text-[10px] font-semibold text-rose-300 border border-rose-700/80">In Service</span> @endif
                                    @if($u->hasMenuPermission('lor')) <span class="px-2.5 py-1 rounded-lg bg-indigo-950/80 text-[10px] font-semibold text-indigo-300 border border-indigo-700/80">LoR</span> @endif
                                    @if($u->hasMenuPermission('crm')) <span class="px-2.5 py-1 rounded-lg bg-purple-950/80 text-[10px] font-semibold text-purple-300 border border-purple-700/80">CRM</span> @endif
                                </div>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" @click="editMode = true; form = { id: {{ $u->id }}, name: '{{ addslashes($u->name) }}', email: '{{ addslashes($u->email) }}', password: '', branch: '{{ addslashes($u->branch) }}', role: '{{ addslashes($u->role) }}', menu_permissions: {{ json_encode($u->menu_permissions ?? ['dashboard', 'total-stock', 'rental-pairs', 'in-stock', 'active-rentals', 'in-service']) }} }; showModal = true" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 text-[11px] font-bold transition-all shadow-sm">
                                        Edit
                                    </button>
                                    @if(auth()->id() != $u->id)
                                        <form action="{{ route('users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 text-rose-400 text-[11px] font-bold transition-all shadow-sm">
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
        </div>
    </div>

    <!-- Create / Edit User Modal -->
    <div x-show="showModal" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-xl" x-transition>
        <div @click.away="showModal = false" class="border border-slate-700/80 rounded-3xl p-7 w-full max-w-xl shadow-[0_0_60px_rgba(0,0,0,0.95)] space-y-6 relative text-slate-100" style="background-color: #0d1322 !important;">
            
            <!-- Modal Title Bar -->
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500/20 to-cyan-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 shadow-inner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-white tracking-tight" x-text="editMode ? 'Edit User Account' : 'Create New User Account'"></h3>
                        <p class="text-[11px] text-slate-400 font-medium">Configure credentials, role branch scope, and menu permissions</p>
                    </div>
                </div>
                <button type="button" @click="showModal = false" class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Form -->
            <form :action="editMode ? '{{ url('/settings/users') }}/' + form.id : '{{ route('users.store') }}'" method="POST" class="space-y-5">
                @csrf
                <template x-if="editMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- Name Field -->
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span>User Name</span>
                    </label>
                    <input type="text" name="name" x-model="form.name" required placeholder="e.g. Surabaya Operation Team" class="w-full px-4 py-3 rounded-2xl border border-slate-700/80 text-white text-xs font-semibold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-all placeholder:text-slate-600" style="background-color: #050913 !important;">
                </div>

                <!-- Email Field -->
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <span>Email Address</span>
                    </label>
                    <input type="email" name="email" x-model="form.email" required placeholder="name@harent.co.id" class="w-full px-4 py-3 rounded-2xl border border-slate-700/80 text-white text-xs font-semibold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-all placeholder:text-slate-600" style="background-color: #050913 !important;">
                </div>

                <!-- Password Field -->
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5 justify-between">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            Password
                        </span>
                        <span x-show="editMode" class="text-[10px] text-slate-400 lowercase font-normal">(leave blank to keep unchanged)</span>
                    </label>
                    <input type="password" name="password" :required="!editMode" placeholder="••••••••" class="w-full px-4 py-3 rounded-2xl border border-slate-700/80 text-white text-xs font-semibold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-all placeholder:text-slate-600" style="background-color: #050913 !important;">
                </div>

                <!-- Role & Branch Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <span>Role Type</span>
                        </label>
                        <select name="role" x-model="form.role" class="w-full px-4 py-3 rounded-2xl border border-slate-700/80 text-white text-xs font-semibold focus:border-indigo-500 focus:outline-none cursor-pointer" style="background-color: #050913 !important;">
                            <option value="branch_user">📍 Branch Account</option>
                            <option value="it_admin">👑 IT Admin (Superuser)</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                            <span>Branch Scoping</span>
                        </label>
                        <select name="branch" x-model="form.branch" class="w-full px-4 py-3 rounded-2xl border border-slate-700/80 text-white text-xs font-semibold focus:border-indigo-500 focus:outline-none cursor-pointer" style="background-color: #050913 !important;">
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

                <!-- Navigation Permissions (Interactive Cards) -->
                <div class="space-y-2 pt-1">
                    <label class="text-[11px] font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        <span>Allowed Navigation Menus</span>
                    </label>

                    <div class="grid grid-cols-2 gap-2 text-xs p-3 rounded-2xl border border-slate-800" style="background-color: #050913 !important;">
                        <label class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-slate-900 cursor-pointer transition-colors text-slate-200">
                            <input type="checkbox" name="menu_permissions[]" value="dashboard" x-model="form.menu_permissions" class="w-4 h-4 rounded border-slate-700 text-indigo-500 focus:ring-indigo-500">
                            <span class="font-semibold">Dashboard</span>
                        </label>
                        <label class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-slate-900 cursor-pointer transition-colors text-slate-200">
                            <input type="checkbox" name="menu_permissions[]" value="total-stock" x-model="form.menu_permissions" class="w-4 h-4 rounded border-slate-700 text-indigo-500 focus:ring-indigo-500">
                            <span class="font-semibold">Total Stock</span>
                        </label>
                        <label class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-slate-900 cursor-pointer transition-colors text-slate-200">
                            <input type="checkbox" name="menu_permissions[]" value="rental-pairs" x-model="form.menu_permissions" class="w-4 h-4 rounded border-slate-700 text-indigo-500 focus:ring-indigo-500">
                            <span class="font-semibold">Rental Pairs</span>
                        </label>
                        <label class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-slate-900 cursor-pointer transition-colors text-slate-200">
                            <input type="checkbox" name="menu_permissions[]" value="in-stock" x-model="form.menu_permissions" class="w-4 h-4 rounded border-slate-700 text-indigo-500 focus:ring-indigo-500">
                            <span class="font-semibold">In Stock (Inventory)</span>
                        </label>
                        <label class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-slate-900 cursor-pointer transition-colors text-slate-200">
                            <input type="checkbox" name="menu_permissions[]" value="active-rentals" x-model="form.menu_permissions" class="w-4 h-4 rounded border-slate-700 text-indigo-500 focus:ring-indigo-500">
                            <span class="font-semibold">Active Rental (Inventory)</span>
                        </label>
                        <label class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-slate-900 cursor-pointer transition-colors text-slate-200">
                            <input type="checkbox" name="menu_permissions[]" value="in-service" x-model="form.menu_permissions" class="w-4 h-4 rounded border-slate-700 text-indigo-500 focus:ring-indigo-500">
                            <span class="font-semibold">In Service (Inventory)</span>
                        </label>
                        <label class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-slate-900 cursor-pointer transition-colors text-slate-300">
                            <input type="checkbox" name="menu_permissions[]" value="lor" x-model="form.menu_permissions" :disabled="form.branch !== 'JKT' && form.branch !== 'ALL' && form.role !== 'it_admin'" class="w-4 h-4 rounded border-slate-700 text-indigo-500 focus:ring-indigo-500">
                            <div class="flex items-center justify-between w-full">
                                <span class="font-semibold">LoR</span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">JKT/IT</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-slate-900 cursor-pointer transition-colors text-slate-300">
                            <input type="checkbox" name="menu_permissions[]" value="crm" x-model="form.menu_permissions" :disabled="form.branch !== 'JKT' && form.branch !== 'ALL' && form.role !== 'it_admin'" class="w-4 h-4 rounded border-slate-700 text-indigo-500 focus:ring-indigo-500">
                            <div class="flex items-center justify-between w-full">
                                <span class="font-semibold">CRM</span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-purple-500/20 text-purple-300 border border-purple-500/30">JKT/IT</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="flex items-center justify-end gap-3 border-t border-slate-800/80 pt-5 mt-6">
                    <button type="button" @click="showModal = false" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition-all">
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
