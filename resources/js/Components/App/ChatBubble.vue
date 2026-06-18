<template>
    <div class="cb" :class="sent ? 'cb--sent' : 'cb--received'">
        <AppAvatar v-if="!sent && showAvatar" :name="author" size="xs" class="cb__avatar" />
        <div v-else-if="!sent" class="cb__avatar-spacer" />

        <div class="cb__group">
            <span v-if="!sent && showAvatar" class="cb__author">{{ author }}</span>
            <div class="cb__bubble">
                <!-- Text content -->
                <span v-if="type === 'text'" class="cb__text">{{ content }}</span>

                <!-- Image -->
                <img v-else-if="type === 'image'" :src="content" class="cb__image" alt="image" />

                <!-- File attachment -->
                <div v-else-if="type === 'file'" class="cb__file">
                    <FileIcon :size="18" />
                    <div class="cb__file-info">
                        <span class="cb__file-name">{{ content }}</span>
                        <span class="cb__file-meta">{{ fileMeta }}</span>
                    </div>
                    <button class="cb__file-dl"><DownloadIcon :size="14" /></button>
                </div>
            </div>
            <div class="cb__meta">
                <span class="cb__time">{{ time }}</span>
                <span v-if="sent" class="cb__status">
                    <CheckCheckIcon v-if="status === 'read'"    :size="12" class="cb__read" />
                    <CheckCheckIcon v-else-if="status === 'delivered'" :size="12" />
                    <CheckIcon v-else :size="12" />
                </span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { FileIcon, DownloadIcon, CheckIcon, CheckCheckIcon } from '@lucide/vue';
import AppAvatar from '@/Components/App/AppAvatar.vue';

defineProps({
    content:    { type: String,  default: '' },
    sent:       { type: Boolean, default: false },
    author:     { type: String,  default: '' },
    time:       { type: String,  default: '' },
    showAvatar: { type: Boolean, default: true },
    type:       { type: String as () => 'text' | 'image' | 'file', default: 'text' },
    fileMeta:   { type: String,  default: '' },
    status:     { type: String as () => 'sent' | 'delivered' | 'read', default: 'sent' },
});
</script>

<style scoped>
.cb { display: flex; align-items: flex-end; gap: 8px; padding: 2px 0; }
.cb--sent     { flex-direction: row-reverse; }
.cb--received { flex-direction: row; }

.cb__avatar { flex-shrink: 0; }
.cb__avatar-spacer { width: 24px; flex-shrink: 0; }

.cb__group { display: flex; flex-direction: column; gap: 2px; max-width: 65%; }
.cb--sent .cb__group { align-items: flex-end; }
.cb--received .cb__group { align-items: flex-start; }

.cb__author { font-size: 11px; font-weight: 600; color: var(--color-text-muted); margin-bottom: 2px; }

.cb__bubble {
    border-radius: 14px; padding: 9px 13px;
    line-height: 1.5;
}
.cb--sent .cb__bubble {
    background: #6366f1; color: #fff;
    border-bottom-right-radius: 4px;
}
.cb--received .cb__bubble {
    background: var(--color-bg-subtle);
    border: 1px solid var(--color-border);
    border-bottom-left-radius: 4px;
}

.cb__text { font-size: 13px; word-break: break-word; }
.cb--sent .cb__text { color: #fff; }
.cb--received .cb__text { color: var(--color-text-primary); }

.cb__image { max-width: 200px; border-radius: 8px; display: block; }

.cb__file {
    display: flex; align-items: center; gap: 10px; min-width: 160px;
}
.cb--sent .cb__file { color: rgba(255,255,255,0.9); }
.cb--received .cb__file { color: var(--color-text-muted); }
.cb__file-info { flex: 1; display: flex; flex-direction: column; gap: 1px; }
.cb__file-name { font-size: 12px; font-weight: 600; }
.cb__file-meta { font-size: 10.5px; opacity: 0.7; }
.cb__file-dl {
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 7px; border: none;
    background: rgba(255,255,255,0.15); cursor: pointer; color: inherit;
    transition: background 120ms ease;
}
.cb__file-dl:hover { background: rgba(255,255,255,0.25); }

.cb__meta { display: flex; align-items: center; gap: 4px; padding: 0 2px; }
.cb__time  { font-size: 10.5px; color: var(--color-text-subtle); }
.cb__status { display: flex; align-items: center; color: var(--color-text-subtle); }
.cb__read   { color: #6366f1; }
</style>
