<template>
    <header
        class="topbar"
        style="background-color: var(--color-topbar-bg); border-color: var(--color-topbar-border);"
    >
        <!-- ── MOBILE SEARCH MODE: full-width search replaces topbar ── -->
        <template v-if="isMobile && mobileSearchOpen">
            <div class="topbar__search topbar__search--mobile" :class="{ 'topbar__search--focused': searchFocused }">
                <Search :size="14" class="topbar__search-icon" />
                <input
                    ref="mobileSearchInput"
                    v-model="searchQuery"
                    class="topbar__search-input"
                    placeholder="Search anything…"
                    @focus="searchFocused = true"
                    @blur="searchFocused = false"
                    @keydown.escape="closeMobileSearch"
                />
                <button v-if="searchQuery" class="topbar__search-clear" @click="searchQuery = ''">
                    <X :size="12" />
                </button>
            </div>
            <TopbarBtn aria-label="Close search" @click="closeMobileSearch">
                <X :size="18" stroke-width="1.7" />
            </TopbarBtn>
        </template>

        <!-- ── NORMAL MODE ── -->
        <template v-else>
            <!-- LEFT: toggle + organisasi + search -->
            <div class="topbar__left">
                <TopbarBtn
                    class="topbar__toggle-btn"
                    :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    @click="$emit('toggle-sidebar')"
                >
                    <component :is="isMobile ? Menu : PanelLeft" :size="19" stroke-width="1.7" />
                </TopbarBtn>

                <!-- Organisasi aktif (P1) — switcher (select2-style) untuk SuperAdmin, badge utk lainnya -->
                <div v-if="tenancy?.is_super_admin" class="topbar__org-switch">
                    <AppSelect
                        :model-value="tenancy.current_organization_id ?? ''"
                        :options="orgOptions"
                        searchable
                        size="sm"
                        placeholder="Semua organisasi"
                        search-placeholder="Cari organisasi…"
                        aria-label="Pilih organisasi"
                        @update:model-value="onSwitchOrg"
                    >
                        <template #prefix><Building2 :size="14" /></template>
                    </AppSelect>
                </div>
                <div v-else-if="organization" class="topbar__org topbar__org--badge" :title="organization.nama">
                    <Building2 :size="14" class="topbar__org-icon" />
                    <span class="topbar__org-name">{{ organization.nama }}</span>
                </div>


            </div>

            <!-- RIGHT: actions + user -->
            <div class="topbar__right">
                <!-- Mobile search icon (shown only on mobile) -->
                <TopbarBtn v-if="isMobile" aria-label="Search" @click="openMobileSearch">
                    <Search :size="18" stroke-width="1.7" />
                </TopbarBtn>

                <!-- Theme toggle -->
                <TopbarBtn :aria-label="isDark ? 'Light mode' : 'Dark mode'" @click="$emit('toggle-theme')">
                    <component :is="isDark ? Sun : Moon" :size="18" stroke-width="1.7" />
                </TopbarBtn>

                <!-- Notifications -->
                <div class="topbar__notif-wrap" ref="notifWrapRef">
                    <TopbarBtn aria-label="Notifications" @click="notifOpen = !notifOpen">
                        <Bell :size="18" stroke-width="1.7" />
                    </TopbarBtn>
                    <span v-if="notificationCount > 0" class="topbar__notif-badge">
                        {{ notificationCount > 9 ? '9+' : notificationCount }}
                    </span>

                    <Transition name="dropdown">
                        <div v-if="notifOpen" class="topbar__notif-menu">
                            <div class="topbar__notif-header">
                                <span class="topbar__notif-title">Notifikasi</span>
                                <button v-if="notificationCount > 0" class="topbar__notif-readall" @click="markAllRead">
                                    Tandai semua terbaca
                                </button>
                            </div>
                            <div class="topbar__notif-list">
                                <button
                                    v-for="n in notifItems"
                                    :key="n.id"
                                    class="topbar__notif-item"
                                    :class="{ 'topbar__notif-item--unread': !n.read }"
                                    @click="openNotif(n)"
                                >
                                    <span class="topbar__notif-dot" :class="{ 'is-read': n.read }" />
                                    <span class="topbar__notif-body">
                                        <span class="topbar__notif-item-title">{{ n.data.title }}</span>
                                        <span class="topbar__notif-item-msg">{{ n.data.message }}</span>
                                        <span class="topbar__notif-item-time">{{ n.created_at }}</span>
                                    </span>
                                </button>
                                <p v-if="!notifItems.length" class="topbar__notif-empty">Belum ada notifikasi.</p>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- Divider (desktop only) -->
                <div class="topbar__divider topbar__divider--desktop" />

                <!-- User avatar + dropdown -->
                <div class="topbar__user-wrap" ref="userWrapRef">
                    <button class="topbar__user-btn" aria-label="User menu" @click="userMenuOpen = !userMenuOpen">
                        <div class="topbar__avatar">
                            <img v-if="displayUser?.avatar" :src="displayUser.avatar" :alt="displayUser?.name" class="topbar__avatar-img" />
                            <span v-else>{{ userInitials }}</span>
                        </div>
                        <!-- Name + chevron hidden on mobile -->
                        <div class="topbar__user-info topbar__user-info--desktop">
                            <p class="topbar__user-name">{{ displayUser?.name ?? 'Pengguna' }}</p>
                            <p class="topbar__user-email">{{ displayUser?.email ?? '' }}</p>
                        </div>
                        <ChevronDown
                            :size="13"
                            stroke-width="2.5"
                            class="topbar__user-chevron topbar__user-chevron--desktop"
                            :class="{ 'rotate-180': userMenuOpen }"
                        />
                    </button>

                    <!-- User dropdown -->
                    <Transition name="dropdown">
                        <div v-if="userMenuOpen" class="topbar__user-menu">
                            <div class="topbar__menu-header">
                                <div class="topbar__menu-avatar">
                                    <img v-if="displayUser?.avatar" :src="displayUser.avatar" :alt="displayUser?.name" class="w-full h-full object-cover" />
                                    <span v-else>{{ userInitials }}</span>
                                </div>
                                <div>
                                    <p class="topbar__menu-name">{{ displayUser?.name ?? 'Pengguna' }}</p>
                                    <p class="topbar__menu-email">{{ displayUser?.email ?? '' }}</p>
                                </div>
                            </div>

                            <div class="topbar__menu-divider" />

                            <div class="topbar__menu-items">
                                <Link href="/settings" class="topbar__menu-item" @click="userMenuOpen = false">
                                    <UserIcon :size="14" /> Profil
                                </Link>
                                <Link href="/settings" class="topbar__menu-item" @click="userMenuOpen = false">
                                    <Settings :size="14" /> Pengaturan
                                </Link>
                            </div>

                            <div class="topbar__menu-divider" />

                            <div class="topbar__menu-items">
                                <button class="topbar__menu-item topbar__menu-item--danger" @click="logout">
                                    <LogOut :size="14" /> Keluar
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </template>
    </header>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import {
    PanelLeft, Menu, Sun, Moon, Search, X,
    Bell, ChevronDown, UserIcon, Settings, LogOut, Building2,
} from '@lucide/vue';
import TopbarBtn from './TopbarBtn.vue';
import AppSelect from './AppSelect.vue';

