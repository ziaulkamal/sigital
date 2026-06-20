/**
 * node-renderer/layout-geometry.mjs
 *
 * Sumber kebenaran TUNGGAL untuk pemetaan koordinat layout sertifikat.
 * Dipakai oleh DUA sisi agar WYSIWYG (editor Vue = render Node):
 *   - Editor (resources/js/Pages/Templates/Editor.vue) meng-import file ini.
 *   - Renderer (node-renderer/render.mjs) meng-import file ini.
 *
 * Layout menyimpan koordinat sebagai FRAKSI (0..1) relatif ukuran kanvas:
 *   x,y  = fraksi lebar/tinggi kanvas (titik acuan element; lihat origin per tipe)
 *   w    = fraksi lebar kanvas (lebar kotak teks / sisi QR-logo-ttd)
 *   size = fraksi TINGGI kanvas (ukuran font) — skalabel terhadap resolusi apa pun
 *
 * Dengan menyimpan fraksi (bukan piksel/pt), gambar latar beresolusi berapa pun
 * dan render dalam dimensi berapa pun menghasilkan tata letak identik.
 */

/** Tipe element yang diizinkan. Harus identik dengan whitelist PHP (TemplateLayout). */
export const ELEMENT_TYPES = [
    'nama_peserta',
    'event',        // keterangan kegiatan (auto bila kosong)
    'tanggal',
    'nomor',
    'qr',           // QR verifikasi resmi app
    'ttd',          // satu TTD (legacy / slot tunggal)
    'tanda_tangan', // BLOK multi penanda tangan auto-layout (TTD/QR SRIKANDI + nama + jabatan)
    'logo',
    'teks',
];

/** Tipe yang merender teks (sisanya merender gambar). */
export const TEXT_TYPES = ['nama_peserta', 'event', 'tanggal', 'nomor', 'teks'];

/** Tipe yang merender gambar/kotak. */
export const IMAGE_TYPES = ['qr', 'ttd', 'logo'];

/** Tipe blok khusus (di-layout sendiri oleh renderer, bukan teks/gambar tunggal). */
export const BLOCK_TYPES = ['tanda_tangan'];

export function isBlockType(type) {
    return BLOCK_TYPES.includes(type);
}

export function isTextType(type) {
    return TEXT_TYPES.includes(type);
}

/** Batasi nilai ke rentang [min,max]. */
export function clamp(value, min, max) {
    const n = Number(value);
    if (!Number.isFinite(n)) return min;
    return Math.min(max, Math.max(min, n));
}

/**
 * Hitung geometri piksel sebuah element pada kanvas berukuran (cw x ch).
 * Mengembalikan posisi & ukuran absolut yang dipakai langsung oleh Konva
 * (editor maupun render). Origin x/y adalah sudut KIRI-ATAS kotak element;
 * untuk teks `align: center` kotak dibentang selebar `w` dan teks ditengahkan.
 *
 * @param {object} el  element layout (fraksi)
 * @param {number} cw  lebar kanvas (px)
 * @param {number} ch  tinggi kanvas (px)
 */
export function elementGeometry(el, cw, ch) {
    const x = clamp(el.x, 0, 1) * cw;
    const y = clamp(el.y, 0, 1) * ch;
    const w = clamp(el.w ?? 0.2, 0.01, 1) * cw;
    const fontSize = clamp(el.size ?? 0.04, 0.005, 0.5) * ch;

    return {
        x,
        y,
        width: w,
        // Untuk gambar (qr/ttd/logo) kita pakai kotak bujur sangkar berbasis lebar,
        // tinggi diatur saat menggambar (rasio asli gambar) — width sebagai acuan.
        height: w,
        fontSize,
        align: el.align === 'left' || el.align === 'right' ? el.align : 'center',
        color: el.color || '#111827',
        bold: !!el.bold,
        font: el.font || 'DejaVu Sans',
    };
}
