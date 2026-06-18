<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="dash">
            <!-- Page header -->
            <div class="dash__header">
                <div>
                    <h1 class="dash__title">Dashboard</h1>
                    <p class="dash__sub">Good morning, Ziaul — here's what's happening today.</p>
                </div>
                <div class="dash__header-date">
                    <CalendarIcon :size="13" />
                    {{ today }}
                </div>
            </div>

            <!-- Quick actions -->
            <QuickActionBar :actions="quickActions" />

            <!-- KPI grid -->
            <div class="dash__kpi-grid">
                <KpiCard
                    v-for="kpi in kpiCards"
                    :key="kpi.label"
                    :label="kpi.label"
                    :value="kpi.value"
                    :sub="kpi.sub"
                    :trend="kpi.trend"
                    :color="kpi.color"
                    :icon="kpi.icon"
                    :sparkline="kpi.sparkline"
                />
            </div>

            <!-- Charts row -->
            <div class="dash__row dash__row--charts">
                <div class="dash__col dash__col--wide">
                    <RevenueChart />
                </div>
                <div class="dash__col dash__col--narrow">
                    <DealFunnelChart />
                </div>
            </div>

            <!-- Bottom row -->
            <div class="dash__row dash__row--bottom">
                <div class="dash__col dash__col--wide">
                    <RecentContactsWidget />
                </div>
                <div class="dash__col dash__col--narrow">
                    <ActivityFeed />
                </div>
            </div>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import BaseLayout from '@/Layouts/BaseLayout.vue';
import KpiCard             from '@/Components/Dashboard/KpiCard.vue';
import QuickActionBar      from '@/Components/Dashboard/QuickActionBar.vue';
import RevenueChart        from '@/Components/Dashboard/RevenueChart.vue';
import DealFunnelChart     from '@/Components/Dashboard/DealFunnelChart.vue';
import ActivityFeed        from '@/Components/Dashboard/ActivityFeed.vue';
import RecentContactsWidget from '@/Components/Dashboard/RecentContactsWidget.vue';
import { navGroups } from '@/data/navGroups';

import {
    CalendarIcon, UserPlusIcon, PhoneCallIcon, FileTextIcon,
    DollarSignIcon, TrendingUpIcon, Users, Mail,
} from '@lucide/vue';

interface KpiCardData {
    label: string;
    value: string;
    sub: string;
    trend: number;
    color: string;
    icon: unknown;
    sparkline: number[];
}

interface QuickAction {
    label: string;
    icon: unknown;
    color?: string;
    onClick?: () => void;
}

const today = new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });

const kpiCards: KpiCardData[] = [
    {
        label:     'Total Revenue',
        value:     '$84,320',
        sub:       '+18.2% vs last month',
        trend:     18.2,
        color:     '#6366f1',
        icon:      DollarSignIcon,
        sparkline: [4200, 5800, 6100, 7400, 6900, 8200, 9100, 8700, 7800, 9200, 10100, 11040],
    },
    {
        label:     'Active Deals',
        value:     '102',
        sub:       '+5 opened this week',
        trend:     8.4,
        color:     '#10b981',
        icon:      TrendingUpIcon,
        sparkline: [62, 70, 75, 68, 80, 85, 90, 88, 94, 98, 100, 102],
    },
    {
        label:     'Contacts',
        value:     '2,481',
        sub:       '+34 added this month',
        trend:     4.1,
        color:     '#f59e0b',
        icon:      Users,
        sparkline: [1800, 1920, 2050, 2100, 2200, 2280, 2310, 2350, 2400, 2430, 2460, 2481],
    },
    {
        label:     'Emails Sent',
        value:     '12.4k',
        sub:       '+1.2k vs last week',
        trend:     -3.6,
        color:     '#ec4899',
        icon:      Mail,
        sparkline: [900, 1100, 950, 1200, 1050, 1300, 1100, 1250, 1400, 1000, 1150, 1200],
    },
];

const quickActions: QuickAction[] = [
    { label: 'Add Contact',   icon: UserPlusIcon,  color: '#6366f1' },
    { label: 'Log Call',      icon: PhoneCallIcon, color: '#10b981' },
    { label: 'New Proposal',  icon: FileTextIcon,  color: '#f59e0b' },
    { label: 'Record Deal',   icon: DollarSignIcon,color: '#8b5cf6' },
];

</script>

<style scoped>
.dash {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 24px;
    max-width: 1400px;
}

.dash__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}
.dash__title {
    font-size: 22px;
    font-weight: 800;
    color: var(--color-text-primary);
    font-family: var(--font-heading);
    letter-spacing: -0.02em;
}
.dash__sub {
    font-size: 13px;
    color: var(--color-text-muted);
    margin-top: 3px;
}
.dash__header-date {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--color-text-subtle);
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: 8px;
    padding: 6px 12px;
    white-space: nowrap;
    flex-shrink: 0;
}

.dash__kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.dash__row {
    display: grid;
    gap: 16px;
}
.dash__row--charts { grid-template-columns: 3fr 2fr; }
.dash__row--bottom { grid-template-columns: 3fr 2fr; }

.dash__col { min-width: 0; }

@media (max-width: 1100px) {
    .dash__kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .dash__row--charts,
    .dash__row--bottom { grid-template-columns: 1fr; }
}

@media (max-width: 640px) {
    .dash__kpi-grid { grid-template-columns: 1fr; }
    .dash { padding: 16px; }
}
</style>
