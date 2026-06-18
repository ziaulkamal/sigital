<?php

/**
 * tests/Feature/TwoFactorTest.php
 * Acceptance P5: 2FA opsional per-user (K6). Aktivasi (QR→konfirmasi kode→recovery codes),
 * tantangan kode TOTP saat login, recovery code sekali pakai, nonaktif wajib password.
 */

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\TwoFactorService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private Google2FA $g;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->g = app(Google2FA::class);
    }

    private function user(): User
    {
        $org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas']);
        $user = User::create(['name' => 'U', 'email' => 'u@test.local', 'password' => 'password123']);
        $user->forceFill(['organization_id' => $org->id])->save();

        return $user;
    }

    /** User dengan 2FA aktif + secret diketahui (untuk uji login). */
    private function userWith2fa(): array
    {
        $user = $this->user();
        $secret = $this->g->generateSecretKey();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['AAAA-BBBB', 'CCCC-DDDD'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        return [$user, $secret];
    }

    // ── Aktivasi ─────────────────────────────────────────────────────────

    public function test_enable_creates_unconfirmed_secret(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/settings/two-factor/enable')
            ->assertRedirect()
            ->assertSessionHas('twoFactor');

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_confirm_with_valid_code_enables_2fa(): void
    {
        $user = $this->user();
        $this->actingAs($user)->post('/settings/two-factor/enable');
        $user->refresh();

        $code = $this->g->getCurrentOtp($user->two_factor_secret);

        $this->actingAs($user)->post('/settings/two-factor/confirm', ['code' => $code])
            ->assertRedirect()
            ->assertSessionHas('twoFactor');

        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());
        $this->assertCount(8, $user->two_factor_recovery_codes);
    }

    public function test_confirm_with_invalid_code_fails(): void
    {
        $user = $this->user();
        $this->actingAs($user)->post('/settings/two-factor/enable');

        $this->actingAs($user)->post('/settings/two-factor/confirm', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $user->refresh();
        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    // ── Tantangan login ──────────────────────────────────────────────────

    public function test_login_with_2fa_redirects_to_challenge_and_not_authenticated(): void
    {
        [$user] = $this->userWith2fa();

        $this->post('/login', ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
    }

    public function test_challenge_with_valid_totp_logs_in(): void
    {
        [$user, $secret] = $this->userWith2fa();

        $this->post('/login', ['email' => $user->email, 'password' => 'password123']);

        $this->post('/two-factor-challenge', ['code' => $this->g->getCurrentOtp($secret)])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_recovery_code_is_single_use(): void
    {
        [$user] = $this->userWith2fa();

        $this->post('/login', ['email' => $user->email, 'password' => 'password123']);
        $this->post('/two-factor-challenge', ['recovery_code' => 'AAAA-BBBB'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // Code dikonsumsi → tersisa satu.
        $user->refresh();
        $this->assertSame(['CCCC-DDDD'], array_values($user->two_factor_recovery_codes));
    }

    public function test_challenge_with_wrong_code_fails(): void
    {
        [$user] = $this->userWith2fa();
        $this->post('/login', ['email' => $user->email, 'password' => 'password123']);

        $this->post('/two-factor-challenge', ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    // ── Nonaktif ─────────────────────────────────────────────────────────

    public function test_disable_requires_correct_password(): void
    {
        [$user] = $this->userWith2fa();

        $this->actingAs($user)->delete('/settings/two-factor', ['current_password' => 'salah'])
            ->assertSessionHasErrors('current_password');
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        $this->actingAs($user)->delete('/settings/two-factor', ['current_password' => 'password123'])
            ->assertSessionHas('success');
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }
}
