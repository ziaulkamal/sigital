{{-- resources/views/emails/certificate.blade.php — email distribusi sertifikat. --}}
<x-mail::message>
# Selamat, {{ $nama }}!

Sertifikat Anda untuk **{{ $acara }}** telah diterbitkan dan terlampir pada email ini (PDF).

**Nomor Sertifikat:** {{ $nomor }}

Untuk memastikan keaslian, pindai QR pada sertifikat atau buka tautan verifikasi berikut:

<x-mail::button :url="$verifyUrl">
Verifikasi Keaslian
</x-mail::button>

Terima kasih.

{{ config('sigital.instansi_nama') }}
</x-mail::message>
