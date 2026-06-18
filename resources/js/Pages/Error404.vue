<template>
    <div class="err">
        <div class="err__bg" aria-hidden="true">
            <div class="err__blob err__blob--1" />
            <div class="err__blob err__blob--2" />
        </div>

        <div class="err__card">
            <div class="err__code">404</div>
            <h1 class="err__title">Page not found</h1>
            <p class="err__desc">
                The page you're looking for doesn't exist or has been moved.
            </p>
            <div class="err__actions">
                <a href="/" class="err__btn err__btn--primary">
                    <HomeIcon :size="15" />
                    Go to Dashboard
                </a>
                <button class="err__btn err__btn--ghost" @click="history.back()">
                    <ArrowLeftIcon :size="15" />
                    Go Back
                </button>
            </div>
        </div>

        <!-- Decorative grid lines -->
        <svg class="err__grid" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <defs>
                <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="0.5" opacity="0.3"/>
                </pattern>
            </defs>
            <rect width="400" height="300" fill="url(#grid)" />
        </svg>
    </div>
</template>

<script setup lang="ts">
import { HomeIcon, ArrowLeftIcon } from '@lucide/vue';
</script>

<style scoped>
.err {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: var(--color-bg); position: relative; overflow: hidden;
    font-family: var(--font-sans);
}
.err__bg { position: absolute; inset: 0; pointer-events: none; }
.err__blob {
    position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.12;
}
.err__blob--1 {
    width: 400px; height: 400px; background: #6366f1;
    top: -100px; right: -100px;
}
.err__blob--2 {
    width: 300px; height: 300px; background: #8b5cf6;
    bottom: -80px; left: -80px;
}
.err__grid {
    position: absolute; inset: 0; width: 100%; height: 100%;
    color: var(--color-border); pointer-events: none;
}
.err__card {
    position: relative; z-index: 1; text-align: center;
    display: flex; flex-direction: column; align-items: center; gap: 16px;
    padding: 48px 32px;
}
.err__code {
    font-size: 120px; font-weight: 900; line-height: 1;
    font-family: var(--font-heading); letter-spacing: -0.04em;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.err__title {
    font-size: 24px; font-weight: 800; color: var(--color-text-primary);
    font-family: var(--font-heading); margin: 0;
}
.err__desc {
    font-size: 15px; color: var(--color-text-muted); max-width: 360px;
    line-height: 1.6; margin: 0;
}
.err__actions { display: flex; align-items: center; gap: 12px; margin-top: 8px; }
.err__btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 22px; border-radius: 10px; font-size: 13.5px; font-weight: 600;
    font-family: var(--font-sans); cursor: pointer; transition: all 150ms ease;
    text-decoration: none; border: none;
}
.err__btn--primary { background: #6366f1; color: #fff; }
.err__btn--primary:hover { background: #4f46e5; }
.err__btn--ghost {
    background: transparent; color: var(--color-text-muted);
    border: 1.5px solid var(--color-border);
}
.err__btn--ghost:hover { border-color: var(--color-text-muted); color: var(--color-text-primary); background: var(--color-surface); }
</style>
