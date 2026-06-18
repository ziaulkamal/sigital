<template>
    <label class="app-radio" :class="{ 'app-radio--disabled': disabled }">
        <div class="app-radio__circle" :class="{ 'app-radio__circle--checked': isChecked }">
            <input
                v-bind="$attrs"
                class="app-radio__input"
                type="radio"
                :checked="isChecked"
                :disabled="disabled"
                :value="value"
                :name="name"
                @change="onChange"
            />
            <span v-if="isChecked" class="app-radio__dot" />
        </div>
        <span v-if="$slots.default || label" class="app-radio__label">
            <slot>{{ label }}</slot>
            <span v-if="description" class="app-radio__desc">{{ description }}</span>
        </span>
    </label>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
    modelValue:  { default: null },
    value:       { default: true },
    label:       { type: String,  default: '' },
    description: { type: String,  default: '' },
    name:        { type: String,  default: '' },
    disabled:    { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
defineOptions({ inheritAttrs: false });

const isChecked = computed<boolean>(() => props.modelValue === props.value);

function onChange(): void {
    emit('update:modelValue', props.value);
}
</script>

<style scoped>
.app-radio { display: inline-flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none; }
.app-radio--disabled { opacity: 0.5; cursor: not-allowed; }

.app-radio__circle {
    position: relative; width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px;
    border: 2px solid var(--color-border); border-radius: 50%;
    background: var(--color-surface);
    transition: border-color 150ms ease, background 150ms ease, box-shadow 150ms ease;
    display: flex; align-items: center; justify-content: center;
}
.app-radio__circle--checked {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-color: #6366f1;
    box-shadow: 0 2px 6px rgba(99,102,241,0.3);
}
.app-radio:hover:not(.app-radio--disabled) .app-radio__circle { border-color: #6366f1; }

.app-radio__input {
    position: absolute; inset: 0; opacity: 0; width: 100%; height: 100%;
    cursor: inherit; margin: 0;
}
.app-radio__input:focus-visible ~ .app-radio__dot,
.app-radio__input:focus-visible {
    outline: 2px solid #6366f1; outline-offset: 2px; border-radius: 50%;
}

.app-radio__dot {
    width: 7px; height: 7px; border-radius: 50%; background: white;
    pointer-events: none;
}

.app-radio__label { font-size: 13.5px; color: var(--color-text-primary); line-height: 1.4; }
.app-radio__desc  { display: block; font-size: 11.5px; color: var(--color-text-muted); margin-top: 1px; }
</style>
