<template>
  <div class="space-y-6">
    <!-- Top Action Header -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.06)]">
      <div>
        <h2 class="text-xl font-bold text-[#1a5a89] flex items-center gap-2.5">
          <svg class="w-6 h-6 text-[#3ac1eb]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          Nurse Account Management
        </h2>
        <p class="text-xs text-gray-500 mt-1">
          Barangay Health Workers (BHW) can create, register, and manage official Nurse accounts for Barangay Honrado Health Center.
        </p>
      </div>
      <button @click="openCreateModal"
              class="flex items-center gap-2 px-5 py-2.5 bg-[#1a5a89] hover:bg-[#154d78] text-white text-xs font-bold rounded-xl shadow-md shadow-[#1a5a89]/20 transition-all cursor-pointer">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        <span>Add Nurse Account</span>
      </button>
    </div>

    <!-- Stats row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.04)]">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Nurse Accounts</p>
        <p class="text-3xl font-extrabold text-[#1a5a89] mt-2">{{ nursesList.length }}</p>
      </div>
      <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.04)]">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Nurses</p>
        <p class="text-3xl font-extrabold text-emerald-600 mt-2">{{ activeNursesCount }}</p>
      </div>
      <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.04)]">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Designation</p>
        <p class="text-sm font-bold text-gray-700 mt-2 flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
          Barangay Honrado Health Center
        </p>
      </div>
    </div>

    <!-- Alert / Toast Messages -->
    <div v-if="successMsg" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between">
      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>{{ successMsg }}</span>
      </div>
      <button @click="successMsg = ''" class="text-emerald-600 hover:text-emerald-800 font-bold">&times;</button>
    </div>

    <!-- Nurses Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.06)] overflow-hidden">
      <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
          <span>Registered Nurse Accounts</span>
          <span class="text-xs font-semibold text-[#1a5a89] bg-blue-50 px-2 py-0.5 rounded-full">{{ nursesList.length }}</span>
        </h3>
        <div class="relative w-full sm:w-64">
          <input v-model="search" type="text" placeholder="Search nurse name or phone..."
                 class="w-full border border-gray-200 rounded-xl pl-9 pr-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-[#3ac1eb]/40 focus:border-[#3ac1eb]">
          <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="bg-gray-50/70 border-b border-gray-100 text-gray-500 uppercase tracking-wider text-[10px]">
              <th class="py-3 px-4">Nurse Name</th>
              <th class="py-3 px-4">Contact Number</th>
              <th class="py-3 px-4">Age / Category</th>
              <th class="py-3 px-4">Role & Status</th>
              <th class="py-3 px-4">Registered Date</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="nurse in filteredNurses" :key="nurse.id" class="hover:bg-blue-50/20 transition-colors">
              <td class="py-3 px-4 whitespace-nowrap">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 font-bold flex items-center justify-center shrink-0">
                    {{ nurse.first_name ? nurse.first_name[0] : 'N' }}
                  </div>
                  <div>
                    <p class="font-bold text-gray-900">{{ nurse.first_name }} {{ nurse.last_name }}</p>
                    <p class="text-[10px] text-gray-400">Nurse ID #{{ nurse.id }}</p>
                  </div>
                </div>
              </td>
              <td class="py-3 px-4 whitespace-nowrap font-medium text-gray-700">
                {{ nurse.phone_number }}
              </td>
              <td class="py-3 px-4 whitespace-nowrap">
                <span v-if="nurse.age" class="font-semibold text-gray-800">{{ nurse.age }} yrs</span>
                <span v-else class="text-gray-400">N/A</span>
              </td>
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                  <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                  Nurse
                </span>
                <span :class="['inline-flex items-center ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold border', (nurse.status || 'active') === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200']">
                  {{ (nurse.status || 'active') === 'active' ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="py-3 px-4 whitespace-nowrap text-gray-500">
                {{ nurse.created_at ? new Date(nurse.created_at).toLocaleDateString() : 'N/A' }}
              </td>
              <td class="py-3 px-4 whitespace-nowrap text-right">
                <button @click="confirmDeleteNurse(nurse)"
                        class="text-red-500 hover:text-red-700 p-1 rounded-lg hover:bg-red-50 transition-colors cursor-pointer"
                        title="Delete nurse account">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </td>
            </tr>
            <tr v-if="filteredNurses.length === 0">
              <td colspan="6" class="py-12 text-center text-gray-400">
                No nurse accounts found. Click "Add Nurse Account" above to register one.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CREATE NURSE MODAL -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-100">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
          <div class="flex items-center gap-2">
            <span class="p-2 rounded-xl bg-blue-100 text-[#1a5a89]">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
              </svg>
            </span>
            <h3 class="font-bold text-gray-900 text-sm">Create Nurse Account</h3>
          </div>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg cursor-pointer">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitCreateNurse" class="p-6 space-y-4">
          <div v-if="formError" class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold">
            {{ formError }}
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
              <input v-model="form.first_name" type="text" required placeholder="e.g. Maria"
                     class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-[#3ac1eb]/40 focus:border-[#3ac1eb]">
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
              <input v-model="form.last_name" type="text" required placeholder="e.g. Santos"
                     class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-[#3ac1eb]/40 focus:border-[#3ac1eb]">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
              <input v-model="form.phone_number" type="tel" required maxlength="11" placeholder="09XXXXXXXXX"
                     @input="formatPhoneInput"
                     class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-[#3ac1eb]/40 focus:border-[#3ac1eb]">
              <p class="text-[10px] text-gray-400 mt-0.5">11-digit starting with 09</p>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Age</label>
              <input v-model.number="form.age" type="number" min="18" max="100" placeholder="e.g. 28"
                     class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-[#3ac1eb]/40 focus:border-[#3ac1eb]">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
              <input v-model="form.password" type="password" required minlength="8" placeholder="••••••••"
                     class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-[#3ac1eb]/40 focus:border-[#3ac1eb]">
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
              <input v-model="form.password_confirmation" type="password" required minlength="8" placeholder="••••••••"
                     class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-[#3ac1eb]/40 focus:border-[#3ac1eb]">
            </div>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
            <button type="button" @click="closeModal" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl cursor-pointer">
              Cancel
            </button>
            <button type="submit" :disabled="submitting"
                    class="px-5 py-2 bg-[#1a5a89] hover:bg-[#154d78] text-white text-xs font-bold rounded-xl shadow-md transition-all disabled:opacity-50 cursor-pointer">
              {{ submitting ? 'Creating...' : 'Create Nurse Account' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  nurseAccounts: {
    type: Array,
    default: () => []
  },
  createNurseUrl: {
    type: String,
    default: '/bhw/nurses'
  }
});

const nursesList = ref([...props.nurseAccounts]);
const search = ref('');
const showModal = ref(false);
const submitting = ref(false);
const formError = ref('');
const successMsg = ref('');

const form = ref({
  first_name: '',
  last_name: '',
  phone_number: '',
  age: null,
  password: '',
  password_confirmation: ''
});

const activeNursesCount = computed(() => {
  return nursesList.value.filter(n => n.status === 'active' || !n.status).length;
});

const filteredNurses = computed(() => {
  if (!search.value) return nursesList.value;
  const q = search.value.toLowerCase();
  return nursesList.value.filter(n => 
    (n.first_name && n.first_name.toLowerCase().includes(q)) ||
    (n.last_name && n.last_name.toLowerCase().includes(q)) ||
    (n.phone_number && n.phone_number.includes(q))
  );
});

function openCreateModal() {
  form.value = {
    first_name: '',
    last_name: '',
    phone_number: '',
    age: null,
    password: '',
    password_confirmation: ''
  };
  formError.value = '';
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  formError.value = '';
}

function formatPhoneInput(e) {
  let val = e.target.value.replace(/[^0-9]/g, '');
  if (val.length >= 1 && val[0] !== '0') val = '';
  else if (val.length >= 2 && val.substring(0, 2) !== '09') val = '0';
  form.value.phone_number = val.slice(0, 11);
}

async function submitCreateNurse() {
  formError.value = '';
  if (form.value.password !== form.value.password_confirmation) {
    formError.value = 'Passwords do not match.';
    return;
  }
  if (!form.value.phone_number.startsWith('09') || form.value.phone_number.length !== 11) {
    formError.value = 'Phone number must be an 11-digit Philippine number starting with 09.';
    return;
  }

  submitting.value = true;
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const res = await fetch(props.createNurseUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(form.value)
    });

    const data = await res.json();
    if (!res.ok) {
      formError.value = data.message || 'Failed to create nurse account.';
    } else {
      if (data.nurse) {
        nursesList.value.unshift(data.nurse);
      }
      successMsg.value = data.message || 'Nurse account created successfully.';
      closeModal();
    }
  } catch (err) {
    console.error(err);
    formError.value = 'Network error. Please try again.';
  } finally {
    submitting.value = false;
  }
}

async function confirmDeleteNurse(nurse) {
  if (!confirm(`Are you sure you want to remove Nurse ${nurse.first_name} ${nurse.last_name}?`)) {
    return;
  }
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const res = await fetch(`/bhw/nurses/${nurse.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    });
    const data = await res.json();
    if (res.ok) {
      nursesList.value = nursesList.value.filter(n => n.id !== nurse.id);
      successMsg.value = data.message || 'Nurse account deleted.';
    } else {
      alert(data.message || 'Failed to delete nurse.');
    }
  } catch (err) {
    console.error(err);
    alert('An error occurred.');
  }
}
</script>
