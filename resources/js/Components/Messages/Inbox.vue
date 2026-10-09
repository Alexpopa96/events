<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    ArrowTopRightOnSquareIcon,
    ChatBubbleLeftRightIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';
import ChatThread from '@/Components/Messages/ChatThread.vue';
import { usePolling } from '@/Composables/usePolling';
import { useLastChat } from '@/Composables/useLastChat';

const props = defineProps({
    // 'client' or 'provider' — decides routes, listing links and empty-state copy.
    side: { type: String, required: true },
    conversations: { type: Array, required: true },
    active: { type: Object, default: null },
    listings: { type: Array, default: () => [] },
    listingFilter: { type: Number, default: null },
    // Tailwind height classes, so each layout can size the panes to its own chrome.
    heightClass: { type: String, default: 'h-[70vh] min-h-[30rem]' },
});

const isProvider = computed(() => props.side === 'provider');

const quickReplies = [
    'Sunt disponibil pe data respectivă, hai să stabilim detaliile.',
    'Vă trimit oferta în cel mai scurt timp.',
    'Prețul pornește de la suma afișată în anunț, în funcție de detalii poate varia.',
    'Mulțumesc pentru mesaj! Vă răspund în cel mai scurt timp.',
];
const indexRoute = computed(() => (isProvider.value ? 'provider.messages.index' : 'messages.index'));
const storeRoute = computed(() => (isProvider.value ? 'provider.messages.store' : 'messages.store'));

const openUrl = (conversation) => route(indexRoute.value, { conversation: conversation.id, ...(props.listingFilter ? { listing: props.listingFilter } : {}) });
const listUrl = computed(() => route(indexRoute.value, props.listingFilter ? { listing: props.listingFilter } : {}));

const listingHref = computed(() => {
    if (!props.active) return null;
    if (isProvider.value) return route('provider.listings.edit', props.active.listing.id);
    return props.active.listing.published ? route('listings.show', props.active.listing.slug) : null;
});

const search = ref('');
const visibleConversations = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return props.conversations;
    return props.conversations.filter((c) => [c.counterpart.name, c.listing.title, c.last_message?.body]
        .some((text) => (text || '').toLowerCase().includes(term)));
});

const filterListing = (event) => {
    const value = event.target.value;
    router.get(route(indexRoute.value), value ? { listing: value } : {}, { preserveScroll: true });
};

const initials = (name) => (name || '?')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

/* ---------- floating bubble ---------- */
// Remember the client's open chat so other pages can offer a bubble back into it.
const page = usePage();
const { remember } = useLastChat();
watch(() => props.active?.id, () => {
    if (isProvider.value || !props.active || !page.props.auth.user) return;

    remember({
        userId: page.props.auth.user.id,
        id: props.active.id,
        name: props.active.counterpart.name,
        logoUrl: props.active.counterpart.logo_url ?? null,
    });
}, { immediate: true });

/* ---------- fallback refresh ---------- */
// New messages/typing/seen-ticks arrive instantly over the socket (see ChatThread);
// this periodic reload is just a safety net — it also re-marks the open thread as
// read, which is why it's still worth keeping, just far less often than before.
usePolling(() => router.reload({
    only: ['conversations', 'active', 'unreadMessages'],
    preserveScroll: true,
    preserveState: true,
}), 20000);
</script>

