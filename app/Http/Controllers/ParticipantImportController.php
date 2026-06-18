<?php

/**
 * app/Http/Controllers/ParticipantImportController.php
 * Impor CSV peserta: pratinjau (validasi+duplikat) lalu commit (FR-07/08).
 */

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\ParticipantImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ParticipantImportController extends Controller
{
    public function __construct(private readonly ParticipantImportService $service) {}

    /** Langkah 1 — kembalikan hasil pratinjau (tanpa menyimpan). */
    public function preview(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('view', $event); // P7 — hanya owner/member acara
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        try {
            $result = $this->service->preview($event, $request->file('file'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('importPreview', $result);
    }

    /** Langkah 2 — simpan baris yang dipilih. */
    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('view', $event); // P7 — hanya owner/member acara
        $data = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.nama' => ['required', 'string'],
            'rows.*.email' => ['nullable', 'email'],
            'rows.*.nik' => ['nullable', 'string'],
        ]);

        $saved = $this->service->commit($event, $data['rows']);

        return redirect()->route('events.show', $event)
            ->with('success', "{$saved} peserta berhasil diimpor.");
    }
}
