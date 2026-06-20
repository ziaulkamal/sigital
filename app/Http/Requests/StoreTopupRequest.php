<?php

/**
 * app/Http/Requests/StoreTopupRequest.php
 * Validasi pengajuan topup credit oleh user. Bukti transfer opsional (disk privat).
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Semua user terautentikasi & terapprove boleh mengajukan topup.
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'amount_credit' => ['required', 'integer', 'min:10', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:255'],
            'proof' => ['nullable', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'amount_credit' => 'jumlah credit',
            'proof' => 'bukti transfer',
        ];
    }
}
