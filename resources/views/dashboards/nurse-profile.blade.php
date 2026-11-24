@extends('layouts.nurse')

@section('title', 'Staff Profile')
@section('page-title', 'Nurse Staff Profile')

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-8">

    {{-- Staff Identity Banner (Distinct from Resident Profile) --}}
    <div class="rounded-2xl overflow-hidden shadow-md border border-slate-200 bg-white">
        <div class="bg-gradient-to-r from-[#0c2d48] via-[#134068] to-[#1d5b91] px-6 py-7 sm:px-8 text-white">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">
                <div class="flex items-center gap-5">
                    <div class="relative shrink-0">
                        @if($user->profile_photo)
                            <img id="nurseProfilePreview" src="{{ asset('storage/' . $user->profile_photo) }}" alt="{{ $user->first_name }}"
                                 class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl object-cover border-4 border-white/25 shadow-lg">
                        @else
                            <div id="nurseProfileInitials" class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-white/15 border-4 border-white/25 flex items-center justify-center text-white font-black text-2xl sm:text-3xl shadow-lg">
                                {{ strtoupper(substr($user->first_name, 0, 1)) }}{{ strtoupper(substr($user->last_name, 0, 1)) }}
                            </div>
                            <img id="nurseProfilePreview" src="" alt="" class="hidden w-20 h-20 sm:w-24 sm:h-24 rounded-2xl object-cover border-4 border-white/25 shadow-lg">
                        @endif
                        <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-400 border-2 border-[#0c2d48]" title="Online"></span>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-md bg-white/15 text-cyan-200 text-[11px] font-bold uppercase tracking-wider">
                                Barangay Nurse &bull; Health Center Admin
                            </span>
                            <span class="px-2.5 py-0.5 rounded-md bg-emerald-500/20 text-emerald-200 text-[11px] font-bold uppercase tracking-wider">
                                {{ ucfirst($user->status ?? 'Active') }}
                            </span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight mt-1.5">
                            {{ $user->first_name }} {{ $user->last_name }}
                        </h2>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-blue-100/90 mt-1.5">
                            <span class="inline-flex items-center gap-1">
                                <span class="material-icons-outlined" style="font-size:14px;">badge</span>
                                Staff ID: <strong>NRS-{{ now()->year }}-{{ str_pad($user->id, 3, '0', STR_PAD_LEFT) }}</strong>
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="material-icons-outlined" style="font-size:14px;">call</span>
                                {{ $user->phone_number }}
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="material-icons-outlined" style="font-size:14px;">schedule</span>
                                Last Login: {{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->format('M d, Y h:i A') : 'Active Session' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="text-left sm:text-right text-xs text-blue-100/80 shrink-0">
                    <div class="uppercase tracking-wider text-[10px] font-bold text-cyan-200/80">Assigned Facility</div>
                    <div class="font-bold text-white text-sm mt-0.5">Barangay Honrado Health Center</div>
                    <div class="mt-0.5">Member since {{ $user->created_at ? $user->created_at->format('M Y') : '2026' }}</div>
                </div>
            </div>
        </div>

        {{-- Clinical Operations Snapshot --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 divide-y lg:divide-y-0 lg:divide-x divide-gray-100 bg-slate-50/70">
            <div class="p-4 sm:p-5 flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                    <span class="material-icons-outlined" style="font-size:20px;">how_to_reg</span>
                </div>
                <div>
                    <div class="text-xl font-black text-gray-900">{{ $staffStats['verified_residents'] }}</div>
                    <div class="text-[11px] font-semibold text-gray-500">Verified Residents</div>
                </div>
            </div>
            <div class="p-4 sm:p-5 flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <span class="material-icons-outlined" style="font-size:20px;">medical_information</span>
                </div>
                <div>
                    <div class="text-xl font-black text-gray-900">{{ $staffStats['consultations_logged'] }}</div>
                    <div class="text-[11px] font-semibold text-gray-500">Consultations Logged</div>
                </div>
            </div>
            <div class="p-4 sm:p-5 flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                    <span class="material-icons-outlined" style="font-size:20px;">medication_liquid</span>
                </div>
                <div>
                    <div class="text-xl font-black text-gray-900">{{ $staffStats['medicine_processed'] }}</div>
                    <div class="text-[11px] font-semibold text-gray-500">Requests Processed</div>
                </div>
            </div>
            <div class="p-4 sm:p-5 flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                    <span class="material-icons-outlined" style="font-size:20px;">home_health</span>
                </div>
                <div>
                    <div class="text-xl font-black text-gray-900">{{ $staffStats['home_services_done'] }}</div>
                    <div class="text-[11px] font-semibold text-gray-500">Home Visits Done</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <span class="material-icons-outlined" style="font-size:16px;">error</span>
                <span>Please fix the following errors:</span>
            </div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 2-Column Staff Settings Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left (2 cols): Staff Details & Photo --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
                <div class="flex items-center gap-2.5">
                    <span class="material-icons-outlined text-[#0c2d48]" style="font-size:20px;">manage_accounts</span>
                    <div>
                        <h3 class="font-bold text-gray-900 text-sm">Staff Account Information</h3>
                        <p class="text-[11px] text-gray-500">Update your official name, contact number, station address, and photo.</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('nurse.profile.update') }}" enctype="multipart/form-data" class="p-6 space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">First Name *</label>
                        <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Last Name *</label>
                        <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Mobile Number *</label>
                        <input type="tel" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" required
                               inputmode="tel" autocomplete="tel" data-ph-phone
                               placeholder="09XXXXXXXXX or +639XXXXXXXXX"
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Gender</label>
                        <select name="gender" class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                            <option value="">Select Gender</option>
                            <option value="Female" {{ old('gender', $user->gender) === 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Male" {{ old('gender', $user->gender) === 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Other" {{ old('gender', $user->gender) === 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Assigned Health Station / Address</label>
                    <input type="text" name="address" value="{{ old('address', $user->address) }}"
                           placeholder="e.g. Barangay Honrado Health Center, San Jose, Antique"
                           class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Profile Photo (Optional)</label>
                    <input type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp" onchange="previewNursePhoto(this)"
                           class="w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-200 rounded-xl p-1.5 bg-gray-50">
                    <p class="text-[11px] text-gray-400 mt-1">JPG, PNG, or WEBP up to 4MB.</p>
                </div>

                <div class="pt-2 border-t border-gray-100 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-[#0c2d48] hover:bg-[#144976] text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-1.5 cursor-pointer">
                        <span class="material-icons-outlined" style="font-size:16px;">save</span>
                        <span>Save Profile Changes</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Right (1 col): Account Security & Password --}}
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-slate-50/60 flex items-center gap-2.5">
                    <span class="material-icons-outlined text-[#0c2d48]" style="font-size:20px;">lock</span>
                    <div>
                        <h3 class="font-bold text-gray-900 text-sm">Change Password</h3>
                        <p class="text-[11px] text-gray-500">Keep your Nurse account secure.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('nurse.profile.password') }}" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Current Password *</label>
                        <input type="password" name="current_password" required placeholder="Enter current password"
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">New Password *</label>
                        <input type="password" name="new_password" required minlength="8" placeholder="Minimum 8 characters"
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Confirm New Password *</label>
                        <input type="password" name="new_password_confirmation" required minlength="8" placeholder="Re-enter new password"
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span class="material-icons-outlined" style="font-size:16px;">key</span>
                        <span>Update Password</span>
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-2">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-800">
                    <span class="material-icons-outlined text-emerald-600" style="font-size:18px;">verified_user</span>
                    <span>Single-Device Session Active</span>
                </div>
                <p class="text-xs text-gray-500 leading-relaxed">
                    Your Nurse account is protected by single-device login enforcement. Signing in from another device automatically logs out this session.
                </p>
            </div>
        </div>
    </div>
</div>

@include('dashboards.partials.phone-otp-modal')

<script src="{{ asset('js/ph-phone.js') }}"></script>
<script>
    function previewNursePhoto(input) {
        if (!input.files || !input.files[0]) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.getElementById('nurseProfilePreview');
            const initials = document.getElementById('nurseProfileInitials');
            if (img) {
                img.src = e.target.result;
                img.classList.remove('hidden');
            }
            if (initials) {
                initials.classList.add('hidden');
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
</script>
@endsection
