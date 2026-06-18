<?php

/**
 * tests/Feature/RegistrationTest.php
 * Registrasi awal TIDAK meminta NIK/HP (dilengkapi setelah di-approve). Akun berstatus
 * pending dan SuperAdmin diberi notifikasi pendaftar baru.
 */

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'Budi', 'email' => 'budi@test.local',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'org_mode' => 'new', 'org_nama' => 'Relawan Z', 'org_kode' => 'RELZ', 'org_type' => 'komunitas',
        ], $override);
    }

    public function test_registration_does_not_require_nik_or_phone(): void
    {
        // Tanpa nik/phone tetap valid (tidak ada error pada field tsb).
        $this->post('/register', $this->payload())->assertSessionHasNoErrors();
    }

    public function test_successful_registration_is_pending_without_identity_and_notifies_super_admin(): void
    {
        $superAdmin = User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);

        $this->post('/register', $this->payload())->assertRedirect('/pending');

        $user = User::where('email', 'budi@test.local')->first();
        $this->assertSame(User::STATUS_PENDING, $user->status);
        $this->assertNull($user->nik);
        $this->assertNull($user->phone);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $superAdmin->id,
            'type' => \App\Notifications\UserRegistered::class,
        ]);
    }
}
