# Rencana: Sistem Peran SuperAdmin, Credit, Paket Enterprise & Log Login GeoIP

## Context

Aplikasi SIGITAL akan dimonetisasi untuk mendapatkan **PAD (Penghasilan Asli Daerah)**. Saat ini setiap user terapprove bebas membuat acara dan template tanpa batas. Perlu ditambahkan:

1. **Penetapan peran oleh SuperAdmin** (Admin / Non-Admin) — peran `Admin` & `Operator` sudah ada di [RolePermissionSeeder](../database/seeders/RolePermissionSeeder.php), tapi belum ada UI bagi SuperAdmin untuk mengubah peran user. "Non-Admin" = `Operator`.
2. **Sistem Credit** — buat acara = **50 credit**, buat template = **10 credit**, 1 credit = Rp1.000. Saldo **per-user**.
3. **Paket Enterprise** — bebas credit selama 1 tahun, ditetapkan SuperAdmin, **wajib 2FA aktif**. Tanpa 2FA aktif → benefit bebas-credit diblokir (tetap kena potong credit biasa / diblokir bila saldo kurang).
4. **Topup credit dalam platform** — alur **manual + konfirmasi SuperAdmin** (user kirim permintaan + bukti, SuperAdmin verifikasi & tambah saldo). Dirancang agar gateway mudah ditambah nanti.
5. **Log login dengan IP + (slot) negara** — catat IP & user-agent setiap login; kolom `country` disiapkan untuk diisi belakangan.

### Keputusan yang sudah disepakati (mengikat)
- Pembayaran: **manual + konfirmasi SuperAdmin**, struktur siap untuk gateway nanti.
- Migrasi user lama: **grandfather** — beri saldo awal **60 credit** (cukup satu acara, tetap perlu topup); acara/template lama tidak ditarik.
- **SuperAdmin dapat menyesuaikan credit user dua arah** — menambah (top up) maupun mengurangi credit secara langsung, terpisah dari alur permintaan topup user.
- Lingkup saldo: **per-user**.
- Enterprise: **ditetapkan SuperAdmin**, wajib 2FA aktif lebih dulu.
- Aturan 2FA Enterprise: **blokir benefit bebas-credit** bila 2FA tidak aktif (tidak menghapus paket).
- GeoIP: **simpan IP + user-agent dulu**, kolom negara diisi belakangan.

### Pola eksisting yang dipakai ulang
- **Service + AuditLogger**: semua mutasi via service, audit via [AuditLogger::log()](../app/Services/AuditLogger.php) (sudah mencatat `ip`).
- **Spatie roles + teams**: assign peran dalam konteks tim (lihat `DatabaseSeeder`); SuperAdmin via `Gate::before`. Set `PermissionRegistrar::setPermissionsTeamId($org)` sebelum `syncRoles`.
- **Flash pattern** untuk redirect + pesan (`back()->with('success', ...)`), dan share via [HandleInertiaRequests](../app/Http/Middleware/HandleInertiaRequests.php).
- **Halaman Users**: [UserController](../app/Http/Controllers/UserController.php) + `resources/js/Pages/Users/Index.vue` sudah ada (tombol ban/unban) — tambah tombol ubah peran & set Enterprise di sana.
- **Throttle/audit login** sudah ada di [LoginRequest](../app/Http/Requests/Auth/LoginRequest.php) & [AuthenticatedSessionController](../app/Http/Controllers/Auth/AuthenticatedSessionController.php).

---

## Bagian 1 — Penetapan Peran oleh SuperAdmin (Admin / Non-Admin)

### Backend
- **`UserController::updateRole`** (method baru) + route `POST users/{user}/role` di grup `permission:manage-users`, otorisasi `abort_unless($actor->isSuperAdmin(), 403)` (hanya SuperAdmin), dan `abort_if` target SuperAdmin / diri sendiri.
  - Validasi: `role in ['Admin','Operator']` (label UI: "Admin" / "Non-Admin").
  - Set team context ke `organization_id` user target, lalu `$user->syncRoles([$role])`. Reset cache permission.
  - Audit `user.role_changed` dengan detail `{from, to}`.
