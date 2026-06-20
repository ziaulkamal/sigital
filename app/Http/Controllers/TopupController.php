<?php

/**
 * app/Http/Controllers/TopupController.php
 * Topup credit dalam platform — alur MANUAL + konfirmasi SuperAdmin.
 *  - User: index (saldo + riwayat), store (ajukan + bukti).
 *  - SuperAdmin: requests (daftar pending lintas-user), approve/reject, proof (lihat bukti).
 *
 * Penambahan saldo SELALU lewat CreditService agar ledger konsisten.
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreTopupRequest;
use App\Models\TopupRequest;
use App\Models\User;
use App\Notifications\TopupRequested;
use App\Notifications\TopupReviewed;
use App\Services\AuditLogger;
use App\Services\CreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TopupController extends Controller
{
    public function __construct(
        private readonly CreditService $credit,
        private readonly AuditLogger $audit,
    ) {}

    /** Halaman saldo + riwayat transaksi + daftar permintaan topup user. */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $transactions = $user->creditTransactions()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => $t->amount,
                'balance_after' => $t->balance_after,
                'description' => $t->description,
                'created_at' => $t->created_at?->toDateTimeString(),
            ]);

        $requests = $user->topupRequests()
            ->latest()
            ->get()
            ->map(fn (TopupRequest $r) => [
                'id' => $r->id,
                'amount_credit' => $r->amount_credit,
                'amount_rupiah' => $r->amount_rupiah,
                'status' => $r->status,
                'reject_reason' => $r->reject_reason,
                'created_at' => $r->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Credits/Index', [
            'balance' => $this->credit->balance($user),
            'rupiahPerCredit' => (int) config('sigital.credit.rupiah_per_credit'),
            'transactions' => $transactions,
            'requests' => $requests,
        ]);
    }

    /** User mengajukan topup (status pending) + simpan bukti di disk privat. */
    public function store(StoreTopupRequest $request): RedirectResponse
    {
        $user = $request->user();
        $rupiahPerCredit = (int) config('sigital.credit.rupiah_per_credit');
        $amountCredit = (int) $request->integer('amount_credit');

        $proofPath = null;
        if ($request->hasFile('proof')) {
            // Disk privat 'local' (bukan public) — bukti transfer hanya untuk SuperAdmin.
            $proofPath = $request->file('proof')->store('topup-proofs', 'local');
        }

        $topup = TopupRequest::create([
            'user_id' => $user->id,
            'amount_credit' => $amountCredit,
            'amount_rupiah' => $amountCredit * $rupiahPerCredit,
            'status' => TopupRequest::STATUS_PENDING,
            'proof_path' => $proofPath,
            'note' => $request->input('note'),
        ]);

        $this->audit->log('credit.topup_requested', $topup, ['amount_credit' => $amountCredit], $user->id);

        // Beri tahu seluruh SuperAdmin (user global tanpa organisasi).
        Notification::send(
            User::whereNull('organization_id')->get(),
            new TopupRequested($topup, $user->name),
        );

        return back()->with('success', "Permintaan topup {$amountCredit} credit dikirim. Menunggu verifikasi.");
    }

    /** Daftar seluruh permintaan topup (SuperAdmin) — pending di atas. */
    public function requests(Request $request): Response
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $requests = TopupRequest::with('user:id,name,email')
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->get()
            ->map(fn (TopupRequest $r) => [
                'id' => $r->id,
                'user' => $r->user ? ['id' => $r->user->id, 'name' => $r->user->name, 'email' => $r->user->email] : null,
                'amount_credit' => $r->amount_credit,
                'amount_rupiah' => $r->amount_rupiah,
                'status' => $r->status,
                'note' => $r->note,
                'has_proof' => $r->proof_path !== null,
                'reject_reason' => $r->reject_reason,
                'created_at' => $r->created_at?->toDateTimeString(),
                'reviewed_at' => $r->reviewed_at?->toDateTimeString(),
            ]);

        return Inertia::render('Credits/Requests', [
            'requests' => $requests,
        ]);
    }

    /** Lihat bukti transfer (SuperAdmin) — stream dari disk privat. */
    public function proof(Request $request, TopupRequest $topup): StreamedResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        abort_if($topup->proof_path === null || ! Storage::disk('local')->exists($topup->proof_path), 404);

        return Storage::disk('local')->response($topup->proof_path);
    }

    /** Setujui topup → tambah saldo via CreditService + tandai approved. */
    public function approve(Request $request, TopupRequest $topup): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->isSuperAdmin(), 403);
        abort_unless($topup->isPending(), 422, 'Permintaan ini sudah diproses.');

        $user = $topup->user;
        $this->credit->grant(
            $user,
            $topup->amount_credit,
            "Topup #{$topup->id}",
            $actor->id,
            \App\Models\CreditTransaction::TYPE_TOPUP,
            $topup,
        );

        $topup->forceFill([
            'status' => TopupRequest::STATUS_APPROVED,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ])->save();

        $this->audit->log('credit.topup_approved', $topup, ['amount_credit' => $topup->amount_credit], $actor->id);
        $user->notify(new TopupReviewed($topup));

        return back()->with('success', "Topup {$topup->amount_credit} credit untuk {$user->name} disetujui.");
    }

    /** Tolak topup → simpan alasan + notifikasi user (saldo tidak berubah). */
    public function reject(Request $request, TopupRequest $topup): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->isSuperAdmin(), 403);
        abort_unless($topup->isPending(), 422, 'Permintaan ini sudah diproses.');

        $data = $request->validate([
            'reject_reason' => ['required', 'string', 'min:3', 'max:255'],
        ], [], ['reject_reason' => 'alasan']);

        $topup->forceFill([
            'status' => TopupRequest::STATUS_REJECTED,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'reject_reason' => $data['reject_reason'],
        ])->save();

        $this->audit->log('credit.topup_rejected', $topup, ['reason' => $data['reject_reason']], $actor->id);
        $topup->user->notify(new TopupReviewed($topup));

        return back()->with('success', "Topup {$topup->user->name} ditolak.");
    }
}
