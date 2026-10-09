<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { ArrowRightIcon, MagnifyingGlassIcon, MapPinIcon, FaceFrownIcon, AdjustmentsHorizontalIcon, XMarkIcon, CheckBadgeIcon } from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/24/solid';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import ProviderCard from '@/Components/Providers/ProviderCard.vue';
import ProviderFilterPanel from '@/Components/Providers/ProviderFilterPanel.vue';
import { formatListingPrice } from '@/Composables/useListingPrice';
import { categoryIcon } from '@/Composables/useCategoryIcon';

const props = defineProps({
    stats: { type: Object, required: true },
    providers: { type: Object, required: true },
    recommended: { type: Array, default: () => [] },
    favoriteProviderIds: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    counties: { type: Array, default: () => [] },
    facets: { type: Object, default: () => ({ rating: {}, featured: 0, price: { min: null, max: null } }) },
    filters: { type: Object, required: true },
});

const favoritedProviderIds = computed(() => new Set(props.favoriteProviderIds));

const search = ref(props.filters.q);
const selectedCategories = ref(props.filters.category_ids.map(Number));
const selectedCounties = ref(props.filters.county_ids.map(Number));
const priceMin = ref(props.filters.price_min ?? '');
const priceMax = ref(props.filters.price_max ?? '');
const rating = ref(props.filters.rating ?? 0);
const featuredOnly = ref(props.filters.featured);
const sort = ref(props.filters.sort);

