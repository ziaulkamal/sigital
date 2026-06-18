<template>
    <div class="ml">
        <!-- Left: sidebar folders -->
        <aside class="ml__sidebar">
            <button class="ml__compose" @click="composing = true">
                <PenSquareIcon :size="14" />
                Compose
            </button>

            <nav class="ml__nav">
                <button
                    v-for="folder in folders"
                    :key="folder.label"
                    class="ml__nav-item"
                    :class="{ 'ml__nav-item--active': activeFolder === folder.label }"
                    @click="activeFolder = folder.label"
                >
                    <component :is="folder.icon" :size="15" />
                    <span class="ml__nav-label">{{ folder.label }}</span>
                    <span v-if="folder.count" class="ml__nav-count">{{ folder.count }}</span>
                </button>
            </nav>
        </aside>

        <!-- Middle: mail list -->
        <div class="ml__list">
            <div class="ml__list-head">
                <span class="ml__list-title">{{ activeFolder }}</span>
                <div class="ml__list-search">
                    <SearchIcon :size="13" />
                    <input v-model="search" class="ml__search-input" placeholder="Search…" />
                </div>
            </div>
            <div class="ml__list-items">
                <MailListItem
                    v-for="mail in filteredMails"
                    :key="mail.id"
                    v-bind="mail"
                    :active="activeMailId === mail.id"
                    :selected="selectedIds.has(mail.id)"
                    @click="openMail(mail)"
                    @select="toggleSelect(mail.id)"
                />
                <div v-if="filteredMails.length === 0" class="ml__empty">
                    No messages in {{ activeFolder }}.
                </div>
            </div>
        </div>

        <!-- Right: thread / reading pane -->
        <div class="ml__thread">
            <template v-if="activeMail">
                <div class="ml__thread-head">
                    <h2 class="ml__thread-subject">{{ activeMail.subject }}</h2>
                    <div class="ml__thread-actions">
                        <button class="ml__action-btn" title="Reply" @click="composing = true">
                            <ReplyIcon :size="15" />
                        </button>
                        <button class="ml__action-btn" title="Forward">
                            <ForwardIcon :size="15" />
                        </button>
                        <button class="ml__action-btn" title="Archive">
                            <ArchiveIcon :size="15" />
                        </button>
                        <button class="ml__action-btn ml__action-btn--danger" title="Delete">
                            <TrashIcon :size="15" />
                        </button>
                    </div>
                </div>

                <div class="ml__message">
                    <div class="ml__msg-meta">
                        <AppAvatar :name="activeMail.from" size="sm" />
                        <div class="ml__msg-from-info">
                            <span class="ml__msg-from">{{ activeMail.from }}</span>
                            <span class="ml__msg-time">{{ activeMail.time }}</span>
                        </div>
                    </div>
                    <p class="ml__msg-body">{{ activeMail.body ?? activeMail.preview }}</p>
                </div>
            </template>
            <div v-else class="ml__thread-empty">
                <InboxIcon :size="32" class="ml__thread-empty-icon" />
                <p>Select a message to read</p>
            </div>
        </div>

        <!-- Compose overlay -->
        <MailCompose v-model="composing" @send="onSend" />
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import {
    PenSquareIcon, InboxIcon, SendIcon, StarIcon, FileIcon,
    TrashIcon, ArchiveIcon, ReplyIcon, ForwardIcon, SearchIcon,
} from '@lucide/vue';
import MailListItem from '@/Components/App/MailListItem.vue';
import MailCompose  from '@/Components/App/MailCompose.vue';
import AppAvatar    from '@/Components/App/AppAvatar.vue';

interface Mail {
    id:       string;
    from:     string;
    subject:  string;
    preview:  string;
    body?:    string;
    time:     string;
    read:     boolean;
    starred:  boolean;
    folder:   string;
    label?:   string;
    labelColor?: string;
}

const folders = [
    { label: 'Inbox',  icon: InboxIcon,  count: 3 },
    { label: 'Sent',   icon: SendIcon,   count: 0 },
    { label: 'Starred',icon: StarIcon,   count: 1 },
    { label: 'Drafts', icon: FileIcon,   count: 2 },
    { label: 'Trash',  icon: TrashIcon,  count: 0 },
];

const mails = ref<Mail[]>([
    { id: 'm1', from: 'Alice Martin',  subject: 'Q2 pipeline review', preview: 'Hi, just checking in on the Q2 numbers...', body: 'Hi, just checking in on the Q2 numbers. Can we sync tomorrow at 10am to review the pipeline status?', time: '9:41 AM', read: false, starred: true,  folder: 'Inbox',  label: 'Work', labelColor: 'primary' },
    { id: 'm2', from: 'Bob Chen',      subject: 'New contact: Sarah Johnson', preview: 'Added Sarah Johnson from Acme Corp...', time: '8:30 AM', read: false, starred: false, folder: 'Inbox' },
    { id: 'm3', from: 'Carol Smith',   subject: 'Proposal sent to Beta Ltd', preview: 'The proposal has been sent...', time: 'Yesterday', read: true, starred: false, folder: 'Inbox', label: 'Follow-up', labelColor: 'warning' },
    { id: 'm4', from: 'Dave Wilson',   subject: 'Call notes — James Brown', preview: '12 min call completed. Follow up...', time: 'Monday', read: true, starred: false, folder: 'Inbox' },
    { id: 'm5', from: 'Me',            subject: 'Re: Enterprise offer', preview: 'Thanks for the quick turnaround...', time: 'Sunday', read: true, starred: false, folder: 'Sent' },
]);

