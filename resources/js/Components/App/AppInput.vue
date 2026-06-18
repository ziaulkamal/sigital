<template>
    <div class="app-input-wrap" :class="{ 'app-input-wrap--error': !!error, 'app-input-wrap--disabled': disabled }">
        <!-- Label -->
        <label v-if="label" :for="inputId" class="app-input__label">
            {{ label }}
            <span v-if="required" class="app-input__required" aria-hidden="true"> *</span>
        </label>

        <!-- Input row -->
        <div class="app-input__row" :class="[`app-input__row--${size}`, { 'app-input__row--focused': isFocused }]">
            <!-- Prefix slot (icon/text) -->
            <span v-if="$slots.prefix" class="app-input__affix app-input__affix--prefix">
                <slot name="prefix" />
            </span>

            <!-- Native input -->
            <input
                :id="inputId"
                ref="inputRef"
                v-bind="$attrs"
                class="app-input__native"
                :class="{ 'app-input__native--has-prefix': $slots.prefix, 'app-input__native--has-suffix': $slots.suffix || (type === 'password') || (clearable && modelValue) }"
                :type="currentType"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :required="required"
                :aria-invalid="!!error"
                :aria-describedby="error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined"
                @input="$emit('update:modelValue', $event.target.value)"
                @focus="isFocused = true"
                @blur="isFocused = false"
            />

            <!-- Clear button -->
            <button
                v-if="clearable && modelValue && !disabled"
                type="button"
                class="app-input__clear"
                aria-label="Clear"
                tabindex="-1"
                @click="$emit('update:modelValue', ''); inputRef?.focus()"
            >
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                    <path d="M1 1L11 11M11 1L1 11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </button>

            <!-- Password toggle -->
            <button
                v-if="type === 'password'"
                type="button"
                class="app-input__clear"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                tabindex="-1"
                @click="showPassword = !showPassword"
            >
                <svg v-if="showPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </button>

            <!-- Suffix slot -->
            <span v-if="$slots.suffix" class="app-input__affix app-input__affix--suffix">
                <slot name="suffix" />
            </span>
        </div>

        <!-- Hint / Error -->
        <p v-if="error" :id="`${inputId}-error`" class="app-input__msg app-input__msg--error" role="alert">{{ error }}</p>
        <p v-else-if="hint" :id="`${inputId}-hint`" class="app-input__msg app-input__msg--hint">{{ hint }}</p>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

const props = defineProps({
    modelValue: { type: String,  default: '' },
    label:      { type: String,  default: '' },
    placeholder:{ type: String,  default: '' },
    type:       { type: String,  default: 'text' },
    size:       { type: String,  default: 'md' },  // sm | md | lg
    error:      { type: String,  default: '' },
    hint:       { type: String,  default: '' },
    disabled:   { type: Boolean, default: false },
    required:   { type: Boolean, default: false },
    clearable:  { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
defineOptions({ inheritAttrs: false });

let _id = 0;
const inputId      = `app-input-${++_id}`;
const inputRef     = ref<HTMLInputElement | null>(null);
const isFocused    = ref(false);
const showPassword = ref(false);
const currentType  = computed<string>(() => props.type === 'password' && showPassword.value ? 'text' : props.type);
</script>

<style scoped>
.app-input-wrap { display: flex; flex-direction: column; gap: 5px; }

.app-input__label {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--color-text-primary);
    letter-spacing: -0.01em;
}
.app-input__required { color: var(--color-danger); }

.app-input__row {
    display: flex;
    align-items: center;
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
    transition: border-color 150ms ease, box-shadow 150ms ease;
    overflow: hidden;
}
.app-input__row--focused {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
}
.app-input-wrap--error .app-input__row {
    border-color: var(--color-danger);
}
.app-input-wrap--error .app-input__row--focused {
    box-shadow: 0 0 0 3px rgba(239,68,68,0.15);
}
.app-input-wrap--disabled .app-input__row {
    background: var(--color-bg-subtle);
    opacity: 0.6;
}

/* Sizes */
.app-input__row--sm  { min-height: 32px; }
.app-input__row--md  { min-height: 38px; }
.app-input__row--lg  { min-height: 44px; }

.app-input__native {
    flex: 1;
    min-width: 0;
    border: none;
    outline: none;
    background: transparent;
    color: var(--color-text-primary);
    font-family: var(--font-sans);
    padding: 0 12px;
    width: 100%;
    height: 100%;
}
.app-input__row--sm  .app-input__native { font-size: 12px; padding: 0 10px; }
.app-input__row--md  .app-input__native { font-size: 13.5px; }
.app-input__row--lg  .app-input__native { font-size: 15px; padding: 0 14px; }
.app-input__native::placeholder { color: var(--color-text-subtle); }
.app-input__native--has-prefix { padding-left: 4px; }
.app-input__native--has-suffix { padding-right: 4px; }

.app-input__affix {
    display: flex;
    align-items: center;
    padding: 0 10px;
    color: var(--color-text-muted);
    font-size: 13px;
    flex-shrink: 0;
}
.app-input__affix--prefix { border-right: 1.5px solid var(--color-border); }
.app-input__affix--suffix  { border-left:  1.5px solid var(--color-border); }

.app-input__clear {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 8px;
    height: 100%;
    background: transparent;
    border: none;
    cursor: pointer;
    color: var(--color-text-subtle);
    transition: color 120ms ease;
    flex-shrink: 0;
}
.app-input__clear:hover { color: var(--color-text-primary); }

.app-input__msg { font-size: 11.5px; }
.app-input__msg--error { color: var(--color-danger); }
.app-input__msg--hint  { color: var(--color-text-muted); }
</style>
