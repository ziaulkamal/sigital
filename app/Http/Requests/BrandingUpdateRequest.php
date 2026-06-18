<?php

/**
 * app/Http/Requests/BrandingUpdateRequest.php
 * Validasi unggah branding organisasi (P6/K8): logo & kop surat (gambar).
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BrandingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-templates') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'kop' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:4096'],
        ];
    }
}
