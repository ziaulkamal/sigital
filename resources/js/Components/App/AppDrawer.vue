<template>
    <Teleport to="body">
        <Transition name="drawer-backdrop">
            <div v-if="modelValue" class="app-drawer-backdrop" @click="onBackdropClick" />
        </Transition>
        <Transition :name="`drawer-${side}`">
            <div
                v-if="modelValue"
                ref="drawerRef"
                class="app-drawer"
                :class="[`app-drawer--${side}`, `app-drawer--${size}`]"
                role="dialog"
                :aria-modal="true"
                :aria-label="title || 'Drawer'"
                tabindex="-1"
                @keydown.esc="onEsc"
            >
                <div v-if="title || $slots.header || !hideClose" class="app-drawer__header">
                    <slot name="header">
                        <span class="app-drawer__title">{{ title }}</span>
                    </slot>
                    <button v-if="!hideClose" type="button" class="app-drawer__close" aria-label="Close" @click="close">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
                <div class="app-drawer__body">
                    <slot />
                </div>
                <div v-if="$slots.footer" class="app-drawer__footer">
                    <slot name="footer" />
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
    side:           { type: String,  default: 'right' }, // left|right
    size:           { type: String,  default: 'md' },    // sm|md|lg|full
    hideClose:      { type: Boolean, default: false },
    closeOnBackdrop:{ type: Boolean, default: true },
    closeOnEsc:     { type: Boolean, default: true },
});
const emit = defineEmits(['update:modelValue', 'close']);
const drawerRef = ref<HTMLElement | null>(null);

function close(): void {
    emit('update:modelValue', false);
    emit('close');
}
function onBackdropClick(): void { if (props.closeOnBackdrop) close(); }
function onEsc(): void           { if (props.closeOnEsc) close(); }

function handleKeydown(e: KeyboardEvent): void { if (e.key === 'Escape' && props.closeOnEsc) close(); }

watch(() => props.modelValue, async (val) => {
    if (val) {
        document.body.style.overflow = 'hidden';
        await nextTick();
        drawerRef.value?.focus();
        document.addEventListener('keydown', handleKeydown);
    } else {
        document.body.style.overflow = '';
        document.removeEventListener('keydown', handleKeydown);
    }
});

onUnmounted(() => {
    document.body.style.overflow = '';
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<style scoped>
.app-drawer-backdrop {
    position: fixed; inset: 0; z-index: 1000;
    background: rgba(0,0,0,0.4); backdrop-filter: blur(2px);
}
.app-drawer {
    position: fixed; top: 0; bottom: 0; z-index: 1001;
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    box-shadow: var(--shadow-lg);
    display: flex; flex-direction: column;
    overflow: hidden; outline: none;
}
.app-drawer--right { right: 0; border-left: 1.5px solid var(--color-border); border-radius: var(--radius-xl) 0 0 var(--radius-xl); }
.app-drawer--left  { left: 0;  border-right: 1.5px solid var(--color-border); border-radius: 0 var(--radius-xl) var(--radius-xl) 0; }

.app-drawer--sm   { width: 320px; }
.app-drawer--md   { width: 440px; }
.app-drawer--lg   { width: 600px; }
.app-drawer--full { width: 100vw; }

.app-drawer__header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 20px 14px;
    border-bottom: 1px solid var(--color-border);
    flex-shrink: 0;
}
.app-drawer__title { font-size: 15px; font-weight: 600; color: var(--color-text-primary); }
.app-drawer__close {
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 7px;
    border: none; background: transparent; cursor: pointer;
    color: var(--color-text-subtle); transition: background 120ms ease, color 120ms ease;
}
.app-drawer__close:hover { background: var(--color-bg-subtle); color: var(--color-text-primary); }
.app-drawer__body { padding: 20px; overflow-y: auto; flex: 1; }
.app-drawer__footer {
    padding: 14px 20px; border-top: 1px solid var(--color-border);
    display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0;
}

/* Backdrop */
.drawer-backdrop-enter-active, .drawer-backdrop-leave-active { transition: opacity 200ms ease; }
.drawer-backdrop-enter-from, .drawer-backdrop-leave-to { opacity: 0; }

/* Right */
.drawer-right-enter-active, .drawer-right-leave-active { transition: transform 260ms cubic-bezier(0.16,1,0.3,1); }
.drawer-right-enter-from, .drawer-right-leave-to { transform: translateX(100%); }

/* Left */
.drawer-left-enter-active, .drawer-left-leave-active { transition: transform 260ms cubic-bezier(0.16,1,0.3,1); }
.drawer-left-enter-from, .drawer-left-leave-to { transform: translateX(-100%); }
</style>
