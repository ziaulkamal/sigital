<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <div class="page__header">
                <h1 class="page__title">Settings</h1>
                <p class="page__sub">Manage your account and preferences</p>
            </div>

            <AppTabs :tabs="tabs">
                <template #profile>
                    <div class="stg__section">
                        <h3 class="stg__section-title">Profile Information</h3>
                        <div class="stg__form">
                            <div class="stg__avatar-row">
                                <AppAvatar name="Ziaul Kamal" size="lg" />
                                <div>
                                    <p class="stg__avatar-name">Ziaul Kamal</p>
                                    <p class="stg__avatar-email">ziaulkamal1109@gmail.com</p>
                                    <button class="stg__change-photo">Change photo</button>
                                </div>
                            </div>
                            <AppDivider />
                            <div class="stg__field-grid">
                                <div class="stg__field">
                                    <label class="stg__label">First Name</label>
                                    <AppInput v-model="profile.firstName" placeholder="First name" />
                                </div>
                                <div class="stg__field">
                                    <label class="stg__label">Last Name</label>
                                    <AppInput v-model="profile.lastName" placeholder="Last name" />
                                </div>
                                <div class="stg__field stg__field--full">
                                    <label class="stg__label">Email Address</label>
                                    <AppInput v-model="profile.email" type="email" placeholder="email@example.com" />
                                </div>
                                <div class="stg__field stg__field--full">
                                    <label class="stg__label">Bio</label>
                                    <AppTextarea v-model="profile.bio" placeholder="Tell us about yourself…" :rows="3" />
                                </div>
                            </div>
                            <div class="stg__actions">
                                <AppButton variant="primary">Save Changes</AppButton>
                                <AppButton variant="ghost">Cancel</AppButton>
                            </div>
                        </div>
                    </div>
                </template>

                <template #notifications>
                    <div class="stg__section">
                        <h3 class="stg__section-title">Notification Preferences</h3>
                        <div class="stg__notif-list">
                            <div v-for="n in notifications" :key="n.key" class="stg__notif-item">
                                <div>
                                    <p class="stg__notif-label">{{ n.label }}</p>
                                    <p class="stg__notif-desc">{{ n.desc }}</p>
                                </div>
                                <AppToggle v-model="n.enabled" />
                            </div>
                        </div>
                    </div>
                </template>

                <template #security>
                    <div class="stg__section">
                        <h3 class="stg__section-title">Change Password</h3>
                        <div class="stg__form">
                            <div class="stg__field">
                                <label class="stg__label">Current Password</label>
                                <AppInput v-model="passwords.current" type="password" placeholder="••••••••" />
                            </div>
                            <div class="stg__field">
                                <label class="stg__label">New Password</label>
                                <AppInput v-model="passwords.new" type="password" placeholder="••••••••" />
                            </div>
                            <div class="stg__field">
                                <label class="stg__label">Confirm New Password</label>
                                <AppInput v-model="passwords.confirm" type="password" placeholder="••••••••" />
                            </div>
                            <div class="stg__actions">
                                <AppButton variant="primary">Update Password</AppButton>
                            </div>
                        </div>
                        <AppDivider label="Danger Zone" />
                        <div class="stg__danger">
                            <div>
                                <p class="stg__danger-title">Delete Account</p>
                                <p class="stg__danger-desc">Permanently delete your account and all associated data.</p>
                            </div>
                            <AppButton variant="danger">Delete Account</AppButton>
                        </div>
                    </div>
                </template>

                <template #appearance>
                    <div class="stg__section">
                        <h3 class="stg__section-title">Appearance</h3>
                        <div class="stg__appear-grid">
                            <div
                                v-for="theme in themes"
                                :key="theme.id"
                                class="stg__theme-card"
                                :class="{ 'stg__theme-card--active': activeTheme === theme.id }"
                                @click="activeTheme = theme.id"
                            >
                                <div class="stg__theme-preview" :style="{ background: theme.bg }">
                                    <div class="stg__theme-sidebar" :style="{ background: theme.sidebar }" />
                                </div>
                                <p class="stg__theme-label">{{ theme.label }}</p>
                            </div>
                        </div>
                    </div>
                </template>
            </AppTabs>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import BaseLayout  from '@/Layouts/BaseLayout.vue';
import AppTabs     from '@/Components/App/AppTabs.vue';
import AppInput    from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppButton   from '@/Components/App/AppButton.vue';
import AppToggle   from '@/Components/App/AppToggle.vue';
import AppAvatar   from '@/Components/App/AppAvatar.vue';
import AppDivider  from '@/Components/App/AppDivider.vue';
import { navGroups } from '@/data/navGroups';
import { BellIcon, UserIcon, ShieldIcon, PaletteIcon } from '@lucide/vue';

