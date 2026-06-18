# PRD — Aplikasi Tata Kelola Sertifikat Digital

## Kontrol Dokumen

| Field | Isi |
|---|---|
| Nama produk | Aplikasi Tata Kelola Sertifikat Digital (kerja: SIGITAL — *Sistem Sertifikat Digital*) |
| Versi dokumen | 0.1 (draft untuk pengkajian) |
| Tanggal | 18 Juni 2026 |
| Status | Draft — menunggu validasi pemangku kepentingan |
| Tipe organisasi | Instansi pemerintah / dinas |
| Penyusun | Tim Product (draft hasil discovery) |

> Dokumen ini adalah hasil perencanaan Fase 1–6. Bagian **Pertanyaan Terbuka** memuat hal yang masih perlu diputuskan instansi sebelum pembangunan dimulai.

---

## 1. Ringkasan Eksekutif

Aplikasi web untuk **tata kelola dan penerbitan sertifikat acara** di lingkungan instansi pemerintah. Sistem mendaftarkan penanda tangan, mengelola acara beserta jadwal dan lokasi, memasukkan peserta (impor massal atau manual), lalu menerbitkan sertifikat ber-nomor unik yang dilengkapi **QR untuk verifikasi keaslian** — seluruhnya tercatat dalam **arsip yang tahan audit**.

Prioritas nilai (berdasarkan kesepakatan): **(1) kerapian arsip & jejak siapa menandatangani apa**, (2) mempersulit pemalsuan, (3) mempercepat penerbitan, (4) verifikasi oleh pihak luar.

---

## 2. Latar Belakang & Pernyataan Masalah

**Masalah inti:** Instansi membutuhkan satu sistem terpusat yang menyimpan dan melacak penerbitan sertifikat acara secara tahan audit, sekaligus mempersulit pemalsuan.

**Kondisi saat ini (asumsi, perlu dikonfirmasi):** sertifikat dibuat manual (mis. pengolah dokumen/desain lepas), nomor diketik tangan, arsip tersebar di folder dan email, dan tidak ada cara cepat membuktikan sebuah sertifikat asli. Akibatnya: rawan salah/ganda nomor, sulit ditelusuri, dan tidak ada jejak audit yang rapi.

**Mengapa sekarang:** mendukung transformasi digital instansi dan kebutuhan arsip jangka panjang yang dapat dipertanggungjawabkan.

---

## 3. Tujuan & Non-Tujuan

### Tujuan
- Menyediakan arsip sertifikat terpusat dengan jejak audit lengkap (siapa, apa, kapan).
- Menerbitkan sertifikat ber-nomor **unik dan terkunci**, sulit dipalsukan, dapat diverifikasi via QR.
- Mempercepat penerbitan, termasuk **penerbitan massal** dari satu daftar peserta.
- Mendistribusikan sertifikat ke peserta (unduh/email) sekaligus mengarsipkannya.

### Non-Tujuan (MVP)
- **Bukan** menggantikan Tanda Tangan Elektronik (TTE) tersertifikasi BSrE pada tahap MVP (dijadikan opsi versi berikutnya).
- **Bukan** portal akun peserta (login peserta) pada MVP.
- **Bukan** sistem multi-instansi / multi-tenant.
- **Tidak** menangani pembayaran apa pun.
- **Tidak** berupa aplikasi mobile native (cukup web responsif).

---

## 4. Pengguna Sasaran & Peran

| Peran | Deskripsi | Akses |
|---|---|---|
| **Admin** | Mengelola data master (penanda tangan, template), pengguna sistem, dan konfigurasi penomoran | Penuh |
| **Operator / Panitia** | Mengelola acara, peserta, menerbitkan & mendistribusikan sertifikat | Operasional |
| **Publik (tanpa login)** | Memverifikasi keaslian sertifikat via QR/halaman verifikasi | Hanya verifikasi |

Konteks pemakaian operator: **campuran kantor dan lapangan** → antarmuka harus responsif (berfungsi baik di desktop maupun browser ponsel).

---

