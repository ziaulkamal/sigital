<template>
    <div ref="wrapRef" class="app-popover-wrap">
        <div
            class="app-popover-trigger"
            @click="trigger === 'click' ? toggle() : undefined"
            @mouseenter="trigger === 'hover' ? open() : undefined"
            @mouseleave="trigger === 'hover' ? close() : undefined"
        >
            <slot name="trigger" />
        </div>

        <Transition name="popover">
            <div
                v-if="isOpen"
                class="app-popover"
                :class="`app-popover--${position}`"
                role="dialog"
            >
                <div v-if="arrow" class="app-popover__arrow" :class="`app-popover__arrow--${position}`" />
                <slot />
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    position: { type: String,  default: 'bottom' }, // top|bottom|left|right
    trigger:  { type: String,  default: 'click' },  // click|hover
    arrow:    { type: Boolean, default: true },
});

const isOpen  = ref<boolean>(false);
const wrapRef = ref<HTMLElement | null>(null);

function toggle(): void { isOpen.value = !isOpen.value; }
function open(): void   { isOpen.value = true; }
function close(): void  { isOpen.value = false; }

function onOutside(e: MouseEvent): void {
    if (wrapRef.value && !wrapRef.value.contains(e.target as Node)) close();
}

onMounted(() => document.addEventListener('click', onOutside));
onUnmounted(() => document.removeEventListener('click', onOutside));
</script>

<style scoped>
.app-popover-wrap { position: relative; display: inline-flex; }
.app-popover-trigger { display: inline-flex; }

.app-popover {
    position: absolute; z-index: 500;
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    padding: 12px 14px;
    min-width: 200px;
}

.app-popover--bottom { top: calc(100% + 10px); left: 0; }
.app-popover--top    { bottom: calc(100% + 10px); left: 0; }
.app-popover--right  { left: calc(100% + 10px); top: 0; }
.app-popover--left   { right: calc(100% + 10px); top: 0; }

.app-popover__arrow {
    position: absolute; width: 10px; height: 10px;
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    transform: rotate(45deg);
}
.app-popover__arrow--bottom { top: -6px; left: 16px; border-bottom: none; border-right: none; }
.app-popover__arrow--top    { bottom: -6px; left: 16px; border-top: none; border-left: none; }
.app-popover__arrow--right  { left: -6px; top: 12px; border-top: none; border-right: none; }
.app-popover__arrow--left   { right: -6px; top: 12px; border-bottom: none; border-left: none; }

.popover-enter-active, .popover-leave-active { transition: opacity 160ms ease, transform 160ms ease; }
.popover-enter-from, .popover-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
