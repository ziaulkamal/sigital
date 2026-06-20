<?php

/**
 * app/Models/LoginLog.php
 * Jejak login (IP + user-agent). Kolom country diisi belakangan via GeoIP.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'ip', 'user_agent', 'country', 'country_code', 'logged_at',
])]
class LoginLog extends Model
{
    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
