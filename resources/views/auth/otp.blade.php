<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Verify Code' }} — MEDLINK</title>

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

        @if(strtoupper($cancelMethod ?? 'GET') === 'POST')
            <form action="{{ $cancelUrl ?? route('login') }}" method="POST" class="shrink-0">
                @csrf
                <button type="submit" class="text-xs sm:text-sm font-semibold text-slate-200 hover:text-emerald-400 flex items-center gap-1 sm:gap-1.5 transition-colors px-2.5 sm:px-3 py-2 rounded-lg hover:bg-white/5 whitespace-nowrap cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Cancel</span>
                </button>
            </form>
        @else
            <a href="{{ $cancelUrl ?? route('login') }}" class="text-xs sm:text-sm font-semibold text-slate-200 hover:text-emerald-400 flex items-center gap-1 sm:gap-1.5 transition-colors px-2.5 sm:px-3 py-2 rounded-lg hover:bg-white/5 shrink-0 whitespace-nowrap">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Cancel</span>
            </a>
        @endif
    </header>

    {{-- Main OTP Card --}}
    <main class="flex-1 flex items-center justify-center px-3.5 sm:px-6 py-6 sm:py-12 relative overflow-hidden">
        <div class="w-full max-w-md bg-white/95 backdrop-blur-xl border border-white/60 rounded-2xl sm:rounded-3xl shadow-2xl shadow-emerald-950/40 p-5 sm:p-9 relative z-10">
            <div class="text-center mb-7">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-bold uppercase tracking-wider mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    SMS Verification
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $title ?? 'Verify Your Number' }}</h1>
                <p class="text-slate-600 text-xs sm:text-sm mt-2 font-medium">
                    We sent a 6-digit code to <span class="font-bold text-slate-900">{{ $masked }}</span>
                </p>
                @if(!empty($expiresMinutes))
                    <p class="text-slate-400 text-xs mt-1">Code expires in {{ $expiresMinutes }} minutes.</p>
                @endif
            </div>

            {{-- Status Flash --}}
            @if(session('status'))
                <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-2.5">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-xs sm:text-sm text-emerald-800 font-medium">{{ session('status') }}</p>
                </div>
            @endif

            {{-- Error Flash --}}
            @if($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 flex items-start gap-2.5">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="text-xs sm:text-sm text-red-700 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <p class="font-medium">{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Verify Form --}}
            <form action="{{ $verifyUrl }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label for="otp_code" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">6-Digit Verification Code</label>
                    <input
                        type="text"
                        name="code"
                        id="otp_code"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        required
                        autofocus
                        placeholder="000000"
                        oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)"
                        class="block w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-center text-2xl font-display font-extrabold tracking-[0.4em] text-slate-900 placeholder-slate-300
                               focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all"
                    >
                </div>

                <button type="submit"
                        class="w-full flex justify-center items-center gap-2 py-3.5 px-4 rounded-xl text-sm font-bold text-white
                               bg-emerald-600 hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500
                               transition-all shadow-lg shadow-emerald-950/20 active:scale-[0.98] cursor-pointer">
                    Verify Code
                </button>
            </form>

            {{-- Resend & Cancel --}}
            <div class="mt-6 pt-5 border-t border-slate-100 flex flex-col items-center gap-3">
                <form action="{{ $resendUrl }}" method="POST" class="w-full">
                    @csrf
                    <button
                        type="submit"
                        id="resendOtpBtn"
                        data-seconds="{{ max(0, (int) ($resendAvailableIn ?? 0)) }}"
                        {{ max(0, (int) ($resendAvailableIn ?? 0)) > 0 ? 'disabled' : '' }}
                        class="w-full py-2.5 px-4 rounded-xl text-xs sm:text-sm font-semibold border border-slate-200 text-slate-700 bg-slate-50 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all cursor-pointer"
                    >
                        @if(max(0, (int) ($resendAvailableIn ?? 0)) > 0)
                            Resend code in <span id="resendCountdown">{{ (int) $resendAvailableIn }}</span>s
                        @else
                            Resend code
                        @endif
                    </button>
                </form>

                @if(strtoupper($cancelMethod ?? 'GET') === 'POST')
                    <form action="{{ $cancelUrl ?? route('login') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-slate-700 hover:underline cursor-pointer">
                            Cancel and go back
                        </button>
                    </form>
                @else
                    <a href="{{ $cancelUrl ?? route('login') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 hover:underline">
                        Cancel and go back
                    </a>
                @endif
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
        (function () {
            const btn = document.getElementById('resendOtpBtn');
            if (!btn) return;
            let remaining = parseInt(btn.getAttribute('data-seconds') || '0', 10);
            if (remaining <= 0) return;

            function formatRemaining(sec) {
                if (sec >= 60) {
                    const m = Math.floor(sec / 60);
                    const s = sec % 60;
                    return m + 'm ' + String(s).padStart(2, '0') + 's';
                }
                return sec + 's';
            }

            btn.disabled = true;
            btn.textContent = 'Resend code in ' + formatRemaining(remaining);
            const timer = setInterval(function () {
                remaining -= 1;
                if (remaining <= 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    btn.textContent = 'Resend code';
                } else {
                    btn.textContent = 'Resend code in ' + formatRemaining(remaining);
                }
            }, 1000);
        })();
    </script>
</body>
</html>
