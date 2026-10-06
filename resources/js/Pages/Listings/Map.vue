<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import romania from '@/data/romania-counties.json';

const props = defineProps({
    counties: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    filters: { type: Object, default: () => ({ category: null, event_type: null }) },
    categories: { type: Array, default: () => [] },
    eventTypes: { type: Array, default: () => [] },
});

const hovered = ref(null);
const tooltip = ref({ x: 0, y: 0 });

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

const max = computed(() => Math.max(1, ...props.counties.map((county) => county.count)));

// Wine (`primary`) at increasing opacity; counties without listings stay neutral.
const fillFor = (county) => {
    if (county.count === 0) return null;
    return { fill: '#7C2E3B', fillOpacity: 0.18 + 0.82 * (county.count / max.value) };
};

const ranking = computed(() =>
    [...props.counties].filter((county) => county.count > 0).sort((a, b) => b.count - a.count).slice(0, 10)
);

const countiesWithListings = computed(() => props.counties.filter((county) => county.count > 0).length);

const label = (count) => (count === 1 ? '1 anunț' : `${count} anunțuri`);

const setFilter = (key, value) => {
    router.get(route('listings.map'), {
        category: props.filters.category || undefined,
        event_type: props.filters.event_type || undefined,
        [key]: value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true, only: ['counties', 'total', 'filters'] });
};

const toggle = (key, value) => setFilter(key, props.filters[key] === value ? null : value);

const hasFilters = computed(() => Boolean(props.filters.category || props.filters.event_type));

const chipClass = (active) => [
    'whitespace-nowrap rounded-full border px-3.5 py-1.5 text-[13px] font-semibold transition-all duration-150',
    active
        ? 'border-transparent bg-primary text-white shadow-sm shadow-primary/25'
        : 'border-ivt-line bg-white text-ivt-ink-soft hover:border-primary/40 hover:text-primary',
];

const open = (county) => {
    if (county.count === 0) return;
    router.get(route('listings.index'), {
        county_ids: [county.id],
        category: props.filters.category || undefined,
        event_type: props.filters.event_type || undefined,
    });
};

const move = (event) => {
    const box = event.currentTarget.getBoundingClientRect();
    tooltip.value = { x: event.clientX - box.left, y: event.clientY - box.top };
};
</script>

