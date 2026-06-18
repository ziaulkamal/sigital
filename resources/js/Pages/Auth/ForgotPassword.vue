<template>
    <AuthLayout
        :title="sent ? 'Periksa email Anda' : 'Atur ulang password'"
        :subtitle="sent ? `Kami mengirim tautan ke ${form.email}` : 'Masukkan email Anda, kami akan kirim tautan atur ulang'"
    >
        <Transition name="fp-fade" mode="out-in">
            <!-- Sudah terkirim -->
            <div v-if="sent" key="sent" class="fp-sent">
                <div class="fp-sent__icon">
                    <MailIcon :size="28" style="color: #6366f1;" />
                </div>
                <p class="fp-sent__text">
                    Tidak menerimanya? Periksa folder spam atau
                    <button class="fp-sent__resend" @click="sent = false">coba email lain</button>.
                </p>
                <AppButton variant="ghost" size="md" style="width:100%" @click="sent = false">
                    Kembali
                </AppButton>
            </div>

            <!-- Form -->
            <form v-else key="form" class="auth-form" @submit.prevent="submit">
                <div class="auth-form__field">
                    <label class="auth-form__label">Email</label>
                    <AppInput
                        v-model="form.email"
                        type="email"
                        placeholder="anda@instansi.go.id"
                        :error="errors.email"
                        size="md"
                    />
                </div>
                <AppButton type="submit" variant="primary" size="lg" :loading="loading" style="width:100%">
                    Kirim Tautan
                </AppButton>
            </form>
        </Transition>

        <template #footer>
            <Link href="/login" style="color: #6366f1; font-size: 13px; font-weight: 600;">
                ← Kembali ke halaman masuk
            </Link>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Link } from '@inertiajs/vue3';
import { MailIcon } from '@lucide/vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AppInput   from '@/Components/App/AppInput.vue';
import AppButton  from '@/Components/App/AppButton.vue';

const form    = reactive({ email: '' });
const errors  = reactive({ email: '' });
const loading = ref(false);
const sent    = ref(false);

function submit() {
    errors.email = form.email ? '' : 'Email wajib diisi.';
    if (errors.email) return;
    loading.value = true;
    setTimeout(() => { loading.value = false; sent.value = true; }, 1200);
}
</script>

<style scoped>
.auth-form { display: flex; flex-direction: column; gap: 18px; }
.auth-form__field { display: flex; flex-direction: column; gap: 6px; }
.auth-form__label { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }

.fp-sent { display: flex; flex-direction: column; align-items: center; gap: 16px; text-align: center; }
.fp-sent__icon {
    width: 64px; height: 64px; border-radius: 16px;
    background: rgba(99,102,241,0.1); display: flex; align-items: center; justify-content: center;
}
.fp-sent__text { font-size: 13.5px; color: var(--color-text-muted); line-height: 1.6; }
.fp-sent__resend {
    color: #6366f1; font-weight: 600; border: none; background: transparent;
    cursor: pointer; font-family: var(--font-sans); font-size: inherit; padding: 0;
}
.fp-sent__resend:hover { text-decoration: underline; }

.fp-fade-enter-active, .fp-fade-leave-active { transition: all 200ms ease; }
.fp-fade-enter-from, .fp-fade-leave-to { opacity: 0; transform: translateY(8px); }
</style>
