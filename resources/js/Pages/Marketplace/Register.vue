<!--
    resources/js/Pages/Marketplace/Register.vue
    Landing pendaftaran Marketplace Creator (publik):
      - Tamu        → info program + syarat + tombol Daftar Akun / Masuk.
      - Belum approve / belum lengkap profil → arahkan ke langkah yang sesuai.
      - Sudah login & siap → form aplikasi (Nama, Alamat, KTP, S&K) → POST /marketplace/apply.
      - Sudah daftar (pending/approved) → ringkas status + tautan ke dashboard Creator.
    Form submit butuh autentikasi (KTP terikat ke akun); itulah kenapa tamu daftar dulu.
-->
<template>
    <AuthLayout title="Daftar Creator" subtitle="Publikasikan template & peroleh royalti">
        <!-- Ringkasan keuntungan + syarat (selalu tampil di atas) -->
        <div class="mp-intro">
            <p class="mp-intro__lead">
                Sebagai <strong>Marketplace Creator</strong>, template Anda dapat dipakai pengguna lain.
                Setiap pemakaian Anda menerima <strong>{{ ownerShare }} credit</strong> royalti
                (≈ Rp{{ (ownerShare * rupiahPerCredit).toLocaleString('id-ID') }}) dari {{ price }} credit per transaksi.
            </p>
            <ul class="mp-req">
                <li><CheckIcon :size="14" /> Upload KTP (wajib)</li>
                <li><CheckIcon :size="14" /> Email &amp; Nomor HP terverifikasi</li>
                <li><CheckIcon :size="14" /> Nama &amp; alamat lengkap</li>
                <li><CheckIcon :size="14" /> Menyetujui Syarat &amp; Ketentuan</li>
                <li><CheckIcon :size="14" /> Rekening pencairan (diisi &amp; diverifikasi setelah disetujui)</li>
            </ul>
        </div>

        <!-- TAMU: harus punya akun dulu -->
        <div v-if="!account.authenticated" class="mp-state">
            <p>Untuk mendaftar sebagai Creator, Anda harus memiliki akun terlebih dahulu.</p>
            <div class="mp-actions">
                <Link href="/register" class="mp-btn mp-btn--primary">Daftar Akun</Link>
                <Link href="/login" class="mp-btn mp-btn--ghost">Sudah punya akun? Masuk</Link>
            </div>
        </div>

        <!-- LOGIN tapi belum approve / belum lengkap profil -->
        <div v-else-if="!account.approved" class="mp-state">
            <p>Akun Anda belum aktif. Pendaftaran Creator tersedia setelah akun disetujui SuperAdmin.</p>
            <Link href="/pending" class="mp-btn mp-btn--ghost">Lihat status akun</Link>
        </div>
        <div v-else-if="account.needs_profile" class="mp-state">
            <p>Lengkapi profil (NIK &amp; nomor HP) dahulu sebelum mendaftar sebagai Creator.</p>
            <Link href="/complete-profile" class="mp-btn mp-btn--primary">Lengkapi Profil</Link>
        </div>

        <!-- Sudah jadi creator / sedang pending -->
        <div v-else-if="account.is_creator || account.application_status === 'pending'" class="mp-state">
            <p v-if="account.application_status === 'pending'">Pendaftaran Anda sedang menunggu verifikasi admin.</p>
            <p v-else>Anda sudah terdaftar sebagai Marketplace Creator.</p>
            <Link href="/marketplace/creator" class="mp-btn mp-btn--primary">Buka Dashboard Creator</Link>
        </div>

        <!-- Siap apply (login, approved, profil lengkap, belum creator) -->
        <form v-else class="auth-form" @submit.prevent="submit">
            <div v-if="account.application_status === 'rejected'" class="mp-alert">
                Pendaftaran sebelumnya ditolak. Alasan: <strong>{{ account.reject_reason }}</strong>. Silakan daftar ulang.
            </div>

            <div class="auth-form__field">
                <label class="auth-form__label">Nama lengkap</label>
                <AppInput v-model="form.full_name" placeholder="Sesuai KTP" :error="form.errors.full_name" />
            </div>

            <div class="auth-form__field">
                <label class="auth-form__label">Alamat lengkap</label>
                <AppTextarea v-model="form.address" :rows="3" placeholder="Alamat domisili sesuai KTP" :error="form.errors.address" />
            </div>

            <div class="auth-form__field">
                <label class="auth-form__label">Foto KTP <span style="color:var(--color-danger)">*</span></label>
                <FileDropzone
                    accept=".pdf,.jpg,.jpeg,.png"
                    :multiple="false"
                    :simulate="false"
                    hint="PDF / JPG / PNG, maks. 5 MB"
                    @files-added="onKtp"
                    @file-removed="form.ktp = null"
                />
                <p v-if="form.errors.ktp" class="auth-form__err">{{ form.errors.ktp }}</p>
            </div>

            <label class="mp-check">
                <input type="checkbox" v-model="form.terms" />
                <span>Saya menyetujui <a href="/legal/syarat-ketentuan" target="_blank">Syarat &amp; Ketentuan</a> yang berlaku.</span>
            </label>
            <p v-if="form.errors.terms" class="auth-form__err">{{ form.errors.terms }}</p>

            <AppButton type="submit" variant="primary" size="lg" :loading="form.processing" :disabled="!form.terms || !form.ktp" style="width:100%">
                Kirim Pendaftaran
            </AppButton>
        </form>

        <template #footer>
            <span style="color: var(--color-text-muted); font-size: 13px;">
                <Link href="/marketplace" style="color: #6366f1; font-weight: 600;">Lihat Marketplace</Link>
            </span>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import { CheckIcon } from '@lucide/vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppButton from '@/Components/App/AppButton.vue';
