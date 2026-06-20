<!--
    resources/js/Pages/Credits/Index.vue
    Halaman Credit (semua user): kartu saldo, form topup (jumlah credit → Rp),
    upload bukti, riwayat transaksi & status permintaan topup.
-->
<template>
    <BaseLayout :nav-groups="navGroups" title="Credit Saya">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Credit Saya</h1>
                    <p class="page__sub">Saldo credit untuk membuat acara &amp; template.</p>
                </div>
            </div>

            <div class="grid">
                <!-- Kartu saldo -->
                <div class="card balance-card">
                    <div class="balance-card__label">Saldo Credit</div>
                    <div class="balance-card__value">{{ balance.toLocaleString('id-ID') }}</div>
                    <div class="balance-card__rp">≈ Rp{{ (balance * rupiahPerCredit).toLocaleString('id-ID') }}</div>
                    <p v-if="isExempt" class="balance-card__note">
                        Akun Anda <strong>bebas-credit</strong> (Enterprise aktif / SuperAdmin).
                    </p>
                </div>

                <!-- Form topup -->
                <div class="card">
                    <h2 class="card__title">Ajukan Topup</h2>
                    <p class="card__sub">1 credit = Rp{{ rupiahPerCredit.toLocaleString('id-ID') }}. Topup diverifikasi manual oleh admin.</p>
                    <form class="form" @submit.prevent="submit">
                        <AppInput
                            v-model.number="form.amount_credit"
                            type="number"
                            label="Jumlah credit"
                            min="10"
                            placeholder="mis. 100"
                            :error="form.errors.amount_credit"
                            required
                        />
                        <p class="form__hint">Total: <strong>Rp{{ ((form.amount_credit || 0) * rupiahPerCredit).toLocaleString('id-ID') }}</strong></p>

                        <AppInput v-model="form.note" label="Catatan (opsional)" placeholder="mis. transfer BCA a.n. ..." :error="form.errors.note" />

                        <label class="file-field">
                            <span class="file-field__label">Bukti transfer (opsional)</span>
                            <input type="file" accept=".png,.jpg,.jpeg,.pdf" @change="onFile" />
                            <span v-if="form.errors.proof" class="file-field__error">{{ form.errors.proof }}</span>
                        </label>

                        <AppButton type="submit" variant="primary" :loading="form.processing" :disabled="!form.amount_credit || form.amount_credit < 10">
                            <template #icon><SendIcon :size="15" /></template>
                            Kirim Permintaan
                        </AppButton>
                    </form>
                </div>
            </div>

            <!-- Permintaan topup -->
            <h2 class="section-title">Permintaan Topup</h2>
            <div v-if="!requests.length" class="empty-sm">Belum ada permintaan topup.</div>
            <div v-else class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Tanggal</th><th>Credit</th><th>Rupiah</th><th>Status</th><th>Catatan</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in requests" :key="r.id">
                            <td>{{ formatDate(r.created_at) }}</td>
                            <td class="mono">{{ r.amount_credit.toLocaleString('id-ID') }}</td>
                            <td class="mono">Rp{{ r.amount_rupiah.toLocaleString('id-ID') }}</td>
                            <td><AppBadge :color="statusColor(r.status)" size="sm">{{ statusLabel(r.status) }}</AppBadge></td>
                            <td class="muted">{{ r.reject_reason || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Riwayat ledger -->
            <h2 class="section-title">Riwayat Transaksi</h2>
            <div v-if="!transactions.length" class="empty-sm">Belum ada transaksi.</div>
            <div v-else class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Tanggal</th><th>Jenis</th><th>Jumlah</th><th>Saldo</th><th>Keterangan</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in transactions" :key="t.id">
                            <td>{{ formatDate(t.created_at) }}</td>
                            <td><AppBadge :color="t.amount >= 0 ? 'success' : 'default'" size="sm">{{ typeLabel(t.type) }}</AppBadge></td>
                            <td class="mono" :class="t.amount >= 0 ? 'pos' : 'neg'">{{ t.amount >= 0 ? '+' : '' }}{{ t.amount.toLocaleString('id-ID') }}</td>
                            <td class="mono">{{ t.balance_after.toLocaleString('id-ID') }}</td>
                            <td class="muted">{{ t.description || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { SendIcon } from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import { navGroups } from '@/data/navGroups';

interface TxRow { id: number; type: string; amount: number; balance_after: number; description: string | null; created_at: string | null }
interface ReqRow { id: number; amount_credit: number; amount_rupiah: number; status: string; reject_reason: string | null; created_at: string | null }

defineProps<{
    balance: number;
    rupiahPerCredit: number;
    transactions: TxRow[];
    requests: ReqRow[];
}>();

const page = usePage();
const isExempt = computed<boolean>(() => (page.props.auth as any)?.user?.is_credit_exempt ?? false);

const form = useForm<{ amount_credit: number | null; note: string; proof: File | null }>({
    amount_credit: null, note: '', proof: null,
});

function onFile(e: Event): void {
    form.proof = (e.target as HTMLInputElement).files?.[0] ?? null;
}
function submit(): void {
    form.post('/credits/topup', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => form.reset(),
    });
}

function formatDate(d: string | null): string {
    if (!d) return '—';
    return new Date(d.replace(' ', 'T')).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
function statusColor(s: string): string {
    return ({ pending: 'warning', approved: 'success', rejected: 'danger' } as Record<string, string>)[s] ?? 'default';
}
function statusLabel(s: string): string {
    return ({ pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' } as Record<string, string>)[s] ?? s;
}
function typeLabel(t: string): string {
    return ({
        topup: 'Topup', consume: 'Pemakaian', grant: 'Penambahan', refund: 'Pengembalian', adjust: 'Penyesuaian',
        template_purchase: 'Beli Template', template_royalty: 'Royalti', withdraw: 'Pencairan', withdraw_fee: 'Biaya Cair',
    } as Record<string, string>)[t] ?? t;
}
</script>

<style scoped>
.page { padding: 24px; max-width: 1100px; margin: 0 auto; }
.page__header { margin-bottom: 16px; }
.page__title { font-size: 22px; font-weight: 700; color: var(--color-text-primary); }
.page__sub { font-size: 13.5px; color: var(--color-text-muted); margin-top: 2px; }

.grid { display: grid; grid-template-columns: 1fr 1.4fr; gap: 16px; margin-bottom: 8px; }
@media (max-width: 720px) { .grid { grid-template-columns: 1fr; } }

.card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; padding: 18px; }
.card__title { font-size: 15px; font-weight: 700; color: var(--color-text-primary); }
.card__sub { font-size: 12.5px; color: var(--color-text-muted); margin: 4px 0 14px; }

.balance-card { background: linear-gradient(135deg, #16a34a, #15803d); color: #fff; border: none; }
.balance-card__label { font-size: 13px; opacity: 0.85; }
.balance-card__value { font-size: 40px; font-weight: 800; line-height: 1.1; margin-top: 4px; }
.balance-card__rp { font-size: 13px; opacity: 0.9; }
.balance-card__note { margin-top: 12px; font-size: 12.5px; background: rgba(255,255,255,0.15); padding: 8px 10px; border-radius: 8px; }

.form { display: flex; flex-direction: column; gap: 12px; }
.form__hint { font-size: 12.5px; color: var(--color-text-muted); margin: -4px 0 0; }
.file-field { display: flex; flex-direction: column; gap: 4px; font-size: 13px; }
.file-field__label { font-weight: 600; color: var(--color-text-primary); }
.file-field__error { color: var(--color-danger, #dc2626); font-size: 12px; }

.section-title { font-size: 15px; font-weight: 700; color: var(--color-text-primary); margin: 24px 0 10px; }
.empty-sm { color: var(--color-text-muted); font-size: 13px; padding: 12px 0; }

.table-wrap { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; overflow: auto; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 11px 14px; text-align: left; font-size: 13px; border-bottom: 1px solid var(--color-border); white-space: nowrap; }
.table th { font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--color-text-subtle); font-weight: 700; }
.table tbody tr:last-child td { border-bottom: none; }
.mono { font-family: var(--font-mono); font-size: 12.5px; }
.muted { color: var(--color-text-muted); }
.pos { color: #16a34a; font-weight: 700; }
.neg { color: var(--color-text-primary); font-weight: 700; }
</style>
