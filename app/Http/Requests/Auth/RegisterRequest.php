<?php

/**
 * app/Http/Requests/Auth/RegisterRequest.php
 * Validasi registrasi panitia (P2/K9): pilih organisasi yang ada ATAU ajukan baru.
 * Dinas WAJIB lampirkan surat rekomendasi; komunitas tidak.
 */

namespace App\Http\Requests\Auth;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('org_kode')) {
            $this->merge(['org_kode' => strtoupper(trim($this->input('org_kode')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],

            'org_mode' => ['required', Rule::in(['existing', 'new'])],

            // Bergabung ke organisasi yang sudah aktif.
            'organization_id' => [
                'required_if:org_mode,existing',
                Rule::exists('organizations', 'id')->where('is_active', true),
            ],

            // Ajukan organisasi baru.
            'org_nama' => ['required_if:org_mode,new', 'nullable', 'string', 'max:255'],
            'org_kode' => ['required_if:org_mode,new', 'nullable', 'string', 'max:50', 'unique:organizations,kode'],
            'org_type' => ['required_if:org_mode,new', 'nullable', Rule::in([Organization::TYPE_DINAS, Organization::TYPE_KOMUNITAS])],

            // Surat rekomendasi: wajib hanya untuk dinas (K9).
            'recommendation_letter' => ['required_if:org_type,'.Organization::TYPE_DINAS, 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'recommendation_letter.required_if' => 'Pendaftaran dinas wajib melampirkan surat rekomendasi penunjukan.',
            'organization_id.required_if' => 'Pilih organisasi yang ingin Anda ikuti.',
        ];
    }
}
