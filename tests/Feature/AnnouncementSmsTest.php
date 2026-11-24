<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnnouncementSmsTest extends TestCase
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
                'data'   => ['id' => 'ann-msg-1'],
            ], 200),
        ]);
    }

    public function test_toggle_off_sends_no_sms(): void
    {
        $nurse = User::factory()->nurse()->create(['active_session_token' => 'tok-n']);
        User::factory()->resident()->create(['phone_number' => '09171110001']);

        $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-n'])
            ->postJson(route('announcements.store'), [
                'title'    => 'Health Advisory',
                'content'  => 'Bring your health card.',
                'status'   => 'active',
                'send_sms' => false,
            ])
            ->assertOk();

        Http::assertNothingSent();
        $this->assertSame(0, SmsLog::where('purpose', 'announcement')->count());
    }

    private function clearTerminatingCallbacks(): void
    {
        $ref = new \ReflectionProperty($this->app, 'terminatingCallbacks');
        $ref->setValue($this->app, []);
    }

    public function test_toggle_on_filters_recipients_and_edit_never_sends(): void
    {
        $nurse = User::factory()->nurse()->create(['active_session_token' => 'tok-n']);
        User::factory()->bhw()->create(['phone_number' => '09171110099']); // staff excluded

        // Eligible (2)
        User::factory()->resident()->create(['phone_number' => '09171110001']);
        User::factory()->resident()->create(['phone_number' => '09171110002']);

        // Excluded
        User::factory()->resident()->inactive()->create(['phone_number' => '09171110003']);
        User::factory()->resident()->pending()->create(['phone_number' => '09171110004']);
        User::factory()->unverifiedRegistration()->create(['phone_number' => '09171110005']);
        User::factory()->walkin()->create();

        $res = $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-n'])
            ->postJson(route('announcements.store'), [
                'title'    => 'Free Vaccination',
                'content'  => 'Visit the center at 8 AM.',
                'status'   => 'active',
                'send_sms' => true,
            ]);

        $res->assertOk()
            ->assertJson(['success' => true]);

        $ann = Announcement::latest('id')->first();
        $this->assertNotNull($ann->sms_sent_at);
        $this->assertSame(2, $ann->sms_recipient_count);
        $this->assertSame(2, SmsLog::where('purpose', 'announcement')->where('status', 'sent')->count());
        Http::assertSentCount(2);

        $this->clearTerminatingCallbacks();

        // Archived announcement with toggle on sends no SMS
        $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-n'])
            ->postJson(route('announcements.store'), [
                'title'    => 'Draft Only',
                'content'  => 'Hidden',
                'status'   => 'archived',
                'send_sms' => true,
            ])->assertOk();

        Http::assertSentCount(2);

        $this->clearTerminatingCallbacks();

        // Edit never resends SMS
        $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-n'])
            ->putJson(route('announcements.update', $ann), [
                'title'    => 'Free Vaccination Updated',
                'content'  => 'Visit the center at 9 AM.',
                'status'   => 'active',
                'send_sms' => true,
            ])->assertOk();

        Http::assertSentCount(2);
    }
}
