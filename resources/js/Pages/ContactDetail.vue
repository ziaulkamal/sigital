<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <!-- Breadcrumb -->
            <AppBreadcrumb :crumbs="[{ label: 'Contacts', href: '/contacts' }, { label: contact.name }]" />

            <!-- Profile header -->
            <div class="cd__hero">
                <div class="cd__hero-left">
                    <AppAvatar :name="contact.name" size="xl" />
                    <div class="cd__hero-info">
                        <h1 class="cd__name">{{ contact.name }}</h1>
                        <p class="cd__role">{{ contact.title }} · {{ contact.company }}</p>
                        <div class="cd__meta">
                            <AppBadge :color="statusColor(contact.status)" size="sm">{{ contact.status }}</AppBadge>
                            <span class="cd__meta-item"><MapPinIcon :size="12" /> {{ contact.location }}</span>
                            <span class="cd__meta-item"><ClockIcon :size="12" /> Last seen {{ contact.lastSeen }}</span>
                        </div>
                    </div>
                </div>
                <div class="cd__hero-actions">
                    <button class="cd__action-btn"><MailIcon :size="14" /> Email</button>
                    <button class="cd__action-btn"><PhoneIcon :size="14" /> Call</button>
                    <button class="cd__action-btn cd__action-btn--primary"><EditIcon :size="14" /> Edit</button>
                </div>
            </div>

            <!-- Stats strip -->
            <div class="cd__stats">
                <div v-for="s in stats" :key="s.label" class="cd__stat">
                    <span class="cd__stat-value">{{ s.value }}</span>
                    <span class="cd__stat-label">{{ s.label }}</span>
                </div>
            </div>

            <!-- Tabs -->
            <AppTabs :tabs="tabs">
                <template #info>
                    <div class="cd__section-grid">
                        <div class="cd__info-card">
                            <h3 class="cd__info-title">Contact Information</h3>
                            <div class="cd__info-rows">
                                <div class="cd__info-row">
                                    <span class="cd__info-key"><MailIcon :size="13" /> Email</span>
                                    <a :href="`mailto:${contact.email}`" class="cd__info-link">{{ contact.email }}</a>
                                </div>
                                <div class="cd__info-row">
                                    <span class="cd__info-key"><PhoneIcon :size="13" /> Phone</span>
                                    <span>{{ contact.phone }}</span>
                                </div>
                                <div class="cd__info-row">
                                    <span class="cd__info-key"><MapPinIcon :size="13" /> Location</span>
                                    <span>{{ contact.location }}</span>
                                </div>
                                <div class="cd__info-row">
                                    <span class="cd__info-key"><LinkIcon :size="13" /> LinkedIn</span>
                                    <a href="#" class="cd__info-link">{{ contact.linkedin }}</a>
                                </div>
                            </div>
                        </div>
                        <div class="cd__info-card">
                            <h3 class="cd__info-title">Company</h3>
                            <div class="cd__info-rows">
                                <div class="cd__info-row">
                                    <span class="cd__info-key"><BuildingIcon :size="13" /> Company</span>
                                    <span>{{ contact.company }}</span>
                                </div>
                                <div class="cd__info-row">
                                    <span class="cd__info-key"><BriefcaseIcon :size="13" /> Title</span>
                                    <span>{{ contact.title }}</span>
                                </div>
                                <div class="cd__info-row">
                                    <span class="cd__info-key"><UsersIcon :size="13" /> Team size</span>
                                    <span>{{ contact.teamSize }}</span>
                                </div>
                                <div class="cd__info-row">
                                    <span class="cd__info-key"><CalendarIcon :size="13" /> Added</span>
                                    <span>{{ contact.addedOn }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template #deals>
                    <div class="cd__deals">
                        <div v-for="d in deals" :key="d.id" class="cd__deal">
                            <div class="cd__deal-left">
                                <span class="cd__deal-name">{{ d.name }}</span>
                                <span class="cd__deal-stage">{{ d.stage }}</span>
                            </div>
                            <div class="cd__deal-right">
                                <span class="cd__deal-value">{{ d.value }}</span>
                                <AppBadge :color="d.color" size="sm">{{ d.status }}</AppBadge>
                            </div>
                        </div>
                    </div>
                </template>

                <template #activity>
                    <div class="cd__activity">
                        <div v-for="(a, i) in activities" :key="i" class="cd__act-item">
                            <div class="cd__act-dot" :style="{ background: a.color }" />
                            <div class="cd__act-body">
                                <p class="cd__act-text">{{ a.text }}</p>
                                <p class="cd__act-time">{{ a.time }}</p>
                            </div>
                        </div>
                    </div>
                </template>
            </AppTabs>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import BaseLayout    from '@/Layouts/BaseLayout.vue';
import AppAvatar     from '@/Components/App/AppAvatar.vue';
import AppBadge      from '@/Components/App/AppBadge.vue';
import AppBreadcrumb from '@/Components/App/AppBreadcrumb.vue';
import AppTabs       from '@/Components/App/AppTabs.vue';
import {
    MailIcon, PhoneIcon, MapPinIcon, ClockIcon, EditIcon, LinkIcon,
    BuildingIcon, BriefcaseIcon, UsersIcon, CalendarIcon,
} from '@lucide/vue';
import { navGroups } from '@/data/navGroups';

const contact = {
    name:     'Sarah Johnson',
    email:    'sarah@acme.com',
    phone:    '+1 555-0101',
    company:  'Acme Corp',
    title:    'VP of Sales',
    location: 'San Francisco, CA',
    linkedin: 'linkedin.com/in/sarahjohnson',
    status:   'Customer',
    lastSeen: '2h ago',
    teamSize: '50–200',
    addedOn:  'Jan 12, 2026',
};

const stats = [
    { label: 'Open Deals',     value: '3' },
    { label: 'Total Revenue',  value: '$48k' },
    { label: 'Emails Sent',    value: '24' },
    { label: 'Calls Logged',   value: '8' },
    { label: 'Days as Client', value: '127' },
];

const tabs = [
    { key: 'info',     label: 'Info' },
    { key: 'deals',    label: 'Deals' },
    { key: 'activity', label: 'Activity' },
];

const statusColorMap: Record<string, string> = {
    Customer: 'success', Lead: 'primary', Prospect: 'warning', Churned: 'danger',
};
function statusColor(s: string): string { return statusColorMap[s] ?? 'default'; }

const deals = [
    { id: 1, name: 'Enterprise Renewal',  stage: 'Negotiation', value: '$24,000', status: 'Active',  color: 'primary' },
    { id: 2, name: 'Add-on Seats',        stage: 'Proposal',    value: '$8,400',  status: 'Active',  color: 'warning' },
    { id: 3, name: 'Initial Contract',    stage: 'Won',         value: '$16,000', status: 'Closed',  color: 'success' },
];

const activities = [
    { text: 'Email sent — Q2 pipeline review', time: '2h ago',    color: '#6366f1' },
    { text: 'Call logged — 12 min with Sarah', time: '1d ago',    color: '#3b82f6' },
    { text: 'Proposal sent to Acme Corp',       time: '3d ago',    color: '#f59e0b' },
    { text: 'Deal "Enterprise Renewal" opened', time: '1w ago',    color: '#10b981' },
    { text: 'Contact added by Alice Martin',    time: 'Jan 12',    color: '#8b5cf6' },
];
</script>

<style scoped>
.page { padding: 24px; display: flex; flex-direction: column; gap: 20px; max-width: 960px; }

.cd__hero {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;
    background: var(--color-surface); border: 1.5px solid var(--color-border);
    border-radius: 16px; padding: 24px;
}
.cd__hero-left { display: flex; align-items: center; gap: 16px; }
.cd__hero-info { display: flex; flex-direction: column; gap: 6px; }
.cd__name  { font-size: 20px; font-weight: 800; color: var(--color-text-primary); font-family: var(--font-heading); margin: 0; }
.cd__role  { font-size: 13px; color: var(--color-text-muted); margin: 0; }
.cd__meta  { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.cd__meta-item { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: var(--color-text-subtle); }
.cd__hero-actions { display: flex; gap: 8px; flex-shrink: 0; align-self: flex-start; }
.cd__action-btn {
    display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px;
    border-radius: 8px; border: 1.5px solid var(--color-border);
    background: var(--color-surface); font-size: 12.5px; font-weight: 500;
    font-family: var(--font-sans); cursor: pointer; color: var(--color-text-primary);
    transition: all 120ms ease;
}
.cd__action-btn:hover { border-color: var(--color-text-muted); }
.cd__action-btn--primary { background: #6366f1; color: #fff; border-color: #6366f1; }
.cd__action-btn--primary:hover { background: #4f46e5; }

.cd__stats {
    display: flex; gap: 0;
    background: var(--color-surface); border: 1.5px solid var(--color-border);
    border-radius: 14px; overflow: hidden;
}
.cd__stat {
    flex: 1; display: flex; flex-direction: column; align-items: center; gap: 3px;
    padding: 16px 12px; border-right: 1px solid var(--color-border);
}
.cd__stat:last-child { border-right: none; }
.cd__stat-value { font-size: 20px; font-weight: 800; color: var(--color-text-primary); font-family: var(--font-heading); letter-spacing: -0.02em; }
.cd__stat-label { font-size: 11px; color: var(--color-text-subtle); text-align: center; }

.cd__section-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.cd__info-card {
    background: var(--color-bg-subtle); border: 1px solid var(--color-border);
    border-radius: 10px; padding: 16px; display: flex; flex-direction: column; gap: 12px;
}
.cd__info-title { font-size: 12px; font-weight: 700; color: var(--color-text-subtle); text-transform: uppercase; letter-spacing: 0.07em; margin: 0; }
.cd__info-rows { display: flex; flex-direction: column; gap: 10px; }
.cd__info-row {
    display: flex; align-items: center; justify-content: space-between;
    font-size: 13px; color: var(--color-text-primary);
}
.cd__info-key { display: inline-flex; align-items: center; gap: 5px; color: var(--color-text-muted); font-size: 12.5px; }
.cd__info-link { color: #6366f1; font-weight: 500; }

.cd__deals { display: flex; flex-direction: column; gap: 10px; }
.cd__deal {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 14px 16px; background: var(--color-bg-subtle); border: 1px solid var(--color-border);
    border-radius: 10px;
}
.cd__deal-left { display: flex; flex-direction: column; gap: 3px; }
.cd__deal-name  { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }
.cd__deal-stage { font-size: 11.5px; color: var(--color-text-muted); }
.cd__deal-right { display: flex; align-items: center; gap: 10px; }
.cd__deal-value { font-size: 14px; font-weight: 700; color: var(--color-text-primary); }

.cd__activity { display: flex; flex-direction: column; gap: 0; }
.cd__act-item { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--color-border); }
.cd__act-item:last-child { border-bottom: none; }
.cd__act-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }
.cd__act-body { display: flex; flex-direction: column; gap: 2px; }
.cd__act-text { font-size: 13px; color: var(--color-text-primary); margin: 0; }
.cd__act-time { font-size: 11px; color: var(--color-text-subtle); margin: 0; }
</style>
