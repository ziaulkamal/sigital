<?php

/**
 * app/Http/Controllers/EventController.php
 * CRUD acara + penetapan penanda tangan/template + ringkasan penerbitan.
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Models\Signatory;
use App\Models\Template;
use App\Services\EventService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function __construct(private readonly EventService $service) {}

    public function index(): Response
    {
        return Inertia::render('Events/Index', [
            'events' => Event::withCount(['registrations', 'signatories'])
                ->orderByDesc('id')->get()->map(fn ($e) => [
                    'id' => $e->id,
                    'nama' => $e->nama,
                    'kode' => $e->kode,
                    'jadwal_mulai' => $e->jadwal_mulai?->toDateTimeString(),
                    'lokasi' => $e->lokasi,
                    'status' => $e->status,
                    'peserta' => $e->registrations_count,
                    'penanda_tangan' => $e->signatories_count,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Events/Form', $this->formOptions());
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = $this->service->create($request->validated());

        return redirect()->route('events.show', $event)->with('success', 'Acara dibuat.');
    }

    public function show(Event $event): Response
    {
        $event->load(['template', 'signatories', 'registrations.person', 'registrations.certificate']);

        return Inertia::render('Events/Show', [
            'event' => [
                'id' => $event->id,
                'nama' => $event->nama,
                'kode' => $event->kode,
                'jadwal_mulai' => $event->jadwal_mulai?->toDateTimeString(),
                'jadwal_selesai' => $event->jadwal_selesai?->toDateTimeString(),
                'lokasi' => $event->lokasi,
                'status' => $event->status,
                'can_issue' => $event->canIssue(),
                'template' => $event->template?->only('id', 'nama'),
                'signatories' => $event->signatories->map->only('id', 'nama', 'jabatan'),
            ],
            'participants' => $event->registrations->map(fn ($r) => [
                'registration_id' => $r->id,
                'nama' => $r->person->nama,
                'email' => $r->person->email,
                'sumber' => $r->sumber,
                'status_kehadiran' => $r->status_kehadiran,
                'certificate' => $r->certificate ? [
                    'id' => $r->certificate->id,
                    'nomor' => $r->certificate->nomor_unik,
                    'status' => $r->certificate->status,
                ] : null,
            ]),
        ]);
    }

    public function edit(Event $event): Response
    {
        $event->load('signatories');

        return Inertia::render('Events/Form', [
            ...$this->formOptions(),
            'event' => [
                'id' => $event->id,
                'nama' => $event->nama,
                'kode' => $event->kode,
                'jadwal_mulai' => $event->jadwal_mulai?->format('Y-m-d\TH:i'),
                'jadwal_selesai' => $event->jadwal_selesai?->format('Y-m-d\TH:i'),
                'lokasi' => $event->lokasi,
                'status' => $event->status,
                'template_id' => $event->template_id,
                'signatory_ids' => $event->signatories->pluck('id'),
            ],
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $this->service->update($event, $request->validated());

        return redirect()->route('events.show', $event)->with('success', 'Acara diperbarui.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('events.index')->with('success', 'Acara dihapus.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'templateOptions' => Template::where('is_active', true)->get()->map->only('id', 'nama'),
            'signatoryOptions' => Signatory::where('is_active', true)->get()->map->only('id', 'nama', 'jabatan'),
        ];
    }
}
