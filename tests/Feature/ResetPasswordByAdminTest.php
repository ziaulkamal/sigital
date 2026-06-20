<?php

/**
 * tests/Feature/ResetPasswordByAdminTest.php
 * SuperAdmin dapat reset password user mana pun; Admin hanya pada instansinya sendiri.
 * Tidak boleh menyasar SuperAdmin atau diri sendiri.
 */

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ResetPasswordByAdminTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;
    private Organization $orgB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->orgA = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas']);
        $this->orgB = Organization::create(['nama' => 'Komunitas B', 'kode' => 'KOMB', 'type' => 'komunitas']);
    }

    private function superAdmin(): User
    {
        return User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);
    }

    private function member(Organization $org, string $role): User
    {
        $user = User::create(['name' => 'U', 'email' => fake()->unique()->safeEmail(), 'password' => 'oldpassword']);
        $user->forceFill([
            'organization_id' => $org->id,
            'nik' => fake()->numerify('################'),
            'phone' => '628'.fake()->numerify('#########'),
        ])->save();
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $user->assignRole($role);

        return $user;
    }

    public function test_super_admin_resets_any_user_password(): void
    {
        $target = $this->member($this->orgA, 'Operator');

        $this->actingAs($this->superAdmin())
            ->post("/users/{$target->id}/reset-password", ['password' => 'newsecret1', 'password_confirmation' => 'newsecret1'])
            ->assertRedirect();

        $this->assertTrue(Hash::check('newsecret1', $target->fresh()->password));
    }

    public function test_admin_resets_user_in_own_org(): void
    {
        $admin = $this->member($this->orgA, 'Admin');
        $target = $this->member($this->orgA, 'Operator');

        $this->actingAs($admin)
            ->post("/users/{$target->id}/reset-password", ['password' => 'newsecret1', 'password_confirmation' => 'newsecret1'])
            ->assertRedirect();

        $this->assertTrue(Hash::check('newsecret1', $target->fresh()->password));
    }

    public function test_admin_cannot_reset_user_in_other_org(): void
    {
        $admin = $this->member($this->orgA, 'Admin');
        $target = $this->member($this->orgB, 'Operator');

        $this->actingAs($admin)
            ->post("/users/{$target->id}/reset-password", ['password' => 'newsecret1', 'password_confirmation' => 'newsecret1'])
            ->assertForbidden();

        $this->assertTrue(Hash::check('oldpassword', $target->fresh()->password));
    }

    public function test_cannot_reset_super_admin_password(): void
    {
        $admin = $this->member($this->orgA, 'Admin');
        $super = $this->superAdmin();

        $this->actingAs($admin)
            ->post("/users/{$super->id}/reset-password", ['password' => 'newsecret1', 'password_confirmation' => 'newsecret1'])
            ->assertForbidden();
    }

    public function test_password_must_be_confirmed(): void
    {
        $target = $this->member($this->orgA, 'Operator');

        $this->actingAs($this->superAdmin())
            ->post("/users/{$target->id}/reset-password", ['password' => 'newsecret1', 'password_confirmation' => 'mismatch'])
            ->assertSessionHasErrors('password');
    }
}
