<template>
    <AuthLayout title="Welcome back" subtitle="Sign in to your CRM Studio account">
        <form class="auth-form" @submit.prevent="submit">
            <div class="auth-form__field">
                <label class="auth-form__label">Email address</label>
                <AppInput
                    v-model="form.email"
                    type="email"
                    placeholder="you@example.com"
                    :error="form.errors.email"
                    size="md"
                />
            </div>

            <div class="auth-form__field">
                <div class="auth-form__label-row">
                    <label class="auth-form__label">Password</label>
                    <a href="/forgot-password" class="auth-form__forgot">Forgot password?</a>
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
                <AppCheckbox v-model="form.remember" label="Remember me for 30 days" />
            </div>

            <AppButton
                type="submit"
                variant="primary"
                size="lg"
                :loading="form.processing"
                style="width:100%"
            >
                Sign in
            </AppButton>

            <div class="auth-form__divider">
                <span>or continue with</span>
            </div>

            <div class="auth-form__social">
                <button type="button" class="auth-form__social-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    Google
                </button>
                <button type="button" class="auth-form__social-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/></svg>
                    GitHub
                </button>
            </div>
        </form>

        <template #footer>
            <span style="color: var(--color-text-muted); font-size: 13px;">
                Don't have an account?
                <a href="/register" style="color: #6366f1; font-weight: 600;">Create one free</a>
            </span>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AuthLayout   from '@/Layouts/AuthLayout.vue';
import AppInput     from '@/Components/App/AppInput.vue';
import AppButton    from '@/Components/App/AppButton.vue';
import AppCheckbox  from '@/Components/App/AppCheckbox.vue';

const form = useForm({ email: '', password: '', remember: false });

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<style scoped>
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
