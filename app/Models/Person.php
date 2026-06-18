<?php

/**
 * app/Models/Person.php
 * Identitas durabel lintas acara → basis arsip jangka panjang & portal peserta (v2).
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['nama', 'email', 'nik'])]
#[Hidden(['nik'])] // minimisasi data: NIK tak ikut terserialisasi by default
class Person extends Model
{
    protected $table = 'people';

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function certificates(): HasManyThrough
    {
        return $this->hasManyThrough(Certificate::class, Registration::class);
    }
}
