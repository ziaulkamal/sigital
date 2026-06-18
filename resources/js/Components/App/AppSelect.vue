<template>
    <div class="app-sel-wrap" :class="{ 'app-sel-wrap--error': !!error }">
        <label v-if="label" :for="selId" class="app-sel__label">
            {{ label }}<span v-if="required" style="color:var(--color-danger)"> *</span>
        </label>

        <div class="app-sel__row" :class="[`app-sel__row--${size}`, { 'app-sel__row--focused': isFocused, 'app-sel__row--open': isOpen }]">
            <!-- Prefix slot -->
            <span v-if="$slots.prefix" class="app-sel__prefix"><slot name="prefix" /></span>

            <!-- Native select -->
            <select
                v-if="native"
                :id="selId"
                v-bind="$attrs"
                class="app-sel__native"
                :value="modelValue"
                :disabled="disabled"
                :aria-invalid="!!error"
                @change="$emit('update:modelValue', $event.target.value)"
                @focus="isFocused = true"
                @blur="isFocused = false"
            >
                <option v-if="placeholder" value="" disabled :selected="!modelValue">{{ placeholder }}</option>
                <option
                    v-for="opt in options"
                    :key="opt.value ?? opt"
                    :value="opt.value ?? opt"
                    :disabled="opt.disabled"
                >
                    {{ opt.label ?? opt }}
                </option>
            </select>

            <!-- Custom select trigger -->
            <button
                v-else
                :id="selId"
                type="button"
                class="app-sel__trigger"
                :disabled="disabled"
                :aria-haspopup="true"
                :aria-expanded="isOpen"
                @click="toggle"
                @keydown.esc="close"
                @keydown.space.prevent="toggle"
                @keydown.enter.prevent="toggle"
            >
                <span class="app-sel__value" :class="{ 'app-sel__value--placeholder': !selectedLabel }">
                    {{ selectedLabel || placeholder }}
                </span>
            </button>

            <!-- Chevron -->
            <span class="app-sel__chevron" :class="{ 'app-sel__chevron--open': isOpen }" aria-hidden="true">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </span>
        </div>

        <!-- Custom dropdown panel -->
        <Transition name="dropdown">
            <div
                v-if="!native && isOpen"
                class="app-sel__panel"
                role="listbox"
                @keydown.esc="close"
            >
                <button
                    v-if="placeholder"
                    type="button"
                    class="app-sel__option app-sel__option--placeholder"
                    role="option"
                    :aria-selected="false"
                    @click="select('')"
                >
                    {{ placeholder }}
                </button>
                <button
                    v-for="opt in options"
                    :key="opt.value ?? opt"
                    type="button"
                    class="app-sel__option"
                    :class="{ 'app-sel__option--active': (opt.value ?? opt) === modelValue, 'app-sel__option--disabled': opt.disabled }"
                    role="option"
                    :aria-selected="(opt.value ?? opt) === modelValue"
                    :disabled="opt.disabled"
                    @click="select(opt.value ?? opt)"
                >
                    <span v-if="opt.icon" class="app-sel__opt-icon"><component :is="opt.icon" :size="14" /></span>
                    {{ opt.label ?? opt }}
                    <svg v-if="(opt.value ?? opt) === modelValue" class="ml-auto" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </button>
            </div>
        </Transition>

        <p v-if="error" class="app-sel__msg app-sel__msg--error" role="alert">{{ error }}</p>
        <p v-else-if="hint" class="app-sel__msg app-sel__msg--hint">{{ hint }}</p>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';

type SelectOption = { value?: string | number; label?: string; disabled?: boolean; icon?: unknown } | string | number;

