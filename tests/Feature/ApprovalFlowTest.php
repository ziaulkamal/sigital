<?php

/**
 * tests/Feature/ApprovalFlowTest.php
 * Acceptance P2: registrasi bersyarat (dinas wajib surat rekomendasi), gate EnsureApproved,
 * dan alur approval/reject SuperAdmin.
 */

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function superAdmin(): User
    {
        return User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);
    }

    private function pendingUser(Organization $org, string $role = 'Operator'): User
    {
        $user = User::create(['name' => 'Pendaftar', 'email' => 'p@test.local', 'password' => 'password']);
        $user->forceFill([
            'organization_id' => $org->id,
            'status' => User::STATUS_PENDING,
            'requested_role' => $role,
        ])->save();

        return $user;
    }

    // ── Registrasi ───────────────────────────────────────────────────────

    public function test_komunitas_can_register_without_recommendation_letter(): void
    {
        $this->post('/register', [
            'name' => 'Budi', 'email' => 'budi@test.local',
            'nik' => '3201010101010001', 'phone' => '081234567801',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'org_mode' => 'new', 'org_nama' => 'Relawan X', 'org_kode' => 'RELX', 'org_type' => 'komunitas',
        ])->assertRedirect('/verify-phone');

        $user = User::where('email', 'budi@test.local')->first();
        $this->assertNotNull($user);
        $this->assertSame(User::STATUS_PENDING, $user->status);
        $this->assertSame('3201010101010001', $user->nik);
        $this->assertSame('Admin', $user->requested_role); // pengaju org baru → calon Admin
        $this->assertFalse($user->organization->is_active);
    }

    public function test_dinas_registration_requires_recommendation_letter(): void
    {
        $this->post('/register', [
            'name' => 'Sari', 'email' => 'sari@test.local',
            'nik' => '3201010101010002', 'phone' => '081234567802',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'org_mode' => 'new', 'org_nama' => 'Dinas Y', 'org_kode' => 'DINY', 'org_type' => 'dinas',
        ])->assertSessionHasErrors('recommendation_letter');

        $this->assertNull(User::where('email', 'sari@test.local')->first());
    }

    public function test_dinas_registration_with_letter_succeeds_and_stores_file(): void
    {
        Storage::fake('local');

        $this->post('/register', [
            'name' => 'Sari', 'email' => 'sari@test.local',
            'nik' => '3201010101010003', 'phone' => '081234567803',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'org_mode' => 'new', 'org_nama' => 'Dinas Y', 'org_kode' => 'DINY', 'org_type' => 'dinas',
            'recommendation_letter' => UploadedFile::fake()->create('surat.pdf', 120, 'application/pdf'),
        ])->assertRedirect('/verify-phone');

        $org = Organization::where('kode', 'DINY')->first();
        $this->assertNotNull($org->recommendation_letter_path);
        Storage::assertExists($org->recommendation_letter_path);
    }

    public function test_existing_org_registration_requests_operator_role(): void
    {
        $org = Organization::create(['nama' => 'Aktif', 'kode' => 'AKT', 'type' => 'dinas', 'is_active' => true]);

        $this->post('/register', [
            'name' => 'Joni', 'email' => 'joni@test.local',
            'nik' => '3201010101010004', 'phone' => '081234567804',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'org_mode' => 'existing', 'organization_id' => $org->id,
        ])->assertRedirect('/verify-phone');

        $user = User::where('email', 'joni@test.local')->first();
        $this->assertSame('Operator', $user->requested_role);
        $this->assertSame($org->id, $user->organization_id);
    }

    // ── Gate EnsureApproved ──────────────────────────────────────────────

    public function test_pending_user_is_redirected_from_app(): void
    {
        $org = Organization::create(['nama' => 'Org', 'kode' => 'ORG', 'type' => 'dinas', 'is_active' => true]);
        $user = $this->pendingUser($org);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/pending');
    }

    public function test_pending_user_can_view_pending_page(): void
    {
        $org = Organization::create(['nama' => 'Org', 'kode' => 'ORG', 'type' => 'dinas', 'is_active' => true]);
        $user = $this->pendingUser($org);

        $this->actingAs($user)->get('/pending')->assertOk();
    }

    // ── Approval / Reject ────────────────────────────────────────────────

    public function test_super_admin_approves_user_activates_org_and_assigns_role(): void
    {
        // Organisasi diajukan (belum aktif), pengaju minta peran Admin.
        $org = Organization::create(['nama' => 'Baru', 'kode' => 'BARU', 'type' => 'komunitas', 'is_active' => false]);
        $user = $this->pendingUser($org, 'Admin');

        $this->actingAs($this->superAdmin())
            ->post("/approvals/{$user->id}/approve")
            ->assertRedirect();

        $user->refresh();
        $org->refresh();
        $this->assertSame(User::STATUS_APPROVED, $user->status);
        $this->assertTrue($org->is_active);

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $this->assertTrue($user->fresh()->hasRole('Admin'));
    }

    public function test_super_admin_can_reject_user(): void
    {
        $org = Organization::create(['nama' => 'Org', 'kode' => 'ORG', 'type' => 'dinas', 'is_active' => true]);
        $user = $this->pendingUser($org);

        $this->actingAs($this->superAdmin())
            ->post("/approvals/{$user->id}/reject", ['reason' => 'Data tidak lengkap'])
            ->assertRedirect();

        $this->assertSame(User::STATUS_REJECTED, $user->fresh()->status);
    }

    public function test_non_super_admin_cannot_access_approvals(): void
    {
        $org = Organization::create(['nama' => 'Org', 'kode' => 'ORG', 'type' => 'dinas', 'is_active' => true]);
        $operator = User::create(['name' => 'Op', 'email' => 'op@test.local', 'password' => 'password']);
        $operator->forceFill(['organization_id' => $org->id, 'status' => User::STATUS_APPROVED])->save();
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $operator->assignRole('Operator');

        $this->actingAs($operator)->get('/approvals')->assertForbidden();
    }
}
