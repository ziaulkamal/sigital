<?php

/**
 * app/Services/TemplateService.php
 * Manajemen template sertifikat per-organisasi (P6/K7): unggah gambar latar +
 * posisi field (koordinat JSON, BUKAN HTML mentah — hindari injeksi).
 */

namespace App\Services;

use App\Models\Template;
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
