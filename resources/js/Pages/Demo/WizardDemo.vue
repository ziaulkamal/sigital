<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <div class="page__header">
                <h1 class="page__title">Wizard Form</h1>
                <p class="page__sub">Multi-step form with animated transitions</p>
            </div>

            <div class="demo-wrap">
                <WizardForm :steps="steps" submit-label="Create Contact" :submitting="submitting" @submit="onSubmit">
                    <template #step-0>
                        <div class="wdemo__step">
                            <h3 class="wdemo__step-title">Basic Information</h3>
                            <p class="wdemo__step-desc">Enter the contact's basic details.</p>
                            <div class="wdemo__fields">
                                <div class="wdemo__field">
                                    <label class="wdemo__label">First Name</label>
                                    <AppInput v-model="form.firstName" placeholder="First name" />
                                </div>
                                <div class="wdemo__field">
                                    <label class="wdemo__label">Last Name</label>
                                    <AppInput v-model="form.lastName" placeholder="Last name" />
                                </div>
                                <div class="wdemo__field wdemo__field--full">
                                    <label class="wdemo__label">Email Address</label>
                                    <AppInput v-model="form.email" type="email" placeholder="email@example.com" />
                                </div>
                                <div class="wdemo__field wdemo__field--full">
                                    <label class="wdemo__label">Phone</label>
                                    <AppInput v-model="form.phone" type="tel" placeholder="+1 555-0100" />
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #step-1>
                        <div class="wdemo__step">
                            <h3 class="wdemo__step-title">Company Details</h3>
                            <p class="wdemo__step-desc">Link this contact to a company.</p>
                            <div class="wdemo__fields">
                                <div class="wdemo__field wdemo__field--full">
                                    <label class="wdemo__label">Company Name</label>
                                    <AppInput v-model="form.company" placeholder="Acme Corp" />
                                </div>
                                <div class="wdemo__field wdemo__field--full">
                                    <label class="wdemo__label">Job Title</label>
                                    <AppInput v-model="form.jobTitle" placeholder="CEO, Sales Manager…" />
                                </div>
                                <div class="wdemo__field wdemo__field--full">
                                    <label class="wdemo__label">Status</label>
                                    <AppSelect v-model="form.status" :options="statusOptions" />
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #step-2>
                        <div class="wdemo__step">
                            <h3 class="wdemo__step-title">Notes & Tags</h3>
                            <p class="wdemo__step-desc">Add any extra context for this contact.</p>
                            <div class="wdemo__fields">
                                <div class="wdemo__field wdemo__field--full">
                                    <label class="wdemo__label">Notes</label>
                                    <AppTextarea v-model="form.notes" placeholder="Add notes about this contact…" :rows="4" />
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #step-3>
                        <div class="wdemo__step">
                            <h3 class="wdemo__step-title">Review & Confirm</h3>
                            <p class="wdemo__step-desc">Please confirm the details before submitting.</p>
                            <div class="wdemo__review">
                                <div class="wdemo__review-row"><span>Name</span><strong>{{ form.firstName }} {{ form.lastName }}</strong></div>
                                <div class="wdemo__review-row"><span>Email</span><strong>{{ form.email || '—' }}</strong></div>
                                <div class="wdemo__review-row"><span>Phone</span><strong>{{ form.phone || '—' }}</strong></div>
                                <div class="wdemo__review-row"><span>Company</span><strong>{{ form.company || '—' }}</strong></div>
                                <div class="wdemo__review-row"><span>Job Title</span><strong>{{ form.jobTitle || '—' }}</strong></div>
                                <div class="wdemo__review-row"><span>Status</span><strong>{{ form.status }}</strong></div>
                            </div>
                            <AppAlert v-if="submitted" type="success" class="wdemo__success">
                                Contact created successfully!
                            </AppAlert>
                        </div>
                    </template>
                </WizardForm>
            </div>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import BaseLayout  from '@/Layouts/BaseLayout.vue';
import WizardForm  from '@/Components/App/WizardForm.vue';
import AppInput    from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppSelect   from '@/Components/App/AppSelect.vue';
import AppAlert    from '@/Components/App/AppAlert.vue';
import { navGroups } from '@/data/navGroups';

const steps = [
    { label: 'Basic Info',   desc: 'Name & contact' },
    { label: 'Company',      desc: 'Org details' },
    { label: 'Notes',        desc: 'Extra context' },
    { label: 'Review',       desc: 'Confirm & submit' },
];

const statusOptions = [
    { label: 'Lead',     value: 'Lead' },
    { label: 'Prospect', value: 'Prospect' },
    { label: 'Customer', value: 'Customer' },
    { label: 'Churned',  value: 'Churned' },
];

const form = ref({
    firstName: '', lastName: '', email: '', phone: '',
    company: '', jobTitle: '', status: 'Lead', notes: '',
});

const submitting = ref(false);
const submitted  = ref(false);

function onSubmit() {
    submitting.value = true;
    setTimeout(() => { submitting.value = false; submitted.value = true; }, 1500);
}
</script>

<style scoped>
.page { padding: 24px; display: flex; flex-direction: column; gap: 24px; max-width: 720px; }
.page__header { }
.page__title { font-size: 20px; font-weight: 800; color: var(--color-text-primary); font-family: var(--font-heading); letter-spacing: -0.02em; }
.page__sub   { font-size: 13px; color: var(--color-text-muted); margin-top: 2px; }
.demo-wrap   { }

.wdemo__step { display: flex; flex-direction: column; gap: 16px; }
.wdemo__step-title { font-size: 15px; font-weight: 700; color: var(--color-text-primary); margin: 0; }
.wdemo__step-desc  { font-size: 13px; color: var(--color-text-muted); margin: 0; }
.wdemo__fields { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.wdemo__field { display: flex; flex-direction: column; gap: 6px; }
.wdemo__field--full { grid-column: 1 / -1; }
.wdemo__label { font-size: 12px; font-weight: 600; color: var(--color-text-muted); }

.wdemo__review { display: flex; flex-direction: column; gap: 0; }
.wdemo__review-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 0; border-bottom: 1px solid var(--color-border);
    font-size: 13px;
}
.wdemo__review-row:last-child { border-bottom: none; }
.wdemo__review-row span { color: var(--color-text-muted); }
.wdemo__review-row strong { color: var(--color-text-primary); }
.wdemo__success { margin-top: 8px; }
</style>
