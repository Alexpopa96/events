<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    PlusIcon,
    MapPinIcon,
    CalendarIcon,
    UsersIcon,
    MagnifyingGlassIcon,
    ChevronRightIcon,
    TagIcon,
} from '@heroicons/vue/24/outline';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';

const props = defineProps({
    quoteRequests: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({ total: 0, pending: 0, active: 0, withOffers: 0, closed: 0 }),
    },
});

const STATUS_META = {
    pending_review: { group: 'pending', label: 'În verificare', dot: 'bg-ivt-gold', badge: 'bg-ivt-gold-bright/15 text-ivt-gold' },
    open: { group: 'published', label: 'Activă', dot: 'bg-ivt-sage', badge: 'bg-ivt-sage/15 text-ivt-sage' },
    closed: { group: 'closed', label: 'Închisă', dot: 'bg-ivt-ink-faint', badge: 'bg-ivt-paper-3 text-ivt-ink-faint' },
    rejected: { group: 'closed', label: 'Închisă', dot: 'bg-ivt-ink-faint', badge: 'bg-ivt-paper-3 text-ivt-ink-faint' },
};
const statusMeta = (status) => STATUS_META[status] ?? STATUS_META.closed;

const tabs = computed(() => [
    { key: 'all', label: 'Toate', count: props.stats.total },
    { key: 'pending', label: 'În așteptare', count: props.stats.pending },
    { key: 'published', label: 'Publicate', count: props.stats.active },
    { key: 'closed', label: 'Închise', count: props.stats.closed },
]);

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

const setTab = (key) => { activeTab.value = key; };

const codeFor = (id) => `INV-${String(id).padStart(5, '0')}`;
</script>

