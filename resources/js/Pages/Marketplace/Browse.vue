<!--
    resources/js/Pages/Marketplace/Browse.vue
    Katalog template marketplace. Pakai template orang lain dengan biaya credit.
-->
<template>
    <BaseLayout :nav-groups="navGroups" title="Marketplace Template">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Marketplace Template</h1>
                    <p class="page__sub">Gunakan template komunitas. Setiap pemakaian {{ price }} credit (10 untuk pembuat, 5 untuk platform).</p>
                </div>
            </div>

            <div v-if="!templates.length" class="empty">
                <StoreIcon :size="34" class="empty__icon" />
                <p>Belum ada template di marketplace.</p>
            </div>

            <div v-else class="grid">
                <div v-for="t in templates" :key="t.id" class="tpl-card">
                    <div class="tpl-card__body">
                        <h3 class="tpl-card__name">{{ t.nama }}</h3>
                        <p class="tpl-card__owner">oleh {{ t.owner || '—' }}</p>
                        <p v-if="t.deskripsi" class="tpl-card__desc">{{ t.deskripsi }}</p>
                        <div class="tpl-card__meta">
                            <span>{{ t.usage_count }}× dipakai</span>
                            <span class="tpl-card__price">{{ t.price }} credit</span>
                        </div>
                    </div>
                    <div class="tpl-card__foot">
                        <AppBadge v-if="t.is_mine" color="default" size="sm">Template Anda</AppBadge>
                        <AppButton v-else variant="primary" size="sm" @click="purchase(t)">
                            <template #icon><DownloadIcon :size="14" /></template>
                            Gunakan ({{ t.price }})
                        </AppButton>
                    </div>
                </div>
            </div>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { StoreIcon, DownloadIcon } from '@lucide/vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppBadge from '@/Components/App/AppBadge.vue';
import { swalConfirm } from '@/Composables/useSwal';
import { navGroups } from '@/data/navGroups';

interface TplRow { id: number; nama: string; deskripsi: string | null; price: number; owner: string | null; is_mine: boolean; usage_count: number }
defineProps<{ templates: TplRow[]; price: number }>();

async function purchase(t: TplRow): Promise<void> {
    const ok = await swalConfirm({
        title: 'Gunakan template ini?',
        text: `${t.price} credit akan dipotong dari saldo Anda.`,
        confirmText: 'Ya, gunakan',
    });
    if (!ok) return;
    useForm({}).post(`/marketplace/${t.id}/purchase`, { preserveScroll: true });
}
</script>

<style scoped>
.page { padding: 24px; max-width: 1100px; margin: 0 auto; }
.page__header { margin-bottom: 16px; }
.page__title { font-size: 22px; font-weight: 700; color: var(--color-text-primary); }
.page__sub { font-size: 13.5px; color: var(--color-text-muted); margin-top: 2px; }

.empty { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 64px 0; color: var(--color-text-subtle); }

.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }
.tpl-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; display: flex; flex-direction: column; overflow: hidden; }
.tpl-card__body { padding: 16px; flex: 1; }
.tpl-card__name { font-size: 15px; font-weight: 700; color: var(--color-text-primary); }
.tpl-card__owner { font-size: 12px; color: var(--color-text-muted); margin-top: 2px; }
.tpl-card__desc { font-size: 12.5px; color: var(--color-text-muted); margin-top: 8px; line-height: 1.4; }
.tpl-card__meta { display: flex; justify-content: space-between; align-items: center; margin-top: 12px; font-size: 12px; color: var(--color-text-subtle); }
.tpl-card__price { font-weight: 700; color: #d97706; }
.tpl-card__foot { padding: 12px 16px; border-top: 1px solid var(--color-border); display: flex; justify-content: flex-end; }
</style>
