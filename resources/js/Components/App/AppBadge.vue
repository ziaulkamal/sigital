<template>
    <span class="app-badge" :class="[`app-badge--${color}`, `app-badge--${size}`, { 'app-badge--dot': dot, 'app-badge--pill': pill }]">
        <span v-if="dot" class="app-badge__dot" />
        <span v-if="$slots.icon && !dot" class="app-badge__icon"><slot name="icon" /></span>
        <slot v-if="!dot" />
        <button v-if="removable && !dot" class="app-badge__remove" type="button" aria-label="Remove" @click.stop="$emit('remove')">
            <svg width="8" height="8" viewBox="0 0 10 10" fill="none">
                <path d="M1 1L9 9M9 1L1 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        </button>
    </span>
</template>

<script setup lang="ts">
defineProps({
    color:    { type: String,  default: 'default' }, // default|primary|success|warning|danger|info
    size:     { type: String,  default: 'md' },      // sm | md | lg
    dot:      { type: Boolean, default: false },
    pill:     { type: Boolean, default: true },
    removable:{ type: Boolean, default: false },
});
defineEmits(['remove']);
</script>

<style scoped>
.app-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-weight: 500; line-height: 1; white-space: nowrap;
    border-radius: 6px; font-family: var(--font-sans);
}
.app-badge--pill { border-radius: 99px; }
.app-badge--dot  { padding: 0; background: transparent !important; gap: 0; }

/* Sizes */
.app-badge--sm { font-size: 10px; padding: 2px 6px; }
.app-badge--md { font-size: 11px; padding: 3px 8px; }
.app-badge--lg { font-size: 12px; padding: 4px 10px; }

/* Colors */
.app-badge--default { background: var(--color-bg-subtle); color: var(--color-text-muted); }
.app-badge--primary { background: rgba(99,102,241,0.12); color: #6366f1; }
.app-badge--success { background: rgba(16,185,129,0.12); color: #10b981; }
.app-badge--warning { background: rgba(245,158,11,0.12); color: #d97706; }
.app-badge--danger  { background: rgba(239,68,68,0.12);  color: #ef4444; }
.app-badge--info    { background: rgba(59,130,246,0.12); color: #3b82f6; }

/* Dot variant */
.app-badge__dot {
    display: inline-block; width: 8px; height: 8px; border-radius: 50%;
}
.app-badge--default .app-badge__dot { background: var(--color-text-muted); }
.app-badge--primary .app-badge__dot { background: #6366f1; }
.app-badge--success .app-badge__dot { background: #10b981; }
.app-badge--warning .app-badge__dot { background: #d97706; }
.app-badge--danger  .app-badge__dot { background: #ef4444; }
.app-badge--info    .app-badge__dot { background: #3b82f6; }

.app-badge__icon { display: flex; align-items: center; }

.app-badge__remove {
    display: flex; align-items: center; justify-content: center;
    margin-left: 1px; padding: 1px; border-radius: 50%;
    border: none; background: transparent; cursor: pointer;
    color: inherit; opacity: 0.7; transition: opacity 120ms ease;
}
.app-badge__remove:hover { opacity: 1; }
</style>