const showMobileFilters = ref(false);
watch(showMobileFilters, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

const activeFilterCount = computed(() => [
    selectedCategories.value.length > 0,
    selectedCounties.value.length > 0,
    Boolean(priceMin.value),
    Boolean(priceMax.value),
    Number(rating.value) > 0,
    Boolean(featuredOnly.value),
].filter(Boolean).length);

const sortOptions = [
    { value: 'recommended', label: 'Recomandate' },
    { value: 'price_asc', label: 'Preț: crescător' },
    { value: 'price_desc', label: 'Preț: descrescător' },
    { value: 'rating', label: 'Rating' },
    { value: 'newest', label: 'Cele mai noi' },
];

const applyFilters = (overrides = {}) => {
    router.get(route('providers.index'), {
        q: search.value || undefined,
        category_ids: selectedCategories.value.length ? selectedCategories.value : undefined,
        county_ids: selectedCounties.value.length ? selectedCounties.value : undefined,
        price_min: priceMin.value || undefined,
        price_max: priceMax.value || undefined,
        rating: rating.value && Number(rating.value) !== 0 ? rating.value : undefined,
        featured: featuredOnly.value || undefined,
        sort: sort.value !== 'recommended' ? sort.value : undefined,
        ...overrides,
    }, { preserveState: true, preserveScroll: true, replace: true, only: ['providers', 'filters', 'stats', 'categories', 'counties', 'facets', 'favoriteProviderIds'] });
};

watch(search, debounce(() => applyFilters({ q: search.value || undefined }), 350));

const setSort = (event) => {
    sort.value = event.target.value;
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    selectedCategories.value = [];
    selectedCounties.value = [];
    priceMin.value = '';
    priceMax.value = '';
    rating.value = 0;
    featuredOnly.value = false;
    sort.value = 'recommended';
    applyFilters();
    showMobileFilters.value = false;
};

const isChipActive = (id) => (id === null ? selectedCategories.value.length === 0 : selectedCategories.value.length === 1 && selectedCategories.value[0] === id);

const selectChip = (id) => {
    selectedCategories.value = id === null ? [] : [id];
    applyFilters();
};

const location = (item) => [item.locality, item.county].filter(Boolean).join(', ');
</script>

<template>
    <Head title="Furnizori — Invita" />

    <div class="overflow-x-clip bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <!-- PAGE HERO -->
            <section class="relative z-30 pb-8 pt-8 lg:pb-10 lg:pt-10">
                <div class="pointer-events-none absolute inset-0 overflow-hidden [mask-image:linear-gradient(to_bottom,#000_55%,transparent)]">
                    <div class="absolute -left-40 -top-40 h-[520px] w-[520px] rounded-full bg-primary/15 blur-3xl animate-float-slow" />
                    <div class="absolute -right-32 top-10 h-[460px] w-[460px] rounded-full bg-ivt-violet/15 blur-3xl animate-float-slower" />
                    <div class="absolute bottom-0 left-1/3 h-[320px] w-[320px] rounded-full bg-ivt-accent-bright/15 blur-3xl animate-float-slower" />
                    <div
                        class="absolute inset-0 opacity-[0.35]"
                        style="background-image: radial-gradient(rgba(26,20,51,0.12) 1px, transparent 1px); background-size: 22px 22px; mask-image: radial-gradient(ellipse 70% 60% at 30% 30%, #000 30%, transparent 75%);"
                    />
                </div>

                <div class="relative mx-auto max-w-[1600px] px-6 lg:px-8">
                    <nav class="flex items-center gap-2 text-[13px] text-ivt-ink-faint" aria-label="Breadcrumb">
                        <Link :href="route('home')" class="transition-colors hover:text-primary">Acasă</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <span class="text-ivt-ink-soft">Furnizori</span>
                    </nav>

                    <div class="mt-8 lg:mt-10">
                        <p class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 py-1 pl-1.5 pr-3.5 text-[12.5px] font-semibold text-ivt-ink-soft shadow-sm backdrop-blur">
                            <span class="rounded-full bg-brand px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider text-white">{{ stats.providersCount }}</span>
                            furnizori activi, {{ stats.categoriesCount }} categorii de servicii
                        </p>

                        <h1 class="mt-6 text-balance font-display text-[34px] font-bold leading-[1.05] tracking-tight text-ivt-ink sm:text-[44px] lg:text-[52px]">
                            Toți furnizorii pentru eveniment,
                            <span class="relative whitespace-nowrap">
                                <span class="text-gradient">la un click distanță.</span>
                                <svg class="absolute -bottom-2 left-0 h-3 w-full text-ivt-accent-bright" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true">
                                    <path d="M2 9 C 80 2, 200 2, 298 7" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                </svg>
                            </span>
                        </h1>

                        <p class="mt-6 max-w-[480px] text-[15.5px] leading-relaxed text-ivt-ink-soft">
                            De la fotografi și formații, până la florării și locații — răsfoiește toți furnizorii verificați și filtrează după categorie, oraș sau buget.
                        </p>

                        <div class="ring-gradient relative z-20 mt-9 grid max-w-[560px] gap-1.5 rounded-[22px] border border-ivt-line bg-white p-2 shadow-ivt-soft transition-shadow focus-within:shadow-ivt-deep hover:shadow-ivt-deep sm:grid-cols-[1fr_auto]">
                            <label class="group flex h-full cursor-text items-center gap-3 rounded-2xl px-4 py-2.5 transition-colors hover:bg-ivt-paper-2">
                                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-ivt-paper-2 text-ivt-violet transition-colors group-focus-within:bg-brand group-focus-within:text-white group-hover:bg-white">
                                    <MagnifyingGlassIcon class="h-[18px] w-[18px]" />
                                </span>
                                <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                                    <span class="text-[10.5px] font-bold uppercase tracking-[0.1em] text-ivt-ink-faint">Furnizor</span>
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="nume, categorie sau zonă…"
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
                            <a href="#furnizori" class="btn-brand whitespace-nowrap rounded-2xl px-7 py-3.5 text-sm font-bold">
                                {{ search ? `${providers.total} rezultate` : 'Vezi furnizorii' }}
                                <ArrowRightIcon class="h-4 w-4" stroke-width="2.5" />
                            </a>
                        </div>

                    </div>
                </div>
            </section>

            <!-- QUICK CATEGORY BAND -->
            <div id="furnizori" v-if="categories.length" class="relative z-20 scroll-mt-24 bg-brand py-4 shadow-glow-primary">
                <div class="group flex overflow-hidden [mask-image:linear-gradient(90deg,transparent,#000_8%,#000_92%,transparent)]">
                    <div class="flex flex-none animate-marquee items-center group-hover:[animation-play-state:paused]">
                        <template v-for="copy in 2" :key="copy">
                            <button
                                type="button"
                                :tabindex="copy === 2 ? -1 : undefined"
                                :aria-hidden="copy === 2 ? 'true' : undefined"
                                @click="selectChip(null)"
                                class="flex items-center gap-2.5 whitespace-nowrap px-6 font-display text-lg font-semibold transition-colors"
                                :class="isChipActive(null) ? 'text-white underline decoration-ivt-accent-light decoration-2 underline-offset-[6px]' : 'text-white/70 hover:text-white'"
                            >
                                Toate categoriile
                                <span class="ml-4 text-white/40 no-underline" aria-hidden="true">✦</span>
                            </button>
                            <button
                                v-for="cat in categories"
                                :key="`${copy}-${cat.id}`"
                                type="button"
                                :tabindex="copy === 2 ? -1 : undefined"
                                :aria-hidden="copy === 2 ? 'true' : undefined"
                                @click="selectChip(cat.id)"
                                class="flex items-center gap-2.5 whitespace-nowrap px-6 font-display text-lg font-semibold transition-colors"
                                :class="isChipActive(cat.id) ? 'text-white underline decoration-ivt-accent-light decoration-2 underline-offset-[6px]' : 'text-white/70 hover:text-white'"
                            >
                                <component :is="categoryIcon(cat.slug)" class="h-5 w-5 text-ivt-accent-light" stroke-width="1.75" />
                                {{ cat.name }}
                                <span class="ml-4 text-white/40" aria-hidden="true">✦</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- LISTING -->
            <section class="py-9 pb-[110px]">
                <div class="mx-auto grid max-w-[1600px] grid-cols-1 gap-9 px-6 lg:grid-cols-[272px_1fr] lg:px-8">

                    <!-- FILTERS -->
                    <aside class="hidden flex-col gap-6 self-start rounded-[18px] border border-ivt-line bg-white p-6 lg:sticky lg:top-24 lg:flex">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold">Filtre</h3>
                            <button type="button" @click="resetFilters" class="text-[12.5px] font-semibold text-primary hover:underline">Resetează</button>
                        </div>

                        <ProviderFilterPanel
                            v-model:selected-categories="selectedCategories"
                            v-model:selected-counties="selectedCounties"
                            v-model:price-min="priceMin"
                            v-model:price-max="priceMax"
                            v-model:rating="rating"
                            v-model:featured-only="featuredOnly"
                            :categories="categories"
                            :counties="counties"
                            :facets="facets"
                            @apply="applyFilters()"
                        />

                        <button
                            type="button"
                            @click="applyFilters()"
                            class="mt-1 w-full rounded-full bg-gradient-to-b from-ivt-ink-2 to-ivt-ink px-5 py-3 text-sm font-semibold text-ivt-paper transition-transform duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(26,20,51,0.4)]"
                        >
                            Aplică filtrele
                        </button>
                    </aside>

                    <!-- RESULTS -->
                    <div>

                        <!-- RECOMMENDED -->
                        <div v-if="recommended.length" class="mb-11">
                            <div class="mb-[18px] flex flex-wrap items-center gap-2.5">
                                <h3 class="font-display text-[18px] font-medium">Recomandate pentru tine</h3>
                                <span class="text-xs text-ivt-ink-faint">cele mai bine cotate de pe platformă</span>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <Link
                                    v-for="item in recommended"
                                    :key="item.id"
                                    :href="route('providers.show', item.slug)"
                                    class="group relative flex flex-col gap-2.5 overflow-hidden rounded-2xl bg-gradient-to-br from-ivt-ink-2 to-ivt-ink p-5 text-ivt-on-dark transition-all duration-300 hover:-translate-y-1.5 hover:shadow-ivt-deep"
                                >
                                    <span class="pointer-events-none absolute -right-[60px] -top-[80px] h-[160px] w-[160px] rounded-full" style="background: radial-gradient(circle, rgba(124,58,237,0.2), transparent 70%);" />
                                    <div class="relative flex items-center justify-between">
                                        <span class="text-[10.5px] font-bold uppercase tracking-[0.06em] text-ivt-on-dark-dim">{{ item.category }}</span>
                                        <span v-if="item.is_featured" class="rounded-full border border-ivt-violet/50 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-[0.08em] text-ivt-accent-bright">Premium</span>
                                    </div>
                                    <h4 class="relative flex items-center gap-1.5 font-display text-[19px] font-medium text-ivt-on-dark">
                                        <span class="truncate">{{ item.company_name }}</span>
                                        <CheckBadgeIcon v-if="item.is_verified" class="h-4 w-4 flex-none text-ivt-accent-bright" title="Verificat ANAF" />
                                    </h4>
                                    <p v-if="location(item)" class="relative flex items-center gap-1 text-[12.5px] text-ivt-on-dark-dim">
                                        <MapPinIcon class="h-3.5 w-3.5 flex-none" /> {{ location(item) }}
                                    </p>
                                    <div class="relative mt-auto flex items-center justify-between border-t border-white/10 pt-3">
                                        <span class="text-[13px] font-semibold text-ivt-on-dark">{{ formatListingPrice(item) }}</span>
                                        <span v-if="item.rating" class="flex items-center gap-1 text-xs text-ivt-accent-bright">
                                            <StarIcon class="h-3.5 w-3.5" /> {{ item.rating }}
                                        </span>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <div class="mb-7 flex flex-wrap items-center justify-between gap-4">
                            <p class="text-[14.5px] text-ivt-ink-soft"><b class="text-ivt-ink">{{ providers.total }}</b> furnizori găsiți</p>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="showMobileFilters = true"
                                    class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white px-4 py-2.5 text-[13.5px] font-semibold text-ivt-ink transition-colors hover:border-ivt-violet hover:text-primary lg:hidden"
                                >
                                    <AdjustmentsHorizontalIcon class="h-4 w-4" />
                                    Filtre
                                    <span
                                        v-if="activeFilterCount"
                                        class="flex h-5 min-w-5 items-center justify-center rounded-full bg-brand px-1 text-[11px] font-bold text-white"
                                    >{{ activeFilterCount }}</span>
                                </button>
                                <select
                                    :value="sort"
                                    @change="setSort"
                                    class="rounded-full border-ivt-line bg-white py-2.5 pl-4 pr-9 text-[13.5px] font-semibold text-ivt-ink focus:border-ivt-violet focus:ring-ivt-violet/30"
                                >
                                    <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- PROVIDER GRID -->
                        <div v-if="providers.data.length" class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <ProviderCard
                                v-for="item in providers.data"
                                :key="item.id"
                                :provider="item"
                                :favorited="favoritedProviderIds.has(item.id)"
                            />
                        </div>

                        <div v-else class="flex flex-col items-center justify-center rounded-2xl border border-ivt-line bg-white px-5 py-16 text-center">
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-ivt-paper-2 text-primary">
                                <FaceFrownIcon class="h-6 w-6" />
                            </span>
                            <p class="mt-3 font-display text-[20px] text-ivt-ink">Niciun furnizor nu corespunde filtrelor tale</p>
                            <p class="mt-1.5 max-w-sm text-sm text-ivt-ink-soft">Încearcă să lărgești bugetul, să elimini o categorie sau să resetezi filtrele.</p>
                        </div>

                        <!-- PAGINATION -->
                        <div v-if="providers.last_page > 1" class="mt-11 flex flex-wrap items-center justify-center gap-1.5">
                            <template v-for="(link, index) in providers.links" :key="index">
                                <button
                                    type="button"
                                    :disabled="!link.url"
                                    @click="link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true, only: ['providers', 'filters'] })"
                                    class="min-w-[2.375rem] rounded-[10px] border border-ivt-line px-3 py-2 text-[13.5px] font-semibold text-ivt-ink-soft transition-colors duration-150 hover:border-ivt-violet hover:text-primary"
                                    :class="[
                                        link.active && 'border-ivt-ink bg-ivt-ink text-ivt-paper hover:border-ivt-ink hover:text-ivt-paper',
                                        !link.url && 'cursor-not-allowed opacity-30 hover:border-ivt-line hover:text-ivt-ink-soft',
                                    ]"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>

                </div>
            </section>
        </main>

        <SiteFooter />

        <!-- MOBILE FILTERS BOTTOM SHEET -->
        <Teleport to="body">
            <div v-if="showMobileFilters" class="fixed inset-0 z-50 lg:hidden">
                <Transition
                    enter-active-class="transition-opacity duration-300 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-200 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                    appear
                >
                    <div class="absolute inset-0 bg-ivt-ink/50" @click="showMobileFilters = false" />
                </Transition>

                <Transition
                    enter-active-class="transition-transform duration-300 ease-out"
                    enter-from-class="translate-y-full"
                    enter-to-class="translate-y-0"
                    leave-active-class="transition-transform duration-200 ease-in"
                    leave-from-class="translate-y-0"
                    leave-to-class="translate-y-full"
                    appear
                >
                    <div class="absolute inset-x-0 bottom-0 flex max-h-[85vh] flex-col rounded-t-[22px] bg-white shadow-ivt-deep">
                        <div class="flex items-center justify-between border-b border-ivt-line px-6 py-4">
                            <h3 class="text-base font-semibold text-ivt-ink">Filtre</h3>
                            <button type="button" @click="showMobileFilters = false" class="flex h-8 w-8 items-center justify-center rounded-full text-ivt-ink-soft hover:bg-ivt-paper-2">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="overflow-y-auto px-6 py-5">
                            <ProviderFilterPanel
                                v-model:selected-categories="selectedCategories"
                                v-model:selected-counties="selectedCounties"
                                v-model:price-min="priceMin"
                                v-model:price-max="priceMax"
                                v-model:rating="rating"
                                v-model:featured-only="featuredOnly"
                                :categories="categories"
                                :counties="counties"
                                :facets="facets"
                                @apply="applyFilters()"
                            />
                        </div>

                        <div class="flex items-center gap-3 border-t border-ivt-line px-6 py-4">
                            <button type="button" @click="resetFilters" class="text-[13px] font-semibold text-primary hover:underline">Resetează</button>
                            <button
                                type="button"
                                @click="applyFilters(); showMobileFilters = false"
                                class="ml-auto flex-1 rounded-full bg-gradient-to-b from-ivt-ink-2 to-ivt-ink px-5 py-3 text-sm font-semibold text-ivt-paper"
                            >
                                Vezi {{ providers.total }} furnizori
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Teleport>
    </div>
</template>
