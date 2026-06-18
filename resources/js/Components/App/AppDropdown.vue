<template>
    <div ref="wrapRef" class="app-dd-wrap">
        <div class="app-dd-trigger" @click="toggle">
            <slot name="trigger" />
        </div>

        <Transition name="dropdown">
            <div
                v-if="isOpen"
                class="app-dd-panel"
                :class="`app-dd-panel--${align}`"
                role="menu"
                @keydown.esc="close"
                @keydown.up.prevent="focusItem(-1)"
                @keydown.down.prevent="focusItem(1)"
            >
                <template v-for="(item, i) in items" :key="i">
                    <div v-if="item.divider" class="app-dd-divider" role="separator" />
                    <button
                        v-else
                        type="button"
                        class="app-dd-item"
                        :class="{ 'app-dd-item--danger': item.danger, 'app-dd-item--disabled': item.disabled }"
                        role="menuitem"
                        :disabled="item.disabled"
                        @click="onItemClick(item)"
                    >
                        <span v-if="item.icon" class="app-dd-item__icon">
                            <component :is="item.icon" :size="14" />
                        </span>
                        {{ item.label }}
                        <span v-if="item.suffix" class="app-dd-item__suffix">{{ item.suffix }}</span>
                    </button>
                </template>
                <slot />
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

type DropdownItem = {
    label?: string;
    icon?: unknown;
    suffix?: string;
    danger?: boolean;
    disabled?: boolean;
    divider?: boolean;
    onClick?: () => void;
};

const props = defineProps({
    items: { type: Array as () => DropdownItem[], default: () => [] },
    align: { type: String, default: 'left' }, // left|right
});
const emit = defineEmits(['select']);

const isOpen  = ref<boolean>(false);
const wrapRef = ref<HTMLElement | null>(null);

function toggle(): void { isOpen.value = !isOpen.value; }
function close(): void  { isOpen.value = false; }

function onItemClick(item: DropdownItem): void {
    if (item.disabled) return;
    item.onClick?.();
    emit('select', item);
    close();
}

function focusItem(dir: number): void {
    const items = wrapRef.value?.querySelectorAll<HTMLElement>('.app-dd-item:not(:disabled)') ?? [];
    const curr  = document.activeElement;
    const idx   = Array.from(items).indexOf(curr as HTMLElement);
    const next  = (idx + dir + items.length) % items.length;
    items[next]?.focus();
}

function onOutside(e: MouseEvent): void {
    if (wrapRef.value && !wrapRef.value.contains(e.target as Node)) close();
}

onMounted(() => document.addEventListener('click', onOutside));
onUnmounted(() => document.removeEventListener('click', onOutside));
</script>

<style scoped>
.app-dd-wrap { position: relative; display: inline-flex; }
.app-dd-trigger { display: inline-flex; }

.app-dd-panel {
    position: absolute; top: calc(100% + 6px); z-index: 500;
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    padding: 4px; min-width: 180px;
    outline: none;
}
.app-dd-panel--left  { left: 0; }
.app-dd-panel--right { right: 0; }

.app-dd-item {
    display: flex; align-items: center; gap: 8px;
    width: 100%; padding: 8px 10px;
    border: none; background: transparent; cursor: pointer; text-align: left;
    font-size: 13px; font-family: var(--font-sans); color: var(--color-text-primary);
    border-radius: var(--radius-md); transition: background 120ms ease;
    outline: none;
}
.app-dd-item:hover:not(:disabled), .app-dd-item:focus { background: var(--color-bg-subtle); }
.app-dd-item--danger  { color: var(--color-danger); }
.app-dd-item--danger:hover:not(:disabled) { background: rgba(239,68,68,0.08); }
.app-dd-item--disabled { opacity: 0.45; cursor: not-allowed; }
.app-dd-item__icon   { display: flex; align-items: center; color: var(--color-text-muted); flex-shrink: 0; }
.app-dd-item--danger .app-dd-item__icon { color: var(--color-danger); }
.app-dd-item__suffix { margin-left: auto; font-size: 11px; color: var(--color-text-subtle); }

.app-dd-divider { height: 1px; background: var(--color-border); margin: 4px 0; }

.dropdown-enter-active, .dropdown-leave-active { transition: opacity 140ms ease, transform 140ms ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
