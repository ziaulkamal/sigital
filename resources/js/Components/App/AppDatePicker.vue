<template>
    <div class="dp" ref="wrapRef">
        <!-- Trigger input -->
        <div
            class="dp__input"
            :class="{ 'dp__input--open': open, 'dp__input--range': range }"
            @click="open = !open"
        >
            <CalendarIcon :size="14" class="dp__input-icon" />
            <span class="dp__input-text" :class="{ 'dp__input-text--placeholder': !displayText }">
                {{ displayText || placeholder }}
            </span>
            <button v-if="displayText" class="dp__input-clear" @click.stop="clear">
                <XIcon :size="12" />
            </button>
        </div>

        <!-- Dropdown panel -->
        <Transition name="dp-drop">
            <div v-if="open" class="dp__panel">
                <!-- Header -->
                <div class="dp__head">
                    <button class="dp__nav" @click="prevMonth"><ChevronLeftIcon :size="15" /></button>
                    <button class="dp__month-label" @click="viewMode = viewMode === 'days' ? 'months' : 'days'">
                        {{ monthLabel }} {{ viewYear }}
                    </button>
                    <button class="dp__nav" @click="nextMonth"><ChevronRightIcon :size="15" /></button>
                </div>

                <!-- Month picker -->
                <div v-if="viewMode === 'months'" class="dp__months">
                    <button
                        v-for="(m, i) in MONTHS"
                        :key="m"
                        class="dp__month-btn"
                        :class="{ 'dp__month-btn--active': i === viewMonth }"
                        @click="viewMonth = i; viewMode = 'days'"
                    >{{ m }}</button>
                </div>

                <!-- Days grid -->
                <template v-else>
                    <div class="dp__weekdays">
                        <span v-for="d in WEEKDAYS" :key="d" class="dp__weekday">{{ d }}</span>
                    </div>
                    <div class="dp__days">
                        <button
                            v-for="cell in cells"
                            :key="cell.key"
                            class="dp__day"
                            :class="{
                                'dp__day--outside':  cell.outside,
                                'dp__day--today':    cell.today,
                                'dp__day--selected': cell.selected,
                                'dp__day--range-in': cell.inRange,
                                'dp__day--range-start': cell.rangeStart,
                                'dp__day--range-end':   cell.rangeEnd,
                            }"
                            :disabled="cell.disabled"
                            @click="selectDay(cell.date)"
                            @mouseenter="hovering = cell.date"
                        >
                            {{ cell.day }}
                        </button>
                    </div>
                </template>

                <!-- Footer -->
                <div class="dp__footer">
                    <button class="dp__today-btn" @click="goToday">Today</button>
                    <button v-if="range && rangeStart && !rangeEnd" class="dp__range-hint">Pick end date</button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { CalendarIcon, XIcon, ChevronLeftIcon, ChevronRightIcon } from '@lucide/vue';

const WEEKDAYS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
const MONTHS   = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

const props = defineProps({
    modelValue: { default: null },
    range:      { type: Boolean, default: false },
    placeholder:{ type: String,  default: 'Pick a date' },
    minDate:    { default: null },
    maxDate:    { default: null },
    format:     { type: String,  default: 'MMM D, YYYY' },
});

const emit = defineEmits<{
    'update:modelValue': [value: Date | [Date, Date] | null];
}>();

const wrapRef    = ref<HTMLElement | null>(null);
const open       = ref(false);
const viewMode   = ref<'days' | 'months'>('days');
const hovering   = ref<Date | null>(null);

const today = new Date();
today.setHours(0,0,0,0);

const viewYear  = ref(today.getFullYear());
const viewMonth = ref(today.getMonth());

const rangeStart = ref<Date | null>(null);
const rangeEnd   = ref<Date | null>(null);
const single     = ref<Date | null>(null);

watch(() => props.modelValue, (v) => {
    if (!v) { single.value = null; rangeStart.value = null; rangeEnd.value = null; return; }
    if (props.range && Array.isArray(v)) { rangeStart.value = v[0]; rangeEnd.value = v[1]; }
    else if (!props.range) { single.value = v as Date; }
}, { immediate: true });

const monthLabel = computed(() => MONTHS[viewMonth.value]);

