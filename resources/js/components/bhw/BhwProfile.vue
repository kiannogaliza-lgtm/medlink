<template>
  <div class="max-w-5xl mx-auto space-y-6 pb-8">
    <!-- Toast / Status Banners -->
    <div v-if="successMessage" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-3.5 rounded-2xl flex items-center justify-between shadow-sm">
      <div class="flex items-center gap-2.5 text-sm font-semibold">
        <span class="material-icons-outlined text-emerald-600" style="font-size:20px;">check_circle</span>
        <span>{{ successMessage }}</span>
      </div>
      <button type="button" @click="successMessage = ''" class="text-emerald-600 hover:text-emerald-800 cursor-pointer">
        <span class="material-icons-outlined" style="font-size:18px;">close</span>
      </button>
    </div>

    <div v-if="errorMessage" class="bg-red-50 border border-red-200 text-red-800 px-5 py-3.5 rounded-2xl flex items-center justify-between shadow-sm">
      <div class="flex items-center gap-2.5 text-sm font-semibold">
        <span class="material-icons-outlined text-red-600" style="font-size:20px;">error</span>
        <span>{{ errorMessage }}</span>
      </div>
      <button type="button" @click="errorMessage = ''" class="text-red-600 hover:text-red-800 cursor-pointer">
        <span class="material-icons-outlined" style="font-size:18px;">close</span>
      </button>
    </div>

    <!-- BHW Field Staff Identity Banner (Distinct from Resident Profile) -->
    <div class="rounded-2xl overflow-hidden shadow-md border border-slate-200 bg-white">
      <div class="bg-gradient-to-r from-[#0c2d48] via-[#0f4c5c] to-[#117a65] px-6 py-7 sm:px-8 text-white">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">
          <div class="flex items-center gap-5">
            <div class="relative shrink-0">
              <img v-if="photoPreviewUrl || user.profile_photo"
                   :src="photoPreviewUrl || ('/storage/' + user.profile_photo)"
                   :alt="user.first_name"
                   class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl object-cover border-4 border-white/25 shadow-lg">
              <div v-else
                   class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-white/15 border-4 border-white/25 flex items-center justify-center text-white font-black text-2xl sm:text-3xl shadow-lg">
                {{ initials }}
              </div>
              <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-400 border-2 border-[#0c2d48]" title="Online"></span>
            </div>

            <div>
              <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-md bg-white/15 text-teal-100 text-[11px] font-bold uppercase tracking-wider">
                  Barangay Health Worker &bull; Field &amp; Home Care
                </span>
                <span class="px-2.5 py-0.5 rounded-md bg-emerald-400/20 text-emerald-100 text-[11px] font-bold uppercase tracking-wider">
                  {{ (user.status || 'Active').toUpperCase() }}
                </span>
              </div>
              <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight mt-1.5">
                {{ user.first_name }} {{ user.last_name }}
              </h2>
              <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-teal-50/90 mt-1.5">
                <span class="inline-flex items-center gap-1">
                  <span class="material-icons-outlined" style="font-size:14px;">badge</span>
                  Staff ID: <strong>BHW-{{ currentYear }}-{{ String(user.id || 0).padStart(3, '0') }}</strong>
                </span>
                <span class="inline-flex items-center gap-1">
                  <span class="material-icons-outlined" style="font-size:14px;">call</span>
                  {{ displayPhone(user.phone_number) }}
                </span>
                <span class="inline-flex items-center gap-1">
                  <span class="material-icons-outlined" style="font-size:14px;">location_on</span>
                  {{ user.address || 'Barangay Honrado' }}
                </span>
              </div>
            </div>
          </div>

          <div class="text-left sm:text-right text-xs text-teal-50/85 shrink-0">
            <div class="uppercase tracking-wider text-[10px] font-bold text-teal-200/80">Assigned Barangay</div>
            <div class="font-bold text-white text-sm mt-0.5">Barangay Honrado</div>
            <div class="mt-0.5">Field Operations Portal</div>
          </div>
        </div>
      </div>

      <!-- Field Activity Summary -->
      <div class="grid grid-cols-2 lg:grid-cols-4 divide-y lg:divide-y-0 lg:divide-x divide-gray-100 bg-slate-50/70">
        <div class="p-4 sm:p-5 flex items-center gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center shrink-0">
            <span class="material-icons-outlined" style="font-size:20px;">task_alt</span>
          </div>
          <div>
            <div class="text-xl font-black text-gray-900">{{ dashboardData.completedServices ?? 0 }}</div>
            <div class="text-[11px] font-semibold text-gray-500">Completed Visits</div>
          </div>
        </div>
        <div class="p-4 sm:p-5 flex items-center gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
            <span class="material-icons-outlined" style="font-size:20px;">pending_actions</span>
          </div>
          <div>
            <div class="text-xl font-black text-gray-900">{{ dashboardData.pendingTasks ?? 0 }}</div>
            <div class="text-[11px] font-semibold text-gray-500">Pending Tasks</div>
          </div>
        </div>
        <div class="p-4 sm:p-5 flex items-center gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
            <span class="material-icons-outlined" style="font-size:20px;">directions_walk</span>
          </div>
          <div>
            <div class="text-xl font-black text-gray-900">{{ dashboardData.walkInPatientsToday ?? 0 }}</div>
            <div class="text-[11px] font-semibold text-gray-500">Walk-Ins Today</div>
          </div>
        </div>
        <div class="p-4 sm:p-5 flex items-center gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
            <span class="material-icons-outlined" style="font-size:20px;">local_shipping</span>
          </div>
          <div>
            <div class="text-xl font-black text-gray-900">{{ completedDeliveriesCount }}</div>
            <div class="text-[11px] font-semibold text-gray-500">Deliveries Completed</div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2-Column Staff Settings -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left (2 cols): BHW Account Details -->
      <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-slate-50/60 flex items-center gap-2.5">
          <span class="material-icons-outlined text-[#0f4c5c]" style="font-size:20px;">badge</span>
          <div>
            <h3 class="font-bold text-gray-900 text-sm">BHW Staff Information</h3>
            <p class="text-[11px] text-gray-500">Keep your contact details, assigned area, and staff photo up to date.</p>
          </div>
        </div>

        <form @submit.prevent="submitProfile" class="p-6 space-y-5">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">First Name *</label>
              <input v-model="form.first_name" type="text" required
                     class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-600">
              <span v-if="errors.first_name" class="text-xs text-red-600 mt-1 block">{{ errors.first_name[0] }}</span>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Last Name *</label>
              <input v-model="form.last_name" type="text" required
                     class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-600">
              <span v-if="errors.last_name" class="text-xs text-red-600 mt-1 block">{{ errors.last_name[0] }}</span>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Mobile Number *</label>
              <input v-model="form.phone_number" type="tel" required :maxlength="maxLengthFor(form.phone_number)" inputmode="tel" autocomplete="tel"
                     @input="handlePhoneInput" @blur="phoneTouched = true" placeholder="09XXXXXXXXX or +639XXXXXXXXX"
                     :class="['w-full px-3.5 py-2.5 bg-gray-50 border rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-600', phoneInvalidOnBlur ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-gray-200']">
              <span v-if="errors.phone_number" class="text-xs text-red-600 mt-1 block">{{ errors.phone_number[0] }}</span>
              <span v-else-if="phoneInvalidOnBlur" class="text-xs text-rose-500 mt-1 block font-medium">Use 09XXXXXXXXX or +639XXXXXXXXX</span>
              <span v-else-if="phoneHint.text" :class="['text-xs mt-1 block font-medium', phoneHint.error ? 'text-rose-500' : 'text-slate-500']">{{ phoneHint.text }}</span>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Gender</label>
              <select v-model="form.gender"
                      class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-600">
                <option value="">Select Gender</option>
                <option value="Female">Female</option>
                <option value="Male">Male</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Assigned Purok / Station Address</label>
            <input v-model="form.address" type="text" placeholder="e.g. Purok 2, Barangay Honrado"
                   class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-600">
            <span v-if="errors.address" class="text-xs text-red-600 mt-1 block">{{ errors.address[0] }}</span>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Profile Photo (Optional)</label>
            <input type="file" accept="image/png,image/jpeg,image/webp" @change="handlePhotoSelect"
                   class="w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 cursor-pointer border border-gray-200 rounded-xl p-1.5 bg-gray-50">
            <p class="text-[11px] text-gray-400 mt-1">JPG, PNG, or WEBP up to 4MB.</p>
          </div>

          <div class="pt-2 border-t border-gray-100 flex justify-end">
            <button type="submit" :disabled="isSavingProfile"
                    class="px-5 py-2.5 bg-[#0f4c5c] hover:bg-[#0c2d48] text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
              <span class="material-icons-outlined" style="font-size:16px;">save</span>
              <span>{{ isSavingProfile ? 'Saving...' : 'Save Profile Changes' }}</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Right (1 col): Password & Security -->
      <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 bg-slate-50/60 flex items-center gap-2.5">
            <span class="material-icons-outlined text-[#0f4c5c]" style="font-size:20px;">lock</span>
            <div>
              <h3 class="font-bold text-gray-900 text-sm">Change Password</h3>
              <p class="text-[11px] text-gray-500">Update your BHW login credentials.</p>
            </div>
          </div>

          <form @submit.prevent="submitPassword" class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Current Password *</label>
              <input v-model="passwordForm.current_password" type="password" required placeholder="Enter current password"
                     class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-600">
              <span v-if="passwordErrors.current_password" class="text-xs text-red-600 mt-1 block">{{ passwordErrors.current_password[0] }}</span>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">New Password *</label>
              <input v-model="passwordForm.new_password" type="password" required minlength="8" placeholder="Minimum 8 characters"
                     class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-600">
              <span v-if="passwordErrors.new_password" class="text-xs text-red-600 mt-1 block">{{ passwordErrors.new_password[0] }}</span>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Confirm New Password *</label>
              <input v-model="passwordForm.new_password_confirmation" type="password" required minlength="8" placeholder="Re-enter new password"
                     class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-600">
            </div>

            <button type="submit" :disabled="isSavingPassword"
                    class="w-full py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
              <span class="material-icons-outlined" style="font-size:16px;">key</span>
              <span>{{ isSavingPassword ? 'Updating...' : 'Update Password' }}</span>
            </button>
          </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-2">
          <div class="flex items-center gap-2 text-xs font-bold text-gray-800">
            <span class="material-icons-outlined text-emerald-600" style="font-size:18px;">verified_user</span>
            <span>Single-Device Session Active</span>
          </div>
          <p class="text-xs text-gray-500 leading-relaxed">
            Your BHW account is protected by single-device login enforcement. Signing in on another device automatically logs out this session.
          </p>
        </div>
      </div>
    </div>

    <PhoneOtpModal
      :show="showPhoneOtpModal"
      :masked="phoneOtpMasked"
      :initial-resend-seconds="phoneOtpResendIn"
      @verified="onPhoneOtpVerified"
      @cancelled="onPhoneOtpCancelled"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { displayPhone, hintFor, isValid, maxLengthFor, sanitizeInput } from '../../utils/phPhone';
import PhoneOtpModal from '../shared/PhoneOtpModal.vue';

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  dashboardData: {
    type: Object,
    default: () => ({})
  },
  medicineRequests: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['profile-updated']);

