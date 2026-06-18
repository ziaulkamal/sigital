<!--
    resources/js/Pages/Signatories/Index.vue
    Manajemen penanda tangan: daftar + tambah/ubah (modal) dengan unggah spesimen TTD.
-->
<template>
    <BaseLayout :nav-groups="navGroups" title="Penanda Tangan">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Penanda Tangan</h1>
                    <p class="page__sub">Kelola nama, jabatan, dan spesimen tanda tangan.</p>
                </div>
                <AppButton variant="primary" :disabled="needsOrg" @click="openCreate">
                    <template #icon><PlusIcon :size="15" /></template>
                    Tambah
                </AppButton>
            </div>

            <FlashBanner />

            <!-- SuperAdmin mode "Semua organisasi": penanda tangan ter-scope org → wajib pilih org dulu. -->
            <div v-if="needsOrg" class="sig__notice">
                <TriangleAlertIcon :size="16" class="sig__notice-icon" />
                <span>
                    Anda sedang di mode <strong>“Semua organisasi”</strong>. Pilih satu organisasi
                    di switcher (kiri atas) sebelum menambah penanda tangan, agar datanya dimiliki organisasi yang benar.
                </span>
            </div>

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

        <!-- P3: konfirmasi nama serupa sebelum membuat penanda tangan baru. -->
        <AppModal v-model="confirmOpen" title="Nama serupa sudah ada">
            <div class="sig__confirm">
                <p class="sig__confirm-lead">
                    Sudah ada penanda tangan dengan nama serupa
                    <strong>“{{ candidates?.nama }}”</strong>. Apakah salah satu di bawah ini yang Anda maksud?
                </p>
                <ul class="sig__matches">
                    <li v-for="m in candidates?.matches ?? []" :key="m.id" class="sig__match">
                        <img v-if="m.gambar_ttd" :src="m.gambar_ttd" alt="TTD" class="sig__ttd" />
                        <span v-else class="sig__muted">—</span>
                        <div class="sig__match-info">
                            <span class="sig__match-nama">{{ m.nama }}</span>
                            <span class="sig__match-jabatan">{{ m.jabatan }}</span>
                        </div>
                        <AppButton variant="ghost" size="sm" @click="useExisting">Gunakan ini</AppButton>
                    </li>
                </ul>
            </div>
            <template #footer>
                <AppButton variant="ghost" @click="useExisting">Batal</AppButton>
                <AppButton variant="primary" :loading="form.processing" @click="createAnyway">
                    Tetap buat baru
                </AppButton>
            </template>
        </AppModal>
    </BaseLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { PlusIcon, PencilIcon, PowerIcon, TriangleAlertIcon } from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import DataTable from '@/Components/App/DataTable.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppModal from '@/Components/App/AppModal.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { swalConfirm } from '@/Composables/useSwal';
import { navGroups } from '@/data/navGroups';

interface Signatory { id: number; nama: string; jabatan: string; gambar_ttd: string | null; is_active: boolean; }
defineProps<{ signatories: Signatory[] }>();

const columns = [
    { key: 'nama', label: 'Nama', sortable: true },
    { key: 'jabatan', label: 'Jabatan', sortable: true },
    { key: 'gambar_ttd', label: 'Spesimen' },
    { key: 'is_active', label: 'Status' },
];

interface Candidate { id: number; nama: string; jabatan: string; gambar_ttd: string | null; }
interface CandidatePayload { nama: string; jabatan: string; matches: Candidate[]; }

const modalOpen = ref(false);
const confirmOpen = ref(false);
const editing = ref<number | null>(null);
const form = useForm<{ nama: string; jabatan: string; gambar_ttd: File | null; confirm: string }>({
    nama: '', jabatan: '', gambar_ttd: null, confirm: '',
});

// Kandidat nama serupa dikirim server lewat flash (pola pratinjau impor CSV).
const page = usePage();
const candidates = ref<CandidatePayload | null>(null);

// SuperAdmin di mode "Semua organisasi" (tanpa org terpilih) → cegah membuat penanda tangan
// tanpa organisasi. Data tenancy dibagikan via HandleInertiaRequests.
const needsOrg = computed(() => {
    const t = page.props.tenancy as { is_super_admin?: boolean; current_organization_id?: number | null } | null;
    return !!t?.is_super_admin && !t?.current_organization_id;
});

function flashCandidates(): CandidatePayload | null {
    return (page.props.flash as { signatoryCandidates?: CandidatePayload })?.signatoryCandidates ?? null;
}

function openCreate() {
    if (needsOrg.value) return; // wajib pilih organisasi dulu (SuperAdmin "Semua")
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
    if (editing.value) {
        form.post(`/signatories/${editing.value}`, { onSuccess: () => { modalOpen.value = false; } });
        return;
    }
    // Buat baru: tanpa confirm dulu → server bisa membalas kandidat serupa (membuka modal konfirmasi).
    form.confirm = '';
    form.post('/signatories', {
        preserveState: true,
        onSuccess: () => {
            const payload = flashCandidates();
            if (payload) {
                candidates.value = payload;
                modalOpen.value = false;
                confirmOpen.value = true;
            } else {
                finishCreate();
            }
        },
    });
}

/** "Tetap buat baru" dari modal konfirmasi → kirim ulang dengan confirm=create_new. */
function createAnyway() {
    form.confirm = 'create_new';
    form.post('/signatories', {
        preserveState: true,
        onSuccess: () => { confirmOpen.value = false; finishCreate(); },
    });
}

/** Memilih kandidat yang sudah ada / batal → tak membuat duplikat. */
function useExisting() {
    confirmOpen.value = false;
    candidates.value = null;
    form.reset();
    form.clearErrors();
}

function finishCreate() {
    modalOpen.value = false;
    candidates.value = null;
    form.reset();
    form.clearErrors();
}

async function deactivate(row: Record<string, unknown>) {
    const ok = await swalConfirm({
        title: 'Nonaktifkan penanda tangan?',
        text: `"${row.nama}" tidak akan bisa dipilih lagi pada acara.`,
        confirmText: 'Ya, nonaktifkan',
        danger: true,
    });
    if (ok) useForm({}).delete(`/signatories/${row.id}`);
}
</script>

<style scoped>
.page { padding: 24px; max-width: 1200px; margin: 0 auto; }
.page__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.page__title { font-size: 22px; font-weight: 700; color: var(--color-text-primary); }
.page__sub { font-size: 13.5px; color: var(--color-text-muted); margin-top: 2px; }
.page__card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; padding: 8px; }
.sig__notice { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 16px; padding: 12px 14px; border-radius: 10px; font-size: 13px; line-height: 1.5; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; }
.sig__notice-icon { flex-shrink: 0; margin-top: 1px; color: #d97706; }
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
.sig__confirm { display: flex; flex-direction: column; gap: 14px; }
.sig__confirm-lead { font-size: 13.5px; color: var(--color-text-muted); line-height: 1.5; }
.sig__matches { display: flex; flex-direction: column; gap: 8px; list-style: none; margin: 0; padding: 0; }
.sig__match { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border: 1px solid var(--color-border); border-radius: 10px; }
.sig__match-info { display: flex; flex-direction: column; flex: 1; }
.sig__match-nama { font-size: 13.5px; font-weight: 600; color: var(--color-text-primary); }
.sig__match-jabatan { font-size: 12.5px; color: var(--color-text-muted); }
</style>
