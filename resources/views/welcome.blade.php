<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MEDLINK — Barangay Honrado Health Center. Check medicine availability, request home health service, and stay updated on health schedules — all online.">
    <title>MEDLINK — Barangay Honrado Health Center</title>

    {{-- Tailwind CSS v4 CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    {{-- Google Fonts & Icons --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

    <style type="text/tailwindcss">
        @theme {
            --font-display: 'Plus Jakarta Sans', sans-serif;
            --font-body: 'Inter', sans-serif;
        }
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4, .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>

    <style>
        :root {
            --gutter-x: clamp(1rem, 3.5vw, 3rem);
            --section-py: clamp(3.5rem, 7vw, 6.5rem);
            --card-pad: clamp(1.25rem, 3vw, 2.25rem);
            --text-hero: clamp(2rem, 5.2vw + 0.6rem, 4.5rem);
            --text-hero-lead: clamp(0.95rem, 1.1vw + 0.55rem, 1.25rem);
            --text-section: clamp(1.65rem, 3vw + 0.5rem, 3rem);
            --text-cta: clamp(1.45rem, 2.5vw + 0.5rem, 2.35rem);
        }

        .fluid-x {
            padding-left: var(--gutter-x);
            padding-right: var(--gutter-x);
        }

        .fluid-section {
            padding-top: var(--section-py);
            padding-bottom: var(--section-py);
        }

        .fluid-card {
            padding: var(--card-pad);
        }

        .fluid-hero-title {
            font-size: var(--text-hero);
        }

        .fluid-hero-lead {
            font-size: var(--text-hero-lead);
        }

        .fluid-section-title {
            font-size: var(--text-section);
        }

        .fluid-cta-title {
            font-size: var(--text-cta);
        }

        /* Offset anchored sections for fixed header */
        section[id] {
            scroll-margin-top: 5.5rem;
        }

        /* Fluid auto-fit services grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 240px), 1fr));
            gap: clamp(1rem, 2vw, 1.5rem);
        }

        /* Lightweight scrolled header */
        #mainHeader {
            transition: background-color 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease, padding 0.25s ease;
        }
        .header-scrolled {
            background: rgba(2, 18, 12, 0.96);
            border-bottom: 1px solid rgba(52, 211, 153, 0.16);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
            padding-top: 0.7rem !important;
            padding-bottom: 0.7rem !important;
        }

        /* Responsive hero background overlay (stronger contrast on portrait mobile) */
        .hero-surface {
            background-image:
                linear-gradient(
                    180deg,
                    rgba(2, 20, 11, 0.88) 0%,
                    rgba(3, 28, 16, 0.84) 55%,
                    rgba(2, 18, 10, 0.92) 100%
                ),
                url('{{ asset('honrado.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        @media (min-width: 768px) {
            .hero-surface {
                background-image:
                    linear-gradient(
                        90deg,
                        rgba(2, 20, 11, 0.93) 0%,
                        rgba(3, 30, 17, 0.85) 48%,
                        rgba(3, 30, 17, 0.52) 100%
                    ),
                    url('{{ asset('honrado.png') }}');
            }
        }

        /* Smooth mobile drawer transition */
        .mobile-menu-panel {
            display: grid;
            grid-template-rows: 0fr;
            opacity: 0;
            pointer-events: none;
            transition: grid-template-rows 0.25s ease, opacity 0.2s ease;
        }
        .mobile-menu-panel.is-open {
            grid-template-rows: 1fr;
            opacity: 1;
            pointer-events: auto;
        }
        .mobile-menu-inner {
            overflow: hidden;
        }

        /* Subtle scroll-reveal animation */
        .reveal {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity 0.55s ease, transform 0.55s ease;
        }
        .reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .reveal {
                opacity: 1 !important;
                transform: none !important;
                transition: none !important;
            }
            .mobile-menu-panel {
                transition: none !important;
            }
        }
    </style>
    @vite(['resources/js/medlink.js'])
</head>
<body class="min-h-full bg-slate-950 text-slate-100 antialiased selection:bg-emerald-500 selection:text-white flex flex-col overflow-x-hidden">

{{-- ══════════════════════════════════════════════════════════════
     STICKY HEADER (Fluid, Responsive & Scroll-Spy Enabled)
══════════════════════════════════════════════════════════════ --}}
<header id="mainHeader" class="fixed top-0 left-0 w-full z-50 fluid-x py-3.5 sm:py-4 lg:py-5">
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-3 sm:gap-4">

        {{-- Brand Logo --}}
        <a href="#top" class="flex items-center gap-2.5 sm:gap-3 min-w-0 flex-1 lg:flex-initial">
            <img src="{{ asset('images/logo.png') }}?v={{ time() }}" alt="MEDLINK Logo"
                 class="w-9 h-9 sm:w-12 sm:h-12 lg:w-14 lg:h-14 rounded-xl sm:rounded-2xl object-contain shrink-0">
            <div class="min-w-0">
                <div class="font-display font-black text-white text-base sm:text-xl lg:text-2xl tracking-tight leading-none flex items-center gap-1.5 sm:gap-2 flex-wrap">
                    <span>MEDLINK</span>
                    <span class="inline-flex items-center gap-1 text-[9px] sm:text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        ONLINE
                    </span>
                </div>
                <div class="text-[11px] sm:text-xs text-slate-300/80 mt-0.5 font-medium truncate">Barangay Honrado Health Center</div>
            </div>
        </a>

        {{-- Desktop Navigation (lg+ to prevent tablet wrapping/crowding) --}}
        <nav aria-label="Primary Navigation" class="hidden lg:flex items-center gap-1 bg-slate-900/75 border border-white/10 rounded-full px-2 py-1.5 shrink-0">
            <a href="#top" data-nav-link="#top"
               class="whitespace-nowrap px-4 py-1.5 rounded-full text-xs xl:text-sm font-bold text-emerald-300 bg-emerald-500/15 transition-colors">
                Home
            </a>
            <a href="#about-us" data-nav-link="#about-us"
               class="whitespace-nowrap px-4 py-1.5 rounded-full text-xs xl:text-sm font-semibold text-slate-200 hover:text-white hover:bg-white/10 transition-colors">
                About Us
            </a>
            <a href="#services" data-nav-link="#services"
               class="whitespace-nowrap px-4 py-1.5 rounded-full text-xs xl:text-sm font-semibold text-slate-200 hover:text-white hover:bg-white/10 transition-colors">
                Services
            </a>
            <a href="#gallery" data-nav-link="#gallery"
               class="whitespace-nowrap px-4 py-1.5 rounded-full text-xs xl:text-sm font-semibold text-slate-200 hover:text-white hover:bg-white/10 transition-colors">
                Health Center Photos
            </a>
        </nav>

        {{-- Quick Action + Mobile/Tablet Hamburger --}}
        <div class="flex items-center gap-2 sm:gap-2.5 shrink-0">
            @if (Route::has('login'))
                <a href="{{ route('login') }}"
                   class="hidden sm:inline-flex items-center gap-1.5 px-3.5 lg:px-4 py-2 rounded-xl text-xs lg:text-sm font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 transition-colors whitespace-nowrap">
                    <span>Sign In</span>
                    <span class="material-icons-outlined" style="font-size: 16px;">arrow_forward</span>
                </a>
            @endif

            <button type="button" id="mobileMenuBtn" onclick="toggleMobileMenu()" aria-label="Toggle Menu" aria-expanded="false" aria-controls="mobileMenu"
                    class="lg:hidden w-10 h-10 rounded-xl bg-slate-900/80 border border-white/15 text-white flex items-center justify-center hover:bg-slate-800 transition-colors cursor-pointer">
                <span id="mobileMenuIcon" class="material-icons-outlined">menu</span>
            </button>
        </div>
    </div>

    {{-- Mobile & Tablet Dropdown Menu --}}
    <div id="mobileMenu" class="mobile-menu-panel lg:hidden max-w-7xl mx-auto">
        <div class="mobile-menu-inner">
            <div class="mt-3 p-4 rounded-2xl bg-slate-900/95 border border-emerald-400/20 shadow-2xl space-y-1.5">
                <a href="#top" data-mobile-nav-link="#top" onclick="closeMobileMenu()"
                   class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-bold text-emerald-400 bg-emerald-500/10 transition-colors">
                    <span>Home</span>
                    <span class="material-icons-outlined" style="font-size: 18px;">chevron_right</span>
                </a>
                <a href="#about-us" data-mobile-nav-link="#about-us" onclick="closeMobileMenu()"
                   class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/10 transition-colors">
                    <span>About Us</span>
                    <span class="material-icons-outlined text-slate-400" style="font-size: 18px;">chevron_right</span>
                </a>
                <a href="#services" data-mobile-nav-link="#services" onclick="closeMobileMenu()"
                   class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/10 transition-colors">
                    <span>Services</span>
                    <span class="material-icons-outlined text-slate-400" style="font-size: 18px;">chevron_right</span>
                </a>
                <a href="#gallery" data-mobile-nav-link="#gallery" onclick="closeMobileMenu()"
                   class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/10 transition-colors">
                    <span>Health Center Photos</span>
                    <span class="material-icons-outlined text-slate-400" style="font-size: 18px;">chevron_right</span>
                </a>
                <div class="pt-3 mt-2 border-t border-white/10 grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 gap-2.5">
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 transition-colors">
                            <span class="material-icons-outlined" style="font-size: 16px;">login</span>
                            Sign In
                        </a>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-xs font-bold text-white bg-white/10 border border-white/20 hover:bg-white/15 transition-colors">
                            <span class="material-icons-outlined" style="font-size: 16px;">person_add</span>
                            Register
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>

{{-- ══════════════════════════════════════════════════════════════
     HERO SECTION (Fluid Scaling & Adaptive Viewport Height)
══════════════════════════════════════════════════════════════ --}}
<section id="top" class="hero-surface relative w-full min-h-[min(100svh,880px)] flex items-center fluid-x pt-28 pb-14 sm:pt-32 sm:pb-20">
    <div class="max-w-7xl mx-auto w-full">
        <div class="max-w-3xl">
            {{-- Location Pill --}}
            <div class="inline-flex items-center gap-2 px-3 sm:px-3.5 py-1.5 rounded-full bg-emerald-500/15 border border-emerald-400/30 text-emerald-300 text-[11px] sm:text-xs font-semibold mb-5 sm:mb-6 max-w-full">
                <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
                <span class="truncate">Brgy. Honrado &bull; San Francisco, Surigao del Norte</span>
            </div>

            {{-- Headline --}}
            <h1 class="font-display fluid-hero-title font-black text-white tracking-tight leading-[1.08] mb-4 sm:mb-5">
                Know. Request. Track.<br>
                <span class="text-emerald-400">The MEDLINK Way.</span>
            </h1>

            {{-- Description --}}
            <p class="text-slate-200/90 fluid-hero-lead font-normal leading-relaxed max-w-2xl mb-7 sm:mb-8">
                Everything you need to know, every request you need to make, and every update you want to track — MEDLINK brings Barangay Honrado's health services together in one convenient place.
            </p>

            {{-- CTA Buttons --}}
            <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3 sm:gap-3.5 mb-7 sm:mb-8">
                @if (Route::has('login'))
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center justify-center gap-2.5 px-6 sm:px-8 py-3.5 sm:py-4 rounded-2xl text-sm sm:text-base font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 active:scale-[0.99] transition-all shadow-lg">
                        <span>Sign In to MEDLINK</span>
                        <span class="material-icons-outlined" style="font-size: 20px;">login</span>
                    </a>
                @endif
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center justify-center gap-2 px-6 sm:px-7 py-3.5 sm:py-4 rounded-2xl text-sm sm:text-base font-bold text-white bg-slate-900/70 hover:bg-slate-900/90 active:scale-[0.99] transition-all border border-white/25">
                        <span class="material-icons-outlined text-emerald-300" style="font-size: 20px;">person_add</span>
                        <span>Create Account</span>
                    </a>
                @endif
            </div>

            {{-- Trust Note --}}
            <div class="flex items-start sm:items-center gap-2 text-xs text-slate-300/85 font-medium max-w-xl">
                <span class="material-icons-outlined text-emerald-400 shrink-0 mt-0.5 sm:mt-0" style="font-size: 16px;">verified</span>
                <span>Managed by Barangay Honrado Health Center. Medicine requests are subject to staff review and approval.</span>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     ABOUT US & COMPARISON SECTION
══════════════════════════════════════════════════════════════ --}}
<section id="about-us" class="w-full bg-slate-50 fluid-section fluid-x border-t border-slate-200/80">
    <div class="max-w-7xl mx-auto">
        <div class="max-w-2xl mb-10 sm:mb-12 reveal">
            <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-emerald-700 bg-emerald-100/90 px-3.5 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                About Us
            </span>
            <h2 class="font-display fluid-section-title font-extrabold text-slate-900 mt-4 tracking-tight leading-tight">
                Barangay Honrado Health Center
            </h2>
            <p class="text-slate-500 text-sm sm:text-base mt-3 leading-relaxed">
                We are dedicated to providing accessible, transparent, and responsive healthcare services to all families in San Francisco, Surigao del Norte.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 lg:gap-8 items-stretch">
            {{-- Traditional System --}}
            <div class="reveal flex flex-col h-full bg-white fluid-card rounded-3xl border border-slate-200 shadow-sm">
                <div class="flex items-center gap-2.5 mb-5">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                        <span class="material-icons-outlined" style="font-size: 20px;">history</span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-rose-600">Traditional System</h3>
                        <p class="text-[11px] text-slate-400 font-medium">Manual walk-in & paper workflow</p>
                    </div>
                </div>
                <ul class="space-y-4 text-sm text-slate-600 flex-1">
                    <li class="flex items-start gap-3">
                        <span class="w-5 h-5 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center shrink-0 mt-0.5">
                            <span class="material-icons-outlined" style="font-size:14px;">close</span>
                        </span>
                        <span>Walk-in trips under heat or rain just to ask if paracetamol or prescription medicine is available.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="w-5 h-5 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center shrink-0 mt-0.5">
                            <span class="material-icons-outlined" style="font-size:14px;">close</span>
                        </span>
                        <span>Manual paper logbooks prone to wear, tear, or misplacement over time.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="w-5 h-5 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center shrink-0 mt-0.5">
                            <span class="material-icons-outlined" style="font-size:14px;">close</span>
                        </span>
                        <span>No live updates on vaccination, prenatal checkup, or ultrasound schedules.</span>
                    </li>
                </ul>
            </div>

            {{-- With Medlink --}}
            <div class="reveal flex flex-col h-full bg-gradient-to-br from-white via-emerald-50/30 to-white fluid-card rounded-3xl border-2 border-emerald-500 shadow-md">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0">
                            <span class="material-icons-outlined" style="font-size: 20px;">bolt</span>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-emerald-700">With MEDLINK Online System</h3>
                            <p class="text-[11px] text-emerald-600/80 font-medium">Fast, digital & synced across portals</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider bg-emerald-500 text-white px-3 py-1 rounded-full shrink-0">
                        Real-Time
                    </span>
                </div>
                <ul class="space-y-4 text-sm text-slate-700 font-medium flex-1">
                    <li class="flex items-start gap-3">
                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                            <span class="material-icons-outlined" style="font-size:14px;">check</span>
                        </span>
                        <span>Check available medicine stock and request home delivery or center pickup in seconds.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                            <span class="material-icons-outlined" style="font-size:14px;">check</span>
                        </span>
                        <span>Digital patient records, disease surveillance, and child vaccine dose tracking.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                            <span class="material-icons-outlined" style="font-size:14px;">check</span>
                        </span>
                        <span>Live barangay health announcements and official notices delivered straight to your phone.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     SERVICES SECTION (Fluid Auto-Fit Grid)
══════════════════════════════════════════════════════════════ --}}
<section id="services" class="w-full bg-white fluid-section fluid-x border-t border-slate-200/80">
    <div class="max-w-7xl mx-auto">
        <div class="max-w-2xl mb-10 sm:mb-12 reveal">
            <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-blue-700 bg-blue-100 px-3.5 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                Digital Services
            </span>
            <h2 class="font-display fluid-section-title font-extrabold text-slate-900 mt-4 tracking-tight leading-tight">
                Everything Your Family Needs
            </h2>
            <p class="text-slate-500 text-sm sm:text-base mt-2 leading-relaxed">
                Designed for residents, Barangay Health Workers, and nurses to stay connected in real time.
            </p>
        </div>

        <div class="services-grid">
            <div class="reveal flex flex-col h-full p-6 rounded-3xl bg-slate-50 border border-slate-200/80 hover:bg-white hover:border-emerald-300 hover:shadow-md hover:-translate-y-1 transition-all duration-200">
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4 shrink-0">
                    <span class="material-icons-outlined" style="font-size: 24px;">medication</span>
                </div>
                <h3 class="font-display font-bold text-slate-900 text-base">Medicine Requests</h3>
                <p class="text-xs sm:text-[13px] text-slate-500 mt-2 leading-relaxed flex-1">Browse live pharmacy inventory, reserve doses, and track approval for pickup or home delivery.</p>
            </div>

            <div class="reveal flex flex-col h-full p-6 rounded-3xl bg-slate-50 border border-slate-200/80 hover:bg-white hover:border-cyan-300 hover:shadow-md hover:-translate-y-1 transition-all duration-200">
                <div class="w-12 h-12 rounded-2xl bg-cyan-100 text-cyan-600 flex items-center justify-center mb-4 shrink-0">
                    <span class="material-icons-outlined" style="font-size: 24px;">home_health</span>
                </div>
                <h3 class="font-display font-bold text-slate-900 text-base">Home Health Visits</h3>
                <p class="text-xs sm:text-[13px] text-slate-500 mt-2 leading-relaxed flex-1">Priority home health service booking for PWD and Senior Citizen residents with live BHW dispatch.</p>
            </div>

            <div class="reveal flex flex-col h-full p-6 rounded-3xl bg-slate-50 border border-slate-200/80 hover:bg-white hover:border-blue-300 hover:shadow-md hover:-translate-y-1 transition-all duration-200">
                <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center mb-4 shrink-0">
                    <span class="material-icons-outlined" style="font-size: 24px;">vaccines</span>
                </div>
                <h3 class="font-display font-bold text-slate-900 text-base">Vaccination & Schedules</h3>
                <p class="text-xs sm:text-[13px] text-slate-500 mt-2 leading-relaxed flex-1">Stay informed on immunization days, prenatal ultrasound slots, and health center appointments.</p>
            </div>

            <div class="reveal flex flex-col h-full p-6 rounded-3xl bg-slate-50 border border-slate-200/80 hover:bg-white hover:border-amber-300 hover:shadow-md hover:-translate-y-1 transition-all duration-200">
                <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mb-4 shrink-0">
                    <span class="material-icons-outlined" style="font-size: 24px;">campaign</span>
                </div>
                <h3 class="font-display font-bold text-slate-900 text-base">Health Advisories</h3>
                <p class="text-xs sm:text-[13px] text-slate-500 mt-2 leading-relaxed flex-1">Receive official barangay announcements, disease surveillance alerts, and live status notifications.</p>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     COMMUNITY GALLERY (Proportional Aspect Ratios & Dynamic Lightbox)
