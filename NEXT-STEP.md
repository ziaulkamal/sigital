# NEXT-STEP — Rencana Lanjutan SIGITAL

Dokumen perencanaan untuk evolusi dari aplikasi single-instansi (MVP) menjadi **platform multi-organisasi** dengan kontrol akses berbasis peran, alur persetujuan, manajemen akun, dan keamanan lanjutan.

> Status: **draft rencana** (belum diimplementasi). Kode/identifier ditulis dalam English; penjelasan dalam Bahasa Indonesia.
> Basis: kondisi kode saat ini (M1–M5 + kontainer selesai). Lihat [PRD.md](PRD.md).

---

## 1. Ringkasan Kebutuhan

| # | Kebutuhan | Inti |
|---|---|---|
| K1 | **Data per-Role/Organisasi** | Tiap **Dinas** mengelola sertifikat yang ia keluarkan sendiri. **SuperAdmin** melihat semua. |
| K2 | **Role Komunitas** | Komunitas (mis. **RelawanTIK**) punya segmen sendiri untuk menerbitkan sertifikat, terpisah dari Dinas. |
| K3 | **Penanda tangan per-user** | Tiap user (kecuali SuperAdmin) mengelola penanda tangannya sendiri. Saat simpan dengan **nama serupa**, sistem menanyakan apakah yang dimaksud adalah data yang sudah ada. |
| K4 | **Approval SuperAdmin** | Izin pemakaian (akun/akses) wajib disetujui SuperAdmin sebelum aktif. |
| K5 | **Manajemen akun** | Ganti password, set nama, set email. |
| K6 | **2FA** | Modul autentikasi dua faktor. |
| K7 | **Upload template** | Tiap user menyiapkan & mengunggah template sertifikatnya sendiri. |
| K8 | **Logo Kop** | Tiap user/organisasi menyiapkan **logo kop** yang tampil pada sertifikat yang diterbitkannya. |
| K9 | **Syarat registrasi bersyarat** | User mendaftar sebagai **panitia** organisasi & menunggu approval SuperAdmin. **Dinas wajib** melampirkan **surat rekomendasi penunjukan**; **komunitas tidak wajib**. |
| K10 | **Kolaborasi acara (join)** | User dapat **bergabung** ke sebuah acara setelah **disetujui pembuat acara** (atau via SuperAdmin). SuperAdmin bebas mengakses acara mana pun. |

---

## 2. Konsep Arsitektur Baru

### 2.1 Entitas "Organization" (multi-tenant)

Memperkenalkan **`Organization`** sebagai tenant. Setiap data operasional (event, signatory, template, certificate) **dimiliki satu organisasi**.

- `Organization.type`: `dinas` | `komunitas` (ekstensibel).
- Contoh: `DISKOMINFO` (dinas), `RELAWANTIK` (komunitas).
- **SuperAdmin** = user global tanpa `organization_id`, melihat & mengelola lintas organisasi.

### 2.2 Strategi peran: spatie/laravel-permission **Teams**

Aktifkan fitur **teams** pada `config/permission.php` (`'teams' => true`), dengan `team_id = organization_id`. Dampak:

- Satu user bisa menjadi **Admin** di organisasinya sendiri tanpa bocor ke organisasi lain.
- **SuperAdmin** memakai peran global (team_id null).
- Tidak perlu menulis sistem peran sendiri — cukup set `team_id` aktif per-request dari `auth()->user()->organization_id`.

> Catatan migrasi: mengaktifkan teams menambah kolom `team_id` ke tabel pivot permission. Perlu re-migrate tabel permission + re-seed peran (lihat [database/seeders/RolePermissionSeeder.php](database/seeders/RolePermissionSeeder.php)).

### 2.3 Data scoping otomatis

Buat trait **`BelongsToOrganization`** (global scope Eloquent):

- Otomatis memfilter query `WHERE organization_id = {current}` untuk user ber-organisasi.
- **Bypass** untuk SuperAdmin (lihat semua).
- Saat `create`, otomatis mengisi `organization_id` dari user aktif.
- Diterapkan pada: `Event`, `Signatory`, `Template`, `Certificate` (dan `AuditLog` untuk tampilan per-org).

```
app/Models/Concerns/BelongsToOrganization.php   // global scope + auto-fill
app/Support/Tenancy.php                          // resolve org aktif + set team_id spatie
```

