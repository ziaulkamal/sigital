<template>
    <div class="kcard" :class="{ 'kcard--dragging': dragging }">
        <!-- Priority + badges -->
        <div class="kcard__top">
            <span class="kcard__priority" :style="{ background: priorityColors[priority] + '20', color: priorityColors[priority] }">
                {{ priority }}
            </span>
            <AppBadge v-if="tag" :color="tagColor" size="sm">{{ tag }}</AppBadge>
        </div>

        <!-- Title -->
        <p class="kcard__title">{{ title }}</p>

        <!-- Description snippet -->
        <p v-if="desc" class="kcard__desc">{{ desc }}</p>

        <!-- Meta row -->
        <div class="kcard__meta">
            <!-- Due date -->
            <div v-if="dueDate" class="kcard__due" :class="{ 'kcard__due--overdue': isOverdue }">
                <CalendarIcon :size="11" />
                {{ fmtDate(dueDate) }}
            </div>

            <!-- Attachments -->
            <div v-if="attachments" class="kcard__attach">
                <PaperclipIcon :size="11" />
                {{ attachments }}
            </div>

            <!-- Spacer -->
            <div class="kcard__spacer" />

            <!-- Assignees -->
            <div v-if="assignees.length" class="kcard__assignees">
                <AppAvatar
                    v-for="(a, i) in assignees.slice(0, 3)"
                    :key="i"
                    :name="a"
                    size="xs"
                    :style="{ marginLeft: i > 0 ? '-6px' : '0', zIndex: 3 - i }"
                />
                <span v-if="assignees.length > 3" class="kcard__extra">+{{ assignees.length - 3 }}</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { CalendarIcon, PaperclipIcon } from '@lucide/vue';
import AppBadge  from '@/Components/App/AppBadge.vue';
import AppAvatar from '@/Components/App/AppAvatar.vue';

type Priority = 'High' | 'Medium' | 'Low';

const priorityColors: Record<Priority, string> = {
    High:   '#ef4444',
    Medium: '#f59e0b',
    Low:    '#10b981',
};

const props = defineProps({
    title:       { type: String,  default: '' },
    desc:        { type: String,  default: '' },
    priority:    { type: String as () => Priority, default: 'Medium' },
    tag:         { type: String,  default: '' },
    tagColor:    { type: String,  default: 'primary' },
    dueDate:     { type: String,  default: '' },
    assignees:   { type: Array as () => string[], default: () => [] },
    attachments: { type: Number,  default: 0 },
    dragging:    { type: Boolean, default: false },
});

const today = new Date();
today.setHours(0,0,0,0);

const isOverdue = computed(() => {
    if (!props.dueDate) return false;
    return new Date(props.dueDate) < today;
});

function fmtDate(d: string): string {
    const date = new Date(d);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
</script>

<style scoped>
.kcard {
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: 10px; padding: 12px 14px;
    display: flex; flex-direction: column; gap: 8px;
    cursor: grab; transition: box-shadow 150ms ease, transform 150ms ease;
    user-select: none;
}
.kcard:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
.kcard--dragging {
    box-shadow: 0 10px 40px rgba(0,0,0,0.18);
    transform: rotate(2deg); opacity: 0.9; cursor: grabbing;
}

.kcard__top { display: flex; align-items: center; gap: 6px; }
.kcard__priority {
    font-size: 10.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.07em; padding: 2px 7px; border-radius: 99px;
}

.kcard__title { font-size: 13px; font-weight: 600; color: var(--color-text-primary); line-height: 1.4; margin: 0; }
.kcard__desc  { font-size: 11.5px; color: var(--color-text-muted); line-height: 1.5; margin: 0; }

.kcard__meta { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.kcard__due {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; color: var(--color-text-subtle);
}
.kcard__due--overdue { color: #ef4444; }
.kcard__attach {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; color: var(--color-text-subtle);
}
.kcard__spacer { flex: 1; }
.kcard__assignees { display: flex; align-items: center; position: relative; }
.kcard__extra {
    font-size: 10px; font-weight: 700; color: var(--color-text-muted);
    margin-left: 4px;
}
</style>
