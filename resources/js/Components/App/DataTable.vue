<template>
    <div class="dt">
        <!-- Toolbar -->
        <div class="dt__toolbar">
            <div class="dt__search-wrap">
                <SearchIcon :size="13" class="dt__search-icon" />
                <input v-model="search" class="dt__search" type="text" :placeholder="searchPlaceholder" />
                <button v-if="search" class="dt__search-clear" @click="search = ''">
                    <XIcon :size="12" />
                </button>
            </div>
            <slot name="toolbar" />
        </div>

        <!-- Bulk action bar -->
        <Transition name="dt-bulk">
            <div v-if="selected.size > 0" class="dt__bulk">
                <span class="dt__bulk-count">{{ selected.size }} selected</span>
                <slot name="bulk-actions" :selected="[...selected]" />
                <button class="dt__bulk-clear" @click="selected.clear()">
                    <XIcon :size="13" />
                    Clear
                </button>
            </div>
        </Transition>

        <!-- Table -->
        <div class="dt__wrap">
            <table class="dt__table">
                <thead>
                    <tr>
                        <th v-if="selectable" class="dt__th dt__th--check">
                            <input
                                type="checkbox"
                                class="dt__check"
                                :checked="allSelected"
                                :indeterminate="someSelected"
                                @change="toggleAll"
                            />
                        </th>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="dt__th"
                            :class="{ 'dt__th--sortable': col.sortable }"
                            :style="col.width ? { width: col.width } : {}"
                            @click="col.sortable && onSort(col.key)"
                        >
                            <div class="dt__th-inner">
                                {{ col.label }}
                                <span v-if="col.sortable" class="dt__sort-icon">
                                    <ChevronUpIcon v-if="sortKey === col.key && sortDir === 'asc'"  :size="12" />
                                    <ChevronDownIcon v-else-if="sortKey === col.key && sortDir === 'desc'" :size="12" />
                                    <ChevronsUpDownIcon v-else :size="12" class="dt__sort-idle" />
                                </span>
                            </div>
                        </th>
                        <th v-if="hasRowActions" class="dt__th dt__th--actions" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="paged.length === 0">
                        <td :colspan="colSpan" class="dt__empty">
                            <slot name="empty">
                                <span style="color: var(--color-text-subtle); font-size: 13px;">No results found.</span>
                            </slot>
                        </td>
                    </tr>
                    <tr
                        v-for="row in paged"
                        :key="rowKey(row)"
                        class="dt__row"
                        :class="{ 'dt__row--selected': selected.has(rowKey(row)) }"
                    >
                        <td v-if="selectable" class="dt__td dt__td--check">
                            <input
                                type="checkbox"
                                class="dt__check"
                                :checked="selected.has(rowKey(row))"
                                @change="toggleRow(rowKey(row))"
                            />
                        </td>
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            class="dt__td"
                            :class="col.class"
                        >
                            <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                                {{ row[col.key] }}
                            </slot>
                        </td>
                        <td v-if="hasRowActions" class="dt__td dt__td--actions">
                            <slot name="row-actions" :row="row" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="filtered.length > 0" class="dt__footer">
            <AppPagination
                v-model="page"
                :total="filtered.length"
                :per-page="perPage"
                @update:per-page="onPerPage"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, useSlots } from 'vue';
import { SearchIcon, XIcon, ChevronUpIcon, ChevronDownIcon, ChevronsUpDownIcon } from '@lucide/vue';
import AppPagination from '@/Components/App/AppPagination.vue';

export interface Column {
    key:      string;
    label:    string;
    sortable?: boolean;
    width?:   string;
    class?:   string;
}

type Row = Record<string, unknown>;

const props = defineProps({
    columns:           { type: Array as () => Column[], default: () => [] },
    rows:              { type: Array as () => Row[],    default: () => [] },
    rowId:             { type: String,  default: 'id' },
    selectable:        { type: Boolean, default: false },
    searchPlaceholder: { type: String,  default: 'Search…' },
    searchKeys:        { type: Array as () => string[], default: () => [] },
    defaultPerPage:    { type: Number,  default: 10 },
});

const slots = useSlots();

const search  = ref('');
const sortKey = ref('');
const sortDir = ref<'asc' | 'desc'>('asc');
const page    = ref(1);
const perPage = ref(props.defaultPerPage);
const selected = ref(new Set<string | number>());

const hasRowActions = computed(() => !!slots['row-actions']);
const colSpan = computed(() =>
    props.columns.length + (props.selectable ? 1 : 0) + (hasRowActions.value ? 1 : 0)
);

function rowKey(row: Row): string | number {
    return row[props.rowId] as string | number;
}

