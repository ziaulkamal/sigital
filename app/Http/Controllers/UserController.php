<?php

/**
 * app/Http/Controllers/UserController.php
 * Daftar pengguna terdaftar beserta instansi & status. Visibilitas:
 *  - SuperAdmin: seluruh pengguna lintas-instansi.
 *  - Admin (org): hanya pengguna pada instansinya sendiri.
 * Otorisasi via permission `manage-users` (SuperAdmin lolos Gate::before; Admin punya izin ini).
 *
 * Aksi monetisasi (ubah peran, set Enterprise, sesuaikan credit) HANYA SuperAdmin
 * (ditegakkan via abort_unless di tiap method).
 */

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BanService;
use App\Services\CreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    public function __construct(
        private readonly BanService $banService,
        private readonly CreditService $credit,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): Response
    {
        $actor = $request->user();

        $query = User::query()->with(['organization', 'roles', 'latestLogin']);

        // Admin org hanya melihat pengguna pada instansinya; SuperAdmin melihat semua.
        if (! $actor->isSuperAdmin()) {
            $query->where('organization_id', $actor->organization_id);
        }

        $users = $query->orderByDesc('created_at')->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'nik' => $u->nik,
            'phone' => $u->phone,
            'status' => $u->status,
            'requested_role' => $u->requested_role,
            'roles' => $u->getRoleNames()->all(),
            'is_super_admin' => $u->isSuperAdmin(),
            'is_banned' => $u->isBanned(),
            'banned_reason' => $u->banned_reason,
            'banned_at' => $u->banned_at?->toDateTimeString(),
            'created_at' => $u->created_at?->toDateTimeString(),
            // Monetisasi.
            'credit_balance' => (int) $u->credit_balance,
            'plan' => $u->plan,
            'is_enterprise' => $u->isEnterprise(),
            'is_enterprise_active' => $u->isEnterpriseActive(),
            'enterprise_expires_at' => $u->enterprise_expires_at?->toDateTimeString(),
            'two_factor_enabled' => $u->hasTwoFactorEnabled(),
            'marketplace_enabled' => $u->isMarketplaceCreator(),
            'last_login' => $u->latestLogin ? [
                'ip' => $u->latestLogin->ip,
                'at' => $u->latestLogin->logged_at?->toDateTimeString(),
            ] : null,
            'organization' => $u->organization ? [
                'id' => $u->organization->id,
                'nama' => $u->organization->nama,
                'kode' => $u->organization->kode,
                'type' => $u->organization->type,
            ] : null,
        ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'isSuperAdmin' => $actor->isSuperAdmin(),
            'currentUserId' => $actor->id,
        ]);
    }

    /** Blokir akun (SuperAdmin). Wajib menyebut alasan → ditampilkan ke user saat login. */
    public function ban(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->isSuperAdmin(), 403);
        abort_if($user->isSuperAdmin() || $user->id === $actor->id, 403, 'Akun ini tidak dapat diblokir.');

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [], ['reason' => 'alasan']);

        $this->banService->ban($user, $actor, $data['reason']);

        return back()->with('success', "Akun {$user->name} diblokir.");
    }

    /** Buka blokir akun (SuperAdmin) → akun aktif kembali. */
    public function unban(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->isSuperAdmin(), 403);

        $this->banService->unban($user, $actor);

        return back()->with('success', "Blokir akun {$user->name} dibuka.");
    }

    /**
     * Ubah peran user (SuperAdmin). "Non-Admin" = Operator. Dijalankan dalam
     * konteks tim (organization_id user target) sesuai spatie teams.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->isSuperAdmin(), 403);
        abort_if($user->isSuperAdmin() || $user->id === $actor->id, 403, 'Peran akun ini tidak dapat diubah.');
        abort_if($user->organization_id === null, 422, 'User tanpa instansi tidak dapat diberi peran.');

        $data = $request->validate([
            'role' => ['required', 'in:Admin,Operator'],
        ]);

        $from = $user->getRoleNames()->all();

        // Set konteks tim ke instansi user target sebelum syncRoles.
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($user->organization_id);
        $user->syncRoles([$data['role']]);
        $registrar->forgetCachedPermissions();

        $this->audit->log('user.role_changed', $user, [
            'from' => $from,
            'to' => $data['role'],
        ], $actor->id);

        $label = $data['role'] === 'Operator' ? 'Non-Admin (Operator)' : 'Admin';

        return back()->with('success', "Peran {$user->name} diubah menjadi {$label}.");
    }

    /**
     * Aktif/cabut paket Enterprise (SuperAdmin). Aktivasi WAJIB 2FA aktif lebih dulu.
     */
    public function setPlan(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->isSuperAdmin(), 403);
        abort_if($user->isSuperAdmin(), 422, 'SuperAdmin tidak memerlukan paket.');

        $data = $request->validate([
            'action' => ['required', 'in:activate,deactivate'],
        ]);

        if ($data['action'] === 'activate') {
            abort_if(! $user->hasTwoFactorEnabled(), 422, 'Paket Enterprise wajib 2FA aktif lebih dulu.');

            $user->forceFill([
                'plan' => 'enterprise',
                'enterprise_started_at' => now(),
                'enterprise_expires_at' => now()->addYear(),
            ])->save();

            $this->audit->log('user.enterprise_activated', $user, [
                'expires_at' => $user->enterprise_expires_at?->toDateTimeString(),
            ], $actor->id);

            return back()->with('success', "Paket Enterprise {$user->name} aktif hingga ".$user->enterprise_expires_at->translatedFormat('d M Y').'.');
        }

        $user->forceFill([
            'plan' => 'free',
            'enterprise_started_at' => null,
            'enterprise_expires_at' => null,
        ])->save();

        $this->audit->log('user.enterprise_deactivated', $user, [], $actor->id);

        return back()->with('success', "Paket Enterprise {$user->name} dicabut.");
    }

    /**
     * Reset password user lain (SuperAdmin: semua instansi · Admin: instansinya sendiri).
     * Tidak boleh menyasar SuperAdmin atau diri sendiri (gunakan halaman Pengaturan untuk diri sendiri).
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        // SuperAdmin bebas; Admin hanya pada instansinya sendiri.
        $allowed = $actor->isSuperAdmin()
            || ($user->organization_id !== null && $user->organization_id === $actor->organization_id);
        abort_unless($allowed, 403);
        abort_if($user->isSuperAdmin() || $user->id === $actor->id, 403, 'Password akun ini tidak dapat direset di sini.');

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], ['password' => 'password']);

        $user->update(['password' => $data['password']]); // cast 'hashed'
        $this->audit->log('user.password_reset', $user, [], $actor->id);

        return back()->with('success', "Password {$user->name} berhasil direset.");
    }

    /**
     * Penyesuaian credit dua arah oleh SuperAdmin (di luar alur topup user).
     * delta boleh +/- (≠0); negatif di-clamp ke 0 oleh CreditService.
     */
    public function adjustCredit(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->isSuperAdmin(), 403);

        $data = $request->validate([
            'delta' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ], [], ['delta' => 'jumlah', 'reason' => 'alasan']);

        $this->credit->adjust($user, (int) $data['delta'], $data['reason'], $actor->id);

        $sign = $data['delta'] > 0 ? '+'.$data['delta'] : (string) $data['delta'];

        return back()->with('success', "Credit {$user->name} disesuaikan {$sign}. Saldo: {$user->credit_balance}.");
    }
}