══════════════════════════════════════════════════════════════ --}}
<section id="gallery" class="w-full bg-slate-50 fluid-section fluid-x border-t border-slate-200/80">
    <div class="max-w-7xl mx-auto">
        <div class="max-w-2xl mb-10 sm:mb-12 reveal">
            <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-emerald-700 bg-emerald-100 px-3.5 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                Community Activities
            </span>
            <h2 class="font-display fluid-section-title font-extrabold text-slate-900 mt-4 tracking-tight leading-tight">
                Barangay Health Center in Action
            </h2>
            <p class="text-slate-500 text-sm sm:text-base mt-2 leading-relaxed">
                Actual moments and documentation of healthcare services, medical missions, and community support in Barangay Honrado. Tap any photo to view full screen.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6">

            {{-- PIC 1 --}}
            <article role="button" tabindex="0" data-gallery-index="0"
                     onclick="openGalleryLightbox(0)"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openGalleryLightbox(0);}"
                     class="reveal group flex flex-col bg-white rounded-3xl overflow-hidden border border-slate-200/90 shadow-sm hover:shadow-lg transition-all duration-200 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                <div class="relative aspect-[16/10] overflow-hidden bg-slate-200">
                    <img src="{{ asset('images/pic1.jpg') }}" alt="Health Center Facility & Clinic" loading="lazy"
                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                    <span class="absolute top-3.5 left-3.5 bg-slate-950/80 text-emerald-300 border border-white/15 text-[11px] font-bold px-3 py-1 rounded-full">
                        Health Center
                    </span>
                </div>
                <div class="p-5 sm:p-6 flex-1 flex flex-col">
                    <h3 class="font-display font-bold text-base text-slate-900">Health Center Facility & Clinic</h3>
                    <p class="text-xs sm:text-[13px] text-slate-500 mt-1.5 leading-relaxed">
                        The Barangay Honrado Health Center building, serving as the central hub for local consultations, walk-in patient triage, and daily medical inquiries.
                    </p>
                </div>
            </article>

            {{-- PIC 2 --}}
            <article role="button" tabindex="0" data-gallery-index="1"
                     onclick="openGalleryLightbox(1)"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openGalleryLightbox(1);}"
                     class="reveal group flex flex-col bg-white rounded-3xl overflow-hidden border border-slate-200/90 shadow-sm hover:shadow-lg transition-all duration-200 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                <div class="relative aspect-[16/10] overflow-hidden bg-slate-200">
                    <img src="{{ asset('images/pic2.jpg') }}" alt="Medicine Dispensing & Inventory" loading="lazy"
                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                    <span class="absolute top-3.5 left-3.5 bg-slate-950/80 text-emerald-300 border border-white/15 text-[11px] font-bold px-3 py-1 rounded-full">
                        Pharmacy
                    </span>
                </div>
                <div class="p-5 sm:p-6 flex-1 flex flex-col">
                    <h3 class="font-display font-bold text-base text-slate-900">Medicine Dispensing & Inventory</h3>
                    <p class="text-xs sm:text-[13px] text-slate-500 mt-1.5 leading-relaxed">
                        Accredited barangay medicine stockroom where paracetamol, maintenance drugs, antibiotics, and vitamins are stored and verified by our nurse.
                    </p>
                </div>
            </article>

            {{-- PIC 3 --}}
            <article role="button" tabindex="0" data-gallery-index="2"
                     onclick="openGalleryLightbox(2)"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openGalleryLightbox(2);}"
                     class="reveal group flex flex-col bg-white rounded-3xl overflow-hidden border border-slate-200/90 shadow-sm hover:shadow-lg transition-all duration-200 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                <div class="relative aspect-[16/10] overflow-hidden bg-slate-200">
                    <img src="{{ asset('images/pic3.jpg') }}" alt="Child & Infant Immunization Day" loading="lazy"
                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                    <span class="absolute top-3.5 left-3.5 bg-slate-950/80 text-emerald-300 border border-white/15 text-[11px] font-bold px-3 py-1 rounded-full">
                        Immunization
                    </span>
                </div>
                <div class="p-5 sm:p-6 flex-1 flex flex-col">
                    <h3 class="font-display font-bold text-base text-slate-900">Child & Infant Immunization Day</h3>
                    <p class="text-xs sm:text-[13px] text-slate-500 mt-1.5 leading-relaxed">
                        National Immunization Program sessions administering 1st dose, 2nd dose, and booster vaccines for toddlers, babies, and school-age children.
                    </p>
                </div>
            </article>

            {{-- PIC 4 --}}
            <article role="button" tabindex="0" data-gallery-index="3"
                     onclick="openGalleryLightbox(3)"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openGalleryLightbox(3);}"
                     class="reveal group flex flex-col bg-white rounded-3xl overflow-hidden border border-slate-200/90 shadow-sm hover:shadow-lg transition-all duration-200 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                <div class="relative aspect-[16/10] overflow-hidden bg-slate-200">
                    <img src="{{ asset('images/pic4.jpg') }}" alt="BHW Home Visits & Delivery" loading="lazy"
                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                    <span class="absolute top-3.5 left-3.5 bg-slate-950/80 text-emerald-300 border border-white/15 text-[11px] font-bold px-3 py-1 rounded-full">
                        Home Visits
                    </span>
                </div>
                <div class="p-5 sm:p-6 flex-1 flex flex-col">
                    <h3 class="font-display font-bold text-base text-slate-900">BHW Home Visits & Delivery</h3>
                    <p class="text-xs sm:text-[13px] text-slate-500 mt-1.5 leading-relaxed">
                        Dedicated Barangay Health Workers bringing essential maintenance medicines directly to the doorsteps of seniors, persons with disabilities, and bedridden residents.
                    </p>
                </div>
            </article>

            {{-- PIC 5 (Spans 2 columns on sm+ with proportional wide aspect ratio) --}}
            <article role="button" tabindex="0" data-gallery-index="4"
                     onclick="openGalleryLightbox(4)"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openGalleryLightbox(4);}"
                     class="reveal group flex flex-col bg-white rounded-3xl overflow-hidden border border-slate-200/90 shadow-sm hover:shadow-lg transition-all duration-200 cursor-pointer sm:col-span-2 lg:col-span-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                <div class="relative aspect-[16/10] sm:aspect-[21/9] overflow-hidden bg-slate-200">
                    <img src="{{ asset('images/pic5.jpg') }}" alt="Health Surveillance & Community Care" loading="lazy"
                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                    <span class="absolute top-3.5 left-3.5 bg-slate-950/80 text-emerald-300 border border-white/15 text-[11px] font-bold px-3 py-1 rounded-full">
                        Community Care
                    </span>
                </div>
                <div class="p-5 sm:p-6 flex-1 flex flex-col">
                    <h3 class="font-display font-bold text-base text-slate-900">Health Surveillance & Community Care</h3>
                    <p class="text-xs sm:text-[13px] text-slate-500 mt-1.5 leading-relaxed">
                        Barangay health workers actively monitoring community wellness, coordinating Dengue awareness drives, and checking vital signs for our residents.
                    </p>
                </div>
            </article>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     CALL TO ACTION BANNER