## 5. Lingkup Produk

### 5.1 Must-have (MVP)

| Kode | Fitur | Alasan esensial |
|---|---|---|
| M1 | Manajemen penanda tangan (nama, jabatan, unggah spesimen TTD) | Tanpa spesimen, sertifikat tak bisa dibubuhi tanda tangan |
| M2 | Manajemen acara (nama, jadwal, lokasi, tetapkan penanda tangan, pilih template) | Mengikat siapa menandatangani untuk acara mana (inti jejak audit) |
| M3 | Manajemen peserta per acara — **impor massal CSV** + input manual | Volume belum pasti; acara instansi sering banyak peserta |
| M4 | Generator nomor sertifikat unik (terstruktur + komponen acak) | Unik walau ada nama kembar; sulit ditebak |
| M5 | Generator sertifikat PDF dari template (sisip nama, acara, nomor, TTD, QR) | Output utama yang dipakai peserta & arsip |
| M6 | QR + halaman verifikasi publik (verifikasi sisi server) | Anti-pemalsuan; pihak luar dapat memastikan keaslian |
| M7 | Arsip & pencarian + **log audit append-only** | Prioritas utama; catatan tak-terubah |
| M8 | Distribusi ke peserta (tautan unduh / kirim email) | Sertifikat untuk diarsipkan **dan** diterima peserta |
| M9 | Autentikasi & peran pengguna (Admin/Operator) | Wajib untuk instansi & agar log audit bermakna |

### 5.2 Should-have (versi berikutnya / v2)
Registrasi mandiri peserta + status kehadiran · integrasi **TTE Tersertifikasi BSrE** · perancang template visual (atur posisi field) · dashboard statistik · notifikasi email otomatis · integrasi e-Office/persuratan instansi.

### 5.3 Won't-have (sekarang — sengaja ditunda)
Portal login peserta · multi-tenant · pembayaran · aplikasi mobile native.

> **Catatan registrasi mandiri:** disepakati ditunda ke v2. Polanya: form terbuka per-acara (peserta **tanpa login**) menghasilkan daftar pendaftar; panitia menyeleksi berdasarkan **kehadiran di lokasi**; sertifikat hanya terbit untuk yang hadir. Karena itu, model data MVP **sudah menyiapkan** field `sumber` dan `status_kehadiran` agar fitur ini bisa dipasang tanpa bongkar ulang (lihat §8).

---

## 6. Kebutuhan Fungsional

### 6.1 Penanda Tangan
- **FR-01** Sistem dapat menambah/ubah/nonaktifkan penanda tangan dengan field: nama, jabatan, unggah gambar spesimen tanda tangan.
- **FR-02** Satu acara dapat memiliki satu atau lebih penanda tangan.

### 6.2 Acara
- **FR-03** Operator dapat membuat acara: nama, jadwal (tanggal/waktu), lokasi, template, dan penanda tangan terkait.
- **FR-04** Sertifikat hanya dapat diterbitkan bila acara sudah memiliki minimal satu penanda tangan **dan** satu template.
- **FR-05** Acara memiliki status (mis. draft → siap terbit → selesai) untuk mengontrol kapan penerbitan diizinkan.

### 6.3 Peserta
- **FR-06** Operator dapat menambah peserta secara manual.
- **FR-07** Operator dapat **mengimpor peserta via CSV**, dengan **validasi + pratinjau** sebelum disimpan (kolom tak cocok ditolak dengan pesan jelas).
- **FR-08** Sistem mendeteksi kemungkinan duplikat peserta dalam satu acara dan memberi peringatan.
- **FR-09** Satu orang (entitas `PERSON`) dapat menjadi peserta di banyak acara; arsipnya terkumpul lintas waktu.

