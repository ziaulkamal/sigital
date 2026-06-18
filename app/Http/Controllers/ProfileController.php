<?php

/**
 * app/Http/Controllers/ProfileController.php
 * Manajemen akun sendiri (P4): perbarui nama/email + ganti password.
 * Keputusan Q4: ganti email cukup langsung + audit (tanpa verifikasi ulang).
 */

namespace App\Http\Controllers;

use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());
        $changed = array_keys($user->getDirty());
        $user->save();

        if ($changed !== []) {
            $this->audit->log('user.profile_updated', $user, ['changed' => $changed]);
        }

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['password' => $request->validated('password')]); // cast 'hashed'
        $this->audit->log('user.password_changed', $user);

        return back()->with('success', 'Password berhasil diperbarui.');
    }

    /** Nonaktifkan akun sendiri (wajib verifikasi password). Akun jadi suspended & sesi diakhiri. */
    public function deactivate(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);
        $user = $request->user();

        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();
        $this->audit->log('user.deactivated', $user);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Akun Anda telah dinonaktifkan.');
    }
}
