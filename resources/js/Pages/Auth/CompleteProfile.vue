<template>
    <AuthLayout
        title="Lengkapi profil Anda"
        subtitle="Akun Anda sudah disetujui. Lengkapi NIK & nomor HP untuk membuka semua fitur."
    >
        <form class="auth-form" @submit.prevent="submit">
            <div class="auth-form__field">
                <label class="auth-form__label">NIK</label>
                <AppInput
                    :model-value="form.nik"
                    inputmode="numeric"
                    placeholder="16 digit NIK"
                    hint="Hanya angka, tepat 16 digit."
                    :error="form.errors.nik"
                    @update:model-value="(v) => (form.nik = onlyDigits(v, 16))"
                />
            </div>

            <div class="auth-form__field">
                <label class="auth-form__label">No. WhatsApp / HP</label>
                <AppInput
                    :model-value="form.phone"
                    inputmode="numeric"
                    placeholder="08xxxxxxxxxx"
                    hint="Hanya angka. Awalan 08 akan disimpan sebagai 628."
                    :error="form.errors.phone"
                    @update:model-value="(v) => (form.phone = onlyDigits(v, 15))"
                />
            </div>

            <AppButton type="submit" variant="primary" size="lg" :loading="form.processing" style="width:100%">
                Simpan & Lanjutkan
            </AppButton>
        </form>

        <template #footer>
            <button class="auth-form__logout" type="button" @click="logout">Keluar</button>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';

const form = useForm<{ nik: string; phone: string }>({ nik: '', phone: '' });

/** Sisakan digit saja dan batasi panjang (cegah karakter non-angka di input). */
function onlyDigits(value: string, max: number): string {
    return value.replace(/\D+/g, '').slice(0, max);
}

function submit(): void {
    form.post('/complete-profile');
}

function logout(): void {
    router.post('/logout');
}
</script>

<style scoped>
.auth-form { display: flex; flex-direction: column; gap: 16px; }
.auth-form__field { display: flex; flex-direction: column; gap: 6px; }
.auth-form__label { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }
.auth-form__logout {
    color: var(--color-text-muted); font-size: 13px; font-weight: 600;
    background: none; border: none; cursor: pointer; font-family: var(--font-sans);
}
.auth-form__logout:hover { color: var(--color-danger); text-decoration: underline; }
</style>
