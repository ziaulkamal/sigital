<?php

/**
 * app/Http/Controllers/TemplateController.php
 * CRUD template sertifikat per-organisasi (P6/K7). Scoping & auto-fill org via trait;
 * uploaded_by diisi di service.
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemplateRequest;
use App\Http\Requests\UpdateTemplateRequest;
use App\Models\Template;
use App\Services\TemplateService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function __construct(private readonly TemplateService $service) {}

    public function index(): Response
    {
        return Inertia::render('Templates/Index', [
            'templates' => Template::orderByDesc('id')->get()->map(fn ($t) => [
                'id' => $t->id,
                'nama' => $t->nama,
                'deskripsi' => $t->deskripsi,
                'background' => $t->background_path ? asset('storage/'.$t->background_path) : null,
                'is_global' => $t->is_global,
                'is_active' => $t->is_active,
            ]),
        ]);
    }

    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        $this->service->create($request->safe()->except('background'), $request->file('background'));

        return back()->with('success', 'Template berhasil ditambahkan.');
    }

    public function update(UpdateTemplateRequest $request, Template $template): RedirectResponse
    {
        $this->service->update($template, $request->safe()->except('background'), $request->file('background'));

        return back()->with('success', 'Template berhasil diperbarui.');
    }

    public function destroy(Template $template): RedirectResponse
    {
        $this->service->deactivate($template);

        return back()->with('success', 'Template dinonaktifkan.');
    }
}
