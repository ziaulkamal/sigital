<?php

/**
 * app/Http/Requests/StoreCreatorApplicationRequest.php
 * Validasi pendaftaran Marketplace Creator: identitas + KTP + persetujuan S&K.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreatorApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'ktp' => ['required', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:5120'],
            // Checkbox S&K wajib dicentang.
            'terms' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'full_name' => 'nama lengkap',
            'address' => 'alamat lengkap',
            'ktp' => 'foto KTP',
            'terms' => 'syarat & ketentuan',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'terms.accepted' => 'Anda harus menyetujui syarat & ketentuan.',
        ];
    }
}
