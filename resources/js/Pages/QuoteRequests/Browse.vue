<script setup>
import { computed, reactive, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRightIcon,
    CalendarDaysIcon,
    MapPinIcon,
    UsersIcon,
    BanknotesIcon,
    PencilSquareIcon,
    InboxArrowDownIcon,
    ChatBubbleLeftRightIcon,
    InboxIcon,
} from '@heroicons/vue/24/outline';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import SectionHeading from '@/Components/Home/SectionHeading.vue';

const props = defineProps({
    quoteRequests: { type: Object, required: true },
    filters: { type: Object, default: () => ({ category: '', county_id: '' }) },
    categories: { type: Array, default: () => [] },
    counties: { type: Array, default: () => [] },
    openCount: { type: Number, default: 0 },
});

const page = usePage();
const isProvider = computed(() => !!page.props.auth.isProvider);

const postQuoteHref = computed(() => (page.props.auth.can?.submitQuoteRequest ? route('quote-requests.create') : '/register/client'));

const form = reactive({ category: props.filters.category, county_id: props.filters.county_id });

watch(form, () => {
    router.get(route('quote-requests.browse'), {
        category: form.category || undefined,
        county_id: form.county_id || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true, only: ['quoteRequests', 'filters'] });
});

const hasFilters = computed(() => !!(form.category || form.county_id));

const resetFilters = () => {
    form.category = '';
    form.county_id = '';
};

const steps = [
    { icon: PencilSquareIcon, title: 'Descrii evenimentul', text: 'Tipul evenimentului, data, locația, numărul de invitați și bugetul.' },
    { icon: InboxArrowDownIcon, title: 'Furnizorii o primesc', text: 'Cererea ajunge la furnizorii din categoria aleasă, după o scurtă verificare.' },
    { icon: ChatBubbleLeftRightIcon, title: 'Primești oferte', text: 'Compari ofertele și discuți direct cu cine îți place.' },
];

const meta = (quoteRequest) => [
    { icon: CalendarDaysIcon, value: quoteRequest.event_date },
    { icon: MapPinIcon, value: quoteRequest.location },
    { icon: UsersIcon, value: quoteRequest.guest_count ? `${quoteRequest.guest_count} invitați` : null },
    { icon: BanknotesIcon, value: quoteRequest.budget_range },
].filter((item) => item.value);
</script>

