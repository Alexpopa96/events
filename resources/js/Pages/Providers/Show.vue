<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    MapPinIcon,
    PhoneIcon,
    EnvelopeIcon,
    ChatBubbleLeftEllipsisIcon,
    ShareIcon,
    GlobeAltIcon,
    CheckBadgeIcon,
    EyeIcon,
    FaceFrownIcon,
    HeartIcon,
    CalendarIcon,
    CheckIcon,
    XMarkIcon,
    ClockIcon,
    ArrowRightIcon,
    BuildingStorefrontIcon,
    Squares2X2Icon,
    SparklesIcon,
    ChatBubbleBottomCenterTextIcon,
    HomeModernIcon,
} from '@heroicons/vue/24/outline';
import { HeartIcon as HeartIconSolid, StarIcon } from '@heroicons/vue/24/solid';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { categoryIcon } from '@/Composables/useCategoryIcon';
import { formatListingPrice } from '@/Composables/useListingPrice';
import { useProviderFavoriteToggle } from '@/Composables/useProviderFavoriteToggle';

const props = defineProps({
    provider: { type: Object, required: true },
    listings: { type: Array, default: () => [] },
    reviews: { type: Array, default: () => [] },
    unavailableDates: { type: Array, default: () => [] },
});

/* ---------- availability check ---------- */
const checkDate = ref('');
const today = new Date().toISOString().slice(0, 10);
const dateIsAvailable = computed(() => !props.unavailableDates.includes(checkDate.value));

const { isFavorited, toggle: toggleFavorite } = useProviderFavoriteToggle(
    props.provider.slug,
    props.provider.is_favorited,
    null,
    props.provider.logo_url ?? props.provider.cover_url,
);

const location = computed(() => [props.provider.locality, props.provider.county].filter(Boolean).join(', '));

/* ---------- share ---------- */
const shareCopied = ref(false);
const shareProvider = async () => {
    const shareData = { title: props.provider.company_name, url: window.location.href };
    if (navigator.share) {
        try { await navigator.share(shareData); } catch { /* cancelled */ }
        return;
    }
    await navigator.clipboard.writeText(window.location.href);
    shareCopied.value = true;
    setTimeout(() => { shareCopied.value = false; }, 2000);
};

/* ---------- tabs ---------- */
const tabs = computed(() => [
    { id: 'anunturi', label: 'Anunțuri', count: listings.value.length },
    { id: 'despre', label: 'Despre' },
    { id: 'recenzii', label: 'Recenzii', count: props.provider.reviews_count },
]);
const activeTab = ref('anunturi');

/* ---------- stats ---------- */
const stats = computed(() => [
    { label: 'anunțuri active', value: props.provider.listings_count, icon: Squares2X2Icon },
    { label: 'recenzii', value: props.provider.reviews_count, icon: ChatBubbleBottomCenterTextIcon },
    { label: 'rating mediu', value: props.provider.rating ?? '—', icon: StarIcon },
    { label: 'furnizor din', value: props.provider.member_since ?? '—', icon: SparklesIcon },
]);

/* ---------- listings filter ---------- */
const listings = computed(() => props.listings);
const activeCategory = ref(null);

const categoryChips = computed(() => props.provider.categories.map((category) => ({
    ...category,
    count: listings.value.filter((item) => item.category_slug === category.slug).length,
})));

const filteredListings = computed(() => (
    activeCategory.value
        ? listings.value.filter((item) => item.category_slug === activeCategory.value)
        : listings.value
));

const gradients = [
    'from-ivt-sand via-ivt-violet-soft to-primary/30',
    'from-ivt-violet-soft to-ivt-violet/40',
    'from-ivt-accent-soft via-ivt-sand to-primary/30',
    'from-ivt-paper-3 to-ivt-violet/30',
];
const listingGradient = (index) => gradients[index % gradients.length];

/* ---------- reviews ---------- */
const reviewsExpanded = ref(false);
const visibleReviews = computed(() => (reviewsExpanded.value ? props.reviews : props.reviews.slice(0, 4)));

