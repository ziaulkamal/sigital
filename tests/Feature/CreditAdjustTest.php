<?php

/**
 * tests/Feature/CreditAdjustTest.php
 * Acceptance Bagian 2 (penyesuaian SuperAdmin dua arah): +30 menambah & ledger;
 * -100 saat saldo 60 → clamp ke 0 (tak minus); non-SuperAdmin 403.
 */

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreditAdjustTest extends TestCase
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

    private function operator(int $balance = 60): User
    {
        $user = User::create(['name' => 'Op', 'email' => fake()->unique()->safeEmail(), 'password' => 'password']);
        $user->forceFill([
            'organization_id' => $this->org->id,
            'nik' => fake()->numerify('################'),
            'phone' => '628'.fake()->numerify('#########'),
            'credit_balance' => $balance,
        ])->save();
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
        $user->assignRole('Operator');

        return $user;
    }

    public function test_positive_adjust_adds_balance_and_ledger(): void
    {
        $op = $this->operator(60);

        $this->actingAs($this->superAdmin())
            ->post("/users/{$op->id}/credit", ['delta' => 30, 'reason' => 'Bonus kegiatan'])
            ->assertRedirect();

        $this->assertSame(90, $op->fresh()->credit_balance);
        $this->assertDatabaseHas('credit_transactions', ['user_id' => $op->id, 'amount' => 30]);
    }

    public function test_negative_adjust_clamps_to_zero(): void
    {
        $op = $this->operator(60);

        $this->actingAs($this->superAdmin())
            ->post("/users/{$op->id}/credit", ['delta' => -100, 'reason' => 'Koreksi'])
            ->assertRedirect();

        $this->assertSame(0, $op->fresh()->credit_balance); // tak minus
        // amount aktual yang terpotong = -60 (clamp).
        $this->assertDatabaseHas('credit_transactions', ['user_id' => $op->id, 'amount' => -60]);
    }

    public function test_zero_delta_rejected(): void
    {
        $op = $this->operator(60);

        $this->actingAs($this->superAdmin())
            ->post("/users/{$op->id}/credit", ['delta' => 0, 'reason' => 'x'])
            ->assertSessionHasErrors('delta');
    }

    public function test_non_super_admin_cannot_adjust(): void
    {
        $op = $this->operator(60);

        $this->actingAs($this->operator())
            ->post("/users/{$op->id}/credit", ['delta' => 30, 'reason' => 'Bonus'])
            ->assertForbidden();
    }
}
