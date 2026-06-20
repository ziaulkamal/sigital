<?php

/**
 * tests/Feature/TemplateBrandingTest.php
 * Acceptance P6: template ter-scope org + uploaded_by (K7); branding logo/kop org (K8);
 * PDF terbit dengan latar template + logo organisasi (renderer kanvas).
 */

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Registration;
use App\Models\Signatory;
use App\Models\Template;
use App\Models\User;
use App\Services\Certificate\CertificatePdfRenderer;
use App\Support\Tenancy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TemplateBrandingTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;
    private Organization $orgB;

    /** @var list<string> */
    private array $realFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->orgA = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas']);
        $this->orgB = Organization::create(['nama' => 'Komunitas B', 'kode' => 'KOMB', 'type' => 'komunitas']);
    }

    protected function tearDown(): void
    {
        foreach ($this->realFiles as $f) {
            @unlink($f);
        }
        parent::tearDown();
    }

    private function adminFor(Organization $org): User
    {
        $user = User::create(['name' => "Admin {$org->kode}", 'email' => "admin-{$org->kode}@test.local", 'password' => 'password']);
        $user->forceFill(['organization_id' => $org->id, 'nik' => fake()->unique()->numerify('################'), 'phone' => '628'.fake()->numerify('#########')])->save();
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $user->assignRole('Admin');

        return $user;
    }

    /** Tulis PNG 1x1 nyata ke disk public (renderer memakai storage_path, bukan Storage fake). */
    private function realPublicPng(string $relative): string
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $abs = storage_path('app/public/'.$relative);
        @mkdir(dirname($abs), 0777, true);
        file_put_contents($abs, $png);
        $this->realFiles[] = $abs;

        return $relative;
    }

    // ── Template CRUD & scoping ──────────────────────────────────────────

    public function test_admin_can_upload_template_with_background(): void
    {
        Storage::fake('public');
        $admin = $this->adminFor($this->orgA);

        $this->actingAs($admin)->post('/templates', [
            'nama' => 'Template Latar',
            'background' => UploadedFile::fake()->image('bg.png', 800, 600),
        ])->assertRedirect()->assertSessionHas('success');

        app(Tenancy::class)->setOrganizationId($this->orgA->id);
        $tpl = Template::first();
        app(Tenancy::class)->setOrganizationId(null);

        $this->assertNotNull($tpl);
        $this->assertSame($this->orgA->id, $tpl->organization_id);
        $this->assertSame($admin->id, $tpl->uploaded_by);
        $this->assertSame('certificates.canvas', $tpl->view);
        $this->assertNotNull($tpl->background_path);
        Storage::disk('public')->assertExists($tpl->background_path);
    }

    public function test_template_listing_is_scoped_but_includes_global(): void
    {
        // Template org B (tak terlihat A) + satu template global (terlihat semua).
        app(Tenancy::class)->setOrganizationId($this->orgB->id);
        Template::create(['nama' => 'Punya B', 'slug' => 'punya-b']);
        app(Tenancy::class)->setOrganizationId(null);
        Template::create(['nama' => 'Global X', 'slug' => 'global-x', 'is_global' => true]); // org null

        $adminA = $this->adminFor($this->orgA);

        $this->actingAs($adminA)->get('/templates')
            ->assertOk()
            ->assertSee('Global X')
            ->assertDontSee('Punya B');
    }

    // ── Branding ─────────────────────────────────────────────────────────

    public function test_admin_can_upload_branding(): void
    {
        Storage::fake('public');
        $admin = $this->adminFor($this->orgA);

        $this->actingAs($admin)->put('/settings/branding', [
            'logo' => UploadedFile::fake()->image('logo.png'),
            'kop' => UploadedFile::fake()->image('kop.png', 1200, 200),
        ])->assertRedirect()->assertSessionHas('success');

        $this->orgA->refresh();
        $this->assertNotNull($this->orgA->logo_path);
        $this->assertNotNull($this->orgA->kop_path);
        $this->assertDatabaseHas('audit_logs', ['aksi' => 'organization.branding_updated']);
    }

    // ── Render PDF dengan latar + logo ───────────────────────────────────

    public function test_pdf_renders_with_template_background_and_org_logo(): void
    {
        // Org A punya logo + template berlatar; terbitkan satu sertifikat.
        $bg = $this->realPublicPng('templates/bg-test.png');
        $logo = $this->realPublicPng('branding/logo-test.png');
        $ttd = $this->realPublicPng('signatures/ttd-test.png');

        $this->orgA->update(['logo_path' => $logo]);

        app(Tenancy::class)->setOrganizationId($this->orgA->id);
        $template = Template::create([
            'nama' => 'Latar A', 'slug' => 'latar-a',
            'background_path' => $bg, 'view' => 'certificates.canvas',
            'posisi_field' => ['nama' => ['x' => 0, 'y' => 240, 'size' => 26, 'align' => 'center']],
        ]);
        $event = Event::create(['nama' => 'Workshop A', 'jadwal_mulai' => now(), 'template_id' => $template->id]);
        $sig = Signatory::create(['nama' => 'Budi', 'jabatan' => 'Kepala', 'gambar_ttd' => $ttd]);
        $event->signatories()->attach($sig->id, ['urutan' => 1]);

        $person = Person::create(['nama' => 'Peserta Satu', 'email' => 'p1@test.local']);
        $reg = Registration::create(['person_id' => $person->id, 'event_id' => $event->id, 'sumber' => 'manual']);
        $cert = Certificate::create([
            'organization_id' => $this->orgA->id,
            'registration_id' => $reg->id,
            'nomor_unik' => 'DINASA/001',
            'qr_token' => bin2hex(random_bytes(20)),
            'status' => Certificate::STATUS_ISSUED,
            'issued_at' => now(),
        ]);
        app(Tenancy::class)->setOrganizationId(null);

        $pdf = app(CertificatePdfRenderer::class)->render($cert);

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    // ── Perancang visual (WYSIWYG) ───────────────────────────────────────

    public function test_admin_can_save_visual_layout(): void
    {
        $admin = $this->adminFor($this->orgA);
        app(Tenancy::class)->setOrganizationId($this->orgA->id);
        $tpl = Template::create(['nama' => 'Visual', 'slug' => 'visual', 'canvas_width' => 1123, 'canvas_height' => 794]);
        app(Tenancy::class)->setOrganizationId(null);

        $this->actingAs($admin)->post("/templates/{$tpl->id}/layout", [
            'layout' => [
                'canvas' => ['w' => 1123, 'h' => 794],
                'elements' => [
                    ['id' => 'a', 'type' => 'nama_peserta', 'x' => 0.5, 'y' => 0.4, 'w' => 0.6, 'size' => 0.06, 'align' => 'center', 'color' => '#1d4ed8', 'bold' => true, 'font' => 'Poppins'],
                    ['id' => 'b', 'type' => 'qr', 'x' => 0.08, 'y' => 0.8, 'w' => 0.12],
                ],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $tpl->refresh();
        $this->assertTrue($tpl->hasVisualLayout());
        $this->assertCount(2, $tpl->posisi_field['elements']);
        $this->assertSame('certificates.canvas', $tpl->view);
        $this->assertContains('Poppins', $tpl->fonts);
        $this->assertDatabaseHas('audit_logs', ['aksi' => 'template.layout_saved']);
    }

    public function test_layout_sanitizes_unknown_type_font_and_clamps_coords(): void
    {
        $admin = $this->adminFor($this->orgA);
        app(Tenancy::class)->setOrganizationId($this->orgA->id);
        $tpl = Template::create(['nama' => 'Sanit', 'slug' => 'sanit']);
        app(Tenancy::class)->setOrganizationId(null);

        $this->actingAs($admin)->post("/templates/{$tpl->id}/layout", [
            'layout' => [
                'canvas' => ['w' => 1000, 'h' => 700],
                'elements' => [
                    // type tak dikenal → dibuang
                    ['id' => 'evil', 'type' => 'script', 'x' => 0.5, 'y' => 0.5],
                    // font di luar whitelist → diganti default; koordinat di luar [0,1] → di-clamp;
                    // warna invalid → default; teks <script> → di-strip.
                    ['id' => 'ok', 'type' => 'teks', 'x' => 5, 'y' => -2, 'w' => 9, 'size' => 0.04, 'font' => 'EvilFont', 'color' => 'javascript:alert(1)', 'text' => '<script>x</script>Halo'],
                ],
            ],
        ])->assertRedirect();

        $tpl->refresh();
        $els = $tpl->posisi_field['elements'];
        $this->assertCount(1, $els); // 'script' dibuang
        $this->assertSame('teks', $els[0]['type']);
        $this->assertEquals(1, $els[0]['x']); // di-clamp ke 1
        $this->assertEquals(0, $els[0]['y']); // di-clamp ke 0
        $this->assertSame(config('fonts.default'), $els[0]['font']);
        $this->assertSame('#111827', $els[0]['color']);
        $this->assertStringNotContainsString('<script>', $els[0]['text']);
    }

    public function test_layout_accepts_tanda_tangan_block(): void
    {
        $admin = $this->adminFor($this->orgA);
        app(Tenancy::class)->setOrganizationId($this->orgA->id);
        $tpl = Template::create(['nama' => 'TTD', 'slug' => 'ttd-block']);
        app(Tenancy::class)->setOrganizationId(null);

        $this->actingAs($admin)->post("/templates/{$tpl->id}/layout", [
            'layout' => [
                'canvas' => ['w' => 1000, 'h' => 700],
                'elements' => [
                    ['id' => 's', 'type' => 'tanda_tangan', 'x' => 0.15, 'y' => 0.72, 'w' => 0.7, 'size' => 0.022, 'font' => 'Poppins', 'color' => '#111827'],
                ],
            ],
        ])->assertRedirect();

        $tpl->refresh();
        $els = $tpl->posisi_field['elements'];
        $this->assertSame('tanda_tangan', $els[0]['type']);
        $this->assertSame('Poppins', $els[0]['font']); // blok membawa gaya teks
    }

    public function test_node_renderer_produces_pdf_when_node_available(): void
    {
        // Lewati bila Node/dependensi node-renderer tak terpasang agar suite tetap hijau.
        $node = (string) config('fonts.node_binary', 'node');
        $check = new \Symfony\Component\Process\Process(
            [$node, '-e', "require('canvas')"],
            base_path('node-renderer'),
        );
        $check->run();
        if (! $check->isSuccessful()) {
            $this->markTestSkipped('Node atau paket canvas (node-renderer) tidak tersedia: '.$check->getErrorOutput());
        }

        $bg = $this->realPublicPng('templates/bg-node.png');

        app(Tenancy::class)->setOrganizationId($this->orgA->id);
        $template = Template::create([
            'nama' => 'Node', 'slug' => 'node-tpl',
            'background_path' => $bg, 'view' => 'certificates.canvas',
            'canvas_width' => 200, 'canvas_height' => 140,
            'fonts' => ['DejaVu Sans'],
            'posisi_field' => [
                'canvas' => ['w' => 200, 'h' => 140],
                'elements' => [
                    ['id' => 'n', 'type' => 'nama_peserta', 'x' => 0.1, 'y' => 0.4, 'w' => 0.8, 'size' => 0.1, 'align' => 'center', 'color' => '#111827', 'bold' => true, 'font' => 'DejaVu Sans'],
                ],
            ],
        ]);
        $event = Event::create(['nama' => 'Workshop Node', 'jadwal_mulai' => now(), 'template_id' => $template->id]);
        $person = Person::create(['nama' => 'Peserta Node', 'email' => 'pn@test.local']);
        $reg = Registration::create(['person_id' => $person->id, 'event_id' => $event->id, 'sumber' => 'manual']);
        $cert = Certificate::create([
            'organization_id' => $this->orgA->id,
            'registration_id' => $reg->id,
            'nomor_unik' => 'DINASA/NODE',
            'qr_token' => bin2hex(random_bytes(20)),
            'status' => Certificate::STATUS_ISSUED,
            'issued_at' => now(),
        ]);
        app(Tenancy::class)->setOrganizationId(null);

        $pdf = app(CertificatePdfRenderer::class)->render($cert);

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }
}
