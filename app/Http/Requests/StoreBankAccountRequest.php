<?php

/**
 * app/Http/Requests/StoreBankAccountRequest.php
 * Validasi rekening pencairan Creator. Diisi setelah aplikasi creator di-approve;
 * setiap pengisian/ubah → status rekening kembali 'pending' (verifikasi ulang).
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya creator aktif yang boleh mengisi rekening.
        return $this->user()?->isMarketplaceCreator() ?? false;
    }

    /** Buang spasi pada nomor rekening sebelum validasi. */
    protected function prepareForValidation(): void
    {
        if ($this->has('bank_account_no')) {
            $this->merge(['bank_account_no' => preg_replace('/\s+/', '', (string) $this->input('bank_account_no'))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_account_no' => ['required', 'string', 'max:50', 'regex:/^[0-9]{6,}$/'],
            'bank_account_holder' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'bank_name' => 'nama bank',
            'bank_account_no' => 'nomor rekening',
            'bank_account_holder' => 'nama pemilik rekening',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'bank_account_no.regex' => 'Nomor rekening harus angka (minimal 6 digit).',
        ];
    }
}
