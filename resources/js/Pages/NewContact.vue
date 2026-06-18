<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <AppBreadcrumb :crumbs="[{ label: 'Contacts', href: '/contacts' }, { label: 'New Contact' }]" />
            <div class="page__header">
                <h1 class="page__title">Add New Contact</h1>
                <p class="page__sub">Fill in the details to create a new CRM contact</p>
            </div>

            <WizardForm
                :steps="steps"
                submit-label="Create Contact"
                :submitting="submitting"
                @submit="onSubmit"
            >
                <!-- Step 0: Personal -->
                <template #step-0>
                    <div class="nc__step">
                        <h3 class="nc__step-title">Personal Information</h3>
                        <p class="nc__step-desc">Basic contact details</p>
                        <div class="nc__fields">
                            <div class="nc__field">
                                <label class="nc__label">First Name *</label>
                                <AppInput v-model="form.firstName" placeholder="First name" />
                            </div>
                            <div class="nc__field">
                                <label class="nc__label">Last Name *</label>
                                <AppInput v-model="form.lastName" placeholder="Last name" />
                            </div>
                            <div class="nc__field nc__field--full">
                                <label class="nc__label">Email Address *</label>
                                <AppInput v-model="form.email" type="email" placeholder="email@company.com" />
                            </div>
                            <div class="nc__field nc__field--full">
                                <label class="nc__label">Phone</label>
                                <AppInput v-model="form.phone" type="tel" placeholder="+1 555-0100" />
                            </div>
                            <div class="nc__field nc__field--full">
                                <label class="nc__label">Location</label>
                                <AppInput v-model="form.location" placeholder="City, Country" />
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Step 1: Company -->
                <template #step-1>
                    <div class="nc__step">
                        <h3 class="nc__step-title">Company Details</h3>
                        <p class="nc__step-desc">Link this contact to a company</p>
                        <div class="nc__fields">
                            <div class="nc__field nc__field--full">
                                <label class="nc__label">Company Name</label>
                                <AppInput v-model="form.company" placeholder="Acme Corp" />
                            </div>
                            <div class="nc__field nc__field--full">
                                <label class="nc__label">Job Title</label>
                                <AppInput v-model="form.title" placeholder="CEO, Sales Manager…" />
                            </div>
                            <div class="nc__field">
                                <label class="nc__label">Status</label>
                                <AppSelect v-model="form.status" :options="statusOptions" />
                            </div>
                            <div class="nc__field">
                                <label class="nc__label">Source</label>
                                <AppSelect v-model="form.source" :options="sourceOptions" />
                            </div>
                            <div class="nc__field nc__field--full">
                                <label class="nc__label">LinkedIn URL</label>
                                <AppInput v-model="form.linkedin" placeholder="https://linkedin.com/in/…" />
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Step 2: Assign & Tags -->
                <template #step-2>
                    <div class="nc__step">
                        <h3 class="nc__step-title">Assignment & Notes</h3>
                        <p class="nc__step-desc">Assign an owner and add context</p>
                        <div class="nc__fields">
                            <div class="nc__field nc__field--full">
                                <label class="nc__label">Assign to</label>
                                <AppSelect v-model="form.owner" :options="ownerOptions" />
                            </div>
                            <div class="nc__field nc__field--full">
                                <label class="nc__label">Notes</label>
                                <AppTextarea v-model="form.notes" placeholder="Add any notes about this contact…" :rows="4" :maxlength="500" />
                            </div>
                            <div class="nc__field nc__field--full">
                                <label class="nc__label">
                                    <AppCheckbox v-model="form.sendWelcome" label="Send welcome email to contact" />
                                </label>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Step 3: Review -->
                <template #step-3>
                    <div class="nc__step">
                        <h3 class="nc__step-title">Review & Confirm</h3>
                        <p class="nc__step-desc">Please verify the details before creating</p>
                        <div class="nc__review">
                            <div class="nc__review-section">
                                <p class="nc__review-heading">Personal</p>
                                <div class="nc__review-row"><span>Name</span><strong>{{ form.firstName }} {{ form.lastName }}</strong></div>
                                <div class="nc__review-row"><span>Email</span><strong>{{ form.email || '—' }}</strong></div>
                                <div class="nc__review-row"><span>Phone</span><strong>{{ form.phone || '—' }}</strong></div>
                                <div class="nc__review-row"><span>Location</span><strong>{{ form.location || '—' }}</strong></div>
                            </div>
                            <div class="nc__review-section">
                                <p class="nc__review-heading">Company</p>
                                <div class="nc__review-row"><span>Company</span><strong>{{ form.company || '—' }}</strong></div>
                                <div class="nc__review-row"><span>Title</span><strong>{{ form.title || '—' }}</strong></div>
                                <div class="nc__review-row"><span>Status</span><strong>{{ form.status }}</strong></div>
                                <div class="nc__review-row"><span>Source</span><strong>{{ form.source }}</strong></div>
                            </div>
                            <div class="nc__review-section">
                                <p class="nc__review-heading">Assignment</p>
                                <div class="nc__review-row"><span>Owner</span><strong>{{ form.owner }}</strong></div>
                                <div class="nc__review-row"><span>Welcome email</span><strong>{{ form.sendWelcome ? 'Yes' : 'No' }}</strong></div>
                            </div>
                        </div>
                        <AppAlert v-if="created" type="success">
                            Contact <strong>{{ form.firstName }} {{ form.lastName }}</strong> created successfully!
                        </AppAlert>
                    </div>
                </template>
            </WizardForm>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import BaseLayout    from '@/Layouts/BaseLayout.vue';