<template>
    <Head title="Cereri de ofertă — Invita" />

    <div class="overflow-x-clip bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <!-- PAGE HERO -->
            <section class="relative pb-8 pt-8 lg:pb-12 lg:pt-10">
                <div class="pointer-events-none absolute inset-0 overflow-hidden [mask-image:linear-gradient(to_bottom,#000_55%,transparent)]">
                    <div class="absolute -left-40 -top-40 h-[520px] w-[520px] rounded-full bg-primary/15 blur-3xl animate-float-slow" />
                    <div class="absolute -right-32 top-10 h-[460px] w-[460px] rounded-full bg-ivt-violet/15 blur-3xl animate-float-slower" />
                    <div
                        class="absolute inset-0 opacity-[0.35]"
                        style="background-image: radial-gradient(rgba(26,20,51,0.12) 1px, transparent 1px); background-size: 22px 22px; mask-image: radial-gradient(ellipse 70% 60% at 30% 30%, #000 30%, transparent 75%);"
                    />
                </div>

                <div class="relative mx-auto max-w-[1600px] px-6 lg:px-8">
                    <nav class="flex items-center gap-2 text-[13px] text-ivt-ink-faint" aria-label="Breadcrumb">
                        <Link :href="route('home')" class="transition-colors hover:text-primary">Acasă</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <span class="text-ivt-ink-soft">Cereri de ofertă</span>
                    </nav>

                    <div class="mt-8 max-w-3xl lg:mt-10">
                        <p class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 py-1 pl-1.5 pr-3.5 text-[12.5px] font-semibold text-ivt-ink-soft shadow-sm backdrop-blur">
                            <span class="rounded-full bg-brand px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider text-white">{{ openCount.toLocaleString('ro-RO') }}</span>
                            cereri deschise acum
                        </p>

                        <h1 class="mt-6 text-balance font-display text-[34px] font-bold leading-[1.05] tracking-tight text-ivt-ink sm:text-[44px] lg:text-[52px]">
                            Nu găsești exact ce cauți?
                            <span class="text-gradient">Spune tu ce ai nevoie.</span>
                        </h1>

                        <p class="mt-6 max-w-[520px] text-[15.5px] leading-relaxed text-ivt-ink-soft">
                            Publici o cerere în două minute, iar furnizorii potriviți din zona ta te contactează direct — tu alegi cu cine discuți mai departe.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3">
                            <Link v-if="isProvider" :href="route('provider.leads.index')" class="btn-brand px-6 py-3 text-sm">
                                Vezi cererile pentru tine <ArrowRightIcon class="h-4 w-4" />
                            </Link>
                            <Link v-else :href="postQuoteHref" class="btn-brand px-6 py-3 text-sm">
                                Publică o cerere <ArrowRightIcon class="h-4 w-4" />
                            </Link>
                            <a href="#cereri" class="rounded-full border border-ivt-line bg-white px-6 py-3 text-sm font-semibold text-ivt-ink transition-colors hover:border-ivt-ink">
                                Vezi cererile recente
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- HOW IT WORKS -->
            <section class="pb-16 pt-4 lg:pb-20">
                <div class="mx-auto grid max-w-[1600px] gap-5 px-6 md:grid-cols-3 lg:px-8">
                    <div
                        v-for="(step, index) in steps"
                        :key="step.title"
                        v-reveal="index * 100"
                        class="rounded-[22px] border border-ivt-line bg-white p-7"
                    >
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <component :is="step.icon" class="h-5 w-5" />
                        </span>
                        <p class="mt-5 text-[11px] font-bold uppercase tracking-[0.14em] text-ivt-ink-faint">Pasul {{ index + 1 }}</p>
                        <h3 class="mt-1.5 font-display text-lg font-medium text-ivt-ink">{{ step.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ivt-ink-soft">{{ step.text }}</p>
                    </div>
                </div>
            </section>

            <!-- LIST -->
            <section id="cereri" class="scroll-mt-24 bg-ivt-paper-2 py-16 lg:py-24">
                <div class="mx-auto max-w-[1600px] px-6 lg:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <SectionHeading eyebrow="Cereri recente" title="Ce caută clienții acum." />

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <select v-model="form.category" class="rounded-xl border-ivt-line bg-white text-sm text-ivt-ink focus:border-ivt-violet focus:ring-ivt-violet" aria-label="Categorie">
                                <option value="">Toate categoriile</option>
                                <option v-for="category in categories" :key="category.slug" :value="category.slug">{{ category.name }}</option>
                            </select>
                            <select v-model="form.county_id" class="rounded-xl border-ivt-line bg-white text-sm text-ivt-ink focus:border-ivt-violet focus:ring-ivt-violet" aria-label="Județ">
                                <option value="">Toate județele</option>
                                <option v-for="county in counties" :key="county.id" :value="county.id">{{ county.name }}</option>
                            </select>
                            <button v-if="hasFilters" type="button" class="text-sm font-semibold text-primary hover:text-primary-bright" @click="resetFilters">
                                Resetează
                            </button>
                        </div>
                    </div>

                    <div v-if="quoteRequests.data.length" class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <article
                            v-for="quoteRequest in quoteRequests.data"
                            :key="quoteRequest.id"
                            class="flex flex-col rounded-[22px] border border-ivt-line bg-white p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-ivt-soft"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <span v-if="quoteRequest.category" class="rounded-full bg-primary/10 px-3 py-1 text-[11.5px] font-semibold text-primary">
                                    {{ quoteRequest.category }}
                                </span>
                                <span class="ml-auto whitespace-nowrap text-xs text-ivt-ink-faint">{{ quoteRequest.created_at_human }}</span>
                            </div>
                            <h3 class="mt-4 font-display text-lg font-medium text-ivt-ink">{{ quoteRequest.title }}</h3>
                            <p v-if="quoteRequest.event_type" class="mt-0.5 text-[13px] text-ivt-ink-soft">{{ quoteRequest.event_type }}</p>
                            <p class="mt-3 flex-1 text-[14px] italic leading-relaxed text-ivt-ink-soft line-clamp-3">&ldquo;{{ quoteRequest.message }}&rdquo;</p>
                            <ul v-if="meta(quoteRequest).length" class="mt-5 flex flex-wrap gap-x-4 gap-y-2 border-t border-ivt-line pt-4 text-[12.5px] text-ivt-ink-soft">
                                <li v-for="item in meta(quoteRequest)" :key="item.value" class="flex items-center gap-1.5">
                                    <component :is="item.icon" class="h-4 w-4 text-ivt-ink-faint" />
                                    {{ item.value }}
                                </li>
                            </ul>
                        </article>
                    </div>

                    <div v-else class="mt-10 flex flex-col items-center justify-center rounded-2xl border border-ivt-line bg-white px-5 py-16 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-ivt-paper-2 text-primary">
                            <InboxIcon class="h-6 w-6" />
                        </span>
                        <p class="mt-3 font-display text-[20px] text-ivt-ink">{{ hasFilters ? 'Nicio cerere pentru filtrele alese' : 'Încă nu există cereri deschise' }}</p>
                        <p class="mt-1.5 max-w-sm text-sm text-ivt-ink-soft">
                            {{ hasFilters ? 'Încearcă altă categorie sau alt județ.' : 'Fii primul care publică o cerere de ofertă.' }}
                        </p>
                    </div>

                    <!-- PAGINATION -->
                    <div v-if="quoteRequests.last_page > 1" class="mt-11 flex flex-wrap items-center justify-center gap-1.5">
                        <template v-for="(link, index) in quoteRequests.links" :key="index">
                            <button
                                type="button"
                                :disabled="!link.url"
                                @click="link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true, only: ['quoteRequests', 'filters'] })"
                                class="min-w-[2.375rem] rounded-[10px] border border-ivt-line bg-white px-3 py-2 text-[13.5px] font-semibold text-ivt-ink-soft transition-colors duration-150 hover:border-ivt-violet hover:text-primary"
                                :class="[
                                    link.active && 'border-ivt-ink bg-ivt-ink text-ivt-paper hover:border-ivt-ink hover:text-ivt-paper',
                                    !link.url && 'cursor-not-allowed opacity-30 hover:border-ivt-line hover:text-ivt-ink-soft',
                                ]"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