### 6.4 Penerbitan Sertifikat
- **FR-10** Sistem menghasilkan **nomor sertifikat unik dan terkunci** saat penerbitan (lihat §11 untuk format).
- **FR-11** Sistem menghasilkan **PDF** dari template, menyisipkan nama peserta, data acara, nomor, gambar TTD, dan **QR**.
- **FR-12** Sistem mendukung **penerbitan massal** untuk seluruh peserta acara dalam satu perintah, diproses **secara asinkron** (antrean) dengan indikator progres.
- **FR-13** Setiap PDF yang terbit disimpan beserta **hash (SHA-256)** untuk deteksi integritas.
- **FR-14** Nomor sertifikat tidak dapat diubah atau dipakai ulang setelah terbit.

### 6.5 Verifikasi
- **FR-15** Setiap sertifikat memuat QR yang mengarah ke **URL verifikasi di server** (bukan menyimpan data di dalam QR).
- **FR-16** Halaman verifikasi publik menampilkan status keaslian + metadata **secukupnya** (nama, acara, tanggal, nomor) — **tanpa** membuka data pribadi berlebih.
- **FR-17** Verifikasi dapat membedakan: sertifikat asli, tidak ditemukan, atau dicabut/dibatalkan.

### 6.6 Arsip, Pencarian & Audit
- **FR-18** Operator dapat mencari sertifikat berdasarkan nama, acara, nomor, atau rentang tanggal.
- **FR-19** Sistem mencatat **log audit append-only** untuk setiap penerbitan dan perubahan penting (aktor, aksi, entitas, waktu) — tidak dapat dihapus diam-diam.
- **FR-20** Log audit dapat diekspor untuk keperluan pemeriksaan.

### 6.7 Distribusi
- **FR-21** Sistem dapat menyediakan tautan unduh per sertifikat.
- **FR-22** Sistem dapat mengirim sertifikat ke email peserta (bila tersedia).

### 6.8 Pengguna & Akses
- **FR-23** Admin dapat mengelola akun pengguna sistem dan perannya.
- **FR-24** Akses fitur dibatasi sesuai peran (Admin/Operator).

---

## 7. Kebutuhan Non-Fungsional

### 7.1 Keamanan
- **NFR-01** Nomor sertifikat memuat komponen acak agar tidak mudah ditebak secara berurutan.
- **NFR-02** Verifikasi keaslian dilakukan **di sisi server**; QR hanya membawa token/URL.
- **NFR-03** Seluruh trafik melalui HTTPS; kredensial tersimpan ter-hash.
- **NFR-04** Otorisasi berbasis peran pada setiap aksi sensitif.

### 7.2 Privasi Data (UU PDP No. 27/2022)
- **NFR-05** Terapkan **minimisasi data**: hanya kumpulkan field peserta yang diperlukan.
- **NFR-06** Halaman verifikasi publik **tidak menampilkan** data pribadi sensitif (mis. NIK, alamat, kontak).
- **NFR-07** Tetapkan dasar pemrosesan, masa retensi, dan mekanisme penghapusan/anonimisasi data.

### 7.3 Integritas & Arsip
- **NFR-08** Simpan hash tiap PDF; perubahan berkas dapat terdeteksi saat verifikasi.
- **NFR-09** Arsip jangka panjang menggunakan format **PDF/A**; sediakan **backup** dan kebijakan retensi.

### 7.4 Kinerja & Ketersediaan
- **NFR-10** Penerbitan massal (mis. ratusan sertifikat) diproses melalui **antrean** agar tidak gagal/timeout.
- **NFR-11** Pencarian arsip menampilkan hasil dalam waktu wajar (target < 30 detik untuk kueri umum).

### 7.5 Aksesibilitas & Kompatibilitas
- **NFR-12** Antarmuka **responsif** (desktop & browser ponsel), mendukung dark mode (disediakan starter).
- **NFR-13** Mendukung peramban modern terkini.

### 7.6 Kepatuhan & Penempatan Data
- **NFR-14** Arsitektur **self-hostable** agar dapat ditempatkan di lingkungan pemerintah (PDN/on-premise) bila diwajibkan. *(Keputusan hosting final — lihat Pertanyaan Terbuka.)*

---

## 8. Model Data

Entitas inti dan relasinya:

