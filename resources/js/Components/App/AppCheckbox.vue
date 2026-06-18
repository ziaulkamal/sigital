<template>
    <label class="app-cb" :class="{ 'app-cb--disabled': disabled }">
        <div class="app-cb__box" :class="{ 'app-cb__box--checked': isChecked, 'app-cb__box--indeterminate': indeterminate }">
            <input
                v-bind="$attrs"
                class="app-cb__input"
                type="checkbox"
                :checked="isChecked"
                :disabled="disabled"
                :value="value"
                :aria-checked="indeterminate ? 'mixed' : isChecked"
                @change="onChange"
            />
            <!-- Check icon -->
            <svg v-if="!indeterminate && isChecked" width="11" height="11" viewBox="0 0 12 12" fill="none">
                <polyline points="1.5,6 5,9.5 10.5,2.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <!-- Indeterminate dash -->
            <span v-if="indeterminate" class="app-cb__dash" />
        </div>
        <span v-if="$slots.default || label" class="app-cb__label">
            <slot>{{ label }}</slot>
            <span v-if="description" class="app-cb__desc">{{ description }}</span>
        </span>
    </label>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
    modelValue:    { default: false },
    value:         { default: true },
    label:         { type: String,  default: '' },
    description:   { type: String,  default: '' },
    indeterminate: { type: Boolean, default: false },
    disabled:      { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
defineOptions({ inheritAttrs: false });

const isChecked = computed<boolean>(() => {
    if (Array.isArray(props.modelValue)) return props.modelValue.includes(props.value);
    return Boolean(props.modelValue);
});

function onChange(e: Event): void {
    const target = e.target as HTMLInputElement;
    if (Array.isArray(props.modelValue)) {
        const arr = [...props.modelValue];
        target.checked ? arr.push(props.value) : arr.splice(arr.indexOf(props.value), 1);
        emit('update:modelValue', arr);
    } else {
        emit('update:modelValue', target.checked);
    }
}
</script>

<style scoped>
.app-cb { display: inline-flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none; }
.app-cb--disabled { opacity: 0.5; cursor: not-allowed; }

.app-cb__box {
    position: relative; width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px;
    border: 2px solid var(--color-border); border-radius: 5px;
    background: var(--color-surface);
    transition: border-color 150ms ease, background 150ms ease, box-shadow 150ms ease;
    display: flex; align-items: center; justify-content: center;
}
.app-cb__box--checked, .app-cb__box--indeterminate {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-color: #6366f1;
    box-shadow: 0 2px 6px rgba(99,102,241,0.3);
}
.app-cb:hover:not(.app-cb--disabled) .app-cb__box { border-color: #6366f1; }

.app-cb__input {
    position: absolute; inset: 0; opacity: 0; width: 100%; height: 100%;
    cursor: inherit; margin: 0;
}
.app-cb__input:focus-visible + svg,
.app-cb__input:focus-visible ~ .app-cb__dash {
    outline: 2px solid #6366f1; outline-offset: 2px;
}
.app-cb__dash { width: 9px; height: 2px; border-radius: 1px; background: white; }

.app-cb__label { font-size: 13.5px; color: var(--color-text-primary); line-height: 1.4; }
.app-cb__desc  { display: block; font-size: 11.5px; color: var(--color-text-muted); margin-top: 1px; }
</style>
