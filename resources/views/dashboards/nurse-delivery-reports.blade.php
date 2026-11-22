@extends('layouts.nurse')

@section('title', 'Delivery Reports & Claiming History')
@section('page-title', 'Delivery Reports')

@section('styles')
<style>
    .kpi-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 20px 22px;
        border: 1px solid #e2e8f0;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }
    .kpi-label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }
    .kpi-value {
        font-size: 28px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
    }
    .kpi-icon {
        position: absolute;
        top: 18px;
        right: 18px;
        opacity: 0.8;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        #printable-area, #printable-area * {
            visibility: visible;
        }
        #printable-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 20px;
        }
        .no-print {
            display: none !important;
        }
    }
</style>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Top Action Bar --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <span class="material-icons-outlined text-blue-600" style="font-size:24px;">local_shipping</span>
                Medicine Delivery & Claiming Reports
            </h2>
            <p class="text-xs text-gray-500 mt-1">Official delivery tracking, claiming proofs, visitor frequency analysis, and daily distribution logs.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('nurse.delivery-reports.export-excel', ['date_filter' => $dateFilter, 'method' => $methodFilter, 'status' => $statusFilter, 'search' => $search]) }}" class="flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all cursor-pointer">
                <span class="material-icons-outlined" style="font-size:18px;">table_view</span>
                <span>Export Excel</span>
            </a>
            <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all cursor-pointer">
                <span class="material-icons-outlined" style="font-size:18px;">print</span>
                <span>Print Delivery Report</span>
            </button>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="kpi-card border-l-4 border-l-blue-600">
            <div class="kpi-label">Today's Total Requests</div>
            <div class="kpi-value">{{ $totalTodayRequests }}</div>
            <div class="text-[11px] text-gray-400 mt-1">{{ now()->format('F d, Y') }}</div>
            <span class="material-icons-outlined kpi-icon text-blue-500" style="font-size:32px;">today</span>
        </div>
        <div class="kpi-card border-l-4 border-l-emerald-600">
            <div class="kpi-label">Completed / Claimed Today</div>
            <div class="kpi-value text-emerald-700">{{ $completedToday }}</div>
            <div class="text-[11px] text-gray-400 mt-1">Dispensed to residents</div>
            <span class="material-icons-outlined kpi-icon text-emerald-500" style="font-size:32px;">check_circle</span>
        </div>
        <div class="kpi-card border-l-4 border-l-indigo-600">
            <div class="kpi-label">Home Deliveries</div>
            <div class="kpi-value text-indigo-700">{{ $deliveriesCount }}</div>
            <div class="text-[11px] text-gray-400 mt-1">Total door-to-door</div>
            <span class="material-icons-outlined kpi-icon text-indigo-500" style="font-size:32px;">two_wheeler</span>
        </div>
        <div class="kpi-card border-l-4 border-l-amber-600">
            <div class="kpi-label">Health Center Pickups</div>
            <div class="kpi-value text-amber-700">{{ $pickupsCount }}</div>
            <div class="text-[11px] text-gray-400 mt-1">Walk-in claims</div>
            <span class="material-icons-outlined kpi-icon text-amber-500" style="font-size:32px;">meeting_room</span>
        </div>
    </div>

    {{-- Grid 2-Column: Frequent Visitors & Today's Medicine Requests --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Frequent Visitors (1 col) --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 lg:col-span-1 flex flex-col">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <span class="material-icons-outlined text-amber-500" style="font-size:20px;">stars</span>
                    <h3 class="font-bold text-gray-900 text-sm">Frequent Visitors & Requesters</h3>
                </div>
                <span class="text-[10px] font-semibold bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full border border-amber-200">Top 10</span>
            </div>
            <p class="text-xs text-gray-500 mt-2 mb-3">Residents with the highest recorded medicine requests.</p>

            <div class="divide-y divide-gray-100 flex-1 overflow-y-auto max-h-[360px]">
                @forelse($frequentVisitors as $idx => $v)
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-6 h-6 rounded-full {{ $idx < 3 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600' }} text-[11px] font-bold flex items-center justify-center shrink-0">
                                {{ $idx + 1 }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-gray-800 truncate">
                                    {{ $v->user ? ($v->user->first_name . ' ' . $v->user->last_name) : 'User #' . $v->user_id }}
                                </p>
                                <p class="text-[10px] text-gray-400 truncate">
                                    Last request: {{ $v->last_request_date ? \Carbon\Carbon::parse($v->last_request_date)->format('M d, Y') : 'N/A' }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-bold text-blue-700">
                                {{ $v->total_requests }} req
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-gray-400 text-xs">No frequent visitor records yet.</div>
                @endforelse
            </div>
        </div>

        {{-- Today's Requests Log (2 cols) --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 lg:col-span-2 flex flex-col">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <span class="material-icons-outlined text-blue-600" style="font-size:20px;">event_note</span>
                    <h3 class="font-bold text-gray-900 text-sm">Today's Medicine Requests ({{ now()->format('M d, Y') }})</h3>
                </div>
                <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-200">
                    {{ $todayRequestsList->count() }} Today
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-2 mb-3">Exact requests submitted today, showing date and time.</p>

            <div class="flex-1 overflow-x-auto max-h-[360px]">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-500 uppercase tracking-wider text-[10px] bg-gray-50/50">
                            <th class="py-2.5 px-3">Time</th>
                            <th class="py-2.5 px-3">Resident</th>
                            <th class="py-2.5 px-3">Medicine</th>
                            <th class="py-2.5 px-3">Method</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3 text-right">Receipt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($todayRequestsList as $tr)
                            <tr class="hover:bg-blue-50/30 transition-colors">
                                <td class="py-2.5 px-3 whitespace-nowrap font-semibold text-gray-600">
                                    {{ $tr->created_at->format('h:i A') }}
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    <span class="font-bold text-gray-800">{{ $tr->user ? ($tr->user->first_name . ' ' . $tr->user->last_name) : 'Guest' }}</span>
                                    @if($tr->patient_type === 'other' && !empty($tr->patient_name))
                                        <div class="text-[10px] text-purple-600 font-medium">For: {{ $tr->patient_name }} ({{ $tr->patient_relationship ?? 'Dependent' }})</div>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    <span class="font-semibold text-blue-700">{{ $tr->item_service }}</span>
                                    <span class="text-gray-500 font-normal">x{{ $tr->quantity }}</span>
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    <span class="text-xs font-bold {{ $tr->delivery_method === 'delivery' ? 'text-indigo-700' : 'text-amber-700' }}">
                                        {{ $tr->delivery_method === 'delivery' ? 'Home Delivery' : 'Pickup' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    <span class="text-xs font-bold {{ $tr->status === 'Completed' ? 'text-emerald-700' : ($tr->status === 'Approved' ? 'text-blue-700' : 'text-gray-700') }}">
                                        {{ $tr->status }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap text-right">
                                    <button type="button" onclick="previewReceipt({{ json_encode($tr) }})" class="text-blue-600 hover:text-blue-800 text-[11px] font-bold underline cursor-pointer">
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400 text-xs">No medicine requests submitted today yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- FULL HISTORY TABLE WITH FILTERS & SEARCH --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6" id="printable-area">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-gray-100 no-print">
            <div>
                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <span class="material-icons-outlined text-blue-600" style="font-size:22px;">history</span>
                    Complete Medicine Requests & Delivery History
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">Filterable audit log of every medicine requested, date requested, and claiming receipt.</p>
            </div>

            {{-- Filter Form --}}
            <form method="GET" action="{{ route('nurse.delivery-reports') }}" class="flex flex-wrap items-center gap-2.5">
                {{-- Date Filter --}}
                <select name="date_filter" onchange="this.form.submit()" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/30 cursor-pointer">
                    <option value="all" {{ $dateFilter === 'all' ? 'selected' : '' }}>All Time</option>
                    <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>Today Only</option>
                    <option value="this_week" {{ $dateFilter === 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ $dateFilter === 'this_month' ? 'selected' : '' }}>This Month</option>
                </select>

                {{-- Delivery Method Filter --}}
                <select name="method" onchange="this.form.submit()" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/30 cursor-pointer">
                    <option value="all" {{ $methodFilter === 'all' ? 'selected' : '' }}>All Methods</option>
                    <option value="delivery" {{ $methodFilter === 'delivery' ? 'selected' : '' }}>Home Delivery</option>
                    <option value="pickup" {{ $methodFilter === 'pickup' ? 'selected' : '' }}>Pickup at Center</option>
                </select>

                {{-- Status Filter --}}
                <select name="status" onchange="this.form.submit()" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/30 cursor-pointer">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="Completed" {{ $statusFilter === 'Completed' ? 'selected' : '' }}>Completed</option>
                    <option value="Approved" {{ $statusFilter === 'Approved' ? 'selected' : '' }}>Approved</option>
                    <option value="Accepted" {{ $statusFilter === 'Accepted' ? 'selected' : '' }}>Accepted</option>
                    <option value="Pending" {{ $statusFilter === 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Rejected" {{ $statusFilter === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                </select>

                {{-- Search Box --}}
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search resident, receipt, medicine..."
                           class="pl-8 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/30 w-[200px]">
                    <span class="material-icons-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" style="font-size:16px;">search</span>
                </div>

                <button type="submit" class="px-3 py-2 bg-gray-800 text-white rounded-lg text-xs font-semibold hover:bg-black transition-colors cursor-pointer">
                    Filter
                </button>
            </form>
        </div>

        {{-- Printable Header (Visible only when printed) --}}
        <div class="hidden print:block mb-6 text-center border-b-2 border-gray-800 pb-4">
            <h1 class="text-xl font-bold uppercase text-gray-900">Barangay Honrado Health Center</h1>
            <h2 class="text-sm font-semibold text-gray-700">Official Medicine Delivery & Claiming Report</h2>
            <p class="text-xs text-gray-500">Generated on: {{ now()->format('F d, Y h:i A') }} by Nurse {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
        </div>

        {{-- Main Table --}}
        <div class="overflow-x-auto mt-4">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b-2 border-gray-100 bg-gray-50 text-gray-700 uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-3">Date Requested</th>
                        <th class="py-3 px-3">Resident Requester</th>
                        <th class="py-3 px-3">Actual Patient</th>
                        <th class="py-3 px-3">Medicine & Qty</th>
                        <th class="py-3 px-3">Method</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3">Receipt / Claim Details</th>
                        <th class="py-3 px-3 text-right no-print">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($allRequests as $req)
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            {{-- Date Requested --}}
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="font-bold text-gray-800">{{ $req->created_at ? $req->created_at->format('M d, Y') : 'N/A' }}</div>
                                <div class="text-[10px] text-gray-400">{{ $req->created_at ? $req->created_at->format('h:i A') : '' }}</div>
                            </td>

                            {{-- Resident Requester --}}
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="font-bold text-gray-900">{{ $req->user ? ($req->user->first_name . ' ' . $req->user->last_name) : 'Guest' }}</div>
                                <div class="text-[10px] text-gray-500">{{ $req->user->phone_number ?? '' }}</div>
                                @if($req->user?->address)
                                    <div class="text-[10px] text-gray-400 max-w-[180px] truncate" title="{{ $req->user->address }}">{{ $req->user->address }}</div>
                                @endif
                            </td>

                            {{-- Actual Patient --}}
                            <td class="py-3 px-3 whitespace-nowrap">
                                @if($req->patient_type === 'other' && !empty($req->patient_name))
                                    <div class="font-semibold text-purple-800">{{ $req->patient_name }}</div>
                                    <div class="text-[10px] text-purple-600 font-medium">
                                        {{ $req->patient_relationship ?? 'Dependent' }}
                                        @if($req->patient_age) • {{ $req->patient_age }} yrs @endif
                                    </div>
                                @else
                                    <div class="font-semibold text-gray-700">Self (Account Owner)</div>
                                    <div class="text-[10px] text-gray-400">
                                        {{ $req->user?->age ? "{$req->user->age} yrs" : '' }}
                                    </div>
                                @endif
                            </td>

                            {{-- Medicine & Qty --}}
                            <td class="py-3 px-3 whitespace-nowrap">
                                <span class="font-bold text-blue-700 text-xs">{{ $req->item_service }}</span>
                                <div class="text-[11px] font-semibold text-gray-700">
                                    Qty: <span class="text-blue-900 font-extrabold">{{ $req->quantity }}</span> {{ $req->medicine->unit ?? 'units' }}
                                </div>
                            </td>

                            {{-- Method --}}
                            <td class="py-3 px-3 whitespace-nowrap">
                                <span class="text-xs font-bold {{ $req->delivery_method === 'delivery' ? 'text-indigo-700' : 'text-amber-700' }}">
                                    {{ $req->delivery_method === 'delivery' ? 'Home Delivery' : 'Pickup at Center' }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td class="py-3 px-3 whitespace-nowrap">
                                <span class="text-xs font-bold {{ $req->status === 'Completed' ? 'text-emerald-700' : ($req->status === 'Approved' ? 'text-blue-700' : ($req->status === 'Accepted' ? 'text-indigo-700' : ($req->status === 'Rejected' ? 'text-red-700' : 'text-amber-700'))) }}">
                                    {{ $req->status }}
                                </span>
                            </td>

                            {{-- Receipt / Claim Details --}}
                            <td class="py-3 px-3 whitespace-nowrap">
                                @if($req->receipt_number || $req->claimed_at)
                                    <div class="font-mono font-bold text-blue-900 text-[11px]">{{ $req->receipt_number ?? 'CLAIMED' }}</div>
                                    <div class="text-[10px] text-gray-500">
                                        Claimed by: <span class="font-semibold text-gray-800">{{ $req->claimed_by ?? 'Resident' }}</span>
                                    </div>
                                    <div class="text-[10px] text-gray-400">
                                        {{ $req->claimed_at ? $req->claimed_at->format('M d, Y h:i A') : '' }}
                                    </div>
                                @else
                                    <span class="text-gray-400 italic text-[11px]">Not yet claimed</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-3 px-3 whitespace-nowrap text-right no-print">
                                <button type="button" onclick="previewReceipt({{ json_encode($req) }})" class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs font-bold transition-all shadow-2xs cursor-pointer">
                                    <span class="material-icons-outlined" style="font-size:16px;">receipt_long</span>
                                    <span>Receipt</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-gray-400 text-xs">
                                No records found matching the specified filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-5 no-print">
            {{ $allRequests->links() }}
        </div>
    </div>

</div>

{{-- MODAL: OFFICIAL RECEIPT PREVIEW (PRINTABLE POPUP) --}}
<div id="receiptModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100 transform transition-all">
        <div class="p-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                <span class="material-icons-outlined text-blue-600" style="font-size:18px;">receipt</span>
                Official Medicine Delivery & Claiming Receipt
            </h3>
            <button onclick="closeReceiptModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                <span class="material-icons-outlined" style="font-size:20px;">close</span>
            </button>
        </div>

        <div class="p-6 overflow-y-auto max-h-[80vh]" id="modalReceiptPrintArea">
            {{-- Receipt Card Inner --}}
            <div style="border: 2px dashed #0c2d48; padding: 22px; border-radius: 12px; background: #fff;">
                <div style="text-align: center; border-bottom: 2px solid #0c2d48; padding-bottom: 12px; margin-bottom: 16px;">
                    <h2 style="margin: 0; font-size: 18px; color: #0c2d48; text-transform: uppercase; font-weight: 800;">Barangay Honrado Health Center</h2>
                    <p style="margin: 3px 0 0; font-size: 11px; color: #64748b;">Official Medicine Delivery & Claiming Receipt</p>
                    <div id="rcptBadge" style="display: inline-block; background: #ecfdf5; color: #059669; padding: 3px 10px; border-radius: 20px; font-weight: bold; font-size: 10px; margin-top: 8px; border: 1px solid #a7f3d0;">
                        OFFICIALLY RECORDED
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px;">
                    <span style="color: #64748b;">Receipt Number:</span>
                    <span id="rcptNumber" style="font-weight: 700; color: #0f172a; font-family: monospace;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px;">
                    <span style="color: #64748b;">Date Requested:</span>
                    <span id="rcptDateRequested" style="font-weight: 700; color: #0f172a;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px;">
                    <span style="color: #64748b;">Date Claimed / Completed:</span>
                    <span id="rcptDateClaimed" style="font-weight: 700; color: #0f172a;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px;">
                    <span style="color: #64748b;">Delivery Method:</span>
                    <span id="rcptMethod" style="font-weight: 700; color: #0f172a;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px;">
                    <span style="color: #64748b;">Address:</span>
                    <span id="rcptAddress" style="font-weight: 700; color: #0f172a; text-align: right; max-width: 250px;"></span>
                </div>

                {{-- Medicine Item Box --}}
                <div style="background: #f8fafc; padding: 12px; border-radius: 8px; margin: 14px 0; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #64748b;">Medicine:</span>
                        <span id="rcptMedName" style="font-weight: 800; color: #0c2d48;"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-top: 4px;">
                        <span style="color: #64748b;">Quantity:</span>
                        <span id="rcptQty" style="font-weight: 700; color: #0f172a;"></span>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px;">
                    <span style="color: #64748b;">Resident Requester:</span>
                    <span id="rcptResident" style="font-weight: 700; color: #0f172a;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px;">
                    <span style="color: #64748b;">Intended Patient:</span>
                    <span id="rcptPatient" style="font-weight: 700; color: #0f172a;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px;">
                    <span style="color: #64748b;">Delivered By (Healthcare Staff):</span>
                    <span id="rcptDeliveredBy" style="font-weight: 700; color: #0f172a;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px;">
                    <span style="color: #64748b;">Received / Claimed By:</span>
                    <span id="rcptClaimedBy" style="font-weight: 700; color: #0f172a;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px;">
                    <span style="color: #64748b;">Claimant Relationship:</span>
                    <span id="rcptClaimedRel" style="font-weight: 700; color: #0f172a;"></span>
                </div>

                {{-- Signatures Box --}}
                <div style="display: flex; justify-content: space-between; margin-top: 30px; padding-top: 15px; border-top: 1px dashed #cbd5e1;">
                    <div style="width: 45%; text-align: center;">
                        <div id="rcptDelivSigImg" style="height: 40px; margin-bottom: 5px;"></div>
                        <div id="rcptDelivSigLine" style="border-top: 1px solid #94a3b8; padding-top: 4px; font-size: 10px; font-weight: bold;">
                            Healthcare Staff Signature
                        </div>
                    </div>
                    <div style="width: 45%; text-align: center;">
                        <div id="rcptClaimSigImg" style="height: 40px; margin-bottom: 5px;"></div>
                        <div id="rcptClaimSigLine" style="border-top: 1px solid #94a3b8; padding-top: 4px; font-size: 10px; font-weight: bold;">
                            Claimant Signature
                        </div>
                    </div>
                </div>

                <div style="text-align: center; font-size: 9px; color: #94a3b8; margin-top: 20px;">
                    Electronic Proof of Delivery &bull; Barangay Honrado Health Information System &bull; Keep for your records
                </div>
            </div>
        </div>

        <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-3">
            <button type="button" onclick="closeReceiptModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer">
                Close
            </button>
            <button type="button" onclick="printReceiptPopup()" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all flex items-center gap-1.5 cursor-pointer">
                <span class="material-icons-outlined" style="font-size:16px;">print</span>
                <span>Print Official Receipt</span>
            </button>
        </div>
    </div>
</div>

<script>
    let activeReceiptData = null;

    function previewReceipt(req) {
        activeReceiptData = req;
        const residentName = req.user ? `${req.user.first_name} ${req.user.last_name}` : 'Resident';
        const isForOther = req.patient_type === 'other' && req.patient_name;
        const patientName = isForOther ? `${req.patient_name} (${req.patient_relationship || 'Dependent'})` : `${residentName} (Self)`;

        const rcptNo = req.receipt_number || `RCPT-${new Date(req.created_at).toISOString().slice(0,10).replace(/-/g,'')}-${String(req.id).padStart(4, '0')}`;
        
        document.getElementById('rcptNumber').textContent = rcptNo;
        document.getElementById('rcptDateRequested').textContent = req.created_at ? new Date(req.created_at).toLocaleString() : 'N/A';
        document.getElementById('rcptDateClaimed').textContent = req.claimed_at ? new Date(req.claimed_at).toLocaleString() : (req.status === 'Completed' ? 'Completed' : 'Pending Completion');
        document.getElementById('rcptMethod').textContent = req.delivery_method === 'delivery' ? 'Home Delivery' : 'Pickup at Health Center';
        document.getElementById('rcptAddress').textContent = req.user?.address || 'Barangay Honrado';

        document.getElementById('rcptMedName').textContent = req.item_service || req.medicine?.name || 'Medicine';
        document.getElementById('rcptQty').textContent = `${req.quantity || 1} units`;

        document.getElementById('rcptResident').textContent = residentName;
        document.getElementById('rcptPatient').textContent = patientName;
        document.getElementById('rcptDeliveredBy').textContent = req.delivered_by || 'Barangay Health Worker';
        document.getElementById('rcptClaimedBy').textContent = req.claimed_by || residentName;
        document.getElementById('rcptClaimedRel').textContent = req.claimed_relationship || (isForOther ? (req.patient_relationship || 'Authorized Rep') : 'Self');

        // Signatures preview
        const delivSigBox = document.getElementById('rcptDelivSigImg');
        delivSigBox.innerHTML = '';
        if (req.delivery_signature) {
            const src = req.delivery_signature.startsWith('data:image') ? req.delivery_signature : `/storage/${req.delivery_signature}`;
            delivSigBox.innerHTML = `<img src="${src}" style="max-height:36px; margin:auto; display:block;">`;
        }

        const claimSigBox = document.getElementById('rcptClaimSigImg');
        claimSigBox.innerHTML = '';
        if (req.proof_photo) {
            const src = req.proof_photo.startsWith('data:image') ? req.proof_photo : `/storage/${req.proof_photo}`;
            claimSigBox.innerHTML = `<img src="${src}" style="max-height:36px; margin:auto; display:block;">`;
        }

        document.getElementById('receiptModal').classList.remove('hidden');
    }

    function closeReceiptModal() {
        document.getElementById('receiptModal').classList.add('hidden');
    }

    function printReceiptPopup() {
        const printContent = document.getElementById('modalReceiptPrintArea').innerHTML;
        const win = window.open('', '_blank', 'width=650,height=750');
        if (!win) return;
        win.document.write(`
            <!DOCTYPE html>
            <html>
                <head>
                    <title>Official Receipt</title>
                    <style>
                        body { font-family: 'Helvetica Neue', Arial, sans-serif; padding: 25px; color: #1e293b; }
                    </style>
                </head>
                <body>
                    ${printContent}
                    <script>
                        window.onload = function() { window.print(); }
                    <\/script>
                </body>
            </html>
        `);
        win.document.close();
    }
</script>
@endsection
