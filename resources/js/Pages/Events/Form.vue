<!--
    resources/js/Pages/Events/Form.vue
    Form buat/ubah acara: jadwal, lokasi, template, dan penetapan penanda tangan.
-->
<template>
    <BaseLayout :nav-groups="navGroups">
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
                    <label class="ev-form__label">Penanda Tangan</label>
                    <p class="ev-form__hint">Pilih satu atau lebih. Urutan mengikuti urutan pemilihan.</p>
                    <div class="ev-form__sigs">
                        <label v-for="s in signatoryOptions" :key="s.id" class="ev-form__sig"
                            :class="{ 'ev-form__sig--on': form.signatory_ids.includes(s.id) }">
                            <input type="checkbox" :value="s.id" v-model="form.signatory_ids" />
                            <span><strong>{{ s.nama }}</strong><br><small>{{ s.jabatan }}</small></span>
                        </label>
                        <p v-if="!signatoryOptions.length" class="ev-form__empty">
                            Belum ada penanda tangan. <Link href="/signatories">Tambah dulu →</Link>
                        </p>
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
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppSelect from '@/Components/App/AppSelect.vue';
import { navGroups } from '@/data/navGroups';

interface Opt { id: number; nama: string; jabatan?: string; }
interface EventData { id: number; nama: string; kode: string | null; jadwal_mulai: string | null; jadwal_selesai: string | null; lokasi: string | null; status: string; template_id: number | null; signatory_ids: number[]; }

const props = defineProps<{
    templateOptions: Opt[];
    signatoryOptions: Opt[];
    event?: EventData;
}>();

const isEdit = computed(() => !!props.event);
const templateOpts = computed(() => props.templateOptions.map((t) => ({ value: t.id, label: t.nama })));

const form = useForm({
    nama: props.event?.nama ?? '',
    kode: props.event?.kode ?? '',
    jadwal_mulai: props.event?.jadwal_mulai ?? '',
    jadwal_selesai: props.event?.jadwal_selesai ?? '',
    lokasi: props.event?.lokasi ?? '',
    template_id: props.event?.template_id ?? '',
    signatory_ids: props.event?.signatory_ids ? [...props.event.signatory_ids] : [],
});

function submit() {
    if (isEdit.value) {
        form.put(`/events/${props.event!.id}`);
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
.ev-form__empty { font-size: 13px; color: var(--color-text-muted); }
.ev-form__actions { display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--color-border); padding-top: 18px; }
@media (max-width: 640px) { .ev-form__grid { grid-template-columns: 1fr; } }
</style>
