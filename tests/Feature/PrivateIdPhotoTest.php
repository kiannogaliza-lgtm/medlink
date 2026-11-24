<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateIdPhotoTest extends TestCase
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
                'data'   => ['id' => 'id-photo-test-1'],
            ], 200),
        ]);
    }

    public function test_registration_stores_id_photo_on_local_disk_only(): void
    {
        $response = $this->post('/register', [
            'first_name'             => 'Maria',
            'last_name'              => 'Clara',
            'phone_number'           => '09171234567',
            'dob'                    => '1995-06-15',
            'patient_classification' => 'Regular Patient',
            'password'               => 'password123',
            'password_confirmation'  => 'password123',
            'id_photo'               => UploadedFile::fake()->image('gov-id.jpg'),
        ]);

        $response->assertRedirect(route('register.verify'));

        $user = User::where('phone_number', '09171234567')->firstOrFail();
        $this->assertNotEmpty($user->id_photo);

        Storage::disk('local')->assertExists($user->id_photo);
        Storage::disk('public')->assertMissing($user->id_photo);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_id_photo_route_access_control_and_missing_file_handling(): void
    {
        $nurse = User::factory()->nurse()->create(['active_session_token' => 'tok-nurse']);
        $bhw = User::factory()->bhw()->create(['active_session_token' => 'tok-bhw']);
        $resident = User::factory()->resident()->pending()->create([
            'active_session_token' => 'tok-res',
            'id_photo'             => 'id_photos/private-id.jpg',
        ]);

        Storage::disk('local')->put('id_photos/private-id.jpg', 'fake-jpeg-bytes');

        // Nurse gets 200 with private cache & nosniff headers
        $nurseResponse = $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-nurse'])
            ->get(route('patient.id-photo', $resident));

        $nurseResponse->assertOk();
        $this->assertStringContainsString('private', (string) $nurseResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $nurseResponse->headers->get('Cache-Control'));
        $nurseResponse->assertHeader('X-Content-Type-Options', 'nosniff');

        // Legacy fallback on public disk still works for nurses
        $legacyResident = User::factory()->resident()->pending()->create([
            'id_photo' => 'id_photos/legacy-public.jpg',
        ]);
        Storage::disk('public')->put('id_photos/legacy-public.jpg', 'legacy-bytes');

        $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-nurse'])
            ->get(route('patient.id-photo', $legacyResident))
            ->assertOk();

        // Resident and BHW are forbidden (403 for JSON, redirect for web)
        $this->actingAs($resident)
            ->withSession(['active_session_token' => 'tok-res'])
            ->getJson(route('patient.id-photo', $resident))
            ->assertForbidden();

        $this->actingAs($bhw)
            ->withSession(['active_session_token' => 'tok-bhw'])
            ->getJson(route('patient.id-photo', $resident))
            ->assertForbidden();

        // Guest is redirected to login
        auth()->logout();
        $this->get(route('patient.id-photo', $resident))
            ->assertRedirect(route('login'));

        // Missing file -> 404
        $missingFileResident = User::factory()->resident()->pending()->create([
            'id_photo' => 'id_photos/does-not-exist.jpg',
        ]);
        $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-nurse'])
            ->get(route('patient.id-photo', $missingFileResident))
            ->assertNotFound();

        // Non-resident -> 404
        $nurseWithPhoto = User::factory()->nurse()->create([
            'id_photo' => 'id_photos/nurse-photo.jpg',
        ]);
        Storage::disk('local')->put('id_photos/nurse-photo.jpg', 'nurse-bytes');
        $this->actingAs($nurse)
            ->withSession(['active_session_token' => 'tok-nurse'])
            ->get(route('patient.id-photo', $nurseWithPhoto))
            ->assertNotFound();
    }

    public function test_resident_portal_json_does_not_contain_id_photo(): void
    {
        $resident = User::factory()->resident()->create([
            'active_session_token' => 'tok-res-portal',
            'id_photo'             => 'id_photos/secret-id-123.jpg',
        ]);

        $this->assertArrayNotHasKey('id_photo', $resident->toArray());
        $this->assertStringNotContainsString('secret-id-123.jpg', $resident->toJson());

        $response = $this->actingAs($resident)
            ->withSession(['active_session_token' => 'tok-res-portal'])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('id_photo', false);
        $response->assertDontSee('secret-id-123.jpg', false);
    }

    public function test_move_private_command_dry_run_and_real_run(): void
    {
        $legacyUser = User::factory()->resident()->create([
            'id_photo' => 'id_photos/legacy-move.jpg',
        ]);
        $alreadyPrivateUser = User::factory()->resident()->create([
            'id_photo' => 'id_photos/already-private.jpg',
        ]);

        Storage::disk('public')->put($legacyUser->id_photo, 'legacy-image-payload');
        Storage::disk('local')->put($alreadyPrivateUser->id_photo, 'private-image-payload');

        // Dry run moves nothing
        $this->artisan('id-photos:move-private', ['--dry-run' => true])
            ->expectsOutputToContain('would_move=1, already_private=1, missing=0, failed=0')
            ->assertExitCode(0);

        Storage::disk('public')->assertExists($legacyUser->id_photo);
        Storage::disk('local')->assertMissing($legacyUser->id_photo);

        // Real run moves and deletes public copy
        $this->artisan('id-photos:move-private')
            ->expectsOutputToContain('moved=1, already_private=1, missing=0, failed=0')
            ->assertExitCode(0);

        Storage::disk('local')->assertExists($legacyUser->id_photo);
        Storage::disk('public')->assertMissing($legacyUser->id_photo);
        $this->assertSame('legacy-image-payload', Storage::disk('local')->get($legacyUser->id_photo));

        // Already-private file remains intact
        Storage::disk('local')->assertExists($alreadyPrivateUser->id_photo);
    }
}
