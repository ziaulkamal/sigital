<?php

/**
 * app/Http/Controllers/TemplateController.php
 * CRUD template sertifikat per-organisasi (P6/K7). Scoping & auto-fill org via trait;
 * uploaded_by diisi di service.
 */

namespace App\Http\Controllers;

use App\Http\Requests\SaveTemplateLayoutRequest;
use App\Http\Requests\StoreTemplateRequest;
use App\Http\Requests\UpdateTemplateRequest;
use App\Models\Template;
use App\Services\Certificate\NodeCanvasRenderer;
use App\Services\TemplateService;
use App\Support\TemplateLayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TemplateController extends Controller
{
    public function __construct(private readonly TemplateService $service) {}

    public function index(\Illuminate\Http\Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Templates/Index', [
            'templates' => Template::orderByDesc('id')->get()->map(fn ($t) => [
                'id' => $t->id,
                'nama' => $t->nama,
                'deskripsi' => $t->deskripsi,
                'background' => $t->background_path ? asset('storage/'.$t->background_path) : null,
                'is_global' => $t->is_global,
                'is_active' => $t->is_active,
                'is_marketplace' => $t->is_marketplace,
                'is_mine' => $t->uploaded_by === $user->id,
            ]),
            'isMarketplaceCreator' => $user->isMarketplaceCreator(),
        ]);
    }

    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        $template = $this->service->create($request->safe()->except('background'), $request->file('background'));

        // Setelah unggah latar, arahkan ke perancang visual untuk menata field.
        return redirect()
            ->route('templates.editor', $template)
            ->with('success', 'Template dibuat. Tata posisi field di perancang.');
    }

    /** Perancang visual (WYSIWYG) untuk menata posisi & gaya field di atas latar. */
    public function editor(Template $template): Response
    {
        return Inertia::render('Templates/Editor', [
            'template' => [
                'id' => $template->id,
                'nama' => $template->nama,
                'deskripsi' => $template->deskripsi,
                'background' => $template->background_path ? asset('storage/'.$template->background_path) : null,
                'canvas' => [
                    'w' => $template->canvas_width,
                    'h' => $template->canvas_height,
                ],
                'layout' => $template->posisi_field ?: ['canvas' => ['w' => $template->canvas_width, 'h' => $template->canvas_height], 'elements' => []],
            ],
            // Daftar font terkurasi untuk dropdown editor (key + kategori).
            'fonts' => collect(config('fonts.families'))
                ->map(fn ($f, $name) => ['value' => $name, 'label' => $name, 'category' => $f['category'] ?? 'sans'])
                ->values(),
        ]);
    }

    public function saveLayout(SaveTemplateLayoutRequest $request, Template $template): RedirectResponse
    {
        $this->service->saveLayout($template, $request->validated('layout'));

        return back()->with('success', 'Tata letak template disimpan.');
    }

    /**
     * Pratinjau PNG dengan data dummy untuk uji kecocokan posisi (poin 4).
     * Layout dikirim dari editor (state terkini, belum tentu tersimpan) lalu disanitasi.
     */
    public function preview(SaveTemplateLayoutRequest $request, Template $template, NodeCanvasRenderer $renderer): HttpResponse
    {
        // Terapkan layout terkini editor ke salinan in-memory (tanpa menyimpan).
        $clean = TemplateLayout::sanitize($request->validated('layout'));
        $template->setAttribute('posisi_field', $clean);

        try {
            $png = $renderer->previewPng($template);
        } catch (\Throwable $e) {
            return response('Gagal membuat pratinjau: '.$e->getMessage(), 422);
        }

        return response($png, 200, ['Content-Type' => 'image/png']);
    }

    /**
     * Sajikan berkas .ttf font terkurasi untuk @font-face editor.
     * Hanya family dalam whitelist config/fonts → aman dari path traversal.
     */
    public function font(string $family, string $weight = 'regular'): BinaryFileResponse
    {
        $meta = config("fonts.families.{$family}");
        abort_unless(is_array($meta), 404);

        $file = $weight === 'bold' ? ($meta['bold'] ?? $meta['regular']) : $meta['regular'];
        $path = base_path((string) config('fonts.path', 'resources/fonts')).DIRECTORY_SEPARATOR.$file;
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'font/ttf',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
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
