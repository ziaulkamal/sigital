<?php

/**
 * tests/Feature/CertificateEnhancementTest.php
 * Enhancement template (poin 1-7): keterangan otomatis, logo/keterangan acara,
 * QR SRIKANDI per-penanda-tangan, regenerate (nomor & QR tetap), batalkan.
 */

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Registration;
use App\Models\Signatory;
use App\Models\Template;
use App\Models\User;
use App\Services\Certificate\CertificateDistributor;
use App\Services\Certificate\CertificateIssuer;
use App\Support\KeteranganGenerator;
use App\Support\Tenancy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CertificateEnhancementTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->org = Organization::create(['nama' => 'Dinas Kominfo Aceh', 'kode' => 'KOMINFO', 'type' => 'dinas']);
    }

    private function adminFor(Organization $org): User
    {
        $user = User::create(['name' => "Admin {$org->kode}", 'email' => "admin-{$org->kode}@test.local", 'password' => 'password']);
        $user->forceFill(['organization_id' => $org->id, 'nik' => fake()->unique()->numerify('################'), 'phone' => '628'.fake()->numerify('#########')])->save();
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $user->assignRole('Admin');

        return $user;
    }

    // ── Keterangan otomatis (poin 5) ─────────────────────────────────────

    public function test_auto_keterangan_single_day_with_duration(): void
    {
        $event = new Event([
            'nama' => 'Workshop AI',
            'lokasi' => 'Aula Utama',
            'jadwal_mulai' => '2026-02-12 08:00:00',
            'jadwal_selesai' => '2026-02-12 16:00:00',
        ]);

        $text = app(KeteranganGenerator::class)->auto($event, 'Dinas Kominfo Aceh');

        $this->assertStringContainsString('telah menyelesaikan kegiatan Workshop AI', $text);
        $this->assertStringContainsString('di Aula Utama', $text);
        $this->assertStringContainsString('12 Februari 2026', $text);
        $this->assertStringContainsString('8 jam', $text); // 08:00–16:00
        $this->assertStringContainsString('Dinas Kominfo Aceh', $text);
        // Satu hari → tidak ada rentang tanggal.
        $this->assertStringNotContainsString('–', $text);
    }

    public function test_auto_keterangan_multi_day_range(): void
    {
        $event = new Event([
            'nama' => 'Bimtek',
            'jadwal_mulai' => '2026-02-12 08:00:00',
            'jadwal_selesai' => '2026-02-14 12:00:00',
        ]);

        $text = app(KeteranganGenerator::class)->auto($event);

        $this->assertStringContainsString('12 – 14 Februari 2026', $text);
    }

    public function test_manual_keterangan_overrides_auto(): void
    {
        $event = new Event(['nama' => 'X', 'keterangan' => 'Keterangan kustom admin.']);

        $this->assertSame('Keterangan kustom admin.', app(KeteranganGenerator::class)->for($event));
    }

    public function test_keterangan_tokens_and_template_substitution(): void
    {
        $event = new Event([
            'nama' => 'Workshop AI', 'lokasi' => 'Aula',
            'jadwal_mulai' => '2026-02-12 08:00:00', 'jadwal_selesai' => '2026-02-12 16:00:00',
        ]);
        $gen = app(KeteranganGenerator::class);

        $tokens = $gen->tokens($event, 'Dinas Kominfo');
        $this->assertSame('Workshop AI', $tokens['acara']);
        $this->assertSame('Aula', $tokens['lokasi']);
        $this->assertSame('8 jam', $tokens['durasi']);
        $this->assertSame('Dinas Kominfo', $tokens['instansi']);

        // Template kustom dgn placeholder → tersubstitusi; **bold** dibiarkan utuh (dirender Node).
        $out = $gen->applyTemplate('Kegiatan **{acara}** ({durasi}) oleh {instansi}', $event, 'Dinas Kominfo');
        $this->assertSame('Kegiatan **Workshop AI** (8 jam) oleh Dinas Kominfo', $out);
    }

    // ── Logo & keterangan acara (poin 1 & 2) ─────────────────────────────

    public function test_event_form_stores_logo_and_keterangan(): void
    {
        Storage::fake('public');
        $admin = $this->adminFor($this->org);
        $sig = $this->makeSignatory();

        $this->actingAs($admin)->post('/events', [
            'nama' => 'Pelatihan Digital',
            'jadwal_mulai' => '2026-03-01 09:00',
            'keterangan' => 'Keterangan acara.',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'signatory_ids' => [$sig->id],
        ])->assertRedirect();

        app(Tenancy::class)->setOrganizationId($this->org->id);
        $event = Event::first();
        app(Tenancy::class)->setOrganizationId(null);

        $this->assertNotNull($event->logo_path);
        $this->assertSame('Keterangan acara.', $event->keterangan);
        Storage::disk('public')->assertExists($event->logo_path);
    }

    // ── QR SRIKANDI per-penanda-tangan (poin 7) ──────────────────────────

    public function test_signatory_stores_srikandi_qr(): void
    {
        Storage::fake('public');
        $admin = $this->adminFor($this->org);
        app(Tenancy::class)->setOrganizationId($this->org->id);

        $this->actingAs($admin)->post('/signatories', [
            'nama' => 'Pejabat Unik Sekali',
            'jabatan' => 'Kepala',
            'qr_srikandi' => UploadedFile::fake()->image('qr.png'),
            'confirm' => 'create_new',
        ])->assertRedirect()->assertSessionHas('success');

        $sig = Signatory::first();
        app(Tenancy::class)->setOrganizationId(null);

        $this->assertNotNull($sig->qr_srikandi_path);
        Storage::disk('public')->assertExists($sig->qr_srikandi_path);
    }

    // ── Regenerate & batalkan (poin 3) ───────────────────────────────────

    public function test_regenerate_keeps_nomor_and_qr_token(): void
    {
        $admin = $this->adminFor($this->org);
        $cert = $this->issueOneCertificate($admin);

        $nomorBefore = $cert->nomor_unik;
        $tokenBefore = $cert->qr_token;
        $issuedBefore = $cert->issued_at;

        $this->actingAs($admin)->post("/certificates/{$cert->id}/regenerate")
            ->assertRedirect()->assertSessionHas('success');

        $cert->refresh();
        $this->assertSame($nomorBefore, $cert->nomor_unik);
        $this->assertSame($tokenBefore, $cert->qr_token);
        $this->assertEquals($issuedBefore->timestamp, $cert->issued_at->timestamp);
        $this->assertSame(Certificate::STATUS_ISSUED, $cert->status);
        $this->assertDatabaseHas('audit_logs', ['aksi' => 'certificate.regenerated']);
    }

    public function test_revoke_marks_certificate_revoked(): void
    {
        $admin = $this->adminFor($this->org);
        $cert = $this->issueOneCertificate($admin);

        $this->actingAs($admin)->post("/certificates/{$cert->id}/revoke", ['alasan' => 'Salah data'])
            ->assertRedirect();

        $cert->refresh();
        $this->assertSame(Certificate::STATUS_REVOKED, $cert->status);
    }

    public function test_superadmin_can_restore_revoked_certificate(): void
    {
        $admin = $this->adminFor($this->org);
        $cert = $this->issueOneCertificate($admin);
        app(CertificateDistributor::class)->revoke($cert, 'salah');
        $this->assertSame(Certificate::STATUS_REVOKED, $cert->fresh()->status);

        // SuperAdmin = organization_id null.
        $super = User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);

        $this->actingAs($super)->post("/certificates/{$cert->id}/restore")
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame(Certificate::STATUS_ISSUED, $cert->fresh()->status);
        $this->assertNull($cert->fresh()->alasan_pencabutan);
        $this->assertDatabaseHas('audit_logs', ['aksi' => 'certificate.restored']);
    }

    public function test_non_superadmin_cannot_restore(): void
    {
        $admin = $this->adminFor($this->org);
        $cert = $this->issueOneCertificate($admin);
        app(CertificateDistributor::class)->revoke($cert, 'salah');

        $this->actingAs($admin)->post("/certificates/{$cert->id}/restore")
            ->assertForbidden();

        $this->assertSame(Certificate::STATUS_REVOKED, $cert->fresh()->status);
    }

    public function test_verify_api_returns_event_details_and_logo(): void
    {
        $admin = $this->adminFor($this->org);
        $cert = $this->issueOneCertificate($admin);
        // Set logo & lokasi acara untuk diuji tampil di verifikasi.
        $event = $cert->registration->event;
        $event->forceFill(['lokasi' => 'Aula RTIK', 'logo_path' => 'event-logos/x.png'])->save();

        $res = $this->getJson("/api/v1/verify/{$cert->qr_token}");

        $res->assertOk()
            ->assertJsonPath('status', 'valid')
            ->assertJsonPath('data.pelaksana', $this->org->nama)
            ->assertJsonPath('data.lokasi', 'Aula RTIK')
            ->assertJsonPath('app.name', config('sigital.brand.name'));
        $this->assertStringContainsString('event-logos/x.png', $res->json('data.logo'));
    }

    public function test_public_download_works_for_valid_and_blocked_for_revoked(): void
    {
        $admin = $this->adminFor($this->org);
        $cert = $this->issueOneCertificate($admin);
        $this->assertNotNull($cert->pdf_path); // diterbitkan + PDF tersimpan

        // Sah → PDF terunduh.
        $this->get("/api/v1/verify/{$cert->qr_token}/download")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // Dicabut → unduh diblokir (404).
        app(CertificateDistributor::class)->revoke($cert, 'x');
        $this->get("/api/v1/verify/{$cert->qr_token}/download")->assertNotFound();
    }

    public function test_regenerate_event_rerenders_all_active(): void
    {
        $admin = $this->adminFor($this->org);
        $cert = $this->issueOneCertificate($admin);
        $event = $cert->registration->event;

        $this->actingAs($admin)->post("/events/{$event->id}/regenerate")
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('audit_logs', ['aksi' => 'certificate.regenerated']);
    }

    // ── Helper ───────────────────────────────────────────────────────────

    private function makeSignatory(): Signatory
    {
        app(Tenancy::class)->setOrganizationId($this->org->id);
        $sig = Signatory::create(['nama' => 'Budi Karya', 'jabatan' => 'Kepala Dinas']);
        app(Tenancy::class)->setOrganizationId(null);

        return $sig;
    }

    private function issueOneCertificate(User $admin): Certificate
    {
        app(Tenancy::class)->setOrganizationId($this->org->id);
        $sig = Signatory::create(['nama' => 'Budi Karya', 'jabatan' => 'Kepala Dinas']);
        // Template legacy (tanpa layout visual) → render DomPDF (tak butuh Node).
        $template = Template::create(['nama' => 'Default', 'slug' => 'default-'.uniqid(), 'view' => 'certificates.default']);
        $event = Event::create([
            'nama' => 'Acara Uji', 'kode' => 'UJI', 'jadwal_mulai' => now(),
            'template_id' => $template->id,
        ]);
        $event->forceFill(['created_by' => $admin->id])->save();
        // Owner sebagai EventMember approved (EventPolicy::view) — sebagaimana EventService.
        EventMember::create([
            'event_id' => $event->id, 'user_id' => $admin->id,
            'role' => EventMember::ROLE_OWNER,
            'status' => EventMember::STATUS_APPROVED, 'approved_at' => now(),
        ]);
        $event->signatories()->attach($sig->id, ['urutan' => 1]);
        $person = Person::create(['nama' => 'Peserta Uji', 'email' => 'uji@test.local']);
        $reg = Registration::create(['person_id' => $person->id, 'event_id' => $event->id, 'sumber' => 'manual']);
        app(Tenancy::class)->setOrganizationId(null);

        return app(CertificateIssuer::class)->issue($reg, $admin->id);
    }
}
