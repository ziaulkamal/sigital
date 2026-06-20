/**
 * node-renderer/render.mjs
 *
 * Renderer sertifikat WYSIWYG. Mesin gambar SAMA dengan editor (Konva) sehingga
 * PDF/PNG hasil = pratinjau editor. Dipanggil per-render oleh Laravel
 * (App\Services\Certificate\NodeCanvasRenderer) via:
 *
 *     node render.mjs   (payload JSON di STDIN, PNG biner di STDOUT)
 *
 * Semua masukan datang dari STDIN (bukan argumen) → tidak ada command injection.
 * Path berkas (latar, ttd, logo, font) sudah diresolusi & divalidasi oleh PHP.
 *
 * Payload:
 * {
 *   "canvas":   { "w": 1123, "h": 794 },
 *   "background": "/abs/path/bg.png" | null,
 *   "elements": [ { type,x,y,w,size,align,color,bold,font,text? } ],
 *   "values":   { "nama_peserta": "...", "event": "...", "tanggal": "...", "nomor": "..." },
 *   "images":   { "qr": "/abs/qr.png"|"data:...", "ttd": "/abs/ttd.png"|null, "logo": "/abs/logo.png"|null },
 *   "fonts":    [ { "family": "Poppins", "regular": "/abs/Poppins-Regular.ttf", "bold": "/abs/Poppins-Bold.ttf" } ]
 * }
 */

import { createCanvas, loadImage, registerFont } from 'canvas';
import { elementGeometry, isTextType, isBlockType } from './layout-geometry.mjs';
import fs from 'node:fs';

/** Baca seluruh STDIN sebagai string. */
function readStdin() {
    return new Promise((resolve, reject) => {
        const chunks = [];
        process.stdin.on('data', (c) => chunks.push(c));
        process.stdin.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
        process.stdin.on('error', reject);
    });
}

/** Daftarkan font terkurasi (yang berkasnya ada) sebelum membuat canvas. */
function registerFonts(fonts) {
    for (const f of fonts || []) {
        try {
            if (f.regular && fs.existsSync(f.regular)) {
                registerFont(f.regular, { family: f.family, weight: 'normal' });
            }
            if (f.bold && fs.existsSync(f.bold)) {
                registerFont(f.bold, { family: f.family, weight: 'bold' });
            }
        } catch (e) {
            process.stderr.write(`warn: gagal daftar font ${f.family}: ${e.message}\n`);
        }
    }
}

/** Teks aktual untuk sebuah element (binding nilai dinamis). */
function textForElement(el, values) {
    if (el.type === 'teks') return el.text ?? '';
    return values?.[el.type] ?? '';
}

/** Muat gambar dari path absolut atau data URI; null bila gagal. */
async function tryLoadImage(src) {
    if (!src) return null;
    try {
        return await loadImage(src);
    } catch (e) {
        process.stderr.write(`warn: gagal muat gambar (${String(src).slice(0, 40)}…): ${e.message}\n`);
        return null;
    }
}

/** Ganti placeholder {token} dengan nilainya (token tak dikenal dibiarkan apa adanya). */
function substituteTokens(template, tokens) {
    return String(template).replace(/\{(\w+)\}/g, (m, key) =>
        Object.prototype.hasOwnProperty.call(tokens, key) ? String(tokens[key]) : m,
    );
}

/**
 * Pecah teks ber-markup **bold** menjadi array kata bertanda bold.
 * `baseBold` = default bold element (toggle "Tebal").
 * @returns {{text:string, bold:boolean}[]} daftar KATA (spasi sbg pemisah)
 */
function parseBold(text, baseBold) {
    const words = [];
    // Split di pembatas ** sambil menjaga newline sebagai token tersendiri.
    const parts = String(text).split('**');
    parts.forEach((part, i) => {
        const bold = baseBold ? i % 2 === 0 : i % 2 === 1; // bagian ganjil = di dalam **..**
        // Pertahankan newline sebagai pemisah baris eksplisit.
        const chunks = part.split(/(\n)/);
        for (const chunk of chunks) {
            if (chunk === '\n') { words.push({ text: '\n', bold }); continue; }
            for (const w of chunk.split(/\s+/).filter(Boolean)) {
                words.push({ text: w, bold });
            }
        }
    });
    return words;
}

/**
 * Gambar teks kaya (segmen normal/bold) dengan word-wrap & perataan.
 * Auto turun ke bawah saat melebihi lebar kotak (g.width).
 */
