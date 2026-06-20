<!--
    resources/js/Pages/Marketplace/AdminDashboard.vue
    Dashboard marketplace SuperAdmin: pendapatan platform, royalti, template terlaris,
    & manajemen pencairan (jadwal / setuju / tolak / bayar).
-->
<template>
    <BaseLayout :nav-groups="navGroups" title="Statistik Marketplace">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Statistik Marketplace</h1>
                    <p class="page__sub">Pendapatan platform &amp; manajemen pencairan royalti.</p>
                </div>
            </div>

            <div class="stats">
                <div class="stat"><span class="stat__label">Total Credit Diperjualbelikan</span><span class="stat__value">{{ stats.total_traded.toLocaleString('id-ID') }}</span></div>
                <div class="stat stat--accent"><span class="stat__label">Pendapatan Platform</span><span class="stat__value">{{ stats.total_platform.toLocaleString('id-ID') }}</span><span class="stat__rp">Rp{{ stats.estimasi_rupiah.toLocaleString('id-ID') }}</span></div>
                <div class="stat"><span class="stat__label">Royalti Creator</span><span class="stat__value">{{ stats.total_royalty.toLocaleString('id-ID') }}</span></div>
            </div>

            <h2 class="section-title">Template Terlaris</h2>
            <div v-if="!topTemplates.length" class="empty-sm">Belum ada transaksi.</div>
            <div v-else class="table-wrap">
                <table class="table">
                    <thead><tr><th>Template</th><th>Penggunaan</th><th>Total Royalti</th></tr></thead>
                    <tbody>
                        <tr v-for="(t, i) in topTemplates" :key="i">
                            <td>{{ t.template }}</td>
                            <td class="mono">{{ t.uses }}</td>
                            <td class="mono">{{ t.royalty.toLocaleString('id-ID') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h2 class="section-title">Pendaftaran Creator</h2>
            <div v-if="!creatorApplications.length" class="empty-sm">Tidak ada pendaftaran menunggu verifikasi.</div>
            <div v-else class="table-wrap">
                <table class="table">
                    <thead><tr><th>Akun</th><th>Nama Lengkap</th><th>Alamat</th><th>KTP</th><th>Diajukan</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <tr v-for="a in creatorApplications" :key="a.id">
                            <td><span class="name">{{ a.name }}</span><br><span class="muted">{{ a.email }}</span></td>
                            <td>{{ a.full_name }}</td>
                            <td class="addr">{{ a.address }}</td>
                            <td><a :href="`/marketplace/creators/${a.id}/ktp`" target="_blank" class="link">Lihat KTP</a></td>
                            <td class="muted">{{ formatDate(a.applied_at) }}</td>
                            <td>
                                <div class="row-actions">
                                    <AppButton variant="primary" size="sm" @click="approveCreator(a)">Setujui</AppButton>
                                    <AppButton variant="danger" size="sm" @click="openReviewReject('creator', a.id, a.name)">Tolak</AppButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h2 class="section-title">Verifikasi Rekening</h2>
            <div v-if="!bankReviews.length" class="empty-sm">Tidak ada rekening menunggu verifikasi.</div>
            <div v-else class="table-wrap">
                <table class="table">
                    <thead><tr><th>Creator</th><th>Bank</th><th>No. Rekening</th><th>Atas Nama</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <tr v-for="b in bankReviews" :key="b.id">
                            <td class="name">{{ b.name }}</td>
                            <td>{{ b.bank_name }}</td>
                            <td class="mono">{{ b.bank_account_no }}</td>
                            <td>{{ b.bank_account_holder }}</td>
                            <td>
                                <div class="row-actions">
                                    <AppButton variant="primary" size="sm" @click="verifyBank(b)">Verifikasi</AppButton>
                                    <AppButton variant="danger" size="sm" @click="openReviewReject('bank', b.id, b.name)">Tolak</AppButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h2 class="section-title">Pencairan Royalti</h2>
            <div v-if="!withdrawals.length" class="empty-sm">Belum ada permintaan pencairan.</div>
            <div v-else class="table-wrap">
                <table class="table">
                    <thead><tr><th>Creator</th><th>Credit</th><th>Bersih</th><th>Rupiah</th><th>Jadwal</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <tr v-for="w in withdrawals" :key="w.id">
                            <td><span class="name">{{ w.user?.name }}</span><br><span class="muted">{{ w.user?.email }}</span></td>
                            <td class="mono">{{ w.credit_requested }}</td>
                            <td class="mono">{{ w.credit_paid }}</td>
                            <td class="mono">Rp{{ w.rupiah_paid.toLocaleString('id-ID') }}</td>
                            <td>{{ w.scheduled_payout_date || '—' }}</td>
                            <td><AppBadge :color="wStatusColor(w.status)" size="sm">{{ wStatusLabel(w.status) }}</AppBadge></td>
                            <td>
                                <div class="row-actions">
                                    <template v-if="w.status === 'pending' || w.status === 'scheduled' || w.status === 'approved'">
                                        <AppButton variant="ghost" size="sm" @click="openSchedule(w)">Jadwal</AppButton>
                                        <AppButton v-if="w.status !== 'approved'" variant="ghost" size="sm" @click="approve(w)">Setujui</AppButton>
                                        <AppButton variant="primary" size="sm" @click="markPaid(w)">Bayar</AppButton>
                                        <AppButton variant="danger" size="sm" @click="openReject(w)">Tolak</AppButton>
                                    </template>
                                    <span v-else class="muted">—</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <AppModal v-model="scheduleOpen" title="Jadwalkan Pencairan">
            <AppInput v-model="scheduleForm.scheduled_payout_date" type="date" label="Tanggal pembayaran" :error="scheduleForm.errors.scheduled_payout_date" />
            <template #footer>
                <AppButton variant="ghost" @click="scheduleOpen = false">Batal</AppButton>
                <AppButton variant="primary" :loading="scheduleForm.processing" :disabled="!scheduleForm.scheduled_payout_date" @click="submitSchedule">Simpan Jadwal</AppButton>
            </template>
        </AppModal>

        <AppModal v-model="rejectOpen" title="Tolak Pencairan">
            <p class="modal-intro">Credit yang ditahan akan dikembalikan ke Creator.</p>
            <AppInput v-model="rejectForm.reason" label="Alasan" :error="rejectForm.errors.reason" />
            <template #footer>
                <AppButton variant="ghost" @click="rejectOpen = false">Batal</AppButton>
                <AppButton variant="danger" :loading="rejectForm.processing" :disabled="rejectForm.reason.trim().length < 3" @click="submitReject">Tolak</AppButton>
            </template>
        </AppModal>

        <!-- Tolak pendaftaran creator / rekening (alasan ke pemohon). -->
        <AppModal v-model="reviewRejectOpen" :title="reviewKind === 'creator' ? 'Tolak Pendaftaran' : 'Tolak Rekening'">
            <p class="modal-intro">Alasan akan dikirim ke pemohon ({{ reviewName }}).</p>
            <AppInput v-model="reviewRejectForm.reason" label="Alasan" :error="reviewRejectForm.errors.reason" />
            <template #footer>
                <AppButton variant="ghost" @click="reviewRejectOpen = false">Batal</AppButton>
                <AppButton variant="danger" :loading="reviewRejectForm.processing" :disabled="reviewRejectForm.reason.trim().length < 3" @click="submitReviewReject">Tolak</AppButton>
            </template>
        </AppModal>
    </BaseLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import AppModal from '@/Components/App/AppModal.vue';
import AppInput from '@/Components/App/AppInput.vue';
import { swalConfirm } from '@/Composables/useSwal';
import { navGroups } from '@/data/navGroups';

interface Stats { total_traded: number; total_platform: number; total_royalty: number; estimasi_rupiah: number }
interface TopRow { template: string; uses: number; royalty: number }
interface WdUser { id: number; name: string; email: string }
interface WdRow { id: number; user: WdUser | null; credit_requested: number; admin_fee_credit: number; credit_paid: number; rupiah_paid: number; status: string; scheduled_payout_date: string | null; notes: string | null; created_at: string | null }
interface CreatorApp { id: number; name: string; email: string; full_name: string | null; address: string | null; applied_at: string | null }
interface BankReview { id: number; name: string; bank_name: string | null; bank_account_no: string | null; bank_account_holder: string | null }

defineProps<{
    stats: Stats;
    topTemplates: TopRow[];
    withdrawals: WdRow[];
    creatorApplications: CreatorApp[];
    bankReviews: BankReview[];
    rupiahPerCredit: number;
}>();

function formatDate(d: string | null): string {
    if (!d) return '—';
    return new Date(d.replace(' ', 'T')).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function wStatusColor(s: string): string {
    return ({ pending: 'warning', scheduled: 'info', approved: 'info', paid: 'success', rejected: 'danger' } as Record<string, string>)[s] ?? 'default';
}
function wStatusLabel(s: string): string {
    return ({ pending: 'Menunggu', scheduled: 'Dijadwalkan', approved: 'Disetujui', paid: 'Dibayar', rejected: 'Ditolak' } as Record<string, string>)[s] ?? s;
}

async function approve(w: WdRow): Promise<void> {
    if (!(await swalConfirm({ title: 'Setujui pencairan?', confirmText: 'Ya' }))) return;
    useForm({}).post(`/marketplace/withdrawals/${w.id}/approve`, { preserveScroll: true });
}
async function markPaid(w: WdRow): Promise<void> {
    if (!(await swalConfirm({ title: 'Tandai sudah dibayar?', text: `Rp${w.rupiah_paid.toLocaleString('id-ID')} ditransfer ke ${w.user?.name}.`, confirmText: 'Ya, sudah dibayar' }))) return;
    useForm({}).post(`/marketplace/withdrawals/${w.id}/paid`, { preserveScroll: true });
}

const scheduleOpen = ref(false);
const scheduleTarget = ref<WdRow | null>(null);
const scheduleForm = useForm<{ scheduled_payout_date: string }>({ scheduled_payout_date: '' });
function openSchedule(w: WdRow): void { scheduleTarget.value = w; scheduleForm.scheduled_payout_date = w.scheduled_payout_date ?? ''; scheduleForm.clearErrors(); scheduleOpen.value = true; }
function submitSchedule(): void {
    if (!scheduleTarget.value) return;
    scheduleForm.post(`/marketplace/withdrawals/${scheduleTarget.value.id}/schedule`, { preserveScroll: true, onSuccess: () => { scheduleOpen.value = false; } });
}

const rejectOpen = ref(false);
const rejectTarget = ref<WdRow | null>(null);
const rejectForm = useForm<{ reason: string }>({ reason: '' });
function openReject(w: WdRow): void { rejectTarget.value = w; rejectForm.reason = ''; rejectForm.clearErrors(); rejectOpen.value = true; }
function submitReject(): void {
    if (!rejectTarget.value) return;
    rejectForm.post(`/marketplace/withdrawals/${rejectTarget.value.id}/reject`, { preserveScroll: true, onSuccess: () => { rejectOpen.value = false; } });
}

// --- Verifikasi pendaftaran creator & rekening ---
async function approveCreator(a: CreatorApp): Promise<void> {
    if (!(await swalConfirm({ title: 'Setujui pendaftaran Creator?', text: `${a.name} akan menjadi Marketplace Creator.`, confirmText: 'Ya, setujui' }))) return;
    useForm({}).post(`/marketplace/creators/${a.id}/approve`, { preserveScroll: true });
}
async function verifyBank(b: BankReview): Promise<void> {
    if (!(await swalConfirm({ title: 'Verifikasi rekening?', text: `${b.name} dapat mulai publish & mencairkan royalti.`, confirmText: 'Ya, verifikasi' }))) return;
    useForm({}).post(`/marketplace/creators/${b.id}/bank/verify`, { preserveScroll: true });
}

const reviewRejectOpen = ref(false);
const reviewKind = ref<'creator' | 'bank'>('creator');
const reviewUserId = ref<number | null>(null);
const reviewName = ref('');
const reviewRejectForm = useForm<{ reason: string }>({ reason: '' });
function openReviewReject(kind: 'creator' | 'bank', userId: number, name: string): void {
    reviewKind.value = kind; reviewUserId.value = userId; reviewName.value = name;
    reviewRejectForm.reason = ''; reviewRejectForm.clearErrors(); reviewRejectOpen.value = true;
}
function submitReviewReject(): void {
    if (reviewUserId.value === null) return;
    const url = reviewKind.value === 'creator'
        ? `/marketplace/creators/${reviewUserId.value}/reject`
        : `/marketplace/creators/${reviewUserId.value}/bank/reject`;
    reviewRejectForm.post(url, { preserveScroll: true, onSuccess: () => { reviewRejectOpen.value = false; } });
}
</script>

<style scoped>
.page { padding: 24px; max-width: 1100px; margin: 0 auto; }
.page__header { margin-bottom: 16px; }
.page__title { font-size: 22px; font-weight: 700; color: var(--color-text-primary); }
.page__sub { font-size: 13.5px; color: var(--color-text-muted); margin-top: 2px; }

.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 8px; }
.stat { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 4px; }
.stat__label { font-size: 12px; color: var(--color-text-muted); }
.stat__value { font-size: 26px; font-weight: 800; color: var(--color-text-primary); }
.stat__rp { font-size: 12px; opacity: 0.9; }
.stat--accent { background: linear-gradient(135deg, #d97706, #b45309); border: none; }
.stat--accent .stat__label, .stat--accent .stat__value, .stat--accent .stat__rp { color: #fff; }

.section-title { font-size: 15px; font-weight: 700; color: var(--color-text-primary); margin: 24px 0 10px; }
.empty-sm { color: var(--color-text-muted); font-size: 13px; padding: 12px 0; }
.table-wrap { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; overflow: auto; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 11px 14px; text-align: left; font-size: 13px; border-bottom: 1px solid var(--color-border); white-space: nowrap; }
.table th { font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--color-text-subtle); font-weight: 700; }
.table tbody tr:last-child td { border-bottom: none; }
.name { font-weight: 600; color: var(--color-text-primary); }
.muted { color: var(--color-text-muted); font-size: 12px; }
.addr { max-width: 240px; white-space: normal; font-size: 12px; color: var(--color-text-muted); }
.link { color: var(--color-primary, #2563eb); font-weight: 600; }
.mono { font-family: var(--font-mono); font-size: 12.5px; }
.row-actions { display: flex; gap: 6px; flex-wrap: wrap; }
.modal-intro { font-size: 13px; color: var(--color-text-muted); margin-bottom: 12px; }
</style>
