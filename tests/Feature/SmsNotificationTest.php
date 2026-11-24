<?php

namespace Tests\Feature;

use App\Models\HomeServiceRequest;
use App\Models\MedicineRequest;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsNotificationTest extends TestCase
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
                'data'   => ['id' => 'notif-msg-1'],
            ], 200),
        ]);
    }

    public function test_pickup_and_delivery_approve_each_send_once(): void
    {
        $nurse = User::factory()->nurse()->create(['active_session_token' => 'tok-n']);
        $bhw = User::factory()->bhw()->create(['first_name' => 'Maria', 'last_name' => 'Santos', 'active_session_token' => 'tok-b']);
        $resident = User::factory()->resident()->create(['phone_number' => '09171112222']);

        $pickup = MedicineRequest::create([
            'user_id'         => $resident->id,
            'type'            => 'Medicine',
            'item_service'    => 'Paracetamol 500mg',
            'quantity'        => 5,
            'delivery_method' => 'pickup',
            'status'          => 'Pending',
        ]);

        $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-n'])
            ->patchJson(route('requests.approve', $pickup))
            ->assertOk();

        $pickup->refresh();
        $this->assertNotNull($pickup->approved_sms_at);
        $this->assertSame(1, SmsLog::where('purpose', 'medicine_pickup')->where('status', 'sent')->count());

        // Delivery approved by BHW includes BHW name
        $delivery = MedicineRequest::create([
            'user_id'         => $resident->id,
            'type'            => 'Medicine',
            'item_service'    => 'Amoxicillin 500mg',
            'quantity'        => 3,
            'delivery_method' => 'delivery',
            'status'          => 'Pending',
        ]);

        $this->actingAs($bhw)
            ->withSession(['active_session_token' => 'tok-b'])
            ->patchJson(route('requests.approve', $delivery))
            ->assertOk();

        $this->assertSame(1, SmsLog::where('purpose', 'medicine_delivery')->where('status', 'sent')->count());
        Http::assertSent(fn ($req) => str_contains((string) $req['content'], 'BHW Maria Santos is on the way'));
    }

    private function clearTerminatingCallbacks(): void
    {
        $ref = new \ReflectionProperty($this->app, 'terminatingCallbacks');
        $ref->setValue($this->app, []);
    }

    public function test_home_service_accept_then_confirm_sends_only_once(): void
    {
        $bhw = User::factory()->bhw()->create(['active_session_token' => 'tok-bhw']);
        $resident = User::factory()->resident()->create(['phone_number' => '09173334444']);

        $hs = HomeServiceRequest::create([
            'user_id'        => $resident->id,
            'patient_name'   => 'Test Resident',
            'service_type'   => 'Vital Signs Monitoring',
            'address'        => 'Purok 1',
            'contact_number' => '09173334444',
            'preferred_date' => now()->toDateString(),
            'preferred_time' => '09:00 AM',
            'status'         => 'Pending',
        ]);

        $this->actingAs($bhw)
            ->withSession(['active_session_token' => 'tok-bhw'])
            ->patchJson(route('bhw.home-services.accept', $hs))
            ->assertOk();

        $this->clearTerminatingCallbacks();

        $this->actingAs($bhw)
            ->withSession(['active_session_token' => 'tok-bhw'])
            ->patchJson(route('bhw.home-services.confirm', $hs))
            ->assertOk();

        $this->assertSame(1, SmsLog::where('purpose', 'home_service_on_the_way')->where('status', 'sent')->count());
        Http::assertSentCount(1);
    }

    public function test_walkin_placeholder_is_skipped_and_unconfigured_key_still_succeeds(): void
    {
        $nurse = User::factory()->nurse()->create(['active_session_token' => 'tok-n2']);
        $walkin = User::factory()->walkin()->create();

        $req = MedicineRequest::create([
            'user_id'         => $walkin->id,
            'type'            => 'Medicine',
            'item_service'    => 'Vitamin C',
            'quantity'        => 2,
            'delivery_method' => 'pickup',
            'status'          => 'Pending',
        ]);

        $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-n2'])
            ->patchJson(route('requests.approve', $req))
            ->assertOk();

        Http::assertNothingSent();
        $this->assertDatabaseHas('sms_logs', [
            'purpose'    => 'medicine_pickup',
            'status'     => 'skipped',
            'error_code' => 'invalid_or_placeholder',
        ]);

        // Unconfigured key still returns 200 and logs skipped/not_configured
        Config::set('services.httpsms.api_key', '');
        $validResident = User::factory()->resident()->create(['phone_number' => '09175556666']);
        $req2 = MedicineRequest::create([
            'user_id'         => $validResident->id,
            'type'            => 'Medicine',
            'item_service'    => 'Cetirizine',
            'quantity'        => 2,
            'delivery_method' => 'pickup',
            'status'          => 'Pending',
        ]);

        $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-n2'])
            ->patchJson(route('requests.approve', $req2))
            ->assertOk();

        Http::assertNothingSent();
        $this->assertDatabaseHas('sms_logs', [
            'purpose'    => 'medicine_pickup',
            'status'     => 'skipped',
            'error_code' => 'not_configured',
        ]);
    }
}
