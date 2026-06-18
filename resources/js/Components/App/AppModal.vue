<template>
    <Teleport to="body">
        <Transition name="modal-backdrop">
            <div v-if="modelValue" class="app-modal-backdrop" @click.self="onBackdropClick" />
        </Transition>
        <Transition name="modal">
            <div v-if="modelValue" class="app-modal-wrap" role="dialog" :aria-modal="true" :aria-label="title || 'Dialog'" @keydown.esc="onEsc">
                <div ref="panelRef" class="app-modal" :class="`app-modal--${size}`" tabindex="-1">
                    <!-- Header -->
                    <div v-if="title || $slots.header || !hideClose" class="app-modal__header">
                        <slot name="header">
                            <span class="app-modal__title">{{ title }}</span>
                        </slot>
                        <button v-if="!hideClose" type="button" class="app-modal__close" aria-label="Close" @click="close">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                <path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="app-modal__body">
                        <slot />
                    </div>

                    <!-- Footer -->
                    <div v-if="$slots.footer" class="app-modal__footer">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, onUnmounted } from 'vue';

const props = defineProps({
    modelValue:     { type: Boolean, default: false },
    title:          { type: String,  default: '' },
    size:           { type: String,  default: 'md' }, // sm|md|lg|xl|full
    hideClose:      { type: Boolean, default: false },
    closeOnBackdrop:{ type: Boolean, default: true },
    closeOnEsc:     { type: Boolean, default: true },
});
const emit = defineEmits(['update:modelValue', 'close']);

const panelRef = ref<HTMLElement | null>(null);

function close(): void {
    emit('update:modelValue', false);
    emit('close');
}

function onBackdropClick(): void {
    if (props.closeOnBackdrop) close();
}
function onEsc(): void {
    if (props.closeOnEsc) close();
}

watch(() => props.modelValue, async (val) => {
    if (val) {
        document.body.style.overflow = 'hidden';
        await nextTick();
        panelRef.value?.focus();
        document.addEventListener('keydown', handleKeydown);
    } else {
        document.body.style.overflow = '';
        document.removeEventListener('keydown', handleKeydown);
    }
});

function handleKeydown(e: KeyboardEvent): void {
    if (e.key === 'Escape' && props.closeOnEsc) close();
}

onUnmounted(() => {
    document.body.style.overflow = '';
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<style scoped>
.app-modal-backdrop {
    position: fixed; inset: 0; z-index: 1000;
    background: rgba(0,0,0,0.45); backdrop-filter: blur(2px);
}
.app-modal-wrap {
    position: fixed; inset: 0; z-index: 1001;
    display: flex; align-items: center; justify-content: center;
    padding: 16px;
}
.app-modal {
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    width: 100%; display: flex; flex-direction: column;
    max-height: calc(100vh - 32px); overflow: hidden;
    outline: none;
}
.app-modal--sm   { max-width: 400px; }
.app-modal--md   { max-width: 560px; }
.app-modal--lg   { max-width: 720px; }
.app-modal--xl   { max-width: 960px; }
.app-modal--full { max-width: calc(100vw - 32px); max-height: calc(100vh - 32px); }

.app-modal__header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 20px 14px;
    border-bottom: 1px solid var(--color-border);
    flex-shrink: 0;
}
.app-modal__title { font-size: 15px; font-weight: 600; color: var(--color-text-primary); }
.app-modal__close {
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 7px;
    border: none; background: transparent; cursor: pointer;
    color: var(--color-text-subtle); transition: background 120ms ease, color 120ms ease;
}
.app-modal__close:hover { background: var(--color-bg-subtle); color: var(--color-text-primary); }

.app-modal__body { padding: 20px; overflow-y: auto; flex: 1; }

.app-modal__footer {
    padding: 14px 20px;
    border-top: 1px solid var(--color-border);
    display: flex; justify-content: flex-end; gap: 10px;
    flex-shrink: 0;
}

/* Transitions */
.modal-backdrop-enter-active, .modal-backdrop-leave-active { transition: opacity 200ms ease; }
.modal-backdrop-enter-from, .modal-backdrop-leave-to { opacity: 0; }

.modal-enter-active { transition: all 240ms cubic-bezier(0.16,1,0.3,1); }
.modal-leave-active { transition: all 180ms ease; }
.modal-enter-from   { opacity: 0; transform: scale(0.95) translateY(8px); }
.modal-leave-to     { opacity: 0; transform: scale(0.95) translateY(8px); }
</style>
