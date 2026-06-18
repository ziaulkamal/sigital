<?php

/**
 * app/Services/ParticipantImportService.php
 * Impor peserta CSV dua-langkah: pratinjau (validasi+deteksi duplikat) lalu commit (FR-07/08).
 */

namespace App\Services;

use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class ParticipantImportService
{
    /** Kolom yang dikenali (header CSV, case-insensitive). */
    private const COLUMNS = ['nama', 'email', 'nik'];

    public function __construct(private readonly ParticipantService $participants) {}

    /**
     * Baca & validasi CSV tanpa menyimpan. Tiap baris diberi status: ok | duplikat | error.
     *
     * @return array{rows: array<int,array<string,mixed>>, summary: array<string,int>}
     */
    public function preview(Event $event, UploadedFile $file): array
    {
        [$header, $records] = $this->readCsv($file);

        if (! in_array('nama', $header, true)) {
            throw new \RuntimeException("Kolom wajib 'nama' tidak ditemukan pada header CSV.");
        }

        $rows = [];
        $seen = [];   // deteksi duplikat di dalam berkas
        $summary = ['ok' => 0, 'duplikat' => 0, 'error' => 0];

        foreach ($records as $i => $record) {
            $data = $this->mapRow($header, $record);
            $row = ['baris' => $i + 2, ...$data]; // +2: header + index 0

            $errors = $this->validateRow($data);
            $key = ! empty($data['email']) ? mb_strtolower($data['email']) : mb_strtolower($data['nama']);

            if ($errors) {
                $row['status'] = 'error';
                $row['pesan'] = implode(' ', $errors);
            } elseif (isset($seen[$key])) {
                $row['status'] = 'duplikat';
                $row['pesan'] = 'Duplikat di dalam berkas.';
            } elseif ($this->participants->isDuplicate($event, $data)) {
                $row['status'] = 'duplikat';
                $row['pesan'] = 'Sudah terdaftar di acara ini.';
            } else {
                $row['status'] = 'ok';
                $row['pesan'] = null;
            }

            $seen[$key] = true;
            $summary[$row['status']]++;
            $rows[] = $row;
        }

        return ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * Simpan baris yang dipilih (hanya yang valid). Mengembalikan jumlah tersimpan.
     *
     * @param  array<int,array<string,mixed>>  $rows
     */
    public function commit(Event $event, array $rows): int
    {
        $saved = 0;
        foreach ($rows as $row) {
            $data = ['nama' => $row['nama'] ?? null, 'email' => $row['email'] ?? null, 'nik' => $row['nik'] ?? null];
            if (empty($data['nama']) || $this->validateRow($data)) {
                continue; // lewati baris tak valid demi keamanan
            }
            $reg = $this->participants->addManual($event, $data, 'impor');
            if ($reg->wasRecentlyCreated) {
                $saved++;
            }
        }

        return $saved;
    }

    /** @return array{0: array<int,string>, 1: array<int,array<int,string>>} */
    private function readCsv(UploadedFile $file): array
    {
        $rows = [];
        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        $header = array_map(fn ($h) => mb_strtolower(trim((string) $h)), array_shift($rows) ?? []);

        return [$header, $rows];
    }

    /** @return array<string,?string> */
    private function mapRow(array $header, array $record): array
    {
        $data = array_fill_keys(self::COLUMNS, null);
        foreach ($header as $idx => $name) {
            if (in_array($name, self::COLUMNS, true)) {
                $data[$name] = isset($record[$idx]) ? trim((string) $record[$idx]) : null;
            }
        }

        return $data;
    }

    /** @return array<int,string> daftar pesan kesalahan (kosong = valid) */
    private function validateRow(array $data): array
    {
        $v = Validator::make($data, [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'nik' => ['nullable', 'string', 'max:32'],
        ]);

        return $v->errors()->all();
    }
}
