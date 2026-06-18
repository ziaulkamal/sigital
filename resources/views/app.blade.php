<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <!-- Favicon: lambang Pemerintah Kabupaten Aceh Barat Daya -->
        <link rel="icon" type="image/png" href="/images/logo-abdya.png" />
        <link rel="apple-touch-icon" href="/images/logo-abdya.png" />

        <title inertia>{{ config('sigital.brand.name', 'SIGITAL') }}</title>
        {{-- Nama brand untuk suffix judul (dibaca app.ts — tidak di-hardcode di JS). --}}
        <meta name="app-name" content="{{ config('sigital.brand.name', 'SIGITAL') }}" />

        <!-- Preconnect for fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net" />

        <!-- Styles & Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.ts'])

        <!-- Inertia -->
        @inertiaHead
    </head>
    <body class="h-full antialiased">
        @inertia
    </body>
</html>
