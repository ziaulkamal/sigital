<template>
    <!-- ── Mobile Overlay ── -->
    <Transition name="overlay">
        <div
            v-if="mobileOpen && isMobile"
            class="fixed inset-0 z-20 bg-black/50 backdrop-blur-sm"
            @click="$emit('update:mobileOpen', false)"
        />
    </Transition>

    <!-- ── Sidebar ── -->
    <aside
        :class="[
            'app-sidebar flex flex-col transition-all duration-300 ease-out select-none',
            isMobile
                ? (mobileOpen ? 'app-sidebar--mobile-open' : 'app-sidebar--mobile-closed')
                : '',
        ]"
        :style="[sidebarStyle, { width: (collapsed && !isMobile ? SIDEBAR_W_COLLAPSED : SIDEBAR_W_EXPANDED) + 'px' }]"
    >
        <!-- Aurora overlay (dark mode only) -->
        <div v-if="isDark" class="sidebar-aurora" aria-hidden="true">
            <div class="sidebar-aurora__blob sidebar-aurora__blob--1" />
            <div class="sidebar-aurora__blob sidebar-aurora__blob--2" />
            <div class="sidebar-aurora__blob sidebar-aurora__blob--3" />
        </div>

        <!-- ── Logo / Brand ── -->
        <div class="flex items-center h-16 shrink-0 px-3 gap-3">
            <div class="sidebar-logo shrink-0">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M10 2L18 6.5V13.5L10 18L2 13.5V6.5L10 2Z" fill="white" fill-opacity="0.95"/>
                    <circle cx="10" cy="10" r="3" fill="white" fill-opacity="0.5"/>
                </svg>
            </div>
            <Transition name="slide-label">
                <div v-if="showLabels" class="flex-1 min-w-0">
                    <div class="text-sm font-bold truncate" :style="{ color: textPrimary }">{{ appName }}</div>
                    <div class="text-xs truncate" :style="{ color: textMuted }">{{ appSubtitle }}</div>
                </div>
            </Transition>
        </div>

        <!-- ── Nav ── -->
        <nav class="flex-1 overflow-y-auto overflow-x-hidden py-2" :class="showLabels ? 'px-3' : 'px-2'">

            <template v-for="(section, si) in navGroups" :key="si">

                <!-- Separator between sections in collapsed mode -->
                <div v-if="!showLabels && si > 0" class="my-2" />

                <!-- Section card wrapper (collapsed) or plain (expanded) -->
                <div
                    :class="[
                        !showLabels ? 'sidebar-icon-group' : '',
                        'space-y-0.5',
                    ]"
                    :style="!showLabels ? iconGroupStyle : ''"
                >
                    <!-- Section heading (expanded only) -->
                    <Transition name="slide-label">
                        <div v-if="showLabels && section.label" class="px-2 pt-4 pb-1">
                            <!-- Label: uppercase bold kecil -->
                            <p
                                class="text-[10.5px] font-extrabold uppercase tracking-[0.1em] truncate leading-tight"
                                :style="{ color: section.color ?? accentColor }"
                            >
                                {{ section.label }}
                            </p>
                            <!-- Subtitle: sangat kecil & muted -->
                            <p
                                v-if="section.subtitle"
                                class="text-[10px] mt-0.5 truncate leading-tight"
                                :style="{ color: '#9ca3af' }"
                            >
                                {{ section.subtitle }}
                            </p>
                        </div>
                    </Transition>

                    <!-- Items -->
                    <template v-for="item in section.items" :key="item.label">
                        <NavItem
                            :item="{ ...item, active: isItemActive(item) }"
                            :show-labels="showLabels"
                            :collapsed="collapsed && !isMobile"
                            :open-keys="openKeys"
                            :depth="0"
                            :accent-color="section.color ?? accentColor"
                            :text-primary="textPrimary"
                            :text-muted="textMuted"
                            :current-path="currentPath"
                            @toggle="handleToggle"
                            @expand-sidebar="onExpandSidebar"
                        />
                    </template>
                </div>

            </template>
        </nav>

        <!-- ── User footer — fixed di bawah sidebar ── -->
        <div class="sidebar-user-footer" :class="showLabels ? 'px-3' : 'px-2'">
            <div class="sidebar-user-divider" :style="{ borderColor: borderColor }" />

            <!-- Expanded -->
            <Transition name="slide-label">
                <div
                    v-if="showLabels"
                    class="sidebar-user-row"
                    @mouseenter="($event.currentTarget as HTMLElement).style.background = isDark ? 'rgba(99,102,241,0.1)' : 'rgba(255,255,255,0.5)'"
                    @mouseleave="($event.currentTarget as HTMLElement).style.background = 'transparent'"
                >
                    <UserAvatar :user="user" size="sm" :border-bg="isDark ? '#1e293b' : '#f0f1f8'" />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold truncate leading-tight" :style="{ color: textPrimary }">
                            {{ user?.name ?? 'Admin User' }}
                        </p>
                        <p class="text-xs truncate" :style="{ color: textMuted }">
                            {{ user?.email ?? 'admin@crm.com' }}
                        </p>
                    </div>
                    <button class="p-1 rounded-lg transition-colors" title="Collapse sidebar" @click.stop="$emit('update:collapsed', true)">
                        <ChevronLeft :size="14" :style="{ color: textMuted }" />
                    </button>
                </div>
            </Transition>

            <!-- Collapsed: avatar only -->
            <div
                v-if="!showLabels"
                class="flex justify-center py-2 cursor-pointer"
                @click="$emit('update:collapsed', false)"
            >
                <div class="relative group">
                    <UserAvatar :user="user" size="sm" :border-bg="isDark ? '#1e293b' : '#f0f1f8'" />
                    <span class="absolute inset-0 rounded-full ring-2 ring-transparent group-hover:ring-indigo-400 transition-all" />
                </div>
            </div>
        </div>

    </aside>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ChevronLeft } from '@lucide/vue';
