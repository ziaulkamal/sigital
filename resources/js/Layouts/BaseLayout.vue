<template>
    <!--
        Layout struktur (flex row):
        ┌───────────────┬──────────────────────────────────────┐
        │  Sidebar      │  [Topbar sticky dalam kolom kanan]   │
        │  sticky       ├──────────────────────────────────────┤
        │  full height  │  Page content                        │
        │  (scrollbar)  │                                      │
        └───────────────┴──────────────────────────────────────┘
    -->
    <div :class="{ dark: isDark }" class="layout-root">
        <!-- Judul head mengikuti halaman (prop title), suffix brand ditambah di app.ts. -->
        <Head :title="title" />


        <!-- ── Sidebar: kolom kiri, sticky full height ── -->
        <AppSidebar
            v-model:collapsed="sidebarCollapsed"
            v-model:mobile-open="sidebarMobileOpen"
            :nav-groups="resolvedNavGroups"
            :user="currentUser"
            :is-mobile="isMobile"
            :is-dark="isDark"
            :app-name="brand.name"
            :app-subtitle="brand.tagline"
            :app-logo="brand.logo"
            @update:collapsed="onCollapsedChange"
        />

        <!-- ── Kolom kanan: topbar + konten ── -->
        <div class="layout-right">
            <AppTopbar
                :sidebar-collapsed="sidebarCollapsed"
                :is-dark="isDark"
                :user="currentUser"
                :notification-count="unreadCount"
                :is-mobile="isMobile"
                @toggle-sidebar="toggleSidebar"
                @toggle-theme="toggleTheme"
            />

            <main class="layout-main">
                <slot />
            </main>

            <!-- Footer: credit pembuat + hak cipta (sesudah login) -->
            <AppFooter />
        </div>

    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme';
import AppSidebar from '@/Components/App/AppSidebar.vue';
import AppTopbar  from '@/Components/App/AppTopbar.vue';
import AppFooter  from '@/Components/App/AppFooter.vue';
import { BREAKPOINT_MOBILE, BREAKPOINT_TABLET } from '@/config/layout';
import {
    LayoutDashboard, Users, Kanban, Mail, MessageSquare,
    Settings, BarChart3, Package, ShoppingCart, FileText, BookOpen,
} from '@lucide/vue';

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
    roles?: string[];
}

interface NavGroup {
    label?: string;
    subtitle?: string;
    color?: string;
    items: NavItem[];
}

type LayoutUser = { name?: string; email?: string; avatar?: string } | null;
// ── Props ──────────────────────────────────────────────────────
const props = defineProps({
    navGroups:         { type: Array as () => NavGroup[] | null, default: null },
    user:              { type: Object as () => LayoutUser,       default: null },
    appName:           { type: String, default: 'SIGITAL' },
    appSubtitle:       { type: String, default: 'Sertifikat Digital' },
    notificationCount: { type: Number, default: 0 },
    // Judul halaman untuk <head>; kosong = pakai nama brand saja.
    title:             { type: String, default: '' },
});

defineEmits(['open-search', 'open-notifications', 'open-user-menu']);

// ── Theme ──────────────────────────────────────────────────────
const { isDark, toggleTheme } = useTheme();

// ── Responsive breakpoints ─────────────────────────────────────
// Nilai breakpoint diambil dari @/config/layout — edit di sana untuk mengubah
const windowWidth       = ref<number>(typeof window !== 'undefined' ? window.innerWidth : 1280);
const isMobile          = computed<boolean>(() => windowWidth.value < BREAKPOINT_MOBILE);
const isTablet          = computed<boolean>(() => windowWidth.value >= BREAKPOINT_MOBILE && windowWidth.value < BREAKPOINT_TABLET);

// Sidebar state — persisted to localStorage
const SIDEBAR_KEY = 'crm_sidebar_collapsed';

function readSidebarCollapsed(): boolean {
    if (typeof window === 'undefined') return false;
    try { return localStorage.getItem(SIDEBAR_KEY) === 'true'; } catch { return false; }
}

const sidebarCollapsed  = ref<boolean>(readSidebarCollapsed());
const sidebarMobileOpen = ref<boolean>(false);

watch(sidebarCollapsed, (val) => {
    try { localStorage.setItem(SIDEBAR_KEY, String(val)); } catch { /* ignore */ }
});

// Auto-collapse on tablet (but don't override user preference on desktop)
watch(isTablet, (tablet) => { if (tablet) sidebarCollapsed.value = true; }, { immediate: true });
watch(isMobile, (mobile) => { if (mobile) { sidebarMobileOpen.value = false; } });

