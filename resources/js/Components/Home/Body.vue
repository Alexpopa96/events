<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, MapPinIcon, HeartIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import { HeartIcon as HeartIconSolid, StarIcon } from '@heroicons/vue/24/solid';
import { categoryIcon } from '@/Composables/useCategoryIcon';
import { formatListingPrice } from '@/Composables/useListingPrice';
import { useFavoriteToggle } from '@/Composables/useFavoriteToggle';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    counties: { type: Array, default: () => [] },
    listings: { type: Array, default: () => [] },
    favoriteListingIds: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ providers: 0, categories: 0, quoteRequests: 0 }) },
    quoteRequests: { type: Array, default: () => [] },
    subscriptionPlans: { type: Array, default: () => [] },
});

const page = usePage();

const searchCategory = defineModel('searchCategory', { default: '' });
const searchCountyId = defineModel('searchCountyId', { default: '' });

const emit = defineEmits(['search']);

/* Gradient "swatches" — stand in for real photography on the hero card
   deck and vendor media when a listing has no cover image, cycling through
   the mockup's four warm/forest combinations. */
const swatches = [
    'from-[#D9B673] to-[#7C2E3B]',
    'from-[#93A88B] to-[#22402F]',
    'from-[#E9DDBB] to-[#B8923F]',
    'from-[#7C2E3B] to-[#3a1219]',
];

const deckTilts = [
    '-rotate-[11deg] -translate-x-[150px] translate-y-2.5',
    '-rotate-[3deg] -translate-x-10 -translate-y-[18px]',
    'rotate-[6deg] translate-x-[90px] translate-y-1',
    'rotate-[15deg] translate-x-[190px] translate-y-[30px]',
];

const heroDeck = props.listings.slice(0, 4);

const location = (listing) => [listing.locality, listing.county].filter(Boolean).join(', ');

/* One favorite-toggle state per listing, built once at setup — safe since
   useFavoriteToggle only wraps refs, no lifecycle hooks. */
const favoriteStates = new Map(
    props.listings.map((listing) => [
        listing.id,
        useFavoriteToggle(listing.slug, props.favoriteListingIds.includes(listing.id), () => {}, listing.cover_url),
    ]),
);

const clientSteps = [
    { title: 'Cauți și filtrezi', text: 'după categorie, oraș și buget, fără cont.' },
    { title: 'Compari furnizorii', text: 'după poze, prețuri, recenzii și disponibilitate.' },
    { title: 'Contactezi direct', text: 'prin telefon, WhatsApp, email sau formular.' },
    { title: 'Publici o cerere', text: 'de ofertă și lași furnizorii potriviți să te găsească pe tine.' },
];

const providerSteps = [
    { title: 'Creezi un cont', text: 'de furnizor și completezi profilul companiei.' },
    { title: 'Alegi un abonament', text: 'Gratuit, Standard sau Premium — și plătești online.' },
    { title: 'Publici anunțuri', text: 'cu galerie foto, video, prețuri și zonă de acoperire.' },
    { title: 'Primești contacte', text: 'și îți urmărești statisticile din dashboard.' },
];

const testimonials = [
    {
        quote: 'Am trecut de la recomandări la prieteni la lead-uri constante în fiecare săptămână, direct pe WhatsApp.',
        name: 'Studio Lumina',
        role: 'Fotograf, Cluj-Napoca',
        swatch: 'from-[#D9B673] to-[#7C2E3B]',
    },
    {
        quote: 'Ne place că nu plătim comision pe fiecare eveniment — abonamentul e previzibil și controlăm bugetul de marketing.',
        name: 'Alegro Live',
        role: 'Formație & DJ, Oradea',
        swatch: 'from-[#93A88B] to-[#22402F]',
    },
    {
        quote: 'Cererile de ofertă ne aduc exact clienții potriviți pentru stilul nostru, fără să căutăm noi peste tot.',
        name: 'Atelier Petale',
        role: 'Wedding Planner, București',
        swatch: 'from-[#E9DDBB] to-[#B8923F]',
    },
];

