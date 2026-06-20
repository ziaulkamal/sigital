{{--
    resources/views/certificates/canvas-image.blade.php
    Pembungkus PDF: satu PNG hasil render Node (WYSIWYG) dibentang penuh A4 landscape.
    Seluruh tata letak sudah final di PNG → di sini hanya full-bleed.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; }
        html, body { width: 842pt; height: 595pt; }
        img { width: 842pt; height: 595pt; display: block; }
    </style>
</head>
<body>
    <img src="{{ $image }}" alt="Sertifikat">
</body>
</html>
