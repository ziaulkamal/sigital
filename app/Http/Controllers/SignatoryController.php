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
use App\Support\Tenancy;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SignatoryController extends Controller
{
    public function __construct(
        private readonly SignatoryService $service,
        private readonly Tenancy $tenancy,
    ) {}

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
        // Cegah penanda tangan yatim: SuperAdmin di mode "Semua organisasi" wajib pilih org dulu.
        if ($this->tenancy->organizationId() === null) {
            return back()->with('error', 'Pilih organisasi terlebih dahulu (switcher kiri atas) sebelum menambah penanda tangan.');
        }

        // P3 — konfirmasi nama serupa. Selama belum "create_new" & ada kandidat mirip
        // dalam organisasi aktif, kembalikan kandidat lewat flash (pola pratinjau impor CSV)
        // agar frontend menampilkan modal konfirmasi tanpa membuat duplikat.
        if ($request->input('confirm') !== 'create_new') {
            $candidates = $this->service->findSimilar($request->validated('nama'));

            if ($candidates->isNotEmpty()) {
                return back()->with('signatoryCandidates', [
                    'nama' => $request->validated('nama'),
                    'jabatan' => $request->validated('jabatan'),
                    'matches' => $candidates->map(fn ($s) => [
                        'id' => $s->id,
                        'nama' => $s->nama,
                        'jabatan' => $s->jabatan,
                        'gambar_ttd' => $s->gambar_ttd ? asset('storage/'.$s->gambar_ttd) : null,
                    ])->all(),
                ]);
            }
        }

        $this->service->create($request->safe()->except('gambar_ttd', 'confirm'), $request->file('gambar_ttd'));

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
