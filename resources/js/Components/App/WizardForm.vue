<template>
    <div class="wiz">
        <!-- Stepper header -->
        <div class="wiz__header">
            <WizardStepper :steps="steps" :current="current" />
        </div>

        <!-- Step content -->
        <div class="wiz__body">
            <Transition :name="transName" mode="out-in">
                <div :key="current" class="wiz__step">
                    <slot :name="`step-${current}`" />
                </div>
            </Transition>
        </div>

        <!-- Footer navigation -->
        <div class="wiz__footer">
            <button
                v-if="current > 0"
                class="wiz__btn wiz__btn--back"
                type="button"
                @click="prev"
            >
                <ChevronLeftIcon :size="14" />
                Back
            </button>
            <div class="wiz__footer-right">
                <button
                    v-if="current < steps.length - 1"
                    class="wiz__btn wiz__btn--next"
                    type="button"
                    @click="next"
                >
                    Next
                    <ChevronRightIcon :size="14" />
                </button>
                <button
                    v-else
                    class="wiz__btn wiz__btn--submit"
                    type="button"
                    :disabled="submitting"
                    @click="submit"
                >
                    <CheckIcon v-if="!submitting" :size="14" />
                    <span v-if="submitting" class="wiz__spinner" />
                    {{ submitting ? 'Submitting…' : submitLabel }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon, CheckIcon } from '@lucide/vue';
import WizardStepper, { type Step } from '@/Components/App/WizardStepper.vue';

const props = defineProps({
    steps:       { type: Array as () => Step[], default: () => [] },
    submitLabel: { type: String, default: 'Submit' },
    submitting:  { type: Boolean, default: false },
});

const emit = defineEmits<{
    'step-change': [index: number];
    'submit': [];
}>();

const current   = ref(0);
const direction = ref<'forward' | 'back'>('forward');
const transName = ref('wiz-fwd');

function next() {
    if (current.value >= props.steps.length - 1) return;
    direction.value = 'forward';
    transName.value = 'wiz-fwd';
    current.value++;
    emit('step-change', current.value);
}

function prev() {
    if (current.value <= 0) return;
    direction.value = 'back';
    transName.value = 'wiz-bck';
    current.value--;
    emit('step-change', current.value);
}

function submit() {
    emit('submit');
}

defineExpose({ current, next, prev });
</script>

<style scoped>
.wiz {
    display: flex; flex-direction: column; gap: 0;
    background: var(--color-surface); border: 1.5px solid var(--color-border);
    border-radius: 16px; overflow: hidden;
}

.wiz__header {
    padding: 24px 28px;
    border-bottom: 1px solid var(--color-border);
    background: var(--color-bg-subtle);
}

.wiz__body { padding: 28px; flex: 1; min-height: 200px; overflow: hidden; }

.wiz__step { /* content fills body */ }

/* Forward transition */
.wiz-fwd-enter-active, .wiz-fwd-leave-active { transition: all 220ms ease; }
.wiz-fwd-enter-from { opacity: 0; transform: translateX(24px); }
.wiz-fwd-leave-to   { opacity: 0; transform: translateX(-24px); }

/* Back transition */
.wiz-bck-enter-active, .wiz-bck-leave-active { transition: all 220ms ease; }
.wiz-bck-enter-from { opacity: 0; transform: translateX(-24px); }
.wiz-bck-leave-to   { opacity: 0; transform: translateX(24px); }

.wiz__footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 28px;
    border-top: 1px solid var(--color-border);
    background: var(--color-bg-subtle);
}
.wiz__footer-right { display: flex; align-items: center; gap: 10px; margin-left: auto; }

.wiz__btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 600;
    font-family: var(--font-sans); cursor: pointer; border: none;
    transition: all 150ms ease;
}
.wiz__btn--back {
    background: transparent; color: var(--color-text-muted);
    border: 1.5px solid var(--color-border);
}
.wiz__btn--back:hover { color: var(--color-text-primary); border-color: var(--color-text-muted); background: var(--color-surface); }
.wiz__btn--next   { background: #6366f1; color: #fff; }
.wiz__btn--next:hover { background: #4f46e5; }
.wiz__btn--submit { background: #10b981; color: #fff; }
.wiz__btn--submit:hover { background: #059669; }
.wiz__btn--submit:disabled { opacity: 0.6; cursor: not-allowed; }

.wiz__spinner {
    width: 13px; height: 13px; border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.35); border-top-color: #fff;
    animation: wiz-spin 600ms linear infinite;
}
@keyframes wiz-spin { to { transform: rotate(360deg); } }
</style>
