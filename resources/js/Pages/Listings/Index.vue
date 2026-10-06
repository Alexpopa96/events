<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import debounce from 'lodash/debounce';
import { MagnifyingGlassIcon, FaceFrownIcon, AdjustmentsHorizontalIcon, XMarkIcon, CalendarDaysIcon, BookmarkIcon } from '@heroicons/vue/24/outline';
import { StarIcon as StarIconSolid } from '@heroicons/vue/24/solid';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import ListingCard from '@/Components/Listing/Card.vue';
import CategoryFilterPanel from '@/Components/Categories/CategoryFilterPanel.vue';
import Modal from '@/Components/Modal.vue';
import { categoryIcon } from '@/Composables/useCategoryIcon';

const toast = useToast();
const page = usePage();

const props = defineProps({
    listings: { type: Object, required: true },
    favoriteListingIds: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    counties: { type: Array, default: () => [] },
    eventTypes: { type: Array, default: () => [] },
    facets: { type: Object, default: () => ({ rating: {}, featured: 0, price: { min: null, max: null }, categories: {}, event_types: {} }) },
});

const search = ref(props.filters.q);
const selectedCounties = ref(props.filters.county_ids.map(Number));
const priceMin = ref(props.filters.price_min ?? '');
const priceMax = ref(props.filters.price_max ?? '');
const rating = ref(props.filters.rating ?? 0);
const featuredOnly = ref(props.filters.featured);
const selectedCategories = ref([...props.filters.categories]);
const selectedEventTypes = ref([...props.filters.event_types]);
const availableOn = ref(props.filters.available_on ?? '');

// Top 6 categories by listing count, offered as one-tap chips above the results.
const quickCategories = computed(() => [...props.categories].slice(0, 6));

const toggleQuickCategory = (slug) => {
    const index = selectedCategories.value.indexOf(slug);
    index === -1 ? selectedCategories.value.push(slug) : selectedCategories.value.splice(index, 1);
    applyFilters();
};

const showMobileFilters = ref(false);
watch(showMobileFilters, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

const activeFilterCount = computed(() => [
    selectedCategories.value.length > 0,
    selectedEventTypes.value.length > 0,
    selectedCounties.value.length > 0,
    Boolean(priceMin.value),
    Boolean(priceMax.value),
    Number(rating.value) > 0,
    Boolean(featuredOnly.value),
    Boolean(availableOn.value),
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
        categories: selectedCategories.value.length ? selectedCategories.value : undefined,
        event_types: selectedEventTypes.value.length ? selectedEventTypes.value : undefined,
        county_ids: selectedCounties.value.length ? selectedCounties.value : undefined,
        price_min: priceMin.value || undefined,
        price_max: priceMax.value || undefined,
        rating: rating.value && Number(rating.value) !== 0 ? rating.value : undefined,
        featured: featuredOnly.value || undefined,
        available_on: availableOn.value || undefined,
        sort: props.filters.sort !== 'newest' ? props.filters.sort : undefined,
        ...overrides,
    }, { preserveState: true, preserveScroll: true, replace: true, only: ['listings', 'filters', 'favoriteListingIds', 'counties', 'facets'] });
};

watch(search, debounce(() => applyFilters({ q: search.value || undefined }), 350));

const setSort = (event) => applyFilters({ sort: event.target.value !== 'newest' ? event.target.value : undefined });

const resetFilters = () => {
    search.value = '';
    selectedCounties.value = [];
    priceMin.value = '';
    priceMax.value = '';
    rating.value = 0;
    featuredOnly.value = false;
    selectedCategories.value = [];
    selectedEventTypes.value = [];
    availableOn.value = '';
    applyFilters({ categories: undefined, event_types: undefined, available_on: undefined, sort: undefined });
    showMobileFilters.value = false;
};

/* ---------- saved search ---------- */
const canSaveSearch = computed(() => page.props.auth.can.saveSearch);

const suggestedSearchName = computed(() => {
    const parts = [];
    if (selectedCategories.value.length) parts.push(selectedCategories.value.join(', '));
    if (search.value.trim()) parts.push(`"${search.value.trim()}"`);
    if (priceMax.value) parts.push(`sub ${priceMax.value} lei`);
    return parts.length ? parts.join(' · ') : 'Toate anunțurile';
});

const showSaveSearch = ref(false);
const saveSearchName = ref('');
const savingSearch = ref(false);

const openSaveSearch = () => {
    saveSearchName.value = suggestedSearchName.value;
    showSaveSearch.value = true;
};

const submitSaveSearch = () => {
    if (!saveSearchName.value.trim() || savingSearch.value) return;
    savingSearch.value = true;
    router.post(route('saved-searches.store'), {
        name: saveSearchName.value.trim(),
        q: search.value || undefined,
        categories: selectedCategories.value.length ? selectedCategories.value : undefined,
        event_types: selectedEventTypes.value.length ? selectedEventTypes.value : undefined,
        county_ids: selectedCounties.value.length ? selectedCounties.value : undefined,
        price_min: priceMin.value || undefined,
        price_max: priceMax.value || undefined,
        rating: rating.value && Number(rating.value) !== 0 ? rating.value : undefined,
        featured: featuredOnly.value || undefined,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { showSaveSearch.value = false; toast.success('Căutarea a fost salvată.'); },
        onFinish: () => { savingSearch.value = false; },
    });
};