const currentYear = new Date().getFullYear();
const isSavingProfile = ref(false);
const isSavingPassword = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const errors = ref({});
const passwordErrors = ref({});
const selectedPhotoFile = ref(null);
const photoPreviewUrl = ref('');
const showPhoneOtpModal = ref(false);
const phoneOtpMasked = ref('');
const phoneOtpResendIn = ref(60);

const form = ref({
  first_name: props.user?.first_name || '',
  last_name: props.user?.last_name || '',
  phone_number: props.user?.phone_number || '',
  gender: props.user?.gender || '',
  address: props.user?.address || ''
});

watch(() => props.user, (u) => {
  if (!u) return;
  form.value.first_name = u.first_name || '';
  form.value.last_name = u.last_name || '';
  form.value.phone_number = u.phone_number || '';
  form.value.gender = u.gender || '';
  form.value.address = u.address || '';
}, { deep: true });

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
});

const initials = computed(() => {
  const f = (props.user?.first_name || 'B').charAt(0).toUpperCase();
  const l = (props.user?.last_name || 'W').charAt(0).toUpperCase();
  return f + l;
});

const completedDeliveriesCount = computed(() => {
  return (props.medicineRequests || []).filter(r => r.status === 'Completed').length;
});

const phoneTouched = ref(false);
const phoneHint = computed(() => hintFor(form.value.phone_number));
const phoneInvalidOnBlur = computed(() => {
  return phoneTouched.value && form.value.phone_number && !isValid(form.value.phone_number);
});

