<!--
    resources/js/Pages/Templates/Editor.vue
    Perancang template sertifikat visual (WYSIWYG). Tiga kolom:
      - Kiri: palette (tambah field bertipe)
      - Tengah: kanvas Konva (latar + element draggable/resizable)
      - Kanan: inspector properti element terpilih
    Layout disimpan sebagai fraksi (0..1) via POST templates/{id}/layout.
    Mesin posisi (elementGeometry) identik dengan node-renderer agar PDF = editor.
-->
<template>
    <BaseLayout :nav-groups="navGroups" title="Perancang Template">
        <div class="ed">
            <!-- Topbar editor -->
            <header class="ed__bar">
                <div class="ed__bar-left">
                    <button class="ed__back" @click="goBack" title="Kembali"><ArrowLeftIcon :size="18" /></button>
                    <div>
                        <h1 class="ed__title">{{ template.nama }}</h1>
                        <p class="ed__sub">Seret field ke posisinya; atur gaya di panel kanan.</p>
                    </div>
                </div>
                <div class="ed__bar-right">
                    <span v-if="dirty" class="ed__dirty">Belum disimpan</span>
                    <div class="ed__grid-ctl">
                        <AppButton :variant="showGrid ? 'primary' : 'outline'" :title="showGrid ? 'Sembunyikan grid' : 'Tampilkan grid'" @click="showGrid = !showGrid">
                            <template #icon><GridIcon :size="15" /></template>
                            Grid
                        </AppButton>
                        <div v-if="showGrid" class="ed__grid-zoom">
                            <button class="ed__grid-btn" title="Perbesar grid (lebih jarang)" :disabled="gridDivisions <= GRID_MIN" @click="changeGrid(-GRID_STEP)">
                                <MinusIcon :size="14" />
                            </button>
                            <span class="ed__grid-val" :title="`Jarak grid ${(100 / gridDivisions).toFixed(1)}%`">{{ gridDivisions }}</span>
                            <button class="ed__grid-btn" title="Perkecil grid (lebih rapat)" :disabled="gridDivisions >= GRID_MAX" @click="changeGrid(GRID_STEP)">
                                <PlusIcon :size="14" />
                            </button>
                        </div>
                    </div>
                    <AppButton variant="outline" :loading="previewing" @click="openPreview">
                        <template #icon><EyeIcon :size="15" /></template>
                        Pratinjau
                    </AppButton>
                    <AppButton variant="primary" :loading="saving" @click="save">
                        <template #icon><SaveIcon :size="15" /></template>
                        Simpan
                    </AppButton>
                </div>
            </header>

            <div class="ed__body">
                <!-- Palette -->
                <aside class="ed__palette">
                    <p class="ed__panel-title">Tambah Field</p>
                    <button v-for="t in paletteTypes" :key="t" class="ed__pal-btn" @click="addElement(t)">
                        <component :is="iconFor(t)" :size="16" />
                        <span>{{ TYPE_META[t].label }}</span>
                    </button>

                    <p class="ed__hint">Field data (nama, tanggal, nomor, QR, TTD, logo) diisi otomatis saat sertifikat diterbitkan. Teks bebas bersifat statis.</p>
                </aside>

                <!-- Kanvas -->
                <main ref="stageWrap" class="ed__canvas-wrap" @mousedown.self="selectedId = null">
                    <div v-if="!template.background" class="ed__no-bg">
                        Template ini belum punya gambar latar.
                    </div>
                    <v-stage
                        v-else
                        ref="stageRef"
                        :config="stageConfig"
                        @mousedown="onStageMouseDown"
                        @touchstart="onStageMouseDown"
                    >
                        <v-layer>
                            <v-image v-if="bgImage" :config="{ image: bgImage, x: 0, y: 0, width: pxW, height: pxH, listening: false }" />

                            <!-- Grid bantu posisi (toggle). Garis tiap 10%; sumbu tengah ditebalkan. -->
                            <template v-if="showGrid">
                                <v-line v-for="line in gridLines" :key="line.key" :config="line.config" />
                            </template>

                            <template v-for="el in layout.elements" :key="el.id">
                                <!-- Element teks -->
                                <v-text
                                    v-if="isTextType(el.type)"
                                    :config="textConfig(el)"
                                    @dragmove="onDragMove(el, $event)"
                                    @transformend="onTransformEnd(el, $event)"
                                    @click="selectedId = el.id"
                                    @tap="selectedId = el.id"
                                />
                                <!-- Logo: preview pakai logo aplikasi bila tersedia -->
                                <v-image
                                    v-else-if="el.type === 'logo' && logoImage"
                                    :config="logoConfig(el)"
                                    @dragmove="onDragMove(el, $event)"
                                    @transformend="onTransformEnd(el, $event)"
                                    @click="selectedId = el.id"
                                    @tap="selectedId = el.id"
                                />
                                <!-- Element gambar lain (placeholder kotak berlabel) -->
                                <v-group
                                    v-else
                                    :config="groupConfig(el)"
                                    @dragmove="onDragMove(el, $event)"
                                    @transformend="onTransformEnd(el, $event)"
                                    @click="selectedId = el.id"
                                    @tap="selectedId = el.id"
                                >
                                    <v-rect :config="placeholderRect(el)" />
                                    <v-text :config="placeholderLabel(el)" />
                                </v-group>
                            </template>

                            <v-transformer ref="transformerRef" :config="transformerConfig" />
                        </v-layer>
                    </v-stage>
                </main>

                <!-- Inspector -->
                <aside class="ed__inspector">
                    <p class="ed__panel-title">Properti</p>
                    <div v-if="!selected" class="ed__empty">Pilih sebuah field untuk mengaturnya.</div>
                    <div v-else class="ed__props">
                        <div class="ed__prop-type">{{ TYPE_META[selected.type].label }}</div>

                        <AppInput
                            v-if="selected.type === 'teks'"
                            v-model="selected.text"
                            label="Teks"
                            @update:modelValue="markDirty"
                        />

                        <!-- Keterangan kegiatan: template kustom + sisip placeholder + bold -->
                        <div v-if="selected.type === 'event'" class="ed__field">
                            <label class="ed__lbl-block">Susunan Keterangan</label>
                            <p class="ed__note">
                                Kosongkan untuk keterangan otomatis. Sisipkan data dengan tombol di bawah, dan bungkus teks dengan <code>**…**</code> untuk menebalkan. Teks panjang otomatis turun ke bawah.
                            </p>
                            <div class="ed__chips">
                                <button v-for="tok in tokenChips" :key="tok.key" type="button" class="ed__chip" @click="insertToken(tok.key)">
                                    {{ tok.label }}
                                </button>
                                <button type="button" class="ed__chip ed__chip--bold" @click="wrapBold">B</button>
                            </div>
                            <AppTextarea :model-value="selected.text ?? ''" :rows="5"
                                placeholder="Kosong = keterangan otomatis…"
                                @update:modelValue="(v: string) => { if (selected) { selected.text = v; markDirty(); } }" />
                        </div>

                        <p v-if="selected.type === 'tanda_tangan'" class="ed__note">
                            Blok ini menampung SEMUA penanda tangan acara secara otomatis (TTD/QR SRIKANDI + nama + jabatan). Atur lebar & posisi blok; gaya di bawah berlaku untuk nama.
                        </p>

                        <template v-if="isStyledType(selected.type)">
                            <AppSelect
                                :model-value="selected.font"
                                :options="fonts"
                                label="Font"
                                searchable
                                @update:modelValue="setFont"
                            />
                            <!-- Pratinjau langsung font terpilih (bentuk huruf + gaya saat ini). -->
                            <div class="ed__font-prev" :style="fontPreviewStyle">
                                <span class="ed__font-prev-label">Pratinjau</span>
                                <span class="ed__font-prev-text">{{ fontPreviewText }}</span>
                            </div>
                            <div class="ed__row">
                                <label class="ed__lbl">Ukuran</label>
                                <input type="number" class="ed__num" min="6" max="400" step="1"
                                    :value="sizePx(selected)"
                                    @input="setSizePx(($event.target as HTMLInputElement).valueAsNumber)" />
                                <span class="ed__unit">px</span>
                                <input type="number" class="ed__num" min="0.5" max="50" step="0.1"
                                    :value="sizePct(selected)"
                                    title="Ukuran sebagai persentase tinggi kanvas"
                                    @input="setSizePct(($event.target as HTMLInputElement).valueAsNumber)" />
                                <span class="ed__unit">%</span>
                            </div>
                            <div class="ed__row">
                                <label class="ed__lbl">Warna</label>
                                <input type="color" :value="selected.color" @input="setColor(($event.target as HTMLInputElement).value)" />
                            </div>
                            <template v-if="isTextType(selected.type)">
                                <div class="ed__row">
                                    <label class="ed__lbl">Perataan</label>
                                    <AppSelect :model-value="selected.align" :options="alignOptions" native @update:modelValue="setAlign" />
                                </div>
                                <AppToggle :model-value="selected.bold ?? false" label="Tebal" @update:modelValue="setBold" />
                            </template>
                        </template>

                        <div class="ed__row">
                            <label class="ed__lbl">Lebar</label>
                            <input type="range" min="2" max="100" step="1"
                                :value="selected.w * 100"
                                @input="setWidth(($event.target as HTMLInputElement).valueAsNumber)" />
                            <input type="number" class="ed__num ed__num--sm" min="2" max="100" step="1"
                                :value="Math.round(selected.w * 100)"
                                title="Lebar sebagai persentase lebar kanvas"
                                @input="setWidth(($event.target as HTMLInputElement).valueAsNumber)" />
                            <span class="ed__unit">%</span>
                        </div>

                        <!-- Posisi sisip manual (X/Y dalam persen) — alternatif drag. -->
                        <div class="ed__row">
                            <label class="ed__lbl">Posisi X</label>
                            <input type="number" class="ed__num ed__num--sm" min="0" max="100" step="0.5"
                                :value="Math.round(selected.x * 1000) / 10"
                                title="Jarak dari kiri (persen lebar kanvas)"
                                @input="setPosX(($event.target as HTMLInputElement).valueAsNumber)" />
                            <span class="ed__unit">%</span>
                            <label class="ed__lbl ed__lbl--y">Posisi Y</label>
                            <input type="number" class="ed__num ed__num--sm" min="0" max="100" step="0.5"
                                :value="Math.round(selected.y * 1000) / 10"
                                title="Jarak dari atas (persen tinggi kanvas)"
                                @input="setPosY(($event.target as HTMLInputElement).valueAsNumber)" />
                            <span class="ed__unit">%</span>
                        </div>

                        <AppButton variant="danger" size="sm" @click="removeSelected">
                            <template #icon><Trash2Icon :size="14" /></template>
                            Hapus Field
                        </AppButton>
                    </div>
                </aside>
            </div>
        </div>

        <AppModal v-model="previewOpen" title="Pratinjau Sertifikat (data contoh)" size="xl">
            <div class="ed__preview">
                <p v-if="previewing" class="ed__preview-loading">Membuat pratinjau…</p>
                <p v-else-if="previewError" class="ed__preview-err">{{ previewError }}</p>
                <img v-else-if="previewSrc" :src="previewSrc" alt="Pratinjau" class="ed__preview-img" />
            </div>
            <template #footer>
                <AppButton variant="ghost" @click="previewOpen = false">Tutup</AppButton>
                <AppButton variant="outline" :loading="previewing" @click="runPreview">Muat Ulang</AppButton>
            </template>
        </AppModal>
    </BaseLayout>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeftIcon, SaveIcon, Trash2Icon, TypeIcon, UserIcon, CalendarIcon,
    HashIcon, QrCodeIcon, PenLineIcon, ImageIcon, FileTextIcon, EyeIcon, UsersIcon, GridIcon,
    PlusIcon, MinusIcon,
} from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppSelect from '@/Components/App/AppSelect.vue';
import AppToggle from '@/Components/App/AppToggle.vue';
import AppModal from '@/Components/App/AppModal.vue';
import { navGroups } from '@/data/navGroups';
import {
    type ElementType, type LayoutElement, type TemplateLayout,
    TYPE_META, elementGeometry, isTextType, isStyledType, makeElement, clamp,
} from './layout';

