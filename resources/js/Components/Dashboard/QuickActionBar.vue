<template>
    <div class="qab">
        <span class="qab__label">Quick Actions</span>
        <div class="qab__actions">
            <button
                v-for="action in actions"
                :key="action.label"
                type="button"
                class="qab__btn"
                :style="{ '--qab-color': action.color }"
                @click="action.onClick?.()"
            >
                <span class="qab__btn-icon">
                    <component :is="action.icon" :size="15" />
                </span>
                {{ action.label }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
type QuickAction = {
    label: string;
    icon: unknown;
    color?: string;
    onClick?: () => void;
};

defineProps({
    actions: { type: Array as () => QuickAction[], default: () => [] },
});
</script>

<style scoped>
.qab {
    display: flex;
    align-items: center;
    gap: 16px;
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: 14px;
    padding: 12px 18px;
}
.qab__label {
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.1em;
    color: var(--color-text-subtle);
    white-space: nowrap; flex-shrink: 0;
}
.qab__actions {
    display: flex; flex-wrap: wrap; gap: 8px;
}
.qab__btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 7px 14px; border-radius: 99px;
    border: 1.5px solid var(--color-border);
    background: var(--color-surface);
    cursor: pointer; font-size: 12.5px; font-weight: 500;
    color: var(--color-text-primary);
    font-family: var(--font-sans);
    transition: all 150ms ease;
}
.qab__btn:hover {
    background: color-mix(in srgb, var(--qab-color, #6366f1) 8%, transparent);
    border-color: var(--qab-color, #6366f1);
    color: var(--qab-color, #6366f1);
}
.qab__btn-icon {
    display: flex; align-items: center;
    color: var(--qab-color, #6366f1);
}
</style>