- Tambahkan ke output `UserController::index` map: sudah ada `roles`. Tidak perlu perubahan struktur.

### Frontend
- `resources/js/Pages/Users/Index.vue`: tombol/menu "Ubah Peran" (hanya tampil untuk SuperAdmin, non-SuperAdmin target) → modal pilih Admin / Non-Admin → `router.post(route('users.role', user.id), {role})`.

---

## Bagian 2 — Sistem Credit (per-user)

### Skema DB (migrasi baru)
- **`users` + kolom**: `credit_balance` (integer, default 0).
- **Tabel `credit_transactions`** (ledger, append-only):
  - `id`, `user_id` (FK), `type` (enum: `topup`, `consume`, `grant`, `refund`), `amount` (integer, +/-), `balance_after` (integer), `reference_type`/`reference_id` (nullable morph — Event/Template), `description`, `created_by` (nullable, actor), `timestamps`.
- **Tabel `topup_requests`** (alur manual, siap-gateway):
  - `id`, `user_id`, `amount_credit` (int), `amount_rupiah` (int = amount_credit * 1000), `status` (enum: `pending`, `approved`, `rejected`), `proof_path` (nullable, bukti transfer di disk privat), `note`, `payment_provider` (nullable, untuk gateway nanti), `external_ref` (nullable), `reviewed_by` (nullable), `reviewed_at` (nullable), `reject_reason` (nullable), `timestamps`.

### Konstanta biaya
- Tambah ke `config/sigital.php` blok `credit`: `cost_event => 50`, `cost_template => 10`, `rupiah_per_credit => 1000`, `signup_grant => 60` (grandfather/awal).

### Service: `app/Services/CreditService.php` (baru)
- `balance(User): int`
- `hasEnough(User, int): bool`
- `consume(User $user, int $amount, Model $reference, string $desc): void` — kurangi saldo, tulis ledger `consume`, audit `credit.consumed`. Lempar `InsufficientCreditException` bila kurang. Bungkus `DB::transaction` + `lockForUpdate` pada baris user (cegah race).
- `grant(User, int, string $desc, ?int $actorId): void` — tambah saldo, ledger `grant`/`topup`, audit.
- **`adjust(User $user, int $delta, string $reason, int $actorId): void`** — penyesuaian manual SuperAdmin dua arah. `delta` boleh positif (tambah) atau negatif (kurangi). Bila negatif, **clamp ke 0** (saldo tak boleh minus; catat amount aktual yang terpotong). Tulis ledger `type=grant` (delta>0) / `type=refund` atau `consume` (delta<0) dengan `created_by=actorId`, audit `credit.adjusted` `{delta, reason}`. Dibungkus `DB::transaction`+`lockForUpdate`.
- **`isCreditExempt(User): bool`** — true bila user Enterprise **aktif & 2FA aktif** (lihat Bagian 3). Dipakai untuk melewati pemotongan.

### Exception: `app/Exceptions/InsufficientCreditException.php` (baru)
- Render ke `back()->with('error', 'Credit tidak cukup. Diperlukan X credit untuk ...')` (atau via handler), agar UX konsisten dengan flash pattern.

### Integrasi pemotongan
- **Buat acara** — di [EventService::create()](../app/Services/EventService.php): sebelum/sesudah `save()`, panggil `CreditService`. Pola: cek `isCreditExempt` → jika tidak, `consume(user, cost_event, $event, 'Buat acara: '.$nama)` di dalam transaksi yang sama. Bila saldo kurang, batalkan (transaksi rollback) & lempar exception sebelum acara dibuat. **Pengecekan saldo harus mendahului pembuatan** agar tak ada acara yatim.
  - Catatan: hanya potong pada **create**, bukan update.
- **Buat template** — di [TemplateService::create()](../app/Services/TemplateService.php): pola sama, `cost_template`, reference = `$template`. Hanya pada create.