interface FontOption { value: string; label: string; category: string }
const props = defineProps<{
    template: {
        id: number; nama: string; deskripsi: string | null; background: string | null;
        canvas: { w: number | null; h: number | null };
        layout: TemplateLayout;
    };
    fonts: FontOption[];
}>();

const paletteTypes: ElementType[] = ['nama_peserta', 'event', 'tanggal', 'nomor', 'qr', 'ttd', 'tanda_tangan', 'logo', 'teks'];
const alignOptions = [
    { value: 'left', label: 'Kiri' },
    { value: 'center', label: 'Tengah' },
    { value: 'right', label: 'Kanan' },
];

// --- State layout (fraksi) ---
const layout = ref<TemplateLayout>({
    canvas: { w: props.template.canvas.w ?? 1123, h: props.template.canvas.h ?? 794 },
    elements: (props.template.layout?.elements ?? []).map((e) => ({ ...e })),
});
const selectedId = ref<string | null>(null);
const dirty = ref(false);
const saving = ref(false);
// Grid bantu posisi — preferensi disimpan agar konsisten antar sesi.
const showGrid = ref(localStorage.getItem('tpl_editor_grid') !== '0');
watch(showGrid, (v) => localStorage.setItem('tpl_editor_grid', v ? '1' : '0'));

