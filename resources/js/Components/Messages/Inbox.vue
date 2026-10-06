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
    <div class="grid gap-5 lg:grid-cols-[21rem_1fr] xl:grid-cols-[25rem_1fr]" :class="heightClass">
        <!-- Conversation list -->
        <section
            class="min-h-0 flex-col overflow-hidden rounded-3xl bg-white shadow-xl shadow-ink/5 ring-1 ring-line"
            :class="active ? 'hidden lg:flex' : 'flex'"
        >
            <div class="space-y-3 p-4 pb-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-serif text-xl text-ink">Conversații</h2>
                    <span v-if="conversations.length" class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary">{{ conversations.length }}</span>
                </div>

                <div v-if="conversations.length" class="relative">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-soft/50" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Caută conversații…"
                        class="w-full rounded-full border-0 bg-primary/5 py-2.5 pl-10 pr-4 text-sm text-ink ring-1 ring-primary/10 placeholder:text-ink-soft/50 focus:bg-white focus:ring-2 focus:ring-primary"
                    />
                </div>

                <select
                    v-if="listings.length > 1"
                    :value="listingFilter ?? ''"
                    @change="filterListing"
                    class="w-full rounded-full border-0 bg-white py-2.5 pl-4 pr-9 text-xs font-semibold text-ink ring-1 ring-line focus:ring-2 focus:ring-primary"
                    aria-label="Filtrează după anunț"
                >
                    <option value="">Toate anunțurile</option>
                    <option v-for="listing in listings" :key="listing.id" :value="listing.id">{{ listing.title }}</option>
                </select>
            </div>

            <ul v-if="visibleConversations.length" class="min-h-0 flex-1 space-y-1 overflow-y-auto px-2 pb-3 [scrollbar-width:thin]">
                <li v-for="conversation in visibleConversations" :key="conversation.id">
                    <Link
                        :href="openUrl(conversation)"
                        preserve-scroll
                        class="flex items-start gap-3 rounded-2xl px-3 py-3 transition-all duration-150"
                        :class="active?.id === conversation.id ? 'bg-primary/5 ring-1 ring-primary/15' : 'hover:bg-primary/[0.03]'"
                    >
                        <span class="relative flex-none">
                            <span class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-primary to-primary-bright text-sm font-semibold text-white ring-2 ring-white">
                                <img v-if="conversation.counterpart.logo_url" :src="conversation.counterpart.logo_url" alt="" class="h-full w-full object-cover" />
                                <template v-else>{{ initials(conversation.counterpart.name) }}</template>
                            </span>
                            <span v-if="conversation.unread" class="absolute -right-0.5 -top-0.5 h-3.5 w-3.5 rounded-full bg-primary ring-2 ring-white" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-baseline justify-between gap-2">
                                <span class="truncate text-sm text-ink" :class="conversation.unread ? 'font-bold' : 'font-semibold'">{{ conversation.counterpart.name }}</span>
                                <span class="flex-none text-[11px]" :class="conversation.unread ? 'font-semibold text-primary' : 'text-ink-soft'">{{ conversation.last_message_at }}</span>
                            </span>
                            <span class="mt-1 flex items-center gap-1.5">
                                <span class="h-4 w-4 flex-none overflow-hidden rounded bg-primary/10">
                                    <img v-if="conversation.listing.cover_url" :src="conversation.listing.cover_url" alt="" class="h-full w-full object-cover" />
                                </span>
                                <span class="truncate text-xs font-medium text-primary">{{ conversation.listing.title }}</span>
                            </span>
                            <span class="mt-1 flex items-center gap-2">
                                <span class="line-clamp-1 flex-1 text-xs" :class="conversation.unread ? 'font-medium text-ink' : 'text-ink-soft'">
                                    <template v-if="conversation.last_message?.mine">Tu: </template>{{ conversation.last_message?.body }}
                                </span>
                                <span v-if="conversation.unread" class="flex h-5 min-w-[1.25rem] flex-none items-center justify-center rounded-full bg-primary px-1.5 text-[11px] font-semibold text-white">
                                    {{ conversation.unread > 99 ? '99+' : conversation.unread }}
                                </span>
                            </span>
                        </span>
                    </Link>
                </li>
            </ul>

            <div v-else-if="conversations.length" class="flex flex-1 flex-col items-center justify-center px-6 py-10 text-center">
                <p class="text-sm font-medium text-ink">Niciun rezultat</p>
                <p class="mt-1 text-sm text-ink-soft">Încearcă alt termen de căutare.</p>
            </div>

            <div v-else class="flex flex-1 flex-col items-center justify-center px-6 py-10 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary"><ChatBubbleLeftRightIcon class="h-7 w-7" /></span>
                <p class="mt-4 text-sm font-semibold text-ink">Nicio conversație încă</p>
                <p v-if="isProvider" class="mt-1 text-sm text-ink-soft">Clienții îți pot scrie direct din pagina fiecărui anunț publicat.</p>
                <template v-else>
                    <p class="mt-1 text-sm text-ink-soft">Deschide un anunț și trimite un mesaj furnizorului.</p>
                    <Link :href="route('listings.index')" class="mt-5 rounded-full bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary/25 transition-all duration-200 hover:-translate-y-0.5 hover:bg-primary-bright">Vezi anunțurile</Link>
                </template>
            </div>
        </section>

        <!-- Thread -->
        <section
            class="min-h-0 flex-col overflow-hidden rounded-3xl bg-white shadow-xl shadow-ink/5 ring-1 ring-line"
            :class="active ? 'flex' : 'hidden lg:flex'"
        >
            <template v-if="active">
                <header class="flex items-center gap-3 border-b border-line bg-white px-4 py-3.5">
                    <Link :href="listUrl" preserve-scroll class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-ink-soft hover:bg-primary/5 hover:text-primary lg:hidden" aria-label="Înapoi la conversații">
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <span class="flex h-11 w-11 flex-none items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-primary to-primary-bright text-sm font-semibold text-white ring-2 ring-primary/10">
                        <img v-if="active.counterpart.logo_url" :src="active.counterpart.logo_url" alt="" class="h-full w-full object-cover" />
                        <template v-else>{{ initials(active.counterpart.name) }}</template>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[15px] font-semibold text-ink">{{ active.counterpart.name }}</p>
                        <p class="truncate text-xs text-ink-soft">despre <span class="font-medium text-primary">{{ active.listing.title }}</span></p>
                    </div>
                    <Link
                        v-if="listingHref"
                        :href="listingHref"
                        class="inline-flex flex-none items-center gap-1.5 rounded-full bg-primary/10 px-3.5 py-2 text-xs font-semibold text-primary transition-colors duration-150 hover:bg-primary hover:text-white"
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

            <div v-else class="flex flex-1 flex-col items-center justify-center bg-gradient-to-br from-primary/5 via-white to-primary/[0.03] px-6 text-center">
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white text-primary shadow-lg shadow-primary/10 ring-1 ring-primary/10">
                    <ChatBubbleLeftRightIcon class="h-8 w-8" />
                </span>
                <p class="mt-4 font-serif text-lg text-ink">Mesajele tale</p>
                <p class="mt-1 text-sm text-ink-soft">Selectează o conversație din listă.</p>
            </div>
        </section>
    </div>
</template>