> **PENTING — grandfather/scoping**: Pemotongan hanya berlaku saat **create baru**. Acara/template yang sudah ada tidak disentuh. Migrasi memberi `signup_grant` ke semua user lama (data backfill di migrasi: `UPDATE users SET credit_balance = 60`).

### Penyesuaian credit langsung oleh SuperAdmin (dua arah)
- **`UserController::adjustCredit`** + route `POST users/{user}/credit` di grup `permission:manage-users`, guard `abort_unless($actor->isSuperAdmin(), 403)`.
  - Validasi: `delta` (integer, boleh negatif, `!= 0`), `reason` (required string, min 3) — alasan masuk audit & ledger.
  - Panggil `CreditService::adjust($user, $delta, $reason, $actor->id)`.
  - Flash `success` "Credit {user} disesuaikan +N / -N. Saldo: M".
- Frontend Users/Index.vue: tombol "Sesuaikan Credit" (SuperAdmin) → modal input jumlah (boleh +/-) + alasan. Tampilkan saldo terkini per user di tabel.
- Catatan: ini **berbeda** dari approve `topup_requests` (alur permintaan user). Keduanya bermuara ke ledger `credit_transactions` lewat `CreditService`.

### Frontend (preflight UX)
- `resources/js/Pages/Events/Form.vue` & `Templates/Index.vue`: tampilkan biaya ("Membuat acara memerlukan 50 credit. Saldo Anda: N") dari shared prop saldo. Tetap andalkan validasi server sebagai sumber kebenaran.

---

## Bagian 3 — Paket Enterprise

### Skema DB (migrasi, gabung dgn migrasi credit atau terpisah)
- **`users` + kolom**: `plan` (enum `free`|`enterprise`, default `free`), `enterprise_started_at` (nullable datetime), `enterprise_expires_at` (nullable datetime).

### Model `User`
- `isEnterprise(): bool` — `plan === 'enterprise' && enterprise_expires_at?->isFuture()`.
- `isEnterpriseActive(): bool` — `isEnterprise() && hasTwoFactorEnabled()` (benefit bebas-credit hanya saat 2FA aktif).
- Cast tanggal Enterprise → `datetime`.

### Penetapan oleh SuperAdmin
- **`UserController::setPlan`** + route `POST users/{user}/plan` (SuperAdmin only).
  - Aksi `activate`: **wajib** `abort_if(! $user->hasTwoFactorEnabled(), 422, '...wajib 2FA aktif...')`. Set `plan=enterprise`, `enterprise_started_at=now()`, `enterprise_expires_at=now()->addYear()`. Audit `user.enterprise_activated`.
  - Aksi `deactivate`: kembalikan ke `free`. Audit `user.enterprise_deactivated`.
- Frontend Users/Index.vue: badge paket + tombol "Set Enterprise / Cabut" (SuperAdmin). Disable tombol activate bila user belum 2FA, dengan tooltip alasan.

### Benefit bebas-credit
- `CreditService::isCreditExempt(User)` mengembalikan `true` untuk Enterprise aktif (2FA on) **atau** SuperAdmin. Saat exempt, `consume` dilewati (skip untuk kesederhanaan, cukup audit `credit.exempt`).
- Bila Enterprise tapi 2FA mati → tidak exempt → tetap kena potong / diblokir bila saldo kurang (sesuai keputusan "blokir benefit").

---

## Bagian 4 — Topup Credit dalam Platform (manual + konfirmasi)

### User mengajukan topup
- **`TopupController`** (baru):
  - `index` (`GET /credits`): halaman saldo + riwayat `credit_transactions` user + daftar `topup_requests` miliknya.
  - `store` (`POST /credits/topup`): `StoreTopupRequest` (amount_credit min, opsional upload `proof`). Buat `topup_request` status `pending`, simpan bukti di disk privat (`local/topup-proofs`). Notifikasi SuperAdmin (kelas `App\Notifications\TopupRequested`, channel database — mengikuti pola notifikasi eksisting). Audit `credit.topup_requested`.
