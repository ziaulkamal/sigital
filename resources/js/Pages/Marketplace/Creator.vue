<!--
    resources/js/Pages/Marketplace/Creator.vue
    Dashboard Marketplace Creator dengan alur bertahap:
      1. Belum daftar / ditolak → form pendaftaran (KTP, identitas, S&K).
      2. Menunggu verifikasi → banner.
      3. Disetujui tapi rekening belum verified → form rekening + status.
      4. Disetujui + rekening verified → dashboard penuh (publish/pencairan aktif).
-->
<template>
    <BaseLayout :nav-groups="navGroups" title="Creator Marketplace">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Creator Marketplace</h1>
                    <p class="page__sub">Publikasikan template &amp; peroleh royalti dari penggunaan.</p>
                </div>
            </div>

            <!-- TAHAP 1: Form pendaftaran (belum daftar atau ditolak) -->
            <div v-if="appStatus === null || appStatus === 'rejected'" class="card">
                <div v-if="appStatus === 'rejected'" class="alert alert--danger">
                    Pendaftaran sebelumnya ditolak. Alasan: <strong>{{ application.reject_reason }}</strong>. Anda dapat mendaftar ulang.
                </div>
                <div class="intro">
                    <SparklesIcon :size="26" />
                    <p>Daftar sebagai <strong>Marketplace Creator</strong> untuk mempublikasikan template dan memperoleh royalti. Lengkapi data berikut untuk diverifikasi admin.</p>
                </div>
                <form class="form" @submit.prevent="submitApply">
                    <AppInput v-model="applyForm.full_name" label="Nama lengkap" :error="applyForm.errors.full_name" required />
                    <AppTextarea v-model="applyForm.address" label="Alamat lengkap" :rows="3" :error="applyForm.errors.address" required />
                    <label class="file-field">
                        <span class="file-field__label">Foto KTP (wajib · PNG/JPG/PDF, maks 5MB)</span>
                        <input type="file" accept=".png,.jpg,.jpeg,.pdf" @change="onKtp" />
                        <span v-if="applyForm.errors.ktp" class="file-field__error">{{ applyForm.errors.ktp }}</span>
                    </label>
                    <label class="check">
                        <input type="checkbox" v-model="applyForm.terms" />
                        <span>Saya menyetujui <a href="/legal/syarat-ketentuan" target="_blank">Syarat &amp; Ketentuan</a> yang berlaku.</span>
                    </label>
                    <span v-if="applyForm.errors.terms" class="file-field__error">{{ applyForm.errors.terms }}</span>
                    <AppButton type="submit" variant="primary" :loading="applyForm.processing" :disabled="!applyForm.terms || !applyForm.ktp">
                        Kirim Pendaftaran
                    </AppButton>
                </form>
            </div>

            <!-- TAHAP 2: Menunggu verifikasi pendaftaran -->
            <div v-else-if="appStatus === 'pending'" class="card status-card">
                <ClockIcon :size="26" />
                <p>Pendaftaran Creator Anda sedang <strong>menunggu verifikasi admin</strong>. Anda akan diberi tahu setelah ditinjau.</p>
            </div>

            <!-- TAHAP 3: Disetujui, rekening belum verified → form rekening -->
            <template v-else>
                <div v-if="!canUseFeatures" class="card">
                    <div class="intro">
                        <CheckCircleIcon :size="26" class="ok" />
                        <p>Pendaftaran Anda <strong>disetujui</strong>. Lengkapi &amp; verifikasi <strong>rekening pencairan</strong> untuk mulai mempublikasikan template dan mencairkan royalti.</p>
                    </div>
                    <div v-if="bank.status === 'pending'" class="alert alert--info">Rekening Anda sedang menunggu verifikasi admin.</div>
                    <div v-if="bank.status === 'rejected'" class="alert alert--danger">Rekening ditolak. Alasan: <strong>{{ bank.reject_reason }}</strong>. Perbarui rekening di bawah.</div>
                    <form class="form" @submit.prevent="submitBank">
                        <AppInput v-model="bankForm.bank_name" label="Nama bank" placeholder="mis. BCA" :error="bankForm.errors.bank_name" required />
                        <AppInput v-model="bankForm.bank_account_no" label="Nomor rekening" placeholder="mis. 1234567890" :error="bankForm.errors.bank_account_no" required />
                        <AppInput v-model="bankForm.bank_account_holder" label="Nama pemilik rekening" :error="bankForm.errors.bank_account_holder" required />
                        <AppButton type="submit" variant="primary" :loading="bankForm.processing">
                            {{ bank.status === 'pending' ? 'Perbarui Rekening' : 'Simpan Rekening' }}
                        </AppButton>
                    </form>
                </div>

                <!-- TAHAP 4: Dashboard penuh -->
                <template v-else>
                    <div class="bank-summary">
                        <CheckCircleIcon :size="16" class="ok" />
                        Rekening terverifikasi: <strong>{{ bank.bank_name }} · {{ bank.bank_account_no }}</strong> a.n. {{ bank.bank_account_holder }}.
                        <button class="link-btn" @click="editBank = !editBank">Ubah</button>
                    </div>
                    <div v-if="editBank" class="card">
                        <p class="card__sub">Mengubah rekening akan memerlukan verifikasi ulang admin.</p>
                        <form class="form" @submit.prevent="submitBank">
                            <AppInput v-model="bankForm.bank_name" label="Nama bank" :error="bankForm.errors.bank_name" required />
                            <AppInput v-model="bankForm.bank_account_no" label="Nomor rekening" :error="bankForm.errors.bank_account_no" required />
                            <AppInput v-model="bankForm.bank_account_holder" label="Nama pemilik rekening" :error="bankForm.errors.bank_account_holder" required />
                            <AppButton type="submit" variant="primary" :loading="bankForm.processing">Simpan &amp; Verifikasi Ulang</AppButton>
                        </form>
                    </div>

                    <div class="stats">
                        <div class="stat"><span class="stat__label">Template Marketplace</span><span class="stat__value">{{ summary.total_templates }}</span></div>
                        <div class="stat"><span class="stat__label">Total Digunakan</span><span class="stat__value">{{ summary.total_used }}</span></div>
                        <div class="stat"><span class="stat__label">Credit Diperoleh</span><span class="stat__value">{{ summary.total_earned.toLocaleString('id-ID') }}</span></div>
                        <div class="stat"><span class="stat__label">Sudah Dicairkan</span><span class="stat__value">{{ summary.total_withdrawn.toLocaleString('id-ID') }}</span></div>
                        <div class="stat stat--accent"><span class="stat__label">Saldo Tersedia</span><span class="stat__value">{{ summary.available.toLocaleString('id-ID') }}</span></div>
                    </div>

                    <div v-if="nextPayout" class="next-payout">
                        <CalendarIcon :size="16" />
                        Pencairan berikutnya: <strong>{{ nextPayout.credit_paid }} credit</strong>
                        (Rp{{ nextPayout.rupiah_paid.toLocaleString('id-ID') }}) dijadwalkan
                        <strong>{{ nextPayout.scheduled_payout_date }}</strong>.
                    </div>

                    <div class="card">
                        <h2 class="card__title">Cairkan Royalti</h2>
                        <p class="card__sub">Biaya admin {{ withdrawFee }} credit/transaksi. Minimal {{ withdrawMin }} credit. 1 credit = Rp{{ rupiahPerCredit.toLocaleString('id-ID') }}.</p>
                        <form class="wd-form" @submit.prevent="submitWithdraw">
                            <AppInput v-model.number="wdForm.credit_requested" type="number" label="Jumlah credit" :min="withdrawMin" :error="wdForm.errors.credit_requested" />
                            <p class="wd-form__hint">Diterima: <strong>{{ Math.max(0, (wdForm.credit_requested || 0) - withdrawFee) }}</strong> credit ≈ Rp{{ (Math.max(0, (wdForm.credit_requested || 0) - withdrawFee) * rupiahPerCredit).toLocaleString('id-ID') }}</p>
                            <AppButton type="submit" variant="primary" :loading="wdForm.processing" :disabled="(wdForm.credit_requested || 0) < withdrawMin">Ajukan Pencairan</AppButton>
                        </form>
                    </div>

                    <h2 class="section-title">Template Marketplace Saya</h2>
                    <div v-if="!templates.length" class="empty-sm">Belum ada template dipublikasikan. Buka halaman Template untuk mempublikasikan.</div>
                    <div v-else class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Template</th><th>Digunakan</th><th>Royalti</th><th>Status</th></tr></thead>
                            <tbody>
                                <tr v-for="t in templates" :key="t.id">
                                    <td>{{ t.nama }}</td>
                                    <td class="mono">{{ t.usage_count }}</td>
                                    <td class="mono">{{ t.royalty.toLocaleString('id-ID') }}</td>
                                    <td><AppBadge :color="t.published ? 'success' : 'default'" size="sm">{{ t.published ? 'Aktif' : 'Ditarik' }}</AppBadge></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h2 class="section-title">Histori Pencairan</h2>
                    <div v-if="!withdrawals.length" class="empty-sm">Belum ada pencairan.</div>
                    <div v-else class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Tanggal</th><th>Credit</th><th>Bersih</th><th>Rupiah</th><th>Jadwal</th><th>Status</th></tr></thead>
                            <tbody>
                                <tr v-for="w in withdrawals" :key="w.id">
                                    <td>{{ formatDate(w.created_at) }}</td>
                                    <td class="mono">{{ w.credit_requested }}</td>
                                    <td class="mono">{{ w.credit_paid }}</td>
                                    <td class="mono">Rp{{ w.rupiah_paid.toLocaleString('id-ID') }}</td>
                                    <td>{{ w.scheduled_payout_date || '—' }}</td>
                                    <td><AppBadge :color="wStatusColor(w.status)" size="sm">{{ wStatusLabel(w.status) }}</AppBadge></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </template>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { SparklesIcon, CalendarIcon, ClockIcon, CheckCircleIcon } from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import { navGroups } from '@/data/navGroups';