import WizardForm    from '@/Components/App/WizardForm.vue';
import AppInput      from '@/Components/App/AppInput.vue';
import AppTextarea   from '@/Components/App/AppTextarea.vue';
import AppSelect     from '@/Components/App/AppSelect.vue';
import AppCheckbox   from '@/Components/App/AppCheckbox.vue';
import AppAlert      from '@/Components/App/AppAlert.vue';
import AppBreadcrumb from '@/Components/App/AppBreadcrumb.vue';
import { navGroups } from '@/data/navGroups';

const steps = [
    { label: 'Personal',   desc: 'Name & contact' },
    { label: 'Company',    desc: 'Org & role' },
    { label: 'Assignment', desc: 'Owner & notes' },
    { label: 'Review',     desc: 'Confirm & save' },
];

const statusOptions = [
    { label: 'Lead',     value: 'Lead' },
    { label: 'Prospect', value: 'Prospect' },
    { label: 'Customer', value: 'Customer' },
    { label: 'Churned',  value: 'Churned' },
];

const sourceOptions = [
    { label: 'Inbound',  value: 'Inbound' },
    { label: 'Outbound', value: 'Outbound' },
    { label: 'Referral', value: 'Referral' },
    { label: 'Event',    value: 'Event' },
    { label: 'Other',    value: 'Other' },
];

const ownerOptions = [
    { label: 'Alice Martin',  value: 'Alice Martin' },
    { label: 'Bob Chen',      value: 'Bob Chen' },
    { label: 'Carol Smith',   value: 'Carol Smith' },
    { label: 'Dave Wilson',   value: 'Dave Wilson' },
];

const form = ref({
    firstName: '', lastName: '', email: '', phone: '', location: '',
    company: '', title: '', status: 'Lead', source: 'Inbound', linkedin: '',
    owner: 'Alice Martin', notes: '', sendWelcome: true,
});

const submitting = ref(false);
const created    = ref(false);

function onSubmit() {
    submitting.value = true;
    setTimeout(() => { submitting.value = false; created.value = true; }, 1500);
}
</script>

<style scoped>
.page { padding: 24px; display: flex; flex-direction: column; gap: 20px; max-width: 760px; }
.page__header { }
.page__title { font-size: 20px; font-weight: 800; color: var(--color-text-primary); font-family: var(--font-heading); letter-spacing: -0.02em; }
.page__sub   { font-size: 13px; color: var(--color-text-muted); margin-top: 2px; }

.nc__step       { display: flex; flex-direction: column; gap: 16px; }
.nc__step-title { font-size: 15px; font-weight: 700; color: var(--color-text-primary); margin: 0; }
.nc__step-desc  { font-size: 13px; color: var(--color-text-muted); margin: 0; }
.nc__fields     { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.nc__field      { display: flex; flex-direction: column; gap: 6px; }
.nc__field--full{ grid-column: 1 / -1; }
.nc__label      { font-size: 12px; font-weight: 600; color: var(--color-text-muted); }

.nc__review { display: flex; flex-direction: column; gap: 16px; }
.nc__review-section {
    background: var(--color-bg-subtle); border: 1px solid var(--color-border);
    border-radius: 10px; padding: 14px; display: flex; flex-direction: column; gap: 8px;
}
.nc__review-heading { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--color-text-subtle); margin: 0 0 4px; }
.nc__review-row {
    display: flex; align-items: center; justify-content: space-between;
    font-size: 13px;
}
.nc__review-row span  { color: var(--color-text-muted); }
.nc__review-row strong{ color: var(--color-text-primary); }
</style>