| Entitas | Field kunci | Catatan |
|---|---|---|
| **User** | id, nama, email, peran, password_hash | Admin/Operator |
| **Signatory** (Penanda Tangan) | id, nama, jabatan, gambar_ttd, *(future)* bsre_cert_id | Spesimen TTD |
| **Event** (Acara) | id, nama, jadwal, lokasi, template_id, status | — |
| **EventSignatory** | event_id, signatory_id | Relasi M:N acara ↔ penanda tangan |
| **Person** (Orang) | id, nama, email, nik_opsional | **Identitas durabel** lintas acara → basis arsip & portal masa depan |
| **Registration** (Peserta-Acara) | id, person_id, event_id, **sumber** (impor/daftar-sendiri), **status_kehadiran** | Gerbang kehadiran (UI tayang v2) |
| **Certificate** (Sertifikat) | id, nomor_unik, qr_token, pdf_path, pdf_hash, issued_at, issued_by, status | Satu per (Person, Event) |
| **Template** | id, nama, posisi_field (JSON) | Perancang visual = v2 |
| **AuditLog** | id, actor_id, aksi, entitas, waktu, detail | **Append-only** |

**Relasi ringkas:** `Template 1—N Event` · `Event N—M Signatory` (via EventSignatory) · `Person 1—N Registration` · `Event 1—N Registration` · `Registration 1—0..1 Certificate` · `User 1—N Certificate (issued_by)` · `User 1—N AuditLog`.

> Pemodelan `Person` sebagai entitas terpisah (bukan sekadar baris peserta per acara) adalah keputusan yang membuat arsip jangka panjang dan portal peserta masa depan dapat dibangun tanpa migrasi besar.

---

## 9. Alur Pengguna

### 9.1 Operator menerbitkan sertifikat (alur utama)
Login → buat acara → tetapkan penanda tangan + template → impor CSV peserta (validasi + pratinjau) → klik **Terbitkan** → sistem mengantre & membuat PDF + nomor + QR → distribusi (unduh/email).
*Titik gesekan:* CSV berantakan → butuh validasi & pratinjau; batch besar → butuh indikator progres.

### 9.2 Peserta menerima sertifikat
Terima email/tautan → unduh PDF → (opsional) tunjukkan ke pihak ketiga.
*Titik gesekan:* tautan kedaluwarsa; tidak tahu cara verifikasi → sertakan petunjuk verifikasi pada email/PDF.

### 9.3 Pihak ketiga memverifikasi
Pindai QR → halaman verifikasi server → status **"ASLI"** + metadata secukupnya.
*Titik gesekan:* jangan bocorkan data pribadi; tampilan harus meyakinkan & jelas.

---

## 10. Arsitektur Teknis

### 10.1 Basis (starter)
Dibangun di atas starter **Laravel-Vue-Starter-E-GOV** (E-Gov CRM) sebagai fondasi UI & shell aplikasi.

| Lapis | Teknologi |
|---|---|
| Backend | Laravel 13 (PHP 8.2+) |
| Jembatan frontend | Inertia.js 3 |
| UI | Vue 3 (Composition API) + TailwindCSS v4 |
| Bahasa | TypeScript (strict) |
| Build | Vite |
| Ikon / Chart | Lucide · Chart.js |

Starter menyediakan `BaseLayout`/`AuthLayout`, ~23 komponen inti (form, tabel, modal, dsb.), komponen kompleks (`DataTable`, `WizardForm`, `FileDropzone`, `AppDatePicker`), dan halaman contoh — mempercepat pembangunan UI admin secara signifikan.

### 10.2 Pemisahan lapisan API (sesuai arahan)
Starter berbasis **Inertia** (server-driven, route lewat `web.php`). Untuk memenuhi kebutuhan "pisahkan API di belakang", arsitektur memisahkan **dua permukaan routing**:

| Permukaan | Berkas route | Auth | Untuk |
|---|---|---|---|
| **Web (Inertia)** | `routes/web.php` | Sesi (session) | UI internal Admin/Operator (CRUD acara, peserta, penerbitan) |
| **API (stateless)** | `routes/api.php` — ber-versi `/api/v1` | Token (**Laravel Sanctum**) | Verifikasi publik QR, status penerbitan batch, **dan masa depan**: intake registrasi mandiri, portal peserta, integrasi BSrE/e-Office |

