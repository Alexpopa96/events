<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import debounce from 'lodash/debounce';
import { MagnifyingGlassIcon, FaceFrownIcon, AdjustmentsHorizontalIcon, XMarkIcon, BookmarkIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import ListingCard from '@/Components/Listing/Card.vue';
import CategoryFilterPanel from '@/Components/Categories/CategoryFilterPanel.vue';
import Modal from '@/Components/Modal.vue';
import DateField from '@/Components/DateField.vue';
import Pagination from '@/Components/Pagination.vue';

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
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['listings', 'filters', 'favoriteListingIds', 'counties', 'facets'],
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
};

const loading = ref(false);

const goToPage = (url) => {
    if (!url) return;
    router.get(url, {}, {
        preserveState: true,
        only: ['listings', 'filters', 'favoriteListingIds'],
        onStart: () => (loading.value = true),
        onFinish: () => {
            loading.value = false;
            document.getElementById('rezultate')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },
    });
};

watch(search, debounce(() => applyFilters({ q: search.value || undefined }), 350));

const setSort = (event) => applyFilters({ sort: event.target.value !== 'newest' ? event.target.value : undefined });

/* ---------- hero context + active filter pills ---------- */
const countyName = (id) => props.counties.find((county) => county.id === id)?.name;

const selectedCountyNames = computed(() => selectedCounties.value.map(countyName).filter(Boolean));

const heroTitle = computed(() => {
    const names = selectedCountyNames.value;
    if (names.length === 1) return { lead: 'Furnizori în', accent: names[0] };
    if (names.length > 1) return { lead: 'Furnizori în', accent: `${names.length} județe` };
    return { lead: 'Găsește furnizorul', accent: 'potrivit.' };
});

const formatDate = (value) => new Date(value).toLocaleDateString('ro-RO', { day: 'numeric', month: 'long' });

const removeFrom = (list, value) => list.filter((item) => item !== value);

const activePills = computed(() => [
    ...selectedCounties.value.map((id) => ({
        key: `county-${id}`,
        label: countyName(id) ?? 'Județ',
        clear: () => (selectedCounties.value = removeFrom(selectedCounties.value, id)),
    })),
    ...selectedCategories.value.map((slug) => ({
        key: `category-${slug}`,
        label: props.categories.find((category) => category.slug === slug)?.name ?? slug,
        clear: () => (selectedCategories.value = removeFrom(selectedCategories.value, slug)),
    })),
    ...selectedEventTypes.value.map((value) => ({
        key: `event-${value}`,
        label: props.eventTypes.find((type) => type.value === value)?.label ?? value,
        clear: () => (selectedEventTypes.value = removeFrom(selectedEventTypes.value, value)),
    })),
    (priceMin.value || priceMax.value) && {
        key: 'price',
        label: priceMin.value && priceMax.value
            ? `${priceMin.value}–${priceMax.value} lei`
            : priceMin.value ? `peste ${priceMin.value} lei` : `sub ${priceMax.value} lei`,
        clear: () => { priceMin.value = ''; priceMax.value = ''; },
    },
    Number(rating.value) > 0 && { key: 'rating', label: `Rating ${rating.value}+`, clear: () => (rating.value = 0) },
    featuredOnly.value && { key: 'featured', label: 'Premium', clear: () => (featuredOnly.value = false) },
    availableOn.value && { key: 'date', label: `Liber pe ${formatDate(availableOn.value)}`, clear: () => (availableOn.value = '') },
].filter(Boolean));

