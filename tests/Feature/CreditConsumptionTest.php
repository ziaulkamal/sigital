<?php

/**
 * tests/Feature/CreditConsumptionTest.php
 * Acceptance Bagian 2: pembuatan acara (50) & template (10) memotong credit;
 * saldo kurang → InsufficientCreditException + flash error, tanpa data yatim.
 */

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreditConsumptionTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas']);
    }

    private function user(int $balance, string $role = 'Operator'): User
    {
        $user = User::create(['name' => 'U', 'email' => fake()->unique()->safeEmail(), 'password' => 'password']);
        $user->forceFill([
            'organization_id' => $this->org->id,
            'nik' => fake()->numerify('################'),
            'phone' => '628'.fake()->numerify('#########'),
            'credit_balance' => $balance,
        ])->save();
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
        $user->assignRole($role);

        return $user;
    }

    private function operator(int $balance): User
    {
        return $this->user($balance, 'Operator');
    }

    // Template dikelola Admin (permission manage-templates).
    private function admin(int $balance): User
    {
        return $this->user($balance, 'Admin');
    }

    public function test_creating_event_deducts_50_credit(): void
    {
        $user = $this->operator(60);

        $this->actingAs($user)->post('/events', [
            'nama' => 'Pelatihan', 'jadwal_mulai' => now()->toDateTimeString(),
        ])->assertRedirect();

        $this->assertSame(10, $user->fresh()->credit_balance);
        $this->assertDatabaseHas('credit_transactions', ['user_id' => $user->id, 'type' => 'consume', 'amount' => -50]);
    }

    public function test_insufficient_credit_blocks_event_and_leaves_no_orphan(): void
    {
        $user = $this->operator(40); // < 50

        $this->actingAs($user)->post('/events', [
            'nama' => 'Gagal', 'jadwal_mulai' => now()->toDateTimeString(),
        ])->assertSessionHas('error');

        $this->assertSame(40, $user->fresh()->credit_balance);
        $this->assertDatabaseMissing('events', ['nama' => 'Gagal']);
        // Upaya pemakaian saat saldo kurang harus tetap terekam di audit (anti-exploit).
        $this->assertDatabaseHas('audit_logs', ['aksi' => 'credit.consume_denied', 'actor_id' => $user->id]);
    }

    public function test_creating_template_deducts_10_credit(): void
    {
        $user = $this->admin(60);

        $this->actingAs($user)->post('/templates', ['nama' => 'Tpl A'])->assertRedirect();

        $this->assertSame(50, $user->fresh()->credit_balance);
        $this->assertDatabaseHas('credit_transactions', ['user_id' => $user->id, 'type' => 'consume', 'amount' => -10]);
    }

    public function test_insufficient_credit_blocks_template(): void
    {
        $user = $this->admin(5); // < 10

        $this->actingAs($user)->post('/templates', ['nama' => 'TplGagal'])->assertSessionHas('error');

        $this->assertSame(5, $user->fresh()->credit_balance);
        $this->assertDatabaseMissing('templates', ['nama' => 'TplGagal']);
    }
}
