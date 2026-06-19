<!--
    resources/js/Pages/Users/Index.vue
    Daftar pengguna terdaftar + instansi & status.
    SuperAdmin: semua instansi · Admin: instansi sendiri.
-->
<template>
    <BaseLayout :nav-groups="navGroups" title="Pengguna">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Pengguna</h1>
                    <p class="page__sub">
                        {{ filtered.length }} dari {{ users.length }} pengguna
                        {{ isSuperAdmin ? 'di seluruh instansi' : 'pada instansi Anda' }}.
                    </p>
                </div>
            </div>

            <div class="toolbar">
                <AppInput v-model="q" placeholder="Cari nama / email / instansi…" size="md" clearable>
                    <template #prefix><SearchIcon :size="15" /></template>
                </AppInput>
                <AppSelect v-model="statusFilter" native size="md" :options="statusOptions" style="max-width: 200px" />
            </div>

            <div v-if="!filtered.length" class="empty">
                <UsersIcon :size="34" class="empty__icon" />
                <p>Tidak ada pengguna yang cocok.</p>
            </div>

            <div v-else class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Instansi</th>
                            <th>Peran</th>
                            <th>Status</th>
                            <th>NIK / HP</th>
                            <th>Terdaftar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in filtered" :key="u.id">
                            <td>
                                <div class="user-cell">
                                    <div class="user-cell__avatar">{{ initials(u.name) }}</div>
                                    <div class="user-cell__info">
                                        <span class="user-cell__name">{{ u.name }}</span>
                                        <span class="user-cell__email">{{ u.email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <template v-if="u.organization">
                                    <span class="org-name">{{ u.organization.nama }}</span>
                                    <span class="org-kode">{{ u.organization.kode }} · {{ u.organization.type }}</span>
                                </template>
                                <span v-else class="muted">— (SuperAdmin)</span>
                            </td>
                            <td>
                                <span v-if="roleLabel(u)" class="muted">{{ roleLabel(u) }}</span>
                                <span v-else class="muted">—</span>
                            </td>
                            <td><AppBadge :color="statusColor(u.status)" size="sm">{{ statusLabel(u.status) }}</AppBadge></td>
                            <td>
                                <span class="mono">{{ u.nik || '—' }}</span><br>
                                <span class="mono muted">{{ u.phone || '—' }}</span>
                            </td>
                            <td><span class="muted">{{ formatDate(u.created_at) }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { SearchIcon, UsersIcon } from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppSelect from '@/Components/App/AppSelect.vue';
import { navGroups } from '@/data/navGroups';

interface UserOrg { id: number; nama: string; kode: string; type: string }
interface UserRow {
    id: number; name: string; email: string;
    nik: string | null; phone: string | null;
    status: string; requested_role: string | null; roles: string[];
    created_at: string | null; organization: UserOrg | null;
}

const props = defineProps<{ users: UserRow[]; isSuperAdmin: boolean }>();

const q = ref('');
const statusFilter = ref('');
const statusOptions = [
    { value: '', label: 'Semua status' },
    { value: 'pending', label: 'Menunggu' },
    { value: 'approved', label: 'Disetujui' },
    { value: 'rejected', label: 'Ditolak' },
    { value: 'suspended', label: 'Nonaktif' },
];

const filtered = computed<UserRow[]>(() => {
    const term = q.value.trim().toLowerCase();
    return props.users.filter((u) => {
        if (statusFilter.value && u.status !== statusFilter.value) return false;
        if (!term) return true;
        return [u.name, u.email, u.organization?.nama, u.organization?.kode]
            .filter(Boolean)
            .some((v) => (v as string).toLowerCase().includes(term));
    });
});

function initials(name: string): string {
    return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase();
}
function roleLabel(u: UserRow): string {
    if (u.roles.length) return u.roles.join(', ');
    return u.requested_role ? `${u.requested_role} (diminta)` : '';
}
function statusColor(s: string): string {
    return ({ pending: 'warning', approved: 'success', rejected: 'danger', suspended: 'default' } as Record<string, string>)[s] ?? 'default';
}
function statusLabel(s: string): string {
    return ({ pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak', suspended: 'Nonaktif' } as Record<string, string>)[s] ?? s;
}
function formatDate(d: string | null): string {
    if (!d) return '—';
    return new Date(d.replace(' ', 'T')).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<style scoped>
.page { padding: 24px; max-width: 1200px; margin: 0 auto; }
.page__header { margin-bottom: 16px; }
.page__title { font-size: 22px; font-weight: 700; color: var(--color-text-primary); }
.page__sub { font-size: 13.5px; color: var(--color-text-muted); margin-top: 2px; }

.toolbar { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.toolbar > :first-child { flex: 1; min-width: 220px; max-width: 360px; }

.empty { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 64px 0; color: var(--color-text-subtle); }
.empty__icon { color: var(--color-text-subtle); }

.table-wrap { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; overflow: auto; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 12px 14px; text-align: left; font-size: 13px; border-bottom: 1px solid var(--color-border); white-space: nowrap; }
.table th { font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--color-text-subtle); font-weight: 700; }
.table tbody tr:last-child td { border-bottom: none; }
.table tbody tr:hover { background: var(--color-bg-subtle); }

.user-cell { display: flex; align-items: center; gap: 10px; }
.user-cell__avatar {
    width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; font-size: 12px; font-weight: 700;
}
.user-cell__info { display: flex; flex-direction: column; }
.user-cell__name { font-weight: 600; color: var(--color-text-primary); }
.user-cell__email { font-size: 12px; color: var(--color-text-muted); }

.org-name { display: block; color: var(--color-text-primary); font-weight: 600; }
.org-kode { display: block; font-size: 11.5px; color: var(--color-text-subtle); }
.muted { color: var(--color-text-muted); }
.mono { font-family: var(--font-mono); font-size: 12px; color: var(--color-text-primary); }
</style>
