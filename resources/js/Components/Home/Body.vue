<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    MagnifyingGlassIcon,
    MapPinIcon,
    HeartIcon,
    ArrowRightIcon,
    ArrowUpRightIcon,
    Squares2X2Icon,
    ScaleIcon,
    ChatBubbleLeftRightIcon,
    MegaphoneIcon,
    UserPlusIcon,
    CreditCardIcon,
    PhotoIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';
import { HeartIcon as HeartIconSolid, StarIcon } from '@heroicons/vue/24/solid';
import SectionHeading from '@/Components/Home/SectionHeading.vue';
import SearchField from '@/Components/Home/SearchField.vue';
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
});

const page = usePage();

const searchCategory = defineModel('searchCategory', { default: '' });
const searchCountyId = defineModel('searchCountyId', { default: '' });

const emit = defineEmits(['search']);

/* Gradient "swatches" — stand in for real photography on the hero mosaic
   and vendor media when a listing has no cover image. */
const swatches = [
    'from-ivt-accent-light to-primary',
    'from-ivt-teal-light to-ivt-ink-2',
    'from-ivt-violet-bright to-ivt-violet',
    'from-primary to-primary-deep',
];

const mosaicHeights = ['h-[300px]', 'h-[210px]', 'h-[230px]', 'h-[280px]'];

const heroColumns = [props.listings.slice(0, 2), props.listings.slice(2, 4)];

const topRated = computed(() => props.listings.filter((listing) => listing.rating).sort((a, b) => b.rating - a.rating)[0]);

const popularCategories = computed(() => [...props.categories].sort((a, b) => b.listings_count - a.listings_count).slice(0, 5));

const categoryOptions = computed(() => props.categories.map((category) => ({
    value: category.slug,
    label: category.name,
    icon: categoryIcon(category.slug),
    meta: `${category.listings_count} anunțuri`,
})));

const countyOptions = computed(() => props.counties.map((county) => ({ value: county.id, label: county.name })));

const location = (listing) => [listing.locality, listing.county].filter(Boolean).join(', ');

const coverStyle = (listing) => (listing.cover_url ? { backgroundImage: `url(${listing.cover_url})` } : {});

/* One favorite-toggle state per listing, built once at setup — safe since
   useFavoriteToggle only wraps refs, no lifecycle hooks. */
const favoriteStates = new Map(
    props.listings.map((listing) => [
        listing.id,
        useFavoriteToggle(listing.slug, props.favoriteListingIds.includes(listing.id), () => {}, listing.cover_url),
    ]),
);

const audience = ref('clients');

const steps = {
    clients: {
        title: 'De la căutare la primul telefon dat',
        items: [
            { icon: MagnifyingGlassIcon, title: 'Cauți și filtrezi', text: 'după categorie, oraș și buget, fără cont.' },
            { icon: ScaleIcon, title: 'Compari furnizorii', text: 'după poze, prețuri, recenzii și disponibilitate.' },
            { icon: ChatBubbleLeftRightIcon, title: 'Contactezi direct', text: 'prin telefon, WhatsApp, email sau formular.' },
            { icon: MegaphoneIcon, title: 'Publici o cerere', text: 'de ofertă și lași furnizorii potriviți să te găsească pe tine.' },
        ],
    },
    providers: {
        title: 'De la înregistrare la primul lead',
        items: [
            { icon: UserPlusIcon, title: 'Creezi un cont', text: 'de furnizor și completezi profilul companiei.' },
            { icon: CreditCardIcon, title: 'Alegi un abonament', text: 'Gratuit, Standard sau Premium — și plătești online.' },
            { icon: PhotoIcon, title: 'Publici anunțuri', text: 'cu galerie foto, video, prețuri și zonă de acoperire.' },
            { icon: ChartBarIcon, title: 'Primești contacte', text: 'și îți urmărești statisticile din dashboard.' },
        ],
    },
};

