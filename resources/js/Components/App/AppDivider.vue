<template>
    <div
        v-if="vertical"
        class="app-divider app-divider--vertical"
        :style="{ height, borderLeftColor: color || undefined }"
        role="separator"
        aria-orientation="vertical"
    />
    <div
        v-else
        class="app-divider app-divider--horizontal"
        :class="{ 'app-divider--has-label': $slots.default }"
        :style="{ borderTopColor: color || undefined }"
        role="separator"
        aria-orientation="horizontal"
    >
        <span v-if="$slots.default" class="app-divider__label">
            <slot />
        </span>
    </div>
</template>

<script setup lang="ts">
defineProps({
    vertical: { type: Boolean, default: false },
    height:   { type: String,  default: '100%' },
    color:    { type: String,  default: '' },
});
</script>

<style scoped>
.app-divider--horizontal {
    border-top: 1px solid var(--color-border);
    width: 100%; margin: 0;
}
.app-divider--horizontal.app-divider--has-label {
    display: flex; align-items: center; gap: 12px;
    border-top: none;
}
.app-divider--horizontal.app-divider--has-label::before,
.app-divider--horizontal.app-divider--has-label::after {
    content: ''; flex: 1; height: 1px;
    background: var(--color-border);
}

.app-divider__label {
    font-size: 11.5px; color: var(--color-text-muted);
    white-space: nowrap; flex-shrink: 0;
}

.app-divider--vertical {
    display: inline-block;
    border-left: 1px solid var(--color-border);
    align-self: stretch;
}
</style>