type TopbarUser = { name?: string; email?: string; avatar?: string } | null;

interface OrgOption { id: number; nama: string; kode: string; type: string }
interface Tenancy {
    is_super_admin: boolean;
    current_organization_id: number | null;
    organizations: OrgOption[];
}

// Data tenancy dibagikan via HandleInertiaRequests (auth.user.organization & props.tenancy).
const page = usePage();
const organization = computed<OrgOption | null>(
    () => ((page.props.auth as { user?: { organization?: OrgOption | null } })?.user?.organization ?? null),
);
const tenancy = computed<Tenancy | null>(() => (page.props.tenancy as Tenancy | null) ?? null);

// Opsi switcher organisasi (SuperAdmin). "Semua organisasi" = placeholder (value '').
const orgOptions = computed(() =>
    (tenancy.value?.organizations ?? []).map((o) => ({ value: o.id, label: o.nama })),
);

// Data user asli dari shared props Inertia (fallback ke prop bila diberikan eksplisit).
const displayUser = computed<TopbarUser>(
    () => ((page.props.auth as { user?: TopbarUser })?.user ?? props.user),
);

function onSwitchOrg(value: string | number): void {
    router.post('/switch-organization',
        { organization_id: value === '' ? null : Number(value) },
        { preserveScroll: true },
    );
}

