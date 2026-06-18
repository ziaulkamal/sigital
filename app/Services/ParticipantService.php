<?php

/**
 * app/Services/ParticipantService.php
 * Tambah peserta manual + cari/buat Person durabel; deteksi duplikat per acara (FR-06/08/09).
 */

namespace App\Services;

use App\Models\Event;
use App\Models\Person;
use App\Models\Registration;

class ParticipantService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Tambah satu peserta. Person dikenali lintas acara via email (jika ada), jika tidak via nama.
     *
     * @param  array{nama:string,email?:?string,nik?:?string}  $data
     */
    public function addManual(Event $event, array $data, string $sumber = 'manual'): Registration
    {
        $person = $this->resolvePerson($data);

        $registration = Registration::firstOrCreate(
            ['person_id' => $person->id, 'event_id' => $event->id],
            ['sumber' => $sumber, 'status_kehadiran' => 'hadir'],
        );

        if ($registration->wasRecentlyCreated) {
            $this->audit->log('participant.added', $registration, ['event_id' => $event->id, 'nama' => $person->nama]);
        }

        return $registration;
    }

    /** Apakah orang ini sudah terdaftar di acara (peringatan duplikat). */
    public function isDuplicate(Event $event, array $data): bool
    {
        $person = $this->findPerson($data);

        return $person && Registration::where('event_id', $event->id)
            ->where('person_id', $person->id)->exists();
    }

    /** @param array{nama:string,email?:?string,nik?:?string} $data */
    private function resolvePerson(array $data): Person
    {
        $person = $this->findPerson($data);
        if ($person) {
            return $person;
        }

        return Person::create([
            'nama' => $data['nama'],
            'email' => $data['email'] ?? null,
            'nik' => $data['nik'] ?? null,
        ]);
    }

    private function findPerson(array $data): ?Person
    {
        if (! empty($data['email'])) {
            return Person::where('email', $data['email'])->first();
        }

        return Person::whereRaw('LOWER(nama) = ?', [mb_strtolower($data['nama'])])->first();
    }
}
