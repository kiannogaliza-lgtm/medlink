<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PatientVerificationSmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.httpsms.api_key', 'test-key');
        Config::set('services.httpsms.from', '+639000000000');

        Http::fake([
            'https://api.httpsms.com/v1/messages/send' => Http::response([
                'status' => 'success',
                'data'   => ['id' => 'verify-msg-1'],
            ], 200),
        ]);
    }

    private function clearTerminatingCallbacks(): void
    {
        $ref = new \ReflectionProperty($this->app, 'terminatingCallbacks');
        $ref->setValue($this->app, []);
    }

    public function test_verifying_pending_resident_activates_notifies_and_sends_sms(): void
    {
        $nurse = User::factory()->nurse()->create(['active_session_token' => 'tok-nurse-1']);
        $resident = User::factory()->resident()->pending()->create([
            'first_name'              => 'Juan',
            'last_name'               => 'Dela Cruz',
            'phone_number'            => '09171234567',
            'registration_unverified' => false,
        ]);

        $response = $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-nurse-1'])
            ->patch(route('patient.verify', $resident));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Patient Juan Dela Cruz has been verified and can now access the system. An SMS was sent to the resident.');

        $resident->refresh();
        $this->assertSame('active', $resident->status);

        $this->assertSame(1, AppNotification::where('user_id', $resident->id)->count());

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.httpsms.com/v1/messages/send'
                && $request['to'] === '+639171234567'
                && str_contains((string) $request['content'], 'MedLink: Hi Juan, your account is now verified');
        });

        $this->assertDatabaseHas('sms_logs', [
            'user_id'      => $resident->id,
            'purpose'      => 'account_approved',
            'status'       => 'sent',
            'phone_masked' => '09******567',
        ]);
    }

    public function test_verifying_twice_sends_only_one_sms_and_one_notification(): void
    {
        $nurse = User::factory()->nurse()->create(['active_session_token' => 'tok-nurse-2']);
        $resident = User::factory()->resident()->pending()->create([
            'phone_number'            => '09172223333',
            'registration_unverified' => false,
        ]);

        $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-nurse-2'])
            ->patch(route('patient.verify', $resident))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->clearTerminatingCallbacks();

        $second = $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-nurse-2'])
            ->patch(route('patient.verify', $resident));

        $second->assertRedirect();
        $second->assertSessionHas('error', 'This account is not awaiting verification.');

        Http::assertSentCount(1);
        $this->assertSame(1, AppNotification::where('user_id', $resident->id)->count());
        $this->assertSame(1, SmsLog::where('user_id', $resident->id)->where('purpose', 'account_approved')->count());
    }

    public function test_verifying_inactive_or_unverified_registration_makes_no_change_and_sends_no_sms(): void
    {
        $nurse = User::factory()->nurse()->create(['active_session_token' => 'tok-nurse-3']);

        $inactive = User::factory()->resident()->inactive()->create([
            'phone_number'            => '09174445555',
            'registration_unverified' => false,
        ]);

        $unverified = User::factory()->resident()->unverifiedRegistration()->create([
            'phone_number' => '09176667777',
        ]);

        $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-nurse-3'])
            ->patch(route('patient.verify', $inactive))
            ->assertRedirect()
            ->assertSessionHas('error', 'This account is not awaiting verification.');

        $this->clearTerminatingCallbacks();

        $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-nurse-3'])
            ->patch(route('patient.verify', $unverified))
            ->assertRedirect()
            ->assertSessionHas('error', 'This account is not awaiting verification.');

        $this->assertSame('inactive', $inactive->fresh()->status);
        $this->assertSame('pending', $unverified->fresh()->status);
        $this->assertTrue((bool) $unverified->fresh()->registration_unverified);

        Http::assertNothingSent();
        $this->assertSame(0, AppNotification::count());
        $this->assertSame(0, SmsLog::count());
    }

    public function test_verifying_with_blank_sms_key_still_succeeds_and_logs_skipped(): void
    {
        Config::set('services.httpsms.api_key', '');

        $nurse = User::factory()->nurse()->create(['active_session_token' => 'tok-nurse-4']);
        $resident = User::factory()->resident()->pending()->create([
            'first_name'              => 'Ana',
            'last_name'               => 'Reyes',
            'phone_number'            => '09178889999',
            'registration_unverified' => false,
        ]);

        $response = $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-nurse-4'])
            ->patch(route('patient.verify', $resident));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Patient Ana Reyes has been verified and can now access the system.');

        $this->assertSame('active', $resident->fresh()->status);
        Http::assertNothingSent();

        $this->assertDatabaseHas('sms_logs', [
            'user_id'    => $resident->id,
            'purpose'    => 'account_approved',
            'status'     => 'skipped',
            'error_code' => 'not_configured',
        ]);
    }

    public function test_rejecting_pending_resident_deletes_user_sends_sms_without_in_app_notification_and_rejecting_active_fails(): void
    {
        $nurse = User::factory()->nurse()->create(['active_session_token' => 'tok-nurse-5']);

        $pending = User::factory()->resident()->pending()->create([
            'first_name'              => 'Pedro',
            'last_name'               => 'Santos',
            'phone_number'            => '09171110000',
            'registration_unverified' => false,
        ]);

        $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-nurse-5'])
            ->patch(route('patient.reject', $pending))
            ->assertRedirect()
            ->assertSessionHas('success', 'Patient Pedro Santos has been rejected and removed. An SMS was sent to the resident.');

        $this->assertDatabaseMissing('users', ['id' => $pending->id]);
        $this->assertSame(0, AppNotification::where('user_id', $pending->id)->count());

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.httpsms.com/v1/messages/send'
                && $request['to'] === '+639171110000'
                && str_contains((string) $request['content'], 'MedLink: Your registration could not be verified.');
        });

        $this->assertDatabaseHas('sms_logs', [
            'purpose'      => 'account_rejected',
            'status'       => 'sent',
            'phone_masked' => '09******000',
        ]);

        $this->clearTerminatingCallbacks();

        $active = User::factory()->resident()->create([
            'status'                  => 'active',
            'phone_number'            => '09172220000',
            'registration_unverified' => false,
        ]);

        $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-nurse-5'])
            ->patch(route('patient.reject', $active))
            ->assertRedirect()
            ->assertSessionHas('error', 'This account is not awaiting verification.');

        $this->assertSame('active', $active->fresh()->status);
        $this->assertSame(0, AppNotification::where('user_id', $active->id)->count());
        Http::assertSentCount(1);
    }
}
