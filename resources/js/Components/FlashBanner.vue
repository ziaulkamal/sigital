<!--
    resources/js/Components/FlashBanner.vue
    Banner notifikasi dari flash session (success/error) yang dibagikan via Inertia.
-->
<template>
    <transition name="flash">
        <div v-if="message" class="flash" :class="`flash--${type}`">
            <span>{{ message }}</span>
            <button class="flash__close" @click="dismissed = true">×</button>
        </div>
    </transition>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const dismissed = ref(false);

const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });
const type = computed<'success' | 'error'>(() => (flash.value.error ? 'error' : 'success'));
const message = computed(() => (dismissed.value ? null : flash.value.error || flash.value.success || null));

// Reset saat pesan baru muncul setelah navigasi.
watch(() => flash.value.success || flash.value.error, () => { dismissed.value = false; });
</script>

<style scoped>
.flash { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 11px 16px; border-radius: 10px; font-size: 13.5px; font-weight: 500; margin-bottom: 16px; }
.flash--success { background: #dcfce7; color: #15803d; }
.flash--error { background: #fee2e2; color: #b91c1c; }
.flash__close { background: none; border: none; font-size: 18px; line-height: 1; cursor: pointer; color: inherit; opacity: 0.6; }
.flash__close:hover { opacity: 1; }
.flash-enter-active, .flash-leave-active { transition: opacity 0.2s; }
.flash-enter-from, .flash-leave-to { opacity: 0; }
</style>
