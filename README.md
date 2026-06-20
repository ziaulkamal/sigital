<div align="center">

<img src="./public/images/logo-abdya.png" alt="Logo Pemerintah Kabupaten Aceh Barat Daya" width="120">

# SIGITAL — Sertifikat Digital

### Layanan Penerbitan Sertifikat Digital Pemerintah Kabupaten Aceh Barat Daya

Dibangun oleh **Dinas Komunikasi, Informatika, Statistik, dan Persandian (Diskominsa) Aceh Barat Daya**

Tahun Pembuatan: **2026**

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue-3.x-42b883?style=flat&logo=vue.js&logoColor=white)](https://vuejs.org)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-4.x-38bdf8?style=flat&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-3178c6?style=flat&logo=typescript&logoColor=white)](https://typescriptlang.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-3.x-9553E9?style=flat)](https://inertiajs.com)

</div>

---

## Preview

<p align="center">
  <img src="./screenshoot.png" alt="SIGITAL Preview" width="100%">
</p>

---

## Tentang

**SIGITAL** adalah aplikasi penerbitan dan pengelolaan sertifikat digital milik **Pemerintah Kabupaten Aceh Barat Daya**, dikembangkan dan dikelola oleh **Diskominsa Aceh Barat Daya**.

Aplikasi ini memungkinkan instansi di lingkungan Pemkab Aceh Barat Daya untuk membuat acara/kegiatan, mengelola peserta, menetapkan penanda tangan, serta menerbitkan sertifikat digital dengan penomoran yang aman dan dapat diverifikasi.

Dibangun di atas stack Laravel + Inertia.js + Vue 3 + TailwindCSS v4 dengan TypeScript strict mode.

---

## Tech Stack

| Layer | Technology | Version |
|---|---|---|
| Backend | Laravel | 13.x |
| Frontend Bridge | Inertia.js | 3.x |
| UI Framework | Vue 3 (Composition API) | 3.5.x |
| Language | TypeScript (strict mode) | 5.x |
| Styling | TailwindCSS v4 (CSS-first) | 4.x |
| Build Tool | Vite | 8.x |
| Icons | @lucide/vue (tree-shakeable) | 1.x |
| Charts | Chart.js + vue-chartjs | 4.x |
| Fonts | DM Sans Variable, JetBrains Mono, Geist | — |

---

## Fitur Utama

### Penerbitan Sertifikat
- **Manajemen Acara** — buat & kelola acara/kegiatan; kolaborasi antar pengguna via *event member* + kode undangan (join code), dengan persetujuan pemilik acara.
- **Manajemen Peserta** — input manual atau impor massal (pratinjau sebelum simpan), pelacakan status sertifikat per peserta.
- **Penanda Tangan (Signatory)** — kelola penanda tangan resmi per-pengguna dengan deteksi & konfirmasi duplikat nama.
- **Penerbitan & Arsip** — render PDF (penomoran aman & sulit ditebak, QR verifikasi), penerbitan satuan maupun massal (batch), pencabutan & pemulihan, regenerasi mengikuti template terbaru, serta distribusi via email.
- **Verifikasi Publik** — halaman verifikasi keaslian sertifikat melalui token QR.

### Template & Perancang Visual
- **Perancang Template (WYSIWYG)** — editor kanvas (Konva) untuk menata posisi field di atas gambar latar; render PDF via Node. Layout berbasis fraksi, sanitasi anti-injeksi, dan font terkurasi.
- **Branding Instansi** — kustomisasi logo & kop surat per organisasi pada sertifikat terbit.

### Multi-Organisasi & Peran
- **Tenancy** — pemisahan data antar instansi (dinas/komunitas); switcher organisasi untuk SuperAdmin.
- **Alur Persetujuan Akun** — registrasi mandiri → status *pending* → persetujuan SuperAdmin; dinas wajib melampirkan surat rekomendasi.
- **Manajemen Peran (SuperAdmin)** — penetapan peran Admin / Operator dalam konteks tim.
- **Reset Password Pengguna** — SuperAdmin (lintas instansi) & Admin (instansinya sendiri) dapat mereset kata sandi pengguna lain.
- **Blokir / Buka Blokir Akun** — pemblokiran disertai alasan yang ditampilkan saat login.

### Monetisasi (PAD) — Sistem Credit
- **Saldo Credit per-pengguna** — buat acara & template memotong credit; seluruh mutasi melalui *ledger* append-only yang dikunci transaksi (anti race-condition) dan tercatat di audit.
- **Topup Credit** — pengajuan manual + unggah bukti transfer (disk privat) → verifikasi/penolakan oleh SuperAdmin.
- **Penyesuaian Credit** — SuperAdmin dapat menambah/mengurangi saldo (dengan alasan; tak bisa minus).
- **Paket Enterprise** — bebas-credit selama berlaku, ditetapkan SuperAdmin, **wajib 2FA aktif** agar benefit berjalan.
- **Anti-eksploit** — saldo non-fillable, seluruh nominal dihitung server-side, dan upaya pemakaian saat saldo kurang tetap terekam di audit.

### Marketplace Template & Creator
- **Pendaftaran Creator** — registrasi dengan unggah KTP (disk privat), data identitas, dan persetujuan S&K; diverifikasi SuperAdmin. Halaman pendaftaran publik tersedia.
- **Rekening Pencairan** — wajib diisi & diverifikasi SuperAdmin sebelum fitur creator (publikasi & pencairan) terbuka.
- **Jual-Beli Template** — pemakaian template milik orang lain dengan bagi hasil credit (royalti pemilik + pendapatan platform yang dicatat terpisah).
- **Pencairan Royalti** — pengajuan pencairan (potong biaya admin), penjadwalan & penandaan dibayar oleh SuperAdmin; notifikasi ke SuperAdmin + Admin instansi.
- **Dashboard** — ringkasan kontribusi & pencairan untuk Creator; statistik marketplace, template terlaris, & verifikasi untuk SuperAdmin.

### Keamanan & Operasional
- **Keamanan Akun** — verifikasi OTP via WhatsApp saat registrasi, Two-Factor Authentication (2FA) opsional dengan recovery code.
- **Log Login (GeoIP-ready)** — pencatatan IP & user-agent setiap login (termasuk via 2FA); slot kolom negara disiapkan.
- **Notifikasi In-App** — registrasi, persetujuan akun, acara, permintaan bergabung, topup, pendaftaran/verifikasi creator & rekening, serta pencairan.
- **Audit Log** — pencatatan aktivitas penting per organisasi (append-only, mencatat IP aktor).
- **Manajemen Profil** — ubah nama/email/password, nonaktifkan akun sendiri, lengkapi profil wajib (NIK + HP) pasca-approve.
- **Dark Mode** — class-based, persisten via `localStorage`.

---

## Halaman Legal & Ketentuan Layanan

SIGITAL menyediakan halaman informasi hukum yang wajib disetujui/diketahui pengguna:

| Halaman | Deskripsi |
|---|---|
| **Syarat dan Ketentuan Layanan** | Ketentuan penggunaan layanan SIGITAL, hak & kewajiban pengguna, batasan tanggung jawab Pemkab Aceh Barat Daya, serta aturan penerbitan dan keabsahan sertifikat digital. |
| **Kebijakan Privasi** | Penjelasan jenis data pribadi yang dikumpulkan (identitas, nomor telepon, dsb.), tujuan penggunaan, dasar pemrosesan, penyimpanan, dan hak pengguna atas datanya, sesuai peraturan perlindungan data yang berlaku. |
| **Pemberitahuan Cookie (Cookie Concern)** | Informasi penggunaan cookie/penyimpanan lokal (sesi login, preferensi tema, dsb.), jenis cookie yang dipakai, dan cara pengguna mengelola persetujuannya. |

> Konten halaman legal dikelola oleh Diskominsa Aceh Barat Daya dan dapat diperbarui sewaktu-waktu mengikuti regulasi yang berlaku.

---

## Instalasi

### Requirements
- PHP 8.2+
- Node.js 20+
- Composer 2.x

### Setup

```bash
# Clone repository
git clone https://github.com/ziaulkamal/sigital.git
cd sigital

# Install dependencies
composer install
npm install

# Konfigurasi environment
cp .env.example .env
php artisan key:generate
php artisan migrate

# Jalankan development server
php artisan serve

# Di terminal terpisah
npm run dev
```

### Production Build

```bash
npm run build
```

---

## Lisensi

Hak cipta dan hak penggunaan aplikasi ini dimiliki oleh **Pemerintah Kabupaten Aceh Barat Daya**. Penggunaan, distribusi, dan modifikasi di luar lingkungan resmi Pemkab Aceh Barat Daya harus dengan izin tertulis dari Diskominsa Aceh Barat Daya.

---

<div align="center">

<img src="./public/images/logo-abdya.png" alt="Logo Aceh Barat Daya" width="64">

**SIGITAL — Sertifikat Digital**

Dibuat oleh **Diskominsa Aceh Barat Daya** &nbsp;·&nbsp; © 2026 Pemerintah Kabupaten Aceh Barat Daya

</div>
