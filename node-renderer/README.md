# SIGITAL Node Renderer (WYSIWYG)

Renderer sertifikat berbasis **Konva + node-canvas**. Memakai mesin gambar yang
**sama** dengan editor (`resources/js/Pages/Templates/Editor.vue`) dan modul
posisi bersama (`layout-geometry.mjs`) sehingga PDF/PNG = pratinjau editor.

Dipanggil **per-render** oleh Laravel (`App\Services\Certificate\NodeCanvasRenderer`)
melalui Symfony Process — payload JSON di STDIN, PNG biner di STDOUT. Tidak ada
service HTTP yang harus selalu hidup; cocok dengan queue (`IssueCertificateJob`).

## Pasang

```bash
cd node-renderer
npm install
```

> **node-canvas butuh pustaka native** (Cairo, Pango, libjpeg, giflib).
> - Windows: paket `canvas` menyediakan prebuilt; biasanya langsung jalan.
> - Linux server (produksi Octane/RoadRunner):
>   `apt-get install -y libcairo2-dev libpango1.0-dev libjpeg-dev libgif-dev librsvg2-dev`
>   lalu `npm install`.

## Uji cepat

```bash
node render.mjs < sample-payload.json > out.png
```

Buka `out.png` dan pastikan posisi/teks sesuai. `sample-payload.json` disertakan
sebagai contoh; path font/gambar di dalamnya harus absolut & ada.

## Konfigurasi dari Laravel

- Biner Node: `config('fonts.node_binary')` (env `NODE_BINARY`, default `node`).
- PHP meresolusi semua path (latar, ttd, logo, font) ke absolut & mengecek
  keberadaannya sebelum mengirim. Renderer hanya membaca path yang diberikan.

## Kontrak STDIN

Lihat komentar kepala `render.mjs` untuk skema payload lengkap.