// Kerapatan grid = jumlah pembagian (semakin besar → garis makin rapat = grid mengecil).
const GRID_MIN = 4;
const GRID_MAX = 40;
const GRID_STEP = 2;
const gridDivisions = ref(Math.min(GRID_MAX, Math.max(GRID_MIN, Number(localStorage.getItem('tpl_editor_grid_div')) || 10)));
watch(gridDivisions, (v) => localStorage.setItem('tpl_editor_grid_div', String(v)));
function changeGrid(delta: number) {
    gridDivisions.value = Math.min(GRID_MAX, Math.max(GRID_MIN, gridDivisions.value + delta));
}

const selected = computed(() => layout.value.elements.find((e) => e.id === selectedId.value) ?? null);

// --- Ukuran kanvas piksel (di-fit ke lebar wrapper, jaga rasio) ---
const stageWrap = ref<HTMLElement | null>(null);
const stageRef = ref<any>(null);
const transformerRef = ref<any>(null);
const bgImage = ref<HTMLImageElement | null>(null);
// Logo aplikasi (shared Inertia) → dipakai sebagai PREVIEW element 'logo' di editor.
const appLogoUrl = (usePage().props.app as { logo?: string | null } | undefined)?.logo ?? null;
const logoImage = ref<HTMLImageElement | null>(null);
const natW = computed(() => layout.value.canvas.w || 1123);
const natH = computed(() => layout.value.canvas.h || 794);
const fitW = ref(900);
const pxW = computed(() => fitW.value);
const pxH = computed(() => (fitW.value * natH.value) / natW.value);