══════════════════════════════════════════════════════════════ --}}
<section class="w-full bg-slate-950 py-12 sm:py-16 fluid-x">
    <div class="reveal max-w-5xl mx-auto rounded-3xl bg-slate-900 border border-emerald-500/20 text-center" style="padding: clamp(1.75rem, 4vw, 3.5rem);">
        <h2 class="font-display fluid-cta-title font-extrabold text-white tracking-tight leading-tight">
            Ready to access Barangay Health Services online?
        </h2>
        <p class="text-slate-300 text-sm sm:text-base mt-3 max-w-xl mx-auto leading-relaxed">
            Create your verified resident account or sign in to request medicines, book home visits, and track updates in real time.
        </p>
        <div class="mt-7 flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center justify-center gap-3 sm:gap-3.5">
            @if (Route::has('login'))
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-2xl text-sm font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 transition-colors">
                    <span>Sign In Now</span>
                    <span class="material-icons-outlined" style="font-size: 18px;">login</span>
                </a>
            @endif
            @if (Route::has('register'))
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-2xl text-sm font-bold text-white bg-white/10 hover:bg-white/20 transition-colors border border-white/20">
                    <span class="material-icons-outlined text-emerald-300" style="font-size: 18px;">person_add</span>
                    <span>Register as Resident</span>
                </a>
            @endif
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════════════════ --}}
<footer class="w-full bg-slate-950 text-slate-400 py-8 fluid-x border-t border-slate-800/80 text-xs">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row flex-wrap items-center justify-between gap-4 text-center md:text-left">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/logo.png') }}?v={{ time() }}" alt="MEDLINK" class="w-8 h-8 rounded-lg object-contain shrink-0">
            <div>
                <span class="font-display font-bold text-white text-sm">MEDLINK</span> &bull; Barangay Honrado Health Center
                <p class="text-[11px] text-slate-500 mt-0.5">San Francisco, Surigao del Norte</p>
            </div>
        </div>
        <p class="text-[11px] text-slate-500">&copy; {{ date('Y') }} Barangay Honrado Health Center. All rights reserved.</p>
    </div>
