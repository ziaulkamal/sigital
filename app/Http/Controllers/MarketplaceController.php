<?php

/**
 * app/Http/Controllers/MarketplaceController.php
 * Marketplace template (Bagian 6):
 *  - browse: katalog template marketplace + tombol pakai.
 *  - purchase: pakai template orang lain (potong credit, royalti pemilik, share platform).
 *  - publish/unpublish: pemilik mempublikasikan template (wajib creator + rekening verified).
 *  - applyCreator/storeBank: pendaftaran Creator + rekening (verifikasi SuperAdmin).
 *  - creator: dashboard Creator (status pendaftaran/rekening + kontribusi + pencairan).
 *  - requestWithdrawal: ajukan pencairan royalti.
 *  - admin*: dashboard, verifikasi pendaftaran/rekening & pencairan oleh SuperAdmin.
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Requests\StoreCreatorApplicationRequest;
use App\Models\MarketplaceWithdrawal;
use App\Models\PlatformCreditLedger;
use App\Models\Template;
use App\Models\TemplateUsageTransaction;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CreatorApplicationService;
use App\Services\MarketplaceWithdrawalService;
use App\Services\TemplateMarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketplaceController extends Controller
{
    public function __construct(
        private readonly TemplateMarketplaceService $marketplace,
        private readonly MarketplaceWithdrawalService $withdrawals,
        private readonly CreatorApplicationService $applications,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Halaman pendaftaran Creator yang dapat diakses publik (tamu maupun login).
     * Tamu → diarahkan daftar/masuk dulu; user login → tampilkan form aplikasi.
     */
    public function register(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Marketplace/Register', [
            'price' => (int) config('sigital.credit.marketplace_price'),
            'ownerShare' => (int) config('sigital.credit.marketplace_owner_share'),
            'rupiahPerCredit' => (int) config('sigital.credit.rupiah_per_credit'),
            // Konteks akun untuk menentukan tampilan (tamu / belum lengkap / siap apply).
            'account' => $user ? [
                'authenticated' => true,
                'approved' => $user->isApproved(),
                'needs_profile' => $user->needsProfileCompletion(),
                'is_creator' => $user->isMarketplaceCreator(),
                'application_status' => $user->creator_status,   // null|pending|approved|rejected
                'reject_reason' => $user->creator_reject_reason,
                'full_name' => $user->creator_full_name ?? $user->name,
            ] : ['authenticated' => false],
        ]);
    }

    /** Katalog template marketplace yang dipublikasikan. */
    public function browse(Request $request): Response
    {
        $user = $request->user();

        // withoutGlobalScope agar template lintas-organisasi tampil di marketplace.
        $templates = Template::withoutOrganizationScope()
            ->where('is_marketplace', true)
            ->whereNotNull('published_at')
            ->where('is_active', true)
            ->with('uploadedBy:id,name')
            ->latest('published_at')
            ->get()
            ->map(fn (Template $t) => [
                'id' => $t->id,
                'nama' => $t->nama,
                'deskripsi' => $t->deskripsi,
                'price' => $t->marketplace_price,
                'owner' => $t->uploadedBy?->name,
                'is_mine' => $t->uploaded_by === $user->id,
                'usage_count' => $t->usageTransactions()->count(),
            ]);

        return Inertia::render('Marketplace/Browse', [
            'templates' => $templates,
            'price' => (int) config('sigital.credit.marketplace_price'),
        ]);
    }

    /** Pakai (beli akses) template marketplace milik orang lain. */
    public function purchase(Request $request, Template $template): RedirectResponse
    {
        try {
            $usage = $this->marketplace->purchaseTemplate($template, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Template \"{$template->nama}\" berhasil digunakan ({$usage->price_credit} credit).");
    }

    /** Publikasikan template milik sendiri (wajib creator aktif + rekening terverifikasi). */
    public function publish(Request $request, Template $template): RedirectResponse
    {
        $user = $request->user();
        abort_unless($template->uploaded_by === $user->id || $user->isSuperAdmin(), 403);
        abort_unless($user->canUseCreatorFeatures() || $user->isSuperAdmin(), 422, 'Lengkapi & verifikasi rekening pencairan dahulu.');

        $this->marketplace->publishTemplate($template, $user);

        return back()->with('success', "Template \"{$template->nama}\" dipublikasikan ke marketplace.");
    }

    public function unpublish(Request $request, Template $template): RedirectResponse
    {
        $user = $request->user();
        abort_unless($template->uploaded_by === $user->id || $user->isSuperAdmin(), 403);

        $this->marketplace->unpublishTemplate($template, $user);

        return back()->with('success', "Template \"{$template->nama}\" ditarik dari marketplace.");
    }

    /** User mendaftar sebagai Marketplace Creator (KTP + identitas + S&K) → menunggu verifikasi. */
    public function applyCreator(StoreCreatorApplicationRequest $request): RedirectResponse
    {
        try {
            $this->applications->apply(
                $request->user(),
                $request->safe()->only(['full_name', 'address']),
                $request->file('ktp'),
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Arahkan ke dashboard Creator (status menunggu verifikasi) — berlaku baik
        // dari halaman register publik maupun form di dashboard.
        return redirect()->route('marketplace.creator')
            ->with('success', 'Pendaftaran Creator terkirim. Menunggu verifikasi admin.');
    }

    /** Creator menyimpan/mengubah rekening pencairan → menunggu verifikasi. */
    public function storeBank(StoreBankAccountRequest $request): RedirectResponse
    {
        try {
            $this->applications->saveBank(
                $request->user(),
                $request->safe()->only(['bank_name', 'bank_account_no', 'bank_account_holder']),
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Rekening tersimpan. Menunggu verifikasi admin.');
    }

    /** Dashboard Creator: ringkasan kontribusi, template, pencairan (Bagian 6.10). */
    public function creator(Request $request): Response
    {
        $user = $request->user();

        $usages = TemplateUsageTransaction::where('owner_user_id', $user->id)->get();
        $withdrawals = $user->marketplaceWithdrawals()->latest()->get();

        $totalEarned = (int) $usages->sum('owner_credit');
        $totalWithdrawn = (int) $withdrawals->whereIn('status', ['approved', 'paid'])->sum('credit_paid');

        // Template marketplace milik user + jumlah penggunaan & royalti.
        $myTemplates = Template::withoutOrganizationScope()
            ->where('uploaded_by', $user->id)
            ->where('is_marketplace', true)
            ->withCount('usageTransactions')
            ->get()
            ->map(fn (Template $t) => [
                'id' => $t->id,
                'nama' => $t->nama,
                'usage_count' => $t->usage_transactions_count,
                'royalty' => (int) TemplateUsageTransaction::where('template_id', $t->id)->sum('owner_credit'),
                'published' => $t->isPublished(),
            ]);

        return Inertia::render('Marketplace/Creator', [
            'isCreator' => $user->isMarketplaceCreator(),
            'canUseFeatures' => $user->canUseCreatorFeatures(),
            // Status pendaftaran & rekening untuk alur bertahap di UI.
            'application' => [
                'status' => $user->creator_status,            // null|pending|approved|rejected
                'reject_reason' => $user->creator_reject_reason,
                'full_name' => $user->creator_full_name,
                'address' => $user->creator_address,
            ],
            'bank' => [
                'status' => $user->bank_status,               // null|pending|verified|rejected
                'reject_reason' => $user->bank_reject_reason,
                'bank_name' => $user->bank_name,
                'bank_account_no' => $user->bank_account_no,
                'bank_account_holder' => $user->bank_account_holder,
            ],
            'summary' => [
                'total_templates' => $myTemplates->count(),
                'total_used' => (int) $usages->count(),
                'total_earned' => $totalEarned,
                'total_withdrawn' => $totalWithdrawn,
                'available' => (int) $user->credit_balance,
            ],
            'templates' => $myTemplates,
            'withdrawals' => $withdrawals->map(fn (MarketplaceWithdrawal $w) => $this->mapWithdrawal($w)),
            'rupiahPerCredit' => (int) config('sigital.credit.rupiah_per_credit'),
            'withdrawFee' => (int) config('sigital.credit.withdraw_fee'),
            'withdrawMin' => (int) config('sigital.credit.withdraw_min'),
            'nextPayout' => $withdrawals->firstWhere('status', MarketplaceWithdrawal::STATUS_SCHEDULED)
                ? $this->mapWithdrawal($withdrawals->firstWhere('status', MarketplaceWithdrawal::STATUS_SCHEDULED))
                : null,
        ]);
    }

    /** Creator mengajukan pencairan royalti. */
    public function requestWithdrawal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'credit_requested' => ['required', 'integer', 'min:1'],
        ], [], ['credit_requested' => 'jumlah credit']);

        try {
            $w = $this->withdrawals->requestWithdrawal($request->user(), (int) $data['credit_requested']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Pencairan {$w->credit_requested} credit diajukan (bersih {$w->credit_paid} credit = Rp".number_format($w->rupiah_paid, 0, ',', '.').').');
    }

    // ── SuperAdmin ───────────────────────────────────────────────────────

    /** Dashboard marketplace SuperAdmin: total transaksi, pendapatan platform, royalti, terlaris. */
    public function adminDashboard(Request $request): Response
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $usages = TemplateUsageTransaction::query();
        $totalTraded = (int) (clone $usages)->sum('price_credit');
        $totalPlatform = (int) PlatformCreditLedger::sum('credit_amount');
        $totalRoyalty = (int) (clone $usages)->sum('owner_credit');

        $topTemplates = TemplateUsageTransaction::query()
            ->selectRaw('template_id, COUNT(*) as uses, SUM(owner_credit) as royalty')
            ->groupBy('template_id')
            ->orderByDesc('uses')
            ->limit(10)
            ->with('template:id,nama')
            ->get()
            ->map(fn ($r) => [
                'template' => $r->template?->nama ?? '—',
                'uses' => (int) $r->uses,
                'royalty' => (int) $r->royalty,
            ]);

        $withdrawals = MarketplaceWithdrawal::with('user:id,name,email')
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'scheduled' THEN 1 WHEN 'approved' THEN 2 ELSE 3 END")
            ->latest()
            ->get()
            ->map(fn (MarketplaceWithdrawal $w) => $this->mapWithdrawal($w, withUser: true));

        // Pendaftaran Creator menunggu verifikasi.
        $creatorApplications = User::where('creator_status', 'pending')
            ->orderBy('creator_applied_at')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'full_name' => $u->creator_full_name,
                'address' => $u->creator_address,
                'applied_at' => $u->creator_applied_at?->toDateTimeString(),
            ]);

        // Rekening menunggu verifikasi.
        $bankReviews = User::where('bank_status', 'pending')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'bank_name' => $u->bank_name,
                'bank_account_no' => $u->bank_account_no,
                'bank_account_holder' => $u->bank_account_holder,
            ]);

        $rupiahPerCredit = (int) config('sigital.credit.rupiah_per_credit');

        return Inertia::render('Marketplace/AdminDashboard', [
            'stats' => [
                'total_traded' => $totalTraded,
                'total_platform' => $totalPlatform,
                'total_royalty' => $totalRoyalty,
                'estimasi_rupiah' => $totalPlatform * $rupiahPerCredit,
            ],
            'topTemplates' => $topTemplates,
            'withdrawals' => $withdrawals,
            'creatorApplications' => $creatorApplications,
            'bankReviews' => $bankReviews,
            'rupiahPerCredit' => $rupiahPerCredit,
        ]);
    }

    /** Lihat KTP pemohon (SuperAdmin) — stream dari disk privat. */
    public function ktp(Request $request, User $user): StreamedResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        abort_if($user->creator_ktp_path === null || ! Storage::disk('local')->exists($user->creator_ktp_path), 404);

        return Storage::disk('local')->response($user->creator_ktp_path);
    }

    public function approveCreator(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        try {
            $this->applications->approve($user, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Pendaftaran Creator {$user->name} disetujui.");
    }

    public function rejectCreator(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:255']], [], ['reason' => 'alasan']);

        try {
            $this->applications->reject($user, $data['reason'], $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Pendaftaran Creator {$user->name} ditolak.");
    }

    public function verifyBank(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        try {
            $this->applications->verifyBank($user, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Rekening {$user->name} terverifikasi.");
    }

    public function rejectBank(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:255']], [], ['reason' => 'alasan']);

        try {
            $this->applications->rejectBank($user, $data['reason'], $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Rekening {$user->name} ditolak.");
    }

    public function scheduleWithdrawal(Request $request, MarketplaceWithdrawal $withdrawal): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['scheduled_payout_date' => ['required', 'date']]);

        try {
            $this->withdrawals->scheduleWithdrawal($withdrawal, new \DateTimeImmutable($data['scheduled_payout_date']), $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Jadwal pencairan ditetapkan.');
    }

    public function approveWithdrawal(Request $request, MarketplaceWithdrawal $withdrawal): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        try {
            $this->withdrawals->approveWithdrawal($withdrawal, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pencairan disetujui.');
    }

    public function rejectWithdrawal(Request $request, MarketplaceWithdrawal $withdrawal): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:255']], [], ['reason' => 'alasan']);

        try {
            $this->withdrawals->rejectWithdrawal($withdrawal, $data['reason'], $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pencairan ditolak & credit dikembalikan.');
    }

    public function markPaid(Request $request, MarketplaceWithdrawal $withdrawal): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        try {
            $this->withdrawals->markAsPaid($withdrawal, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pencairan ditandai telah dibayar.');
    }

    /** @return array<string,mixed> */
    private function mapWithdrawal(MarketplaceWithdrawal $w, bool $withUser = false): array
    {
        $base = [
            'id' => $w->id,
            'credit_requested' => $w->credit_requested,
            'admin_fee_credit' => $w->admin_fee_credit,
            'credit_paid' => $w->credit_paid,
            'rupiah_paid' => $w->rupiah_paid,
            'status' => $w->status,
            'scheduled_payout_date' => $w->scheduled_payout_date?->toDateString(),
            'notes' => $w->notes,
            'created_at' => $w->created_at?->toDateTimeString(),
        ];

        if ($withUser) {
            $base['user'] = $w->user ? ['id' => $w->user->id, 'name' => $w->user->name, 'email' => $w->user->email] : null;
        }

        return $base;
    }
}
