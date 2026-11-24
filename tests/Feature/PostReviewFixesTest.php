<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_registration_is_excluded_from_reports_and_user_management(): void
    {
        $nurse = User::factory()->nurse()->create(['status' => 'active']);

        User::factory()->resident()->create([
            'first_name'              => 'Verified',
            'last_name'               => 'Resident',
            'phone_number'            => '09171110001',
            'status'                  => 'active',
            'registration_unverified' => false,
        ]);

        $unverified = User::factory()->unverifiedRegistration()->create([
            'first_name'   => 'Ghost',
            'last_name'    => 'Unverified',
            'phone_number' => '09171110002',
        ]);

        $reportsResponse = $this->actingAs($nurse)->get(route('nurse.reports'));
        $reportsResponse->assertOk();
        $reportsResponse->assertViewHas('totalPatients', 1);

        $usersResponse = $this->actingAs($nurse)->get(route('nurse.users'));
        $usersResponse->assertOk();
        $usersResponse->assertViewHas('totalResidents', 1);
        $usersResponse->assertDontSee('Ghost Unverified');
        $this->assertFalse(
            $usersResponse->viewData('users')->contains('id', $unverified->id)
        );
    }

    public function test_staff_creating_user_with_phone_held_by_unverified_registration_succeeds_and_purges_old_row(): void
    {
        $nurse = User::factory()->nurse()->create(['status' => 'active']);
        $bhw = User::factory()->bhw()->create([
            'status'            => 'active',
            'can_manage_nurses' => true,
        ]);

        // 1. Nurse creates user via UserManagementController::store
        Storage::disk('public')->put('id_photos/stale1.jpg', 'fake-image');
        $stale1 = User::factory()->unverifiedRegistration()->create([
            'phone_number' => '09172220001',
            'id_photo'     => 'id_photos/stale1.jpg',
        ]);

        $this->actingAs($nurse)->post(route('users.store'), [
            'first_name'            => 'Maria',
            'last_name'             => 'Santos',
            'phone_number'          => '09172220001',
            'role'                  => 'resident',
            'password'              => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertRedirect(route('nurse.users'));

        $this->assertDatabaseMissing('users', ['id' => $stale1->id]);
        Storage::disk('public')->assertMissing('id_photos/stale1.jpg');
        $this->assertDatabaseHas('users', [
            'phone_number'            => '09172220001',
            'first_name'              => 'Maria',
            'registration_unverified' => false,
        ]);

        // 2. BHW creates nurse via BhwDashboardController::storeNurse
        Storage::disk('public')->put('id_photos/stale2.jpg', 'fake-image');
        $stale2 = User::factory()->unverifiedRegistration()->create([
            'phone_number' => '09172220002',
            'id_photo'     => 'id_photos/stale2.jpg',
        ]);

        $this->actingAs($bhw)->postJson(route('bhw.nurses.store'), [
            'first_name'            => 'Elena',
            'last_name'             => 'Reyes',
            'phone_number'          => '09172220002',
            'password'              => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('users', ['id' => $stale2->id]);
        Storage::disk('public')->assertMissing('id_photos/stale2.jpg');
        $this->assertDatabaseHas('users', [
            'phone_number'            => '09172220002',
            'role'                    => 'nurse',
            'registration_unverified' => false,
        ]);

        // 3. BHW creates walk-in via WalkinController::store
        Storage::disk('public')->put('id_photos/stale3.jpg', 'fake-image');
        $stale3 = User::factory()->unverifiedRegistration()->create([
            'phone_number' => '09172220003',
            'id_photo'     => 'id_photos/stale3.jpg',
        ]);

        $this->actingAs($bhw)->postJson(route('bhw.walkin.store'), [
            'first_name'   => 'Carlos',
            'last_name'    => 'Diaz',
            'phone_number' => '09172220003',
            'purpose'      => 'Checkup',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('users', ['id' => $stale3->id]);
        Storage::disk('public')->assertMissing('id_photos/stale3.jpg');
        $this->assertDatabaseHas('users', [
            'phone_number'            => '09172220003',
            'first_name'              => 'Carlos',
            'registration_unverified' => false,
        ]);
    }

    public function test_prune_unverified_registrations_command_respects_dry_run_and_hours(): void
    {
        Storage::disk('public')->put('id_photos/old.jpg', 'fake-old');
        Storage::disk('public')->put('id_photos/recent.jpg', 'fake-recent');

        $oldUnverified = User::factory()->unverifiedRegistration()->create([
            'phone_number' => '09173330001',
            'id_photo'     => 'id_photos/old.jpg',
            'created_at'   => now()->subHours(30),
        ]);

        $recentUnverified = User::factory()->unverifiedRegistration()->create([
            'phone_number' => '09173330002',
            'id_photo'     => 'id_photos/recent.jpg',
            'created_at'   => now()->subHours(2),
        ]);

        $oldVerified = User::factory()->resident()->create([
            'phone_number'            => '09173330003',
            'registration_unverified' => false,
            'created_at'              => now()->subHours(48),
        ]);

        // Dry run deletes nothing
        $this->artisan('registrations:prune-unverified', ['--hours' => 24, '--dry-run' => true])
            ->expectsOutputToContain('1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['id' => $oldUnverified->id]);
        $this->assertDatabaseHas('users', ['id' => $recentUnverified->id]);
        $this->assertDatabaseHas('users', ['id' => $oldVerified->id]);
        Storage::disk('public')->assertExists('id_photos/old.jpg');

        // Real run deletes only unverified rows older than 24 hours
        $this->artisan('registrations:prune-unverified', ['--hours' => 24])
            ->expectsOutputToContain('1')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['id' => $oldUnverified->id]);
        Storage::disk('public')->assertMissing('id_photos/old.jpg');

        $this->assertDatabaseHas('users', ['id' => $recentUnverified->id]);
        Storage::disk('public')->assertExists('id_photos/recent.jpg');
        $this->assertDatabaseHas('users', ['id' => $oldVerified->id]);
    }
}
