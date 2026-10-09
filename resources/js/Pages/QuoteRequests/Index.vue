<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    PlusIcon,
    MapPinIcon,
    CalendarIcon,
    UsersIcon,
    MagnifyingGlassIcon,
    ArrowRightIcon,
    BanknotesIcon,
    ClockIcon,
    XMarkIcon,
    ChevronUpDownIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline';
import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from '@headlessui/vue';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { categoryIcon } from '@/Composables/useCategoryIcon';

const props = defineProps({
    quoteRequests: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({ total: 0, pending: 0, active: 0, withOffers: 0, closed: 0 }),
    },
});

const STATUS_META = {
    pending_review: { group: 'pending', label: 'În verificare', dot: 'bg-ivt-violet', badge: 'bg-ivt-accent-bright/15 text-ivt-violet' },
    open: { group: 'published', label: 'Activă', dot: 'bg-ivt-teal', badge: 'bg-ivt-teal/15 text-ivt-teal' },
    closed: { group: 'closed', label: 'Închisă', dot: 'bg-ivt-ink-faint', badge: 'bg-ivt-paper-3 text-ivt-ink-faint' },
    rejected: { group: 'closed', label: 'Închisă', dot: 'bg-ivt-ink-faint', badge: 'bg-ivt-paper-3 text-ivt-ink-faint' },
};
const statusMeta = (status) => STATUS_META[status] ?? STATUS_META.closed;

const tabs = computed(() => [
    { key: 'all', label: 'Toate', count: props.stats.total, dot: 'bg-gradient-to-br from-primary to-ivt-violet' },
    { key: 'pending', label: 'În așteptare', count: props.stats.pending, dot: 'bg-ivt-violet' },
    { key: 'published', label: 'Publicate', count: props.stats.active, dot: 'bg-ivt-teal' },
    { key: 'closed', label: 'Închise', count: props.stats.closed, dot: 'bg-ivt-ink-faint' },
]);

const currentTab = computed(() => tabs.value.find((tab) => tab.key === activeTab.value) ?? tabs.value[0]);

const activeTab = ref('all');
const search = ref('');

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    return props.quoteRequests.filter((qr) => {
        const matchesTab = activeTab.value === 'all' || statusMeta(qr.status).group === activeTab.value;
        if (!matchesTab) return false;
        if (!term) return true;
        return [qr.title, qr.category, qr.city, qr.county]
            .filter(Boolean)
            .some((field) => field.toLowerCase().includes(term));
    });
});


const codeFor = (id) => `INV-${String(id).padStart(5, '0')}`;
</script>