- Route di grup `['auth','approved','profile.complete']` (semua user), tanpa permission khusus.

### SuperAdmin verifikasi
- **`TopupController::approve` / `reject`** + route `POST credits/topup/{request}/approve|reject`, grup `permission:approve-users` (SuperAdmin) atau guard `isSuperAdmin()`.
  - approve → `CreditService::grant(user, amount_credit, 'Topup #id', actorId)` + set request `approved`, `reviewed_by`, `reviewed_at`. Notifikasi user. Audit `credit.topup_approved`.
  - reject → set `rejected` + `reject_reason`, notifikasi user. Audit `credit.topup_rejected`.
- Halaman admin: **Rekomendasi** halaman baru `resources/js/Pages/Credits/Requests.vue` (daftar semua topup pending lintas-user) + lihat bukti.

### Frontend
- `resources/js/Pages/Credits/Index.vue` (user): kartu saldo, form topup (jumlah credit → tampilkan Rp = ×1000), upload bukti, riwayat transaksi & status request.
- Nav: item "Credit" untuk semua user; "Permintaan Topup" untuk SuperAdmin.

---

## Bagian 5 — Log Login dengan IP + GeoIP (slot)

### Skema DB
- **Tabel `login_logs`** (baru): `id`, `user_id` (FK), `ip` (string), `user_agent` (string, nullable), `country` (string, nullable — diisi belakangan), `country_code` (string, nullable), `logged_at` (datetime), `timestamps`.

### Pencatatan
- Di [AuthenticatedSessionController::store()](../app/Http/Controllers/Auth/AuthenticatedSessionController.php) pada jalur login sukses (setelah `session()->regenerate()`, sebelum redirect dashboard) **dan** setelah verifikasi 2FA sukses di [TwoFactorChallengeController](../app/Http/Controllers/Auth/TwoFactorChallengeController.php) — catat `login_logs` (ip via `$request->ip()`, user_agent via `$request->userAgent()`).
- Buat helper kecil `LoginLogService::record(User, Request)` agar dipanggil dari dua tempat tanpa duplikasi. Kolom `country` dibiarkan null (slot untuk GeoIP nanti — sisakan TODO + method `enrichCountry()` stub).

### Tampilan untuk SuperAdmin
- **Rekomendasi**: tampilkan "Login terakhir (IP)" di Users/Index, dan section riwayat login di drawer/modal detail user (SuperAdmin). Data via `UserController` (eager load latest login).

---

## File yang dibuat / diubah (ringkas)

**Migrasi baru** (`database/migrations/`):
- `..._add_credit_and_plan_to_users.php` — `credit_balance`, `plan`, `enterprise_started_at`, `enterprise_expires_at` + backfill grant 60.
- `..._create_credit_transactions_table.php`
- `..._create_topup_requests_table.php`
- `..._create_login_logs_table.php`

**Model**: `CreditTransaction`, `TopupRequest`, `LoginLog` (baru); `User` (relasi + `isEnterprise*`, casts, `credit_balance` non-fillable).

**Service**: `CreditService`, `LoginLogService` (baru); ubah `EventService::create`, `TemplateService::create`.

**Controller**: `TopupController` (baru); ubah `UserController` (updateRole, setPlan, adjustCredit, riwayat login), `AuthenticatedSessionController`, `TwoFactorChallengeController`.

**Request**: `StoreTopupRequest` (baru); validasi role/plan/credit inline atau request kecil.

**Exception**: `InsufficientCreditException` (baru) + registrasi render.

**Notifications**: `TopupRequested`, `TopupReviewed` (baru, channel database — pola eksisting).

**Config**: `config/sigital.php` blok `credit`.

**Routes** (`routes/web.php`): role/plan/credit-adjust/topup user, topup admin, (login log dibaca via UserController).

**Frontend** (`resources/js/`): `Pages/Credits/Index.vue`, `Pages/Credits/Requests.vue`, `Pages/Users/LoginHistory.vue` (atau modal); ubah `Pages/Users/Index.vue`, `Events/Form.vue`, `Templates/Index.vue`, nav di `BaseLayout`/sidebar, share saldo di `HandleInertiaRequests` (`auth.user.credit_balance`, `plan`, `is_enterprise_active`).

