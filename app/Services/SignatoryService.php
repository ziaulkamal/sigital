<?php

/**
 * app/Services/SignatoryService.php
 * Logika manajemen penanda tangan + penyimpanan spesimen TTD (FR-01).
 */

namespace App\Services;

use App\Models\Signatory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SignatoryService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** @param array{nama:string,jabatan:string,is_active?:bool} $data */
    public function create(array $data, ?UploadedFile $ttd = null): Signatory
    {
        if ($ttd) {
            $data['gambar_ttd'] = $ttd->store('signatures', 'public');
        }

        $signatory = Signatory::create($data);
        $this->audit->log('signatory.created', $signatory, ['nama' => $signatory->nama]);

        return $signatory;
    }

    /** @param array{nama:string,jabatan:string,is_active?:bool} $data */
    public function update(Signatory $signatory, array $data, ?UploadedFile $ttd = null): Signatory
    {
        if ($ttd) {
            $this->deleteTtd($signatory);
            $data['gambar_ttd'] = $ttd->store('signatures', 'public');
        }

        $signatory->update($data);
        $this->audit->log('signatory.updated', $signatory, ['nama' => $signatory->nama]);

        return $signatory;
    }

    /** Nonaktifkan (soft) ketimbang hapus, agar jejak penerbitan lama tetap utuh. */
    public function deactivate(Signatory $signatory): void
    {
        $signatory->update(['is_active' => false]);
        $this->audit->log('signatory.deactivated', $signatory);
    }

    private function deleteTtd(Signatory $signatory): void
    {
        if ($signatory->gambar_ttd) {
            Storage::disk('public')->delete($signatory->gambar_ttd);
        }
    }
}