Manfaat pemisahan: UI admin tetap ringkas lewat Inertia, sementara endpoint yang perlu diakses lintas-kanal (publik, integrasi, portal) berdiri sebagai **API JSON yang bersih, ber-versi, dan stateless** — sehingga penambahan kanal baru tidak mengganggu UI internal.

### 10.3 Pola berlapis (layered) di backend
`Route` → `Controller` (tipis) → `FormRequest` (validasi) → `Service` (logika bisnis) → `Repository` → `Eloquent Model`; respons API dirapikan via **API Resource** (transformer). Pemisahan ini menjaga logika bisnis tidak tercampur di controller dan memudahkan pengujian.

### 10.4 Komponen pendukung
- **Basis data:** PostgreSQL **direkomendasikan** (kuat untuk integritas & constraint arsip). MySQL dapat diterima bila infrastruktur instansi mengharuskan.
- **Antrean (queue):** Laravel Queue (driver database/Redis) untuk penerbitan PDF massal.
- **PDF:** pustaka render PDF (mis. DomPDF) + langkah konversi **PDF/A** untuk arsip jangka panjang.
- **QR:** pustaka pembuat QR (mis. simple-qrcode); QR memuat URL verifikasi + token opaque.
- **Penyimpanan berkas:** Laravel Filesystem (lokal / S3-compatible), dengan `pdf_hash` (SHA-256) tersimpan di basis data.
- **Email:** Laravel Mail (SMTP) untuk distribusi.
- **Peran & izin:** sesi Laravel untuk web + Sanctum untuk API; manajemen peran (mis. paket permission) untuk Admin/Operator.

### 10.5 Alternatif yang dipertimbangkan
Sebelumnya dipertimbangkan stack React + Node.js + PostgreSQL (API-first murni). Dibandingkan itu, jalur **Laravel + Inertia + Vue** dipilih karena: satu kerangka matang untuk UI admin + backend, ekosistem Laravel kaya untuk antrean/PDF/QR/mail, dan **starter E-GOV** memangkas waktu pembangunan UI. Lapisan API terpisah tetap memberi keleluasaan lintas-kanal seperti pendekatan API-first.

---

## 11. Aturan Penomoran & Verifikasi (detail produk)

**Konsep awal "nomor enkripsi dari nama" direvisi** menjadi dua hal terpisah agar aman dan tidak bentrok:

1. **Nomor sertifikat — unik & terstruktur.** Contoh format:
   `[KODE-INSTANSI]/[KODE-ACARA]/[NO-URUT]/[BULAN-ROMAWI]/[TAHUN]`
   ditambah **sufiks acak pendek** (mis. 4–6 karakter) agar tidak mudah ditebak.
   Contoh: `DISKOMINFO/PELATIHAN-AI/0125/VI/2026-7QK3`.
   *Format final mengikuti nomenklatur yang berlaku di instansi — lihat Pertanyaan Terbuka.*

2. **Token verifikasi — terpisah dari nomor.** QR memuat URL berisi **token opaque** (acak, tak bermakna) yang dipetakan ke sertifikat di server. Saat dipindai, server menampilkan status keaslian. Karena verifikasi server-side dan token acak, sertifikat palsu tidak bisa "mengaku" asli.

3. **Integritas berkas.** Hash SHA-256 dari PDF disimpan; bila berkas diubah, perbandingan hash akan menandainya.

> *Catatan keaslian:* pada MVP keaslian bersifat **verifikasi internal** (nomor unik + QR + hash). Ini cukup untuk tata kelola & anti-pemalsuan tingkat instansi, tetapi tanda tangannya **belum berstatus tersertifikasi secara hukum** seperti TTE BSrE. Integrasi BSrE disiapkan sebagai jalur upgrade (v2).

---

## 12. Rencana Eksekusi (Milestone)