import FileDropzone from '@/Components/App/FileDropzone.vue';

interface Account {
    authenticated: boolean;
    approved?: boolean;
    needs_profile?: boolean;
    is_creator?: boolean;
    application_status?: string | null;
    reject_reason?: string | null;
    full_name?: string | null;
}

const props = defineProps<{
    price: number;
    ownerShare: number;
    rupiahPerCredit: number;
    account: Account;
}>();

const form = useForm<{ full_name: string; address: string; ktp: File | null; terms: boolean }>({
    full_name: props.account.full_name ?? '', address: '', ktp: null, terms: false,
});

function onKtp(files: File[]): void {
    form.ktp = files[0] ?? null;
}
function submit(): void {
    form.post('/marketplace/apply', { forceFormData: true });
}
</script>

<style scoped>
.mp-intro { margin-bottom: 18px; }
.mp-intro__lead { font-size: 13.5px; line-height: 1.55; color: var(--color-text-muted); }
.mp-req { list-style: none; margin: 12px 0 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.mp-req li { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--color-text-primary); }
.mp-req svg { color: #16a34a; flex-shrink: 0; }

.mp-state { display: flex; flex-direction: column; gap: 12px; align-items: stretch; }
.mp-state p { font-size: 13.5px; line-height: 1.5; color: var(--color-text-primary); }
.mp-actions { display: flex; flex-direction: column; gap: 8px; }

.mp-btn {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 11px 16px; border-radius: 10px; font-size: 14px; font-weight: 600;
    text-decoration: none; transition: all 150ms ease;
}
.mp-btn--primary { background: #6366f1; color: #fff; }
.mp-btn--primary:hover { background: #4f46e5; }
.mp-btn--ghost { background: var(--color-bg-subtle); color: var(--color-text-primary); border: 1px solid var(--color-border); }

.mp-alert { padding: 10px 12px; border-radius: 10px; font-size: 13px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
.mp-check { display: flex; gap: 8px; align-items: flex-start; font-size: 13px; color: var(--color-text-primary); }
.mp-check a { color: #6366f1; font-weight: 600; }

.auth-form { display: flex; flex-direction: column; gap: 16px; }
.auth-form__field { display: flex; flex-direction: column; gap: 6px; }
.auth-form__label { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }
.auth-form__err { font-size: 11.5px; color: var(--color-danger); }
</style>
