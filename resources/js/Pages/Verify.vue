<!--
    resources/js/Pages/Verify.vue
    Halaman verifikasi publik — ambil status keaslian dari API stateless (server-side).
    Menampilkan logo acara (bila ada), pelaksana, lokasi, rentang tanggal, badge
    "Terverifikasi oleh sistem" (shield), dan identitas aplikasi.
-->
<template>
    <Head title="Verifikasi Sertifikat" />

    <div class="vf">
        <div class="vf__card">
            <!-- Header brand aplikasi -->
            <div class="vf__app">
                <img v-if="app.logo" :src="app.logo" :alt="app.name" class="vf__app-logo" />
                <span v-else class="vf__app-mark" aria-hidden="true">
                    <ShieldCheckIcon :size="18" />
                </span>
                <div class="vf__app-text">
                    <span class="vf__app-name">{{ app.name || 'SIGITAL' }}</span>
                    <span class="vf__app-tag">{{ app.tagline || 'Verifikasi Sertifikat Digital' }}</span>
                </div>
            </div>

            <div v-if="loading" class="vf__loading">
                <span class="vf__spinner" aria-hidden="true"></span>
                Memeriksa keaslian…
            </div>

            <template v-else>
                <!-- Logo acara — hanya bila tersedia (area dikondisikan) -->
                <div v-if="data?.logo" class="vf__hero">
                    <img :src="data.logo" alt="Logo Acara" class="vf__hero-logo" />
                </div>

                <!-- Badge status + shield -->
                <div class="vf__status" :class="`vf__status--${result.status}`">
                    <span class="vf__status-icon">
                        <ShieldCheckIcon v-if="result.status === 'valid'" :size="22" />
                        <ShieldXIcon v-else-if="result.status === 'revoked'" :size="22" />
                        <ShieldAlertIcon v-else :size="22" />
                    </span>
                    <div class="vf__status-text">
                        <strong>{{ result.message }}</strong>
                        <span v-if="result.status === 'valid'" class="vf__status-sub">Terverifikasi oleh sistem</span>
                    </div>
                </div>

                <!-- Detail sertifikat -->
                <dl v-if="data" class="vf__meta">
                    <div class="vf__row"><dt>Nomor</dt><dd class="vf__mono">{{ data.nomor }}</dd></div>
                    <div class="vf__row"><dt>Nama</dt><dd>{{ data.nama }}</dd></div>
                    <div class="vf__row"><dt>Acara</dt><dd>{{ data.acara }}</dd></div>
                    <div v-if="data.pelaksana" class="vf__row"><dt>Pelaksana</dt><dd>{{ data.pelaksana }}</dd></div>
                    <div v-if="data.lokasi" class="vf__row"><dt>Lokasi</dt><dd>{{ data.lokasi }}</dd></div>
                    <div class="vf__row"><dt>Tanggal Acara</dt><dd>{{ rentangTanggal }}</dd></div>
                    <div class="vf__row"><dt>Diterbitkan</dt><dd>{{ data.diterbitkan || '-' }}</dd></div>
                </dl>

                <!-- Unduh PDF — hanya untuk sertifikat sah -->
                <a v-if="result.status === 'valid'" :href="`/api/v1/verify/${token}/download`" class="vf__download">
                    <DownloadIcon :size="16" />
                    Unduh Sertifikat (PDF)
                </a>
            </template>

            <p class="vf__hint">
                <LockIcon :size="12" class="vf__hint-icon" />
                Verifikasi dilakukan di sisi server. Data pribadi sensitif tidak ditampilkan.
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { ShieldCheckIcon, ShieldXIcon, ShieldAlertIcon, LockIcon, DownloadIcon } from '@lucide/vue';

interface VerifyData {
    nomor: string; nama: string; acara: string;
    pelaksana?: string | null; lokasi?: string | null;
    tanggal_mulai?: string | null; tanggal_selesai?: string | null;
    tanggal_acara: string | null; diterbitkan: string | null;
    logo?: string | null;
}
interface AppBrand { name: string | null; tagline: string | null; logo: string | null; }
interface VerifyResult {
    status: 'valid' | 'revoked' | 'not_found' | 'error';
    message: string;
    data?: VerifyData;
    app?: AppBrand;
}