**Seeder**: `RolePermissionSeeder` tak perlu permission baru (pakai `manage-users`/`approve-users` eksisting). Pakai `isSuperAdmin()` guard di controller.

---

## Verifikasi (end-to-end)

Toolchain: PowerShell + Herd (`php artisan`), bukan Bash.

1. **Migrasi & seed**: `php artisan migrate` lalu cek user lama dapat `credit_balance=60`.
2. **Tes fitur (PHPUnit)** — tambah test feature mengikuti pola tests eksisting:
   - `CreditConsumptionTest`: user saldo 60 → buat acara (50) sukses, saldo jadi 10; buat acara lagi → gagal `InsufficientCreditException` + flash error, **tidak ada Event baru** di DB.
   - Template (10) serupa.
   - `EnterprisePlanTest`: aktivasi Enterprise tanpa 2FA → 422; dengan 2FA → sukses; user Enterprise+2FA buat acara → saldo tidak berkurang; Enterprise tanpa 2FA → tetap terpotong/diblokir.
   - `RoleAssignmentTest`: SuperAdmin set user jadi Admin/Operator → `assertTrue($user->hasRole('Admin'))` dalam team context; non-SuperAdmin ditolak 403.
   - `TopupFlowTest`: user ajukan topup → request pending; SuperAdmin approve → saldo bertambah + ledger; reject → tidak bertambah.
   - `CreditAdjustTest`: SuperAdmin `adjustCredit(+30)` → saldo +30 & ledger; `adjustCredit(-100)` saat saldo 60 → saldo clamp ke 0 (tak minus); non-SuperAdmin ditolak 403.
   - `LoginLogTest`: login sukses → satu baris `login_logs` dengan ip terisi; login via 2FA juga tercatat.
   - Jalankan: `php artisan test`.
3. **Build frontend**: `npm run build` memastikan halaman Vue baru tervalidasi.
4. **Manual**: login sebagai SuperAdmin → ubah peran user, set Enterprise (cek blokir tanpa 2FA), sesuaikan/approve credit; login sebagai user biasa → buat acara hingga credit habis, ajukan topup. Cek `login_logs` mencatat IP.

## Risiko / catatan
- **Race condition saldo**: pakai `DB::transaction` + `lockForUpdate` di `CreditService` (consume/grant/adjust).
- **Acara yatim**: cek saldo SEBELUM `Event::save()` dalam transaksi; rollback bila kurang.
- **Team context spatie** saat `syncRoles` wajib di-set ke org user target, lalu `forgetCachedPermissions()`.
- **2FA share**: `auth.user.two_factor_enabled` sudah dibagikan; reuse untuk UI gating tombol Enterprise.

---

# Bagian 6 — Marketplace Template (Enthusiast)

## Context

Untuk meningkatkan ekosistem template dan memberikan insentif kepada pembuat desain, SIGITAL akan menyediakan fitur Marketplace Template.

User dapat secara sukarela mengizinkan template miliknya digunakan oleh user lain dengan sistem bagi hasil credit.

### Tujuan

- Meningkatkan jumlah template berkualitas dalam platform.
- Memberikan insentif kepada pembuat template.
- Menjadi sumber pendapatan tambahan platform.
- Memberikan data monetisasi kepada SuperAdmin sebagai bahan evaluasi PAD.

### Keputusan yang disepakati (mengikat)

- Template dapat dipublikasikan ke Marketplace secara opsional.
- User lain dapat menggunakan template Marketplace dengan biaya **15 credit**.
- Dari 15 credit:
  - **10 credit** diberikan kepada pemilik template.
  - **5 credit** menjadi pendapatan sistem/platform.
- Pemilik template dapat mengajukan pencairan credit menjadi rupiah.
- Setiap pencairan dikenakan biaya administrasi **10 credit per transaksi pencairan**.
- Nilai konversi mengikuti sistem existing:
  - **1 credit = Rp1.000**
