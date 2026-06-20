<?php

/**
 * app/Http/Requests/SaveTemplateLayoutRequest.php
 * Validasi penyimpanan layout dari perancang visual (WYSIWYG).
 * Validasi di sini sengaja longgar pada struktur; sanitasi ketat (whitelist
 * type/font, clamp koordinat, strip teks) dilakukan App\Support\TemplateLayout.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveTemplateLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-templates') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'layout' => ['required', 'array'],
            'layout.canvas' => ['required', 'array'],
            'layout.canvas.w' => ['required', 'integer', 'min:1', 'max:20000'],
            'layout.canvas.h' => ['required', 'integer', 'min:1', 'max:20000'],
            'layout.elements' => ['present', 'array', 'max:50'],
            // type/font sengaja TIDAK divalidasi ketat di sini — App\Support\TemplateLayout
            // membuang tipe/font tak dikenal & meng-clamp koordinat (sanitasi tunggal).
            'layout.elements.*.type' => ['required', 'string'],
            'layout.elements.*.x' => ['required', 'numeric'],
            'layout.elements.*.y' => ['required', 'numeric'],
            'layout.elements.*.w' => ['nullable', 'numeric'],
            'layout.elements.*.size' => ['nullable', 'numeric'],
            'layout.elements.*.font' => ['nullable', 'string'],
            'layout.elements.*.color' => ['nullable', 'string'],
            'layout.elements.*.align' => ['nullable', 'string'],
            'layout.elements.*.bold' => ['nullable', 'boolean'],
            'layout.elements.*.text' => ['nullable', 'string', 'max:300'],
            'layout.elements.*.id' => ['nullable', 'string', 'max:64'],
        ];
    }
}
