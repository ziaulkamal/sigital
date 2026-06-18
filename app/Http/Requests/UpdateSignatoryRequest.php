<?php

/**
 * app/Http/Requests/UpdateSignatoryRequest.php
 * Validasi pembaruan penanda tangan.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSignatoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-signatories') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'gambar_ttd' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'is_active' => ['boolean'],
        ];
    }
}
