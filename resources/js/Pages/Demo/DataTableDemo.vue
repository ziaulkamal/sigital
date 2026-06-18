<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Data Table</h1>
                    <p class="page__sub">Sort, filter, paginate, bulk select, and row actions</p>
                </div>
            </div>
            <div class="page__card">
                <DataTable
                    :columns="columns"
                    :rows="rows"
                    :selectable="true"
                    search-placeholder="Search orders…"
                    :search-keys="['order', 'customer', 'status']"
                    :default-per-page="8"
                >
                    <template #cell-status="{ row }">
                        <AppBadge :color="statusColor(String(row.status))" size="sm">{{ row.status }}</AppBadge>
                    </template>
                    <template #cell-amount="{ row }">
                        <span style="font-weight: 600; color: var(--color-text-primary);">${{ row.amount }}</span>
                    </template>
                    <template #row-actions>
                        <div style="display:flex; gap:6px; justify-content:flex-end;">
                            <button class="demo-btn">View</button>
                            <button class="demo-btn demo-btn--danger">Delete</button>
                        </div>
                    </template>
                    <template #bulk-actions="{ selected }">
                        <button class="demo-btn">Export {{ selected.length }} rows</button>
                    </template>
                </DataTable>
            </div>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import BaseLayout from '@/Layouts/BaseLayout.vue';
import DataTable  from '@/Components/App/DataTable.vue';
import AppBadge   from '@/Components/App/AppBadge.vue';
import { navGroups } from '@/data/navGroups';

const columns = [
    { key: 'order',    label: 'Order #',   sortable: true, width: '110px' },
    { key: 'customer', label: 'Customer',  sortable: true },
    { key: 'status',   label: 'Status',    sortable: true },
    { key: 'amount',   label: 'Amount',    sortable: true },
    { key: 'date',     label: 'Date',      sortable: true },
];

const statusColors: Record<string, string> = {
    Paid:     'success',
    Pending:  'warning',
    Refunded: 'danger',
    Draft:    'default',
};

function statusColor(s: string): string { return statusColors[s] ?? 'default'; }

const rows = Array.from({ length: 32 }, (_, i) => ({
    id:       i + 1,
    order:    `#${1000 + i}`,
    customer: ['Sarah Johnson','James Brown','Emily Davis','Michael Lee','Jessica Wilson','David Martinez','Alice Martin','Bob Chen'][i % 8],
    status:   ['Paid','Pending','Refunded','Draft'][i % 4],
    amount:   ((i + 1) * 47 + 120).toFixed(2),
    date:     `2026-0${(i % 5) + 1}-${String((i % 28) + 1).padStart(2, '0')}`,
}));
</script>

<style scoped>
.page { padding: 24px; display: flex; flex-direction: column; gap: 20px; }
.page__header { display: flex; align-items: center; justify-content: space-between; }
.page__title  { font-size: 20px; font-weight: 800; color: var(--color-text-primary); font-family: var(--font-heading); letter-spacing: -0.02em; }
.page__sub    { font-size: 13px; color: var(--color-text-muted); margin-top: 2px; }
.page__card { background: var(--color-surface); border: 1.5px solid var(--color-border); border-radius: 14px; overflow: hidden; }
.demo-btn {
    padding: 4px 10px; border-radius: 6px; border: 1.5px solid var(--color-border);
    background: transparent; font-size: 11.5px; font-weight: 500; cursor: pointer;
    font-family: var(--font-sans); color: var(--color-text-primary); transition: all 120ms ease;
}
.demo-btn:hover { border-color: #6366f1; color: #6366f1; }
.demo-btn--danger:hover { border-color: #ef4444; color: #ef4444; }
</style>
