<!--
    resources/js/Pages/Certificates/Index.vue
    Arsip sertifikat: pencarian (nama/acara/nomor/tanggal) + unduh/email/cabut.
-->
<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Arsip Sertifikat</h1>
                    <p class="page__sub">{{ certificates.total }} sertifikat terarsip.</p>
                </div>
            </div>

            <FlashBanner />

            <!-- Filter pencarian (auto-apply, gaya search topbar) -->
            <div class="filters">
                <div class="filters__search">
                    <SearchIcon class="filters__search-icon" :size="16" />
                    <input
                        v-model="f.q"
                        type="search"
                        class="filters__search-input"
                        placeholder="Cari nama, nomor, atau acara…"
                        @keyup.enter="applyNow"
                    />
                    <span v-if="loading" class="filters__spinner" aria-hidden="true"></span>
                </div>
                <AppSelect class="filters__field" v-model="f.event_id" :options="eventOpts" placeholder="Semua acara" />
                <AppSelect class="filters__field" v-model="f.status" :options="statusOpts" placeholder="Semua status" />
                <AppInput class="filters__field" v-model="f.from" type="date" />
                <AppInput class="filters__field" v-model="f.to" type="date" />
                <button v-if="hasActiveFilter" type="button" class="filters__reset" @click="reset">
                    <XIcon :size="14" /> Reset
                </button>
            </div>

            <div class="page__card">
                <table class="tbl">
                    <thead>
                        <tr><th>Nomor</th><th>Nama</th><th>Acara</th><th>Terbit</th><th>Status</th><th class="th-actions">Aksi</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in certificates.data" :key="c.id">
                            <td class="tbl__mono">{{ c.nomor }}</td>
                            <td>{{ c.nama }}</td>
                            <td>{{ c.acara }}</td>
                            <td>{{ c.issued_at || '—' }}</td>
                            <td>
                                <AppBadge :color="c.status === 'revoked' ? 'danger' : 'success'" size="sm">
                                    {{ c.status === 'revoked' ? 'Dicabut' : 'Asli' }}
                                </AppBadge>
                            </td>
                            <td class="cell-actions">
                                <div class="row-actions">
                                    <a :href="`/certificates/${c.id}/download`" class="icon-btn" title="Unduh PDF"><DownloadIcon :size="15" /></a>
                                    <button v-if="c.has_email" class="icon-btn" title="Kirim email" @click="sendEmail(c)"><MailIcon :size="15" /></button>
                                    <span v-else class="icon-btn icon-btn--ghost" title="Tanpa email"><MailIcon :size="15" /></span>
                                    <button v-if="c.status !== 'revoked'" class="icon-btn icon-btn--danger" title="Cabut sertifikat" @click="revoke(c)"><BanIcon :size="15" /></button>
                                    <span v-else class="icon-slot"></span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!certificates.data.length"><td colspan="6" class="tbl__empty">Tidak ada sertifikat ditemukan.</td></tr>
                    </tbody>
                </table>

                <div v-if="certificates.links.length > 3" class="pagination">
                    <component :is="link.url ? Link : 'span'" v-for="(link, i) in certificates.links" :key="i"
                        :href="link.url || undefined" class="pagination__link"
                        :class="{ 'pagination__link--active': link.active, 'pagination__link--disabled': !link.url }"
                        v-html="link.label" />
                </div>
            </div>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { reactive, ref, computed, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { SearchIcon, DownloadIcon, MailIcon, BanIcon, XIcon } from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppSelect from '@/Components/App/AppSelect.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { navGroups } from '@/data/navGroups';

interface CertRow { id: number; nomor: string; nama: string; acara: string; status: string; issued_at: string | null; has_email: boolean; }
interface PageLink { url: string | null; label: string; active: boolean; }

const props = defineProps<{
    filters: Record<string, string>;
    events: { id: number; nama: string }[];
    certificates: { data: CertRow[]; links: PageLink[]; total: number };
}>();

const f = reactive({
    q: props.filters.q ?? '', event_id: props.filters.event_id ?? '',
    status: props.filters.status ?? '', from: props.filters.from ?? '', to: props.filters.to ?? '',
});

const eventOpts = props.events.map((e) => ({ value: e.id, label: e.nama }));
const statusOpts = [{ value: 'issued', label: 'Asli' }, { value: 'revoked', label: 'Dicabut' }];

const loading = ref(false);
const hasActiveFilter = computed(() => Object.values(f).some((v) => v !== '' && v !== null));

// Auto-apply: setiap perubahan filter memicu pencarian (debounce 350ms).
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
    router.get('/certificates', cleaned(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => { loading.value = true; },
        onFinish: () => { loading.value = false; },
    });
}
function reset() {
    // Kosongkan filter; watcher otomatis menjalankan pencarian ulang.
    f.q = ''; f.event_id = ''; f.status = ''; f.from = ''; f.to = '';
}
function cleaned() {
    return Object.fromEntries(Object.entries(f).filter(([, v]) => v !== '' && v !== null));
}
function sendEmail(c: CertRow) {
    if (confirm(`Kirim sertifikat ke email ${c.nama}?`)) {
        router.post(`/certificates/${c.id}/email`, {}, { preserveScroll: true });
    }
}
function revoke(c: CertRow) {
    const alasan = prompt(`Alasan pencabutan untuk ${c.nomor}:`) ?? '';
    router.post(`/certificates/${c.id}/revoke`, { alasan }, { preserveScroll: true });
}
</script>

