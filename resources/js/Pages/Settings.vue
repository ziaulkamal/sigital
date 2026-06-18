<!--
    resources/js/Pages/Settings.vue
    Pengaturan akun sendiri (P4): tab Akun (profil) + Keamanan (ganti password).
    Tab Keamanan akan diperluas dengan 2FA pada P5.
-->
<template>
    <BaseLayout :nav-groups="navGroups" title="Pengaturan">
        <div class="page">
            <div class="page__header">
                <h1 class="page__title">Pengaturan</h1>
                <p class="page__sub">Kelola profil dan keamanan akun Anda.</p>
            </div>

            <FlashBanner />

            <AppTabs v-model="activeTab" :tabs="tabs">
                <!-- ── Akun (profil) ── -->
                <template #akun>
                    <div class="stg__section">
                        <h3 class="stg__section-title">Informasi Profil</h3>
                        <form class="stg__form" @submit.prevent="submitProfile">
                            <div class="stg__field">
                                <AppInput v-model="profileForm.name" label="Nama" required :error="profileForm.errors.name" />
                            </div>
                            <div class="stg__field">
                                <AppInput v-model="profileForm.email" type="email" label="Email" required :error="profileForm.errors.email" />
                            </div>
                            <div class="stg__actions">
                                <AppButton variant="primary" :loading="profileForm.processing" @click="submitProfile">
                                    Simpan Perubahan
                                </AppButton>
                            </div>
                        </form>

                        <AppDivider label="Zona Berbahaya" />
                        <div class="stg__danger">
                            <p class="stg__danger-desc">Menonaktifkan akun akan mengakhiri sesi Anda dan mencegah login. Hubungi administrator untuk mengaktifkan kembali.</p>
                            <div class="stg__field">
                                <AppInput v-model="deactivateForm.current_password" type="password" label="Password (untuk konfirmasi)" :error="deactivateForm.errors.current_password" />
                            </div>
                            <div class="stg__actions">
                                <AppButton variant="danger" :loading="deactivateForm.processing" @click="deactivate">
                                    Nonaktifkan Akun
                                </AppButton>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ── Keamanan (ganti password) ── -->
                <template #keamanan>
                    <div class="stg__section">
                        <h3 class="stg__section-title">Ganti Password</h3>
                        <form class="stg__form" @submit.prevent="submitPassword">
                            <div class="stg__field">
                                <AppInput v-model="passwordForm.current_password" type="password" label="Password Saat Ini" required :error="passwordForm.errors.current_password" />
                            </div>
                            <div class="stg__field">
                                <AppInput v-model="passwordForm.password" type="password" label="Password Baru" required :error="passwordForm.errors.password" />
                            </div>
                            <div class="stg__field">
                                <AppInput v-model="passwordForm.password_confirmation" type="password" label="Konfirmasi Password Baru" required />
                            </div>
                            <div class="stg__actions">
                                <AppButton variant="primary" :loading="passwordForm.processing" @click="submitPassword">
                                    Perbarui Password
                                </AppButton>
                            </div>
                        </form>

                        <AppDivider label="Autentikasi Dua Faktor (2FA)" />

                        <!-- 2FA aktif -->
                        <div v-if="twoFactorEnabled" class="stg__2fa">
                            <p class="stg__2fa-status stg__2fa-status--on">2FA aktif. Akun Anda dilindungi kode TOTP saat login.</p>

                            <div v-if="recoveryCodes.length" class="stg__codes">
                                <p class="stg__codes-title">Recovery codes (simpan di tempat aman, sekali pakai):</p>
                                <ul class="stg__codes-list">
                                    <li v-for="c in recoveryCodes" :key="c">{{ c }}</li>
                                </ul>
                            </div>

                            <form class="stg__form" @submit.prevent="disable2fa">
                                <div class="stg__field">
                                    <AppInput v-model="disableForm.current_password" type="password" label="Password Saat Ini (untuk menonaktifkan)" required :error="disableForm.errors.current_password" />
                                </div>
                                <div class="stg__actions">
                                    <AppButton variant="ghost" :loading="recoveryForm.processing" @click="regenerateCodes">
                                        Terbitkan Ulang Recovery Codes
                                    </AppButton>
                                    <AppButton variant="danger" :loading="disableForm.processing" @click="disable2fa">
                                        Nonaktifkan 2FA
                                    </AppButton>
                                </div>
                            </form>
                        </div>

                        <!-- Setup 2FA: tampilkan QR + konfirmasi kode -->
                        <div v-else-if="setup" class="stg__2fa">
                            <p class="stg__2fa-hint">Pindai QR berikut dengan aplikasi authenticator (Google Authenticator, Authy, dll), lalu masukkan kode 6 digit untuk mengonfirmasi.</p>
                            <div class="stg__qr" v-html="setup.svg" />
                            <p class="stg__secret">Atau masukkan kunci manual: <code>{{ setup.secret }}</code></p>
                            <form class="stg__form" @submit.prevent="confirm2fa">
                                <div class="stg__field">
                                    <AppInput v-model="confirmForm.code" label="Kode Verifikasi" placeholder="123456" required :error="confirmForm.errors.code" />
                                </div>
                                <div class="stg__actions">
                                    <AppButton variant="primary" :loading="confirmForm.processing" @click="confirm2fa">
                                        Konfirmasi &amp; Aktifkan
                                    </AppButton>
                                </div>
                            </form>
                        </div>

                        <!-- Belum aktif -->
                        <div v-else class="stg__2fa">
                            <p class="stg__2fa-status">2FA belum aktif. Tambahkan lapisan keamanan ekstra saat login.</p>
                            <div class="stg__actions">
                                <AppButton variant="primary" :loading="enableForm.processing" @click="enable2fa">
                                    Aktifkan 2FA
                                </AppButton>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ── Branding organisasi (P6/K8) — Admin org ── -->
                <template v-if="canManageBranding" #branding>
                    <div class="stg__section">
                        <h3 class="stg__section-title">Branding Organisasi</h3>
                        <p class="stg__2fa-hint">Logo &amp; kop disisipkan otomatis di kepala sertifikat yang Anda terbitkan.</p>
                        <form class="stg__form" @submit.prevent="submitBranding">
                            <div class="stg__field">
                                <label class="stg__label">Logo (PNG/JPG, maks 2MB)</label>
                                <input type="file" accept="image/png,image/jpeg" @change="onLogo" />
                                <p v-if="brandingForm.errors.logo" class="stg__err">{{ brandingForm.errors.logo }}</p>
                            </div>
                            <div class="stg__field">
                                <label class="stg__label">Kop Surat (PNG/JPG, maks 4MB)</label>
                                <input type="file" accept="image/png,image/jpeg" @change="onKop" />
                                <p v-if="brandingForm.errors.kop" class="stg__err">{{ brandingForm.errors.kop }}</p>
                            </div>
                            <div class="stg__actions">
                                <AppButton variant="primary" :loading="brandingForm.processing" @click="submitBranding">
                                    Simpan Branding
                                </AppButton>
                            </div>
                        </form>
                    </div>
                </template>
            </AppTabs>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import AppTabs from '@/Components/App/AppTabs.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppDivider from '@/Components/App/AppDivider.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { swalConfirm } from '@/Composables/useSwal';
