<?php

/**
 * app/Http/Controllers/SignatoryController.php
 * CRUD penanda tangan (data master Admin).
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreSignatoryRequest;
use App\Http\Requests\UpdateSignatoryRequest;
use App\Models\Signatory;
use App\Services\SignatoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SignatoryController extends Controller
{
    public function __construct(private readonly SignatoryService $service) {}

    public function index(): Response
    {
        return Inertia::render('Signatories/Index', [
            'signatories' => Signatory::orderByDesc('id')->get()->map(fn ($s) => [
                'id' => $s->id,
                'nama' => $s->nama,
                'jabatan' => $s->jabatan,
                'gambar_ttd' => $s->gambar_ttd ? asset('storage/'.$s->gambar_ttd) : null,
                'is_active' => $s->is_active,
            ]),
        ]);
    }

    public function store(StoreSignatoryRequest $request): RedirectResponse
    {
        $this->service->create($request->safe()->except('gambar_ttd'), $request->file('gambar_ttd'));

        return back()->with('success', 'Penanda tangan berhasil ditambahkan.');
    }

    public function update(UpdateSignatoryRequest $request, Signatory $signatory): RedirectResponse
    {
        $this->service->update($signatory, $request->safe()->except('gambar_ttd'), $request->file('gambar_ttd'));

        return back()->with('success', 'Penanda tangan berhasil diperbarui.');
    }

    public function destroy(Signatory $signatory): RedirectResponse
    {
        $this->service->deactivate($signatory);

        return back()->with('success', 'Penanda tangan dinonaktifkan.');
    }
}
