<!--
    resources/js/Pages/Events/Index.vue
    Daftar acara dengan status, jumlah peserta, dan pintasan ke detail.
-->
<template>
    <BaseLayout :nav-groups="navGroups" title="Acara">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Acara</h1>
                    <p class="page__sub">Kelola acara, peserta, dan penerbitan sertifikat.</p>
                </div>
                <div class="ev__header-actions">
                    <AppButton variant="ghost" @click="joinOpen = true">
                        <template #icon><UserPlusIcon :size="15" /></template>
                        Gabung Acara
                    </AppButton>
                    <AppButton variant="primary" tag="a" href="/events/create">
                        <template #icon><PlusIcon :size="15" /></template>
                        Acara Baru
                    </AppButton>
                </div>
            </div>

            <FlashBanner />

            <div class="page__card">
                <DataTable :columns="columns" :rows="events" row-id="id"
                    search-placeholder="Cari acara…" :search-keys="['nama', 'kode', 'lokasi']">
                    <template #cell-nama="{ row }">
                        <Link :href="`/events/${row.id}`" class="ev__link">{{ row.nama }}</Link>
                        <p class="ev__sub">{{ row.kode || '—' }}</p>
                    </template>
                    <template #cell-status="{ row }">
                        <AppBadge :color="statusColor(String(row.status))" size="sm">{{ statusLabel(String(row.status)) }}</AppBadge>
                    </template>
                    <template #row-actions="{ row }">
                        <Link :href="`/events/${row.id}`" class="ev__action"><EyeIcon :size="15" /></Link>
                    </template>
                </DataTable>
            </div>
        </div>

        <AppModal v-model="joinOpen" title="Gabung Acara">
            <form class="ev__join" @submit.prevent="submitJoin">
                <p class="ev__join-hint">Masukkan kode undangan dari pemilik acara. Permintaan Anda menunggu persetujuan pemilik.</p>
                <AppInput v-model="joinForm.join_code" label="Kode Undangan" placeholder="MIS. A1B2C3D4" required :error="joinForm.errors.join_code" />
            </form>
            <template #footer>
                <AppButton variant="ghost" @click="joinOpen = false">Batal</AppButton>
                <AppButton variant="primary" :loading="joinForm.processing" @click="submitJoin">Kirim Permintaan</AppButton>
            </template>
        </AppModal>
    </BaseLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { PlusIcon, EyeIcon, UserPlusIcon } from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import DataTable from '@/Components/App/DataTable.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import AppModal from '@/Components/App/AppModal.vue';
import AppInput from '@/Components/App/AppInput.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { navGroups } from '@/data/navGroups';

interface EventRow { id: number; nama: string; kode: string | null; jadwal_mulai: string | null; lokasi: string | null; status: string; peserta: number; penanda_tangan: number; }
defineProps<{ events: EventRow[] }>();

const joinOpen = ref(false);
const joinForm = useForm({ join_code: '' });
function submitJoin() {
    joinForm.post('/events/join', { onSuccess: () => { joinOpen.value = false; joinForm.reset(); } });
}

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
.ev__header-actions { display: flex; gap: 10px; }
.ev__join { display: flex; flex-direction: column; gap: 14px; }
.ev__join-hint { font-size: 13px; color: var(--color-text-muted); line-height: 1.5; }
</style>
