<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — MEDLINK</title>

    {{-- Tailwind CSS v4 CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style type="text/tailwindcss">
        @theme {
            --font-display: 'Plus Jakarta Sans', sans-serif;
            --font-body: 'Inter', sans-serif;
        }
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4, .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
    <style>
        .auth-bg {
            background-image:
                linear-gradient(
                    135deg,
                    rgba(2, 20, 11, 0.90) 0%,
                    rgba(3, 30, 17, 0.82) 50%,
                    rgba(2, 20, 11, 0.94) 100%
                ),
                url('{{ asset('honrado.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: scroll;
        }
        @media (min-width: 768px) {
            .auth-bg { background-attachment: fixed; }
        }
    </style>
</head>
<body class="auth-bg text-slate-900 antialiased min-h-[100dvh] flex flex-col justify-between relative selection:bg-emerald-500 selection:text-white overflow-x-hidden">

    {{-- Header / Brand --}}
    <header class="w-full max-w-7xl mx-auto px-4 sm:px-8 py-4 sm:h-24 flex items-center justify-between gap-3 relative z-10">
        <a href="/" class="flex items-center gap-2.5 sm:gap-3.5 group min-w-0" aria-label="MEDLINK Home">
            <img src="{{ asset('images/logo.png') }}?v={{ time() }}" alt="MEDLINK Logo" class="w-10 h-10 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl object-contain flex-shrink-0 drop-shadow-xl group-hover:scale-105 transition-transform">
            <div class="min-w-0">
                <div class="font-display font-black text-white text-base sm:text-2xl tracking-tight leading-none flex items-center gap-1.5 sm:gap-2 flex-wrap">
                    <span>MEDLINK</span>
                    <span class="text-[9px] sm:text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">ONLINE</span>
                </div>
                <div class="text-[11px] sm:text-sm text-slate-300/80 mt-0.5 sm:mt-1 font-medium truncate">Barangay Honrado Health Center</div>
            </div>
        </a>

        <a href="/" class="text-xs sm:text-sm font-semibold text-slate-200 hover:text-emerald-400 flex items-center gap-1 sm:gap-1.5 transition-colors px-2.5 sm:px-3 py-2 rounded-lg hover:bg-white/5 shrink-0 whitespace-nowrap">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            <span class="hidden xs:inline sm:inline">Back to Home</span>
            <span class="sm:hidden">Home</span>
        </a>
    </header>

    {{-- Main Login Card Container --}}
    <main class="flex-1 flex items-center justify-center px-3.5 sm:px-6 py-6 sm:py-12 relative overflow-hidden">
        <div class="w-full max-w-md bg-white/95 backdrop-blur-xl border border-white/60 rounded-2xl sm:rounded-3xl shadow-2xl shadow-emerald-950/40 p-5 sm:p-9 relative z-10 transition-all">
            {{-- Form Header --}}
            <div class="text-center mb-7">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-bold uppercase tracking-wider mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Account Access
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Welcome back</h1>
                <p class="text-slate-500 text-xs sm:text-sm mt-1.5 font-medium">Sign in to your MEDLINK account to continue</p>
            </div>

            {{-- Success Status Notification --}}
            @if(session('status'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-emerald-800 font-medium">{{ session('status') }}</p>
                </div>
            @endif

            {{-- Error Notification --}}
            @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="text-sm text-red-700">
                        @foreach($errors->all() as $error)
                            <p class="font-medium">{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Form --}}
            <form action="{{ url('/login') }}" method="POST" class="space-y-6">
                @csrf
                
                {{-- Role Selection --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2.5">Login as</label>
                    <div class="grid grid-cols-3 gap-2.5">
                        <label class="cursor-pointer relative">
                            <input type="radio" name="role" value="resident" class="peer sr-only" checked>
                            <div class="flex flex-col items-center justify-center py-2.5 px-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-600 font-semibold text-xs sm:text-sm transition-all 
                                        peer-checked:border-emerald-500 peer-checked:bg-emerald-50/80 peer-checked:text-emerald-700 peer-checked:shadow-sm hover:bg-slate-100">
                                Resident
                            </div>
                        </label>
                        <label class="cursor-pointer relative">
                            <input type="radio" name="role" value="bhw" class="peer sr-only">
                            <div class="flex flex-col items-center justify-center py-2.5 px-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-600 font-semibold text-xs sm:text-sm transition-all 
                                        peer-checked:border-emerald-500 peer-checked:bg-emerald-50/80 peer-checked:text-emerald-700 peer-checked:shadow-sm hover:bg-slate-100">
                                BHW
                            </div>
                        </label>
                        <label class="cursor-pointer relative">
                            <input type="radio" name="role" value="nurse" class="peer sr-only">
                            <div class="flex flex-col items-center justify-center py-2.5 px-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-600 font-semibold text-xs sm:text-sm transition-all 
                                        peer-checked:border-emerald-500 peer-checked:bg-emerald-50/80 peer-checked:text-emerald-700 peer-checked:shadow-sm hover:bg-slate-100">
                                Nurse
                            </div>
                        </label>
                    </div>
                </div>
                
                {{-- Phone Number --}}
                <div>
                    <label for="phone_number" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Phone Number</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </span>
                        <input type="tel" name="phone_number" id="phone_number" required
                               value="{{ old('phone_number') }}"
                               placeholder="09XXXXXXXXX or +639XXXXXXXXX"
                               inputmode="tel"
                               autocomplete="tel"
                               data-ph-phone
                               class="block w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-base sm:text-sm placeholder-slate-400 font-medium
                                      focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                    </div>
                </div>

                {{-- Password --}}
                <div>
                    <div class="mb-2">
                        <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Password</label>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input type="password" name="password" id="password" required placeholder="••••••••"
                               class="block w-full pl-10 pr-12 py-3 bg-slate-50 border border-slate-200 rounded-xl text-base sm:text-sm placeholder-slate-400 font-medium
                                      focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none" aria-label="Toggle Password Visibility">
                            {{-- Eye Open Icon --}}
                            <svg id="eye-open-icon" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            {{-- Eye Close Icon --}}
                            <svg id="eye-close-icon" class="w-4.5 h-4.5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Remember Me & Forgot Account --}}
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" 
                               class="w-4 h-4 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500 focus:ring-offset-0">
                        <label for="remember" class="ml-2 text-xs sm:text-sm text-slate-600 font-medium select-none">Remember this device</label>
                    </div>
                    <button type="button" onclick="openForgotModal('reset')"
                            class="text-xs sm:text-sm font-semibold text-emerald-600 hover:text-emerald-700 hover:underline cursor-pointer">
                        Forgot account?
                    </button>
                </div>

                {{-- Submit Button --}}
                <button type="submit"
                        class="w-full flex justify-center items-center gap-2 py-3.5 px-4 rounded-xl text-sm font-bold text-white
                               bg-emerald-600 hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500
                               transition-all shadow-lg shadow-emerald-950/20 active:scale-[0.98]">
                    Sign In
                </button>
            </form>

            {{-- Register Option --}}
            <div class="mt-7 text-center border-t border-slate-100 pt-5">
                <p class="text-xs sm:text-sm text-slate-500 font-medium">
                    Don't have an account? 
                    <a href="{{ route('register') }}" class="font-bold text-emerald-600 hover:text-emerald-700 hover:underline ml-1">Create an account</a>
                </p>
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="w-full border-t border-white/10 bg-transparent py-6 relative z-10">
        <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-white/40">
            <span>&copy; {{ date('Y') }} MEDLINK. Barangay Honrado Health Center.</span>
            <span>Web-based inventory &amp; health service request system</span>
        </div>
    </footer>

    {{-- Forgot Account Modal --}}
    <div id="forgotAccountModal" role="dialog" aria-modal="true" aria-labelledby="forgotModalTitle"
         onclick="if(event.target === this) closeForgotModal()"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/75 backdrop-blur-sm p-3.5 sm:p-6">
        <div class="w-full max-w-md bg-white rounded-2xl sm:rounded-3xl shadow-2xl p-5 sm:p-7 relative max-h-[92dvh] overflow-y-auto">
            <button type="button" onclick="closeForgotModal()" aria-label="Close modal"
                    class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <div class="mb-5 pr-8">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold uppercase tracking-wider">
                    Account Recovery
                </span>
                <h2 id="forgotModalTitle" class="font-display text-xl font-extrabold text-slate-900 mt-2">Forgot your account?</h2>
                <p class="text-xs text-slate-500 mt-1">Reset your password or look up your registered phone number.</p>
            </div>

            {{-- Modal Tabs --}}
            <div class="grid grid-cols-2 gap-1.5 p-1 rounded-xl bg-slate-100 mb-5">
                <button type="button" id="tabBtnReset" onclick="switchForgotTab('reset')"
                        class="py-2 px-3 rounded-lg text-xs font-bold transition-all cursor-pointer bg-white text-emerald-700 shadow-sm">
                    Reset Password
                </button>
                <button type="button" id="tabBtnFind" onclick="switchForgotTab('find')"
                        class="py-2 px-3 rounded-lg text-xs font-semibold transition-all cursor-pointer text-slate-600 hover:text-slate-900">
                    Find My Account
                </button>
            </div>

            {{-- Tab 1: Reset Password --}}
            <div id="forgotTabReset">
                @if($errors->forgot->any())
                    <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 font-medium space-y-1">
                        @foreach($errors->forgot->all() as $err)
                            <p>{{ $err }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('forgot.reset-password') }}" method="POST" class="space-y-3.5">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Role</label>
                        <select name="recover_role" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500">
                            <option value="resident" {{ old('recover_role', 'resident') === 'resident' ? 'selected' : '' }}>Resident</option>
                            <option value="bhw" {{ old('recover_role') === 'bhw' ? 'selected' : '' }}>BHW</option>
                            <option value="nurse" {{ old('recover_role') === 'nurse' ? 'selected' : '' }}>Nurse</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">First Name</label>
                            <input type="text" name="recover_first_name" required value="{{ old('recover_first_name') }}" placeholder="Juan"
                                   class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Last Name</label>
                            <input type="text" name="recover_last_name" required value="{{ old('recover_last_name') }}" placeholder="Dela Cruz"
                                   class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Registered Phone Number</label>
                        <input type="tel" name="recover_phone_number" required value="{{ old('recover_phone_number') }}"
                               placeholder="09XXXXXXXXX or +639XXXXXXXXX"
                               inputmode="tel" autocomplete="tel" data-ph-phone
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500">
                    </div>
                    <button type="submit"
                            class="w-full py-3 px-4 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 transition-all shadow-md cursor-pointer mt-1">
                        Send Verification Code
                    </button>
                </form>
            </div>

            {{-- Tab 2: Find My Account --}}
            <div id="forgotTabFind" class="hidden">
                @if(session('found_account_hint'))
                    <div class="mb-4 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 font-semibold">
                        {{ session('found_account_hint') }}
                    </div>
                @endif
                @if($errors->find->any())
                    <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 font-medium space-y-1">
                        @foreach($errors->find->all() as $err)
                            <p>{{ $err }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('forgot.find') }}" method="POST" class="space-y-3.5">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Role</label>
                        <select name="find_role" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500">
                            <option value="resident" {{ old('find_role', 'resident') === 'resident' ? 'selected' : '' }}>Resident</option>
                            <option value="bhw" {{ old('find_role') === 'bhw' ? 'selected' : '' }}>BHW</option>
                            <option value="nurse" {{ old('find_role') === 'nurse' ? 'selected' : '' }}>Nurse</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">First Name</label>
                        <input type="text" name="find_first_name" required value="{{ old('find_first_name') }}" placeholder="Juan"
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Last Name</label>
                        <input type="text" name="find_last_name" required value="{{ old('find_last_name') }}" placeholder="Dela Cruz"
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500">
                    </div>
                    <button type="submit"
                            class="w-full py-3 px-4 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 transition-all shadow-md cursor-pointer mt-1">
                        Find Registered Number
                    </button>
                </form>

                <p class="mt-4 text-[11px] text-slate-400 text-center leading-relaxed">
                    Still locked out? Visit the Barangay Honrado Health Center Nurse or BHW desk with a valid ID for manual recovery.
                </p>
            </div>
        </div>
    </div>

    <script>
        function openForgotModal(tab = 'reset') {
            const modal = document.getElementById('forgotAccountModal');
            if (!modal) return;
            switchForgotTab(tab);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeForgotModal() {
            const modal = document.getElementById('forgotAccountModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        function switchForgotTab(tab) {
            const resetTab = document.getElementById('forgotTabReset');
            const findTab = document.getElementById('forgotTabFind');
            const btnReset = document.getElementById('tabBtnReset');
            const btnFind = document.getElementById('tabBtnFind');
            if (!resetTab || !findTab) return;

            const isReset = tab === 'reset';
            resetTab.classList.toggle('hidden', !isReset);
            findTab.classList.toggle('hidden', isReset);

            btnReset.className = isReset
                ? 'py-2 px-3 rounded-lg text-xs font-bold transition-all cursor-pointer bg-white text-emerald-700 shadow-sm'
                : 'py-2 px-3 rounded-lg text-xs font-semibold transition-all cursor-pointer text-slate-600 hover:text-slate-900';
            btnFind.className = !isReset
                ? 'py-2 px-3 rounded-lg text-xs font-bold transition-all cursor-pointer bg-white text-emerald-700 shadow-sm'
                : 'py-2 px-3 rounded-lg text-xs font-semibold transition-all cursor-pointer text-slate-600 hover:text-slate-900';
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeForgotModal();
        });

        @if(session('open_forgot_modal'))
            openForgotModal('{{ session('open_forgot_modal') }}');
        @elseif($errors->forgot->any())
            openForgotModal('reset');
        @elseif($errors->find->any())
            openForgotModal('find');
        @endif

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeOpen = document.getElementById('eye-open-icon');
            const eyeClose = document.getElementById('eye-close-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClose.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClose.classList.add('hidden');
            }
        }

        function closeOtherDeviceAlert() {
            const modal = document.getElementById('otherDeviceAlertModal');
            if (modal) modal.remove();
            if (window.location.search.includes('kicked=1')) {
                const url = new URL(window.location.href);
                url.searchParams.delete('kicked');
                window.history.replaceState({}, '', url.pathname + url.search);
            }
        }
    </script>

    {{-- Popup Alert: Another Device Opened This Account --}}
    @if(session('session_kicked') || request()->query('kicked') === '1')
    <div id="otherDeviceAlertModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-sm p-6 text-center space-y-4">
            <div class="w-12 h-12 mx-auto rounded-full bg-red-50 text-red-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">Account Opened on Another Device</h3>
                <p class="text-sm text-slate-600 mt-1.5 leading-relaxed">
                    {{ session('session_kicked') ?: 'There is another device that opened this account. You have been automatically logged out.' }}
                </p>
            </div>
            <button type="button" onclick="closeOtherDeviceAlert()"
                    class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-xl transition-colors cursor-pointer">
                Close
            </button>
        </div>
    </div>
    @endif

    <script src="{{ asset('js/ph-phone.js') }}"></script>
</body>
</html>