### 2.4 Matriks Peran & Izin (target)

| Kemampuan | SuperAdmin | Admin (org) | Operator (org) |
|---|:--:|:--:|:--:|
| Lihat **semua** organisasi | ✅ | — | — |
| Kelola Organization (CRUD) | ✅ | — | — |
| **Approve** user & akses | ✅ | — | — |
| Kelola user dalam org | ✅ | ✅ | — |
| Kelola signatory | ✅* | ✅ | ✅ |
| Kelola template (upload) | ✅* | ✅ | ✅ |
| Kelola event & peserta | ✅* | ✅ | ✅ |
| Terbitkan & distribusi sertifikat | ✅* | ✅ | ✅ |
| Lihat arsip & audit | per-org/global | per-org | per-org |

`*` SuperAdmin secara teknis bisa, tetapi **pengelolaan signatory/template dilakukan masing-masing user** (K3) — SuperAdmin fokus tata kelola lintas-org, bukan operasional harian.

---

## 3. Perubahan Model Data

### 3.1 Tabel baru

```
organizations
  id, nama, kode (unik), type (dinas|komunitas),
  logo_path, kop_path (header kop sertifikat), alamat,
  recommendation_letter_path (wajib bila type=dinas),
  is_active, requested_by (nullable), approved_at, timestamps
```

> **Logo Kop (K8):** `logo_path` (logo instansi/komunitas) + `kop_path` (kop surat opsional) disiapkan masing-masing user/organisasi dan **disisipkan otomatis di kepala sertifikat** saat penerbitan.
> **Surat rekomendasi (K9):** `recommendation_letter_path` diisi saat registrasi **hanya untuk dinas** dan menjadi bahan verifikasi SuperAdmin saat approve.

```
event_members            // kolaborasi acara (K10)
  id, event_id, user_id,
  role (owner|collaborator),
  status (pending|approved|rejected),
  requested_at, approved_by, approved_at, timestamps
  UNIQUE(event_id, user_id)
```

> **Kolaborasi acara (K10):** pembuat acara otomatis menjadi `owner`. User lain mengajukan `join` → `pending` → **owner acara atau SuperAdmin** menyetujui → `collaborator` mendapat akses ke acara itu (peserta, penerbitan, distribusi sesuai izin perannya).

### 3.2 Perubahan kolom

| Tabel | Tambahan |
|---|---|
| `users` | `organization_id` (nullable → SuperAdmin), `status` (pending\|approved\|rejected\|suspended), `approved_by`, `approved_at` |
| `signatories` | `organization_id`, `created_by` |
| `events` | `organization_id`, `created_by` |
| `templates` | `organization_id` (nullable=global), `uploaded_by`, `file_path`, `mime`, `is_global` |
| `certificates` | `organization_id` (denormalisasi untuk scoping & index cepat) |
| `audit_logs` | `organization_id` (nullable) |
| `people` | **tetap global** — visibilitas dibatasi via registrasi (lihat §8 Pertanyaan Terbuka) |

### 3.3 Dampak pada penomoran

[app/Services/Certificate/CertificateNumberGenerator.php](app/Services/Certificate/CertificateNumberGenerator.php) saat ini memakai `config('sigital.instansi_kode')`. **Diubah** agar memakai `$event->organization->kode` sehingga tiap organisasi punya prefix nomor sendiri (mis. `RELAWANTIK/...` vs `DISKOMINFO/...`).

---

## 4. Fase Implementasi

Disusun agar tiap fase **dapat dirilis berdiri sendiri** dan risiko tertinggi (tenancy + scoping) divalidasi lebih dulu.

### Fase P1 — Fondasi Multi-Tenant & Scoping  `[L]`
**Tujuan:** semua data terpisah per organisasi; SuperAdmin lihat semua.

- Backend:
  - Migrasi: `organizations` + kolom `organization_id` di entitas terkait.
  - Model `Organization` + relasi (`hasMany` event/signatory/template/certificate/user).
  - Trait `BelongsToOrganization` (global scope + auto-fill).
  - Aktifkan spatie **teams**; set team aktif via middleware `SetCurrentOrganization`.
  - Update `CertificateNumberGenerator` → pakai kode organisasi.
  - Seeder: organisasi contoh (`DISKOMINFO` dinas, `RELAWANTIK` komunitas) + assign user lama.
