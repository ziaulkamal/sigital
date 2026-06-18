<template>
    <nav class="app-breadcrumb" aria-label="Breadcrumb">
        <ol class="app-breadcrumb__list">
            <li v-for="(crumb, i) in crumbs" :key="i" class="app-breadcrumb__item">
                <!-- Separator -->
                <span v-if="i > 0" class="app-breadcrumb__sep" aria-hidden="true">
                    <slot name="separator">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </slot>
                </span>

                <!-- Last item (current page) -->
                <span v-if="i === crumbs.length - 1" class="app-breadcrumb__current" aria-current="page">
                    {{ crumb.label }}
                </span>

                <!-- Inertia link -->
                <Link v-else-if="crumb.href" :href="crumb.href" class="app-breadcrumb__link">
                    {{ crumb.label }}
                </Link>

                <!-- Plain span -->
                <span v-else class="app-breadcrumb__link app-breadcrumb__link--plain">{{ crumb.label }}</span>
            </li>
        </ol>
    </nav>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type Crumb = { label: string; href?: string };

defineProps({
    crumbs: { type: Array as () => Crumb[], default: () => [] }, // [{ label, href? }]
});
</script>

<style scoped>
.app-breadcrumb { }
.app-breadcrumb__list {
    display: flex; align-items: center; flex-wrap: wrap;
    list-style: none; margin: 0; padding: 0; gap: 2px;
}
.app-breadcrumb__item {
    display: flex; align-items: center; gap: 2px;
}
.app-breadcrumb__sep { display: flex; align-items: center; color: var(--color-text-subtle); }
.app-breadcrumb__link {
    font-size: 13px; color: var(--color-text-muted);
    text-decoration: none; padding: 2px 4px; border-radius: 4px;
    transition: color 120ms ease, background 120ms ease;
}
.app-breadcrumb__link:hover { color: var(--color-text-primary); background: var(--color-bg-subtle); }
.app-breadcrumb__current {
    font-size: 13px; color: var(--color-text-primary); font-weight: 500;
    padding: 2px 4px;
}
</style>
