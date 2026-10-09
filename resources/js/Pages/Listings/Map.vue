<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRightIcon, ArrowUpRightIcon, MapPinIcon, Squares2X2Icon, XMarkIcon } from '@heroicons/vue/24/outline';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import SearchField from '@/Components/Home/SearchField.vue';
import romania from '@/data/romania-counties.json';
import { ivt, primary } from '@/palette';
import { categoryIcon } from '@/Composables/useCategoryIcon';

const props = defineProps({
    counties: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    filters: { type: Object, default: () => ({ category: null, event_type: null }) },
    categories: { type: Array, default: () => [] },
    eventTypes: { type: Array, default: () => [] },
});

const hovered = ref(null);
const tooltip = ref({ x: 0, y: 0 });
const loading = ref(false);

// Bucharest sits inside Ilfov's outline, so it has to be painted last or it is hidden.
const PAINT_LAST = 'Bucuresti';
// Ilfov's centroid falls on top of Bucharest; nudge its label out of the way.
const LABEL_OFFSET_Y = { Ilfov: -26 };

// GeoJSON names are ASCII (e.g. "Bistrita-Nasaud"), same as the counties table.
const shapes = computed(() =>
    props.counties
        .filter((county) => romania.counties[county.name])
        .map((county) => ({ ...county, ...romania.counties[county.name] }))
        .sort((a, b) => (a.name === PAINT_LAST) - (b.name === PAINT_LAST))
);

const labelled = computed(() => shapes.value.filter((county) => county.count > 0));

const hoveredShape = computed(() => hovered.value && shapes.value.find((county) => county.id === hovered.value.id));

const max = computed(() => Math.max(1, ...props.counties.map((county) => county.count)));

// Wine (`primary`) at increasing opacity; counties without listings stay neutral.
const fillFor = (county) => {
    if (county.count === 0) return null;
    return { fill: primary.DEFAULT, fillOpacity: 0.18 + 0.82 * (county.count / max.value) };
};

const ranking = computed(() =>
    [...props.counties].filter((county) => county.count > 0).sort((a, b) => b.count - a.count).slice(0, 10)
);

const countiesWithListings = computed(() => props.counties.filter((county) => county.count > 0).length);

const label = (count) => (count === 1 ? '1 anunț' : `${count} anunțuri`);

const share = (count) => (props.total ? Math.round((count / props.total) * 100) : 0);

