<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account — MEDLINK</title>

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
            <span class="hidden sm:inline">Back to Home</span>
            <span class="sm:hidden">Home</span>
        </a>
    </header>

    {{-- Main Register Card Container --}}
    <main class="flex-1 flex items-center justify-center px-3.5 sm:px-6 py-6 sm:py-12 relative overflow-hidden">
        <div class="w-full max-w-lg bg-white/95 backdrop-blur-xl border border-white/60 rounded-2xl sm:rounded-3xl shadow-2xl shadow-emerald-950/40 p-5 sm:p-9 relative z-10">
            {{-- Form Header --}}
            <div class="text-center mb-7">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-bold uppercase tracking-wider mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Resident Registration
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Create your account</h1>
                <p class="text-slate-500 text-xs sm:text-sm mt-1.5 font-medium">Join MEDLINK to access Barangay Honrado health services online</p>
            </div>

            {{-- Status Notification --}}
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
            <form action="{{ url('/register') }}" method="POST" class="space-y-5" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="role" value="resident">

                {{-- First and Last Name --}}
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">First Name</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </span>
                            <input type="text" name="first_name" id="first_name" required placeholder="Kian"
                                   value="{{ old('first_name') }}"
                                   class="block w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-base sm:text-sm placeholder-slate-400 font-medium
                                          focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                        </div>
                    </div>
                    <div>
                        <label for="last_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Last Name</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </span>
                            <input type="text" name="last_name" id="last_name" required placeholder="Nogaliza"
                                   value="{{ old('last_name') }}"
                                   class="block w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-base sm:text-sm placeholder-slate-400 font-medium
                                          focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                        </div>
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
                               placeholder="09XXXXXXXXX or +639XXXXXXXXX"
                               value="{{ old('phone_number') }}"
                               inputmode="tel"
                               autocomplete="tel"
                               data-ph-phone
                               class="block w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-base sm:text-sm placeholder-slate-400 font-medium
                                      focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                    </div>
                </div>

                {{-- Senior-Friendly Birthday Selector (Month / Day / Year + Auto Age) --}}
                <div class="rounded-2xl bg-emerald-50/55 p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <label class="flex items-center gap-1.5 text-xs font-extrabold text-slate-800 uppercase tracking-wider">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Birthday (Date of Birth)</span>
                        </label>
                        <span id="age-category-badge" class="hidden text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 transition-all"></span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        {{-- Birth Month --}}
                        <div>
                            <label for="birth_month" class="block text-[11px] font-bold text-slate-600 mb-1">Month</label>
                            <select id="birth_month" required onchange="syncBirthdayParts()"
                                    class="block w-full px-3.5 py-3 bg-white border border-slate-200 rounded-xl text-base sm:text-sm text-slate-800 font-semibold cursor-pointer
                                           focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                                <option value="">Select Month</option>
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <option value="04">April</option>
                                <option value="05">May</option>
                                <option value="06">June</option>
                                <option value="07">July</option>
                                <option value="08">August</option>
                                <option value="09">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>

                        {{-- Birth Day --}}
                        <div>
                            <label for="birth_day" class="block text-[11px] font-bold text-slate-600 mb-1">Day</label>
                            <select id="birth_day" required onchange="syncBirthdayParts()"
                                    class="block w-full px-3.5 py-3 bg-white border border-slate-200 rounded-xl text-base sm:text-sm text-slate-800 font-semibold cursor-pointer
                                           focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                                <option value="">Select Day</option>
                                @for ($d = 1; $d <= 31; $d++)
                                    <option value="{{ str_pad($d, 2, '0', STR_PAD_LEFT) }}">{{ $d }}</option>
                                @endfor
                            </select>
                        </div>

                        {{-- Birth Year --}}
                        <div>
                            <label for="birth_year" class="block text-[11px] font-bold text-slate-600 mb-1">Year</label>
                            <select id="birth_year" required onchange="syncBirthdayParts()"
                                    class="block w-full px-3.5 py-3 bg-white border border-slate-200 rounded-xl text-base sm:text-sm text-slate-800 font-semibold cursor-pointer
                                           focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                                <option value="">Select Year</option>
                                @for ($y = now()->year; $y >= 1900; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="dob" id="dob" value="{{ old('dob') }}">
                    <input type="hidden" name="age" id="age" value="{{ old('age') }}">

                    {{-- Clear Auto-Age Summary Banner --}}
                    <div id="auto-age-preview" class="hidden mt-3 px-4 py-2.5 rounded-xl bg-white shadow-xs flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 text-xs sm:text-sm text-slate-700">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                            <span>Your Computed Age:</span>
                        </div>
                        <span id="auto-age-number" class="font-display font-extrabold text-sm sm:text-base text-emerald-700"></span>
                    </div>
                </div>

                {{-- Gender & Patient Classification --}}
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="gender" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Gender</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </span>
                            <select name="gender" id="gender" required
                                    class="block w-full pl-10 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-base sm:text-sm text-slate-700 font-medium appearance-none cursor-pointer
                                           focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                                <option value="">Select Gender</option>
                                <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                                <option value="Other" {{ old('gender') === 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        @error('gender')
                            <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="patient_classification" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Patient Classification</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </span>
                            <select name="patient_classification" id="patient_classification" required
                                    class="block w-full pl-10 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-base sm:text-sm text-slate-700 font-medium appearance-none cursor-pointer
                                           focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                                <option value="Regular Patient" {{ old('patient_classification', 'Regular Patient') === 'Regular Patient' ? 'selected' : '' }}>Regular Patient</option>
                                <option value="PWD" {{ old('patient_classification') === 'PWD' ? 'selected' : '' }}>PWD (Person with Disability)</option>
                                <option value="Senior Citizen" {{ old('patient_classification') === 'Senior Citizen' ? 'selected' : '' }}>Senior Citizen</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Password --}}
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </span>
                            <input type="password" name="password" id="password" required placeholder="••••••••"
                                   class="block w-full pl-10 pr-12 py-3 bg-slate-50 border border-slate-200 rounded-xl text-base sm:text-sm placeholder-slate-400 font-medium
                                          focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                            <button type="button" onclick="togglePassword('password', 'eye-open-1', 'eye-close-1')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none" aria-label="Toggle Password">
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
                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Confirm Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </span>
                            <input type="password" name="password_confirmation" id="password_confirmation" required placeholder="••••••••"
                                   class="block w-full pl-10 pr-12 py-3 bg-slate-50 border border-slate-200 rounded-xl text-base sm:text-sm placeholder-slate-400 font-medium
                                          focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all">
                            <button type="button" onclick="togglePassword('password_confirmation', 'eye-open-2', 'eye-close-2')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none" aria-label="Toggle Password">
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
                </div>

                {{-- ID Photo Upload (Residents only) --}}
                <div id="id-photo-section">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Government ID Photo <span class="text-rose-500">*</span></label>
                    <div id="id-drop-zone"
                         class="relative border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50 hover:border-emerald-400 hover:bg-emerald-50/30 transition-all cursor-pointer group"
                         onclick="document.getElementById('id_photo').click()">
                        {{-- Preview image (hidden initially) --}}
                        <img id="id-preview" src="#" alt="ID Preview"
                             class="hidden w-full max-h-44 object-contain rounded-xl p-2">
                        {{-- Upload prompt --}}
                        <div id="id-upload-prompt" class="flex flex-col items-center justify-center py-7 px-4 text-center">
                            <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 flex items-center justify-center mb-2.5 text-slate-400 group-hover:text-emerald-600 group-hover:border-emerald-300 transition-colors shadow-xs">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 3h18M3 3v18"/>
                                </svg>
                            </div>
                            <p class="text-xs sm:text-sm font-bold text-slate-700">Click or drag to upload your ID</p>
                            <p class="text-[11px] text-slate-400 mt-1">National ID, Driver's License, Passport, etc. &bull; JPG, PNG &bull; max 5MB</p>
                        </div>
                        <input type="file" id="id_photo" name="id_photo" accept="image/*" required
                               class="sr-only"
                               onchange="previewIdPhoto(this)">
                    </div>
                    @error('id_photo')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Terms Checkbox --}}
                <div class="flex items-start">
                    <input type="checkbox" id="terms" name="terms" required
                           class="mt-1 w-4 h-4 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500 focus:ring-offset-0">
                    <label for="terms" id="terms-label" class="ml-2 text-xs sm:text-sm text-slate-600 font-medium select-none">
                        I agree to register with correct information as a resident of Barangay Honrado.
                    </label>
                </div>

                {{-- Submit Button --}}
                <button type="submit"
                        class="w-full flex justify-center items-center gap-2 py-3.5 px-4 rounded-xl text-sm font-bold text-white
                               bg-emerald-600 hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500
                               transition-all shadow-lg shadow-emerald-950/20 active:scale-[0.98]">
                    Register Account
                </button>
            </form>

            {{-- Login Option --}}
            <div class="mt-7 text-center border-t border-slate-100 pt-5">
                <p class="text-xs sm:text-sm text-slate-500 font-medium">
                    Already have an account? 
                    <a href="{{ route('login') }}" class="font-bold text-emerald-600 hover:text-emerald-700 hover:underline ml-1">Sign in instead</a>
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

    <script>
        function togglePassword(inputId, openIconId, closeIconId) {
            const input = document.getElementById(inputId);
            const eyeOpen = document.getElementById(openIconId);
            const eyeClose = document.getElementById(closeIconId);
            
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

        function previewIdPhoto(input) {
            const preview = document.getElementById('id-preview');
            const prompt  = document.getElementById('id-upload-prompt');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    prompt.classList.add('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function getAgeCategory(ageVal) {
            if (ageVal === '' || isNaN(ageVal)) return null;
            const age = parseInt(ageVal, 10);
            if (age < 0) return null;
            if (age >= 60) return { label: 'Senior citizen', color: 'bg-amber-100 text-amber-800 border-amber-300' };
            if (age >= 30) return { label: 'Adult', color: 'bg-emerald-100 text-emerald-800 border-emerald-300' };
            if (age >= 18) return { label: 'Young adult', color: 'bg-blue-100 text-blue-800 border-blue-300' };
            if (age >= 13) return { label: 'Teenager / Adolescent', color: 'bg-purple-100 text-purple-800 border-purple-300' };
            if (age >= 3)  return { label: 'Child', color: 'bg-cyan-100 text-cyan-800 border-cyan-300' };
            if (age >= 1)  return { label: 'Toddler', color: 'bg-yellow-100 text-yellow-800 border-yellow-300' };
            return { label: 'Infant', color: 'bg-pink-100 text-pink-800 border-pink-300' };
        }

        function calculateAgeFromDob(dobValue) {
            if (!dobValue) return null;
            const parts = dobValue.split('-').map(Number);
            if (parts.length !== 3 || parts.some(isNaN)) return null;
            const birthDate = new Date(parts[0], parts[1] - 1, parts[2]);
            const today = new Date();
            if (birthDate > today) return null;

            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            return age >= 0 && age <= 130 ? age : null;
        }

        function syncBirthdayParts() {
            const monthEl = document.getElementById('birth_month');
            const dayEl = document.getElementById('birth_day');
            const yearEl = document.getElementById('birth_year');
            const dobHidden = document.getElementById('dob');
            if (!monthEl || !dayEl || !yearEl || !dobHidden) return;

            const m = monthEl.value;
            const y = yearEl.value;

            // Clamp valid days for selected month & year (handles Feb 28/29 & 30-day months)
            if (m) {
                const refYear = y ? parseInt(y, 10) : 2024;
                const maxDays = new Date(refYear, parseInt(m, 10), 0).getDate();
                Array.from(dayEl.options).forEach((opt) => {
                    if (!opt.value) return;
                    const dayNum = parseInt(opt.value, 10);
                    opt.hidden = dayNum > maxDays;
                    opt.disabled = dayNum > maxDays;
                });
                if (dayEl.value && parseInt(dayEl.value, 10) > maxDays) {
                    dayEl.value = String(maxDays).padStart(2, '0');
                }
            }

            const d = dayEl.value;
            if (y && m && d) {
                const iso = `${y}-${m}-${d}`;
                dobHidden.value = iso;
                updateAgeFromBirthday(iso);
            } else {
                dobHidden.value = '';
                updateAgeFromBirthday('');
            }
        }

        function updateAgeFromBirthday(dobValue) {
            const ageHidden = document.getElementById('age');
            const preview = document.getElementById('auto-age-preview');
            const ageNum = document.getElementById('auto-age-number');
            const computedAge = calculateAgeFromDob(dobValue);

            if (computedAge === null) {
                if (ageHidden) ageHidden.value = '';
                if (preview) preview.classList.add('hidden');
                if (ageNum) ageNum.textContent = '';
                updateAgeCategory('');
                return;
            }

            if (ageHidden) ageHidden.value = String(computedAge);
            if (ageNum) ageNum.textContent = `${computedAge} ${computedAge === 1 ? 'year' : 'years'} old`;
            if (preview) preview.classList.remove('hidden');
            updateAgeCategory(computedAge);
        }

        function updateAgeCategory(val) {
            const badge = document.getElementById('age-category-badge');
            const classificationSelect = document.getElementById('patient_classification');
            const result = getAgeCategory(val);
            if (!result) {
                badge.className = 'hidden';
                badge.textContent = '';
                return;
            }
            badge.className = `text-[11px] font-bold px-2 py-0.5 rounded-full border transition-all ${result.color}`;
            badge.textContent = result.label;

            // Auto-select Senior Citizen in classification if age >= 60 and user hasn't explicitly chosen PWD
            if (classificationSelect) {
                const numericAge = parseInt(val, 10);
                if (numericAge >= 60 && classificationSelect.value !== 'PWD') {
                    classificationSelect.value = 'Senior Citizen';
                } else if (numericAge < 60 && classificationSelect.value === 'Senior Citizen') {
                    classificationSelect.value = 'Regular Patient';
                }
            }
        }

        function onRoleChange(role) {
            const idSection = document.getElementById('id-photo-section');
            const idInput   = document.getElementById('id_photo');
            const termsLbl  = document.getElementById('terms-label');

            if (role === 'nurse') {
                if (idSection) idSection.classList.add('hidden');
                if (idInput)   idInput.removeAttribute('required');
                if (termsLbl)  termsLbl.textContent = 'I agree to register with correct information as a Nurse of Barangay Honrado Health Center.';
            } else {
                if (idSection) idSection.classList.remove('hidden');
                if (idInput)   idInput.setAttribute('required', 'required');
                if (termsLbl)  termsLbl.textContent = 'I agree to register with correct information as a resident of Barangay Honrado.';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const dobInput = document.getElementById('dob');
            if (dobInput && dobInput.value) {
                const parts = dobInput.value.split('-');
                if (parts.length === 3) {
                    const yEl = document.getElementById('birth_year');
                    const mEl = document.getElementById('birth_month');
                    const dEl = document.getElementById('birth_day');
                    if (yEl) yEl.value = parts[0];
                    if (mEl) mEl.value = parts[1];
                    if (dEl) dEl.value = parts[2];
                }
                syncBirthdayParts();
            }

            const selectedRole = document.querySelector('input[name="role"]:checked');
            if (selectedRole) {
                onRoleChange(selectedRole.value);
            }
        });

        // Drag-and-drop support
        const dropZone = document.getElementById('id-drop-zone');
        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('border-green-500','bg-green-50'); });
        dropZone.addEventListener('dragleave', () => { dropZone.classList.remove('border-green-500','bg-green-50'); });
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('border-green-500','bg-green-50');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                const dt = new DataTransfer();
                dt.items.add(file);
                const input = document.getElementById('id_photo');
                input.files = dt.files;
                previewIdPhoto(input);
            }
        });
    </script>

    <script src="{{ asset('js/ph-phone.js') }}"></script>
</body>
</html>