<template>
    <div
        class="grid overflow-hidden rounded-[24px] border-2 border-ivt-line bg-white shadow-[0_24px_60px_-36px_rgba(26,20,51,0.35)] lg:grid-cols-[19rem_1fr] xl:grid-cols-[21rem_1fr]"
        :class="heightClass"
    >
        <!-- Conversation list -->
        <section
            class="min-h-0 flex-col border-ivt-line lg:border-r-2"
            :class="active ? 'hidden lg:flex' : 'flex'"
        >
            <div class="space-y-2.5 border-b border-ivt-line px-4 pb-3 pt-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-[19px] font-medium text-ivt-ink">Conversații</h2>
                    <span v-if="conversations.length" class="rounded-full bg-ivt-ink px-2 py-0.5 text-[11px] font-bold tabular-nums text-ivt-accent-bright">{{ conversations.length }}</span>
                </div>

                <label v-if="conversations.length" class="group flex items-center gap-2 rounded-xl bg-ivt-paper-2 px-3 py-2 transition-colors focus-within:bg-white focus-within:ring-2 focus-within:ring-primary/30">
                    <MagnifyingGlassIcon class="h-4 w-4 flex-none text-ivt-ink-faint group-focus-within:text-primary" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Caută…"
                        class="w-full border-0 bg-transparent p-0 text-[13px] text-ivt-ink placeholder:text-ivt-ink-faint focus:outline-none focus:ring-0"
                    />
                </label>

                <select
                    v-if="listings.length > 1"
                    :value="listingFilter ?? ''"
                    @change="filterListing"
                    class="w-full rounded-xl border-0 bg-ivt-paper-2 py-2 pl-3 pr-9 text-xs font-semibold text-ivt-ink focus:ring-2 focus:ring-primary/30"
                    aria-label="Filtrează după anunț"
                >
                    <option value="">Toate anunțurile</option>
                    <option v-for="listing in listings" :key="listing.id" :value="listing.id">{{ listing.title }}</option>
                </select>
            </div>

            <ul v-if="visibleConversations.length" class="min-h-0 flex-1 space-y-0.5 overflow-y-auto p-2 [scrollbar-width:thin]">
                <li v-for="conversation in visibleConversations" :key="conversation.id">
                    <Link
                        :href="openUrl(conversation)"
                        preserve-scroll
                        class="group relative flex items-center gap-3 rounded-2xl px-2.5 py-2.5 transition-colors duration-150"
                        :class="active?.id === conversation.id ? 'bg-ivt-ink text-ivt-paper' : 'hover:bg-ivt-paper-2'"
                    >
                        <span class="relative flex-none">
                            <span
                                class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl text-[13px] font-semibold"
                                :class="active?.id === conversation.id ? 'bg-white/10 text-ivt-accent-bright' : 'bg-brand text-white'"
                            >
                                <img v-if="conversation.counterpart.logo_url" :src="conversation.counterpart.logo_url" alt="" class="h-full w-full object-cover" />
                                <template v-else>{{ initials(conversation.counterpart.name) }}</template>
                            </span>
                            <span
                                v-if="conversation.unread"
                                class="absolute -right-1 -top-1 h-3 w-3 rounded-full bg-ivt-accent-bright ring-2"
                                :class="active?.id === conversation.id ? 'ring-ivt-ink' : 'ring-white'"
                            />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-baseline justify-between gap-2">
                                <span
                                    class="truncate text-[13.5px]"
                                    :class="[
                                        conversation.unread ? 'font-bold' : 'font-semibold',
                                        active?.id === conversation.id ? 'text-white' : 'text-ivt-ink',
                                    ]"
                                >{{ conversation.counterpart.name }}</span>
                                <span
                                    class="flex-none text-[10.5px] tabular-nums"
                                    :class="active?.id === conversation.id ? 'text-ivt-on-dark-dim' : (conversation.unread ? 'font-semibold text-primary' : 'text-ivt-ink-faint')"
                                >{{ conversation.last_message_at }}</span>
                            </span>
                            <span
                                class="mt-0.5 block truncate text-[11px] font-semibold uppercase tracking-[0.04em]"
                                :class="active?.id === conversation.id ? 'text-ivt-accent-bright' : 'text-primary/80'"
                            >{{ conversation.listing.title }}</span>
                            <span class="mt-0.5 flex items-center gap-2">
                                <span
                                    class="line-clamp-1 flex-1 text-[12px]"
                                    :class="active?.id === conversation.id ? 'text-ivt-on-dark-dim' : (conversation.unread ? 'font-medium text-ivt-ink' : 'text-ivt-ink-faint')"
                                >
                                    <template v-if="conversation.last_message?.mine">Tu: </template>{{ conversation.last_message?.body }}
                                </span>
                                <span v-if="conversation.unread" class="flex h-[18px] min-w-[18px] flex-none items-center justify-center rounded-full bg-brand px-1 text-[10px] font-bold text-white">
                                    {{ conversation.unread > 99 ? '99+' : conversation.unread }}
                                </span>
                            </span>
                        </span>
                    </Link>
                </li>
            </ul>

            <div v-else-if="conversations.length" class="flex flex-1 flex-col items-center justify-center px-6 py-10 text-center">
                <p class="text-sm font-semibold text-ivt-ink">Niciun rezultat</p>
                <p class="mt-1 text-[13px] text-ivt-ink-faint">Încearcă alt termen de căutare.</p>
            </div>

            <div v-else class="flex flex-1 flex-col items-center justify-center px-6 py-10 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-ivt-paper-2 text-primary"><ChatBubbleLeftRightIcon class="h-6 w-6" stroke-width="1.5" /></span>
                <p class="mt-3 text-sm font-semibold text-ivt-ink">Nicio conversație încă</p>
                <p v-if="isProvider" class="mt-1 text-[13px] text-ivt-ink-faint">Clienții îți pot scrie direct din pagina fiecărui anunț publicat.</p>
                <template v-else>
                    <p class="mt-1 text-[13px] text-ivt-ink-faint">Deschide un anunț și trimite un mesaj furnizorului.</p>
                    <Link :href="route('listings.index')" class="mt-4 rounded-full bg-ivt-ink px-5 py-2 text-[13px] font-semibold text-ivt-paper transition-transform hover:-translate-y-0.5">Vezi anunțurile</Link>
                </template>
            </div>
        </section>

        <!-- Thread -->
        <section
            class="min-h-0 flex-col"
            :class="active ? 'flex' : 'hidden lg:flex'"
        >
            <template v-if="active">
                <header class="flex items-center gap-3 border-b border-ivt-line px-4 py-3">
                    <Link :href="listUrl" preserve-scroll class="flex h-8 w-8 flex-none items-center justify-center rounded-full text-ivt-ink-soft hover:bg-ivt-paper-2 hover:text-primary lg:hidden" aria-label="Înapoi la conversații">
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <span class="flex h-10 w-10 flex-none items-center justify-center overflow-hidden rounded-xl bg-brand text-[13px] font-semibold text-white">
                        <img v-if="active.counterpart.logo_url" :src="active.counterpart.logo_url" alt="" class="h-full w-full object-cover" />
                        <template v-else>{{ initials(active.counterpart.name) }}</template>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[14.5px] font-semibold text-ivt-ink">{{ active.counterpart.name }}</p>
                        <p class="truncate text-[12px] text-ivt-ink-faint">despre <span class="font-semibold text-primary">{{ active.listing.title }}</span></p>
                    </div>
                    <Link
                        v-if="listingHref"
                        :href="listingHref"
                        class="inline-flex flex-none items-center gap-1.5 rounded-full bg-ivt-paper-2 px-3 py-1.5 text-[12px] font-semibold text-ivt-ink transition-colors duration-150 hover:bg-ivt-ink hover:text-ivt-paper"
                    >
                        <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5" /> <span class="hidden sm:inline">Vezi anunțul</span>
                    </Link>
                </header>

                <div class="min-h-0 flex-1">
                    <ChatThread
                        :messages="active.messages"
                        :post-url="route(storeRoute, active.id)"
                        :reset-key="active.id"
                        :conversation-id="active.id"
                        :counterpart-last-read-id="active.counterpart_last_read_id"
                        :quick-replies="isProvider ? quickReplies : []"
                    />
                </div>
            </template>

            <div v-else class="relative flex flex-1 flex-col items-center justify-center overflow-hidden bg-ivt-paper px-6 text-center">
                <span
                    class="pointer-events-none absolute left-1/2 top-1/2 h-72 w-72 -translate-x-1/2 -translate-y-1/2 rounded-full"
                    style="background: radial-gradient(circle, rgba(124,58,237,0.12), transparent 70%);"
                />
                <span class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-primary shadow-ivt-soft ring-1 ring-ivt-line">
                    <ChatBubbleLeftRightIcon class="h-7 w-7" stroke-width="1.5" />
                </span>
                <p class="relative mt-4 font-display text-lg text-ivt-ink">Mesajele tale</p>
                <p class="relative mt-1 text-[13px] text-ivt-ink-faint">Selectează o conversație din listă.</p>
            </div>
        </section>
    </div>
</template>