const testimonials = [
    {
        quote: 'Am trecut de la recomandări la prieteni la lead-uri constante în fiecare săptămână, direct pe WhatsApp.',
        name: 'Studio Lumina',
        role: 'Fotograf, Cluj-Napoca',
        swatch: 'from-ivt-accent-light to-primary',
    },
    {
        quote: 'Ne place că nu plătim comision pe fiecare eveniment — abonamentul e previzibil și controlăm bugetul de marketing.',
        name: 'Alegro Live',
        role: 'Formație & DJ, Oradea',
        swatch: 'from-ivt-teal-light to-ivt-ink-2',
    },
    {
        quote: 'Cererile de ofertă ne aduc exact clienții potriviți pentru stilul nostru, fără să căutăm noi peste tot.',
        name: 'Atelier Petale',
        role: 'Wedding Planner, București',
        swatch: 'from-ivt-violet-bright to-ivt-violet',
    },
];

const formatStat = (value, withPlus = true) => {
    const n = Number(value) || 0;
    return withPlus && n > 0 ? `${n.toLocaleString('ro-RO')}+` : n.toLocaleString('ro-RO');
};

const heroStats = computed(() => [
    { value: formatStat(props.stats.providers), label: 'furnizori activi' },
    { value: formatStat(props.stats.categories, false), label: 'categorii de servicii' },
    { value: formatStat(props.stats.quoteRequests), label: 'cereri de ofertă' },
]);

</script>

