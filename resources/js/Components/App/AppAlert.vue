<template>
    <Transition name="alert">
        <div v-if="visible" class="app-alert" :class="`app-alert--${type}`" role="alert">
            <span class="app-alert__icon" aria-hidden="true">
                <svg v-if="type === 'success'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                <svg v-else-if="type === 'error'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <svg v-else-if="type === 'warning'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </span>
            <div class="app-alert__content">
                <p v-if="title" class="app-alert__title">{{ title }}</p>
                <p class="app-alert__desc"><slot>{{ description }}</slot></p>
            </div>
            <button v-if="dismissible" type="button" class="app-alert__close" aria-label="Dismiss" @click="visible = false; $emit('dismiss')">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                    <path d="M1 1L11 11M11 1L1 11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { ref } from 'vue';

defineProps({
    type:        { type: String,  default: 'info' }, // info|success|warning|error
    title:       { type: String,  default: '' },
    description: { type: String,  default: '' },
    dismissible: { type: Boolean, default: false },
});
defineEmits(['dismiss']);

const visible = ref<boolean>(true);
</script>

<style scoped>
.app-alert {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 12px 14px; border-radius: var(--radius-md);
    border: 1.5px solid transparent;
}
.app-alert--info    { background: rgba(59,130,246,0.08);  border-color: rgba(59,130,246,0.2);  color: #1d4ed8; }
.app-alert--success { background: rgba(16,185,129,0.08);  border-color: rgba(16,185,129,0.2);  color: #047857; }
.app-alert--warning { background: rgba(245,158,11,0.08);  border-color: rgba(245,158,11,0.2);  color: #b45309; }
.app-alert--error   { background: rgba(239,68,68,0.08);   border-color: rgba(239,68,68,0.2);   color: #b91c1c; }

.app-alert__icon { display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 1px; }

.app-alert__content { flex: 1; min-width: 0; }
.app-alert__title { font-size: 13px; font-weight: 600; margin-bottom: 2px; }
.app-alert__desc  { font-size: 12.5px; line-height: 1.5; opacity: 0.85; }

.app-alert__close {
    flex-shrink: 0; border: none; background: transparent; cursor: pointer;
    color: inherit; opacity: 0.6; padding: 2px; border-radius: 4px;
    display: flex; align-items: center;
    transition: opacity 120ms ease;
}
.app-alert__close:hover { opacity: 1; }

.alert-enter-active, .alert-leave-active { transition: all 200ms ease; }
.alert-enter-from, .alert-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
