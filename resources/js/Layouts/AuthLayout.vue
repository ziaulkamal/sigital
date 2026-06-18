<template>
    <!-- Judul head mengikuti halaman (prop title), suffix brand ditambah di app.ts. -->
    <Head :title="title" />

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
                class="flex items-center justify-center w-14 h-14 rounded-xl shadow-lg overflow-hidden p-1.5"
                style="background-color: var(--color-surface); border: 1px solid var(--color-border);"
            >
                <img :src="brand.logo ?? logoAbdya" :alt="brand.name" class="w-full h-full object-contain" />
            </div>
            <h1
                class="text-xl font-bold tracking-tight"
                style="color: var(--color-text-primary); font-family: var(--font-heading);"
            >
                {{ brand.name }}
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

        <!-- Footer khusus halaman (mis. tautan kembali ke login) -->
        <div v-if="$slots.footer" class="relative mt-6 flex items-center gap-4 text-sm" style="color: var(--color-text-muted);">
            <slot name="footer" />
        </div>

        <!-- Footer bersama: tautan legal + credit pembuat (sebelum login) -->
        <div class="relative mt-2 w-full max-w-md">
            <AppFooter />
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
import { Head, usePage } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme';
import { Sun, Moon } from '@lucide/vue';
import AppFooter from '@/Components/App/AppFooter.vue';

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

// Logo default: lambang Pemerintah Kabupaten Aceh Barat Daya (dipakai bila brand.logo kosong).
const logoAbdya = '/images/logo-abdya.png';

const page = usePage();
const brand = computed(() => {
    const a = page.props.app as { name?: string; logo?: string | null } | undefined;
    return { name: a?.name ?? 'SIGITAL', logo: a?.logo ?? null };
});

const { isDark, toggleTheme } = useTheme();
</script>
