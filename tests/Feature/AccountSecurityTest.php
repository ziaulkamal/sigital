<?php

/**
 * tests/Feature/AccountSecurityTest.php
 * Pengaturan akun: nonaktifkan akun (wajib password) + blokir login akun nonaktif;
 * Dashboard menyajikan data nyata (ter-scope) untuk user & SuperAdmin.
 */

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function approvedUser(): User
    {
        $org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas']);
        $user = User::create(['name' => 'U', 'email' => 'u@test.local', 'password' => 'password123']);
        $user->forceFill(['organization_id' => $org->id, 'status' => User::STATUS_APPROVED, 'nik' => fake()->unique()->numerify('################'), 'phone' => '628'.fake()->numerify('#########')])->save();
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $user->assignRole('Operator');

        return $user;
    }

    public function test_deactivate_requires_correct_password(): void
    {
        $user = $this->approvedUser();

        $this->actingAs($user)->delete('/settings/account', ['current_password' => 'salah'])
            ->assertSessionHasErrors('current_password');
        $this->assertSame(User::STATUS_APPROVED, $user->fresh()->status);
    }

    public function test_user_can_deactivate_account(): void
    {
        $user = $this->approvedUser();

        $this->actingAs($user)->delete('/settings/account', ['current_password' => 'password123'])
            ->assertRedirect('/login');

        $this->assertSame(User::STATUS_SUSPENDED, $user->fresh()->status);
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['aksi' => 'user.deactivated', 'actor_id' => $user->id]);
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = $this->approvedUser();
        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();

        $this->post('/login', ['email' => 'u@test.local', 'password' => 'password123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_dashboard_renders_for_org_user(): void
    {
        $user = $this->approvedUser();
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_dashboard_renders_for_super_admin(): void
    {
        $this->approvedUser();
        $super = User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);

        $this->actingAs($super)->get('/dashboard')->assertOk();
    }
}
