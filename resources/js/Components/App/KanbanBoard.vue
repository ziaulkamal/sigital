<template>
    <div class="kb">
        <div
            v-for="col in board"
            :key="col.id"
            class="kb__col"
            @dragover.prevent="dragOverCol = col.id"
            @drop.prevent="onDrop(col.id)"
        >
            <!-- Column header -->
            <div class="kb__col-head">
                <div class="kb__col-dot" :style="{ background: col.color }" />
                <span class="kb__col-label">{{ col.label }}</span>
                <span class="kb__col-count">{{ col.cards.length }}</span>
                <button class="kb__col-add" @click="addCard(col.id)">
                    <PlusIcon :size="14" />
                </button>
            </div>

            <!-- Cards -->
            <div
                class="kb__cards"
                :class="{ 'kb__cards--over': dragOverCol === col.id && draggingCard !== null }"
            >
                <div
                    v-for="card in col.cards"
                    :key="card.id"
                    class="kb__card-wrap"
                    draggable="true"
                    @dragstart="onDragStart(card.id, col.id)"
                    @dragend="onDragEnd"
                >
                    <KanbanCard
                        v-bind="card"
                        :dragging="draggingCard === card.id"
                    />
                </div>

                <!-- Drop placeholder -->
                <div v-if="dragOverCol === col.id && draggingCard !== null" class="kb__placeholder" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { PlusIcon } from '@lucide/vue';
import KanbanCard from '@/Components/App/KanbanCard.vue';

interface CardData {
    id:          string;
    title:       string;
    desc?:       string;
    priority:    'High' | 'Medium' | 'Low';
    tag?:        string;
    tagColor?:   string;
    dueDate?:    string;
    assignees:   string[];
    attachments: number;
}

interface Column {
    id:     string;
    label:  string;
    color:  string;
    cards:  CardData[];
}

const props = defineProps({
    columns: { type: Array as () => Column[], default: null },
});

const emit = defineEmits<{
    'card-moved': [cardId: string, fromCol: string, toCol: string];
    'card-added': [colId: string];
}>();

function defaultColumns(): Column[] {
    return [
        {
            id: 'lead', label: 'Lead', color: '#6366f1',
            cards: [
                { id: 'c1', title: 'Acme Corp renewal', priority: 'High',   assignees: ['Alice Martin', 'Bob Chen'], attachments: 2, dueDate: '2026-05-30', tag: 'Enterprise', tagColor: 'primary' },
                { id: 'c2', title: 'Beta Ltd intro call', priority: 'Medium', assignees: ['Carol Smith'], attachments: 0, dueDate: '2026-06-05' },
            ],
        },
        {
            id: 'qualified', label: 'Qualified', color: '#8b5cf6',
            cards: [
                { id: 'c3', title: 'Gamma Inc proposal', priority: 'High', assignees: ['Dave Wilson'], attachments: 3, dueDate: '2026-05-22', tag: 'SaaS', tagColor: 'warning' },
            ],
        },
        {
            id: 'proposal', label: 'Proposal', color: '#f59e0b',
            cards: [
                { id: 'c4', title: 'Delta Systems — 50 seats', priority: 'Medium', assignees: ['Alice Martin', 'Eve Johnson'], attachments: 1, dueDate: '2026-05-28' },
                { id: 'c5', title: 'Eta Networks security audit', priority: 'Low', assignees: ['Bob Chen'], attachments: 0, dueDate: '2026-06-15' },
            ],
        },
        {
            id: 'negotiation', label: 'Negotiation', color: '#ec4899',
            cards: [
                { id: 'c6', title: 'Zeta Corp custom plan', priority: 'High', assignees: ['Carol Smith', 'Dave Wilson'], attachments: 4, dueDate: '2026-05-20', tag: 'Urgent', tagColor: 'danger' },
            ],
        },
        {
            id: 'won', label: 'Won', color: '#10b981',
            cards: [
                { id: 'c7', title: 'Omega Ltd — Enterprise', priority: 'Medium', assignees: ['Alice Martin'], attachments: 2, dueDate: '2026-05-10', tag: 'Closed', tagColor: 'success' },
            ],
        },
    ];
}

const board       = reactive<Column[]>(props.columns?.length ? props.columns : defaultColumns());
const draggingCard = ref<string | null>(null);
const draggingFrom = ref<string | null>(null);
const dragOverCol  = ref<string | null>(null);

function onDragStart(cardId: string, colId: string) {
    draggingCard.value = cardId;
    draggingFrom.value = colId;
}

function onDragEnd() {
    draggingCard.value = null;
    draggingFrom.value = null;
    dragOverCol.value  = null;
}

function onDrop(toColId: string) {
    if (!draggingCard.value || !draggingFrom.value) return;
    if (draggingFrom.value === toColId) { dragOverCol.value = null; return; }

    const fromCol = board.find(c => c.id === draggingFrom.value);
    const toCol   = board.find(c => c.id === toColId);
    if (!fromCol || !toCol) return;

    const idx  = fromCol.cards.findIndex(c => c.id === draggingCard.value);
    if (idx === -1) return;

    const [card] = fromCol.cards.splice(idx, 1);
    toCol.cards.push(card);

    emit('card-moved', card.id, draggingFrom.value, toColId);
    dragOverCol.value = null;
}

function addCard(colId: string) {
    const col = board.find(c => c.id === colId);
    if (!col) return;
    const id = `c${Date.now()}`;
    col.cards.push({ id, title: 'New card', priority: 'Medium', assignees: [], attachments: 0 });
    emit('card-added', colId);
}
</script>

<style scoped>
.kb {
    display: flex; gap: 14px; overflow-x: auto;
    padding-bottom: 8px; align-items: flex-start;
}
.kb__col {
    flex: 0 0 240px; min-width: 240px;
    display: flex; flex-direction: column; gap: 8px;
}

.kb__col-head {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 4px 6px;
}
.kb__col-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.kb__col-label {
    font-size: 12px; font-weight: 700; color: var(--color-text-primary);
    text-transform: uppercase; letter-spacing: 0.06em; flex: 1;
}
.kb__col-count {
    font-size: 11px; font-weight: 700; color: var(--color-text-subtle);
    background: var(--color-bg-subtle); border-radius: 99px;
    padding: 1px 7px;
}
.kb__col-add {
    display: flex; align-items: center; justify-content: center;
    width: 24px; height: 24px; border-radius: 6px; border: none;
    background: transparent; cursor: pointer; color: var(--color-text-subtle);
    transition: all 120ms ease;
}
.kb__col-add:hover { background: var(--color-bg-subtle); color: var(--color-text-primary); }

.kb__cards {
    display: flex; flex-direction: column; gap: 8px;
    min-height: 80px; border-radius: 10px;
    padding: 4px; transition: background 150ms ease;
}
.kb__cards--over { background: rgba(99,102,241,0.07); }

.kb__card-wrap { cursor: grab; }
.kb__placeholder {
    height: 60px; border: 2px dashed rgba(99,102,241,0.4);
    border-radius: 10px; background: rgba(99,102,241,0.05);
}
</style>
