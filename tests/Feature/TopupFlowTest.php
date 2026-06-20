<?php

/**
 * tests/Feature/TopupFlowTest.php
 * Acceptance Bagian 4: user ajukan topup → pending; SuperAdmin approve → saldo
 * bertambah + ledger; reject → saldo tetap.
 */

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\TopupRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TopupFlowTest extends TestCase
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

    private function operator(int $balance = 10): User
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

    public function test_user_requests_topup_creates_pending(): void
    {
        $op = $this->operator();

        $this->actingAs($op)->post('/credits/topup', ['amount_credit' => 100])->assertRedirect();

        $this->assertDatabaseHas('topup_requests', [
            'user_id' => $op->id, 'amount_credit' => 100, 'amount_rupiah' => 100000, 'status' => 'pending',
        ]);
        $this->assertSame(10, $op->fresh()->credit_balance); // belum bertambah
    }

    public function test_approve_adds_balance_and_ledger(): void
    {
        $op = $this->operator(10);
        $req = TopupRequest::create([
            'user_id' => $op->id, 'amount_credit' => 100, 'amount_rupiah' => 100000, 'status' => 'pending',
        ]);

        $this->actingAs($this->superAdmin())->post("/credits/topup/{$req->id}/approve")->assertRedirect();

        $this->assertSame(110, $op->fresh()->credit_balance);
        $this->assertSame('approved', $req->fresh()->status);
        $this->assertDatabaseHas('credit_transactions', ['user_id' => $op->id, 'type' => 'topup', 'amount' => 100]);
    }

    public function test_reject_does_not_add_balance(): void
    {
        $op = $this->operator(10);
        $req = TopupRequest::create([
            'user_id' => $op->id, 'amount_credit' => 100, 'amount_rupiah' => 100000, 'status' => 'pending',
        ]);

        $this->actingAs($this->superAdmin())
            ->post("/credits/topup/{$req->id}/reject", ['reject_reason' => 'Bukti tidak valid'])
            ->assertRedirect();

        $this->assertSame(10, $op->fresh()->credit_balance);
        $this->assertSame('rejected', $req->fresh()->status);
    }

    public function test_non_super_admin_cannot_approve(): void
    {
        $op = $this->operator(10);
        $req = TopupRequest::create([
            'user_id' => $op->id, 'amount_credit' => 100, 'amount_rupiah' => 100000, 'status' => 'pending',
        ]);

        $this->actingAs($this->operator())->post("/credits/topup/{$req->id}/approve")->assertForbidden();
    }
}
