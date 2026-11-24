export function clean(raw) {
  if (raw === null || raw === undefined) return '';
  return String(raw).trim().replace(/[\s\-\.\(\)]+/g, '');
}

export function detectFormat(raw) {
  const cleaned = clean(raw);
  if (cleaned.startsWith('+63')) return 'e164';
  if (cleaned.startsWith('09')) return 'local';
  return null;
}

export function normalize(raw) {
  const cleaned = clean(raw);
  if (/^\+639[0-9]{9}$/.test(cleaned)) {
    return '0' + cleaned.slice(3);
  }
  if (/^09[0-9]{9}$/.test(cleaned)) {
    return cleaned;
  }
  return null;
}

export function isValid(raw) {
  return normalize(raw) !== null;
}

export function maxLengthFor(value) {
  const cleaned = clean(value);
  if (cleaned.startsWith('+')) return 13;
  if (cleaned.startsWith('0')) return 11;
  return 13;
}

export function sanitizeInput(raw) {
  const str = String(raw || '');
  const hasLeadingPlus = str.trimStart().startsWith('+');
  const digitsOnly = str.replace(/\D/g, '');
  const value = hasLeadingPlus ? '+' + digitsOnly : digitsOnly;
  return value.slice(0, maxLengthFor(value));
}

export function hintFor(value) {
  const cleaned = clean(value);
  if (!cleaned) return { text: '', error: false };

  if (cleaned.startsWith('+')) {
    if (cleaned === '+' || cleaned === '+6' || cleaned === '+63' || cleaned.startsWith('+639')) {
      return { text: 'Format: +639XXXXXXXXX', error: false };
    }
    return { text: 'Use 09XXXXXXXXX or +639XXXXXXXXX', error: true };
  }

  if (cleaned.startsWith('0')) {
    if (cleaned === '0' || cleaned.startsWith('09')) {
      return { text: 'Format: 09XXXXXXXXX', error: false };
    }
    return { text: 'Use 09XXXXXXXXX or +639XXXXXXXXX', error: true };
  }

  return { text: 'Use 09XXXXXXXXX or +639XXXXXXXXX', error: true };
}

export function isPlaceholder(phone) {
  if (phone === null || phone === undefined) return true;
  const trimmed = String(phone).trim();
  return trimmed === '' || trimmed.startsWith('WALKIN-');
}

export function displayPhone(phone, fallback = 'No phone') {
  return isPlaceholder(phone) ? fallback : phone;
}

export function mask(phone, format = null) {
  if (isPlaceholder(phone)) return 'No phone';
  const detected = format ?? detectFormat(phone);
  const normalized = normalize(phone);
  if (!normalized) {
    const cleaned = clean(phone);
    return cleaned.length >= 7
      ? cleaned.slice(0, 4) + '*'.repeat(Math.max(0, cleaned.length - 7)) + cleaned.slice(-3)
      : cleaned;
  }
  const lastThree = normalized.slice(-3);
  return detected === 'e164' ? `+639******${lastThree}` : `09******${lastThree}`;
}
