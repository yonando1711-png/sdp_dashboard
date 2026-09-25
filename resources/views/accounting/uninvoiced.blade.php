@extends('layouts.app')

@section('title', 'Uninvoiced Accounting - Accounting Report')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 font-bold text-[11px] border border-emerald-200 dark:border-emerald-700/80">
                    Accounting Report
                </span>
                <span class="text-xs text-slate-400">/</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Uninvoiced</span>
            </div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-slate-100 tracking-tight mt-1">
                Uninvoiced Accounting
            </h1>
            <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Track uninvoiced contracts, pending billing periods, and unbilled revenue across fiscal periods.
            </p>
        </div>

        <!-- Header Controls & Year Switcher -->
        <div class="flex items-center gap-2 shrink-0 flex-wrap sm:flex-nowrap">
            <!-- Year Selector Dropdown -->
            <div class="relative" x-data="{ openYear: false }" @click.outside="openYear = false">
                <button type="button" @click="openYear = !openYear"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 transition-all whitespace-nowrap">
                    <span>📅 Year: {{ $year }}</span>
                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openYear" x-cloak style="display: none;"
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-2 z-50 text-xs divide-y divide-slate-100 dark:divide-slate-700/60">
                    <div class="px-2 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Switch Fiscal Year</div>
                    <div class="py-1 space-y-1">
                        @foreach(range(max((int)$year, now()->year), 2024) as $yOpt)
                            <a href="{{ route('accounting.uninvoiced', array_merge(request()->except(['year']), ['year' => $yOpt])) }}"
                               class="flex items-center justify-between px-2.5 py-1.5 rounded-lg {{ $yOpt == $year ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 font-bold' : 'hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200' }}">
                                <span>{{ $yOpt }}{{ $yOpt == now()->year ? ' (Current)' : '' }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
        <form id="filterForm" method="GET" action="{{ route('accounting.uninvoiced') }}" class="flex flex-wrap items-center justify-between gap-3">
            <input type="hidden" name="year" value="{{ $year }}">

            <div class="flex flex-wrap items-center gap-3 flex-1">
                <!-- Search Input -->
                <div class="relative min-w-[220px] flex-1">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search Customer Name or Ref Code..." 
                           class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                {{-- Date Range Pickers (Universal Cross-Browser Month Pickers) --}}
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    {{-- From Month Picker --}}
                    <div x-data="monthPicker('start_month', '{{ $startMonth }}')" class="relative">
                        <input type="hidden" name="start_month" :value="value">
                        <button type="button" @click="toggle()" class="flex items-center gap-1.5 cursor-pointer select-none py-0.5 px-1 rounded-lg hover:bg-slate-200/50 dark:hover:bg-slate-800/50 transition-colors">
                            <span class="text-[11px] font-bold text-slate-500 uppercase">From:</span>
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-100" x-text="displayText"></span>
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </button>

                        {{-- Month Dropdown Modal --}}
                        <div x-show="open" 
                             @click.outside="open = false" 
                             x-transition 
                             x-cloak
                             class="absolute z-50 top-full mt-2 left-0 w-64 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl p-3 text-slate-800 dark:text-slate-100">
                            <!-- Year Selector -->
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100 dark:border-slate-800">
                                <button type="button" @click="year--" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <span class="font-bold text-sm tracking-wide" x-text="year"></span>
                                <button type="button" @click="year++" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                            <!-- 12 Months Grid -->
                            <div class="grid grid-cols-4 gap-1.5 py-1 text-xs">
                                <template x-for="(mName, idx) in monthNames" :key="idx">
                                    <button type="button" 
                                            @click="selectMonth(idx + 1)"
                                            :class="isSelected(idx + 1) ? 'bg-indigo-600 text-white font-bold shadow-sm' : (isCurrent(idx + 1) ? 'border border-indigo-500/50 text-indigo-500 dark:text-indigo-300 hover:bg-slate-100 dark:hover:bg-slate-800' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white')"
                                            class="py-2 rounded-xl text-center transition-all cursor-pointer font-medium"
                                            x-text="mName">
                                    </button>
                                </template>
                            </div>
                            <!-- Footer Actions -->
                            <div class="flex items-center justify-between pt-2 mt-2 border-t border-slate-100 dark:border-slate-800 text-[11px]">
                                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">Cancel</button>
                                <button type="button" @click="selectThisMonth()" class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">This month</button>
                            </div>
                        </div>
                    </div>

                    <span class="text-slate-400 text-xs px-0.5 select-none">&rarr;</span>

                    {{-- To Month Picker --}}
                    <div x-data="monthPicker('end_month', '{{ $endMonth }}')" class="relative">
                        <input type="hidden" name="end_month" :value="value">
                        <button type="button" @click="toggle()" class="flex items-center gap-1.5 cursor-pointer select-none py-0.5 px-1 rounded-lg hover:bg-slate-200/50 dark:hover:bg-slate-800/50 transition-colors">
                            <span class="text-[11px] font-bold text-slate-500 uppercase">To:</span>
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-100" x-text="displayText"></span>
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </button>

                        {{-- Month Dropdown Modal --}}
                        <div x-show="open" 
                             @click.outside="open = false" 
                             x-transition 
                             x-cloak
                             class="absolute z-50 top-full mt-2 right-0 w-64 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl p-3 text-slate-800 dark:text-slate-100">
                            <!-- Year Selector -->
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100 dark:border-slate-800">
                                <button type="button" @click="year--" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <span class="font-bold text-sm tracking-wide" x-text="year"></span>
                                <button type="button" @click="year++" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                            <!-- 12 Months Grid -->
                            <div class="grid grid-cols-4 gap-1.5 py-1 text-xs">
                                <template x-for="(mName, idx) in monthNames" :key="idx">
                                    <button type="button" 
                                            @click="selectMonth(idx + 1)"
                                            :class="isSelected(idx + 1) ? 'bg-indigo-600 text-white font-bold shadow-sm' : (isCurrent(idx + 1) ? 'border border-indigo-500/50 text-indigo-500 dark:text-indigo-300 hover:bg-slate-100 dark:hover:bg-slate-800' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white')"
                                            class="py-2 rounded-xl text-center transition-all cursor-pointer font-medium"
                                            x-text="mName">
                                    </button>
                                </template>
                            </div>
                            <!-- Footer Actions -->
                            <div class="flex items-center justify-between pt-2 mt-2 border-t border-slate-100 dark:border-slate-800 text-[11px]">
                                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">Cancel</button>
                                <button type="button" @click="selectThisMonth()" class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">This month</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit and Reset Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-indigo-600/20 cursor-pointer">
                    Apply Filter
                </button>
                <a href="{{ route('accounting.uninvoiced', ['year' => $year]) }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold rounded-xl transition-all">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Main Content State / Placeholder -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-8 text-center space-y-4 shadow-sm">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-2xl border border-emerald-200 dark:border-emerald-800">
            📑
        </div>
        <div class="max-w-md mx-auto">
            <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">
                Uninvoiced Accounting Report
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Showing range from <strong class="text-slate-700 dark:text-slate-200">{{ $startMonth }}</strong> to <strong class="text-slate-700 dark:text-slate-200">{{ $endMonth }}</strong> for Year <strong class="text-slate-700 dark:text-slate-200">{{ $year }}</strong>.
            </p>
        </div>
    </div>
</div>

<script>
function monthPicker(name, initialValue) {
    const fullMonths = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    let now = new Date();
    let initYear = now.getFullYear();
    let initMonth = now.getMonth() + 1;
    
    if (initialValue && typeof initialValue === 'string' && initialValue.includes('-')) {
        const parts = initialValue.split('-');
        initYear = parseInt(parts[0], 10) || initYear;
        initMonth = parseInt(parts[1], 10) || initMonth;
    }

    return {
        open: false,
        name: name,
        value: initialValue || `${initYear}-${String(initMonth).padStart(2, '0')}`,
        year: initYear,
        selectedYear: initYear,
        selectedMonth: initMonth,
        monthNames: shortMonths,
        
        get displayText() {
            if (!this.value || !this.value.includes('-')) return this.value;
            const [y, m] = this.value.split('-');
            const mIdx = parseInt(m, 10) - 1;
            return (fullMonths[mIdx] || '') + ' ' + y;
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.year = this.selectedYear;
            }
        },
        isSelected(m) {
            return this.year === this.selectedYear && m === this.selectedMonth;
        },
        isCurrent(m) {
            const today = new Date();
            return this.year === today.getFullYear() && m === (today.getMonth() + 1);
        },
        selectMonth(m) {
            this.selectedYear = this.year;
            this.selectedMonth = m;
            this.value = `${this.year}-${String(m).padStart(2, '0')}`;
            this.open = false;
        },
        selectThisMonth() {
            const today = new Date();
            this.year = today.getFullYear();
            this.selectMonth(today.getMonth() + 1);
        }
    };
}
</script>
@endsection
