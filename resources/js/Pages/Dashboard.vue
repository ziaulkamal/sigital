<!--
    resources/js/Pages/Dashboard.vue
    Ringkasan operasional SIGITAL: statistik nyata (ter-scope organisasi), acara terbaru,
    dan aktivitas terakhir. Tanpa data/teks hardcode.
-->
<template>
    <BaseLayout :nav-groups="navGroups" title="Dashboard">
        <div class="dash">
            <div class="dash__header">
                <div>
                    <h1 class="dash__title">{{ greeting }}, {{ greetingName }}</h1>
                    <p class="dash__sub">Ringkasan aktivitas {{ appName }} Anda.</p>
                </div>
                <div class="dash__date">
                    <CalendarIcon :size="13" />
                    {{ today }}
                </div>
            </div>

            <!-- Aksi cepat -->
            <div class="dash__quick">
                <Link v-for="a in quickActions" :key="a.label" :href="a.href" class="dash__quick-btn">
                    <component :is="a.icon" :size="16" />
                    {{ a.label }}
                </Link>
            </div>

            <!-- Statistik -->
            <div class="dash__stats">
                <div v-for="s in stats" :key="s.key" class="stat">
                    <div class="stat__icon" :style="{ background: s.color + '1a', color: s.color }">
                        <component :is="iconFor(s.icon)" :size="20" />
                    </div>
                    <div>
                        <div class="stat__value">{{ s.value }}</div>
                        <div class="stat__label">{{ s.label }}</div>
                    </div>
                </div>
            </div>

            <div class="dash__row">
                <!-- Acara terbaru -->
                <div class="dash__card">
                    <div class="dash__card-head">
                        <h2 class="dash__card-title">Acara Terbaru</h2>
                        <Link href="/events" class="dash__card-link">Lihat semua</Link>
                    </div>
                    <ul v-if="recentEvents.length" class="dash__list">
                        <li v-for="e in recentEvents" :key="e.id" class="dash__list-item">
                            <Link :href="`/events/${e.id}`" class="dash__list-main">
                                <span class="dash__list-title">{{ e.nama }}</span>
                                <span class="dash__list-sub">{{ e.jadwal_mulai ?? '—' }}</span>
                            </Link>
                            <AppBadge :color="statusColor(e.status)" size="sm">{{ statusLabel(e.status) }}</AppBadge>
                        </li>
                    </ul>
                    <p v-else class="dash__empty">Belum ada acara.</p>
                </div>

                <!-- Aktivitas terakhir -->
                <div class="dash__card">
                    <div class="dash__card-head">
                        <h2 class="dash__card-title">Aktivitas Terakhir</h2>
                    </div>
                    <ul v-if="recentActivity.length" class="dash__activity">
                        <li v-for="a in recentActivity" :key="a.id" class="dash__activity-item">
                            <span class="dash__activity-dot" />
                            <div>
                                <span class="dash__activity-text"><strong>{{ a.aktor }}</strong> — {{ a.aksi }}</span>
                                <span class="dash__activity-time">{{ a.waktu }}</span>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="dash__empty">Belum ada aktivitas.</p>
                </div>
            </div>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import { navGroups } from '@/data/navGroups';
import {
    CalendarIcon, AwardIcon, PenLineIcon, LayoutTemplateIcon,
    Building2, UserCheckIcon, PlusIcon,
} from '@lucide/vue';

interface Stat { key: string; label: string; value: number; icon: string; color: string; }
interface RecentEvent { id: number; nama: string; jadwal_mulai: string | null; status: string; }
interface Activity { id: number; aktor: string; aksi: string; waktu: string; }

defineProps<{
    greetingName: string;
    stats: Stat[];
    recentEvents: RecentEvent[];
    recentActivity: Activity[];
}>();

const appName = computed(() => (usePage().props.app as { name?: string })?.name ?? 'SIGITAL');
const today = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 11) return 'Selamat pagi';
    if (h < 15) return 'Selamat siang';
    if (h < 19) return 'Selamat sore';
    return 'Selamat malam';
});

