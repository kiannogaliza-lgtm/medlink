@extends('layouts.nurse')

@section('title', 'Vaccinations')
@section('page-title', 'Vaccinations')

@section('styles')
<style>
    .dose-tab {
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .dose-tab.active {
        background: #0c2d48;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(12, 45, 72, 0.2);
    }
    .dose-tab:not(.active) {
        background: #f1f5f9;
        color: #475569;
    }
    .dose-tab:not(.active):hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    @media print {
        header, aside, .no-print, button, form, .pagination {
            display: none !important;
        }
        body, .main-wrapper {
            background: #fff !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .print-only {
            display: block !important;
        }
    }
    .print-only {
        display: none;
    }
</style>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Printable Header --}}
    <div class="print-only p-6 border-b-2 border-gray-800 text-center">
        <h1 class="text-2xl font-black text-gray-900 uppercase">Barangay Honrado Health Center</h1>
        <h2 class="text-base font-bold text-gray-700 mt-1">Immunization Masterlist & Dose Tracking Report</h2>
        <p class="text-xs text-gray-500 mt-0.5">Municipal Health Office National Immunization Program &bull; Date: {{ now()->format('F d, Y') }}</p>
    </div>

    {{-- Header Metrics --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Vaccinations</p>
                <p class="text-2xl font-black text-gray-800">{{ $totalVaccinations }}</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <span class="material-icons-outlined" style="font-size:22px;">vaccines</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider mb-1">1st Dose Given</p>
                <p class="text-2xl font-black text-emerald-600">{{ $firstDoseCount }}</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-icons-outlined" style="font-size:22px;">looks_one</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-purple-600 uppercase tracking-wider mb-1">2nd Dose Given</p>
                <p class="text-2xl font-black text-purple-600">{{ $secondDoseCount }}</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <span class="material-icons-outlined" style="font-size:22px;">looks_two</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-1">3rd Dose & Boosters</p>
                <p class="text-2xl font-black text-indigo-600">{{ $succeedingCount }}</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <span class="material-icons-outlined" style="font-size:22px;">verified</span>
            </div>
        </div>
    </div>

    {{-- Main Container --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Toolbar: Actions & Filters --}}
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4 no-print">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('nurse.vaccinations', ['age_category' => $ageCategoryFilter]) }}" class="dose-tab {{ $doseFilter === 'all' ? 'active' : '' }}">
                    <span>All Doses</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/20">{{ $totalVaccinations }}</span>
                </a>
                <a href="{{ route('nurse.vaccinations', ['dose' => '1st Dose', 'age_category' => $ageCategoryFilter]) }}" class="dose-tab {{ $doseFilter === '1st Dose' ? 'active' : '' }}">
                    <span>1st Dose</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $doseFilter === '1st Dose' ? 'bg-white/20' : 'bg-emerald-100 text-emerald-800' }}">{{ $firstDoseCount }}</span>
                </a>
                <a href="{{ route('nurse.vaccinations', ['dose' => '2nd Dose', 'age_category' => $ageCategoryFilter]) }}" class="dose-tab {{ $doseFilter === '2nd Dose' ? 'active' : '' }}">
                    <span>2nd Dose</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $doseFilter === '2nd Dose' ? 'bg-white/20' : 'bg-purple-100 text-purple-800' }}">{{ $secondDoseCount }}</span>
                </a>
                <a href="{{ route('nurse.vaccinations', ['dose' => '3rd Dose', 'age_category' => $ageCategoryFilter]) }}" class="dose-tab {{ $doseFilter === '3rd Dose' ? 'active' : '' }}">
                    <span>3rd Dose & Boosters</span>
                </a>

                {{-- Age Category Filter Dropdown --}}
                <div class="relative inline-block ml-1">
                    <select onchange="filterAgeCategory(this.value)" class="text-xs font-semibold px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 cursor-pointer">
                        <option value="all" {{ ($ageCategoryFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Age Categories</option>
                        <option value="Newborn" {{ ($ageCategoryFilter ?? '') === 'Newborn' ? 'selected' : '' }}>Newborn (Birth)</option>
                        <option value="Infant" {{ ($ageCategoryFilter ?? '') === 'Infant' ? 'selected' : '' }}>Infant (6 weeks–11 months)</option>
                        <option value="Young child" {{ ($ageCategoryFilter ?? '') === 'Young child' ? 'selected' : '' }}>Young child (12–23 months)</option>
                        <option value="Preschool child" {{ ($ageCategoryFilter ?? '') === 'Preschool child' ? 'selected' : '' }}>Preschool child (2–4 years)</option>
                        <option value="School-age child" {{ ($ageCategoryFilter ?? '') === 'School-age child' ? 'selected' : '' }}>School-age child (5–9 years)</option>
                        <option value="Adolescent" {{ ($ageCategoryFilter ?? '') === 'Adolescent' ? 'selected' : '' }}>Adolescent (10–19 years)</option>
                        <option value="Adult" {{ ($ageCategoryFilter ?? '') === 'Adult' ? 'selected' : '' }}>Adult (20–59 years)</option>
                        <option value="Senior citizen" {{ ($ageCategoryFilter ?? '') === 'Senior citizen' ? 'selected' : '' }}>Senior citizen (60 years and above)</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('nurse.vaccinations.export-excel', ['dose' => $doseFilter, 'age_category' => $ageCategoryFilter, 'search' => $search]) }}" class="px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm flex items-center gap-1.5 cursor-pointer" title="Export vaccinations to Excel">
                    <span class="material-icons-outlined" style="font-size:16px;">table_view</span>
                    <span>Export Excel</span>
                </a>
                <button onclick="window.print()" class="px-3.5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="material-icons-outlined" style="font-size:16px;">print</span>
                    <span>Print Masterlist</span>
                </button>
                <button onclick="openVaccineModal()" class="px-4 py-2.5 bg-[#0c2d48] hover:bg-[#144976] text-white text-xs font-bold rounded-xl transition-all shadow flex items-center gap-1.5 cursor-pointer">
                    <span class="material-icons-outlined" style="font-size:16px;">add_circle</span>
                    <span>Record Vaccine Dose</span>
                </button>
            </div>
        </div>

        {{-- Vaccinations Table --}}
        @if($vaccinations->isEmpty())
            <div class="text-center py-16 text-gray-400">
                <span class="material-icons-outlined mb-2 text-gray-300" style="font-size:56px;">vaccines</span>
                <p class="font-semibold text-gray-600 text-base">No vaccination records found</p>
                <p class="text-xs text-gray-400 mt-1">Click "Record Vaccine Dose" above to add immunization records across newborns, children, adolescents, adults, and seniors.</p>
            </div>
        @else
            <div class="overflow-x-auto w-full">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/60 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                            <th class="py-3.5 px-5 text-left">Patient</th>
                            <th class="py-3.5 px-5 text-left">Age / Classification</th>
                            <th class="py-3.5 px-5 text-left">Resident / Family Account</th>
                            <th class="py-3.5 px-5 text-left">Vaccine Administered</th>
                            <th class="py-3.5 px-5 text-center">Dose</th>
                            <th class="py-3.5 px-5 text-left">Date Given</th>
                            <th class="py-3.5 px-5 text-left">Provider</th>
                            <th class="py-3.5 px-5 text-center">Status</th>
                            <th class="py-3.5 px-5 text-right no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($vaccinations as $v)
                            <tr class="hover:bg-blue-50/20 transition-colors">
                                <td class="py-4 px-5 font-bold text-gray-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold shrink-0">
                                            {{ strtoupper(substr($v->child_name, 0, 1)) }}
                                        </div>
                                        <span>{{ $v->child_name }}</span>
                                    </div>
                                    <div class="text-[11px] text-gray-400 font-normal mt-0.5">
                                        {{ $v->child_gender ?? 'Gender unspecified' }} &bull; DOB: {{ $v->child_dob ? $v->child_dob->format('M d, Y') : 'N/A' }}
                                    </div>
                                </td>
                                <td class="py-4 px-5">
                                    <span class="text-xs font-semibold text-purple-700">
                                        {{ $v->age_category_label }}
                                    </span>
                                </td>
                                <td class="py-4 px-5">
                                    @if($v->parent)
                                        <div class="font-semibold text-blue-700 text-xs">{{ $v->parent->first_name }} {{ $v->parent->last_name }}</div>
                                        <div class="text-[11px] text-gray-400">{{ \App\Support\PhoneNumber::isPlaceholder($v->parent->phone_number) ? 'No phone' : $v->parent->phone_number }}</div>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Walk-in</span>
                                    @endif
                                </td>
                                <td class="py-4 px-5 font-bold text-gray-800">
                                    {{ $v->vaccine_name }}
                                    @if($v->notes)
                                        <div class="text-[11px] text-gray-400 font-normal italic mt-0.5">"{{ $v->notes }}"</div>
                                    @endif
                                </td>
                                <td class="py-4 px-5 text-center whitespace-nowrap">
                                    <span class="text-xs font-bold text-blue-800">
                                        {{ $v->dose_number }}
                                    </span>
                                </td>
                                <td class="py-4 px-5 text-xs text-gray-600 whitespace-nowrap">
                                    {{ $v->date_administered ? $v->date_administered->format('M d, Y') : 'Scheduled' }}
                                </td>
                                <td class="py-4 px-5 text-xs text-gray-600">
                                    {{ $v->administered_by ?? 'Health Center Staff' }}
                                </td>
                                <td class="py-4 px-5 text-center whitespace-nowrap">
                                    @php
                                        $statusColor = $v->status === 'Completed'
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                            : ($v->status === 'Missed'
                                                ? 'bg-red-50 text-red-700 border-red-200'
                                                : 'bg-blue-50 text-blue-700 border-blue-200');
                                    @endphp
                                    <form method="POST" action="{{ route('nurse.vaccinations.update-status', $v->id) }}" class="inline-block no-print">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" onchange="this.form.submit()" aria-label="Update vaccination status"
                                                class="text-[11px] font-bold px-2.5 py-1 rounded-full border cursor-pointer focus:outline-none {{ $statusColor }}">
                                            <option value="Completed" {{ $v->status === 'Completed' ? 'selected' : '' }}>Completed</option>
                                            <option value="Scheduled" {{ $v->status === 'Scheduled' ? 'selected' : '' }}>Scheduled</option>
                                            <option value="Missed" {{ $v->status === 'Missed' ? 'selected' : '' }}>Missed</option>
                                        </select>
                                    </form>
                                    <span class="print-only text-[11px] font-bold {{ $v->status === 'Completed' ? 'text-emerald-700' : ($v->status === 'Missed' ? 'text-red-700' : 'text-blue-700') }}">
                                        {{ $v->status }}
                                    </span>
                                </td>
                                <td class="py-4 px-5 text-right whitespace-nowrap no-print">
                                    <form method="POST" action="{{ route('nurse.vaccinations.destroy', $v->id) }}" onsubmit="return confirm('Delete this vaccination record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors" title="Delete record">
                                            <span class="material-icons-outlined" style="font-size:18px;">delete</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-100 no-print">
                {{ $vaccinations->links() }}
            </div>
        @endif
    </div>
</div>

{{-- RECORD VACCINE MODAL --}}
<div id="recordVaccineModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-blue-50/40">
            <div class="flex items-center gap-2 text-[#0c2d48]">
                <span class="material-icons-outlined text-blue-600">vaccines</span>
                <h3 class="font-bold text-base">Record Vaccine Dose</h3>
            </div>
            <button type="button" onclick="closeVaccineModal()" class="text-gray-400 hover:text-gray-600 p-1">
                <span class="material-icons-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="{{ route('nurse.vaccinations.store') }}" class="p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Resident / Parent / Guardian Account</label>
                <select name="user_id" id="modal_user_id" onchange="handleResidentSelect(this)" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs focus:ring-2 focus:ring-blue-500/30">
                    <option value="">-- Walk-in without Resident Account --</option>
                    @foreach($parents as $p)
                        @php
                            $dobFormatted = '';
                            if (!empty($p->dob)) {
                                $dobFormatted = $p->dob instanceof \Carbon\Carbon ? $p->dob->format('Y-m-d') : date('Y-m-d', strtotime($p->dob));
                            }
                        @endphp
                        <option value="{{ $p->id }}" data-name="{{ $p->first_name }} {{ $p->last_name }}" data-dob="{{ $dobFormatted }}" data-gender="{{ $p->gender }}" data-age="{{ $p->age }}">
                            {{ $p->first_name }} {{ $p->last_name }} ({{ \App\Support\PhoneNumber::isPlaceholder($p->phone_number) ? 'No phone' : $p->phone_number }})
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-gray-400 mt-0.5">Linking the resident/parent will automatically send a notification to their resident account and reflect this vaccine record.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Patient Full Name *</label>
                    <input type="text" name="child_name" id="modal_patient_name" required placeholder="e.g. Baby Lucas Santos, Juan Dela Cruz" class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500/30">
                </div>

                {{-- Age Category Selection --}}
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Age Category *</label>
                    <select name="age_category" id="modal_age_category" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-semibold text-purple-700 focus:ring-2 focus:ring-blue-500/30">
                        <option value="Newborn">Newborn (Birth)</option>
                        <option value="Infant">Infant (6 weeks–11 months)</option>
                        <option value="Young child">Young child (12–23 months)</option>
                        <option value="Preschool child">Preschool child (2–4 years)</option>
                        <option value="School-age child">School-age child (5–9 years)</option>
                        <option value="Adolescent">Adolescent (10–19 years)</option>
                        <option value="Adult">Adult (20–59 years)</option>
                        <option value="Senior citizen">Senior citizen (60 years and above)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Date of Birth</label>
                    <input type="date" name="child_dob" id="modal_dob" onchange="autoSuggestAgeCategory(this.value)" class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500/30">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Gender</label>
                    <select name="child_gender" id="modal_gender" class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500/30">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Vaccine Name *</label>
                    <select name="vaccine_name" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-bold text-blue-700 focus:ring-2 focus:ring-blue-500/30">
                        <optgroup label="Infant & Childhood Vaccines">
                            <option value="BCG (Tuberculosis)">BCG (At Birth)</option>
                            <option value="Hepatitis B">Hepatitis B (At Birth)</option>
                            <option value="Pentavalent (DTP-HepB-Hib)">Pentavalent (DTP-HepB-Hib)</option>
                            <option value="Oral Polio Vaccine (OPV)">Oral Polio Vaccine (OPV)</option>
                            <option value="Inactivated Polio (IPV)">Inactivated Polio (IPV)</option>
                            <option value="Pneumococcal Conjugate (PCV)">Pneumococcal Conjugate (PCV)</option>
                            <option value="Measles, Mumps, Rubella (MMR)">Measles, Mumps, Rubella (MMR)</option>
                        </optgroup>
                        <optgroup label="Adolescent, Adult & Senior Vaccines">
                            <option value="Flu Vaccine (Influenza)">Flu Vaccine (Influenza)</option>
                            <option value="Pneumococcal Polysaccharide (Pneumonia)">Pneumococcal Polysaccharide (Pneumonia)</option>
                            <option value="COVID-19 Vaccine">COVID-19 Vaccine</option>
                            <option value="Tetanus Toxoid (TT / Td)">Tetanus Toxoid (TT / Td)</option>
                            <option value="HPV (Human Papillomavirus)">HPV (Human Papillomavirus)</option>
                            <option value="Hepatitis B (Adult)">Hepatitis B (Adult)</option>
                            <option value="Rabies Post-Exposure">Rabies Post-Exposure</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Dose Applicable *</label>
                    <select name="dose_number" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-bold text-purple-700 focus:ring-2 focus:ring-blue-500/30">
                        <option value="1st Dose">1st Dose</option>
                        <option value="2nd Dose">2nd Dose</option>
                        <option value="3rd Dose">3rd Dose</option>
                        <option value="Booster 1">Booster 1</option>
                        <option value="Booster 2">Booster 2</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Date Administered *</label>
                    <input type="date" name="date_administered" required value="{{ now()->toDateString() }}" class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500/30">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Dose Status *</label>
                    <select name="status" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500/30">
                        <option value="Completed">Completed</option>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Missed">Missed</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Immunization / Reaction Notes</label>
                <textarea name="notes" rows="2" placeholder="e.g. Tolerated well, right deltoid injection, immunization card updated." class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500/30 resize-none"></textarea>
            </div>

            <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeVaccineModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 bg-[#0c2d48] hover:bg-[#144976] text-white text-xs font-bold rounded-xl transition-all shadow">
                    Record Vaccine Dose
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openVaccineModal() {
        document.getElementById('recordVaccineModal').classList.remove('hidden');
        document.getElementById('recordVaccineModal').classList.add('flex');
    }
    function closeVaccineModal() {
        document.getElementById('recordVaccineModal').classList.remove('flex');
        document.getElementById('recordVaccineModal').classList.add('hidden');
    }

    function filterAgeCategory(category) {
        const url = new URL(window.location);
        if (category === 'all') {
            url.searchParams.delete('age_category');
        } else {
            url.searchParams.set('age_category', category);
        }
        window.location = url.toString();
    }

    function autoSuggestAgeCategory(dobString) {
        if (!dobString) return;
        const dob = new Date(dobString);
        const today = new Date();
        const diffMs = today - dob;
        if (diffMs < 0) return;

        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        const diffYears = today.getFullYear() - dob.getFullYear();
        const categorySelect = document.getElementById('modal_age_category');

        if (diffDays <= 42) {
            categorySelect.value = 'Newborn';
        } else if (diffDays <= 365) {
            categorySelect.value = 'Infant';
        } else if (diffDays <= 730) {
            categorySelect.value = 'Young child';
        } else if (diffYears < 5) {
            categorySelect.value = 'Preschool child';
        } else if (diffYears < 10) {
            categorySelect.value = 'School-age child';
        } else if (diffYears < 20) {
            categorySelect.value = 'Adolescent';
        } else if (diffYears < 60) {
            categorySelect.value = 'Adult';
        } else {
            categorySelect.value = 'Senior citizen';
        }
    }

    function handleResidentSelect(select) {
        const opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) return;
        const name = opt.getAttribute('data-name');
        const dob = opt.getAttribute('data-dob');
        const gender = opt.getAttribute('data-gender');
        const age = opt.getAttribute('data-age');

        // Only pre-fill patient name if user hasn't typed a different name or it's empty
        const nameInput = document.getElementById('modal_patient_name');
        if (!nameInput.value) {
            nameInput.value = name || '';
        }
        if (dob) {
            document.getElementById('modal_dob').value = dob;
            autoSuggestAgeCategory(dob);
        } else if (age) {
            const ageNum = parseInt(age, 10);
            const categorySelect = document.getElementById('modal_age_category');
            if (ageNum < 1) categorySelect.value = 'Infant';
            else if (ageNum <= 2) categorySelect.value = 'Young child';
            else if (ageNum <= 4) categorySelect.value = 'Preschool child';
            else if (ageNum <= 9) categorySelect.value = 'School-age child';
            else if (ageNum <= 19) categorySelect.value = 'Adolescent';
            else if (ageNum <= 59) categorySelect.value = 'Adult';
            else categorySelect.value = 'Senior citizen';
        }
        if (gender) {
            document.getElementById('modal_gender').value = gender;
        }
    }
</script>
@endsection