- Frontend:
  - Badge nama organisasi di topbar; SuperAdmin mendapat **switcher "Semua / pilih organisasi"**.
- **Acceptance:** Operator dinas A tidak melihat data dinas B; SuperAdmin melihat gabungan; nomor sertifikat memakai kode org.
- **Migrasi data lama:** seluruh data MVP di-assign ke satu organisasi default.

### Fase P2 — Alur Approval & Onboarding  `[M]`
**Tujuan:** user mendaftar sebagai **panitia** organisasi & menunggu approval SuperAdmin (K4, K9).

- Backend:
  - `users.status` default `pending`; middleware `EnsureApproved` memblokir login non-approved.
  - Registrasi panitia: pilih **tipe** (dinas/komunitas) + organisasi (atau **ajukan organisasi baru**).
  - **Validasi bersyarat (K9):** bila `type = dinas` → **wajib** unggah `recommendation_letter` (PDF/gambar); bila `type = komunitas` → tidak wajib. Disimpan ke `organizations.recommendation_letter_path` (atau tabel request).
  - `ApprovalService`: approve/reject → set org + role, kirim email, catat `AuditLog` (`user.approved`/`rejected`).
- Frontend:
  - Halaman registrasi diperluas: tipe organisasi, pilih/ajukan organisasi, peran, **upload surat rekomendasi (muncul hanya bila dinas)**.
  - Halaman tunggu "menunggu persetujuan".
  - **Panel SuperAdmin → Persetujuan**: daftar pending + **pratinjau/unduh surat rekomendasi**, aksi approve/reject + alasan.
- **Acceptance:** dinas tanpa surat rekomendasi ditolak di validasi; komunitas bisa daftar tanpa surat; user baru tak bisa masuk sebelum di-approve; approval mengaktifkan akun dengan org & peran yang benar.

### Fase P3 — Signatory per-user + Konfirmasi Duplikat  `[M]`
**Tujuan:** tiap user kelola signatory; nama serupa dikonfirmasi (K3).

- Backend:
  - `signatories` ter-scope organisasi + `created_by`.
  - Endpoint `POST /signatories/check` → normalisasi nama (lowercase, trim, hapus gelar) + cari kandidat mirip dalam org (mis. `LIKE` / similarity). Kembalikan daftar kandidat.
  - `store` menerima `confirm` (`use_existing:{id}` atau `create_new:true`). Tanpa konfirmasi & ada kandidat → tolak dengan 409 + kandidat.
- Frontend:
  - Saat simpan, bila ada kandidat → **modal**: "Nama serupa sudah ada — apakah maksud Anda salah satu ini?" → pilih existing **atau** "Tetap buat baru".
- **Acceptance:** menambah "Dr. Budi" saat sudah ada "Budi" memunculkan konfirmasi; memilih existing tidak membuat duplikat.
- Pola UI mengikuti alur **pratinjau impor CSV** yang sudah ada di [resources/js/Pages/Events/Show.vue](resources/js/Pages/Events/Show.vue).

### Fase P4 — Manajemen Akun (Profil)  `[S]`
**Tujuan:** ganti nama, email, password (K5).

- Backend: `ProfileController` (update nama/email + verifikasi ulang email opsional), `PasswordController` (update password dengan konfirmasi password lama).
- Frontend: halaman **Pengaturan → Akun** (form profil + form ganti password dengan indikator kekuatan).
- **Acceptance:** perubahan tersimpan + tercatat di `AuditLog` (`user.profile_updated`, `user.password_changed`).

### Fase P5 — Modul 2FA  `[M]`
**Tujuan:** autentikasi dua faktor (K6).

- **Rekomendasi:** integrasi **Laravel Fortify** (TOTP + recovery codes + dukungan Inertia) — sekaligus menstandarkan update password/2FA. Alternatif tanpa Fortify: `pragmarx/google2fa-qrcode` + recovery codes manual.
- Backend: enable/confirm/disable 2FA, tantangan kode saat login, recovery codes sekali pakai.
- Frontend: **Pengaturan → Keamanan**: tampilkan QR, input verifikasi, daftar recovery codes; langkah tantangan 2FA di alur login.
- **Acceptance:** user dengan 2FA aktif wajib memasukkan kode TOTP saat login; recovery code berfungsi sekali pakai.

