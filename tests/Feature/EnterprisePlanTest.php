<?php

/**
 * tests/Feature/EnterprisePlanTest.php
 * Acceptance Bagian 3: aktivasi Enterprise wajib 2FA; Enterprise+2FA bebas-credit;
 * Enterprise tanpa 2FA tetap terpotong; hanya SuperAdmin yang menetapkan.
 */

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EnterprisePlanTest extends TestCase
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

    private function operator(int $balance = 60, bool $with2fa = false): User
    {
        $user = User::create(['name' => 'Op', 'email' => fake()->unique()->safeEmail(), 'password' => 'password']);
        $user->forceFill([
            'organization_id' => $this->org->id,
            'nik' => fake()->numerify('################'),
            'phone' => '628'.fake()->numerify('#########'),
            'credit_balance' => $balance,
        ])->save();
        if ($with2fa) {
            $user->forceFill([
                'two_factor_secret' => 'SECRET', 'two_factor_confirmed_at' => now(),
            ])->save();
        }
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
        $user->assignRole('Operator');

        return $user;
    }

    public function test_activation_without_2fa_is_rejected(): void
    {
        $op = $this->operator(with2fa: false);

        $this->actingAs($this->superAdmin())
            ->post("/users/{$op->id}/plan", ['action' => 'activate'])
            ->assertStatus(422);

        $this->assertSame('free', $op->fresh()->plan);
    }

    public function test_activation_with_2fa_succeeds(): void
    {
        $op = $this->operator(with2fa: true);

        $this->actingAs($this->superAdmin())
            ->post("/users/{$op->id}/plan", ['action' => 'activate'])
            ->assertRedirect();

        $fresh = $op->fresh();
        $this->assertSame('enterprise', $fresh->plan);
        $this->assertTrue($fresh->isEnterpriseActive());
    }

    public function test_enterprise_with_2fa_is_credit_exempt(): void
    {
        $op = $this->operator(balance: 60, with2fa: true);
        $op->forceFill(['plan' => 'enterprise', 'enterprise_started_at' => now(), 'enterprise_expires_at' => now()->addYear()])->save();

        $this->actingAs($op)->post('/events', ['nama' => 'Gratis', 'jadwal_mulai' => now()->toDateTimeString()])
            ->assertRedirect();

        $this->assertSame(60, $op->fresh()->credit_balance); // tidak terpotong
    }

    public function test_enterprise_without_2fa_still_charged(): void
    {
        $op = $this->operator(balance: 60, with2fa: false);
        // Paket ada tapi 2FA mati → benefit diblokir.
        $op->forceFill(['plan' => 'enterprise', 'enterprise_started_at' => now(), 'enterprise_expires_at' => now()->addYear()])->save();

        $this->actingAs($op)->post('/events', ['nama' => 'Bayar', 'jadwal_mulai' => now()->toDateTimeString()])
            ->assertRedirect();

        $this->assertSame(10, $op->fresh()->credit_balance); // tetap terpotong 50
    }

    public function test_non_super_admin_cannot_set_plan(): void
    {
        $op = $this->operator(with2fa: true);
        $other = $this->operator(with2fa: false);

        $this->actingAs($other)->post("/users/{$op->id}/plan", ['action' => 'activate'])
            ->assertForbidden();
    }
}
