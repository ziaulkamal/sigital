<?php

/**
 * app/Http/Controllers/ParticipantController.php
 * Tambah/hapus peserta manual pada sebuah acara (FR-06).
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreParticipantRequest;
use App\Models\Event;
use App\Models\Registration;
use App\Services\ParticipantService;
use Illuminate\Http\RedirectResponse;

class ParticipantController extends Controller
{
    public function __construct(private readonly ParticipantService $service) {}

    public function store(StoreParticipantRequest $request, Event $event): RedirectResponse
    {
        if ($this->service->isDuplicate($event, $request->validated())) {
            return back()->with('error', 'Peserta sudah terdaftar di acara ini.');
        }

        $this->service->addManual($event, $request->validated());

        return back()->with('success', 'Peserta ditambahkan.');
    }

    public function destroy(Registration $registration): RedirectResponse
    {
        abort_unless($registration->certificate()->doesntExist(), 422, 'Peserta dengan sertifikat terbit tak bisa dihapus.');
        $registration->delete();

        return back()->with('success', 'Peserta dihapus.');
    }
}
