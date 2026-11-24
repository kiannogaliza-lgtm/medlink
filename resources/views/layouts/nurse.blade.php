<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Nurse Dashboard') — MEDLINK</title>
    <meta name="description" content="MEDLINK Nurse Dashboard - Manage medicine inventory, patient requests, and healthcare services.">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style type="text/tailwindcss">
        @import "tailwindcss";
        body { font-family: 'Inter', sans-serif; }
    </style>
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }

        /* Sidebar & Layout Structure */
        .sidebar {
            width: 240px;
            min-width: 240px;
            max-width: 240px;
            background: linear-gradient(180deg, #0c2d48 0%, #0a2540 50%, #081d33 100%);
            transition: transform 0.3s ease-in-out;
        }
        @media (min-width: 1024px) {
            .sidebar {
                transform: translateX(0) !important;
            }
            .main-wrapper {
                margin-left: 240px;
                width: calc(100% - 240px);
            }
        }
        @media (max-width: 1023px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            .main-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
        .main-wrapper {
            min-width: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 8px;
            color: #94a3b8;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .sidebar-link:hover {
            background: rgba(255,255,255,0.08);
            color: #e2e8f0;
        }
        .sidebar-link.active {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }

        /* Stat cards */
        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px 24px;
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        /* Modal overlay */
        .modal-overlay {
            position: fixed;
            inset: 0;
            padding: 1rem;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        .modal-content {
            background: #ffffff;
            border-radius: 16px;
            padding: clamp(1.25rem, 4vw, 2rem);
            width: 100%;
            max-width: 500px;
            max-height: 90dvh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            box-shadow: 0 25px 60px rgba(0,0,0,0.15);
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }
        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }

        /* Smooth horizontal touch scroll for data tables & filter tabs */
        .overflow-x-auto {
            -webkit-overflow-scrolling: touch;
        }

        /* Mobile ergonomics across all Nurse dashboard pages */
        @media (max-width: 639px) {
            .stat-card {
                padding: 14px 16px;
            }
            .modal-content .grid-cols-2,
            .modal-content .grid-cols-3 {
                grid-template-columns: 1fr !important;
            }
            .modal-content .col-span-2 {
                grid-column: span 1 / span 1 !important;
            }
            .modal-content input,
            .modal-content select,
            .modal-content textarea {
                font-size: 16px;
            }
            .toast {
                top: 12px;
                left: 12px;
                right: 12px;
                max-width: none;
                padding: 12px 16px;
            }
        }

        /* Status badges */
        .badge-in-stock { color: #059669; }
        .badge-low-stock { color: #d97706; }
        .badge-critical { color: #dc2626; }
        .badge-pending { color: #d97706; }
        .badge-accepted { color: #2563eb; }
        .badge-approved { color: #059669; }
        .badge-rejected { color: #dc2626; }

        /* Toast notification */
        .toast {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 100;
            padding: 14px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            animation: slideIn 0.4s ease, fadeOut 0.4s ease 3s forwards;
        }
        .toast-success { background: #059669; color: #fff; }
        .toast-error { background: #dc2626; color: #fff; }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; pointer-events: none; }
        }
    </style>
    @yield('styles')
</head>
<body class="bg-[#f0f4f8] text-gray-900 antialiased min-h-[100dvh] overflow-x-hidden">
    @php
        $nurseUser = auth()->user();
        $nurseReadAt = $nurseUser ? $nurseUser->notifications_read_at : null;

        $pendingMedQuery = \App\Models\MedicineRequest::where('status', 'Pending');
        $pendingPatQuery = \App\Models\User::where('role', 'resident')->where('status', 'pending')->where('registration_unverified', false);
        $pendingSchQuery = \App\Models\Schedule::where('status', 'Pending');
        $pendingHomeQuery = \App\Models\HomeServiceRequest::where('status', 'Pending');

        if ($nurseReadAt) {
            $pendingMedQuery->where('updated_at', '>', $nurseReadAt);
            $pendingPatQuery->where('created_at', '>', $nurseReadAt);
            $pendingSchQuery->where('updated_at', '>', $nurseReadAt);
            $pendingHomeQuery->where('updated_at', '>', $nurseReadAt);
        }

        $pendingMed = $pendingMedQuery->count();
        $pendingPat = $pendingPatQuery->count();
        $pendingSch = $pendingSchQuery->count();
        $pendingHome = $pendingHomeQuery->count();
        $nursePersonalNotifs = $nurseUser
            ? \App\Models\AppNotification::where('user_id', $nurseUser->id)->orderBy('created_at', 'desc')->take(6)->get()
            : collect();
        $unreadPersonalNotifs = $nurseUser
            ? \App\Models\AppNotification::where('user_id', $nurseUser->id)->where('is_read', false)->count()
            : 0;
        $totalNotifications = $pendingMed + $pendingPat + $pendingSch + $pendingHome + $unreadPersonalNotifs;
    @endphp

    {{-- Toast Notifications --}}
    @if(session('success'))
        <div class="toast toast-success" id="toast-notification">
            <span class="material-icons-outlined align-middle mr-2" style="font-size:18px;">check_circle</span>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="toast toast-error" id="toast-notification">
            <span class="material-icons-outlined align-middle mr-2" style="font-size:18px;">error</span>
            {{ session('error') }}
        </div>
    @endif

    <div class="flex min-h-screen">
        {{-- Mobile Overlay Backdrop --}}
        <div id="nurse-sidebar-backdrop" onclick="toggleNurseMobileSidebar()" class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs z-35 hidden transition-opacity lg:hidden"></div>

        {{-- Sidebar --}}
        <aside id="nurse-sidebar" class="sidebar w-[240px] min-h-screen flex flex-col fixed left-0 top-0 bottom-0 z-40">
            {{-- Logo --}}
            <div class="px-5 py-5 flex items-center justify-between border-b border-white/10">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.png') }}?v={{ time() }}" alt="Logo" class="w-14 h-14 rounded-2xl object-contain drop-shadow-xl shrink-0">
                    <div>
                        <div class="text-white font-black text-lg tracking-wide">MEDLINK</div>
                        <div class="text-blue-300 text-[10px] font-semibold uppercase tracking-wider">Nurse Dashboard</div>
                    </div>
                </div>
                {{-- Close button for mobile drawer --}}
                <button type="button" onclick="toggleNurseMobileSidebar()" class="lg:hidden text-gray-400 hover:text-white p-1 rounded-lg focus:outline-none cursor-pointer">
                    <span class="material-icons-outlined" style="font-size:22px;">close</span>
                </button>
            </div>

            <nav class="flex-1 px-3 mt-3 space-y-1 overflow-y-auto">
                <div class="px-3 pt-1 pb-1 text-[11px] font-bold uppercase tracking-wider text-cyan-200/70">Overview</div>
                <a href="{{ route('dashboard') }}" class="sidebar-link @if(request()->routeIs('dashboard')) active @endif">
                    <span class="material-icons-outlined" style="font-size:20px;">dashboard</span> <span>Dashboard</span>
                </a>
                <a href="{{ route('nurse.patient-verification') }}" class="sidebar-link @if(request()->routeIs('nurse.patient-verification')) active @endif">
                    <span class="material-icons-outlined" style="font-size:20px;">how_to_reg</span> <span>Patient Verification</span>
                </a>
                <a href="{{ route('nurse.notifications') }}" class="sidebar-link @if(request()->routeIs('nurse.notifications')) active @endif">
                    <span class="material-icons-outlined" style="font-size:20px;">notifications</span> <span>Notifications</span>
                    <span id="nurse-sidebar-notif-badge" class="ml-auto bg-red-500 text-white text-[11px] font-bold px-2 py-0.5 rounded-full {{ $totalNotifications > 0 ? '' : 'hidden' }}">{{ $totalNotifications > 99 ? '99+' : $totalNotifications }}</span>
                </a>

                <div class="px-3 pt-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-cyan-200/70">Medicine & Inventory</div>
                <a href="{{ route('nurse.medicine-requests') }}" class="sidebar-link @if(request()->routeIs('nurse.medicine-requests*')) active @endif">
                    <span class="material-icons-outlined" style="font-size:20px;">medication_liquid</span> <span>Medicine Requests</span>
                    @if($pendingMed > 0)
                        <span class="ml-auto bg-amber-500 text-white text-[11px] font-bold px-2 py-0.5 rounded-full">{{ $pendingMed }}</span>
                    @endif
                </a>
                <a href="{{ route('nurse.inventory') }}" class="sidebar-link @if(request()->routeIs('nurse.inventory')) active @endif">
                    <span class="material-icons-outlined" style="font-size:20px;">medication</span> <span>Medicine Inventory</span>
                </a>
                <a href="{{ route('nurse.returns') }}" class="sidebar-link @if(request()->routeIs('nurse.returns*')) active @endif">
                    <span class="material-icons-outlined" style="font-size:20px;">assignment_return</span> <span>Returned Medicines</span>
                </a>

                <div class="px-3 pt-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-cyan-200/70">Health Services</div>
                <a href="{{ route('nurse.home-services') }}" class="sidebar-link @if(request()->routeIs('nurse.home-services*')) active @endif">
                    <span class="material-icons-outlined" style="font-size:20px;">home</span> <span>Home Services</span>
                </a>
                <a href="{{ route('nurse.consultations') }}" class="sidebar-link @if(request()->routeIs('nurse.consultations*')) active @endif">
                    <span class="material-icons-outlined" style="font-size:20px;">medical_information</span> <span>Consultations & Disease Alerts</span>
                </a>
                <a href="{{ route('nurse.vaccinations') }}" class="sidebar-link @if(request()->routeIs('nurse.vaccinations*')) active @endif">
                    <span class="material-icons-outlined" style="font-size:20px;">vaccines</span> <span>Vaccinations</span>
                </a>

                <div class="px-3 pt-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-cyan-200/70">Management & Reports</div>
                <a href="{{ route('nurse.reports') }}" class="sidebar-link @if(request()->routeIs('nurse.reports')) active @endif">
                    <span class="material-icons-outlined" style="font-size:20px;">assessment</span> <span>Reports</span>
                </a>
                <a href="{{ route('nurse.delivery-reports') }}" class="sidebar-link @if(request()->routeIs('nurse.delivery-reports*')) active @endif">
                    <span class="material-icons-outlined" style="font-size:20px;">local_shipping</span> <span>Delivery Reports</span>
                </a>
                <a href="{{ route('nurse.announcements') }}" class="sidebar-link @if(request()->routeIs('nurse.announcements*')) active @endif">
                    <span class="material-icons-outlined" style="font-size:20px;">campaign</span> <span>Schedule Announcements</span>
                </a>
                <a href="{{ route('nurse.users') }}" class="sidebar-link @if(request()->routeIs('nurse.users')) active @endif">
                    <span class="material-icons-outlined" style="font-size:20px;">manage_accounts</span> <span>User Management</span>
                </a>
                <a href="{{ route('nurse.account-history') }}" class="sidebar-link @if(request()->routeIs('nurse.account-history*')) active @endif">
                    <span class="material-icons-outlined" style="font-size:20px;">history</span> <span>Account History Logs</span>
                </a>
                <a href="{{ route('nurse.profile') }}" class="sidebar-link @if(request()->routeIs('nurse.profile*')) active @endif">
                    <span class="material-icons-outlined" style="font-size:20px;">account_circle</span> <span>My Profile</span>
                </a>
            </nav>

            {{-- Footer --}}
            <div class="px-5 py-4 text-[10px] text-blue-400/50">
                Barangay Honrado<br>
                Health System v1.0
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="main-wrapper flex-1 min-w-0">
            {{-- Top Navbar --}}
            <header class="bg-white h-[64px] flex items-center justify-between gap-2 px-3 sm:px-6 lg:px-8 border-b border-gray-200 sticky top-0 z-30">
                <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                    {{-- Mobile Hamburger Button --}}
                    <button type="button" onclick="toggleNurseMobileSidebar()" class="lg:hidden p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none cursor-pointer shrink-0" aria-label="Toggle Menu">
                        <span class="material-icons-outlined block" style="font-size:24px;">menu</span>
                    </button>
                    <div class="min-w-0">
                        <h1 class="text-sm sm:text-lg font-bold text-gray-900 truncate">@yield('page-title', 'Nurse Dashboard')</h1>
                        <p class="text-[11px] sm:text-xs text-gray-400 hidden sm:block truncate">Welcome back, {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 sm:gap-4 shrink-0">
                    {{-- Mobile Search Toggle Button --}}
                    <button type="button" id="nurse-mobile-search-toggle" onclick="toggleNurseMobileSearch()" aria-label="Search"
                            class="sm:hidden text-gray-500 hover:text-[#1a5a89] transition-colors p-2 bg-gray-50 rounded-full border border-gray-100 focus:outline-none cursor-pointer">
                        <span class="material-icons-outlined block" style="font-size:20px;">search</span>
                    </button>

                    {{-- Search (Desktop inline, Mobile expandable bar below header) --}}
                    <div class="hidden sm:block relative" id="nurse-global-search-container">
                        <span class="material-icons-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" style="font-size:18px;">search</span>
                        <input type="text" id="nurse-global-search-input" placeholder="Search..." autocomplete="off"
                               class="pl-9 pr-7 sm:pl-10 sm:pr-8 py-2 bg-gray-50 border border-gray-200 rounded-lg text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 w-full sm:w-[220px] sm:focus:w-[280px] transition-all">
                        <button type="button" id="nurse-global-search-clear" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <span class="material-icons-outlined" style="font-size:16px;">close</span>
                        </button>

                        {{-- Search Results Dropdown --}}
                        <div id="nurse-search-results" class="hidden absolute right-0 mt-2 w-[min(24rem,calc(100vw-1.5rem))] bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-50 transform origin-top-right transition-all">
                            <div class="px-4 py-2.5 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between text-xs text-gray-500 font-semibold">
                                <span>Search Results</span>
                                <span id="nurse-search-count" class="text-blue-600 font-bold"></span>
                            </div>
                            <div id="nurse-search-list" class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                                {{-- Dynamically populated --}}
                            </div>
                        </div>
                    </div>

                    {{-- Notifications --}}
                    <div class="relative" id="notification-dropdown-container">
                        <button onclick="toggleNotifications()" class="relative text-gray-500 hover:text-[#1a5a89] transition-colors p-2 bg-gray-50 rounded-full border border-gray-100 focus:outline-none cursor-pointer">
                            <span class="material-icons-outlined block" style="font-size:20px;">notifications</span>
                            <span id="nurse-notif-badge" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 border-2 border-white rounded-full text-[10px] text-white flex items-center justify-center font-bold {{ $totalNotifications > 0 ? '' : 'hidden' }}">
                                {{ $totalNotifications > 9 ? '9+' : $totalNotifications }}
                            </span>
                        </button>
                        
                        {{-- Dropdown Panel (Viewport-safe on mobile) --}}
                        <div id="notification-panel" class="fixed sm:absolute left-3 right-3 sm:left-auto sm:right-0 top-[70px] sm:top-auto sm:mt-3 sm:w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden hidden z-50 transform origin-top-right transition-all">
                            <div class="px-5 py-3.5 border-b border-gray-100 bg-gray-50/70 flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <span class="material-icons-outlined text-[#1a5a89]" style="font-size:18px;">notifications</span>
                                    <h3 class="text-sm font-bold text-gray-800">Notifications</h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span id="nurse-notif-count" class="text-xs font-semibold bg-blue-100 text-blue-700 py-0.5 px-2.5 rounded-full">
                                        {{ $totalNotifications }} New
                                    </span>
                                    @if($totalNotifications > 0)
                                    <button id="nurse-mark-read-btn" onclick="markAllNurseNotificationsRead()" class="text-[11px] text-blue-600 hover:text-blue-800 font-medium cursor-pointer">
                                        Mark read
                                    </button>
                                    @endif
                                </div>
                            </div>
                            <div id="nurse-notif-list" class="max-h-96 overflow-y-auto divide-y divide-gray-50">
                                @if($totalNotifications > 0)
                                    @if($pendingMed > 0)
                                    <a href="{{ route('nurse.medicine-requests') }}" class="block px-4 py-3.5 hover:bg-blue-50/60 transition-colors">
                                        <div class="flex gap-3 items-start">
                                            <div class="w-9 h-9 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0 mt-0.5">
                                                <span class="material-icons-outlined" style="font-size: 18px;">medication</span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between">
                                                    <p class="text-xs font-bold text-gray-800">Medicine Requests</p>
                                                    <span class="text-[10px] bg-red-100 text-red-700 px-1.5 py-0.2 rounded font-semibold">{{ $pendingMed }}</span>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">You have {{ $pendingMed }} pending medicine request(s) to review.</p>
                                            </div>
                                        </div>
                                    </a>
                                    @endif

                                    @if($pendingPat > 0)
                                    <a href="{{ route('nurse.patient-verification') }}" class="block px-4 py-3.5 hover:bg-blue-50/60 transition-colors">
                                        <div class="flex gap-3 items-start">
                                            <div class="w-9 h-9 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 mt-0.5">
                                                <span class="material-icons-outlined" style="font-size: 18px;">how_to_reg</span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between">
                                                    <p class="text-xs font-bold text-gray-800">Patient Verifications</p>
                                                    <span class="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.2 rounded font-semibold">{{ $pendingPat }}</span>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">You have {{ $pendingPat }} pending resident registration(s) to verify.</p>
                                            </div>
                                        </div>
                                    </a>
                                    @endif

                                    @if($pendingSch > 0)
                                    <a href="{{ route('nurse.scheduling') }}" class="block px-4 py-3.5 hover:bg-blue-50/60 transition-colors">
                                        <div class="flex gap-3 items-start">
                                            <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 mt-0.5">
                                                <span class="material-icons-outlined" style="font-size: 18px;">calendar_month</span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between">
                                                    <p class="text-xs font-bold text-gray-800">Pending Schedules</p>
                                                    <span class="text-[10px] bg-blue-100 text-blue-700 px-1.5 py-0.2 rounded font-semibold">{{ $pendingSch }}</span>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">You have {{ $pendingSch }} new appointment(s) waiting for confirmation.</p>
                                            </div>
                                        </div>
                                    </a>
                                    @endif

                                    @if($pendingHome > 0)
                                    <a href="{{ route('nurse.home-services') }}" class="block px-4 py-3.5 hover:bg-blue-50/60 transition-colors">
                                        <div class="flex gap-3 items-start">
                                            <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                                                <span class="material-icons-outlined" style="font-size: 18px;">health_and_safety</span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between">
                                                    <p class="text-xs font-bold text-gray-800">Home Services</p>
                                                    <span class="text-[10px] bg-emerald-100 text-emerald-700 px-1.5 py-0.2 rounded font-semibold">{{ $pendingHome }}</span>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">You have {{ $pendingHome }} pending home service request(s) to assign or review.</p>
                                            </div>
                                        </div>
                                    </a>
                                    @endif

                                    @foreach($nursePersonalNotifs as $pn)
                                    <a href="{{ route('nurse.notifications') }}" class="block px-4 py-3 transition-colors {{ !$pn->is_read ? 'bg-blue-50/40 hover:bg-blue-50/70' : 'hover:bg-gray-50' }}">
                                        <div class="flex gap-3 items-start">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5 {{ $pn->type === 'success' ? 'bg-green-100 text-green-600' : ($pn->type === 'error' ? 'bg-red-100 text-red-600' : ($pn->type === 'warning' ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600')) }}">
                                                <span class="material-icons-outlined" style="font-size: 16px;">
                                                    {{ $pn->type === 'success' ? 'check_circle' : ($pn->type === 'error' ? 'cancel' : ($pn->type === 'warning' ? 'warning' : 'info')) }}
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-bold text-gray-800 truncate">{{ $pn->title }}</p>
                                                <p class="text-xs text-gray-500 mt-0.5 leading-snug line-clamp-2">{{ $pn->message }}</p>
                                                <span class="text-[10px] text-gray-400 mt-1 block">{{ $pn->time_ago }}</span>
                                            </div>
                                            @if(!$pn->is_read)
                                                <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0 mt-1.5"></span>
                                            @endif
                                        </div>
                                    </a>
                                    @endforeach
                                @elseif($nursePersonalNotifs->isNotEmpty())
                                    @foreach($nursePersonalNotifs as $pn)
                                    <a href="{{ route('nurse.notifications') }}" class="block px-4 py-3 hover:bg-gray-50 transition-colors">
                                        <div class="flex gap-3 items-start">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5 {{ $pn->type === 'success' ? 'bg-green-100 text-green-600' : ($pn->type === 'error' ? 'bg-red-100 text-red-600' : ($pn->type === 'warning' ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600')) }}">
                                                <span class="material-icons-outlined" style="font-size: 16px;">
                                                    {{ $pn->type === 'success' ? 'check_circle' : ($pn->type === 'error' ? 'cancel' : ($pn->type === 'warning' ? 'warning' : 'info')) }}
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-bold text-gray-800 truncate">{{ $pn->title }}</p>
                                                <p class="text-xs text-gray-500 mt-0.5 leading-snug line-clamp-2">{{ $pn->message }}</p>
                                                <span class="text-[10px] text-gray-400 mt-1 block">{{ $pn->time_ago }}</span>
                                            </div>
                                        </div>
                                    </a>
                                    @endforeach
                                @else
                                    <div class="px-4 py-8 text-center text-gray-400">
                                        <span class="material-icons-outlined mb-2 text-gray-300" style="font-size: 36px;">notifications_none</span>
                                        <p class="text-xs font-semibold text-gray-600">You're all caught up!</p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">No pending requests or alerts.</p>
                                    </div>
                                @endif
                            </div>
                            <a href="{{ route('nurse.notifications') }}" class="block px-4 py-3 text-center text-xs font-bold text-blue-600 hover:bg-blue-50/70 border-t border-gray-100 transition-colors">
                                View All Notifications &rarr;
                            </a>
                        </div>
                    </div>

                        {{-- User --}}
                        <div class="flex items-center gap-2 sm:gap-3">
                            <a href="{{ route('nurse.profile') }}" class="flex items-center gap-2 sm:gap-3 hover:opacity-85 transition-opacity" title="View Staff Profile">
                                <div class="text-right hidden sm:block">
                                    <div class="text-sm font-semibold text-gray-800">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</div>
                                    <div class="text-[10px] text-green-500 font-medium">Online</div>
                                </div>
                                @if(auth()->user()->profile_photo)
                                    <img src="{{ asset('storage/' . auth()->user()->profile_photo) }}" alt="{{ auth()->user()->first_name }}" class="w-8 h-8 sm:w-9 sm:h-9 rounded-full object-cover border border-gray-200 shadow-sm">
                                @else
                                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-bold text-xs sm:text-sm">
                                        {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name, 0, 1)) }}
                                    </div>
                                @endif
                            </a>
                            <form id="nurse-logout-form" method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="button" onclick="openNurseLogoutModal()" title="Logout" class="text-gray-400 hover:text-red-500 transition-colors cursor-pointer p-1">
                                    <span class="material-icons-outlined" style="font-size:20px;">logout</span>
                                </button>
                            </form>
                        </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="p-4 sm:p-6 lg:p-8 flex-1 min-w-0">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Logout & Account Action Confirmation Modal --}}
    <div id="logoutConfirmModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4" onclick="closeNurseLogoutModal(event)">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-sm overflow-hidden" onclick="event.stopPropagation()">
            {{-- Step 1: Confirm Logout + Account Options --}}
            <div id="nurseLogoutStepMain">
                <div class="p-6 text-center">
                    <div class="w-14 h-14 mx-auto rounded-full bg-red-50 text-red-600 flex items-center justify-center mb-4">
                        <span class="material-icons-outlined" style="font-size:28px;">logout</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Confirm Logout</h3>
                    <p class="text-sm text-gray-500 mt-1.5">Are you sure you want to log out of your Nurse account?</p>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="closeNurseLogoutModal()" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-100 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmNurseLogout()" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm transition-colors cursor-pointer">
                        Confirm Logout
                    </button>
                </div>
                <div class="px-6 py-3.5 bg-white border-t border-gray-100">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 text-center mb-2.5">Account Options</p>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="openNurseAccountAction('deactivate')" class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-amber-800 bg-amber-50 hover:bg-amber-100 transition-colors cursor-pointer">
                            <span class="material-icons-outlined" style="font-size:16px;">person_off</span>
                            <span>Deactivate</span>
                        </button>
                        <button type="button" onclick="openNurseAccountAction('delete')" class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 transition-colors cursor-pointer">
                            <span class="material-icons-outlined" style="font-size:16px;">delete_forever</span>
                            <span>Delete Account</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Step 2: Deactivate / Delete Account Confirmation --}}
            <div id="nurseLogoutStepAction" class="hidden">
                <div class="p-6">
                    <div class="flex items-start gap-3">
                        <div id="nurseActionIconWrap" class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <span id="nurseActionIcon" class="material-icons-outlined" style="font-size:22px;">person_off</span>
                        </div>
                        <div>
                            <h3 id="nurseActionTitle" class="text-base font-bold text-gray-900">Deactivate Account?</h3>
                            <p id="nurseActionDesc" class="text-xs text-gray-500 mt-1 leading-relaxed"></p>
                        </div>
                    </div>

                    <div id="nurseActionError" class="hidden mt-4 p-3 rounded-xl bg-red-50 text-xs text-red-700 font-semibold"></div>

                    <form id="nurseAccountActionForm" onsubmit="submitNurseAccountAction(event)" class="mt-4 space-y-3">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">Confirm Current Password *</label>
                            <input type="password" id="nurseActionPassword" required placeholder="Enter your password"
                                   class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">Reason (Optional)</label>
                            <textarea id="nurseActionReason" rows="2" maxlength="500" placeholder="Optional reason..."
                                      class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 resize-none"></textarea>
                        </div>
                        <div class="pt-2 flex items-center justify-end gap-2">
                            <button type="button" onclick="backToNurseLogoutStep()" class="px-3.5 py-2 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors cursor-pointer">
                                Back
                            </button>
                            <button type="submit" id="nurseActionSubmitBtn" class="px-4 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-sm transition-colors cursor-pointer">
                                Confirm
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @yield('scripts')
    <script>
        let nurseAccountActionType = 'deactivate';

        function openNurseLogoutModal() {
            backToNurseLogoutStep();
            const modal = document.getElementById('logoutConfirmModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }
        function closeNurseLogoutModal(e) {
            if (e && e.target !== document.getElementById('logoutConfirmModal')) return;
            const modal = document.getElementById('logoutConfirmModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            backToNurseLogoutStep();
        }
        function confirmNurseLogout() {
            const form = document.getElementById('nurse-logout-form');
            if (form) form.submit();
        }
        function openNurseAccountAction(type) {
            nurseAccountActionType = type;
            const mainStep = document.getElementById('nurseLogoutStepMain');
            const actionStep = document.getElementById('nurseLogoutStepAction');
            const iconWrap = document.getElementById('nurseActionIconWrap');
            const icon = document.getElementById('nurseActionIcon');
            const title = document.getElementById('nurseActionTitle');
            const desc = document.getElementById('nurseActionDesc');
            const submitBtn = document.getElementById('nurseActionSubmitBtn');
            const errBox = document.getElementById('nurseActionError');

            if (errBox) { errBox.classList.add('hidden'); errBox.textContent = ''; }
            const pw = document.getElementById('nurseActionPassword');
            const rs = document.getElementById('nurseActionReason');
            if (pw) pw.value = '';
            if (rs) rs.value = '';

            if (type === 'delete') {
                iconWrap.className = 'w-11 h-11 rounded-xl bg-red-50 text-red-600 flex items-center justify-center shrink-0';
                icon.textContent = 'delete_forever';
                title.textContent = 'Delete Account Permanently?';
                desc.textContent = 'This permanently deletes your Nurse account, logs the event in Account History, and signs you out immediately.';
                submitBtn.className = 'px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm transition-colors cursor-pointer';
                submitBtn.textContent = 'Yes, Delete Account';
            } else {
                iconWrap.className = 'w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0';
                icon.textContent = 'person_off';
                title.textContent = 'Deactivate Your Account?';
                desc.textContent = 'Your Nurse account will be set to inactive, logged in Account History, and you will be signed out until reactivated.';
                submitBtn.className = 'px-4 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-sm transition-colors cursor-pointer';
                submitBtn.textContent = 'Yes, Deactivate';
            }

            mainStep.classList.add('hidden');
            actionStep.classList.remove('hidden');
        }
        function backToNurseLogoutStep() {
            const mainStep = document.getElementById('nurseLogoutStepMain');
            const actionStep = document.getElementById('nurseLogoutStepAction');
            if (mainStep) mainStep.classList.remove('hidden');
            if (actionStep) actionStep.classList.add('hidden');
        }
        async function submitNurseAccountAction(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('nurseActionSubmitBtn');
            const errBox = document.getElementById('nurseActionError');
            const password = document.getElementById('nurseActionPassword').value;
            const reason = document.getElementById('nurseActionReason').value;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const isDelete = nurseAccountActionType === 'delete';

            submitBtn.disabled = true;
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Processing...';
            errBox.classList.add('hidden');

            try {
                const res = await fetch(isDelete ? '/account/delete' : '/account/deactivate', {
                    method: isDelete ? 'DELETE' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ password, reason })
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    errBox.textContent = data.message || 'Incorrect password or unable to process request.';
                    errBox.classList.remove('hidden');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    return;
                }
                window.location.href = data.redirect || '/login';
            } catch (err) {
                errBox.textContent = 'Network error. Please try again.';
                errBox.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeNurseLogoutModal();
        });

        function toggleNurseMobileSidebar() {
            const sidebar = document.getElementById('nurse-sidebar');
            const backdrop = document.getElementById('nurse-sidebar-backdrop');
            if (sidebar) sidebar.classList.toggle('mobile-open');
            if (backdrop) backdrop.classList.toggle('hidden');
        }

        function toggleNurseMobileSearch() {
            const container = document.getElementById('nurse-global-search-container');
            const input = document.getElementById('nurse-global-search-input');
            if (!container) return;
            const isMobileOpen = container.classList.contains('!block');
            if (isMobileOpen) {
                container.classList.remove('!block', 'fixed', 'left-3', 'right-3', 'top-[70px]', 'z-50', 'bg-white', 'p-2', 'rounded-xl', 'shadow-xl', 'border', 'border-gray-200');
                container.classList.add('hidden', 'relative');
            } else {
                container.classList.remove('hidden', 'relative');
                container.classList.add('!block', 'fixed', 'left-3', 'right-3', 'top-[70px]', 'z-50', 'bg-white', 'p-2', 'rounded-xl', 'shadow-xl', 'border', 'border-gray-200');
                if (input) setTimeout(() => input.focus(), 50);
            }
        }

        function toggleNotifications() {
            const panel = document.getElementById('notification-panel');
            panel.classList.toggle('hidden');
        }

        function markAllNurseNotificationsRead() {
            const badge = document.getElementById('nurse-notif-badge');
            if (badge) badge.classList.add('hidden');

            const sidebarBadge = document.getElementById('nurse-sidebar-notif-badge');
            if (sidebarBadge) sidebarBadge.classList.add('hidden');

            const count = document.getElementById('nurse-notif-count');
            if (count) count.innerText = '0 New';

            const btn = document.getElementById('nurse-mark-read-btn');
            if (btn) btn.classList.add('hidden');

            const list = document.getElementById('nurse-notif-list');
            if (list) {
                list.innerHTML = `
                    <div class="px-4 py-8 text-center text-gray-400">
                        <span class="material-icons-outlined mb-2 text-gray-300" style="font-size: 36px;">notifications_none</span>
                        <p class="text-xs font-semibold text-gray-600">You're all caught up!</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">No pending requests or alerts.</p>
                    </div>
                `;
            }

            // Persist to backend so it doesn't return on page refresh
            fetch('{{ route("nurse.notifications.read") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({})
            }).catch(e => console.error('Failed to mark nurse notifications read', e));
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const container = document.getElementById('notification-dropdown-container');
            const panel = document.getElementById('notification-panel');
            if (container && panel && !container.contains(event.target)) {
                panel.classList.add('hidden');
            }

            const searchContainer = document.getElementById('nurse-global-search-container');
            const searchResults = document.getElementById('nurse-search-results');
            const mobileSearchToggle = document.getElementById('nurse-mobile-search-toggle');
            if (searchContainer && searchResults && !searchContainer.contains(event.target)) {
                searchResults.classList.add('hidden');
                if (searchContainer.classList.contains('!block') && (!mobileSearchToggle || !mobileSearchToggle.contains(event.target))) {
                    toggleNurseMobileSearch();
                }
            }
        });

        // ── Nurse Global Search ─────────────────────────
        (function() {
            const searchInput = document.getElementById('nurse-global-search-input');
            const searchResults = document.getElementById('nurse-search-results');
            const searchList = document.getElementById('nurse-search-list');
            const searchCount = document.getElementById('nurse-search-count');
            const searchClear = document.getElementById('nurse-global-search-clear');
            let searchDebounceTimer = null;

            if (!searchInput) return;

            searchInput.addEventListener('input', function() {
                const q = this.value.trim();
                if (q.length > 0) {
                    searchClear.classList.remove('hidden');
                } else {
                    searchClear.classList.add('hidden');
                    searchResults.classList.add('hidden');
                    return;
                }

                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(() => performSearch(q), 250);
            });

            searchClear.addEventListener('click', function() {
                searchInput.value = '';
                searchClear.classList.add('hidden');
                searchResults.classList.add('hidden');
                searchInput.focus();
            });

            async function performSearch(q) {
                try {
                    searchList.innerHTML = `
                        <div class="px-4 py-6 text-center text-gray-400">
                            <span class="material-icons-outlined animate-spin text-blue-500 mb-1" style="font-size:24px;">sync</span>
                            <p class="text-xs">Searching...</p>
                        </div>
                    `;
                    searchResults.classList.remove('hidden');

                    const response = await fetch(`/api/search?q=${encodeURIComponent(q)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) throw new Error('Search failed');

                    const data = await response.json();
                    renderResults(data.results || [], q);
                } catch (e) {
                    console.error('Search error', e);
                    searchList.innerHTML = `
                        <div class="px-4 py-6 text-center text-red-500">
                            <p class="text-xs">Failed to load search results.</p>
                        </div>
                    `;
                }
            }

            function renderResults(results, query) {
                if (!results.length) {
                    searchCount.textContent = '0 found';
                    searchList.innerHTML = `
                        <div class="px-4 py-8 text-center text-gray-400">
                            <span class="material-icons-outlined mb-2 text-gray-300" style="font-size: 32px;">search_off</span>
                            <p class="text-xs font-semibold text-gray-600">No results found</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">No match for "${escapeHtml(query)}"</p>
                        </div>
                    `;
                    return;
                }

                searchCount.textContent = `${results.length} found`;
                let html = '';
                results.forEach(item => {
                    html += `
                        <a href="${item.url || '#'}" class="block px-4 py-3 hover:bg-blue-50/60 transition-colors group">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 mt-0.5 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                    <span class="material-icons-outlined" style="font-size:18px;">${item.icon || 'search'}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-xs font-bold text-gray-800 truncate group-hover:text-blue-700">${escapeHtml(item.title)}</p>
                                        <span class="text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-medium shrink-0">${escapeHtml(item.category)}</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 truncate mt-0.5">${escapeHtml(item.subtitle)}</p>
                                </div>
                            </div>
                        </a>
                    `;
                });
                searchList.innerHTML = html;
            }

            function escapeHtml(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }
        })();
    </script>
    {{-- Real-Time Auto-Sync Engine --}}
    <script src="{{ asset('js/auto-sync.js') }}?v={{ time() }}"></script>
</body>
</html>
