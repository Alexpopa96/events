<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ChatBubbleLeftRightIcon, PaperAirplaneIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    messages: { type: Array, required: true },
    // Where a new message is POSTed, plus any extra fields the endpoint needs (e.g. listing_id).
    postUrl: { type: String, required: true },
    extra: { type: Object, default: () => ({}) },
    placeholder: { type: String, default: 'Scrie un mesaj…' },
    emptyText: { type: String, default: 'Nicio conversație încă. Scrie primul mesaj.' },
    // Changing this (e.g. the conversation id) clears the draft and jumps to the latest message.
    resetKey: { type: [String, Number], default: null },
    disabled: { type: Boolean, default: false },
    // Enables the live channel (typing + instant delivery + seen ticks). Null until
    // the thread has a real conversation behind it (e.g. before the first message).
    conversationId: { type: [String, Number], default: null },
    // The other side's last-read message id, so "mine" bubbles can show a seen tick.
    counterpartLastReadId: { type: Number, default: 0 },
    // Canned openers (providers only) shown as tappable chips above the composer.
    quickReplies: { type: Array, default: () => [] },
});

const page = usePage();
const myId = computed(() => page.props.auth.user?.id);

const form = useForm({ body: '' });
const scroller = ref(null);
const composer = ref(null);

const scrollToBottom = (smooth = false) => nextTick(() => {
    if (scroller.value) scroller.value.scrollTo({ top: scroller.value.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
});

/* ---------- live channel: instant delivery, typing, seen ---------- */
// Messages that arrived over the socket but aren't in `props.messages` yet (the
// parent hasn't re-fetched). Cleared whenever the prop catches up, so this never
// grows into a second source of truth.
const liveMessages = ref([]);
const seenUpTo = ref(props.counterpartLastReadId);
const counterpartTyping = ref(false);
let typingClearTimer = null;
let typingThrottle = 0;
let channel = null;

const allMessages = computed(() => {
    const seenIds = new Set(props.messages.map((m) => m.id));
    return [...props.messages, ...liveMessages.value.filter((m) => !seenIds.has(m.id))];
});

const subscribe = (id) => {
    if (!id || !window.Echo) return;

    channel = window.Echo.private(`conversation.${id}`)
        .listen('.message.sent', (event) => {
            if (event.sender_id === myId.value) return; // my own send already lands via the form response

            liveMessages.value.push({ id: event.id, body: event.body, mine: false, time: event.time, day: event.day });
            counterpartTyping.value = false;
            scrollToBottom(true);
        })
        .listen('.conversation.read', (event) => {
            seenUpTo.value = Math.max(seenUpTo.value, event.last_read_id);
        })
        .listenForWhisper('typing', () => {
            counterpartTyping.value = true;
            clearTimeout(typingClearTimer);
            typingClearTimer = setTimeout(() => { counterpartTyping.value = false; }, 3000);
        });
};

const unsubscribe = (id) => {
    if (id && window.Echo) window.Echo.leave(`conversation.${id}`);
    channel = null;
    clearTimeout(typingClearTimer);
    counterpartTyping.value = false;
};

watch(() => props.conversationId, (id, previous) => {
    unsubscribe(previous);
    liveMessages.value = [];
    seenUpTo.value = props.counterpartLastReadId;
    subscribe(id);
}, { immediate: true });

watch(() => props.counterpartLastReadId, (value) => { seenUpTo.value = Math.max(seenUpTo.value, value); });

// Once the parent's own messages include everything we received live, drop the overlay.
watch(() => props.messages.length, () => {
    const ids = new Set(props.messages.map((m) => m.id));
    liveMessages.value = liveMessages.value.filter((m) => !ids.has(m.id));
});

onBeforeUnmount(() => unsubscribe(props.conversationId));

const notifyTyping = () => {
    if (!channel || props.disabled) return;
    const now = Date.now();
    if (now - typingThrottle < 2000) return; // at most once every 2s, no point whispering on every keystroke
    typingThrottle = now;
    channel.whisper('typing', {});
};

/* ---------- rendering ---------- */
// Messages grouped by day, so a date pill separates each run.
const groups = computed(() => {
    const out = [];
    for (const message of allMessages.value) {
        const last = out[out.length - 1];
        if (last && last.day === message.day) last.items.push(message);
        else out.push({ day: message.day, items: [message] });
    }
    return out;
});

const isSeen = (message) => message.mine && message.id <= seenUpTo.value;

const resize = () => nextTick(() => {
    const el = composer.value;
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = `${Math.min(el.scrollHeight, 140)}px`;
});

const send = () => {
    if (props.disabled || !form.body.trim() || form.processing) return;

    form.transform((data) => ({ ...data, ...props.extra })).post(props.postUrl, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            resize();
            scrollToBottom(true);
        },
    });
};

const useQuickReply = (text) => {
    form.body = form.body ? `${form.body}\n${text}` : text;
    nextTick(() => { composer.value?.focus(); resize(); });
};

