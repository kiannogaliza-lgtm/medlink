@extends('layouts.nurse')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('styles')
<style>
    .notif-filter-tab {
        padding: 8px 14px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }
    .notif-filter-tab:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .notif-filter-tab.active {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 3px 10px rgba(37, 99, 235, 0.25);
    }
</style>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Header & Filters --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <span class="material-icons-outlined text-blue-600" style="font-size:24px;">notifications_active</span>
                    All Notifications
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Live feed of medicine requests, resident verifications, home services, schedules, inventory alerts, and account events.
                </p>
            </div>
            <div class="flex items-center gap-2.5 shrink-0">
                @if($counts['unread'] > 0)
                    <span class="px-3 py-1.5 rounded-full bg-red-50 text-red-700 text-xs font-bold">
                        {{ $counts['unread'] }} Unread
                    </span>
                @endif
                <button type="button" onclick="markAllPageNotificationsRead()"
                        class="px-4 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm flex items-center gap-1.5 transition-colors cursor-pointer">
                    <span class="material-icons-outlined" style="font-size:16px;">done_all</span>
                    <span>Mark All as Read</span>
                </button>
            </div>
        </div>

        {{-- Filter Tabs --}}
        <div class="flex flex-wrap items-center gap-2 mt-5 pt-5 border-t border-gray-100">
            @php
                $tabs = [
                    'all'           => ['label' => 'All',           'icon' => 'inbox'],
                    'unread'        => ['label' => 'Unread',        'icon' => 'mark_email_unread'],
                    'medicine'      => ['label' => 'Medicine',      'icon' => 'medication'],
                    'patients'      => ['label' => 'Patients',      'icon' => 'how_to_reg'],
                    'home_services' => ['label' => 'Home Services', 'icon' => 'home'],
                    'schedules'     => ['label' => 'Schedules',     'icon' => 'calendar_month'],
                    'inventory'     => ['label' => 'Inventory',     'icon' => 'inventory_2'],
                    'accounts'      => ['label' => 'Accounts',      'icon' => 'manage_accounts'],
                ];
            @endphp

            @foreach($tabs as $key => $tab)
                <a href="{{ route('nurse.notifications', ['filter' => $key]) }}"
                   class="notif-filter-tab {{ $filter === $key ? 'active' : '' }}">
                    <span class="material-icons-outlined" style="font-size:15px;">{{ $tab['icon'] }}</span>
                    <span>{{ $tab['label'] }}</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter === $key ? 'bg-white/25 text-white' : 'bg-gray-100 text-gray-600' }}">
                        {{ $counts[$key] ?? 0 }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Notifications Feed --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        @if($filteredNotifications->isEmpty())
            <div class="py-16 px-6 text-center text-gray-400">
                <span class="material-icons-outlined mb-2 text-gray-300" style="font-size:52px;">notifications_off</span>
                <p class="font-bold text-gray-700 text-base">No notifications found</p>
                <p class="text-xs text-gray-400 mt-1">You're all caught up in this category.</p>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($filteredNotifications as $item)
                    @php
                        $colorMap = [
                            'red'     => ['bg' => 'bg-red-50',     'text' => 'text-red-600',     'badge' => 'bg-red-100 text-red-700'],
                            'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600',   'badge' => 'bg-amber-100 text-amber-700'],
                            'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'badge' => 'bg-emerald-100 text-emerald-700'],
                            'purple'  => ['bg' => 'bg-purple-50',  'text' => 'text-purple-600',  'badge' => 'bg-purple-100 text-purple-700'],
                            'blue'    => ['bg' => 'bg-blue-50',    'text' => 'text-blue-600',    'badge' => 'bg-blue-100 text-blue-700'],
                        ];
                        $palette = $colorMap[$item['color']] ?? $colorMap['blue'];
                    @endphp
                    <a href="{{ $item['url'] }}"
                       class="flex items-start gap-4 px-5 sm:px-6 py-4 hover:bg-blue-50/40 transition-colors {{ $item['is_unread'] ? 'bg-blue-50/25' : '' }}">
                        <div class="w-10 h-10 rounded-xl {{ $palette['bg'] }} {{ $palette['text'] }} flex items-center justify-center shrink-0 mt-0.5">
                            <span class="material-icons-outlined" style="font-size:20px;">{{ $item['icon'] }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    @if($item['is_unread'])
                                        <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0" title="Unread"></span>
                                    @endif
                                    <h3 class="text-sm font-bold text-gray-900 truncate">{{ $item['title'] }}</h3>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $palette['badge'] }}">
                                        {{ $item['badge'] }}
                                    </span>
                                    <span class="text-[11px] text-gray-400 font-medium">
                                        {{ $item['time']->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-600 mt-1 leading-relaxed">{{ $item['message'] }}</p>
                            <p class="text-[10px] text-gray-400 mt-1">{{ $item['time']->format('M d, Y • h:i A') }}</p>
                        </div>
                        <span class="material-icons-outlined text-gray-300 shrink-0 self-center hidden sm:block" style="font-size:18px;">chevron_right</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection

@section('scripts')
<script>
    async function markAllPageNotificationsRead() {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch('{{ route('nurse.notifications.read') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({})
            });
            if (res.ok) {
                window.location.reload();
            }
        } catch (e) {
            console.error('Failed to mark notifications as read', e);
        }
    }
</script>
@endsection
