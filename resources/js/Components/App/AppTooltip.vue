<template>
    <div class="app-tooltip-wrap" @mouseenter="show" @mouseleave="hide" @focusin="show" @focusout="hide">
        <slot />
        <Transition name="tooltip">
            <div
                v-if="visible && (content || $slots.content)"
                class="app-tooltip"
                :class="`app-tooltip--${position}`"
                role="tooltip"
            >
                <slot name="content">{{ content }}</slot>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps({
    content:  { type: String,  default: '' },
    position: { type: String,  default: 'top' }, // top|bottom|left|right
    delay:    { type: Number,  default: 0 },
    disabled: { type: Boolean, default: false },
});

const visible = ref<boolean>(false);
let timer: ReturnType<typeof setTimeout> | null = null;

function show(): void {
    if (props.disabled) return;
    if (timer !== null) clearTimeout(timer);
    timer = setTimeout(() => { visible.value = true; }, props.delay);
}
function hide(): void {
    if (timer !== null) clearTimeout(timer);
    visible.value = false;
}
</script>

<style scoped>
.app-tooltip-wrap { position: relative; display: inline-flex; }

.app-tooltip {
    position: absolute; z-index: 9000;
    background: var(--color-text-primary); color: var(--color-surface);
    font-size: 11.5px; font-family: var(--font-sans);
    padding: 5px 9px; border-radius: 6px;
    white-space: nowrap; pointer-events: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.18);
}
.app-tooltip::after {
    content: ''; position: absolute;
    border: 5px solid transparent;
}

/* Top */
.app-tooltip--top    { bottom: calc(100% + 8px); left: 50%; transform: translateX(-50%); }
.app-tooltip--top::after { top: 100%; left: 50%; transform: translateX(-50%); border-top-color: var(--color-text-primary); }

/* Bottom */
.app-tooltip--bottom { top: calc(100% + 8px); left: 50%; transform: translateX(-50%); }
.app-tooltip--bottom::after { bottom: 100%; left: 50%; transform: translateX(-50%); border-bottom-color: var(--color-text-primary); }

/* Left */
.app-tooltip--left   { right: calc(100% + 8px); top: 50%; transform: translateY(-50%); }
.app-tooltip--left::after { left: 100%; top: 50%; transform: translateY(-50%); border-left-color: var(--color-text-primary); }

/* Right */
.app-tooltip--right  { left: calc(100% + 8px); top: 50%; transform: translateY(-50%); }
.app-tooltip--right::after { right: 100%; top: 50%; transform: translateY(-50%); border-right-color: var(--color-text-primary); }

/* Transition */
.tooltip-enter-active, .tooltip-leave-active { transition: opacity 140ms ease, transform 140ms ease; }
.tooltip-enter-from, .tooltip-leave-to { opacity: 0; }
.app-tooltip--top.tooltip-enter-from    { transform: translateX(-50%) translateY(4px); }
.app-tooltip--bottom.tooltip-enter-from { transform: translateX(-50%) translateY(-4px); }
.app-tooltip--left.tooltip-enter-from   { transform: translateY(-50%) translateX(4px); }
.app-tooltip--right.tooltip-enter-from  { transform: translateY(-50%) translateX(-4px); }
</style>
