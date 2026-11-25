<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
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
            --gutter-x: clamp(0.875rem, 3.2vw, 2.5rem);
            --text-hero: clamp(1.9rem, 4.1vw + 0.5rem, 3.85rem);
            --text-hero-lead: clamp(0.95rem, 1vw + 0.55rem, 1.15rem);
            --text-section: clamp(1.45rem, 2.4vw + 0.45rem, 2.25rem);
            --text-cta: clamp(1.35rem, 2.3vw + 0.45rem, 2.15rem);
        }

        .fluid-x {
            padding-left: var(--gutter-x);
            padding-right: var(--gutter-x);
        }

        .fluid-hero-title { font-size: var(--text-hero); }
        .fluid-hero-lead { font-size: var(--text-hero-lead); }
        .fluid-section-title { font-size: var(--text-section); }
        .fluid-cta-title { font-size: var(--text-cta); }

        /* Offset anchored sections for fixed header */
        section[id] {
            scroll-margin-top: 5.75rem;
        }

        /* Soft borderless surface shadows */
        .panel-shadow {
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03), 0 14px 36px -18px rgba(6, 78, 59, 0.14);
        }
        .card-shadow {
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03), 0 8px 24px -14px rgba(6, 78, 59, 0.12);
        }
        .card-shadow:hover {
            box-shadow: 0 18px 38px -16px rgba(6, 78, 59, 0.2);
        }
        .dev-shadow {
            box-shadow: 0 12px 32px -12px rgba(15, 23, 42, 0.18);
        }
        .dev-shadow:hover {
            box-shadow: 0 24px 48px -16px rgba(6, 78, 59, 0.28);
        }

        /* Aesthetic liquid-glass header & nav pill */
        #mainHeader {
            transition: background-color 0.3s ease, box-shadow 0.3s ease, padding 0.3s ease, backdrop-filter 0.3s ease;
        }
        .header-scrolled {
            background: rgba(3, 20, 14, 0.84);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            box-shadow: 0 10px 34px rgba(0, 0, 0, 0.32);
            padding-top: 0.65rem !important;
            padding-bottom: 0.65rem !important;
        }
        .nav-glass {
            background: rgba(6, 24, 18, 0.48);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            box-shadow: 0 10px 30px -8px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.14);
        }
        .nav-pill-active {
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.24), rgba(45, 212, 191, 0.16));
            color: #6ee7b7;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18), 0 4px 14px -4px rgba(16, 185, 129, 0.35);
        }
        .hero-glass {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 10px 28px -10px rgba(0, 0, 0, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.14);
        }

        /* Cinematic full-fit hero background overlay */
        .hero-surface {
            background-image:
                radial-gradient(circle at 18% 36%, rgba(16, 185, 129, 0.18), transparent 46%),
                linear-gradient(
                    180deg,
                    rgba(2, 18, 12, 0.86) 0%,
                    rgba(3, 24, 16, 0.78) 52%,
                    rgba(2, 16, 11, 0.92) 100%
                ),
                url('{{ asset('honrado.png') }}');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
        }
        @media (min-width: 768px) {
            .hero-surface {
                background-image:
                    radial-gradient(circle at 16% 38%, rgba(16, 185, 129, 0.20), transparent 44%),
                    linear-gradient(
                        95deg,
                        rgba(2, 18, 12, 0.92) 0%,
                        rgba(3, 26, 17, 0.82) 44%,
                        rgba(3, 26, 17, 0.42) 78%,
                        rgba(2, 18, 12, 0.52) 100%
                    ),
                    linear-gradient(
                        180deg,
                        rgba(2, 16, 11, 0.45) 0%,
                        transparent 26%,
                        transparent 74%,
                        rgba(2, 16, 11, 0.75) 100%
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

        /* Smooth FAQ accordion transition */
        .faq-answer {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.28s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .faq-item.is-open .faq-answer {
            grid-template-rows: 1fr;
        }
        .faq-answer-inner {
            overflow: hidden;
        }
        .faq-plus {
            transition: transform 0.25s ease;
        }
        .faq-item.is-open .faq-plus {
            transform: rotate(45deg);
        }

        /* Subtle scroll-reveal animation */
        .reveal {
            opacity: 0;
            transform: translateY(16px);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
        .reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Developer fallback pattern */
        .dev-fallback-bg {
            background:
                radial-gradient(circle at 20% 20%, rgba(16, 185, 129, 0.28), transparent 55%),
                radial-gradient(circle at 80% 80%, rgba(6, 95, 70, 0.45), transparent 60%),
                linear-gradient(145deg, #0b241b 0%, #071913 100%);
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .reveal {
                opacity: 1 !important;
                transform: none !important;
                transition: none !important;
            }
            .mobile-menu-panel, .faq-answer, .faq-plus {
                transition: none !important;
            }
        }
    </style>
    @vite(['resources/js/medlink.js'])
</head>
<body class="min-h-full bg-[#f3f7f5] text-slate-900 antialiased selection:bg-emerald-500 selection:text-white flex flex-col overflow-x-hidden">

{{-- ══════════════════════════════════════════════════════════════
     STICKY HEADER (Borderless Glass Pill, Mobile-First & Scroll-Spy)
══════════════════════════════════════════════════════════════ --}}
<header id="mainHeader" class="fixed top-0 left-0 w-full z-50 fluid-x py-3.5 sm:py-4 lg:py-5">
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-2.5 sm:gap-4">

        {{-- Brand Logo --}}
        <a href="#top" class="flex items-center gap-2.5 sm:gap-3 min-w-0 flex-1 lg:flex-initial">
            <img src="{{ asset('images/logo.png') }}?v={{ time() }}" alt="MEDLINK Logo"
                 class="w-9 h-9 sm:w-11 sm:h-11 lg:w-12 lg:h-12 rounded-xl sm:rounded-2xl object-contain shrink-0">
            <div class="min-w-0">
                <div class="font-display font-black text-white text-base sm:text-lg lg:text-xl tracking-tight leading-none">
                    MEDLINK
                </div>
                <div class="text-[11px] sm:text-xs text-slate-300/80 mt-0.5 font-medium truncate">Barangay Honrado Health Center</div>
            </div>
        </a>

        {{-- Desktop Navigation (Aesthetic Frosted Glass Pill) --}}
        <nav aria-label="Primary Navigation" class="hidden lg:flex items-center gap-1 nav-glass rounded-full p-1.5 shrink-0">
            <a href="#top" data-nav-link="#top"
               class="whitespace-nowrap px-4 py-2 rounded-full text-xs xl:text-[13px] tracking-wide font-bold nav-pill-active transition-all duration-200">
                Home
            </a>
            <a href="#about-us" data-nav-link="#about-us"
               class="whitespace-nowrap px-4 py-2 rounded-full text-xs xl:text-[13px] tracking-wide font-medium text-slate-200/90 hover:text-white hover:bg-white/10 transition-all duration-200">
                About
            </a>
            <a href="#services" data-nav-link="#services"
               class="whitespace-nowrap px-4 py-2 rounded-full text-xs xl:text-[13px] tracking-wide font-medium text-slate-200/90 hover:text-white hover:bg-white/10 transition-all duration-200">
                Services
            </a>
            <a href="#gallery" data-nav-link="#gallery"
               class="whitespace-nowrap px-4 py-2 rounded-full text-xs xl:text-[13px] tracking-wide font-medium text-slate-200/90 hover:text-white hover:bg-white/10 transition-all duration-200">
                Health Center
            </a>
            <a href="#faq" data-nav-link="#faq"
               class="whitespace-nowrap px-4 py-2 rounded-full text-xs xl:text-[13px] tracking-wide font-medium text-slate-200/90 hover:text-white hover:bg-white/10 transition-all duration-200">
                FAQ
            </a>
            <a href="#developers" data-nav-link="#developers"
               class="whitespace-nowrap px-4 py-2 rounded-full text-xs xl:text-[13px] tracking-wide font-medium text-slate-200/90 hover:text-white hover:bg-white/10 transition-all duration-200">
                Developers
            </a>
        </nav>

        {{-- Mobile/Tablet Hamburger --}}
        <div class="flex items-center lg:hidden shrink-0">
            <button type="button" id="mobileMenuBtn" onclick="toggleMobileMenu()" aria-label="Toggle Menu" aria-expanded="false" aria-controls="mobileMenu"
                    class="w-11 h-11 rounded-xl bg-slate-900/80 backdrop-blur-md text-white flex items-center justify-center hover:bg-slate-800 active:scale-95 transition-all cursor-pointer">
                <span id="mobileMenuIcon" class="material-icons-outlined">menu</span>
            </button>
        </div>
    </div>

    {{-- Mobile & Tablet Dropdown Menu --}}
    <div id="mobileMenu" class="mobile-menu-panel lg:hidden max-w-7xl mx-auto">
        <div class="mobile-menu-inner">
            <div class="mt-3 p-3.5 rounded-2xl bg-slate-900/95 backdrop-blur-xl shadow-2xl space-y-1">
                <a href="#top" data-mobile-nav-link="#top" onclick="closeMobileMenu()"
                   class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-bold text-emerald-400 bg-emerald-500/10 transition-colors">
                    <span class="flex items-center gap-2.5">
                        <span class="material-icons-outlined" style="font-size: 18px;">home</span>
                        Home
                    </span>
                    <span class="material-icons-outlined" style="font-size: 18px;">chevron_right</span>
                </a>
                <a href="#about-us" data-mobile-nav-link="#about-us" onclick="closeMobileMenu()"
                   class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/10 transition-colors">
                    <span class="flex items-center gap-2.5">
                        <span class="material-icons-outlined text-slate-400" style="font-size: 18px;">info</span>
                        About MEDLINK
                    </span>
                    <span class="material-icons-outlined text-slate-400" style="font-size: 18px;">chevron_right</span>
                </a>
                <a href="#services" data-mobile-nav-link="#services" onclick="closeMobileMenu()"
                   class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/10 transition-colors">
                    <span class="flex items-center gap-2.5">
                        <span class="material-icons-outlined text-slate-400" style="font-size: 18px;">medical_services</span>
                        Services
                    </span>
                    <span class="material-icons-outlined text-slate-400" style="font-size: 18px;">chevron_right</span>
                </a>
                <a href="#gallery" data-mobile-nav-link="#gallery" onclick="closeMobileMenu()"
                   class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/10 transition-colors">
                    <span class="flex items-center gap-2.5">
                        <span class="material-icons-outlined text-slate-400" style="font-size: 18px;">photo_library</span>
                        Health Center
                    </span>
                    <span class="material-icons-outlined text-slate-400" style="font-size: 18px;">chevron_right</span>
                </a>
                <a href="#faq" data-mobile-nav-link="#faq" onclick="closeMobileMenu()"
                   class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/10 transition-colors">
                    <span class="flex items-center gap-2.5">
                        <span class="material-icons-outlined text-slate-400" style="font-size: 18px;">help_outline</span>
                        FAQ & Contact
                    </span>
                    <span class="material-icons-outlined text-slate-400" style="font-size: 18px;">chevron_right</span>
                </a>
                <a href="#developers" data-mobile-nav-link="#developers" onclick="closeMobileMenu()"
                   class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/10 transition-colors">
                    <span class="flex items-center gap-2.5">
                        <span class="material-icons-outlined text-slate-400" style="font-size: 18px;">code</span>
                        Developers
                    </span>
                    <span class="material-icons-outlined text-slate-400" style="font-size: 18px;">chevron_right</span>
                </a>

                <div class="pt-3 mt-2 grid grid-cols-2 gap-2.5">
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="flex items-center justify-center gap-1.5 py-3 rounded-xl text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 transition-colors">
                            <span class="material-icons-outlined" style="font-size: 16px;">login</span>
                            Sign In
                        </a>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="flex items-center justify-center gap-1.5 py-3 rounded-xl text-xs font-bold text-white bg-white/10 hover:bg-white/15 transition-colors">
                            <span class="material-icons-outlined text-emerald-300" style="font-size: 16px;">person_add</span>
                            Register
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>

{{-- ══════════════════════════════════════════════════════════════
     HERO SECTION (Full-Fit Cinematic & Mobile-Optimized)
══════════════════════════════════════════════════════════════ --}}
<section id="top" class="hero-surface relative w-full min-h-screen min-h-[100dvh] flex flex-col justify-between fluid-x pt-28 pb-8 sm:pt-32 sm:pb-10">
    <div class="max-w-7xl mx-auto w-full my-auto py-4 sm:py-6">
        <div class="max-w-4xl">
            {{-- Location Pill --}}
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full hero-glass text-emerald-200 text-[11px] sm:text-xs font-semibold mb-5 sm:mb-6 max-w-full">
                <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0 animate-pulse"></span>
                <span class="truncate">Brgy. Honrado &bull; San Francisco, Surigao del Norte</span>
            </div>

            {{-- Headline --}}
            <h1 class="font-display fluid-hero-title font-black text-white tracking-tight leading-[1.08] mb-4 sm:mb-5 drop-shadow-[0_6px_24px_rgba(0,0,0,0.35)]">
                CHECK. REQUEST. CONNECT.<br>
                <span class="bg-gradient-to-r from-emerald-300 via-emerald-400 to-teal-300 bg-clip-text text-transparent">The MEDLINK Way.</span>
            </h1>

            {{-- Description --}}
            <p class="text-slate-100/90 fluid-hero-lead font-normal leading-relaxed max-w-2xl mb-7 sm:mb-8">
                Everything you need to know, every request you need to make, and every update you want to track. MEDLINK brings Barangay Honrado's health services together in one convenient place.
            </p>

            {{-- CTA Buttons --}}
            <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3 sm:gap-3.5 mb-7 sm:mb-8">
                @if (Route::has('login'))
                    <a href="{{ route('login') }}"
                       class="group inline-flex items-center justify-center gap-2.5 px-7 sm:px-8 py-3.5 sm:py-4 rounded-2xl text-sm sm:text-base font-bold text-slate-950 bg-gradient-to-r from-emerald-400 to-teal-300 hover:from-emerald-300 hover:to-teal-200 hover:-translate-y-0.5 active:scale-[0.99] transition-all shadow-[0_14px_34px_-8px_rgba(52,211,153,0.55)]">
                        <span>Sign In to MEDLINK</span>
                        <span class="material-icons-outlined transition-transform group-hover:translate-x-0.5" style="font-size: 20px;">login</span>
                    </a>
                @endif
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center justify-center gap-2 px-6 sm:px-7 py-3.5 sm:py-4 rounded-2xl text-sm sm:text-base font-bold text-white hero-glass hover:bg-white/16 hover:-translate-y-0.5 active:scale-[0.99] transition-all">
                        <span class="material-icons-outlined text-emerald-300" style="font-size: 20px;">person_add</span>
                        <span>Create Account</span>
                    </a>
                @endif
            </div>

            {{-- Trust Note --}}
            <div class="flex items-start sm:items-center gap-2 text-xs text-slate-200/80 font-medium max-w-xl">
                <span class="material-icons-outlined text-emerald-400 shrink-0 mt-0.5 sm:mt-0" style="font-size: 16px;">verified</span>
                <span>Managed by Barangay Honrado Health Center. Medicine requests are subject to staff review and approval.</span>
            </div>
        </div>
    </div>

    {{-- Scroll Cue --}}
    <div class="max-w-7xl mx-auto w-full pt-4 flex justify-end">
        <a href="#about-us"
           class="hidden lg:inline-flex items-center gap-2 px-4 py-2.5 rounded-full hero-glass text-xs font-semibold text-slate-200 hover:text-emerald-300 hover:bg-white/14 transition-all shrink-0">
            <span>Explore MEDLINK</span>
            <span class="material-icons-outlined animate-bounce" style="font-size: 16px;">arrow_downward</span>
        </a>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     MAIN LAYERED CARD CANVAS (01 → 09, Borderless & Phone-Ready)
══════════════════════════════════════════════════════════════ --}}
<main class="w-full bg-[#f3f7f5] py-8 sm:py-12 lg:py-16 fluid-x">
    <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">

        {{-- ── 01 / ABOUT MEDLINK ────────────────────────────────── --}}
        <section id="about-us" class="reveal bg-white rounded-3xl sm:rounded-[2rem] p-6 sm:p-9 lg:p-11 panel-shadow">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-7 lg:gap-10 items-center">
                <div class="lg:col-span-6">
                    <p class="text-[11px] sm:text-xs font-extrabold uppercase tracking-[0.14em] text-emerald-700">
                        01 / ABOUT MEDLINK
                    </p>
                    <h2 class="font-display fluid-section-title font-extrabold text-[#0c2d20] mt-2.5 tracking-tight leading-tight">
                        Connecting Barangay Health Services
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base mt-3.5 leading-relaxed">
                        A digital platform designed for Barangay Honrado residents to access medicine information, coordinate home health visits, and track health service requests in real time.
                    </p>
                    <div class="mt-6 flex flex-wrap items-center gap-4">
                        <a href="#services"
                           class="group inline-flex items-center gap-2 text-sm font-bold text-emerald-700 hover:text-emerald-800 transition-colors">
                            <span>Explore Services</span>
                            <span class="material-icons-outlined transition-transform group-hover:translate-x-1" style="font-size: 18px;">arrow_forward</span>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-6">
                    <div class="rounded-2xl sm:rounded-3xl bg-[#eaf3ee] p-7 sm:p-10 flex flex-col items-center justify-center text-center min-h-[210px] sm:min-h-[240px]">
                        <div class="w-14 h-14 rounded-2xl bg-white text-emerald-700 flex items-center justify-center shadow-sm mb-4">
                            <span class="material-icons-outlined" style="font-size: 30px;">groups</span>
                        </div>
                        <p class="font-display font-bold text-[#0c2d20] text-base sm:text-lg">
                            Community + Digital Healthcare
                        </p>
                        <p class="text-xs sm:text-sm text-slate-600 mt-1.5 max-w-sm">
                            Built for residents, Barangay Health Workers, and nurses in San Francisco, Surigao del Norte.
                        </p>
                        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                            <span class="px-3 py-1 rounded-full bg-white/90 text-[#0c2d20] text-[11px] font-semibold shadow-xs">Real-Time Stock</span>
                            <span class="px-3 py-1 rounded-full bg-white/90 text-[#0c2d20] text-[11px] font-semibold shadow-xs">Nurse Reviewed</span>
                            <span class="px-3 py-1 rounded-full bg-white/90 text-[#0c2d20] text-[11px] font-semibold shadow-xs">Mobile Ready</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── 02 / SERVICES ─────────────────────────────────────── --}}
        <section id="services" class="reveal bg-[#e9f3ee] rounded-3xl sm:rounded-[2rem] p-6 sm:p-9 lg:p-11 panel-shadow">
            <div class="mb-6 sm:mb-8">
                <p class="text-[11px] sm:text-xs font-extrabold uppercase tracking-[0.14em] text-emerald-700">
                    02 / SERVICES
                </p>
                <h2 class="font-display fluid-section-title font-extrabold text-[#0c2d20] mt-2 tracking-tight leading-tight">
                    What You Can Do with MEDLINK
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                @php
                    $services = [
                        ['icon' => 'medication', 'title' => 'Medicine Availability', 'desc' => 'Browse live barangay pharmacy stock and available doses before heading to the center.'],
                        ['icon' => 'assignment', 'title' => 'Medicine Requests', 'desc' => 'Submit online medicine requests for center pickup or home delivery with nurse review.'],
                        ['icon' => 'medical_services', 'title' => 'Health Services', 'desc' => 'Access consultations, immunization days, prenatal care, and barangay health programs.'],
                        ['icon' => 'calendar_month', 'title' => 'Consultation Schedules', 'desc' => 'View upcoming clinic hours, vaccination dates, and prenatal ultrasound slots.'],
                        ['icon' => 'fact_check', 'title' => 'Request Tracking', 'desc' => 'Track every request from submission to approval and release in real time.'],
                        ['icon' => 'health_and_safety', 'title' => 'Home Care Coordination', 'desc' => 'Priority BHW home health visits and medicine delivery for seniors and PWDs.'],
                    ];
                @endphp

                @foreach ($services as $service)
                    <div class="group bg-white rounded-2xl p-5 sm:p-6 card-shadow transition-all duration-200 hover:-translate-y-1 flex flex-col">
                        <div class="w-10 h-10 rounded-xl bg-[#edf6f1] text-emerald-700 flex items-center justify-center mb-4 shrink-0 group-hover:bg-emerald-700 group-hover:text-white transition-colors">
                            <span class="material-icons-outlined" style="font-size: 21px;">{{ $service['icon'] }}</span>
                        </div>
                        <h3 class="font-display font-bold text-[#0c2d20] text-base">{{ $service['title'] }}</h3>
                        <p class="text-xs sm:text-[13px] text-slate-500 mt-1.5 leading-relaxed flex-1">{{ $service['desc'] }}</p>
                        <div class="mt-4 h-1 w-14 rounded-full bg-[#e3efe8] overflow-hidden">
                            <div class="h-full w-1/2 bg-emerald-600/60 rounded-full transition-all duration-300 group-hover:w-full group-hover:bg-emerald-600"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ── 03 / HOW IT WORKS ─────────────────────────────────── --}}
        <section id="how-it-works" class="reveal bg-white rounded-3xl sm:rounded-[2rem] p-6 sm:p-9 lg:p-11 panel-shadow">
            <div class="mb-7 sm:mb-10">
                <p class="text-[11px] sm:text-xs font-extrabold uppercase tracking-[0.14em] text-emerald-700">
                    03 / HOW IT WORKS
                </p>
                <h2 class="font-display fluid-section-title font-extrabold text-[#0c2d20] mt-2 tracking-tight leading-tight">
                    Five Steps to Get Started
                </h2>
            </div>

            @php
                $steps = [
                    ['num' => '1', 'title' => 'Sign Up', 'desc' => 'Create your verified resident account'],
                    ['num' => '2', 'title' => 'Explore', 'desc' => 'Check stock & clinic services'],
                    ['num' => '3', 'title' => 'Request', 'desc' => 'Submit medicine or visit request'],
                    ['num' => '4', 'title' => 'Review', 'desc' => 'Nurse verifies & approves request'],
                    ['num' => '5', 'title' => 'Track', 'desc' => 'Follow live pickup or delivery'],
                ];
            @endphp

            <div class="relative">
                {{-- Subtle horizontal connector on lg+ --}}
                <div class="hidden lg:block absolute top-6 left-[10%] right-[10%] h-0.5 bg-[#e5f0ea]" aria-hidden="true"></div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-5 sm:gap-6 relative">
                    @foreach ($steps as $index => $step)
                        <div class="flex flex-col items-center text-center group {{ $index === 4 ? 'col-span-2 sm:col-span-1' : '' }}">
                            <div class="w-12 h-12 rounded-full bg-emerald-700 text-white font-display font-extrabold text-base flex items-center justify-center shadow-md group-hover:scale-105 group-hover:bg-emerald-600 transition-all">
                                {{ $step['num'] }}
                            </div>
                            <h3 class="font-display font-bold text-[#0c2d20] text-sm sm:text-base mt-3.5">{{ $step['title'] }}</h3>
                            <p class="text-[11px] sm:text-xs text-slate-500 mt-1 max-w-[160px] leading-relaxed">{{ $step['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ── 04 / MEDICINE INFORMATION ─────────────────────────── --}}
        <section id="medicine-info" class="reveal bg-[#e9f3ee] rounded-3xl sm:rounded-[2rem] p-6 sm:p-9 lg:p-11 panel-shadow">
            <div class="mb-6 sm:mb-8">
                <p class="text-[11px] sm:text-xs font-extrabold uppercase tracking-[0.14em] text-emerald-700">
                    04 / MEDICINE INFORMATION
                </p>
                <h2 class="font-display fluid-section-title font-extrabold text-[#0c2d20] mt-2 tracking-tight leading-tight">
                    Know Before You Request
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                <div class="bg-white rounded-2xl p-6 sm:p-7 card-shadow transition-all duration-200 hover:-translate-y-0.5">
                    <div class="w-10 h-10 rounded-xl bg-[#edf6f1] text-emerald-700 flex items-center justify-center mb-4">
                        <span class="material-icons-outlined" style="font-size: 22px;">medication</span>
                    </div>
                    <h3 class="font-display font-bold text-[#0c2d20] text-base sm:text-lg">Medicine Information</h3>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1.5 leading-relaxed">
                        Availability, dosage information, and quantity limits are displayed transparently so every household gets fair and safe access.
                    </p>
                </div>

                <div class="bg-white rounded-2xl p-6 sm:p-7 card-shadow transition-all duration-200 hover:-translate-y-0.5">
                    <div class="w-10 h-10 rounded-xl bg-[#edf6f1] text-emerald-700 flex items-center justify-center mb-4">
                        <span class="material-icons-outlined" style="font-size: 22px;">verified_user</span>
                    </div>
                    <h3 class="font-display font-bold text-[#0c2d20] text-base sm:text-lg">Nurse Approval</h3>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1.5 leading-relaxed">
                        Requests are reviewed before medicine release by our accredited barangay nurse to ensure safe dispensing and accurate records.
                    </p>
                </div>
            </div>
        </section>

        {{-- ── 05 / HEALTH CENTER ────────────────────────────────── --}}
        <section id="gallery" class="reveal bg-white rounded-3xl sm:rounded-[2rem] p-6 sm:p-9 lg:p-11 panel-shadow">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6 sm:mb-8">
                <div>
                    <p class="text-[11px] sm:text-xs font-extrabold uppercase tracking-[0.14em] text-emerald-700">
                        05 / HEALTH CENTER
                    </p>
                    <h2 class="font-display fluid-section-title font-extrabold text-[#0c2d20] mt-2 tracking-tight leading-tight">
                        Barangay Honrado Health Center
                    </h2>
                </div>
                <button type="button" onclick="openGalleryLightbox(0)"
                        class="self-start sm:self-auto inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#edf5f1] hover:bg-emerald-700 text-emerald-800 hover:text-white text-xs font-bold transition-colors cursor-pointer">
                    <span class="material-icons-outlined" style="font-size: 16px;">photo_library</span>
                    <span>View All 4 Photos</span>
                </button>
            </div>

            @php
                $centerPhotos = [
                    ['index' => 0, 'src' => asset('images/hc-exterior.jpg'), 'label' => 'Exterior Photo', 'title' => 'Barangay Health Center Frontage'],
                    ['index' => 1, 'src' => asset('images/hc-interior.jpg'), 'label' => 'Interior Photo', 'title' => 'BHW Records & Coordination Desk'],
                    ['index' => 2, 'src' => asset('images/hc-service.jpg'), 'label' => 'Service Area', 'title' => 'Nurse Consultation & Clinic Area'],
                ];
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                @foreach ($centerPhotos as $photo)
                    <article role="button" tabindex="0"
                             onclick="openGalleryLightbox({{ $photo['index'] }})"
                             onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openGalleryLightbox({{ $photo['index'] }});}"
                             class="group relative aspect-[16/11] rounded-2xl overflow-hidden bg-[#e8efeb] cursor-pointer card-shadow focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600">
                        <img src="{{ $photo['src'] }}" alt="{{ $photo['title'] }}" loading="lazy"
                             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/75 via-slate-950/15 to-transparent"></div>
                        <span class="absolute top-3 left-3 px-3 py-1 rounded-full bg-slate-950/65 backdrop-blur-md text-white text-[11px] font-semibold">
                            {{ $photo['label'] }}
                        </span>
                        <div class="absolute bottom-3.5 left-4 right-4 flex items-end justify-between gap-2">
                            <p class="font-display font-bold text-white text-sm leading-snug">{{ $photo['title'] }}</p>
                            <span class="w-8 h-8 rounded-full bg-white/20 backdrop-blur-md text-white flex items-center justify-center shrink-0 group-hover:bg-emerald-500 transition-colors">
                                <span class="material-icons-outlined" style="font-size: 16px;">open_in_full</span>
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-2 text-xs sm:text-[13px] font-medium text-slate-600">
                <span class="inline-flex items-center gap-1.5">
                    <span class="material-icons-outlined text-emerald-700" style="font-size: 16px;">location_on</span>
                    Brgy. Honrado, San Francisco, Surigao del Norte
                </span>
                <span class="text-slate-300 hidden sm:inline">&bull;</span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="material-icons-outlined text-emerald-700" style="font-size: 16px;">schedule</span>
                    Mon – Fri, 8:00 AM – 5:00 PM
                </span>
                <span class="text-slate-300 hidden sm:inline">&bull;</span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="material-icons-outlined text-emerald-700" style="font-size: 16px;">call</span>
                    Official Health Center Desk
                </span>
            </div>
        </section>

        {{-- ── 06 / ANNOUNCEMENTS ────────────────────────────────── --}}
        <section id="announcements" class="reveal bg-[#e9f3ee] rounded-3xl sm:rounded-[2rem] p-6 sm:p-9 lg:p-11 panel-shadow">
            <div class="mb-6 sm:mb-8">
                <p class="text-[11px] sm:text-xs font-extrabold uppercase tracking-[0.14em] text-emerald-700">
                    06 / ANNOUNCEMENTS
                </p>
                <h2 class="font-display fluid-section-title font-extrabold text-[#0c2d20] mt-2 tracking-tight leading-tight">
                    Latest Health Center Updates
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                <div class="bg-white rounded-2xl p-6 sm:p-7 card-shadow flex flex-col">
                    <div class="w-10 h-10 rounded-xl bg-[#edf6f1] text-emerald-700 flex items-center justify-center mb-4">
                        <span class="material-icons-outlined" style="font-size: 21px;">campaign</span>
                    </div>
                    <h3 class="font-display font-bold text-[#0c2d20] text-base sm:text-lg">Schedule Updates</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1.5 leading-relaxed">
                        Weekly child immunization sessions, prenatal checkups, and senior citizen maintenance refill schedules are posted live inside your resident portal.
                    </p>
                    <div class="mt-5 space-y-2.5">
                        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-xl bg-[#f4f8f6] text-xs">
                            <span class="font-semibold text-[#0c2d20]">Child & Infant Immunization</span>
                            <span class="text-emerald-700 font-bold shrink-0">Weekly</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-xl bg-[#f4f8f6] text-xs">
                            <span class="font-semibold text-[#0c2d20]">Prenatal & Maternal Care</span>
                            <span class="text-emerald-700 font-bold shrink-0">By Schedule</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 sm:p-7 card-shadow flex flex-col">
                    <div class="w-10 h-10 rounded-xl bg-[#edf6f1] text-emerald-700 flex items-center justify-center mb-4">
                        <span class="material-icons-outlined" style="font-size: 21px;">notifications_active</span>
                    </div>
                    <h3 class="font-display font-bold text-[#0c2d20] text-base sm:text-lg">Health Announcements</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1.5 leading-relaxed">
                        Official barangay health advisories, seasonal disease surveillance alerts, and community wellness campaigns directly from our nurses and BHWs.
                    </p>
                    <div class="mt-5 space-y-2.5">
                        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-xl bg-[#f4f8f6] text-xs">
                            <span class="font-semibold text-[#0c2d20]">Dengue & Community Surveillance</span>
                            <span class="text-emerald-700 font-bold shrink-0">Active</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-xl bg-[#f4f8f6] text-xs">
                            <span class="font-semibold text-[#0c2d20]">Home Visits for Seniors & PWDs</span>
                            <span class="text-emerald-700 font-bold shrink-0">Available</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── 07 / FREQUENTLY ASKED QUESTIONS ───────────────────── --}}
        <section id="faq" class="reveal bg-white rounded-3xl sm:rounded-[2rem] p-6 sm:p-9 lg:p-11 panel-shadow">
            <div class="mb-6 sm:mb-8">
                <p class="text-[11px] sm:text-xs font-extrabold uppercase tracking-[0.14em] text-emerald-700">
                    07 / FREQUENTLY ASKED QUESTIONS
                </p>
                <h2 class="font-display fluid-section-title font-extrabold text-[#0c2d20] mt-2 tracking-tight leading-tight">
                    Questions? We've Got Answers.
                </h2>
            </div>

            @php
                $faqs = [
                    [
                        'q' => 'How do I request medicines?',
                        'a' => 'Sign in to your MEDLINK resident account, browse the live pharmacy inventory, select the medicine you need, choose pickup or home delivery, and submit your request in seconds.'
                    ],
                    [
                        'q' => 'Are medicine requests approved automatically?',
                        'a' => 'No. Every medicine request is carefully reviewed by our Barangay Honrado Health Center nurse to verify stock, safe dosage, and patient eligibility before release.'
                    ],
                    [
                        'q' => 'How can I track my request?',
                        'a' => 'Once submitted, you can monitor your request status in real time right from your resident dashboard — from Pending Review to Approved, Ready for Pickup, or Out for Home Delivery.'
                    ],
                ];
            @endphp

            <div class="space-y-3">
                @foreach ($faqs as $idx => $faq)
                    <div class="faq-item rounded-2xl bg-[#f4f8f6] transition-colors hover:bg-[#edf5f1]">
                        <button type="button" onclick="toggleFaq(this)" aria-expanded="false"
                                class="w-full flex items-center justify-between gap-4 px-5 sm:px-6 py-4 sm:py-5 text-left cursor-pointer min-h-[56px]">
                            <span class="font-display font-semibold text-[#0c2d20] text-sm sm:text-base">{{ $faq['q'] }}</span>
                            <span class="faq-plus w-8 h-8 rounded-full bg-white text-emerald-700 flex items-center justify-center shrink-0 shadow-xs">
                                <span class="material-icons-outlined" style="font-size: 18px;">add</span>
                            </span>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner">
                                <p class="px-5 sm:px-6 pb-5 text-xs sm:text-sm text-slate-600 leading-relaxed">
                                    {{ $faq['a'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ── 08 / CONTACT & LOCATION ───────────────────────────── --}}
        <section id="contact" class="reveal bg-[#e9f3ee] rounded-3xl sm:rounded-[2rem] p-6 sm:p-9 lg:p-11 panel-shadow">
            <div class="mb-6 sm:mb-8">
                <p class="text-[11px] sm:text-xs font-extrabold uppercase tracking-[0.14em] text-emerald-700">
                    08 / CONTACT & LOCATION
                </p>
                <h2 class="font-display fluid-section-title font-extrabold text-[#0c2d20] mt-2 tracking-tight leading-tight">
                    Find Us. Stay Connected.
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 items-stretch">
                <div class="bg-white rounded-2xl p-6 sm:p-7 card-shadow flex flex-col justify-between gap-5">
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="w-9 h-9 rounded-xl bg-[#edf6f1] text-emerald-700 flex items-center justify-center shrink-0">
                                <span class="material-icons-outlined" style="font-size: 19px;">location_on</span>
                            </span>
                            <div>
                                <p class="font-display font-bold text-[#0c2d20] text-sm sm:text-base">Barangay Honrado</p>
                                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">San Francisco, Surigao del Norte, Philippines</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-9 h-9 rounded-xl bg-[#edf6f1] text-emerald-700 flex items-center justify-center shrink-0">
                                <span class="material-icons-outlined" style="font-size: 19px;">support_agent</span>
                            </span>
                            <div>
                                <p class="font-display font-bold text-[#0c2d20] text-sm sm:text-base">Official Contact</p>
                                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Barangay Honrado Health Center Front Desk &amp; BHW Station</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-9 h-9 rounded-xl bg-[#edf6f1] text-emerald-700 flex items-center justify-center shrink-0">
                                <span class="material-icons-outlined" style="font-size: 19px;">schedule</span>
                            </span>
                            <div>
                                <p class="font-display font-bold text-[#0c2d20] text-sm sm:text-base">Operating Hours</p>
                                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Monday – Friday &bull; 8:00 AM – 5:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="https://www.google.com/maps/search/?api=1&query=Barangay+Honrado+San+Francisco+Surigao+del+Norte"
                   target="_blank" rel="noopener noreferrer"
                   class="group rounded-2xl bg-[#dcebe3] hover:bg-[#d2e6dc] p-7 sm:p-9 flex flex-col items-center justify-center text-center min-h-[210px] transition-colors">
                    <div class="w-13 h-13 rounded-2xl bg-white text-emerald-700 flex items-center justify-center shadow-sm mb-3.5 group-hover:scale-105 transition-transform">
                        <span class="material-icons-outlined" style="font-size: 26px;">map</span>
                    </div>
                    <p class="font-display font-bold text-[#0c2d20] text-base">Verified Map</p>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1 max-w-xs">
                        Barangay Honrado, San Francisco, Surigao del Norte
                    </p>
                    <span class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-white text-emerald-800 text-xs font-bold shadow-xs group-hover:bg-emerald-700 group-hover:text-white transition-colors">
                        <span>Open in Google Maps</span>
                        <span class="material-icons-outlined" style="font-size: 15px;">open_in_new</span>
                    </span>
                </a>
            </div>
        </section>

        {{-- ── 09 / DEVELOPERS (The Team Techyknowloadeia + 4 Members, Borderless) ── --}}
        <section id="developers" class="reveal bg-white rounded-3xl sm:rounded-[2rem] p-6 sm:p-9 lg:p-11 panel-shadow">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-7 sm:mb-9">
                <div>
                    <p class="text-[11px] sm:text-xs font-extrabold uppercase tracking-[0.14em] text-emerald-700">
                        09 / DEVELOPERS
                    </p>
                    <h2 class="font-display fluid-section-title font-extrabold text-[#0c2d20] mt-2 tracking-tight leading-tight">
                        Meet the Builders of MEDLINK
                    </h2>
                    <p class="text-slate-500 text-xs sm:text-sm mt-2 max-w-xl leading-relaxed">
                        The dedicated team behind Barangay Honrado's digital health platform.
                    </p>
                </div>
            </div>

            {{-- Featured 5th Image: Bigger Aesthetic Team Showcase --}}
            <article role="button" tabindex="0"
                     onclick="openPhotoModal('{{ asset('images/team-techyknowloadeia.jpg') }}', 'Team Techyknowloadeia')"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openPhotoModal('{{ asset('images/team-techyknowloadeia.jpg') }}', 'Team Techyknowloadeia');}"
                     class="group mb-7 sm:mb-9 rounded-3xl overflow-hidden bg-[#071d15] dev-shadow transition-all duration-300 hover:-translate-y-1 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600">
                <div class="grid grid-cols-1 lg:grid-cols-12 items-stretch">
                    {{-- Large Crisp Studio Team Photo --}}
                    <div class="lg:col-span-7 relative aspect-square sm:aspect-[4/3] lg:aspect-auto lg:min-h-[420px] overflow-hidden bg-[#eef2f5]">
                        <img src="{{ asset('images/team-techyknowloadeia.jpg') }}"
                             alt="Team Techyknowloadeia"
                             loading="lazy"
                             class="w-full h-full object-cover object-top transition-transform duration-700 group-hover:scale-[1.03]">
                        <span class="absolute top-4 left-4 inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-slate-950/65 backdrop-blur-md text-emerald-300 text-[11px] font-bold tracking-wide">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            TEAM
                        </span>
                        <span class="absolute bottom-4 right-4 w-10 h-10 rounded-full bg-slate-950/55 backdrop-blur-md text-white flex items-center justify-center group-hover:bg-emerald-500 transition-colors">
                            <span class="material-icons-outlined" style="font-size: 18px;">open_in_full</span>
                        </span>
                    </div>

                    {{-- Aesthetic Editorial Team Panel --}}
                    <div class="lg:col-span-5 relative p-6 sm:p-8 lg:p-10 flex flex-col justify-center overflow-hidden"
                         style="background: radial-gradient(circle at 85% 15%, rgba(16, 185, 129, 0.24), transparent 52%), linear-gradient(150deg, #09241a 0%, #051610 100%);">
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-emerald-300/90">
                            Development Group
                        </p>
                        <h3 class="font-display font-black text-white text-2xl sm:text-3xl lg:text-[2rem] tracking-tight leading-[1.12] mt-2.5">
                            Team<br>
                            <span class="bg-gradient-to-r from-emerald-300 via-emerald-400 to-teal-300 bg-clip-text text-transparent">
                                Techyknowloadeia
                            </span>
                        </h3>
                        <p class="text-slate-300/90 text-xs sm:text-sm leading-relaxed mt-4">
                            United by a mission to bridge community healthcare and modern technology, we designed and engineered MEDLINK to make Barangay Honrado Health Center faster, transparent, and accessible from any phone.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-2">
                            <span class="px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md text-emerald-200 text-[11px] font-semibold">
                                4 Core Members
                            </span>
                            <span class="px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md text-slate-200 text-[11px] font-semibold">
                                MEDLINK Creators
                            </span>
                            <span class="px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md text-slate-200 text-[11px] font-semibold">
                                Barangay Honrado
                            </span>
                        </div>
                    </div>
                </div>
            </article>

            {{-- Individual Developer Cards (4 Members) --}}
            @php
                $developers = [
                    [
                        'name' => 'Louise Vera M. Piape',
                        'role' => 'Project Manager',
                        'photo' => asset('images/dev1.jpg'),
                    ],
                    [
                        'name' => 'Kian P. Nogaliza',
                        'role' => 'Lead System Developer',
                        'photo' => asset('images/dev2.jpg'),
                    ],
                    [
                        'name' => 'Charbel Brient Gumalang',
                        'role' => 'System Analyst and Documentation Specialist',
                        'photo' => asset('images/dev3.jpg'),
                    ],
                    [
                        'name' => 'Clark Ian A. Caturan',
                        'role' => 'Quality Assurance and Technical Documentation Assistant',
                        'photo' => asset('images/dev4.jpg'),
                    ],
                ];
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                @foreach ($developers as $dev)
                    <article role="button" tabindex="0"
                             onclick="openPhotoModal('{{ $dev['photo'] }}', '{{ $dev['name'] }} — {{ $dev['role'] }}')"
                             onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openPhotoModal('{{ $dev['photo'] }}', '{{ $dev['name'] }} — {{ $dev['role'] }}');}"
                             class="group flex flex-col h-full rounded-3xl overflow-hidden bg-[#f5f9f7] card-shadow transition-all duration-300 hover:-translate-y-1.5 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600">
                        {{-- Clean Studio Portrait --}}
                        <div class="relative aspect-square w-full overflow-hidden bg-[#eef3f1]">
                            <img src="{{ $dev['photo'] }}"
                                 alt="{{ $dev['name'] }} — {{ $dev['role'] }}"
                                 loading="lazy"
                                 class="w-full h-full object-cover object-top transition-transform duration-500 group-hover:scale-105">
                            <div class="absolute inset-x-0 bottom-0 h-14 bg-gradient-to-t from-black/15 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        </div>

                        {{-- Name & Role (Clean, Full Text, No Color Border) --}}
                        <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between bg-[#f5f9f7] group-hover:bg-[#edf5f1] transition-colors">
                            <div>
                                <h3 class="font-display font-extrabold text-[#0c2d20] text-sm sm:text-[15px] leading-snug">
                                    {{ $dev['name'] }}
                                </h3>
                                <p class="text-xs font-medium text-emerald-700 mt-1.5 leading-relaxed">
                                    {{ $dev['role'] }}
                                </p>
                            </div>
                            <div class="mt-4 h-1 w-10 rounded-full bg-[#dcebe3] overflow-hidden">
                                <div class="h-full w-1/2 bg-emerald-600/70 rounded-full transition-all duration-300 group-hover:w-full group-hover:bg-emerald-600"></div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

    </div>
</main>

{{-- ══════════════════════════════════════════════════════════════
     CALL TO ACTION BANNER (Borderless)
══════════════════════════════════════════════════════════════ --}}
<section class="w-full bg-slate-950 py-12 sm:py-16 fluid-x">
    <div class="reveal max-w-5xl mx-auto rounded-3xl bg-gradient-to-br from-[#0a2319] via-slate-900 to-slate-950 text-center shadow-2xl" style="padding: clamp(1.75rem, 4vw, 3.5rem);">
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
                   class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-2xl text-sm font-bold text-white bg-white/10 hover:bg-white/20 transition-colors">
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
<footer class="w-full bg-slate-950 text-slate-400 py-8 fluid-x text-xs">
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
     FLOATING BACK-TO-TOP BUTTON (Phone & Desktop Friendly)
══════════════════════════════════════════════════════════════ --}}
<button type="button" id="backToTopBtn" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })" aria-label="Back to top"
        class="fixed bottom-5 right-5 z-40 w-11 h-11 rounded-full bg-emerald-700 hover:bg-emerald-600 text-white shadow-xl flex items-center justify-center opacity-0 pointer-events-none translate-y-3 transition-all duration-300 cursor-pointer">
    <span class="material-icons-outlined" style="font-size: 20px;">arrow_upward</span>
</button>

{{-- ══════════════════════════════════════════════════════════════
     GALLERY LIGHTBOX MODAL (Adaptive + Prev/Next + Swipe)
══════════════════════════════════════════════════════════════ --}}
<div id="galleryLightbox" role="dialog" aria-modal="true" aria-label="Gallery Photo Viewer"
     onclick="closeGalleryLightbox()"
     class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/95 backdrop-blur-sm p-3 sm:p-6">
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
             class="max-h-[75dvh] max-w-[92vw] w-auto rounded-2xl shadow-2xl object-contain select-none">
        <div class="flex items-center justify-between sm:justify-center gap-4 w-full max-w-md px-2">
            <button type="button" onclick="navigateGallery(-1)" aria-label="Previous photo"
                    class="sm:hidden w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 text-white flex items-center justify-center shrink-0 cursor-pointer">
                <span class="material-icons-outlined" style="font-size: 20px;">chevron_left</span>
            </button>
            <div class="text-center min-w-0">
                <p id="lightboxCaption" class="font-display text-sm sm:text-base font-bold text-white truncate"></p>
                <p id="lightboxCounter" class="text-[11px] text-slate-400 font-medium mt-0.5"></p>
            </div>
            <button type="button" onclick="navigateGallery(1)" aria-label="Next photo"
                    class="sm:hidden w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 text-white flex items-center justify-center shrink-0 cursor-pointer">
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
    const mainHeader = document.getElementById('mainHeader');
    const backToTopBtn = document.getElementById('backToTopBtn');
    function updateHeaderOnScroll() {
        const y = window.scrollY;
        if (mainHeader) mainHeader.classList.toggle('header-scrolled', y > 40);
        if (backToTopBtn) {
            const showTop = y > 520;
            backToTopBtn.classList.toggle('opacity-0', !showTop);
            backToTopBtn.classList.toggle('pointer-events-none', !showTop);
            backToTopBtn.classList.toggle('translate-y-3', !showTop);
        }
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

    // 3. Smooth FAQ Accordion
    function toggleFaq(btn) {
        const item = btn.closest('.faq-item');
        if (!item) return;
        const willOpen = !item.classList.contains('is-open');
        document.querySelectorAll('.faq-item.is-open').forEach((openItem) => {
            if (openItem !== item) {
                openItem.classList.remove('is-open');
                const openBtn = openItem.querySelector('button[aria-expanded]');
                if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
            }
        });
        item.classList.toggle('is-open', willOpen);
        btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }

    // 4. Dynamic Scroll-Spy for active nav link highlighting
    const sectionIds = ['top', 'about-us', 'services', 'gallery', 'faq', 'developers'];
    const desktopLinks = document.querySelectorAll('[data-nav-link]');
    const mobileLinks = document.querySelectorAll('[data-mobile-nav-link]');

    function setActiveNav(targetHash) {
        desktopLinks.forEach((link) => {
            const isActive = link.getAttribute('data-nav-link') === targetHash;
            link.classList.toggle('font-bold', isActive);
            link.classList.toggle('nav-pill-active', isActive);
            link.classList.toggle('font-medium', !isActive);
            link.classList.toggle('text-slate-200/90', !isActive);
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
            rootMargin: '-30% 0px -60% 0px',
            threshold: 0
        });

        sectionIds.forEach((id) => {
            const el = document.getElementById(id);
            if (el) navObserver.observe(el);
        });
    }

    // 5. Subtle Scroll-Reveal for sections
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
            threshold: 0.08,
            rootMargin: '0px 0px -30px 0px'
        });
        revealEls.forEach((el) => revealObserver.observe(el));
    } else {
        revealEls.forEach((el) => el.classList.add('is-visible'));
    }

    // 6. Dynamic Gallery Lightbox (Index-based + Prev/Next + Swipe)
    const galleryItems = [
        { src: '{{ asset('images/hc-exterior.jpg') }}', caption: 'Barangay Health Center Frontage' },
        { src: '{{ asset('images/hc-interior.jpg') }}', caption: 'BHW Records & Coordination Desk' },
        { src: '{{ asset('images/hc-service.jpg') }}', caption: 'Nurse Consultation & Clinic Area' },
        { src: '{{ asset('honrado.png') }}', caption: 'Barangay Honrado Health Center Compound' }
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

    function openPhotoModal(src, caption) {
        const lb = document.getElementById('galleryLightbox');
        const img = document.getElementById('lightboxImg');
        const cap = document.getElementById('lightboxCaption');
        const counter = document.getElementById('lightboxCounter');
        if (!lb || !img) return;
        img.src = src;
        img.alt = caption;
        if (cap) cap.textContent = caption;
        if (counter) counter.textContent = 'Team Techyknowloadeia';
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
