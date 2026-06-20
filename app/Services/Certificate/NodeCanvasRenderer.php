<?php

/**
 * app/Services/Certificate/NodeCanvasRenderer.php
 *
 * Render sertifikat dari layout perancang visual (WYSIWYG) memakai mesin Konva
 * yang sama dengan editor. Membangun payload per-sertifikat (binding nilai
 * dinamis + QR + path font/gambar absolut), memanggil Node CLI via Symfony
 * Process (payload via STDIN → PNG via STDOUT), lalu membungkus PNG menjadi
 * PDF A4 landscape (DomPDF, full-bleed) agar konsisten dgn alur penerbitan.
 *
 * Keamanan: tidak ada masukan klien yang diteruskan sebagai argumen shell;
 * seluruh payload dikirim via STDIN. Semua path diresolusi server & dicek ada.
 */

namespace App\Services\Certificate;

use App\Models\Certificate;
use App\Models\Template;
use App\Support\KeteranganGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class NodeCanvasRenderer
{
    public function __construct(
        private readonly QrCodeGenerator $qr,
        private readonly KeteranganGenerator $keterangan,
    ) {}

    /** Hasilkan biner PDF untuk sertifikat memakai layout visual template. */
    public function render(Certificate $certificate): string
    {
        $png = $this->renderPng($certificate);

        // Bungkus PNG menjadi PDF A4 landscape (full-bleed).
        $dataUri = 'data:image/png;base64,'.base64_encode($png);

        return Pdf::loadView('certificates.canvas-image', ['image' => $dataUri])
            ->setPaper('a4', 'landscape')
            ->output();
    }

    /** Render PNG mentah (dipakai render() dan pratinjau editor). */
    public function renderPng(Certificate $certificate): string
    {
        $payload = $this->buildPayload($certificate);

        return $this->runNode($payload);
    }

    /**
     * Pratinjau PNG dengan DATA DUMMY untuk uji kecocokan posisi di editor (poin 4).
     * Tidak menyentuh data nyata; QR dummy memakai token contoh.
     */
    public function previewPng(Template $template): string
    {
        $layout = $template->posisi_field ?? ['canvas' => [], 'elements' => []];
        $cw = (int) ($template->canvas_width ?: ($layout['canvas']['w'] ?? 1123));
        $ch = (int) ($template->canvas_height ?: ($layout['canvas']['h'] ?? 794));

        $dummyTtd = $this->publicPath($template->background_path); // sekadar gambar contoh bila ada
        $signatories = [
            ['nama' => 'Dr. Ahmad Dahlan, M.Si', 'jabatan' => 'Kepala Dinas', 'image' => $dummyTtd, 'is_srikandi' => false],
            ['nama' => 'Siti Aminah, S.Kom', 'jabatan' => 'Ketua Panitia', 'image' => $dummyTtd, 'is_srikandi' => true],
        ];

        $payload = [
            'canvas' => ['w' => $cw, 'h' => $ch],
            'background' => $this->publicPath($template->background_path),
            'elements' => $layout['elements'] ?? [],
            'values' => [
                'nama_peserta' => 'Budi Santoso',
                'event' => 'telah menyelesaikan kegiatan Workshop Transformasi Digital di Aula Utama pada tanggal 12 – 14 Februari 2026 dengan jumlah waktu 18 jam yang diadakan oleh '.($template->organization?->nama ?? config('sigital.instansi_nama')).'.',
                'tanggal' => '12 Februari 2026',
                'nomor' => 'No. CONTOH/001/2026',
            ],
            // Token contoh untuk template keterangan kustom (pratinjau).
            'tokens' => [
                'acara' => 'Workshop Transformasi Digital',
                'lokasi' => 'Aula Utama',
                'tanggal' => '12 – 14 Februari 2026',
                'durasi' => '18 jam',
                'instansi' => $template->organization?->nama ?? config('sigital.instansi_nama'),
            ],
            'images' => [
                'qr' => $this->qr->svgDataUri('preview-dummy-token', 600),
                'ttd' => $dummyTtd,
                'logo' => $this->publicPath($template->organization?->logo_path),
            ],
            'signatories' => $signatories,
            'fonts' => $this->fontPaths($template->fonts ?? []),
        ];

        return $this->runNode($payload);
    }

    /**
     * Bangun payload untuk Node: layout + nilai dinamis + path absolut.
     *
     * @return array<string,mixed>
     */
    public function buildPayload(Certificate $certificate): array
    {
        $certificate->loadMissing([
            'registration.person',
            'registration.event.signatories',
            'registration.event.template',
            'registration.event.organization',
        ]);

        $event = $certificate->registration->event;
        $template = $event->template;
        $person = $certificate->registration->person;
        $org = $event->organization;

        $layout = $template?->posisi_field ?? ['canvas' => [], 'elements' => []];
        $cw = (int) ($template?->canvas_width ?: ($layout['canvas']['w'] ?? 1123));
        $ch = (int) ($template?->canvas_height ?: ($layout['canvas']['h'] ?? 794));

        $instansi = $org?->nama ?? config('sigital.instansi_nama');

        // Penanda tangan (urut sesuai pivot) → tiap orang: TTD basah ATAU QR SRIKANDI + nama/jabatan.
        $signatories = $event->signatories->map(fn ($s) => [
            'nama' => $s->nama,
            'jabatan' => $s->jabatan,
            // SRIKANDI QR diutamakan sebagai gambar bila ada; selain itu TTD basah.
            'image' => $this->publicPath($s->qr_srikandi_path) ?? $this->publicPath($s->gambar_ttd),
            'is_srikandi' => $s->qr_srikandi_path !== null,
        ])->values()->all();

        // Logo acara (poin 1) → fallback ke logo organisasi bila kosong.
        $logo = $this->publicPath($event->logo_path) ?? $this->publicPath($org?->logo_path);

        return [
            'canvas' => ['w' => $cw, 'h' => $ch],
            'background' => $this->publicPath($template?->background_path),
            'elements' => $layout['elements'] ?? [],
            'values' => [
                'nama_peserta' => $person->nama,
                // Keterangan kegiatan (poin 2/5): keterangan acara (admin) atau auto.
                // Dipakai bila element 'event' TIDAK punya template kustom sendiri.
                'event' => $this->keterangan->for($event, $instansi),
                'tanggal' => $event->jadwal_mulai
                    ? Carbon::parse($event->jadwal_mulai)->translatedFormat('d F Y')
                    : '',
                'nomor' => 'No. '.$certificate->nomor_unik,
            ],
            // Token untuk template keterangan kustom per-element (disisip via {token}).
            'tokens' => $this->keterangan->tokens($event, $instansi),
            'images' => [
                'qr' => $this->qr->svgDataUri($certificate->qr_token, 600),
                // 'ttd' tunggal = penanda tangan pertama (kompat slot lama).
                'ttd' => $signatories[0]['image'] ?? null,
                'logo' => $logo,
            ],
            // Blok 'tanda_tangan' (poin 6): semua penanda tangan untuk auto-layout di Node.
            'signatories' => $signatories,
            'fonts' => $this->fontPaths($template?->fonts ?? []),
        ];
    }

    /**
     * Jalankan Node renderer. Payload via STDIN, PNG via STDOUT.
     */
    private function runNode(array $payload): string
    {
        $script = base_path('node-renderer/render.mjs');
        if (! is_file($script)) {
            throw new RuntimeException("Node renderer tidak ditemukan: {$script}");
        }

        $node = (string) config('fonts.node_binary', 'node');

        $process = new Process([$node, $script]);
        $process->setInput(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $process->setTimeout(60);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            throw new RuntimeException(
                'Render Node gagal: '.$process->getErrorOutput(),
                previous: $e,
            );
        }

        $png = $process->getOutput();
        if ($png === '' || ! str_starts_with($png, "\x89PNG")) {
            throw new RuntimeException('Render Node tidak menghasilkan PNG valid. STDERR: '.$process->getErrorOutput());
        }

        return $png;
    }

    /**
     * Path .ttf absolut untuk font yang dipakai template (whitelist config).
     *
     * @param  array<int,string>  $families
     * @return array<int, array{family:string, regular:?string, bold:?string}>
     */
    private function fontPaths(array $families): array
    {
        $dir = base_path((string) config('fonts.path', 'resources/fonts'));
        $catalog = (array) config('fonts.families', []);

        // Selalu sertakan font default sebagai fallback.
        $default = (string) config('fonts.default', 'DejaVu Sans');
        $families = array_values(array_unique([...$families, $default]));

        $out = [];
        foreach ($families as $name) {
            $meta = $catalog[$name] ?? null;
            if (! $meta) {
                continue;
            }
            $regular = $dir.DIRECTORY_SEPARATOR.($meta['regular'] ?? '');
            $bold = isset($meta['bold']) ? $dir.DIRECTORY_SEPARATOR.$meta['bold'] : null;

            $out[] = [
                'family' => $meta['family'] ?? $name,
                'regular' => is_file($regular) ? $regular : null,
                'bold' => $bold && is_file($bold) ? $bold : null,
            ];
        }

        return $out;
    }

    /** Path absolut berkas pada disk public bila ada. */
    private function publicPath(?string $relative): ?string
    {
        if ($relative === null || $relative === '') {
            return null;
        }

        $absolute = storage_path('app/public/'.$relative);

        return file_exists($absolute) ? $absolute : null;
    }
}
