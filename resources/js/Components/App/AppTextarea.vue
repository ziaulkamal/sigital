<template>
    <div class="app-ta-wrap" :class="{ 'app-ta-wrap--error': !!error }">
        <label v-if="label" :for="taId" class="app-ta__label">
            {{ label }}
            <span v-if="required" style="color:var(--color-danger)"> *</span>
        </label>

        <div class="app-ta__row" :class="{ 'app-ta__row--focused': isFocused }">
            <textarea
                :id="taId"
                ref="taRef"
                v-bind="$attrs"
                class="app-ta__native"
                :value="modelValue"
                :placeholder="placeholder"
                :rows="rows"
                :disabled="disabled"
                :maxlength="maxlength || undefined"
                :aria-invalid="!!error"
                :style="{ resize: autoResize ? 'none' : 'vertical', height: computedHeight }"
                @input="onInput"
                @focus="isFocused = true"
                @blur="isFocused = false"
            />
        </div>

        <div class="app-ta__footer">
            <p v-if="error" class="app-ta__msg app-ta__msg--error" role="alert">{{ error }}</p>
            <p v-else-if="hint" class="app-ta__msg app-ta__msg--hint">{{ hint }}</p>
            <span v-else />
            <span v-if="maxlength" class="app-ta__count" :class="{ 'app-ta__count--over': charCount >= maxlength }">
                {{ charCount }}/{{ maxlength }}
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue';

const props = defineProps({
    modelValue: { type: String,  default: '' },
    label:      { type: String,  default: '' },
    placeholder:{ type: String,  default: '' },
    rows:       { type: Number,  default: 4 },
    maxlength:  { type: Number,  default: null },
    error:      { type: String,  default: '' },
    hint:       { type: String,  default: '' },
    disabled:   { type: Boolean, default: false },
    required:   { type: Boolean, default: false },
    autoResize: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue']);
defineOptions({ inheritAttrs: false });

let _id = 0;
const taId          = `app-ta-${++_id}`;
const taRef         = ref<HTMLTextAreaElement | null>(null);
const isFocused     = ref(false);
const naturalHeight = ref('auto');

const charCount      = computed<number>(() => (props.modelValue ?? '').length);
const computedHeight = computed<string | undefined>(() => props.autoResize ? naturalHeight.value : undefined);

async function onInput(e: Event): Promise<void> {
    emit('update:modelValue', (e.target as HTMLTextAreaElement).value);
    if (props.autoResize) {
        naturalHeight.value = 'auto';
        await nextTick();
        naturalHeight.value = taRef.value ? taRef.value.scrollHeight + 'px' : 'auto';
    }
}
</script>

<style scoped>
.app-ta-wrap { display: flex; flex-direction: column; gap: 5px; }

.app-ta__label { font-size: 12.5px; font-weight: 600; color: var(--color-text-primary); }

.app-ta__row {
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
    transition: border-color 150ms ease, box-shadow 150ms ease;
}
.app-ta__row--focused {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
}
.app-ta-wrap--error .app-ta__row { border-color: var(--color-danger); }

.app-ta__native {
    width: 100%;
    border: none;
    outline: none;
    background: transparent;
    color: var(--color-text-primary);
    font-family: var(--font-sans);
    font-size: 13.5px;
    padding: 10px 12px;
    line-height: 1.6;
    min-height: 80px;
    overflow-y: hidden;
}
.app-ta__native::placeholder { color: var(--color-text-subtle); }

.app-ta__footer { display: flex; justify-content: space-between; align-items: center; min-height: 18px; }
.app-ta__msg { font-size: 11.5px; }
.app-ta__msg--error { color: var(--color-danger); }
.app-ta__msg--hint  { color: var(--color-text-muted); }
.app-ta__count { font-size: 11px; color: var(--color-text-subtle); }
.app-ta__count--over { color: var(--color-danger); font-weight: 600; }
</style>
