<template>
    <div class="funnel">
        <div class="funnel__head">
            <div>
                <p class="funnel__title">Deal Pipeline</p>
                <p class="funnel__sub">{{ totalDeals }} active deals · ${{ totalValue }}</p>
            </div>
            <div class="funnel__legend">
                <span class="funnel__dot" style="background:#6366f1" />
                <span class="funnel__legend-label">Deals</span>
            </div>
        </div>

        <!-- Donut chart -->
        <div class="funnel__donut-wrap">
            <Doughnut :data="chartData" :options="chartOptions" />
            <div class="funnel__donut-center">
                <span class="funnel__donut-num">{{ totalDeals }}</span>
                <span class="funnel__donut-sub">Deals</span>
            </div>
        </div>

        <!-- Stage list -->
        <div class="funnel__stages">
            <div v-for="stage in stages" :key="stage.label" class="funnel__stage">
                <div class="funnel__stage-left">
                    <span class="funnel__stage-dot" :style="{ background: stage.color }" />
                    <span class="funnel__stage-label">{{ stage.label }}</span>
                </div>
                <div class="funnel__stage-right">
                    <span class="funnel__stage-count">{{ stage.count }}</span>
                    <div class="funnel__stage-bar">
                        <div
                            class="funnel__stage-fill"
                            :style="{ width: `${(stage.count / totalDeals) * 100}%`, background: stage.color }"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip } from 'chart.js';

ChartJS.register(ArcElement, Tooltip);

const stages = [
    { label: 'Prospecting', count: 42, color: '#6366f1', value: '$126k' },
    { label: 'Proposal',    count: 28, color: '#8b5cf6', value: '$84k' },
    { label: 'Negotiation', count: 17, color: '#a78bfa', value: '$51k' },
    { label: 'Closing',     count: 9,  color: '#c4b5fd', value: '$27k' },
    { label: 'Won',         count: 6,  color: '#10b981', value: '$18k' },
];

const totalDeals = computed(() => stages.reduce((s, st) => s + st.count, 0));
const totalValue = '306k';

const chartData = computed(() => ({
    labels:   stages.map(s => s.label),
    datasets: [{
        data:            stages.map(s => s.count),
        backgroundColor: stages.map(s => s.color),
        borderWidth: 0,
        hoverOffset: 6,
    }],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '72%',
    plugins: {
        legend:  { display: false },
        tooltip: {
            backgroundColor: '#1e1b4b',
            titleColor: '#c7d2fe',
            bodyColor: '#fff',
            padding: 10,
            cornerRadius: 8,
            callbacks: {
                label: (ctx: { label: string; parsed: number }) =>
                    ` ${ctx.label}: ${ctx.parsed} deals`,
            },
        },
    },
};
</script>

<style scoped>
.funnel {
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: 16px;
    padding: 22px;
    display: flex; flex-direction: column; gap: 18px;
    height: 100%;
}
.funnel__head { display: flex; align-items: flex-start; justify-content: space-between; }
.funnel__title { font-size: 14px; font-weight: 700; color: var(--color-text-primary); letter-spacing: -0.01em; }
.funnel__sub   { font-size: 12px; color: var(--color-text-muted); margin-top: 2px; }

.funnel__legend { display: flex; align-items: center; gap: 6px; }
.funnel__dot    { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.funnel__legend-label { font-size: 11.5px; color: var(--color-text-muted); }

.funnel__donut-wrap {
    position: relative; height: 160px;
    display: flex; align-items: center; justify-content: center;
}
.funnel__donut-center {
    position: absolute; inset: 0;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    pointer-events: none;
}
.funnel__donut-num { font-size: 26px; font-weight: 800; color: var(--color-text-primary); letter-spacing: -0.03em; }
.funnel__donut-sub { font-size: 11px; color: var(--color-text-muted); }

.funnel__stages { display: flex; flex-direction: column; gap: 10px; }
.funnel__stage  { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.funnel__stage-left  { display: flex; align-items: center; gap: 8px; min-width: 100px; }
.funnel__stage-right { display: flex; align-items: center; gap: 10px; flex: 1; }
.funnel__stage-dot   { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.funnel__stage-label { font-size: 12px; color: var(--color-text-muted); }
.funnel__stage-count { font-size: 12px; font-weight: 600; color: var(--color-text-primary); min-width: 24px; text-align: right; }
.funnel__stage-bar   { flex: 1; height: 5px; background: var(--color-bg-subtle); border-radius: 99px; overflow: hidden; }
.funnel__stage-fill  { height: 100%; border-radius: 99px; transition: width 600ms ease; }
</style>