function logout(): void {
    userMenuOpen.value = false;
    router.post('/logout');
}

const props = defineProps({
    sidebarCollapsed:  { type: Boolean, default: false },
    isDark:            { type: Boolean, default: false },
    user:              { type: Object as () => TopbarUser, default: null },
    notificationCount: { type: Number,  default: 0 },
    isMobile:          { type: Boolean, default: false },
});

defineEmits(['toggle-sidebar', 'toggle-theme', 'open-notifications', 'open-user-menu']);

const searchQuery       = ref('');
const searchFocused     = ref(false);
const mobileSearchOpen  = ref(false);
const mobileSearchInput = ref<HTMLInputElement | null>(null);
const userMenuOpen      = ref(false);
const userWrapRef       = ref<HTMLElement | null>(null);
const notifOpen         = ref(false);
const notifWrapRef      = ref<HTMLElement | null>(null);

// Notifikasi dari shared props (HandleInertiaRequests).
interface NotifItem { id: string; type: string; data: { title: string; message: string; url: string | null }; read: boolean; created_at: string }
const notifItems = computed<NotifItem[]>(
    () => ((page.props.notifications as { items?: NotifItem[] } | null)?.items ?? []),
);

function openNotif(n: NotifItem) {
    notifOpen.value = false;
    if (!n.read) router.post(`/notifications/${n.id}/read`, {}, { preserveScroll: true, preserveState: false });
    if (n.data.url) router.visit(n.data.url);
}
function markAllRead() {
    router.post('/notifications/read-all', {}, { preserveScroll: true });
}

const userInitials = computed<string>(() => {
    const name = displayUser.value?.name ?? 'Pengguna';
    return name.split(' ').map((n: string) => n[0]).slice(0, 2).join('').toUpperCase();
});

async function openMobileSearch() {
    mobileSearchOpen.value = true;
    await nextTick();
    mobileSearchInput.value?.focus();
}

function closeMobileSearch() {
    mobileSearchOpen.value = false;
    searchQuery.value = '';
}

function onClickOutside(e: MouseEvent) {
    if (userWrapRef.value && !userWrapRef.value.contains(e.target as Node)) {
        userMenuOpen.value = false;
    }
    if (notifWrapRef.value && !notifWrapRef.value.contains(e.target as Node)) {
        notifOpen.value = false;
    }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside));
</script>

<style scoped>
.topbar {
    position: sticky; top: 0; z-index: 40; /* di atas sidebar (z-20 desktop, z-30 mobile) */
    display: flex; align-items: center; justify-content: space-between;
    height: 56px; border-bottom: 1px solid;
    padding: 0 12px 0 0;
    gap: 8px;
}

/* ── LEFT ── */
.topbar__left {
    display: flex; align-items: center; gap: 8px;
    flex: 1; min-width: 0;
    padding-left: 12px;
}
.topbar__toggle-btn { flex-shrink: 0; }

