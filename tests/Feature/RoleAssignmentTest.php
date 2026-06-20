<?php

/**
 * tests/Feature/RoleAssignmentTest.php
 * Acceptance Bagian 1: SuperAdmin mengubah peran user (Admin/Operator) dalam
 * konteks tim; non-SuperAdmin ditolak 403.
 */

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas']);
    }

    private function superAdmin(): User
    {
        return User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);
    }

    private function member(string $role = 'Operator'): User
    {
        $user = User::create(['name' => 'U', 'email' => fake()->unique()->safeEmail(), 'password' => 'password']);
        $user->forceFill([
            'organization_id' => $this->org->id,
            'nik' => fake()->numerify('################'),
            'phone' => '628'.fake()->numerify('#########'),
        ])->save();
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
        $user->assignRole($role);

        return $user;
    }

    public function test_super_admin_promotes_operator_to_admin(): void
    {
        $op = $this->member('Operator');

        $this->actingAs($this->superAdmin())
            ->post("/users/{$op->id}/role", ['role' => 'Admin'])
            ->assertRedirect();

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
        $this->assertTrue($op->fresh()->hasRole('Admin'));
        $this->assertFalse($op->fresh()->hasRole('Operator'));
    }

    public function test_super_admin_demotes_admin_to_operator(): void
    {
        $admin = $this->member('Admin');

        $this->actingAs($this->superAdmin())
            ->post("/users/{$admin->id}/role", ['role' => 'Operator'])
            ->assertRedirect();

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
        $this->assertTrue($admin->fresh()->hasRole('Operator'));
    }

    public function test_invalid_role_rejected(): void
    {
        $op = $this->member('Operator');

        $this->actingAs($this->superAdmin())
            ->post("/users/{$op->id}/role", ['role' => 'SuperAdmin'])
            ->assertSessionHasErrors('role');
    }

    public function test_non_super_admin_cannot_change_role(): void
    {
        $op = $this->member('Operator');
        $admin = $this->member('Admin');

        $this->actingAs($admin)->post("/users/{$op->id}/role", ['role' => 'Admin'])
            ->assertForbidden();
    }
}