- User tidak wajib memiliki role khusus untuk menjadi pembuat template.
- Menggunakan pendekatan **flag marketplace** agar tetap kompatibel dengan role Admin dan Operator yang sudah ada.

---

## Bagian 6.1 — Aktivasi Marketplace Creator

### Skema DB

Tambahkan kolom pada tabel `users`:

```php
marketplace_enabled boolean default false
marketplace_joined_at datetime nullable
```

### Aktivasi

- User dapat mengajukan aktivasi sebagai Marketplace Creator.
- Atau diaktifkan langsung oleh SuperAdmin.
- Setelah aktif:
  - dapat mempublikasikan template ke Marketplace.
  - dapat memperoleh royalti penggunaan template.
  - dapat melakukan pencairan saldo hasil kontribusi.

### Model User

Method baru:

```php
public function isMarketplaceCreator(): bool
```

---

## Bagian 6.2 — Marketplace Template

### Skema DB

Tambahkan kolom pada tabel `templates`:

```php
is_marketplace boolean default false
marketplace_price integer default 15
published_at datetime nullable
```

### Aturan

- Hanya template yang ditandai Marketplace yang dapat digunakan user lain.
- Harga awal ditetapkan tetap:
  - 15 credit
- Pemilik template dapat:
  - publish
  - unpublish
- Template milik sendiri tidak dikenakan biaya saat digunakan oleh pemiliknya.

---

## Bagian 6.3 — Transaksi Penggunaan Template

### Tabel Baru

`template_usage_transactions`

```php
id

template_id
owner_user_id
buyer_user_id

price_credit
owner_credit
platform_credit

created_at
updated_at
```

### Tujuan

- histori penggunaan template
- dasar perhitungan royalti
- dasar pencairan
- laporan pendapatan platform

---

## Bagian 6.4 — Alur Credit Marketplace

### Saat User Menggunakan Template Marketplace

Contoh:

User A = pemilik template

User B = pengguna template

### Alur

1. Sistem memeriksa saldo User B.
2. Saldo minimum harus 15 credit.
3. Potong 15 credit dari User B.
4. Tambahkan 10 credit ke User A.
5. Tambahkan 5 credit ke ledger pendapatan platform.
6. Simpan histori penggunaan template.
7. Audit seluruh transaksi.

### Ledger Credit

User pembeli:

```text
consume -15
Menggunakan template marketplace
```

User pemilik:

```text
grant +10
Royalti template marketplace
```

Platform:

```text
platform_revenue +5
```

---

## Bagian 6.5 — Platform Revenue Tracking

### Tabel Baru

`platform_credit_ledger`

```php
id

source_type
source_id

credit_amount

description

created_at
updated_at
```

### Tujuan

Mencatat seluruh credit yang menjadi pendapatan platform.

Contoh:

```text
Template Marketplace
+5 credit
```

---

## Bagian 6.6 — Dashboard SuperAdmin Marketplace

### Statistik Baru

Tambahkan widget dashboard:

#### Total Credit Marketplace

```text
Total Credit Diperjualbelikan
```

#### Pendapatan Platform

```text
Total Credit Pendapatan Sistem
```

#### Royalti Creator

```text
Total Credit Dibagikan ke Creator
```

#### Template Terlaris

Menampilkan:

- Nama Template
- Jumlah Penggunaan
- Total Royalti

### Laporan Keuangan

SuperAdmin dapat melihat:

```text
Total Credit Marketplace

Total Credit Masuk ke Platform

Estimasi Rupiah

Total Royalti Creator
```

Konversi:

```text
credit × Rp1.000
```

---

## Bagian 6.7 — Pencairan Royalti Creator

### Tujuan

Creator dapat mengubah credit hasil kontribusi menjadi rupiah.

### Aturan

Setiap pencairan dikenakan biaya:

```text
10 credit
```

### Contoh

Saldo creator:

```text
100 credit
```

