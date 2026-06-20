<?php

/**
 * app/Models/TemplateUsageTransaction.php
 * Satu transaksi penggunaan template marketplace (histori royalti & laporan).
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'template_id', 'owner_user_id', 'buyer_user_id',
    'price_credit', 'owner_credit', 'platform_credit',
])]
class TemplateUsageTransaction extends Model
{
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }
}