<template>
    <Head title="Harta anunțurilor — Invita" />

    <div class="bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <section class="border-b border-ivt-line bg-ivt-paper-2 py-12 sm:py-14">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <p class="mb-5 flex items-center gap-2 text-[13px] text-ivt-ink-faint">
                        <Link :href="route('home')" class="text-ivt-ink-soft transition-colors hover:text-ivt-wine">Acasă</Link>
                        <span>›</span>
                        <span>Harta anunțurilor</span>
                    </p>
                    <h1 class="max-w-2xl font-serif text-[34px] font-medium leading-[1.05] tracking-[-0.01em] sm:text-[44px]">
                        Anunțuri pe hartă, județ cu județ.
                    </h1>
                    <p class="mt-4 max-w-lg text-[17px] leading-relaxed text-ivt-ink-soft">
                        {{ total }} {{ total === 1 ? 'anunț activ' : 'anunțuri active' }} în {{ countiesWithListings }} {{ countiesWithListings === 1 ? 'județ' : 'județe' }}{{ hasFilters ? ' pentru filtrele alese' : '' }}. Alege un județ ca să vezi furnizorii de acolo.
                    </p>
                </div>
            </section>

            <section class="mx-auto max-w-7xl space-y-4 px-6 pt-8 lg:px-8">
                <div>
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-ivt-ink-faint">Tip eveniment</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="type in eventTypes"
                            :key="type.value"
                            type="button"
                            :aria-pressed="filters.event_type === type.value"
                            :class="chipClass(filters.event_type === type.value)"
                            @click="toggle('event_type', type.value)"
                        >{{ type.label }}</button>
                    </div>
                </div>
                <div>
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-ivt-ink-faint">Tip serviciu</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="category in categories"
                            :key="category.slug"
                            type="button"
                            :aria-pressed="filters.category === category.slug"
                            :class="chipClass(filters.category === category.slug)"
                            @click="toggle('category', category.slug)"
                        >{{ category.name }}</button>
                    </div>
                </div>
                <button
                    v-if="hasFilters"
                    type="button"
                    class="text-[13px] font-semibold text-primary hover:underline"
                    @click="router.get(route('listings.map'), {}, { preserveState: true, preserveScroll: true, replace: true, only: ['counties', 'total', 'filters'] })"
                >Șterge filtrele</button>
            </section>

            <section class="mx-auto grid max-w-7xl gap-8 px-6 py-10 lg:grid-cols-[1fr_320px] lg:px-8">
                <div class="relative rounded-3xl border border-ivt-line bg-white p-4 shadow-ivt-soft sm:p-6" @mousemove="move">
                    <svg
                        :viewBox="`0 0 ${romania.width} ${romania.height}`"
                        class="h-auto w-full"
                        role="img"
                        aria-label="Harta României cu numărul de anunțuri pe județ"
                    >
                        <g stroke="#fff" stroke-width="1.2" stroke-linejoin="round">
                            <path
                                v-for="county in shapes"
                                :key="county.id"
                                :d="county.d"
                                :fill="fillFor(county)?.fill ?? '#ECE7DF'"
                                :fill-opacity="fillFor(county)?.fillOpacity ?? 1"
                                :class="['outline-none focus:outline-none focus-visible:stroke-ivt-ink focus-visible:[stroke-width:2.5]', county.count ? 'cursor-pointer' : 'cursor-default', hovered?.id === county.id ? 'brightness-90' : '']"
                                :tabindex="county.count ? 0 : -1"
                                :aria-label="`${county.name}: ${label(county.count)}`"
                                @mouseenter="hovered = county"
                                @mouseleave="hovered = null"
                                @focus="hovered = county"
                                @blur="hovered = null"
                                @click="open(county)"
                                @keydown.enter="open(county)"
                            />
                        </g>
                        <g class="pointer-events-none" text-anchor="middle" font-size="15" font-weight="700">
                            <text
                                v-for="county in shapes.filter((c) => c.count > 0)"
                                :key="county.id"
                                :x="county.cx"
                                :y="county.cy + 5 + (LABEL_OFFSET_Y[county.name] ?? 0)"
                :fill="county.count / max > 0.45 && !LABEL_OFFSET_Y[county.name] ? '#fff' : '#7C2E3B'"
                            >{{ county.count }}</text>
                        </g>
                    </svg>

                    <div
                        v-if="hovered"
                        class="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full rounded-xl bg-ivt-ink px-3 py-2 text-xs text-ivt-paper shadow-ivt-deep"
                        :style="{ left: `${tooltip.x}px`, top: `${tooltip.y - 12}px` }"
                    >
                        <p class="font-bold">{{ hovered.name }}</p>
                        <p>{{ label(hovered.count) }}</p>
                    </div>

                    <div class="mt-4 flex items-center gap-3 text-xs text-ivt-ink-faint">
                        <span>Mai puține</span>
                        <span class="h-2 w-40 rounded-full" style="background: linear-gradient(to right, rgba(124,46,59,0.18), rgba(124,46,59,1));" />
                        <span>Mai multe</span>
                    </div>
                </div>

                <aside class="h-fit rounded-3xl border border-ivt-line bg-white p-6 shadow-ivt-soft">
                    <h2 class="font-serif text-xl font-medium">Cele mai active județe</h2>
                    <ol v-if="ranking.length" class="mt-4 space-y-1">
                        <li v-for="county in ranking" :key="county.id">
                            <Link
                                :href="route('listings.index', { county_ids: [county.id] })"
                                class="flex items-center justify-between rounded-xl px-3 py-2 text-sm transition-colors hover:bg-ivt-paper-2"
                                @mouseenter="hovered = county"
                                @mouseleave="hovered = null"
                            >
                                <span class="font-medium">{{ county.name }}</span>
                                <span class="font-bold text-ivt-wine">{{ county.count }}</span>
                            </Link>
                        </li>
                    </ol>
                    <p v-else class="mt-4 text-sm text-ivt-ink-soft">{{ hasFilters ? 'Niciun anunț pentru filtrele alese.' : 'Nu există încă anunțuri publicate.' }}</p>
                </aside>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
