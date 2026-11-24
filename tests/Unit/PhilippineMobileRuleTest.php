<?php

namespace Tests\Unit;

use App\Rules\PhilippineMobile;
use PHPUnit\Framework\TestCase;

class PhilippineMobileRuleTest extends TestCase
{
    private function passes(mixed $value): bool
    {
        $failed = false;
        $rule = new PhilippineMobile();
        $rule->validate('phone_number', $value, function () use (&$failed) {
            $failed = true;
        });

        return !$failed;
    }

    public function test_valid_philippine_mobile_numbers_pass(): void
    {
        $this->assertTrue($this->passes('09171234567'));
        $this->assertTrue($this->passes('+639171234567'));
        $this->assertTrue($this->passes('0917-123-4567'));
        $this->assertTrue($this->passes('+63 917 123 4567'));
    }

    public function test_invalid_numbers_fail_with_expected_message(): void
    {
        foreach (['639171234567', '9171234567', '0281234567', 'WALKIN-123-4567', '', null, 9171234567] as $bad) {
            $message = null;
            (new PhilippineMobile())->validate('phone_number', $bad, function (string $msg) use (&$message) {
                $message = $msg;
            });

            $this->assertSame(
                'The :attribute must be a Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
                $message
            );
        }
    }
}
