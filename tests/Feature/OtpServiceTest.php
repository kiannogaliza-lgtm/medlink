<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\OtpLockout;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $lastCode = '';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.httpsms.api_key', 'test-key');
        Config::set('services.httpsms.from', '+639000000000');
        Config::set('otp.log_fallback', false);

        Http::fake([
            'https://api.httpsms.com/v1/messages/send' => function ($request) {
                $content = (string) ($request->data()['content'] ?? '');
                if (preg_match('/\b(\d{6})\b/', $content, $m)) {
                    $this->lastCode = $m[1];
                }

                return Http::response([
                    'status' => 'success',
                    'data'   => ['id' => 'msg-test-001'],
                ], 200);
            },
        ]);
    }

    public function test_issue_stores_bcrypt_hash_not_plaintext_code(): void
    {
        $service = app(OtpService::class);
        $res = $service->issue('+639171234567', 'login');

        $this->assertTrue($res['ok']);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->lastCode);

        $otp = OtpCode::first();
        $this->assertNotNull($otp);
        $this->assertSame('09171234567', $otp->phone);
        $this->assertNotSame($this->lastCode, $otp->code_hash);
        $this->assertTrue(Hash::check($this->lastCode, $otp->code_hash));

        Http::assertSent(fn ($req) => $req->url() === 'https://api.httpsms.com/v1/messages/send'
            && $req->hasHeader('x-api-key', 'test-key')
            && $req['to'] === '+639171234567');
    }

    public function test_resend_cooldown_and_hourly_limit(): void
    {
        $service = app(OtpService::class);

        $first = $service->issue('09171234567', 'login');
        $this->assertTrue($first['ok']);

        // Immediate resend blocked by 60s cooldown
        $second = $service->issue('09171234567', 'login');
        $this->assertFalse($second['ok']);
        $this->assertSame('cooldown', $second['error']);
        $this->assertGreaterThan(0, $second['retry_after']);

        // Send 4 more spaced 65s apart -> total 5 in an hour succeeds
        for ($i = 0; $i < 4; $i++) {
            $this->travel(65)->seconds();
            $r = $service->issue('09171234567', 'login');
            $this->assertTrue($r['ok']);
        }

        // 6th within the hour fails with hourly_limit
        $this->travel(65)->seconds();
        $sixth = $service->issue('09171234567', 'login');
        $this->assertFalse($sixth['ok']);
        $this->assertSame('hourly_limit', $sixth['error']);
    }

    public function test_expiry_per_purpose(): void
    {
        $service = app(OtpService::class);
        $cases = [
            'registration'   => 30,
            'login'          => 5,
            'password_reset' => 10,
            'phone_change'   => 10,
        ];

        foreach ($cases as $purpose => $minutes) {
            $phone = '091712345' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT);
            $issued = $service->issue($phone, $purpose);
            $this->assertTrue($issued['ok']);
            $code = $this->lastCode;

            $this->travel($minutes + 1)->minutes();
            $verify = $service->verify($phone, $purpose, $code);
            $this->assertFalse($verify['ok']);
            $this->assertSame('expired', $verify['error']);
        }
    }

    public function test_five_wrong_attempts_lock_out_login_reset_phone_change_but_not_registration(): void
    {
        $service = app(OtpService::class);

        // Lockout purposes
        foreach (['login', 'password_reset', 'phone_change'] as $idx => $purpose) {
            $phone = '0918000000' . $idx;
            $this->assertTrue($service->issue($phone, $purpose)['ok']);
            $wrong = $this->lastCode === '000000' ? '999999' : '000000';

            for ($attempt = 1; $attempt <= 4; $attempt++) {
                $r = $service->verify($phone, $purpose, $wrong);
                $this->assertSame('invalid', $r['error']);
                $this->assertSame(5 - $attempt, $r['attempts_left']);
            }

            $fifth = $service->verify($phone, $purpose, $wrong);
            $this->assertSame('too_many_attempts', $fifth['error']);
            $this->assertSame(1800, $fifth['retry_after']);
            $this->assertNotNull($service->lockoutRemaining($phone, $purpose));
            $this->assertDatabaseHas('otp_lockouts', ['phone' => $phone, 'purpose' => $purpose]);

            // Further issue is locked
            $lockedIssue = $service->issue($phone, $purpose);
            $this->assertSame('locked', $lockedIssue['error']);
        }

        // Registration: 5 wrong invalidates code, NO lockout
        $regPhone = '09180000099';
        $this->assertTrue($service->issue($regPhone, 'registration')['ok']);
        $wrong = $this->lastCode === '000000' ? '999999' : '000000';

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $res = $service->verify($regPhone, 'registration', $wrong);
        }
        $this->assertSame('too_many_attempts', $res['error']);
        $this->assertNull($res['retry_after']);
        $this->assertNull($service->lockoutRemaining($regPhone, 'registration'));
        $this->assertDatabaseMissing('otp_lockouts', ['phone' => $regPhone, 'purpose' => 'registration']);
    }

    public function test_consumed_code_cannot_be_reused(): void
    {
        $service = app(OtpService::class);
        $this->assertTrue($service->issue('09171112222', 'login')['ok']);
        $code = $this->lastCode;

        $this->assertTrue($service->verify('09171112222', 'login', $code)['ok']);

        $reuse = $service->verify('09171112222', 'login', $code);
        $this->assertFalse($reuse['ok']);
        $this->assertSame('no_code', $reuse['error']);
    }

    public function test_log_fallback_logs_outside_production(): void
    {
        Config::set('services.httpsms.api_key', '');
        Config::set('services.httpsms.from', '');
        Config::set('otp.log_fallback', true);

        Log::spy();
        $service = app(OtpService::class);

        $res = $service->issue('09172223333', 'login');
        $this->assertTrue($res['ok']);
        Log::shouldHaveReceived('info')->once()->withArgs(fn ($msg) => str_contains($msg, '[OTP-DEV] login 09******333 code='));
    }

    public function test_log_fallback_never_logs_in_production(): void
    {
        Config::set('services.httpsms.api_key', '');
        Config::set('services.httpsms.from', '');
        Config::set('otp.log_fallback', true);
        $this->app['env'] = 'production';

        Log::spy();
        $service = app(OtpService::class);

        $prodRes = $service->issue('09172224444', 'login');
        $this->assertFalse($prodRes['ok']);
        $this->assertSame('sms_unavailable', $prodRes['error']);
        Log::shouldNotHaveReceived('info');
    }
}