Pencairan:

```text
100 credit

-10 credit biaya administrasi

90 credit dicairkan
```

Nilai rupiah:

```text
90 × Rp1.000

= Rp90.000
```

### Saldo Minimum

Direkomendasikan:

```text
Minimal pencairan:
100 credit
```

(opsional, dapat diubah kemudian melalui config)

---

## Bagian 6.8 — Withdrawal Marketplace Creator

### Tabel Baru

`marketplace_withdrawals`

```php
id

user_id

credit_requested

admin_fee_credit

credit_paid

rupiah_paid

status

requested_at
processed_at

processed_by

notes

created_at
updated_at
```

### Status

```text
pending
scheduled
approved
rejected
paid
```

### Alur

Creator:

1. Mengajukan pencairan.
2. Sistem menghitung biaya admin.
3. Menunggu verifikasi.

SuperAdmin:

1. Meninjau permintaan.
2. Menjadwalkan pencairan.
3. Menandai telah dibayar.

---

## Bagian 6.9 — Jadwal Pencairan

### Tujuan

Memberikan transparansi kepada Creator.

### Data yang Ditampilkan

```text
Pencairan Berikutnya

Status

Tanggal Pencairan

Jumlah Credit

Nilai Rupiah
```

### Kolom Baru

Pada tabel withdrawal:

```php
scheduled_payout_date datetime nullable
```

---

## Bagian 6.10 — Dashboard Marketplace Creator

### Ringkasan

Menampilkan:

```text
Total Template Marketplace

Total Digunakan

Total Credit Diperoleh

Total Sudah Dicairkan

Saldo Tersedia
```

### Histori Kontribusi

Menampilkan:

- Template
- Jumlah penggunaan
- Credit diperoleh

### Histori Pencairan

Menampilkan:

- Tanggal
- Credit
- Nilai Rupiah
- Status

---

## Bagian 6.11 — Service Baru

### TemplateMarketplaceService

Method:

```php
publishTemplate()

unpublishTemplate()

purchaseTemplate()

recordRevenue()
```

### MarketplaceWithdrawalService

Method:

```php
requestWithdrawal()

scheduleWithdrawal()

approveWithdrawal()

rejectWithdrawal()

markAsPaid()
```

---

## Bagian 6.12 — Integrasi dengan Credit Service

Tambahkan type baru pada ledger:

```php
topup
consume
grant
refund

template_purchase
template_royalty

withdraw
withdraw_fee

platform_revenue
```

### Audit

Tambahkan event audit:

```text
marketplace.template_published

marketplace.template_unpublished

marketplace.template_purchased

marketplace.royalty_granted

marketplace.withdraw_requested

marketplace.withdraw_scheduled

marketplace.withdraw_paid
```

---

## Bagian 6.13 — Verifikasi (End-to-End)

### Marketplace

- User saldo 15 credit dapat menggunakan template Marketplace.
- User saldo kurang dari 15 credit ditolak.
- Pemilik template menerima 10 credit.
- Platform menerima 5 credit.
- Histori transaksi tercatat.

### Withdrawal

- Creator mengajukan pencairan.
- Sistem memotong biaya admin 10 credit.
- Nilai rupiah dihitung benar.
- Status pencairan berubah sesuai alur.

### Dashboard

- SuperAdmin dapat melihat total pendapatan Marketplace.
- Creator dapat melihat total kontribusi.
- Creator dapat melihat histori pencairan.
- Creator dapat melihat jadwal pencairan berikutnya.

---

## Risiko / Catatan

- Seluruh mutasi credit Marketplace wajib menggunakan `CreditService` untuk menjaga konsistensi ledger.
- Gunakan `DB::transaction` + `lockForUpdate` pada proses pembelian template untuk mencegah race condition.
- Pendapatan platform harus tercatat terpisah dari saldo user.
- Creator tidak boleh menerima royalti dari penggunaan template miliknya sendiri.
- Penghapusan template Marketplace tidak boleh menghapus histori transaksi yang sudah terjadi.