import { SIDEBAR_W_EXPANDED, SIDEBAR_W_COLLAPSED } from '@/config/layout';
import NavItem    from './NavItem.vue';
import UserAvatar from './UserAvatar.vue';

interface NavItemType {
    label: string;
    icon?: unknown;
    href?: string;
    badge?: string | number;
    badgeColor?: string;
    subtitle?: string;
    active?: boolean;
    onClick?: () => void;
    children?: NavItemType[];
}

interface NavGroup {
    label?: string;
    subtitle?: string;
    color?: string;
    items: NavItemType[];
}

type SidebarUser = { name?: string; email?: string; avatar?: string } | null;

const props = defineProps({
    navGroups:   { type: Array as () => NavGroup[], required: true },
    user:        { type: Object as () => SidebarUser, default: null },
    collapsed:   { type: Boolean, default: false },
    mobileOpen:  { type: Boolean, default: false },
    isMobile:    { type: Boolean, default: false },
    isDark:      { type: Boolean, default: false },
    appName:     { type: String,  default: 'E-Gov CRM' },
    appSubtitle: { type: String,  default: 'Laravel + Tailwinds + Vue' },
});

defineEmits(['update:collapsed', 'update:mobileOpen']);

const page        = usePage();
const openKeys    = ref<Set<string>>(new Set());

// ── Active state based on current URL ──
const currentPath = computed(() => {
    const url = page.url as string;
    return url.split('?')[0]; // strip query string
});

function isItemActive(item: NavItemType): boolean {
    if (!item.href) return false;
    return currentPath.value === item.href ||
           (item.href !== '/' && currentPath.value.startsWith(item.href));
}

function hasActiveDescendant(items: NavItemType[]): boolean {
    return items.some(i => isItemActive(i) || (i.children ? hasActiveDescendant(i.children) : false));
}

// ── Auto-open dropdown that contains the active page ──
function syncOpenKeys(): void {
    const next = new Set<string>();
    props.navGroups.forEach(group => {
        group.items.forEach((item) => {
            if (item.children && hasActiveDescendant(item.children)) {
                next.add(`0-${item.label}`);
                // also open nested
                item.children.forEach(child => {
                    if (child.children && hasActiveDescendant(child.children)) {
                        next.add(`1-${child.label}`);
                    }
                });
            }
        });
    });
    openKeys.value = next;
}

watch(currentPath, syncOpenKeys, { immediate: true });

function handleToggle(key: string): void {
    const s = new Set(openKeys.value);
    s.has(key) ? s.delete(key) : s.add(key);
    openKeys.value = s;
}

function onExpandSidebar(): void {}

const showLabels = computed<boolean>(() => !props.collapsed || props.isMobile);

// ── Design tokens (reactive on isDark) ──
const accentColor = '#6366f1';

const sidebarBg   = computed(() => props.isDark ? '#0d1117'        : '#f0f1f8');
const borderColor = computed(() => props.isDark ? 'rgba(99,102,241,0.15)' : 'rgba(99,102,241,0.12)');
const textPrimary = computed(() => props.isDark ? '#e2e8f0'        : '#1e1b4b');
const textMuted   = computed(() => props.isDark ? '#64748b'        : '#6b7280');

const sidebarStyle = computed(() => ({
    backgroundColor: sidebarBg.value,
    borderRight: `1px solid ${borderColor.value}`,
    // position & height diatur via CSS class .app-sidebar
    // overflow: hidden clips aurora blobs di luar bounds sidebar.
    // Scrollbar nav tetap terlihat karena ada di DALAM bounding box sidebar.
    overflow: 'hidden',
}));

