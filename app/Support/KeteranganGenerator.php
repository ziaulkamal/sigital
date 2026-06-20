<?php

/**
 * app/Support/KeteranganGenerator.php
 *
 * Menghasilkan keterangan kegiatan otomatis untuk sertifikat bila admin tidak
 * mengisi `events.keterangan`. Format (permintaan user):
 *
 *   "telah menyelesaikan kegiatan {nama acara} pada {lokasi} pada tanggal
 *    {tgl mulai – tgl selesai; bila 1 hari → 1 tanggal saja} dengan jumlah waktu
 *    {durasi jam dari waktu mulai–selesai} Jam yang diadakan oleh {organisasi/instansi}"
 *
 * Bagian opsional (lokasi/durasi) dirangkai luwes agar kalimat tetap rapi bila
 * datanya tidak lengkap.
 */

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Carbon;

class KeteranganGenerator
{
    /** Keterangan final untuk sebuah acara: pakai input admin bila ada, else otomatis. */
    public function for(Event $event, ?string $organisasi = null): string
    {
        $manual = trim((string) $event->keterangan);
        if ($manual !== '') {
            return $manual;
        }

        return $this->auto($event, $organisasi);
    }

    /**
     * Nilai placeholder yang dapat disisipkan admin di template keterangan:
     * {acara} {lokasi} {tanggal} {durasi} {instansi}.
     *
     * @return array<string,string>
     */
    public function tokens(Event $event, ?string $organisasi = null): array
    {
        $mulai = $event->jadwal_mulai ? Carbon::parse($event->jadwal_mulai) : null;
        $selesai = $event->jadwal_selesai ? Carbon::parse($event->jadwal_selesai) : null;
        $jam = $this->durasiJam($mulai, $selesai);

        return [
            'acara' => (string) $event->nama,
            'lokasi' => (string) ($event->lokasi ?? ''),
            'tanggal' => $mulai ? $this->formatTanggal($mulai, $selesai) : '',
            'durasi' => $jam !== null ? $jam.' jam' : '',
            'instansi' => trim((string) ($organisasi ?? config('sigital.instansi_nama'))),
        ];
    }

    /** Ganti placeholder {token} pada template dengan nilainya. **bold** dibiarkan utuh. */
    public function applyTemplate(string $template, Event $event, ?string $organisasi = null): string
    {
        $tokens = $this->tokens($event, $organisasi);
        $replace = [];
        foreach ($tokens as $k => $v) {
            $replace['{'.$k.'}'] = $v;
        }

        return strtr($template, $replace);
    }

    public function auto(Event $event, ?string $organisasi = null): string
    {
        $mulai = $event->jadwal_mulai ? Carbon::parse($event->jadwal_mulai) : null;
        $selesai = $event->jadwal_selesai ? Carbon::parse($event->jadwal_selesai) : null;

        $parts = ['telah menyelesaikan kegiatan '.$event->nama];

        if ($event->lokasi) {
            $parts[] = 'di '.$event->lokasi;
        }

        if ($mulai) {
            $parts[] = 'pada tanggal '.$this->formatTanggal($mulai, $selesai);
        }

        $jam = $this->durasiJam($mulai, $selesai);
        if ($jam !== null) {
            $parts[] = 'dengan jumlah waktu '.$jam.' jam';
        }

        $org = trim((string) ($organisasi ?? config('sigital.instansi_nama')));
        if ($org !== '') {
            $parts[] = 'yang diadakan oleh '.$org;
        }

        return implode(' ', $parts).'.';
    }

    /** Rentang tanggal; bila hari sama (atau selesai null) → satu tanggal saja. */
    private function formatTanggal(Carbon $mulai, ?Carbon $selesai): string
    {
        if ($selesai === null || $mulai->isSameDay($selesai)) {
            return $mulai->translatedFormat('d F Y');
        }

        // Bila bulan & tahun sama, ringkas: "1 – 3 Februari 2026".
        if ($mulai->isSameMonth($selesai) && $mulai->year === $selesai->year) {
            return $mulai->translatedFormat('d').' – '.$selesai->translatedFormat('d F Y');
        }

        return $mulai->translatedFormat('d F Y').' – '.$selesai->translatedFormat('d F Y');
    }

    /** Total jam (dibulatkan) antara mulai & selesai; null bila tak bisa dihitung. */
    private function durasiJam(?Carbon $mulai, ?Carbon $selesai): ?int
    {
        if ($mulai === null || $selesai === null || $selesai->lessThanOrEqualTo($mulai)) {
            return null;
        }

        $jam = (int) round($mulai->diffInMinutes($selesai) / 60);

        return $jam > 0 ? $jam : null;
    }
}
