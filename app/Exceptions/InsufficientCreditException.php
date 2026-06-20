<?php

/**
 * app/Exceptions/InsufficientCreditException.php
 * Dilempar CreditService saat saldo user tak cukup untuk konsumsi.
 *  - render(): redirect back dengan flash 'error' (konsisten dgn flash pattern UI).
 *  - report(): catat upaya gagal ke audit DI LUAR transaksi yang sudah rollback,
 *    sehingga setiap percobaan pemakaian saat saldo kurang tetap terdeteksi
 *    (anti-exploit: jejak utuh meski pembuatan acara/template dibatalkan).
 */

namespace App\Exceptions;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use RuntimeException;

class InsufficientCreditException extends RuntimeException
{
    public function __construct(
        public readonly int $required,
        public readonly int $available,
        public readonly string $action = '',
        public readonly ?int $userId = null,
        public readonly ?Model $reference = null,
    ) {
        $aksi = $action !== '' ? " untuk {$action}" : '';
        parent::__construct("Credit tidak cukup. Diperlukan {$required} credit{$aksi}, saldo Anda {$available}.");
    }

    /** Catat upaya yang gagal (dipanggil handler setelah transaksi unwind). */
    public function report(): void
    {
        if ($this->userId === null) {
            return;
        }

        app(AuditLogger::class)->log('credit.consume_denied', $this->reference, [
            'required' => $this->required,
            'available' => $this->available,
            'action' => $this->action,
        ], $this->userId);
    }

    public function render(Request $request)
    {
        return back()->with('error', $this->getMessage());
    }
}