const iconGroupStyle = computed<Record<string, string>>(() => ({
    backgroundColor: props.isDark ? 'rgba(30,41,59,0.6)' : '#ffffff',
    borderRadius: '14px',
    padding: '6px',
    boxShadow: props.isDark ? '0 1px 3px rgba(0,0,0,0.3)' : '0 1px 3px rgba(0,0,0,0.06)',
}));
</script>

<style scoped>
/* ── Sidebar positioning ── */

/* ──────────────────────────────────────────────────────────────
   Desktop (≥ 768px):
   - sticky top:0 → sidebar mulai dari paling atas, sejajar dengan topbar
   - height:100vh → full tinggi viewport
   - Nav bagian dalam (flex-1 + overflow-y:auto) yang scroll,
     bukan sidebar-nya sendiri
   ────────────────────────────────────────────────────────────── */
.app-sidebar {
    position: sticky;
    top: 0;
    height: 100vh;
    flex-shrink: 0;
    z-index: 20;
    /* overflow: hidden diatur via sidebarStyle inline agar aurora ter-clip,
       tapi scrollbar nav tetap terlihat karena ada di dalam sidebar */
}

/* ──────────────────────────────────────────────────────────────
   Mobile (< 768px): fixed overlay slide dari kiri
   ────────────────────────────────────────────────────────────── */
@media (max-width: 767px) {
    .app-sidebar {
        position: fixed;
        top: 0; bottom: 0; left: 0;
        height: 100dvh;
        z-index: 30;
    }
    .app-sidebar--mobile-open  { transform: translateX(0); box-shadow: 0 0 40px rgba(0,0,0,0.35); }
    .app-sidebar--mobile-closed { transform: translateX(-100%); }
}

.sidebar-logo {
    width: 38px; height: 38px; border-radius: 12px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a78bfa 100%);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 12px rgba(99,102,241,0.35);
    flex-shrink: 0;
}

/* ── Aurora overlay ── */
.sidebar-aurora {
    position: absolute;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    overflow: hidden;
}

.sidebar-aurora__blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(48px);
    opacity: 0.55;
    will-change: transform;
}

.sidebar-aurora__blob--1 {
    width: 220px; height: 220px;
    top: -40px; left: -60px;
    background: radial-gradient(circle, #6366f1 0%, transparent 70%);
    animation: aurora-1 14s ease-in-out infinite alternate;
}
.sidebar-aurora__blob--2 {
    width: 180px; height: 180px;
    top: 30%; right: -50px;
    background: radial-gradient(circle, #8b5cf6 0%, transparent 70%);
    animation: aurora-2 18s ease-in-out infinite alternate;
    opacity: 0.4;
}
.sidebar-aurora__blob--3 {
    width: 200px; height: 200px;
    bottom: 60px; left: -30px;
    background: radial-gradient(circle, #06b6d4 0%, transparent 70%);
    animation: aurora-3 22s ease-in-out infinite alternate;
    opacity: 0.3;
}

@keyframes aurora-1 {
    0%   { transform: translate(0px, 0px) scale(1); }
    50%  { transform: translate(30px, 40px) scale(1.15); }
    100% { transform: translate(10px, 80px) scale(0.9); }
}
@keyframes aurora-2 {
    0%   { transform: translate(0px, 0px) scale(1); }
    40%  { transform: translate(-20px, 30px) scale(1.2); }
    100% { transform: translate(15px, -50px) scale(0.85); }
}
@keyframes aurora-3 {
    0%   { transform: translate(0px, 0px) scale(1); }
    60%  { transform: translate(40px, -30px) scale(1.1); }
    100% { transform: translate(-10px, -60px) scale(1.25); }
}

/* Make nav content sit above aurora */
.sidebar-aurora ~ * {
    position: relative;
    z-index: 1;
}

/* ── User footer ── */
.sidebar-user-footer {
    flex-shrink: 0;
    padding-bottom: 10px;
    padding-top: 4px;
}

.sidebar-user-divider {
    border-top: 1px solid;
    margin-bottom: 6px;
}

.sidebar-user-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 12px;
    cursor: pointer;
    transition: background 130ms ease;
}

/* Transitions */
.overlay-enter-active, .overlay-leave-active { transition: opacity 220ms ease; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }

.slide-label-enter-active, .slide-label-leave-active {
    transition: opacity 150ms ease, max-width 260ms cubic-bezier(.4,0,.2,1);
    overflow: hidden; max-width: 220px;
}
.slide-label-enter-from, .slide-label-leave-to { opacity: 0; max-width: 0; }
</style>
