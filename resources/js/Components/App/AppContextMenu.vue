<template>
    <div ref="wrapRef" class="app-ctx-wrap" @contextmenu.prevent="onRightClick">
        <slot />
        <Teleport to="body">
            <Transition name="ctx">
                <div
                    v-if="isOpen"
                    class="app-ctx-panel"
                    :style="{ top: `${pos.y}px`, left: `${pos.x}px` }"
                    role="menu"
                    @keydown.esc="close"
                >
                    <template v-for="(item, i) in items" :key="i">
                        <div v-if="item.divider" class="app-ctx-divider" role="separator" />
                        <button
                            v-else
                            type="button"
                            class="app-ctx-item"
                            :class="{ 'app-ctx-item--danger': item.danger, 'app-ctx-item--disabled': item.disabled }"
                            role="menuitem"
                            :disabled="item.disabled"
                            @click="onItemClick(item)"
                        >
                            <span v-if="item.icon" class="app-ctx-item__icon">
                                <component :is="item.icon" :size="13" />
                            </span>
                            {{ item.label }}
                            <span v-if="item.shortcut" class="app-ctx-item__shortcut">{{ item.shortcut }}</span>
                        </button>
                    </template>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue';

type ContextMenuItem = {
    label?: string;
    icon?: unknown;
    shortcut?: string;
    danger?: boolean;
    disabled?: boolean;
    divider?: boolean;
    onClick?: () => void;
};

const props = defineProps({
    items: { type: Array as () => ContextMenuItem[], default: () => [] },
});
const emit = defineEmits(['select']);

const isOpen  = ref<boolean>(false);
const pos     = ref<{ x: number; y: number }>({ x: 0, y: 0 });
const wrapRef = ref<HTMLElement | null>(null);

async function onRightClick(e: MouseEvent): Promise<void> {
    isOpen.value = false;
    await nextTick();
    pos.value    = { x: e.clientX, y: e.clientY };
    isOpen.value = true;
}

function close(): void { isOpen.value = false; }

function onItemClick(item: ContextMenuItem): void {
    if (item.disabled) return;
    item.onClick?.();
    emit('select', item);
    close();
}

function onOutside(e: MouseEvent): void {
    if (!(e.target as Element).closest('.app-ctx-panel')) close();
}

onMounted(() => {
    document.addEventListener('click', onOutside);
    document.addEventListener('contextmenu', (e: MouseEvent) => {
        if (!wrapRef.value?.contains(e.target as Node)) close();
    });
});
onUnmounted(() => document.removeEventListener('click', onOutside));
</script>

<style scoped>
.app-ctx-wrap { display: contents; }
</style>

<style>
.app-ctx-panel {
    position: fixed; z-index: 9999;
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    padding: 4px; min-width: 180px;
}
.app-ctx-item {
    display: flex; align-items: center; gap: 8px;
    width: 100%; padding: 7px 10px;
    border: none; background: transparent; cursor: pointer; text-align: left;
    font-size: 12.5px; font-family: var(--font-sans); color: var(--color-text-primary);
    border-radius: var(--radius-md); transition: background 120ms ease;
}
.app-ctx-item:hover:not(:disabled) { background: var(--color-bg-subtle); }
.app-ctx-item--danger  { color: var(--color-danger); }
.app-ctx-item--danger:hover:not(:disabled) { background: rgba(239,68,68,0.08); }
.app-ctx-item--disabled { opacity: 0.45; cursor: not-allowed; }
.app-ctx-item__icon    { display: flex; align-items: center; color: var(--color-text-muted); }
.app-ctx-item--danger .app-ctx-item__icon { color: var(--color-danger); }
.app-ctx-item__shortcut { margin-left: auto; font-size: 10.5px; color: var(--color-text-subtle); }
.app-ctx-divider { height: 1px; background: var(--color-border); margin: 4px 0; }

.ctx-enter-active, .ctx-leave-active { transition: opacity 120ms ease, transform 120ms ease; }
.ctx-enter-from, .ctx-leave-to { opacity: 0; transform: scale(0.96); }
</style>
