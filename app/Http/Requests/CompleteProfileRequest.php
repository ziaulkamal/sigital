<?php

/**
 * app/Http/Requests/CompleteProfileRequest.php
 * Validasi "lengkapi profil" wajib setelah akun di-approve: NIK (16 digit) + nomor HP.
 * Nomor HP dinormalisasi (08… → 628…) di mutator User; di sini cukup pastikan angka.
 */

namespace App\Http\Requests;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** Bersihkan input non-digit sebelum validasi (input UI sudah angka-saja, ini lapis aman). */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nik' => preg_replace('/\D+/', '', (string) $this->input('nik')),
            'phone' => Phone::normalize((string) $this->input('phone')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nik' => [
                'required', 'digits:16',
                Rule::unique('users', 'nik')->ignore($this->user()->id),
            ],
            // Setelah normalisasi: 62 diikuti 9–14 digit (mis. 628123456789).
            'phone' => ['required', 'string', 'regex:/^62[0-9]{8,15}$/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus tepat 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'phone.regex' => 'Nomor HP tidak valid (contoh: 081234567890).',
        ];
    }
}
