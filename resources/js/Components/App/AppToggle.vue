<template>
    <label class="app-toggle" :class="[`app-toggle--${size}`, { 'app-toggle--disabled': disabled, 'app-toggle--label-left': labelPosition === 'left' }]">
        <span v-if="(label || $slots.default) && labelPosition === 'left'" class="app-toggle__label">
            <slot>{{ label }}</slot>
            <span v-if="description" class="app-toggle__desc">{{ description }}</span>
        </span>

        <div class="app-toggle__track" :class="{ 'app-toggle__track--on': modelValue }">
            <input
                v-bind="$attrs"
                class="app-toggle__input"
                type="checkbox"
                :checked="modelValue"
                :disabled="disabled"
                @change="$emit('update:modelValue', $event.target.checked)"
            />
            <span class="app-toggle__thumb" />
        </div>

        <span v-if="(label || $slots.default) && labelPosition !== 'left'" class="app-toggle__label">
            <slot>{{ label }}</slot>
            <span v-if="description" class="app-toggle__desc">{{ description }}</span>
        </span>
    </label>
</template>

<script setup lang="ts">
defineProps({
    modelValue:    { type: Boolean, default: false },
    label:         { type: String,  default: '' },
    description:   { type: String,  default: '' },
    labelPosition: { type: String,  default: 'right' }, // left | right
    size:          { type: String,  default: 'md' },    // sm | md | lg
    disabled:      { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
defineOptions({ inheritAttrs: false });
</script>

<style scoped>
.app-toggle { display: inline-flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none; }
.app-toggle--disabled { opacity: 0.5; cursor: not-allowed; }
.app-toggle--label-left { flex-direction: row-reverse; }

.app-toggle__track {
    position: relative; flex-shrink: 0;
    border-radius: 99px;
    background: var(--color-border);
    transition: background 180ms ease, box-shadow 180ms ease;
}
.app-toggle--sm .app-toggle__track { width: 32px; height: 18px; }
.app-toggle--md .app-toggle__track { width: 40px; height: 22px; }
.app-toggle--lg .app-toggle__track { width: 48px; height: 26px; }

.app-toggle__track--on {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    box-shadow: 0 2px 6px rgba(99,102,241,0.35);
}

.app-toggle__input {
    position: absolute; inset: 0; opacity: 0; width: 100%; height: 100%;
    cursor: inherit; margin: 0; border-radius: inherit;
}
.app-toggle__input:focus-visible + .app-toggle__thumb {
    outline: 2px solid #6366f1; outline-offset: 3px; border-radius: 50%;
}

.app-toggle__thumb {
    position: absolute; top: 50%; transform: translateY(-50%);
    border-radius: 50%; background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    transition: left 180ms ease, width 120ms ease;
    pointer-events: none;
}
.app-toggle--sm .app-toggle__thumb { width: 13px; height: 13px; left: 2.5px; }
.app-toggle--md .app-toggle__thumb { width: 16px; height: 16px; left: 3px; }
.app-toggle--lg .app-toggle__thumb { width: 20px; height: 20px; left: 3px; }

.app-toggle--sm .app-toggle__track--on .app-toggle__thumb { left: calc(32px - 13px - 2.5px); }
.app-toggle--md .app-toggle__track--on .app-toggle__thumb { left: calc(40px - 16px - 3px); }
.app-toggle--lg .app-toggle__track--on .app-toggle__thumb { left: calc(48px - 20px - 3px); }

.app-toggle__label { font-size: 13.5px; color: var(--color-text-primary); line-height: 1.4; padding-top: 2px; }
.app-toggle--sm .app-toggle__label { padding-top: 1px; }
.app-toggle--lg .app-toggle__label { padding-top: 3px; }
.app-toggle__desc { display: block; font-size: 11.5px; color: var(--color-text-muted); margin-top: 1px; }
</style>
