<!--
    resources/js/Pages/Credits/Requests.vue
    Verifikasi permintaan topup lintas-user (SuperAdmin): lihat bukti, approve/reject.
-->
<template>
    <BaseLayout :nav-groups="navGroups" title="Permintaan Topup">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Permintaan Topup</h1>
                    <p class="page__sub">{{ pendingCount }} menunggu verifikasi dari {{ requests.length }} permintaan.</p>
                </div>
            </div>

            <div v-if="!requests.length" class="empty">
                <ReceiptIcon :size="34" class="empty__icon" />
                <p>Belum ada permintaan topup.</p>
            </div>

            <div v-else class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Tanggal</th><th>Pengguna</th><th>Credit</th><th>Rupiah</th><th>Bukti</th><th>Status</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in requests" :key="r.id">
                            <td>{{ formatDate(r.created_at) }}</td>
                            <td>
                                <span class="name">{{ r.user?.name }}</span><br>
                                <span class="muted">{{ r.user?.email }}</span>
                            </td>
                            <td class="mono">{{ r.amount_credit.toLocaleString('id-ID') }}</td>
                            <td class="mono">Rp{{ r.amount_rupiah.toLocaleString('id-ID') }}</td>
                            <td>
                                <a v-if="r.has_proof" :href="`/credits/topup/${r.id}/proof`" target="_blank" class="link">Lihat</a>
                                <span v-else class="muted">—</span>
                            </td>
                            <td><AppBadge :color="statusColor(r.status)" size="sm">{{ statusLabel(r.status) }}</AppBadge></td>
                            <td>
                                <div v-if="r.status === 'pending'" class="row-actions">
                                    <AppButton variant="primary" size="sm" @click="approve(r)">
                                        <template #icon><CheckIcon :size="14" /></template>
                                        Setujui
                                    </AppButton>
                                    <AppButton variant="danger" size="sm" @click="openReject(r)">
                                        <template #icon><XIcon :size="14" /></template>
                                        Tolak
                                    </AppButton>
                                </div>
                                <span v-else class="muted">{{ r.reject_reason || formatDate(r.reviewed_at) }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <AppModal v-model="rejectOpen" title="Tolak Permintaan Topup">
            <p class="modal-intro">Tolak topup <strong>{{ rejectTarget?.amount_credit }}</strong> credit dari <strong>{{ rejectTarget?.user?.name }}</strong>.</p>
            <AppTextarea v-model="rejectForm.reject_reason" label="Alasan penolakan" :rows="3" :error="rejectForm.errors.reject_reason" required />
            <template #footer>
                <AppButton variant="ghost" @click="rejectOpen = false">Batal</AppButton>
                <AppButton variant="danger" :loading="rejectForm.processing" :disabled="rejectForm.reject_reason.trim().length < 3" @click="submitReject">Tolak</AppButton>
            </template>
        </AppModal>
    </BaseLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { ReceiptIcon, CheckIcon, XIcon } from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import AppModal from '@/Components/App/AppModal.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import { swalConfirm } from '@/Composables/useSwal';
import { navGroups } from '@/data/navGroups';

interface ReqUser { id: number; name: string; email: string }
interface ReqRow {
    id: number; user: ReqUser | null;
    amount_credit: number; amount_rupiah: number;
    status: string; note: string | null; has_proof: boolean;
    reject_reason: string | null; created_at: string | null; reviewed_at: string | null;
}

const props = defineProps<{ requests: ReqRow[] }>();

const pendingCount = computed<number>(() => props.requests.filter((r) => r.status === 'pending').length);

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

async function approve(r: ReqRow): Promise<void> {
    const ok = await swalConfirm({
        title: 'Setujui topup?',
        text: `${r.amount_credit} credit akan ditambahkan ke ${r.user?.name}.`,
        confirmText: 'Ya, setujui',
    });
    if (!ok) return;
    useForm({}).post(`/credits/topup/${r.id}/approve`, { preserveScroll: true });
}

const rejectOpen = ref(false);
const rejectTarget = ref<ReqRow | null>(null);
const rejectForm = useForm<{ reject_reason: string }>({ reject_reason: '' });

function openReject(r: ReqRow): void {
    rejectTarget.value = r;
    rejectForm.reject_reason = '';
    rejectForm.clearErrors();
    rejectOpen.value = true;
}
function submitReject(): void {
    if (!rejectTarget.value) return;
    rejectForm.post(`/credits/topup/${rejectTarget.value.id}/reject`, {
        preserveScroll: true,
        onSuccess: () => { rejectOpen.value = false; },
    });
}
</script>

<style scoped>
.page { padding: 24px; max-width: 1100px; margin: 0 auto; }
.page__header { margin-bottom: 16px; }
.page__title { font-size: 22px; font-weight: 700; color: var(--color-text-primary); }
.page__sub { font-size: 13.5px; color: var(--color-text-muted); margin-top: 2px; }

.empty { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 64px 0; color: var(--color-text-subtle); }

.table-wrap { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; overflow: auto; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 11px 14px; text-align: left; font-size: 13px; border-bottom: 1px solid var(--color-border); white-space: nowrap; }
.table th { font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--color-text-subtle); font-weight: 700; }
.table tbody tr:last-child td { border-bottom: none; }
.name { font-weight: 600; color: var(--color-text-primary); }
.mono { font-family: var(--font-mono); font-size: 12.5px; }
.muted { color: var(--color-text-muted); font-size: 12px; }
.link { color: var(--color-primary, #2563eb); font-weight: 600; }
.row-actions { display: flex; gap: 6px; }
.modal-intro { font-size: 13.5px; line-height: 1.5; color: var(--color-text-primary); margin-bottom: 14px; }
</style>