interface Summary { total_templates: number; total_used: number; total_earned: number; total_withdrawn: number; available: number }
interface TplRow { id: number; nama: string; usage_count: number; royalty: number; published: boolean }
interface WdRow { id: number; credit_requested: number; admin_fee_credit: number; credit_paid: number; rupiah_paid: number; status: string; scheduled_payout_date: string | null; notes: string | null; created_at: string | null }
interface Application { status: string | null; reject_reason: string | null; full_name: string | null; address: string | null }
interface Bank { status: string | null; reject_reason: string | null; bank_name: string | null; bank_account_no: string | null; bank_account_holder: string | null }

const props = defineProps<{
    isCreator: boolean;
    canUseFeatures: boolean;
    application: Application;
    bank: Bank;
    summary: Summary;
    templates: TplRow[];
    withdrawals: WdRow[];
    rupiahPerCredit: number;
    withdrawFee: number;
    withdrawMin: number;
    nextPayout: WdRow | null;
}>();

const appStatus = props.application.status;
const editBank = ref(false);

// Form pendaftaran.
const applyForm = useForm<{ full_name: string; address: string; ktp: File | null; terms: boolean }>({
    full_name: props.application.full_name ?? '', address: props.application.address ?? '', ktp: null, terms: false,
});
function onKtp(e: Event): void {
    applyForm.ktp = (e.target as HTMLInputElement).files?.[0] ?? null;
}
function submitApply(): void {
    applyForm.post('/marketplace/apply', { forceFormData: true, preserveScroll: true });
}