</footer>

{{-- ══════════════════════════════════════════════════════════════
     GALLERY LIGHTBOX MODAL (Adaptive + Prev/Next + Swipe)
══════════════════════════════════════════════════════════════ --}}
<div id="galleryLightbox" role="dialog" aria-modal="true" aria-label="Gallery Photo Viewer"
     onclick="closeGalleryLightbox()"
     class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/95 p-3 sm:p-6">
    <button type="button" onclick="closeGalleryLightbox()" aria-label="Close photo"
            class="absolute top-4 right-4 sm:top-5 sm:right-5 w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 text-white flex items-center justify-center transition-colors cursor-pointer z-10">
        <span class="material-icons-outlined">close</span>
    </button>

    <button type="button" onclick="event.stopPropagation(); navigateGallery(-1)" aria-label="Previous photo"
            class="hidden sm:flex absolute left-4 lg:left-6 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/15 hover:bg-white/25 text-white items-center justify-center transition-colors cursor-pointer z-10">
        <span class="material-icons-outlined">chevron_left</span>
    </button>

    <div id="lightboxStage" class="max-w-4xl w-full flex flex-col items-center gap-3" onclick="event.stopPropagation()">
        <img id="lightboxImg" src="" alt=""
             class="max-h-[75dvh] max-w-[92vw] w-auto rounded-2xl shadow-2xl border border-white/15 object-contain select-none">
        <div class="flex items-center justify-between sm:justify-center gap-4 w-full max-w-md px-2">
            <button type="button" onclick="navigateGallery(-1)" aria-label="Previous photo"
                    class="sm:hidden w-9 h-9 rounded-full bg-white/15 hover:bg-white/25 text-white flex items-center justify-center shrink-0 cursor-pointer">
                <span class="material-icons-outlined" style="font-size: 20px;">chevron_left</span>
            </button>
            <div class="text-center min-w-0">
                <p id="lightboxCaption" class="font-display text-sm sm:text-base font-bold text-white truncate"></p>
                <p id="lightboxCounter" class="text-[11px] text-slate-400 font-medium mt-0.5"></p>
            </div>
            <button type="button" onclick="navigateGallery(1)" aria-label="Next photo"
                    class="sm:hidden w-9 h-9 rounded-full bg-white/15 hover:bg-white/25 text-white flex items-center justify-center shrink-0 cursor-pointer">
                <span class="material-icons-outlined" style="font-size: 20px;">chevron_right</span>
            </button>
        </div>
    </div>

    <button type="button" onclick="event.stopPropagation(); navigateGallery(1)" aria-label="Next photo"
            class="hidden sm:flex absolute right-4 lg:right-6 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/15 hover:bg-white/25 text-white items-center justify-center transition-colors cursor-pointer z-10">
        <span class="material-icons-outlined">chevron_right</span>
    </button>