<template>
    <Head title="Cererile mele" />

    <div class="bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <!-- Page header -->
            <section class="relative overflow-hidden border-b border-ivt-line bg-ivt-paper-2 py-12">
                <div
                    class="pointer-events-none absolute inset-x-[-10%] -top-[40%] h-[150%]"
                    style="background: radial-gradient(50% 60% at 15% 0%, rgba(168,127,46,0.10), transparent 60%), radial-gradient(45% 45% at 95% 10%, rgba(124,46,59,0.08), transparent 60%);"
                />
                <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
                    <nav class="mb-5 flex flex-wrap items-center gap-1.5 text-[13px] text-ivt-ink-faint">
                        <Link href="/" class="text-ivt-ink-soft transition-colors hover:text-ivt-wine">Acasă</Link>
                        <ChevronRightIcon class="h-3.5 w-3.5" />
                        <Link :href="route('profile.show')" class="text-ivt-ink-soft transition-colors hover:text-ivt-wine">Contul meu</Link>
                        <ChevronRightIcon class="h-3.5 w-3.5" />
                        <span>Cererile mele</span>
                    </nav>

                    <div class="flex flex-wrap items-end justify-between gap-5">
                        <div>
                            <p class="flex items-center gap-2.5 text-xs font-bold uppercase tracking-[0.18em] text-ivt-wine">
                                <span class="inline-block h-px w-[22px] bg-ivt-gold" /> Contul meu
                            </p>
                            <h1 class="mt-2.5 font-serif text-[32px] leading-tight text-ivt-ink sm:text-[36px]">Cererile mele</h1>
                            <p class="mt-2 max-w-md text-[14.5px] text-ivt-ink-soft">Toate cererile de ofertă publicate de tine, statusul lor și ofertele primite de la furnizori.</p>
                        </div>
                        <Link
                            :href="route('quote-requests.create')"
                            class="inline-flex items-center gap-2 rounded-full bg-gradient-to-b from-ivt-wine-bright to-ivt-wine px-6 py-3 text-sm font-semibold text-ivt-paper transition-transform hover:-translate-y-0.5"
                        >
                            <PlusIcon class="h-4 w-4" /> Publică o cerere nouă
                        </Link>
                    </div>
                </div>
            </section>

            <section class="py-10 sm:py-12">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">

            <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-4">
                <div class="rounded-2xl bg-gradient-to-br from-ivt-ink-2 to-ivt-ink p-5">
                    <b class="block font-serif text-[28px] font-semibold text-ivt-on-dark">{{ stats.total }}</b>
                    <span class="text-[12.5px] text-ivt-on-dark-dim">cereri publicate</span>
                </div>
                <div class="rounded-2xl bg-ivt-paper-2 p-5">
                    <b class="block font-serif text-[28px] font-semibold text-ivt-ink">{{ stats.active }}</b>
                    <span class="text-[12.5px] text-ivt-ink-faint">active acum</span>
                </div>
                <div class="rounded-2xl bg-ivt-paper-2 p-5">
                    <b class="block font-serif text-[28px] font-semibold text-ivt-ink">{{ stats.pending }}</b>
                    <span class="text-[12.5px] text-ivt-ink-faint">în așteptare aprobare</span>
                </div>
                <div class="rounded-2xl bg-ivt-paper-2 p-5">
                    <b class="block font-serif text-[28px] font-semibold text-ivt-ink">{{ stats.withOffers }}</b>
                    <span class="text-[12.5px] text-ivt-ink-faint">oferte primite în total</span>
                </div>
            </div>

            <div class="mb-7 flex flex-wrap items-center justify-between gap-4 border-b border-ivt-line pb-5">
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        @click="setTab(tab.key)"
                        class="flex items-center gap-1.5 rounded-full px-4 py-2 text-[13.5px] font-semibold transition-all"
                        :class="activeTab === tab.key ? 'bg-ivt-ink text-ivt-paper' : 'text-ivt-ink-soft hover:bg-ivt-paper-2'"
                    >
                        {{ tab.label }}
                        <span
                            class="rounded-full px-2 py-0.5 text-[11px]"
                            :class="activeTab === tab.key ? 'bg-white/20 text-ivt-paper' : 'bg-ivt-paper-3 text-ivt-ink-soft'"
                        >{{ tab.count }}</span>
                    </button>
                </div>
                <div class="relative min-w-[220px] flex-1 sm:w-64 sm:flex-none">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ivt-ink-faint" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Caută în cererile tale…"
                        class="w-full rounded-full border border-ivt-line bg-white py-2.5 pl-10 pr-4 text-[13.5px] text-ivt-ink placeholder:text-ivt-ink-faint focus:border-ivt-gold focus:outline-none"
                    />
                </div>
            </div>

            <div v-if="filtered.length" class="flex flex-col gap-3.5 pb-16">
                <div
                    v-for="qr in filtered"
                    :key="qr.id"
                    class="flex overflow-hidden rounded-2xl border border-ivt-line bg-white transition-all duration-200 hover:-translate-y-0.5 hover:shadow-ivt-soft"
                >
                    <div class="w-1.5 flex-none" :class="statusMeta(qr.status).dot" />
                    <div class="m-5 flex h-[52px] w-[52px] flex-none items-center justify-center rounded-2xl bg-ivt-paper-2">
                        <TagIcon class="h-6 w-6 text-ivt-wine" />
                    </div>
                    <div class="flex flex-1 flex-wrap items-center justify-between gap-4 py-4 pr-5">
                        <div class="min-w-0">
                            <h3 class="text-[16.5px] font-semibold text-ivt-ink">{{ qr.title }}</h3>
                            <div class="mt-1.5 flex flex-wrap items-center gap-x-3.5 gap-y-1 text-[12.5px] text-ivt-ink-faint">
                                <span class="flex items-center gap-1"><TagIcon class="h-3.5 w-3.5" /> {{ qr.category }}</span>
                                <span v-if="qr.city || qr.county" class="flex items-center gap-1"><MapPinIcon class="h-3.5 w-3.5" /> {{ [qr.city, qr.county].filter(Boolean).join(', ') }}</span>
                                <span v-if="qr.event_date" class="flex items-center gap-1"><CalendarIcon class="h-3.5 w-3.5" /> {{ qr.event_date }}</span>
                                <span v-if="qr.guest_count" class="flex items-center gap-1"><UsersIcon class="h-3.5 w-3.5" /> {{ qr.guest_count }} persoane</span>
                            </div>
                            <p class="mt-1 text-[12px] text-ivt-ink-faint">Trimisă {{ qr.created_at_human }} · Cod {{ codeFor(qr.id) }}</p>
                        </div>
                        <div class="flex items-center gap-6">
                            <span class="rounded-full px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wide" :class="statusMeta(qr.status).badge">{{ statusMeta(qr.status).label }}</span>
                            <div class="text-center">
                                <b class="block font-serif text-xl font-semibold text-ivt-ink">{{ qr.status === 'pending_review' ? '—' : qr.offers_count }}</b>
                                <span class="text-[11px] text-ivt-ink-faint">oferte</span>
                            </div>
                            <Link
                                :href="route('quote-requests.show', qr.id)"
                                class="whitespace-nowrap rounded-full border border-ivt-line px-4 py-2 text-[12.5px] font-semibold text-ivt-ink-soft transition-colors hover:border-ivt-gold hover:text-ivt-wine"
                            >
                                Detalii
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-2xl border border-ivt-line bg-white px-6 py-20 text-center">
                <p class="text-[34px]">🗂️</p>
                <p class="mt-3 font-serif text-[22px] italic text-ivt-ink">
                    {{ quoteRequests.length ? 'Nicio cerere găsită' : 'Nu ai trimis încă nicio cerere de ofertă' }}
                </p>
                <p class="mt-2 text-[14px] text-ivt-ink-faint">
                    {{ quoteRequests.length ? 'Încearcă alt termen de căutare sau alege un alt filtru de status.' : 'Spune-ne ce cauți și primești oferte direct de la furnizori.' }}
                </p>
                <Link
                    :href="route('quote-requests.create')"
                    class="mt-6 inline-flex items-center gap-2 rounded-full bg-gradient-to-b from-ivt-wine-bright to-ivt-wine px-6 py-3 text-sm font-semibold text-ivt-paper transition-transform hover:-translate-y-0.5"
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
