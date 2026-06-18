<template>
    <div
        class="min-h-screen flex flex-col items-center justify-center p-4"
        :class="{ dark: isDark }"
        style="background-color: var(--color-bg);"
    >
        <!-- Background decoration -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
            <div
                class="absolute -top-40 -right-40 w-80 h-80 rounded-full opacity-10 blur-3xl"
                style="background-color: var(--color-primary);"
            />
            <div
                class="absolute -bottom-40 -left-40 w-80 h-80 rounded-full opacity-10 blur-3xl"
                style="background-color: var(--color-info);"
            />
        </div>

        <!-- Logo / Brand -->
        <div class="relative mb-8 flex flex-col items-center gap-3">
            <div
                class="flex items-center justify-center w-12 h-12 rounded-xl shadow-lg"
                style="background-color: var(--color-primary);"
            >
                <svg width="20" height="20" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 2L14 5V11L8 14L2 11V5L8 2Z" fill="white" fill-opacity="0.9"/>
                </svg>
            </div>
            <h1
                class="text-xl font-bold tracking-tight"
                style="color: var(--color-text-primary); font-family: var(--font-heading);"
            >
                CRM Studio
            </h1>
        </div>

        <!-- Card -->
        <div
            class="relative w-full max-w-md rounded-2xl border p-8 shadow-xl"
            style="background-color: var(--color-surface); border-color: var(--color-border);"
        >
            <!-- Title slot -->
            <div v-if="$slots.title || title" class="mb-6">
                <slot name="title">
                    <h2
                        class="text-2xl font-bold"
                        style="color: var(--color-text-primary); font-family: var(--font-heading);"
                    >
                        {{ title }}
                    </h2>
                    <p v-if="subtitle" class="mt-1 text-sm" style="color: var(--color-text-muted);">
                        {{ subtitle }}
                    </p>
                </slot>
            </div>

            <!-- Main content -->
            <slot />
        </div>

        <!-- Footer links -->
        <div class="relative mt-6 flex items-center gap-4 text-sm" style="color: var(--color-text-muted);">
            <slot name="footer">
                <span>© {{ year }} CRM Studio</span>
            </slot>
        </div>

        <!-- Theme toggle -->
        <button
            class="fixed top-4 right-4 p-2 rounded-lg transition-colors hover:bg-[--color-bg-subtle]"
            :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            @click="toggleTheme"
        >
            <component
                :is="isDark ? Sun : Moon"
                :size="18"
                style="color: var(--color-text-muted);"
            />
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useTheme } from '@/Composables/useTheme';
import { Sun, Moon } from '@lucide/vue';

defineProps({
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: '',
    },
});

const { isDark, toggleTheme } = useTheme();
const year = computed<number>(() => new Date().getFullYear());
</script>