### Fase P6 — Template & Branding (Logo Kop) per-user  `[M]`
**Tujuan:** tiap user menyiapkan template sendiri (K7) + logo kop organisasinya (K8).

- Backend:
  - `templates` ter-scope org + `uploaded_by` + `file_path`.
  - Unggah **gambar latar** (PNG/JPG/PDF) sebagai template; field teks (nama/acara/nomor/QR/TTD) ditempatkan via `posisi_field` (JSON). **Tidak menerima HTML mentah** (hindari injeksi).
  - **Branding (K8):** unggah `logo_path` + `kop_path` per organisasi (validasi gambar, maks ukuran). Disimpan via Laravel Filesystem ter-scope org.
  - Renderer [app/Services/Certificate/CertificatePdfRenderer.php](app/Services/Certificate/CertificatePdfRenderer.php) mendukung latar dari `file_path`, **menyisipkan logo/kop organisasi di kepala sertifikat**, + overlay field sesuai koordinat.
- Frontend:
  - **Halaman Template** (daftar + upload + pratinjau); pemilihan template di form acara dibatasi milik org.
  - **Pengaturan → Branding**: unggah logo & kop + pratinjau posisi di sertifikat.
- **Acceptance:** user mengunggah template + logo kop, memakainya di acara, PDF terbit dengan latar + logo kop organisasi tersebut.
- **Lanjutan (v-next):** perancang visual drag-drop posisi field & logo (mengganti input koordinat manual).

### Fase P7 — Kolaborasi Acara (Join & Approval)  `[M]`
**Tujuan:** user dapat bergabung ke acara milik orang lain dengan persetujuan pembuat acara / SuperAdmin (K10).

- Backend:
  - Tabel `event_members`; pembuat acara otomatis `owner` (status `approved`).
  - `POST /events/{event}/join` → buat permintaan `pending` (catat `AuditLog` `event.join_requested`).
  - `POST /events/{event}/members/{member}/approve|reject` → **hanya owner acara atau SuperAdmin** (catat `event.join_approved`/`rejected`).
  - **Akses acara** = SuperAdmin **atau** member `approved` (owner/collaborator). `EventPolicy` digabung dengan scoping organisasi (lihat §5).
- Frontend:
  - Tombol **"Gabung acara"** + daftar permintaan masuk di [Events/Show](resources/js/Pages/Events/Show.vue) untuk owner (approve/reject).
  - Penanda peran member (owner/collaborator) pada detail acara.
- **Acceptance:** user non-member tak bisa akses acara; setelah join di-approve owner/SuperAdmin, ia bisa mengelola acara sesuai izin perannya; SuperAdmin selalu bisa akses.

---

## 5. Keamanan & Otorisasi

- **Policies** untuk tiap entitas (`EventPolicy`, `SignatoryPolicy`, `CertificatePolicy`, `TemplatePolicy`) memastikan akses hanya pada data organisasi sendiri; SuperAdmin `before()` → allow.
- Global scope mencegah kebocoran **read**; Policy mencegah akses **objek lintas-org** lewat ID langsung (IDOR).
- **Akses acara (K10)** = SuperAdmin **atau** organisasi pemilik (sesuai scoping) **atau** `event_members` ber-status `approved`. Bila join lintas-org diizinkan (lihat §8), global scope acara perlu dilonggarkan khusus untuk member yang di-approve (union: org-scope ∪ membership).
- Verifikasi publik QR **tetap lintas-org** (publik) — tidak terpengaruh scoping (lihat [app/Http/Controllers/Api/V1/VerificationController.php](app/Http/Controllers/Api/V1/VerificationController.php)).
- Tambah aksi audit baru: `organization.created`, `user.approved/rejected`, `signatory.linked`, `template.uploaded`, `auth.2fa_enabled`, dst.

---

## 6. Dampak ke Kode yang Sudah Ada

| Berkas | Perubahan |
|---|---|
| [config/permission.php](config/permission.php) | aktifkan `teams` |
| [database/seeders/RolePermissionSeeder.php](database/seeders/RolePermissionSeeder.php) | tambah peran `SuperAdmin`; peran org-scoped |
| [app/Models/User.php](app/Models/User.php) | relasi `organization()`, status, 2FA |
| [app/Http/Middleware/HandleInertiaRequests.php](app/Http/Middleware/HandleInertiaRequests.php) | share `organization` + flag SuperAdmin |
| [app/Services/Certificate/CertificateNumberGenerator.php](app/Services/Certificate/CertificateNumberGenerator.php) | kode dari organisasi |
| [routes/web.php](routes/web.php) | grup route SuperAdmin (organizations, approvals) + profil/keamanan |
| [resources/js/data/navGroups.ts](resources/js/data/navGroups.ts) | menu SuperAdmin + Template + Akun/Keamanan (role-aware sudah ada) |

