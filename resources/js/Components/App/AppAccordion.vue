<template>
    <div class="app-accordion">
        <div
            v-for="(item, i) in items"
            :key="i"
            class="app-accordion__item"
            :class="{ 'app-accordion__item--open': isOpen(i) }"
        >
            <button
                type="button"
                class="app-accordion__trigger"
                :aria-expanded="isOpen(i)"
                @click="toggle(i)"
            >
                <span class="app-accordion__title">{{ item.title }}</span>
                <svg
                    class="app-accordion__chevron"
                    :class="{ 'app-accordion__chevron--open': isOpen(i) }"
                    width="14" height="14" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                >
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </button>
            <Transition
                @enter="onEnter"
                @after-enter="afterEnter"
                @leave="onLeave"
            >
                <div v-if="isOpen(i)" class="app-accordion__body">
                    <div class="app-accordion__content">
                        <slot :name="`item-${i}`">{{ item.content }}</slot>
                    </div>
                </div>
            </Transition>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

type AccordionItem = { title: string; content?: string };

const props = defineProps({
    items:    { type: Array as () => AccordionItem[], default: () => [] }, // [{ title, content }]
    multiple: { type: Boolean, default: false },
});

const openSet = ref<Set<number>>(new Set());

function isOpen(i: number): boolean { return openSet.value.has(i); }

function toggle(i: number): void {
    const s = new Set(openSet.value);
    if (s.has(i)) {
        s.delete(i);
    } else {
        if (!props.multiple) s.clear();
        s.add(i);
    }
    openSet.value = s;
}

function onEnter(el: Element): void {
    const htmlEl = el as HTMLElement;
    htmlEl.style.maxHeight = '0';
    htmlEl.style.overflow  = 'hidden';
    requestAnimationFrame(() => { htmlEl.style.maxHeight = htmlEl.scrollHeight + 'px'; });
}
function afterEnter(el: Element): void {
    const htmlEl = el as HTMLElement;
    htmlEl.style.maxHeight = '';
    htmlEl.style.overflow  = '';
}
function onLeave(el: Element): void {
    const htmlEl = el as HTMLElement;
    htmlEl.style.maxHeight = htmlEl.scrollHeight + 'px';
    htmlEl.style.overflow  = 'hidden';
    requestAnimationFrame(() => { htmlEl.style.maxHeight = '0'; });
}
</script>

<style scoped>
.app-accordion { border: 1.5px solid var(--color-border); border-radius: var(--radius-lg); overflow: hidden; }

.app-accordion__item + .app-accordion__item { border-top: 1px solid var(--color-border); }

.app-accordion__trigger {
    display: flex; align-items: center; justify-content: space-between;
    width: 100%; padding: 14px 16px;
    border: none; background: transparent; cursor: pointer; text-align: left;
    font-family: var(--font-sans); color: var(--color-text-primary);
    transition: background 120ms ease;
}
.app-accordion__trigger:hover { background: var(--color-bg-subtle); }
.app-accordion__item--open .app-accordion__trigger { background: var(--color-bg-subtle); }

.app-accordion__title { font-size: 13.5px; font-weight: 500; }

.app-accordion__chevron { transition: transform 220ms ease; flex-shrink: 0; color: var(--color-text-muted); }
.app-accordion__chevron--open { transform: rotate(180deg); }

.app-accordion__body { transition: max-height 240ms ease, opacity 220ms ease; }
.v-leave-active { transition: max-height 220ms ease, opacity 180ms ease !important; }
.v-enter-from, .v-leave-to { opacity: 0; }

.app-accordion__content { padding: 4px 16px 16px; font-size: 13.5px; color: var(--color-text-muted); line-height: 1.6; }
</style>
