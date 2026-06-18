<?php

/**
 * app/Models/AuditLog.php
 * Catatan append-only. updated_at dimatikan; pembaruan/penghapusan diblokir di level model.
 */

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

#[Fillable(['actor_id', 'aksi', 'entitas', 'entitas_id', 'detail', 'ip'])]
class AuditLog extends Model
{
    use BelongsToOrganization;

    public const UPDATED_AT = null; // hanya created_at

    protected function casts(): array
    {
        return ['detail' => 'array'];
    }

    /** Tegakkan append-only: cegah update & delete pada model. */
    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('AuditLog bersifat append-only dan tidak dapat diubah.'));
        static::deleting(fn () => throw new RuntimeException('AuditLog bersifat append-only dan tidak dapat dihapus.'));
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
