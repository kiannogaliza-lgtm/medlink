(function () {
    'use strict';

    const INVALID_CLASSES = ['border-rose-500', 'ring-2', 'ring-rose-500/20'];

    function clean(raw) {
        return String(raw || '').trim().replace(/[\s\-\.\(\)]+/g, '');
    }

    function sanitize(raw) {
        const str = String(raw || '');
        const hasLeadingPlus = str.trimStart().startsWith('+');
        const digitsOnly = str.replace(/\D/g, '');
        let value = hasLeadingPlus ? '+' + digitsOnly : digitsOnly;

        if (value.startsWith('+')) {
            return value.slice(0, 13);
        }

        if (value.startsWith('0')) {
            return value.slice(0, 11);
        }

        return value.slice(0, 13);
    }

    function isValid(raw) {
        const c = clean(raw);
        return /^09[0-9]{9}$/.test(c) || /^\+639[0-9]{9}$/.test(c);
    }

    function getHintState(raw) {
        const val = clean(raw);

        if (!val) {
            return { text: '', error: false };
        }

        if (val.startsWith('+')) {
            if (val === '+' || val === '+6' || val === '+63' || val.startsWith('+639')) {
                return { text: 'Format: +639XXXXXXXXX', error: false };
            }
            return { text: 'Use 09XXXXXXXXX or +639XXXXXXXXX', error: true };
        }

        if (val.startsWith('0')) {
            if (val === '0' || val.startsWith('09')) {
                return { text: 'Format: 09XXXXXXXXX', error: false };
            }
            return { text: 'Use 09XXXXXXXXX or +639XXXXXXXXX', error: true };
        }

        return { text: 'Use 09XXXXXXXXX or +639XXXXXXXXX', error: true };
    }

    function ensureHintElement(input) {
        const anchor = input.closest('.relative') || input;
        let hint = anchor.nextElementSibling;
        if (hint && hint.hasAttribute('data-ph-phone-hint')) {
            return hint;
        }

        hint = document.createElement('p');
        hint.setAttribute('data-ph-phone-hint', 'true');
        hint.className = 'text-xs mt-1 font-medium hidden';
        anchor.insertAdjacentElement('afterend', hint);
        return hint;
    }

    function setInvalidStyle(input, isInvalid) {
        INVALID_CLASSES.forEach(function (cls) {
            input.classList.toggle(cls, isInvalid);
        });
    }

    function updateHint(input, hint, forceErrorOnIncomplete) {
        const val = clean(input.value);
        const state = getHintState(val);

        if (!val) {
            hint.textContent = '';
            hint.classList.add('hidden');
            setInvalidStyle(input, false);
            return;
        }

        if (forceErrorOnIncomplete && !isValid(val)) {
            hint.textContent = 'Use 09XXXXXXXXX or +639XXXXXXXXX';
            hint.classList.remove('hidden', 'text-slate-500');
            hint.classList.add('text-rose-500');
            setInvalidStyle(input, true);
            return;
        }

        hint.textContent = state.text;
        hint.classList.toggle('hidden', !state.text);
        hint.classList.toggle('text-rose-500', state.error);
        hint.classList.toggle('text-slate-500', !state.error);

        if (!forceErrorOnIncomplete) {
            setInvalidStyle(input, state.error);
        } else {
            setInvalidStyle(input, false);
        }
    }

    function attach(input) {
        if (input.dataset.phPhoneBound === '1') {
            return;
        }
        input.dataset.phPhoneBound = '1';

        const hint = ensureHintElement(input);

        input.addEventListener('input', function () {
            const sanitized = sanitize(input.value);
            if (input.value !== sanitized) {
                input.value = sanitized;
            }

            if (sanitized.startsWith('+')) {
                input.maxLength = 13;
            } else if (sanitized.startsWith('0')) {
                input.maxLength = 11;
            } else {
                input.maxLength = 13;
            }

            updateHint(input, hint, false);
        });

        input.addEventListener('blur', function () {
            updateHint(input, hint, true);
        });

        if (input.value) {
            if (input.value.startsWith('+')) {
                input.maxLength = 13;
            } else if (input.value.startsWith('0')) {
                input.maxLength = 11;
            }
            updateHint(input, hint, false);
        }
    }

    function init() {
        document.querySelectorAll('input[data-ph-phone]').forEach(attach);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.MedLinkPhPhone = {
        init: init,
        attach: attach,
        clean: clean,
        isValid: isValid,
    };
})();
