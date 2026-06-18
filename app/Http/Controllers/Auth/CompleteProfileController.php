<?php

/**
 * app/Http/Controllers/Auth/CompleteProfileController.php
 * Lengkapi profil wajib (NIK + nomor HP) setelah akun di-approve. Sebelum lengkap,
 * seluruh fitur dikunci oleh middleware EnsureProfileComplete. SuperAdmin tidak melewati ini.
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteProfileRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompleteProfileController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(Request $request): Response|RedirectResponse
    {
        // Sudah lengkap → tak perlu di halaman ini.
        if (! $request->user()->needsProfileCompletion()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/CompleteProfile');
    }

    public function store(CompleteProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        // Mutator User::phone menormalkan 08… → 628… saat di-set.
        $user->forceFill($request->validated())->save();

        $this->audit->log('user.profile_completed', $user, ['fields' => ['nik', 'phone']]);

        return redirect()->route('dashboard')->with('success', 'Profil lengkap. Selamat datang!');
    }
}