const visit = (params) => {
    router.get(route('listings.map'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['counties', 'total', 'filters'],
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
};

const setFilter = (key, value) => visit({
    category: props.filters.category || undefined,
    event_type: props.filters.event_type || undefined,
    [key]: value || undefined,
});

const toggle = (key, value) => setFilter(key, props.filters[key] === value ? null : value);

const clearFilters = () => visit({});

const hasFilters = computed(() => Boolean(props.filters.category || props.filters.event_type));

const activeFilters = computed(() => [
    props.filters.event_type && {
        key: 'event_type',
        label: props.eventTypes.find((type) => type.value === props.filters.event_type)?.label,
    },
    props.filters.category && {
        key: 'category',
        label: props.categories.find((category) => category.slug === props.filters.category)?.name,
    },
].filter((filter) => filter && filter.label));

const bandItemClass = (active) => [
    'flex items-center gap-2.5 whitespace-nowrap px-6 font-display text-[17px] font-semibold transition-colors',
    active
        ? 'text-white underline decoration-ivt-accent-light decoration-2 underline-offset-[6px]'
        : 'text-white/70 hover:text-white',
];

const listingsUrl = (county) => route('listings.index', {
    county_ids: [county.id],
    category: props.filters.category || undefined,
    event_type: props.filters.event_type || undefined,
});

// Hero quick-jump: counties with listings first, busiest on top, empty ones alphabetical after.
const selectedCounty = ref('');

const countyOptions = computed(() =>
    [...props.counties]
        .sort((a, b) => b.count - a.count || a.name.localeCompare(b.name, 'ro'))
        .map((county) => ({ value: county.id, label: county.name, meta: county.count ? label(county.count) : 'Fără anunțuri încă' }))
);

const goToCounty = () => {
    const county = props.counties.find((item) => item.id === selectedCounty.value);
    if (county) router.get(listingsUrl(county));
};

const open = (county) => {
    if (county.count === 0) return;
    router.get(listingsUrl(county));
};

const move = (event) => {
    const box = event.currentTarget.getBoundingClientRect();
    tooltip.value = { x: event.clientX - box.left, y: event.clientY - box.top };
};

// Keyboard focus has no pointer position, so the tooltip follows the county's centroid instead.
const focusCounty = (county, event) => {
    hovered.value = county;
    const svg = event.target.ownerSVGElement;
    const wrap = svg?.parentElement?.getBoundingClientRect();
    const box = svg?.getBoundingClientRect();
    if (!wrap || !box) return;
    tooltip.value = {
        x: box.left - wrap.left + (county.cx / romania.width) * box.width,
        y: box.top - wrap.top + (county.cy / romania.height) * box.height,
    };
};

onBeforeUnmount(() => (hovered.value = null));
</script>

<template>
    <Head title="Harta anunțurilor — Invita" />

    <div class="overflow-x-clip bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <!-- Hero -->
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
                        <Link :href="route('listings.index')" class="transition-colors hover:text-primary">Anunțuri</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <span class="text-ivt-ink-soft">Hartă</span>
                    </nav>

                    <div class="mt-8 lg:mt-10">
                        <p class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 py-1 pl-1.5 pr-3.5 text-[12.5px] font-semibold text-ivt-ink-soft shadow-sm backdrop-blur">
                            <span class="rounded-full bg-brand px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider text-white">{{ total }}</span>
                            anunțuri active în {{ countiesWithListings }} din {{ counties.length }} județe
                        </p>

                        <h1 class="mt-6 text-balance font-display text-[34px] font-bold leading-[1.05] tracking-tight text-ivt-ink sm:text-[44px] lg:text-[52px]">
                            Găsește furnizori
                            <span class="relative whitespace-nowrap">
                                <span class="text-gradient">aproape de tine.</span>
                                <svg class="absolute -bottom-2 left-0 h-3 w-full text-ivt-accent-bright" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true">
                                    <path d="M2 9 C 80 2, 200 2, 298 7" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                </svg>
                            </span>
                        </h1>

                        <p class="mt-6 max-w-[480px] text-[15.5px] leading-relaxed text-ivt-ink-soft">
                            Fiecare județ, colorat după câte oferte are. Caută direct localitatea evenimentului sau explorează harta de mai jos.
                        </p>

                        <form
                            class="ring-gradient relative z-20 mt-9 grid max-w-[560px] gap-1.5 rounded-[22px] border border-ivt-line bg-white p-2 shadow-ivt-soft transition-shadow focus-within:shadow-ivt-deep hover:shadow-ivt-deep sm:grid-cols-[1fr_auto]"
                            @submit.prevent="goToCounty"
                        >
                            <SearchField
                                v-model="selectedCounty"
                                label="Județ"
                                placeholder="Alege județul"
                                empty-label="Niciun județ"
                                :icon="MapPinIcon"
                                :options="countyOptions"
                            />
                            <button
                                type="submit"
                                class="btn-brand whitespace-nowrap rounded-2xl px-7 py-3.5 text-sm font-bold disabled:pointer-events-none disabled:opacity-40"
                                :disabled="!selectedCounty"
                            >
                                Vezi anunțurile
                                <ArrowRightIcon class="h-4 w-4" stroke-width="2.5" />
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Filters -->
            <section class="sticky top-[81px] z-20 shadow-glow-primary">
                <div class="flex items-stretch bg-brand">
                    <p class="relative z-10 flex w-28 flex-none items-center bg-ivt-ink/25 px-6 text-[10.5px] font-bold uppercase tracking-[0.12em] text-white/80 lg:w-32 lg:px-8">Eveniment</p>
                    <div class="group flex min-w-0 flex-1 overflow-hidden py-3.5 [mask-image:linear-gradient(90deg,transparent,#000_6%,#000_94%,transparent)]">
                        <div class="flex flex-none animate-marquee items-center group-hover:[animation-play-state:paused]">
                            <template v-for="copy in 4" :key="copy">
                                <button
                                    v-for="type in eventTypes"
                                    :key="`${copy}-${type.value}`"
                                    type="button"
                                    :tabindex="copy > 1 ? -1 : undefined"
                                    :aria-hidden="copy > 1 ? 'true' : undefined"
                                    :aria-pressed="copy === 1 ? filters.event_type === type.value : undefined"
                                    :class="bandItemClass(filters.event_type === type.value)"
                                    @click="toggle('event_type', type.value)"
                                >
                                    {{ type.label }}
                                    <span class="ml-4 text-white/40" aria-hidden="true">✦</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="flex items-stretch border-t border-white/10 bg-ivt-ink">
                    <p class="relative z-10 flex w-28 flex-none items-center bg-white/5 px-6 text-[10.5px] font-bold uppercase tracking-[0.12em] text-white/70 lg:w-32 lg:px-8">Serviciu</p>
                    <div class="group flex min-w-0 flex-1 overflow-hidden py-3.5 [mask-image:linear-gradient(90deg,transparent,#000_6%,#000_94%,transparent)]">
                        <div class="flex flex-none animate-marquee items-center [animation-direction:reverse] group-hover:[animation-play-state:paused]">
                            <template v-for="copy in 2" :key="copy">
                                <button
                                    v-for="category in categories"
                                    :key="`${copy}-${category.slug}`"
                                    type="button"
                                    :tabindex="copy === 2 ? -1 : undefined"
                                    :aria-hidden="copy === 2 ? 'true' : undefined"
                                    :aria-pressed="copy === 1 ? filters.category === category.slug : undefined"
                                    :class="bandItemClass(filters.category === category.slug)"
                                    @click="toggle('category', category.slug)"
                                >
                                    <component :is="categoryIcon(category.slug)" class="h-5 w-5 text-ivt-accent-light" stroke-width="1.75" />
                                    {{ category.name }}
                                    <span class="ml-4 text-white/40" aria-hidden="true">✦</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Map + ranking -->
            <section id="harta" class="mx-auto grid max-w-[1600px] scroll-mt-48 gap-6 px-6 py-10 lg:grid-cols-[1fr_360px] lg:gap-8 lg:px-8 lg:py-12">
                <div class="min-w-0">
                    <div v-if="hasFilters" class="mb-4 flex flex-wrap items-center gap-2">
                        <span class="text-[13px] text-ivt-ink-soft">
                            <strong class="font-semibold text-ivt-ink">{{ label(total) }}</strong> pentru
                        </span>
                        <button
                            v-for="filter in activeFilters"
                            :key="filter.key"
                            type="button"
                            class="group inline-flex items-center gap-1.5 rounded-full bg-primary/[0.08] py-1 pl-3 pr-1.5 text-[13px] font-semibold text-primary transition-colors hover:bg-primary/[0.14]"
                            :aria-label="`Elimină filtrul ${filter.label}`"
                            @click="setFilter(filter.key, null)"
                        >
                            {{ filter.label }}
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/70 transition-colors group-hover:bg-white">
                                <XMarkIcon class="h-3 w-3" stroke-width="2.5" />
                            </span>
                        </button>
                        <button type="button" class="ml-1 text-[13px] font-semibold text-ivt-ink-faint underline-offset-4 transition-colors hover:text-ivt-ink hover:underline" @click="clearFilters">
                            Șterge tot
                        </button>
                    </div>

                    <div
                        class="relative overflow-hidden rounded-[28px] border border-ivt-line bg-white shadow-ivt-soft"
                        @mousemove="move"
                    >
                        <div
                            class="pointer-events-none absolute inset-0 opacity-60"
                            style="background-image: radial-gradient(rgba(26,20,51,0.08) 1px, transparent 1px); background-size: 18px 18px;"
                        />

                        <div class="relative p-4 transition-opacity duration-300 sm:p-8" :class="loading && 'opacity-50'">
                            <svg
                                :viewBox="`0 0 ${romania.width} ${romania.height}`"
                                class="h-auto w-full drop-shadow-[0_18px_30px_rgba(26,20,51,0.12)]"
                                role="img"
                                aria-label="Harta României cu numărul de anunțuri pe județ"
                            >
                                <g stroke="#fff" stroke-width="1.4" stroke-linejoin="round">
                                    <path
                                        v-for="county in shapes"
                                        :key="county.id"
                                        :d="county.d"
                                        :fill="fillFor(county)?.fill ?? ivt['paper-3']"
                                        :fill-opacity="fillFor(county)?.fillOpacity ?? 1"
                                        :class="[
                                            'outline-none transition-[fill-opacity,filter] duration-300 focus:outline-none',
                                            county.count ? 'cursor-pointer' : 'cursor-default',
                                            hovered && hovered.id !== county.id ? '[filter:saturate(0.6)_opacity(0.75)]' : '',
                                        ]"
                                        :tabindex="county.count ? 0 : -1"
                                        :aria-label="`${county.name}: ${label(county.count)}`"
                                        @mouseenter="hovered = county"
                                        @mouseleave="hovered = null"
                                        @focus="focusCounty(county, $event)"
                                        @blur="hovered = null"
                                        @click="open(county)"
                                        @keydown.enter="open(county)"
                                    />
                                </g>

                                <!-- Hovered county redrawn on top so its outline isn't clipped by neighbours. -->
                                <path
                                    v-if="hoveredShape"
                                    :d="hoveredShape.d"
                                    fill="none"
                                    :stroke="ivt.ink"
                                    stroke-width="2.5"
                                    stroke-linejoin="round"
                                    class="pointer-events-none"
                                />

                                <g class="pointer-events-none" text-anchor="middle" font-size="14" font-weight="700" font-family="Inter, sans-serif">
                                    <text
                                        v-for="county in labelled"
                                        :key="county.id"
                                        :x="county.cx"
                                        :y="county.cy + 5 + (LABEL_OFFSET_Y[county.name] ?? 0)"
                                        :fill="county.count / max > 0.45 && !LABEL_OFFSET_Y[county.name] ? ivt.paper : primary.DEFAULT"
                                    >{{ county.count }}</text>
                                </g>
                            </svg>
                        </div>

                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="opacity-0 scale-95"
                            leave-active-class="transition duration-100 ease-in"
                            leave-to-class="opacity-0"
                        >
                            <div
                                v-if="hovered"
                                class="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full"
                                :style="{ left: `${tooltip.x}px`, top: `${tooltip.y - 14}px` }"
                            >
                                <div class="min-w-[150px] rounded-2xl bg-ivt-ink px-4 py-3 text-ivt-on-dark shadow-ivt-deep">
                                    <p class="flex items-center gap-1.5 text-[13px] font-bold">
                                        <MapPinIcon class="h-3.5 w-3.5 text-ivt-accent-bright" stroke-width="2" />
                                        {{ hovered.name }}
                                    </p>
                                    <p class="mt-1 font-display text-xl leading-none text-ivt-accent-bright">{{ hovered.count }}</p>
                                    <p class="mt-1 text-[11.5px] text-ivt-on-dark-dim">
                                        {{ hovered.count ? `${hovered.count === 1 ? 'anunț' : 'anunțuri'} · ${share(hovered.count)}% din total` : 'Niciun anunț încă' }}
                                    </p>
                                </div>
                                <div class="mx-auto h-2 w-3 bg-ivt-ink [clip-path:polygon(0_0,100%_0,50%_100%)]" />
                            </div>
                        </Transition>

                        <div class="relative flex flex-wrap items-center justify-between gap-3 border-t border-ivt-line bg-ivt-paper-2/50 px-5 py-3.5 sm:px-8">
                            <div class="flex items-center gap-3 text-[12px] font-medium text-ivt-ink-faint">
                                <span>Mai puține</span>
                                <span class="h-2 w-32 rounded-full sm:w-44" style="background: linear-gradient(to right, rgba(225,29,99,0.18), rgba(225,29,99,1));" />
                                <span>Mai multe</span>
                            </div>
                            <p class="text-[12px] text-ivt-ink-faint">Click pe un județ pentru a vedea anunțurile</p>
                        </div>
                    </div>
                </div>

                <aside class="flex flex-col gap-6 lg:h-fit" :class="hasFilters && 'lg:mt-12'">
                    <div class="rounded-[28px] border border-ivt-line bg-white p-6 shadow-ivt-soft">
                        <div class="flex items-baseline justify-between">
                            <h2 class="font-display text-[22px] tracking-tight">Top județe</h2>
                            <span v-if="ranking.length" class="text-[11px] font-bold uppercase tracking-[0.12em] text-ivt-ink-faint">Anunțuri</span>
                        </div>

                        <ol v-if="ranking.length" class="mt-4 space-y-0.5">
                            <li v-for="(county, index) in ranking" :key="county.id">
                                <Link
                                    :href="listingsUrl(county)"
                                    class="group relative block rounded-xl px-3 py-2.5 transition-colors"
                                    :class="hovered?.id === county.id ? 'bg-ivt-paper-2' : 'hover:bg-ivt-paper-2'"
                                    @mouseenter="hovered = county"
                                    @mouseleave="hovered = null"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="flex h-6 w-6 flex-none items-center justify-center rounded-full text-[11px] font-bold tabular-nums"
                                            :class="index < 3 ? 'bg-ivt-ink text-ivt-accent-bright' : 'bg-ivt-paper-3 text-ivt-ink-soft'"
                                        >{{ index + 1 }}</span>
                                        <span class="min-w-0 flex-1 truncate text-sm font-semibold">{{ county.name }}</span>
                                        <span class="text-sm font-bold tabular-nums text-primary">{{ county.count }}</span>
                                        <ArrowRightIcon class="h-3.5 w-3.5 -translate-x-1 text-ivt-ink-faint opacity-0 transition-all group-hover:translate-x-0 group-hover:opacity-100" stroke-width="2" />
                                    </div>
                                    <div class="ml-9 mt-1.5 h-1 overflow-hidden rounded-full bg-ivt-paper-3">
                                        <div class="h-full rounded-full bg-primary transition-[width] duration-500" :style="{ width: `${(county.count / max) * 100}%`, opacity: 0.35 + 0.65 * (county.count / max) }" />
                                    </div>
                                </Link>
                            </li>
                        </ol>

                        <div v-else class="mt-5 rounded-2xl border border-dashed border-ivt-line bg-ivt-paper-2/50 px-5 py-8 text-center">
                            <MapPinIcon class="mx-auto h-6 w-6 text-ivt-ink-faint" />
                            <p class="mt-2 text-sm text-ivt-ink-soft">
                                {{ hasFilters ? 'Niciun anunț pentru filtrele alese.' : 'Nu există încă anunțuri publicate.' }}
                            </p>
                            <button v-if="hasFilters" type="button" class="mt-3 text-[13px] font-semibold text-primary hover:underline" @click="clearFilters">
                                Șterge filtrele
                            </button>
                        </div>
                    </div>

                    <Link
                        :href="route('listings.index', { category: filters.category || undefined, event_type: filters.event_type || undefined })"
                        class="group relative overflow-hidden rounded-[28px] bg-ivt-ink p-6 text-ivt-on-dark shadow-ivt-deep"
                    >
                        <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-ivt-violet/20 blur-3xl transition-transform duration-500 group-hover:scale-125" />
                        <div class="relative flex items-start justify-between gap-4">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-ivt-accent-bright">
                                <Squares2X2Icon class="h-5 w-5" />
                            </span>
                            <ArrowUpRightIcon class="h-5 w-5 text-ivt-on-dark-dim transition-transform duration-300 group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-ivt-accent-bright" />
                        </div>
                        <p class="relative mt-5 font-display text-[22px] leading-tight">Preferi o listă?</p>
                        <p class="relative mt-1.5 text-[13.5px] leading-relaxed text-ivt-on-dark-dim">
                            Vezi toate anunțurile{{ hasFilters ? ' cu aceste filtre' : '' }}, cu prețuri, recenzii și disponibilitate.
                        </p>
                    </Link>
                </aside>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
