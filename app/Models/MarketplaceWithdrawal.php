<?php

/**
 * app/Models/MarketplaceWithdrawal.php
 * Pencairan royalti Creator (credit → rupiah). Status: pending|scheduled|approved|rejected|paid.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'credit_requested', 'admin_fee_credit', 'credit_paid', 'rupiah_paid',
    'status', 'scheduled_payout_date', 'requested_at', 'processed_at', 'processed_by', 'notes',
])]
class MarketplaceWithdrawal extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PAID = 'paid';

    protected function casts(): array
    {
        return [
            'scheduled_payout_date' => 'datetime',
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_SCHEDULED, self::STATUS_APPROVED], true);
    }
}
