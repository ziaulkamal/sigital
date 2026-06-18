<template>
    <Teleport to="body">
        <Transition name="mc-pop">
            <div v-if="modelValue" class="mc">
                <!-- Header -->
                <div class="mc__head">
                    <span class="mc__title">New Message</span>
                    <div class="mc__head-actions">
                        <button class="mc__head-btn" @click="minimized = !minimized" :title="minimized ? 'Expand' : 'Minimize'">
                            <MinusIcon :size="14" />
                        </button>
                        <button class="mc__head-btn mc__head-btn--close" @click="emit('update:modelValue', false)">
                            <XIcon :size="14" />
                        </button>
                    </div>
                </div>

                <!-- Body (hidden when minimized) -->
                <div v-show="!minimized" class="mc__body">
                    <!-- Fields -->
                    <div class="mc__field">
                        <label class="mc__label">To</label>
                        <input v-model="form.to"      class="mc__input" type="text" placeholder="recipient@example.com" />
                    </div>
                    <div class="mc__field mc__field--border">
                        <label class="mc__label">Cc</label>
                        <input v-model="form.cc"      class="mc__input" type="text" placeholder="optional" />
                    </div>
                    <div class="mc__field mc__field--border">
                        <label class="mc__label">Subject</label>
                        <input v-model="form.subject" class="mc__input" type="text" placeholder="Subject line…" />
                    </div>

                    <!-- Toolbar -->
                    <div class="mc__toolbar">
                        <button v-for="tool in formatTools" :key="tool.label" class="mc__tool" :title="tool.label" @click="tool.action">
                            <component :is="tool.icon" :size="14" />
                        </button>
                        <div class="mc__tool-sep" />
                        <button class="mc__tool" title="Attach file" @click="attachRef?.click()">
                            <PaperclipIcon :size="14" />
                        </button>
                        <input ref="attachRef" type="file" multiple style="display:none" @change="onAttach" />
                        <button class="mc__tool" title="Insert link">
                            <LinkIcon :size="14" />
                        </button>
                    </div>

                    <!-- Body textarea -->
                    <textarea
                        v-model="form.body"
                        class="mc__textarea"
                        placeholder="Write your message…"
                        rows="8"
                    />

                    <!-- Attachments -->
                    <div v-if="attachments.length" class="mc__attachments">
                        <div v-for="(a, i) in attachments" :key="i" class="mc__attach-chip">
                            <PaperclipIcon :size="11" />
                            {{ a.name }}
                            <button class="mc__attach-rm" @click="attachments.splice(i, 1)"><XIcon :size="10" /></button>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mc__footer">
                        <button class="mc__send" :disabled="!canSend" @click="send">
                            <SendIcon :size="14" />
                            Send
                        </button>
                        <button class="mc__discard" @click="emit('update:modelValue', false)">
                            <TrashIcon :size="14" />
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import {
    XIcon, MinusIcon, BoldIcon, ItalicIcon, UnderlineIcon,
    ListIcon, PaperclipIcon, LinkIcon, SendIcon, TrashIcon,
} from '@lucide/vue';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
});

const emit = defineEmits<{
    'update:modelValue': [v: boolean];
    'send': [form: { to: string; cc: string; subject: string; body: string }];
}>();

const minimized = ref(false);
const attachRef = ref<HTMLInputElement | null>(null);
const attachments = ref<File[]>([]);

const form = ref({ to: '', cc: '', subject: '', body: '' });

const canSend = computed(() => form.value.to.trim() && form.value.body.trim());

function send() {
    if (!canSend.value) return;
    emit('send', { ...form.value });
    emit('update:modelValue', false);
    form.value = { to: '', cc: '', subject: '', body: '' };
    attachments.value = [];
}

function onAttach(e: Event) {
    const files = Array.from((e.target as HTMLInputElement).files ?? []);
    attachments.value.push(...files);
}

