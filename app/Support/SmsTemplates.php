<?php

namespace App\Support;

use Illuminate\Support\Str;

final class SmsTemplates
{
    public static function otp(string $purpose, string $code, int $minutes): string
    {
        $template = match ($purpose) {
            'registration'   => "MedLink: Your verification code is {$code}. Valid for {$minutes} minutes. Do not share this code with anyone.",
            'login'          => "MedLink: Your login code is {$code}. Valid for {$minutes} minutes. Do not share this code with anyone.",
            'password_reset' => "MedLink: Your password reset code is {$code}. Valid for {$minutes} minutes. If you did not request this, ignore this message.",
            'phone_change'   => "MedLink: Your code to confirm this new number is {$code}. Valid for {$minutes} minutes. Do not share this code.",
            default          => "MedLink: Your verification code is {$code}. Valid for {$minutes} minutes. Do not share this code with anyone.",
        };

        return self::sanitize($template);
    }

    public static function pickupReady(string $reqId, string $item, int $qty): string
    {
        $cleanId = self::sanitize($reqId);
        $prefix = "MedLink: Your request {$cleanId} (";
        $suffix = " x{$qty}) is approved and ready for pickup at Barangay Honrado Health Center.";
        $budget = max(10, 160 - strlen($prefix) - strlen($suffix));
        $shortItem = self::fit($item, $budget);

        return self::fit($prefix . $shortItem . $suffix, 160);
    }

    public static function deliveryOnTheWay(string $reqId, ?string $bhwName = null): string
    {
        $cleanId = self::sanitize($reqId);
        $cleanName = $bhwName !== null ? self::sanitize($bhwName) : '';
        $cleanName = preg_replace('/^BHW\s+/i', '', $cleanName) ?? $cleanName;
        $cleanName = trim($cleanName);

        if ($cleanName !== '') {
            $prefix = 'MedLink: BHW ';
            $suffix = " is on the way with your medicine ({$cleanId}). Please be ready for a short consultation before it is handed over.";
            $budget = max(6, 160 - strlen($prefix) - strlen($suffix));
            $shortName = self::fit($cleanName, $budget);

            return self::fit($prefix . $shortName . $suffix, 160);
        }

        return self::fit(
            "MedLink: A BHW is on the way with your medicine ({$cleanId}). Please be ready for a short consultation before it is handed over.",
            160
        );
    }

    public static function homeServiceOnTheWay(string $hsId, string $bhwName, string $serviceType): string
    {
        $cleanId = self::sanitize($hsId);
        $cleanBhw = self::fit($bhwName, 32);
        $prefix = "MedLink: {$cleanBhw} is on the way for your home service ({$cleanId}, ";
        $suffix = '). Please be ready at your address.';
        $budget = max(10, 160 - strlen($prefix) - strlen($suffix));
        $shortService = self::fit($serviceType, $budget);

        return self::fit($prefix . $shortService . $suffix, 160);
    }

    public static function announcement(string $title, string $content): string
    {
        $cleanTitle = self::sanitize($title);
        $cleanContent = self::sanitize($content);

        return self::fit("MedLink Announcement: {$cleanTitle}. {$cleanContent}", 306);
    }

    public static function accountApproved(string $firstName): string
    {
        $cleanFirst = trim(substr(self::sanitize($firstName), 0, 20));
        if ($cleanFirst === '') {
            $cleanFirst = 'Resident';
        }

        return self::fit(
            "MedLink: Hi {$cleanFirst}, your account is now verified by Barangay Honrado Health Center. You can log in to request medicine and home services.",
            160
        );
    }

    public static function accountRejected(): string
    {
        return self::fit(
            'MedLink: Your registration could not be verified. Please visit Barangay Honrado Health Center with a valid government ID.',
            160
        );
    }

    public static function sanitize(string $text): string
    {
        $replaced = str_replace(
            ["\u{2014}", "\u{2013}", "\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{2026}"],
            ['-', '-', "'", "'", '"', '"', '...'],
            $text
        );

        // Transliterate accented characters (e.g. ñ -> n, é -> e) before stripping non-ASCII
        $ascii = Str::ascii($replaced);

        // Strip non-GSM-7 / non-ASCII characters (including emojis)
        $gsmOnly = preg_replace('/[^\x20-\x7E\r\n]/u', '', $ascii) ?? '';

        // Collapse newlines and repeated spaces
        $collapsed = preg_replace('/[\r\n\t]+/', ' ', $gsmOnly) ?? '';
        $collapsed = preg_replace('/\s{2,}/', ' ', $collapsed) ?? '';

        return trim($collapsed);
    }

    public static function fit(string $text, int $max = 160): string
    {
        $clean = self::sanitize($text);

        if (strlen($clean) <= $max) {
            return $clean;
        }

        if ($max <= 3) {
            return substr($clean, 0, max(0, $max));
        }

        return rtrim(substr($clean, 0, $max - 3)) . '...';
    }
}