<style scoped>
.page { padding: 24px; max-width: 1200px; margin: 0 auto; }
.page__header { margin-bottom: 18px; }
.page__title { font-size: 22px; font-weight: 700; color: var(--color-text-primary); }
.page__sub { font-size: 13.5px; color: var(--color-text-muted); margin-top: 2px; }
.filters { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; align-items: center; }

/* Search pill (mengikuti gaya search topbar) */
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
.filters__search-input::-webkit-search-cancel-button { cursor: pointer; }
.filters__spinner {
    position: absolute; right: 14px; width: 14px; height: 14px;
    border: 2px solid var(--color-border); border-top-color: var(--color-primary);
    border-radius: 50%; animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.filters__field { width: 160px; }

.filters__reset {
    display: inline-flex; align-items: center; gap: 5px; flex-shrink: 0;
    height: 38px; padding: 0 14px; border-radius: 8px;
    border: 1px solid var(--color-border); background: var(--color-surface);
    color: var(--color-text-muted); font-size: 13px; font-family: var(--font-sans); cursor: pointer;
    transition: color 150ms ease, border-color 150ms ease;
}
.filters__reset:hover { color: var(--color-text-primary); border-color: var(--color-text-muted); }

@media (max-width: 640px) {
    .filters__search { flex: 1 1 100%; }
    .filters__field { flex: 1 1 140px; width: auto; }
    .filters__reset { width: 100%; justify-content: center; }
}
.page__card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; padding: 8px; }
.tbl { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.tbl th, .tbl td { text-align: left; padding: 11px 14px; border-bottom: 1px solid var(--color-border); }
.tbl th { font-size: 12px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--color-text-muted); }
.tbl__mono { font-family: var(--font-mono, monospace); font-size: 12px; }
.tbl__empty { text-align: center; color: var(--color-text-subtle); padding: 32px; }
.th-actions { text-align: right; }
.cell-actions { width: 1%; white-space: nowrap; text-align: right; }
.row-actions { display: inline-flex; gap: 6px; justify-content: flex-end; }
.icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text-muted); cursor: pointer; transition: background 140ms ease, color 140ms ease, border-color 140ms ease; }
.icon-btn:hover { color: var(--color-text-primary); border-color: var(--color-text-muted); }
.icon-btn--danger:hover { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
.icon-btn--ghost { opacity: 0.3; cursor: not-allowed; }
.icon-btn--ghost:hover { color: var(--color-text-muted); border-color: var(--color-border); background: var(--color-surface); }
.icon-slot { display: inline-block; width: 32px; height: 32px; }
.pagination { display: flex; flex-wrap: wrap; gap: 4px; padding: 14px; justify-content: center; }
.pagination__link { padding: 6px 11px; border-radius: 8px; border: 1px solid var(--color-border); font-size: 13px; color: var(--color-text-muted); }
.pagination__link--active { background: var(--color-primary); color: #fff; border-color: var(--color-primary); }
.pagination__link--disabled { opacity: 0.4; }
</style>