const clearPill = (pill) => {
    pill.clear();
    applyFilters();
};

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
    <ClientLayout :title="$page.props.seo?.full_title ?? 'Anunțuri'" full-bleed>
        <div class="overflow-x-clip bg-ivt-paper font-invita text-ivt-ink antialiased">
            <!-- Hero + search -->
            <section class="relative z-30 pb-4 pt-8 sm:pb-8 lg:pb-10 lg:pt-10">
                <div class="pointer-events-none absolute inset-0 overflow-hidden [mask-image:linear-gradient(to_bottom,#000_55%,transparent)]">
                    <div class="absolute -left-40 -top-40 h-[520px] w-[520px] rounded-full bg-primary/15 blur-3xl animate-float-slow" />
                    <div class="absolute -right-32 top-10 h-[460px] w-[460px] rounded-full bg-ivt-violet/15 blur-3xl animate-float-slower" />
                    <div class="absolute bottom-0 left-1/3 h-[320px] w-[320px] rounded-full bg-ivt-accent-bright/15 blur-3xl animate-float-slower" />
                    <div
                        class="absolute inset-0 opacity-[0.35]"
                        style="background-image: radial-gradient(rgba(26,20,51,0.12) 1px, transparent 1px); background-size: 22px 22px; mask-image: radial-gradient(ellipse 70% 60% at 30% 30%, #000 30%, transparent 75%);"
                    />
                </div>

                <div class="relative mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">
                    <nav class="flex items-center gap-2 text-[13px] text-ivt-ink-faint" aria-label="Breadcrumb">
                        <Link :href="route('home')" class="transition-colors hover:text-primary">Acasă</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <Link v-if="selectedCountyNames.length" :href="route('listings.index')" class="transition-colors hover:text-primary">Anunțuri</Link>
                        <span v-else class="text-ivt-ink-soft">Anunțuri</span>
                        <template v-if="selectedCountyNames.length === 1">
                            <span aria-hidden="true" class="opacity-50">/</span>
                            <span class="text-ivt-ink-soft">{{ selectedCountyNames[0] }}</span>
                        </template>
                    </nav>

                    <div class="mt-8 min-w-0 lg:mt-10">
                        <p class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 py-1 pl-1.5 pr-3.5 text-[12.5px] font-semibold text-ivt-ink-soft shadow-sm backdrop-blur">
                            <span class="rounded-full bg-brand px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider text-white">{{ listings.total }}</span>
                            {{ listings.total === 1 ? 'anunț' : 'anunțuri' }} de la furnizori verificați
                        </p>

                        <h1 class="mt-6 text-balance font-display text-[34px] font-bold leading-[1.05] tracking-tight text-ivt-ink sm:text-[44px] lg:text-[52px]">
                            {{ heroTitle.lead }}
                            <span class="relative whitespace-nowrap">
                                <span class="text-gradient">{{ heroTitle.accent }}</span>
                                <svg class="absolute -bottom-2 left-0 h-3 w-full text-ivt-accent-bright" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true">
                                    <path d="M2 9 C 80 2, 200 2, 298 7" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                </svg>
                            </span>
                        </h1>

                        <p class="mt-6 max-w-[480px] text-[15.5px] leading-relaxed text-ivt-ink-soft">
                            Caută după serviciu sau după data evenimentului și compară ofertele furnizorilor verificați.
                        </p>

                        <div class="ring-gradient relative z-20 mt-9 grid max-w-[640px] gap-1.5 rounded-[22px] border border-ivt-line bg-white p-2 shadow-ivt-soft transition-shadow focus-within:shadow-ivt-deep hover:shadow-ivt-deep sm:grid-cols-[1fr_auto]">
                            <label class="group flex cursor-text items-center gap-3 rounded-2xl px-4 py-2.5 transition-colors hover:bg-ivt-paper-2">
                                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-ivt-paper-2 text-ivt-violet transition-colors group-focus-within:bg-brand group-focus-within:text-white group-hover:bg-white">
                                    <MagnifyingGlassIcon class="h-[18px] w-[18px]" />
                                </span>
                                <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                                    <span class="text-[10.5px] font-bold uppercase tracking-[0.1em] text-ivt-ink-faint">Caută</span>
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="Fotograf, DJ, restaurant…"
                                        class="w-full border-0 bg-transparent p-0 text-[14.5px] font-semibold text-ivt-ink placeholder:font-semibold placeholder:text-ivt-ink-faint focus:outline-none focus:ring-0"
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

                            <DateField
                                v-model="availableOn"
                                label="Data evenimentului"
                                placeholder="Oricând"
                                class="sm:min-w-[220px] sm:border-l sm:border-ivt-line"
                                @change="applyFilters({ available_on: $event || undefined })"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <div id="rezultate" class="mx-auto grid max-w-[1600px] scroll-mt-28 grid-cols-1 gap-8 px-4 pb-8 pt-3 sm:px-6 sm:py-8 lg:grid-cols-[272px_1fr] lg:px-8 lg:py-10">
                <!-- FILTERS -->
                <aside class="hidden self-start rounded-[24px] border border-ivt-line bg-white p-6 shadow-ivt-soft lg:sticky lg:top-[105px] lg:block lg:max-h-[calc(100vh-125px)] lg:overflow-y-auto lg:[scrollbar-width:thin]">
                    <div class="mb-5 flex items-center justify-between">
                        <h2 class="flex items-center gap-2 font-display text-[19px] tracking-tight">
                            Filtre
                            <span v-if="activeFilterCount" class="flex h-5 min-w-5 items-center justify-center rounded-full bg-ivt-ink px-1.5 font-invita text-[11px] font-bold text-ivt-accent-bright">{{ activeFilterCount }}</span>
                        </h2>
                        <button v-if="activeFilterCount" type="button" class="text-[12.5px] font-semibold text-primary hover:underline" @click="resetFilters">Resetează</button>
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
                <div class="min-w-0">
                    <div class="mb-5 flex flex-wrap items-center gap-x-3 gap-y-2 sm:justify-between sm:gap-4">
                        <p class="order-last text-[13.5px] text-ivt-ink-soft sm:order-first" :class="activePills.length && 'hidden sm:block'">
                            <span class="font-display text-[18px] tabular-nums text-ivt-ink sm:text-[22px]">{{ listings.total }}</span>
                            {{ listings.total === 1 ? 'rezultat' : 'rezultate' }}
                            <span v-if="availableOn">disponibile pe {{ formatDate(availableOn) }}</span>
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-full border border-ivt-line bg-white px-4 py-2 text-[13px] font-semibold text-ivt-ink transition-colors hover:border-primary/30 hover:text-primary lg:hidden"
                                @click="showMobileFilters = true"
                            >
                                <AdjustmentsHorizontalIcon class="h-4 w-4 text-ivt-violet" />
                                Filtre
                                <span v-if="activeFilterCount" class="flex h-5 min-w-5 items-center justify-center rounded-full bg-brand px-1 text-[11px] font-bold text-white">{{ activeFilterCount }}</span>
                            </button>
                            <button
                                v-if="canSaveSearch && activeFilterCount > 0"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-full border border-ivt-line bg-white px-4 py-2 text-[13px] font-semibold text-ivt-ink transition-colors hover:border-primary/30 hover:text-primary"
                                @click="openSaveSearch"
                            >
                                <BookmarkIcon class="h-4 w-4 text-ivt-violet" /> Salvează<span class="hidden sm:inline"> căutarea</span>
                            </button>
                            <div class="relative">
                                <select
                                    :value="filters.sort"
                                    class="appearance-none rounded-full border border-ivt-line bg-white bg-none py-2 pl-4 pr-9 text-[13px] font-semibold text-ivt-ink transition-colors hover:border-primary/30 focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    aria-label="Sortează"
                                    @change="setSort"
                                >
                                    <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                                <ChevronDownIcon class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-ivt-ink-faint" stroke-width="2.5" />
                            </div>
                        </div>
                    </div>

                    <div v-if="activePills.length" class="mb-6 flex flex-wrap items-center gap-2">
                        <button
                            v-for="pill in activePills"
                            :key="pill.key"
                            type="button"
                            class="group inline-flex items-center gap-1.5 rounded-full bg-primary/[0.08] py-1 pl-3 pr-1.5 text-[12.5px] font-semibold text-primary transition-colors hover:bg-primary/[0.14]"
                            :aria-label="`Elimină filtrul ${pill.label}`"
                            @click="clearPill(pill)"
                        >
                            {{ pill.label }}
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/70 transition-colors group-hover:bg-white">
                                <XMarkIcon class="h-3 w-3" stroke-width="2.5" />
                            </span>
                        </button>
                        <button type="button" class="ml-1 text-[12.5px] font-semibold text-ivt-ink-faint underline-offset-4 transition-colors hover:text-ivt-ink hover:underline" @click="resetFilters">
                            Șterge tot
                        </button>
                        <p class="ml-2 text-[13.5px] text-ivt-ink-soft sm:hidden">
                            <span class="font-display text-[18px] tabular-nums text-ivt-ink">{{ listings.total }}</span>
                            {{ listings.total === 1 ? 'rezultat' : 'rezultate' }}
                            <span v-if="availableOn">disponibile pe {{ formatDate(availableOn) }}</span>
                        </p>
                    </div>

                    <div class="transition-opacity duration-300" :class="loading && 'pointer-events-none opacity-50'">
                        <div v-if="listings.data.length" class="grid grid-cols-2 gap-3 sm:gap-5 xl:grid-cols-3">
                            <ListingCard
                                v-for="listing in listings.data"
                                :key="listing.id"
                                :listing="listing"
                                :favorited="favoriteListingIds.includes(listing.id)"
                            />
                        </div>
                        <div v-else class="flex flex-col items-center justify-center rounded-[24px] border border-dashed border-ivt-line bg-ivt-paper-2/50 px-5 py-20 text-center">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-ivt-violet shadow-ivt-soft">
                                <FaceFrownIcon class="h-6 w-6" />
                            </span>
                            <p class="mt-4 font-display text-[20px]">Niciun anunț găsit</p>
                            <p class="mt-1 max-w-sm text-[13.5px] text-ivt-ink-soft">Încearcă alți termeni de căutare sau renunță la câteva filtre.</p>
                            <div class="mt-5 flex flex-wrap justify-center gap-2">
                                <button v-if="activeFilterCount || search" type="button" class="rounded-full bg-ivt-ink px-5 py-2.5 text-[13px] font-bold text-ivt-paper transition-colors hover:bg-primary" @click="resetFilters">
                                    Șterge filtrele
                                </button>
                                <Link :href="route('listings.map')" class="rounded-full border border-ivt-line bg-white px-5 py-2.5 text-[13px] font-semibold text-ivt-ink transition-colors hover:border-primary/30 hover:text-primary">
                                    Vezi harta
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <Pagination v-if="listings.last_page > 1" class="mt-10" :name="listings" :links="listings.links" @navigate="goToPage" />
                </div>
            </div>
        </div>

        <!-- MOBILE FILTERS BOTTOM SHEET -->
        <Teleport to="body">
            <Transition name="sheet" :duration="{ enter: 350, leave: 250 }">
                <div v-if="showMobileFilters" class="fixed inset-0 z-50 font-invita text-ivt-ink lg:hidden">
                    <div class="sheet-backdrop absolute inset-0 bg-ivt-ink/50 backdrop-blur-sm" @click="showMobileFilters = false" />

                    <div class="sheet-panel absolute inset-x-0 bottom-0 flex max-h-[85vh] flex-col rounded-t-[28px] bg-white shadow-2xl">
                        <div class="mx-auto mt-2.5 h-1 w-10 rounded-full bg-ivt-paper-3" />
                        <div class="flex items-center justify-between border-b border-ivt-line px-6 pb-4 pt-3">
                            <h3 class="font-display text-[20px] tracking-tight">Filtre</h3>
                            <button type="button" @click="showMobileFilters = false" class="flex h-8 w-8 items-center justify-center rounded-full text-ivt-ink-faint transition-colors hover:bg-ivt-paper-2 hover:text-ivt-ink">
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

                        <div class="flex items-center gap-3 border-t border-ivt-line px-6 py-4">
                            <button type="button" @click="resetFilters" class="text-[13px] font-semibold text-primary hover:underline">Resetează</button>
                            <button
                                type="button"
                                @click="applyFilters(); showMobileFilters = false"
                                class="ml-auto flex-1 rounded-2xl bg-ivt-ink px-5 py-3.5 text-sm font-bold text-ivt-paper transition-colors hover:bg-primary"
                            >
                                Vezi {{ listings.total }} anunțuri
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <Modal :show="showSaveSearch" max-width="md" @close="showSaveSearch = false">
            <div class="p-6 font-invita text-ivt-ink">
                <h3 class="font-display text-[20px] tracking-tight">Salvează această căutare</h3>
                <p class="mt-1.5 text-[13.5px] leading-relaxed text-ivt-ink-soft">Te anunțăm prin email de îndată ce apare un anunț nou care se potrivește filtrelor curente.</p>

                <label for="saved-search-name" class="mb-1.5 mt-5 block text-[10.5px] font-bold uppercase tracking-[0.1em] text-ivt-ink-faint">Nume</label>
                <input
                    id="saved-search-name"
                    v-model="saveSearchName"
                    type="text"
                    maxlength="100"
                    class="w-full rounded-xl border-ivt-line text-sm font-semibold text-ivt-ink focus:border-primary focus:ring-primary/20"
                    @keyup.enter="submitSaveSearch"
                />

                <div class="mt-5 flex items-center justify-end gap-3">
                    <button type="button" class="rounded-full px-4 py-2 text-sm font-semibold text-ivt-ink-soft transition-colors hover:bg-ivt-paper-2" @click="showSaveSearch = false">
                        Anulează
                    </button>
                    <button
                        type="button"
                        :disabled="savingSearch || !saveSearchName.trim()"
                        class="rounded-full bg-ivt-ink px-5 py-2 text-sm font-bold text-ivt-paper transition-colors hover:bg-primary disabled:cursor-not-allowed disabled:opacity-50"
                        @click="submitSaveSearch"
                    >
                        Salvează
                    </button>
                </div>
            </div>
        </Modal>
    </ClientLayout>
</template>

<style scoped>
.sheet-enter-active .sheet-backdrop,
.sheet-leave-active .sheet-backdrop {
    transition: opacity 250ms ease;
}
.sheet-enter-active .sheet-panel {
    transition: transform 350ms cubic-bezier(0.32, 0.72, 0, 1);
}
.sheet-leave-active .sheet-panel {
    transition: transform 250ms ease-in;
}
.sheet-enter-from .sheet-backdrop,
.sheet-leave-to .sheet-backdrop {
    opacity: 0;
}
.sheet-enter-from .sheet-panel,
.sheet-leave-to .sheet-panel {
    transform: translateY(100%);
}
</style>
