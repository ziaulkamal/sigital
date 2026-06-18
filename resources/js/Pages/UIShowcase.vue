<template>
    <BaseLayout>
        <AppToast />

        <div class="sc-root">

            <!-- ── Sticky left nav ──────────────────────────── -->
            <aside class="sc-nav">
                <p class="sc-nav__heading">Form Controls</p>
                <a v-for="c in NAV_FORM"   :key="c.id" :href="`#${c.id}`" class="sc-nav__link">{{ c.name }}</a>
                <p class="sc-nav__heading">Display</p>
                <a v-for="c in NAV_DISPLAY" :key="c.id" :href="`#${c.id}`" class="sc-nav__link">{{ c.name }}</a>
                <p class="sc-nav__heading">Feedback</p>
                <a v-for="c in NAV_FEEDBACK" :key="c.id" :href="`#${c.id}`" class="sc-nav__link">{{ c.name }}</a>
                <p class="sc-nav__heading">Overlay</p>
                <a v-for="c in NAV_OVERLAY" :key="c.id" :href="`#${c.id}`" class="sc-nav__link">{{ c.name }}</a>
                <p class="sc-nav__heading">Structure</p>
                <a v-for="c in NAV_STRUCT"  :key="c.id" :href="`#${c.id}`" class="sc-nav__link">{{ c.name }}</a>
            </aside>

            <!-- ── Main content ─────────────────────────────── -->
            <main class="sc-main">

                <!-- Hero -->
                <div class="sc-hero">
                    <div class="sc-hero__eyebrow">
                        <span class="sc-chip sc-chip--violet">Batch 02</span>
                        <span class="sc-chip sc-chip--green">TypeScript</span>
                        <span class="sc-chip sc-chip--blue">Vue 3</span>
                    </div>
                    <h1 class="sc-hero__title">Component Library</h1>
                    <p class="sc-hero__sub">23 interactive components — previews, variants, dan state lengkap.</p>
                </div>

                <!-- ════════════ FORM CONTROLS ════════════ -->
                <div class="sc-category-label">Form Controls</div>

                <!-- AppButton -->
                <section id="button" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppButton</span>
                        <span class="sc-section__desc">7 variant · 4 size · loading · icon slots</span>
                    </div>
                    <div class="sc-grid sc-grid--3">
                        <div class="sc-card">
                            <p class="sc-card__label">Variant</p>
                            <div class="sc-preview sc-preview--wrap">
                                <AppButton variant="primary">Primary</AppButton>
                                <AppButton variant="secondary">Secondary</AppButton>
                                <AppButton variant="ghost">Ghost</AppButton>
                                <AppButton variant="outline">Outline</AppButton>
                                <AppButton variant="danger">Danger</AppButton>
                                <AppButton variant="success">Success</AppButton>
                                <AppButton variant="warning">Warning</AppButton>
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Size</p>
                            <div class="sc-preview sc-preview--wrap">
                                <AppButton size="xs">XSmall</AppButton>
                                <AppButton size="sm">Small</AppButton>
                                <AppButton size="md">Medium</AppButton>
                                <AppButton size="lg">Large</AppButton>
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">State &amp; Icons</p>
                            <div class="sc-preview sc-preview--wrap">
                                <AppButton :loading="true">Loading</AppButton>
                                <AppButton :disabled="true">Disabled</AppButton>
                                <AppButton :icon-only="true">
                                    <template #icon><PlusIcon :size="14" /></template>
                                </AppButton>
                                <AppButton>
                                    <template #icon><PlusIcon :size="14" /></template>
                                    New Item
                                </AppButton>
                                <AppButton variant="secondary">
                                    Export <template #icon-right><DownloadIcon :size="14" /></template>
                                </AppButton>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppInput -->
                <section id="input" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppInput</span>
                        <span class="sc-section__desc">prefix/suffix · password · clearable · size · error</span>
                    </div>
                    <div class="sc-grid sc-grid--3">
                        <div class="sc-card">
                            <p class="sc-card__label">Default</p>
                            <div class="sc-preview sc-preview--col">
                                <AppInput v-model="demoText" placeholder="Enter text…" label="Label" hint="Hint text" />
                                <AppInput v-model="demoText" placeholder="Clearable" label="Clearable" :clearable="true" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Error &amp; Password</p>
                            <div class="sc-preview sc-preview--col">
                                <AppInput v-model="demoText" placeholder="Error state" label="With Error" error="This field is required" />
                                <AppInput v-model="demoPass" type="password" placeholder="••••••••" label="Password" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Prefix / Suffix</p>
                            <div class="sc-preview sc-preview--col">
                                <AppInput v-model="demoText" placeholder="Search…">
                                    <template #prefix><SearchIcon :size="14" /></template>
                                </AppInput>
                                <AppInput v-model="demoText" placeholder="Domain…">
                                    <template #suffix>.com</template>
                                </AppInput>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppTextarea -->
                <section id="textarea" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppTextarea</span>
                        <span class="sc-section__desc">auto-resize · char counter · error</span>
                    </div>
                    <div class="sc-grid sc-grid--2">
                        <div class="sc-card">
                            <p class="sc-card__label">Default + Counter</p>
                            <div class="sc-preview">
                                <AppTextarea v-model="demoArea" label="Notes" placeholder="Write something…" :maxlength="200" hint="Auto-resizes as you type" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Error State</p>
                            <div class="sc-preview">
                                <AppTextarea v-model="demoArea2" label="Message" placeholder="Write…" error="Message is too short" />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppSelect -->
                <section id="select" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppSelect</span>
                        <span class="sc-section__desc">custom dropdown · native · size · error</span>
                    </div>
                    <div class="sc-grid sc-grid--3">
                        <div class="sc-card">
                            <p class="sc-card__label">Custom Dropdown</p>
                            <div class="sc-preview sc-preview--col">
                                <AppSelect v-model="demoSelect" :options="selectOptions" label="Role" placeholder="Choose a role…" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Error State</p>
                            <div class="sc-preview sc-preview--col">
                                <AppSelect v-model="demoSelect" :options="selectOptions" label="Required field" error="Please select a role" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Size Variants</p>
                            <div class="sc-preview sc-preview--col">
                                <AppSelect v-model="demoSelect" :options="selectOptions" placeholder="Small" size="sm" />
                                <AppSelect v-model="demoSelect" :options="selectOptions" placeholder="Medium" size="md" />
                                <AppSelect v-model="demoSelect" :options="selectOptions" placeholder="Large" size="lg" />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppCheckbox -->
                <section id="checkbox" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppCheckbox</span>
                        <span class="sc-section__desc">boolean · array mode · indeterminate · description</span>
                    </div>
                    <div class="sc-grid sc-grid--2">
                        <div class="sc-card">
                            <p class="sc-card__label">States</p>
                            <div class="sc-preview sc-preview--col sc-preview--gap-lg">
                                <AppCheckbox v-model="checkA" label="Remember me" />
                                <AppCheckbox v-model="checkB" label="With description" description="Secondary text below the label" />
                                <AppCheckbox :model-value="false" :indeterminate="true" label="Indeterminate" />
                                <AppCheckbox :model-value="false" :disabled="true" label="Disabled" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Array Mode</p>
                            <div class="sc-preview sc-preview--col sc-preview--gap-lg">
                                <AppCheckbox v-model="checkArr" value="alpha" label="Alpha" />
                                <AppCheckbox v-model="checkArr" value="beta"  label="Beta" />
                                <AppCheckbox v-model="checkArr" value="gamma" label="Gamma" />
                                <div class="sc-value-pill">{{ JSON.stringify(checkArr) }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppRadio -->
                <section id="radio" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppRadio</span>
                        <span class="sc-section__desc">single selection · description · disabled</span>
                    </div>
                    <div class="sc-grid sc-grid--2">
                        <div class="sc-card">
                            <p class="sc-card__label">Radio Group</p>
                            <div class="sc-preview sc-preview--col sc-preview--gap-lg">
                                <AppRadio v-model="radioVal" value="monthly"  label="Monthly"  description="Billed every month" />
                                <AppRadio v-model="radioVal" value="yearly"   label="Yearly"   description="Save 20% annually" />
                                <AppRadio v-model="radioVal" value="lifetime" label="Lifetime" :disabled="true" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Selected value</p>
                            <div class="sc-preview sc-preview--center">
                                <div class="sc-value-pill sc-value-pill--lg">{{ radioVal }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppToggle -->
                <section id="toggle" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppToggle</span>
                        <span class="sc-section__desc">sm/md/lg · label kiri/kanan · disabled</span>
                    </div>
                    <div class="sc-grid sc-grid--2">
                        <div class="sc-card">
                            <p class="sc-card__label">Size Variants</p>
                            <div class="sc-preview sc-preview--col sc-preview--gap-lg">
                                <AppToggle v-model="toggleA" size="sm" label="Small toggle" />
                                <AppToggle v-model="toggleB" size="md" label="Medium toggle" />
                                <AppToggle v-model="toggleC" size="lg" label="Large toggle" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Label Position &amp; State</p>
                            <div class="sc-preview sc-preview--col sc-preview--gap-lg">
                                <AppToggle v-model="toggleA" label="Label right" label-position="right" />
                                <AppToggle v-model="toggleA" label="Label left"  label-position="left" />
                                <AppToggle v-model="toggleA" label="Disabled"    :disabled="true" />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ════════════ DISPLAY ════════════ -->
                <div class="sc-category-label">Display</div>

                <!-- AppBadge -->
                <section id="badge" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppBadge</span>
                        <span class="sc-section__desc">6 warna · dot · removable · size</span>
                    </div>
                    <div class="sc-grid sc-grid--3">
                        <div class="sc-card">
                            <p class="sc-card__label">Color Variants</p>
                            <div class="sc-preview sc-preview--wrap">
                                <AppBadge color="default">Default</AppBadge>
                                <AppBadge color="primary">Primary</AppBadge>
                                <AppBadge color="success">Success</AppBadge>
                                <AppBadge color="warning">Warning</AppBadge>
                                <AppBadge color="danger">Danger</AppBadge>
                                <AppBadge color="info">Info</AppBadge>
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Dot Variant</p>
                            <div class="sc-preview sc-preview--wrap">
                                <AppBadge color="success" :dot="true" />
                                <AppBadge color="danger"  :dot="true" />
                                <AppBadge color="warning" :dot="true" />
                                <AppBadge color="primary" :dot="true" />
                                <AppBadge color="info"    :dot="true" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Size &amp; Removable</p>
                            <div class="sc-preview sc-preview--wrap">
                                <AppBadge color="primary" size="sm">Small</AppBadge>
                                <AppBadge color="success" size="md" :removable="true" @remove="() => {}">Removable</AppBadge>
                                <AppBadge color="danger"  size="lg">Large</AppBadge>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppAvatar -->
                <section id="avatar" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppAvatar</span>
                        <span class="sc-section__desc">xs–xl · initials · status dot · AvatarGroup</span>
                    </div>
                    <div class="sc-grid sc-grid--3">
                        <div class="sc-card">
                            <p class="sc-card__label">Size Scale</p>
                            <div class="sc-preview sc-preview--wrap sc-preview--center-v">
                                <AppAvatar v-for="sz in ['xs','sm','md','lg','xl']" :key="sz" :size="sz" name="Ziaul Kamal" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Status Dot</p>
                            <div class="sc-preview sc-preview--wrap">
                                <div class="sc-avatar-item">
                                    <AppAvatar name="Alice" :show-dot="true" dot-color="#22c55e" size="lg" />
                                    <span class="sc-avatar-item__label">Online</span>
                                </div>
                                <div class="sc-avatar-item">
                                    <AppAvatar name="Bob" :show-dot="true" dot-color="#f59e0b" size="lg" />
                                    <span class="sc-avatar-item__label">Away</span>
                                </div>
                                <div class="sc-avatar-item">
                                    <AppAvatar name="Carol" :show-dot="true" dot-color="#ef4444" size="lg" />
                                    <span class="sc-avatar-item__label">Busy</span>
                                </div>
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Avatar Group</p>
                            <div class="sc-preview sc-preview--center">
                                <AppAvatar :group="true" :users="groupUsers" :max="4" size="md" />
                                <span style="font-size:12px;color:var(--color-text-muted);margin-left:10px">5 members</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppSpinner -->
                <section id="spinner" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppSpinner</span>
                        <span class="sc-section__desc">5 size · 5 warna</span>
                    </div>
                    <div class="sc-grid sc-grid--2">
                        <div class="sc-card">
                            <p class="sc-card__label">Size Scale</p>
                            <div class="sc-preview sc-preview--wrap sc-preview--center-v">
                                <AppSpinner size="xs" />
                                <AppSpinner size="sm" />
                                <AppSpinner size="md" />
                                <AppSpinner size="lg" />
                                <AppSpinner size="xl" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Color Variants</p>
                            <div class="sc-preview sc-preview--wrap sc-preview--center-v">
                                <AppSpinner color="primary" size="lg" />
                                <AppSpinner color="success" size="lg" />
                                <AppSpinner color="danger"  size="lg" />
                                <AppSpinner color="warning" size="lg" />
                                <div class="sc-dark-swatch"><AppSpinner color="white" size="lg" /></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppSkeleton -->
                <section id="skeleton" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppSkeleton</span>
                        <span class="sc-section__desc">rect · text · circle · shimmer</span>
                    </div>
                    <div class="sc-grid sc-grid--2">
                        <div class="sc-card">
                            <p class="sc-card__label">Text Lines</p>
                            <div class="sc-preview sc-preview--col">
                                <AppSkeleton variant="text" width="55%" />
                                <AppSkeleton variant="text" width="90%" />
                                <AppSkeleton variant="text" width="70%" />
                                <AppSkeleton variant="rect" height="72px" style="margin-top:4px" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Profile Card</p>
                            <div class="sc-preview">
                                <div style="display:flex;align-items:center;gap:12px;width:100%">
                                    <AppSkeleton variant="circle" size="44px" style="flex-shrink:0" />
                                    <div style="display:flex;flex-direction:column;gap:8px;flex:1">
                                        <AppSkeleton variant="text" width="60%" />
                                        <AppSkeleton variant="text" width="40%" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppDivider -->
                <section id="divider" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppDivider</span>
                        <span class="sc-section__desc">horizontal · vertical · label slot</span>
                    </div>
                    <div class="sc-grid sc-grid--2">
                        <div class="sc-card">
                            <p class="sc-card__label">Horizontal</p>
                            <div class="sc-preview sc-preview--col sc-preview--gap-lg">
                                <AppDivider />
                                <AppDivider><span>OR</span></AppDivider>
                                <AppDivider><span>Continue with email</span></AppDivider>
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Vertical</p>
                            <div class="sc-preview sc-preview--center" style="height:56px">
                                <span class="sc-divider-item">Dashboard</span>
                                <AppDivider :vertical="true" />
                                <span class="sc-divider-item">Reports</span>
                                <AppDivider :vertical="true" />
                                <span class="sc-divider-item">Settings</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppProgressBar -->
                <section id="progress" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppProgressBar</span>
                        <span class="sc-section__desc">determinate · indeterminate · striped · 5 warna</span>
                    </div>
                    <div class="sc-grid sc-grid--2">
                        <div class="sc-card">
                            <p class="sc-card__label">Color Variants</p>
                            <div class="sc-preview sc-preview--col sc-preview--gap-lg">
                                <AppProgressBar :value="72" color="primary" label="Primary" :show-value="true" />
                                <AppProgressBar :value="55" color="success" label="Success" :show-value="true" />
                                <AppProgressBar :value="38" color="warning" label="Warning" :show-value="true" />
                                <AppProgressBar :value="80" color="danger"  label="Danger"  :show-value="true" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Size &amp; Indeterminate</p>
                            <div class="sc-preview sc-preview--col sc-preview--gap-lg">
                                <AppProgressBar :value="60" size="xs" />
                                <AppProgressBar :value="60" size="sm" :striped="true" />
                                <AppProgressBar :value="60" size="md" />
                                <AppProgressBar :value="60" size="lg" />
                                <AppProgressBar :indeterminate="true" color="primary" />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ════════════ FEEDBACK ════════════ -->
                <div class="sc-category-label">Feedback</div>

                <!-- AppAlert -->
                <section id="alert" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppAlert</span>
                        <span class="sc-section__desc">4 tipe · dismissible</span>
                    </div>
                    <div class="sc-grid sc-grid--2">
                        <div class="sc-card">
                            <p class="sc-card__label">Info &amp; Success</p>
                            <div class="sc-preview sc-preview--col">
                                <AppAlert type="info"    title="Information" description="Here is some useful information for you." :dismissible="true" />
                                <AppAlert type="success" title="Success!"    description="Your changes have been saved successfully." :dismissible="true" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Warning &amp; Error</p>
                            <div class="sc-preview sc-preview--col">
                                <AppAlert type="warning" title="Warning" description="This action cannot be easily undone." :dismissible="true" />
                                <AppAlert type="error"   title="Error"   description="Something went wrong. Please try again." :dismissible="true" />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppToast -->
                <section id="toast" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppToast + useToast</span>
                        <span class="sc-section__desc">4 tipe · auto-dismiss · action button</span>
                    </div>
                    <div class="sc-grid sc-grid--1">
                        <div class="sc-card">
                            <p class="sc-card__label">Trigger Toast</p>
                            <div class="sc-preview sc-preview--wrap">
                                <AppButton variant="success" @click="toast.success('Changes saved.', { title: 'Saved!' })">
                                    <template #icon><CheckIcon :size="14"/></template>
                                    Success
                                </AppButton>
                                <AppButton variant="danger" @click="toast.error('Something went wrong.', { title: 'Error' })">
                                    <template #icon><XIcon :size="14"/></template>
                                    Error
                                </AppButton>
                                <AppButton variant="warning" @click="toast.warning('Check your input.', { title: 'Warning' })">
                                    <template #icon><AlertTriangleIcon :size="14"/></template>
                                    Warning
                                </AppButton>
                                <AppButton variant="secondary" @click="toast.info('Did you know?', { title: 'Info' })">
                                    <template #icon><InfoIcon :size="14"/></template>
                                    Info
                                </AppButton>
                                <AppButton variant="outline" @click="toastWithAction">
                                    With Action
                                </AppButton>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppModal -->
                <section id="modal" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppModal</span>
                        <span class="sc-section__desc">sm/md/lg/xl/full · ESC · backdrop click</span>
                    </div>
                    <div class="sc-grid sc-grid--1">
                        <div class="sc-card">
                            <p class="sc-card__label">Size Variants</p>
                            <div class="sc-preview sc-preview--wrap">
                                <AppButton v-for="s in ['sm','md','lg','xl','full']" :key="s" variant="secondary" size="sm" @click="openModal(s as ModalSize)">
                                    Open {{ s.toUpperCase() }}
                                </AppButton>
                            </div>
                        </div>
                    </div>
                    <AppModal v-model="modalOpen" :title="`${modalSize.toUpperCase()} Modal`" :size="modalSize">
                        <p style="color:var(--color-text-muted);font-size:13.5px;line-height:1.7">
                            This is a <strong>{{ modalSize }}</strong> modal. Press <kbd>ESC</kbd> or click the backdrop to close.
                            Supports header, body, and footer slots.
                        </p>
                        <template #footer>
                            <AppButton variant="ghost"   @click="modalOpen = false">Cancel</AppButton>
                            <AppButton variant="primary" @click="modalOpen = false">Confirm</AppButton>
                        </template>
                    </AppModal>
                </section>

                <!-- AppDrawer -->
                <section id="drawer" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppDrawer</span>
                        <span class="sc-section__desc">left/right · sm/md/lg · overlay</span>
                    </div>
                    <div class="sc-grid sc-grid--1">
                        <div class="sc-card">
                            <p class="sc-card__label">Direction</p>
                            <div class="sc-preview sc-preview--wrap">
                                <AppButton variant="secondary" @click="drawerSide='right'; drawerOpen=true">
                                    <template #icon><PanelRightIcon :size="14"/></template>
                                    Right Drawer
                                </AppButton>
                                <AppButton variant="secondary" @click="drawerSide='left'; drawerOpen=true">
                                    <template #icon><PanelLeftIcon :size="14"/></template>
                                    Left Drawer
                                </AppButton>
                            </div>
                        </div>
                    </div>
                    <AppDrawer v-model="drawerOpen" title="Drawer Panel" :side="drawerSide" size="md">
                        <div style="display:flex;flex-direction:column;gap:16px">
                            <AppAlert type="info" description="Slides in from the side. Supports header, body, and footer." />
                            <AppInput v-model="demoText" placeholder="Input inside drawer…" label="Name" />
                            <AppSelect v-model="demoSelect" :options="selectOptions" label="Role" />
                        </div>
                        <template #footer>
                            <AppButton variant="ghost"   @click="drawerOpen = false">Cancel</AppButton>
                            <AppButton variant="primary" @click="drawerOpen = false">Save Changes</AppButton>
                        </template>
                    </AppDrawer>
                </section>

                <!-- ════════════ OVERLAY ════════════ -->
                <div class="sc-category-label">Overlay</div>

                <!-- AppTooltip -->
                <section id="tooltip" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppTooltip</span>
                        <span class="sc-section__desc">4 posisi · delay · slot content</span>
                    </div>
                    <div class="sc-grid sc-grid--1">
                        <div class="sc-card">
                            <p class="sc-card__label">Positions &amp; Delay</p>
                            <div class="sc-preview sc-preview--wrap sc-preview--center" style="padding: 32px 20px">
                                <AppTooltip content="Top tooltip"    position="top">
                                    <AppButton variant="secondary" size="sm">Top</AppButton>
                                </AppTooltip>
                                <AppTooltip content="Right tooltip"  position="right">
                                    <AppButton variant="secondary" size="sm">Right</AppButton>
                                </AppTooltip>
                                <AppTooltip content="Bottom tooltip" position="bottom">
                                    <AppButton variant="secondary" size="sm">Bottom</AppButton>
                                </AppTooltip>
                                <AppTooltip content="Left tooltip"   position="left">
                                    <AppButton variant="secondary" size="sm">Left</AppButton>
                                </AppTooltip>
                                <AppTooltip content="500ms delay ⏱" :delay="500">
                                    <AppButton variant="outline" size="sm">Delayed</AppButton>
                                </AppTooltip>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppPopover -->
                <section id="popover" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppPopover</span>
                        <span class="sc-section__desc">click/hover · arrow · custom content</span>
                    </div>
                    <div class="sc-grid sc-grid--2">
                        <div class="sc-card">
                            <p class="sc-card__label">Click Trigger</p>
                            <div class="sc-preview sc-preview--center" style="padding:40px 20px">
                                <AppPopover trigger="click">
                                    <template #trigger>
                                        <AppButton variant="secondary">
                                            <template #icon-right><ChevronDownIcon :size="13"/></template>
                                            Open Popover
                                        </AppButton>
                                    </template>
                                    <p style="font-size:13px;font-weight:600;color:var(--color-text-primary);margin-bottom:4px">User Details</p>
                                    <p style="font-size:12.5px;color:var(--color-text-muted);line-height:1.5">Floating panel with custom content. Click outside to dismiss.</p>
                                </AppPopover>
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Hover Trigger</p>
                            <div class="sc-preview sc-preview--center" style="padding:40px 20px">
                                <AppPopover trigger="hover">
                                    <template #trigger>
                                        <AppButton variant="outline">Hover Me</AppButton>
                                    </template>
                                    <p style="font-size:12.5px;color:var(--color-text-muted)">Shows on hover, hides on mouse out.</p>
                                </AppPopover>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppDropdown -->
                <section id="dropdown" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppDropdown</span>
                        <span class="sc-section__desc">icon · divider · danger item · keyboard nav</span>
                    </div>
                    <div class="sc-grid sc-grid--2">
                        <div class="sc-card">
                            <p class="sc-card__label">Action Menu</p>
                            <div class="sc-preview sc-preview--center" style="padding:32px 20px">
                                <AppDropdown :items="dropdownItems" align="left">
                                    <template #trigger>
                                        <AppButton variant="secondary">
                                            Actions
                                            <template #icon-right><ChevronDownIcon :size="13"/></template>
                                        </AppButton>
                                    </template>
                                </AppDropdown>
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Icon-only Trigger</p>
                            <div class="sc-preview sc-preview--center" style="padding:32px 20px">
                                <AppDropdown :items="dropdownItems" align="right">
                                    <template #trigger>
                                        <AppButton variant="ghost" :icon-only="true">
                                            <template #icon><MoreHorizontalIcon :size="16"/></template>
                                        </AppButton>
                                    </template>
                                </AppDropdown>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppContextMenu -->
                <section id="contextmenu" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppContextMenu</span>
                        <span class="sc-section__desc">klik kanan · posisi auto · shortcut label</span>
                    </div>
                    <div class="sc-grid sc-grid--1">
                        <div class="sc-card">
                            <p class="sc-card__label">Right-click Area</p>
                            <div class="sc-preview sc-preview--center">
                                <AppContextMenu :items="contextItems">
                                    <div class="sc-ctx-zone">
                                        <MousePointerClickIcon :size="20" style="color:var(--color-text-subtle);margin-bottom:8px" />
                                        <span>Right-click anywhere in this area</span>
                                    </div>
                                </AppContextMenu>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ════════════ STRUCTURE ════════════ -->
                <div class="sc-category-label">Structure</div>

                <!-- AppTabs -->
                <section id="tabs" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppTabs</span>
                        <span class="sc-section__desc">underline · pill · lazy render · badge</span>
                    </div>
                    <div class="sc-grid sc-grid--1">
                        <div class="sc-card">
                            <p class="sc-card__label">Underline Variant</p>
                            <div class="sc-preview sc-preview--col" style="padding:0">
                                <AppTabs v-model="tabA" :tabs="tabsData" variant="underline">
                                    <template #overview>
                                        <div class="sc-tab-panel">Overview — summary of your account activity.</div>
                                    </template>
                                    <template #analytics>
                                        <div class="sc-tab-panel">Analytics — detailed charts and metrics.</div>
                                    </template>
                                    <template #reports>
                                        <div class="sc-tab-panel">Reports — downloadable reports.</div>
                                    </template>
                                    <template #settings>
                                        <div class="sc-tab-panel">Settings (disabled)</div>
                                    </template>
                                </AppTabs>
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Pill Variant</p>
                            <div class="sc-preview sc-preview--col" style="padding:0">
                                <AppTabs v-model="tabB" :tabs="tabsSimple" variant="pill">
                                    <template #all>      <div class="sc-tab-panel">All items shown here.</div></template>
                                    <template #active>   <div class="sc-tab-panel">Active items only.</div></template>
                                    <template #archived> <div class="sc-tab-panel">Archived items.</div></template>
                                </AppTabs>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppAccordion -->
                <section id="accordion" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppAccordion</span>
                        <span class="sc-section__desc">single · multiple expand · animated</span>
                    </div>
                    <div class="sc-grid sc-grid--2">
                        <div class="sc-card">
                            <p class="sc-card__label">Single Expand</p>
                            <div class="sc-preview sc-preview--col" style="padding:0">
                                <AppAccordion :items="accordionItems" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Multiple Expand</p>
                            <div class="sc-preview sc-preview--col" style="padding:0">
                                <AppAccordion :items="accordionItems" :multiple="true" />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppBreadcrumb -->
                <section id="breadcrumb" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppBreadcrumb</span>
                        <span class="sc-section__desc">Inertia Link compatible · custom separator</span>
                    </div>
                    <div class="sc-grid sc-grid--2">
                        <div class="sc-card">
                            <p class="sc-card__label">Short Path</p>
                            <div class="sc-preview">
                                <AppBreadcrumb :crumbs="[{ label:'Dashboard', href:'/' }, { label:'Contacts', href:'/contacts' }, { label:'John Doe' }]" />
                            </div>
                        </div>
                        <div class="sc-card">
                            <p class="sc-card__label">Deep Path</p>
                            <div class="sc-preview">
                                <AppBreadcrumb :crumbs="[{ label:'Home', href:'/' }, { label:'Settings', href:'/settings' }, { label:'Team' }, { label:'Permissions' }]" />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- AppCard -->
                <section id="card" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppCard</span>
                        <span class="sc-section__desc">header/footer slots · padding variants · hoverable</span>
                    </div>
                    <div class="sc-grid sc-grid--3">
                        <AppCard title="Basic Card" subtitle="With subtitle">
                            <p class="sc-card-body-text">Card body content goes here.</p>
                        </AppCard>
                        <AppCard title="Hoverable" :hoverable="true">
                            <p class="sc-card-body-text">Hover for lift effect.</p>
                            <template #footer>
                                <AppButton variant="primary" size="sm">Action</AppButton>
                                <AppButton variant="ghost"   size="sm">Cancel</AppButton>
                            </template>
                        </AppCard>
                        <AppCard :flat="true" title="Flat Card" padding="sm">
                            <p class="sc-card-body-text">No border, subtle background.</p>
                        </AppCard>
                    </div>
                </section>

                <!-- AppEmptyState -->
                <section id="empty" class="sc-section">
                    <div class="sc-section__head">
                        <span class="sc-section__name">AppEmptyState</span>
                        <span class="sc-section__desc">icon · title · description · CTA slot</span>
                    </div>
                    <div class="sc-grid sc-grid--2">
                        <AppCard padding="none">
                            <AppEmptyState size="sm" title="No results found" description="Try adjusting your filters or search terms.">
                                <AppButton variant="secondary" size="sm">Clear filters</AppButton>
                            </AppEmptyState>
                        </AppCard>
                        <AppCard padding="none">
                            <AppEmptyState size="md" title="Nothing here yet" description="Start by creating your first item to get going.">
                                <AppButton variant="primary">
                                    <template #icon><PlusIcon :size="14"/></template>
                                    Create item
                                </AppButton>
                            </AppEmptyState>
                        </AppCard>
                    </div>
                </section>

            </main>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import {
    PlusIcon, DownloadIcon, SearchIcon, ChevronDownIcon,
    MoreHorizontalIcon, EditIcon, CopyIcon, Trash2Icon,
    CheckIcon, XIcon, AlertTriangleIcon, InfoIcon,
    PanelLeftIcon, PanelRightIcon, MousePointerClickIcon,
} from '@lucide/vue';

import BaseLayout      from '@/Layouts/BaseLayout.vue';
import AppToast        from '@/Components/App/AppToast.vue';
import AppBreadcrumb   from '@/Components/App/AppBreadcrumb.vue';
import AppButton       from '@/Components/App/AppButton.vue';
import AppInput        from '@/Components/App/AppInput.vue';
import AppTextarea     from '@/Components/App/AppTextarea.vue';
import AppSelect       from '@/Components/App/AppSelect.vue';
import AppCheckbox     from '@/Components/App/AppCheckbox.vue';
import AppRadio        from '@/Components/App/AppRadio.vue';
import AppToggle       from '@/Components/App/AppToggle.vue';
import AppBadge        from '@/Components/App/AppBadge.vue';
import AppAvatar       from '@/Components/App/AppAvatar.vue';
import AppSpinner      from '@/Components/App/AppSpinner.vue';
import AppSkeleton     from '@/Components/App/AppSkeleton.vue';
import AppDivider      from '@/Components/App/AppDivider.vue';
import AppProgressBar  from '@/Components/App/AppProgressBar.vue';
import AppAlert        from '@/Components/App/AppAlert.vue';
import AppModal        from '@/Components/App/AppModal.vue';
import AppDrawer       from '@/Components/App/AppDrawer.vue';
import AppTooltip      from '@/Components/App/AppTooltip.vue';
import AppPopover      from '@/Components/App/AppPopover.vue';
import AppDropdown     from '@/Components/App/AppDropdown.vue';
import AppContextMenu  from '@/Components/App/AppContextMenu.vue';
import AppTabs         from '@/Components/App/AppTabs.vue';
import AppAccordion    from '@/Components/App/AppAccordion.vue';
import AppCard         from '@/Components/App/AppCard.vue';
import AppEmptyState   from '@/Components/App/AppEmptyState.vue';
import { useToast }    from '@/Composables/useToast';

// Nav
const NAV_FORM     = [{ id:'button',      name:'AppButton' },{ id:'input',     name:'AppInput' },{ id:'textarea',  name:'AppTextarea' },{ id:'select',    name:'AppSelect' },{ id:'checkbox',  name:'AppCheckbox' },{ id:'radio',     name:'AppRadio' },{ id:'toggle',    name:'AppToggle' }];
const NAV_DISPLAY  = [{ id:'badge',       name:'AppBadge' },{ id:'avatar',     name:'AppAvatar' },{ id:'spinner',   name:'AppSpinner' },{ id:'skeleton',  name:'AppSkeleton' },{ id:'divider',   name:'AppDivider' },{ id:'progress',  name:'AppProgressBar' }];
const NAV_FEEDBACK = [{ id:'alert',       name:'AppAlert' },{ id:'toast',      name:'AppToast' },{ id:'modal',     name:'AppModal' },{ id:'drawer',    name:'AppDrawer' }];
const NAV_OVERLAY  = [{ id:'tooltip',     name:'AppTooltip' },{ id:'popover',    name:'AppPopover' },{ id:'dropdown',   name:'AppDropdown' },{ id:'contextmenu',name:'AppContextMenu' }];
const NAV_STRUCT   = [{ id:'tabs',        name:'AppTabs' },{ id:'accordion',   name:'AppAccordion' },{ id:'breadcrumb', name:'AppBreadcrumb' },{ id:'card',      name:'AppCard' },{ id:'empty',     name:'AppEmptyState' }];

// Toast
const toast = useToast();
function toastWithAction(): void {
    toast.info('New version available.', {
        title: 'Update',
        duration: 0,
        action: { label: 'Refresh', onClick: () => location.reload() },
    });
}

// Form state
const demoText   = ref('');
const demoPass   = ref('');
const demoArea   = ref('');
const demoArea2  = ref('');
const demoSelect = ref('');
const checkA     = ref(true);
const checkB     = ref(true);
const checkArr   = ref<string[]>(['alpha']);
const radioVal   = ref('monthly');
const toggleA    = ref(false);
const toggleB    = ref(true);
const toggleC    = ref(false);

// Modal
type ModalSize = 'sm' | 'md' | 'lg' | 'xl' | 'full';
const modalOpen = ref(false);
const modalSize = ref<ModalSize>('md');
function openModal(size: ModalSize): void {
    modalSize.value = size;
    modalOpen.value = true;
}

// Drawer
const drawerOpen = ref(false);
const drawerSide = ref<'left' | 'right'>('right');

// Tabs
const tabA = ref('overview');
const tabB = ref('all');
const tabsData = [
    { value: 'overview',  label: 'Overview' },
    { value: 'analytics', label: 'Analytics', badge: '3' },
    { value: 'reports',   label: 'Reports' },
    { value: 'settings',  label: 'Settings', disabled: true },
];
const tabsSimple = [
    { value: 'all',      label: 'All' },
    { value: 'active',   label: 'Active' },
    { value: 'archived', label: 'Archived' },
];

// Accordion
const accordionItems = [
    { title: 'What is included in the free plan?', content: 'Up to 3 projects, 5 team members, and 2GB of storage.' },
    { title: 'Can I upgrade at any time?',          content: 'Yes, upgrade or downgrade anytime from billing settings.' },
    { title: 'Is my data secure?',                  content: 'All data is encrypted using AES-256 and TLS 1.3.' },
];

// Data
const selectOptions = [
    { value: 'admin',  label: 'Administrator' },
    { value: 'editor', label: 'Editor' },
    { value: 'viewer', label: 'Viewer', disabled: true },
    { value: 'guest',  label: 'Guest' },
];
const groupUsers = [
    { name: 'Alice Martin' }, { name: 'Bob Chen' },
    { name: 'Carol Smith' }, { name: 'Dave Wilson' }, { name: 'Eve Johnson' },
];
const dropdownItems = [
    { label: 'Edit',      icon: EditIcon },
    { label: 'Duplicate', icon: CopyIcon },
    { divider: true },
    { label: 'Delete',    icon: Trash2Icon, danger: true },
];
const contextItems = [
    { label: 'Copy',   icon: CopyIcon,    shortcut: '⌘C' },
    { label: 'Edit',   icon: EditIcon,    shortcut: '⌘E' },
    { divider: true },
    { label: 'Delete', icon: Trash2Icon,  danger: true, shortcut: '⌫' },
];
</script>

<style scoped>
/* ── Root layout ──────────────────────────────────────────── */
.sc-root {
    display: flex;
    min-height: 100%;
    align-items: flex-start;
}
.sc-nav * {
    opacity: 1 !important;
    visibility: visible !important;
}
/* ── Left nav ─────────────────────────────────────────────── */
.sc-nav {
    width: 220px;
    flex-shrink: 0;

    position: sticky;
    top: 0;

    height: 100vh;
    overflow-y: auto;

    padding: 28px 16px 28px 20px;

    border-right: 1px solid var(--color-border);
    background: #fff;

    z-index: 10;
}

/* heading */
.sc-nav__heading {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;

    color: #64748b;

    margin: 18px 0 8px;

    position: relative;
    z-index: 2;
}
.sc-nav__heading:first-child { margin-top: 0; }
/* link */
.sc-nav__link {
    display: block;

    font-size: 13px;
    line-height: 1.4;

    color: #334155;

    text-decoration: none;

    padding: 7px 10px;

    border-radius: 8px;

    position: relative;
    z-index: 2;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sc-nav__link:hover {
    background: #f1f5f9;
    color: #0f172a;
}
/* ── Main ─────────────────────────────────────────────────── */
.sc-main {
    flex: 1;
    min-width: 0;
    padding: 32px 36px 80px;
    max-width: 960px;
}

/* ── Hero ─────────────────────────────────────────────────── */
.sc-hero {
    margin-bottom: 40px;
}
.sc-hero__eyebrow {
    display: flex;
    gap: 8px;
    margin-bottom: 14px;
}
.sc-chip {
    font-size: 10.5px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 99px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.sc-chip--violet { background: rgba(99,102,241,0.1);  color: #6366f1; }
.sc-chip--green  { background: rgba(16,185,129,0.1);  color: #059669; }
.sc-chip--blue   { background: rgba(59,130,246,0.1);  color: #2563eb; }

.sc-hero__title {
    font-size: 28px;
    font-weight: 800;
    color: var(--color-text-primary);
    letter-spacing: -0.03em;
    margin: 0 0 8px;
    line-height: 1.2;
}
.sc-hero__sub {
    font-size: 14px;
    color: var(--color-text-muted);
    margin: 0;
}

/* ── Category label ───────────────────────────────────────── */
.sc-category-label {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--color-text-subtle);
    padding: 0 0 12px;
    margin-top: 8px;
    border-bottom: 1px solid var(--color-border);
}

/* ── Section ──────────────────────────────────────────────── */
.sc-section {
    padding: 28px 0;
    border-bottom: 1px solid var(--color-border);
    scroll-margin-top: 24px;
}
.sc-section__head {
    display: flex;
    align-items: baseline;
    gap: 12px;
    margin-bottom: 16px;
}
.sc-section__name {
    font-size: 15px;
    font-weight: 700;
    color: var(--color-text-primary);
    letter-spacing: -0.02em;
    font-family: var(--font-mono);
}
.sc-section__desc {
    font-size: 12px;
    color: var(--color-text-muted);
}

/* ── Grid layouts ─────────────────────────────────────────── */
.sc-grid {
    display: grid;
    gap: 14px;
}
.sc-grid--1 { grid-template-columns: 1fr; }
.sc-grid--2 { grid-template-columns: repeat(2, 1fr); }
.sc-grid--3 { grid-template-columns: repeat(3, 1fr); }

/* ── Card ─────────────────────────────────────────────────── */
.sc-card {
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: var(--color-surface);
    overflow: hidden;
}
.sc-card__label {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--color-text-subtle);
    padding: 10px 14px 0;
    margin: 0;
}

/* ── Preview area ─────────────────────────────────────────── */
.sc-preview {
    padding: 16px 14px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    min-height: 60px;
}
.sc-preview--col      { flex-direction: column; align-items: stretch; }
.sc-preview--wrap     { flex-wrap: wrap; align-items: center; }
.sc-preview--center   { align-items: center; justify-content: center; }
.sc-preview--center-v { align-items: center; }
.sc-preview--gap-lg   { gap: 14px; }

/* ── Misc helpers ─────────────────────────────────────────── */
.sc-value-pill {
    font-family: var(--font-mono);
    font-size: 11.5px;
    background: var(--color-bg-subtle);
    border: 1px solid var(--color-border);
    padding: 4px 10px;
    border-radius: 6px;
    color: #6366f1;
    width: fit-content;
}
.sc-value-pill--lg {
    font-size: 14px;
    padding: 8px 18px;
    font-weight: 600;
    color: var(--color-text-primary);
}

.sc-dark-swatch {
    background: #1e1b4b;
    padding: 8px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sc-avatar-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}
.sc-avatar-item__label {
    font-size: 10.5px;
    color: var(--color-text-muted);
}

.sc-divider-item {
    font-size: 12.5px;
    color: var(--color-text-muted);
    padding: 0 4px;
}

.sc-ctx-zone {
    width: 100%;
    height: 96px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 1.5px dashed var(--color-border);
    border-radius: 10px;
    font-size: 12.5px;
    color: var(--color-text-muted);
    cursor: context-menu;
    background: var(--color-bg-subtle);
    transition: border-color 150ms ease, background 150ms ease;
}
.sc-ctx-zone:hover { border-color: #6366f1; background: rgba(99,102,241,0.04); }

.sc-tab-panel {
    padding: 14px 16px;
    font-size: 13px;
    color: var(--color-text-muted);
    background: var(--color-bg-subtle);
    border-radius: var(--radius-md);
    margin: 4px 0;
    line-height: 1.5;
}

.sc-card-body-text {
    font-size: 13px;
    color: var(--color-text-muted);
    line-height: 1.5;
    margin: 0;
}

/* ── Responsive ───────────────────────────────────────────── */
@media (max-width: 900px) {
    .sc-nav { display: none; }
    .sc-grid--3 { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .sc-main { padding: 20px 16px 60px; }
    .sc-grid--2, .sc-grid--3 { grid-template-columns: 1fr; }
}
</style>