const stageConfig = computed(() => ({ width: pxW.value, height: pxH.value }));
const transformerConfig = { rotateEnabled: false, enabledAnchors: ['middle-left', 'middle-right'], borderStroke: '#1a56db', anchorStroke: '#1a56db' };

// Garis grid bantu posisi: dibagi `gridDivisions` bagian (kerapatan dapat diatur).
// Sumbu tengah (bila ada pembagian genap) ditebalkan & merah untuk cek centering.
// listening:false → tak mengganggu drag/seleksi element.
const gridLines = computed(() => {
    const lines: { key: string; config: Record<string, unknown> }[] = [];
    const w = pxW.value;
    const h = pxH.value;
    const n = gridDivisions.value;
    const mid = n % 2 === 0 ? n / 2 : -1; // indeks garis tengah (hanya bila genap)
    for (let i = 1; i < n; i++) {
        const center = i === mid;
        const stroke = center ? 'rgba(220,38,38,0.55)' : 'rgba(26,86,219,0.22)';
        const strokeWidth = center ? 1.2 : 0.6;
        const x = (w * i) / n;
        const y = (h * i) / n;
        lines.push({ key: `v${i}`, config: { points: [x, 0, x, h], stroke, strokeWidth, listening: false } });
        lines.push({ key: `h${i}`, config: { points: [0, y, w, y], stroke, strokeWidth, listening: false } });
    }
    return lines;
});

function fitToWrap() {
    const wrap = stageWrap.value;
    if (!wrap) return;
    const avail = wrap.clientWidth - 32;
    fitW.value = Math.max(280, Math.min(avail, 1100));
}

// Suntik @font-face untuk font terkurasi → canvas Konva memakai font yang sama
// dengan renderer Node (WYSIWYG). Berkas .ttf disajikan via /fonts/{family}.
function injectFontFaces() {
    const css = props.fonts
        .map((f) => {
            const fam = encodeURIComponent(f.value);
            return `@font-face{font-family:'${f.value}';font-weight:normal;src:url('/fonts/${fam}/regular') format('truetype');}`
                + `@font-face{font-family:'${f.value}';font-weight:bold;src:url('/fonts/${fam}/bold') format('truetype');}`;
        })
        .join('');
    const style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);

    // Muat font lalu redraw kanvas agar metrik benar.
    if ((document as any).fonts?.ready) {
        Promise.all(
            props.fonts.map((f) => (document as any).fonts.load(`16px '${f.value}'`).catch(() => {})),
        ).then(() => stageRef.value?.getStage()?.batchDraw());
    }
}

