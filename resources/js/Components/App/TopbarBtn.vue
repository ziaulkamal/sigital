<template>
    <button
        class="topbar-btn"
        v-bind="$attrs"
    >
        <slot />
    </button>
</template>

<script setup lang="ts">
// Reusable topbar icon button — icon slot, hover state, focus ring
</script>

<style scoped>
.topbar-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    background: transparent;
    cursor: pointer;
    color: var(--color-text-muted);
    transition: background-color 120ms ease, color 120ms ease;
    flex-shrink: 0;
}

.topbar-btn:hover {
    background-color: var(--color-bg-subtle);
    color: var(--color-text-primary);
}

.topbar-btn:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 1px;
}
</style>
