<template>
    <div class="app-empty" :class="`app-empty--${size}`">
        <div v-if="$slots.icon || icon" class="app-empty__icon-wrap">
            <slot name="icon">
                <component :is="icon" :size="iconSizeMap[size]" />
            </slot>
        </div>
        <p class="app-empty__title">{{ title }}</p>
        <p v-if="description" class="app-empty__desc">{{ description }}</p>
        <div v-if="$slots.default" class="app-empty__cta">
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps({
    title:       { type: String, default: 'Nothing here yet' },
    description: { type: String, default: '' },
    icon:        { default: null },
    size:        { type: String, default: 'md' }, // sm|md|lg
});

const iconSizeMap: Record<string, number> = { sm: 28, md: 40, lg: 56 };
</script>

<style scoped>
.app-empty {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    text-align: center; padding: 40px 24px;
}
.app-empty--sm { padding: 24px 16px; }
.app-empty--lg { padding: 60px 32px; }

.app-empty__icon-wrap {
    display: flex; align-items: center; justify-content: center;
    width: 72px; height: 72px; border-radius: 20px;
    background: var(--color-bg-subtle);
    color: var(--color-text-subtle);
    margin-bottom: 16px;
}
.app-empty--sm .app-empty__icon-wrap { width: 52px; height: 52px; border-radius: 14px; margin-bottom: 12px; }
.app-empty--lg .app-empty__icon-wrap { width: 96px; height: 96px; border-radius: 28px; margin-bottom: 20px; }

.app-empty__title { font-size: 14px; font-weight: 600; color: var(--color-text-primary); }
.app-empty--sm .app-empty__title { font-size: 13px; }
.app-empty--lg .app-empty__title { font-size: 16px; }

.app-empty__desc {
    margin-top: 6px; font-size: 13px; color: var(--color-text-muted);
    max-width: 320px; line-height: 1.6;
}
.app-empty__cta { margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
</style>
