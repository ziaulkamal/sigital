<template>
    <!-- ── SINGLE item ── -->
    <template v-if="!hasChildren">
        <component
            :is="item.href ? Link : 'button'"
            :href="item.href ?? undefined"
            class="nav-item"
            :class="[
                itemDepthClass,
                isActive ? 'nav-item--active' : 'nav-item--default',
                collapsed && depth === 0 ? 'nav-item--icon-only' : '',
            ]"
            :style="isActive && depth === 0 ? activeItemStyle : ''"
            :title="collapsed && depth === 0 ? item.label : ''"
            @click="item.onClick ? item.onClick() : undefined"
        >
            <!-- Icon (root only) -->
            <span
                v-if="depth === 0"
                class="nav-icon"
                :class="collapsed && depth === 0 ? 'nav-icon--collapsed' : ''"
                :style="isActive && depth === 0 ? 'color: #fff;' : iconColorStyle"
            >
                <component :is="item.icon" :size="iconSize" :stroke-width="1.8" />
            </span>

            <!-- Label -->
            <Transition name="slide-label">
                <span v-if="showLabels" class="flex-1 min-w-0 truncate text-left">
                    {{ item.label }}
                    <span v-if="item.subtitle" class="block text-[11px] mt-0.5 font-normal" :style="{ color: isActive ? 'rgba(255,255,255,0.75)' : '#9ca3af' }">
                        {{ item.subtitle }}
                    </span>
                </span>
            </Transition>

            <!-- Badge -->
            <Transition name="slide-label">
                <span
                    v-if="showLabels && item.badge"
                    class="nav-badge"
                    :style="{ backgroundColor: item.badgeColor ?? accentColor }"
                >
                    {{ item.badge }}
                </span>
            </Transition>
        </component>
    </template>

    <!-- ── DROPDOWN item (has children) ── -->
    <template v-else>
        <!-- Dropdown trigger -->
        <button
            class="nav-item"
            :class="[
                itemDepthClass,
                isOpen || hasActiveChild ? 'nav-item--open' : 'nav-item--default',
                collapsed && depth === 0 ? 'nav-item--icon-only' : '',
            ]"
            :title="collapsed && depth === 0 ? item.label : ''"
            :style="hasActiveChild && depth === 0 && !showLabels ? activeItemStyle : ''"
            @click="onDropdownClick"
        >
            <!-- Icon (root only) -->
            <span
                v-if="depth === 0"
                class="nav-icon"
                :class="collapsed && depth === 0 ? 'nav-icon--collapsed' : ''"
                :style="(hasActiveChild && depth === 0 && !showLabels) ? 'color: #fff' : iconColorStyle"
            >
                <component :is="item.icon" :size="iconSize" :stroke-width="1.8" />
            </span>

            <!-- Label -->
            <Transition name="slide-label">
                <span v-if="showLabels" class="flex-1 min-w-0 truncate text-left">{{ item.label }}</span>
            </Transition>

            <!-- Badge -->
            <Transition name="slide-label">
                <span v-if="showLabels && item.badge" class="nav-badge" :style="{ backgroundColor: item.badgeColor ?? accentColor }">
                    {{ item.badge }}
                </span>
            </Transition>

            <!-- Chevron arrow -->
            <Transition name="slide-label">
                <ChevronRight
                    v-if="showLabels"
                    :size="14"
                    stroke-width="2.5"
                    class="shrink-0 transition-transform duration-200"
                    :class="isOpen ? 'rotate-90' : ''"
                    style="color: #9ca3af;"
                />
            </Transition>
        </button>

        <!-- Children (accordion) -->
        <Transition name="accordion">
            <div v-if="isOpen && showLabels" class="overflow-hidden">
                <div class="py-0.5 space-y-0.5">
                    <NavItem
                        v-for="child in item.children"
                        :key="child.label"
                        :item="child"
                        :show-labels="showLabels"
                        :collapsed="collapsed"
                        :open-keys="openKeys"
                        :depth="depth + 1"
                        :accent-color="accentColor"
                        :text-primary="textPrimary"
                        :text-muted="textMuted"
                        :current-path="currentPath"
                        @toggle="$emit('toggle', $event)"
                        @expand-sidebar="$emit('expand-sidebar')"
                    />
                </div>
            </div>
        </Transition>
    </template>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';

interface NavItem {
    label: string;
    icon?: unknown;
    href?: string;
    badge?: string | number;
    badgeColor?: string;
    subtitle?: string;
    active?: boolean;
    onClick?: () => void;
    children?: NavItem[];
}

const props = defineProps({
    item:        { type: Object as () => NavItem, required: true },
    showLabels:  { type: Boolean, default: true },
    collapsed:   { type: Boolean, default: false },
    openKeys:    { type: Object as () => Set<string>, required: true },
    depth:       { type: Number,  default: 0 },
    accentColor: { type: String,  default: '#6366f1' },
    textPrimary: { type: String,  default: '#1e1b4b' },
    textMuted:   { type: String,  default: '#6b7280' },
    currentPath: { type: String,  default: '' },
});

