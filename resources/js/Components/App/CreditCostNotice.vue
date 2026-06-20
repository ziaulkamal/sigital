<!--
    resources/js/Components/App/CreditCostNotice.vue
    Banner preflight biaya credit. Validasi server tetap sumber kebenaran;
    ini hanya informatif. Tersembunyi bila user bebas-credit (Enterprise/SuperAdmin).
-->
<template>
    <div v-if="!isExempt" class="cost-notice" :class="{ 'cost-notice--low': !enough }">
        <WalletIcon :size="16" />
        <span>
            Tindakan ini memerlukan <strong>{{ cost }} credit</strong>. Saldo Anda:
        <!-- cost dari prop atau config bersama (kind) -->
            <strong>{{ balance.toLocaleString('id-ID') }}</strong>.
            <template v-if="!enough"> — saldo tidak cukup, <a href="/credits">topup di sini</a>.</template>
        </span>
    </div>
    <div v-else class="cost-notice cost-notice--exempt">
        <WalletIcon :size="16" />
        <span>Akun Anda bebas-credit (Enterprise aktif / SuperAdmin).</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { WalletIcon } from '@lucide/vue';

// Beri `cost` langsung, atau `kind` ('event'|'template') untuk mengambil dari config bersama.
const props = defineProps<{ cost?: number; kind?: 'event' | 'template' }>();

const page = usePage();
const user = computed<any>(() => (page.props.auth as any)?.user ?? {});
const creditCfg = computed<any>(() => (page.props.app as any)?.credit ?? {});
const isExempt = computed<boolean>(() => user.value?.is_credit_exempt ?? false);
const balance = computed<number>(() => user.value?.credit_balance ?? 0);
const cost = computed<number>(() => {
    if (typeof props.cost === 'number') return props.cost;
    if (props.kind === 'event') return creditCfg.value.cost_event ?? 0;
    if (props.kind === 'template') return creditCfg.value.cost_template ?? 0;
    return 0;
});
const enough = computed<boolean>(() => balance.value >= cost.value);
</script>

<style scoped>
.cost-notice {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 12px; border-radius: 10px; font-size: 13px;
    background: var(--color-bg-subtle, #f1f5f9); color: var(--color-text-primary);
    border: 1px solid var(--color-border); margin-bottom: 14px;
}
.cost-notice--low { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
.cost-notice--exempt { background: #ecfdf5; border-color: #a7f3d0; color: #047857; }
.cost-notice a { color: inherit; font-weight: 700; text-decoration: underline; }
</style>
