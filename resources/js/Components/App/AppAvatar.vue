<template>
    <div class="app-avatar-group" v-if="group">
        <div
            v-for="(u, i) in displayUsers"
            :key="i"
            class="app-avatar app-avatar--grouped"
            :class="`app-avatar--${size}`"
            :style="{ background: getGradient(u), zIndex: displayUsers.length - i, marginLeft: i === 0 ? '0' : overlapMap[size] }"
            :title="u.name || u"
        >
            <img v-if="u.avatar" :src="u.avatar" :alt="u.name" class="app-avatar__img" />
            <span v-else class="app-avatar__initials">{{ initials(u.name || u) }}</span>
        </div>
        <div v-if="overflow > 0" class="app-avatar app-avatar--grouped app-avatar--overflow" :class="`app-avatar--${size}`" :style="{ marginLeft: overlapMap[size] }">
            +{{ overflow }}
        </div>
    </div>

    <div v-else class="app-avatar" :class="`app-avatar--${size}`" :style="avatarStyle">
        <img v-if="user?.avatar || src" :src="user?.avatar || src" :alt="user?.name || alt" class="app-avatar__img" />
        <span v-else class="app-avatar__initials">{{ displayInitials }}</span>
        <span v-if="showDot" class="app-avatar__dot" :style="dotStyle" />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

type AvatarUser = { name?: string; avatar?: string; color?: string } | null;
type GroupUser  = { name?: string; avatar?: string; color?: string } | string;

const props = defineProps({
    user:      { type: Object as () => AvatarUser, default: null },
    src:       { type: String,  default: '' },
    alt:       { type: String,  default: '' },
    name:      { type: String,  default: '' },
    size:      { type: String,  default: 'md' }, // xs|sm|md|lg|xl
    color:     { type: String,  default: '' },
    showDot:   { type: Boolean, default: false },
    dotColor:  { type: String,  default: '#22c55e' },
    borderBg:  { type: String,  default: 'var(--color-surface)' },
    // Group mode
    group:     { type: Boolean, default: false },
    users:     { type: Array as () => Array<string | { name?: string; avatar?: string; color?: string }>, default: () => [] },
    max:       { type: Number,  default: 4 },
});

const gradients: string[] = [
    'linear-gradient(135deg,#6366f1,#8b5cf6)',
    'linear-gradient(135deg,#10b981,#059669)',
    'linear-gradient(135deg,#f59e0b,#f97316)',
    'linear-gradient(135deg,#ec4899,#f43f5e)',
    'linear-gradient(135deg,#3b82f6,#6366f1)',
    'linear-gradient(135deg,#14b8a6,#10b981)',
];

const overlapMap: Record<string, string> = { xs: '-6px', sm: '-8px', md: '-10px', lg: '-12px', xl: '-14px' };

function getGradient(u: GroupUser | AvatarUser): string {
    if (u && typeof u === 'object' && u.color) return u.color;
    const name = (u && typeof u === 'object' ? u.name : u) || '';
    return gradients[(name as string).charCodeAt(0) % gradients.length];
}

function initials(name: string): string {
    if (!name) return '?';
    const parts = String(name).trim().split(' ');
    return parts.length >= 2
        ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
        : String(name).slice(0, 2).toUpperCase();
}

const displayInitials = computed<string>(() => {
    const n = props.user?.name || props.name || props.alt;
    return initials(n);
});

const avatarStyle = computed(() => ({
    background: props.color || getGradient(props.user || props.name),
}));

const dotStyle = computed(() => ({
    backgroundColor: props.dotColor,
    border: `2px solid ${props.borderBg}`,
    boxShadow: `0 0 0 1px ${props.dotColor}44`,
}));

const displayUsers = computed(() => props.users.slice(0, props.max));
const overflow = computed<number>(() => Math.max(0, props.users.length - props.max));
</script>

<style scoped>
.app-avatar {
    position: relative; flex-shrink: 0; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 600; color: white; overflow: hidden;
}

.app-avatar--xs { width: 24px; height: 24px; font-size: 9px; }
.app-avatar--sm { width: 30px; height: 30px; font-size: 11px; }
.app-avatar--md { width: 36px; height: 36px; font-size: 13px; }
.app-avatar--lg { width: 44px; height: 44px; font-size: 16px; }
.app-avatar--xl { width: 56px; height: 56px; font-size: 20px; }

.app-avatar__img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.app-avatar__initials { line-height: 1; }

.app-avatar__dot {
    position: absolute; bottom: 0; right: 0;
    width: 10px; height: 10px; border-radius: 50%;
}
.app-avatar--xs .app-avatar__dot { width: 7px; height: 7px; }
.app-avatar--sm .app-avatar__dot { width: 8px; height: 8px; }
.app-avatar--lg .app-avatar__dot { width: 12px; height: 12px; }
.app-avatar--xl .app-avatar__dot { width: 14px; height: 14px; }

/* Group */
.app-avatar-group { display: flex; align-items: center; }
.app-avatar--grouped {
    border: 2px solid var(--color-surface);
    cursor: default;
}
.app-avatar--overflow {
    background: var(--color-bg-subtle);
    color: var(--color-text-muted);
    font-size: 11px;
    font-weight: 600;
}
</style>