onMounted(() => {
    fitToWrap();
    window.addEventListener('resize', fitToWrap);
    injectFontFaces();
    if (props.template.background) {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            bgImage.value = img;
            // Bila dimensi natural belum tersimpan, ambil dari gambar.
            if (!props.template.canvas.w) {
                layout.value.canvas = { w: img.naturalWidth, h: img.naturalHeight };
            }
        };
        img.src = props.template.background;
    }
    // Muat logo aplikasi untuk preview element 'logo'.
    if (appLogoUrl) {
        const logo = new Image();
        logo.crossOrigin = 'anonymous';
        logo.onload = () => { logoImage.value = logo; stageRef.value?.getStage()?.batchDraw(); };
        logo.src = appLogoUrl;
    }
});

// Teks contoh untuk pratinjau kanvas (token → nilai contoh, buang markup **).
const SAMPLE_TOKENS: Record<string, string> = {
    acara: 'Workshop Transformasi Digital',
    tanggal: '12 – 14 Februari 2026',
    durasi: '18 jam',
    lokasi: 'Aula Utama',
    instansi: 'Dinas Kominfo',
};
const SAMPLE_KETERANGAN = 'telah menyelesaikan kegiatan Workshop Transformasi Digital di Aula Utama pada tanggal 12 – 14 Februari 2026 dengan jumlah waktu 18 jam yang diadakan oleh Dinas Kominfo.';

function textForCanvas(el: LayoutElement): string {
    if (el.type === 'teks') return el.text || TYPE_META[el.type].placeholder;
    if (el.type === 'event') {
        const tpl = (el.text ?? '').trim();
        if (!tpl) return SAMPLE_KETERANGAN; // kosong → keterangan otomatis (contoh)
        return tpl
            .replace(/\{(\w+)\}/g, (m, k) => SAMPLE_TOKENS[k] ?? m)
            .replace(/\*\*/g, '');
    }
    return TYPE_META[el.type].placeholder;
}

// --- Konva config builders (fraksi → piksel) ---
function textConfig(el: LayoutElement) {
    const g = elementGeometry(el, pxW.value, pxH.value);
    return {
        id: el.id,
        x: g.x,
        y: g.y,
        width: g.width,
        text: textForCanvas(el),
        fontSize: g.fontSize,
        fontFamily: g.font,
        fontStyle: g.bold ? 'bold' : 'normal',
        fill: g.color,
        align: g.align,
        wrap: 'word',
        draggable: true,
    };
}

function groupConfig(el: LayoutElement) {
    const g = elementGeometry(el, pxW.value, pxH.value);
    return { id: el.id, x: g.x, y: g.y, draggable: true };
}
// Logo aplikasi sebagai preview: lebar = g.width, tinggi jaga rasio asli (spt renderer).
function logoConfig(el: LayoutElement) {
    const g = elementGeometry(el, pxW.value, pxH.value);
    const img = logoImage.value!;
    const ratio = (img.naturalHeight / img.naturalWidth) || 1;
    return { id: el.id, x: g.x, y: g.y, image: img, width: g.width, height: g.width * ratio, draggable: true };
}
function placeholderHeight(el: LayoutElement, g: ReturnType<typeof elementGeometry>) {
    // Blok tanda tangan = kotak lebar & pendek; gambar lain = bujur sangkar.
    return el.type === 'tanda_tangan' ? g.width * 0.28 : g.width;
}
function placeholderRect(el: LayoutElement) {
    const g = elementGeometry(el, pxW.value, pxH.value);
    return { x: 0, y: 0, width: g.width, height: placeholderHeight(el, g), fill: 'rgba(26,86,219,0.08)', stroke: '#1a56db', strokeWidth: 1, dash: [6, 4], cornerRadius: 6 };
}
function placeholderLabel(el: LayoutElement) {
    const g = elementGeometry(el, pxW.value, pxH.value);
    return { x: 0, y: placeholderHeight(el, g) / 2 - 8, width: g.width, text: TYPE_META[el.type].placeholder, fontSize: 13, fontStyle: 'bold', fill: '#1a56db', align: 'center' };
}

// --- Interaksi ---
function onStageMouseDown(e: any) {
    // Klik area kosong (stage itu sendiri) → batalkan seleksi.
    if (e.target === e.target.getStage()) selectedId.value = null;
}

