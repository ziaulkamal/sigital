<template>
    <div
        class="app-card"
        :class="[`app-card--${padding}`, { 'app-card--hoverable': hoverable, 'app-card--flat': flat }]"
    >
        <div v-if="$slots.header || title" class="app-card__header">
            <slot name="header">
                <div class="app-card__header-content">
                    <p class="app-card__title">{{ title }}</p>
                    <p v-if="subtitle" class="app-card__subtitle">{{ subtitle }}</p>
                </div>
                <div v-if="$slots.actions" class="app-card__actions">
                    <slot name="actions" />
                </div>
            </slot>
        </div>

        <div class="app-card__body">
            <slot />
        </div>

        <div v-if="$slots.footer" class="app-card__footer">
            <slot name="footer" />
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps({
    title:     { type: String,  default: '' },
    subtitle:  { type: String,  default: '' },
    padding:   { type: String,  default: 'md' }, // none|sm|md|lg
    hoverable: { type: Boolean, default: false },
    flat:      { type: Boolean, default: false },
});
</script>

<style scoped>
.app-card {
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-xl);
    display: flex; flex-direction: column;
}
.app-card--flat { box-shadow: none; border: none; background: var(--color-bg-subtle); }
.app-card--hoverable { transition: box-shadow 200ms ease, transform 200ms ease; cursor: pointer; }
.app-card--hoverable:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); }

/* Padding variants applied to body */
.app-card--none .app-card__body { padding: 0; }
.app-card--sm   .app-card__body { padding: 12px; }
.app-card--md   .app-card__body { padding: 20px; }
.app-card--lg   .app-card__body { padding: 28px; }

.app-card__header {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    padding: 16px 20px 0;
    border-bottom: 1px solid var(--color-border);
    padding-bottom: 14px;
}
.app-card--none .app-card__header,
.app-card--sm   .app-card__header { padding: 12px 12px 0; padding-bottom: 10px; }
.app-card--lg   .app-card__header { padding: 24px 28px 0; padding-bottom: 16px; }

.app-card__header-content { flex: 1; min-width: 0; }
.app-card__title    { font-size: 14px; font-weight: 600; color: var(--color-text-primary); }
.app-card__subtitle { font-size: 12px; color: var(--color-text-muted); margin-top: 2px; }
.app-card__actions  { flex-shrink: 0; display: flex; align-items: center; gap: 8px; }

.app-card__footer {
    padding: 14px 20px;
    border-top: 1px solid var(--color-border);
    flex-shrink: 0;
}
.app-card--sm .app-card__footer { padding: 10px 12px; }
.app-card--lg .app-card__footer { padding: 18px 28px; }
</style>
