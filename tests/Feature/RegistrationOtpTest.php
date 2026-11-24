<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegistrationOtpTest extends TestCase
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

                return Http::response(['status' => 'success', 'data' => ['id' => 'reg-msg-1']], 200);
            },
        ]);
    }

    private function registrationPayload(string $phone = '+639171234567'): array
    {
        return [
            'first_name'             => 'Juan',
            'last_name'              => 'Dela Cruz',
            'phone_number'           => $phone,
            'dob'                    => '1995-05-10',
            'patient_classification' => 'Regular Patient',
            'password'               => 'secret1234',
            'password_confirmation'  => 'secret1234',
            'id_photo'               => UploadedFile::fake()->image('id.jpg'),
        ];
    }

    public function test_register_requires_otp_before_login_and_nurse_notification(): void
    {
        $nurse = User::factory()->nurse()->create();

        $res = $this->post('/register', $this->registrationPayload('+639171234567'));
        $res->assertRedirect(route('register.verify'));

        $this->assertGuest();

        $user = User::where('phone_number', '09171234567')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->registration_unverified);
        $this->assertNull($user->phone_verified_at);

        // Nurse is NOT notified before OTP verification
        $this->assertSame(0, AppNotification::where('user_id', $nurse->id)->count());

        // OTP screen shows +639******567 matching typed format
        $this->get(route('register.verify'))
            ->assertOk()
            ->assertSee('+639******567');

        // Verify OTP -> logged in, phone_verified_at set, nurse notified, redirects to pending.approval
        $verify = $this->post(route('register.verify.submit'), ['code' => $this->lastCode]);
        $verify->assertRedirect(route('pending.approval'));

        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertFalse($user->registration_unverified);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertSame(1, AppNotification::where('user_id', $nurse->id)->count());
    }

    public function test_reregistering_same_unverified_number_replaces_old_row(): void
    {
        $this->post('/register', $this->registrationPayload('09179998888'))
            ->assertRedirect(route('register.verify'));

        $first = User::where('phone_number', '09179998888')->first();
        $this->assertNotNull($first);
        $oldId = $first->id;

        $this->travel(65)->seconds();

        $this->post('/register', array_merge($this->registrationPayload('+639179998888'), ['first_name' => 'Pedro']))
            ->assertRedirect(route('register.verify'));

        $this->assertDatabaseMissing('users', ['id' => $oldId]);
        $this->assertSame(1, User::where('phone_number', '09179998888')->count());
        $this->assertSame('Pedro', User::where('phone_number', '09179998888')->value('first_name'));
    }

    public function test_unverified_user_login_redirects_to_registration_verify(): void
    {
        $user = User::factory()->unverifiedRegistration()->create([
            'phone_number' => '09175556666',
            'password'     => 'secret1234',
        ]);

        $res = $this->post('/login', [
            'phone_number' => '09175556666',
            'password'     => 'secret1234',
            'role'         => 'resident',
        ]);

        $res->assertRedirect(route('register.verify'));
        $this->assertGuest();
        $this->assertSame($user->id, session('reg_otp_user_id'));
    }

    public function test_sixth_registration_within_ten_minutes_from_same_ip_is_throttled(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/register', $this->registrationPayload('0917100000' . $i))
                ->assertRedirect(route('register.verify'));
        }

        $this->post('/register', $this->registrationPayload('09171000006'))
            ->assertStatus(429);
    }
}
