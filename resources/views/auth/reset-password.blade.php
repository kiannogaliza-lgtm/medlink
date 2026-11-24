<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password — MEDLINK</title>

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

        <a href="{{ route('login') }}" class="text-xs sm:text-sm font-semibold text-slate-200 hover:text-emerald-400 flex items-center gap-1 sm:gap-1.5 transition-colors px-2.5 sm:px-3 py-2 rounded-lg hover:bg-white/5 shrink-0 whitespace-nowrap">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            <span>Back to Sign In</span>
        </a>
    </header>

    {{-- Main Card --}}
    <main class="flex-1 flex items-center justify-center px-3.5 sm:px-6 py-6 sm:py-12 relative overflow-hidden">
        <div class="w-full max-w-md bg-white/95 backdrop-blur-xl border border-white/60 rounded-2xl sm:rounded-3xl shadow-2xl shadow-emerald-950/40 p-5 sm:p-9 relative z-10">
            <div class="text-center mb-7">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-bold uppercase tracking-wider mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Account Recovery
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Set a new password</h1>
                <p class="text-slate-500 text-xs sm:text-sm mt-1.5 font-medium">
                    Your phone number has been verified. Choose a new password for your MEDLINK account.
                </p>
            </div>

            {{-- Error Notification --}}
            @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="text-sm text-red-700 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <p class="font-medium">{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <form action="{{ route('password.new.submit') }}" method="POST" class="space-y-5">
                @csrf

                {{-- New Password --}}
                <div>
                    <label for="new_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">New Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input type="password" name="new_password" id="new_password" required minlength="8" placeholder="At least 8 characters"
                               class="block w-full pl-10 pr-12 py-3 bg-slate-50 border border-slate-200 rounded-xl text-base sm:text-sm placeholder-slate-400 font-medium
                                      focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                        <button type="button" onclick="toggleResetPassword('new_password', 'eye-open-1', 'eye-close-1')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none" aria-label="Toggle Password Visibility">
                            <svg id="eye-open-1" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg id="eye-close-1" class="w-4.5 h-4.5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Confirm New Password --}}
                <div>
                    <label for="new_password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Confirm New Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" required minlength="8" placeholder="Re-enter new password"
                               class="block w-full pl-10 pr-12 py-3 bg-slate-50 border border-slate-200 rounded-xl text-base sm:text-sm placeholder-slate-400 font-medium
                                      focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                        <button type="button" onclick="toggleResetPassword('new_password_confirmation', 'eye-open-2', 'eye-close-2')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none" aria-label="Toggle Password Visibility">
                            <svg id="eye-open-2" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg id="eye-close-2" class="w-4.5 h-4.5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="w-full flex justify-center items-center gap-2 py-3.5 px-4 rounded-xl text-sm font-bold text-white
                               bg-emerald-600 hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500
                               transition-all shadow-lg shadow-emerald-950/20 active:scale-[0.98] cursor-pointer">
                    Update Password
                </button>
            </form>

            <div class="mt-6 text-center border-t border-slate-100 pt-5">
                <a href="{{ route('login') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 hover:underline">
                    Cancel and return to Sign In
                </a>
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

    <script>
        function toggleResetPassword(inputId, openIconId, closeIconId) {
            const input = document.getElementById(inputId);
            const eyeOpen = document.getElementById(openIconId);
            const eyeClose = document.getElementById(closeIconId);
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClose.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClose.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
