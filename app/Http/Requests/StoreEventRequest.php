<?php

/**
 * app/Http/Requests/StoreEventRequest.php
 * Validasi pembuatan acara (FR-03).
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-events') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['nullable', 'string', 'max:50'],
            'jadwal_mulai' => ['required', 'date'],
            'jadwal_selesai' => ['nullable', 'date', 'after_or_equal:jadwal_mulai'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'template_id' => ['nullable', 'exists:templates,id'],
            'signatory_ids' => ['array'],
            'signatory_ids.*' => ['integer', 'exists:signatories,id'],
        ];
    }
}