const props = defineProps<{ token: string }>();

const loading = ref(true);
const result = ref<VerifyResult>({ status: 'error', message: '' });

const data = computed(() => result.value.data ?? null);
const app = computed<AppBrand>(() => result.value.app ?? { name: 'SIGITAL', tagline: 'Verifikasi Sertifikat Digital', logo: null });

// Rentang tanggal mulai–selesai; bila sama / selesai kosong → satu tanggal saja.
const rentangTanggal = computed(() => {
    const d = data.value;
    if (!d) return '-';
    const mulai = d.tanggal_mulai;
    const selesai = d.tanggal_selesai;
    if (!mulai) return '-';
    if (selesai && selesai !== mulai) return `${mulai} – ${selesai}`;
    return mulai;
});

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
.vf {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: linear-gradient(160deg, #f8fafc 0%, #eef2ff 100%);
    padding: 24px; font-family: var(--font-sans, sans-serif);
}
.vf__card {
    width: 100%; max-width: 460px; background: #fff;
    border: 1px solid #e2e8f0; border-radius: 20px;
    padding: 28px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.10);
}

/* Brand aplikasi */
.vf__app { display: flex; align-items: center; gap: 10px; padding-bottom: 18px; border-bottom: 1px solid #f1f5f9; }
.vf__app-logo { height: 34px; width: auto; object-fit: contain; }
.vf__app-mark {
    display: inline-flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 9px;
    background: linear-gradient(135deg, #2563eb, #4f46e5); color: #fff;
}
.vf__app-text { display: flex; flex-direction: column; line-height: 1.25; }
.vf__app-name { font-size: 14px; font-weight: 700; color: #0f172a; }
.vf__app-tag { font-size: 11px; color: #64748b; }

.vf__loading { display: flex; align-items: center; justify-content: center; gap: 10px; color: #64748b; padding: 40px 0; }
.vf__spinner { width: 18px; height: 18px; border: 2px solid #cbd5e1; border-top-color: #2563eb; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Logo acara */
.vf__hero { display: flex; justify-content: center; margin: 22px 0 4px; }
.vf__hero-logo {
    max-height: 96px; max-width: 70%; object-fit: contain;
    padding: 8px; border-radius: 12px;
}

/* Status */
.vf__status {
    display: flex; align-items: center; gap: 12px;
    padding: 16px; border-radius: 14px; margin: 18px 0; text-align: left;
}
.vf__status-icon { display: inline-flex; flex-shrink: 0; }
.vf__status-text { display: flex; flex-direction: column; line-height: 1.3; }
.vf__status-text strong { font-size: 15px; font-weight: 700; }
.vf__status-sub { font-size: 12px; opacity: 0.85; }
.vf__status--valid { background: #dcfce7; color: #15803d; }
.vf__status--revoked { background: #fee2e2; color: #b91c1c; }
.vf__status--not_found, .vf__status--error { background: #fef3c7; color: #b45309; }

/* Meta */
.vf__meta { border-top: 1px solid #e2e8f0; padding-top: 14px; }
.vf__row { display: flex; justify-content: space-between; gap: 16px; padding: 8px 0; font-size: 14px; border-bottom: 1px solid #f8fafc; }
.vf__row:last-child { border-bottom: none; }
.vf__row dt { color: #64748b; flex-shrink: 0; }
.vf__row dd { font-weight: 600; color: #0f172a; text-align: right; }
.vf__mono { font-family: var(--font-mono, monospace); font-size: 13px; }

.vf__download {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    margin-top: 18px; padding: 12px 16px; border-radius: 12px;
    background: linear-gradient(135deg, #2563eb, #4f46e5); color: #fff;
    font-size: 14px; font-weight: 600; text-decoration: none;
    transition: filter 0.15s;
}
.vf__download:hover { filter: brightness(1.08); }
.vf__hint { display: flex; align-items: center; justify-content: center; gap: 5px; margin-top: 18px; font-size: 11.5px; color: #94a3b8; }
.vf__hint-icon { flex-shrink: 0; }
</style>
