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
                            <th class="col--hide-md">Instansi</th>
                            <th class="col--hide-sm">Peran</th>
                            <th>Status</th>
                            <th v-if="isSuperAdmin" class="col--hide-sm">Credit</th>
                            <th v-if="isSuperAdmin" class="col--hide-lg">Paket</th>
                            <th v-if="isSuperAdmin" class="col--hide-xl">Login Terakhir</th>
                            <th class="col--hide-lg">NIK / HP</th>
                            <th class="col--hide-xl">Terdaftar</th>
                            <th v-if="canManage">Aksi</th>
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
                                        <!-- Ringkasan info untuk layar kecil (kolom tersembunyi). -->
                                        <span class="user-cell__meta">
                                            <template v-if="u.organization">{{ u.organization.kode }} · {{ roleLabel(u) || '—' }}</template>
                                            <template v-else>SuperAdmin</template>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="col--hide-md">
                                <template v-if="u.organization">
                                    <span class="org-name">{{ u.organization.nama }}</span>
                                    <span class="org-kode">{{ u.organization.kode }} · {{ u.organization.type }}</span>
                                </template>
                                <span v-else class="muted">— (SuperAdmin)</span>
                            </td>
                            <td class="col--hide-sm">
                                <span v-if="roleLabel(u)" class="muted">{{ roleLabel(u) }}</span>
                                <span v-else class="muted">—</span>
                            </td>
                            <td>
                                <AppBadge :color="u.is_banned ? 'danger' : statusColor(u.status)" size="sm">
                                    {{ u.is_banned ? 'Diblokir' : statusLabel(u.status) }}
                                </AppBadge>
                                <span v-if="u.is_banned && u.banned_reason" class="ban-reason" :title="u.banned_reason">
                                    {{ u.banned_reason }}
                                </span>
                            </td>
                            <td v-if="isSuperAdmin" class="col--hide-sm">
                                <span class="credit-amount">{{ u.credit_balance.toLocaleString('id-ID') }}</span>
                            </td>
                            <td v-if="isSuperAdmin" class="col--hide-lg">
                                <AppBadge v-if="u.is_enterprise_active" color="success" size="sm">Enterprise</AppBadge>
                                <AppBadge v-else-if="u.is_enterprise" color="warning" size="sm" title="Paket aktif tapi 2FA mati → benefit diblokir">Enterprise (2FA mati)</AppBadge>
                                <span v-else class="muted">Free</span>
                            </td>
                            <td v-if="isSuperAdmin" class="col--hide-xl">
                                <template v-if="u.last_login">
                                    <span class="mono">{{ u.last_login.ip || '—' }}</span><br>
                                    <span class="muted" style="font-size: 11.5px">{{ formatDateTime(u.last_login.at) }}</span>
                                </template>
                                <span v-else class="muted">—</span>
                            </td>
                            <td class="col--hide-lg">
                                <span class="mono">{{ u.nik || '—' }}</span><br>
                                <span class="mono muted">{{ u.phone || '—' }}</span>
                            </td>
                            <td class="col--hide-xl"><span class="muted">{{ formatDate(u.created_at) }}</span></td>
                            <td v-if="canManage">
                                <div v-if="u.is_super_admin || u.id === currentUserId" class="muted">—</div>
                                <div v-else class="row-actions">
                                    <!-- Reset password: SuperAdmin (semua) & Admin (instansinya sendiri). -->
                                    <AppButton v-if="canResetPassword(u)" variant="ghost" size="sm" @click="openReset(u)">
                                        <template #icon><KeyRoundIcon :size="14" /></template>
                                        Reset Sandi
                                    </AppButton>

                                    <!-- Aksi monetisasi & blokir: SuperAdmin saja. -->
                                    <template v-if="isSuperAdmin">
                                        <AppButton v-if="u.is_banned" variant="outline" size="sm" @click="unban(u)">
                                            <template #icon><CircleCheckIcon :size="14" /></template>
                                            Buka Blokir
                                        </AppButton>
                                        <template v-else>
                                            <AppButton v-if="u.organization" variant="ghost" size="sm" @click="openRole(u)">
                                                <template #icon><UserCogIcon :size="14" /></template>
                                                Peran
                                            </AppButton>
                                            <AppButton variant="ghost" size="sm" @click="openCredit(u)">
                                                <template #icon><WalletIcon :size="14" /></template>
                                                Credit
                                            </AppButton>
                                            <AppButton
                                                :variant="u.is_enterprise ? 'outline' : 'primary'"
                                                size="sm"
                                                :disabled="!u.is_enterprise && !u.two_factor_enabled"
                                                :title="!u.is_enterprise && !u.two_factor_enabled ? 'User wajib mengaktifkan 2FA dahulu' : ''"
                                                @click="togglePlan(u)"
                                            >
                                                <template #icon><CrownIcon :size="14" /></template>
                                                {{ u.is_enterprise ? 'Cabut' : 'Enterprise' }}
                                            </AppButton>
                                            <AppButton variant="danger" size="sm" @click="openBan(u)">
                                                <template #icon><BanIcon :size="14" /></template>
                                                Blokir
                                            </AppButton>
                                        </template>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal blokir akun: wajib menyebut alasan (ditampilkan ke user saat login). -->
        <AppModal v-model="banOpen" title="Blokir Akun">
            <p class="ban-modal__intro">
                Blokir akun <strong>{{ banTarget?.name }}</strong>. Akun tidak dapat masuk dan
                <strong>alasan di bawah akan ditampilkan kepada pengguna saat mencoba login</strong>.
            </p>
            <AppTextarea
                v-model="banReason"
                label="Alasan pemblokiran"
                :rows="4"
                placeholder="Mis. Pelanggaran ketentuan penggunaan…"
                :error="banForm.errors.reason"
                required
            />
            <template #footer>
                <AppButton variant="ghost" @click="banOpen = false">Batal</AppButton>
                <AppButton variant="danger" :loading="banForm.processing" :disabled="banReason.trim().length < 5" @click="submitBan">
                    <template #icon><BanIcon :size="15" /></template>
                    Blokir Akun
                </AppButton>
            </template>
        </AppModal>

        <!-- Modal ubah peran: Admin / Non-Admin (Operator). -->
        <AppModal v-model="roleOpen" title="Ubah Peran">
            <p class="modal-intro">
                Atur peran <strong>{{ roleTarget?.name }}</strong> pada instansi
                <strong>{{ roleTarget?.organization?.nama }}</strong>.
            </p>
            <AppSelect
                v-model="roleForm.role"
                native
                label="Peran"
                :options="[
                    { value: 'Admin', label: 'Admin (kelola instansi)' },
                    { value: 'Operator', label: 'Non-Admin (Operator)' },
                ]"
                :error="roleForm.errors.role"
            />
            <template #footer>
                <AppButton variant="ghost" @click="roleOpen = false">Batal</AppButton>
                <AppButton variant="primary" :loading="roleForm.processing" @click="submitRole">Simpan Peran</AppButton>
            </template>
        </AppModal>

        <!-- Modal sesuaikan credit (dua arah). Negatif → dikurangi (clamp 0). -->
        <AppModal v-model="creditOpen" title="Sesuaikan Credit">
            <p class="modal-intro">
                Saldo saat ini <strong>{{ creditTarget?.credit_balance.toLocaleString('id-ID') }}</strong> credit.
                Masukkan jumlah positif untuk menambah atau negatif untuk mengurangi.
            </p>
            <AppInput
                v-model.number="creditForm.delta"
                type="number"
                label="Jumlah (boleh negatif)"
                placeholder="mis. 100 atau -50"
                :error="creditForm.errors.delta"
            />
            <AppInput
                v-model="creditForm.reason"
                label="Alasan"
                placeholder="mis. Bonus kegiatan / koreksi"
                :error="creditForm.errors.reason"
            />
            <template #footer>
                <AppButton variant="ghost" @click="creditOpen = false">Batal</AppButton>
                <AppButton
                    variant="primary"
                    :loading="creditForm.processing"
                    :disabled="!creditForm.delta || creditForm.reason.trim().length < 3"
                    @click="submitCredit"
                >
                    Terapkan
                </AppButton>
            </template>
        </AppModal>

        <!-- Modal reset password (SuperAdmin / Admin instansi). -->
        <AppModal v-model="resetOpen" title="Reset Password">
            <p class="modal-intro">
                Setel password baru untuk <strong>{{ resetTarget?.name }}</strong>. Beritahukan password ini
                kepada pengguna secara aman; mereka dapat menggantinya nanti di Pengaturan.
            </p>
            <AppInput
                v-model="resetForm.password"
                type="password"
                label="Password baru"
                placeholder="Minimal 8 karakter"
                :error="resetForm.errors.password"
            />
            <AppInput
                v-model="resetForm.password_confirmation"
                type="password"
                label="Ulangi password"
                placeholder="Ketik ulang password"
            />
            <template #footer>
                <AppButton variant="ghost" @click="resetOpen = false">Batal</AppButton>
                <AppButton
                    variant="primary"
                    :loading="resetForm.processing"
                    :disabled="resetForm.password.length < 8 || resetForm.password !== resetForm.password_confirmation"
                    @click="submitReset"
                >
                    <template #icon><KeyRoundIcon :size="15" /></template>
                    Reset Password
                </AppButton>
            </template>
        </AppModal>
    </BaseLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { SearchIcon, UsersIcon, BanIcon, CircleCheckIcon, UserCogIcon, WalletIcon, CrownIcon, KeyRoundIcon } from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppSelect from '@/Components/App/AppSelect.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppModal from '@/Components/App/AppModal.vue';
