<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_clean_removes_spaces_dashes_dots_and_parentheses(): void
    {
        $this->assertSame('+639171234567', PhoneNumber::clean(' +63 (917) 123-45.67 '));
        $this->assertSame('09171234567', PhoneNumber::clean('0917-123-4567'));
        $this->assertSame('', PhoneNumber::clean(null));
    }

    public function test_detect_format(): void
    {
        $this->assertSame('e164', PhoneNumber::detectFormat('+639171234567'));
        $this->assertSame('e164', PhoneNumber::detectFormat('+63 917-123-4567'));
        $this->assertSame('local', PhoneNumber::detectFormat('09171234567'));
        $this->assertSame('local', PhoneNumber::detectFormat('0917 123 4567'));
        $this->assertNull(PhoneNumber::detectFormat('639171234567'));
        $this->assertNull(PhoneNumber::detectFormat('9171234567'));
        $this->assertNull(PhoneNumber::detectFormat('0281234567'));
        $this->assertNull(PhoneNumber::detectFormat('WALKIN-12345-6789'));
    }

    public function test_normalize_accepts_only_valid_ph_mobile_numbers(): void
    {
        $this->assertSame('09171234567', PhoneNumber::normalize('+639171234567'));
        $this->assertSame('09171234567', PhoneNumber::normalize('09171234567'));
        $this->assertSame('09171234567', PhoneNumber::normalize('+63 917-123-4567'));
        $this->assertSame('09171234567', PhoneNumber::normalize('0917 123 4567'));

        // Reject invalid formats
        $this->assertNull(PhoneNumber::normalize('639171234567'));
        $this->assertNull(PhoneNumber::normalize('9171234567'));
        $this->assertNull(PhoneNumber::normalize('0281234567'));
        $this->assertNull(PhoneNumber::normalize('+14155552671'));
        $this->assertNull(PhoneNumber::normalize('0917123456'));
        $this->assertNull(PhoneNumber::normalize('091712345678'));
        $this->assertNull(PhoneNumber::normalize('WALKIN-1700000000-1234'));
        $this->assertNull(PhoneNumber::normalize(''));
        $this->assertNull(PhoneNumber::normalize(null));
    }

    public function test_to_e164(): void
    {
        $this->assertSame('+639171234567', PhoneNumber::toE164('09171234567'));
    }

    public function test_mask_formats_local_and_e164(): void
    {
        $this->assertSame('09******567', PhoneNumber::mask('09171234567'));
        $this->assertSame('+639******567', PhoneNumber::mask('09171234567', 'e164'));
        $this->assertSame('+639******567', PhoneNumber::mask('+639171234567'));
        $this->assertSame('09******567', PhoneNumber::mask('+639171234567', 'local'));
        $this->assertSame('No phone', PhoneNumber::mask('WALKIN-12345-6789'));
        $this->assertSame('No phone', PhoneNumber::mask(null));
    }

    public function test_is_placeholder_and_is_sendable(): void
    {
        $this->assertTrue(PhoneNumber::isPlaceholder(null));
        $this->assertTrue(PhoneNumber::isPlaceholder(''));
        $this->assertTrue(PhoneNumber::isPlaceholder('WALKIN-12345-6789'));
        $this->assertFalse(PhoneNumber::isPlaceholder('09171234567'));

        $this->assertTrue(PhoneNumber::isSendable('09171234567'));
        $this->assertTrue(PhoneNumber::isSendable('+639171234567'));
        $this->assertFalse(PhoneNumber::isSendable('WALKIN-12345-6789'));
        $this->assertFalse(PhoneNumber::isSendable('0281234567'));
        $this->assertFalse(PhoneNumber::isSendable(null));
    }
}
