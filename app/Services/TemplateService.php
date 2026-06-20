<?php

/**
 * app/Services/TemplateService.php
 * Manajemen template sertifikat per-organisasi (P6/K7): unggah gambar latar +
 * posisi field (koordinat JSON, BUKAN HTML mentah — hindari injeksi).
 */

namespace App\Services;

use App\Models\Template;
use App\Support\TemplateLayout;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemplateService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{nama:string,deskripsi?:string|null,posisi_field?:array|null,is_active?:bool}  $data
     */
    public function create(array $data, ?UploadedFile $background = null): Template
    {
        $template = new Template($this->prepare($data, $background));
        // Latar diunggah → render via kanvas koordinat; selain itu pakai view default.
        $template->view = $template->background_path ? 'certificates.canvas' : 'certificates.default';
        $template->uploaded_by = Auth::id();
        $template->save();

        $this->audit->log('template.uploaded', $template, ['nama' => $template->nama]);

        return $template;
    }

    /** @param array{nama:string,deskripsi?:string|null,posisi_field?:array|null,is_active?:bool} $data */
    public function update(Template $template, array $data, ?UploadedFile $background = null): Template
    {
        if ($background) {
            $this->deleteBackground($template);
        }

        $template->fill($this->prepare($data, $background));
        if ($background) {
            $template->view = 'certificates.canvas';
        }
        $template->save();

        $this->audit->log('template.updated', $template, ['nama' => $template->nama]);

        return $template;
    }

    public function deactivate(Template $template): void
    {
        $template->update(['is_active' => false]);
        $this->audit->log('template.deactivated', $template);
    }

    /**
     * Simpan layout dari perancang visual (WYSIWYG). Layout DISANITASI penuh
     * (anti-injeksi) sebelum disimpan; `fonts` diturunkan dari element teks.
     *
     * @param  array<string,mixed>|null  $layout
     */
    public function saveLayout(Template $template, ?array $layout): Template
    {
        $clean = TemplateLayout::sanitize($layout);

        $template->posisi_field = $clean;
        $template->fonts = $this->collectFonts($clean);
        // Template dgn layout visual selalu dirender via kanvas.
        $template->view = 'certificates.canvas';
        $template->save();

        $this->audit->log('template.layout_saved', $template, [
            'elements' => count($clean['elements']),
        ]);

        return $template;
    }

    /**
     * Daftar font unik yang dipakai element teks (untuk dimuat Node renderer).
     *
     * @param  array{elements: array<int, array<string,mixed>>}  $layout
     * @return array<int, string>
     */
    private function collectFonts(array $layout): array
    {
        $fonts = [];
        foreach ($layout['elements'] as $el) {
            if (isset($el['font']) && is_string($el['font'])) {
                $fonts[$el['font']] = true;
            }
        }

        return array_values(array_keys($fonts));
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function prepare(array $data, ?UploadedFile $background): array
    {
        if (! isset($data['slug']) || $data['slug'] === null) {
            $data['slug'] = Str::slug($data['nama']).'-'.Str::lower(Str::random(6));
        }

        if ($background) {
            $data['background_path'] = $background->store('templates', 'public');
            $data['background_mime'] = $background->getMimeType();

            // Dimensi natural gambar → basis konversi fraksi→piksel (WYSIWYG).
            $size = @getimagesize($background->getRealPath());
            if ($size !== false) {
                $data['canvas_width'] = $size[0];
                $data['canvas_height'] = $size[1];
            }
        }

        return $data;
    }

    private function deleteBackground(Template $template): void
    {
        if ($template->background_path) {
            Storage::disk('public')->delete($template->background_path);
        }
    }
}
