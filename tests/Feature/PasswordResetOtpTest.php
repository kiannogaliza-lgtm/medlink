<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PasswordResetOtpTest extends TestCase
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

                return Http::response(['status' => 'success', 'data' => ['id' => 'pwr-msg-1']], 200);
            },
        ]);
    }

    public function test_full_password_reset_happy_path_clears_active_session_token(): void
    {
        $user = User::factory()->resident()->create([
            'first_name'           => 'Maria',
            'last_name'            => 'Clara',
            'phone_number'         => '09171234567',
            'password'             => 'oldpassword1',
            'active_session_token' => 'existing-device-token',
        ]);

        // Step 1: Request reset
        $this->post(route('forgot.reset-password'), [
            'recover_role'         => 'resident',
            'recover_phone_number' => '+639171234567',
        ])->assertRedirect(route('password.otp'));

        // Step 2: Verify OTP
        $this->get(route('password.otp'))
            ->assertOk()
            ->assertSee('+639******567');

        $this->post(route('password.otp.submit'), ['code' => $this->lastCode])
            ->assertRedirect(route('password.new'));

        // Step 3: Set new password
        $this->get(route('password.new'))->assertOk();

        $this->post(route('password.new.submit'), [
            'new_password'              => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertNull($user->active_session_token);
    }

    public function test_new_password_page_blocked_without_verified_otp(): void
    {
        $this->get(route('password.new'))
            ->assertRedirect(route('login'));

        $this->post(route('password.new.submit'), [
            'new_password'              => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ])->assertRedirect(route('login'));
    }

    public function test_five_wrong_codes_trigger_lockout(): void
    {
        $user = User::factory()->resident()->create([
            'first_name'   => 'Jose',
            'last_name'    => 'Rizal',
            'phone_number' => '09178889999',
        ]);

        $this->post(route('forgot.reset-password'), [
            'recover_role'         => 'resident',
            'recover_phone_number' => '09178889999',
        ])->assertRedirect(route('password.otp'));

        $wrong = $this->lastCode === '000000' ? '999999' : '000000';
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.otp.submit'), ['code' => $wrong]);
        }

        $this->assertDatabaseHas('otp_lockouts', [
            'phone'   => $user->phone_number,
            'purpose' => 'password_reset',
        ]);

        // Advance past 1-minute route throttle; 30-minute OTP lockout must still block modal request
        $this->travel(65)->seconds();

        // New request from modal is blocked by lockout
        $blocked = $this->from(route('login'))->post(route('forgot.reset-password'), [
            'recover_role'         => 'resident',
            'recover_phone_number' => '09178889999',
        ]);
        $blocked->assertRedirect(route('login'));
        $blocked->assertSessionHasErrors('forgot', null, 'forgot');
    }
}
