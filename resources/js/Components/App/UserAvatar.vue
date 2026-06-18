<template>
    <div class="relative shrink-0" :style="wrapStyle">
        <!-- Avatar circle -->
        <div
            class="rounded-full flex items-center justify-center text-white font-semibold overflow-hidden"
            :style="avatarStyle"
        >
            <img
                v-if="user?.avatar"
                :src="user.avatar"
                :alt="user?.name"
                class="w-full h-full object-cover"
            />
            <span v-else :style="{ fontSize: s.font }">{{ initials }}</span>
        </div>

        <!-- Online dot — positioned bottom-right, border matches parent bg -->
        <span
            v-if="showDot"
            class="absolute rounded-full"
            :style="{
                width: s.dot,
                height: s.dot,
                bottom: '-1px',
                right: '-1px',
                backgroundColor: '#22c55e',   /* green-500 */
                border: `2px solid ${borderBg}`,
                boxShadow: '0 0 0 1px rgba(34,197,94,0.3)',
            }"
        />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

type AvatarUser = { name?: string; avatar?: string } | null;

type SizeEntry = { px: string; font: string; dot: string };

const props = defineProps({
    user:     { type: Object as () => AvatarUser, default: null },
    size:     { type: String,  default: 'md' },   // xs | sm | md | lg
    showDot:  { type: Boolean, default: true },
    color:    { type: String,  default: '#6366f1' },
    borderBg: { type: String,  default: '#f0f1f8' }, // harus match bg parent
});

const sizeMap: Record<string, SizeEntry> = {
    xs: { px: '24px', font: '10px', dot: '8px'  },
    sm: { px: '32px', font: '12px', dot: '10px' },
    md: { px: '38px', font: '14px', dot: '12px' },
    lg: { px: '48px', font: '17px', dot: '13px' },
};

const s = computed<SizeEntry>(() => sizeMap[props.size] ?? sizeMap.md);

const initials = computed<string>(() => {
    const name = props.user?.name ?? 'U';
    return name.split(' ').map((n: string) => n[0]).slice(0, 2).join('').toUpperCase();
});

const wrapStyle   = computed(() => ({ width: s.value.px, height: s.value.px }));
const avatarStyle = computed(() => ({
    width:      s.value.px,
    height:     s.value.px,
    background: `linear-gradient(135deg, ${props.color}, ${lighten(props.color)})`,
    fontSize:   s.value.font,
}));

function lighten(hex: string): string {
    const map: Record<string, string> = {
        '#6366f1': '#8b5cf6',
        '#10b981': '#34d399',
        '#f59e0b': '#fbbf24',
        '#ef4444': '#f87171',
    };
    return map[hex] ?? '#a5b4fc';
}
</script>
