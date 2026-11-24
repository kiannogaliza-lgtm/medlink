<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl border border-gray-100 p-6 relative">
      <button
        type="button"
        @click="cancelChange"
        :disabled="isSubmitting"
        aria-label="Close modal"
        class="absolute top-4 right-4 w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition-colors cursor-pointer disabled:opacity-50"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <div class="mb-5 pr-8">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold uppercase tracking-wider">
          SMS Verification
        </span>
        <h3 class="text-lg font-extrabold text-gray-900 mt-2">Confirm your new number</h3>
        <p class="text-xs text-gray-600 mt-1">
          We sent a 6-digit code to <span class="font-bold text-gray-900">{{ masked }}</span>
        </p>
      </div>

      <div v-if="statusMsg" class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 font-medium">
        {{ statusMsg }}
      </div>

      <div v-if="errorMsg" class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 font-medium">
        {{ errorMsg }}
      </div>

      <form @submit.prevent="verifyCode" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">6-Digit Code</label>
          <input
            v-model="code"
            type="text"
            inputmode="numeric"
            autocomplete="one-time-code"
            maxlength="6"
            required
            placeholder="000000"
            @input="code = code.replace(/\D/g, '').slice(0, 6)"
            class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-center text-2xl font-extrabold tracking-[0.4em] text-gray-900 placeholder-gray-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500"
          >
        </div>

        <button
          type="submit"
          :disabled="isSubmitting || code.length !== 6"
          class="w-full py-3 px-4 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md cursor-pointer"
        >
          {{ isSubmitting ? 'Verifying...' : 'Verify' }}
        </button>
      </form>

      <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between gap-3">
        <button
          type="button"
          @click="resendCode"
          :disabled="isResending || countdown > 0"
          class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 disabled:text-gray-400 disabled:cursor-not-allowed cursor-pointer"
        >
          <span v-if="countdown > 0">Resend in {{ formattedCountdown }}</span>
          <span v-else>{{ isResending ? 'Sending...' : 'Resend code' }}</span>
        </button>

        <button
          type="button"
          @click="cancelChange"
          :disabled="isSubmitting"
          class="text-xs font-semibold text-gray-500 hover:text-gray-700 cursor-pointer disabled:opacity-50"
        >
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  masked: { type: String, default: '' },
  initialResendSeconds: { type: Number, default: 60 },
});

const emit = defineEmits(['verified', 'cancelled']);

const code = ref('');
const errorMsg = ref('');
const statusMsg = ref('');
const isSubmitting = ref(false);
const isResending = ref(false);
const countdown = ref(0);
let timerId = null;

const getCsrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const formattedCountdown = computed(() => {
  const sec = countdown.value;
  if (sec >= 60) {
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    return `${m}m ${String(s).padStart(2, '0')}s`;
  }
  return `${sec}s`;
});

const startCountdown = (seconds) => {
  if (timerId) clearInterval(timerId);
  countdown.value = Math.max(0, Number(seconds) || 0);
  if (countdown.value <= 0) return;

  timerId = setInterval(() => {
    countdown.value -= 1;
    if (countdown.value <= 0) {
      clearInterval(timerId);
      timerId = null;
    }
  }, 1000);
};

watch(
  () => props.show,
  (visible) => {
    if (visible) {
      code.value = '';
      errorMsg.value = '';
      statusMsg.value = '';
      startCountdown(props.initialResendSeconds);
    } else if (timerId) {
      clearInterval(timerId);
      timerId = null;
    }
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  if (timerId) clearInterval(timerId);
});

const verifyCode = async () => {
  errorMsg.value = '';
  statusMsg.value = '';
  isSubmitting.value = true;

  try {
    const res = await fetch('/account/phone/verify', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrf(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ code: code.value }),
    });

    const data = await res.json();
    if (!res.ok) {
      errorMsg.value = data.message || 'Unable to verify code.';
      if (data.retry_after) {
        startCountdown(data.retry_after);
      }
      return;
    }

    emit('verified', { user: data.user, message: data.message });
  } catch (e) {
    errorMsg.value = 'Network error. Please try again.';
  } finally {
    isSubmitting.value = false;
  }
};

const resendCode = async () => {
  errorMsg.value = '';
  statusMsg.value = '';
  isResending.value = true;

  try {
    const res = await fetch('/account/phone/resend', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrf(),
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    const data = await res.json();
    if (!res.ok) {
      errorMsg.value = data.message || 'Could not resend code.';
      if (data.retry_after) {
        startCountdown(data.retry_after);
      }
      return;
    }

    statusMsg.value = data.message || 'A new verification code has been sent.';
    startCountdown(data.resend_available_in || 60);
  } catch (e) {
    errorMsg.value = 'Network error. Please try again.';
  } finally {
    isResending.value = false;
  }
};

const cancelChange = async () => {
  isSubmitting.value = true;
  try {
    const res = await fetch('/account/phone/cancel', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrf(),
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    const data = await res.json().catch(() => ({}));
    emit('cancelled', { user: data.user || null, message: data.message || 'Pending phone number change cancelled.' });
  } catch (e) {
    emit('cancelled', { user: null, message: 'Pending phone number change cancelled.' });
  } finally {
    isSubmitting.value = false;
  }
};
</script>
