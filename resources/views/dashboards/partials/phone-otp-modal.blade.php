@php
    $otpOpen = (bool) session('phone_change_otp_required', false);
    $otpMasked = session('phone_change_masked', '');
    $otpResendIn = (int) session('phone_change_resend_in', 60);
@endphp

<div id="nursePhoneOtpModal"
     class="fixed inset-0 z-50 {{ $otpOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="nursePhoneOtpTitle">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl border border-gray-100 p-6 relative">
        <button type="button"
                onclick="nurseCancelPhoneOtp()"
                aria-label="Close modal"
                class="absolute top-4 right-4 w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition-colors cursor-pointer">
            <span class="material-icons-outlined" style="font-size:18px;">close</span>
        </button>

        <div class="mb-5 pr-8">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold uppercase tracking-wider">
                SMS Verification
            </span>
            <h3 id="nursePhoneOtpTitle" class="text-lg font-extrabold text-gray-900 mt-2">Confirm your new number</h3>
            <p class="text-xs text-gray-600 mt-1">
                We sent a 6-digit code to <span id="nursePhoneOtpMasked" class="font-bold text-gray-900">{{ $otpMasked }}</span>
            </p>
        </div>

        <div id="nursePhoneOtpStatus" class="hidden mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 font-medium"></div>
        <div id="nursePhoneOtpError" class="hidden mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 font-medium"></div>

        <form id="nursePhoneOtpForm" onsubmit="nurseVerifyPhoneOtp(event)" class="space-y-4">
            <div>
                <label for="nursePhoneOtpCode" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">6-Digit Code</label>
                <input id="nursePhoneOtpCode"
                       type="text"
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       maxlength="6"
                       required
                       placeholder="000000"
                       oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)"
                       class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-center text-2xl font-extrabold tracking-[0.4em] text-gray-900 placeholder-gray-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500">
            </div>

            <button id="nursePhoneOtpSubmit"
                    type="submit"
                    class="w-full py-3 px-4 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 transition-all shadow-md cursor-pointer">
                Verify
            </button>
        </form>

        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between gap-3">
            <button id="nursePhoneOtpResend"
                    type="button"
                    onclick="nurseResendPhoneOtp()"
                    data-seconds="{{ $otpResendIn }}"
                    class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 disabled:text-gray-400 disabled:cursor-not-allowed cursor-pointer">
                Resend code
            </button>

            <button type="button"
                    onclick="nurseCancelPhoneOtp()"
                    class="text-xs font-semibold text-gray-500 hover:text-gray-700 cursor-pointer">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        let timer = null;

        function csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        function formatSec(sec) {
            if (sec >= 60) {
                const m = Math.floor(sec / 60);
                const s = sec % 60;
                return m + 'm ' + String(s).padStart(2, '0') + 's';
            }
            return sec + 's';
        }

        function startResendTimer(seconds) {
            const btn = document.getElementById('nursePhoneOtpResend');
            if (!btn) return;
            if (timer) clearInterval(timer);

            let remaining = Math.max(0, parseInt(seconds || '0', 10));
            if (remaining <= 0) {
                btn.disabled = false;
                btn.textContent = 'Resend code';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Resend in ' + formatSec(remaining);
            timer = setInterval(function () {
                remaining -= 1;
                if (remaining <= 0) {
                    clearInterval(timer);
                    timer = null;
                    btn.disabled = false;
                    btn.textContent = 'Resend code';
                } else {
                    btn.textContent = 'Resend in ' + formatSec(remaining);
                }
            }, 1000);
        }

        function showFeedback(type, message) {
            const statusEl = document.getElementById('nursePhoneOtpStatus');
            const errorEl = document.getElementById('nursePhoneOtpError');
            statusEl.classList.add('hidden');
            errorEl.classList.add('hidden');
            if (type === 'status' && message) {
                statusEl.textContent = message;
                statusEl.classList.remove('hidden');
            } else if (type === 'error' && message) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
            }
        }

        window.nurseVerifyPhoneOtp = async function (event) {
            event.preventDefault();
            const codeInput = document.getElementById('nursePhoneOtpCode');
            const submitBtn = document.getElementById('nursePhoneOtpSubmit');
            showFeedback(null, '');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Verifying...';

            try {
                const res = await fetch("{{ route('account.phone.verify') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ code: codeInput.value })
                });
                const data = await res.json();
                if (!res.ok) {
                    showFeedback('error', data.message || 'Unable to verify code.');
                    if (data.retry_after) startResendTimer(data.retry_after);
                    return;
                }
                window.location.reload();
            } catch (e) {
                showFeedback('error', 'Network error. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Verify';
            }
        };

        window.nurseResendPhoneOtp = async function () {
            const btn = document.getElementById('nursePhoneOtpResend');
            showFeedback(null, '');
            btn.disabled = true;

            try {
                const res = await fetch("{{ route('account.phone.resend') }}", {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                if (!res.ok) {
                    showFeedback('error', data.message || 'Could not resend code.');
                    if (data.retry_after) startResendTimer(data.retry_after);
                    else btn.disabled = false;
                    return;
                }
                showFeedback('status', data.message || 'A new verification code has been sent.');
                startResendTimer(data.resend_available_in || 60);
            } catch (e) {
                showFeedback('error', 'Network error. Please try again.');
                btn.disabled = false;
            }
        };

        window.nurseCancelPhoneOtp = async function () {
            try {
                await fetch("{{ route('account.phone.cancel') }}", {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
            } finally {
                const modal = document.getElementById('nursePhoneOtpModal');
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
                window.location.reload();
            }
        };

        @if($otpOpen)
            startResendTimer({{ $otpResendIn }});
        @endif
    })();
</script>
