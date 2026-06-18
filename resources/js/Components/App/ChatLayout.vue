<template>
    <div class="cl">
        <!-- Left: contacts sidebar -->
        <aside class="cl__sidebar">
            <div class="cl__sidebar-head">
                <span class="cl__sidebar-title">Messages</span>
                <button class="cl__new-btn" title="New chat">
                    <SquarePenIcon :size="15" />
                </button>
            </div>
            <div class="cl__search-wrap">
                <SearchIcon :size="13" class="cl__search-icon" />
                <input v-model="search" class="cl__search" placeholder="Search…" />
            </div>
            <div class="cl__contacts">
                <button
                    v-for="c in filteredContacts"
                    :key="c.id"
                    class="cl__contact"
                    :class="{ 'cl__contact--active': activeId === c.id }"
                    @click="activeId = c.id"
                >
                    <div class="cl__contact-avatar">
                        <AppAvatar :name="c.name" size="sm" />
                        <span v-if="c.online" class="cl__online-dot" />
                    </div>
                    <div class="cl__contact-info">
                        <div class="cl__contact-row">
                            <span class="cl__contact-name">{{ c.name }}</span>
                            <span class="cl__contact-time">{{ c.lastTime }}</span>
                        </div>
                        <div class="cl__contact-row">
                            <span class="cl__contact-preview">{{ c.lastMessage }}</span>
                            <span v-if="c.unread" class="cl__unread-badge">{{ c.unread }}</span>
                        </div>
                    </div>
                </button>
            </div>
        </aside>

        <!-- Right: chat area -->
        <div class="cl__chat">
            <template v-if="activeContact">
                <!-- Chat topbar -->
                <div class="cl__chat-head">
                    <div class="cl__chat-who">
                        <AppAvatar :name="activeContact.name" size="sm" />
                        <div>
                            <p class="cl__chat-name">{{ activeContact.name }}</p>
                            <p class="cl__chat-status">{{ activeContact.online ? 'Online' : 'Offline' }}</p>
                        </div>
                    </div>
                    <div class="cl__chat-actions">
                        <button class="cl__chat-btn" title="Call"><PhoneIcon :size="15" /></button>
                        <button class="cl__chat-btn" title="Video call"><VideoIcon :size="15" /></button>
                        <button class="cl__chat-btn" title="Info"><InfoIcon :size="15" /></button>
                    </div>
                </div>

                <!-- Messages -->
                <div ref="messagesEl" class="cl__messages">
                    <template v-for="(msg, i) in activeMessages" :key="msg.id">
                        <!-- Date divider -->
                        <div v-if="showDateDivider(i)" class="cl__date-divider">
                            <span>{{ msg.date }}</span>
                        </div>
                        <ChatBubble
                            :content="msg.content"
                            :sent="msg.sent"
                            :author="activeContact.name"
                            :time="msg.time"
                            :show-avatar="!msg.sent && (i === 0 || messages[activeId][i - 1]?.sent)"
                            :status="msg.status"
                            :type="msg.type ?? 'text'"
                        />
                    </template>
                </div>

                <!-- Input -->
                <ChatInput @send="onSend" @attach="onAttach" />
            </template>

            <!-- Empty state -->
            <div v-else class="cl__chat-empty">
                <MessageSquareIcon :size="36" class="cl__chat-empty-icon" />
                <p>Select a conversation</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, watch } from 'vue';
import {
    SearchIcon, SquarePenIcon, PhoneIcon, VideoIcon, InfoIcon, MessageSquareIcon,
} from '@lucide/vue';
import AppAvatar  from '@/Components/App/AppAvatar.vue';
import ChatBubble from '@/Components/App/ChatBubble.vue';
import ChatInput  from '@/Components/App/ChatInput.vue';

interface Contact {
    id:          string;
    name:        string;
    online:      boolean;
    lastMessage: string;
    lastTime:    string;
    unread:      number;
}