function onDragMove(el: LayoutElement, e: any) {
    const node = e.target;
    el.x = clamp(node.x() / pxW.value, 0, 1);
    el.y = clamp(node.y() / pxH.value, 0, 1);
    markDirty();
}

function onTransformEnd(el: LayoutElement, e: any) {
    const node = e.target;
    const scaleX = node.scaleX();
    const newWidthPx = Math.max(8, (node.width() || elementGeometry(el, pxW.value, pxH.value).width) * scaleX);
    el.w = clamp(newWidthPx / pxW.value, 0.02, 1);
    node.scaleX(1);
    node.scaleY(1);
    el.x = clamp(node.x() / pxW.value, 0, 1);
    el.y = clamp(node.y() / pxH.value, 0, 1);
    markDirty();
}

// Pasang transformer ke node terpilih.
watch(selectedId, async () => {
    await nextTick();
    const tr = transformerRef.value?.getNode();
    const stage = stageRef.value?.getStage();
    if (!tr || !stage) return;
    if (!selectedId.value) { tr.nodes([]); tr.getLayer()?.batchDraw(); return; }
    const node = stage.findOne('#' + selectedId.value);
    tr.nodes(node ? [node] : []);
    tr.getLayer()?.batchDraw();
});

// --- Mutasi properti ---
function markDirty() { dirty.value = true; }
function addElement(type: ElementType) {
    const el = makeElement(type);
    layout.value.elements.push(el);
    selectedId.value = el.id;
    markDirty();
}
function removeSelected() {
    if (!selected.value) return;
    layout.value.elements = layout.value.elements.filter((e) => e.id !== selectedId.value);
    selectedId.value = null;
    markDirty();
}
function setFont(v: string) { if (selected.value) { selected.value.font = v; markDirty(); } }
// Ukuran teks ditampilkan & diinput dalam PX (relatif tinggi kanvas natural),
// tapi DISIMPAN sebagai fraksi tinggi agar WYSIWYG di resolusi apa pun.
function sizePx(el: LayoutElement): number {
    return Math.round((el.size ?? 0.04) * natH.value);
}
function setSizePx(px: number) {
    if (!selected.value || !Number.isFinite(px)) return;
    const clamped = Math.max(6, Math.min(400, px));
    selected.value.size = clamped / natH.value;
    markDirty();
}
// Ukuran sebagai persen tinggi kanvas (input alternatif, identik makna dgn fraksi).
function sizePct(el: LayoutElement): number {
    return Math.round((el.size ?? 0.04) * 1000) / 10;
}
function setSizePct(pct: number) {
    if (!selected.value || !Number.isFinite(pct)) return;
    selected.value.size = clamp(pct / 100, 0.005, 0.5);
    markDirty();
}
function setColor(v: string) { if (selected.value) { selected.value.color = v; markDirty(); } }
function setAlign(v: string) { if (selected.value) { selected.value.align = v as LayoutElement['align']; markDirty(); } }
function setBold(v: boolean) { if (selected.value) { selected.value.bold = v; markDirty(); } }
function setWidth(pct: number) { if (selected.value && Number.isFinite(pct)) { selected.value.w = clamp(pct / 100, 0.02, 1); markDirty(); } }
// Sisip posisi manual (X/Y) — alternatif drag, dalam persen kanvas.
function setPosX(pct: number) { if (selected.value && Number.isFinite(pct)) { selected.value.x = clamp(pct / 100, 0, 1); markDirty(); } }
function setPosY(pct: number) { if (selected.value && Number.isFinite(pct)) { selected.value.y = clamp(pct / 100, 0, 1); markDirty(); } }

// --- Pratinjau font di inspector (bentuk huruf + gaya element terpilih) ---
const fontPreviewStyle = computed(() => {
    const el = selected.value;
    if (!el) return {};
    const g = elementGeometry(el, pxW.value, pxH.value);
    return {
        fontFamily: `'${g.font}', sans-serif`,
        fontWeight: g.bold ? 700 : 400,
        color: g.color,
    } as Record<string, string | number>;
});
const fontPreviewText = computed(() => {
    const el = selected.value;
    if (!el) return '';
    // Untuk keterangan/teks panjang, cuplik agar swatch tetap ringkas.
    const raw = textForCanvas(el).replace(/\s+/g, ' ').trim();
    return raw.length > 48 ? raw.slice(0, 48) + '…' : (raw || 'AaBbCc 123');
});

