# Menjalankan SIGITAL dengan Kontainer (Podman / Docker)

Stack: **app (Octane + RoadRunner)** · **worker (queue)** · **scheduler** · **PostgreSQL 16** · **Redis 7**.

## Prasyarat (Windows + Podman)

```powershell
podman machine start          # nyalakan VM (sekali per sesi boot)
```

## A. Dengan compose (disarankan)

`podman compose` butuh provider. Pasang sekali (Python sudah ada di mesin ini):

```powershell
pip install podman-compose
podman compose version          # verifikasi: podman-compose terdeteksi
```

Jalankan:

```powershell
# APP_KEY sudah diisi di .env.docker. Bila perlu baru: php artisan key:generate --show
podman compose up -d --build
podman exec sertifikat-legal-app_app_1 php artisan db:seed --force   # seed awal (sekali)
podman compose logs -f app
podman compose down             # hentikan (volume tetap tersimpan)
```

> **Troubleshooting** — bila `up` gagal dengan `volume ... already exists` (bug podman-compose
> saat ada sisa run sebelumnya): jalankan `podman compose down` lalu `up` lagi. Jika volume masih
> nyangkut: `podman compose down -v` (hati-hati: menghapus data) atau hapus volume `sertifikat-legal-app_*`
> yang tidak terpakai.

## B. Tanpa compose (podman run manual)

Bila tidak ingin memasang provider compose:

```powershell
podman network create sigital-net

podman run -d --name sigital-postgres --network sigital-net --network-alias postgres `
  -e POSTGRES_DB=sigital -e POSTGRES_USER=sigital -e POSTGRES_PASSWORD=secret `
  -v sigital-pgdata:/var/lib/postgresql/data docker.io/library/postgres:16-alpine

podman run -d --name sigital-redis --network sigital-net --network-alias redis `
  docker.io/library/redis:7-alpine

podman build -t sigital:latest .

podman run -d --name sigital-app --network sigital-net --env-file .env.docker `
  -p 8000:8000 -v sigital-storage:/var/www/html/storage/app localhost/sigital:latest

# Worker antrean & scheduler (opsional, image sama):
podman run -d --name sigital-worker --network sigital-net --env-file .env.docker `
  -v sigital-storage:/var/www/html/storage/app localhost/sigital:latest `
  php artisan queue:work --tries=3 --timeout=120

podman run -d --name sigital-scheduler --network sigital-net --env-file .env.docker `
  localhost/sigital:latest php artisan schedule:work
```

Seed data awal (Admin/Operator/template):

```powershell
podman exec sigital-app php artisan db:seed --force
```

## Akses

- Aplikasi: <http://localhost:8000>
- Login: `admin@sigital.test` / `password` (atau `operator@sigital.test`)
- Verifikasi publik: `http://localhost:8000/verify/{token}` · API: `/api/v1/verify/{token}`

## Catatan

- Entrypoint otomatis: tunggu DB → `migrate --force` → `storage:link` → cache config/route/event → `octane:start --server=roadrunner`.
- **PDF/A** aktif di kontainer (`SIGITAL_PDFA=true`) memakai Ghostscript yang sudah terpasang di image.
- Setelah mengubah kode PHP/Blade, **rebuild image** (Octane menjalankan kode dari memori): `podman build -t sigital:latest .` lalu recreate container app.
- Volume `sigital-storage` menyimpan arsip PDF; `sigital-pgdata` menyimpan database — keduanya bertahan antar-rebuild.