const tabs = [
    { key: 'profile',       label: 'Profile',       icon: UserIcon },
    { key: 'notifications', label: 'Notifications', icon: BellIcon },
    { key: 'security',      label: 'Security',      icon: ShieldIcon },
    { key: 'appearance',    label: 'Appearance',    icon: PaletteIcon },
];

const profile = ref({ firstName: 'Ziaul', lastName: 'Kamal', email: 'ziaulkamal1109@gmail.com', bio: '' });
const passwords = ref({ current: '', new: '', confirm: '' });
const activeTheme = ref('light');

const notifications = ref([
    { key: 'new_contact',  label: 'New Contact',       desc: 'Get notified when a new contact is added.', enabled: true },
    { key: 'deal_closed',  label: 'Deal Closed',        desc: 'Get notified when a deal is closed.',       enabled: true },
    { key: 'email_reply',  label: 'Email Replies',      desc: 'Get notified when you receive a reply.',    enabled: false },
    { key: 'weekly_report',label: 'Weekly Report',      desc: 'Receive a weekly performance summary.',     enabled: true },
    { key: 'marketing',    label: 'Marketing Updates',  desc: 'News and feature announcements.',           enabled: false },
]);

const themes = [
    { id: 'light', label: 'Light',      bg: '#f8fafc', sidebar: '#fff' },
    { id: 'dark',  label: 'Dark',       bg: '#0f172a', sidebar: '#1e293b' },
    { id: 'system',label: 'System',     bg: 'linear-gradient(135deg, #f8fafc 50%, #0f172a 50%)', sidebar: '#6366f1' },
];
</script>

<style scoped>
.page { padding: 24px; display: flex; flex-direction: column; gap: 20px; max-width: 800px; }
.page__header { }
.page__title { font-size: 20px; font-weight: 800; color: var(--color-text-primary); font-family: var(--font-heading); letter-spacing: -0.02em; }
.page__sub   { font-size: 13px; color: var(--color-text-muted); margin-top: 2px; }

.stg__section { display: flex; flex-direction: column; gap: 20px; }
.stg__section-title { font-size: 14px; font-weight: 700; color: var(--color-text-primary); margin: 0; }

.stg__form { display: flex; flex-direction: column; gap: 16px; }
.stg__avatar-row { display: flex; align-items: center; gap: 16px; }
.stg__avatar-name  { font-size: 15px; font-weight: 700; color: var(--color-text-primary); margin: 0; }
.stg__avatar-email { font-size: 12.5px; color: var(--color-text-muted); margin: 2px 0 8px; }
.stg__change-photo {
    font-size: 12px; color: #6366f1; font-weight: 500; border: none;
    background: transparent; cursor: pointer; font-family: var(--font-sans); padding: 0;
}
.stg__change-photo:hover { text-decoration: underline; }

.stg__field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.stg__field { display: flex; flex-direction: column; gap: 6px; }
.stg__field--full { grid-column: 1 / -1; }
.stg__label { font-size: 12px; font-weight: 600; color: var(--color-text-muted); }

.stg__actions { display: flex; gap: 10px; }

.stg__notif-list { display: flex; flex-direction: column; gap: 0; }
.stg__notif-item {
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    padding: 14px 0; border-bottom: 1px solid var(--color-border);
}
.stg__notif-item:last-child { border-bottom: none; }
.stg__notif-label { font-size: 13px; font-weight: 600; color: var(--color-text-primary); margin: 0; }
.stg__notif-desc  { font-size: 12px; color: var(--color-text-muted); margin: 2px 0 0; }

.stg__danger {
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    padding: 16px; border-radius: 10px;
    border: 1.5px solid rgba(239,68,68,0.3); background: rgba(239,68,68,0.04);
}
.stg__danger-title { font-size: 13px; font-weight: 600; color: #ef4444; margin: 0; }
.stg__danger-desc  { font-size: 12px; color: var(--color-text-muted); margin: 2px 0 0; }

.stg__appear-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.stg__theme-card {
    display: flex; flex-direction: column; gap: 8px; cursor: pointer;
    border: 2px solid var(--color-border); border-radius: 10px;
    padding: 8px; transition: border-color 150ms ease;
}
.stg__theme-card:hover { border-color: color-mix(in srgb, #6366f1 40%, var(--color-border)); }
.stg__theme-card--active { border-color: #6366f1; }
.stg__theme-preview {
    height: 70px; border-radius: 6px; overflow: hidden; display: flex;
}
.stg__theme-sidebar { width: 25%; height: 100%; }
.stg__theme-label { font-size: 12px; font-weight: 600; color: var(--color-text-primary); text-align: center; }
</style>
