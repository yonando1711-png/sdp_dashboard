@extends('layouts.app')

@section('title', 'SK System Logs - Surat Kuasa')

@section('content')
<div class="w-full space-y-6" x-data="skLogsApp()">

    @if(!$authenticated)
    <!-- UNLOCK PAGE MODAL / PROMPT -->
    <div class="min-h-[60vh] flex items-center justify-center py-12">
        <div class="w-full max-w-md bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 rounded-3xl p-8 shadow-2xl space-y-6 text-center">
            <div class="w-16 h-16 rounded-3xl bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center mx-auto text-white shadow-lg shadow-indigo-500/30">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Surat Kuasa Protected Access</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 font-medium">Enter secondary password to access SK Logs</p>
            </div>
            <form action="{{ route('surat-kuasa.auth') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1 text-left">
                    <label class="text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Secondary Password</label>
                    <input type="password" name="password" required autofocus placeholder="Enter PIN / Password" class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-[#050913] border border-slate-300 dark:border-slate-700/80 text-slate-900 dark:text-white text-sm font-semibold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-all">
                </div>
                <button type="submit" class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white font-extrabold text-sm shadow-lg shadow-indigo-500/25 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    Unlock SK Logs
                </button>
            </form>
        </div>
    </div>
    @else

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xl">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Surat Kuasa System & Automation Logs</h1>
                    <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-500 border border-rose-500/20 text-[10px] font-bold">IT Admin Only</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Diagnostic audit trail for Auto-Generation, Fast Sync, and Email delivery</p>
            </div>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <button type="button" @click="window.location.reload()" class="px-3.5 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all flex items-center gap-1.5" title="Refresh logs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Refresh
            </button>

            @if($stats['total'] > 0)
                <button type="button" @click="clearAllLogs()" class="px-3.5 py-2.5 rounded-2xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-bold border border-rose-500/20 transition-all flex items-center gap-1.5" title="Clear all logs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Clear Logs
                </button>
            @endif
        </div>
    </div>

    <!-- Metric KPI Stat Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <a href="{{ route('surat-kuasa.logs') }}" class="p-4 rounded-2xl bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 shadow-sm hover:border-indigo-500 transition-colors {{ empty($level) ? 'ring-2 ring-indigo-500' : '' }}">
            <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">Total Logs</div>
            <div class="text-xl font-extrabold text-slate-800 dark:text-white mt-1">{{ number_format($stats['total']) }}</div>
        </a>
        <a href="{{ route('surat-kuasa.logs', array_merge(request()->except('page'), ['level' => 'error'])) }}" class="p-4 rounded-2xl bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 shadow-sm hover:border-rose-500 transition-colors {{ $level === 'error' ? 'ring-2 ring-rose-500' : '' }}">
            <div class="text-[11px] font-bold text-rose-600 dark:text-rose-400 uppercase flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span> Errors
            </div>
            <div class="text-xl font-extrabold text-rose-600 dark:text-rose-400 mt-1">{{ number_format($stats['error']) }}</div>
        </a>
        <a href="{{ route('surat-kuasa.logs', array_merge(request()->except('page'), ['level' => 'warning'])) }}" class="p-4 rounded-2xl bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 shadow-sm hover:border-amber-500 transition-colors {{ $level === 'warning' ? 'ring-2 ring-amber-500' : '' }}">
            <div class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span> Warnings
            </div>
            <div class="text-xl font-extrabold text-amber-600 dark:text-amber-400 mt-1">{{ number_format($stats['warning']) }}</div>
        </a>
        <a href="{{ route('surat-kuasa.logs', array_merge(request()->except('page'), ['level' => 'success'])) }}" class="p-4 rounded-2xl bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 shadow-sm hover:border-emerald-500 transition-colors {{ $level === 'success' ? 'ring-2 ring-emerald-500' : '' }}">
            <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Success
            </div>
            <div class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($stats['success']) }}</div>
        </a>
        <a href="{{ route('surat-kuasa.logs', array_merge(request()->except('page'), ['level' => 'info'])) }}" class="p-4 rounded-2xl bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 shadow-sm hover:border-blue-500 transition-colors {{ $level === 'info' ? 'ring-2 ring-blue-500' : '' }}">
            <div class="text-[11px] font-bold text-blue-600 dark:text-blue-400 uppercase flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span> Info
            </div>
            <div class="text-xl font-extrabold text-blue-600 dark:text-blue-400 mt-1">{{ number_format($stats['info']) }}</div>
        </a>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-md flex flex-col md:flex-row items-center justify-between gap-3">
        <form method="GET" action="{{ route('surat-kuasa.logs') }}" class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            @if($level)
                <input type="hidden" name="level" value="{{ $level }}">
            @endif

            <!-- Event Type Filter -->
            <select name="event_type" onchange="this.form.submit()" class="px-3 py-2 rounded-xl bg-slate-50 dark:bg-[#050913] border border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 focus:outline-none focus:border-indigo-500">
                <option value="">All Event Types</option>
                <option value="auto_generate" {{ $eventType === 'auto_generate' ? 'selected' : '' }}>Auto-Generate</option>
                <option value="email_send"    {{ $eventType === 'email_send'    ? 'selected' : '' }}>Email Delivery</option>
                <option value="fast_sync"     {{ $eventType === 'fast_sync'     ? 'selected' : '' }}>Fast Sync</option>
                <option value="odoo_sync"     {{ $eventType === 'odoo_sync'     ? 'selected' : '' }}>Initial Discovery</option>
                <option value="system"        {{ $eventType === 'system'        ? 'selected' : '' }}>System</option>
            </select>

            <!-- Search Input -->
            <div class="relative min-w-[260px] flex-1">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search message, lot number, doc no..." class="w-full pl-9 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-[#050913] border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 font-medium">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-colors shadow-sm">
                Filter
            </button>

            @if($search || $level || $eventType)
                <a href="{{ route('surat-kuasa.logs') }}" class="px-3 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold hover:bg-slate-300">
                    Clear Filters
                </a>
            @endif
        </form>
    </div>

    <!-- Table Section -->
    <div class="bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-[#050913] border-b border-slate-200 dark:border-slate-800 text-slate-500 uppercase tracking-wider font-extrabold text-[10px]">
                    <tr>
                        <th class="py-4 px-5">Time</th>
                        <th class="py-4 px-5">Level</th>
                        <th class="py-4 px-5">Event Type</th>
                        <th class="py-4 px-5">Unit / Lot</th>
                        <th class="py-4 px-5">Message</th>
                        <th class="py-4 px-5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-5 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                                {{ $log->created_at->format('d M Y, H:i:s') }}
                            </td>
                            <td class="py-4 px-5">
                                @if($log->level === 'error')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 font-bold text-[10px] border border-rose-500/20">
                                        ● ERROR
                                    </span>
                                @elseif($log->level === 'warning')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold text-[10px] border border-amber-500/20">
                                        ▲ WARNING
                                    </span>
                                @elseif($log->level === 'success')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold text-[10px] border border-emerald-500/20">
                                        ✓ SUCCESS
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 font-bold text-[10px] border border-blue-500/20">
                                        ℹ INFO
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-5 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 font-mono text-slate-700 dark:text-slate-300 text-[10px] font-bold uppercase">
                                    {{ str_replace('_', ' ', $log->event_type) }}
                                </span>
                            </td>
                            <td class="py-4 px-5 font-mono">
                                @if($log->lot_number)
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $log->lot_number }}</div>
                                    @if($log->doc_no)
                                        <div class="text-[10px] text-indigo-600 dark:text-indigo-400">{{ $log->doc_no }}</div>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-4 px-5">
                                <div class="text-slate-800 dark:text-slate-200 max-w-xl font-medium leading-relaxed">
                                    {{ $log->message }}
                                </div>
                            </td>
                            <td class="py-4 px-5 text-right whitespace-nowrap space-x-1">
                                @if(!empty($log->details))
                                    <button type="button" @click="viewDetails({{ json_encode($log) }})" class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs inline-flex items-center gap-1" title="View details / stack trace">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        Details
                                    </button>
                                @endif
                                <button type="button" @click="deleteSingleLog({{ $log->id }})" class="px-2 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 font-bold text-xs inline-flex items-center" title="Delete log">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center text-slate-400">
                                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p class="text-sm font-bold">No system logs recorded yet</p>
                                <p class="text-xs text-slate-500 mt-1">Automation, sync, and email error events will be recorded here automatically.</p>
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

    <!-- DETAILS MODAL -->
    <div x-show="showDetailModal" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/90 backdrop-blur-md" x-transition>
        <div @click.away="showDetailModal = false" class="bg-white dark:bg-[#0d1322] border border-slate-200 dark:border-slate-700/80 rounded-3xl p-7 w-full max-w-3xl shadow-2xl space-y-5 text-slate-900 dark:text-slate-100 max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-500 font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Log Event Details</h3>
                        <p class="text-xs text-slate-400 font-mono" x-text="activeLog?.created_at"></p>
                    </div>
                </div>
                <button type="button" @click="showDetailModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>

            <div class="space-y-4 overflow-y-auto pr-2 flex-1 text-xs">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase">Message</label>
                    <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-xl font-medium mt-1 text-slate-800 dark:text-slate-200" x-text="activeLog?.message"></div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase">Level</label>
                        <div class="font-bold uppercase mt-1" x-text="activeLog?.level"></div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase">Event Type</label>
                        <div class="font-mono mt-1" x-text="activeLog?.event_type"></div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase">Unit / Doc</label>
                        <div class="font-mono mt-1" x-text="(activeLog?.lot_number || '—') + (activeLog?.doc_no ? ' (' + activeLog?.doc_no + ')' : '')"></div>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase">JSON Context / Error Payload</label>
                    <pre class="p-4 bg-slate-950 text-emerald-400 font-mono text-[11px] rounded-2xl overflow-x-auto mt-1 leading-relaxed max-h-72 select-all" x-text="JSON.stringify(activeLog?.details, null, 2)"></pre>
                </div>
            </div>

            <div class="flex justify-end pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="showDetailModal = false" class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function skLogsApp() {
    return {
        showDetailModal: false,
        activeLog: null,

        viewDetails(log) {
            this.activeLog = log;
            this.showDetailModal = true;
        },

        async deleteSingleLog(id) {
            if (!confirm('Delete this system log entry?')) return;
            try {
                const res = await fetch('{{ url('/surat-kuasa/logs') }}/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to delete log.');
                }
            } catch (e) {
                alert('Network error deleting log.');
            }
        },

        async clearAllLogs() {
            if (!confirm('Are you sure you want to CLEAR ALL system logs? This action cannot be undone.')) return;
            try {
                const res = await fetch('{{ route('surat-kuasa.logs.clear') }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to clear logs.');
                }
            } catch (e) {
                alert('Network error clearing logs.');
            }
        }
    };
}
</script>
@endsection
