<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <div class="page__header">
                <h1 class="page__title">Date Picker</h1>
                <p class="page__sub">Single date and date range picker</p>
            </div>

            <div class="demo-grid">
                <div class="demo-card">
                    <p class="demo-card__label">Single Date</p>
                    <AppDatePicker v-model="single" placeholder="Pick a date" />
                    <p class="demo-card__value">Selected: <strong>{{ single ? fmtDate(single as Date) : '—' }}</strong></p>
                </div>
                <div class="demo-card">
                    <p class="demo-card__label">Date Range</p>
                    <AppDatePicker v-model="range" :range="true" placeholder="Pick date range" />
                    <p class="demo-card__value">
                        Range: <strong>{{ rangeText }}</strong>
                    </p>
                </div>
                <div class="demo-card">
                    <p class="demo-card__label">With Min/Max Constraints</p>
                    <AppDatePicker v-model="constrained" :min-date="minDate" :max-date="maxDate" placeholder="Constrained range" />
                    <p class="demo-card__hint">Only dates in the next 30 days are selectable.</p>
                </div>
            </div>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import BaseLayout    from '@/Layouts/BaseLayout.vue';
import AppDatePicker from '@/Components/App/AppDatePicker.vue';
import { navGroups } from '@/data/navGroups';

const single      = ref(null);
const range       = ref(null);
const constrained = ref(null);

const minDate = new Date();
const maxDate = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000);

function fmtDate(d: Date): string {
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

const rangeText = computed(() => {
    if (!range.value || !Array.isArray(range.value)) return '—';
    const [s, e] = range.value as [Date, Date];
    return s && e ? `${fmtDate(s)} — ${fmtDate(e)}` : s ? fmtDate(s) : '—';
});
</script>

<style scoped>
.page { padding: 24px; display: flex; flex-direction: column; gap: 24px; }
.page__header { }
.page__title { font-size: 20px; font-weight: 800; color: var(--color-text-primary); font-family: var(--font-heading); letter-spacing: -0.02em; }
.page__sub   { font-size: 13px; color: var(--color-text-muted); margin-top: 2px; }
.demo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
.demo-card {
    background: var(--color-surface); border: 1.5px solid var(--color-border);
    border-radius: 14px; padding: 20px; display: flex; flex-direction: column; gap: 12px;
}
.demo-card__label { font-size: 12px; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.07em; margin: 0; }
.demo-card__value { font-size: 12.5px; color: var(--color-text-muted); margin: 0; }
.demo-card__hint  { font-size: 11.5px; color: var(--color-text-subtle); margin: 0; }
</style>