const onKeydown = (event) => {
    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
        event.preventDefault();
        send();
    }
};

onMounted(() => scrollToBottom());

watch(() => props.resetKey, () => {
    form.reset();
    form.clearErrors();
    resize();
    scrollToBottom();
});

watch(() => allMessages.value.length, (now, before) => {
    if (now > before) scrollToBottom(true);
});
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <div ref="scroller" class="min-h-0 flex-1 space-y-1.5 overflow-y-auto bg-gradient-to-b from-primary/[0.04] to-primary/[0.02] px-4 py-5 [scrollbar-width:thin] sm:px-6">
            <div v-if="!allMessages.length" class="flex h-full flex-col items-center justify-center px-6 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-white text-primary shadow-lg shadow-primary/10 ring-1 ring-primary/10">
                    <ChatBubbleLeftRightIcon class="h-7 w-7" />
                </span>
                <p class="mt-3 text-sm text-ink-soft">{{ emptyText }}</p>
            </div>

            <template v-for="group in groups" :key="group.day">
                <div class="flex justify-center py-3">
                    <span class="rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-ink-soft shadow-sm shadow-ink/5 ring-1 ring-line">{{ group.day }}</span>
                </div>
                <div v-for="message in group.items" :key="message.id" class="flex" :class="message.mine ? 'justify-end' : 'justify-start'">
                    <div
                        class="max-w-[85%] px-4 py-2.5 sm:max-w-[68%]"
                        :class="message.mine
                            ? 'rounded-3xl rounded-br-lg bg-gradient-to-br from-primary to-primary-bright text-white shadow-md shadow-primary/20'
                            : 'rounded-3xl rounded-bl-lg bg-white text-ink shadow-sm shadow-ink/5 ring-1 ring-line'"
                    >
                        <p class="whitespace-pre-line break-words text-sm leading-relaxed">{{ message.body }}</p>
                        <p class="mt-1 flex items-center justify-end gap-1 text-right text-[10px]" :class="message.mine ? 'text-white/70' : 'text-ink-soft/70'">
                            {{ message.time }}<span v-if="isSeen(message)">· Văzut</span>
                        </p>
                    </div>
                </div>
            </template>

            <div v-if="counterpartTyping" class="flex justify-start">
                <div class="flex items-center gap-1 rounded-3xl rounded-bl-lg bg-white px-4 py-3 shadow-sm shadow-ink/5 ring-1 ring-line">
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-ink-soft/50 [animation-delay:-0.2s]" />
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-ink-soft/50 [animation-delay:-0.1s]" />
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-ink-soft/50" />
                </div>
            </div>
        </div>

        <form @submit.prevent="send" class="border-t border-line bg-white p-3 sm:p-4">
            <div v-if="quickReplies.length" class="mb-2.5 flex gap-1.5 overflow-x-auto pb-0.5 [scrollbar-width:thin]">
                <button
                    v-for="reply in quickReplies"
                    :key="reply"
                    type="button"
                    :disabled="disabled"
                    @click="useQuickReply(reply)"
                    class="flex-none rounded-full bg-primary/5 px-3 py-1.5 text-xs font-medium text-primary transition-colors duration-150 hover:bg-primary/10 disabled:pointer-events-none disabled:opacity-40"
                >
                    {{ reply }}
                </button>
            </div>
            <p v-if="form.errors.body || form.errors.listing_id" class="mb-2 px-2 text-xs text-primary">{{ form.errors.body || form.errors.listing_id }}</p>
            <div class="flex items-end gap-2 rounded-[1.75rem] bg-primary/5 p-1.5 ring-1 ring-primary/10 transition-shadow duration-150 focus-within:bg-white focus-within:ring-2 focus-within:ring-primary">
                <textarea
                    ref="composer"
                    v-model="form.body"
                    rows="1"
                    maxlength="2000"
                    :placeholder="placeholder"
                    :disabled="disabled"
                    @keydown="onKeydown"
                    @input="resize(); notifyTyping()"
                    class="max-h-36 min-h-[2.5rem] flex-1 resize-none border-0 bg-transparent px-3.5 py-2.5 text-sm text-ink placeholder:text-ink-soft/60 focus:ring-0"
                ></textarea>
                <button
                    type="submit"
                    :disabled="disabled || form.processing || !form.body.trim()"
                    class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-primary text-white shadow-md shadow-primary/25 transition-all duration-200 hover:bg-primary-bright active:scale-95 disabled:pointer-events-none disabled:opacity-40"
                    aria-label="Trimite"
                >
                    <PaperAirplaneIcon class="h-[18px] w-[18px]" />
                </button>
            </div>
            <p class="mt-1.5 hidden px-3 text-[11px] text-ink-soft/70 sm:block">Enter trimite · Shift+Enter rând nou</p>
        </form>
    </div>
</template>
