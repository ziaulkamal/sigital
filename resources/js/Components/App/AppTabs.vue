<template>
    <div class="app-tabs" :class="`app-tabs--${variant}`">
        <!-- Tab list -->
        <div class="app-tabs__list" role="tablist">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                type="button"
                class="app-tabs__tab"
                :class="{ 'app-tabs__tab--active': modelValue === tab.value, 'app-tabs__tab--disabled': tab.disabled }"
                role="tab"
                :aria-selected="modelValue === tab.value"
                :disabled="tab.disabled"
                @click="select(tab)"
            >
                <span v-if="tab.icon" class="app-tabs__tab-icon">
                    <component :is="tab.icon" :size="14" />
                </span>
                {{ tab.label }}
                <span v-if="tab.badge" class="app-tabs__badge">{{ tab.badge }}</span>
            </button>
        </div>

        <!-- Tab panels -->
        <div class="app-tabs__panels">
            <template v-for="tab in tabs" :key="tab.value">
                <div
                    v-if="!lazy || modelValue === tab.value"
                    v-show="modelValue === tab.value"
                    class="app-tabs__panel"
                    role="tabpanel"
                >
                    <slot :name="tab.value" />
                </div>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
type Tab = {
    value: string;
    label: string;
    icon?: unknown;
    badge?: string | number;
    disabled?: boolean;
};

const props = defineProps({
    modelValue: { default: '' },
    tabs:       { type: Array as () => Tab[], default: () => [] }, // [{ value, label, icon?, badge?, disabled? }]
    variant:    { type: String,  default: 'underline' }, // underline|pill
    lazy:       { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

function select(tab: Tab): void {
    if (!tab.disabled) emit('update:modelValue', tab.value);
}
</script>

<style scoped>
.app-tabs { display: flex; flex-direction: column; }

/* ── List ── */
.app-tabs__list { display: flex; }

.app-tabs--underline .app-tabs__list {
    border-bottom: 1.5px solid var(--color-border);
    gap: 0;
}
.app-tabs--pill .app-tabs__list {
    gap: 4px;
    background: var(--color-bg-subtle);
    border-radius: var(--radius-md);
    padding: 4px;
    width: fit-content;
}

/* ── Tab button ── */
.app-tabs__tab {
    display: inline-flex; align-items: center; gap: 6px;
    border: none; background: transparent; cursor: pointer;
    font-family: var(--font-sans); font-size: 13px; font-weight: 500;
    color: var(--color-text-muted);
    transition: color 150ms ease, background 150ms ease;
    white-space: nowrap;
}
.app-tabs__tab--disabled { opacity: 0.45; cursor: not-allowed; }

/* Underline */
.app-tabs--underline .app-tabs__tab {
    padding: 9px 14px;
    border-bottom: 2px solid transparent;
    margin-bottom: -1.5px;
}
.app-tabs--underline .app-tabs__tab:hover:not(.app-tabs__tab--disabled) { color: var(--color-text-primary); }
.app-tabs--underline .app-tabs__tab--active {
    color: #6366f1;
    border-bottom-color: #6366f1;
}

/* Pill */
.app-tabs--pill .app-tabs__tab {
    padding: 6px 14px;
    border-radius: calc(var(--radius-md) - 2px);
}
.app-tabs--pill .app-tabs__tab:hover:not(.app-tabs__tab--disabled) { background: var(--color-border); }
.app-tabs--pill .app-tabs__tab--active {
    background: var(--color-surface);
    color: var(--color-text-primary);
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}

.app-tabs__tab-icon { display: flex; align-items: center; }
.app-tabs__badge {
    font-size: 10px; font-weight: 600; padding: 1px 5px;
    background: rgba(99,102,241,0.12); color: #6366f1;
    border-radius: 99px;
}

/* ── Panels ── */
.app-tabs__panels { padding-top: 16px; }
</style>
