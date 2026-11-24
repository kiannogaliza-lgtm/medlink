<?php

namespace Tests\Feature;

use App\Models\AccountLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhoneChangeOtpTest extends TestCase
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

                return Http::response(['status' => 'success', 'data' => ['id' => 'pc-msg-1']], 200);
            },
        ]);
    }

    public function test_phone_remains_unchanged_until_otp_verified(): void
    {
        $user = User::factory()->resident()->create([
            'first_name'           => 'Ana',
            'last_name'            => 'Santos',
            'phone_number'         => '09171110001',
            'active_session_token' => 'tok-1',
        ]);

        $res = $this->actingAs($user)
            ->withSession(['active_session_token' => 'tok-1'])
            ->putJson(route('resident.profile.update'), [
                'first_name'   => 'Ana Updated',
                'last_name'    => 'Santos',
                'phone_number' => '+639172220002',
            ]);

        $res->assertOk()
            ->assertJson([
                'success'      => true,
                'otp_required' => true,
                'masked'       => '+639******002',
            ]);

        $user->refresh();
        $this->assertSame('Ana Updated', $user->first_name);
        $this->assertSame('09171110001', $user->phone_number);
        $this->assertSame('09172220002', $user->pending_phone_number);

        // Verify OTP -> phone updates & AccountLog has masked numbers only
        $verify = $this->actingAs($user)
            ->withSession(['active_session_token' => 'tok-1', 'phone_change_format' => 'e164'])
            ->postJson(route('account.phone.verify'), ['code' => $this->lastCode]);

        $verify->assertOk()->assertJson(['success' => true]);

        $user->refresh();
        $this->assertSame('09172220002', $user->phone_number);
        $this->assertNull($user->pending_phone_number);
        $this->assertNotNull($user->phone_verified_at);

        $log = AccountLog::where('action', 'Phone Number Changed')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('09******001', $log->details);
        $this->assertStringContainsString('+639******002', $log->details);
        $this->assertStringNotContainsString('09172220002', $log->details);
    }

    public function test_cancel_clears_pending_phone_number(): void
    {
        $user = User::factory()->bhw()->create([
            'phone_number'         => '09173330001',
            'pending_phone_number' => '09173330002',
            'active_session_token' => 'tok-2',
        ]);

        $this->actingAs($user)
            ->withSession(['active_session_token' => 'tok-2'])
            ->postJson(route('account.phone.cancel'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $user->refresh();
        $this->assertSame('09173330001', $user->phone_number);
        $this->assertNull($user->pending_phone_number);
    }

    public function test_duplicate_phone_number_is_rejected(): void
    {
        User::factory()->resident()->create(['phone_number' => '09174440009']);

        $user = User::factory()->resident()->create([
            'phone_number'         => '09174440001',
            'active_session_token' => 'tok-3',
        ]);

        $this->actingAs($user)
            ->withSession(['active_session_token' => 'tok-3'])
            ->putJson(route('resident.profile.update'), [
                'first_name'   => $user->first_name,
                'last_name'    => $user->last_name,
                'phone_number' => '+639174440009',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone_number');

        Http::assertNothingSent();
    }
}
