<?php

/**
 * tests/Feature/SignatoryDuplicateTest.php
 * Acceptance P3: signatory ter-scope organisasi + jejak pembuat (created_by);
 * menambah "Dr. Budi" saat sudah ada "Budi" memunculkan konfirmasi (tak membuat duplikat);
 * "tetap buat baru" (confirm=create_new) melewati cek dan menyimpan.
 */

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Signatory;
use App\Models\User;
use App\Services\SignatoryService;
use App\Support\Tenancy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SignatoryDuplicateTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;
    private Organization $orgB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->orgA = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas']);
        $this->orgB = Organization::create(['nama' => 'Komunitas B', 'kode' => 'KOMB', 'type' => 'komunitas']);
    }

    private function adminFor(Organization $org, string $email): User
    {
        $user = User::create(['name' => "Admin {$org->kode}", 'email' => $email, 'password' => 'password']);
        $user->forceFill(['organization_id' => $org->id, 'nik' => fake()->unique()->numerify('################'), 'phone' => '628'.fake()->numerify('#########')])->save();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $user->assignRole('Admin');

        return $user;
    }

    private function signatoryIn(Organization $org, string $nama, string $jabatan = 'Kepala'): Signatory
    {
        app(Tenancy::class)->setOrganizationId($org->id);
        $s = Signatory::create(['nama' => $nama, 'jabatan' => $jabatan]);
        app(Tenancy::class)->setOrganizationId(null);

        return $s;
    }

    // ── Normalisasi nama ─────────────────────────────────────────────────

    public function test_normalize_name_strips_titles_and_punctuation(): void
    {
        $service = app(SignatoryService::class);

        $this->assertSame('budi', $service->normalizeName('Dr. Budi'));
        $this->assertSame('budi santoso', $service->normalizeName('Budi Santoso, S.Kom'));
        $this->assertSame('budi', $service->normalizeName('  BUDI  '));
    }

    // ── Konfirmasi duplikat ──────────────────────────────────────────────

    public function test_adding_similar_name_returns_candidates_without_creating(): void
    {
        $admin = $this->adminFor($this->orgA, 'admin-a@test.local');
        $this->signatoryIn($this->orgA, 'Budi');

        $this->actingAs($admin)
            ->post('/signatories', ['nama' => 'Dr. Budi', 'jabatan' => 'Sekretaris'])
            ->assertRedirect()
            ->assertSessionHas('signatoryCandidates');

        // Tidak ada duplikat yang dibuat — masih hanya "Budi".
        app(Tenancy::class)->setOrganizationId($this->orgA->id);
        $this->assertSame(1, Signatory::count());
        app(Tenancy::class)->setOrganizationId(null);
    }

    public function test_create_new_confirmation_bypasses_check_and_saves(): void
    {
        $admin = $this->adminFor($this->orgA, 'admin-a@test.local');
        $this->signatoryIn($this->orgA, 'Budi');

        $this->actingAs($admin)
            ->post('/signatories', ['nama' => 'Dr. Budi', 'jabatan' => 'Sekretaris', 'confirm' => 'create_new'])
            ->assertRedirect()
            ->assertSessionHas('success');

        app(Tenancy::class)->setOrganizationId($this->orgA->id);
        $this->assertSame(2, Signatory::count());
        $new = Signatory::where('nama', 'Dr. Budi')->first();
        $this->assertNotNull($new);
        $this->assertSame($admin->id, $new->created_by);
        app(Tenancy::class)->setOrganizationId(null);
    }

    public function test_unique_name_is_created_directly(): void
    {
        $admin = $this->adminFor($this->orgA, 'admin-a@test.local');
        $this->signatoryIn($this->orgA, 'Budi');

        $this->actingAs($admin)
            ->post('/signatories', ['nama' => 'Siti Aminah', 'jabatan' => 'Bendahara'])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionMissing('signatoryCandidates');

        app(Tenancy::class)->setOrganizationId($this->orgA->id);
        $this->assertSame(2, Signatory::count());
        app(Tenancy::class)->setOrganizationId(null);
    }

    public function test_duplicate_check_is_scoped_per_organization(): void
    {
        // "Budi" ada di org B, tapi Admin org A tetap boleh membuat "Dr. Budi" tanpa konfirmasi.
        $adminA = $this->adminFor($this->orgA, 'admin-a@test.local');
        $this->signatoryIn($this->orgB, 'Budi');

        $this->actingAs($adminA)
            ->post('/signatories', ['nama' => 'Dr. Budi', 'jabatan' => 'Sekretaris'])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionMissing('signatoryCandidates');
    }
}
