<?php

/**
 * tests/Feature/LoginLogTest.php
 * Acceptance Bagian 5: login sukses mencatat satu baris login_logs dengan IP;
 * login via 2FA juga tercatat.
 */

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class LoginLogTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas']);
    }

    private function approvedUser(): User
    {
        $user = User::create(['name' => 'U', 'email' => 'u@test.local', 'password' => 'password123']);
        $user->forceFill([
            'organization_id' => $this->org->id,
            'status' => User::STATUS_APPROVED,
            'nik' => fake()->numerify('################'),
            'phone' => '628'.fake()->numerify('#########'),
        ])->save();

        return $user;
    }

    public function test_successful_login_records_login_log(): void
    {
        $user = $this->approvedUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'password123']);

        $this->assertDatabaseCount('login_logs', 1);
        $log = $user->loginLogs()->first();
        $this->assertNotNull($log->ip);
        $this->assertNotNull($log->logged_at);
    }

    public function test_login_via_2fa_is_recorded(): void
    {
        $user = $this->approvedUser();
        $g = app(Google2FA::class);
        $secret = $g->generateSecretKey();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'password123']);
        $this->post('/two-factor-challenge', ['code' => $g->getCurrentOtp($secret)]);

        $this->assertDatabaseCount('login_logs', 1);
        $this->assertAuthenticatedAs($user);
    }
}
