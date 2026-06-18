<!--
    resources/js/Pages/Signatories/Index.vue
    Manajemen penanda tangan: daftar + tambah/ubah (modal) dengan unggah spesimen TTD.
-->
<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Penanda Tangan</h1>
                    <p class="page__sub">Kelola nama, jabatan, dan spesimen tanda tangan.</p>
                </div>
                <AppButton variant="primary" @click="openCreate">
                    <template #icon><PlusIcon :size="15" /></template>
                    Tambah
                </AppButton>
            </div>

            <FlashBanner />

            <div class="page__card">
                <DataTable :columns="columns" :rows="signatories" row-id="id"
                    search-placeholder="Cari penanda tangan…" :search-keys="['nama', 'jabatan']">
                    <template #cell-gambar_ttd="{ row }">
                        <img v-if="row.gambar_ttd" :src="String(row.gambar_ttd)" alt="TTD" class="sig__ttd" />
                        <span v-else class="sig__muted">—</span>
                    </template>
                    <template #cell-is_active="{ row }">
                        <AppBadge :color="row.is_active ? 'success' : 'default'" size="sm">
                            {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                        </AppBadge>
                    </template>
                    <template #row-actions="{ row }">
                        <div class="sig__actions">
                            <button class="sig__btn" @click="openEdit(row)"><PencilIcon :size="15" /></button>
                            <button v-if="row.is_active" class="sig__btn sig__btn--danger" @click="deactivate(row)">
                                <PowerIcon :size="15" />
                            </button>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <AppModal v-model="modalOpen" :title="editing ? 'Ubah Penanda Tangan' : 'Tambah Penanda Tangan'">
            <form class="sig__form" @submit.prevent="submit">
                <AppInput v-model="form.nama" label="Nama" required :error="form.errors.nama" />
                <AppInput v-model="form.jabatan" label="Jabatan" required :error="form.errors.jabatan" />
                <div class="sig__field">
                    <label class="sig__label">Spesimen TTD (PNG/JPG, maks 2MB)</label>
                    <input type="file" accept="image/png,image/jpeg" @change="onFile" />
                    <p v-if="form.errors.gambar_ttd" class="sig__error">{{ form.errors.gambar_ttd }}</p>
                </div>
            </form>
            <template #footer>
                <AppButton variant="ghost" @click="modalOpen = false">Batal</AppButton>
                <AppButton variant="primary" :loading="form.processing" @click="submit">Simpan</AppButton>
            </template>
        </AppModal>
    </BaseLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { PlusIcon, PencilIcon, PowerIcon } from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import DataTable from '@/Components/App/DataTable.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppModal from '@/Components/App/AppModal.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { navGroups } from '@/data/navGroups';

interface Signatory { id: number; nama: string; jabatan: string; gambar_ttd: string | null; is_active: boolean; }
defineProps<{ signatories: Signatory[] }>();

const columns = [
    { key: 'nama', label: 'Nama', sortable: true },
    { key: 'jabatan', label: 'Jabatan', sortable: true },
    { key: 'gambar_ttd', label: 'Spesimen' },
    { key: 'is_active', label: 'Status' },
];

const modalOpen = ref(false);
const editing = ref<number | null>(null);
const form = useForm<{ nama: string; jabatan: string; gambar_ttd: File | null }>({
    nama: '', jabatan: '', gambar_ttd: null,
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    modalOpen.value = true;
}

function openEdit(row: Record<string, unknown>) {
    editing.value = Number(row.id);
    form.reset();
    form.clearErrors();
    form.nama = String(row.nama);
    form.jabatan = String(row.jabatan);
    modalOpen.value = true;
}

function onFile(e: Event) {
    const target = e.target as HTMLInputElement;
    form.gambar_ttd = target.files?.[0] ?? null;
}

function submit() {
    const opts = { onSuccess: () => { modalOpen.value = false; } };
    if (editing.value) {
        form.post(`/signatories/${editing.value}`, opts);
    } else {
        form.post('/signatories', opts);
    }
}

function deactivate(row: Record<string, unknown>) {
    if (confirm(`Nonaktifkan ${row.nama}?`)) {
        useForm({}).delete(`/signatories/${row.id}`);
    }
}
</script>

<style scoped>
.page { padding: 24px; max-width: 1200px; margin: 0 auto; }
.page__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.page__title { font-size: 22px; font-weight: 700; color: var(--color-text-primary); }
.page__sub { font-size: 13.5px; color: var(--color-text-muted); margin-top: 2px; }
.page__card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; padding: 8px; }
.sig__ttd { height: 28px; object-fit: contain; }
.sig__muted { color: var(--color-text-subtle); }
.sig__actions { display: flex; gap: 6px; }
.sig__btn { display: inline-flex; padding: 6px; border-radius: 8px; border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text-muted); cursor: pointer; }
.sig__btn:hover { color: var(--color-text-primary); }
.sig__btn--danger:hover { color: #dc2626; border-color: #fecaca; }
.sig__form { display: flex; flex-direction: column; gap: 16px; }
.sig__field { display: flex; flex-direction: column; gap: 6px; }
.sig__label { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }
.sig__error { font-size: 12px; color: #dc2626; }
</style>