const ratingCounts = computed(() => {
    const counts = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
    props.reviews.forEach((review) => { counts[review.rating] = (counts[review.rating] ?? 0) + 1; });
    return counts;
});
const ratingPct = (star) => (props.reviews.length ? Math.round((ratingCounts.value[star] / props.reviews.length) * 100) : 0);

// How much of star n (1-5) is filled for the given rating, as a CSS width.
const starFill = (rating, n) => `${Math.round(Math.min(Math.max((rating ?? 0) - (n - 1), 0), 1) * 100)}%`;

const initials = (name) => (name || '')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

const socialLabels = { facebook: 'FB', instagram: 'IG', tiktok: 'TT' };
const socialLinks = computed(() => Object.entries(props.provider.social_links ?? {})
    .filter(([, url]) => url)
    .map(([platform, url]) => ({ platform, url, label: socialLabels[platform] ?? platform.slice(0, 2).toUpperCase() })));

const infoRows = computed(() => [
    { label: 'Locație', value: location.value, icon: MapPinIcon },
    { label: 'Adresă', value: props.provider.address, icon: HomeModernIcon },
    { label: 'Răspunde', value: props.provider.response_time_label, icon: ClockIcon },
    { label: 'Membru din', value: props.provider.member_since, icon: SparklesIcon },
    { label: 'Anunțuri active', value: props.provider.listings_count, icon: BuildingStorefrontIcon },
].filter((row) => row.value !== null && row.value !== undefined && row.value !== ''));
</script>

