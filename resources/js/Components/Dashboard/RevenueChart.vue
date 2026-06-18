<template>
    <div class="rev-chart">
        <div class="rev-chart__head">
            <div>
                <p class="rev-chart__title">Revenue Overview</p>
                <p class="rev-chart__sub">Monthly revenue vs target</p>
            </div>
            <div class="rev-chart__controls">
                <button
                    v-for="p in PERIODS"
                    :key="p"
                    type="button"
                    class="rev-chart__period"
                    :class="{ 'rev-chart__period--active': period === p }"
                    @click="period = p"
                >
                    {{ p }}
                </button>
            </div>
        </div>

        <!-- Summary row -->
        <div class="rev-chart__summary">
            <div class="rev-chart__stat">
                <span class="rev-chart__stat-value">$84,320</span>
                <span class="rev-chart__stat-label">Total Revenue</span>
            </div>
            <div class="rev-chart__stat-divider" />
            <div class="rev-chart__stat">
                <span class="rev-chart__stat-value" style="color:#10b981">+18.2%</span>
                <span class="rev-chart__stat-label">vs last period</span>
            </div>
            <div class="rev-chart__stat-divider" />
            <div class="rev-chart__stat">
                <span class="rev-chart__stat-value">$7,640</span>
                <span class="rev-chart__stat-label">Avg per month</span>
            </div>
        </div>

        <!-- Chart canvas -->
        <div class="rev-chart__canvas-wrap">
            <Line :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale, LinearScale, PointElement, LineElement,
    Filler, Tooltip, Legend,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip, Legend);

const PERIODS = ['7D', '1M', '3M', '1Y'] as const;
type Period = typeof PERIODS[number];
const period = ref<Period>('1M');

const DATASETS: Record<Period, { labels: string[]; revenue: number[]; target: number[] }> = {
    '7D': {
        labels:  ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        revenue: [1200,1800,1400,2100,1900,2800,2400],
        target:  [1500,1500,1500,1500,1500,1500,1500],
    },
    '1M': {
        labels:  ['Jan 1','Jan 8','Jan 15','Jan 22','Jan 29'],
        revenue: [5200,7100,6400,8900,9200],
        target:  [6000,6000,6000,6000,6000],
    },
    '3M': {
        labels:  ['Oct','Nov','Dec'],
        revenue: [22000,28000,34320],
        target:  [25000,25000,25000],
    },
    '1Y': {
        labels:  ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        revenue: [4200,5800,6100,7400,6900,8200,9100,8700,7800,9200,10100,11040],
        target:  [7000,7000,7000,7000,7000,7000,7000,7000,7000,7000,7000,7000],
    },
};

const chartData = computed(() => {
    const d = DATASETS[period.value];
    return {
        labels: d.labels,
        datasets: [
            {
                label: 'Revenue',
                data: d.revenue,
                borderColor: '#6366f1',
                backgroundColor: (ctx: { chart: ChartJS }) => {
                    const chart = ctx.chart;
                    const { ctx: c, chartArea } = chart;
                    if (!chartArea) return 'transparent';
                    const gradient = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(99,102,241,0.2)');
                    gradient.addColorStop(1, 'rgba(99,102,241,0)');
                    return gradient;
                },
                fill: true,
                tension: 0.42,
                pointRadius: 3,
                pointHoverRadius: 5,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                borderWidth: 2,
            },
            {
                label: 'Target',
                data: d.target,
                borderColor: 'rgba(156,163,175,0.5)',
                borderDash: [6, 4],
                borderWidth: 1.5,
                fill: false,
                tension: 0,
                pointRadius: 0,
                pointHoverRadius: 0,
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index' as const, intersect: false },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#1e1b4b',
            titleColor: '#c7d2fe',
            bodyColor: '#fff',
            padding: 10,
            cornerRadius: 8,
            callbacks: {
                label: (ctx: { dataset: { label: string }; parsed: { y: number } }) =>
                    ` ${ctx.dataset.label}: $${ctx.parsed.y.toLocaleString()}`,
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            border: { display: false },
            ticks: { color: '#9ca3af', font: { size: 11, family: 'DM Sans' } },
        },
        y: {
            grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false },
            border: { display: false, dash: [4, 4] },
            ticks: {
                color: '#9ca3af',
                font: { size: 11, family: 'DM Sans' },
                callback: (v: string | number) => `$${Number(v).toLocaleString()}`,
            },
        },
    },
};
</script>

<style scoped>
.rev-chart {
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: 16px;
    padding: 22px;
    display: flex; flex-direction: column; gap: 18px;
    height: 100%;
}
.rev-chart__head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
}
.rev-chart__title { font-size: 14px; font-weight: 700; color: var(--color-text-primary); letter-spacing: -0.01em; }
.rev-chart__sub   { font-size: 12px; color: var(--color-text-muted); margin-top: 2px; }

.rev-chart__controls { display: flex; gap: 2px; background: var(--color-bg-subtle); border-radius: 8px; padding: 3px; }
.rev-chart__period {
    padding: 4px 10px; border-radius: 6px; border: none; background: transparent;
    font-size: 11.5px; font-weight: 500; color: var(--color-text-muted);
    cursor: pointer; transition: all 140ms ease; font-family: var(--font-sans);
}
.rev-chart__period--active {
    background: var(--color-surface); color: var(--color-text-primary);
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}

.rev-chart__summary {
    display: flex; align-items: center; gap: 20px;
}
.rev-chart__stat { display: flex; flex-direction: column; gap: 2px; }
.rev-chart__stat-value { font-size: 17px; font-weight: 700; color: var(--color-text-primary); letter-spacing: -0.02em; }
.rev-chart__stat-label { font-size: 11px; color: var(--color-text-muted); }
.rev-chart__stat-divider { width: 1px; height: 30px; background: var(--color-border); }

.rev-chart__canvas-wrap { flex: 1; min-height: 180px; position: relative; }
</style>