---

## 7. Urutan & Dependensi

```
P1 (tenancy)  ──► P2 (approval)  ──► P3 (signatory)
       │                 │
       │                 └────────► P7 (kolaborasi acara)
       └──► P6 (template + logo kop, butuh org)
P4 (akun) ─ independen, bisa paralel
P5 (2FA)  ─ setelah/paralel P4
```

P1 adalah prasyarat utama (semua scoping bergantung padanya). P7 butuh P1 (acara ter-scope) + P2 (user sudah ter-approve). P4 & P5 independen dan bisa dikerjakan kapan saja.

---

## 8. Pertanyaan Terbuka (perlu keputusan sebelum mulai)

1. **Person lintas-organisasi:** apakah satu orang yang sama boleh "dibagikan" antar organisasi, atau tiap org punya registry peserta terpisah? (Rekomendasi: Person tetap global, tapi **visibilitas & PII dibatasi** lewat registrasi org.)
2. **Pengajuan organisasi baru:** saat registrasi, apakah user boleh mengajukan organisasi baru (lalu SuperAdmin approve org + user sekaligus), atau organisasi harus dibuat SuperAdmin lebih dulu?
3. **Self-registration vs undangan:** akun dibuat via registrasi mandiri (lalu approve) atau via **undangan** oleh Admin org / SuperAdmin?
4. **Email verifikasi** saat ganti email: wajib verifikasi ulang atau cukup audit log?
5. **2FA wajib?** apakah 2FA opsional per-user, atau wajib untuk peran tertentu (mis. Admin/SuperAdmin)?
6. **Format template:** cukup unggah gambar latar + posisi field, atau perlu beberapa preset tata letak bawaan?
7. **Join acara lintas-organisasi (K10):** apakah kolaborasi acara boleh **lintas organisasi** (mis. panitia komunitas membantu acara dinas), atau **hanya dalam organisasi yang sama**? Ini menentukan apakah global scope acara perlu dilonggarkan.
8. **Penemuan acara untuk join:** user menemukan acara via **kode/tautan undangan** dari owner, atau ada **daftar acara terbuka** yang bisa diminta untuk bergabung?

---

## 9. Estimasi Kasar

| Fase | Ukuran | Risiko |
|---|---|---|
| P1 Tenancy & scoping | L | Tinggi (fondasi, migrasi data) |
| P2 Approval | M | Sedang |
| P3 Signatory + duplikat | M | Sedang |
| P4 Akun | S | Rendah |
| P5 2FA | M | Sedang |
| P6 Template + Logo Kop | M | Sedang (render PDF) |
| P7 Kolaborasi acara | M | Sedang (rekonsiliasi scoping) |

`S ≈ <1 hari · M ≈ 1–3 hari · L ≈ 3–5 hari` (indikatif, 1 dev).

---

## 10. Definition of Done (keseluruhan)

- [ ] Data benar-benar terisolasi per organisasi; SuperAdmin lihat semua.
- [ ] Akun baru tidak aktif sebelum di-approve SuperAdmin.
- [ ] Komunitas (RelawanTIK) menerbitkan sertifikat di segmennya sendiri.
- [ ] Signatory dikelola per-user dengan konfirmasi nama duplikat.
- [ ] User dapat ganti nama/email/password & mengaktifkan 2FA.
- [ ] User dapat mengunggah & memakai template sendiri.
- [ ] User dapat menyiapkan **logo kop** dan logo tersebut tampil pada sertifikat yang diterbitkannya.
- [ ] Registrasi **dinas wajib** surat rekomendasi; **komunitas tidak** — diverifikasi SuperAdmin saat approve.
- [ ] User dapat **join acara** dan owner/SuperAdmin menyetujui; non-member tak punya akses.
- [ ] Semua aksi sensitif tercatat di `AuditLog` (per organisasi).
- [ ] Uji otorisasi lintas-org (IDOR) lulus.
