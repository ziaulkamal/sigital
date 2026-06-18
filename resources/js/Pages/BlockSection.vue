<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <div class="page__header">
                <h1 class="page__title">Block Sections</h1>
                <p class="page__sub">Reusable UI blocks and card layout patterns</p>
            </div>

            <!-- Section: Stat cards -->
            <section class="bs__section">
                <h2 class="bs__section-title">Stat Cards</h2>
                <div class="bs__grid bs__grid--4">
                    <div v-for="s in statCards" :key="s.label" class="bs__stat-card">
                        <div class="bs__stat-card-icon" :style="{ background: s.color + '18' }">
                            <component :is="s.icon" :size="18" :style="{ color: s.color }" />
                        </div>
                        <div class="bs__stat-card-val">{{ s.value }}</div>
                        <div class="bs__stat-card-label">{{ s.label }}</div>
                        <div class="bs__stat-card-trend" :class="s.trend > 0 ? 'bs__trend--up' : 'bs__trend--down'">
                            <component :is="s.trend > 0 ? TrendingUpIcon : TrendingDownIcon" :size="11" />
                            {{ Math.abs(s.trend) }}% this month
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section: Pricing cards -->
            <section class="bs__section">
                <h2 class="bs__section-title">Pricing Cards</h2>
                <div class="bs__grid bs__grid--3">
                    <div
                        v-for="plan in plans"
                        :key="plan.name"
                        class="bs__pricing-card"
                        :class="{ 'bs__pricing-card--featured': plan.featured }"
                    >
                        <div v-if="plan.featured" class="bs__pricing-badge">Most Popular</div>
                        <p class="bs__pricing-name">{{ plan.name }}</p>
                        <div class="bs__pricing-price">
                            <span class="bs__pricing-currency">$</span>
                            <span class="bs__pricing-amount">{{ plan.price }}</span>
                            <span class="bs__pricing-period">/mo</span>
                        </div>
                        <p class="bs__pricing-desc">{{ plan.desc }}</p>
                        <ul class="bs__pricing-features">
                            <li v-for="f in plan.features" :key="f" class="bs__pricing-feature">
                                <CheckIcon :size="13" />
                                {{ f }}
                            </li>
                        </ul>
                        <button class="bs__pricing-btn" :class="plan.featured ? 'bs__pricing-btn--featured' : ''">
                            {{ plan.cta }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- Section: Team member cards -->
            <section class="bs__section">
                <h2 class="bs__section-title">Team Cards</h2>
                <div class="bs__grid bs__grid--4">
                    <div v-for="m in team" :key="m.name" class="bs__team-card">
                        <AppAvatar :name="m.name" size="lg" />
                        <div class="bs__team-info">
                            <p class="bs__team-name">{{ m.name }}</p>
                            <p class="bs__team-role">{{ m.role }}</p>
                        </div>
                        <div class="bs__team-social">
                            <button v-for="s in m.socials" :key="s" class="bs__team-social-btn">
                                <component :is="s" :size="14" />
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section: Feature highlight -->
            <section class="bs__section">
                <h2 class="bs__section-title">Feature Highlights</h2>
                <div class="bs__features">
                    <div v-for="f in features" :key="f.title" class="bs__feature">
                        <div class="bs__feature-icon" :style="{ background: f.color + '18' }">
                            <component :is="f.icon" :size="20" :style="{ color: f.color }" />
                        </div>
                        <div>
                            <p class="bs__feature-title">{{ f.title }}</p>
                            <p class="bs__feature-desc">{{ f.desc }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section: Alert / notification blocks -->
            <section class="bs__section">
                <h2 class="bs__section-title">Alert Blocks</h2>
                <div class="bs__alerts">
                    <AppAlert type="success">Your changes have been saved successfully.</AppAlert>
                    <AppAlert type="info">A new version is available. Refresh to update.</AppAlert>
                    <AppAlert type="warning">Your trial expires in 3 days. Upgrade now to keep access.</AppAlert>
                    <AppAlert type="error">Failed to connect. Please check your network and try again.</AppAlert>
                </div>
            </section>

            <!-- Section: Empty states -->
            <section class="bs__section">
                <h2 class="bs__section-title">Empty States</h2>
                <div class="bs__grid bs__grid--3">
                    <AppEmptyState
                        icon="inbox"
                        title="No messages"
                        desc="Your inbox is empty. Compose a message to get started."
                        size="sm"
                    >
                        <template #action>
                            <button class="bs__empty-btn">Compose</button>
                        </template>
                    </AppEmptyState>
                    <AppEmptyState
                        icon="users"
                        title="No contacts yet"
                        desc="Add your first contact to start managing your CRM."
                        size="sm"
                    >
                        <template #action>
                            <button class="bs__empty-btn">Add Contact</button>
                        </template>
                    </AppEmptyState>
                    <AppEmptyState
                        icon="search"
                        title="No results"
                        desc="Try adjusting your search or filter to find what you're looking for."
                        size="sm"
                    />
                </div>
            </section>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import BaseLayout   from '@/Layouts/BaseLayout.vue';
import AppAvatar    from '@/Components/App/AppAvatar.vue';
import AppAlert     from '@/Components/App/AppAlert.vue';
import AppEmptyState from '@/Components/App/AppEmptyState.vue';
import { navGroups } from '@/data/navGroups';
import {
    UsersIcon, MailIcon, DollarSignIcon, TrendingUpIcon, TrendingDownIcon,
    BarChart3Icon, CheckIcon, ZapIcon, ShieldIcon, GlobeIcon,
    LinkIcon, XIcon, CodeIcon, PhoneIcon,
} from '@lucide/vue';

const statCards = [
    { label: 'Total Revenue',  value: '$84k',  trend:  18.2, color: '#6366f1', icon: DollarSignIcon },
    { label: 'Active Contacts',value: '2,481', trend:  4.1,  color: '#10b981', icon: UsersIcon },
    { label: 'Emails Sent',    value: '12.4k', trend: -3.6,  color: '#f59e0b', icon: MailIcon },
    { label: 'Conversion Rate',value: '24.8%', trend:  6.2,  color: '#ec4899', icon: BarChart3Icon },
];

const plans = [
    {
        name: 'Starter', price: '19', desc: 'Perfect for freelancers and small teams.',
        cta: 'Get Started', featured: false,
        features: ['5 team members', '1,000 contacts', 'Basic reporting', 'Email support'],
    },
    {
        name: 'Pro', price: '49', desc: 'Everything you need to scale your CRM.',
        cta: 'Start Free Trial', featured: true,
        features: ['25 team members', '10,000 contacts', 'Advanced analytics', 'Priority support', 'API access'],
    },
    {
        name: 'Enterprise', price: '99', desc: 'Custom solutions for large organizations.',
        cta: 'Contact Sales', featured: false,
        features: ['Unlimited members', 'Unlimited contacts', 'Custom reporting', 'Dedicated manager', 'SSO & SAML'],
    },
];

const team = [
    { name: 'Alice Martin',  role: 'Head of Sales',    socials: [LinkIcon, XIcon] },
    { name: 'Bob Chen',      role: 'Account Manager',  socials: [LinkIcon, CodeIcon] },
    { name: 'Carol Smith',   role: 'Business Dev',     socials: [LinkIcon] },
    { name: 'Dave Wilson',   role: 'Sales Engineer',   socials: [LinkIcon, XIcon, CodeIcon] },
];

const features = [
    { title: 'Lightning Fast', desc: 'Built for performance from the ground up with Vite and Vue 3.', color: '#6366f1', icon: ZapIcon },
    { title: 'Enterprise Security', desc: 'SOC 2 Type II certified with end-to-end encryption.', color: '#10b981', icon: ShieldIcon },
    { title: 'Global Scale', desc: 'Deploy to 200+ regions worldwide with automatic failover.', color: '#f59e0b', icon: GlobeIcon },
    { title: 'Advanced Analytics', desc: 'Real-time dashboards and AI-powered insights.', color: '#ec4899', icon: BarChart3Icon },
    { title: 'Team Collaboration', desc: 'Built-in chat, tasks, and document sharing.', color: '#8b5cf6', icon: UsersIcon },
    { title: 'Integrations', desc: 'Connect with 200+ tools via native integrations and API.', color: '#3b82f6', icon: PhoneIcon },
];
</script>

<style scoped>
.page { padding: 24px; display: flex; flex-direction: column; gap: 32px; }
.page__header { }
.page__title { font-size: 20px; font-weight: 800; color: var(--color-text-primary); font-family: var(--font-heading); letter-spacing: -0.02em; }
.page__sub   { font-size: 13px; color: var(--color-text-muted); margin-top: 2px; }

.bs__section { display: flex; flex-direction: column; gap: 16px; }
.bs__section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--color-text-subtle); }