// --- Keterangan kustom (element 'event'): sisip placeholder & bold ---
const tokenChips = [
    { key: 'acara', label: '+ Nama Acara' },
    { key: 'tanggal', label: '+ Tanggal' },
    { key: 'durasi', label: '+ Durasi' },
    { key: 'lokasi', label: '+ Lokasi' },
    { key: 'instansi', label: '+ Instansi' },
];
function appendToText(snippet: string) {
    if (!selected.value) return;
    const cur = selected.value.text ?? '';
    selected.value.text = cur ? `${cur} ${snippet}` : snippet;
    markDirty();
}
function insertToken(key: string) { appendToText(`{${key}}`); }
function wrapBold() { appendToText('**teks tebal**'); }

// --- Simpan ---
function save() {
    saving.value = true;
    const payload = {
        layout: {
            canvas: { w: Math.round(natW.value), h: Math.round(natH.value) },
            elements: layout.value.elements,
        },
    };
    useForm(payload).post(`/templates/${props.template.id}/layout`, {
        preserveScroll: true,
        onSuccess: () => { dirty.value = false; },
        onFinish: () => { saving.value = false; },
    });
}

function goBack() { router.visit('/templates'); }

// --- Pratinjau (data dummy) ---
const previewOpen = ref(false);
const previewing = ref(false);
const previewSrc = ref<string | null>(null);
const previewError = ref<string | null>(null);

function openPreview() {
    previewOpen.value = true;
    runPreview();
}

async function runPreview() {
    previewing.value = true;
    previewError.value = null;
    if (previewSrc.value) { URL.revokeObjectURL(previewSrc.value); previewSrc.value = null; }
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const res = await fetch(`/templates/${props.template.id}/preview`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, Accept: 'image/png' },
            body: JSON.stringify({
                layout: {
                    canvas: { w: Math.round(natW.value), h: Math.round(natH.value) },
                    elements: layout.value.elements,
                },
            }),
        });
        if (!res.ok) {
            previewError.value = await res.text().catch(() => 'Gagal membuat pratinjau.');
            return;
        }
        const blob = await res.blob();
        previewSrc.value = URL.createObjectURL(blob);
    } catch {
        previewError.value = 'Tidak dapat menghubungi server pratinjau.';
    } finally {
        previewing.value = false;
    }
}

// --- Ikon palette ---
function iconFor(t: ElementType) {
    return {
        nama_peserta: UserIcon, event: FileTextIcon, tanggal: CalendarIcon, nomor: HashIcon,
        qr: QrCodeIcon, ttd: PenLineIcon, tanda_tangan: UsersIcon, logo: ImageIcon, teks: TypeIcon,
    }[t];
}

// Label kategori ramah untuk badge di dropdown font.
const CATEGORY_LABEL: Record<string, string> = {
    sans: 'Sans', serif: 'Serif', display: 'Dekoratif', mono: 'Mono',
};
// Opsi font dengan PREVIEW: setiap label dirender memakai font-nya sendiri
// (style.fontFamily) + badge kategori → user tahu bentuk huruf sebelum memilih.
const fonts = computed(() => props.fonts.map((f) => ({
    value: f.value,
    label: f.label,
    style: { fontFamily: `'${f.value}', sans-serif` },
    badge: CATEGORY_LABEL[f.category] ?? f.category,
})));
</script>

<style scoped>
.ed { display: flex; flex-direction: column; height: calc(100vh - var(--topbar-height, 64px)); }
.ed__bar { display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; border-bottom: 1px solid var(--color-border); background: var(--color-surface); }
.ed__bar-left { display: flex; align-items: center; gap: 12px; }
.ed__back { display: inline-flex; padding: 8px; border-radius: 8px; border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text-muted); cursor: pointer; }
.ed__back:hover { color: var(--color-text-primary); }
.ed__title { font-size: 16px; font-weight: 700; color: var(--color-text-primary); }
.ed__sub { font-size: 12px; color: var(--color-text-muted); }
.ed__bar-right { display: flex; align-items: center; gap: 12px; }
.ed__dirty { font-size: 12px; color: var(--color-warning); font-weight: 600; }
.ed__grid-ctl { display: flex; align-items: center; gap: 6px; }
.ed__grid-zoom { display: flex; align-items: center; gap: 4px; padding: 2px; border: 1px solid var(--color-border); border-radius: 8px; }
.ed__grid-btn { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border: none; background: transparent; color: var(--color-text-muted); cursor: pointer; border-radius: 6px; }
.ed__grid-btn:hover:not(:disabled) { background: var(--color-bg-subtle); color: var(--color-text-primary); }
.ed__grid-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.ed__grid-val { min-width: 22px; text-align: center; font-size: 12.5px; font-weight: 600; color: var(--color-text-primary); font-variant-numeric: tabular-nums; }