<template>
    <Head title="Cererile mele" />

    <div class="overflow-x-clip bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <!-- PAGE HERO -->
            <section class="relative z-30 pt-8 lg:pt-10">
                <div class="pointer-events-none absolute inset-0 overflow-hidden [mask-image:linear-gradient(to_bottom,#000_55%,transparent)]">
                    <div class="absolute -left-40 -top-40 h-[520px] w-[520px] rounded-full bg-primary/15 blur-3xl animate-float-slow" />
                    <div class="absolute -right-32 top-10 h-[460px] w-[460px] rounded-full bg-ivt-violet/15 blur-3xl animate-float-slower" />
                    <div class="absolute bottom-0 left-1/3 h-[320px] w-[320px] rounded-full bg-ivt-accent-bright/15 blur-3xl animate-float-slower" />
                    <div
                        class="absolute inset-0 opacity-[0.35]"
                        style="background-image: radial-gradient(rgba(26,20,51,0.12) 1px, transparent 1px); background-size: 22px 22px; mask-image: radial-gradient(ellipse 70% 60% at 30% 30%, #000 30%, transparent 75%);"
                    />
                </div>

                <div class="relative mx-auto max-w-[1600px] px-6 pb-6 lg:px-8 lg:pb-8">
                    <nav class="flex items-center gap-2 text-[13px] text-ivt-ink-faint" aria-label="Breadcrumb">
                        <Link href="/" class="transition-colors hover:text-primary">Acasă</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <Link :href="route('profile.show')" class="transition-colors hover:text-primary">Contul meu</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <span class="text-ivt-ink-soft">Cererile mele</span>
                    </nav>

                    <div class="mt-8 lg:mt-10">
                        <p class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 py-1 pl-1.5 pr-3.5 text-[12.5px] font-semibold text-ivt-ink-soft shadow-sm backdrop-blur">
                            <span class="rounded-full bg-brand px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider text-white">{{ stats.active }}</span>
                            {{ stats.active === 1 ? 'cerere activă' : 'cereri active' }}, {{ stats.withOffers }} oferte primite
                        </p>

                        <h1 class="mt-6 text-balance font-display text-[34px] font-bold leading-[1.05] tracking-tight text-ivt-ink sm:text-[44px] lg:text-[52px]">
                            Cererile tale de ofertă,
                            <span class="relative whitespace-nowrap">
                                <span class="text-gradient">într-un singur loc.</span>
                                <svg class="absolute -bottom-2 left-0 h-3 w-full text-ivt-accent-bright" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true">
                                    <path d="M2 9 C 80 2, 200 2, 298 7" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                </svg>
                            </span>
                        </h1>

                        <p class="mt-6 max-w-[480px] text-[15.5px] leading-relaxed text-ivt-ink-soft">
                            Urmărește statusul fiecărei cereri publicate și ofertele primite de la furnizori.
                        </p>

                        <div class="ring-gradient relative z-20 mt-9 grid max-w-[620px] gap-1.5 rounded-[22px] border border-ivt-line bg-white p-2 shadow-ivt-soft transition-shadow focus-within:shadow-ivt-deep hover:shadow-ivt-deep sm:grid-cols-[1fr_auto]">
                            <label class="group flex h-full cursor-text items-center gap-3 rounded-2xl px-4 py-2.5 transition-colors hover:bg-ivt-paper-2">
                                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-ivt-paper-2 text-ivt-violet transition-colors group-focus-within:bg-brand group-focus-within:text-white group-hover:bg-white">
                                    <MagnifyingGlassIcon class="h-[18px] w-[18px]" />
                                </span>
                                <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                                    <span class="text-[10.5px] font-bold uppercase tracking-[0.1em] text-ivt-ink-faint">Caută</span>
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="titlu, categorie sau oraș…"
                                        autocomplete="off"
                                        class="w-full truncate border-0 bg-transparent p-0 text-[14.5px] font-semibold text-ivt-ink placeholder:font-semibold placeholder:text-ivt-ink-faint focus:outline-none focus:ring-0"
                                    />
                                </span>
                                <button
                                    v-if="search"
                                    type="button"
                                    class="flex h-6 w-6 flex-none items-center justify-center rounded-full text-ivt-ink-faint transition-colors hover:bg-ivt-paper-3 hover:text-ivt-ink"
                                    aria-label="Șterge căutarea"
                                    @click.prevent="search = ''"
                                >
                                    <XMarkIcon class="h-3.5 w-3.5" />
                                </button>
                            </label>
                            <Listbox v-model="activeTab" as="div" class="relative sm:w-56 sm:border-l sm:border-ivt-line sm:pl-1.5">
                                <ListboxButton
                                    v-slot="{ open }"
                                    class="group flex h-full w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-left transition-colors hover:bg-ivt-paper-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                                >
                                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-ivt-paper-2 transition-colors group-hover:bg-white">
                                        <span class="h-2.5 w-2.5 rounded-full" :class="currentTab.dot" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[10.5px] font-bold uppercase tracking-[0.1em] text-ivt-ink-faint">Status</span>
                                        <span class="block truncate text-[14.5px] font-semibold text-ivt-ink">{{ currentTab.label }}</span>
                                    </span>
                                    <span class="rounded-full bg-ivt-ink px-2 py-0.5 text-[11px] font-bold tabular-nums text-ivt-paper">{{ currentTab.count }}</span>
                                    <ChevronUpDownIcon class="h-5 w-5 flex-none text-ivt-ink-soft transition-colors group-hover:text-primary" :class="{ 'text-primary': open }" />
                                </ListboxButton>

                                <transition
                                    enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 -translate-y-1 scale-[0.98]"
                                    enter-to-class="opacity-100 translate-y-0 scale-100"
                                    leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0"
                                >
                                    <ListboxOptions class="absolute right-0 z-30 mt-2 w-full sm:w-64 origin-top overflow-hidden rounded-2xl border border-ivt-line bg-white p-1.5 shadow-[0_20px_45px_-15px_rgba(26,20,51,0.35)] focus:outline-none">
                                        <ListboxOption
                                            v-for="tab in tabs"
                                            :key="tab.key"
                                            :value="tab.key"
                                            v-slot="{ active, selected }"
                                            as="template"
                                        >
                                            <li
                                                class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-[13.5px] transition-colors"
                                                :class="active ? 'bg-ivt-paper-2 text-ivt-ink' : 'text-ivt-ink-soft'"
                                            >
                                                <span class="h-2 w-2 flex-none rounded-full" :class="tab.dot" />
                                                <span class="flex-1 truncate" :class="selected ? 'font-semibold text-ivt-ink' : 'font-medium'">{{ tab.label }}</span>
                                                <span
                                                    class="min-w-[1.5rem] rounded-full px-1.5 py-0.5 text-center text-[11px] font-bold tabular-nums"
                                                    :class="selected ? 'bg-ivt-ink text-ivt-paper' : 'bg-ivt-paper-3 text-ivt-ink-faint'"
                                                >{{ tab.count }}</span>
                                                <CheckIcon class="h-4 w-4 flex-none text-primary" :class="selected ? 'opacity-100' : 'opacity-0'" stroke-width="2.5" />
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                        </div>
                    </div>
                </div>

            </section>

            <section id="cereri" class="scroll-mt-24 pb-10 pt-2 sm:pb-12">
                <div class="mx-auto max-w-[1600px] px-6 lg:px-8">

            <div v-if="filtered.length" class="grid grid-cols-1 gap-5 pb-16 md:grid-cols-2 2xl:grid-cols-3">
                <Link
                    v-for="qr in filtered"
                    :key="qr.id"
                    :href="route('quote-requests.show', qr.id)"
                    class="group relative flex flex-col overflow-hidden rounded-[22px] border-2 border-ivt-line bg-white p-5 transition-all duration-300 hover:-translate-y-1 hover:border-primary/30 hover:shadow-glow-primary sm:p-6"
                >
                    <span
                        class="pointer-events-none absolute -right-16 -top-20 h-44 w-44 rounded-full opacity-0 transition-opacity duration-500 group-hover:opacity-100"
                        style="background: radial-gradient(circle, rgba(124,58,237,0.14), transparent 70%);"
                    />

                    <!-- Top: icon + status -->
                    <div class="relative flex items-start justify-between gap-3">
                        <span class="flex h-12 w-12 flex-none items-center justify-center rounded-2xl bg-ivt-paper-2 text-primary transition-all duration-300 group-hover:rotate-[-6deg] group-hover:scale-110 group-hover:bg-brand group-hover:text-white">
                            <component :is="categoryIcon(qr.category_slug)" class="h-6 w-6" stroke-width="1.5" />
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[10.5px] font-bold uppercase tracking-[0.08em]" :class="statusMeta(qr.status).badge">
                            <span class="relative flex h-1.5 w-1.5">
                                <span v-if="qr.status === 'open'" class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-60" :class="statusMeta(qr.status).dot" />
                                <span class="relative h-1.5 w-1.5 rounded-full" :class="statusMeta(qr.status).dot" />
                            </span>
                            {{ statusMeta(qr.status).label }}
                        </span>
                    </div>

                    <!-- Title + category -->
                    <div class="relative mt-4">
                        <p class="flex flex-wrap items-center gap-2 text-[11px] font-bold uppercase tracking-[0.1em] text-ivt-ink-faint">
                            {{ qr.category }}
                            <span v-if="qr.package_size > 1" class="rounded-full bg-ivt-violet/15 px-2 py-0.5 text-[10px] tracking-[0.06em] text-ivt-violet">Pachet · {{ qr.package_size }} servicii</span>
                        </p>
                        <h3 class="mt-1.5 line-clamp-2 font-display text-[19px] font-medium leading-snug text-ivt-ink transition-colors group-hover:text-primary">{{ qr.title }}</h3>
                        <p v-if="qr.message" class="mt-1.5 line-clamp-2 text-[13px] leading-relaxed text-ivt-ink-soft">{{ qr.message }}</p>
                    </div>

                    <!-- Details -->
                    <div class="relative mt-4 flex flex-wrap gap-1.5">
                        <span v-if="qr.event_date" class="inline-flex items-center gap-1.5 rounded-full bg-ivt-paper-2 px-2.5 py-1 text-[12px] font-medium text-ivt-ink-soft">
                            <CalendarIcon class="h-3.5 w-3.5 text-primary" /> {{ qr.event_date }}
                        </span>
                        <span v-if="qr.city || qr.county" class="inline-flex items-center gap-1.5 rounded-full bg-ivt-paper-2 px-2.5 py-1 text-[12px] font-medium text-ivt-ink-soft">
                            <MapPinIcon class="h-3.5 w-3.5 text-primary" /> {{ [qr.city, qr.county].filter(Boolean).join(', ') }}
                        </span>
                        <span v-if="qr.guest_count" class="inline-flex items-center gap-1.5 rounded-full bg-ivt-paper-2 px-2.5 py-1 text-[12px] font-medium text-ivt-ink-soft">
                            <UsersIcon class="h-3.5 w-3.5 text-primary" /> {{ qr.guest_count }} persoane
                        </span>
                        <span v-if="qr.budget_range" class="inline-flex items-center gap-1.5 rounded-full bg-ivt-paper-2 px-2.5 py-1 text-[12px] font-medium text-ivt-ink-soft">
                            <BanknotesIcon class="h-3.5 w-3.5 text-primary" /> {{ qr.budget_range }}
                        </span>
                    </div>

                    <!-- Footer: offers + CTA -->
                    <div class="relative mt-auto pt-5">
                        <div class="flex items-center justify-between gap-4 border-t border-ivt-line pt-4">
                            <div v-if="qr.status === 'pending_review'" class="flex items-center gap-2.5">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-ivt-accent-bright/15 text-ivt-violet">
                                    <ClockIcon class="h-5 w-5" />
                                </span>
                                <span class="text-[12.5px] leading-tight text-ivt-ink-faint">Ofertele apar<br />după aprobare</span>
                            </div>
                            <div v-else class="flex items-center gap-2.5">
                                <span
                                    class="flex h-10 min-w-10 items-center justify-center rounded-xl px-2 font-display text-[18px] font-semibold tabular-nums"
                                    :class="qr.offers_count ? 'bg-ivt-ink text-ivt-accent-bright' : 'bg-ivt-paper-2 text-ivt-ink-faint'"
                                >{{ qr.offers_count }}</span>
                                <span class="text-[12.5px] leading-tight text-ivt-ink-faint">
                                    {{ qr.offers_count === 1 ? 'ofertă' : 'oferte' }}<br />primite
                                </span>
                            </div>

                            <span class="inline-flex items-center gap-1.5 rounded-full bg-ivt-paper-2 py-2 pl-4 pr-3 text-[12.5px] font-semibold text-ivt-ink transition-all duration-300 group-hover:bg-ivt-ink group-hover:text-ivt-paper">
                                Detalii
                                <ArrowRightIcon class="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-0.5" stroke-width="2.5" />
                            </span>
                        </div>
                        <p class="mt-3 flex items-center justify-between text-[11.5px] text-ivt-ink-faint">
                            <span>Trimisă {{ qr.created_at_human }}</span>
                            <span class="font-mono tracking-tight">{{ codeFor(qr.id) }}</span>
                        </p>
                    </div>
                </Link>
            </div>

            <div v-else class="rounded-2xl border border-ivt-line bg-white px-6 py-12 text-center">
                <p class="text-[34px]">🗂️</p>
                <p class="mt-3 font-display text-[22px] text-ivt-ink">
                    {{ quoteRequests.length ? 'Nicio cerere găsită' : 'Nu ai trimis încă nicio cerere de ofertă' }}
                </p>
                <p class="mt-2 text-[14px] text-ivt-ink-faint">
                    {{ quoteRequests.length ? 'Încearcă alt termen de căutare sau alege un alt filtru de status.' : 'Spune-ne ce cauți și primești oferte direct de la furnizori.' }}
                </p>
                <Link
                    :href="route('quote-requests.create')"
                    class="mt-6 inline-flex items-center gap-2 rounded-full bg-gradient-to-b from-primary-bright to-primary px-6 py-3 text-sm font-semibold text-ivt-paper transition-transform hover:-translate-y-0.5"
                >
                    <PlusIcon class="h-4 w-4" /> Publică o cerere nouă
                </Link>
            </div>

                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
