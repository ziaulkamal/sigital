<?php

/**
 * tests/Feature/RegistrationOtpTest.php
 * Syarat mutlak registrasi: NIK + WhatsApp wajib; verifikasi OTP WhatsApp sebelum
 * menunggu persetujuan (anti akun palsu). SuperAdmin diberi notifikasi pendaftar baru.
 */

namespace Tests\Feature;

use App\Models\User;
use App\Services\WhatsApp\PhoneOtpService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationOtpTest extends TestCase
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
            'nik' => '3201010101019999', 'phone' => '081299990001',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'org_mode' => 'new', 'org_nama' => 'Relawan Z', 'org_kode' => 'RELZ', 'org_type' => 'komunitas',
        ], $override);
    }

    public function test_registration_requires_nik_and_phone(): void
    {
        $this->post('/register', $this->payload(['nik' => '', 'phone' => '']))
            ->assertSessionHasErrors(['nik', 'phone']);
    }

    public function test_nik_must_be_16_digits(): void
    {
        $this->post('/register', $this->payload(['nik' => '123']))
            ->assertSessionHasErrors('nik');
    }

    public function test_successful_registration_stores_identity_and_redirects_to_otp(): void
    {
        $superAdmin = User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);

        $this->post('/register', $this->payload())->assertRedirect('/verify-phone');

        $user = User::where('email', 'budi@test.local')->first();
        $this->assertSame('3201010101019999', $user->nik);
        $this->assertSame('081299990001', $user->phone);
        $this->assertNull($user->phone_verified_at);

        // SuperAdmin menerima notifikasi pendaftar baru.
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $superAdmin->id,
            'type' => \App\Notifications\UserRegistered::class,
        ]);
    }

    public function test_pending_redirects_to_verify_phone_when_unverified(): void
    {
        $this->post('/register', $this->payload());
        $user = User::where('email', 'budi@test.local')->first();

        $this->actingAs($user)->get('/pending')->assertRedirect('/verify-phone');
    }

    public function test_correct_otp_verifies_phone(): void
    {
        $this->post('/register', $this->payload());
        $user = User::where('email', 'budi@test.local')->first();

        // Hasilkan OTP yang diketahui untuk pengujian.
        $code = app(PhoneOtpService::class)->sendOtp($user);

        $this->actingAs($user)->post('/verify-phone', ['code' => $code])
            ->assertRedirect('/pending');

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_wrong_otp_is_rejected(): void
    {
        $this->post('/register', $this->payload());
        $user = User::where('email', 'budi@test.local')->first();
        app(PhoneOtpService::class)->sendOtp($user);

        $this->actingAs($user)->post('/verify-phone', ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->phone_verified_at);
    }
}
