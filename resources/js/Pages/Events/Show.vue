<!--
    resources/js/Pages/Events/Show.vue
    Detail acara: info, peserta (manual + impor CSV), dan penerbitan batch dengan progres.
-->
<template>
    <BaseLayout :nav-groups="navGroups" title="Detail Acara">
        <div class="page">
            <div class="page__header">
                <div>
                    <Link href="/events" class="page__back">← Acara</Link>
                    <h1 class="page__title">{{ event.nama }}</h1>
                    <p class="page__sub">{{ event.kode || '—' }} · {{ event.lokasi || 'Tanpa lokasi' }}</p>
                </div>
                <div class="page__head-actions">
                    <AppBadge :color="statusColor(event.status)" size="md">{{ statusLabel(event.status) }}</AppBadge>
                    <AppButton variant="outline" tag="a" :href="`/events/${event.id}/edit`">
                        <template #icon><PencilIcon :size="14" /></template>
                        Ubah
                    </AppButton>
                </div>
            </div>

            <FlashBanner />

            <!-- Ringkasan kesiapan -->
            <div class="grid">
                <div class="info-card">
                    <h3>Template</h3>
                    <p>{{ event.template?.nama ?? 'Belum dipilih' }}</p>
                </div>
                <div class="info-card">
                    <h3>Penanda Tangan ({{ event.signatories.length }})</h3>
                    <p v-if="event.signatories.length">{{ event.signatories.map(s => s.nama).join(', ') }}</p>
                    <p v-else class="muted">Belum ada</p>
                </div>
                <div class="info-card">
                    <h3>Kesiapan Terbit</h3>
                    <AppBadge :color="event.can_issue ? 'success' : 'danger'" size="sm">
                        {{ event.can_issue ? 'Siap diterbitkan' : 'Butuh template + penanda tangan' }}
                    </AppBadge>
                </div>
            </div>

            <!-- Progres batch -->
            <div v-if="batch.active" class="batch">
                <div class="batch__head">
                    <span>Penerbitan massal berjalan… {{ batch.processed }}/{{ batch.total }}</span>
                    <span>{{ batch.progress }}%</span>
                </div>
                <AppProgressBar :value="batch.progress" />
            </div>

            <!-- Peserta -->
            <div class="page__card">
                <div class="card__toolbar">
                    <h2 class="card__title">Peserta ({{ participants.length }})</h2>
                    <div class="card__tools">
                        <AppButton variant="outline" size="sm" @click="importOpen = true">
                            <template #icon><UploadIcon :size="14" /></template>
                            Impor CSV
                        </AppButton>
                        <AppButton variant="outline" size="sm" @click="manualOpen = true">
                            <template #icon><UserPlusIcon :size="14" /></template>
                            Tambah
                        </AppButton>
                        <AppButton variant="primary" size="sm" :disabled="!event.can_issue" :loading="issuing" @click="issueBatch">
                            <template #icon><AwardIcon :size="14" /></template>
                            Terbitkan Massal
                        </AppButton>
                    </div>
                </div>

                <DataTable :columns="columns" :rows="participants" row-id="registration_id"
                    search-placeholder="Cari peserta…" :search-keys="['nama', 'email']">
                    <template #cell-sumber="{ row }">
                        <AppBadge color="info" size="sm">{{ row.sumber }}</AppBadge>
                    </template>
                    <template #cell-certificate="{ row }">
                        <span v-if="row.certificate" class="cert-no">
                            <AppBadge :color="row.certificate.status === 'revoked' ? 'danger' : 'success'" size="sm">
                                {{ row.certificate.status === 'revoked' ? 'Dicabut' : row.certificate.nomor }}
                            </AppBadge>
                        </span>
                        <span v-else class="muted">Belum terbit</span>
                    </template>
                    <template #row-actions="{ row }">
                        <div class="row-actions">
                            <a v-if="row.certificate" :href="`/certificates/${row.certificate.id}/download`" class="icon-btn" title="Unduh PDF">
                                <DownloadIcon :size="15" />
                            </a>
                            <button v-else-if="event.can_issue" class="icon-btn" title="Terbitkan" @click="issueOne(row)">
                                <AwardIcon :size="15" />
                            </button>
                            <button v-if="!row.certificate" class="icon-btn icon-btn--danger" title="Hapus" @click="removeParticipant(row)">
                                <Trash2Icon :size="15" />
                            </button>
                        </div>
                    </template>
                </DataTable>
            </div>

            <!-- Kolaborasi acara (P7) -->
            <div class="page__card">
                <div class="card__toolbar">
                    <h2 class="card__title">Kolaborasi ({{ event.members.length }})</h2>
                    <div v-if="event.is_owner && event.join_code" class="collab__code">
                        <span class="collab__code-label">Kode undangan:</span>
                        <code class="collab__code-val">{{ event.join_code }}</code>
                        <button class="collab__copy" title="Salin" @click="copyCode"><CopyIcon :size="14" /></button>
                    </div>
                </div>

                <table class="collab__table">
                    <thead><tr><th>Nama</th><th>Peran</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="m in event.members" :key="m.id">
                            <td>
                                <span class="collab__name">{{ m.user?.name ?? '—' }}</span>
                                <span class="collab__email">{{ m.user?.email }}</span>
                            </td>
                            <td><AppBadge :color="m.role === 'owner' ? 'info' : 'default'" size="sm">{{ m.role === 'owner' ? 'Pemilik' : 'Kolaborator' }}</AppBadge></td>
                            <td><AppBadge :color="memberStatusColor(m.status)" size="sm">{{ memberStatusLabel(m.status) }}</AppBadge></td>
                            <td class="collab__actions">
                                <template v-if="event.is_owner && m.status === 'pending'">
                                    <button class="icon-btn" title="Setujui" @click="approveMember(m.id)"><CheckIcon :size="15" /></button>
                                    <button class="icon-btn icon-btn--danger" title="Tolak" @click="rejectMember(m.id)"><XIcon :size="15" /></button>
                                </template>
                            </td>
                        </tr>
                        <tr v-if="!event.members.length"><td colspan="4" class="muted">Belum ada anggota.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal tambah manual -->
        <AppModal v-model="manualOpen" title="Tambah Peserta">
            <form class="form-col" @submit.prevent="addManual">
                <AppInput v-model="manualForm.nama" label="Nama" required :error="manualForm.errors.nama" />
                <AppInput v-model="manualForm.email" type="email" label="Email (opsional)" :error="manualForm.errors.email" />
                <AppInput v-model="manualForm.nik" label="NIK (opsional)" :error="manualForm.errors.nik" />
            </form>
            <template #footer>
                <AppButton variant="ghost" @click="manualOpen = false">Batal</AppButton>
                <AppButton variant="primary" :loading="manualForm.processing" @click="addManual">Simpan</AppButton>
            </template>
        </AppModal>

        <!-- Modal impor CSV -->
        <AppModal v-model="importOpen" title="Impor Peserta dari CSV" size="lg">
            <div v-if="!preview" class="import">
                <p class="import__hint">Kolom yang dikenali: <code>nama</code> (wajib), <code>email</code>, <code>nik</code>.</p>
                <input type="file" accept=".csv,text/csv" @change="onCsv" />
                <p v-if="importForm.errors.file" class="err">{{ importForm.errors.file }}</p>
            </div>
            <div v-else class="import">
                <div class="import__summary">
                    <AppBadge color="success" size="sm">{{ preview.summary.ok }} valid</AppBadge>
                    <AppBadge color="warning" size="sm">{{ preview.summary.duplikat }} duplikat</AppBadge>
                    <AppBadge color="danger" size="sm">{{ preview.summary.error }} error</AppBadge>
                </div>
                <div class="import__table-wrap">
                    <table class="import__table">
                        <thead><tr><th>Baris</th><th>Nama</th><th>Email</th><th>Status</th><th>Pesan</th></tr></thead>
                        <tbody>
                            <tr v-for="r in preview.rows" :key="r.baris" :class="`r--${r.status}`">
                                <td>{{ r.baris }}</td><td>{{ r.nama }}</td><td>{{ r.email }}</td>
                                <td><AppBadge :color="rowColor(r.status)" size="sm">{{ r.status }}</AppBadge></td>
                                <td class="muted">{{ r.pesan }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="import__note">Hanya baris berstatus <strong>valid</strong> ({{ preview.summary.ok }}) yang akan disimpan.</p>
            </div>
            <template #footer>
                <AppButton variant="ghost" @click="closeImport">Batal</AppButton>
                <AppButton v-if="!preview" variant="primary" :loading="importForm.processing" :disabled="!importForm.file" @click="doPreview">
                    Pratinjau
                </AppButton>
                <AppButton v-else variant="primary" :loading="committing" :disabled="!preview.summary.ok" @click="commitImport">
                    Simpan {{ preview.summary.ok }} Peserta
                </AppButton>
            </template>
        </AppModal>
    </BaseLayout>
</template>

<script setup lang="ts">
import { computed, reactive, ref, onMounted, onUnmounted } from 'vue';
import { router, useForm, usePage, Link } from '@inertiajs/vue3';
import { PencilIcon, UploadIcon, UserPlusIcon, AwardIcon, DownloadIcon, Trash2Icon, CopyIcon, CheckIcon, XIcon } from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import DataTable from '@/Components/App/DataTable.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import AppModal from '@/Components/App/AppModal.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppProgressBar from '@/Components/App/AppProgressBar.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { swalConfirm } from '@/Composables/useSwal';
import { navGroups } from '@/data/navGroups';

interface Cert { id: number; nomor: string; status: string; }
interface Participant { registration_id: number; nama: string; email: string | null; sumber: string; status_kehadiran: string; certificate: Cert | null; }
interface Member { id: number; user: { id: number; name: string; email: string } | null; role: string; status: string; }
interface EventData { id: number; nama: string; kode: string | null; lokasi: string | null; status: string; can_issue: boolean; template: { id: number; nama: string } | null; signatories: { id: number; nama: string; jabatan: string }[]; is_owner: boolean; join_code: string | null; members: Member[]; }
interface PreviewRow { baris: number; nama: string; email: string; status: string; pesan: string | null; }
interface Preview { rows: PreviewRow[]; summary: { ok: number; duplikat: number; error: number }; }

const props = defineProps<{ event: EventData; participants: Participant[] }>();
const page = usePage();

const columns = [
    { key: 'nama', label: 'Nama', sortable: true },
    { key: 'email', label: 'Email' },
    { key: 'sumber', label: 'Sumber' },
    { key: 'certificate', label: 'Sertifikat' },
];

/* ---- Tambah manual ---- */
const manualOpen = ref(false);
const manualForm = useForm({ nama: '', email: '', nik: '' });
function addManual() {
    manualForm.post(`/events/${props.event.id}/participants`, {
        onSuccess: () => { manualForm.reset(); manualOpen.value = false; },
    });
}

/* ---- Hapus & terbit satuan ---- */
async function removeParticipant(row: Record<string, unknown>) {
    const ok = await swalConfirm({
        title: 'Hapus peserta?',
        text: `"${row.nama}" akan dihapus dari acara ini.`,
        confirmText: 'Ya, hapus',
        danger: true,
    });
    if (ok) router.delete(`/participants/${row.registration_id}`, { preserveScroll: true });
}
function issueOne(row: Record<string, unknown>) {
    router.post(`/registrations/${row.registration_id}/issue`, {}, { preserveScroll: true });
}

/* ---- Kolaborasi (P7) ---- */
function memberStatusColor(s: string) {
    return ({ pending: 'warning', approved: 'success', rejected: 'danger' } as Record<string, string>)[s] ?? 'default';
}
function memberStatusLabel(s: string) {
    return ({ pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' } as Record<string, string>)[s] ?? s;
}
function approveMember(id: number) {
    router.post(`/events/${props.event.id}/members/${id}/approve`, {}, { preserveScroll: true });
}
async function rejectMember(id: number) {
    const ok = await swalConfirm({
        title: 'Tolak permintaan gabung?',
        text: 'Pengguna ini tidak akan menjadi kolaborator acara.',
        confirmText: 'Ya, tolak',
        danger: true,
    });
    if (ok) router.post(`/events/${props.event.id}/members/${id}/reject`, {}, { preserveScroll: true });
}
function copyCode() {
    if (props.event.join_code) navigator.clipboard?.writeText(props.event.join_code);
}

/* ---- Impor CSV ---- */
const importOpen = ref(false);
const importForm = useForm<{ file: File | null }>({ file: null });
const committing = ref(false);
const preview = computed<Preview | null>(() => (page.props.flash as { importPreview?: Preview })?.importPreview ?? null);

function onCsv(e: Event) {
    importForm.file = (e.target as HTMLInputElement).files?.[0] ?? null;
}
function doPreview() {
    importForm.post(`/events/${props.event.id}/import/preview`, { preserveScroll: true, preserveState: true });
}
function commitImport() {
    committing.value = true;
    const rows = (preview.value?.rows ?? []).filter((r) => r.status === 'ok')
        .map((r) => ({ nama: r.nama, email: r.email || null, nik: null }));
    router.post(`/events/${props.event.id}/import`, { rows }, {
        onFinish: () => { committing.value = false; closeImport(); },
    });
}
function closeImport() {
    importOpen.value = false;
    importForm.reset();
}

/* ---- Penerbitan batch + polling progres ---- */
const issuing = ref(false);
const batch = reactive({ active: false, id: '', total: 0, processed: 0, progress: 0 });
let timer: number | undefined;

function issueBatch() {
    issuing.value = true;
    router.post(`/events/${props.event.id}/issue`, {}, {
        preserveScroll: true,
        onFinish: () => {
            issuing.value = false;
            const id = (page.props.flash as { batchId?: string })?.batchId;
            if (id) startPolling(id);
        },
    });
}
function startPolling(id: string) {
    batch.active = true; batch.id = id; batch.progress = 0;
    poll();
    timer = window.setInterval(poll, 1500);
}
async function poll() {
    try {
        const res = await fetch(`/batch/${batch.id}/status`, { headers: { Accept: 'application/json' } });
        const d = await res.json();
        batch.total = d.total ?? 0; batch.processed = d.processed ?? 0; batch.progress = d.progress ?? 0;
        if (d.finished) { stopPolling(); router.reload({ only: ['participants'] }); }
    } catch { stopPolling(); }
}
function stopPolling() {
    batch.active = false;
    if (timer) { clearInterval(timer); timer = undefined; }
}

onMounted(() => {
    const id = (page.props.flash as { batchId?: string })?.batchId;
    if (id) startPolling(id);
});
onUnmounted(stopPolling);

/* ---- helpers ---- */
function statusColor(s: string) { return ({ draft: 'default', siap_terbit: 'warning', selesai: 'success' } as Record<string, string>)[s] ?? 'default'; }
function statusLabel(s: string) { return ({ draft: 'Draft', siap_terbit: 'Siap Terbit', selesai: 'Selesai' } as Record<string, string>)[s] ?? s; }
function rowColor(s: string) { return ({ ok: 'success', duplikat: 'warning', error: 'danger' } as Record<string, string>)[s] ?? 'default'; }
</script>

<style scoped>
.page { padding: 24px; max-width: 1200px; margin: 0 auto; }
.page__header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 20px; gap: 16px; }
.page__back { font-size: 13px; color: var(--color-text-muted); }
.page__title { font-size: 22px; font-weight: 700; color: var(--color-text-primary); margin-top: 4px; }
.page__sub { font-size: 13.5px; color: var(--color-text-muted); margin-top: 2px; }
.page__head-actions { display: flex; align-items: center; gap: 10px; }
.grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 18px; }
.info-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 12px; padding: 16px; }
.info-card h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-text-muted); margin-bottom: 8px; }
.info-card p { font-size: 14px; color: var(--color-text-primary); }
.muted { color: var(--color-text-subtle); }
.batch { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 12px; padding: 16px; margin-bottom: 18px; }
.batch__head { display: flex; justify-content: space-between; font-size: 13px; color: var(--color-text-muted); margin-bottom: 8px; }
.page__card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; padding: 8px; margin-bottom: 18px; }
.page__card:last-child { margin-bottom: 0; }
.card__toolbar { display: flex; align-items: center; justify-content: space-between; padding: 12px 12px 4px; }
.card__title { font-size: 15px; font-weight: 700; color: var(--color-text-primary); }
.card__tools { display: flex; gap: 8px; }
.row-actions { display: flex; gap: 6px; }
.icon-btn { display: inline-flex; padding: 6px; border-radius: 8px; border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text-muted); cursor: pointer; }
.icon-btn:hover { color: var(--color-text-primary); }
.icon-btn--danger:hover { color: #dc2626; border-color: #fecaca; }
.cert-no { font-size: 12px; }
.form-col { display: flex; flex-direction: column; gap: 14px; }
.import { display: flex; flex-direction: column; gap: 14px; }
.import__hint { font-size: 13px; color: var(--color-text-muted); }
.import__hint code { background: var(--color-bg, #f1f5f9); padding: 1px 5px; border-radius: 4px; }
.import__summary { display: flex; gap: 8px; }
.import__table-wrap { max-height: 320px; overflow: auto; border: 1px solid var(--color-border); border-radius: 8px; }
.import__table { width: 100%; border-collapse: collapse; font-size: 13px; }
.import__table th, .import__table td { text-align: left; padding: 8px 10px; border-bottom: 1px solid var(--color-border); }
.import__table th { position: sticky; top: 0; background: var(--color-surface); }
.import__note { font-size: 12.5px; color: var(--color-text-muted); }
.r--error { background: rgba(239, 68, 68, 0.05); }
.err { font-size: 12px; color: #dc2626; }
.collab__code { display: flex; align-items: center; gap: 8px; }
.collab__code-label { font-size: 12.5px; color: var(--color-text-muted); }
.collab__code-val { font-family: var(--font-mono); font-size: 13px; background: var(--color-bg-subtle); padding: 3px 8px; border-radius: 6px; letter-spacing: 1px; }
.collab__copy { display: inline-flex; padding: 5px; border-radius: 6px; border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text-muted); cursor: pointer; }
.collab__copy:hover { color: var(--color-text-primary); }
.collab__table { width: 100%; border-collapse: collapse; font-size: 13px; }
.collab__table th, .collab__table td { text-align: left; padding: 9px 10px; border-bottom: 1px solid var(--color-border); }
.collab__name { font-weight: 600; color: var(--color-text-primary); }
.collab__email { display: block; font-size: 12px; color: var(--color-text-subtle); }
.collab__actions { display: flex; gap: 6px; justify-content: flex-end; }
@media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
</style>
