<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <div class="page__header">
                <h1 class="page__title">File Upload</h1>
                <p class="page__sub">Drag & drop file upload with progress simulation</p>
            </div>

            <div class="demo-grid">
                <div class="demo-card demo-card--wide">
                    <p class="demo-card__label">Multiple Files</p>
                    <FileDropzone accept="*" :multiple="true" hint="Any file type up to 10 MB" />
                </div>
                <div class="demo-card">
                    <p class="demo-card__label">Images Only</p>
                    <FileDropzone accept="image/*" :multiple="true" hint="PNG, JPG, GIF up to 5 MB" />
                </div>
                <div class="demo-card">
                    <p class="demo-card__label">Single File</p>
                    <FileDropzone accept=".pdf,.doc,.docx" :multiple="false" hint="PDF or Word document" />
                </div>
            </div>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import BaseLayout  from '@/Layouts/BaseLayout.vue';
import FileDropzone from '@/Components/App/FileDropzone.vue';
import { navGroups } from '@/data/navGroups';
</script>

<style scoped>
.page { padding: 24px; display: flex; flex-direction: column; gap: 24px; }
.page__header { }
.page__title { font-size: 20px; font-weight: 800; color: var(--color-text-primary); font-family: var(--font-heading); letter-spacing: -0.02em; }
.page__sub   { font-size: 13px; color: var(--color-text-muted); margin-top: 2px; }
.demo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
.demo-card {
    background: var(--color-surface); border: 1.5px solid var(--color-border);
    border-radius: 14px; padding: 20px; display: flex; flex-direction: column; gap: 14px;
}
.demo-card--wide { grid-column: 1 / -1; }
.demo-card__label { font-size: 12px; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.07em; margin: 0; }
</style>