function fmt(d: Date): string {
    return props.format
        .replace('YYYY', String(d.getFullYear()))
        .replace('MMM',  MONTHS[d.getMonth()])
        .replace('MM',   String(d.getMonth() + 1).padStart(2, '0'))
        .replace('D',    String(d.getDate()))
        .replace('DD',   String(d.getDate()).padStart(2, '0'));
}

const displayText = computed(() => {
    if (props.range) {
        if (rangeStart.value && rangeEnd.value)
            return `${fmt(rangeStart.value)} — ${fmt(rangeEnd.value)}`;
        if (rangeStart.value) return fmt(rangeStart.value);
        return '';
    }
    return single.value ? fmt(single.value) : '';
});

const cells = computed(() => {
    const first = new Date(viewYear.value, viewMonth.value, 1);
    const startPad = first.getDay();
    const result = [];
    for (let i = startPad - 1; i >= 0; i--) {
        const d = new Date(viewYear.value, viewMonth.value, -i);
        result.push(makeCell(d, true));
    }
    const days = new Date(viewYear.value, viewMonth.value + 1, 0).getDate();
    for (let i = 1; i <= days; i++) {
        result.push(makeCell(new Date(viewYear.value, viewMonth.value, i), false));
    }
    while (result.length % 7 !== 0) {
        const d = new Date(viewYear.value, viewMonth.value + 1, result.length - days - startPad + 1);
        result.push(makeCell(d, true));
    }
    return result;
});

function makeCell(date: Date, outside: boolean) {
    const ts   = date.getTime();
    const hov  = hovering.value?.getTime();
    const rs   = rangeStart.value?.getTime();
    const re   = rangeEnd.value?.getTime();
    const sel  = single.value?.getTime();
    const isToday   = ts === today.getTime();
    const selected  = props.range ? (ts === rs || ts === re) : ts === sel;
    const rangeStart_ = props.range && ts === rs;
    const rangeEnd_   = props.range && ts === re;
    const inRange = props.range && rs && (re
        ? ts > rs && ts < re
        : hov ? (ts > Math.min(rs, hov) && ts < Math.max(rs, hov)) : false);
    const disabled = !!(
        (props.minDate && ts < (props.minDate as Date).getTime()) ||
        (props.maxDate && ts > (props.maxDate as Date).getTime())
    );
    return { date, day: date.getDate(), key: ts, outside, today: isToday, selected, inRange, rangeStart: rangeStart_, rangeEnd: rangeEnd_, disabled };
}

function selectDay(d: Date) {
    if (!props.range) {
        single.value = d;
        emit('update:modelValue', d);
        open.value = false;
        return;
    }
    if (!rangeStart.value || (rangeStart.value && rangeEnd.value)) {
        rangeStart.value = d; rangeEnd.value = null;
    } else {
        if (d < rangeStart.value) { rangeEnd.value = rangeStart.value; rangeStart.value = d; }
        else rangeEnd.value = d;
        emit('update:modelValue', [rangeStart.value, rangeEnd.value]);
        open.value = false;
    }
}

function clear() {
    single.value = null; rangeStart.value = null; rangeEnd.value = null;
    emit('update:modelValue', null);
}

function goToday() {
    viewYear.value = today.getFullYear(); viewMonth.value = today.getMonth();
    if (!props.range) { selectDay(new Date(today)); }
}

function prevMonth() {
    if (viewMonth.value === 0) { viewMonth.value = 11; viewYear.value--; }
    else viewMonth.value--;
}
function nextMonth() {
    if (viewMonth.value === 11) { viewMonth.value = 0; viewYear.value++; }
    else viewMonth.value++;
}