// Form rekening (prefill dari data tersimpan).
const bankForm = useForm<{ bank_name: string; bank_account_no: string; bank_account_holder: string }>({
    bank_name: props.bank.bank_name ?? '', bank_account_no: props.bank.bank_account_no ?? '', bank_account_holder: props.bank.bank_account_holder ?? '',
});
function submitBank(): void {
    bankForm.post('/marketplace/bank', { preserveScroll: true, onSuccess: () => { editBank.value = false; } });
}

// Pencairan.
const wdForm = useForm<{ credit_requested: number | null }>({ credit_requested: null });
function submitWithdraw(): void {
    wdForm.post('/marketplace/withdrawals', { preserveScroll: true, onSuccess: () => wdForm.reset() });
}

function formatDate(d: string | null): string {
    if (!d) return '—';
    return new Date(d.replace(' ', 'T')).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}
function wStatusColor(s: string): string {
    return ({ pending: 'warning', scheduled: 'info', approved: 'info', paid: 'success', rejected: 'danger' } as Record<string, string>)[s] ?? 'default';
}
function wStatusLabel(s: string): string {
    return ({ pending: 'Menunggu', scheduled: 'Dijadwalkan', approved: 'Disetujui', paid: 'Dibayar', rejected: 'Ditolak' } as Record<string, string>)[s] ?? s;
}
</script>

