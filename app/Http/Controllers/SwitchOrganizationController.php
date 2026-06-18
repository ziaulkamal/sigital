<?php

/**
 * app/Http/Controllers/SwitchOrganizationController.php
 * Switcher organisasi SuperAdmin (P1): simpan pilihan di sesi.
 * null = lihat semua organisasi; sebuah id = scope ke organisasi tersebut.
 */

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchOrganizationController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        // Hanya SuperAdmin (org null) yang boleh berpindah konteks organisasi.
        abort_unless($request->user()?->isSuperAdmin() ?? false, 403);

        $data = $request->validate([
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
        ]);

        $request->session()->put('current_organization_id', $data['organization_id'] ?? null);

        return back();
    }
}