const activeFolder = ref('Inbox');
const activeMailId = ref<string | null>(null);
const selectedIds  = ref(new Set<string>());
const composing    = ref(false);
const search       = ref('');

const activeMail = computed(() => mails.value.find(m => m.id === activeMailId.value) ?? null);

const filteredMails = computed(() => {
    const q = search.value.toLowerCase();
    return mails.value
        .filter(m => m.folder === activeFolder.value)
        .filter(m => !q || m.from.toLowerCase().includes(q) || m.subject.toLowerCase().includes(q));
});

function openMail(mail: Mail) {
    activeMailId.value = mail.id;
    mail.read = true;
}

function toggleSelect(id: string) {
    if (selectedIds.value.has(id)) selectedIds.value.delete(id);
    else selectedIds.value.add(id);
}

function onSend(form: { to: string; subject: string; body: string; cc: string }) {
    mails.value.unshift({
        id:      `m${Date.now()}`,
        from:    'Me',
        subject: form.subject || '(No subject)',
        preview: form.body.slice(0, 80),
        body:    form.body,
        time:    'Just now',
        read:    true,
        starred: false,
        folder:  'Sent',
    });
}
</script>

<style scoped>
.ml {
    display: flex; height: 100%; min-height: 0;
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: 16px; overflow: hidden;
}

/* Sidebar */
.ml__sidebar {
    width: 180px; flex-shrink: 0;
    border-right: 1px solid var(--color-border);
    display: flex; flex-direction: column; gap: 12px;
    padding: 16px 12px;
    background: var(--color-bg-subtle);
}
.ml__compose {
    display: flex; align-items: center; gap: 7px;
    padding: 8px 14px; border-radius: 8px; border: none;
    background: #6366f1; color: #fff;
    font-size: 13px; font-weight: 600; font-family: var(--font-sans);
    cursor: pointer; transition: background 150ms ease;
}
.ml__compose:hover { background: #4f46e5; }
.ml__nav { display: flex; flex-direction: column; gap: 2px; }
.ml__nav-item {
    display: flex; align-items: center; gap: 9px;
    padding: 7px 10px; border-radius: 8px; border: none;
    background: transparent; cursor: pointer; width: 100%;
    font-size: 13px; font-weight: 500; color: var(--color-text-muted);
    font-family: var(--font-sans); transition: all 120ms ease;
}
.ml__nav-item:hover { background: var(--color-surface); color: var(--color-text-primary); }
.ml__nav-item--active { background: var(--color-surface); color: #6366f1; font-weight: 600; }
.ml__nav-label { flex: 1; text-align: left; }
.ml__nav-count {
    font-size: 10.5px; font-weight: 700; color: #fff;
    background: #6366f1; border-radius: 99px; padding: 1px 6px;
}

/* Mail list */
.ml__list {
    width: 280px; flex-shrink: 0;
    border-right: 1px solid var(--color-border);
    display: flex; flex-direction: column;
    overflow: hidden;
}
.ml__list-head {
    display: flex; align-items: center; justify-content: space-between; gap: 8px;
    padding: 12px 14px; border-bottom: 1px solid var(--color-border);
    flex-shrink: 0;
}
.ml__list-title { font-size: 13px; font-weight: 700; color: var(--color-text-primary); }
.ml__list-search {
    display: flex; align-items: center; gap: 5px;
    background: var(--color-bg-subtle); border: 1px solid var(--color-border);
    border-radius: 6px; padding: 4px 8px;
    color: var(--color-text-subtle);
}
.ml__search-input {
    border: none; outline: none; background: transparent;
    font-size: 11.5px; color: var(--color-text-primary); font-family: var(--font-sans);
    width: 80px;
}
.ml__list-items { flex: 1; overflow-y: auto; }
.ml__empty { padding: 32px; text-align: center; font-size: 13px; color: var(--color-text-subtle); }

/* Thread / reading pane */
.ml__thread {
    flex: 1; min-width: 0; display: flex; flex-direction: column; overflow-y: auto;
}
.ml__thread-head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    padding: 20px 24px 14px;
    border-bottom: 1px solid var(--color-border);
    flex-shrink: 0;
}
.ml__thread-subject { font-size: 16px; font-weight: 700; color: var(--color-text-primary); margin: 0; }
.ml__thread-actions { display: flex; gap: 4px; flex-shrink: 0; }
.ml__action-btn {
    display: flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 8px; border: none;
    background: transparent; cursor: pointer; color: var(--color-text-muted);
    transition: all 120ms ease;
}
.ml__action-btn:hover { background: var(--color-bg-subtle); color: var(--color-text-primary); }
.ml__action-btn--danger:hover { background: rgba(239,68,68,0.1); color: #ef4444; }

.ml__message { padding: 20px 24px; }
.ml__msg-meta { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
.ml__msg-from-info { display: flex; flex-direction: column; gap: 1px; }
.ml__msg-from { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }
.ml__msg-time { font-size: 11px; color: var(--color-text-subtle); }
.ml__msg-body { font-size: 13.5px; color: var(--color-text-muted); line-height: 1.7; }

.ml__thread-empty {
    flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 10px; color: var(--color-text-subtle);
    font-size: 13px;
}
.ml__thread-empty-icon { opacity: 0.3; }
</style>
