<template>
    <div class="feed">
        <div class="feed__head">
            <p class="feed__title">Activity Feed</p>
            <button type="button" class="feed__see-all">See all</button>
        </div>

        <div class="feed__list">
            <div v-for="(item, i) in activities" :key="i" class="feed__item">
                <!-- Timeline connector -->
                <div class="feed__connector">
                    <div class="feed__avatar-wrap">
                        <AppAvatar :name="item.user" size="sm" />
                        <span class="feed__type-dot" :style="{ background: item.color }">
                            <component :is="item.icon" :size="8" style="color:white" />
                        </span>
                    </div>
                    <div v-if="i < activities.length - 1" class="feed__line" />
                </div>

                <!-- Content -->
                <div class="feed__content">
                    <p class="feed__text">
                        <span class="feed__user">{{ item.user }}</span>
                        {{ item.action }}
                        <span v-if="item.target" class="feed__target">{{ item.target }}</span>
                    </p>
                    <p class="feed__time">{{ item.time }}</p>
                    <div v-if="item.note" class="feed__note">{{ item.note }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { PhoneIcon, MailIcon, FileTextIcon, StarIcon, UserPlusIcon, CheckCircleIcon } from '@lucide/vue';
import AppAvatar from '@/Components/App/AppAvatar.vue';

const activities = [
    { user: 'Alice Martin',  action: 'closed a deal with', target: 'Acme Corp',      icon: CheckCircleIcon, color: '#10b981', time: '2m ago',   note: '$24,000 — Enterprise plan' },
    { user: 'Bob Chen',      action: 'added a new contact',target: 'Sarah Johnson',  icon: UserPlusIcon,   color: '#6366f1', time: '18m ago',  note: null },
    { user: 'Carol Smith',   action: 'sent a proposal to', target: 'Beta Ltd',       icon: FileTextIcon,   color: '#f59e0b', time: '45m ago',  note: null },
    { user: 'Dave Wilson',   action: 'called',             target: 'James Brown',    icon: PhoneIcon,      color: '#3b82f6', time: '1h ago',   note: '12 min call — follow up scheduled' },
    { user: 'Alice Martin',  action: 'emailed',            target: 'Gamma Inc',      icon: MailIcon,       color: '#8b5cf6', time: '2h ago',   note: null },
    { user: 'Eve Johnson',   action: 'marked as priority', target: 'Delta Systems',  icon: StarIcon,       color: '#f97316', time: '3h ago',   note: null },
];
</script>

<style scoped>
.feed {
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: 16px;
    padding: 22px;
    display: flex; flex-direction: column; gap: 18px;
    height: 100%;
}
.feed__head { display: flex; align-items: center; justify-content: space-between; }
.feed__title { font-size: 14px; font-weight: 700; color: var(--color-text-primary); letter-spacing: -0.01em; }
.feed__see-all {
    font-size: 12px; font-weight: 500; color: #6366f1;
    border: none; background: transparent; cursor: pointer;
    font-family: var(--font-sans); padding: 0;
}
.feed__see-all:hover { text-decoration: underline; }

.feed__list { display: flex; flex-direction: column; overflow-y: auto; }

.feed__item { display: flex; gap: 12px; }

.feed__connector { display: flex; flex-direction: column; align-items: center; flex-shrink: 0; width: 32px; }
.feed__avatar-wrap { position: relative; flex-shrink: 0; }
.feed__type-dot {
    position: absolute; bottom: -2px; right: -2px;
    width: 14px; height: 14px; border-radius: 50%;
    border: 2px solid var(--color-surface);
    display: flex; align-items: center; justify-content: center;
}
.feed__line { flex: 1; width: 1.5px; background: var(--color-border); margin: 4px 0; min-height: 14px; }

.feed__content { flex: 1; padding-bottom: 18px; min-width: 0; }
.feed__text  { font-size: 12.5px; color: var(--color-text-muted); line-height: 1.5; margin: 0; }
.feed__user  { font-weight: 600; color: var(--color-text-primary); }
.feed__target{ font-weight: 500; color: #6366f1; }
.feed__time  { font-size: 11px; color: var(--color-text-subtle); margin-top: 2px; }
.feed__note  {
    margin-top: 6px; padding: 7px 10px;
    background: var(--color-bg-subtle);
    border-left: 2.5px solid var(--color-border);
    border-radius: 0 6px 6px 0;
    font-size: 12px; color: var(--color-text-muted);
    line-height: 1.4;
}
</style>