const filtered = computed(() => {
    let data = [...props.rows];
    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        const keys = props.searchKeys.length ? props.searchKeys : props.columns.map(c => c.key);
        data = data.filter(row => keys.some(k => String(row[k] ?? '').toLowerCase().includes(q)));
    }
    if (sortKey.value) {
        data.sort((a, b) => {
            const av = a[sortKey.value] ?? '';
            const bv = b[sortKey.value] ?? '';
            const cmp = String(av).localeCompare(String(bv), undefined, { numeric: true });
            return sortDir.value === 'asc' ? cmp : -cmp;
        });
    }
    return data;
});

const paged = computed(() => {
    const start = (page.value - 1) * perPage.value;
    return filtered.value.slice(start, start + perPage.value);
});

const allSelected  = computed(() => paged.value.length > 0 && paged.value.every(r => selected.value.has(rowKey(r))));
const someSelected = computed(() => paged.value.some(r => selected.value.has(rowKey(r))) && !allSelected.value);

function onSort(key: string) {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'asc';
    }
    page.value = 1;
}

function toggleAll() {
    if (allSelected.value) {
        paged.value.forEach(r => selected.value.delete(rowKey(r)));
    } else {
        paged.value.forEach(r => selected.value.add(rowKey(r)));
    }
}

function toggleRow(id: string | number) {
    if (selected.value.has(id)) selected.value.delete(id);
    else selected.value.add(id);
}

function onPerPage(n: number) {
    perPage.value = n;
    page.value = 1;
}
</script>

<style scoped>
.dt { display: flex; flex-direction: column; gap: 0; }

.dt__toolbar {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--color-border);
}
.dt__search-wrap {
    display: flex; align-items: center; gap: 7px;
    flex: 1; max-width: 280px;
    border: 1.5px solid var(--color-border); border-radius: 8px;
    padding: 6px 10px; background: var(--color-bg-subtle);
}
.dt__search-icon { color: var(--color-text-subtle); flex-shrink: 0; }
.dt__search {
    flex: 1; border: none; background: transparent; outline: none;
    font-size: 12.5px; color: var(--color-text-primary); font-family: var(--font-sans);
}
.dt__search::placeholder { color: var(--color-text-subtle); }
.dt__search-clear {
    display: flex; align-items: center; justify-content: center;
    border: none; background: transparent; cursor: pointer;
    color: var(--color-text-subtle); padding: 0; flex-shrink: 0;
}
.dt__search-clear:hover { color: var(--color-text-muted); }

.dt__bulk {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 16px;
    background: color-mix(in srgb, #6366f1 8%, transparent);
    border-bottom: 1px solid color-mix(in srgb, #6366f1 20%, transparent);
}
.dt__bulk-count { font-size: 12.5px; font-weight: 600; color: #6366f1; margin-right: 4px; }
.dt__bulk-clear {
    display: inline-flex; align-items: center; gap: 4px;
    margin-left: auto; font-size: 12px; font-weight: 500;
    color: var(--color-text-muted); border: none; background: transparent;
    cursor: pointer; font-family: var(--font-sans); padding: 0;
}
.dt__bulk-clear:hover { color: var(--color-text-primary); }

.dt-bulk-enter-active, .dt-bulk-leave-active { transition: all 180ms ease; }
.dt-bulk-enter-from, .dt-bulk-leave-to { opacity: 0; transform: translateY(-6px); }

.dt__wrap { overflow-x: auto; }
.dt__table { width: 100%; border-collapse: collapse; }

.dt__th {
    text-align: left; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.07em;
    color: var(--color-text-subtle);
    padding: 10px 14px; border-bottom: 1.5px solid var(--color-border);
    white-space: nowrap; user-select: none;
}
.dt__th--check { width: 40px; padding: 10px 12px; }
.dt__th--actions { width: 60px; }
.dt__th--sortable { cursor: pointer; }
.dt__th--sortable:hover { color: var(--color-text-primary); }
.dt__th-inner { display: flex; align-items: center; gap: 4px; }
.dt__sort-icon { display: flex; align-items: center; }
.dt__sort-idle { color: var(--color-text-subtle); opacity: 0.5; }

.dt__row { transition: background 100ms ease; }
.dt__row:hover { background: var(--color-bg-subtle); }
.dt__row--selected { background: color-mix(in srgb, #6366f1 5%, transparent); }

.dt__td {
    padding: 11px 14px; border-bottom: 1px solid var(--color-border);
    font-size: 13px; color: var(--color-text-primary); vertical-align: middle;
}
.dt__td--check { padding: 11px 12px; width: 40px; }
.dt__td--actions { padding: 8px 12px; text-align: right; }
.dt__row:last-child .dt__td { border-bottom: none; }

.dt__check {
    width: 15px; height: 15px; border-radius: 4px;
    accent-color: #6366f1; cursor: pointer;
}

.dt__empty { padding: 40px; text-align: center; }

.dt__footer {
    padding: 12px 16px;
    border-top: 1px solid var(--color-border);
}
</style>