const handlePhoneInput = (e) => {
  form.value.phone_number = sanitizeInput(e.target.value);
};

const handlePhotoSelect = (e) => {
  const file = e.target.files?.[0];
  if (!file) return;
  selectedPhotoFile.value = file;
  const reader = new FileReader();
  reader.onload = (ev) => {
    photoPreviewUrl.value = ev.target?.result || '';
  };
  reader.readAsDataURL(file);
};

const getCsrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const submitProfile = async () => {
  isSavingProfile.value = true;
  errors.value = {};
  successMessage.value = '';
  errorMessage.value = '';

  try {
    const fd = new FormData();
    fd.append('first_name', form.value.first_name);
    fd.append('last_name', form.value.last_name);
    fd.append('phone_number', form.value.phone_number);
    fd.append('gender', form.value.gender || '');
    fd.append('address', form.value.address || '');
    if (selectedPhotoFile.value) {
      fd.append('profile_photo', selectedPhotoFile.value);
    }

    const res = await fetch('/bhw/profile', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrf(),
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: fd
    });

    const data = await res.json();
    if (!res.ok) {
      if (res.status === 422 && data.errors) {
        errors.value = data.errors;
        errorMessage.value = 'Please correct the highlighted fields.';
      } else {
        errorMessage.value = data.message || 'Failed to update profile.';
      }
      return;
    }

    selectedPhotoFile.value = null;
    photoPreviewUrl.value = '';
    successMessage.value = data.message || 'BHW staff profile updated successfully.';
    if (data.user) {
      emit('profile-updated', data.user);
    }
    if (data.otp_required) {
      phoneOtpMasked.value = data.masked || '';
      phoneOtpResendIn.value = data.resend_available_in || 60;
      showPhoneOtpModal.value = true;
    }
  } catch (e) {
    errorMessage.value = 'Network error. Please try again.';
  } finally {
    isSavingProfile.value = false;
  }
};

