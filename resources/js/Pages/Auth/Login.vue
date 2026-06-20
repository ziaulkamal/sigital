<template>
    <AuthLayout title="Selamat datang" :subtitle="`Masuk ke akun ${brandName}`">
        <!-- Akun diblokir → tampilkan alasan dengan jelas (di atas form). -->
        <div v-if="bannedMessage" class="auth-banned" role="alert">
            <BanIcon :size="18" class="auth-banned__icon" />
            <div>
                <strong class="auth-banned__title">Akun diblokir</strong>
                <p class="auth-banned__text">{{ bannedMessage }}</p>
            </div>
        </div>

        <form class="auth-form" @submit.prevent="submit">
            <div class="auth-form__field">
                <label class="auth-form__label">Email</label>
                <AppInput
                    v-model="form.email"
                    type="email"
                    placeholder="anda@instansi.go.id"
                    :error="bannedMessage ? '' : form.errors.email"
                    size="md"
                />
            </div>

            <div class="auth-form__field">
                <div class="auth-form__label-row">
                    <label class="auth-form__label">Password</label>
                    <Link href="/forgot-password" class="auth-form__forgot">Lupa password?</Link>
                </div>
                <AppInput
                    v-model="form.password"
                    type="password"
                    placeholder="••••••••"
                    :error="form.errors.password"
                    size="md"
                />
            </div>

            <div class="auth-form__row">
                <AppCheckbox v-model="form.remember" label="Ingat saya selama 30 hari" />
            </div>

            <AppButton
                type="submit"
                variant="primary"
                size="lg"
                :loading="form.processing"
                style="width:100%"
            >
                Masuk
            </AppButton>
        </form>

        <template #footer>
            <span style="color: var(--color-text-muted); font-size: 13px;">
                Belum punya akun?
                <Link href="/register" style="color: #6366f1; font-weight: 600;">Daftar sekarang</Link>
            </span>
            <span style="display:block; margin-top:6px; color: var(--color-text-muted); font-size: 13px;">
                Ingin jadi Creator?
                <Link href="/creator/register" style="color: #6366f1; font-weight: 600;">Daftar Creator</Link>
            </span>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import { BanIcon } from '@lucide/vue';
import AuthLayout   from '@/Layouts/AuthLayout.vue';
import AppInput     from '@/Components/App/AppInput.vue';
import AppButton    from '@/Components/App/AppButton.vue';
import AppCheckbox  from '@/Components/App/AppCheckbox.vue';

const brandName = computed(() => (usePage().props.app as { name?: string })?.name ?? 'SIGITAL');
const form = useForm({ email: '', password: '', remember: false });

// Pesan blokir berasal dari error 'email' yang diawali "Akun Anda diblokir."
const bannedMessage = computed(() => {
    const err = form.errors.email ?? '';
    return err.startsWith('Akun Anda diblokir') ? err : '';
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<style scoped>
.auth-banned {
    display: flex; gap: 10px; align-items: flex-start;
    padding: 12px 14px; margin-bottom: 18px;
    background: var(--color-danger-soft, rgba(220,38,38,0.08));
    border: 1px solid var(--color-danger, #dc2626); border-radius: 10px;
}
.auth-banned__icon { color: var(--color-danger, #dc2626); flex-shrink: 0; margin-top: 1px; }
.auth-banned__title { display: block; font-size: 13px; font-weight: 700; color: var(--color-danger, #dc2626); }
.auth-banned__text { font-size: 12.5px; line-height: 1.45; color: var(--color-text-primary); margin-top: 2px; }

.auth-form { display: flex; flex-direction: column; gap: 18px; }
.auth-form__field { display: flex; flex-direction: column; gap: 6px; }
.auth-form__label { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }
.auth-form__label-row { display: flex; align-items: center; justify-content: space-between; }
.auth-form__forgot { font-size: 12.5px; color: #6366f1; font-weight: 500; }
.auth-form__forgot:hover { text-decoration: underline; }
.auth-form__row { display: flex; align-items: center; }
.auth-form__divider {
    display: flex; align-items: center; gap: 12px;
    font-size: 12px; color: var(--color-text-subtle);
}
.auth-form__divider::before,
.auth-form__divider::after {
    content: ''; flex: 1; height: 1px; background: var(--color-border);
}
.auth-form__social { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.auth-form__social-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 9px 16px; border-radius: 9px;
    border: 1.5px solid var(--color-border); background: var(--color-surface);
    font-size: 13px; font-weight: 500; font-family: var(--font-sans);
    color: var(--color-text-primary); cursor: pointer; transition: all 150ms ease;
}
.auth-form__social-btn:hover { border-color: var(--color-text-muted); }
</style>
