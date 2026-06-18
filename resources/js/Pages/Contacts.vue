<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Contacts</h1>
                    <p class="page__sub">Manage your CRM contacts</p>
                </div>
                <button class="page__cta">
                    <UserPlusIcon :size="14" />
                    Add Contact
                </button>
            </div>

            <div class="page__card">
                <DataTable
                    :columns="columns"
                    :rows="rows"
                    :selectable="true"
                    row-id="id"
                    search-placeholder="Search contacts…"
                    :search-keys="['name', 'email', 'company', 'status']"
                >
                    <template #cell-name="{ row }">
                        <div class="ct__name-cell">
                            <AppAvatar :name="String(row.name)" size="sm" />
                            <div>
                                <p class="ct__name">{{ row.name }}</p>
                                <p class="ct__email">{{ row.email }}</p>
                            </div>
                        </div>
                    </template>
                    <template #cell-status="{ row }">
                        <AppBadge :color="statusColor(String(row.status))" size="sm">{{ row.status }}</AppBadge>
                    </template>
                    <template #row-actions="{ row }">
                        <AppDropdown>
                            <template #trigger>
                                <button class="ct__action-btn"><MoreHorizontalIcon :size="15" /></button>
                            </template>
                            <template #default="{ close }">
                                <button class="ct__menu-item" @click="close">Edit</button>
                                <button class="ct__menu-item" @click="close">Send Email</button>
                                <button class="ct__menu-item ct__menu-item--danger" @click="close">Delete</button>
                            </template>
                        </AppDropdown>
                    </template>
                    <template #bulk-actions="{ selected }">
                        <button class="ct__bulk-btn">Export ({{ selected.length }})</button>
                        <button class="ct__bulk-btn ct__bulk-btn--danger">Delete ({{ selected.length }})</button>
                    </template>
                </DataTable>
            </div>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import BaseLayout    from '@/Layouts/BaseLayout.vue';
import DataTable     from '@/Components/App/DataTable.vue';
import AppAvatar     from '@/Components/App/AppAvatar.vue';
import AppBadge      from '@/Components/App/AppBadge.vue';
import AppDropdown   from '@/Components/App/AppDropdown.vue';
import { UserPlusIcon, MoreHorizontalIcon } from '@lucide/vue';
import { navGroups } from '@/data/navGroups';

const columns = [
    { key: 'name',     label: 'Contact',     sortable: true, width: '220px' },
    { key: 'company',  label: 'Company',     sortable: true },
    { key: 'status',   label: 'Status',      sortable: true },
    { key: 'phone',    label: 'Phone' },
    { key: 'lastSeen', label: 'Last Seen',   sortable: true },
];

const rows = [
    { id: 1, name: 'Sarah Johnson',  email: 'sarah@acme.com',     company: 'Acme Corp',     status: 'Customer',  phone: '+1 555-0101', lastSeen: '2h ago' },
    { id: 2, name: 'James Brown',    email: 'james@betaltd.com',  company: 'Beta Ltd',      status: 'Lead',      phone: '+1 555-0102', lastSeen: '5h ago' },
    { id: 3, name: 'Emily Davis',    email: 'emily@gamma.io',     company: 'Gamma Inc',     status: 'Prospect',  phone: '+1 555-0103', lastSeen: '1d ago' },
    { id: 4, name: 'Michael Lee',    email: 'm.lee@delta.co',     company: 'Delta Systems', status: 'Customer',  phone: '+1 555-0104', lastSeen: '2d ago' },
    { id: 5, name: 'Jessica Wilson', email: 'j.wilson@eta.net',   company: 'Eta Networks',  status: 'Lead',      phone: '+1 555-0105', lastSeen: '3d ago' },
    { id: 6, name: 'David Martinez', email: 'd.m@zeta.com',       company: 'Zeta Corp',     status: 'Churned',   phone: '+1 555-0106', lastSeen: '1w ago' },
    { id: 7, name: 'Alice Martin',   email: 'alice@omega.com',    company: 'Omega Ltd',     status: 'Customer',  phone: '+1 555-0107', lastSeen: '30m ago' },
    { id: 8, name: 'Bob Chen',       email: 'bob@sigma.io',       company: 'Sigma Corp',    status: 'Prospect',  phone: '+1 555-0108', lastSeen: '4h ago' },
    { id: 9, name: 'Carol Smith',    email: 'carol@alpha.net',    company: 'Alpha Ltd',     status: 'Lead',      phone: '+1 555-0109', lastSeen: '6h ago' },
    { id: 10, name: 'Dave Wilson',   email: 'dave@theta.com',     company: 'Theta Inc',     status: 'Customer',  phone: '+1 555-0110', lastSeen: '1d ago' },
    { id: 11, name: 'Eve Johnson',   email: 'eve@kappa.co',       company: 'Kappa LLC',     status: 'Prospect',  phone: '+1 555-0111', lastSeen: '2d ago' },
    { id: 12, name: 'Frank Garcia',  email: 'frank@lambda.io',    company: 'Lambda Corp',   status: 'Churned',   phone: '+1 555-0112', lastSeen: '2w ago' },
];

const statusColorMap: Record<string, string> = {
    Customer: 'success',
    Lead:     'primary',
    Prospect: 'warning',
    Churned:  'danger',
};

function statusColor(s: string): string {
    return statusColorMap[s] ?? 'default';
}
</script>

<style scoped>
.page { padding: 24px; display: flex; flex-direction: column; gap: 20px; }
.page__header { display: flex; align-items: center; justify-content: space-between; }
.page__title  { font-size: 20px; font-weight: 800; color: var(--color-text-primary); font-family: var(--font-heading); letter-spacing: -0.02em; }
.page__sub    { font-size: 13px; color: var(--color-text-muted); margin-top: 2px; }
.page__cta {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 18px; border-radius: 9px; border: none;
    background: #6366f1; color: #fff; font-size: 13px; font-weight: 600;
    font-family: var(--font-sans); cursor: pointer; transition: background 150ms ease;
}
.page__cta:hover { background: #4f46e5; }
.page__card {
    background: var(--color-surface); border: 1.5px solid var(--color-border);
    border-radius: 14px; overflow: hidden;
}
.ct__name-cell { display: flex; align-items: center; gap: 10px; }
.ct__name  { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }
.ct__email { font-size: 11px; color: var(--color-text-muted); }
.ct__action-btn {
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 7px; border: none;
    background: transparent; cursor: pointer; color: var(--color-text-subtle);
    transition: all 120ms ease;
}
.ct__action-btn:hover { background: var(--color-bg-subtle); color: var(--color-text-primary); }
.ct__menu-item {
    display: block; width: 100%; text-align: left; padding: 8px 14px;
    border: none; background: transparent; cursor: pointer;
    font-size: 13px; color: var(--color-text-primary); font-family: var(--font-sans);
    transition: background 100ms ease;
}
.ct__menu-item:hover { background: var(--color-bg-subtle); }
.ct__menu-item--danger { color: #ef4444; }
.ct__bulk-btn {
    padding: 5px 12px; border-radius: 7px; border: 1.5px solid var(--color-border);
    background: var(--color-surface); font-size: 12px; font-weight: 500;
    font-family: var(--font-sans); cursor: pointer; color: var(--color-text-primary);
    transition: all 120ms ease;
}
.ct__bulk-btn:hover { border-color: #6366f1; color: #6366f1; }
.ct__bulk-btn--danger:hover { border-color: #ef4444; color: #ef4444; }
</style>