const props = defineProps({
    modelValue: { default: '' },
    options:    { type: Array as () => SelectOption[], default: () => [] },
    label:      { type: String,  default: '' },
    placeholder:{ type: String,  default: 'Select…' },
    size:       { type: String,  default: 'md' },
    error:      { type: String,  default: '' },
    hint:       { type: String,  default: '' },
    disabled:   { type: Boolean, default: false },
    required:   { type: Boolean, default: false },
    native:     { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
defineOptions({ inheritAttrs: false });

let _id = 0;
const selId     = `app-sel-${++_id}`;
const isFocused = ref(false);
const isOpen    = ref(false);

const selectedLabel = computed<string>(() => {
    const opt = props.options.find(o => (typeof o === 'object' && o !== null ? (o as { value?: string | number }).value : o) === props.modelValue);
    if (!opt) return '';
    return typeof opt === 'object' && opt !== null ? ((opt as { label?: string }).label ?? String(opt)) : String(opt);
});

function toggle(): void { isOpen.value = !isOpen.value; }
function close(): void  { isOpen.value = false; }
function select(val: string | number): void {
    emit('update:modelValue', val);
    close();
}

function onOutsideClick(e: MouseEvent): void {
    if (!(e.target as Element).closest('.app-sel-wrap')) close();
}
onMounted(() => document.addEventListener('click', onOutsideClick));
onUnmounted(() => document.removeEventListener('click', onOutsideClick));
</script>

<style scoped>
.app-sel-wrap { position: relative; display: flex; flex-direction: column; gap: 5px; }

.app-sel__label { font-size: 12.5px; font-weight: 600; color: var(--color-text-primary); }

.app-sel__row {
    display: flex;
    align-items: center;
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
    transition: border-color 150ms ease, box-shadow 150ms ease;
    overflow: hidden;
}
.app-sel__row--focused, .app-sel__row--open { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
.app-sel-wrap--error .app-sel__row { border-color: var(--color-danger); }
.app-sel__row--sm { min-height: 32px; }
.app-sel__row--md { min-height: 38px; }
.app-sel__row--lg { min-height: 44px; }

.app-sel__native {
    flex: 1; border: none; outline: none; background: transparent; cursor: pointer;
    color: var(--color-text-primary); font-family: var(--font-sans); font-size: 13.5px;
    padding: 0 4px 0 12px; width: 100%; appearance: none;
}
.app-sel__trigger {
    flex: 1; border: none; background: transparent; cursor: pointer; text-align: left;
    color: var(--color-text-primary); font-family: var(--font-sans); font-size: 13.5px;
    padding: 0 4px 0 12px; display: flex; align-items: center; min-width: 0;
}
.app-sel__value { truncate: true; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.app-sel__value--placeholder { color: var(--color-text-subtle); }

.app-sel__prefix { display: flex; align-items: center; padding: 0 10px; color: var(--color-text-muted); border-right: 1.5px solid var(--color-border); }
.app-sel__chevron { display: flex; align-items: center; padding: 0 10px; color: var(--color-text-subtle); transition: transform 200ms ease; flex-shrink: 0; }
.app-sel__chevron--open { transform: rotate(180deg); }

.app-sel__panel {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 50;
    background: var(--color-surface); border: 1.5px solid var(--color-border);
    border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);
    padding: 4px; max-height: 260px; overflow-y: auto;
}
.app-sel__option {
    display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 10px;
    border: none; background: transparent; cursor: pointer; text-align: left;
    font-size: 13px; font-family: var(--font-sans); color: var(--color-text-primary);
    border-radius: var(--radius-md); transition: background 120ms ease;
}
.app-sel__option:hover:not(:disabled) { background: var(--color-bg-subtle); }
.app-sel__option--active { color: #6366f1; font-weight: 500; }
.app-sel__option--placeholder { color: var(--color-text-subtle); font-style: italic; }
.app-sel__option--disabled { opacity: 0.45; cursor: not-allowed; }
.app-sel__opt-icon { display: flex; align-items: center; color: var(--color-text-muted); }

.app-sel__msg { font-size: 11.5px; }
.app-sel__msg--error { color: var(--color-danger); }
.app-sel__msg--hint  { color: var(--color-text-muted); }

.dropdown-enter-active, .dropdown-leave-active { transition: opacity 140ms ease, transform 140ms ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