import { navGroups } from '@/data/navGroups';
import { UserIcon, ShieldIcon, ImageIcon } from '@lucide/vue';

const page = usePage();
const authUser = computed(() => (page.props.auth as { user?: { name?: string; email?: string; two_factor_enabled?: boolean; roles?: string[]; is_super_admin?: boolean } })?.user ?? null);

// Branding hanya untuk Admin organisasi (bukan SuperAdmin yang tak punya org).
const canManageBranding = computed(() => !authUser.value?.is_super_admin && (authUser.value?.roles ?? []).includes('Admin'));

const tabs = computed(() => [
    { value: 'akun', label: 'Akun', icon: UserIcon },
    { value: 'keamanan', label: 'Keamanan', icon: ShieldIcon },
    ...(canManageBranding.value ? [{ value: 'branding', label: 'Branding', icon: ImageIcon }] : []),
]);
const activeTab = ref('akun');

// ── Status & data 2FA (P5) ──
interface TwoFactorFlash { stage: 'setup' | 'enabled'; svg?: string; secret?: string; recovery_codes?: string[] }
const twoFactorEnabled = computed(() => authUser.value?.two_factor_enabled ?? false);
const tfFlash = computed<TwoFactorFlash | null>(() => (page.props.flash as { twoFactor?: TwoFactorFlash })?.twoFactor ?? null);
const setup = computed(() => (tfFlash.value?.stage === 'setup' ? tfFlash.value : null));
const recoveryCodes = computed(() => (tfFlash.value?.stage === 'enabled' ? tfFlash.value.recovery_codes ?? [] : []));

