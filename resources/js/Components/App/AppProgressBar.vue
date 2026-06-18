<template>
    <div class="app-progress" :class="`app-progress--${size}`">
        <div v-if="label || showValue" class="app-progress__header">
            <span v-if="label" class="app-progress__label">{{ label }}</span>
            <span v-if="showValue" class="app-progress__value">{{ Math.round(value) }}%</span>
        </div>
        <div class="app-progress__track" :class="{ 'app-progress__track--striped': striped }">
            <div
                v-if="indeterminate"
                class="app-progress__bar app-progress__bar--indeterminate"
                :style="{ background: colorValue }"
            />
            <div
                v-else
                class="app-progress__bar"
                :style="{ width: `${clampedValue}%`, background: colorValue, transition: animate ? 'width 400ms ease' : 'none' }"
                role="progressbar"
                :aria-valuenow="clampedValue"
                aria-valuemin="0"
                aria-valuemax="100"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
    value:         { type: Number,  default: 0 },
    color:         { type: String,  default: 'primary' },
    size:          { type: String,  default: 'md' },      // xs|sm|md|lg
    label:         { type: String,  default: '' },
    showValue:     { type: Boolean, default: false },
    indeterminate: { type: Boolean, default: false },
    striped:       { type: Boolean, default: false },
    animate:       { type: Boolean, default: true },
});

const colorMap: Record<string, string> = {
    primary: 'linear-gradient(90deg,#6366f1,#8b5cf6)',
    success: 'linear-gradient(90deg,#10b981,#059669)',
    warning: 'linear-gradient(90deg,#f59e0b,#f97316)',
    danger:  'linear-gradient(90deg,#ef4444,#f97316)',
    info:    'linear-gradient(90deg,#3b82f6,#6366f1)',
};

const colorValue   = computed<string>(() => colorMap[props.color] || props.color);
const clampedValue = computed<number>(() => Math.min(100, Math.max(0, props.value)));
</script>

<style scoped>
.app-progress { display: flex; flex-direction: column; gap: 5px; }

.app-progress__header { display: flex; justify-content: space-between; align-items: center; }
.app-progress__label  { font-size: 12px; font-weight: 500; color: var(--color-text-primary); }
.app-progress__value  { font-size: 11px; color: var(--color-text-muted); }

.app-progress__track {
    width: 100%; border-radius: 99px;
    background: var(--color-bg-subtle);
    overflow: hidden;
    position: relative;
}
.app-progress--xs .app-progress__track { height: 3px; }
.app-progress--sm .app-progress__track { height: 5px; }
.app-progress--md .app-progress__track { height: 8px; }
.app-progress--lg .app-progress__track { height: 12px; }

.app-progress__bar { height: 100%; border-radius: 99px; }

.app-progress__bar--indeterminate {
    width: 45%;
    animation: indeterminate 1.4s ease-in-out infinite;
}

.app-progress__track--striped .app-progress__bar {
    background-image: repeating-linear-gradient(
        45deg,
        rgba(255,255,255,0.15) 0px,
        rgba(255,255,255,0.15) 8px,
        transparent 8px,
        transparent 16px
    );
}

@keyframes indeterminate {
    0%   { transform: translateX(-100%); }
    60%  { transform: translateX(200%); }
    100% { transform: translateX(200%); }
}
</style>