<template>
    <div class="overflow-x-clip bg-ivt-paper font-invita text-ivt-ink">
        <!-- Hero -->
        <section class="relative pb-16 pt-14 lg:pb-24 lg:pt-20">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -left-40 -top-40 h-[520px] w-[520px] rounded-full bg-primary/15 blur-3xl animate-float-slow" />
                <div class="absolute -right-32 top-10 h-[460px] w-[460px] rounded-full bg-ivt-violet/15 blur-3xl animate-float-slower" />
                <div class="absolute bottom-0 left-1/3 h-[320px] w-[320px] rounded-full bg-ivt-accent-bright/15 blur-3xl animate-float-slower" />
                <div
                    class="absolute inset-0 opacity-[0.35]"
                    style="background-image: radial-gradient(rgba(26,20,51,0.12) 1px, transparent 1px); background-size: 22px 22px; mask-image: radial-gradient(ellipse 70% 60% at 30% 30%, #000 30%, transparent 75%);"
                />
            </div>

            <div class="relative mx-auto grid max-w-[1600px] gap-14 px-6 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:px-8">
                <div>
                    <p class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 py-1 pl-1.5 pr-3.5 text-[12.5px] font-semibold text-ivt-ink-soft shadow-sm backdrop-blur">
                        <span class="rounded-full bg-brand px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider text-white">Nou</span>
                        Marketplace pentru evenimente, fără comisioane
                    </p>

                    <h1 class="mt-6 text-balance font-display text-[42px] font-bold leading-[1.0] tracking-tight text-ivt-ink sm:text-[58px] lg:text-[74px]">
                        Găsește furnizorul potrivit,
                        <span class="relative whitespace-nowrap">
                            <span class="text-gradient">fără intermediari.</span>
                            <svg class="absolute -bottom-2 left-0 h-3 w-full text-ivt-accent-bright" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true">
                                <path d="M2 9 C 80 2, 200 2, 298 7" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                            </svg>
                        </span>
                    </h1>

                    <p class="mt-7 max-w-[500px] text-lg leading-relaxed text-ivt-ink-soft">
                        Fotografi, formații, wedding planneri, restaurante și zeci de alți furnizori — toți într-un singur loc. Compari, alegi și îi contactezi direct.
                    </p>

                    <form
                        @submit.prevent="emit('search')"
                        class="ring-gradient relative z-20 mt-9 grid max-w-[640px] gap-1.5 rounded-[22px] border border-ivt-line bg-white p-2 shadow-ivt-soft transition-shadow focus-within:shadow-ivt-deep hover:shadow-ivt-deep sm:grid-cols-[1.2fr_1fr_auto]"
                    >
                        <SearchField
                            v-model="searchCategory"
                            label="Serviciu"
                            placeholder="Toate categoriile"
                            empty-label="Nicio categorie"
                            :icon="Squares2X2Icon"
                            :options="categoryOptions"
                            :columns="2"
                            class="sm:border-r sm:border-ivt-line"
                        />
                        <SearchField
                            v-model="searchCountyId"
                            label="Locație"
                            placeholder="Toate locațiile"
                            empty-label="Niciun județ"
                            :icon="MapPinIcon"
                            :options="countyOptions"
                        />
                        <button type="submit" class="btn-brand rounded-2xl px-7 py-3.5 text-sm font-bold">
                            <MagnifyingGlassIcon class="h-4 w-4" /> Caută
                        </button>
                    </form>

                    <div v-if="popularCategories.length" class="mt-5 flex flex-wrap items-center gap-2">
                        <span class="mr-1 text-[12.5px] text-ivt-ink-faint">Populare:</span>
                        <Link
                            v-for="category in popularCategories"
                            :key="category.id"
                            :href="route('categories.show', category.slug)"
                            class="rounded-full border border-ivt-line bg-white/70 px-3 py-1 text-[12.5px] font-medium text-ivt-ink-soft transition-colors hover:border-ivt-violet hover:text-primary"
                        >
                            {{ category.name }}
                        </Link>
                    </div>

                    <dl class="mt-10 grid max-w-[520px] grid-cols-3 divide-x divide-ivt-line">
                        <div v-for="stat in heroStats" :key="stat.label" class="flex flex-col-reverse justify-end px-3 first:pl-0 sm:px-5">
                            <dt class="text-[12px] leading-snug text-ivt-ink-faint sm:text-[12.5px]">{{ stat.label }}</dt>
                            <dd class="font-display text-[26px] font-bold leading-tight text-ivt-ink sm:text-[30px]">{{ stat.value }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Hero mosaic -->
                <div v-if="listings.length" class="relative hidden lg:block">
                    <div class="grid grid-cols-2 gap-4">
                        <div v-for="(column, columnIndex) in heroColumns" :key="columnIndex" class="flex flex-col gap-4" :class="columnIndex === 1 && 'pt-14'">
                            <Link
                                v-for="(listing, index) in column"
                                :key="listing.id"
                                :href="route('listings.show', listing.slug)"
                                v-tilt="8"
                                class="group relative block overflow-hidden rounded-[26px] shadow-ivt-deep ring-1 ring-black/5"
                                :class="mosaicHeights[columnIndex * 2 + index]"
                            >
                                <div
                                    class="absolute inset-0 bg-cover bg-center bg-gradient-to-br transition-transform duration-700 [transition-timing-function:cubic-bezier(.2,.8,.2,1)] group-hover:scale-105"
                                    :class="!listing.cover_url && swatches[(columnIndex * 2 + index) % swatches.length]"
                                    :style="coverStyle(listing)"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-ivt-ink-deep/85 via-ivt-ink-deep/10 to-transparent" />
                                <div class="absolute inset-x-0 bottom-0 p-5">
                                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-ivt-accent-bright">{{ listing.category }}</p>
                                    <h4 class="mt-1 font-display text-lg font-semibold leading-snug text-white line-clamp-1">{{ listing.title }}</h4>
                                    <p class="mt-1 text-[12.5px] text-white/75">{{ formatListingPrice(listing) }}</p>
                                </div>
                                <span class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-full bg-white/20 text-white opacity-0 backdrop-blur-md transition-opacity group-hover:opacity-100">
                                    <ArrowUpRightIcon class="h-4 w-4" />
                                </span>
                            </Link>
                        </div>
                    </div>

                    <div v-if="topRated" class="absolute -left-8 top-[44%] flex items-center gap-3 rounded-2xl border border-white/60 bg-white/85 py-3 pl-3 pr-4 shadow-ivt-deep backdrop-blur-md animate-float-slow">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-ivt-accent-bright/20">
                            <StarIcon class="h-5 w-5 text-ivt-accent-bright" />
                        </span>
                        <span>
                            <b class="block text-sm text-ivt-ink">{{ topRated.rating }} / 5</b>
                            <span class="text-[11.5px] text-ivt-ink-faint">{{ topRated.reviews_count }} recenzii · {{ topRated.provider.company_name }}</span>
                        </span>
                    </div>

                    <Link v-if="quoteRequests.length" :href="route('quote-requests.browse')" class="absolute -bottom-6 right-6 flex max-w-[260px] items-center gap-3 rounded-2xl bg-ivt-ink py-3 pl-3 pr-4 text-ivt-on-dark shadow-ivt-deep animate-float-slower">
                        <span class="relative flex h-2.5 w-2.5 flex-none">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-ivt-accent-bright opacity-60" />
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-ivt-accent-bright" />
                        </span>
                        <span class="min-w-0">
                            <b class="block text-[13px]">Cerere nouă de ofertă</b>
                            <span class="block truncate text-[11.5px] text-ivt-on-dark-dim">
                                {{ [quoteRequests[0].category, quoteRequests[0].county].filter(Boolean).join(' · ') || quoteRequests[0].event_type }}
                            </span>
                        </span>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Bandă de categorii -->
        <div v-if="categories.length" class="relative -rotate-1 bg-brand py-4 shadow-glow-primary">
            <div class="group flex overflow-hidden [mask-image:linear-gradient(90deg,transparent,#000_8%,#000_92%,transparent)]">
                <div class="flex flex-none animate-marquee items-center group-hover:[animation-play-state:paused]">
                    <template v-for="copy in 2" :key="copy">
                        <Link
                            v-for="category in categories"
                            :key="`${copy}-${category.id}`"
                            :href="route('categories.show', category.slug)"
                            :tabindex="copy === 2 ? -1 : undefined"
                            :aria-hidden="copy === 2 ? 'true' : undefined"
                            class="flex items-center gap-2.5 whitespace-nowrap px-6 font-display text-lg font-semibold text-white/90 transition-colors hover:text-white"
                        >
                            <component :is="categoryIcon(category.slug)" class="h-5 w-5 text-ivt-accent-light" stroke-width="1.75" />
                            {{ category.name }}
                            <span class="ml-4 text-white/40" aria-hidden="true">✦</span>
                        </Link>
                    </template>
                </div>
            </div>
        </div>

        <!-- Categorii -->
        <section id="categorii" class="mx-auto max-w-[1600px] px-6 py-16 lg:px-8 lg:py-24">
            <SectionHeading v-reveal eyebrow="Categorii" title="Fiecare detaliu al evenimentului, într-o singură vitrină.">
                De la fotograf și DJ, până la florărie și torturi — tot ce îți trebuie, organizat pe categorii.
            </SectionHeading>

            <div v-reveal="100" class="-mx-6 mt-12 flex snap-x snap-mandatory scroll-px-6 gap-3.5 overflow-x-auto px-6 pb-2 [scrollbar-width:none] sm:mx-0 sm:grid sm:grid-cols-3 sm:overflow-visible sm:px-0 lg:grid-cols-6">
                <Link
                    v-for="category in categories"
                    :key="category.id"
                    :href="route('categories.show', category.slug)"
                    v-spotlight
                    class="group relative flex w-[150px] flex-none snap-start flex-col gap-4 overflow-hidden rounded-[20px] border border-ivt-line bg-white p-5 transition-all duration-300 hover:-translate-y-1 hover:border-primary/30 hover:shadow-glow-primary sm:w-auto"
                >
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-ivt-paper-2 text-primary transition-all duration-300 group-hover:rotate-[-6deg] group-hover:scale-110 group-hover:bg-brand group-hover:text-white">
                        <component :is="categoryIcon(category.slug)" class="h-6 w-6" stroke-width="1.5" />
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-ivt-ink">{{ category.name }}</span>
                        <small class="mt-0.5 block text-[11.5px] text-ivt-ink-faint">{{ category.listings_count }} anunțuri</small>
                    </span>
                    <ArrowUpRightIcon class="absolute right-4 top-4 h-4 w-4 -translate-x-1 translate-y-1 text-ivt-ink-faint opacity-0 transition-all duration-300 group-hover:translate-x-0 group-hover:translate-y-0 group-hover:opacity-100" />
                </Link>
            </div>
        </section>

        <!-- Cum funcționează -->
        <section id="cum-functioneaza" class="relative bg-ivt-paper-2 py-16 lg:py-24">
            <div class="mx-auto max-w-[1600px] px-6 lg:px-8">
                <SectionHeading v-reveal eyebrow="Cum funcționează" title="Fără rezervări, fără comisioane. Doar conexiune directă.">
                    Platforma nu intermediază plăți sau rezervări — furnizorii plătesc un abonament ca să fie vizibili, clienții contactează direct cine le place.
                </SectionHeading>

                <div v-reveal="100" class="mt-12">
                    <div class="inline-flex rounded-full border border-ivt-line bg-white p-1 shadow-sm" role="tablist">
                        <button
                            v-for="option in [{ key: 'clients', label: 'Pentru clienți' }, { key: 'providers', label: 'Pentru furnizori' }]"
                            :key="option.key"
                            type="button"
                            role="tab"
                            :aria-selected="audience === option.key"
                            @click="audience = option.key"
                            class="rounded-full px-5 py-2 text-sm font-semibold transition-all duration-300"
                            :class="audience === option.key ? 'bg-brand text-white shadow-glow-primary' : 'text-ivt-ink-soft hover:text-ivt-ink'"
                        >
                            {{ option.label }}
                        </button>
                    </div>

                    <h3 class="mt-8 font-display text-2xl font-semibold text-ivt-ink">{{ steps[audience].title }}</h3>

                    <Transition mode="out-in" enter-from-class="opacity-0 translate-y-2" leave-to-class="opacity-0 -translate-y-2" enter-active-class="transition duration-300" leave-active-class="transition duration-200">
                        <ol :key="audience" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <li
                                v-for="(step, index) in steps[audience].items"
                                :key="step.title"
                                v-spotlight
                                class="group relative overflow-hidden rounded-[22px] border border-ivt-line bg-white p-6 transition-all duration-300 hover:-translate-y-1 hover:border-ivt-violet/25 hover:shadow-ivt-soft"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-ivt-paper-2 text-primary transition-all duration-300 group-hover:scale-110 group-hover:bg-brand group-hover:text-white">
                                        <component :is="step.icon" class="h-5 w-5" />
                                    </span>
                                    <span class="text-gradient font-display text-4xl font-bold opacity-30 transition-opacity duration-300 group-hover:opacity-100">0{{ index + 1 }}</span>
                                </div>
                                <p class="mt-5 text-[15px] font-semibold text-ivt-ink">{{ step.title }}</p>
                                <p class="mt-1 text-[14px] leading-relaxed text-ivt-ink-soft">{{ step.text }}</p>
                            </li>
                        </ol>
                    </Transition>
                </div>
            </div>
        </section>

        <!-- Furnizori -->
        <section v-if="listings.length" id="furnizori" class="mx-auto max-w-[1600px] px-6 py-16 lg:px-8 lg:py-24">
            <SectionHeading v-reveal eyebrow="Furnizori recomandați" title="O selecție din vitrina săptămânii.">
                Anunțurile Premium apar primele în rezultate și beneficiază de vizibilitate extinsă.
            </SectionHeading>

            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="(listing, index) in listings" :key="listing.id" v-reveal="index * 80">
                    <article v-spotlight class="ring-gradient group relative flex h-full flex-col overflow-hidden rounded-[22px] border border-ivt-line bg-white transition-all duration-300 hover:-translate-y-1.5 hover:shadow-ivt-deep">
                        <Link :href="route('listings.show', listing.slug)" class="relative block aspect-[4/3] overflow-hidden">
                            <div
                                class="absolute inset-0 bg-cover bg-center bg-gradient-to-br transition-transform duration-700 group-hover:scale-105"
                                :class="!listing.cover_url && swatches[index % swatches.length]"
                                :style="coverStyle(listing)"
                            />
                            <span v-if="listing.is_featured" class="absolute left-3 top-3 rounded-full border border-white/20 bg-brand px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.08em] text-white shadow-glow-primary">
                                Premium
                            </span>
                            <span v-if="listing.rating" class="absolute bottom-3 left-3 flex items-center gap-1 rounded-full bg-white/90 px-2.5 py-1 text-[12px] font-semibold text-ivt-ink backdrop-blur-md">
                                <StarIcon class="h-3.5 w-3.5 text-ivt-accent-bright" /> {{ listing.rating }}
                                <span class="font-normal text-ivt-ink-faint">({{ listing.reviews_count }})</span>
                            </span>
                        </Link>
                        <button
                            v-if="page.props.auth.user"
                            type="button"
                            @click="favoriteStates.get(listing.id).toggle($event)"
                            class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/85 text-primary shadow-sm backdrop-blur-md transition-transform hover:scale-110 active:scale-95"
                            :aria-label="favoriteStates.get(listing.id).isFavorited.value ? 'Elimină de la favorite' : 'Adaugă la favorite'"
                        >
                            <HeartIconSolid v-if="favoriteStates.get(listing.id).isFavorited.value" class="h-4 w-4" />
                            <HeartIcon v-else class="h-4 w-4" />
                        </button>

                        <div class="flex flex-1 flex-col p-5">
                            <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ listing.category }}</p>
                            <Link :href="route('listings.show', listing.slug)" class="mt-1.5 block font-display text-lg font-semibold leading-snug text-ivt-ink line-clamp-2 hover:text-primary">
                                {{ listing.title }}
                            </Link>
                            <p v-if="location(listing)" class="mt-1.5 flex items-center gap-1.5 text-[13px] text-ivt-ink-faint">
                                <MapPinIcon class="h-3.5 w-3.5 flex-none" /> {{ location(listing) }}
                            </p>
                            <div class="mt-auto flex items-center justify-between pt-4">
                                <span class="font-display text-[15px] font-bold text-ivt-ink">{{ formatListingPrice(listing) }}</span>
                                <Link :href="route('listings.show', listing.slug)" class="flex h-9 w-9 items-center justify-center rounded-full bg-ivt-paper-2 text-ivt-ink transition-all group-hover:bg-brand group-hover:text-white group-hover:shadow-glow-primary" aria-label="Vezi anunțul">
                                    <ArrowRightIcon class="h-4 w-4 transition-transform group-hover:-rotate-45" />
                                </Link>
                            </div>
                        </div>
                    </article>
                </div>
            </div>

            <div class="mt-12 flex justify-center">
                <Link :href="route('listings.index')" class="group inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white px-6 py-3 text-sm font-semibold text-ivt-ink transition-all hover:-translate-y-0.5 hover:border-transparent hover:bg-ivt-ink hover:text-ivt-paper hover:shadow-ivt-deep">
                    Vezi toți furnizorii <ArrowRightIcon class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                </Link>
            </div>
        </section>

        <!-- Testimoniale -->
        <section class="mx-auto max-w-[1600px] px-6 py-16 lg:px-8 lg:py-24">
            <SectionHeading v-reveal eyebrow="Furnizori mulțumiți" title="Cine folosește deja platforma." />

            <div class="mt-12 grid gap-6 lg:grid-cols-3">
                <figure
                    v-for="(testimonial, index) in testimonials"
                    :key="testimonial.name"
                    v-reveal="index * 100"
                    v-spotlight
                    class="relative flex flex-col overflow-hidden rounded-[22px] border border-ivt-line bg-white p-7 transition-all duration-300 hover:-translate-y-1 hover:shadow-ivt-soft"
                >
                    <span class="text-gradient pointer-events-none absolute right-6 top-2 font-display text-[80px] font-bold leading-none opacity-20" aria-hidden="true">&rdquo;</span>
                    <div class="flex gap-0.5">
                        <StarIcon v-for="n in 5" :key="n" class="h-4 w-4 text-ivt-accent-bright" />
                    </div>
                    <blockquote class="mt-4 flex-1 text-[16px] font-medium leading-relaxed text-ivt-ink">&ldquo;{{ testimonial.quote }}&rdquo;</blockquote>
                    <figcaption class="mt-6 flex items-center gap-3 border-t border-ivt-line pt-5">
                        <div class="h-10 w-10 flex-none rounded-full bg-gradient-to-br ring-2 ring-white" :class="testimonial.swatch" />
                        <div>
                            <b class="block text-[13.5px] font-bold text-ivt-ink">{{ testimonial.name }}</b>
                            <span class="text-xs text-ivt-ink-faint">{{ testimonial.role }}</span>
                        </div>
                    </figcaption>
                </figure>
            </div>
        </section>

        <!-- Despre -->
        <section id="despre" class="border-t border-ivt-line py-16 lg:py-20">
            <div v-reveal class="mx-auto max-w-3xl px-6 text-center lg:px-8">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-primary">Despre noi</p>
                <h2 class="mt-3.5 text-balance font-display text-[clamp(28px,3vw,40px)] leading-tight text-ivt-ink">O platformă făcută pentru piața de evenimente din România</h2>
                <p class="mt-5 text-[17px] leading-relaxed text-ivt-ink-soft">
                    EventHub este locul unde oamenii care organizează o nuntă, un botez sau orice alt eveniment găsesc furnizori de încredere — fotografi, formații, restaurante, decoratori și mulți alții — și trimit cereri de ofertă direct, fără intermediari.
                </p>
            </div>
        </section>

        <!-- CTA final -->
        <section class="mx-auto max-w-[1600px] px-6 pb-20 lg:px-8">
            <div v-reveal class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-ivt-ink-2 to-ivt-ink px-8 py-14 text-center sm:px-14 sm:py-20">
                <div
                    class="pointer-events-none absolute inset-0 opacity-40"
                    style="background-image: linear-gradient(rgba(247,244,255,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(247,244,255,0.06) 1px, transparent 1px); background-size: 44px 44px; mask-image: radial-gradient(ellipse 60% 70% at 50% 50%, #000 20%, transparent 80%);"
                />
                <div class="pointer-events-none absolute -right-[120px] -top-[160px] h-[420px] w-[420px] rounded-full animate-float-slow" style="background: radial-gradient(circle, rgba(124,58,237,0.28), transparent 70%);" />
                <div class="pointer-events-none absolute -bottom-[180px] -left-[120px] h-[380px] w-[380px] rounded-full animate-float-slower" style="background: radial-gradient(circle, rgba(225,29,99,0.35), transparent 70%);" />
                <p class="relative inline-flex items-center gap-2 rounded-full border border-white/15 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-ivt-accent-bright">
                    <span class="h-1.5 w-1.5 rounded-full bg-ivt-accent-bright" /> Pentru furnizori
                </p>
                <h2 class="relative mx-auto mt-5 max-w-2xl text-balance font-display text-[clamp(30px,3.8vw,50px)] leading-[1.08] tracking-tight text-ivt-on-dark">
                    Anunțul tău, în fața clienților care caută chiar acum.
                </h2>
                <p class="relative mx-auto mt-4 max-w-[460px] text-[15px] text-ivt-on-dark-dim">
                    Creezi cont în câteva minute și îți publici primul anunț chiar azi.
                </p>
                <div class="relative mt-9 flex flex-wrap justify-center gap-3">
                    <Link href="/register" class="btn-brand px-7 py-3.5 text-sm">
                        Creează cont furnizor <ArrowRightIcon class="h-4 w-4" />
                    </Link>
                    <Link :href="route('subscriptions.index')" class="rounded-full border border-white/25 bg-white/5 px-7 py-3.5 text-sm font-semibold text-ivt-on-dark backdrop-blur transition-colors hover:border-white/60 hover:bg-white/10">
                        Vezi abonamentele
                    </Link>
                </div>
            </div>
        </section>
    </div>
</template>
