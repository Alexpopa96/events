<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { MagnifyingGlassIcon, FaceFrownIcon, AdjustmentsHorizontalIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import ListingCard from '@/Components/Listing/Card.vue';
import CategoryFilterPanel from '@/Components/Categories/CategoryFilterPanel.vue';
import { categoryIcon } from '@/Composables/useCategoryIcon';

const props = defineProps({
    listings: { type: Object, required: true },
    favoriteListingIds: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    counties: { type: Array, default: () => [] },
    facets: { type: Object, default: () => ({ rating: {}, featured: 0, price: { min: null, max: null }, categories: {} }) },
});

const search = ref(props.filters.q);
const selectedCounties = ref(props.filters.county_ids.map(Number));
const priceMin = ref(props.filters.price_min ?? '');
const priceMax = ref(props.filters.price_max ?? '');
const rating = ref(props.filters.rating ?? 0);
const featuredOnly = ref(props.filters.featured);
const activeCategory = ref(props.filters.category);

const showMobileFilters = ref(false);
watch(showMobileFilters, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

const activeFilterCount = computed(() => [
    selectedCounties.value.length > 0,
    Boolean(priceMin.value),
    Boolean(priceMax.value),
    Number(rating.value) > 0,
    Boolean(featuredOnly.value),
].filter(Boolean).length);

const sortOptions = [
    { value: 'newest', label: 'Cele mai noi' },
    { value: 'rating', label: 'Cele mai bine cotate' },
    { value: 'price_asc', label: 'Preț crescător' },
    { value: 'price_desc', label: 'Preț descrescător' },
];

const applyFilters = (overrides = {}) => {
    router.get(route('listings.index'), {
        q: search.value || undefined,
        category: activeCategory.value || undefined,
        county_ids: selectedCounties.value.length ? selectedCounties.value : undefined,
        price_min: priceMin.value || undefined,
        price_max: priceMax.value || undefined,
        rating: rating.value && Number(rating.value) !== 0 ? rating.value : undefined,
        featured: featuredOnly.value || undefined,
        sort: props.filters.sort !== 'newest' ? props.filters.sort : undefined,
        ...overrides,
    }, { preserveState: true, preserveScroll: true, replace: true, only: ['listings', 'filters', 'favoriteListingIds', 'counties', 'facets'] });
};

watch(search, debounce(() => applyFilters({ q: search.value || undefined }), 350));

const toggleCategory = (slug) => {
    activeCategory.value = activeCategory.value === slug ? null : slug;
    applyFilters({ category: activeCategory.value || undefined });
};

const setSort = (event) => applyFilters({ sort: event.target.value !== 'newest' ? event.target.value : undefined });

const resetFilters = () => {
    search.value = '';
    selectedCounties.value = [];
    priceMin.value = '';
    priceMax.value = '';
    rating.value = 0;
    featuredOnly.value = false;
    activeCategory.value = null;
    applyFilters({ category: undefined, sort: undefined });
    showMobileFilters.value = false;
};

const categoryCount = (categoryId) => props.facets.categories?.[categoryId] ?? 0;
</script>

<template>
    <ClientLayout title="Anunțuri">
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="font-serif text-2xl text-ink sm:text-3xl">Găsește furnizorul potrivit</h1>
                    <p class="mt-1 text-sm text-ink-soft">{{ listings.total }} anunțuri de la furnizori verificați</p>
                </div>
                <button
                    type="button"
                    @click="showMobileFilters = true"
                    class="relative flex items-center gap-2 rounded-full border border-line bg-white px-4 py-2.5 text-sm font-semibold text-ink shadow-sm shadow-ink/5 lg:hidden"
                >
                    <AdjustmentsHorizontalIcon class="h-[18px] w-[18px] text-ink-soft" />
                    Filtre
                    <span v-if="activeFilterCount" class="flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-500 px-1 text-[11px] font-bold text-white">{{ activeFilterCount }}</span>
                </button>
            </div>

            <!-- Search -->
            <div class="relative">
                <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-soft/50" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Caută fotograf, DJ, restaurant…"
                    class="w-full rounded-2xl border border-line bg-white py-3.5 pl-12 pr-4 text-sm text-ink shadow-sm shadow-ink/5 transition-all duration-150 placeholder:text-ink-soft/50 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-400/20"
                />
            </div>

            <!-- Category chips -->
            <div
                class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0"
                style="mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent); -webkit-mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);"
            >
                <button
                    v-for="cat in categories"
                    :key="cat.slug"
                    type="button"
                    @click="toggleCategory(cat.slug)"
                    class="inline-flex flex-none items-center gap-1.5 rounded-full px-3.5 py-2 text-sm font-medium transition-all duration-150"
                    :class="activeCategory === cat.slug
                        ? 'bg-brand-500 text-white shadow-sm shadow-brand-500/25'
                        : 'bg-white text-ink-soft border border-line hover:border-brand-200 hover:text-brand-600'"
                >
                    <component :is="categoryIcon(cat.slug)" class="h-4 w-4" />
                    {{ cat.name }}
                    <span class="text-xs opacity-70">{{ categoryCount(cat.id) }}</span>
                </button>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-[260px_1fr]">
                <!-- FILTERS -->
                <aside class="hidden flex-col gap-6 self-start rounded-2xl border border-line bg-white p-6 lg:sticky lg:top-24 lg:flex">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-ink">Filtre</h3>
                        <button type="button" @click="resetFilters" class="text-[12.5px] font-semibold text-brand-600 hover:underline">Resetează</button>
                    </div>

                    <CategoryFilterPanel
                        v-model:selected-counties="selectedCounties"
                        v-model:price-min="priceMin"
                        v-model:price-max="priceMax"
                        v-model:rating="rating"
                        v-model:featured-only="featuredOnly"
                        :counties="counties"
                        :facets="facets"
                        @apply="applyFilters()"
                    />

                    <button
                        type="button"
                        @click="applyFilters()"
                        class="mt-1 w-full rounded-full bg-brand-500 px-5 py-3 text-sm font-semibold text-white transition-transform duration-200 hover:-translate-y-0.5 hover:shadow-sm hover:shadow-brand-500/25"
                    >
                        Aplică filtrele
                    </button>
                </aside>

                <!-- RESULTS -->
                <div>
                    <div class="mb-5 flex flex-wrap items-center justify-end gap-4">
                        <select
                            :value="filters.sort"
                            @change="setSort"
                            class="rounded-xl border-line bg-white text-sm text-ink shadow-sm shadow-ink/5 transition-colors duration-150 hover:border-brand-300 focus:border-brand-400 focus:ring-brand-400/20 sm:w-56"
                        >
                            <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>

                    <div v-if="listings.data.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <ListingCard
                            v-for="listing in listings.data"
                            :key="listing.id"
                            :listing="listing"
                            :favorited="favoriteListingIds.includes(listing.id)"
                        />
                    </div>
                    <div v-else class="flex flex-col items-center justify-center rounded-2xl border border-line bg-white px-5 py-16 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                            <FaceFrownIcon class="h-6 w-6" />
                        </span>
                        <p class="mt-3 text-sm font-medium text-ink">Niciun anunț găsit</p>
                        <p class="mt-1 text-sm text-ink-soft">Încearcă alți termeni de căutare sau alte filtre.</p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="listings.last_page > 1" class="flex flex-wrap items-center justify-center gap-1.5 pt-6">
                        <template v-for="(link, index) in listings.links" :key="index">
                            <button
                                type="button"
                                :disabled="!link.url"
                                @click="link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true, only: ['listings', 'filters', 'favoriteListingIds'] })"
                                class="min-w-[2.25rem] rounded-xl px-3 py-2 text-sm font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-400/40"
                                :class="[
                                    link.active ? 'bg-brand-500 text-white shadow-sm shadow-brand-500/25' : 'text-ink-soft hover:bg-white',
                                    !link.url && 'opacity-30 cursor-not-allowed',
                                ]"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- MOBILE FILTERS BOTTOM SHEET -->
        <Teleport to="body">
            <div v-if="showMobileFilters" class="fixed inset-0 z-50 lg:hidden">
                <div class="absolute inset-0 bg-ink/50" @click="showMobileFilters = false" />

                <div class="absolute inset-x-0 bottom-0 flex max-h-[85vh] flex-col rounded-t-3xl bg-white shadow-lg">
                    <div class="flex items-center justify-between border-b border-line px-6 py-4">
                        <h3 class="text-base font-semibold text-ink">Filtre</h3>
                        <button type="button" @click="showMobileFilters = false" class="flex h-8 w-8 items-center justify-center rounded-full text-ink-soft hover:bg-paper">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="overflow-y-auto px-6 py-5">
                        <CategoryFilterPanel
                            v-model:selected-counties="selectedCounties"
                            v-model:price-min="priceMin"
                            v-model:price-max="priceMax"
                            v-model:rating="rating"
                            v-model:featured-only="featuredOnly"
                            :counties="counties"
                            :facets="facets"
                            @apply="applyFilters()"
                        />
                    </div>

                    <div class="flex items-center gap-3 border-t border-line px-6 py-4">
                        <button type="button" @click="resetFilters" class="text-[13px] font-semibold text-brand-600 hover:underline">Resetează</button>
                        <button
                            type="button"
                            @click="applyFilters(); showMobileFilters = false"
                            class="ml-auto flex-1 rounded-full bg-brand-500 px-5 py-3 text-sm font-semibold text-white"
                        >
                            Vezi {{ listings.total }} anunțuri
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </ClientLayout>
</template>