<style scoped>
.page { padding: 24px; max-width: 1100px; margin: 0 auto; }
.page__header { margin-bottom: 16px; }
.page__title { font-size: 22px; font-weight: 700; color: var(--color-text-primary); }
.page__sub { font-size: 13.5px; color: var(--color-text-muted); margin-top: 2px; }

.card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; padding: 18px; margin-bottom: 14px; }
.card__title { font-size: 15px; font-weight: 700; color: var(--color-text-primary); }
.card__sub { font-size: 12.5px; color: var(--color-text-muted); margin: 4px 0 14px; }
.status-card { display: flex; align-items: center; gap: 12px; color: var(--color-text-muted); }

.intro { display: flex; gap: 12px; align-items: flex-start; margin-bottom: 16px; color: var(--color-text-muted); }
.intro p { font-size: 13.5px; line-height: 1.5; }
.ok { color: #16a34a; }

.alert { padding: 10px 12px; border-radius: 10px; font-size: 13px; margin-bottom: 14px; }
.alert--danger { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
.alert--info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }

.form { display: flex; flex-direction: column; gap: 12px; max-width: 460px; }
.file-field { display: flex; flex-direction: column; gap: 4px; font-size: 13px; }
.file-field__label { font-weight: 600; color: var(--color-text-primary); }
.file-field__error { color: var(--color-danger, #dc2626); font-size: 12px; }
.check { display: flex; gap: 8px; align-items: flex-start; font-size: 13px; color: var(--color-text-primary); }
.check a { color: var(--color-primary, #2563eb); font-weight: 600; }

.bank-summary { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 13px; color: var(--color-text-primary); background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 10px 12px; margin-bottom: 14px; }
.link-btn { background: none; border: none; color: var(--color-primary, #2563eb); font-weight: 600; cursor: pointer; padding: 0; }

.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 14px; }
.stat { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 12px; padding: 14px; display: flex; flex-direction: column; gap: 4px; }
.stat__label { font-size: 12px; color: var(--color-text-muted); }
.stat__value { font-size: 22px; font-weight: 800; color: var(--color-text-primary); }
.stat--accent { background: linear-gradient(135deg, #d97706, #b45309); border: none; }
.stat--accent .stat__label, .stat--accent .stat__value { color: #fff; }

.next-payout { display: flex; align-items: center; gap: 8px; padding: 10px 12px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; border-radius: 10px; font-size: 13px; margin-bottom: 14px; }

.wd-form { display: flex; flex-direction: column; gap: 10px; max-width: 360px; }
.wd-form__hint { font-size: 12.5px; color: var(--color-text-muted); margin: -2px 0 0; }

.section-title { font-size: 15px; font-weight: 700; color: var(--color-text-primary); margin: 24px 0 10px; }
.empty-sm { color: var(--color-text-muted); font-size: 13px; padding: 12px 0; }
.table-wrap { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; overflow: auto; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 11px 14px; text-align: left; font-size: 13px; border-bottom: 1px solid var(--color-border); white-space: nowrap; }
.table th { font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--color-text-subtle); font-weight: 700; }
.table tbody tr:last-child td { border-bottom: none; }
.mono { font-family: var(--font-mono); font-size: 12.5px; }
</style>
