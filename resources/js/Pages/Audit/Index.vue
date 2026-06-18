<!--
    resources/js/Pages/Audit/Index.vue
    Penampil log audit append-only + ekspor CSV (FR-19/20).
-->
<template>
    <BaseLayout :nav-groups="navGroups" title="Log Audit">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Log Audit</h1>
                    <p class="page__sub">{{ logs.total }} catatan · append-only, tak dapat diubah.</p>
                </div>
                <AppButton variant="outline" tag="a" href="/audit/export" external>
                    <template #icon><DownloadIcon :size="14" /></template>
                    Ekspor CSV
                </AppButton>
            </div>

            <div class="filters">
                <div class="filters__search">
                    <SearchIcon class="filters__search-icon" :size="16" />
                    <input
                        v-model="f.aksi"
                        type="search"
                        class="filters__search-input"
                        placeholder="Filter aksi… (mis. certificate.issued)"
                        @keyup.enter="applyNow"
                    />
                    <span v-if="loading" class="filters__spinner" aria-hidden="true"></span>
                </div>
                <button v-if="f.aksi" type="button" class="filters__reset" @click="reset">
                    <XIcon :size="14" /> Reset
                </button>
            </div>

            <div class="page__card">
                <table class="tbl">
                    <thead><tr><th>Waktu</th><th>Aktor</th><th>Aksi</th><th>Entitas</th><th>IP</th><th>Detail</th></tr></thead>
                    <tbody>
                        <tr v-for="l in logs.data" :key="l.id">
                            <td class="tbl__mono">{{ l.waktu }}</td>
                            <td>{{ l.aktor }}</td>
                            <td><AppBadge color="info" size="sm">{{ l.aksi }}</AppBadge></td>
                            <td class="tbl__mono">{{ l.entitas }}</td>
                            <td class="muted">{{ l.ip || '—' }}</td>
                            <td class="tbl__detail">{{ l.detail ? JSON.stringify(l.detail) : '—' }}</td>
                        </tr>
                        <tr v-if="!logs.data.length"><td colspan="6" class="tbl__empty">Belum ada catatan.</td></tr>
                    </tbody>
                </table>

                <div v-if="logs.links.length > 3" class="pagination">
                    <component :is="link.url ? Link : 'span'" v-for="(link, i) in logs.links" :key="i"
                        :href="link.url || undefined" class="pagination__link"
                        :class="{ 'pagination__link--active': link.active, 'pagination__link--disabled': !link.url }"
                        v-html="link.label" />
                </div>
            </div>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { SearchIcon, DownloadIcon, XIcon } from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import { navGroups } from '@/data/navGroups';

interface LogRow { id: number; waktu: string; aktor: string; aksi: string; entitas: string; ip: string | null; detail: unknown; }
interface PageLink { url: string | null; label: string; active: boolean; }

const props = defineProps<{
    filters: Record<string, string>;
    logs: { data: LogRow[]; links: PageLink[]; total: number };
}>();

const f = reactive({ aksi: props.filters.aksi ?? '' });
const loading = ref(false);

// Auto-apply: ketik filter → cari otomatis (debounce 350ms).
let debounce: number | undefined;
watch(f, () => {
    clearTimeout(debounce);
    debounce = window.setTimeout(apply, 350);
});

function applyNow() {
    clearTimeout(debounce);
    apply();
}
function apply() {
    router.get('/audit', f.aksi ? { aksi: f.aksi } : {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => { loading.value = true; },
        onFinish: () => { loading.value = false; },
    });
}
function reset() {
    f.aksi = '';
}
</script>

<style scoped>
.page { padding: 24px; max-width: 1200px; margin: 0 auto; }
.page__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.page__title { font-size: 22px; font-weight: 700; color: var(--color-text-primary); }
.page__sub { font-size: 13.5px; color: var(--color-text-muted); margin-top: 2px; }
.filters { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; align-items: center; }
.filters__search { position: relative; display: flex; align-items: center; flex: 1 1 280px; min-width: 220px; }
.filters__search-icon { position: absolute; left: 14px; color: var(--color-text-subtle); pointer-events: none; }
.filters__search-input {
    width: 100%; height: 38px; padding: 0 38px 0 40px;
    border-radius: 999px; border: 1.5px solid var(--color-border);
    background: var(--color-bg, #f1f5f9); color: var(--color-text-primary);
    font-size: 13.5px; font-family: var(--font-sans); outline: none;
    transition: border-color 150ms ease, background 150ms ease, box-shadow 150ms ease;
}
.filters__search-input::placeholder { color: var(--color-text-subtle); }
.filters__search-input:focus {
    border-color: var(--color-primary); background: var(--color-surface);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 14%, transparent);
}
.filters__spinner {
    position: absolute; right: 14px; width: 14px; height: 14px;
    border: 2px solid var(--color-border); border-top-color: var(--color-primary);
    border-radius: 50%; animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.filters__reset {
    display: inline-flex; align-items: center; gap: 5px; flex-shrink: 0;
    height: 38px; padding: 0 14px; border-radius: 8px;
    border: 1px solid var(--color-border); background: var(--color-surface);
    color: var(--color-text-muted); font-size: 13px; font-family: var(--font-sans); cursor: pointer;
    transition: color 150ms ease, border-color 150ms ease;
}
.filters__reset:hover { color: var(--color-text-primary); border-color: var(--color-text-muted); }
.page__card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; padding: 8px; }
.tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
.tbl th, .tbl td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--color-border); vertical-align: top; }
.tbl th { font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--color-text-muted); }
.tbl__mono { font-family: var(--font-mono, monospace); font-size: 11.5px; }
.tbl__detail { font-family: var(--font-mono, monospace); font-size: 11px; color: var(--color-text-muted); max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tbl__empty { text-align: center; color: var(--color-text-subtle); padding: 32px; }
.muted { color: var(--color-text-subtle); }
.pagination { display: flex; flex-wrap: wrap; gap: 4px; padding: 14px; justify-content: center; }
.pagination__link { padding: 6px 11px; border-radius: 8px; border: 1px solid var(--color-border); font-size: 13px; color: var(--color-text-muted); }
.pagination__link--active { background: var(--color-primary); color: #fff; border-color: var(--color-primary); }
.pagination__link--disabled { opacity: 0.4; }
</style>
