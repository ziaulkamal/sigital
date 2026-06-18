<template>
    <svg
        class="app-spinner"
        :class="`app-spinner--${size}`"
        :style="{ color: colorValue }"
        viewBox="0 0 24 24"
        fill="none"
        aria-label="Loading"
        role="status"
    >
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="32" stroke-dashoffset="10" opacity="0.25"/>
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="32" stroke-dashoffset="10" class="app-spinner__arc"/>
    </svg>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
    size:  { type: String, default: 'md' }, // xs|sm|md|lg|xl
    color: { type: String, default: 'primary' }, // primary|white|muted|or any hex
});

const colorMap: Record<string, string> = {
    primary: '#6366f1',
    white:   '#ffffff',
    muted:   'var(--color-text-muted)',
    success: '#10b981',
    danger:  '#ef4444',
    warning: '#f59e0b',
};

const colorValue = computed<string>(() => colorMap[props.color] || props.color);
</script>

<style scoped>
.app-spinner { animation: spin 0.75s linear infinite; flex-shrink: 0; }
.app-spinner--xs { width: 12px; height: 12px; }
.app-spinner--sm { width: 16px; height: 16px; }
.app-spinner--md { width: 20px; height: 20px; }
.app-spinner--lg { width: 28px; height: 28px; }
.app-spinner--xl { width: 40px; height: 40px; }

.app-spinner__arc { opacity: 0.85; }

@keyframes spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
</style>
