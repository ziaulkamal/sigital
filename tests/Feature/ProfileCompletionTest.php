<?php

/**
 * tests/Feature/ProfileCompletionTest.php
 * Setelah di-approve, fitur dikunci hingga NIK + nomor HP dilengkapi. Nomor HP
 * dinormalisasi (08… → 628…). SuperAdmin tidak terkena gate ini.
 */

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function approvedWithoutProfile(): User
    {
        $org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas', 'is_active' => true]);
        $user = User::create(['name' => 'U', 'email' => 'u@test.local', 'password' => 'password']);
        $user->forceFill([
            'organization_id' => $org->id,
            'status' => User::STATUS_APPROVED,
            'nik' => null,
            'phone' => null,
        ])->save();
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $user->assignRole('Operator');

        return $user;
    }

    public function test_approved_user_without_profile_is_redirected_to_complete_profile(): void
    {
        $user = $this->approvedWithoutProfile();

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/complete-profile');
    }

    public function test_completing_profile_normalizes_phone_and_unlocks_app(): void
    {
        $user = $this->approvedWithoutProfile();

        $this->actingAs($user)->post('/complete-profile', [
            'nik' => '3201010101019999',
            'phone' => '081299990001',
        ])->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertSame('3201010101019999', $user->nik);
        $this->assertSame('6281299990001', $user->phone); // 08… → 628…
        $this->assertFalse($user->needsProfileCompletion());

        // Setelah lengkap, fitur terbuka.
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_invalid_nik_is_rejected(): void
    {
        $user = $this->approvedWithoutProfile();

        $this->actingAs($user)->post('/complete-profile', [
            'nik' => '123', 'phone' => '081299990001',
        ])->assertSessionHasErrors('nik');
    }

    public function test_super_admin_is_not_gated(): void
    {
        $super = User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);

        $this->actingAs($super)->get('/dashboard')->assertOk();
    }
}