Urutan disusun agar **risiko tertinggi divalidasi lebih dulu**.

| # | Milestone | Isi |
|---|---|---|
| **M1** | Fondasi | Setup starter, pemisahan route web/API, model data, auth + peran, kerangka **log audit** |
| **M2** | Jantung produk | Generator **nomor unik** + PDF dari 1 template + **QR + halaman verifikasi** (risiko teknis tertinggi) |
| **M3** | Operasional | Kelola acara + penanda tangan + impor CSV peserta + **penerbitan batch asinkron** |
| **M4** | Distribusi & arsip | Unduh/email + arsip + pencarian |
| **M5** | Pengerasan | **PDF/A**, hash integritas, retensi, backup, audit ekspor |
| *(v2)* | Lanjutan | Registrasi mandiri + kehadiran · TTE BSrE · perancang template · dashboard · notifikasi |

*(Estimasi waktu/jam tidak disertakan pada draft ini; dapat ditambahkan saat penjadwalan.)*

---

## 13. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| **Keamanan nomor/QR** — nomor mudah ditebak atau QR dipalsu | Anti-pemalsuan gagal | Token acak opaque + verifikasi server-side + hash PDF |
| **Scope creep** — fitur lanjutan (registrasi mandiri dll.) merembet ke MVP | Jadwal & biaya membengkak | Kolom Won't-have terkunci; registrasi mandiri **difondasikan** tapi tidak ditayangkan |
| **Kepatuhan & hosting** — PDP + lokasi data (PDN/on-prem) belum pasti | Risiko hukum/teknis di akhir | Stack self-hostable; minimisasi data; **putuskan hosting sebelum membangun** |

---

## 14. Metrik Sukses MVP

- Waktu terbit per sertifikat turun drastis dibanding proses manual.
- **100%** sertifikat terbit memiliki nomor unik & dapat diverifikasi via QR.
- **0** sertifikat dapat terbit tanpa tercatat di log audit.
- Penerbitan batch (mis. ~200 peserta) selesai tanpa kegagalan.
- Petugas menemukan sertifikat lama via pencarian dalam **< 30 detik**.

---

## 15. Pertanyaan Terbuka

1. **Lokasi hosting:** wajib di lingkungan pemerintah (PDN/on-premise) atau boleh cloud?
2. **Masa retensi arsip:** berapa lama sertifikat & datanya wajib disimpan?
3. **Nomenklatur nomor:** format & kode resmi penomoran sertifikat di instansi?
4. **Template awal:** berapa desain template yang perlu disiapkan sejak MVP?
5. **Basis data:** PostgreSQL atau mengikuti standar infrastruktur instansi (MySQL)?
6. **Kebijakan email:** server email/SMTP instansi yang akan dipakai untuk distribusi?

---

## 16. Glosarium

- **TTE Tersertifikasi:** Tanda Tangan Elektronik bersertifikat dari PSrE resmi (mis. BSrE), berkekuatan hukum berdasarkan UU ITE & PP 71/2019.
- **BSrE:** Balai/Balai Besar Sertifikasi Elektronik (BSSN) — PSrE instansi pemerintah.
- **PDF/A:** standar format PDF untuk pengarsipan jangka panjang.
- **Sanctum:** mekanisme autentikasi token untuk API Laravel.
- **Inertia.js:** jembatan yang menghubungkan controller Laravel dengan halaman Vue tanpa membangun API terpisah.
- **Append-only:** catatan yang hanya bisa ditambah, tidak diubah/dihapus — menjaga integritas jejak audit.

---

## 17. Lampiran — Referensi Regulasi
- UU No. 11 Tahun 2008 jo. UU No. 19 Tahun 2016 tentang Informasi dan Transaksi Elektronik (ITE).
- PP No. 71 Tahun 2019 tentang Penyelenggaraan Sistem dan Transaksi Elektronik.
- UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP).

*Referensi disediakan untuk konteks kepatuhan; pengkajian hukum final sebaiknya dilakukan bersama unit hukum instansi.*
