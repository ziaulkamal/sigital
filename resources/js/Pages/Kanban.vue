<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Deal Pipeline</h1>
                    <p class="page__sub">Drag cards between columns to update deal stage</p>
                </div>
                <button class="page__cta">
                    <PlusIcon :size="14" />
                    Add Deal
                </button>
            </div>
            <div class="page__kanban">
                <KanbanBoard />
            </div>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import BaseLayout  from '@/Layouts/BaseLayout.vue';
import KanbanBoard from '@/Components/App/KanbanBoard.vue';
import { PlusIcon } from '@lucide/vue';
import { navGroups } from '@/data/navGroups';
</script>

<style scoped>
.page { padding: 24px; display: flex; flex-direction: column; gap: 20px; height: 100%; }
.page__header { display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
.page__title  { font-size: 20px; font-weight: 800; color: var(--color-text-primary); font-family: var(--font-heading); letter-spacing: -0.02em; }
.page__sub    { font-size: 13px; color: var(--color-text-muted); margin-top: 2px; }
.page__cta {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 18px; border-radius: 9px; border: none;
    background: #6366f1; color: #fff; font-size: 13px; font-weight: 600;
    font-family: var(--font-sans); cursor: pointer; transition: background 150ms ease;
}
.page__cta:hover { background: #4f46e5; }
.page__kanban { flex: 1; min-height: 0; overflow-x: auto; }
</style>