function toggleSidebar(): void {
    if (isMobile.value) {
        sidebarMobileOpen.value = !sidebarMobileOpen.value;
    } else {
        sidebarCollapsed.value = !sidebarCollapsed.value;
    }
}

function onCollapsedChange(val: boolean): void {
    sidebarCollapsed.value = val;
}


function handleResize(): void {
    windowWidth.value = window.innerWidth;
}

onMounted(() => window.addEventListener('resize', handleResize));
onUnmounted(() => window.removeEventListener('resize', handleResize));

// ── Nav (disaring berdasarkan peran user) ──────────────────────
const page = usePage();
const userRoles = computed<string[]>(
    () => ((page.props.auth as { user?: { roles?: string[] } })?.user?.roles ?? []),
);

// User asli & brand dari shared props (HandleInertiaRequests) — tanpa hardcode.
const currentUser = computed<LayoutUser>(
    () => ((page.props.auth as { user?: LayoutUser })?.user ?? props.user),
);
const brand = computed(() => {
    const a = page.props.app as { name?: string; tagline?: string; logo?: string | null } | undefined;
    return { name: a?.name ?? props.appName, tagline: a?.tagline ?? props.appSubtitle, logo: a?.logo ?? null };
});
const unreadCount = computed<number>(
    () => ((page.props.notifications as { unread?: number } | null)?.unread ?? props.notificationCount),
);

/** Item tampil bila tak punya batasan peran, atau peran user cocok. */
function canSee(item: NavItem): boolean {
    return !item.roles || item.roles.some((r) => userRoles.value.includes(r));
}

/** Saring item per peran (rekursif untuk submenu) lalu buang grup kosong. */
function filterGroups(groups: NavGroup[]): NavGroup[] {
    return groups
        .map((group) => ({
            ...group,
            items: group.items.filter(canSee).map((item) => ({
                ...item,
                children: item.children?.filter(canSee),
            })),
        }))
        .filter((group) => group.items.length > 0);
}

const resolvedNavGroups = computed<NavGroup[]>(() => filterGroups(props.navGroups ?? defaultNavGroups));

const defaultNavGroups: NavGroup[] = [
    {
        label: '',
        items: [
            { label: 'Dashboard', icon: LayoutDashboard, href: '/', active: false },
            { label: 'Reports',   icon: BarChart3,       href: '/reports', active: false },
        ],
    },
    {
        label: 'Applications',
        subtitle: 'Custom made application designs',
        color: '#6366f1',
        items: [
            {
                label: 'Contacts',
                icon: Users,
                href: '/contacts',
                active: false,
            },
            {
                label: 'Kanban',
                icon: Kanban,
                href: '/kanban',
                active: false,
                badge: 'NEW',
                badgeColor: '#6366f1',
            },
            {
                // Dropdown example
                label: 'E-Commerce',
                icon: ShoppingCart,
                children: [
                    { label: 'Products', icon: Package, href: '/products', active: false },
                    { label: 'Orders',   icon: Kanban,  href: '/orders',   active: false },
                ],
            },
            {
                // Multi-dropdown example
                label: 'Pages',
                icon: FileText,
                children: [
                    {
                        label: 'Blog',
                        icon: BookOpen,
                        children: [
                            { label: 'All Posts', href: '/blog',        active: false },
                            { label: 'Create',    href: '/blog/create', active: false },
                        ],
                    },
                    { label: 'Landing', icon: FileText, href: '/landing', active: false },
                ],
            },
            {
                label: 'Inbox',
                icon: Mail,
                href: '/mail',
                active: false,
                badge: '3',
                badgeColor: '#ef4444',
            },
            {
                label: 'Chat',
                icon: MessageSquare,
                href: '/chat',
                active: false,
                subtitle: '3 unread messages',
            },
        ],
    },
    {
        label: 'System',
        color: '#64748b',
        items: [
            { label: 'Settings', icon: Settings, href: '/settings', active: false },
        ],
    },
];
</script>

<style scoped>
/* Root: flex ROW — sidebar kiri, konten kanan */
.layout-root {
    display: flex;
    min-height: 100vh;
    background-color: var(--color-bg);
}

/* Kolom kanan: topbar sticky + page content */
.layout-right {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background-color: var(--color-bg);
}

/* Page content: fills remaining height */
.layout-main {
    flex: 1;
}
</style>
