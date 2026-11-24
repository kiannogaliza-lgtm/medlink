<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoginOtpTest extends TestCase
{
    use RefreshDatabase;

    private string $lastCode = '';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.httpsms.api_key', 'test-key');
        Config::set('services.httpsms.from', '+639000000000');

        Http::fake([
            'https://api.httpsms.com/v1/messages/send' => function ($request) {
                if (preg_match('/\b(\d{6})\b/', (string) ($request->data()['content'] ?? ''), $m)) {
                    $this->lastCode = $m[1];
                }

                return Http::response(['status' => 'success', 'data' => ['id' => 'login-msg-1']], 200);
            },
        ]);
    }

    public function test_each_role_requires_otp_and_dashboard_is_unreachable_mid_step(): void
    {
        foreach (['resident', 'bhw', 'nurse'] as $index => $role) {
            $phone = '0917111000' . $index;
            $user = User::factory()->{$role}()->create([
                'phone_number' => $phone,
                'password'     => 'secret1234',
            ]);

            $typed = $index === 0 ? '+63' . substr($phone, 1) : $phone;
            $expectedMask = $index === 0 ? '+639******000' : '09******00' . $index;

            $this->post('/login', [
                'phone_number' => $typed,
                'password'     => 'secret1234',
                'role'         => $role,
            ])->assertRedirect(route('login.otp'));

            // Not authenticated yet -> /dashboard redirects to /login
            $this->assertGuest();
            $this->get('/dashboard')->assertRedirect(route('login'));

            // Mask follows typed format
            $this->get(route('login.otp'))
                ->assertOk()
                ->assertSee($expectedMask);

            // Verify OTP -> authenticated with active_session_token
            $this->post(route('login.otp.submit'), ['code' => $this->lastCode])
                ->assertRedirect(route('dashboard'));

            $this->assertAuthenticatedAs($user);
            $user->refresh();
            $this->assertNotNull($user->active_session_token);
            $this->assertSame($user->active_session_token, session('active_session_token'));

            $this->post(route('logout'));
        }
    }

    public function test_five_wrong_login_otps_lock_out_and_return_to_login(): void
    {
        $user = User::factory()->nurse()->create([
            'phone_number' => '09172220001',
            'password'     => 'secret1234',
        ]);

        $this->post('/login', [
            'phone_number' => '09172220001',
            'password'     => 'secret1234',
            'role'         => 'nurse',
        ])->assertRedirect(route('login.otp'));

        $wrong = $this->lastCode === '000000' ? '999999' : '000000';
        for ($i = 0; $i < 4; $i++) {
            $this->post(route('login.otp.submit'), ['code' => $wrong])
                ->assertSessionHasErrors('code');
        }

        $fifth = $this->post(route('login.otp.submit'), ['code' => $wrong]);
        $fifth->assertRedirect(route('login'));
        $fifth->assertSessionHasErrors('phone_number');
        $this->assertGuest();
        $this->assertDatabaseHas('otp_lockouts', [
            'phone'   => $user->phone_number,
            'purpose' => 'login',
        ]);
    }

    public function test_login_with_unsendable_phone_is_rejected_without_sending_otp(): void
    {
        $expectedError = 'Your account has no valid mobile number on file. Please visit Barangay Honrado Health Center to update it.';

        foreach (['WALKIN-123', '0281234567'] as $phone) {
            User::factory()->resident()->create([
                'phone_number' => $phone,
                'password'     => 'secret1234',
                'status'       => 'active',
            ]);

            $response = $this->post('/login', [
                'phone_number' => $phone,
                'password'     => 'secret1234',
                'role'         => 'resident',
            ]);

            $response->assertSessionHasErrors([
                'phone_number' => $expectedError,
            ]);
            $this->assertGuest();
        }

        $this->assertDatabaseCount('otp_codes', 0);
        Http::assertNothingSent();
    }
}
