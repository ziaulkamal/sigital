<template>
    <div
        class="mli"
        :class="{ 'mli--unread': !read, 'mli--active': active, 'mli--selected': selected }"
        @click="emit('click')"
    >
        <!-- Checkbox -->
        <input type="checkbox" class="mli__check" :checked="selected" @click.stop @change="emit('select')" />

        <!-- Avatar -->
        <AppAvatar :name="from" size="sm" class="mli__avatar" />

        <!-- Content -->
        <div class="mli__body">
            <div class="mli__row">
                <span class="mli__from">{{ from }}</span>
                <span class="mli__time">{{ time }}</span>
            </div>
            <div class="mli__row">
                <span class="mli__subject">{{ subject }}</span>
                <div class="mli__badges">
                    <span v-if="starred" class="mli__star mli__star--on">★</span>
                    <AppBadge v-if="label" :color="labelColor" size="sm">{{ label }}</AppBadge>
                </div>
            </div>
            <p class="mli__preview">{{ preview }}</p>
        </div>

        <!-- Unread dot -->
        <div v-if="!read" class="mli__dot" />
    </div>
</template>

<script setup lang="ts">
import AppAvatar from '@/Components/App/AppAvatar.vue';
import AppBadge  from '@/Components/App/AppBadge.vue';

defineProps({
    from:       { type: String,  default: '' },
    subject:    { type: String,  default: '' },
    preview:    { type: String,  default: '' },
    time:       { type: String,  default: '' },
    read:       { type: Boolean, default: false },
    active:     { type: Boolean, default: false },
    selected:   { type: Boolean, default: false },
    starred:    { type: Boolean, default: false },
    label:      { type: String,  default: '' },
    labelColor: { type: String,  default: 'primary' },
});

const emit = defineEmits<{
    click:  [];
    select: [];
}>();
</script>

<style scoped>
.mli {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 12px 14px; cursor: pointer; position: relative;
    border-bottom: 1px solid var(--color-border);
    transition: background 100ms ease;
}
.mli:hover { background: var(--color-bg-subtle); }
.mli--active { background: color-mix(in srgb, #6366f1 7%, transparent); }
.mli--selected { background: color-mix(in srgb, #6366f1 5%, transparent); }

.mli__check {
    flex-shrink: 0; align-self: center; margin-top: 2px;
    accent-color: #6366f1; width: 14px; height: 14px; cursor: pointer;
}
.mli__avatar { flex-shrink: 0; margin-top: 1px; }

.mli__body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.mli__row  { display: flex; align-items: center; justify-content: space-between; gap: 8px; }

.mli__from {
    font-size: 12.5px; font-weight: 600; color: var(--color-text-primary);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.mli--unread .mli__from { font-weight: 700; }
.mli__time {
    font-size: 11px; color: var(--color-text-subtle); white-space: nowrap; flex-shrink: 0;
}
.mli--unread .mli__time { font-weight: 600; color: var(--color-text-muted); }

.mli__subject {
    font-size: 12px; color: var(--color-text-muted);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1;
}
.mli--unread .mli__subject { font-weight: 600; color: var(--color-text-primary); }

.mli__badges { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }
.mli__star { font-size: 12px; color: var(--color-text-subtle); }
.mli__star--on { color: #f59e0b; }

.mli__preview {
    font-size: 11.5px; color: var(--color-text-subtle); margin: 0;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.mli__dot {
    position: absolute; top: 50%; right: 14px; transform: translateY(-50%);
    width: 7px; height: 7px; border-radius: 50%; background: #6366f1;
    flex-shrink: 0;
}
</style>