interface Message {
    id:      string;
    content: string;
    sent:    boolean;
    time:    string;
    date:    string;
    status?: 'sent' | 'delivered' | 'read';
    type?:   'text' | 'image' | 'file';
}

const contacts = ref<Contact[]>([
    { id: 'c1', name: 'Alice Martin',  online: true,  lastMessage: 'Sounds great, see you then!', lastTime: '9:41', unread: 2 },
    { id: 'c2', name: 'Bob Chen',      online: false, lastMessage: 'I added Sarah Johnson.',       lastTime: '8:30', unread: 0 },
    { id: 'c3', name: 'Carol Smith',   online: true,  lastMessage: 'Proposal sent to Beta Ltd.',   lastTime: 'Mon',  unread: 1 },
    { id: 'c4', name: 'Dave Wilson',   online: false, lastMessage: '12 min call — follow up.',     lastTime: 'Sun',  unread: 0 },
    { id: 'c5', name: 'Eve Johnson',   online: true,  lastMessage: 'Marked Delta as priority.',    lastTime: 'Sat',  unread: 0 },
]);

type MessageMap = Record<string, Message[]>;
const messages = ref<MessageMap>({
    c1: [
        { id: '1', content: 'Hey! Q2 pipeline review — tomorrow at 10?', sent: false, time: '9:30 AM', date: 'Today', status: 'read' },
        { id: '2', content: "Sure, works for me. I'll prep the numbers.", sent: true,  time: '9:32 AM', date: 'Today', status: 'read' },
        { id: '3', content: 'Sounds great, see you then!',                sent: false, time: '9:41 AM', date: 'Today' },
    ],
    c2: [
        { id: '1', content: 'I added Sarah Johnson from Acme Corp.', sent: false, time: '8:30 AM', date: 'Today' },
    ],
    c3: [
        { id: '1', content: 'Proposal sent to Beta Ltd. Should hear back by Wednesday.', sent: false, time: 'Mon 2:15 PM', date: 'Monday' },
    ],
    c4: [], c5: [],
});

const activeId    = ref('c1');
const search      = ref('');
const messagesEl  = ref<HTMLElement | null>(null);

const filteredContacts = computed(() => {
    const q = search.value.toLowerCase();
    return contacts.value.filter(c => !q || c.name.toLowerCase().includes(q));
});

const activeContact  = computed(() => contacts.value.find(c => c.id === activeId.value) ?? null);
const activeMessages = computed(() => messages.value[activeId.value] ?? []);

function showDateDivider(i: number): boolean {
    if (i === 0) return true;
    return activeMessages.value[i].date !== activeMessages.value[i - 1].date;
}

function scrollToBottom() {
    nextTick(() => {
        if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
    });
}

watch(activeId, scrollToBottom, { immediate: true });

function onSend(text: string) {
    if (!activeId.value) return;
    if (!messages.value[activeId.value]) messages.value[activeId.value] = [];
    messages.value[activeId.value].push({
        id:      String(Date.now()),
        content: text,
        sent:    true,
        time:    new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }),
        date:    'Today',
        status:  'sent',
    });
    const contact = contacts.value.find(c => c.id === activeId.value);
    if (contact) { contact.lastMessage = text; contact.lastTime = 'Just now'; }
    scrollToBottom();
}

function onAttach(file: File) {
    if (!activeId.value) return;
    if (!messages.value[activeId.value]) messages.value[activeId.value] = [];
    messages.value[activeId.value].push({
        id:      String(Date.now()),
        content: file.name,
        sent:    true,
        time:    new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }),
        date:    'Today',
        status:  'sent',
        type:    'file',
    });
    scrollToBottom();
}
</script>

<style scoped>
.cl {
    display: flex; height: 100%; min-height: 0;
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: 16px; overflow: hidden;
}

