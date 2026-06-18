<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <ChatLayout />
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import BaseLayout from '@/Layouts/BaseLayout.vue';
import ChatLayout from '@/Components/App/ChatLayout.vue';
import { navGroups } from '@/data/navGroups';
</script>

<style scoped>
.page { padding: 24px; height: 100%; display: flex; flex-direction: column; }
.page > * { flex: 1; min-height: 0; }
</style>
