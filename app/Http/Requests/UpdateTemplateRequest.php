<?php

/**
 * app/Http/Requests/UpdateTemplateRequest.php
 * Validasi pembaruan template sertifikat (P6).
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-templates') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'background' => ['nullable', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:5120'],
            'posisi_field' => ['nullable', 'array'],
            'posisi_field.*.x' => ['nullable', 'numeric'],
            'posisi_field.*.y' => ['nullable', 'numeric'],
            'posisi_field.*.size' => ['nullable', 'numeric'],
            'is_active' => ['boolean'],
        ];
    }
}
