import '../css/app.css';

import '@fontsource-variable/dm-sans';
import '@fontsource/jetbrains-mono/400.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { showFlash } from '@/Composables/useSwal';

type FlashProps = { flash?: { success?: string | null; error?: string | null } };

// Bridge flash → SweetAlert toast pada setiap kunjungan Inertia (sukses/gagal/peringatan).
router.on('success', (event) => {
    showFlash((event.detail.page.props as FlashProps).flash);
});

// Nama brand dibaca dari meta tag (di-render Blade dari config sigital.brand.name) —
// suffix judul tidak di-hardcode di JS.
const appName =
    document.querySelector('meta[name="app-name"]')?.getAttribute('content')?.trim() || 'SIGITAL';

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),

    resolve: (name: string) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),

    setup({ el, App, props, plugin }) {
        // Flash dari load awal (mis. redirect setelah login) — router 'success' tidak terpicu di sini.
        showFlash((props.initialPage.props as FlashProps).flash);

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },

    progress: {
        color: 'var(--color-primary)',
        showSpinner: false,
    },
});