const formatTools = [
    { label: 'Bold',      icon: BoldIcon,      action: () => {} },
    { label: 'Italic',    icon: ItalicIcon,    action: () => {} },
    { label: 'Underline', icon: UnderlineIcon, action: () => {} },
    { label: 'List',      icon: ListIcon,      action: () => {} },
];
</script>

<style scoped>
.mc {
    position: fixed; bottom: 0; right: 24px; z-index: 600;
    width: 480px; border-radius: 12px 12px 0 0;
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    box-shadow: 0 -4px 32px rgba(0,0,0,0.14);
    display: flex; flex-direction: column; overflow: hidden;
}

.mc-pop-enter-active, .mc-pop-leave-active { transition: all 220ms ease; }
.mc-pop-enter-from, .mc-pop-leave-to { transform: translateY(100%); opacity: 0; }

.mc__head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px; background: #1e1b4b; cursor: pointer;
}
.mc__title { font-size: 13px; font-weight: 600; color: #e0e7ff; }
.mc__head-actions { display: flex; gap: 4px; }
.mc__head-btn {
    display: flex; align-items: center; justify-content: center;
    width: 24px; height: 24px; border-radius: 5px; border: none;
    background: transparent; color: #a5b4fc; cursor: pointer;
    transition: background 120ms ease;
}
.mc__head-btn:hover { background: rgba(255,255,255,0.12); color: #fff; }
.mc__head-btn--close:hover { background: #ef4444; }

.mc__body { display: flex; flex-direction: column; }

.mc__field {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 14px;
}
.mc__field--border { border-top: 1px solid var(--color-border); }
.mc__label { font-size: 11.5px; font-weight: 600; color: var(--color-text-subtle); width: 48px; flex-shrink: 0; }
.mc__input {
    flex: 1; border: none; outline: none; background: transparent;
    font-size: 13px; color: var(--color-text-primary); font-family: var(--font-sans);
}

.mc__toolbar {
    display: flex; align-items: center; gap: 2px;
    padding: 6px 10px; border-top: 1px solid var(--color-border);
    border-bottom: 1px solid var(--color-border);
}
.mc__tool {
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px; border: none;
    background: transparent; color: var(--color-text-muted); cursor: pointer;
    transition: all 120ms ease;
}
.mc__tool:hover { background: var(--color-bg-subtle); color: var(--color-text-primary); }
.mc__tool-sep { width: 1px; height: 16px; background: var(--color-border); margin: 0 4px; }

.mc__textarea {
    flex: 1; border: none; outline: none; resize: none;
    font-size: 13px; color: var(--color-text-primary); font-family: var(--font-sans);
    line-height: 1.6; padding: 12px 14px;
    background: transparent; min-height: 160px;
}

.mc__attachments {
    display: flex; flex-wrap: wrap; gap: 6px; padding: 8px 14px;
    border-top: 1px solid var(--color-border);
}
.mc__attach-chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11.5px; color: var(--color-text-muted);
    background: var(--color-bg-subtle); border: 1px solid var(--color-border);
    border-radius: 99px; padding: 3px 8px;
}
.mc__attach-rm {
    display: flex; align-items: center; border: none; background: transparent;
    cursor: pointer; color: var(--color-text-subtle); padding: 0; margin-left: 2px;
}

.mc__footer {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border-top: 1px solid var(--color-border);
    background: var(--color-bg-subtle);
}
.mc__send {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 18px; border-radius: 8px; border: none;
    background: #6366f1; color: #fff; font-size: 13px; font-weight: 600;
    font-family: var(--font-sans); cursor: pointer; transition: background 150ms ease;
}
.mc__send:hover:not(:disabled) { background: #4f46e5; }
.mc__send:disabled { opacity: 0.5; cursor: not-allowed; }
.mc__discard {
    display: flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 8px; border: none;
    background: transparent; cursor: pointer; color: var(--color-text-subtle);
    transition: all 120ms ease; margin-left: auto;
}
.mc__discard:hover { background: rgba(239,68,68,0.1); color: #ef4444; }
</style>
