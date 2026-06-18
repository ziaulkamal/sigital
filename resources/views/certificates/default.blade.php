{{--
    resources/views/certificates/default.blade.php
    Template PDF sertifikat MVP (A4 landscape). Perancang visual = v2.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; }
        .sheet {
            width: 100%; height: 540pt; position: relative;
            border: 10pt solid #1e3a8a; padding: 38pt 54pt;
        }
        .inner { border: 1.5pt solid #93c5fd; height: 100%; padding: 26pt 40pt; text-align: center; }
        .eyebrow { font-size: 11pt; letter-spacing: 3pt; color: #2563eb; text-transform: uppercase; }
        .instansi { font-size: 13pt; font-weight: bold; margin-top: 6pt; color: #1e3a8a; }
        .title { font-size: 30pt; font-weight: bold; margin: 18pt 0 4pt; color: #0f172a; }
        .subtitle { font-size: 11pt; color: #6b7280; }
        .name { font-size: 26pt; font-weight: bold; margin: 22pt 0 6pt; color: #1d4ed8; }
        .rule { width: 280pt; height: 1pt; background: #cbd5e1; margin: 4pt auto 14pt; }
        .event-name { font-size: 14pt; font-weight: bold; }
        .event-meta { font-size: 10.5pt; color: #6b7280; margin-top: 4pt; }
        .footer { position: absolute; left: 54pt; right: 54pt; bottom: 46pt; }
        .footer-table { width: 100%; }
        .footer-table td { vertical-align: bottom; }
        .qr-cell { width: 130pt; text-align: left; }
        .qr-cell img { width: 96pt; height: 96pt; }
        .qr-caption { font-size: 7.5pt; color: #9ca3af; margin-top: 3pt; }
        .sign-cell { text-align: center; }
        .sign-img { height: 46pt; margin-bottom: 2pt; }
        .sign-name { font-size: 10.5pt; font-weight: bold; border-top: 0.8pt solid #475569; padding-top: 3pt; }
        .sign-role { font-size: 9pt; color: #6b7280; }
        .nomor { font-size: 9pt; color: #6b7280; margin-top: 2pt; }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="inner">
            <div class="eyebrow">Sertifikat</div>
            <div class="instansi">{{ $instansi }}</div>

            <div class="title">SERTIFIKAT</div>
            <div class="subtitle">Diberikan kepada</div>

            <div class="name">{{ $person->nama }}</div>
            <div class="rule"></div>

            <div class="subtitle">atas partisipasinya dalam</div>
            <div class="event-name">{{ $event->nama }}</div>
            <div class="event-meta">
                {{ \Illuminate\Support\Carbon::parse($event->jadwal_mulai)->translatedFormat('d F Y') }}
                @if($event->lokasi) &middot; {{ $event->lokasi }} @endif
            </div>
            <div class="nomor">No. {{ $certificate->nomor_unik }}</div>
        </div>

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td class="qr-cell">
                        <img src="{{ $qrDataUri }}" alt="QR Verifikasi">
                        <div class="qr-caption">Pindai untuk verifikasi keaslian</div>
                    </td>
                    @foreach($signatories as $s)
                        <td class="sign-cell">
                            @if($s->gambar_ttd && file_exists(storage_path('app/public/'.$s->gambar_ttd)))
                                <img class="sign-img" src="{{ storage_path('app/public/'.$s->gambar_ttd) }}" alt="TTD">
                            @else
                                <div style="height:46pt"></div>
                            @endif
                            <div class="sign-name">{{ $s->nama }}</div>
                            <div class="sign-role">{{ $s->jabatan }}</div>
                        </td>
                    @endforeach
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
