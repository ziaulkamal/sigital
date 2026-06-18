/**
 * resources/js/Composables/useSwal.ts
 * Pembungkus SweetAlert2 bertema SIGITAL. Menyediakan toast (untuk feedback aksi
 * sukses/gagal/peringatan) dan dialog konfirmasi untuk aksi destruktif.
 *
 * Warna mengikuti CSS custom properties tema (mendukung dark mode).
 */
import Swal, { type SweetAlertIcon } from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

function cssVar(name: string, fallback: string): string {
    if (typeof window === 'undefined') return fallback;
    const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return v || fallback;
}

/** Opsi tema agar Swal menyatu dengan surface/teks aplikasi (light & dark). */
function themed() {
    return {
        background: cssVar('--color-surface', '#ffffff'),
        color: cssVar('--color-text-primary', '#0f172a'),
        confirmButtonColor: cssVar('--color-primary', '#6366f1'),
    };
}

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    didOpen: (el) => {
        el.addEventListener('mouseenter', Swal.stopTimer);
        el.addEventListener('mouseleave', Swal.resumeTimer);
    },
});

/** Toast ringkas di pojok kanan atas (feedback aksi). */
export function swalToast(icon: SweetAlertIcon, title: string) {
    return toast.fire({ icon, title, ...themed() });
}

export const swalSuccess = (title: string, text?: string) =>
    swalToast('success', text ? `${title} — ${text}` : title);

export const swalError = (title: string, text?: string) =>
    swalToast('error', text ? `${title} — ${text}` : title);

export const swalWarning = (title: string, text?: string) =>
    swalToast('warning', text ? `${title} — ${text}` : title);

export const swalInfo = (title: string, text?: string) => swalToast('info', text ?? title);

interface ConfirmOptions {
    title?: string;
    text?: string;
    confirmText?: string;
    cancelText?: string;
    danger?: boolean;
}

/** Dialog konfirmasi (mis. sebelum hapus). Resolve true bila pengguna menyetujui. */
export async function swalConfirm(opts: ConfirmOptions = {}): Promise<boolean> {
    const res = await Swal.fire({
        icon: 'warning',
        title: opts.title ?? 'Anda yakin?',
        text: opts.text,
        showCancelButton: true,
        reverseButtons: true,
        focusCancel: true,
        confirmButtonText: opts.confirmText ?? 'Ya, lanjutkan',
        cancelButtonText: opts.cancelText ?? 'Batal',
        ...themed(),
        confirmButtonColor: opts.danger
            ? cssVar('--color-danger', '#ef4444')
            : cssVar('--color-primary', '#6366f1'),
        cancelButtonColor: cssVar('--color-bg-subtle', '#e2e8f0'),
    });
    return res.isConfirmed;
}

/** Bridge: tampilkan flash sukses/error dari server (props Inertia) sebagai toast. */
export function showFlash(flash: { success?: string | null; error?: string | null } | null | undefined) {
    if (!flash) return;
    if (flash.error) swalError(flash.error);
    else if (flash.success) swalSuccess(flash.success);
}

export function useSwal() {
    return { swalToast, swalSuccess, swalError, swalWarning, swalInfo, swalConfirm, showFlash, Swal };
}
