<!--
    resources/js/Pages/Events/Form.vue
    Form buat/ubah acara: jadwal, lokasi, template, dan penetapan penanda tangan.
-->
<template>
    <BaseLayout :nav-groups="navGroups" title="Form Acara">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">{{ isEdit ? 'Ubah Acara' : 'Acara Baru' }}</h1>
                    <p class="page__sub">Tetapkan jadwal, template, dan penanda tangan.</p>
                </div>
            </div>

            <form class="ev-form page__card" @submit.prevent="submit">
                <div class="ev-form__grid">
                    <AppInput v-model="form.nama" label="Nama Acara" required :error="form.errors.nama" />
                    <AppInput v-model="form.kode" label="Kode Acara (untuk nomor)" hint="mis. PELATIHAN-AI" :error="form.errors.kode" />
                    <AppInput v-model="form.jadwal_mulai" type="datetime-local" label="Jadwal Mulai" required :error="form.errors.jadwal_mulai" />
                    <AppInput v-model="form.jadwal_selesai" type="datetime-local" label="Jadwal Selesai" :error="form.errors.jadwal_selesai" />
                    <AppInput v-model="form.lokasi" label="Lokasi" :error="form.errors.lokasi" />
                    <AppSelect v-model="form.template_id" label="Template" :options="templateOpts" placeholder="Pilih template…" :error="form.errors.template_id" />
                </div>

                <div class="ev-form__field">
                    <label class="ev-form__label">Keterangan Kegiatan</label>
                    <p class="ev-form__hint">Dipakai pada sertifikat. Jika dikosongkan, keterangan dibuat otomatis dari nama acara, lokasi, tanggal, durasi, dan instansi.</p>
                    <AppTextarea v-model="form.keterangan" :rows="3" placeholder="Kosongkan untuk keterangan otomatis…" :error="form.errors.keterangan" />
                </div>

                <div class="ev-form__field">
                    <label class="ev-form__label">Logo Acara (opsional)</label>
                    <p class="ev-form__hint">PNG/JPG. Bila kosong, sertifikat memakai logo organisasi.</p>
                    <div class="ev-form__logo">
                        <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="ev-form__logo-img" />
                        <input type="file" accept="image/png,image/jpeg" @change="onLogo" />
                    </div>
                    <p v-if="form.errors.logo" class="ev-form__err">{{ form.errors.logo }}</p>
                </div>

                <div class="ev-form__field">
                    <label class="ev-form__label">Penanda Tangan</label>

                    <!-- Ada penanda tangan: daftar pilihan -->
                    <template v-if="signatoryOptions.length">
                        <p class="ev-form__hint">Pilih satu atau lebih. Urutan mengikuti urutan pemilihan.</p>
                        <div class="ev-form__sigs">
                            <label v-for="s in signatoryOptions" :key="s.id" class="ev-form__sig"
                                :class="{ 'ev-form__sig--on': form.signatory_ids.includes(s.id) }">
                                <input type="checkbox" :value="s.id" v-model="form.signatory_ids" />
                                <span><strong>{{ s.nama }}</strong><br><small>{{ s.jabatan }}</small></span>
                            </label>
                        </div>
                    </template>

                    <!-- Belum ada penanda tangan: CTA dipertegas -->
                    <div v-else class="ev-form__empty">
                        <div class="ev-form__empty-icon">
                            <PenLine :size="22" />
                        </div>
                        <div class="ev-form__empty-text">
                            <strong>Belum ada penanda tangan</strong>
                            <span>Tambahkan minimal satu penanda tangan terlebih dahulu sebelum membuat acara.</span>
                        </div>
                        <AppButton variant="primary" size="md" tag="a" href="/signatories">
                            <Plus :size="16" /> Tambah Penanda Tangan
                        </AppButton>
                    </div>
                </div>

                <div class="ev-form__actions">
                    <AppButton variant="ghost" tag="a" href="/events">Batal</AppButton>
                    <AppButton variant="primary" :loading="form.processing" @click="submit">
                        {{ isEdit ? 'Simpan Perubahan' : 'Buat Acara' }}
                    </AppButton>
                </div>
            </form>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { PenLine, Plus } from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppSelect from '@/Components/App/AppSelect.vue';
import { navGroups } from '@/data/navGroups';

