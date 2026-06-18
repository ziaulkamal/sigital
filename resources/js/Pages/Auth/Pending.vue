<template>
    <AuthLayout :title="title" :subtitle="subtitle">
        <div class="pending">
            <div class="pending__icon" :class="`pending__icon--${tone}`">
                <component :is="icon" :size="30" />
            </div>

            <p class="pending__text">{{ message }}</p>

            <form @submit.prevent="logout">
                <AppButton type="submit" variant="secondary" size="md" style="width:100%">
                    Keluar
                </AppButton>
            </form>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { Clock, XCircle, Ban } from '@lucide/vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AppButton  from '@/Components/App/AppButton.vue';

const page = usePage();
const status = computed<string>(
    () => ((page.props.auth as { user?: { status?: string } })?.user?.status ?? 'pending'),
);

const config = computed(() => {
    switch (status.value) {
        case 'rejected':
            return { tone: 'danger', icon: XCircle, title: 'Pendaftaran ditolak',
                subtitle: 'Akun Anda tidak disetujui',
                message: 'Maaf, pendaftaran Anda belum dapat disetujui. Silakan hubungi administrator untuk informasi lebih lanjut.' };
        case 'suspended':
            return { tone: 'danger', icon: Ban, title: 'Akun ditangguhkan',
                subtitle: 'Akses Anda dinonaktifkan sementara',
                message: 'Akun Anda sedang ditangguhkan. Silakan hubungi administrator.' };
        default:
            return { tone: 'warning', icon: Clock, title: 'Menunggu persetujuan',
                subtitle: 'Akun Anda sedang ditinjau SuperAdmin',
                message: 'Terima kasih telah mendaftar. Akun Anda akan aktif setelah disetujui SuperAdmin. Kami akan memberi tahu Anda via email.' };
    }
});

const tone = computed(() => config.value.tone);
const icon = computed(() => config.value.icon);
const title = computed(() => config.value.title);
const subtitle = computed(() => config.value.subtitle);
const message = computed(() => config.value.message);

function logout(): void {
    router.post('/logout');
}
</script>

<style scoped>
.pending { display: flex; flex-direction: column; align-items: center; gap: 18px; text-align: center; }
.pending__icon {
    width: 64px; height: 64px; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
}
.pending__icon--warning { background: color-mix(in srgb, #f59e0b 12%, transparent); color: #f59e0b; }
.pending__icon--danger  { background: color-mix(in srgb, #ef4444 12%, transparent); color: #ef4444; }
.pending__text { font-size: 13.5px; line-height: 1.6; color: var(--color-text-muted); }
</style>