const onPhoneOtpVerified = ({ user, message }) => {
  showPhoneOtpModal.value = false;
  successMessage.value = message || 'Phone number verified and updated successfully!';
  if (user) {
    form.value.phone_number = user.phone_number || '';
    emit('profile-updated', user);
  }
};

const onPhoneOtpCancelled = ({ user, message }) => {
  showPhoneOtpModal.value = false;
  successMessage.value = message || 'Pending phone number change cancelled.';
  if (user) {
    form.value.phone_number = user.phone_number || '';
    emit('profile-updated', user);
  }
};

const submitPassword = async () => {
  isSavingPassword.value = true;
  passwordErrors.value = {};
  successMessage.value = '';
  errorMessage.value = '';

  try {
    const res = await fetch('/bhw/profile/password', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrf(),
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(passwordForm.value)
    });

    const data = await res.json();
    if (!res.ok) {
      if (res.status === 422 && data.errors) {
        passwordErrors.value = data.errors;
        errorMessage.value = data.message || 'Please check your password inputs.';
      } else {
        errorMessage.value = data.message || 'Failed to update password.';
      }
      return;
    }

    passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' };
    successMessage.value = data.message || 'Password updated successfully.';
  } catch (e) {
    errorMessage.value = 'Network error. Please try again.';
  } finally {
    isSavingPassword.value = false;
  }
};
</script>
