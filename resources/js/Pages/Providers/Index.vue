<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { MagnifyingGlassIcon, MapPinIcon, FaceFrownIcon, AdjustmentsHorizontalIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/24/solid';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import ProviderCard from '@/Components/Providers/ProviderCard.vue';
import ProviderFilterPanel from '@/Components/Providers/ProviderFilterPanel.vue';
import { formatListingPrice } from '@/Composables/useListingPrice';

const props = defineProps({
    stats: { type: Object, required: true },
    providers: { type: Object, required: true },
    recommended: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    counties: { type: Array, default: () => [] },
    facets: { type: Object, default: () => ({ rating: {}, featured: 0, price: { min: null, max: null } }) },
    filters: { type: Object, required: true },
});

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
    }, { preserveState: true, preserveScroll: true, replace: true, only: ['providers', 'filters', 'stats', 'categories', 'counties', 'facets'] });
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

    <div class="bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <!-- PAGE HERO -->
            <section class="relative overflow-hidden border-b border-ivt-line bg-ivt-paper-2 py-12 sm:py-14">
                <div
                    class="pointer-events-none absolute inset-x-[-10%] -top-[30%] h-[130%]"
                    style="background: radial-gradient(50% 60% at 15% 0%, rgba(168,127,46,0.10), transparent 60%), radial-gradient(45% 45% at 95% 10%, rgba(124,46,59,0.08), transparent 60%);"
                />

                <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
                    <p class="mb-5 flex items-center gap-2 text-[13px] text-ivt-ink-faint">
                        <Link :href="route('home')" class="text-ivt-ink-soft transition-colors hover:text-ivt-wine">Acasă</Link>
                        <span>›</span>
                        <span>Furnizori</span>
                    </p>

                    <p class="flex items-center gap-2.5 text-xs font-bold uppercase tracking-[0.18em] text-ivt-wine">
                        <span class="inline-block h-px w-[22px] bg-ivt-gold" /> Furnizori
                    </p>
                    <h1 class="mt-2 max-w-[640px] font-serif text-[clamp(30px,3.6vw,44px)] font-medium leading-[1.05] tracking-[-0.01em] text-ivt-ink">
                        Toți furnizorii de servicii pentru evenimente.
                    </h1>
                    <p class="mt-3 max-w-[520px] text-[15.5px] leading-relaxed text-ivt-ink-soft">
                        De la fotografi și formații, până la florării și locații — răsfoiește toți furnizorii verificați și filtrează după categorie, oraș sau buget.
                    </p>

                    <div class="relative mt-7 flex flex-wrap gap-2.5">
                        <div class="flex min-w-[260px] flex-1 items-center gap-2.5 rounded-full border border-ivt-line bg-white p-1 pl-4 shadow-ivt-soft">
                            <MagnifyingGlassIcon class="h-[18px] w-[18px] flex-none text-ivt-ink-faint" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Caută după nume, categorie sau zonă — ex. „Studio Lumina”…"
                                class="w-full border-none bg-transparent px-1 py-3 text-[14.5px] font-medium text-ivt-ink placeholder:text-ivt-ink-faint focus:outline-none focus:ring-0"
                            />
                        </div>
                        <button
                            type="button"
                            @click="showMobileFilters = true"
                            class="relative flex flex-none items-center gap-2 rounded-full border border-ivt-line bg-white px-4 py-3 text-[13.5px] font-semibold text-ivt-ink shadow-ivt-soft lg:hidden"
                        >
                            <AdjustmentsHorizontalIcon class="h-[18px] w-[18px] text-ivt-ink-faint" />
                            Filtre
                            <span
                                v-if="activeFilterCount"
                                class="flex h-5 min-w-5 items-center justify-center rounded-full bg-ivt-wine px-1 text-[11px] font-bold text-white"
                            >{{ activeFilterCount }}</span>
                        </button>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-7">
                        <div class="stat">
                            <b class="block font-serif text-[22px] font-semibold text-ivt-ink">{{ stats.providersCount }}</b>
                            <span class="text-xs text-ivt-ink-faint">furnizori activi</span>
                        </div>
                        <div class="stat">
                            <b class="block font-serif text-[22px] font-semibold text-ivt-ink">{{ stats.categoriesCount }}</b>
                            <span class="text-xs text-ivt-ink-faint">categorii</span>
                        </div>
                        <div class="stat">
                            <b class="block font-serif text-[22px] font-semibold text-ivt-ink">{{ stats.avgRating ?? '—' }}</b>
                            <span class="text-xs text-ivt-ink-faint">rating mediu platformă</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- QUICK CATEGORY CHIPS -->
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="-mx-1 flex gap-2.5 overflow-x-auto px-1 py-5">
                    <button
                        type="button"
                        @click="selectChip(null)"
                        class="flex-none whitespace-nowrap rounded-full border px-[18px] py-2.5 text-[13.5px] font-semibold transition-colors duration-150"
                        :class="isChipActive(null) ? 'border-ivt-ink bg-ivt-ink text-ivt-paper' : 'border-ivt-line text-ivt-ink-soft hover:border-ivt-gold hover:text-ivt-wine'"
                    >
                        Toate categoriile
                    </button>
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        type="button"
                        @click="selectChip(cat.id)"
                        class="flex-none whitespace-nowrap rounded-full border px-[18px] py-2.5 text-[13.5px] font-semibold transition-colors duration-150"
                        :class="isChipActive(cat.id) ? 'border-ivt-ink bg-ivt-ink text-ivt-paper' : 'border-ivt-line text-ivt-ink-soft hover:border-ivt-gold hover:text-ivt-wine'"
                    >
                        {{ cat.name }}
                    </button>
                </div>
            </div>

            <!-- LISTING -->
            <section class="py-9 pb-[110px]">
                <div class="mx-auto grid max-w-7xl grid-cols-1 gap-9 px-6 lg:grid-cols-[272px_1fr] lg:px-8">

                    <!-- FILTERS -->
                    <aside class="hidden flex-col gap-6 self-start rounded-[18px] border border-ivt-line bg-white p-6 lg:sticky lg:top-24 lg:flex">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold">Filtre</h3>
                            <button type="button" @click="resetFilters" class="text-[12.5px] font-semibold text-ivt-wine hover:underline">Resetează</button>
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
                            class="mt-1 w-full rounded-full bg-gradient-to-b from-ivt-ink-2 to-ivt-ink px-5 py-3 text-sm font-semibold text-ivt-paper transition-transform duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(22,40,31,0.4)]"
                        >
                            Aplică filtrele
                        </button>
                    </aside>

                    <!-- RESULTS -->
                    <div>

                        <!-- RECOMMENDED -->
                        <div v-if="recommended.length" class="mb-11">
                            <div class="mb-[18px] flex flex-wrap items-center gap-2.5">
                                <h3 class="font-serif text-[18px] font-medium">Recomandate pentru tine</h3>
                                <span class="text-xs text-ivt-ink-faint">cele mai bine cotate de pe platformă</span>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <Link
                                    v-for="item in recommended"
                                    :key="item.id"
                                    :href="route('providers.show', item.slug)"
                                    class="group relative flex flex-col gap-2.5 overflow-hidden rounded-2xl bg-gradient-to-br from-ivt-ink-2 to-ivt-ink p-5 text-ivt-on-dark transition-all duration-300 hover:-translate-y-1.5 hover:shadow-ivt-deep"
                                >
                                    <span class="pointer-events-none absolute -right-[60px] -top-[80px] h-[160px] w-[160px] rounded-full" style="background: radial-gradient(circle, rgba(201,162,79,0.2), transparent 70%);" />
                                    <div class="relative flex items-center justify-between">
                                        <span class="text-[10.5px] font-bold uppercase tracking-[0.06em] text-ivt-on-dark-dim">{{ item.category }}</span>
                                        <span v-if="item.is_featured" class="rounded-full border border-ivt-gold/50 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-[0.08em] text-ivt-gold-bright">Premium</span>
                                    </div>
                                    <h4 class="relative font-serif text-[19px] font-medium text-ivt-on-dark">{{ item.company_name }}</h4>
                                    <p v-if="location(item)" class="relative flex items-center gap-1 text-[12.5px] text-ivt-on-dark-dim">
                                        <MapPinIcon class="h-3.5 w-3.5 flex-none" /> {{ location(item) }}
                                    </p>
                                    <div class="relative mt-auto flex items-center justify-between border-t border-white/10 pt-3">
                                        <span class="text-[13px] font-semibold text-ivt-on-dark">{{ formatListingPrice(item) }}</span>
                                        <span v-if="item.rating" class="flex items-center gap-1 text-xs text-ivt-gold-bright">
                                            <StarIcon class="h-3.5 w-3.5" /> {{ item.rating }}
                                        </span>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <div class="mb-7 flex flex-wrap items-center justify-between gap-4">
                            <p class="text-[14.5px] text-ivt-ink-soft"><b class="text-ivt-ink">{{ providers.total }}</b> furnizori găsiți</p>
                            <select
                                :value="sort"
                                @change="setSort"
                                class="rounded-full border-ivt-line bg-white py-2.5 pl-4 pr-9 text-[13.5px] font-semibold text-ivt-ink focus:border-ivt-gold focus:ring-ivt-gold/30"
                            >
                                <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>

                        <!-- PROVIDER GRID -->
                        <div v-if="providers.data.length" class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <ProviderCard
                                v-for="item in providers.data"
                                :key="item.id"
                                :provider="item"
                            />
                        </div>

                        <div v-else class="flex flex-col items-center justify-center rounded-2xl border border-ivt-line bg-white px-5 py-16 text-center">
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-ivt-paper-2 text-ivt-wine">
                                <FaceFrownIcon class="h-6 w-6" />
                            </span>
                            <p class="mt-3 font-serif text-[20px] italic text-ivt-ink">Niciun furnizor nu corespunde filtrelor tale</p>
                            <p class="mt-1.5 max-w-sm text-sm text-ivt-ink-soft">Încearcă să lărgești bugetul, să elimini o categorie sau să resetezi filtrele.</p>
                        </div>

                        <!-- PAGINATION -->
                        <div v-if="providers.last_page > 1" class="mt-11 flex flex-wrap items-center justify-center gap-1.5">
                            <template v-for="(link, index) in providers.links" :key="index">
                                <button
                                    type="button"
                                    :disabled="!link.url"
                                    @click="link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true, only: ['providers', 'filters'] })"
                                    class="min-w-[2.375rem] rounded-[10px] border border-ivt-line px-3 py-2 text-[13.5px] font-semibold text-ivt-ink-soft transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
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
                            <button type="button" @click="resetFilters" class="text-[13px] font-semibold text-ivt-wine hover:underline">Resetează</button>
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