import { swalConfirm } from '@/Composables/useSwal';
import { navGroups } from '@/data/navGroups';

interface UserOrg { id: number; nama: string; kode: string; type: string }
interface LastLogin { ip: string | null; at: string | null }
interface UserRow {
    id: number; name: string; email: string;
    nik: string | null; phone: string | null;
    status: string; requested_role: string | null; roles: string[];
    is_super_admin: boolean;
    is_banned: boolean; banned_reason: string | null; banned_at: string | null;
    created_at: string | null; organization: UserOrg | null;
    credit_balance: number; plan: string;
    is_enterprise: boolean; is_enterprise_active: boolean;
    two_factor_enabled: boolean; marketplace_enabled: boolean;
    last_login: LastLogin | null;
}

const props = defineProps<{ users: UserRow[]; isSuperAdmin: boolean; currentUserId: number }>();

// Aktor saat ini (dari shared props) untuk menentukan kewenangan Admin lintas-instansi.
const page = usePage();
const actor = computed<any>(() => (page.props.auth as any)?.user ?? {});
const isAdmin = computed<boolean>(() => (actor.value?.roles ?? []).includes('Admin'));
const canManage = computed<boolean>(() => props.isSuperAdmin || isAdmin.value);

/** SuperAdmin boleh reset siapa pun; Admin hanya pada instansinya sendiri. */
function canResetPassword(u: UserRow): boolean {
    if (u.is_super_admin || u.id === props.currentUserId) return false;
    if (props.isSuperAdmin) return true;
    return isAdmin.value && !!u.organization && u.organization.id === actor.value?.organization?.id;
}

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
function formatDateTime(d: string | null): string {
    if (!d) return '—';
    return new Date(d.replace(' ', 'T')).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

// --- Blokir / buka blokir (SuperAdmin) ---
const banOpen = ref(false);
const banTarget = ref<UserRow | null>(null);
const banReason = ref('');
const banForm = useForm<{ reason: string }>({ reason: '' });

function openBan(u: UserRow): void {
    banTarget.value = u;
    banReason.value = '';
    banForm.clearErrors();
    banOpen.value = true;
}

function submitBan(): void {
    if (!banTarget.value) return;
    banForm.reason = banReason.value.trim();
    banForm.post(`/users/${banTarget.value.id}/ban`, {
        preserveScroll: true,
        onSuccess: () => { banOpen.value = false; banTarget.value = null; },
    });
}

async function unban(u: UserRow): Promise<void> {
    const ok = await swalConfirm({
        title: 'Buka blokir akun?',
        text: `${u.name} akan dapat masuk kembali ke aplikasi.`,
        confirmText: 'Ya, buka blokir',
    });
    if (!ok) return;
    useForm({}).post(`/users/${u.id}/unban`, { preserveScroll: true });
}

// --- Ubah peran (SuperAdmin) ---
const roleOpen = ref(false);
const roleTarget = ref<UserRow | null>(null);
const roleForm = useForm<{ role: string }>({ role: 'Operator' });

function openRole(u: UserRow): void {
    roleTarget.value = u;
    roleForm.role = u.roles.includes('Admin') ? 'Admin' : 'Operator';
    roleForm.clearErrors();
    roleOpen.value = true;
}
function submitRole(): void {
    if (!roleTarget.value) return;
    roleForm.post(`/users/${roleTarget.value.id}/role`, {
        preserveScroll: true,
        onSuccess: () => { roleOpen.value = false; },
    });
}

// --- Sesuaikan credit (SuperAdmin, dua arah) ---
const creditOpen = ref(false);
const creditTarget = ref<UserRow | null>(null);
const creditForm = useForm<{ delta: number | null; reason: string }>({ delta: null, reason: '' });

function openCredit(u: UserRow): void {
    creditTarget.value = u;
    creditForm.delta = null;
    creditForm.reason = '';
    creditForm.clearErrors();
    creditOpen.value = true;
}
function submitCredit(): void {
    if (!creditTarget.value) return;
    creditForm.post(`/users/${creditTarget.value.id}/credit`, {
        preserveScroll: true,
        onSuccess: () => { creditOpen.value = false; },
    });
}

// --- Reset password (SuperAdmin / Admin instansi) ---
const resetOpen = ref(false);
const resetTarget = ref<UserRow | null>(null);
const resetForm = useForm<{ password: string; password_confirmation: string }>({ password: '', password_confirmation: '' });

function openReset(u: UserRow): void {
    resetTarget.value = u;
    resetForm.password = '';
    resetForm.password_confirmation = '';
    resetForm.clearErrors();
    resetOpen.value = true;
}
function submitReset(): void {
    if (!resetTarget.value) return;
    resetForm.post(`/users/${resetTarget.value.id}/reset-password`, {
        preserveScroll: true,
        onSuccess: () => { resetOpen.value = false; resetForm.reset(); },
    });
}

// --- Set / cabut Enterprise (SuperAdmin) ---
async function togglePlan(u: UserRow): Promise<void> {
    const activate = !u.is_enterprise;
    if (activate && !u.two_factor_enabled) return; // tombol disabled, jaga-jaga
    const ok = await swalConfirm({
        title: activate ? 'Aktifkan paket Enterprise?' : 'Cabut paket Enterprise?',
        text: activate
            ? `${u.name} akan bebas-credit selama 1 tahun (selama 2FA aktif).`
            : `${u.name} kembali ke paket Free.`,
        confirmText: activate ? 'Ya, aktifkan' : 'Ya, cabut',
    });
    if (!ok) return;
    useForm({ action: activate ? 'activate' : 'deactivate' })
        .post(`/users/${u.id}/plan`, { preserveScroll: true });
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

.table-wrap { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 12px 14px; text-align: left; font-size: 13px; border-bottom: 1px solid var(--color-border); vertical-align: top; }
.table th { font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--color-text-subtle); font-weight: 700; white-space: nowrap; }
.table tbody tr:last-child td { border-bottom: none; }
.table tbody tr:hover { background: var(--color-bg-subtle); }

/* Responsif: sembunyikan kolom sekunder bertahap agar tabel tak memanjang ke kanan.
   Info penting (instansi/peran) tetap muncul ringkas di sel "Pengguna" via .user-cell__meta. */
.user-cell__meta { display: none; font-size: 11.5px; color: var(--color-text-subtle); margin-top: 2px; }
@media (max-width: 1280px) { .col--hide-xl { display: none; } }
@media (max-width: 1024px) { .col--hide-lg { display: none; } }
@media (max-width: 768px)  { .col--hide-md { display: none; } }
@media (max-width: 600px)  {
    .col--hide-sm { display: none; }
    .user-cell__meta { display: block; }
    .table th, .table td { padding: 10px 10px; }
}

.user-cell { display: flex; align-items: center; gap: 10px; }
.user-cell__avatar {
    width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; font-size: 12px; font-weight: 700;
}
.user-cell__info { display: flex; flex-direction: column; min-width: 0; }
.user-cell__name { font-weight: 600; color: var(--color-text-primary); }
.user-cell__email { font-size: 12px; color: var(--color-text-muted); overflow: hidden; text-overflow: ellipsis; max-width: 200px; }

.org-name { display: block; color: var(--color-text-primary); font-weight: 600; }
.org-kode { display: block; font-size: 11.5px; color: var(--color-text-subtle); }
.muted { color: var(--color-text-muted); }
.mono { font-family: var(--font-mono); font-size: 12px; color: var(--color-text-primary); }

.ban-reason {
    display: block; max-width: 220px; margin-top: 4px;
    font-size: 11.5px; line-height: 1.4; color: var(--color-danger, #dc2626);
    white-space: normal; overflow: hidden; text-overflow: ellipsis;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
}
.ban-modal__intro { font-size: 13.5px; line-height: 1.5; color: var(--color-text-primary); margin-bottom: 14px; }
.modal-intro { font-size: 13.5px; line-height: 1.5; color: var(--color-text-primary); margin-bottom: 14px; }
.modal-intro + :deep(.app-field) { margin-bottom: 12px; }
.credit-amount { font-weight: 700; color: var(--color-text-primary); }
.row-actions { display: flex; gap: 6px; flex-wrap: wrap; }
</style>
