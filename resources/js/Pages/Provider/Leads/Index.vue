<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import ChatThread from '@/Components/Messages/ChatThread.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { usePolling } from '@/Composables/usePolling';
import {
    ArrowLeftIcon,
    BanknotesIcon,
    BuildingStorefrontIcon,
    CalendarIcon,
    CheckCircleIcon,
    ChatBubbleLeftRightIcon,
    ClockIcon,
    CurrencyDollarIcon,
    EnvelopeIcon,
    InboxIcon,
    MagnifyingGlassIcon,
    MapPinIcon,
    PhoneIcon,
    SparklesIcon,
    TrashIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const toast = useToast();

const props = defineProps({
    leads: Array,
    hasCategories: Boolean,
    chat: { type: Object, default: null },
    // Restored from the URL (?cerere=&tab=&filtru=) so a refresh keeps the same view.
    selected: { type: Number, default: null },
    tab: { type: String, default: 'call' },
    filter: { type: String, default: 'all' },
    q: { type: String, default: '' },
});

const filters = [
    { value: 'all', label: 'Toate' },
    { value: 'new', label: 'Noi' },
    { value: 'contacted', label: 'Contactate' },
];

const activeFilter = ref(props.filter);
const search = ref(props.q);
const selectedId = ref(props.selected);
// On small screens the list and the open request take turns; on lg they sit side by side.
const mobileDetail = ref(props.selected !== null);

// Lowercase and strip diacritics, so "stefan" finds "Ștefan".
const fold = (text) => (text ?? '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

const searched = computed(() => {
    const terms = fold(search.value).split(/\s+/).filter(Boolean);
    if (!terms.length) return props.leads;

    return props.leads.filter((lead) => {
        const haystack = fold([lead.name, lead.email, lead.category, lead.city, lead.county, lead.message].join(' '));
        return terms.every((term) => haystack.includes(term));
    });
});

const counts = computed(() => ({
    all: searched.value.length,
    new: searched.value.filter((lead) => !lead.contacted).length,
    contacted: searched.value.filter((lead) => lead.contacted).length,
}));

const byStatus = (leads) => {
    if (activeFilter.value === 'new') return leads.filter((lead) => !lead.contacted);
    if (activeFilter.value === 'contacted') return leads.filter((lead) => lead.contacted);
    return leads;
};

// What the list shows: status filter + search.
const filteredLeads = computed(() => byStatus(searched.value));

// The open request follows the status filter but NOT the search, so typing in the
// search box narrows the list without swapping out the pane on the right.
const openable = computed(() => byStatus(props.leads));
const current = computed(() => openable.value.find((lead) => lead.id === selectedId.value) ?? openable.value[0] ?? null);

const open = (lead) => {
    selectedId.value = lead.id;
    mobileDetail.value = true;
};

const backToList = () => {
    selectedId.value = null;
    mobileDetail.value = false;
};

const initials = (name) => (name || '?')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

const place = (lead) => [lead.city, lead.county].filter(Boolean).join(', ');

const details = (lead) => [
    { label: 'Locație', value: place(lead), icon: MapPinIcon },
    { label: 'Data evenimentului', value: lead.event_date, icon: CalendarIcon },
    { label: 'Buget', value: lead.budget_range, icon: BanknotesIcon },
].filter((item) => item.value);

const quickReplies = [
    'Sunt disponibil pe data respectivă, hai să stabilim detaliile.',
    'Vă trimit oferta în cel mai scurt timp.',
    'Prețul pornește de la suma afișată în anunț, în funcție de detalii poate varia.',
    'Mulțumesc pentru mesaj! Vă răspund în cel mai scurt timp.',
];

/* ---------- contact tabs ---------- */
const tabs = [
    { key: 'call', label: 'Apel', icon: PhoneIcon },
    { key: 'email', label: 'Email', icon: EnvelopeIcon },
    { key: 'chat', label: 'Chat', icon: ChatBubbleLeftRightIcon },
    { key: 'offer', label: 'Ofertă', icon: CurrencyDollarIcon },
];

const tab = ref(props.tab);

// A lead can be chatted with when the client has an account and the provider has a
// published listing in that category (the thread hangs off one of those listings).
const canChat = computed(() => (current.value?.message_listings.length ?? 0) > 0);

// The chat prop is fetched on demand for one lead; ignore it if it belongs to another.
const thread = computed(() => (props.chat && props.chat.lead_id === current.value?.id ? props.chat : null));

const chatListingId = ref(null);
watch(current, (lead) => { chatListingId.value = lead?.message_listings?.[0]?.id ?? null; }, { immediate: true });

/* ---------- URL state ---------- */
// Selected request, tab and filter live in the query string, so refresh/share restores them.
const query = () => {
    const params = {};
    if (current.value && (selectedId.value !== null || tab.value === 'chat')) params.cerere = current.value.id;
    if (tab.value !== 'call') params.tab = tab.value;
    if (activeFilter.value !== 'all') params.filtru = activeFilter.value;
    if (search.value.trim()) params.q = search.value.trim();
    return params;
};

const sync = () => {
    if (tab.value === 'chat' && current.value && canChat.value) {
        // The chat tab also needs the thread from the server (and keeps it fresh while polling).
        router.get(route('provider.leads.index'), query(), {
            only: ['chat', 'leads', 'unreadMessages'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
        return;
    }

    // Client-side only: rewrites the URL without asking the server for anything.
    router.replace({ url: route('provider.leads.index', query()), preserveState: true, preserveScroll: true });
};

watch([selectedId, tab, activeFilter, () => current.value?.id], sync);
// Typing rewrites the URL too, but not on every keystroke.
let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(sync, 300);
});
onBeforeUnmount(() => clearTimeout(searchTimer));

// New messages arrive instantly over the socket (see ChatThread); this is now just
// a safety net — and it's what re-marks the open thread as read, every 20s instead of 5s.
usePolling(() => tab.value === 'chat' && canChat.value && sync(), 20000, () => tab.value === 'chat');

// Outside the chat tab, still pick up new client messages so the badges stay current.
usePolling(
    () => router.reload({ only: ['leads', 'unreadMessages'], preserveState: true, preserveScroll: true }),
    15000,
    () => !(tab.value === 'chat' && canChat.value),
);

/* ---------- status badges ---------- */
// What needs attention on a request: unread chat first, then who owes the next reply, then contact state.
const statuses = (lead) => {
    const thread = lead.thread;
    const out = [];

    if (thread?.unread) {
        out.push({ key: 'unread', label: `${thread.unread} ${thread.unread === 1 ? 'mesaj nou' : 'mesaje noi'}`, class: 'bg-primary text-white', icon: ChatBubbleLeftRightIcon });
    } else if (thread?.last_from === 'client') {
        out.push({ key: 'reply', label: 'De răspuns', class: 'bg-amber-100 text-amber-700', icon: ChatBubbleLeftRightIcon });
    } else if (thread?.last_from === 'me') {
        out.push({ key: 'waiting', label: 'Așteaptă răspuns', class: 'bg-ivt-paper-2 text-ivt-ink-soft', icon: ClockIcon });
    }

    if (lead.contacted) {
        out.push({ key: 'contacted', label: 'Contactat', class: 'bg-emerald-100 text-emerald-700', icon: CheckCircleIcon });
    } else if (!thread) {
        out.push({ key: 'new', label: 'Nouă', class: 'bg-ivt-gold/15 text-ivt-gold', icon: SparklesIcon });
    }

    if (lead.offer) {
        out.push(offerStatusMeta[lead.offer.status]);
    }

    return out;
};

/* ---------- offer form ---------- */
const offerStatusMeta = {
    sent: { key: 'offer-sent', label: 'Ofertă trimisă', class: 'bg-primary/10 text-primary', icon: CurrencyDollarIcon },
    viewed: { key: 'offer-viewed', label: 'Ofertă văzută', class: 'bg-primary/10 text-primary', icon: CurrencyDollarIcon },
    accepted: { key: 'offer-accepted', label: 'Ofertă acceptată', class: 'bg-emerald-100 text-emerald-700', icon: CheckCircleIcon },
    declined: { key: 'offer-declined', label: 'Ofertă refuzată', class: 'bg-rose-100 text-rose-700', icon: XMarkIcon },
    withdrawn: { key: 'offer-withdrawn', label: 'Ofertă retrasă', class: 'bg-ivt-paper-2 text-ivt-ink-soft', icon: XMarkIcon },
    expired: { key: 'offer-expired', label: 'Ofertă expirată', class: 'bg-ivt-paper-2 text-ivt-ink-soft', icon: ClockIcon },
};

const offerForm = useForm({ listing_id: null, price: null, valid_until: '', includesText: '', message: '' });

// A sensible default validity window: up to the event date when there is one, capped at 14 days out.
const defaultValidUntil = (lead) => {
    const max = new Date(Date.now() + 14 * 86400000);
    if (lead?.event_date_iso) {
        const eventDate = new Date(lead.event_date_iso);
        if (eventDate < max) return lead.event_date_iso;
    }
    return max.toISOString().slice(0, 10);
};

// Reloads the form whenever a different lead becomes current, from its existing offer if editable.
watch(current, (lead) => {
    offerForm.clearErrors();
    if (lead?.offer && lead.offer.editable) {
        offerForm.listing_id = lead.offer.listing_id;
        offerForm.price = lead.offer.price;
        offerForm.valid_until = lead.offer.valid_until;
        offerForm.includesText = lead.offer.includes.join('\n');
        offerForm.message = lead.offer.message ?? '';
    } else {
        offerForm.listing_id = lead?.offer_listings?.[0]?.id ?? null;
        offerForm.price = null;
        offerForm.valid_until = defaultValidUntil(lead);
        offerForm.includesText = '';
        offerForm.message = '';
    }
}, { immediate: true });

const submitOffer = () => {
    offerForm
        .transform((data) => ({
            ...data,
            includes: data.includesText.split('\n').map((line) => line.trim()).filter(Boolean),
        }))
        .post(route('provider.leads.offer.store', current.value.id), {
            preserveScroll: true,
            onSuccess: () => toast.success(current.value.offer ? 'Oferta a fost actualizată.' : 'Oferta a fost trimisă.'),
        });
};

const offerToWithdraw = ref(null);
const withdrawingOffer = ref(false);

// Bridges the dialog's own open/close (e.g. Esc, backdrop click) with which offer it's confirming for.
const offerWithdrawDialogOpen = computed({
    get: () => !!offerToWithdraw.value,
    set: (open) => { if (!open) offerToWithdraw.value = null; },
});

const withdrawOffer = () => {
    withdrawingOffer.value = true;
    router.delete(route('provider.offers.destroy', offerToWithdraw.value.id), {
        preserveScroll: true,
        onSuccess: () => toast.success('Oferta a fost retrasă.'),
        onFinish: () => { withdrawingOffer.value = false; offerToWithdraw.value = null; },
    });
};

const needsAttention = (lead) => !!lead.thread?.unread || (!lead.contacted && !lead.thread);

const mailto = (lead) => `mailto:${lead.email}?subject=${encodeURIComponent(`Oferta pentru ${lead.category}`)}`;

const markingContacted = ref(null);

const markContacted = (lead) => {
    if (markingContacted.value) return;
    markingContacted.value = lead.id;
    router.post(route('provider.leads.contacted', lead.id), {}, {
        preserveScroll: true,
        onFinish: () => { markingContacted.value = null; },
    });
};
</script>

<template>
    <ProviderLayout title="Cereri de ofertă">
        <div v-if="!hasCategories" class="bg-white border border-ivt-line rounded-2xl p-10 text-center shadow-sm shadow-ivt-ink/5">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-ivt-paper-2 text-primary">
                <BuildingStorefrontIcon class="h-6 w-6" />
            </span>
            <p class="mt-3 text-sm font-medium text-ivt-ink">Publică un anunț mai întâi</p>
            <p class="mt-1 text-sm text-ivt-ink-soft">Publică cel puțin un anunț ca să vezi cererile de ofertă din categoria ta.</p>
        </div>

        <div v-else-if="!leads.length" class="bg-white border border-ivt-line rounded-2xl p-10 text-center shadow-sm shadow-ivt-ink/5">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-ivt-paper-2 text-primary">
                <InboxIcon class="h-6 w-6" />
            </span>
            <p class="mt-3 text-sm font-medium text-ivt-ink">Nicio cerere deschisă momentan</p>
            <p class="mt-1 text-sm text-ivt-ink-soft">Te anunțăm imediat ce apare o cerere nouă în categoriile tale.</p>
        </div>

        <div v-else class="grid gap-4 lg:h-[calc(100vh-8rem)] lg:grid-cols-[20rem_1fr] xl:grid-cols-[24rem_1fr]">
            <!-- Inbox list -->
            <section
                class="flex min-h-0 flex-col overflow-hidden rounded-2xl border border-ivt-line bg-white shadow-sm shadow-ivt-ink/5"
                :class="mobileDetail ? 'hidden lg:flex' : 'flex'"
            >
                <div class="space-y-2.5 border-b border-ivt-line p-3">
                    <div class="relative">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ivt-ink-soft/60" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Caută după nume, oraș, categorie…"
                            aria-label="Caută o cerere"
                            class="w-full rounded-full border-ivt-line bg-ivt-paper-2 py-2 pl-10 pr-9 text-sm text-ivt-ink placeholder:text-ivt-ink-soft/60 focus:border-primary focus:ring-primary/20 [&::-webkit-search-cancel-button]:appearance-none"
                        />
                        <button
                            v-if="search"
                            type="button"
                            @click="search = ''"
                            class="absolute right-2.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-ivt-ink-soft hover:bg-white hover:text-ivt-ink"
                            aria-label="Șterge căutarea"
                        >
                            <XMarkIcon class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="flex items-center gap-1 rounded-full bg-ivt-paper-2 p-1" role="tablist">
                        <button
                            v-for="filter in filters"
                            :key="filter.value"
                            type="button"
                            role="tab"
                            :aria-selected="activeFilter === filter.value"
                            @click="activeFilter = filter.value"
                            class="flex flex-1 items-center justify-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition-all duration-150"
                            :class="activeFilter === filter.value ? 'bg-white text-primary shadow-sm shadow-ivt-ink/10' : 'text-ivt-ink-soft hover:text-ivt-ink'"
                        >
                            {{ filter.label }}
                            <span class="tabular-nums" :class="activeFilter === filter.value ? 'text-primary/70' : 'text-ivt-ink-soft/60'">{{ counts[filter.value] }}</span>
                        </button>
                    </div>
                </div>

                <ul v-if="filteredLeads.length" class="min-h-0 flex-1 divide-y divide-ivt-line overflow-y-auto">
                    <li v-for="lead in filteredLeads" :key="lead.id">
                        <button
                            type="button"
                            @click="open(lead)"
                            class="flex w-full items-start gap-3 border-l-[3px] px-4 py-3.5 text-left transition-colors duration-150"
                            :class="current?.id === lead.id ? 'border-primary bg-primary/5' : 'border-transparent hover:bg-ivt-paper-2/60'"
                        >
                            <span class="relative flex h-11 w-11 flex-none items-center justify-center rounded-full bg-primary text-sm font-semibold text-white">
                                {{ initials(lead.name) }}
                                <span
                                    v-if="needsAttention(lead)"
                                    class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full ring-2 ring-white"
                                    :class="lead.thread?.unread ? 'bg-primary' : 'bg-ivt-gold-bright'"
                                ></span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-baseline justify-between gap-2">
                                    <span class="truncate text-sm text-ivt-ink" :class="needsAttention(lead) ? 'font-semibold' : 'font-medium'">{{ lead.name }}</span>
                                    <span class="flex-none text-[11px] text-ivt-ink-soft">{{ lead.thread?.last_at ?? lead.created_at }}</span>
                                </span>
                                <span class="mt-0.5 block truncate text-xs font-medium text-primary">{{ lead.category }}</span>
                                <span class="mt-1 line-clamp-2 text-xs leading-relaxed" :class="needsAttention(lead) ? 'text-ivt-ink' : 'text-ivt-ink-soft'">{{ lead.message }}</span>
                                <span class="mt-2 flex flex-wrap gap-1.5">
                                    <span
                                        v-for="status in statuses(lead)"
                                        :key="status.key"
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                        :class="status.class"
                                    >
                                        <component :is="status.icon" class="h-3 w-3" /> {{ status.label }}
                                    </span>
                                </span>
                            </span>
                        </button>
                    </li>
                </ul>
                <p v-else class="flex-1 px-4 py-10 text-center text-sm text-ivt-ink-soft">
                    {{ search.trim() ? `Nicio cerere pentru „${search.trim()}”.` : 'Nicio cerere în această categorie.' }}
                </p>
            </section>

            <!-- Open request -->
            <section
                class="min-h-0 overflow-y-auto rounded-2xl border border-ivt-line bg-white shadow-sm shadow-ivt-ink/5"
                :class="mobileDetail ? 'block' : 'hidden lg:block'"
            >
                <template v-if="current">
                    <div class="border-b border-ivt-line px-5 py-3 lg:hidden">
                        <button type="button" @click="backToList" class="inline-flex items-center gap-1.5 text-sm font-medium text-ivt-ink-soft hover:text-primary">
                            <ArrowLeftIcon class="h-4 w-4" /> Toate cererile
                        </button>
                    </div>

                    <div class="p-5 sm:p-8">
                        <!-- Sender -->
                        <div class="flex items-center gap-4">
                            <span class="flex h-14 w-14 flex-none items-center justify-center rounded-full bg-primary text-lg font-semibold text-white shadow-glow-primary">
                                {{ initials(current.name) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-serif text-xl text-ivt-ink">{{ current.name }}</p>
                                <p class="text-xs text-ivt-ink-soft">a cerut o ofertă · {{ current.created_at }}</p>
                            </div>
                            <span class="flex flex-none flex-wrap justify-end gap-1.5">
                                <span
                                    v-for="status in statuses(current)"
                                    :key="status.key"
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="status.class"
                                >
                                    <component :is="status.icon" class="h-3.5 w-3.5" /> {{ status.label }}
                                </span>
                            </span>
                        </div>

                        <span class="mt-5 inline-block rounded-full bg-ivt-paper-2 px-3 py-1 text-xs font-semibold text-primary">{{ current.category }}</span>

                        <!-- Message bubble -->
                        <div class="mt-3 rounded-2xl rounded-tl-md bg-ivt-paper-2 px-5 py-4">
                            <p class="whitespace-pre-line text-[15px] leading-relaxed text-ivt-ink">{{ current.message }}</p>
                        </div>

                        <!-- Event details -->
                        <dl v-if="details(current).length" class="mt-5 grid grid-cols-[repeat(auto-fit,minmax(9rem,1fr))] gap-3">
                            <div v-for="item in details(current)" :key="item.label" class="rounded-2xl border border-ivt-line px-4 py-3">
                                <dt class="flex items-center gap-1.5 text-xs text-ivt-ink-soft">
                                    <component :is="item.icon" class="h-4 w-4 text-primary" /> {{ item.label }}
                                </dt>
                                <dd class="mt-1 text-sm font-semibold text-ivt-ink">{{ item.value }}</dd>
                            </div>
                        </dl>

                        <!-- Contact -->
                        <div class="mt-8 rounded-2xl border border-ivt-line bg-ivt-paper-2/40 p-4 sm:p-5">
                            <p class="flex items-center gap-2 text-sm font-semibold text-ivt-ink">
                                <ChatBubbleLeftRightIcon class="h-4 w-4 text-primary" /> Contactează clientul
                            </p>
                            <p class="mt-1 text-xs text-ivt-ink-soft">Platforma nu intermediază rezervarea — contactează clientul direct.</p>

                            <div class="mt-4 flex items-center gap-1 rounded-full bg-ivt-paper-2 p-1" role="tablist">
                                <button
                                    v-for="item in tabs"
                                    :key="item.key"
                                    type="button"
                                    role="tab"
                                    :aria-selected="tab === item.key"
                                    @click="tab = item.key"
                                    class="flex flex-1 items-center justify-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-all duration-150"
                                    :class="tab === item.key ? 'bg-white text-primary shadow-sm shadow-ivt-ink/10' : 'text-ivt-ink-soft hover:text-ivt-ink'"
                                >
                                    <component :is="item.icon" class="h-4 w-4" /> {{ item.label }}
                                </button>
                            </div>

                            <!-- Call -->
                            <div v-if="tab === 'call'" class="mt-4 rounded-2xl border border-ivt-line bg-white p-5 text-center">
                                <template v-if="current.phone">
                                    <p class="text-xs text-ivt-ink-soft">Număr de telefon</p>
                                    <p class="mt-1 text-2xl font-semibold tabular-nums text-ivt-ink">{{ current.phone }}</p>
                                    <a
                                        :href="`tel:${current.phone}`"
                                        class="mt-4 inline-flex items-center gap-2 rounded-full bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/25 transition-all duration-200 hover:bg-primary-bright hover:shadow-glow-primary active:scale-[0.98]"
                                    >
                                        <PhoneIcon class="h-4 w-4" /> Sună acum
                                    </a>
                                </template>
                                <p v-else class="text-sm text-ivt-ink-soft">Clientul nu a lăsat un număr de telefon. Folosește Email sau Chat.</p>
                            </div>

                            <!-- Email -->
                            <div v-else-if="tab === 'email'" class="mt-4 rounded-2xl border border-ivt-line bg-white p-5 text-center">
                                <p class="text-xs text-ivt-ink-soft">Adresă de email</p>
                                <p class="mt-1 break-all text-lg font-semibold text-ivt-ink">{{ current.email }}</p>
                                <a
                                    :href="mailto(current)"
                                    class="mt-4 inline-flex items-center gap-2 rounded-full bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/25 transition-all duration-200 hover:bg-primary-bright hover:shadow-glow-primary active:scale-[0.98]"
                                >
                                    <EnvelopeIcon class="h-4 w-4" /> Trimite email
                                </a>
                            </div>

                            <!-- Chat -->
                            <div v-else-if="tab === 'chat'" class="mt-4">
                                <p v-if="!canChat" class="rounded-2xl border border-ivt-line bg-white p-5 text-center text-sm text-ivt-ink-soft">
                                    <template v-if="!current.has_account">Chatul nu e disponibil: cererea nu e legată de un cont de client (contul a fost șters sau cererea e veche). Folosește Apel sau Email.</template>
                                    <template v-else>Chatul are nevoie de un anunț publicat în categoria „{{ current.category }}”, de care să lege conversația. Publică unul și chatul se activează.</template>
                                </p>

                                <div v-else class="flex h-[30rem] flex-col overflow-hidden rounded-2xl border border-ivt-line bg-white">
                                    <div class="flex items-center gap-2 border-b border-ivt-line px-4 py-2.5 text-xs text-ivt-ink-soft">
                                        <template v-if="thread?.listing">
                                            Conversație despre „<span class="truncate font-medium text-ivt-ink">{{ thread.listing.title }}</span>”
                                        </template>
                                        <template v-else-if="current.message_listings.length > 1">
                                            <label for="chat-listing" class="flex-none">Din partea anunțului</label>
                                            <select id="chat-listing" v-model="chatListingId" class="min-w-0 flex-1 rounded-full border-ivt-line bg-ivt-paper-2 py-1 pl-3 pr-8 text-xs font-medium text-ivt-ink focus:border-primary focus:ring-primary/20">
                                                <option v-for="listing in current.message_listings" :key="listing.id" :value="listing.id">{{ listing.title }}</option>
                                            </select>
                                        </template>
                                        <template v-else>
                                            Din partea anunțului „<span class="truncate font-medium text-ivt-ink">{{ current.message_listings[0].title }}</span>”
                                        </template>
                                    </div>

                                    <div class="min-h-0 flex-1">
                                        <ChatThread
                                            v-if="thread"
                                            :messages="thread.messages"
                                            :post-url="thread.conversation_id ? route('provider.messages.store', thread.conversation_id) : route('provider.leads.message', current.id)"
                                            :extra="thread.conversation_id ? {} : { listing_id: chatListingId }"
                                            :reset-key="current.id"
                                            :conversation-id="thread.conversation_id"
                                            :counterpart-last-read-id="thread.counterpart_last_read_id"
                                            :quick-replies="quickReplies"
                                            :placeholder="`Scrie-i lui ${current.name.split(' ')[0]}…`"
                                            empty-text="Nicio conversație încă. Scrie primul mesaj — clientul îl primește în contul lui."
                                        />
                                        <div v-else class="flex h-full items-center justify-center text-sm text-ivt-ink-soft">Se încarcă conversația…</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Offer -->
                            <div v-else class="mt-4">
                                <p v-if="!current.offer_listings.length" class="rounded-2xl border border-ivt-line bg-white p-5 text-center text-sm text-ivt-ink-soft">
                                    Publică un anunț în categoria „{{ current.category }}” ca să poți trimite o ofertă.
                                </p>

                                <template v-else-if="current.offer && !current.offer.editable">
                                    <div class="rounded-2xl border border-ivt-line bg-white p-5">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="font-serif text-2xl text-ivt-ink">{{ current.offer.price.toLocaleString('ro-RO') }} lei</span>
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold"
                                                :class="offerStatusMeta[current.offer.status].class"
                                            >
                                                <component :is="offerStatusMeta[current.offer.status].icon" class="h-3.5 w-3.5" />
                                                {{ offerStatusMeta[current.offer.status].label }}
                                            </span>
                                        </div>
                                        <ul v-if="current.offer.includes.length" class="mt-3 space-y-1 text-sm text-ivt-ink-soft">
                                            <li v-for="line in current.offer.includes" :key="line" class="flex items-start gap-1.5">
                                                <CheckCircleIcon class="mt-0.5 h-4 w-4 flex-none text-primary" /> {{ line }}
                                            </li>
                                        </ul>
                                        <p v-if="current.offer.message" class="mt-3 whitespace-pre-line text-sm text-ivt-ink-soft">{{ current.offer.message }}</p>
                                        <p v-if="current.offer.status === 'accepted'" class="mt-4 rounded-xl bg-emerald-50 px-3.5 py-2.5 text-sm text-emerald-700">
                                            Clientul a acceptat oferta. Contactează-l cât mai repede.
                                        </p>
                                    </div>
                                </template>

                                <form v-else class="rounded-2xl border border-ivt-line bg-white p-5" @submit.prevent="submitOffer">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="offer-listing" class="mb-1.5 block text-sm font-medium text-ivt-ink">Anunț</label>
                                            <select
                                                id="offer-listing"
                                                v-model="offerForm.listing_id"
                                                class="w-full rounded-xl border-ivt-line text-sm text-ivt-ink focus:border-primary focus:ring-primary"
                                            >
                                                <option v-for="listing in current.offer_listings" :key="listing.id" :value="listing.id">{{ listing.title }}</option>
                                            </select>
                                            <p v-if="offerForm.errors.listing_id" class="mt-1 text-xs text-rose-600">{{ offerForm.errors.listing_id }}</p>
                                        </div>
                                        <div>
                                            <label for="offer-price" class="mb-1.5 block text-sm font-medium text-ivt-ink">Preț (lei)</label>
                                            <input
                                                id="offer-price"
                                                v-model.number="offerForm.price"
                                                type="number"
                                                min="1"
                                                class="w-full rounded-xl border-ivt-line text-sm text-ivt-ink focus:border-primary focus:ring-primary"
                                            />
                                            <p v-if="offerForm.errors.price" class="mt-1 text-xs text-rose-600">{{ offerForm.errors.price }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <label for="offer-valid" class="mb-1.5 block text-sm font-medium text-ivt-ink">Valabilă până la</label>
                                        <input
                                            id="offer-valid"
                                            v-model="offerForm.valid_until"
                                            type="date"
                                            :min="new Date().toISOString().slice(0, 10)"
                                            :max="current.event_date_iso || undefined"
                                            class="w-full rounded-xl border-ivt-line text-sm text-ivt-ink focus:border-primary focus:ring-primary sm:w-56"
                                        />
                                        <p v-if="offerForm.errors.valid_until" class="mt-1 text-xs text-rose-600">{{ offerForm.errors.valid_until }}</p>
                                    </div>

                                    <div class="mt-4">
                                        <label for="offer-includes" class="mb-1.5 block text-sm font-medium text-ivt-ink">Ce include <span class="font-normal text-ivt-ink-soft">(câte un rând)</span></label>
                                        <textarea
                                            id="offer-includes"
                                            v-model="offerForm.includesText"
                                            rows="3"
                                            placeholder="8 ore filmare&#10;Album foto&#10;Drona inclusă"
                                            class="w-full rounded-xl border-ivt-line text-sm text-ivt-ink placeholder:text-ivt-ink-soft/50 focus:border-primary focus:ring-primary"
                                        ></textarea>
                                    </div>

                                    <div class="mt-4">
                                        <label for="offer-message" class="mb-1.5 block text-sm font-medium text-ivt-ink">Mesaj pentru client <span class="font-normal text-ivt-ink-soft">(opțional)</span></label>
                                        <textarea
                                            id="offer-message"
                                            v-model="offerForm.message"
                                            rows="3"
                                            maxlength="1500"
                                            class="w-full rounded-xl border-ivt-line text-sm text-ivt-ink placeholder:text-ivt-ink-soft/50 focus:border-primary focus:ring-primary"
                                        ></textarea>
                                        <p v-if="offerForm.errors.message" class="mt-1 text-xs text-rose-600">{{ offerForm.errors.message }}</p>
                                    </div>

                                    <div class="mt-5 flex flex-wrap items-center gap-3">
                                        <button
                                            type="submit"
                                            :disabled="offerForm.processing"
                                            class="inline-flex items-center gap-2 rounded-full bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/25 transition-all duration-200 hover:bg-primary-bright disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <CurrencyDollarIcon class="h-4 w-4" /> {{ current.offer ? 'Actualizează oferta' : 'Trimite oferta' }}
                                        </button>
                                        <button
                                            v-if="current.offer && current.offer.editable"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-rose-600 hover:text-rose-700"
                                            @click="offerToWithdraw = current.offer"
                                        >
                                            <TrashIcon class="h-4 w-4" /> Retrage oferta
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <button
                                v-if="!current.contacted"
                                type="button"
                                :disabled="markingContacted === current.id"
                                @click="markContacted(current)"
                                class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-ivt-ink-soft transition-colors duration-150 hover:text-primary disabled:pointer-events-none disabled:opacity-50"
                            >
                                <svg v-if="markingContacted === current.id" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                </svg>
                                <CheckCircleIcon v-else class="h-4 w-4" />
                                Marchează ca și contactat
                            </button>
                        </div>
                    </div>
                </template>
                <div v-else class="flex h-full min-h-[16rem] flex-col items-center justify-center px-6 text-center">
                    <ChatBubbleLeftRightIcon class="h-10 w-10 text-primary/30" />
                    <p class="mt-3 text-sm text-ivt-ink-soft">Selectează o cerere din listă.</p>
                </div>
            </section>
        </div>

        <ConfirmDialog
            v-model:show="offerWithdrawDialogOpen"
            title="Retragi oferta?"
            message="Clientul nu o va mai vedea. Poți trimite oricând o ofertă nouă."
            confirm-label="Retrage oferta"
            :processing="withdrawingOffer"
            @confirm="withdrawOffer"
        />
    </ProviderLayout>
</template>
