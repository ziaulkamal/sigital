<!--
    resources/js/Pages/Templates/Index.vue
    Manajemen template sertifikat (P6): daftar + tambah/ubah (modal) dengan unggah gambar latar.
    Perancang posisi field drag-drop = v-next; koordinat memakai default bila kosong.
-->
<template>
    <BaseLayout :nav-groups="navGroups" title="Template Sertifikat">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Template Sertifikat</h1>
                    <p class="page__sub">Unggah gambar latar; field nama/acara/QR/TTD ditata otomatis.</p>
                </div>
                <AppButton variant="primary" @click="openCreate">
                    <template #icon><PlusIcon :size="15" /></template>
                    Tambah
                </AppButton>
            </div>

            <FlashBanner />

            <div class="page__card">
                <DataTable :columns="columns" :rows="templates" row-id="id"
                    search-placeholder="Cari template…" :search-keys="['nama', 'deskripsi']">
                    <template #cell-background="{ row }">
                        <img v-if="row.background" :src="String(row.background)" alt="Latar" class="tpl__thumb" />
                        <span v-else class="tpl__muted">—</span>
                    </template>
                    <template #cell-is_active="{ row }">
                        <AppBadge :color="row.is_active ? 'success' : 'default'" size="sm">
                            {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                        </AppBadge>
                    </template>
                    <template #row-actions="{ row }">
                        <div class="tpl__actions">
                            <button class="tpl__btn" @click="openEdit(row)"><PencilIcon :size="15" /></button>
                            <button v-if="row.is_active" class="tpl__btn tpl__btn--danger" @click="deactivate(row)">
                                <PowerIcon :size="15" />
                            </button>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <AppModal v-model="modalOpen" :title="editing ? 'Ubah Template' : 'Tambah Template'">
            <form class="tpl__form" @submit.prevent="submit">
                <AppInput v-model="form.nama" label="Nama" required :error="form.errors.nama" />
                <AppTextarea v-model="form.deskripsi" label="Deskripsi" :rows="2" :error="form.errors.deskripsi" />
                <div class="tpl__field">
                    <label class="tpl__label">Gambar Latar (PNG/JPG/PDF, maks 5MB)</label>
                    <input type="file" accept="image/png,image/jpeg,application/pdf" @change="onFile" />
                    <p v-if="form.errors.background" class="tpl__error">{{ form.errors.background }}</p>
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
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { navGroups } from '@/data/navGroups';

interface Template { id: number; nama: string; deskripsi: string | null; background: string | null; is_global: boolean; is_active: boolean; }
defineProps<{ templates: Template[] }>();

const columns = [
    { key: 'nama', label: 'Nama', sortable: true },
    { key: 'deskripsi', label: 'Deskripsi' },
    { key: 'background', label: 'Latar' },
    { key: 'is_active', label: 'Status' },
];

const modalOpen = ref(false);
const editing = ref<number | null>(null);
const form = useForm<{ nama: string; deskripsi: string; background: File | null }>({
    nama: '', deskripsi: '', background: null,
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
    form.deskripsi = row.deskripsi ? String(row.deskripsi) : '';
    modalOpen.value = true;
}

function onFile(e: Event) {
    const target = e.target as HTMLInputElement;
    form.background = target.files?.[0] ?? null;
}

function submit() {
    const opts = { onSuccess: () => { modalOpen.value = false; } };
    if (editing.value) {
        form.post(`/templates/${editing.value}`, opts);
    } else {
        form.post('/templates', opts);
    }
}

function deactivate(row: Record<string, unknown>) {
    if (confirm(`Nonaktifkan template ${row.nama}?`)) {
        useForm({}).delete(`/templates/${row.id}`);
    }
}
</script>

<style scoped>
.page { padding: 24px; max-width: 1200px; margin: 0 auto; }
.page__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.page__title { font-size: 22px; font-weight: 700; color: var(--color-text-primary); }
.page__sub { font-size: 13.5px; color: var(--color-text-muted); margin-top: 2px; }
.page__card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; padding: 8px; }
.tpl__thumb { height: 34px; object-fit: contain; border: 1px solid var(--color-border); border-radius: 4px; }
.tpl__muted { color: var(--color-text-subtle); }
.tpl__actions { display: flex; gap: 6px; }
.tpl__btn { display: inline-flex; padding: 6px; border-radius: 8px; border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text-muted); cursor: pointer; }
.tpl__btn:hover { color: var(--color-text-primary); }
.tpl__btn--danger:hover { color: #dc2626; border-color: #fecaca; }
.tpl__form { display: flex; flex-direction: column; gap: 16px; }
.tpl__field { display: flex; flex-direction: column; gap: 6px; }
.tpl__label { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }
.tpl__error { font-size: 12px; color: #dc2626; }
</style>
