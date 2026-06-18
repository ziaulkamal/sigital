<?php

/**
 * tests/Feature/ProfileManagementTest.php
 * Acceptance P4: user dapat ganti nama/email (langsung + audit, tanpa verifikasi ulang — Q4)
 * & ganti password (wajib password lama benar). Perubahan tercatat di AuditLog.
 */

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function user(): User
    {
        $org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas']);
        $user = User::create(['name' => 'Lama', 'email' => 'lama@test.local', 'password' => 'password123']);
        $user->forceFill(['organization_id' => $org->id, 'nik' => fake()->unique()->numerify('################'), 'phone' => '628'.fake()->numerify('#########')])->save();

        return $user;
    }

    public function test_user_can_update_name_and_email(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->patch('/settings/profile', ['name' => 'Baru', 'email' => 'baru@test.local'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Baru', $user->name);
        $this->assertSame('baru@test.local', $user->email);
        $this->assertDatabaseHas('audit_logs', ['aksi' => 'user.profile_updated', 'actor_id' => $user->id]);
    }

    public function test_email_must_be_unique(): void
    {
        $user = $this->user();
        User::create(['name' => 'Lain', 'email' => 'dipakai@test.local', 'password' => 'password123']);

        $this->actingAs($user)
            ->patch('/settings/profile', ['name' => 'Baru', 'email' => 'dipakai@test.local'])
            ->assertSessionHasErrors('email');
    }

    public function test_keeping_same_email_is_allowed(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->patch('/settings/profile', ['name' => 'Baru', 'email' => 'lama@test.local'])
            ->assertSessionHasNoErrors();
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->put('/settings/password', [
                'current_password' => 'salah',
                'password' => 'rahasiabaru',
                'password_confirmation' => 'rahasiabaru',
            ])
            ->assertSessionHasErrors('current_password');
    }

    public function test_user_can_change_password(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->put('/settings/password', [
                'current_password' => 'password123',
                'password' => 'rahasiabaru',
                'password_confirmation' => 'rahasiabaru',
            ])
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('rahasiabaru', $user->password));
        $this->assertDatabaseHas('audit_logs', ['aksi' => 'user.password_changed', 'actor_id' => $user->id]);
    }
}