.bs__grid { display: grid; gap: 16px; }
.bs__grid--3 { grid-template-columns: repeat(3, 1fr); }
.bs__grid--4 { grid-template-columns: repeat(4, 1fr); }

/* Stat cards */
.bs__stat-card {
    background: var(--color-surface); border: 1.5px solid var(--color-border);
    border-radius: 14px; padding: 20px; display: flex; flex-direction: column; gap: 6px;
}
.bs__stat-card-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 4px; }
.bs__stat-card-val   { font-size: 24px; font-weight: 800; color: var(--color-text-primary); font-family: var(--font-heading); letter-spacing: -0.02em; }
.bs__stat-card-label { font-size: 12px; color: var(--color-text-muted); }
.bs__stat-card-trend { display: inline-flex; align-items: center; gap: 3px; font-size: 11.5px; font-weight: 600; margin-top: 4px; }
.bs__trend--up   { color: #10b981; }
.bs__trend--down { color: #ef4444; }

/* Pricing */
.bs__pricing-card {
    position: relative; background: var(--color-surface);
    border: 1.5px solid var(--color-border); border-radius: 16px; padding: 24px;
    display: flex; flex-direction: column; gap: 14px;
}
.bs__pricing-card--featured { border-color: #6366f1; box-shadow: 0 0 0 1px #6366f1; }
.bs__pricing-badge {
    position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
    background: #6366f1; color: #fff; font-size: 11px; font-weight: 700;
    padding: 3px 12px; border-radius: 99px;
}
.bs__pricing-name   { font-size: 14px; font-weight: 700; color: var(--color-text-muted); }
.bs__pricing-price  { display: flex; align-items: flex-start; gap: 2px; }
.bs__pricing-currency { font-size: 18px; font-weight: 700; color: var(--color-text-primary); margin-top: 6px; }
.bs__pricing-amount  { font-size: 40px; font-weight: 900; color: var(--color-text-primary); font-family: var(--font-heading); line-height: 1; }
.bs__pricing-period  { font-size: 13px; color: var(--color-text-muted); align-self: flex-end; margin-bottom: 4px; }
.bs__pricing-desc    { font-size: 13px; color: var(--color-text-muted); line-height: 1.5; }
.bs__pricing-features { list-style: none; display: flex; flex-direction: column; gap: 8px; flex: 1; }
.bs__pricing-feature  { display: flex; align-items: center; gap: 7px; font-size: 13px; color: var(--color-text-muted); }
.bs__pricing-feature svg { color: #10b981; flex-shrink: 0; }
.bs__pricing-btn {
    padding: 10px 20px; border-radius: 9px; font-size: 13.5px; font-weight: 600;
    font-family: var(--font-sans); cursor: pointer; border: 1.5px solid var(--color-border);
    background: transparent; color: var(--color-text-primary); transition: all 150ms ease;
}
.bs__pricing-btn:hover { border-color: #6366f1; color: #6366f1; }
.bs__pricing-btn--featured { background: #6366f1; color: #fff; border-color: #6366f1; }
.bs__pricing-btn--featured:hover { background: #4f46e5; }

/* Team */
.bs__team-card {
    background: var(--color-surface); border: 1.5px solid var(--color-border);
    border-radius: 14px; padding: 20px; display: flex; flex-direction: column; align-items: center; gap: 10px;
    text-align: center;
}
.bs__team-info { display: flex; flex-direction: column; gap: 2px; }
.bs__team-name { font-size: 13.5px; font-weight: 700; color: var(--color-text-primary); margin: 0; }
.bs__team-role { font-size: 12px; color: var(--color-text-muted); margin: 0; }
.bs__team-social { display: flex; gap: 6px; }
.bs__team-social-btn {
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 7px; border: 1px solid var(--color-border);
    background: transparent; cursor: pointer; color: var(--color-text-muted);
    transition: all 120ms ease;
}
.bs__team-social-btn:hover { color: #6366f1; border-color: #6366f1; }

/* Features */
.bs__features {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
}
.bs__feature {
    display: flex; align-items: flex-start; gap: 14px;
    background: var(--color-surface); border: 1.5px solid var(--color-border);
    border-radius: 12px; padding: 16px;
}
.bs__feature-icon {
    width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.bs__feature-title { font-size: 13.5px; font-weight: 700; color: var(--color-text-primary); margin: 0 0 4px; }
.bs__feature-desc  { font-size: 12.5px; color: var(--color-text-muted); line-height: 1.5; margin: 0; }

/* Alerts */
.bs__alerts { display: flex; flex-direction: column; gap: 10px; }

/* Empty states */
.bs__empty-btn {
    padding: 7px 18px; border-radius: 8px; border: none;
    background: #6366f1; color: #fff; font-size: 13px; font-weight: 600;
    font-family: var(--font-sans); cursor: pointer; transition: background 150ms ease;
}
.bs__empty-btn:hover { background: #4f46e5; }

@media (max-width: 1024px) {
    .bs__grid--4 { grid-template-columns: repeat(2, 1fr); }
    .bs__features { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .bs__grid--3, .bs__grid--4, .bs__features { grid-template-columns: 1fr; }
}
</style>