interface Opt { id: number; nama: string; jabatan?: string; }
interface EventData { id: number; nama: string; kode: string | null; jadwal_mulai: string | null; jadwal_selesai: string | null; lokasi: string | null; keterangan: string | null; logo: string | null; status: string; template_id: number | null; signatory_ids: number[]; }

const props = defineProps<{
    templateOptions: Opt[];
    signatoryOptions: Opt[];
    event?: EventData;
}>();

const isEdit = computed(() => !!props.event);
const templateOpts = computed(() => props.templateOptions.map((t) => ({ value: t.id, label: t.nama })));

const form = useForm<{
    nama: string; kode: string; jadwal_mulai: string; jadwal_selesai: string;
    lokasi: string; keterangan: string; logo: File | null;
    template_id: number | string; signatory_ids: number[];
}>({
    nama: props.event?.nama ?? '',
    kode: props.event?.kode ?? '',
    jadwal_mulai: props.event?.jadwal_mulai ?? '',
    jadwal_selesai: props.event?.jadwal_selesai ?? '',
    lokasi: props.event?.lokasi ?? '',
    keterangan: props.event?.keterangan ?? '',
    logo: null,
    template_id: props.event?.template_id ?? '',
    signatory_ids: props.event?.signatory_ids ? [...props.event.signatory_ids] : [],
});

const logoPreview = ref<string | null>(props.event?.logo ?? null);
function onLogo(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    form.logo = file;
    logoPreview.value = file ? URL.createObjectURL(file) : (props.event?.logo ?? null);
}

function submit() {
    if (isEdit.value) {
        // Sertakan _method agar multipart (logo) terkirim lewat POST → spoof PUT.
        form.transform((d) => ({ ...d, _method: 'put' })).post(`/events/${props.event!.id}`);
    } else {
        form.post('/events');
    }
}
</script>

<style scoped>
.page { padding: 24px; max-width: 900px; margin: 0 auto; }
.page__header { margin-bottom: 20px; }
.page__title { font-size: 22px; font-weight: 700; color: var(--color-text-primary); }
.page__sub { font-size: 13.5px; color: var(--color-text-muted); margin-top: 2px; }
.page__card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; padding: 24px; }
.ev-form { display: flex; flex-direction: column; gap: 22px; }
.ev-form__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.ev-form__field { display: flex; flex-direction: column; gap: 6px; }
.ev-form__label { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }
.ev-form__hint { font-size: 12px; color: var(--color-text-muted); }
.ev-form__sigs { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; margin-top: 6px; }
.ev-form__sig { display: flex; gap: 10px; align-items: flex-start; padding: 12px; border: 1.5px solid var(--color-border); border-radius: 10px; cursor: pointer; font-size: 13px; }
.ev-form__sig--on { border-color: var(--color-primary); background: var(--color-primary-soft, #eff6ff); }
.ev-form__sig small { color: var(--color-text-muted); }
/* CTA dipertegas saat belum ada penanda tangan */
.ev-form__empty {
    display: flex; align-items: center; gap: 14px;
    margin-top: 8px; padding: 16px 18px;
    border: 1.5px dashed var(--color-primary);
    border-radius: 12px;
    background: var(--color-primary-soft, #eff6ff);
}
.ev-form__empty-icon {
    display: flex; align-items: center; justify-content: center;
    width: 44px; height: 44px; flex-shrink: 0;
    border-radius: 10px;
    background: var(--color-primary); color: #fff;
}
.ev-form__empty-text { display: flex; flex-direction: column; gap: 2px; flex: 1; min-width: 0; }
.ev-form__empty-text strong { font-size: 14px; color: var(--color-text-primary); font-weight: 700; }
.ev-form__empty-text span { font-size: 12.5px; color: var(--color-text-muted); line-height: 1.5; }

@media (max-width: 560px) {
    .ev-form__empty { flex-direction: column; align-items: flex-start; text-align: left; }
}
.ev-form__logo { display: flex; align-items: center; gap: 14px; }
.ev-form__logo-img { height: 48px; border: 1px solid var(--color-border); border-radius: 8px; object-fit: contain; background: #fff; padding: 2px; }
.ev-form__err { font-size: 12px; color: var(--color-danger); }
.ev-form__actions { display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--color-border); padding-top: 18px; }
@media (max-width: 640px) { .ev-form__grid { grid-template-columns: 1fr; } }
</style>
