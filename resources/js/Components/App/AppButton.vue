<template>
    <component
        :is="tag"
        v-bind="attrs"
        class="app-btn"
        :class="[`app-btn--${variant}`, `app-btn--${size}`, { 'app-btn--loading': loading, 'app-btn--icon-only': iconOnly }]"
        :disabled="disabled || loading"
        :aria-busy="loading"
    >
        <!-- Left icon / Spinner -->
        <span v-if="loading" class="app-btn__spinner" aria-hidden="true">
            <svg class="animate-spin" width="14" height="14" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="32" stroke-dashoffset="10"/>
            </svg>
        </span>
        <span v-else-if="$slots.icon" class="app-btn__icon" aria-hidden="true">
            <slot name="icon" />
        </span>

        <!-- Label -->
        <span v-if="$slots.default && !iconOnly" class="app-btn__label">
            <slot />
        </span>

        <!-- Right icon -->
        <span v-if="$slots['icon-right'] && !loading && !iconOnly" class="app-btn__icon" aria-hidden="true">
            <slot name="icon-right" />
        </span>
    </component>
</template>

<script setup lang="ts">
import { computed, useAttrs } from 'vue';

const props = defineProps({
    variant:  { type: String,  default: 'primary' }, // primary | secondary | ghost | danger | outline | success | warning
    size:     { type: String,  default: 'md' },       // xs | sm | md | lg
    tag:      { type: String,  default: 'button' },
    loading:  { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    iconOnly: { type: Boolean, default: false },
    href:     { type: String,  default: null },
});

const rawAttrs = useAttrs();
const attrs = computed(() => ({
    ...rawAttrs,
    ...(props.tag === 'a' || props.href ? { href: props.href } : { type: (rawAttrs.type as string) ?? 'button' }),
}));
const tag = computed<string>(() => props.href ? 'a' : props.tag);
</script>

<style scoped>
.app-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    font-weight: 500;
    border-radius: var(--radius-md);
    border: 1.5px solid transparent;
    cursor: pointer;
    transition: background 150ms ease, color 150ms ease, border-color 150ms ease,
                box-shadow 150ms ease, opacity 150ms ease;
    white-space: nowrap;
    text-decoration: none;
    font-family: var(--font-sans);
    outline: none;
    position: relative;
    user-select: none;
    letter-spacing: -0.01em;
}
.app-btn:focus-visible { box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 30%, transparent); }
.app-btn:disabled, .app-btn--loading { opacity: 0.55; cursor: not-allowed; pointer-events: none; }

/* ── Sizes ── */
.app-btn--xs { padding: 3px 10px; font-size: 11.5px; gap: 4px; border-radius: var(--radius-sm); }
.app-btn--sm { padding: 5px 13px; font-size: 12.5px; }
.app-btn--md { padding: 7.5px 16px; font-size: 13.5px; }
.app-btn--lg { padding: 10px 22px; font-size: 15px; }
.app-btn--icon-only.app-btn--xs { padding: 5px; }
.app-btn--icon-only.app-btn--sm { padding: 7px; }
.app-btn--icon-only.app-btn--md { padding: 9px; }
.app-btn--icon-only.app-btn--lg { padding: 11px; }

/* ── Variants ── */
.app-btn--primary {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 2px 8px rgba(99,102,241,0.3);
}
.app-btn--primary:hover:not(:disabled) {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    box-shadow: 0 4px 14px rgba(99,102,241,0.4);
}

.app-btn--secondary {
    background: var(--color-bg-subtle);
    color: var(--color-text-primary);
    border-color: var(--color-border);
}
.app-btn--secondary:hover:not(:disabled) { background: var(--color-border); }

.app-btn--ghost {
    background: transparent;
    color: var(--color-text-muted);
    border-color: transparent;
}
.app-btn--ghost:hover:not(:disabled) { background: var(--color-bg-subtle); color: var(--color-text-primary); }

.app-btn--outline {
    background: transparent;
    color: var(--color-primary);
    border-color: var(--color-primary);
}
.app-btn--outline:hover:not(:disabled) { background: color-mix(in srgb, var(--color-primary) 8%, transparent); }

.app-btn--danger {
    background: linear-gradient(135deg, #ef4444, #f97316);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 2px 8px rgba(239,68,68,0.3);
}
.app-btn--danger:hover:not(:disabled) { background: linear-gradient(135deg, #dc2626, #ea580c); }

.app-btn--success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 2px 8px rgba(16,185,129,0.25);
}
.app-btn--success:hover:not(:disabled) { background: linear-gradient(135deg, #059669, #047857); }

.app-btn--warning {
    background: linear-gradient(135deg, #f59e0b, #f97316);
    color: #fff;
    border-color: transparent;
}
.app-btn--warning:hover:not(:disabled) { background: linear-gradient(135deg, #d97706, #ea580c); }

/* ── Inner ── */
.app-btn__icon, .app-btn__spinner { display: flex; align-items: center; flex-shrink: 0; }
.app-btn__label { flex: 1; }
</style>
