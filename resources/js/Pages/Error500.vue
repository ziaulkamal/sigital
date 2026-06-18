<template>
    <div class="err">
        <div class="err__bg" aria-hidden="true">
            <div class="err__blob err__blob--1" />
            <div class="err__blob err__blob--2" />
        </div>
        <svg class="err__grid" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <defs>
                <pattern id="grid500" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="0.5" opacity="0.3"/>
                </pattern>
            </defs>
            <rect width="400" height="300" fill="url(#grid500)" />
        </svg>

        <div class="err__card">
            <div class="err__icon">
                <AlertTriangleIcon :size="40" style="color: #f59e0b;" />
            </div>
            <div class="err__code err__code--500">500</div>
            <h1 class="err__title">Something went wrong</h1>
            <p class="err__desc">
                Our servers encountered an unexpected error. We've been notified and are working on a fix.
            </p>
            <div class="err__actions">
                <button class="err__btn err__btn--primary" @click="reload">
                    <RefreshCwIcon :size="15" />
                    Try Again
                </button>
                <a href="/" class="err__btn err__btn--ghost">
                    <HomeIcon :size="15" />
                    Go to Dashboard
                </a>
            </div>
            <p class="err__id">Error reference: <code>ERR_500_{{ errorId }}</code></p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { AlertTriangleIcon, RefreshCwIcon, HomeIcon } from '@lucide/vue';

const errorId = Math.random().toString(36).slice(2, 10).toUpperCase();
function reload() { window.location.reload(); }
</script>

<style scoped>
.err {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: var(--color-bg); position: relative; overflow: hidden;
    font-family: var(--font-sans);
}
.err__bg { position: absolute; inset: 0; pointer-events: none; }
.err__blob {
    position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.1;
}
.err__blob--1 { width: 400px; height: 400px; background: #f59e0b; top: -100px; right: -100px; }
.err__blob--2 { width: 300px; height: 300px; background: #ef4444; bottom: -80px; left: -80px; }
.err__grid {
    position: absolute; inset: 0; width: 100%; height: 100%;
    color: var(--color-border); pointer-events: none;
}
.err__card {
    position: relative; z-index: 1; text-align: center;
    display: flex; flex-direction: column; align-items: center; gap: 14px;
    padding: 48px 32px;
}
.err__icon {
    width: 72px; height: 72px; border-radius: 20px;
    background: rgba(245,158,11,0.1); display: flex; align-items: center; justify-content: center;
    margin-bottom: 4px;
}
.err__code {
    font-size: 100px; font-weight: 900; line-height: 1;
    font-family: var(--font-heading); letter-spacing: -0.04em;
    background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.err__title {
    font-size: 24px; font-weight: 800; color: var(--color-text-primary);
    font-family: var(--font-heading); margin: 0;
}
.err__desc {
    font-size: 15px; color: var(--color-text-muted); max-width: 400px;
    line-height: 1.6; margin: 0;
}
.err__actions { display: flex; align-items: center; gap: 12px; margin-top: 8px; }
.err__btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 22px; border-radius: 10px; font-size: 13.5px; font-weight: 600;
    font-family: var(--font-sans); cursor: pointer; transition: all 150ms ease;
    text-decoration: none; border: none;
}
.err__btn--primary { background: #f59e0b; color: #fff; }
.err__btn--primary:hover { background: #d97706; }
.err__btn--ghost {
    background: transparent; color: var(--color-text-muted);
    border: 1.5px solid var(--color-border);
}
.err__btn--ghost:hover { border-color: var(--color-text-muted); color: var(--color-text-primary); background: var(--color-surface); }
.err__id { font-size: 11.5px; color: var(--color-text-subtle); margin: 4px 0 0; }
.err__id code { font-family: var(--font-mono); background: var(--color-bg-subtle); padding: 1px 5px; border-radius: 4px; }
</style>