const emit = defineEmits(['toggle', 'expand-sidebar']);

// Unique key for this dropdown item
const itemKey = computed<string>(() => `${props.depth}-${props.item.label}`);

// Active state: utamakan flag `active` yang sudah dihitung induk (AppSidebar pakai
// kecocokan href TERPANJANG agar sub-rute tak mengaktifkan induk). Fallback ke
// pencocokan batas-segmen ('/') untuk item anak yang tak menerima flag.
const isActive = computed<boolean>(() => {
    if (props.item.active !== undefined) return !!props.item.active;
    if (!props.currentPath || !props.item.href) return false;
    const path = props.item.href;
    return props.currentPath === path || (path !== '/' && props.currentPath.startsWith(path + '/'));
});

const hasChildren = computed<boolean>(() => Array.isArray(props.item.children) && props.item.children!.length > 0);

const isOpen = computed<boolean>(() => props.openKeys.has(itemKey.value));

const hasActiveChild = computed<boolean>(() => {
    if (!hasChildren.value) return false;
    return checkActiveChild(props.item.children!);
});

function checkActiveChild(children: NavItem[]): boolean {
    for (const child of children) {
        if (child.active) return true;
        if (child.children && checkActiveChild(child.children)) return true;
    }
    return false;
}

function onDropdownClick(): void {
    if (props.collapsed && props.depth === 0) {
        emit('expand-sidebar');
        return;
    }
    emit('toggle', itemKey.value);
}

// Sizing by depth
const iconSize = computed<number>(() => props.depth === 0 ? 18 : 16);

const itemDepthClass = computed<string>(() => {
    if (props.depth === 0) return 'nav-item--root';
    if (props.depth === 1) return 'nav-item--child';
    return 'nav-item--grandchild';
});

const activeItemStyle = computed(() => ({
    background: `linear-gradient(135deg, ${props.accentColor} 0%, ${lighten(props.accentColor)} 100%)`,
    color: '#ffffff',
    boxShadow: `0 4px 12px ${props.accentColor}40`,
}));

const iconColorStyle = computed(() => ({
    color: isActive.value || (hasActiveChild.value && !props.showLabels)
        ? props.accentColor
        : '#94a3b8',
}));

function lighten(hex: string): string {
    // Simple color shift for gradient end
    const map: Record<string, string> = {
        '#6366f1': '#8b5cf6',
        '#10b981': '#34d399',
        '#f59e0b': '#fbbf24',
        '#ef4444': '#f87171',
        '#3b82f6': '#60a5fa',
        '#ec4899': '#f472b6',
    };
    return map[hex] ?? hex + 'cc';
}
</script>

<style scoped>
/* ── Base nav item ── */
.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    border: none;
    background: transparent;
    cursor: pointer;
    text-decoration: none;
    border-radius: 10px;
    font-size: 0.8125rem;
    font-weight: 500;
    transition: background-color 130ms ease, box-shadow 130ms ease, color 130ms ease;
    white-space: nowrap;
}

/* Root depth (0) */
.nav-item--root {
    padding: 8px 10px;
    color: #475569;
}
/* Child depth (1) */
.nav-item--child {
    padding: 6px 10px 6px 36px;
    font-size: 0.8rem;
    color: #64748b;
    border-radius: 8px;
}
/* Grandchild depth (2) */
.nav-item--grandchild {
    padding: 5px 10px 5px 52px;
    font-size: 0.775rem;
    color: #64748b;
    border-radius: 7px;
}

/* States */
.nav-item--default:hover,
.nav-item--open:hover {
    background-color: rgba(99,102,241,0.09);
    color: var(--color-text-primary);
}

.nav-item--open {
    color: var(--color-text-primary);
    background-color: rgba(99,102,241,0.07);
}

/* Icon-only (collapsed root) */
.nav-item--icon-only {
    width: 44px;
    height: 44px;
    padding: 0;
    border-radius: 12px;
    justify-content: center;
    gap: 0;
    margin: 0 auto;
}

/* Icons */
.nav-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    transition: color 130ms ease;
}

.nav-icon--collapsed {
    width: 22px;
    height: 22px;
}

/* Badge */
.nav-badge {
    flex-shrink: 0;
    padding: 2px 7px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    color: #fff;
    line-height: 1.4;
    letter-spacing: 0.02em;
}

/* ── Accordion transition ── */
.accordion-enter-active,
.accordion-leave-active {
    transition: max-height 280ms cubic-bezier(.4,0,.2,1), opacity 220ms ease;
    max-height: 500px;
    overflow: hidden;
}
.accordion-enter-from,
.accordion-leave-to {
    max-height: 0;
    opacity: 0;
}

/* ── Label slide transition ── */
.slide-label-enter-active,
.slide-label-leave-active {
    transition: opacity 140ms ease, max-width 250ms cubic-bezier(.4,0,.2,1);
    overflow: hidden;
    max-width: 200px;
}
.slide-label-enter-from,
.slide-label-leave-to { opacity: 0; max-width: 0; }
</style>