const formatStat = (value, withPlus = true) => {
    const n = Number(value) || 0;
    return withPlus && n > 0 ? `${n.toLocaleString('ro-RO')}+` : n.toLocaleString('ro-RO');
};

const postQuoteHref = () => (page.props.auth.can?.submitQuoteRequest ? route('quote-requests.create') : '/register/client');
</script>

<template>
    <div class="bg-ivt-paper font-invita text-ivt-ink">
        <!-- Hero -->
        <section class="relative overflow-hidden pb-14 pt-16 lg:pt-20">
            <div class="pointer-events-none absolute inset-x-[-10%] -top-[20%] h-[110%]">
                <div class="absolute inset-0" style="background: radial-gradient(55% 55% at 25% 10%, rgba(168,127,46,0.09), transparent 60%), radial-gradient(45% 45% at 90% 5%, rgba(124,46,59,0.08), transparent 60%);" />
            </div>

            <div class="relative mx-auto grid max-w-7xl gap-10 px-6 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:px-8">
                <div>
                    <p class="flex items-center gap-2.5 text-xs font-bold uppercase tracking-[0.18em] text-ivt-wine">
                        <span class="inline-block h-px w-[22px] bg-ivt-gold" /> Marketplace pentru evenimente
                    </p>
                    <h1 class="mt-4 font-serif text-[38px] leading-[1.05] tracking-tight text-ivt-ink sm:text-5xl lg:text-[62px]">
                        Găsește furnizorul potrivit,<br /><em class="font-normal italic text-ivt-wine">fără intermediari.</em>
                    </h1>
                    <p class="mt-5 max-w-[480px] text-lg leading-relaxed text-ivt-ink-soft">
                        Fotografi, formații, wedding planneri, restaurante și zeci de alți furnizori — toți într-un singur loc. Compari, alegi și îi contactezi direct.
                    </p>

                    <form @submit.prevent="emit('search')" class="mt-10 grid max-w-[560px] gap-2 rounded-[18px] border border-ivt-line bg-white p-2.5 shadow-ivt-soft sm:grid-cols-[1.2fr_1fr_auto]">
                        <div class="flex flex-col gap-0.5 rounded-xl px-4 py-2.5 sm:border-r sm:border-ivt-line">
                            <label class="text-[10.5px] font-bold uppercase tracking-[0.1em] text-ivt-ink-faint">Serviciu</label>
                            <select v-model="searchCategory" class="border-0 bg-transparent p-0 text-[14.5px] font-semibold text-ivt-ink focus:outline-none focus:ring-0">
                                <option value="">Toate categoriile</option>
                                <option v-for="category in categories" :key="category.id" :value="category.slug">{{ category.name }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-0.5 rounded-xl px-4 py-2.5 sm:border-b-0 sm:border-r sm:border-ivt-line">
                            <label class="text-[10.5px] font-bold uppercase tracking-[0.1em] text-ivt-ink-faint">Locație</label>
                            <select v-model="searchCountyId" class="border-0 bg-transparent p-0 text-[14.5px] font-semibold text-ivt-ink focus:outline-none focus:ring-0">
                                <option value="">Toate locațiile</option>
                                <option v-for="county in counties" :key="county.id" :value="county.id">{{ county.name }}</option>
                            </select>
                        </div>
                        <button type="submit" class="flex items-center justify-center gap-2 rounded-xl bg-ivt-ink px-6 py-3 text-sm font-bold text-ivt-paper transition-colors hover:bg-ivt-wine">
                            <MagnifyingGlassIcon class="h-4 w-4" /> Caută
                        </button>
                    </form>

                    <div class="mt-8 flex flex-wrap gap-8">
                        <div>
                            <b class="block font-serif text-[26px] font-semibold text-ivt-ink">{{ formatStat(stats.providers) }}</b>
                            <span class="text-[12.5px] text-ivt-ink-faint">furnizori activi</span>
                        </div>
                        <div>
                            <b class="block font-serif text-[26px] font-semibold text-ivt-ink">{{ formatStat(stats.categories, false) }}</b>
                            <span class="text-[12.5px] text-ivt-ink-faint">categorii de servicii</span>
                        </div>
                        <div>
                            <b class="block font-serif text-[26px] font-semibold text-ivt-ink">{{ formatStat(stats.quoteRequests) }}</b>
                            <span class="text-[12.5px] text-ivt-ink-faint">cereri de ofertă trimise</span>
                        </div>
                    </div>
                </div>

                <div v-if="heroDeck.length" class="group relative hidden h-[420px] items-center justify-center sm:flex lg:h-[520px]">
                    <Link
                        v-for="(listing, index) in heroDeck"
                        :key="listing.id"
                        :href="route('listings.show', listing.slug)"
                        class="absolute w-[230px] rounded-2xl border border-white/10 bg-gradient-to-br from-ivt-ink-2 to-ivt-ink p-5 text-ivt-on-dark shadow-ivt-deep transition-all duration-500 [transition-timing-function:cubic-bezier(.2,.8,.2,1)] hover:!z-20 hover:rotate-0 hover:scale-110 hover:shadow-2xl"
                        :class="deckTilts[index % deckTilts.length]"
                        :style="{ zIndex: index === 1 || index === 2 ? 2 : 1 }"
                    >
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-ivt-gold-bright">{{ listing.category }}</p>
                        <h4 class="mt-2.5 font-serif text-xl font-semibold text-ivt-on-dark line-clamp-1">{{ listing.title }}</h4>
                        <p v-if="location(listing)" class="mt-2.5 flex items-center gap-1.5 text-[12.5px] text-ivt-on-dark-dim">
                            <MapPinIcon class="h-3.5 w-3.5 flex-none" /> {{ location(listing) }}
                        </p>
                        <div
                            class="mt-4 h-20 rounded-[10px] bg-cover bg-center bg-gradient-to-br"
                            :class="!listing.cover_url && swatches[index % swatches.length]"
                            :style="listing.cover_url ? { backgroundImage: `url(${listing.cover_url})` } : {}"
                        />
                        <div class="mt-3.5 flex items-center justify-between text-xs text-ivt-on-dark-dim">
                            <span>de la</span>
                            <b class="text-sm text-ivt-on-dark">{{ formatListingPrice(listing) }}</b>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Categorii -->
        <section id="categorii" class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-24">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="flex items-center gap-2.5 text-xs font-bold uppercase tracking-[0.18em] text-ivt-wine">
                        <span class="inline-block h-px w-[22px] bg-ivt-gold" /> Categorii
                    </p>
                    <h2 class="mt-3.5 max-w-xl font-serif text-[clamp(30px,3.2vw,42px)] leading-tight text-ivt-ink">
                        Fiecare detaliu al evenimentului, într-o singură vitrină.
                    </h2>
                </div>
                <p class="max-w-[380px] text-[15px] leading-relaxed text-ivt-ink-soft">
                    De la fotograf și DJ, până la florărie și torturi — categoriile se administrează dinamic din panoul de admin.
                </p>
            </div>

            <div class="mt-14 grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-6">
                <Link
                    v-for="category in categories"
                    :key="category.id"
                    :href="route('categories.show', category.slug)"
                    class="flex flex-col gap-3.5 rounded-2xl border border-ivt-line bg-white px-4 py-5 transition-all duration-200 hover:-translate-y-1 hover:border-ivt-gold hover:shadow-ivt-soft"
                >
                    <component :is="categoryIcon(category.slug)" class="h-[30px] w-[30px] text-ivt-wine" stroke-width="1.4" />
                    <span>
                        <span class="block text-sm font-semibold text-ivt-ink">{{ category.name }}</span>
                        <small class="mt-0.5 block text-[11.5px] text-ivt-ink-faint">{{ category.listings_count }} anunțuri</small>
                    </span>
                </Link>
            </div>
        </section>

        <!-- Cum funcționează -->
        <section id="cum-functioneaza" class="border-y border-ivt-line bg-ivt-paper-2 py-16 lg:py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-6">
                    <div>
                        <p class="flex items-center gap-2.5 text-xs font-bold uppercase tracking-[0.18em] text-ivt-wine">
                            <span class="inline-block h-px w-[22px] bg-ivt-gold" /> Cum funcționează
                        </p>
                        <h2 class="mt-3.5 max-w-xl font-serif text-[clamp(30px,3.2vw,42px)] leading-tight text-ivt-ink">
                            Fără rezervări, fără comisioane. Doar conexiune directă.
                        </h2>
                    </div>
                    <p class="max-w-[380px] text-[15px] leading-relaxed text-ivt-ink-soft">
                        Platforma nu intermediază plăți sau rezervări — furnizorii plătesc un abonament ca să fie vizibili, clienții contactează direct cine le place.
                    </p>
                </div>

                <div class="mt-14 grid gap-7 lg:grid-cols-2">
                    <div class="rounded-[20px] border border-ivt-line bg-white p-9">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ivt-sage">Pentru clienți</p>
                        <h3 class="mt-1.5 font-serif text-[22px] font-medium text-ivt-ink">De la căutare la primul telefon dat</h3>
                        <div class="mt-6 flex flex-col">
                            <div v-for="(step, index) in clientSteps" :key="step.title" class="flex gap-4 border-t border-ivt-line py-4 first:border-t-0 first:pt-0">
                                <span class="min-w-[28px] font-serif text-xl italic text-ivt-gold">{{ index + 1 }}</span>
                                <p class="text-[14.5px] leading-relaxed text-ivt-ink-soft">
                                    <strong class="mb-0.5 block text-[15px] font-semibold text-ivt-ink">{{ step.title }}</strong>{{ step.text }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-[20px] border border-ivt-line bg-white p-9">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ivt-sage">Pentru furnizori</p>
                        <h3 class="mt-1.5 font-serif text-[22px] font-medium text-ivt-ink">De la înregistrare la primul lead</h3>
                        <div class="mt-6 flex flex-col">
                            <div v-for="(step, index) in providerSteps" :key="step.title" class="flex gap-4 border-t border-ivt-line py-4 first:border-t-0 first:pt-0">
                                <span class="min-w-[28px] font-serif text-xl italic text-ivt-gold">{{ index + 1 }}</span>
                                <p class="text-[14.5px] leading-relaxed text-ivt-ink-soft">
                                    <strong class="mb-0.5 block text-[15px] font-semibold text-ivt-ink">{{ step.title }}</strong>{{ step.text }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Furnizori -->
        <section v-if="listings.length" id="furnizori" class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-24">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="flex items-center gap-2.5 text-xs font-bold uppercase tracking-[0.18em] text-ivt-wine">
                        <span class="inline-block h-px w-[22px] bg-ivt-gold" /> Furnizori recomandați
                    </p>
                    <h2 class="mt-3.5 max-w-xl font-serif text-[clamp(30px,3.2vw,42px)] leading-tight text-ivt-ink">
                        O selecție din vitrina săptămânii.
                    </h2>
                </div>
                <p class="max-w-[380px] text-[15px] leading-relaxed text-ivt-ink-soft">
                    Anunțurile Premium apar primele în rezultate și beneficiază de vizibilitate extinsă.
                </p>
            </div>

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(listing, index) in listings.slice(0, 3)"
                    :key="listing.id"
                    class="group relative overflow-hidden rounded-[20px] border border-ivt-line bg-white shadow-[0_20px_40px_-32px_rgba(22,40,31,0.25)] transition-all duration-300 hover:-translate-y-1.5 hover:shadow-ivt-deep"
                >
                    <Link :href="route('listings.show', listing.slug)" class="relative block h-[180px] bg-cover bg-center bg-gradient-to-br" :class="!listing.cover_url && swatches[index % swatches.length]" :style="listing.cover_url ? { backgroundImage: `url(${listing.cover_url})` } : {}">
                        <span v-if="listing.is_featured" class="absolute left-3.5 top-3.5 rounded-full border border-white/15 bg-ivt-ink px-3 py-1.5 text-[10.5px] font-bold uppercase tracking-[0.08em] text-ivt-gold-bright">
                            Premium
                        </span>
                    </Link>
                    <button
                        v-if="page.props.auth.user"
                        type="button"
                        @click="favoriteStates.get(listing.id).toggle($event)"
                        class="absolute right-3.5 top-3.5 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-ivt-wine transition-transform hover:scale-110"
                        :aria-label="favoriteStates.get(listing.id).isFavorited.value ? 'Elimină de la favorite' : 'Adaugă la favorite'"
                    >
                        <HeartIconSolid v-if="favoriteStates.get(listing.id).isFavorited.value" class="h-4 w-4" />
                        <HeartIcon v-else class="h-4 w-4" />
                    </button>

                    <div class="p-5">
                        <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-ivt-wine">{{ listing.category }}</p>
                        <Link :href="route('listings.show', listing.slug)" class="mt-2 block font-serif text-xl font-semibold text-ivt-ink line-clamp-1 hover:text-ivt-wine">
                            {{ listing.title }}
                        </Link>
                        <p v-if="location(listing)" class="mt-1.5 flex items-center gap-1.5 text-[13px] text-ivt-ink-faint">
                            <MapPinIcon class="h-3.5 w-3.5 flex-none" /> {{ location(listing) }}
                        </p>
                        <div v-if="listing.rating" class="mt-3 flex items-center gap-2 text-[13px]">
                            <span class="flex items-center gap-0.5 text-ivt-gold">
                                <StarIcon v-for="n in 5" :key="n" class="h-3.5 w-3.5" :class="n <= Math.round(listing.rating) ? 'text-ivt-gold' : 'text-ivt-line'" />
                            </span>
                            <span class="text-ivt-ink-faint">{{ listing.rating }} · {{ listing.reviews_count }} recenzii</span>
                        </div>
                        <div class="mt-4 flex items-center justify-between border-t border-ivt-line pt-4">
                            <span class="text-[13px] font-semibold text-ivt-ink">{{ formatListingPrice(listing) }}</span>
                            <Link :href="route('listings.show', listing.slug)" class="flex h-8 w-8 items-center justify-center rounded-full bg-ivt-ink text-ivt-paper transition-colors hover:bg-ivt-wine">
                                <ArrowRightIcon class="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-12 flex justify-center">
                <Link :href="route('listings.index')" class="rounded-full border border-ivt-line px-6 py-3 text-sm font-semibold text-ivt-ink transition-colors hover:border-ivt-gold hover:text-ivt-wine">
                    Vezi toți furnizorii
                </Link>
            </div>
        </section>

        <!-- Cereri de ofertă -->
        <section id="cereri" class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-24">
            <div class="grid overflow-hidden rounded-[24px] border border-ivt-line shadow-ivt-soft lg:grid-cols-[0.9fr_1.1fr]">
                <div class="flex flex-col justify-center bg-ivt-paper-2 p-9 lg:p-14">
                    <p class="flex items-center gap-2.5 text-xs font-bold uppercase tracking-[0.18em] text-ivt-wine">
                        <span class="inline-block h-px w-[22px] bg-ivt-gold" /> Cereri de ofertă
                    </p>
                    <h2 class="mt-3.5 font-serif text-[32px] leading-tight text-ivt-ink">Nu găsești exact ce cauți? Spune tu ce ai nevoie.</h2>
                    <p class="mt-4 max-w-[380px] text-[15px] leading-relaxed text-ivt-ink-soft">
                        Publici o cerere în două minute, iar furnizorii potriviți din zona ta te contactează direct — tu alegi cu cine discuți mai departe.
                    </p>
                    <Link :href="postQuoteHref()" class="mt-6 inline-flex w-fit items-center justify-center rounded-full bg-gradient-to-b from-ivt-wine-bright to-ivt-wine px-6 py-3 text-sm font-semibold text-ivt-paper transition-transform hover:-translate-y-0.5">
                        Publică o cerere
                    </Link>
                </div>
                <div class="flex flex-col gap-3.5 p-9 lg:p-12">
                    <div v-if="!quoteRequests.length" class="rounded-2xl border border-ivt-line bg-white p-5 text-sm text-ivt-ink-soft">
                        Fii primul care publică o cerere de ofertă.
                    </div>
                    <div v-for="quoteRequest in quoteRequests" :key="quoteRequest.id" class="flex items-center justify-between gap-4 rounded-2xl border border-ivt-line bg-white px-5 py-4">
                        <div>
                            <p class="text-[14.5px] italic text-ivt-ink line-clamp-1">&ldquo;{{ quoteRequest.message }}&rdquo;</p>
                            <p class="mt-1.5 text-xs text-ivt-ink-faint">
                                {{ [quoteRequest.event_type, quoteRequest.county, quoteRequest.budget_range].filter(Boolean).join(' · ') }}
                            </p>
                        </div>
                        <span v-if="quoteRequest.category" class="whitespace-nowrap rounded-full border border-ivt-gold px-3 py-1.5 text-[11px] font-semibold text-ivt-wine">
                            {{ quoteRequest.category }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Abonamente -->
        <section v-if="subscriptionPlans.length" id="abonamente" class="border-y border-ivt-line bg-ivt-paper-2 py-16 lg:py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-6">
                    <div>
                        <p class="flex items-center gap-2.5 text-xs font-bold uppercase tracking-[0.18em] text-ivt-wine">
                            <span class="inline-block h-px w-[22px] bg-ivt-gold" /> Abonamente pentru furnizori
                        </p>
                        <h2 class="mt-3.5 max-w-xl font-serif text-[clamp(30px,3.2vw,42px)] leading-tight text-ivt-ink">
                            Vizibilitate plătită, nu comision pe rezervare.
                        </h2>
                    </div>
                    <p class="max-w-[380px] text-[15px] leading-relaxed text-ivt-ink-soft">
                        Alegi planul potrivit afacerii tale și îl poți schimba oricând din dashboard.
                    </p>
                </div>

                <div class="mt-14 grid gap-6 lg:grid-cols-3">
                    <div
                        v-for="(plan, index) in subscriptionPlans"
                        :key="plan.slug"
                        class="flex flex-col rounded-[20px] border p-9 transition-transform duration-300 hover:-translate-y-1.5"
                        :class="plan.slug === 'standard' || (subscriptionPlans.length === 3 && index === 1)
                            ? 'relative border-ivt-ink bg-gradient-to-br from-ivt-ink-2 to-ivt-ink text-ivt-on-dark shadow-ivt-deep'
                            : 'border-ivt-line bg-white hover:shadow-ivt-soft'"
                    >
                        <span
                            v-if="plan.slug === 'standard' || (subscriptionPlans.length === 3 && index === 1)"
                            class="absolute -top-3 left-9 rounded-full bg-ivt-gold-bright px-3.5 py-1.5 text-[11px] font-extrabold uppercase tracking-[0.06em] text-[#221708]"
                        >
                            Recomandat
                        </span>
                        <h3 class="font-serif text-xl font-medium" :class="plan.slug === 'standard' || (subscriptionPlans.length === 3 && index === 1) ? 'text-ivt-on-dark' : 'text-ivt-ink'">
                            {{ plan.name }}
                        </h3>
                        <p class="mt-2 min-h-9 text-[13px]" :class="plan.slug === 'standard' || (subscriptionPlans.length === 3 && index === 1) ? 'text-ivt-on-dark-dim' : 'text-ivt-ink-soft'">
                            {{ plan.description }}
                        </p>
                        <div class="mt-6 flex items-baseline gap-1.5">
                            <b class="font-serif text-[44px] font-semibold">{{ plan.price > 0 ? plan.price.toLocaleString('ro-RO') : '0' }} lei</b>
                            <span class="text-[13px]" :class="plan.slug === 'standard' || (subscriptionPlans.length === 3 && index === 1) ? 'text-ivt-on-dark-dim' : 'text-ivt-ink-faint'">/ lună</span>
                        </div>
                        <ul class="mt-6 flex flex-1 flex-col gap-3.5">
                            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2.5 text-sm" :class="plan.slug === 'standard' || (subscriptionPlans.length === 3 && index === 1) ? 'text-ivt-on-dark-dim' : 'text-ivt-ink-soft'">
                                <span class="text-ivt-gold">✓</span> {{ feature }}
                            </li>
                        </ul>
                        <Link
                            href="/register"
                            class="mt-7 block rounded-full py-3 text-center text-sm font-semibold transition-colors"
                            :class="plan.slug === 'standard' || (subscriptionPlans.length === 3 && index === 1)
                                ? 'border border-ivt-gold-bright text-ivt-gold-bright hover:bg-white/5'
                                : 'border border-ivt-line text-ivt-ink hover:border-ivt-gold hover:text-ivt-wine'"
                        >
                            Alege {{ plan.name }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimoniale -->
        <section class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-24">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="flex items-center gap-2.5 text-xs font-bold uppercase tracking-[0.18em] text-ivt-wine">
                        <span class="inline-block h-px w-[22px] bg-ivt-gold" /> Furnizori mulțumiți
                    </p>
                    <h2 class="mt-3.5 font-serif text-[clamp(30px,3.2vw,42px)] leading-tight text-ivt-ink">Cine folosește deja platforma.</h2>
                </div>
            </div>

            <div class="mt-14 grid gap-6 lg:grid-cols-3">
                <div v-for="testimonial in testimonials" :key="testimonial.name" class="rounded-[18px] border border-ivt-line bg-white p-7">
                    <p class="font-serif text-[15px] italic leading-relaxed text-ivt-ink">&ldquo;{{ testimonial.quote }}&rdquo;</p>
                    <div class="mt-5 flex items-center gap-3">
                        <div class="h-9 w-9 flex-none rounded-full bg-gradient-to-br" :class="testimonial.swatch" />
                        <div>
                            <b class="block text-[13.5px] font-bold text-ivt-ink">{{ testimonial.name }}</b>
                            <span class="text-xs text-ivt-ink-faint">{{ testimonial.role }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Despre -->
        <section id="despre" class="border-t border-ivt-line py-16 lg:py-20">
            <div class="mx-auto max-w-4xl px-6 text-center lg:px-8">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-ivt-wine">Despre noi</p>
                <h2 class="mt-3.5 font-serif text-[clamp(28px,3vw,38px)] leading-tight text-ivt-ink">O platformă făcută pentru piața de evenimente din România</h2>
                <p class="mt-5 leading-relaxed text-ivt-ink-soft">
                    EventHub este locul unde oamenii care organizează o nuntă, un botez sau orice alt eveniment găsesc furnizori de încredere — fotografi, formații, restaurante, decoratori și mulți alții — și trimit cereri de ofertă direct, fără intermediari.
                </p>
            </div>
        </section>

        <!-- CTA final -->
        <section class="mx-auto max-w-7xl px-6 pb-20 lg:px-8">
            <div class="relative overflow-hidden rounded-[28px] bg-gradient-to-br from-ivt-ink-2 to-ivt-ink px-8 py-14 text-center sm:px-14 sm:py-[72px]">
                <div class="pointer-events-none absolute -right-[120px] -top-[160px] h-[420px] w-[420px] rounded-full" style="background: radial-gradient(circle, rgba(201,162,79,0.25), transparent 70%);" />
                <p class="relative flex items-center justify-center gap-2.5 text-xs font-bold uppercase tracking-[0.18em] text-ivt-gold-bright">
                    <span class="inline-block h-px w-[22px] bg-ivt-gold-bright" /> Pentru furnizori
                </p>
                <h2 class="relative mt-4 font-serif text-[clamp(30px,3.6vw,46px)] leading-tight text-ivt-on-dark">
                    Anunțul tău, în fața clienților care caută chiar acum.
                </h2>
                <p class="relative mx-auto mt-4 max-w-[460px] text-[15px] text-ivt-on-dark-dim">
                    Creezi cont în câteva minute și îți publici primul anunț chiar azi.
                </p>
                <div class="relative mt-8 flex flex-wrap justify-center gap-4">
                    <Link href="/register" class="rounded-full bg-gradient-to-b from-ivt-gold-bright to-ivt-gold px-6 py-3 text-sm font-semibold text-[#221708] transition-transform hover:-translate-y-0.5">
                        Creează cont furnizor
                    </Link>
                    <a href="#abonamente" class="rounded-full border border-white/30 px-6 py-3 text-sm font-semibold text-ivt-on-dark transition-colors hover:border-ivt-gold-bright hover:text-ivt-gold-bright">
                        Vezi abonamentele
                    </a>
                </div>
            </div>
        </section>
    </div>
</template>
