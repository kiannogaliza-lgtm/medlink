<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\OtpLockout;
use App\Models\User;
use App\Support\PhoneNumber;
use App\Support\SmsTemplates;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function __construct(private readonly SmsService $sms) {}

    /**
     * @return array{ok: bool, error: ?string, retry_after: ?int, expires_in_minutes: ?int}
     */
    public function issue(string $phone, string $purpose, ?User $user = null): array
    {
        $normalized = PhoneNumber::normalize($phone);

        if ($normalized === null) {
            return [
                'ok'                 => false,
                'error'              => 'sms_unavailable',
                'retry_after'        => null,
                'expires_in_minutes' => null,
            ];
        }

        $lockoutSeconds = $this->lockoutRemaining($normalized, $purpose);
        if ($lockoutSeconds !== null) {
            return [
                'ok'                 => false,
                'error'              => 'locked',
                'retry_after'        => $lockoutSeconds,
                'expires_in_minutes' => null,
            ];
        }

        $cooldownSeconds = (int) config('otp.resend_cooldown_seconds', 60);
        $lastSent = OtpCode::where('phone', $normalized)
            ->where('purpose', $purpose)
            ->latest('sent_at')
            ->first();

        if ($lastSent && $lastSent->sent_at) {
            $elapsed = (int) $lastSent->sent_at->diffInSeconds(now(), false);
            if ($elapsed < $cooldownSeconds) {
                return [
                    'ok'                 => false,
                    'error'              => 'cooldown',
                    'retry_after'        => max(1, $cooldownSeconds - $elapsed),
                    'expires_in_minutes' => null,
                ];
            }
        }

        $maxPerHour = (int) config('otp.max_sends_per_hour', 15);
        $sendsLastHour = OtpCode::where('phone', $normalized)
            ->where('purpose', $purpose)
            ->where('sent_at', '>=', now()->subHour())
            ->count();

        if ($sendsLastHour >= $maxPerHour) {
            return [
                'ok'                 => false,
                'error'              => 'hourly_limit',
                'retry_after'        => null,
                'expires_in_minutes' => null,
            ];
        }

        // Invalidate any previous active codes for this phone + purpose
        OtpCode::where('phone', $normalized)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->whereNull('invalidated_at')
            ->update([
                'invalidated_at'     => now(),
                'invalidated_reason' => 'superseded',
            ]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresMinutes = (int) config("otp.expiry_minutes.{$purpose}", 10);

        $otp = OtpCode::create([
            'user_id'    => $user?->getKey(),
            'phone'      => $normalized,
            'purpose'    => $purpose,
            'code_hash'  => Hash::make($code),
            'expires_at' => now()->addMinutes($expiresMinutes),
            'attempts'   => 0,
            'sent_at'    => now(),
            'ip_address' => request()?->ip(),
        ]);

        if (!$this->sms->isConfigured()) {
            if (config('otp.log_fallback') && !app()->environment('production')) {
                Log::info('[OTP-DEV] ' . $purpose . ' ' . PhoneNumber::mask($normalized) . ' code=' . $code);

                return [
                    'ok'                 => true,
                    'error'              => null,
                    'retry_after'        => null,
                    'expires_in_minutes' => $expiresMinutes,
                ];
            }

            $otp->update([
                'invalidated_at'     => now(),
                'invalidated_reason' => 'sms_unavailable',
            ]);

            return [
                'ok'                 => false,
                'error'              => 'sms_unavailable',
                'retry_after'        => null,
                'expires_in_minutes' => null,
            ];
        }

        $message = SmsTemplates::otp($purpose, $code, $expiresMinutes);
        $smsResult = $this->sms->send($normalized, $message, 'otp_' . $purpose, $user, $otp);

        if (!$smsResult['ok']) {
            $otp->update([
                'invalidated_at'     => now(),
                'invalidated_reason' => 'sms_failed',
            ]);

            return [
                'ok'                 => false,
                'error'              => 'sms_failed',
                'retry_after'        => null,
                'expires_in_minutes' => null,
            ];
        }

        return [
            'ok'                 => true,
            'error'              => null,
            'retry_after'        => null,
            'expires_in_minutes' => $expiresMinutes,
        ];
    }

    /**
     * @return array{ok: bool, error: ?string, attempts_left: ?int, retry_after: ?int}
     */
    public function verify(string $phone, string $purpose, string $code): array
    {
        $trimmedCode = trim($code);

        if (preg_match('/^\d{6}$/', $trimmedCode) !== 1) {
            return [
                'ok'            => false,
                'error'         => 'bad_format',
                'attempts_left' => null,
                'retry_after'   => null,
            ];
        }

        $normalized = PhoneNumber::normalize($phone);
        if ($normalized === null) {
            return [
                'ok'            => false,
                'error'         => 'no_code',
                'attempts_left' => null,
                'retry_after'   => null,
            ];
        }

        $lockoutSeconds = $this->lockoutRemaining($normalized, $purpose);
        if ($lockoutSeconds !== null) {
            return [
                'ok'            => false,
                'error'         => 'locked',
                'attempts_left' => null,
                'retry_after'   => $lockoutSeconds,
            ];
        }

        $maxAttempts = (int) config('otp.max_attempts', 5);
        $lockoutMinutes = (int) config('otp.lockout_minutes', 30);
        $lockoutPurposes = (array) config('otp.lockout_purposes', []);

        return DB::transaction(function () use ($normalized, $purpose, $trimmedCode, $maxAttempts, $lockoutMinutes, $lockoutPurposes) {
            $otp = OtpCode::where('phone', $normalized)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->whereNull('invalidated_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$otp) {
                return [
                    'ok'            => false,
                    'error'         => 'no_code',
                    'attempts_left' => null,
                    'retry_after'   => null,
                ];
            }

            if ($otp->expires_at->isPast()) {
                $otp->update([
                    'invalidated_at'     => now(),
                    'invalidated_reason' => 'expired',
                ]);

                return [
                    'ok'            => false,
                    'error'         => 'expired',
                    'attempts_left' => null,
                    'retry_after'   => null,
                ];
            }

            if (!Hash::check($trimmedCode, $otp->code_hash)) {
                $otp->attempts = $otp->attempts + 1;

                if ($otp->attempts >= $maxAttempts) {
                    $otp->invalidated_at = now();
                    $otp->invalidated_reason = 'too_many_attempts';
                    $otp->save();

                    $retryAfter = null;
                    if (in_array($purpose, $lockoutPurposes, true)) {
                        OtpLockout::updateOrCreate(
                            ['phone' => $normalized, 'purpose' => $purpose],
                            ['locked_until' => now()->addMinutes($lockoutMinutes)]
                        );
                        $retryAfter = $lockoutMinutes * 60;
                    }

                    return [
                        'ok'            => false,
                        'error'         => 'too_many_attempts',
                        'attempts_left' => 0,
                        'retry_after'   => $retryAfter,
                    ];
                }

                $otp->save();

                return [
                    'ok'            => false,
                    'error'         => 'invalid',
                    'attempts_left' => $maxAttempts - $otp->attempts,
                    'retry_after'   => null,
                ];
            }

            $otp->update(['consumed_at' => now()]);

            return [
                'ok'            => true,
                'error'         => null,
                'attempts_left' => null,
                'retry_after'   => null,
            ];
        });
    }

    public function lockoutRemaining(string $phone, string $purpose): ?int
    {
        $normalized = PhoneNumber::normalize($phone) ?? $phone;

        $lockout = OtpLockout::where('phone', $normalized)
            ->where('purpose', $purpose)
            ->first();

        if (!$lockout || !$lockout->locked_until || $lockout->locked_until->isPast()) {
            return null;
        }

        $remaining = (int) now()->diffInSeconds($lockout->locked_until, false);

        return $remaining > 0 ? $remaining : null;
    }

    public function errorMessage(array $result): string
    {
        $error = $result['error'] ?? null;

        return match ($error) {
            'locked' => 'Too many incorrect codes. Try again in ' . max(1, (int) ceil(($result['retry_after'] ?? 60) / 60)) . ' minutes.',
            'cooldown' => 'Please wait ' . max(1, (int) ($result['retry_after'] ?? 60)) . ' seconds before requesting a new code.',
            'hourly_limit' => 'Too many codes requested. Try again later.',
            'sms_unavailable', 'sms_failed' => "We couldn't send the code right now. Please try again or contact Barangay Honrado Health Center.",
            'expired' => 'This code has expired. Request a new one.',
            'invalid' => 'Incorrect code. ' . (int) ($result['attempts_left'] ?? 0) . ' attempt(s) left.',
            'too_many_attempts' => !empty($result['retry_after'])
                ? 'Too many incorrect attempts. This code is no longer valid. Try again in ' . max(1, (int) ceil($result['retry_after'] / 60)) . ' minutes.'
                : 'Too many incorrect attempts. This code is no longer valid.',
            'no_code' => 'No active code. Request a new one.',
            'bad_format' => 'Enter the 6-digit code.',
            default => 'Unable to verify code. Please try again.',
        };
    }
}
