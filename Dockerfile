# Dockerfile — image produksi SIGITAL (Octane + RoadRunner). Kompatibel Docker & Podman.
# Build multi-stage: aset frontend → dependensi PHP → runtime ramping.

# ---------- Stage 1: build aset frontend (Vite) ----------
FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY vite.config.ts tsconfig.json ./
RUN npm run build

# ---------- Stage 2: dependensi PHP (Composer) ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Lewati skrip artisan (source belum lengkap) & abaikan cek ekstensi (tersedia di runtime).
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction \
    --optimize-autoloader --ignore-platform-reqs

# ---------- Stage 3: runtime ----------
FROM php:8.4-cli-bookworm AS runtime

ENV DEBIAN_FRONTEND=noninteractive

# Composer (untuk dump-autoload & package:discover di runtime) + ekstensi PHP.
COPY --from=vendor /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions pdo_pgsql pgsql redis gd zip bcmath intl opcache pcntl sockets \
    && apt-get update \
    && apt-get install -y --no-install-recommends ghostscript postgresql-client \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Konfigurasi PHP produksi (opcache).
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-sigital.ini

# Source aplikasi + dependensi + aset hasil build.
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# Selesaikan skrip composer (package:discover) sekarang source sudah lengkap.
RUN composer dump-autoload --optimize --no-dev \
    # Unduh biner RoadRunner ke root proyek.
    && ./vendor/bin/rr get-binary --location /var/www/html \
    && chmod +x /var/www/html/rr \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 8000
ENTRYPOINT ["entrypoint"]
# Default: server Octane + RoadRunner.
CMD ["php", "artisan", "octane:start", "--server=roadrunner", "--host=0.0.0.0", "--port=8000"]
