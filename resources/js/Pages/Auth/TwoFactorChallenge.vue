<!--
    resources/js/Pages/Auth/TwoFactorChallenge.vue
    Tantangan 2FA saat login (P5): masukkan kode TOTP, atau gunakan recovery code.
-->
<template>
    <AuthLayout title="Verifikasi Dua Faktor" :subtitle="useRecovery ? 'Masukkan salah satu recovery code Anda' : 'Masukkan kode dari aplikasi authenticator Anda'">
        <form class="auth-form" @submit.prevent="submit">
            <div v-if="!useRecovery" class="auth-form__field">
                <label class="auth-form__label">Kode Autentikasi</label>
                <AppInput
                    v-model="form.code"
                    placeholder="123456"
                    :error="form.errors.code"
                    size="md"
                />
            </div>
            <div v-else class="auth-form__field">
                <label class="auth-form__label">Recovery Code</label>
                <AppInput
                    v-model="form.recovery_code"
                    placeholder="XXXXXXXXXX-XXXXXXXXXX"
                    :error="form.errors.code"
                    size="md"
                />
            </div>

            <AppButton type="submit" variant="primary" size="lg" :loading="form.processing" style="width:100%">
                Verifikasi
            </AppButton>

            <button type="button" class="auth-form__toggle" @click="toggleMode">
                {{ useRecovery ? 'Gunakan kode authenticator' : 'Gunakan recovery code' }}
            </button>
        </form>
    </AuthLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';

const useRecovery = ref(false);
const form = useForm({ code: '', recovery_code: '' });

function toggleMode() {
    useRecovery.value = !useRecovery.value;
    form.clearErrors();
    form.code = '';
    form.recovery_code = '';
}

function submit() {
    form.post('/two-factor-challenge', {
        onFinish: () => { form.code = ''; form.recovery_code = ''; },
    });
}
</script>

<style scoped>
.auth-form { display: flex; flex-direction: column; gap: 18px; }
.auth-form__field { display: flex; flex-direction: column; gap: 6px; }
.auth-form__label { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }
.auth-form__toggle {
    font-size: 12.5px; color: #6366f1; font-weight: 500; background: none;
    border: none; cursor: pointer; font-family: var(--font-sans); padding: 0;
}
.auth-form__toggle:hover { text-decoration: underline; }
</style>