</script>

<template>
    <ClientLayout title="Anunțuri">
        <div class="space-y-8">
            <!-- Hero + search -->
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary/10 via-white to-primary/5 px-5 py-8 ring-1 ring-primary/10 sm:px-10 sm:py-12">
                <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-primary/10 blur-3xl" />
                <div class="pointer-events-none absolute -bottom-24 left-1/3 h-56 w-56 rounded-full bg-primary/5 blur-3xl" />

                <div class="relative max-w-2xl">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-primary ring-1 ring-primary/15 backdrop-blur">
                        <span class="h-1.5 w-1.5 rounded-full bg-primary" />
                        {{ listings.total }} anunțuri de la furnizori verificați
                    </span>
                    <h1 class="mt-4 font-serif text-3xl leading-tight text-ink sm:text-4xl">Găsește furnizorul potrivit pentru evenimentul tău</h1>
                </div>

                <div class="relative mt-6 flex max-w-2xl flex-wrap items-center gap-2">
                    <div class="relative min-w-[14rem] flex-1">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-primary/60" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Caută fotograf, DJ, restaurant…"
                            class="w-full rounded-full border-0 bg-white py-4 pl-12 pr-4 text-sm text-ink shadow-lg shadow-primary/10 ring-1 ring-line transition-shadow duration-150 placeholder:text-ink-soft/50 focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>
                    <div class="relative">
                        <CalendarDaysIcon class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-primary/60" />
                        <input
                            v-model="availableOn"
                            type="date"
                            :min="new Date().toISOString().slice(0, 10)"
                            title="Disponibil pe data"
                            @change="applyFilters()"
                            class="w-[10.5rem] rounded-full border-0 bg-white py-4 pl-12 pr-4 text-sm text-ink shadow-lg shadow-primary/10 ring-1 ring-line transition-shadow duration-150 focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>
                    <button
                        v-if="availableOn"
                        type="button"
                        @click="availableOn = ''; applyFilters({ available_on: undefined });"
                        class="flex h-[52px] w-[52px] flex-none items-center justify-center rounded-full bg-white text-ink-soft shadow-lg shadow-primary/10 ring-1 ring-line transition-colors hover:text-primary"
                        title="Șterge filtrul de dată"
                    >
                        <XMarkIcon class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        @click="showMobileFilters = true"
                        class="relative flex h-[52px] flex-none items-center gap-2 rounded-full bg-white px-4 text-sm font-semibold text-ink shadow-lg shadow-primary/10 ring-1 ring-line transition-colors duration-150 hover:ring-primary/40 lg:hidden"
                    >
                        <AdjustmentsHorizontalIcon class="h-[18px] w-[18px] text-primary" />
                        <span class="hidden sm:inline">Filtre</span>
                        <span v-if="activeFilterCount" class="flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1 text-[11px] font-bold text-white">{{ activeFilterCount }}</span>
                    </button>
                </div>
            </section>

            <!-- Quick filter chips: one tap for the filters people reach for most, without opening the full panel -->
            <div class="-mt-2 flex flex-wrap gap-2 overflow-x-auto pb-1">
                <button
                    v-for="category in quickCategories"
                    :key="category.slug"
                    type="button"
                    @click="toggleQuickCategory(category.slug)"
                    class="inline-flex flex-none items-center gap-1.5 rounded-full px-3.5 py-2 text-[13px] font-medium transition-colors duration-150"
                    :class="selectedCategories.includes(category.slug)
                        ? 'bg-primary text-white shadow-sm shadow-primary/25'
                        : 'bg-white text-ink-soft ring-1 ring-line hover:ring-primary/40'"
                >
                    <component :is="categoryIcon(category.slug)" class="h-3.5 w-3.5" />
                    {{ category.name }}
                </button>
                <button
                    type="button"
                    @click="featuredOnly = !featuredOnly; applyFilters()"
                    class="inline-flex flex-none items-center gap-1.5 rounded-full px-3.5 py-2 text-[13px] font-medium transition-colors duration-150"
                    :class="featuredOnly ? 'bg-primary text-white shadow-sm shadow-primary/25' : 'bg-white text-ink-soft ring-1 ring-line hover:ring-primary/40'"
                >
                    Premium
                </button>
                <button
                    type="button"
                    @click="rating = Number(rating) === 4.5 ? 0 : 4.5; applyFilters()"
                    class="inline-flex flex-none items-center gap-1.5 rounded-full px-3.5 py-2 text-[13px] font-medium transition-colors duration-150"
                    :class="Number(rating) === 4.5 ? 'bg-primary text-white shadow-sm shadow-primary/25' : 'bg-white text-ink-soft ring-1 ring-line hover:ring-primary/40'"
                >
                    <StarIconSolid class="h-3.5 w-3.5" /> 4.5+
                </button>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-[260px_1fr]">
                <!-- FILTERS -->
                <aside class="hidden flex-col gap-6 self-start rounded-3xl bg-white p-6 shadow-xl shadow-ink/5 ring-1 ring-line lg:sticky lg:top-24 lg:flex">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-ink">Filtre</h3>
                        <button type="button" @click="resetFilters" class="text-[12.5px] font-semibold text-primary-bright hover:underline">Resetează</button>
                    </div>

                    <CategoryFilterPanel
                        v-model:selected-categories="selectedCategories"
                        v-model:selected-event-types="selectedEventTypes"
                        :categories="categories"
                        :event-types="eventTypes"
                        v-model:selected-counties="selectedCounties"
                        v-model:price-min="priceMin"
                        v-model:price-max="priceMax"
                        v-model:rating="rating"
                        v-model:featured-only="featuredOnly"
                        :counties="counties"
                        :facets="facets"
                        @apply="applyFilters()"
                    />
                </aside>

                <!-- RESULTS -->
                <div>
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
                        <p class="text-sm text-ink-soft">
                            <span class="font-semibold text-ink">{{ listings.total }}</span> rezultate
                            <span v-if="availableOn">disponibile pe {{ new Date(availableOn).toLocaleDateString('ro-RO', { day: 'numeric', month: 'long' }) }}</span>
                        </p>
                        <div class="flex items-center gap-2.5">
                            <button
                                v-if="canSaveSearch && activeFilterCount > 0"
                                type="button"
                                @click="openSaveSearch"
                                class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2.5 text-sm font-semibold text-ink shadow-sm ring-1 ring-line transition-colors duration-150 hover:ring-primary/40"
                            >
                                <BookmarkIcon class="h-4 w-4 text-primary" /> Salvează căutarea
                            </button>
                            <select
                                :value="filters.sort"
                                @change="setSort"
                                class="rounded-full border-0 bg-white py-2.5 pl-4 pr-10 text-sm text-ink shadow-sm ring-1 ring-line transition-shadow duration-150 hover:ring-primary/40 focus:ring-2 focus:ring-primary sm:w-56"
                            >
                                <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="listings.data.length" class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        <ListingCard
                            v-for="listing in listings.data"
                            :key="listing.id"
                            :listing="listing"
                            :favorited="favoriteListingIds.includes(listing.id)"
                        />
                    </div>
                    <div v-else class="flex flex-col items-center justify-center rounded-3xl bg-white px-5 py-20 text-center ring-1 ring-line">
                        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary">
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
                                class="min-w-[2.5rem] rounded-full px-3.5 py-2 text-sm font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                                :class="[
                                    link.active ? 'bg-primary text-white shadow-sm shadow-primary/25' : 'text-ink-soft hover:bg-white hover:ring-1 hover:ring-line',
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
                <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm" @click="showMobileFilters = false" />

                <div class="absolute inset-x-0 bottom-0 flex max-h-[85vh] flex-col rounded-t-3xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-line px-6 py-4">
                        <h3 class="text-base font-semibold text-ink">Filtre</h3>
                        <button type="button" @click="showMobileFilters = false" class="flex h-8 w-8 items-center justify-center rounded-full text-ink-soft hover:bg-paper">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="overflow-y-auto px-6 py-5">
                        <CategoryFilterPanel
                            v-model:selected-categories="selectedCategories"
                            v-model:selected-event-types="selectedEventTypes"
                            :categories="categories"
                            :event-types="eventTypes"
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
                        <button type="button" @click="resetFilters" class="text-[13px] font-semibold text-primary-bright hover:underline">Resetează</button>
                        <button
                            type="button"
                            @click="applyFilters(); showMobileFilters = false"
                            class="ml-auto flex-1 rounded-full bg-primary px-5 py-3 text-sm font-semibold text-white"
                        >
                            Vezi {{ listings.total }} anunțuri
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Modal :show="showSaveSearch" max-width="md" @close="showSaveSearch = false">
            <div class="p-6">
                <h3 class="font-serif text-lg text-ink">Salvează această căutare</h3>
                <p class="mt-1.5 text-sm text-ink-soft">Te anunțăm prin email de îndată ce apare un anunț nou care se potrivește filtrelor curente.</p>

                <label for="saved-search-name" class="mb-1.5 mt-4 block text-sm font-medium text-ink">Nume</label>
                <input
                    id="saved-search-name"
                    v-model="saveSearchName"
                    type="text"
                    maxlength="100"
                    class="w-full rounded-xl border-line text-sm text-ink focus:border-primary focus:ring-primary"
                    @keyup.enter="submitSaveSearch"
                />

                <div class="mt-5 flex items-center justify-end gap-3">
                    <button type="button" class="rounded-xl px-4 py-2 text-sm font-medium text-ink-soft transition-colors hover:bg-primary/5" @click="showSaveSearch = false">
                        Anulează
                    </button>
                    <button
                        type="button"
                        :disabled="savingSearch || !saveSearchName.trim()"
                        class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary/25 transition-colors hover:bg-primary-bright disabled:cursor-not-allowed disabled:opacity-50"
                        @click="submitSaveSearch"
                    >
                        Salvează
                    </button>
                </div>
            </div>
        </Modal>
    </ClientLayout>
</template>