const quickActions = [
    { label: 'Acara Baru', icon: PlusIcon, href: '/events/create' },
    { label: 'Penanda Tangan', icon: PenLineIcon, href: '/signatories' },
    { label: 'Template', icon: LayoutTemplateIcon, href: '/templates' },
    { label: 'Arsip Sertifikat', icon: AwardIcon, href: '/certificates' },
];

const iconMap: Record<string, unknown> = {
    calendar: CalendarIcon, award: AwardIcon, pen: PenLineIcon, template: LayoutTemplateIcon,
    building: Building2, 'user-check': UserCheckIcon,
};
function iconFor(name: string) { return iconMap[name] ?? CalendarIcon; }

function statusColor(s: string) {
    return ({ draft: 'default', siap_terbit: 'warning', selesai: 'success' } as Record<string, string>)[s] ?? 'default';
}
function statusLabel(s: string) {
    return ({ draft: 'Draft', siap_terbit: 'Siap Terbit', selesai: 'Selesai' } as Record<string, string>)[s] ?? s;
}
</script>

<style scoped>
.dash { display: flex; flex-direction: column; gap: 20px; padding: 24px; max-width: 1200px; }
.dash__header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.dash__title { font-size: 22px; font-weight: 800; color: var(--color-text-primary); font-family: var(--font-heading); letter-spacing: -0.02em; }
.dash__sub { font-size: 13px; color: var(--color-text-muted); margin-top: 3px; }
.dash__date {
    display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: var(--color-text-subtle);
    background: var(--color-surface); border: 1.5px solid var(--color-border); border-radius: 8px;
    padding: 6px 12px; white-space: nowrap; flex-shrink: 0;
}
.dash__quick { display: flex; flex-wrap: wrap; gap: 10px; }
.dash__quick-btn {
    display: inline-flex; align-items: center; gap: 8px; padding: 9px 14px; border-radius: 10px;
    border: 1.5px solid var(--color-border); background: var(--color-surface);
    font-size: 13px; font-weight: 600; color: var(--color-text-primary); transition: all 130ms ease;
}
.dash__quick-btn:hover { border-color: #6366f1; color: #6366f1; }

.dash__stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.stat {
    display: flex; align-items: center; gap: 14px; padding: 18px;
    background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px;
}
.stat__icon { width: 44px; height: 44px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat__value { font-size: 24px; font-weight: 800; color: var(--color-text-primary); line-height: 1.1; }
.stat__label { font-size: 12.5px; color: var(--color-text-muted); margin-top: 2px; }

.dash__row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.dash__card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; padding: 18px; }
.dash__card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.dash__card-title { font-size: 15px; font-weight: 700; color: var(--color-text-primary); }
.dash__card-link { font-size: 12.5px; color: #6366f1; font-weight: 600; }
.dash__card-link:hover { text-decoration: underline; }
.dash__list { display: flex; flex-direction: column; gap: 2px; list-style: none; margin: 0; padding: 0; }
.dash__list-item { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--color-border); }
.dash__list-item:last-child { border-bottom: none; }
.dash__list-main { display: flex; flex-direction: column; min-width: 0; }
.dash__list-title { font-size: 13.5px; font-weight: 600; color: var(--color-text-primary); }
.dash__list-title:hover { color: #6366f1; }
.dash__list-sub { font-size: 12px; color: var(--color-text-subtle); }
.dash__activity { display: flex; flex-direction: column; gap: 0; list-style: none; margin: 0; padding: 0; }
.dash__activity-item { display: flex; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--color-border); }
.dash__activity-item:last-child { border-bottom: none; }
.dash__activity-dot { width: 7px; height: 7px; border-radius: 50%; background: #6366f1; margin-top: 6px; flex-shrink: 0; }
.dash__activity-text { display: block; font-size: 13px; color: var(--color-text-primary); }
.dash__activity-time { display: block; font-size: 11.5px; color: var(--color-text-subtle); margin-top: 1px; }
.dash__empty { font-size: 13px; color: var(--color-text-subtle); padding: 16px 0; text-align: center; }

@media (max-width: 1000px) { .dash__stats { grid-template-columns: repeat(2, 1fr); } .dash__row { grid-template-columns: 1fr; } }
@media (max-width: 600px) { .dash__stats { grid-template-columns: 1fr; } .dash { padding: 16px; } }
</style>
