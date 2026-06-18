<!--
    resources/js/Pages/Verify.vue
    Halaman verifikasi publik — ambil status keaslian dari API stateless (server-side).
-->
<template>
    <div class="verify">
        <div class="verify__card">
            <div class="verify__brand">{{ instansi }}</div>
            <h1 class="verify__heading">Verifikasi Sertifikat</h1>

            <div v-if="loading" class="verify__state verify__state--loading">
                <span class="verify__spinner" aria-hidden="true"></span>
                Memeriksa keaslian…
            </div>

            <template v-else>
                <div class="verify__status" :class="`verify__status--${result.status}`">
                    <span class="verify__icon">{{ icon }}</span>
                    <span>{{ result.message }}</span>
                </div>

                <dl v-if="result.data" class="verify__meta">
                    <div class="verify__row"><dt>Nomor</dt><dd>{{ result.data.nomor }}</dd></div>
                    <div class="verify__row"><dt>Nama</dt><dd>{{ result.data.nama }}</dd></div>
                    <div class="verify__row"><dt>Acara</dt><dd>{{ result.data.acara }}</dd></div>
                    <div class="verify__row"><dt>Tanggal Acara</dt><dd>{{ result.data.tanggal_acara || '-' }}</dd></div>
                    <div class="verify__row"><dt>Diterbitkan</dt><dd>{{ result.data.diterbitkan || '-' }}</dd></div>
                </dl>
            </template>

            <p class="verify__hint">Verifikasi dilakukan di sisi server. Data pribadi sensitif tidak ditampilkan.</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

interface VerifyData {
    nomor: string; nama: string; acara: string;
    tanggal_acara: string | null; diterbitkan: string | null;
}
interface VerifyResult {
    status: 'valid' | 'revoked' | 'not_found' | 'error';
    message: string;
    data?: VerifyData;
}

const props = defineProps<{ token: string }>();

const instansi = 'SIGITAL — Verifikasi Sertifikat Digital';
const loading = ref(true);
const result = ref<VerifyResult>({ status: 'error', message: '' });

const icon = computed(() => ({
    valid: '✓', revoked: '✕', not_found: '?', error: '!',
}[result.value.status]));

onMounted(async () => {
    try {
        const res = await fetch(`/api/v1/verify/${props.token}`, {
            headers: { Accept: 'application/json' },
        });
        result.value = await res.json();
    } catch {
        result.value = { status: 'error', message: 'Gagal menghubungi server verifikasi.' };
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
.verify {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: var(--color-bg, #f1f5f9); padding: 24px; font-family: var(--font-sans, sans-serif);
}
.verify__card {
    width: 100%; max-width: 480px; background: var(--color-surface, #fff);
    border: 1px solid var(--color-border, #e2e8f0); border-radius: 16px;
    padding: 32px; box-shadow: 0 10px 40px rgba(15, 23, 42, 0.08); text-align: center;
}
.verify__brand { font-size: 12px; letter-spacing: 1px; text-transform: uppercase; color: var(--color-text-muted, #64748b); }
.verify__heading { font-size: 22px; font-weight: 700; margin: 6px 0 22px; color: var(--color-text-primary, #0f172a); }
.verify__state--loading { display: flex; align-items: center; justify-content: center; gap: 10px; color: var(--color-text-muted, #64748b); padding: 24px 0; }
.verify__spinner { width: 18px; height: 18px; border: 2px solid #cbd5e1; border-top-color: #2563eb; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.verify__status {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    padding: 16px; border-radius: 12px; font-weight: 600; font-size: 15px; margin-bottom: 18px;
}
.verify__status--valid { background: #dcfce7; color: #15803d; }
.verify__status--revoked { background: #fee2e2; color: #b91c1c; }
.verify__status--not_found, .verify__status--error { background: #fef3c7; color: #b45309; }
.verify__icon { font-size: 18px; font-weight: 800; }
.verify__meta { text-align: left; border-top: 1px solid var(--color-border, #e2e8f0); padding-top: 14px; }
.verify__row { display: flex; justify-content: space-between; gap: 16px; padding: 7px 0; font-size: 14px; }
.verify__row dt { color: var(--color-text-muted, #64748b); }
.verify__row dd { font-weight: 600; color: var(--color-text-primary, #0f172a); text-align: right; }
.verify__hint { margin-top: 18px; font-size: 12px; color: var(--color-text-subtle, #94a3b8); }
</style>
