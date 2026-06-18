<template>
    <div class="wstep" :class="`wstep--${variant}`">
        <div
            v-for="(step, i) in steps"
            :key="i"
            class="wstep__item"
            :class="{
                'wstep__item--done':    i < current,
                'wstep__item--active':  i === current,
                'wstep__item--pending': i > current,
            }"
        >
            <!-- Connector line before -->
            <div v-if="i > 0" class="wstep__line" :class="{ 'wstep__line--done': i <= current }" />

            <!-- Circle -->
            <div class="wstep__circle">
                <CheckIcon v-if="i < current" :size="13" />
                <span v-else>{{ i + 1 }}</span>
            </div>

            <!-- Labels -->
            <div v-if="variant !== 'compact'" class="wstep__labels">
                <span class="wstep__label">{{ step.label }}</span>
                <span v-if="step.desc" class="wstep__desc">{{ step.desc }}</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { CheckIcon } from '@lucide/vue';

export interface Step {
    label: string;
    desc?: string;
}

defineProps({
    steps:   { type: Array as () => Step[], default: () => [] },
    current: { type: Number, default: 0 },
    variant: { type: String as () => 'default' | 'compact', default: 'default' },
});
</script>

<style scoped>
.wstep {
    display: flex; align-items: flex-start; gap: 0; width: 100%;
}
.wstep--compact { align-items: center; }

.wstep__item {
    display: flex; align-items: center; flex: 1; gap: 10px; position: relative;
}
.wstep--compact .wstep__item { justify-content: center; }

.wstep__line {
    flex: 1; height: 2px; background: var(--color-border); border-radius: 99px;
    transition: background 300ms ease;
}
.wstep__line--done { background: #6366f1; }

.wstep__circle {
    width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 12.5px; font-weight: 700; transition: all 200ms ease;
    border: 2px solid var(--color-border);
    background: var(--color-surface); color: var(--color-text-subtle);
}
.wstep__item--done .wstep__circle {
    background: #6366f1; border-color: #6366f1; color: #fff;
}
.wstep__item--active .wstep__circle {
    border-color: #6366f1; color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99,102,241,0.15);
}

.wstep__labels { display: flex; flex-direction: column; gap: 1px; white-space: nowrap; }
.wstep__label  { font-size: 12.5px; font-weight: 600; color: var(--color-text-muted); }
.wstep__desc   { font-size: 11px; color: var(--color-text-subtle); }
.wstep__item--active  .wstep__label { color: var(--color-text-primary); }
.wstep__item--done    .wstep__label { color: var(--color-text-muted); }
</style>
