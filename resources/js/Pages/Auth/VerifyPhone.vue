<!--
    resources/js/Pages/Auth/VerifyPhone.vue
    Verifikasi OTP WhatsApp setelah registrasi (anti akun palsu).
-->
<template>
    <AuthLayout title="Verifikasi WhatsApp" :subtitle="`Kode OTP dikirim ke ${phoneMasked}`">
        <FlashBanner />
        <form class="auth-form" @submit.prevent="submit">
            <div class="auth-form__field">
                <label class="auth-form__label">Kode OTP</label>
                <AppInput v-model="form.code" placeholder="6 digit" :error="form.errors.code" size="md" />
            </div>

            <AppButton type="submit" variant="primary" size="lg" :loading="form.processing" style="width:100%">
                Verifikasi
            </AppButton>

            <div class="auth-form__resend">
                <span>Tidak menerima kode?</span>
                <button type="button" class="auth-form__link" :disabled="resend.processing" @click="resendOtp">
                    Kirim ulang
                </button>
            </div>
        </form>

        <template #footer>
            <button type="button" class="auth-form__link" @click="logout">Keluar</button>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';
import FlashBanner from '@/Components/FlashBanner.vue';

defineProps<{ phoneMasked: string }>();

const form = useForm({ code: '' });
const resend = useForm({});

function submit() {
    form.post('/verify-phone', { onError: () => form.reset('code') });
}
function resendOtp() {
    resend.post('/verify-phone/resend', { preserveScroll: true });
}
function logout() {
    router.post('/logout');
}
</script>

<style scoped>
.auth-form { display: flex; flex-direction: column; gap: 16px; }
.auth-form__field { display: flex; flex-direction: column; gap: 6px; }
.auth-form__label { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }
.auth-form__resend { display: flex; gap: 6px; justify-content: center; font-size: 13px; color: var(--color-text-muted); }
.auth-form__link { background: none; border: none; cursor: pointer; color: #6366f1; font-weight: 600; font-family: var(--font-sans); font-size: 13px; }
.auth-form__link:hover { text-decoration: underline; }
</style>
