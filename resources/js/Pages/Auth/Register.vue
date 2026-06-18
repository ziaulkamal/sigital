<template>
    <AuthLayout title="Daftar sebagai panitia" subtitle="Akun aktif setelah disetujui SuperAdmin">
        <form class="auth-form" @submit.prevent="submit">
            <div class="auth-form__field">
                <label class="auth-form__label">Nama lengkap</label>
                <AppInput v-model="form.name" placeholder="Nama Anda" :error="form.errors.name" />
            </div>

            <div class="auth-form__field">
                <label class="auth-form__label">Email</label>
                <AppInput v-model="form.email" type="email" placeholder="anda@instansi.go.id" :error="form.errors.email" />
            </div>

            <div class="auth-form__row-2">
                <div class="auth-form__field">
                    <label class="auth-form__label">Password</label>
                    <AppInput v-model="form.password" type="password" placeholder="Min. 8 karakter" :error="form.errors.password" />
                </div>
                <div class="auth-form__field">
                    <label class="auth-form__label">Ulangi password</label>
                    <AppInput v-model="form.password_confirmation" type="password" placeholder="Ulangi" />
                </div>
            </div>

            <!-- Pilih: gabung organisasi yang ada / ajukan baru -->
            <div class="auth-form__field">
                <label class="auth-form__label">Organisasi</label>
                <div class="auth-form__seg">
                    <button
                        type="button"
                        class="auth-form__seg-btn"
                        :class="{ 'auth-form__seg-btn--active': form.org_mode === 'existing' }"
                        @click="form.org_mode = 'existing'"
                    >
                        Gabung yang ada
                    </button>
                    <button
                        type="button"
                        class="auth-form__seg-btn"
                        :class="{ 'auth-form__seg-btn--active': form.org_mode === 'new' }"
                        @click="form.org_mode = 'new'"
                    >
                        Ajukan baru
                    </button>
                </div>
            </div>

            <!-- Gabung organisasi yang ada -->
            <div v-if="form.org_mode === 'existing'" class="auth-form__field">
                <AppSelect
                    v-model="form.organization_id"
                    native
                    label="Pilih organisasi"
                    placeholder="— pilih —"
                    :options="orgOptions"
                    :error="form.errors.organization_id"
                />
            </div>

            <!-- Ajukan organisasi baru -->
            <template v-else>
                <div class="auth-form__row-2">
                    <div class="auth-form__field">
                        <label class="auth-form__label">Nama organisasi</label>
                        <AppInput v-model="form.org_nama" placeholder="mis. Dinas Kominfo" :error="form.errors.org_nama" />
                    </div>
                    <div class="auth-form__field">
                        <label class="auth-form__label">Kode</label>
                        <AppInput v-model="form.org_kode" placeholder="mis. DISKOMINFO" :error="form.errors.org_kode" />
                    </div>
                </div>

                <div class="auth-form__field">
                    <AppSelect
                        v-model="form.org_type"
                        native
                        label="Tipe organisasi"
                        :options="typeOptions"
                        :error="form.errors.org_type"
                    />
                </div>

                <!-- Surat rekomendasi wajib untuk dinas (K9) -->
                <div v-if="form.org_type === 'dinas'" class="auth-form__field">
                    <label class="auth-form__label">Surat rekomendasi penunjukan <span style="color:var(--color-danger)">*</span></label>
                    <FileDropzone
                        accept=".pdf,.jpg,.jpeg,.png"
                        :multiple="false"
                        :simulate="false"
                        hint="PDF / JPG / PNG, maks. 5 MB"
                        @files-added="onLetterAdded"
                        @file-removed="form.recommendation_letter = null"
                    />
                    <p v-if="form.errors.recommendation_letter" class="auth-form__err">{{ form.errors.recommendation_letter }}</p>
                </div>
            </template>

            <AppButton type="submit" variant="primary" size="lg" :loading="form.processing" style="width:100%">
                Daftar
            </AppButton>
        </form>

        <template #footer>
            <span style="color: var(--color-text-muted); font-size: 13px;">
                Sudah punya akun?
                <a href="/login" style="color: #6366f1; font-weight: 600;">Masuk</a>
            </span>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthLayout   from '@/Layouts/AuthLayout.vue';
import AppInput     from '@/Components/App/AppInput.vue';
import AppSelect    from '@/Components/App/AppSelect.vue';
import AppButton    from '@/Components/App/AppButton.vue';
import FileDropzone from '@/Components/App/FileDropzone.vue';

interface OrgOption { id: number; nama: string; kode: string; type: string }

const props = defineProps<{ organizations: OrgOption[] }>();

const form = useForm<{
    name: string; email: string; password: string; password_confirmation: string;
    org_mode: 'existing' | 'new';
    organization_id: number | '';
    org_nama: string; org_kode: string; org_type: 'dinas' | 'komunitas';
    recommendation_letter: File | null;
}>({
    name: '', email: '', password: '', password_confirmation: '',
    org_mode: 'existing',
    organization_id: '',
    org_nama: '', org_kode: '', org_type: 'dinas',
    recommendation_letter: null,
});

const orgOptions = computed(() =>
    props.organizations.map((o) => ({ value: o.id, label: `${o.nama} (${o.type})` })),
);
const typeOptions = [
    { value: 'dinas', label: 'Dinas (wajib surat rekomendasi)' },
    { value: 'komunitas', label: 'Komunitas' },
];

function onLetterAdded(files: File[]): void {
    form.recommendation_letter = files[0] ?? null;
}

function submit(): void {
    form.post('/register', {
        forceFormData: true,
        onError: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<style scoped>
.auth-form { display: flex; flex-direction: column; gap: 16px; }
.auth-form__row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.auth-form__field { display: flex; flex-direction: column; gap: 6px; }
.auth-form__label { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }
.auth-form__err { font-size: 11.5px; color: var(--color-danger); }

.auth-form__seg {
    display: grid; grid-template-columns: 1fr 1fr; gap: 4px;
    padding: 4px; border: 1.5px solid var(--color-border);
    border-radius: 10px; background: var(--color-bg-subtle);
}
.auth-form__seg-btn {
    padding: 8px 12px; border: none; border-radius: 7px; cursor: pointer;
    background: transparent; font-size: 13px; font-weight: 600;
    font-family: var(--font-sans); color: var(--color-text-muted);
    transition: all 150ms ease;
}
.auth-form__seg-btn--active { background: var(--color-surface); color: var(--color-text-primary); box-shadow: var(--shadow-sm); }
</style>