.ed__body { display: grid; grid-template-columns: 220px 1fr 280px; flex: 1; min-height: 0; }
.ed__palette, .ed__inspector { padding: 16px; overflow-y: auto; background: var(--color-surface); }
.ed__palette { border-right: 1px solid var(--color-border); }
.ed__inspector { border-left: 1px solid var(--color-border); }
.ed__panel-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--color-text-subtle); margin-bottom: 12px; }
.ed__pal-btn { display: flex; align-items: center; gap: 10px; width: 100%; padding: 9px 11px; margin-bottom: 6px; border-radius: 9px; border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text-primary); font-size: 13px; cursor: pointer; text-align: left; }
.ed__pal-btn:hover { border-color: var(--color-primary); color: var(--color-primary); }
.ed__hint { margin-top: 14px; font-size: 11.5px; line-height: 1.5; color: var(--color-text-muted); }

.ed__canvas-wrap { display: flex; align-items: flex-start; justify-content: center; padding: 24px; overflow: auto; background: var(--color-bg-subtle); }
.ed__no-bg { margin-top: 40px; color: var(--color-text-muted); font-size: 14px; }

.ed__empty { font-size: 13px; color: var(--color-text-muted); }
.ed__props { display: flex; flex-direction: column; gap: 14px; }
.ed__prop-type { font-size: 13px; font-weight: 700; color: var(--color-primary); }
.ed__row { display: flex; align-items: center; gap: 10px; }
.ed__lbl { font-size: 12.5px; color: var(--color-text-muted); min-width: 62px; }
.ed__val { font-size: 12px; color: var(--color-text-primary); min-width: 36px; text-align: right; }
.ed__row input[type=range] { flex: 1; accent-color: var(--color-primary); }
.ed__row input[type=color] { width: 40px; height: 28px; border: 1px solid var(--color-border); border-radius: 6px; background: none; cursor: pointer; }
.ed__num { width: 72px; padding: 5px 8px; border: 1px solid var(--color-border); border-radius: 7px; font-size: 13px; color: var(--color-text-primary); background: var(--color-surface); }
.ed__num--sm { width: 58px; }
.ed__num:focus { outline: none; border-color: var(--color-primary); }
.ed__unit { font-size: 12px; color: var(--color-text-muted); }
.ed__lbl--y { min-width: unset; margin-left: 4px; }

/* Pratinjau font terpilih: latar bersih, teks dirender memakai font/gaya element. */
.ed__font-prev {
    display: flex; flex-direction: column; gap: 3px;
    padding: 10px 12px; border: 1px solid var(--color-border); border-radius: 9px;
    background: var(--color-bg-subtle); overflow: hidden;
}
.ed__font-prev-label { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--color-text-subtle); }
.ed__font-prev-text { font-size: 22px; line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ed__note { font-size: 11.5px; line-height: 1.5; color: var(--color-text-muted); background: var(--color-bg-subtle); padding: 8px 10px; border-radius: 8px; }
.ed__note code { background: var(--color-surface); padding: 0 4px; border-radius: 4px; font-size: 11px; }
.ed__field { display: flex; flex-direction: column; gap: 8px; }
.ed__lbl-block { font-size: 12.5px; font-weight: 600; color: var(--color-text-primary); }
.ed__chips { display: flex; flex-wrap: wrap; gap: 5px; }
.ed__chip { font-size: 11px; padding: 3px 8px; border: 1px solid var(--color-border); border-radius: 999px; background: var(--color-surface); color: var(--color-primary); cursor: pointer; }
.ed__chip:hover { background: var(--color-bg-subtle); }
.ed__chip--bold { font-weight: 800; color: var(--color-text-primary); min-width: 26px; }
.ed__preview { display: flex; align-items: center; justify-content: center; min-height: 200px; background: var(--color-bg-subtle); border-radius: 10px; }
.ed__preview-img { max-width: 100%; max-height: 70vh; border: 1px solid var(--color-border); border-radius: 6px; }
.ed__preview-loading { color: var(--color-text-muted); font-size: 14px; }
.ed__preview-err { color: var(--color-danger); font-size: 13px; padding: 16px; text-align: center; }
</style>
