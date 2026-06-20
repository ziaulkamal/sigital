<?php

/**
 * app/Models/CreditTransaction.php
 * Satu baris ledger credit (append-only). Ditulis hanya oleh CreditService.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'user_id', 'type', 'amount', 'balance_after',
    'reference_type', 'reference_id', 'description', 'created_by',
])]
class CreditTransaction extends Model
{
    public const TYPE_TOPUP = 'topup';
    public const TYPE_CONSUME = 'consume';
    public const TYPE_GRANT = 'grant';
    public const TYPE_REFUND = 'refund';
    public const TYPE_ADJUST = 'adjust';
    // Marketplace (Bagian 6).
    public const TYPE_TEMPLATE_PURCHASE = 'template_purchase';
    public const TYPE_TEMPLATE_ROYALTY = 'template_royalty';
    public const TYPE_WITHDRAW = 'withdraw';
    public const TYPE_WITHDRAW_FEE = 'withdraw_fee';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
