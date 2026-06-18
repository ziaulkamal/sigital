<!--
    resources/js/Pages/Events/Index.vue
    Daftar acara dengan status, jumlah peserta, dan pintasan ke detail.
-->
<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Acara</h1>
                    <p class="page__sub">Kelola acara, peserta, dan penerbitan sertifikat.</p>
                </div>
                <AppButton variant="primary" tag="a" href="/events/create">
                    <template #icon><PlusIcon :size="15" /></template>
                    Acara Baru
                </AppButton>
            </div>

            <FlashBanner />

            <div class="page__card">
                <DataTable :columns="columns" :rows="events" row-id="id"
                    search-placeholder="Cari acara…" :search-keys="['nama', 'kode', 'lokasi']">
                    <template #cell-nama="{ row }">
                        <a :href="`/events/${row.id}`" class="ev__link">{{ row.nama }}</a>
                        <p class="ev__sub">{{ row.kode || '—' }}</p>
                    </template>
                    <template #cell-status="{ row }">
                        <AppBadge :color="statusColor(String(row.status))" size="sm">{{ statusLabel(String(row.status)) }}</AppBadge>
                    </template>
                    <template #row-actions="{ row }">
                        <a :href="`/events/${row.id}`" class="ev__action"><EyeIcon :size="15" /></a>
                    </template>
                </DataTable>
            </div>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { PlusIcon, EyeIcon } from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import DataTable from '@/Components/App/DataTable.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { navGroups } from '@/data/navGroups';

interface EventRow { id: number; nama: string; kode: string | null; jadwal_mulai: string | null; lokasi: string | null; status: string; peserta: number; penanda_tangan: number; }
defineProps<{ events: EventRow[] }>();

const columns = [
    { key: 'nama', label: 'Acara', sortable: true },
    { key: 'jadwal_mulai', label: 'Jadwal', sortable: true },
    { key: 'lokasi', label: 'Lokasi' },
    { key: 'peserta', label: 'Peserta', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

function statusColor(s: string) {
    return ({ draft: 'default', siap_terbit: 'warning', selesai: 'success' } as Record<string, string>)[s] ?? 'default';
}
function statusLabel(s: string) {
    return ({ draft: 'Draft', siap_terbit: 'Siap Terbit', selesai: 'Selesai' } as Record<string, string>)[s] ?? s;
}
</script>

<style scoped>
.page { padding: 24px; max-width: 1200px; margin: 0 auto; }
.page__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.page__title { font-size: 22px; font-weight: 700; color: var(--color-text-primary); }
.page__sub { font-size: 13.5px; color: var(--color-text-muted); margin-top: 2px; }
.page__card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; padding: 8px; }
.ev__link { font-weight: 600; color: var(--color-text-primary); }
.ev__link:hover { color: var(--color-primary); }
.ev__sub { font-size: 12px; color: var(--color-text-subtle); }
.ev__action { display: inline-flex; padding: 6px; border-radius: 8px; border: 1px solid var(--color-border); color: var(--color-text-muted); }
.ev__action:hover { color: var(--color-text-primary); }
</style>
