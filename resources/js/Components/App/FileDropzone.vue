<template>
    <div class="fdz">
        <!-- Drop area -->
        <div
            class="fdz__area"
            :class="{ 'fdz__area--over': isDragging, 'fdz__area--disabled': disabled }"
            @dragover.prevent="isDragging = true"
            @dragleave="isDragging = false"
            @drop.prevent="onDrop"
            @click="!disabled && inputRef?.click()"
        >
            <input
                ref="inputRef"
                type="file"
                class="fdz__input"
                :accept="accept"
                :multiple="multiple"
                :disabled="disabled"
                @change="onFileChange"
            />
            <div class="fdz__icon-wrap">
                <UploadCloudIcon :size="28" class="fdz__icon" />
            </div>
            <p class="fdz__label">
                <span class="fdz__label-link">Click to upload</span> or drag & drop
            </p>
            <p class="fdz__hint">{{ hint }}</p>
        </div>

        <!-- File list -->
        <div v-if="files.length" class="fdz__list">
            <div v-for="(f, i) in files" :key="i" class="fdz__file">
                <div class="fdz__file-icon">
                    <component :is="fileIcon(f.file)" :size="16" />
                </div>
                <div class="fdz__file-info">
                    <span class="fdz__file-name">{{ f.file.name }}</span>
                    <span class="fdz__file-size">{{ fmtSize(f.file.size) }}</span>
                </div>
                <div class="fdz__file-progress-wrap">
                    <div class="fdz__file-progress">
                        <div
                            class="fdz__file-bar"
                            :class="f.error ? 'fdz__file-bar--error' : f.progress === 100 ? 'fdz__file-bar--done' : ''"
                            :style="{ width: `${f.progress}%` }"
                        />
                    </div>
                    <span class="fdz__file-pct" :class="{ 'fdz__file-pct--error': f.error }">
                        {{ f.error ? 'Error' : f.progress === 100 ? 'Done' : `${f.progress}%` }}
                    </span>
                </div>
                <button class="fdz__file-remove" @click.stop="remove(i)">
                    <XIcon :size="13" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { UploadCloudIcon, XIcon, FileTextIcon, ImageIcon, FileIcon } from '@lucide/vue';

interface FileEntry { file: File; progress: number; error: boolean; }

const props = defineProps({
    accept:   { type: String,  default: '*' },
    multiple: { type: Boolean, default: true },
    maxSize:  { type: Number,  default: 10 * 1024 * 1024 },
    disabled: { type: Boolean, default: false },
    hint:     { type: String,  default: 'PNG, JPG, PDF up to 10 MB' },
    simulate: { type: Boolean, default: true },
});

const emit = defineEmits<{
    'files-added': [files: File[]];
    'file-removed': [index: number];
}>();

const inputRef  = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const files      = ref<FileEntry[]>([]);

function onDrop(e: DragEvent) {
    isDragging.value = false;
    const dropped = Array.from(e.dataTransfer?.files ?? []);
    add(dropped);
}

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const picked = Array.from(input.files ?? []);
    add(picked);
    input.value = '';
}

function add(list: File[]) {
    const valid = list.filter(f => f.size <= props.maxSize);
    const entries: FileEntry[] = valid.map(f => ({ file: f, progress: 0, error: false }));
    files.value.push(...entries);
    emit('files-added', valid);
    if (props.simulate) entries.forEach((entry) => simulateProgress(entry));
}

function simulateProgress(entry: FileEntry) {
    const tick = () => {
        if (entry.progress >= 100) return;
        entry.progress = Math.min(100, entry.progress + Math.random() * 20 + 5);
        if (entry.progress < 100) setTimeout(tick, 120 + Math.random() * 80);
    };
    setTimeout(tick, 80);
}

function remove(i: number) {
    files.value.splice(i, 1);
    emit('file-removed', i);
}

function fmtSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

function fileIcon(f: File) {
    if (f.type.startsWith('image/')) return ImageIcon;
    if (f.type === 'application/pdf' || f.name.endsWith('.pdf')) return FileTextIcon;
    return FileIcon;
}
</script>

<style scoped>
.fdz { display: flex; flex-direction: column; gap: 12px; }

.fdz__area {
    border: 2px dashed var(--color-border); border-radius: 12px;
    padding: 32px 24px; display: flex; flex-direction: column; align-items: center;
    gap: 8px; cursor: pointer; text-align: center;
    background: var(--color-bg-subtle);
    transition: all 200ms ease;
}
.fdz__area:hover:not(.fdz__area--disabled) {
    border-color: #6366f1;
    background: color-mix(in srgb, #6366f1 4%, var(--color-bg-subtle));
}
.fdz__area--over {
    border-color: #6366f1; border-style: solid;
    background: color-mix(in srgb, #6366f1 8%, var(--color-bg-subtle));
}
.fdz__area--disabled { opacity: 0.5; cursor: not-allowed; }

.fdz__input { display: none; }

.fdz__icon-wrap {
    width: 52px; height: 52px; border-radius: 12px;
    background: color-mix(in srgb, #6366f1 10%, transparent);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 4px;
}
.fdz__icon { color: #6366f1; }

.fdz__label { font-size: 13.5px; color: var(--color-text-muted); margin: 0; }
.fdz__label-link { color: #6366f1; font-weight: 600; }
.fdz__hint { font-size: 11.5px; color: var(--color-text-subtle); margin: 0; }

.fdz__list { display: flex; flex-direction: column; gap: 8px; }

.fdz__file {
    display: flex; align-items: center; gap: 10px;
    background: var(--color-surface); border: 1.5px solid var(--color-border);
    border-radius: 9px; padding: 10px 12px;
}
.fdz__file-icon {
    width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
    background: var(--color-bg-subtle);
    display: flex; align-items: center; justify-content: center;
    color: var(--color-text-muted);
}
.fdz__file-info {
    display: flex; flex-direction: column; gap: 1px; flex: 0 0 auto; min-width: 0;
}
.fdz__file-name {
    font-size: 12.5px; font-weight: 600; color: var(--color-text-primary);
    max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.fdz__file-size { font-size: 11px; color: var(--color-text-subtle); }

.fdz__file-progress-wrap {
    flex: 1; display: flex; align-items: center; gap: 8px; min-width: 0;
}
.fdz__file-progress {
    flex: 1; height: 5px; background: var(--color-bg-subtle); border-radius: 99px; overflow: hidden;
}
.fdz__file-bar {
    height: 100%; border-radius: 99px; background: #6366f1;
    transition: width 100ms linear;
}
.fdz__file-bar--done  { background: #10b981; }
.fdz__file-bar--error { background: #ef4444; }
.fdz__file-pct { font-size: 11px; font-weight: 600; color: var(--color-text-muted); white-space: nowrap; }
.fdz__file-pct--error { color: #ef4444; }

.fdz__file-remove {
    display: flex; align-items: center; justify-content: center;
    width: 24px; height: 24px; border-radius: 6px; border: none;
    background: transparent; cursor: pointer; color: var(--color-text-subtle);
    flex-shrink: 0; transition: all 120ms ease;
}
.fdz__file-remove:hover { background: rgba(239,68,68,0.1); color: #ef4444; }
</style>