/* ── Organisasi aktif (badge / switcher) ── */
.topbar__org {
    display: flex; align-items: center; gap: 6px;
    height: 34px; padding: 0 10px;
    border: 1.5px solid var(--color-border);
    border-radius: 9px;
    background: var(--color-bg-subtle);
    flex-shrink: 0; max-width: 220px;
}
.topbar__org-icon { color: var(--color-text-subtle); flex-shrink: 0; }
.topbar__org-name {
    font-size: 12.5px; font-weight: 600; color: var(--color-text-primary);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
/* Switcher organisasi (SuperAdmin) — lebar tetap, nama panjang ter-ellipsis di trigger
   & tampil penuh di dropdown (gaya select2). */
.topbar__org-switch { width: 240px; flex-shrink: 0; }

/* Sembunyikan badge organisasi di mobile agar topbar tetap ringkas */
@media (max-width: 767px) {
    .topbar__org--badge { display: none; }
    .topbar__org { max-width: 150px; }
    .topbar__org-switch { width: 150px; }
}

/* ── Search (desktop only) ── */
.topbar__search--desktop {
    display: flex; align-items: center; gap: 8px;
    flex: 1; min-width: 0; max-width: 260px;
    height: 34px; padding: 0 10px;
    border: 1.5px solid var(--color-border);
    border-radius: 9px;
    background: var(--color-bg-subtle);
    transition: border-color 160ms ease, background 160ms ease, box-shadow 160ms ease, max-width 200ms ease;
}
.topbar__search--desktop.topbar__search--focused {
    max-width: 320px;
    border-color: #6366f1;
    background: var(--color-surface);
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

/* ── Search (mobile full-width) ── */
.topbar__search--mobile {
    display: flex; align-items: center; gap: 8px;
    flex: 1; min-width: 0;
    height: 36px; padding: 0 12px;
    border: 1.5px solid var(--color-border);
    border-radius: 9px;
    background: var(--color-bg-subtle);
}
.topbar__search--mobile.topbar__search--focused {
    border-color: #6366f1;
    background: var(--color-surface);
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.topbar__search-icon  { color: var(--color-text-subtle); flex-shrink: 0; }
.topbar__search-input {
    flex: 1; border: none; outline: none; background: transparent;
    font-size: 13px; color: var(--color-text-primary); font-family: var(--font-sans);
    min-width: 0;
}
.topbar__search-input::placeholder { color: var(--color-text-subtle); }
.topbar__search-clear {
    display: flex; align-items: center; border: none; background: transparent;
    cursor: pointer; color: var(--color-text-subtle); padding: 0; flex-shrink: 0;
}
.topbar__search-kbd {
    font-size: 10px; font-family: var(--font-mono);
    color: var(--color-text-subtle); background: var(--color-border);
    border-radius: 4px; padding: 2px 5px; flex-shrink: 0; line-height: 1.4;
}

/* Hide desktop search on mobile, show search icon instead */
@media (max-width: 767px) {
    .topbar__search--desktop { display: none; }
}

/* ── RIGHT ── */
.topbar__right {
    display: flex; align-items: center; gap: 2px; flex-shrink: 0;
}
.topbar__notif-wrap { position: relative; }
.topbar__notif-badge {
    position: absolute; top: 4px; right: 4px;
    min-width: 15px; height: 15px;
    background: linear-gradient(135deg, #ef4444, #f97316);
    color: #fff; font-size: 9px; font-weight: 700;
    border-radius: 99px; padding: 0 3px;
    display: flex; align-items: center; justify-content: center;
    pointer-events: none;
}
.topbar__notif-menu {
    position: absolute; top: calc(100% + 8px); right: 0; width: 340px; max-width: 92vw;
    background: var(--color-surface); border: 1.5px solid var(--color-border);
    border-radius: 14px; box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden; z-index: 100;
}
.topbar__notif-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 14px; border-bottom: 1px solid var(--color-border);
}
.topbar__notif-title { font-size: 13.5px; font-weight: 700; color: var(--color-text-primary); }
.topbar__notif-readall { font-size: 11.5px; color: #6366f1; font-weight: 600; background: none; border: none; cursor: pointer; font-family: var(--font-sans); }
.topbar__notif-readall:hover { text-decoration: underline; }
.topbar__notif-list { max-height: 380px; overflow-y: auto; padding: 4px; }
.topbar__notif-item {
    display: flex; gap: 10px; width: 100%; text-align: left; padding: 10px 10px;
    border: none; background: transparent; cursor: pointer; border-radius: 10px;
    transition: background 120ms ease;
}
.topbar__notif-item:hover { background: var(--color-bg-subtle); }
.topbar__notif-item--unread { background: rgba(99,102,241,0.05); }
.topbar__notif-dot { width: 8px; height: 8px; border-radius: 50%; background: #6366f1; margin-top: 6px; flex-shrink: 0; }
.topbar__notif-dot.is-read { background: var(--color-border); }
.topbar__notif-body { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.topbar__notif-item-title { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }
.topbar__notif-item-msg { font-size: 12px; color: var(--color-text-muted); line-height: 1.4; }
.topbar__notif-item-time { font-size: 11px; color: var(--color-text-subtle); margin-top: 2px; }
.topbar__notif-empty { padding: 24px; text-align: center; font-size: 12.5px; color: var(--color-text-subtle); }

.topbar__divider--desktop {
    width: 1px; height: 20px; margin: 0 4px;
    background: var(--color-border);
}
@media (max-width: 767px) {
    .topbar__divider--desktop { display: none; }
}

/* ── User button ── */
.topbar__user-wrap { position: relative; }
.topbar__user-btn {
    display: flex; align-items: center; gap: 6px;
    padding: 4px 6px 4px 4px; border-radius: 10px; border: none;
    background: transparent; cursor: pointer;
    transition: background 130ms ease;
}
.topbar__user-btn:hover { background: var(--color-bg-subtle); }
.topbar__avatar {
    width: 30px; height: 30px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; font-size: 11px; font-weight: 700;
    overflow: hidden; flex-shrink: 0;
}
.topbar__avatar-img { width: 100%; height: 100%; object-fit: cover; }

/* User name/email/chevron — desktop only */
.topbar__user-info--desktop { display: flex; flex-direction: column; align-items: flex-start; }
.topbar__user-chevron--desktop { color: var(--color-text-subtle); transition: transform 200ms ease; }
@media (max-width: 767px) {
    .topbar__user-info--desktop  { display: none; }
    .topbar__user-chevron--desktop { display: none; }
    .topbar__user-btn { padding: 4px; }
}

.topbar__user-name  { font-size: 12.5px; font-weight: 600; color: var(--color-text-primary); line-height: 1.2; white-space: nowrap; }
.topbar__user-email { font-size: 10.5px; color: var(--color-text-muted); white-space: nowrap; }

/* ── Dropdown menu ── */
.topbar__user-menu {
    position: absolute; top: calc(100% + 8px); right: 0;
    width: 220px;
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
    z-index: 100;
}
.topbar__menu-header { display: flex; align-items: center; gap: 10px; padding: 14px 14px 12px; }
.topbar__menu-avatar {
    width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; font-size: 12px; font-weight: 700; overflow: hidden;
}
.topbar__menu-name  { font-size: 13px; font-weight: 700; color: var(--color-text-primary); }
.topbar__menu-email { font-size: 11px; color: var(--color-text-muted); margin-top: 1px; }
.topbar__menu-divider { height: 1px; background: var(--color-border); }
.topbar__menu-items { padding: 6px; display: flex; flex-direction: column; gap: 1px; }
.topbar__menu-item {
    display: flex; align-items: center; gap: 9px;
    padding: 8px 10px; border-radius: 8px;
    font-size: 13px; color: var(--color-text-primary);
    text-decoration: none; background: transparent; border: none;
    cursor: pointer; width: 100%; text-align: left;
    transition: background 120ms ease;
}
.topbar__menu-item:hover { background: var(--color-bg-subtle); }
.topbar__menu-item svg { color: var(--color-text-muted); flex-shrink: 0; }
.topbar__menu-item--danger { color: #ef4444; }
.topbar__menu-item--danger:hover { background: rgba(239,68,68,0.06); }
.topbar__menu-item--danger svg { color: #ef4444; }

/* Dropdown animation */
.dropdown-enter-active { transition: opacity 150ms ease, transform 150ms ease; }
.dropdown-leave-active { transition: opacity 100ms ease, transform 100ms ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-6px) scale(0.97); }
</style>
