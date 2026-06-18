<?php

/**
 * app/Http/Controllers/BrandingController.php
 * Branding organisasi (P6/K8): unggah logo & kop yang disisipkan di kepala sertifikat.
 * Menyasar organisasi milik user. SuperAdmin (tanpa org) tidak punya branding sendiri.
 */

namespace App\Http\Controllers;

use App\Http\Requests\BrandingUpdateRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function update(BrandingUpdateRequest $request): RedirectResponse
    {
        $org = $request->user()->organization;

        if ($org === null) {
            return back()->with('error', 'SuperAdmin tidak memiliki organisasi untuk branding.');
        }

        $data = [];

        if ($logo = $request->file('logo')) {
            $this->deleteIfExists($org->logo_path);
            $data['logo_path'] = $logo->store('branding', 'public');
        }

        if ($kop = $request->file('kop')) {
            $this->deleteIfExists($org->kop_path);
            $data['kop_path'] = $kop->store('branding', 'public');
        }

        if ($data === []) {
            return back()->with('error', 'Tidak ada berkas yang diunggah.');
        }

        $org->update($data);
        $this->audit->log('organization.branding_updated', $org, ['fields' => array_keys($data)]);

        return back()->with('success', 'Branding organisasi berhasil diperbarui.');
    }

    private function deleteIfExists(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