function onOutside(e: MouseEvent) {
    if (wrapRef.value && !wrapRef.value.contains(e.target as Node)) open.value = false;
}
onMounted(() => document.addEventListener('mousedown', onOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', onOutside));
</script>

<style scoped>
.dp { position: relative; display: inline-block; }

.dp__input {
    display: flex; align-items: center; gap: 8px;
    border: 1.5px solid var(--color-border); border-radius: 9px;
    padding: 8px 12px; background: var(--color-surface);
    cursor: pointer; min-width: 180px; transition: border-color 150ms ease;
    user-select: none;
}
.dp__input:hover { border-color: color-mix(in srgb, #6366f1 40%, var(--color-border)); }
.dp__input--open { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
.dp__input-icon { color: var(--color-text-subtle); flex-shrink: 0; }
.dp__input-text { flex: 1; font-size: 13px; color: var(--color-text-primary); }
.dp__input-text--placeholder { color: var(--color-text-subtle); }
.dp__input-clear {
    display: flex; align-items: center; border: none; background: transparent;
    cursor: pointer; color: var(--color-text-subtle); padding: 0;
}
.dp__input-clear:hover { color: var(--color-text-primary); }

.dp__panel {
    position: absolute; top: calc(100% + 6px); left: 0; z-index: 200;
    background: var(--color-surface); border: 1.5px solid var(--color-border);
    border-radius: 12px; padding: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    min-width: 260px;
}

.dp-drop-enter-active, .dp-drop-leave-active { transition: all 160ms ease; }
.dp-drop-enter-from, .dp-drop-leave-to { opacity: 0; transform: translateY(-6px) scale(0.97); }

.dp__head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 10px;
}
.dp__nav {
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 7px; border: none;
    background: transparent; cursor: pointer; color: var(--color-text-muted);
    transition: background 120ms ease;
}
.dp__nav:hover { background: var(--color-bg-subtle); color: var(--color-text-primary); }
.dp__month-label {
    font-size: 13px; font-weight: 700; color: var(--color-text-primary);
    border: none; background: transparent; cursor: pointer;
    font-family: var(--font-sans); padding: 4px 8px; border-radius: 6px;
    transition: background 120ms ease;
}
.dp__month-label:hover { background: var(--color-bg-subtle); }

.dp__months {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; margin-bottom: 8px;
}
.dp__month-btn {
    padding: 8px 4px; border-radius: 7px; border: none;
    background: transparent; cursor: pointer; font-size: 12.5px;
    font-family: var(--font-sans); color: var(--color-text-muted);
    transition: all 120ms ease;
}
.dp__month-btn:hover { background: var(--color-bg-subtle); color: var(--color-text-primary); }
.dp__month-btn--active { background: #6366f1; color: #fff; font-weight: 600; }
.dp__month-btn--active:hover { background: #4f46e5; }

.dp__weekdays {
    display: grid; grid-template-columns: repeat(7, 1fr); margin-bottom: 4px;
}
.dp__weekday {
    text-align: center; font-size: 10.5px; font-weight: 700;
    color: var(--color-text-subtle); text-transform: uppercase;
    letter-spacing: 0.06em; padding: 4px 0;
}

.dp__days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; }
.dp__day {
    aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
    border-radius: 7px; border: none; background: transparent;
    font-size: 12.5px; font-family: var(--font-sans); cursor: pointer;
    color: var(--color-text-primary); transition: all 100ms ease;
}
.dp__day:hover:not(:disabled) { background: var(--color-bg-subtle); }
.dp__day--outside { color: var(--color-text-subtle); }
.dp__day--today { font-weight: 700; color: #6366f1; }
.dp__day--selected { background: #6366f1 !important; color: #fff; font-weight: 600; border-radius: 7px; }
.dp__day--range-in { background: rgba(99,102,241,0.1); border-radius: 0; }
.dp__day--range-start { background: #6366f1; color: #fff; border-radius: 7px 0 0 7px; }
.dp__day--range-end   { background: #6366f1; color: #fff; border-radius: 0 7px 7px 0; }
.dp__day:disabled { opacity: 0.3; cursor: not-allowed; }

.dp__footer {
    display: flex; align-items: center; gap: 8px; margin-top: 10px;
    padding-top: 10px; border-top: 1px solid var(--color-border);
}
.dp__today-btn {
    font-size: 12px; font-weight: 500; color: #6366f1;
    border: none; background: transparent; cursor: pointer;
    font-family: var(--font-sans); padding: 0;
}
.dp__today-btn:hover { text-decoration: underline; }
.dp__range-hint {
    font-size: 11.5px; color: var(--color-text-subtle);
    border: none; background: transparent; padding: 0; font-family: var(--font-sans);
}
</style>
