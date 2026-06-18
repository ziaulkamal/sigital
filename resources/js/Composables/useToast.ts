import { reactive, readonly } from 'vue';

export interface ToastAction {
    label: string;
    onClick: () => void;
}

export type ToastType = 'default' | 'success' | 'error' | 'warning' | 'info';

export interface Toast {
    id: number;
    type: ToastType;
    title: string;
    message: string;
    duration: number;
    action: ToastAction | null;
}

export interface ToastOptions {
    type?: ToastType;
    title?: string;
    message?: string;
    duration?: number;
    action?: ToastAction;
}

let _nextId = 0;

const state = reactive<{ toasts: Toast[] }>({ toasts: [] });

function add(options: ToastOptions): number {
    const id = ++_nextId;
    const toast: Toast = {
        id,
        type:     options.type    ?? 'default',
        title:    options.title   ?? '',
        message:  options.message ?? '',
        duration: options.duration ?? 4000,
        action:   options.action  ?? null,
    };
    state.toasts.push(toast);
    if (toast.duration > 0) {
        setTimeout(() => remove(id), toast.duration);
    }
    return id;
}

function remove(id: number): void {
    const idx = state.toasts.findIndex(t => t.id === id);
    if (idx !== -1) state.toasts.splice(idx, 1);
}

export function useToast() {
    return {
        toasts: readonly(state.toasts),
        toast(options: ToastOptions | string): number {
            return add(typeof options === 'string' ? { message: options } : options);
        },
        success(message: string, options: Omit<ToastOptions, 'type' | 'message'> = {}): number {
            return add({ ...options, type: 'success', message });
        },
        error(message: string, options: Omit<ToastOptions, 'type' | 'message'> = {}): number {
            return add({ ...options, type: 'error', message });
        },
        warning(message: string, options: Omit<ToastOptions, 'type' | 'message'> = {}): number {
            return add({ ...options, type: 'warning', message });
        },
        info(message: string, options: Omit<ToastOptions, 'type' | 'message'> = {}): number {
            return add({ ...options, type: 'info', message });
        },
        dismiss: remove,
        clear(): void { state.toasts.splice(0); },
    };
}
