<template>
    <!-- Judul head mengikuti halaman (prop title), suffix brand ditambah di app.ts. -->
    <Head :title="title" />

    <div :class="{ dark: isDark }" class="legal" style="background-color: var(--color-bg);">
        <!-- Header: logo daerah + nama brand -->
        <header class="legal__header">
            <Link href="/" class="legal__brand">
                <img src="/images/logo-abdya.png" alt="Logo Pemerintah Kabupaten Aceh Barat Daya" class="legal__logo" />
                <span class="legal__brand-name">{{ brand.name }}</span>
            </Link>

            <button
                class="legal__theme"
                :aria-label="isDark ? 'Mode terang' : 'Mode gelap'"
                @click="toggleTheme"
            >
                <component :is="isDark ? Sun : Moon" :size="18" style="color: var(--color-text-muted);" />
            </button>
        </header>

        <!-- Konten dokumen -->
        <main class="legal__main">
            <article class="legal__doc">
                <h1 class="legal__title">{{ title }}</h1>
                <p v-if="updatedAt" class="legal__updated">Terakhir diperbarui: {{ updatedAt }}</p>
                <div class="legal__body">
                    <slot />
                </div>
            </article>
        </main>

        <AppFooter />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Sun, Moon } from '@lucide/vue';
import { useTheme } from '@/Composables/useTheme';
import AppFooter from '@/Components/App/AppFooter.vue';

defineProps({
    title:     { type: String, default: '' },
    updatedAt: { type: String, default: '' },
});

const page = usePage();
const brand = computed(() => {
    const a = page.props.app as { name?: string } | undefined;
    return { name: a?.name ?? 'SIGITAL' };
});

const { isDark, toggleTheme } = useTheme();
</script>

<style scoped>
.legal { min-height: 100vh; display: flex; flex-direction: column; }

.legal__header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid var(--color-border);
    background-color: var(--color-surface);
}
.legal__brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
.legal__logo { width: 32px; height: 32px; object-fit: contain; }
.legal__brand-name {
    font-size: 16px; font-weight: 700; color: var(--color-text-primary);
    font-family: var(--font-heading);
}
.legal__theme {
    padding: 8px; border-radius: 9px; border: none; background: transparent; cursor: pointer;
    transition: background 120ms ease;
}
.legal__theme:hover { background: var(--color-bg-subtle); }

.legal__main { flex: 1; display: flex; justify-content: center; padding: 32px 20px; }
.legal__doc { width: 100%; max-width: 760px; }
.legal__title {
    font-size: 28px; font-weight: 800; color: var(--color-text-primary);
    font-family: var(--font-heading); line-height: 1.2;
}
.legal__updated { margin-top: 6px; font-size: 13px; color: var(--color-text-subtle); }
.legal__body { margin-top: 24px; }

/* Tipografi dokumen — diterapkan ke konten slot (deep). */
.legal__body :deep(h2) {
    font-size: 18px; font-weight: 700; color: var(--color-text-primary);
    margin: 28px 0 10px; font-family: var(--font-heading);
}
.legal__body :deep(p),
.legal__body :deep(li) {
    font-size: 14.5px; line-height: 1.7; color: var(--color-text-muted);
}
.legal__body :deep(p) { margin: 10px 0; }
.legal__body :deep(ul) { margin: 10px 0; padding-left: 22px; list-style: disc; }
.legal__body :deep(li) { margin: 4px 0; }
.legal__body :deep(a) { color: var(--color-primary); font-weight: 600; }
</style>
