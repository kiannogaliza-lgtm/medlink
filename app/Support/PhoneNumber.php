<?php

namespace App\Support;

use Illuminate\Http\Request;

final class PhoneNumber
{
    public static function clean(?string $raw): string
    {
        if ($raw === null) {
            return '';
        }

        return preg_replace('/[\s\-\.\(\)]+/', '', trim($raw)) ?? '';
    }

    public static function detectFormat(?string $raw): ?string
    {
        $cleaned = self::clean($raw);

        if (str_starts_with($cleaned, '+63')) {
            return 'e164';
        }

        if (str_starts_with($cleaned, '09')) {
            return 'local';
        }

        return null;
    }

    public static function normalize(?string $raw): ?string
    {
        $cleaned = self::clean($raw);

        if (preg_match('/^\+639[0-9]{9}$/', $cleaned) === 1) {
            return '0' . substr($cleaned, 3);
        }

        if (preg_match('/^09[0-9]{9}$/', $cleaned) === 1) {
            return $cleaned;
        }

        return null;
    }

    public static function toE164(string $local09): string
    {
        return '+63' . substr($local09, 1);
    }

    public static function mask(?string $phone, ?string $format = null): string
    {
        if (self::isPlaceholder($phone)) {
            return 'No phone';
        }

        $detected = $format ?? self::detectFormat($phone);
        $normalized = self::normalize($phone);

        if ($normalized === null) {
            $cleaned = self::clean($phone);
            return strlen($cleaned) >= 7
                ? substr($cleaned, 0, 4) . str_repeat('*', max(0, strlen($cleaned) - 7)) . substr($cleaned, -3)
                : $cleaned;
        }

        $lastThree = substr($normalized, -3);

        if ($detected === 'e164') {
            return '+639******' . $lastThree;
        }

        return '09******' . $lastThree;
    }

    public static function isPlaceholder(?string $phone): bool
    {
        if ($phone === null) {
            return true;
        }

        $trimmed = trim($phone);

        return $trimmed === '' || str_starts_with($trimmed, 'WALKIN-');
    }

    public static function isSendable(?string $phone): bool
    {
        return !self::isPlaceholder($phone) && self::normalize($phone) !== null;
    }

    public static function mergeNormalized(Request $request, array $fields): void
    {
        foreach ($fields as $field) {
            if (!$request->has($field)) {
                continue;
            }

            $value = $request->input($field);

            if (!is_string($value) || trim($value) === '') {
                continue;
            }

            $normalized = self::normalize($value);

            if ($normalized !== null) {
                $request->merge([$field => $normalized]);
            }
        }
    }
}
