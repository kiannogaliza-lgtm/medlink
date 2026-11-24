<?php

namespace Tests\Unit;

use App\Support\SmsTemplates;
use PHPUnit\Framework\TestCase;

class SmsTemplatesTest extends TestCase
{
    public function test_sanitize_transliterates_accents_and_normalizes_punctuation(): void
    {
        $this->assertSame('Pena - "Hi"', SmsTemplates::sanitize("Peña \u{2013} \u{201C}Hi\u{201D}"));
        $this->assertSame('Jose Cafe', SmsTemplates::sanitize('José Café'));
    }

    public function test_all_templates_stay_within_sms_length_limits(): void
    {
        foreach (['registration', 'login', 'password_reset', 'phone_change', 'other'] as $purpose) {
            $msg = SmsTemplates::otp($purpose, '123456', 10);
            $this->assertLessThanOrEqual(160, strlen($msg), "OTP template for {$purpose} exceeded 160 chars.");
        }

        $longName = str_repeat('Peña-Dela-Cruz ', 20);
        $longItem = str_repeat('Paracetamol 500mg Tablet ', 15);
        $longService = str_repeat('Blood Pressure & Prenatal Checkup ', 15);

        $this->assertLessThanOrEqual(160, strlen(SmsTemplates::pickupReady('REQ-001', $longItem, 30)));
        $this->assertLessThanOrEqual(160, strlen(SmsTemplates::deliveryOnTheWay('REQ-001', $longName)));
        $this->assertLessThanOrEqual(160, strlen(SmsTemplates::deliveryOnTheWay('REQ-001', null)));
        $this->assertLessThanOrEqual(160, strlen(SmsTemplates::homeServiceOnTheWay('HS-001', $longName, $longService)));

        $announcement = SmsTemplates::announcement(
            'Barangay Peña Health Advisory',
            str_repeat('Free checkup for niños and seniors at Barangay Honrado. ', 15)
        );
        $this->assertLessThanOrEqual(306, strlen($announcement));
        $this->assertStringContainsString('Pena', $announcement);
        $this->assertStringContainsString('ninos', $announcement);
    }

    public function test_account_approved_truncates_long_name_and_is_ascii_within_160_chars(): void
    {
        $longFirstName = 'María José Peña-Dela Cruz-Villanueva-Santos';
        $this->assertGreaterThanOrEqual(40, mb_strlen($longFirstName));

        $msg = SmsTemplates::accountApproved($longFirstName);

        $this->assertLessThanOrEqual(160, strlen($msg));
        $this->assertMatchesRegularExpression('/^[\x20-\x7E]+$/', $msg);
        $this->assertStringStartsWith('MedLink: Hi Maria Jose Pena-Dela,', $msg);
        $this->assertStringContainsString('Barangay Honrado Health Center', $msg);

        $rejected = SmsTemplates::accountRejected();
        $this->assertLessThanOrEqual(160, strlen($rejected));
        $this->assertMatchesRegularExpression('/^[\x20-\x7E]+$/', $rejected);
        $this->assertStringContainsString('could not be verified', $rejected);
    }
}