<template>
    <Head :title="`${provider.company_name} — Invita`" />

    <div class="bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main class="pb-24 lg:pb-0">
            <!-- PROFILE HERO -->
            <section class="relative isolate overflow-hidden bg-primary-night text-ivt-on-dark">
                <img
                    v-if="provider.cover_url"
                    :src="provider.cover_url"
                    alt=""
                    class="absolute inset-0 -z-20 h-full w-full scale-105 object-cover opacity-45"
                />
                <div class="absolute inset-0 -z-10 bg-gradient-to-b from-primary-night/40 via-primary-night/75 to-primary-night" />
                <div class="pointer-events-none absolute -left-24 -top-32 -z-10 h-[420px] w-[420px] rounded-full bg-primary/35 blur-[110px] animate-float-slow" />
                <div class="pointer-events-none absolute -right-20 top-10 -z-10 h-[380px] w-[380px] rounded-full bg-ivt-violet/40 blur-[110px] animate-float-slower" />

                <div class="mx-auto max-w-[1600px] px-6 pb-10 pt-6 lg:px-8 lg:pb-12">
                    <nav class="flex flex-wrap items-center gap-2 text-[13px] text-ivt-on-dark-dim">
                        <Link :href="route('home')" class="transition-colors hover:text-white">Acasă</Link>
                        <span class="opacity-40">/</span>
                        <Link :href="route('providers.index')" class="transition-colors hover:text-white">Furnizori</Link>
                        <span class="opacity-40">/</span>
                        <span class="truncate text-white">{{ provider.company_name }}</span>
                    </nav>

                    <div class="mt-14 flex flex-col gap-8 sm:mt-20 lg:flex-row lg:items-end lg:justify-between">
                        <div class="flex flex-col gap-6 sm:flex-row sm:items-end">
                            <div class="relative flex-none">
                                <div class="absolute -inset-1 rounded-[30px] bg-gradient-to-br from-primary via-ivt-violet to-ivt-accent-bright opacity-80 blur-sm" />
                                <div class="relative flex h-[112px] w-[112px] items-center justify-center overflow-hidden rounded-[26px] bg-gradient-to-br from-ivt-ink-2 to-ivt-ink ring-4 ring-primary-night sm:h-[128px] sm:w-[128px]">
                                    <img v-if="provider.logo_url" :src="provider.logo_url" :alt="provider.company_name" class="h-full w-full object-cover" />
                                    <component v-else :is="categoryIcon(provider.categories[0]?.slug)" class="h-12 w-12 text-ivt-accent-bright" />
                                </div>
                                <span
                                    v-if="provider.is_verified"
                                    class="absolute -bottom-2 -right-2 flex h-9 w-9 items-center justify-center rounded-full bg-ivt-teal text-white ring-4 ring-primary-night"
                                    title="CUI confirmat la ANAF"
                                >
                                    <CheckBadgeIcon class="h-5 w-5" />
                                </span>
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span v-if="provider.is_featured" class="inline-flex items-center gap-1 rounded-full bg-ivt-accent-bright px-3 py-1 text-[10.5px] font-bold uppercase tracking-[0.08em] text-ivt-accent-ink">
                                        <SparklesIcon class="h-3.5 w-3.5" /> Premium
                                    </span>
                                    <span v-if="provider.is_verified" class="inline-flex items-center gap-1 rounded-full bg-white/10 px-3 py-1 text-[10.5px] font-bold uppercase tracking-[0.06em] text-ivt-teal-light ring-1 ring-white/15 backdrop-blur" title="CUI confirmat la ANAF">
                                        <CheckBadgeIcon class="h-3.5 w-3.5" /> Verificat ANAF
                                    </span>
                                    <span
                                        v-for="category in provider.categories.slice(0, 3)"
                                        :key="category.id"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-ivt-on-dark ring-1 ring-white/15 backdrop-blur"
                                    >
                                        <component :is="categoryIcon(category.slug)" class="h-3.5 w-3.5" /> {{ category.name }}
                                    </span>
                                </div>

                                <h1 class="mt-3 font-display text-4xl font-semibold leading-[1.05] tracking-tight text-white sm:text-5xl lg:text-6xl">
                                    {{ provider.company_name }}
                                </h1>

                                <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-ivt-on-dark-dim">
                                    <span v-if="provider.rating" class="inline-flex items-center gap-1.5 font-semibold text-white">
                                        <StarIcon class="h-4 w-4 text-ivt-accent-bright" /> {{ provider.rating }}
                                        <span class="font-normal text-ivt-on-dark-dim">({{ provider.reviews_count }} recenzii)</span>
                                    </span>
                                    <span v-if="location" class="inline-flex items-center gap-1.5">
                                        <MapPinIcon class="h-4 w-4 text-primary-bright" /> {{ location }}
                                    </span>
                                    <span v-if="provider.response_time_label" class="inline-flex items-center gap-1.5">
                                        <ClockIcon class="h-4 w-4 text-ivt-violet-bright" /> {{ provider.response_time_label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="relative flex flex-wrap items-center gap-2.5">
                            <button
                                type="button"
                                @click="shareProvider"
                                aria-label="Distribuie profilul"
                                class="flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/20 backdrop-blur transition-all duration-200 hover:scale-105 hover:bg-white/20"
                            >
                                <ShareIcon class="h-5 w-5" />
                            </button>
                            <button
                                type="button"
                                @click="toggleFavorite"
                                class="inline-flex h-12 items-center gap-2 rounded-full bg-white/10 px-5 text-sm font-semibold text-white ring-1 ring-white/20 backdrop-blur transition-all duration-200 hover:bg-white/20"
                            >
                                <HeartIconSolid v-if="isFavorited" class="h-5 w-5 text-primary-bright" />
                                <HeartIcon v-else class="h-5 w-5" />
                                {{ isFavorited ? 'Salvat' : 'Salvează' }}
                            </button>
                            <a href="#contact" class="btn-brand h-12 px-6 text-sm">
                                Contactează <ArrowRightIcon class="h-4 w-4" />
                            </a>
                            <Transition
                                enter-from-class="opacity-0 translate-y-1"
                                enter-active-class="transition duration-200"
                                leave-to-class="opacity-0"
                                leave-active-class="transition duration-200"
                            >
                                <span v-if="shareCopied" class="absolute -top-10 left-0 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-ivt-ink shadow-lg">Link copiat</span>
                            </Transition>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="mt-10 grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <div
                            v-for="stat in stats"
                            :key="stat.label"
                            class="flex items-center gap-4 rounded-2xl bg-white/[0.06] px-5 py-4 ring-1 ring-white/10 backdrop-blur-md transition-colors duration-200 hover:bg-white/[0.1]"
                        >
                            <span class="flex h-11 w-11 flex-none items-center justify-center rounded-xl bg-brand shadow-glow-primary">
                                <component :is="stat.icon" class="h-5 w-5 text-white" />
                            </span>
                            <div class="min-w-0">
                                <b class="block font-display text-2xl font-semibold leading-none text-white">{{ stat.value }}</b>
                                <span class="mt-1 block text-xs text-ivt-on-dark-dim">{{ stat.label }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CONTENT -->
            <section class="relative py-10 lg:py-14 lg:pb-[100px]">
                <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-64 bg-gradient-to-b from-ivt-paper-2 to-transparent" />
                <div class="mx-auto grid max-w-[1600px] grid-cols-1 gap-10 px-6 lg:grid-cols-[1fr_360px] lg:px-8">

                    <div class="min-w-0">
                        <nav class="mb-8 flex overflow-x-auto [scrollbar-width:none]">
                            <div class="inline-flex gap-1 rounded-full bg-white p-1.5 shadow-ivt-soft ring-1 ring-ivt-line">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                    type="button"
                                    @click="activeTab = tab.id"
                                    class="inline-flex items-center gap-2 whitespace-nowrap rounded-full px-5 py-2.5 text-[13.5px] font-semibold transition-all duration-200"
                                    :class="activeTab === tab.id ? 'bg-brand text-white shadow-glow-primary' : 'text-ivt-ink-soft hover:bg-ivt-paper-2 hover:text-primary'"
                                >
                                    {{ tab.label }}
                                    <span
                                        v-if="tab.count !== undefined"
                                        class="rounded-full px-2 py-0.5 text-[11px] font-bold"
                                        :class="activeTab === tab.id ? 'bg-white/20 text-white' : 'bg-ivt-paper-3 text-ivt-ink-soft'"
                                    >{{ tab.count }}</span>
                                </button>
                            </div>
                        </nav>

                        <!-- LISTINGS -->
                        <section v-show="activeTab === 'anunturi'">
                            <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-primary">Portofoliu</p>
                                    <h2 class="mt-1 font-display text-2xl font-semibold text-ivt-ink sm:text-3xl">
                                        Serviciile oferite de <span class="text-gradient">{{ provider.company_name }}</span>
                                    </h2>
                                </div>
                            </div>

                            <div v-if="categoryChips.length > 1" class="-mx-6 mb-7 flex gap-2 overflow-x-auto px-6 pb-1 [scrollbar-width:none] sm:mx-0 sm:flex-wrap sm:px-0">
                                <button
                                    type="button"
                                    @click="activeCategory = null"
                                    class="inline-flex flex-none items-center gap-1.5 whitespace-nowrap rounded-full px-4 py-2 text-[13px] font-semibold ring-1 transition-all duration-200"
                                    :class="activeCategory === null ? 'bg-ivt-ink text-white ring-ivt-ink' : 'bg-white text-ivt-ink-soft ring-ivt-line hover:text-primary hover:ring-primary/30'"
                                >
                                    Toate <span class="opacity-60">{{ listings.length }}</span>
                                </button>
                                <button
                                    v-for="category in categoryChips"
                                    :key="category.id"
                                    type="button"
                                    @click="activeCategory = category.slug"
                                    class="inline-flex flex-none items-center gap-1.5 whitespace-nowrap rounded-full px-4 py-2 text-[13px] font-semibold ring-1 transition-all duration-200"
                                    :class="activeCategory === category.slug ? 'bg-ivt-ink text-white ring-ivt-ink' : 'bg-white text-ivt-ink-soft ring-ivt-line hover:text-primary hover:ring-primary/30'"
                                >
                                    <component :is="categoryIcon(category.slug)" class="h-4 w-4" />
                                    {{ category.name }} <span class="opacity-60">{{ category.count }}</span>
                                </button>
                            </div>

                            <TransitionGroup
                                v-if="filteredListings.length"
                                tag="div"
                                class="grid grid-cols-1 gap-6 sm:grid-cols-2 2xl:grid-cols-3"
                                enter-from-class="opacity-0 translate-y-3"
                                enter-active-class="transition duration-300"
                            >
                                <Link
                                    v-for="(item, index) in filteredListings"
                                    :key="item.id"
                                    v-spotlight
                                    :href="route('listings.show', item.slug)"
                                    class="ring-gradient group flex flex-col overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-ivt-line transition-all duration-300 hover:-translate-y-1.5 hover:shadow-ivt-deep"
                                >
                                    <div class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br" :class="item.cover_url ? 'bg-ivt-paper-2' : listingGradient(index)">
                                        <img v-if="item.cover_url" :src="item.cover_url" :alt="item.title" loading="lazy" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110" />
                                        <div v-else class="flex h-full w-full items-center justify-center">
                                            <component :is="categoryIcon(item.category_slug)" class="h-14 w-14 text-primary/40" />
                                        </div>
                                        <div class="absolute inset-0 bg-gradient-to-t from-ivt-ink/50 via-transparent to-transparent opacity-70 transition-opacity duration-300 group-hover:opacity-90" />

                                        <span v-if="item.is_featured" class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-ivt-accent-bright px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.06em] text-ivt-accent-ink shadow">
                                            <SparklesIcon class="h-3 w-3" /> Premium
                                        </span>
                                        <span v-if="item.rating" class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-white/95 px-2.5 py-1 text-xs font-bold text-ivt-ink shadow backdrop-blur">
                                            <StarIcon class="h-3.5 w-3.5 text-ivt-accent-bright" /> {{ item.rating }}
                                            <span class="font-medium text-ivt-ink-faint">({{ item.reviews_count }})</span>
                                        </span>
                                        <span class="absolute bottom-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-ivt-ink/60 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur">
                                            <component :is="categoryIcon(item.category_slug)" class="h-3.5 w-3.5" /> {{ item.category }}
                                        </span>
                                    </div>
                                    <div class="relative z-[2] flex flex-1 flex-col gap-4 p-5">
                                        <div><h3 class="font-display text-lg font-semibold leading-snug text-ivt-ink transition-colors group-hover:text-primary">{{ item.title }}</h3>
                                        <p v-if="item.description" class="mt-2 line-clamp-2 text-[13px] leading-relaxed text-ivt-ink-soft">{{ item.description }}</p></div>
                                        <div class="mt-auto flex items-center justify-between gap-3 border-t border-ivt-line pt-4">
                                            <span class="text-[15px] font-bold text-primary">{{ formatListingPrice(item) }}</span>
                                            <span class="inline-flex items-center gap-1 text-xs text-ivt-ink-faint">
                                                <EyeIcon class="h-3.5 w-3.5" /> {{ item.views_count }}
                                            </span>
                                        </div>
                                    </div>
                                </Link>
                            </TransitionGroup>
                            <div v-else class="flex flex-col items-center rounded-3xl border border-dashed border-ivt-line bg-ivt-paper-2 py-14 text-center">
                                <BuildingStorefrontIcon class="h-8 w-8 text-ivt-ink-faint/50" />
                                <p class="mt-3 text-sm font-medium text-ivt-ink-soft">Niciun anunț în această categorie.</p>
                            </div>
                        </section>

                        <!-- ABOUT -->
                        <section v-show="activeTab === 'despre'">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-primary">Povestea noastră</p>
                            <h2 class="mt-1 font-display text-2xl font-semibold text-ivt-ink sm:text-3xl">Despre {{ provider.company_name }}</h2>

                            <div class="mt-6 rounded-3xl bg-white p-7 shadow-sm ring-1 ring-ivt-line sm:p-9">
                                <p v-if="provider.description" class="whitespace-pre-line text-[15.5px] leading-[1.85] text-ivt-ink-soft">{{ provider.description }}</p>
                                <p v-else class="text-[15px] leading-[1.75] text-ivt-ink-faint">Acest furnizor nu a adăugat încă o descriere.</p>
                            </div>

                            <div v-if="provider.categories.length" class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                <button
                                    v-for="category in categoryChips"
                                    :key="category.id"
                                    type="button"
                                    @click="activeCategory = category.slug; activeTab = 'anunturi'"
                                    v-spotlight
                                    class="group flex items-center gap-3 rounded-2xl bg-white p-4 text-left ring-1 ring-ivt-line transition-all duration-200 hover:-translate-y-0.5 hover:shadow-ivt-soft hover:ring-primary/25"
                                >
                                    <span class="flex h-11 w-11 flex-none items-center justify-center rounded-xl bg-brand-soft text-primary transition-transform duration-200 group-hover:scale-110">
                                        <component :is="categoryIcon(category.slug)" class="h-5 w-5" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-ivt-ink">{{ category.name }}</span>
                                        <span class="block text-xs text-ivt-ink-faint">{{ category.count }} {{ category.count === 1 ? 'anunț' : 'anunțuri' }}</span>
                                    </span>
                                </button>
                            </div>
                        </section>

                        <!-- REVIEWS -->
                        <section v-show="activeTab === 'recenzii'">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-primary">Ce spun clienții</p>
                            <div class="mt-1 flex flex-wrap items-end justify-between gap-2.5">
                                <h2 class="font-display text-2xl font-semibold text-ivt-ink sm:text-3xl">Recenzii</h2>
                                <span class="text-[13px] text-ivt-ink-faint">{{ provider.reviews_count }} recenzii verificate, din toate anunțurile</span>
                            </div>

                            <div v-if="!reviews.length" class="mt-6 flex flex-col items-center justify-center rounded-3xl border border-dashed border-ivt-line bg-ivt-paper-2 px-5 py-14 text-center">
                                <FaceFrownIcon class="h-8 w-8 text-ivt-ink-faint/50" />
                                <p class="mt-3 text-sm font-semibold text-ivt-ink">Nicio recenzie încă</p>
                                <p class="mt-1 text-sm text-ivt-ink-faint">Acest furnizor nu a primit încă recenzii.</p>
                            </div>

                            <template v-else>
                                <div class="relative mt-6 flex flex-wrap items-center gap-8 overflow-hidden rounded-3xl bg-ivt-ink p-7 text-ivt-on-dark sm:gap-12 sm:p-9">
                                    <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-ivt-violet/40 blur-3xl" />
                                    <div class="pointer-events-none absolute -bottom-20 left-10 h-48 w-48 rounded-full bg-primary/30 blur-3xl" />
                                    <div class="relative text-center">
                                        <b class="text-gradient-light block font-display text-6xl font-semibold leading-none">{{ provider.rating }}</b>
                                        <div class="mt-3 flex justify-center gap-0.5">
                                            <span v-for="n in 5" :key="n" class="relative h-5 w-5">
                                                <StarIcon class="absolute inset-0 h-5 w-5 text-white/15" />
                                                <span class="absolute inset-y-0 left-0 overflow-hidden" :style="{ width: starFill(provider.rating, n) }">
                                                    <StarIcon class="h-5 w-5 text-ivt-accent-bright" />
                                                </span>
                                            </span>
                                        </div>
                                        <span class="mt-2 block text-xs text-ivt-on-dark-dim">din {{ provider.reviews_count }} recenzii</span>
                                    </div>
                                    <div class="relative min-w-[220px] flex-1 space-y-2">
                                        <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="flex items-center gap-3 text-xs text-ivt-on-dark-dim">
                                            <span class="inline-flex w-7 items-center gap-0.5">{{ star }} <StarIcon class="h-3 w-3 text-ivt-accent-bright" /></span>
                                            <span class="h-2 flex-1 overflow-hidden rounded-full bg-white/10">
                                                <span class="block h-full rounded-full bg-brand transition-[width] duration-700" :style="{ width: ratingPct(star) + '%' }" />
                                            </span>
                                            <span class="w-9 text-right font-semibold text-white">{{ ratingPct(star) }}%</span>
                                        </div>
                                    </div>
                                </div>

                                <ul class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-2">
                                    <li v-for="review in visibleReviews" :key="review.id" class="flex flex-col rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ivt-line transition-shadow duration-200 hover:shadow-ivt-soft">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-brand text-sm font-semibold text-white shadow-glow-primary">
                                                {{ initials(review.author) }}
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-semibold text-ivt-ink">{{ review.author }}</p>
                                                <p class="truncate text-xs text-ivt-ink-faint">{{ review.created_at }}</p>
                                            </div>
                                            <div class="flex flex-none gap-0.5">
                                                <StarIcon v-for="n in 5" :key="n" class="h-3.5 w-3.5" :class="n <= review.rating ? 'text-ivt-accent-bright' : 'text-ivt-paper-3'" />
                                            </div>
                                        </div>
                                        <p v-if="review.listing_title" class="mt-4 inline-flex self-start rounded-full bg-ivt-paper-2 px-3 py-1 text-[11px] font-semibold text-ivt-ink-soft">
                                            {{ review.listing_title }}
                                        </p>
                                        <p v-if="review.comment" class="mt-3 text-sm leading-relaxed text-ivt-ink-soft">„{{ review.comment }}”</p>
                                        <div v-if="review.provider_reply" class="mt-4 rounded-2xl border-l-[3px] border-primary bg-ivt-paper-2 p-4">
                                            <p class="text-xs font-semibold text-ivt-ink">
                                                Răspuns de la {{ provider.company_name }}
                                                <span class="font-normal text-ivt-ink-faint">· {{ review.provider_replied_at }}</span>
                                            </p>
                                            <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-ivt-ink-soft">{{ review.provider_reply }}</p>
                                        </div>
                                    </li>
                                </ul>

                                <div v-if="reviews.length > 4" class="mt-6 text-center">
                                    <button
                                        type="button"
                                        @click="reviewsExpanded = !reviewsExpanded"
                                        class="rounded-full bg-white px-6 py-3 text-sm font-semibold text-ivt-ink ring-1 ring-ivt-line transition-all duration-200 hover:text-primary hover:ring-primary/30 hover:shadow-ivt-soft"
                                    >
                                        {{ reviewsExpanded ? 'Arată mai puține' : `Vezi toate cele ${reviews.length} recenzii` }}
                                    </button>
                                </div>
                            </template>
                        </section>
                    </div>

                    <!-- SIDEBAR -->
                    <aside class="lg:sticky lg:top-24 lg:self-start">
                        <div class="flex flex-col gap-5">
                            <div id="contact" class="scroll-mt-28 overflow-hidden rounded-3xl bg-white shadow-ivt-deep ring-1 ring-ivt-line">
                                <div class="relative overflow-hidden bg-brand px-6 py-5 text-white">
                                    <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/15 blur-2xl" />
                                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/75">Contact direct</p>
                                    <h3 class="mt-1 font-display text-xl font-semibold leading-tight">{{ provider.company_name }}</h3>
                                    <p v-if="provider.response_time_label" class="mt-2 inline-flex items-center gap-1.5 text-xs text-white/85">
                                        <span class="relative flex h-2 w-2">
                                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-300 opacity-75" />
                                            <span class="relative inline-flex h-2 w-2 rounded-full bg-success-400" />
                                        </span>
                                        {{ provider.response_time_label }}
                                    </p>
                                </div>

                                <div class="space-y-2.5 p-6">
                                    <a
                                        v-if="provider.phone"
                                        :href="`tel:${provider.phone}`"
                                        class="flex items-center justify-center gap-2 rounded-2xl bg-ivt-ink px-4 py-3.5 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-ivt-ink-2 hover:shadow-ivt-soft"
                                    >
                                        <PhoneIcon class="h-4 w-4" /> {{ provider.phone }}
                                    </a>
                                    <a
                                        v-if="provider.whatsapp"
                                        :href="`https://wa.me/${provider.whatsapp.replace(/[^0-9]/g, '')}`"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex items-center justify-center gap-2 rounded-2xl bg-success-50 px-4 py-3.5 text-sm font-semibold text-success-700 ring-1 ring-success-200 transition-all duration-200 hover:-translate-y-0.5 hover:bg-success-100"
                                    >
                                        <ChatBubbleLeftEllipsisIcon class="h-4 w-4" /> Scrie pe WhatsApp
                                    </a>
                                    <a
                                        v-if="provider.email"
                                        :href="`mailto:${provider.email}`"
                                        class="flex items-center justify-center gap-2 rounded-2xl px-4 py-3.5 text-sm font-semibold text-ivt-ink ring-1 ring-ivt-line transition-all duration-200 hover:-translate-y-0.5 hover:text-primary hover:ring-primary/30"
                                    >
                                        <EnvelopeIcon class="h-4 w-4 flex-none" /> <span class="truncate">{{ provider.email }}</span>
                                    </a>

                                    <div v-if="socialLinks.length || provider.website" class="flex flex-wrap gap-2 border-t border-ivt-line pt-4">
                                        <a
                                            v-for="social in socialLinks"
                                            :key="social.platform"
                                            :href="social.url"
                                            target="_blank"
                                            rel="noopener"
                                            :aria-label="social.platform"
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-ivt-paper-2 text-[11px] font-bold text-ivt-ink-soft transition-all duration-200 hover:-translate-y-0.5 hover:bg-brand hover:text-white"
                                        >
                                            {{ social.label }}
                                        </a>
                                        <a
                                            v-if="provider.website"
                                            :href="provider.website"
                                            target="_blank"
                                            rel="noopener"
                                            aria-label="Website"
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-ivt-paper-2 text-ivt-ink-soft transition-all duration-200 hover:-translate-y-0.5 hover:bg-brand hover:text-white"
                                        >
                                            <GlobeAltIcon class="h-4 w-4" />
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Availability check -->
                            <div class="rounded-3xl bg-white p-6 ring-1 ring-ivt-line">
                                <p class="flex items-center gap-2.5 text-sm font-semibold text-ivt-ink">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-soft text-primary"><CalendarIcon class="h-4 w-4" /></span>
                                    Verifică disponibilitatea
                                </p>
                                <input
                                    v-model="checkDate"
                                    type="date"
                                    :min="today"
                                    class="mt-4 w-full rounded-xl border-ivt-line bg-ivt-paper-2 text-sm text-ivt-ink focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <Transition
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-active-class="transition duration-200"
                                >
                                    <p
                                        v-if="checkDate"
                                        :key="checkDate"
                                        class="mt-3 flex items-center gap-2 rounded-xl px-3.5 py-2.5 text-sm font-semibold"
                                        :class="dateIsAvailable ? 'bg-success-50 text-success-700' : 'bg-danger-50 text-danger-700'"
                                    >
                                        <CheckIcon v-if="dateIsAvailable" class="h-4 w-4" />
                                        <XMarkIcon v-else class="h-4 w-4" />
                                        {{ dateIsAvailable ? 'Disponibil în această dată' : 'Ocupat în această dată' }}
                                    </p>
                                </Transition>
                            </div>

                            <div v-if="infoRows.length" class="rounded-3xl bg-ivt-paper-2 p-2">
                                <div v-for="row in infoRows" :key="row.label" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-[13.5px]">
                                    <component :is="row.icon" class="h-4 w-4 flex-none text-primary" />
                                    <span class="text-ivt-ink-faint">{{ row.label }}</span>
                                    <span class="ml-auto text-right font-semibold text-ivt-ink">{{ row.value }}</span>
                                </div>
                            </div>
                        </div>
                    </aside>

                </div>
            </section>
        </main>

        <!-- Mobile contact bar -->
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-ivt-line bg-white/90 px-4 py-3 backdrop-blur-lg lg:hidden">
            <div class="flex items-center gap-2.5">
                <button
                    type="button"
                    @click="toggleFavorite"
                    :aria-label="isFavorited ? 'Elimină din favorite' : 'Salvează'"
                    class="flex h-12 w-12 flex-none items-center justify-center rounded-full ring-1 ring-ivt-line"
                    :class="isFavorited ? 'text-primary' : 'text-ivt-ink'"
                >
                    <HeartIconSolid v-if="isFavorited" class="h-5 w-5" />
                    <HeartIcon v-else class="h-5 w-5" />
                </button>
                <a
                    v-if="provider.phone"
                    :href="`tel:${provider.phone}`"
                    class="flex h-12 flex-1 items-center justify-center gap-2 rounded-full bg-ivt-ink text-sm font-semibold text-white"
                >
                    <PhoneIcon class="h-4 w-4" /> Sună
                </a>
                <a href="#contact" class="btn-brand h-12 flex-1 text-sm">
                    Contactează
                </a>
            </div>
        </div>

        <SiteFooter />
    </div>
</template>
