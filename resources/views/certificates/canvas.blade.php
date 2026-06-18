{{--
    resources/views/certificates/canvas.blade.php
    Template kanvas (P6): gambar latar penuh + field ditempatkan via koordinat (posisi_field).
    A4 landscape = 842pt x 595pt. Koordinat dalam pt dari sudut kiri-atas.
    Field align 'center' diabaikan koordinat x-nya (dibentang penuh & ditengahkan pada y).
--}}
@php
    // Ambil posisi field dengan fallback default agar template tanpa koordinat tetap rapi.
    $pos = function (string $key, array $def) use ($positions) {
        $given = is_array($positions[$key] ?? null) ? $positions[$key] : [];
        return array_merge($def, $given);
    };

    $style = function (array $f): string {
        $css = 'position:absolute;top:'.$f['y'].'pt;';
        if (($f['align'] ?? 'center') === 'center') {
            $css .= 'left:0;width:842pt;text-align:center;';
        } else {
            $css .= 'left:'.$f['x'].'pt;text-align:left;';
        }
        $css .= 'font-size:'.$f['size'].'pt;color:'.($f['color'] ?? '#111827').';';
        if (! empty($f['bold'])) {
            $css .= 'font-weight:bold;';
        }
        return $css;
    };

    $fNama    = $pos('nama',    ['x' => 0, 'y' => 250, 'size' => 28, 'align' => 'center', 'color' => '#1d4ed8', 'bold' => true]);
    $fLabel   = $pos('label',   ['x' => 0, 'y' => 222, 'size' => 11, 'align' => 'center', 'color' => '#6b7280']);
    $fEvent   = $pos('event',   ['x' => 0, 'y' => 322, 'size' => 14, 'align' => 'center', 'color' => '#111827', 'bold' => true]);
    $fTanggal = $pos('tanggal', ['x' => 0, 'y' => 348, 'size' => 10.5, 'align' => 'center', 'color' => '#6b7280']);
    $fNomor   = $pos('nomor',   ['x' => 0, 'y' => 372, 'size' => 9, 'align' => 'center', 'color' => '#6b7280']);
    $fQr      = $pos('qr',      ['x' => 60, 'y' => 452, 'size' => 9]);
    $fSign    = $pos('signatures', ['x' => 540, 'y' => 430, 'size' => 10]);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; }
        .canvas { position: relative; width: 842pt; height: 595pt; overflow: hidden; }
        .bg { position: absolute; top: 0; left: 0; width: 842pt; height: 595pt; }
        .kop { position: absolute; top: 0; left: 0; width: 842pt; max-height: 80pt; }
        .logo { position: absolute; top: 28pt; left: 40pt; height: 56pt; }
        .qr-img { width: 92pt; height: 92pt; }
        .qr-cap { font-size: 7.5pt; color: #9ca3af; margin-top: 3pt; }
        .sign { display: inline-block; text-align: center; margin-left: 18pt; }
        .sign-img { height: 44pt; }
        .sign-name { font-size: 10pt; font-weight: bold; border-top: 0.8pt solid #475569; padding-top: 3pt; }
        .sign-role { font-size: 8.5pt; color: #6b7280; }
    </style>
</head>
<body>
    <div class="canvas">
        @if(!empty($backgroundPath))
            <img class="bg" src="{{ $backgroundPath }}" alt="">
        @endif

        @if(!empty($kopPath))
            <img class="kop" src="{{ $kopPath }}" alt="Kop">
        @elseif(!empty($logoPath))
            <img class="logo" src="{{ $logoPath }}" alt="Logo">
        @endif

        <div style="{{ $style($fLabel) }}">Diberikan kepada</div>
        <div style="{{ $style($fNama) }}">{{ $person->nama }}</div>
        <div style="{{ $style($fEvent) }}">{{ $event->nama }}</div>
        <div style="{{ $style($fTanggal) }}">
            {{ \Illuminate\Support\Carbon::parse($event->jadwal_mulai)->translatedFormat('d F Y') }}
            @if($event->lokasi) &middot; {{ $event->lokasi }} @endif
        </div>
        <div style="{{ $style($fNomor) }}">No. {{ $certificate->nomor_unik }}</div>

        <div style="position:absolute;top:{{ $fQr['y'] }}pt;left:{{ $fQr['x'] }}pt;text-align:center;">
            <img class="qr-img" src="{{ $qrDataUri }}" alt="QR Verifikasi">
            <div class="qr-cap">Pindai untuk verifikasi</div>
        </div>

        <div style="position:absolute;top:{{ $fSign['y'] }}pt;left:{{ $fSign['x'] }}pt;">
            @foreach($signatories as $s)
                <div class="sign">
                    @if($s->gambar_ttd && file_exists(storage_path('app/public/'.$s->gambar_ttd)))
                        <img class="sign-img" src="{{ storage_path('app/public/'.$s->gambar_ttd) }}" alt="TTD">
                    @else
                        <div style="height:44pt"></div>
                    @endif
                    <div class="sign-name">{{ $s->nama }}</div>
                    <div class="sign-role">{{ $s->jabatan }}</div>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
