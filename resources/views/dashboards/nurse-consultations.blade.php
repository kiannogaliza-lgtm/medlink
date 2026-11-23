@extends('layouts.nurse')

@section('title', 'Consultations & Disease Monitoring')
@section('page-title', 'Consultations & Disease Monitoring')

@section('styles')
<style>
    .disease-tab {
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
    .disease-tab.active {
        background: #0c2d48;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(12, 45, 72, 0.2);
    }
    .disease-tab:not(.active) {
        background: #f1f5f9;
        color: #475569;
    }
    .disease-tab:not(.active):hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .badge-dengue { color: #dc2626; }
    .badge-tb     { color: #d97706; }
    .badge-hiv    { color: #7e22ce; }
    .badge-measles{ color: #c2410c; }
    .badge-other  { color: #0284c7; }

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

    {{-- Printable Header (Shows only on print) --}}
    <div class="print-only p-6 border-b-2 border-gray-800 text-center">
        <h1 class="text-2xl font-black text-gray-900 uppercase">Barangay Honrado Health Center</h1>
        <h2 class="text-base font-bold text-gray-700 mt-1">Official Disease Surveillance & Consultation Report</h2>
        <p class="text-xs text-gray-500 mt-0.5">Municipal Health Office Reporting & Epidemic Case Monitoring &bull; Date: {{ now()->format('F d, Y h:i A') }}</p>
    </div>

    {{-- High Alert Banner if any disease threshold is reached --}}
    @if($dengueAlert || $tbAlert || $hivAlert || $measlesAlert)
        <div class="p-5 rounded-2xl bg-gradient-to-r from-red-600 to-rose-700 text-white shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                    <span class="material-icons-outlined text-white" style="font-size:28px;">warning</span>
                </div>
                <div>
                    <h3 class="font-extrabold text-base tracking-wide flex items-center gap-2">
                        <span>EPIDEMIC THRESHOLD REACHED ({{ $thresholdLimit }}+ Cases Alert)</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-white text-red-700 uppercase">Barangay Alert Active</span>
                    </h3>
                    <p class="text-xs text-red-100 mt-0.5">
                        High incidence detected:
                        @if($dengueAlert) <strong>Dengue ({{ $dengueCount }} cases)</strong> @endif
                        @if($tbAlert) <strong>&bull; Tuberculosis ({{ $tbCount }} cases)</strong> @endif
                        @if($hivAlert) <strong>&bull; HIV ({{ $hivCount }} cases)</strong> @endif
                        @if($measlesAlert) <strong>&bull; Measles ({{ $measlesCount }} cases)</strong> @endif
                        &bull; Automatic public health announcement has been broadcast to all resident dashboards.
                    </p>
                </div>
            </div>
            <button onclick="window.print()" class="px-4 py-2.5 bg-white text-red-700 hover:bg-red-50 text-xs font-bold rounded-xl transition-all shadow shrink-0 flex items-center gap-1.5 cursor-pointer">
                <span class="material-icons-outlined" style="font-size:16px;">print</span>
                <span>Print Health Office Report</span>
            </button>
        </div>
    @endif

    {{-- Header & Metrics --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        {{-- Total Consultations --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Consultations</p>
                <p class="text-2xl font-black text-gray-800">{{ $totalCases }}</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <span class="material-icons-outlined" style="font-size:22px;">medical_services</span>
            </div>
        </div>

        {{-- Dengue Cases --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between relative overflow-hidden">
            <div>
                <div class="flex items-center gap-1.5 mb-1">
                    <p class="text-xs font-semibold text-red-600 uppercase tracking-wider">Dengue</p>
                    @if($dengueAlert) <span class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></span> @endif
                </div>
                <p class="text-2xl font-black text-red-600">{{ $dengueCount }}</p>
                <p class="text-[10px] text-gray-400 mt-0.5">Threshold: {{ $thresholdLimit }} cases</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                <span class="material-icons-outlined" style="font-size:22px;">pest_control</span>
            </div>
        </div>

        {{-- Tuberculosis Cases --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 mb-1">
                    <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Tuberculosis (TB)</p>
                    @if($tbAlert) <span class="w-2 h-2 rounded-full bg-amber-600 animate-pulse"></span> @endif
                </div>
                <p class="text-2xl font-black text-amber-600">{{ $tbCount }}</p>
                <p class="text-[10px] text-gray-400 mt-0.5">Threshold: {{ $thresholdLimit }} cases</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-icons-outlined" style="font-size:22px;">air</span>
            </div>
        </div>

        {{-- HIV Cases --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 mb-1">
                    <p class="text-xs font-semibold text-purple-600 uppercase tracking-wider">HIV Surveillance</p>
                    @if($hivAlert) <span class="w-2 h-2 rounded-full bg-purple-600 animate-pulse"></span> @endif
                </div>
                <p class="text-2xl font-black text-purple-600">{{ $hivCount }}</p>
                <p class="text-[10px] text-gray-400 mt-0.5">Threshold: {{ $thresholdLimit }} cases</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <span class="material-icons-outlined" style="font-size:22px;">healing</span>
            </div>
        </div>

        {{-- Measles Cases --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 mb-1">
                    <p class="text-xs font-semibold text-orange-600 uppercase tracking-wider">Measles</p>
                    @if($measlesAlert) <span class="w-2 h-2 rounded-full bg-orange-600 animate-pulse"></span> @endif
                </div>
                <p class="text-2xl font-black text-orange-600">{{ $measlesCount }}</p>
                <p class="text-[10px] text-gray-400 mt-0.5">Threshold: {{ $thresholdLimit }} cases</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                <span class="material-icons-outlined" style="font-size:22px;">coronavirus</span>
            </div>
        </div>
    </div>

    {{-- Main Container --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Toolbar: Actions, Filters & Print --}}
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4 no-print">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('nurse.consultations') }}" class="disease-tab {{ $diseaseFilter === 'all' ? 'active' : '' }}">
                    <span>All Cases</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $diseaseFilter === 'all' ? 'bg-white/20' : 'bg-gray-200 text-gray-700' }}">{{ $totalCases }}</span>
                </a>
                @php
                    $filterTabs = [
                        ['value' => 'General Consultation', 'label' => 'General Consultation', 'badge' => 'bg-blue-100 text-blue-700'],
                        ['value' => 'Fever / Flu',          'label' => 'Fever / Flu',          'badge' => 'bg-sky-100 text-sky-700'],
                        ['value' => 'Cough / Cold',         'label' => 'Cough / Cold',         'badge' => 'bg-cyan-100 text-cyan-700'],
                        ['value' => 'Hypertension',         'label' => 'Hypertension',         'badge' => 'bg-rose-100 text-rose-700'],
                        ['value' => 'Dengue',               'label' => 'Dengue',               'badge' => 'bg-red-100 text-red-700'],
                        ['value' => 'Tuberculosis',         'label' => 'Tuberculosis',         'badge' => 'bg-amber-100 text-amber-700'],
                        ['value' => 'HIV',                  'label' => 'HIV',                  'badge' => 'bg-purple-100 text-purple-700'],
                        ['value' => 'Measles',              'label' => 'Measles',              'badge' => 'bg-orange-100 text-orange-700'],
                        ['value' => 'Other',                'label' => 'Other',                'badge' => 'bg-slate-200 text-slate-700'],
                    ];
                @endphp
                @foreach($filterTabs as $tab)
                    <a href="{{ route('nurse.consultations', ['disease' => $tab['value']]) }}" class="disease-tab {{ $diseaseFilter === $tab['value'] ? 'active' : '' }}">
                        <span>{{ $tab['label'] }}</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $diseaseFilter === $tab['value'] ? 'bg-white/20' : $tab['badge'] }}">{{ $caseCounts[$tab['value']] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="{{ route('nurse.consultations.export-excel', ['disease' => $diseaseFilter, 'search' => $search]) }}" class="px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm flex items-center gap-1.5 cursor-pointer whitespace-nowrap" title="Export consultations to Excel">
                    <span class="material-icons-outlined" style="font-size:16px;">table_view</span>
                    <span>Export Excel</span>
                </a>
                <button onclick="window.print()" class="px-3.5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap" title="Print official summary report for Health Office">
                    <span class="material-icons-outlined" style="font-size:16px;">print</span>
                    <span>Print Summary</span>
                </button>
                <form method="POST" action="{{ route('nurse.consultations.clear') }}" onsubmit="return confirm('{{ $diseaseFilter === 'all' ? 'Are you sure you want to clear ALL consultation records? This action cannot be undone.' : 'Are you sure you want to clear all ' . addslashes($diseaseFilter) . ' consultation records? This action cannot be undone.' }}');" class="inline">
                    @csrf
                    <input type="hidden" name="disease" value="{{ $diseaseFilter }}">
                    <button type="submit" class="px-3.5 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap" title="Clear consultation records">
                        <span class="material-icons-outlined" style="font-size:16px;">delete_sweep</span>
                        <span>Clear List</span>
                    </button>
                </form>
                <button onclick="openRecordModal()" class="px-4 py-2.5 bg-[#0c2d48] hover:bg-[#144976] text-white text-xs font-bold rounded-xl transition-all shadow flex items-center gap-1.5 cursor-pointer whitespace-nowrap">
                    <span class="material-icons-outlined" style="font-size:16px;">add_circle</span>
                    <span>Record Consultation</span>
                </button>
            </div>
        </div>

        {{-- Consultations Table --}}
        @if($consultations->isEmpty())
            <div class="text-center py-16 text-gray-400">
                <span class="material-icons-outlined mb-2 text-gray-300" style="font-size:56px;">healing</span>
                <p class="font-semibold text-gray-600 text-base">No consultation records found</p>
                <p class="text-xs text-gray-400 mt-1">Click "Record Consultation" above to add patient diagnosis and disease monitoring cases.</p>
            </div>
        @else
            <div class="overflow-x-auto w-full">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/60 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                            <th class="py-3.5 px-5 text-left">Date</th>
                            <th class="py-3.5 px-5 text-left">Patient Details</th>
                            <th class="py-3.5 px-5 text-left">Disease Case</th>
                            <th class="py-3.5 px-5 text-left">Symptoms & Diagnosis</th>
                            <th class="py-3.5 px-5 text-left">Treatment / Notes</th>
                            <th class="py-3.5 px-5 text-center">Status</th>
                            <th class="py-3.5 px-5 text-right no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($consultations as $item)
                            <tr class="hover:bg-blue-50/20 transition-colors">
                                <td class="py-4 px-5 text-xs text-gray-600 whitespace-nowrap">
                                    <div class="font-bold text-gray-800">{{ $item->consultation_date ? $item->consultation_date->format('M d, Y') : 'N/A' }}</div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">By {{ $item->attending_nurse }}</div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="font-bold text-gray-900">{{ $item->patient_name }}</div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">
                                        {{ $item->patient_age ? "{$item->patient_age} yrs old" : 'Age N/A' }} &bull; {{ $item->patient_gender ?? 'N/A' }}
                                    </div>
                                    @if($item->address)
                                        <div class="text-[11px] text-gray-400 flex items-center gap-1 mt-0.5">
                                            <span class="material-icons-outlined" style="font-size:12px;">location_on</span>
                                            {{ $item->address }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-4 px-5 whitespace-nowrap">
                                    @if(strtolower($item->disease_case) === 'dengue')
                                        <span class="badge-dengue text-xs font-bold inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span> Dengue Case
                                        </span>
                                    @elseif(strtolower($item->disease_case) === 'tuberculosis')
                                        <span class="badge-tb text-xs font-bold inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span> Tuberculosis (TB)
                                        </span>
                                    @elseif(strtolower($item->disease_case) === 'hiv')
                                        <span class="badge-hiv text-xs font-bold inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-600"></span> HIV Surveillance
                                        </span>
                                    @elseif(strtolower($item->disease_case) === 'measles')
                                        <span class="badge-measles text-xs font-bold inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-600"></span> Measles
                                        </span>
                                    @else
                                        <span class="badge-other text-xs font-bold inline-flex items-center gap-1">
                                            {{ $item->disease_case }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-5 max-w-xs">
                                    <div class="text-xs font-semibold text-gray-800">{{ $item->diagnosis ?? 'General Diagnosis' }}</div>
                                    @if($item->symptoms)
                                        <div class="text-[11px] text-gray-500 mt-0.5 line-clamp-2" title="{{ $item->symptoms }}">
                                            Symptoms: {{ $item->symptoms }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-4 px-5 max-w-xs text-xs text-gray-600">
                                    {{ $item->treatment_notes ?? 'Standard clinic care.' }}
                                </td>
                                <td class="py-4 px-5 text-center whitespace-nowrap">
                                    @php
                                        $statusColor = match($item->status) {
                                            'Home Delivery' => 'text-indigo-600',
                                            'Walk-in'       => 'text-blue-600',
                                            'Active'        => 'text-amber-600',
                                            'Referred'      => 'text-purple-600',
                                            default         => 'text-green-700',
                                        };
                                    @endphp
                                    <span class="text-xs font-bold {{ $statusColor }}">
                                        {{ $item->status }}
                                    </span>
                                </td>
                                <td class="py-4 px-5 text-right whitespace-nowrap no-print">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                onclick="openViewConsultationModal({{ json_encode([
                                                    'patient_name'      => $item->patient_name,
                                                    'patient_age'       => $item->patient_age,
                                                    'patient_gender'    => $item->patient_gender,
                                                    'address'           => $item->address,
                                                    'disease_case'      => $item->disease_case,
                                                    'symptoms'          => $item->symptoms,
                                                    'diagnosis'         => $item->diagnosis,
                                                    'treatment_notes'   => $item->treatment_notes,
                                                    'consultation_date' => $item->consultation_date ? $item->consultation_date->format('M d, Y') : 'N/A',
                                                    'attending_nurse'   => $item->attending_nurse ?: 'Health Center Staff',
                                                    'status'            => $item->status,
                                                ]) }})"
                                                class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold rounded-lg transition-colors inline-flex items-center gap-1 cursor-pointer"
                                                title="View full consultation details & patient symptoms">
                                            <span class="material-icons-outlined" style="font-size:15px;">visibility</span>
                                            <span>View</span>
                                        </button>
                                        <form method="POST" action="{{ route('nurse.consultations.destroy', $item->id) }}" onsubmit="return confirm('Delete this consultation record?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors cursor-pointer" title="Delete record">
                                                <span class="material-icons-outlined" style="font-size:18px;">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-100 no-print">
                {{ $consultations->links() }}
            </div>
        @endif
    </div>
</div>

{{-- RECORD CONSULTATION MODAL --}}
<div id="recordConsultationModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-blue-50/40">
            <div class="flex items-center gap-2 text-[#0c2d48]">
                <span class="material-icons-outlined text-blue-600">add_circle</span>
                <h3 class="font-bold text-base">Record Patient Consultation</h3>
            </div>
            <button type="button" onclick="closeRecordModal()" class="text-gray-400 hover:text-gray-600 p-1">
                <span class="material-icons-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="{{ route('nurse.consultations.store') }}" class="p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Select Registered Resident (Optional)</label>
                <select name="user_id" onchange="autofillResident(this)" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs focus:ring-2 focus:ring-blue-500/30">
                    <option value="">-- Non-registered or Walk-in Patient --</option>
                    @foreach($residents as $res)
                        <option value="{{ $res->id }}" data-name="{{ $res->first_name }} {{ $res->last_name }}" data-age="{{ $res->age }}" data-gender="{{ $res->gender }}" data-address="{{ $res->address }}">
                            {{ $res->first_name }} {{ $res->last_name }} ({{ $res->age ? "{$res->age}y" : 'Age N/A' }})
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-gray-400 mt-0.5">Selecting a registered resident will automatically send them a portal notification regarding this record.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Patient Full Name *</label>
                    <input type="text" name="patient_name" id="modal_patient_name" required placeholder="e.g. Juan Dela Cruz" class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500/30">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Age (Years)</label>
                    <input type="number" name="patient_age" id="modal_patient_age" min="0" max="130" placeholder="e.g. 28" class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500/30">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Gender</label>
                    <select name="patient_gender" id="modal_patient_gender" class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500/30">
                        <option value="">Select...</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Barangay Address / Zone</label>
                <input type="text" name="address" id="modal_patient_address" placeholder="e.g. Purok 4, Barangay Honrado" class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500/30">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Disease / Surveillance Case *</label>
                    <select name="disease_case" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-bold text-blue-700 focus:ring-2 focus:ring-blue-500/30">
                        <option value="General Consultation">General Consultation</option>
                        <option value="Fever / Flu">Fever / Flu</option>
                        <option value="Cough / Cold">Cough / Cold</option>
                        <option value="Hypertension">Hypertension</option>
                        <option value="Dengue">Dengue Fever</option>
                        <option value="Tuberculosis">Tuberculosis (TB)</option>
                        <option value="HIV">HIV Surveillance</option>
                        <option value="Measles">Measles</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Consultation Date *</label>
                    <input type="date" name="consultation_date" required value="{{ now()->toDateString() }}" class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500/30">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Reported Symptoms</label>
                <textarea name="symptoms" rows="2" placeholder="e.g. High fever, retro-orbital pain, body rashes..." class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500/30 resize-none"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Diagnosis</label>
                    <input type="text" name="diagnosis" placeholder="e.g. Suspected Dengue NS1 Positive" class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500/30">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Case Status *</label>
                    <select name="status" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500/30">
                        <option value="Active">Active Case</option>
                        <option value="Recovered">Recovered</option>
                        <option value="Referred">Referred to District Hospital</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Treatment / Prescription Notes</label>
                <textarea name="treatment_notes" rows="2" placeholder="e.g. Oral rehydration therapy, paracetamol, bed rest advised." class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500/30 resize-none"></textarea>
            </div>

            <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeRecordModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 bg-[#0c2d48] hover:bg-[#144976] text-white text-xs font-bold rounded-xl transition-all shadow">
                    Save Consultation Record
                </button>
            </div>
        </form>
    </div>
</div>

{{-- View Consultation Details Modal --}}
<div id="viewConsultationModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs items-center justify-center p-4 no-print" onclick="closeViewConsultationModal(event)">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden" onclick="event.stopPropagation()">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-blue-50/50">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                    <span class="material-icons-outlined text-xl">medical_information</span>
                </div>
                <div>
                    <h3 id="viewConsPatientName" class="font-bold text-gray-900 text-base">Consultation Details</h3>
                    <p id="viewConsMeta" class="text-xs text-gray-500">—</p>
                </div>
            </div>
            <button type="button" onclick="closeViewConsultationModal()" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                <span class="material-icons-outlined">close</span>
            </button>
        </div>

        <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
            <div class="grid grid-cols-2 gap-3">
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Disease / Case</span>
                    <span id="viewConsDisease" class="text-sm font-bold text-blue-700 mt-0.5 block">—</span>
                </div>
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Status</span>
                    <span id="viewConsStatus" class="text-sm font-bold text-emerald-700 mt-0.5 block">—</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Date &amp; Recorded By</span>
                    <span id="viewConsDateBy" class="text-xs font-semibold text-gray-800 mt-0.5 block">—</span>
                </div>
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Address</span>
                    <span id="viewConsAddress" class="text-xs font-semibold text-gray-800 mt-0.5 block">—</span>
                </div>
            </div>

            <div class="p-4 bg-amber-50/70 rounded-xl border border-amber-200">
                <span class="text-[11px] font-bold text-amber-900 uppercase tracking-wider block">Patient Symptoms</span>
                <p id="viewConsSymptoms" class="text-sm text-gray-900 mt-1 whitespace-pre-line leading-relaxed">—</p>
            </div>

            <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-100">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Diagnosis / Consultation Assessment</span>
                <p id="viewConsDiagnosis" class="text-xs text-gray-800 font-medium mt-1 whitespace-pre-line leading-relaxed">—</p>
            </div>

            <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-100">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Treatment / Notes</span>
                <p id="viewConsTreatment" class="text-xs text-gray-700 mt-1 whitespace-pre-line leading-relaxed">—</p>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex justify-end">
            <button type="button" onclick="closeViewConsultationModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-lg transition-colors cursor-pointer">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    function openViewConsultationModal(data) {
        document.getElementById('viewConsPatientName').textContent = data.patient_name || 'Patient';
        const ageText = data.patient_age !== null && data.patient_age !== undefined ? `${data.patient_age} yrs old` : 'Age N/A';
        const genderText = data.patient_gender || 'N/A';
        document.getElementById('viewConsMeta').textContent = `${ageText} • ${genderText}`;
        document.getElementById('viewConsDisease').textContent = data.disease_case || 'General Consultation';
        document.getElementById('viewConsStatus').textContent = data.status || 'Active';
        document.getElementById('viewConsDateBy').textContent = `${data.consultation_date || 'N/A'} — By ${data.attending_nurse || 'Staff'}`;
        document.getElementById('viewConsAddress').textContent = data.address || 'Barangay Honrado';
        document.getElementById('viewConsSymptoms').textContent = data.symptoms || 'No symptoms recorded.';
        document.getElementById('viewConsDiagnosis').textContent = data.diagnosis || 'None provided.';
        document.getElementById('viewConsTreatment').textContent = data.treatment_notes || 'Standard clinic care.';

        const modal = document.getElementById('viewConsultationModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeViewConsultationModal(e) {
        if (e && e.target !== document.getElementById('viewConsultationModal')) return;
        const modal = document.getElementById('viewConsultationModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function openRecordModal() {
        document.getElementById('recordConsultationModal').classList.remove('hidden');
        document.getElementById('recordConsultationModal').classList.add('flex');
    }
    function closeRecordModal() {
        document.getElementById('recordConsultationModal').classList.remove('flex');
        document.getElementById('recordConsultationModal').classList.add('hidden');
    }
    function autofillResident(select) {
        const opt = select.options[select.selectedIndex];
        if (opt.value) {
            document.getElementById('modal_patient_name').value = opt.getAttribute('data-name') || '';
            document.getElementById('modal_patient_age').value = opt.getAttribute('data-age') || '';
            document.getElementById('modal_patient_gender').value = opt.getAttribute('data-gender') || '';
            document.getElementById('modal_patient_address').value = opt.getAttribute('data-address') || '';
        }
    }
</script>
@endsection

