<template>
    <Teleport to="body">
        <div class="app-toast-region" aria-live="polite" aria-label="Notifications">
            <TransitionGroup name="toast" tag="div" class="app-toast-stack">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="app-toast"
                    :class="`app-toast--${toast.type}`"
                    role="alert"
                >
                    <span class="app-toast__icon" aria-hidden="true">
                        <svg v-if="toast.type === 'success'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <svg v-else-if="toast.type === 'error'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        <svg v-else-if="toast.type === 'warning'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </span>
                    <div class="app-toast__body">
                        <p v-if="toast.title" class="app-toast__title">{{ toast.title }}</p>
                        <p class="app-toast__message">{{ toast.message }}</p>
                        <button v-if="toast.action" type="button" class="app-toast__action" @click="toast.action.onClick(); dismiss(toast.id)">
                            {{ toast.action.label }}
                        </button>
                    </div>
                    <button type="button" class="app-toast__close" aria-label="Dismiss" @click="dismiss(toast.id)">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                            <path d="M1 1L11 11M11 1L1 11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { useToast } from '@/Composables/useToast';

const { toasts, dismiss } = useToast();
</script>

<style scoped>
.app-toast-region {
    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
    display: flex; flex-direction: column; gap: 10px;
    pointer-events: none;
}
.app-toast-stack { display: flex; flex-direction: column; gap: 10px; }

.app-toast {
    display: flex; align-items: flex-start; gap: 12px;
    min-width: 300px; max-width: 420px;
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    padding: 12px 14px;
    pointer-events: all;
}

.app-toast__icon {
    flex-shrink: 0; display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 8px; margin-top: 1px;
}
.app-toast--success .app-toast__icon { background: rgba(16,185,129,0.12); color: #10b981; }
.app-toast--error   .app-toast__icon { background: rgba(239,68,68,0.12);  color: #ef4444; }
.app-toast--warning .app-toast__icon { background: rgba(245,158,11,0.12); color: #d97706; }
.app-toast--info    .app-toast__icon { background: rgba(59,130,246,0.12); color: #3b82f6; }
.app-toast--default .app-toast__icon { background: var(--color-bg-subtle); color: var(--color-text-muted); }

.app-toast__body { flex: 1; min-width: 0; }
.app-toast__title   { font-size: 13px; font-weight: 600; color: var(--color-text-primary); margin-bottom: 2px; }
.app-toast__message { font-size: 12.5px; color: var(--color-text-muted); line-height: 1.4; }
.app-toast__action  {
    margin-top: 8px; border: none; background: transparent; padding: 0;
    font-size: 12.5px; font-weight: 600; color: #6366f1; cursor: pointer;
    font-family: var(--font-sans);
}
.app-toast__action:hover { text-decoration: underline; }

.app-toast__close {
    flex-shrink: 0; display: flex; align-items: center; justify-content: center;
    width: 22px; height: 22px; border-radius: 6px;
    border: none; background: transparent; cursor: pointer;
    color: var(--color-text-subtle); transition: background 120ms ease, color 120ms ease;
}
.app-toast__close:hover { background: var(--color-bg-subtle); color: var(--color-text-primary); }

/* Transitions */
.toast-enter-active { transition: all 240ms cubic-bezier(0.16,1,0.3,1); }
.toast-leave-active { transition: all 180ms ease; }
.toast-enter-from   { opacity: 0; transform: translateX(24px); }
.toast-leave-to     { opacity: 0; transform: translateX(24px); }
.toast-move         { transition: transform 200ms ease; }
</style>
