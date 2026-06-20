<?php

/**
 * tests/Feature/CreatorRegistrationTest.php
 * Pendaftaran Marketplace Creator (KTP + identitas + S&K) + verifikasi rekening:
 * apply → pending → approve; rekening → pending → verify; gerbang publish/pencairan.
 */

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreatorRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
        $this->org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas']);
    }

    private function superAdmin(): User
    {
        return User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);
    }

    private function member(string $role = 'Admin'): User
    {
        $user = User::create(['name' => 'U', 'email' => fake()->unique()->safeEmail(), 'password' => 'password']);
        $user->forceFill([
            'organization_id' => $this->org->id,
            'nik' => fake()->numerify('################'),
            'phone' => '628'.fake()->numerify('#########'),
        ])->save();
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
        $user->assignRole($role);

        return $user;
    }

    private function applyAs(User $user): void
    {
        $this->actingAs($user)->post('/marketplace/apply', [
            'full_name' => 'Budi Santoso',
            'address' => 'Jl. Mawar No. 1, Aceh Barat Daya',
            'ktp' => UploadedFile::fake()->image('ktp.jpg'),
            'terms' => true,
        ])->assertRedirect();
    }

    public function test_public_register_page_accessible_to_guest(): void
    {
        $this->get('/creator/register')->assertOk();
    }

    public function test_public_register_page_accessible_when_logged_in(): void
    {
        $this->actingAs($this->member())->get('/creator/register')->assertOk();
    }

    public function test_apply_via_public_route_redirects_to_creator_dashboard(): void
    {
        $user = $this->member();

        $this->actingAs($user)->post('/marketplace/apply', [
            'full_name' => 'Budi Santoso',
            'address' => 'Jl. Mawar No. 1',
            'ktp' => UploadedFile::fake()->image('ktp.jpg'),
            'terms' => true,
        ])->assertRedirect(route('marketplace.creator'));

        $this->assertSame('pending', $user->fresh()->creator_status);
    }

    public function test_apply_requires_ktp_and_terms(): void
    {
        $user = $this->member();

        $this->actingAs($user)->post('/marketplace/apply', [
            'full_name' => 'Budi', 'address' => 'Jl. X', 'terms' => false,
        ])->assertSessionHasErrors(['ktp', 'terms']);

        $this->assertNull($user->fresh()->creator_status);
    }

    public function test_apply_creates_pending_application_and_stores_ktp_privately(): void
    {
        $user = $this->member();
        $this->applyAs($user);

        $fresh = $user->fresh();
        $this->assertSame('pending', $fresh->creator_status);
        $this->assertFalse($fresh->isMarketplaceCreator());
        $this->assertNotNull($fresh->creator_ktp_path);
        Storage::disk('local')->assertExists($fresh->creator_ktp_path);
        $this->assertDatabaseHas('audit_logs', ['aksi' => 'marketplace.creator_applied']);
    }

    public function test_apply_notifies_super_admin(): void
    {
        $super = $this->superAdmin();
        $user = $this->member();
        $this->applyAs($user);

        $this->assertSame(1, $super->notifications()->count());
    }

    public function test_super_admin_approves_application(): void
    {
        $user = $this->member();
        $this->applyAs($user);

        $this->actingAs($this->superAdmin())->post("/marketplace/creators/{$user->id}/approve")->assertRedirect();

        $this->assertTrue($user->fresh()->isMarketplaceCreator());
        $this->assertSame('approved', $user->fresh()->creator_status);
    }

    public function test_super_admin_rejects_application(): void
    {
        $user = $this->member();
        $this->applyAs($user);

        $this->actingAs($this->superAdmin())
            ->post("/marketplace/creators/{$user->id}/reject", ['reason' => 'KTP buram'])
            ->assertRedirect();

        $this->assertFalse($user->fresh()->isMarketplaceCreator());
        $this->assertSame('rejected', $user->fresh()->creator_status);
    }

    public function test_non_super_admin_cannot_approve(): void
    {
        $user = $this->member();
        $this->applyAs($user);

        $this->actingAs($this->member())->post("/marketplace/creators/{$user->id}/approve")->assertForbidden();
    }

    public function test_bank_flow_and_feature_gate(): void
    {
        // Creator approved tapi belum punya rekening.
        $user = $this->member();
        $user->forceFill(['marketplace_enabled' => true, 'creator_status' => 'approved'])->save();

        // Belum verified → tidak bisa pakai fitur.
        $this->assertFalse($user->fresh()->canUseCreatorFeatures());

        // Simpan rekening → pending.
        $this->actingAs($user)->post('/marketplace/bank', [
            'bank_name' => 'BCA', 'bank_account_no' => '1234567890', 'bank_account_holder' => 'Budi Santoso',
        ])->assertRedirect();
        $this->assertSame('pending', $user->fresh()->bank_status);

        // SuperAdmin verifikasi → verified → fitur terbuka.
        $this->actingAs($this->superAdmin())->post("/marketplace/creators/{$user->id}/bank/verify")->assertRedirect();
        $this->assertTrue($user->fresh()->canUseCreatorFeatures());
    }

    public function test_publish_blocked_until_bank_verified(): void
    {
        $user = $this->member();
        $user->forceFill(['marketplace_enabled' => true, 'creator_status' => 'approved'])->save();
        $template = new Template(['nama' => 'Tpl', 'slug' => 'tpl-'.fake()->numerify('####'), 'view' => 'certificates.default', 'is_active' => true]);
        $template->uploaded_by = $user->id;
        $template->organization_id = $this->org->id;
        $template->save();

        // Tanpa bank verified → publish ditolak.
        $this->actingAs($user)->post("/marketplace/{$template->id}/publish")->assertStatus(422);

        // Setelah verified → boleh publish.
        $user->forceFill(['bank_status' => 'verified'])->save();
        $this->actingAs($user)->post("/marketplace/{$template->id}/publish")->assertRedirect();
        $this->assertTrue($template->fresh()->isPublished());
    }

    public function test_ktp_only_viewable_by_super_admin(): void
    {
        $user = $this->member();
        $this->applyAs($user);

        // Non-SuperAdmin ditolak.
        $this->actingAs($this->member())->get("/marketplace/creators/{$user->id}/ktp")->assertForbidden();
        // SuperAdmin boleh.
        $this->actingAs($this->superAdmin())->get("/marketplace/creators/{$user->id}/ktp")->assertOk();
    }
}
