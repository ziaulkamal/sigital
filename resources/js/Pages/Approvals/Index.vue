<!--
    resources/js/Pages/Approvals/Index.vue
    Panel Persetujuan SuperAdmin (P2/K4): tinjau pendaftar pending, approve/reject,
    pratinjau surat rekomendasi (wajib untuk dinas, K9).
-->
<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Persetujuan Akun</h1>
                    <p class="page__sub">{{ pending.length }} pendaftar menunggu persetujuan.</p>
                </div>
            </div>

            <div v-if="!pending.length" class="empty">
                <CheckCircle2 :size="34" class="empty__icon" />
                <p>Tidak ada pendaftar yang menunggu.</p>
            </div>

            <div v-else class="cards">
                <div v-for="u in pending" :key="u.id" class="card">
                    <div class="card__main">
                        <div class="card__avatar">{{ initials(u.name) }}</div>
                        <div class="card__info">
                            <p class="card__name">{{ u.name }}</p>
                            <p class="card__email">{{ u.email }}</p>
                            <div class="card__meta">
                                <AppBadge v-if="u.organization" color="info" size="sm">
                                    {{ u.organization.nama }} ({{ u.organization.kode }})
                                </AppBadge>
                                <AppBadge :color="u.organization?.type === 'dinas' ? 'primary' : 'success'" size="sm">
                                    {{ u.organization?.type }}
                                </AppBadge>
                                <AppBadge size="sm">Peran: {{ u.requested_role }}</AppBadge>
                                <AppBadge v-if="u.organization?.is_proposed" color="warning" size="sm">
                                    Organisasi baru
                                </AppBadge>
                            </div>
                            <a
                                v-if="u.organization?.has_recommendation"
                                class="card__letter"
                                :href="`/approvals/${u.id}/recommendation`"
                                target="_blank"
                            >
                                <FileText :size="13" /> Lihat surat rekomendasi
                            </a>
                            <p v-else-if="u.organization?.type === 'dinas'" class="card__warn">
                                <AlertTriangle :size="13" /> Dinas tanpa surat rekomendasi
                            </p>
                        </div>
                    </div>

                    <div class="card__actions">
                        <template v-if="rejectingId === u.id">
                            <input
                                v-model="rejectReason"
                                class="card__reason"
                                placeholder="Alasan penolakan (opsional)"
                                @keyup.enter="confirmReject(u.id)"
                            />
                            <AppButton variant="danger" size="sm" :loading="busyId === u.id" @click="confirmReject(u.id)">
                                Tolak
                            </AppButton>
                            <AppButton variant="ghost" size="sm" @click="cancelReject">Batal</AppButton>
                        </template>
                        <template v-else>
                            <AppButton variant="primary" size="sm" :loading="busyId === u.id" @click="approve(u.id)">
                                <template #icon><Check :size="14" /></template>
                                Setujui
                            </AppButton>
                            <AppButton variant="outline" size="sm" @click="startReject(u.id)">Tolak</AppButton>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Check, CheckCircle2, FileText, AlertTriangle } from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import { navGroups } from '@/data/navGroups';

interface PendingOrg {
    id: number; nama: string; kode: string; type: string;
    is_proposed: boolean; has_recommendation: boolean;
}
interface PendingUser {
    id: number; name: string; email: string; requested_role: string;
    created_at: string | null; organization: PendingOrg | null;
}

defineProps<{ pending: PendingUser[] }>();

const busyId = ref<number | null>(null);
const rejectingId = ref<number | null>(null);
const rejectReason = ref('');

function initials(name: string): string {
    return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase();
}

function approve(id: number): void {
    busyId.value = id;
    router.post(`/approvals/${id}/approve`, {}, {
        preserveScroll: true,
        onFinish: () => { busyId.value = null; },
    });
}

function startReject(id: number): void {
    rejectingId.value = id;
    rejectReason.value = '';
}
function cancelReject(): void {
    rejectingId.value = null;
}
function confirmReject(id: number): void {
    busyId.value = id;
    router.post(`/approvals/${id}/reject`, { reason: rejectReason.value }, {
        preserveScroll: true,
        onFinish: () => { busyId.value = null; rejectingId.value = null; },
    });
}
</script>

<style scoped>
.page { padding: 24px; max-width: 900px; margin: 0 auto; }
.page__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.page__title { font-size: 22px; font-weight: 700; color: var(--color-text-primary); }
.page__sub { font-size: 13.5px; color: var(--color-text-muted); margin-top: 2px; }

.empty { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 64px 0; color: var(--color-text-subtle); }
.empty__icon { color: #10b981; }

.cards { display: flex; flex-direction: column; gap: 12px; }
.card {
    display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
    background: var(--color-surface); border: 1px solid var(--color-border);
    border-radius: 14px; padding: 16px 18px;
}
.card__main { display: flex; gap: 14px; align-items: flex-start; min-width: 0; flex: 1; }
.card__avatar {
    width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; font-size: 13px; font-weight: 700;
}
.card__info { min-width: 0; }
.card__name { font-size: 14.5px; font-weight: 700; color: var(--color-text-primary); }
.card__email { font-size: 12.5px; color: var(--color-text-muted); margin-bottom: 8px; }
.card__meta { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
.card__letter { display: inline-flex; align-items: center; gap: 5px; font-size: 12.5px; color: #6366f1; font-weight: 600; }
.card__warn { display: inline-flex; align-items: center; gap: 5px; font-size: 12.5px; color: #f59e0b; }
.card__actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.card__reason {
    height: 34px; padding: 0 12px; border-radius: 8px; min-width: 200px;
    border: 1.5px solid var(--color-border); background: var(--color-bg-subtle);
    font-size: 13px; font-family: var(--font-sans); color: var(--color-text-primary); outline: none;
}
.card__reason:focus { border-color: var(--color-primary); }
</style>
