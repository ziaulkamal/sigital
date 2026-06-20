<?php

/**
 * tests/Feature/MarketplaceTest.php
 * Acceptance Bagian 6: pembelian template (15→10 owner +5 platform), no self-royalty,
 * saldo kurang ditolak, histori tercatat; pencairan dengan biaya admin.
 */

namespace Tests\Feature;

use App\Models\MarketplaceWithdrawal;
use App\Models\Organization;
use App\Models\Template;
use App\Models\User;
use App\Services\MarketplaceWithdrawalService;
use App\Services\TemplateMarketplaceService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas']);
    }

    private function user(int $balance = 0, bool $creator = false, string $role = 'Admin'): User
    {
        $user = User::create(['name' => 'U', 'email' => fake()->unique()->safeEmail(), 'password' => 'password']);
        $user->forceFill([
            'organization_id' => $this->org->id,
            'nik' => fake()->numerify('################'),
            'phone' => '628'.fake()->numerify('#########'),
            'credit_balance' => $balance,
            'marketplace_enabled' => $creator,
            'marketplace_joined_at' => $creator ? now() : null,
            'creator_status' => $creator ? 'approved' : null,
            // Creator pada test dianggap sudah punya rekening terverifikasi agar
            // gerbang publish/pencairan (canUseCreatorFeatures) terpenuhi.
            'bank_status' => $creator ? 'verified' : null,
        ])->save();
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
        $user->assignRole($role);

        return $user;
    }

    private function publishedTemplate(User $owner): Template
    {
        $t = new Template(['nama' => 'Tpl MP', 'slug' => 'tpl-mp-'.fake()->unique()->numerify('####'), 'view' => 'certificates.default', 'is_active' => true]);
        $t->uploaded_by = $owner->id;
        $t->organization_id = $this->org->id;
        $t->save();
        $t->forceFill(['is_marketplace' => true, 'marketplace_price' => 15, 'published_at' => now()])->save();

        return $t;
    }

    public function test_purchase_splits_credit_owner_and_platform(): void
    {
        $owner = $this->user(balance: 0, creator: true);
        $buyer = $this->user(balance: 20);
        $template = $this->publishedTemplate($owner);

        app(TemplateMarketplaceService::class)->purchaseTemplate($template, $buyer);

        $this->assertSame(5, $buyer->fresh()->credit_balance);   // 20 - 15
        $this->assertSame(10, $owner->fresh()->credit_balance);  // +10 royalti
        $this->assertDatabaseHas('template_usage_transactions', [
            'template_id' => $template->id, 'owner_user_id' => $owner->id, 'buyer_user_id' => $buyer->id,
            'price_credit' => 15, 'owner_credit' => 10, 'platform_credit' => 5,
        ]);
        $this->assertDatabaseHas('platform_credit_ledger', ['credit_amount' => 5]);
    }

    public function test_buyer_with_insufficient_credit_is_rejected(): void
    {
        $owner = $this->user(balance: 0, creator: true);
        $buyer = $this->user(balance: 10); // < 15
        $template = $this->publishedTemplate($owner);

        $this->actingAs($buyer)->post("/marketplace/{$template->id}/purchase")
            ->assertSessionHas('error');

        $this->assertSame(10, $buyer->fresh()->credit_balance);
        $this->assertDatabaseCount('template_usage_transactions', 0);
    }

    public function test_owner_cannot_pay_for_own_template(): void
    {
        $owner = $this->user(balance: 100, creator: true);
        $template = $this->publishedTemplate($owner);

        $this->actingAs($owner)->post("/marketplace/{$template->id}/purchase")
            ->assertSessionHas('error');

        $this->assertSame(100, $owner->fresh()->credit_balance); // tak terpotong
        $this->assertDatabaseCount('template_usage_transactions', 0);
    }

    public function test_withdrawal_deducts_fee_and_computes_rupiah(): void
    {
        $creator = $this->user(balance: 100, creator: true);

        $w = app(MarketplaceWithdrawalService::class)->requestWithdrawal($creator, 100);

        $this->assertSame(0, $creator->fresh()->credit_balance);  // 100 ditahan
        $this->assertSame(10, $w->admin_fee_credit);
        $this->assertSame(90, $w->credit_paid);
        $this->assertSame(90000, $w->rupiah_paid);                 // 90 * 1000
        $this->assertDatabaseHas('platform_credit_ledger', ['credit_amount' => 10]);
    }

    public function test_withdrawal_reject_refunds_credit(): void
    {
        $creator = $this->user(balance: 100, creator: true);
        $w = app(MarketplaceWithdrawalService::class)->requestWithdrawal($creator, 100);
        $this->assertSame(0, $creator->fresh()->credit_balance);

        $super = User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);
        $this->actingAs($super)->post("/marketplace/withdrawals/{$w->id}/reject", ['reason' => 'Data rekening salah'])
            ->assertRedirect();

        $this->assertSame(100, $creator->fresh()->credit_balance); // dikembalikan
        $this->assertSame('rejected', $w->fresh()->status);
    }

    public function test_below_minimum_withdrawal_rejected(): void
    {
        $creator = $this->user(balance: 200, creator: true);

        $this->actingAs($creator)->post('/marketplace/withdrawals', ['credit_requested' => 50]) // < 100 min
            ->assertSessionHas('error');

        $this->assertSame(200, $creator->fresh()->credit_balance);
    }

    public function test_withdrawal_blocked_without_verified_bank(): void
    {
        // Creator approved tapi rekening belum verified → pencairan ditolak.
        $creator = $this->user(balance: 200, creator: true);
        $creator->forceFill(['bank_status' => null])->save();

        $this->actingAs($creator)->post('/marketplace/withdrawals', ['credit_requested' => 100])
            ->assertSessionHas('error');

        $this->assertSame(200, $creator->fresh()->credit_balance);
    }

    public function test_withdrawal_request_notifies_super_admin_and_org_admin(): void
    {
        $creator = $this->user(balance: 200, creator: true, role: 'Operator');
        $orgAdmin = $this->user(balance: 0, role: 'Admin'); // instansi sama
        $super = User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);

        app(MarketplaceWithdrawalService::class)->requestWithdrawal($creator, 100);

        $this->assertSame(1, $super->notifications()->count());
        $this->assertSame(1, $orgAdmin->notifications()->count());
        // Pemohon sendiri tidak menerima notifikasi.
        $this->assertSame(0, $creator->notifications()->count());
    }
}
