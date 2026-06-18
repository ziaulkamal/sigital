<?php

/**
 * app/Models/Certificate.php
 * Sertifikat terbit: nomor unik terkunci, token QR opaque, path & hash PDF, status keaslian.
 */

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id', 'registration_id', 'nomor_unik', 'qr_token', 'pdf_path', 'pdf_hash',
    'status', 'alasan_pencabutan', 'issued_at', 'issued_by', 'batch_id',
])]
class Certificate extends Model
{
    use BelongsToOrganization;

    public const STATUS_ISSUED = 'issued';
    public const STATUS_REVOKED = 'revoked';

    protected function casts(): array
    {
        return ['issued_at' => 'datetime'];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }
}
