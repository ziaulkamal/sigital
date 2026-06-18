<template>
    <footer class="app-footer">
        <!-- Tautan halaman legal (publik) -->
        <nav class="app-footer__links" aria-label="Tautan legal">
            <Link href="/legal/syarat-ketentuan" class="app-footer__link">Syarat &amp; Ketentuan</Link>
            <span class="app-footer__sep" aria-hidden="true">·</span>
            <Link href="/legal/kebijakan-privasi" class="app-footer__link">Kebijakan Privasi</Link>
            <span class="app-footer__sep" aria-hidden="true">·</span>
            <Link href="/legal/cookie" class="app-footer__link">Cookie</Link>
        </nav>

        <!-- Credit pembuat + hak cipta -->
        <p class="app-footer__credit">
            Dibuat oleh <strong>Diskominsa Aceh Barat Daya</strong>
            <span class="app-footer__sep" aria-hidden="true">·</span>
            © {{ year }} Pemerintah Kabupaten Aceh Barat Daya
        </p>
    </footer>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

// Tahun dihitung dinamis (tidak di-hardcode).
const year = computed<number>(() => new Date().getFullYear());
</script>

<style scoped>
.app-footer {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 16px 12px;
    text-align: center;
    font-size: 12.5px;
    color: var(--color-text-muted);
}
.app-footer__links {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.app-footer__link {
    color: var(--color-text-muted);
    text-decoration: none;
    font-weight: 600;
    transition: color 120ms ease;
}
.app-footer__link:hover { color: var(--color-primary); }
.app-footer__sep { color: var(--color-text-subtle); }
.app-footer__credit { color: var(--color-text-subtle); line-height: 1.5; }
.app-footer__credit strong { color: var(--color-text-muted); font-weight: 700; }
</style>
