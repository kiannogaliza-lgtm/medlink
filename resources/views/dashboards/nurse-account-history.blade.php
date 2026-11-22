@extends('layouts.nurse')

@section('title', 'Account History Logs')
@section('page-title', 'Account History Logs')

@section('styles')
<style>
    .log-filter-tab {
        padding: 8px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .log-filter-tab:hover {
        background: #f1f5f9;
        color: #1e293b;
    }
    .log-filter-tab.active {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
    }

    /* Role badges: clean colored text without border or background box */
    .role-badge-nurse { color: #7c3aed; }
    .role-badge-bhw { color: #2563eb; }
    .role-badge-resident { color: #16a34a; }

    /* Action badges: clean colored text with icon without border box */
    .action-badge-created { color: #059669; }
    .action-badge-login { color: #2563eb; }
    .action-badge-updated { color: #d97706; }
    .action-badge-reset { color: #dc2626; }
</style>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Header Banner --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <span class="material-icons-outlined text-blue-600" style="font-size:24px;">history</span>
                    Account History Logs
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Audit trail tracking resident, BHW, and nurse account creation dates, creator details, and log-in events.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('nurse.account-history.export-excel', request()->only(['action', 'role', 'search'])) }}"
                   class="px-3.5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg flex items-center gap-1.5 shadow-xs transition-colors cursor-pointer">
                    <span class="material-icons-outlined" style="font-size:16px;">download</span>
                    <span>Export to Excel</span>
                </a>
                @if($totalLogs > 0)
                <button type="button" onclick="openClearHistoryModal()"
                        class="px-3.5 py-2 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg flex items-center gap-1.5 transition-colors cursor-pointer">
                    <span class="material-icons-outlined" style="font-size:16px;">delete_sweep</span>
                    <span>Clear All History</span>
                </button>
                @endif
                <button type="button" onclick="window.print()" class="px-3.5 py-2 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center gap-1.5 transition-colors cursor-pointer">
                    <span class="material-icons-outlined" style="font-size:16px;">print</span>
                    <span>Print Log</span>
                </button>
            </div>
        </div>

        {{-- Filters & Search Bar --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mt-6 pt-5 border-t border-gray-100">
            {{-- Tabs --}}
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('nurse.account-history', ['search' => request('search'), 'role' => request('role')]) }}"
                   class="log-filter-tab {{ !request('action') || request('action') === 'all' ? 'active' : '' }}">
                    <span>All Logs</span>
                    <span class="text-xs px-1.5 py-0.2 rounded-full {{ !request('action') || request('action') === 'all' ? 'bg-white/25' : 'bg-gray-100' }}">
                        {{ $totalLogs }}
                    </span>
                </a>
                <a href="{{ route('nurse.account-history', ['action' => 'Account Created', 'search' => request('search'), 'role' => request('role')]) }}"
                   class="log-filter-tab {{ request('action') === 'Account Created' ? 'active' : '' }}">
                    <span class="material-icons-outlined" style="font-size:16px;">person_add</span>
                    <span>Account Created</span>
                    <span class="text-xs px-1.5 py-0.2 rounded-full {{ request('action') === 'Account Created' ? 'bg-white/25' : 'bg-green-100 text-green-700' }}">
                        {{ $accountCreatedCount }}
                    </span>
                </a>
                <a href="{{ route('nurse.account-history', ['action' => 'Logged In', 'search' => request('search'), 'role' => request('role')]) }}"
                   class="log-filter-tab {{ request('action') === 'Logged In' ? 'active' : '' }}">
                    <span class="material-icons-outlined" style="font-size:16px;">login</span>
                    <span>Logged In</span>
                    <span class="text-xs px-1.5 py-0.2 rounded-full {{ request('action') === 'Logged In' ? 'bg-white/25' : 'bg-blue-100 text-blue-700' }}">
                        {{ $loginCount }}
                    </span>
                </a>
                <a href="{{ route('nurse.account-history', ['action' => 'Account Deactivated', 'search' => request('search'), 'role' => request('role')]) }}"
                   class="log-filter-tab {{ request('action') === 'Account Deactivated' ? 'active' : '' }}">
                    <span class="material-icons-outlined" style="font-size:16px;">person_off</span>
                    <span>Deactivated</span>
                    <span class="text-xs px-1.5 py-0.2 rounded-full {{ request('action') === 'Account Deactivated' ? 'bg-white/25' : 'bg-amber-100 text-amber-700' }}">
                        {{ $deactivatedCount ?? 0 }}
                    </span>
                </a>
                <a href="{{ route('nurse.account-history', ['action' => 'Account Deleted', 'search' => request('search'), 'role' => request('role')]) }}"
                   class="log-filter-tab {{ request('action') === 'Account Deleted' ? 'active' : '' }}">
                    <span class="material-icons-outlined" style="font-size:16px;">delete_forever</span>
                    <span>Deleted</span>
                    <span class="text-xs px-1.5 py-0.2 rounded-full {{ request('action') === 'Account Deleted' ? 'bg-white/25' : 'bg-red-100 text-red-700' }}">
                        {{ $deletedCount ?? 0 }}
                    </span>
                </a>
            </div>

            {{-- Search & Role filter --}}
            <form method="GET" action="{{ route('nurse.account-history') }}" class="flex items-center gap-2">
                @if(request('action'))
                    <input type="hidden" name="action" value="{{ request('action') }}">
                @endif
                <select name="role" onchange="this.form.submit()" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="all">All Roles</option>
                    <option value="resident" {{ request('role') === 'resident' ? 'selected' : '' }}>Resident</option>
                    <option value="bhw" {{ request('role') === 'bhw' ? 'selected' : '' }}>BHW</option>
                    <option value="nurse" {{ request('role') === 'nurse' ? 'selected' : '' }}>Nurse</option>
                </select>
                <div class="relative min-w-[220px]">
                    <span class="material-icons-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" style="font-size:17px;">search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search user or phone..."
                           class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                </div>
            </form>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Total History Logs</p>
                <p class="text-2xl font-black text-gray-900">{{ $totalLogs }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <span class="material-icons-outlined" style="font-size:22px;">format_list_bulleted</span>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider mb-0.5">Accounts Created</p>
                <p class="text-2xl font-black text-emerald-600">{{ $accountCreatedCount }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-icons-outlined" style="font-size:22px;">person_add</span>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-blue-600 uppercase tracking-wider mb-0.5">Log-in Events</p>
                <p class="text-2xl font-black text-blue-600">{{ $loginCount }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <span class="material-icons-outlined" style="font-size:22px;">lock_clock</span>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-purple-600 uppercase tracking-wider mb-0.5">Today's Activity</p>
                <p class="text-2xl font-black text-purple-600">{{ $todayActivities }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <span class="material-icons-outlined" style="font-size:22px;">today</span>
            </div>
        </div>
    </div>

    {{-- Main Logs Table --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-5">Date & Time</th>
                        <th class="py-3.5 px-5">User Account</th>
                        <th class="py-3.5 px-5">Role</th>
                        <th class="py-3.5 px-5">Event / Action</th>
                        <th class="py-3.5 px-5">Created By / Actor</th>
                        <th class="py-3.5 px-5">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-blue-50/20 transition-colors">
                            {{-- Date & Time --}}
                            <td class="py-4 px-5 whitespace-nowrap">
                                <div class="font-bold text-gray-900">
                                    {{ $log->logged_at ? $log->logged_at->format('M d, Y') : 'N/A' }}
                                </div>
                                <div class="text-[11px] text-gray-500 font-medium">
                                    {{ $log->logged_at ? $log->logged_at->format('h:i:s A') : '' }}
                                </div>
                            </td>

                            {{-- User Account --}}
                            <td class="py-4 px-5">
                                <div class="font-bold text-gray-900 text-sm">{{ $log->user_name }}</div>
                                <div class="text-[11px] text-gray-500 flex items-center gap-1 mt-0.5">
                                    <span class="material-icons-outlined" style="font-size:12px;">phone</span>
                                    {{ $log->phone_number ?: 'No phone' }}
                                </div>
                            </td>

                            {{-- Role --}}
                            <td class="py-4 px-5 whitespace-nowrap">
                                <span class="role-badge-{{ $log->user_role }} text-xs font-bold uppercase tracking-wider">
                                    {{ $log->user_role }}
                                </span>
                            </td>

                            {{-- Event / Action --}}
                            <td class="py-4 px-5 whitespace-nowrap">
                                @if($log->action === 'Account Created')
                                    <span class="action-badge-created inline-flex items-center gap-1.5 text-xs font-bold">
                                        <span class="material-icons-outlined" style="font-size:16px;">person_add</span>
                                        Account Created
                                    </span>
                                @elseif($log->action === 'Logged In')
                                    <span class="action-badge-login inline-flex items-center gap-1.5 text-xs font-bold">
                                        <span class="material-icons-outlined" style="font-size:16px;">login</span>
                                        Logged In
                                    </span>
                                @elseif($log->action === 'Account Updated')
                                    <span class="action-badge-updated inline-flex items-center gap-1.5 text-xs font-bold">
                                        <span class="material-icons-outlined" style="font-size:16px;">edit_note</span>
                                        Account Updated
                                    </span>
                                @elseif($log->action === 'Account Deactivated')
                                    <span class="text-amber-600 inline-flex items-center gap-1.5 text-xs font-bold">
                                        <span class="material-icons-outlined" style="font-size:16px;">person_off</span>
                                        Account Deactivated
                                    </span>
                                @elseif($log->action === 'Account Reactivated')
                                    <span class="text-emerald-600 inline-flex items-center gap-1.5 text-xs font-bold">
                                        <span class="material-icons-outlined" style="font-size:16px;">how_to_reg</span>
                                        Account Reactivated
                                    </span>
                                @elseif($log->action === 'Account Deleted')
                                    <span class="text-red-600 inline-flex items-center gap-1.5 text-xs font-bold">
                                        <span class="material-icons-outlined" style="font-size:16px;">delete_forever</span>
                                        Account Deleted
                                    </span>
                                @elseif($log->action === 'Password Reset')
                                    <span class="text-indigo-600 inline-flex items-center gap-1.5 text-xs font-bold">
                                        <span class="material-icons-outlined" style="font-size:16px;">lock_reset</span>
                                        Password Reset
                                    </span>
                                @else
                                    <span class="action-badge-reset inline-flex items-center gap-1.5 text-xs font-bold">
                                        <span class="material-icons-outlined" style="font-size:16px;">info</span>
                                        {{ $log->action }}
                                    </span>
                                @endif
                            </td>

                            {{-- Actor (Who created / performed) --}}
                            <td class="py-4 px-5 whitespace-nowrap">
                                <div class="font-semibold text-gray-800">
                                    {{ $log->actor_name ?: 'System' }}
                                </div>
                                <div class="text-[11px] text-gray-400 capitalize">
                                    {{ $log->actor_role ? "({$log->actor_role})" : '' }}
                                </div>
                            </td>

                            {{-- Details --}}
                            <td class="py-4 px-5 max-w-xs">
                                <div class="text-xs text-gray-700 line-clamp-2" title="{{ $log->details }}">
                                    {{ $log->details ?: 'Normal account event.' }}
                                </div>
                                @if($log->ip_address)
                                    <div class="text-[10px] text-gray-400 mt-0.5">
                                        IP: {{ $log->ip_address }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-gray-400">
                                <span class="material-icons-outlined mb-2" style="font-size:48px;">history_toggle_off</span>
                                <p class="font-semibold text-gray-600 text-sm">No account history logs found</p>
                                <p class="text-xs text-gray-400 mt-1">Logs will appear automatically when accounts are registered and users log in.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>

{{-- Clear All Account History Confirmation Modal --}}
<div id="clearHistoryModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4" onclick="closeClearHistoryModal(event)">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-md overflow-hidden" onclick="event.stopPropagation()">
        <form method="POST" action="{{ route('nurse.account-history.clear') }}">
            @csrf
            <div class="p-6 text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto">
                    <span class="material-icons-outlined text-2xl">delete_sweep</span>
                </div>
                <h3 class="font-bold text-gray-900 text-base">Clear All Account History Logs?</h3>
                <p class="text-xs text-gray-500 leading-relaxed">
                    Are you sure you want to permanently clear all <strong>{{ $totalLogs }}</strong> account history log(s)? This action cannot be undone.
                </p>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-2.5 bg-gray-50/70">
                <button type="button" onclick="closeClearHistoryModal()" class="px-4 py-2 bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 text-xs font-semibold rounded-lg transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg shadow-xs transition-colors cursor-pointer">
                    Yes, Clear All History
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openClearHistoryModal() {
        const modal = document.getElementById('clearHistoryModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeClearHistoryModal(e) {
        if (e && e.target !== document.getElementById('clearHistoryModal')) return;
        const modal = document.getElementById('clearHistoryModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeClearHistoryModal();
    });
</script>
@endsection
