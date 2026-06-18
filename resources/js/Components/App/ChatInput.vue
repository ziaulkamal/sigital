<template>
    <div class="ci">
        <!-- Attach -->
        <button class="ci__action" title="Attach file" @click="fileRef?.click()">
            <PaperclipIcon :size="17" />
        </button>
        <input ref="fileRef" type="file" style="display:none" @change="onAttach" />

        <!-- Emoji (stub) -->
        <button class="ci__action" title="Emoji" @click="showEmoji = !showEmoji">
            <SmileIcon :size="17" />
        </button>

        <!-- Input -->
        <div class="ci__input-wrap">
            <textarea
                ref="textareaRef"
                v-model="text"
                class="ci__input"
                :placeholder="placeholder"
                rows="1"
                @keydown.enter.exact.prevent="send"
                @input="autoResize"
            />
        </div>

        <!-- Send -->
        <button
            class="ci__send"
            :class="{ 'ci__send--active': text.trim() }"
            :disabled="!text.trim()"
            @click="send"
        >
            <SendIcon :size="16" />
        </button>
    </div>
</template>

<script setup lang="ts">
import { ref, nextTick } from 'vue';
import { PaperclipIcon, SmileIcon, SendIcon } from '@lucide/vue';

const props = defineProps({
    placeholder: { type: String, default: 'Type a message…' },
});

const emit = defineEmits<{
    send: [message: string];
    attach: [file: File];
}>();

const text        = ref('');
const fileRef     = ref<HTMLInputElement | null>(null);
const textareaRef = ref<HTMLTextAreaElement | null>(null);
const showEmoji   = ref(false);

function send() {
    if (!text.value.trim()) return;
    emit('send', text.value.trim());
    text.value = '';
    nextTick(() => autoResize());
}

function autoResize() {
    const el = textareaRef.value;
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function onAttach(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) emit('attach', file);
}
</script>

<style scoped>
.ci {
    display: flex; align-items: flex-end; gap: 6px;
    padding: 10px 14px;
    border-top: 1px solid var(--color-border);
    background: var(--color-surface);
}
.ci__action {
    display: flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 9px; border: none;
    background: transparent; color: var(--color-text-subtle); cursor: pointer;
    flex-shrink: 0; transition: all 120ms ease;
}
.ci__action:hover { background: var(--color-bg-subtle); color: var(--color-text-primary); }

.ci__input-wrap {
    flex: 1; background: var(--color-bg-subtle); border: 1.5px solid var(--color-border);
    border-radius: 10px; padding: 7px 12px;
    transition: border-color 150ms ease;
}
.ci__input-wrap:focus-within { border-color: color-mix(in srgb, #6366f1 40%, var(--color-border)); }
.ci__input {
    width: 100%; border: none; outline: none; background: transparent; resize: none;
    font-size: 13.5px; color: var(--color-text-primary); font-family: var(--font-sans);
    line-height: 1.5; overflow-y: hidden; max-height: 120px;
}
.ci__input::placeholder { color: var(--color-text-subtle); }

.ci__send {
    display: flex; align-items: center; justify-content: center;
    width: 36px; height: 36px; border-radius: 10px; border: none;
    background: var(--color-border); color: var(--color-text-subtle);
    cursor: not-allowed; flex-shrink: 0; transition: all 150ms ease;
}
.ci__send--active {
    background: #6366f1; color: #fff; cursor: pointer;
}
.ci__send--active:hover { background: #4f46e5; }
</style>
