<template>
    <div class="kpi">
        <div class="kpi__top">
            <div class="kpi__icon" :style="{ background: `${color}18` }">
                <component :is="icon" :size="18" :style="{ color }" />
            </div>
            <div class="kpi__trend" :class="trend >= 0 ? 'kpi__trend--up' : 'kpi__trend--down'">
                <component :is="trend >= 0 ? TrendingUpIcon : TrendingDownIcon" :size="13" />
                {{ Math.abs(trend) }}%
            </div>
        </div>

        <div class="kpi__value">{{ value }}</div>
        <div class="kpi__label">{{ label }}</div>
        <div class="kpi__sub">{{ sub }}</div>

        <!-- Sparkline -->
        <svg v-if="sparkline.length" class="kpi__spark" viewBox="0 0 100 28" preserveAspectRatio="none">
            <defs>
                <linearGradient :id="`sg-${uid}`" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" :stop-color="color" stop-opacity="0.25" />
                    <stop offset="100%" :stop-color="color" stop-opacity="0" />
                </linearGradient>
            </defs>
            <path :d="areaPath" :fill="`url(#sg-${uid})`" />
            <path :d="linePath" :stroke="color" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { TrendingUpIcon, TrendingDownIcon } from '@lucide/vue';

const props = defineProps({
    label:     { type: String,  default: '' },
    value:     { type: String,  default: '0' },
    sub:       { type: String,  default: '' },
    trend:     { type: Number,  default: 0 },
    color:     { type: String,  default: '#6366f1' },
    icon:      { default: null },
    sparkline: { type: Array as () => number[], default: () => [] },
});

let _uid = 0;
const uid = ++_uid;

const sparkPath = computed(() => {
    const data = props.sparkline;
    if (data.length < 2) return { line: '', area: '' };
    const min = Math.min(...data);
    const max = Math.max(...data);
    const range = max - min || 1;
    const w = 100 / (data.length - 1);
    const points = data.map((v, i) => ({
        x: i * w,
        y: 26 - ((v - min) / range) * 24,
    }));
    const line = points.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' ');
    const area = `${line} L${points[points.length - 1].x},28 L0,28 Z`;
    return { line, area };
});

const linePath  = computed(() => sparkPath.value.line);
const areaPath  = computed(() => sparkPath.value.area);
</script>

<style scoped>
.kpi {
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: 16px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    position: relative;
    overflow: hidden;
    transition: box-shadow 200ms ease, transform 200ms ease;
}
.kpi:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.07); transform: translateY(-1px); }

.kpi__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.kpi__icon {
    width: 40px; height: 40px; border-radius: 11px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.kpi__trend {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 11.5px; font-weight: 600;
    padding: 3px 8px; border-radius: 99px;
}
.kpi__trend--up   { background: rgba(16,185,129,0.1); color: #10b981; }
.kpi__trend--down { background: rgba(239,68,68,0.1);  color: #ef4444; }

.kpi__value {
    font-size: 26px; font-weight: 800;
    color: var(--color-text-primary);
    letter-spacing: -0.03em;
    line-height: 1.1;
    font-family: var(--font-heading);
}
.kpi__label {
    font-size: 12px; font-weight: 600;
    color: var(--color-text-muted);
    text-transform: uppercase; letter-spacing: 0.06em;
    margin-top: 2px;
}
.kpi__sub {
    font-size: 11.5px;
    color: var(--color-text-subtle);
}

.kpi__spark {
    width: 100%; height: 36px;
    margin-top: 12px; flex-shrink: 0;
}
</style>
