#!/usr/bin/env sh
# docker/entrypoint.sh — siapkan aplikasi lalu jalankan perintah utama (CMD).
set -e

# Tunggu PostgreSQL siap (jika dipakai).
if [ "${DB_CONNECTION:-pgsql}" = "pgsql" ]; then
    echo "Menunggu PostgreSQL di ${DB_HOST:-postgres}:${DB_PORT:-5432}…"
    until pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-sigital}" >/dev/null 2>&1; do
        sleep 2
    done
fi

# Pastikan APP_KEY ada.
if [ -z "${APP_KEY}" ]; then
    echo "APP_KEY kosong — membuat kunci baru."
    php artisan key:generate --force
fi

# Migrasi & optimasi (hanya untuk proses web utama).
case "$*" in
    *octane:start*)
        php artisan migrate --force
        php artisan storage:link || true
        php artisan config:cache
        php artisan route:cache
        php artisan event:cache
        ;;
esac

exec "$@"
