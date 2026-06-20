/**
 * resources/js/Pages/Templates/layout.ts
 *
 * Tipe & helper layout untuk perancang template (sisi editor).
 * Rumus fraksi→piksel HARUS identik dengan node-renderer/layout-geometry.mjs
 * agar pratinjau editor = hasil render PDF (WYSIWYG). Bila salah satu diubah,
 * ubah keduanya.
 */

export const TEXT_TYPES = ['nama_peserta', 'event', 'tanggal', 'nomor', 'teks'] as const;
export const IMAGE_TYPES = ['qr', 'ttd', 'logo'] as const;
export const BLOCK_TYPES = ['tanda_tangan'] as const;
export type ElementType =
    | (typeof TEXT_TYPES)[number]
    | (typeof IMAGE_TYPES)[number]
    | (typeof BLOCK_TYPES)[number];

export function isBlockType(type: ElementType): boolean {
    return (BLOCK_TYPES as readonly string[]).includes(type);
}

export interface LayoutElement {
    id: string;
    type: ElementType;
    x: number; // fraksi lebar (0..1)
    y: number; // fraksi tinggi (0..1)
    w: number; // fraksi lebar
    size?: number; // fraksi tinggi (ukuran font) — hanya teks
    align?: 'left' | 'center' | 'right';
    color?: string;
    bold?: boolean;
    font?: string;
    text?: string; // hanya type 'teks'
}

export interface CanvasMeta {
    w: number;
    h: number;
}

export interface TemplateLayout {
    canvas: CanvasMeta;
    elements: LayoutElement[];
}

export function isTextType(type: ElementType): boolean {
    return (TEXT_TYPES as readonly string[]).includes(type);
}

/** Tipe yang punya properti gaya teks (font/size/color) — termasuk blok tanda_tangan. */
export function isStyledType(type: ElementType): boolean {
    return isTextType(type) || type === 'tanda_tangan';
}

export function clamp(value: number, min: number, max: number): number {
    if (!Number.isFinite(value)) return min;
    return Math.min(max, Math.max(min, value));
}

/** Geometri piksel sebuah element pada kanvas (cw x ch). Mirror dari .mjs. */
export function elementGeometry(el: LayoutElement, cw: number, ch: number) {
    const x = clamp(el.x, 0, 1) * cw;
    const y = clamp(el.y, 0, 1) * ch;
    const w = clamp(el.w ?? 0.2, 0.01, 1) * cw;
    const fontSize = clamp(el.size ?? 0.04, 0.005, 0.5) * ch;
    return {
        x,
        y,
        width: w,
        fontSize,
        align: (el.align === 'left' || el.align === 'right' ? el.align : 'center') as 'left' | 'center' | 'right',
        color: el.color || '#111827',
        bold: !!el.bold,
        font: el.font || 'DejaVu Sans',
    };
}

/** Label ramah & teks placeholder per tipe (ditampilkan di editor). */
export const TYPE_META: Record<ElementType, { label: string; placeholder: string; image?: boolean; block?: boolean }> = {
    nama_peserta: { label: 'Nama Peserta', placeholder: 'Nama Peserta' },
    event: { label: 'Keterangan Kegiatan', placeholder: 'Keterangan kegiatan…' },
    tanggal: { label: 'Tanggal', placeholder: '01 Januari 2026' },
    nomor: { label: 'Nomor Sertifikat', placeholder: 'No. 001/SERT/2026' },
    teks: { label: 'Teks Bebas', placeholder: 'Teks…' },
    qr: { label: 'QR Verifikasi', placeholder: 'QR', image: true },
    ttd: { label: 'Tanda Tangan (1)', placeholder: 'TTD', image: true },
    tanda_tangan: { label: 'Tanda Tangan (Banyak)', placeholder: 'Blok Tanda Tangan', block: true },
    logo: { label: 'Logo', placeholder: 'LOGO', image: true },
};

let idSeq = 0;
export function newElementId(): string {
    idSeq += 1;
    return `el${Date.now().toString(36)}${idSeq}`;
}

/** Element baru dengan default sesuai tipe, ditaruh di tengah kanvas. */
export function makeElement(type: ElementType): LayoutElement {
    const base: LayoutElement = {
        id: newElementId(),
        type,
        x: 0.5 - 0.15,
        y: 0.45,
        w: 0.3,
    };
    if (isTextType(type)) {
        base.size = type === 'nama_peserta' ? 0.06 : 0.03;
        base.align = 'center';
        base.color = type === 'nama_peserta' ? '#1d4ed8' : '#111827';
        base.bold = type === 'nama_peserta';
        base.font = 'Poppins';
        if (type === 'teks') base.text = 'Teks';
    } else if (isBlockType(type)) {
        // Blok tanda tangan: lebar besar, di bawah kanvas; ukuran teks nama/jabatan.
        base.x = 0.15;
        base.y = 0.72;
        base.w = 0.7;
        base.size = 0.022;
        base.color = '#111827';
        base.font = 'Poppins';
    } else {
        base.w = type === 'qr' ? 0.12 : 0.18;
    }
    return base;
}
