<?php

/**
 * app/Http/Controllers/EventMemberController.php
 * Kolaborasi acara (P7/K10): minta gabung via kode undangan, owner/SuperAdmin approve/reject.
 */

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventMember;
use App\Services\EventMembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EventMemberController extends Controller
{
    public function __construct(private readonly EventMembershipService $service) {}

    /** Kirim permintaan gabung memakai kode undangan dari owner (Q8). */
    public function join(Request $request): RedirectResponse
    {
        $data = $request->validate(['join_code' => ['required', 'string', 'max:20']]);

        try {
            $member = $this->service->requestJoinByCode($data['join_code'], $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('events.show', $member->event_id)
            ->with('success', 'Permintaan gabung terkirim, menunggu persetujuan pemilik acara.');
    }

    public function approve(Event $event, EventMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $event);
        abort_unless($member->event_id === $event->id, 404);

        $this->service->approve($member, request()->user());

        return back()->with('success', 'Anggota disetujui.');
    }

    public function reject(Event $event, EventMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $event);
        abort_unless($member->event_id === $event->id, 404);

        $this->service->reject($member, request()->user());

        return back()->with('success', 'Permintaan ditolak.');
    }
}
