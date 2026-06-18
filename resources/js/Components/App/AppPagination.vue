<template>
    <div class="pgn">
        <!-- Per-page selector -->
        <div class="pgn__per">
            <span class="pgn__per-label">Rows</span>
            <select class="pgn__per-select" :value="perPage" @change="onPerPage">
                <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
            </select>
        </div>

        <!-- Info -->
        <span class="pgn__info">
            {{ from }}–{{ to }} of {{ total }}
        </span>

        <!-- Buttons -->
        <div class="pgn__btns">
            <button class="pgn__btn" :disabled="modelValue === 1" @click="emit('update:modelValue', 1)" aria-label="First page">
                <ChevronsLeftIcon :size="14" />
            </button>
            <button class="pgn__btn" :disabled="modelValue === 1" @click="emit('update:modelValue', modelValue - 1)" aria-label="Previous page">
                <ChevronLeftIcon :size="14" />
            </button>

            <button
                v-for="p in pages"
                :key="p"
                class="pgn__btn pgn__btn--page"
                :class="{ 'pgn__btn--active': p === modelValue, 'pgn__btn--ellipsis': p === '…' }"
                :disabled="p === '…'"
                @click="typeof p === 'number' && emit('update:modelValue', p)"
            >
                {{ p }}
            </button>

            <button class="pgn__btn" :disabled="modelValue === lastPage" @click="emit('update:modelValue', modelValue + 1)" aria-label="Next page">
                <ChevronRightIcon :size="14" />
            </button>
            <button class="pgn__btn" :disabled="modelValue === lastPage" @click="emit('update:modelValue', lastPage)" aria-label="Last page">
                <ChevronsRightIcon :size="14" />
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon, ChevronsLeftIcon, ChevronsRightIcon } from '@lucide/vue';

const props = defineProps({
    modelValue:    { type: Number,  default: 1 },
    total:         { type: Number,  default: 0 },
    perPage:       { type: Number,  default: 10 },
    perPageOptions:{ type: Array as () => number[], default: () => [10, 25, 50, 100] },
});

const emit = defineEmits<{
    'update:modelValue': [page: number];
    'update:perPage':    [n: number];
}>();

const lastPage = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)));
const from     = computed(() => Math.min((props.modelValue - 1) * props.perPage + 1, props.total));
const to       = computed(() => Math.min(props.modelValue * props.perPage, props.total));

const pages = computed<(number | string)[]>(() => {
    const last = lastPage.value;
    const cur  = props.modelValue;
    if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
    const result: (number | string)[] = [1];
    if (cur > 3)              result.push('…');
    for (let i = Math.max(2, cur - 1); i <= Math.min(last - 1, cur + 1); i++) result.push(i);
    if (cur < last - 2)       result.push('…');
    result.push(last);
    return result;
});

function onPerPage(e: Event) {
    emit('update:perPage', Number((e.target as HTMLSelectElement).value));
    emit('update:modelValue', 1);
}
</script>

<style scoped>
.pgn {
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.pgn__per { display: flex; align-items: center; gap: 6px; }
.pgn__per-label { font-size: 12px; color: var(--color-text-muted); }
.pgn__per-select {
    font-size: 12px; font-family: var(--font-sans);
    border: 1.5px solid var(--color-border); border-radius: 7px;
    padding: 4px 8px; background: var(--color-surface);
    color: var(--color-text-primary); outline: none; cursor: pointer;
}
.pgn__info {
    font-size: 12px; color: var(--color-text-muted);
    margin-right: auto;
}
.pgn__btns { display: flex; gap: 2px; }
.pgn__btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 30px; height: 30px; padding: 0 6px;
    border-radius: 7px; border: 1.5px solid transparent;
    background: transparent; cursor: pointer;
    font-size: 12.5px; font-weight: 500; font-family: var(--font-sans);
    color: var(--color-text-muted);
    transition: all 120ms ease;
}
.pgn__btn:hover:not(:disabled):not(.pgn__btn--ellipsis) {
    background: var(--color-bg-subtle);
    border-color: var(--color-border);
    color: var(--color-text-primary);
}
.pgn__btn:disabled { opacity: 0.35; cursor: not-allowed; }
.pgn__btn--active {
    background: #6366f1; color: #fff;
    border-color: #6366f1;
}
.pgn__btn--active:hover { background: #4f46e5; border-color: #4f46e5; color: #fff; }
.pgn__btn--ellipsis { cursor: default; }
</style>
