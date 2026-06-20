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

    public function test_super_admin_can_ban_and_unban_user_with_reason(): void
    {
        $org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas', 'is_active' => true]);
        $target = $this->userIn($org, 'Operator', 'op@test.local');
        $super = User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);

        $this->actingAs($super)
            ->post("/users/{$target->id}/ban", ['reason' => 'Pelanggaran ketentuan penggunaan.'])
            ->assertRedirect();

        $target->refresh();
        $this->assertTrue($target->isBanned());
        $this->assertSame(User::STATUS_SUSPENDED, $target->status);
        $this->assertSame('Pelanggaran ketentuan penggunaan.', $target->banned_reason);
        $this->assertSame($super->id, $target->banned_by);

        $this->actingAs($super)->post("/users/{$target->id}/unban")->assertRedirect();

        $target->refresh();
        $this->assertFalse($target->isBanned());
        $this->assertSame(User::STATUS_APPROVED, $target->status);
        $this->assertNull($target->banned_reason);
    }

    public function test_ban_requires_a_reason(): void
    {
        $org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas', 'is_active' => true]);
        $target = $this->userIn($org, 'Operator', 'op@test.local');
        $super = User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);

        $this->actingAs($super)
            ->post("/users/{$target->id}/ban", ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertFalse($target->refresh()->isBanned());
    }

    public function test_admin_cannot_ban_users(): void
    {
        $org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas', 'is_active' => true]);
        $admin = $this->userIn($org, 'Admin', 'admin@test.local');
        $target = $this->userIn($org, 'Operator', 'op@test.local');

        $this->actingAs($admin)
            ->post("/users/{$target->id}/ban", ['reason' => 'tidak boleh'])
            ->assertForbidden();

        $this->assertFalse($target->refresh()->isBanned());
    }

    public function test_super_admin_cannot_ban_another_super_admin_or_self(): void
    {
        $super = User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);
        $otherSuper = User::create(['name' => 'Super 2', 'email' => 'super2@test.local', 'password' => 'password']);

        $this->actingAs($super)
            ->post("/users/{$otherSuper->id}/ban", ['reason' => 'percobaan'])
            ->assertForbidden();

        $this->actingAs($super)
            ->post("/users/{$super->id}/ban", ['reason' => 'percobaan'])
            ->assertForbidden();
    }

    public function test_banned_user_cannot_login_and_sees_reason(): void
    {
        $org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas', 'is_active' => true]);
        $target = $this->userIn($org, 'Operator', 'op@test.local');
        $target->forceFill(['password' => bcrypt('secret123')])->save();
        $super = User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);

        app(\App\Services\BanService::class)->ban($target->refresh(), $super, 'Akun disalahgunakan.');

        $response = $this->post('/login', ['email' => 'op@test.local', 'password' => 'secret123']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Akun disalahgunakan.', session('errors')->first('email'));
        $this->assertGuest();
    }
}