</div>

<script>
    // 1. Lightweight sticky header on scroll
    const mainHeader = document.getElementById('mainHeader');
    function updateHeaderOnScroll() {
        if (!mainHeader) return;
        mainHeader.classList.toggle('header-scrolled', window.scrollY > 40);
    }
    window.addEventListener('scroll', updateHeaderOnScroll, { passive: true });
    updateHeaderOnScroll();

    // 2. Mobile/Tablet drawer toggle + outside click + resize auto-close
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenuIcon = document.getElementById('mobileMenuIcon');

    function setMobileMenuOpen(open) {
        if (!mobileMenu) return;
        mobileMenu.classList.toggle('is-open', open);
        if (mobileMenuBtn) mobileMenuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (mobileMenuIcon) mobileMenuIcon.textContent = open ? 'close' : 'menu';
    }
    function toggleMobileMenu() {
        if (!mobileMenu) return;
        setMobileMenuOpen(!mobileMenu.classList.contains('is-open'));
    }
    function closeMobileMenu() {
        setMobileMenuOpen(false);
    }

    document.addEventListener('click', (e) => {
        if (!mobileMenu || !mobileMenu.classList.contains('is-open')) return;
        if (mainHeader && !mainHeader.contains(e.target)) {
            closeMobileMenu();
        }
    });

    const desktopMediaQuery = window.matchMedia('(min-width: 1024px)');
    if (desktopMediaQuery.addEventListener) {
        desktopMediaQuery.addEventListener('change', (e) => {
            if (e.matches) closeMobileMenu();
        });
    }

    // 3. Dynamic Scroll-Spy for active nav link highlighting
    const sectionIds = ['top', 'about-us', 'services', 'gallery'];
    const desktopLinks = document.querySelectorAll('[data-nav-link]');
    const mobileLinks = document.querySelectorAll('[data-mobile-nav-link]');

    function setActiveNav(targetHash) {
        desktopLinks.forEach((link) => {
            const isActive = link.getAttribute('data-nav-link') === targetHash;
            link.classList.toggle('font-bold', isActive);
            link.classList.toggle('text-emerald-300', isActive);
            link.classList.toggle('bg-emerald-500/15', isActive);
            link.classList.toggle('font-semibold', !isActive);
            link.classList.toggle('text-slate-200', !isActive);
        });
        mobileLinks.forEach((link) => {
            const isActive = link.getAttribute('data-mobile-nav-link') === targetHash;
            link.classList.toggle('font-bold', isActive);
            link.classList.toggle('text-emerald-400', isActive);
            link.classList.toggle('bg-emerald-500/10', isActive);
            link.classList.toggle('font-semibold', !isActive);
            link.classList.toggle('text-slate-200', !isActive);
        });
    }

    if ('IntersectionObserver' in window) {
        const navObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    setActiveNav('#' + entry.target.id);
                }
            });
        }, {
            rootMargin: '-35% 0px -55% 0px',
            threshold: 0
        });

        sectionIds.forEach((id) => {
            const el = document.getElementById(id);
            if (el) navObserver.observe(el);
        });
    }

    // 4. Subtle Scroll-Reveal for sections and cards
    const revealEls = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const revealObserver = new IntersectionObserver((entries, obs) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -40px 0px'
        });
        revealEls.forEach((el) => revealObserver.observe(el));
    } else {
        revealEls.forEach((el) => el.classList.add('is-visible'));
    }

    // 5. Dynamic Gallery Lightbox (Index-based + Prev/Next + Swipe)
    const galleryItems = [
        { src: '{{ asset('images/pic1.jpg') }}', caption: 'Health Center Facility & Clinic' },
        { src: '{{ asset('images/pic2.jpg') }}', caption: 'Medicine Dispensing & Inventory' },
        { src: '{{ asset('images/pic3.jpg') }}', caption: 'Child & Infant Immunization Day' },
        { src: '{{ asset('images/pic4.jpg') }}', caption: 'BHW Home Visits & Delivery' },
        { src: '{{ asset('images/pic5.jpg') }}', caption: 'Health Surveillance & Community Care' }
    ];
    let currentGalleryIndex = 0;
    let touchStartX = 0;

    function renderLightboxItem() {
        const item = galleryItems[currentGalleryIndex];
        const img = document.getElementById('lightboxImg');
        const cap = document.getElementById('lightboxCaption');
        const counter = document.getElementById('lightboxCounter');
        if (!item || !img) return;
        img.src = item.src;
        img.alt = item.caption;
        if (cap) cap.textContent = item.caption;
        if (counter) counter.textContent = `${currentGalleryIndex + 1} / ${galleryItems.length}`;
    }

    function openGalleryLightbox(index) {
        const lb = document.getElementById('galleryLightbox');
        if (!lb) return;
        currentGalleryIndex = ((index % galleryItems.length) + galleryItems.length) % galleryItems.length;
        renderLightboxItem();
        lb.classList.remove('hidden');
        lb.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function navigateGallery(delta) {
        currentGalleryIndex = (currentGalleryIndex + delta + galleryItems.length) % galleryItems.length;
        renderLightboxItem();
    }

    function closeGalleryLightbox() {
        const lb = document.getElementById('galleryLightbox');
        if (!lb) return;
        lb.classList.add('hidden');
        lb.classList.remove('flex');
        document.body.style.overflow = '';
    }

    const lightboxStage = document.getElementById('lightboxStage');
    if (lightboxStage) {
        lightboxStage.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        lightboxStage.addEventListener('touchend', (e) => {
            const diff = e.changedTouches[0].screenX - touchStartX;
            if (Math.abs(diff) > 40) {
                navigateGallery(diff < 0 ? 1 : -1);
            }
        }, { passive: true });
    }

    document.addEventListener('keydown', (e) => {
        const lb = document.getElementById('galleryLightbox');
        const lbOpen = lb && !lb.classList.contains('hidden');
        if (e.key === 'Escape') {
            closeGalleryLightbox();
            closeMobileMenu();
        } else if (lbOpen && e.key === 'ArrowRight') {
            navigateGallery(1);
        } else if (lbOpen && e.key === 'ArrowLeft') {
            navigateGallery(-1);
        }
    });
</script>
</body>
</html>
