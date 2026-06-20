# Berkas Font Terkurasi

Folder ini menampung berkas `.ttf` Google Fonts yang **di-bundle lokal** agar:

1. **Deterministik & offline** — render sertifikat tidak bergantung jaringan.
2. **WYSIWYG** — editor (Vue) dan renderer (Node) memakai berkas font yang **sama**,
   sehingga posisi & bentuk huruf identik antara pratinjau dan PDF.
3. **Aman** — hanya font dari daftar terkurasi yang boleh dipakai (lihat `config/fonts.php`).

## Daftar yang dibutuhkan

Berkas mengikuti `config/fonts.php` → `families[*].regular` & `families[*].bold`:

| Family                | Regular                          | Bold                          |
|-----------------------|----------------------------------|-------------------------------|
| DejaVu Sans           | `DejaVuSans.ttf`                 | `DejaVuSans-Bold.ttf`         |
| Poppins               | `Poppins-Regular.ttf`           | `Poppins-Bold.ttf`            |
| Roboto                | `Roboto-Regular.ttf`            | `Roboto-Bold.ttf`             |
| Montserrat            | `Montserrat-Regular.ttf`        | `Montserrat-Bold.ttf`         |
| Open Sans             | `OpenSans-Regular.ttf`          | `OpenSans-Bold.ttf`           |
| Lato                  | `Lato-Regular.ttf`              | `Lato-Bold.ttf`               |
| Merriweather          | `Merriweather-Regular.ttf`      | `Merriweather-Bold.ttf`       |
| Lora                  | `Lora-Regular.ttf`              | `Lora-Bold.ttf`               |
| Playfair Display      | `PlayfairDisplay-Regular.ttf`   | `PlayfairDisplay-Bold.ttf`    |
| Cormorant Garamond    | `CormorantGaramond-Regular.ttf` | `CormorantGaramond-Bold.ttf`  |
| Great Vibes           | `GreatVibes-Regular.ttf`        | (pakai regular)               |

> `DejaVuSans*.ttf` sudah disertakan paket `barryvdh/laravel-dompdf`
> (`vendor/dompdf/dompdf/lib/fonts/`). Salin dari sana bila perlu fallback.

## Cara mengunduh

Unduh ZIP keluarga dari <https://fonts.google.com> lalu salin berkas `.ttf` statis
(bukan variable font) ke folder ini. Atau gunakan `google-webfonts-helper`:

```
https://gwfh.mranftl.com/fonts
```

Setelah berkas tersedia, editor memuatnya via `@font-face` dan renderer Node
mendaftarkannya via `registerFont`. Tidak perlu langkah build tambahan.

## Catatan

- Jika sebuah `.ttf` tidak ada, renderer akan **fallback** ke `DejaVu Sans`
  (lihat `config/fonts.php` → `default`) dan menulis peringatan ke stderr.
- Lisensi font (OFL/Apache) sertakan saat distribusi produksi.
