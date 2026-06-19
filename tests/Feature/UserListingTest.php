<?php

/**
 * tests/Feature/UserListingTest.php
 * Visibilitas daftar pengguna: SuperAdmin lintas-instansi; Admin hanya instansinya;
 * Operator tidak punya akses.
 */

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function userIn(Organization $org, string $role, string $email): User
    {
        $user = User::create(['name' => $role.' '.$org->kode, 'email' => $email, 'password' => 'password']);
        $user->forceFill([
            'organization_id' => $org->id,
            'status' => User::STATUS_APPROVED,
            'nik' => fake()->unique()->numerify('################'),
            'phone' => '628'.fake()->numerify('#########'),
        ])->save();
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_only_sees_users_from_own_instansi(): void
    {
        $orgA = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas', 'is_active' => true]);
        $orgB = Organization::create(['nama' => 'Dinas B', 'kode' => 'DINASB', 'type' => 'dinas', 'is_active' => true]);

        $adminA = $this->userIn($orgA, 'Admin', 'admin-a@test.local');
        $this->userIn($orgA, 'Operator', 'op-a@test.local');
        $this->userIn($orgB, 'Operator', 'op-b@test.local');

        $this->actingAs($adminA)->get('/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Users/Index')
                ->where('isSuperAdmin', false)
                ->has('users', 2) // adminA + op-a (orgA saja)
            );
    }

    public function test_super_admin_sees_all_users(): void
    {
        $orgA = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas', 'is_active' => true]);
        $this->userIn($orgA, 'Admin', 'admin-a@test.local');
        $super = User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);

        $this->actingAs($super)->get('/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Users/Index')
                ->where('isSuperAdmin', true)
                ->has('users', 2) // adminA + super
            );
    }

    public function test_operator_cannot_access_user_listing(): void
    {
        $org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas', 'is_active' => true]);
        $operator = $this->userIn($org, 'Operator', 'op@test.local');

        $this->actingAs($operator)->get('/users')->assertForbidden();
    }

    public function test_pending_registrant_is_visible_to_admin(): void
    {
        $org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas', 'is_active' => true]);
        $admin = $this->userIn($org, 'Admin', 'admin@test.local');

        // Pendaftar pending pada instansi yang sama.
        $pending = User::create(['name' => 'Pendaftar', 'email' => 'p@test.local', 'password' => 'password']);
        $pending->forceFill(['organization_id' => $org->id, 'status' => User::STATUS_PENDING])->save();

        $this->actingAs($admin)->get('/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('users', 2));
    }
}