/* Sidebar */
.cl__sidebar {
    width: 260px; flex-shrink: 0;
    border-right: 1px solid var(--color-border);
    display: flex; flex-direction: column;
    background: var(--color-bg-subtle);
}
.cl__sidebar-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 14px 10px;
}
.cl__sidebar-title { font-size: 14px; font-weight: 700; color: var(--color-text-primary); }
.cl__new-btn {
    display: flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 8px; border: none;
    background: transparent; color: var(--color-text-muted); cursor: pointer;
    transition: all 120ms ease;
}
.cl__new-btn:hover { background: var(--color-surface); color: var(--color-text-primary); }

.cl__search-wrap {
    display: flex; align-items: center; gap: 7px;
    margin: 0 12px 10px; padding: 7px 10px;
    background: var(--color-surface); border: 1px solid var(--color-border);
    border-radius: 9px;
}
.cl__search-icon { color: var(--color-text-subtle); flex-shrink: 0; }
.cl__search {
    flex: 1; border: none; outline: none; background: transparent;
    font-size: 12.5px; color: var(--color-text-primary); font-family: var(--font-sans);
}

.cl__contacts { flex: 1; overflow-y: auto; }
.cl__contact {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; width: 100%; border: none;
    background: transparent; cursor: pointer; text-align: left;
    border-bottom: 1px solid var(--color-border);
    transition: background 100ms ease;
}
.cl__contact:hover { background: var(--color-surface); }
.cl__contact--active { background: var(--color-surface); }

.cl__contact-avatar { position: relative; flex-shrink: 0; }
.cl__online-dot {
    position: absolute; bottom: -1px; right: -1px;
    width: 9px; height: 9px; border-radius: 50%;
    background: #10b981; border: 2px solid var(--color-bg-subtle);
}
.cl__contact--active .cl__online-dot { border-color: var(--color-surface); }

.cl__contact-info { flex: 1; min-width: 0; }
.cl__contact-row { display: flex; align-items: center; justify-content: space-between; gap: 6px; }
.cl__contact-name {
    font-size: 13px; font-weight: 600; color: var(--color-text-primary);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.cl__contact-time { font-size: 10.5px; color: var(--color-text-subtle); flex-shrink: 0; }
.cl__contact-preview {
    font-size: 11.5px; color: var(--color-text-subtle);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1;
}
.cl__unread-badge {
    background: #6366f1; color: #fff;
    font-size: 10px; font-weight: 700; border-radius: 99px;
    padding: 1px 6px; flex-shrink: 0;
}

/* Chat area */
.cl__chat { flex: 1; display: flex; flex-direction: column; min-width: 0; overflow: hidden; }

.cl__chat-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; border-bottom: 1px solid var(--color-border);
    flex-shrink: 0;
}
.cl__chat-who { display: flex; align-items: center; gap: 10px; }
.cl__chat-name { font-size: 13.5px; font-weight: 700; color: var(--color-text-primary); margin: 0; }
.cl__chat-status { font-size: 11px; color: var(--color-text-subtle); margin: 0; }
.cl__chat-actions { display: flex; gap: 4px; }
.cl__chat-btn {
    display: flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 8px; border: none;
    background: transparent; color: var(--color-text-muted); cursor: pointer;
    transition: all 120ms ease;
}
.cl__chat-btn:hover { background: var(--color-bg-subtle); color: var(--color-text-primary); }

.cl__messages {
    flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 4px;
}

.cl__date-divider {
    display: flex; align-items: center; justify-content: center;
    margin: 12px 0;
}
.cl__date-divider span {
    font-size: 11px; color: var(--color-text-subtle);
    background: var(--color-bg-subtle); border: 1px solid var(--color-border);
    border-radius: 99px; padding: 2px 10px;
}

.cl__chat-empty {
    flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 10px; color: var(--color-text-subtle); font-size: 13px;
}
.cl__chat-empty-icon { opacity: 0.25; }
</style>
