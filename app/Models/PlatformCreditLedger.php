<?php

/**
 * app/Models/PlatformCreditLedger.php
 * Ledger pendapatan platform (terpisah dari saldo user).
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'source_type', 'source_id', 'credit_amount', 'description',
])]
class PlatformCreditLedger extends Model
{
    protected $table = 'platform_credit_ledger';

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