function drawRichText(ctx, words, g) {
    const fontFor = (bold) => `${bold ? 'bold ' : ''}${g.fontSize}px "${g.font}", "DejaVu Sans", sans-serif`;
    const space = () => { ctx.font = fontFor(false); return ctx.measureText(' ').width; };
    const spaceW = space();

    // Susun kata menjadi baris-baris (tiap baris = array kata).
    const lines = [[]];
    let lineW = 0;
    for (const word of words) {
        if (word.text === '\n') { lines.push([]); lineW = 0; continue; }
        ctx.font = fontFor(word.bold);
        const w = ctx.measureText(word.text).width;
        const add = lines[lines.length - 1].length === 0 ? w : spaceW + w;
        if (lineW + add > g.width && lines[lines.length - 1].length > 0) {
            lines.push([word]);
            lineW = w;
        } else {
            lines[lines.length - 1].push(word);
            lineW += add;
        }
    }

    const lineHeight = g.fontSize * 1.3;
    lines.forEach((lineWords, li) => {
        // Hitung lebar baris (untuk center/right).
        let totalW = 0;
        lineWords.forEach((word, idx) => {
            ctx.font = fontFor(word.bold);
            totalW += ctx.measureText(word.text).width + (idx > 0 ? spaceW : 0);
        });
        let cursorX = g.x;
        if (g.align === 'center') cursorX = g.x + (g.width - totalW) / 2;
        else if (g.align === 'right') cursorX = g.x + (g.width - totalW);

        const y = g.y + li * lineHeight;
        ctx.textAlign = 'left'; // gambar kata demi kata dari kiri baris
        lineWords.forEach((word, idx) => {
            if (idx > 0) cursorX += spaceW;
            ctx.font = fontFor(word.bold);
            ctx.fillText(word.text, cursorX, y);
            cursorX += ctx.measureText(word.text).width;
        });
    });
}

/**
 * Gambar blok multi penanda tangan (poin 6): N orang berderet di dalam lebar blok.
 * Tiap kolom: gambar TTD/QR SRIKANDI (jaga rasio) lalu nama (tebal) & jabatan di bawah.
 * g.x/g.y = sudut kiri-atas blok, g.width = lebar blok, g.fontSize = ukuran nama.
 */
async function drawSignatureBlock(ctx, g, signatories) {
    const n = signatories.length;
    if (n === 0) return;

    const colW = g.width / n;
    const nameSize = g.fontSize;
    const roleSize = nameSize * 0.82;
    // Tinggi pita spesimen (TTD/QR) — konsisten antar kolom agar nama sejajar.
    const bandH = nameSize * 3.2;
    const gap = nameSize * 0.35; // jarak spesimen → nama

    for (let i = 0; i < n; i++) {
        const s = signatories[i];
        const cx = g.x + colW * i + colW / 2; // tengah kolom

        // Gambar TTD / QR SRIKANDI: ditengahkan horizontal, RATA BAWAH pita
        // agar nama selalu menempel tepat di bawah spesimen.
        const img = await tryLoadImage(s.image);
        if (img) {
            const ratio = (img.height / img.width) || 1;
            let w = colW * 0.55;
            let h = w * ratio;
            if (h > bandH) { h = bandH; w = h / ratio; }
            // y bawah gambar = g.y + bandH (rata bawah pita).
            ctx.drawImage(img, cx - w / 2, g.y + bandH - h, w, h);
        }

        // Nama TEPAT di bawah pita spesimen, lalu jabatan.
        let cursorY = g.y + bandH + gap;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        ctx.fillStyle = g.color;
        ctx.font = `bold ${nameSize}px "${g.font}", "DejaVu Sans", sans-serif`;
        ctx.fillText(String(s.nama || ''), cx, cursorY, colW * 0.95);
        cursorY += nameSize * 1.2;

        if (s.jabatan) {
            ctx.font = `${roleSize}px "${g.font}", "DejaVu Sans", sans-serif`;
            ctx.fillText(String(s.jabatan), cx, cursorY, colW * 0.95);
        }
    }
}

async function main() {
    const payload = JSON.parse(await readStdin());
    const cw = Math.max(1, payload.canvas?.w | 0);
    const ch = Math.max(1, payload.canvas?.h | 0);

    registerFonts(payload.fonts);

    const canvas = createCanvas(cw, ch);
    const ctx = canvas.getContext('2d');

    // Latar putih (jaga-jaga bila gambar latar transparan/absen).
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, cw, ch);

    // 1) Gambar latar (full-bleed).
    const bg = await tryLoadImage(payload.background);
    if (bg) ctx.drawImage(bg, 0, 0, cw, ch);

    // 2) Element sesuai urutan layout.
    for (const el of payload.elements || []) {
        const g = elementGeometry(el, cw, ch);

        if (isBlockType(el.type)) {
            // Blok tanda tangan: deret penanda tangan (gambar TTD/QR + nama + jabatan).
            await drawSignatureBlock(ctx, g, payload.signatories || []);
            continue;
        }

        if (isTextType(el.type)) {
            let text = String(textForElement(el, payload.values));
            // Element 'event' boleh punya template kustom (el.text) berisi {token} & **bold**.
            if (el.type === 'event' && el.text) {
                text = substituteTokens(el.text, payload.tokens || {});
            }
            if (!text) continue;

            ctx.fillStyle = g.color;
            ctx.textBaseline = 'top';

            // Pecah jadi segmen (normal/bold) lalu word-wrap dengan menjaga bold per kata.
            const segments = parseBold(text, g.bold);
            drawRichText(ctx, segments, g);
        } else {
            // qr | ttd | logo → gambar, jaga rasio asli, lebar = g.width.
            const img = await tryLoadImage(payload.images?.[el.type]);
            if (!img) continue;

            const ratio = img.height / img.width || 1;
            const w = g.width;
            const h = w * ratio;
            ctx.drawImage(img, g.x, g.y, w, h);
        }
    }

    // Keluarkan PNG ke STDOUT (biner).
    const out = canvas.toBuffer('image/png');
    process.stdout.write(out);
}

main().catch((e) => {
    process.stderr.write(`error: ${e.stack || e.message}\n`);
    process.exit(1);
});
