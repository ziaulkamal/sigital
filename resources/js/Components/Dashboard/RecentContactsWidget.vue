<template>
    <div class="rcw">
        <div class="rcw__head">
            <p class="rcw__title">Recent Contacts</p>
            <button type="button" class="rcw__see-all">View all</button>
        </div>

        <!-- Search -->
        <div class="rcw__search">
            <SearchIcon :size="13" class="rcw__search-icon" />
            <input v-model="query" class="rcw__search-input" type="text" placeholder="Search contacts…" />
        </div>

        <!-- Table -->
        <div class="rcw__table-wrap">
            <table class="rcw__table">
                <thead>
                    <tr>
                        <th class="rcw__th">Contact</th>
                        <th class="rcw__th">Company</th>
                        <th class="rcw__th">Status</th>
                        <th class="rcw__th">Last activity</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in filtered" :key="c.name" class="rcw__row">
                        <td class="rcw__td">
                            <div class="rcw__contact">
                                <AppAvatar :name="c.name" size="sm" />
                                <div class="rcw__contact-info">
                                    <span class="rcw__contact-name">{{ c.name }}</span>
                                    <span class="rcw__contact-email">{{ c.email }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="rcw__td rcw__company">{{ c.company }}</td>
                        <td class="rcw__td">
                            <AppBadge :color="statusColor(c.status)" size="sm">{{ c.status }}</AppBadge>
                        </td>
                        <td class="rcw__td rcw__time">{{ c.lastActivity }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-if="filtered.length === 0" class="rcw__empty">No contacts match "{{ query }}"</div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { SearchIcon } from '@lucide/vue';
import AppAvatar from '@/Components/App/AppAvatar.vue';
import AppBadge  from '@/Components/App/AppBadge.vue';

type Status = 'Customer' | 'Lead' | 'Prospect' | 'Churned';

interface Contact {
    name: string;
    email: string;
    company: string;
    status: Status;
    lastActivity: string;
}

const contacts: Contact[] = [
    { name: 'Sarah Johnson',  email: 'sarah@acme.com',     company: 'Acme Corp',     status: 'Customer',  lastActivity: '2h ago' },
    { name: 'James Brown',    email: 'james@betaltd.com',  company: 'Beta Ltd',      status: 'Lead',      lastActivity: '5h ago' },
    { name: 'Emily Davis',    email: 'emily@gamma.io',     company: 'Gamma Inc',     status: 'Prospect',  lastActivity: '1d ago' },
    { name: 'Michael Lee',    email: 'm.lee@delta.co',     company: 'Delta Systems', status: 'Customer',  lastActivity: '2d ago' },
    { name: 'Jessica Wilson', email: 'j.wilson@eta.net',   company: 'Eta Networks',  status: 'Lead',      lastActivity: '3d ago' },
    { name: 'David Martinez', email: 'd.m@zeta.com',       company: 'Zeta Corp',     status: 'Churned',   lastActivity: '1w ago' },
];

const query    = ref('');
const filtered = computed(() =>
    contacts.filter(c =>
        c.name.toLowerCase().includes(query.value.toLowerCase()) ||
        c.company.toLowerCase().includes(query.value.toLowerCase()),
    ),
);

function statusColor(s: Status): string {
    const map: Record<Status, string> = {
        Customer: 'success',
        Lead:     'primary',
        Prospect: 'warning',
        Churned:  'danger',
    };
    return map[s];
}
</script>

<style scoped>
.rcw {
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: 16px;
    padding: 22px;
    display: flex; flex-direction: column; gap: 14px;
    height: 100%;
}
.rcw__head { display: flex; align-items: center; justify-content: space-between; }
.rcw__title { font-size: 14px; font-weight: 700; color: var(--color-text-primary); letter-spacing: -0.01em; }
.rcw__see-all {
    font-size: 12px; font-weight: 500; color: #6366f1;
    border: none; background: transparent; cursor: pointer;
    font-family: var(--font-sans); padding: 0;
}
.rcw__see-all:hover { text-decoration: underline; }

.rcw__search {
    display: flex; align-items: center; gap: 8px;
    border: 1.5px solid var(--color-border);
    border-radius: 8px; padding: 7px 10px;
    background: var(--color-bg-subtle);
}
.rcw__search-icon { color: var(--color-text-subtle); flex-shrink: 0; }
.rcw__search-input {
    flex: 1; border: none; background: transparent; outline: none;
    font-size: 12.5px; color: var(--color-text-primary);
    font-family: var(--font-sans);
}
.rcw__search-input::placeholder { color: var(--color-text-subtle); }

.rcw__table-wrap { overflow-x: auto; flex: 1; }
.rcw__table { width: 100%; border-collapse: collapse; }
.rcw__th {
    text-align: left; font-size: 10.5px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.07em;
    color: var(--color-text-subtle);
    padding: 0 12px 10px; border-bottom: 1px solid var(--color-border);
    white-space: nowrap;
}
.rcw__row { transition: background 120ms ease; }
.rcw__row:hover { background: var(--color-bg-subtle); }
.rcw__td { padding: 11px 12px; border-bottom: 1px solid var(--color-border); vertical-align: middle; }
.rcw__row:last-child .rcw__td { border-bottom: none; }

.rcw__contact { display: flex; align-items: center; gap: 10px; }
.rcw__contact-info { display: flex; flex-direction: column; gap: 1px; }
.rcw__contact-name  { font-size: 12.5px; font-weight: 600; color: var(--color-text-primary); white-space: nowrap; }
.rcw__contact-email { font-size: 11px; color: var(--color-text-muted); }
.rcw__company { font-size: 12.5px; color: var(--color-text-muted); white-space: nowrap; }
.rcw__time    { font-size: 11.5px; color: var(--color-text-subtle); white-space: nowrap; }

.rcw__empty { text-align: center; padding: 24px; font-size: 13px; color: var(--color-text-subtle); }
</style>