const profileForm = useForm({
    name: authUser.value?.name ?? '',
    email: authUser.value?.email ?? '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submitProfile() {
    profileForm.patch('/settings/profile', { preserveScroll: true });
}

function submitPassword() {
    passwordForm.put('/settings/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

// ── Nonaktifkan akun ──
const deactivateForm = useForm({ current_password: '' });
async function deactivate() {
    const ok = await swalConfirm({
        title: 'Nonaktifkan akun?',
        text: 'Anda akan keluar dan tidak bisa login lagi.',
        confirmText: 'Ya, nonaktifkan',
        danger: true,
    });
    if (ok) deactivateForm.delete('/settings/account');
}

// ── 2FA ──
const enableForm = useForm({});
const confirmForm = useForm({ code: '' });
const disableForm = useForm({ current_password: '' });
const recoveryForm = useForm({});

function enable2fa() {
    enableForm.post('/settings/two-factor/enable', { preserveScroll: true });
}

function confirm2fa() {
    confirmForm.post('/settings/two-factor/confirm', {
        preserveScroll: true,
        onSuccess: () => confirmForm.reset(),
    });
}

function regenerateCodes() {
    recoveryForm.post('/settings/two-factor/recovery-codes', { preserveScroll: true });
}

function disable2fa() {
    disableForm.delete('/settings/two-factor', {
        preserveScroll: true,
        onSuccess: () => disableForm.reset(),
    });
}

// ── Branding (P6) ──
const brandingForm = useForm<{ logo: File | null; kop: File | null }>({ logo: null, kop: null });

function onLogo(e: Event) { brandingForm.logo = (e.target as HTMLInputElement).files?.[0] ?? null; }
function onKop(e: Event) { brandingForm.kop = (e.target as HTMLInputElement).files?.[0] ?? null; }

function submitBranding() {
    // method spoofing PUT untuk multipart.
    brandingForm.transform((d) => ({ ...d, _method: 'put' })).post('/settings/branding', {
        preserveScroll: true,
        forceFormData: true,
    });
}
</script>

<style scoped>
.page { padding: 24px; display: flex; flex-direction: column; gap: 20px; max-width: 800px; }
.page__title { font-size: 20px; font-weight: 800; color: var(--color-text-primary); font-family: var(--font-heading); letter-spacing: -0.02em; }
.page__sub { font-size: 13px; color: var(--color-text-muted); margin-top: 2px; }

.stg__section { display: flex; flex-direction: column; gap: 20px; }
.stg__section-title { font-size: 14px; font-weight: 700; color: var(--color-text-primary); margin: 0; }
.stg__form { display: flex; flex-direction: column; gap: 16px; max-width: 420px; }
.stg__field { display: flex; flex-direction: column; gap: 6px; }
.stg__label { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }
.stg__err { font-size: 12px; color: #dc2626; }
.stg__actions { display: flex; gap: 10px; flex-wrap: wrap; }
.stg__danger { display: flex; flex-direction: column; gap: 14px; max-width: 420px; }
.stg__danger-desc { font-size: 13px; color: var(--color-text-muted); line-height: 1.5; }

.stg__2fa { display: flex; flex-direction: column; gap: 16px; max-width: 420px; }
.stg__2fa-status { font-size: 13px; color: var(--color-text-muted); margin: 0; }
.stg__2fa-status--on { color: #16a34a; font-weight: 600; }
.stg__2fa-hint { font-size: 13px; color: var(--color-text-muted); margin: 0; line-height: 1.5; }
.stg__qr { width: 200px; height: 200px; background: #fff; padding: 8px; border: 1px solid var(--color-border); border-radius: 10px; }
.stg__qr :deep(svg) { width: 100%; height: 100%; }
.stg__secret { font-size: 12.5px; color: var(--color-text-muted); margin: 0; }
.stg__secret code { font-family: var(--font-mono); background: var(--color-bg-subtle); padding: 2px 6px; border-radius: 6px; }
.stg__codes { padding: 14px; border: 1px solid var(--color-border); border-radius: 10px; background: var(--color-bg-subtle); }
.stg__codes-title { font-size: 12.5px; font-weight: 600; color: var(--color-text-primary); margin: 0 0 8px; }
.stg__codes-list { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 16px; list-style: none; margin: 0; padding: 0; font-family: var(--font-mono); font-size: 12.5px; color: var(--color-text-primary); }
</style>
