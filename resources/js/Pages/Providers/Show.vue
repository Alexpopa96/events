<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Listbox, ListboxButton, ListboxOption, ListboxOptions } from '@headlessui/vue';
import {
    MapPinIcon,
    PhoneIcon,
    EnvelopeIcon,
    ChatBubbleLeftEllipsisIcon,
    ShareIcon,
    GlobeAltIcon,
    CheckBadgeIcon,
    EyeIcon,
    HeartIcon,
    CalendarIcon,
    CheckIcon,
    XMarkIcon,
    ClockIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    PlayCircleIcon,
    BuildingStorefrontIcon,
    Squares2X2Icon,
    ChevronUpDownIcon,
    SparklesIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline';
import { HeartIcon as HeartIconSolid, StarIcon } from '@heroicons/vue/24/solid';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import DatePickerModal from '@/Components/DatePickerModal.vue';
import { categoryIcon } from '@/Composables/useCategoryIcon';
import { formatListingPrice } from '@/Composables/useListingPrice';
import { useProviderFavoriteToggle } from '@/Composables/useProviderFavoriteToggle';

const props = defineProps({
    provider: { type: Object, required: true },
    listings: { type: Array, default: () => [] },
    gallery: { type: Array, default: () => [] },
    reviews: { type: Array, default: () => [] },
    unavailableDates: { type: Array, default: () => [] },
});

/* ---------- availability check ---------- */
const checkDate = ref('');
const dateIsAvailable = computed(() => !props.unavailableDates.includes(checkDate.value));

const { isFavorited, toggle: toggleFavorite } = useProviderFavoriteToggle(
    props.provider.slug,
    props.provider.is_favorited,
    null,
    props.provider.logo_url ?? props.provider.cover_url ?? props.gallery[0]?.url ?? null,
);

const location = computed(() => [props.provider.locality, props.provider.county].filter(Boolean).join(', '));

const initials = (name) => (name || '')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

/* ---------- hero title: last word gets the gradient + underline ---------- */
const titleWords = computed(() => props.provider.company_name.trim().split(/\s+/));
const titleLead = computed(() => titleWords.value.slice(0, -1).join(' '));
const titleAccent = computed(() => titleWords.value.at(-1));

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
    { id: 'anunturi', label: 'Anunțuri', count: props.listings.length },
    { id: 'despre', label: 'Despre' },
    { id: 'galerie', label: 'Galerie', count: props.gallery.length },
    { id: 'recenzii', label: 'Recenzii', count: props.provider.reviews_count },
]);
const activeTab = ref('anunturi');

onMounted(() => {
    if (window.location.hash === '#recenzii') activeTab.value = 'recenzii';
});

/* ---------- listings filter ---------- */
const activeCategory = ref(null);

const categoryChips = computed(() => props.provider.categories.map((category) => ({
    ...category,
    count: props.listings.filter((item) => item.category_slug === category.slug).length,
})));

// Dropdown options: "all" first, then one entry per category the provider lists in.
const categoryOptions = computed(() => [
    { slug: null, name: 'Toate serviciile', count: props.listings.length },
    ...categoryChips.value,
]);
const activeChip = computed(() => categoryChips.value.find((category) => category.slug === activeCategory.value) ?? null);

const filteredListings = computed(() => (
    activeCategory.value
        ? props.listings.filter((item) => item.category_slug === activeCategory.value)
        : props.listings
));

/* ---------- gallery / lightbox ---------- */
const lightboxIndex = ref(null);
const activeLightboxMedia = computed(() => (lightboxIndex.value !== null ? props.gallery[lightboxIndex.value] : null));
const openLightbox = (index) => { lightboxIndex.value = index; };
const closeLightbox = () => { lightboxIndex.value = null; };
const nextMedia = () => { lightboxIndex.value = (lightboxIndex.value + 1) % props.gallery.length; };
const prevMedia = () => { lightboxIndex.value = (lightboxIndex.value - 1 + props.gallery.length) % props.gallery.length; };

const onKeydown = (event) => {
    if (lightboxIndex.value === null) return;
    if (event.key === 'Escape') closeLightbox();
    if (event.key === 'ArrowRight') nextMedia();
    if (event.key === 'ArrowLeft') prevMedia();
};

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));

/* ---------- reviews ---------- */
const reviewsExpanded = ref(false);
const visibleReviews = computed(() => (reviewsExpanded.value ? props.reviews : props.reviews.slice(0, 3)));

const ratingCounts = computed(() => {
    const counts = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
    props.reviews.forEach((review) => { counts[review.rating] = (counts[review.rating] ?? 0) + 1; });
    return counts;
});
const ratingPct = (star) => (props.reviews.length ? Math.round((ratingCounts.value[star] / props.reviews.length) * 100) : 0);

/* ---------- sidebar ---------- */
const socialLabels = { facebook: 'FB', instagram: 'IG', tiktok: 'TT' };
const socialLinks = computed(() => Object.entries(props.provider.social_links ?? {})
    .filter(([, url]) => url)
    .map(([platform, url]) => ({ platform, url, label: socialLabels[platform] ?? platform.slice(0, 2).toUpperCase() })));

const infoRows = computed(() => [
    { label: 'Categorii', value: props.provider.categories.map((category) => category.name).join(', ') },
    { label: 'Locație', value: location.value },
    { label: 'Adresă', value: props.provider.address },
    { label: 'Membru din', value: props.provider.member_since },
    { label: 'Anunțuri active', value: props.provider.listings_count },
].filter((row) => row.value !== null && row.value !== undefined && row.value !== ''));
</script>

<template>
    <ClientLayout full-bleed :title="$page.props.seo?.full_title ?? `${provider.company_name} — Invita`">
        <!-- PROFILE HERO -->
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

            <div class="relative mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">
                <nav class="flex items-center gap-2 text-[13px] text-ivt-ink-faint" aria-label="Breadcrumb">
                    <Link :href="route('home')" class="transition-colors hover:text-primary">Acasă</Link>
                    <span aria-hidden="true" class="opacity-50">/</span>
                    <Link :href="route('providers.index')" class="transition-colors hover:text-primary">Furnizori</Link>
                    <span aria-hidden="true" class="opacity-50">/</span>
                    <span class="truncate text-ivt-ink-soft">{{ provider.company_name }}</span>
                </nav>

                <div class="mt-8 flex flex-col gap-8 lg:mt-10 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 py-1 pl-1.5 pr-3.5 text-[12.5px] font-semibold text-ivt-ink-soft shadow-sm backdrop-blur">
                                <span class="flex items-center gap-1 rounded-full bg-brand px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider text-white">
                                    <component :is="categoryIcon(provider.categories[0]?.slug)" class="h-3 w-3" stroke-width="2.2" />
                                    {{ provider.listings_count }}
                                </span>
                                {{ provider.listings_count === 1 ? 'anunț activ' : 'anunțuri active' }}<template v-if="provider.categories.length"> · {{ provider.categories.map((category) => category.name).join(', ') }}</template>
                            </p>
                            <span v-if="provider.is_featured" class="inline-flex items-center gap-1 rounded-full bg-ivt-accent-soft px-3 py-1 text-[10.5px] font-bold uppercase tracking-[0.08em] text-ivt-accent-ink">
                                <SparklesIcon class="h-3.5 w-3.5" /> Premium
                            </span>
                            <span v-if="provider.is_verified" class="inline-flex items-center gap-1 rounded-full border border-ivt-line bg-white/80 px-3 py-1 text-[10.5px] font-bold uppercase tracking-[0.06em] text-ivt-teal backdrop-blur" title="CUI confirmat la ANAF">
                                <CheckBadgeIcon class="h-3.5 w-3.5" /> Verificat ANAF
                            </span>
                        </div>

                        <h1 class="mt-6 text-balance font-display text-[34px] font-bold leading-[1.05] tracking-tight text-ivt-ink sm:text-[44px] lg:text-[52px]">
                            <template v-if="titleLead">{{ `${titleLead} ` }}</template>
                            <span class="relative whitespace-nowrap">
                                <span class="text-gradient">{{ titleAccent }}</span>
                                <svg class="absolute -bottom-2 left-0 h-3 w-full text-ivt-accent-bright" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true">
                                    <path d="M2 9 C 80 2, 200 2, 298 7" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                </svg>
                            </span>
                        </h1>

                        <p v-if="provider.description" class="mt-6 max-w-[560px] text-[15.5px] leading-relaxed text-ivt-ink-soft">
                            {{ provider.description }}
                        </p>

                        <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-ivt-ink-soft">
                            <span v-if="provider.rating" class="inline-flex items-center gap-1.5 font-semibold text-ivt-ink">
                                <StarIcon class="h-4 w-4 text-ivt-accent-bright" /> {{ provider.rating }}
                                <span class="font-normal text-ivt-ink-faint">({{ provider.reviews_count }} recenzii)</span>
                            </span>
                            <span v-if="location" class="inline-flex items-center gap-1.5">
                                <MapPinIcon class="h-4 w-4 text-primary" /> {{ location }}
                            </span>
                            <span v-if="provider.response_time_label" class="inline-flex items-center gap-1.5">
                                <ClockIcon class="h-4 w-4 text-ivt-violet" /> {{ provider.response_time_label }}
                            </span>
                            <span v-if="provider.member_since" class="inline-flex items-center gap-1.5">
                                <SparklesIcon class="h-4 w-4 text-ivt-violet" /> pe platformă din {{ provider.member_since }}
                            </span>
                        </div>
                    </div>

                    <div class="ring-gradient relative flex w-full flex-none items-center gap-1.5 self-start rounded-[22px] border border-ivt-line bg-white p-2 shadow-ivt-soft transition-shadow hover:shadow-ivt-deep sm:w-auto lg:self-end">
                        <button
                            type="button"
                            @click="shareProvider"
                            aria-label="Distribuie profilul"
                            class="flex h-12 w-12 items-center justify-center rounded-2xl text-ivt-ink-soft transition-colors hover:bg-ivt-paper-2 hover:text-primary"
                        >
                            <ShareIcon class="h-5 w-5" />
                        </button>
                        <button
                            type="button"
                            @click="toggleFavorite"
                            class="inline-flex h-12 items-center gap-2 rounded-2xl px-4 text-sm font-semibold text-ivt-ink transition-colors hover:bg-ivt-paper-2 hover:text-primary"
                        >
                            <HeartIconSolid v-if="isFavorited" class="h-5 w-5 text-primary" />
                            <HeartIcon v-else class="h-5 w-5" />
                            {{ isFavorited ? 'Salvat' : 'Salvează' }}
                        </button>
                        <a href="#contact" class="btn-brand flex-1 justify-center whitespace-nowrap rounded-2xl px-7 py-3.5 text-sm font-bold sm:flex-none">
                            Contactează <ArrowRightIcon class="h-4 w-4" stroke-width="2.5" />
                        </a>
                        <Transition
                            enter-from-class="opacity-0 translate-y-1"
                            enter-active-class="transition duration-200"
                            leave-to-class="opacity-0"
                            leave-active-class="transition duration-200"
                        >
                            <span v-if="shareCopied" class="absolute -top-10 left-0 rounded-full bg-ivt-ink px-3 py-1.5 text-xs font-semibold text-white shadow-lg">Link copiat</span>
                        </Transition>
                    </div>
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-[1600px] px-4 pb-5 sm:px-6 sm:pb-6 lg:px-8 lg:pb-8">
        <div class="mt-2 grid grid-cols-1 gap-8 lg:grid-cols-[1fr_380px] lg:gap-10">
            <!-- Main column -->
            <div class="min-w-0">
                <!-- Tabs -->
                <nav class="-mx-4 mb-8 overflow-x-auto px-4 [scrollbar-width:none] sm:mx-0 sm:px-0">
                    <div class="flex min-w-max gap-7 border-b border-ivt-line">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            type="button"
                            @click="activeTab = tab.id"
                            class="group relative -mb-px inline-flex items-center gap-2 whitespace-nowrap pb-3.5 pt-1 text-[15px] font-semibold transition-colors duration-200"
                            :class="activeTab === tab.id ? 'text-ivt-ink' : 'text-ivt-ink-soft hover:text-ivt-ink'"
                        >
                            {{ tab.label }}
                            <span
                                v-if="tab.count !== undefined"
                                class="min-w-[1.5rem] rounded-md px-1.5 py-0.5 text-center text-[11px] font-bold tabular-nums transition-colors duration-200"
                                :class="activeTab === tab.id ? 'bg-primary/10 text-primary' : 'bg-ivt-paper-3 text-ivt-ink-soft group-hover:text-ivt-ink'"
                            >{{ tab.count }}</span>
                            <span
                                class="absolute inset-x-0 bottom-0 h-[3px] origin-left rounded-full bg-brand transition-transform duration-300"
                                :class="activeTab === tab.id ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100 group-hover:opacity-30'"
                            />
                        </button>
                    </div>
                </nav>

                <!-- Listings -->
                <section v-show="activeTab === 'anunturi'" class="lg:mb-14">
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                        <h2 class="font-display text-2xl text-ivt-ink">Serviciile oferite</h2>

                        <Listbox v-if="categoryChips.length > 1" v-model="activeCategory" as="div" class="relative w-full sm:w-auto">
                            <ListboxButton
                                class="flex w-full items-center gap-3 rounded-2xl bg-white py-2 pl-2 pr-3.5 text-left ring-1 ring-ivt-line transition-all duration-150 hover:ring-primary/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 sm:min-w-[260px]"
                            >
                                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <component :is="activeChip ? categoryIcon(activeChip.slug) : Squares2X2Icon" class="h-[18px] w-[18px]" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-[10.5px] font-bold uppercase tracking-[0.1em] text-ivt-ink-soft">Serviciu</span>
                                    <span class="block truncate text-sm font-semibold text-ivt-ink">
                                        {{ activeChip?.name ?? 'Toate serviciile' }}
                                        <span class="font-normal text-ivt-ink-soft">· {{ activeChip?.count ?? listings.length }}</span>
                                    </span>
                                </span>
                                <ChevronUpDownIcon class="h-5 w-5 flex-none text-ivt-ink-soft" />
                            </ListboxButton>

                            <Transition
                                enter-active-class="transition duration-150 ease-out"
                                enter-from-class="opacity-0 -translate-y-1 scale-[0.98]"
                                leave-active-class="transition duration-100 ease-in"
                                leave-to-class="opacity-0 -translate-y-1"
                            >
                                <ListboxOptions
                                    class="absolute right-0 z-30 mt-2 max-h-80 w-full origin-top overflow-auto rounded-2xl bg-white p-1.5 shadow-2xl shadow-ivt-ink/10 ring-1 ring-ivt-line focus:outline-none sm:min-w-[280px]"
                                >
                                    <ListboxOption
                                        v-for="option in categoryOptions"
                                        :key="option.slug ?? 'all'"
                                        v-slot="{ active, selected }"
                                        :value="option.slug"
                                        as="template"
                                    >
                                        <li
                                            class="flex cursor-pointer items-center gap-3 rounded-xl px-2.5 py-2 text-sm transition-colors duration-100"
                                            :class="active ? 'bg-primary/5' : ''"
                                        >
                                            <span
                                                class="flex h-8 w-8 flex-none items-center justify-center rounded-lg transition-colors"
                                                :class="selected ? 'bg-brand text-white' : 'bg-primary/10 text-primary'"
                                            >
                                                <component :is="option.slug ? categoryIcon(option.slug) : Squares2X2Icon" class="h-4 w-4" />
                                            </span>
                                            <span class="min-w-0 flex-1 truncate" :class="selected ? 'font-semibold text-ivt-ink' : 'font-medium text-ivt-ink-soft'">{{ option.name }}</span>
                                            <span class="rounded-full bg-primary/5 px-2 py-0.5 text-[11px] font-semibold text-ivt-ink-soft">{{ option.count }}</span>
                                            <CheckIcon v-if="selected" class="h-4 w-4 flex-none text-primary" stroke-width="2.5" />
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </Transition>
                        </Listbox>
                    </div>

                    <div v-if="filteredListings.length" class="grid grid-cols-2 gap-3 sm:gap-6">
                        <Link
                            v-for="item in filteredListings"
                            :key="item.id"
                            :href="route('listings.show', item.slug)"
                            class="group flex min-w-0 flex-col overflow-hidden rounded-2xl bg-white p-1.5 ring-1 ring-ivt-line sm:rounded-3xl sm:p-2 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-ivt-ink/5"
                        >
                            <div class="relative aspect-square overflow-hidden rounded-xl bg-primary/5 sm:aspect-[4/3] sm:rounded-2xl">
                                <img v-if="item.cover_url" :src="item.cover_url" :alt="item.title" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                                <div v-else class="flex h-full w-full items-center justify-center">
                                    <component :is="categoryIcon(item.category_slug)" class="h-14 w-14 text-primary/30" />
                                </div>
                                <span v-if="item.is_featured" class="absolute left-2 top-2 rounded-full bg-primary px-2 py-0.5 text-[9px] sm:left-3 sm:top-3 sm:px-2.5 sm:py-1 sm:text-[10px] font-bold uppercase tracking-[0.07em] text-white shadow">Recomandat</span>
                                <span v-if="item.rating" class="absolute right-2 top-2 inline-flex items-center gap-1 rounded-full bg-white/95 px-2 py-0.5 text-[11px] sm:right-3 sm:top-3 sm:px-2.5 sm:py-1 sm:text-xs font-semibold text-ivt-ink shadow backdrop-blur">
                                    <StarIcon class="h-3.5 w-3.5 text-primary" /> {{ item.rating }}
                                    <span class="hidden font-normal text-ivt-ink-soft sm:inline">({{ item.reviews_count }})</span>
                                </span>
                            </div>
                            <div class="flex flex-1 flex-col px-1.5 pb-1.5 pt-3 sm:px-3 sm:pb-3 sm:pt-4">
                                <p class="inline-flex min-w-0 items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.08em] text-primary sm:text-[11px]">
                                    <component :is="categoryIcon(item.category_slug)" class="h-3.5 w-3.5 flex-none" /> <span class="truncate">{{ item.category }}</span>
                                </p>
                                <h3 class="mt-1 line-clamp-2 font-display text-[14.5px] leading-snug sm:mt-1.5 sm:text-lg text-ivt-ink transition-colors group-hover:text-primary">{{ item.title }}</h3>
                                <p v-if="item.description" class="mt-1.5 hidden line-clamp-2 text-[13px] sm:[display:-webkit-box] leading-relaxed text-ivt-ink-soft">{{ item.description }}</p>
                                <div class="mt-auto pt-3 sm:pt-4">
                                    <div class="flex items-center justify-between gap-2 border-t border-ivt-line pt-2.5 sm:gap-3 sm:pt-3.5">
                                        <span class="truncate text-[13px] font-bold text-primary sm:text-[15px]">{{ formatListingPrice(item) }}</span>
                                        <span class="hidden flex-none items-center gap-1 text-xs text-ivt-ink-soft sm:inline-flex">
                                            <EyeIcon class="h-3.5 w-3.5" /> {{ item.views_count }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>
                    <div v-else class="flex flex-col items-center rounded-3xl border border-dashed border-ivt-line bg-white py-14 text-center">
                        <BuildingStorefrontIcon class="h-8 w-8 text-primary/30" />
                        <p class="mt-3 text-sm font-medium text-ivt-ink-soft">Niciun anunț în această categorie.</p>
                    </div>
                </section>

                <!-- About -->
                <section v-show="activeTab === 'despre'" class="lg:mb-14">
                    <h2 class="mb-4 font-display text-2xl text-ivt-ink">Despre {{ provider.company_name }}</h2>

                    <p v-if="provider.description" class="whitespace-pre-line text-[15px] leading-[1.8] text-ivt-ink-soft">{{ provider.description }}</p>
                    <p v-else class="text-[15px] leading-[1.8] text-ivt-ink-soft/70">Acest furnizor nu a adăugat încă o descriere.</p>

                    <div class="mt-7 grid grid-cols-3 gap-3 sm:gap-4">
                        <div class="rounded-3xl bg-primary/5 px-5 py-5 ring-1 ring-primary/10">
                            <StarIcon class="h-5 w-5 text-primary" />
                            <p class="mt-2 font-display text-2xl text-ivt-ink">{{ provider.rating ?? '—' }}</p>
                            <span class="text-xs text-ivt-ink-soft">rating mediu</span>
                        </div>
                        <div class="rounded-3xl bg-primary/5 px-5 py-5 ring-1 ring-primary/10">
                            <ChatBubbleLeftEllipsisIcon class="h-5 w-5 text-primary" />
                            <p class="mt-2 font-display text-2xl text-ivt-ink">{{ provider.reviews_count }}</p>
                            <span class="text-xs text-ivt-ink-soft">recenzii</span>
                        </div>
                        <div class="rounded-3xl bg-primary/5 px-5 py-5 ring-1 ring-primary/10">
                            <Squares2X2Icon class="h-5 w-5 text-primary" />
                            <p class="mt-2 font-display text-2xl text-ivt-ink">{{ provider.listings_count }}</p>
                            <span class="text-xs text-ivt-ink-soft">anunțuri active</span>
                        </div>
                    </div>

                    <div v-if="categoryChips.length" class="mt-7">
                        <h3 class="mb-3 text-sm font-semibold text-ivt-ink">Servicii oferite</h3>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <button
                                v-for="category in categoryChips"
                                :key="category.id"
                                type="button"
                                @click="activeCategory = category.slug; activeTab = 'anunturi'"
                                class="group flex items-center gap-3 rounded-2xl bg-white p-3.5 text-left ring-1 ring-ivt-line transition-all duration-200 hover:bg-primary/5 hover:ring-primary/30"
                            >
                                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <component :is="categoryIcon(category.slug)" class="h-5 w-5" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-ivt-ink">{{ category.name }}</span>
                                    <span class="block text-xs text-ivt-ink-soft">{{ category.count }} {{ category.count === 1 ? 'anunț' : 'anunțuri' }}</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Gallery -->
                <section v-show="activeTab === 'galerie'" class="lg:mb-14">
                    <div class="mb-5 flex items-center justify-between">
                        <h2 class="font-display text-2xl text-ivt-ink">Galerie</h2>
                        <span v-if="gallery.length" class="text-[13px] text-ivt-ink-soft">{{ gallery.length }} fotografii</span>
                    </div>

                    <p v-if="!gallery.length" class="rounded-3xl border border-dashed border-ivt-line bg-white py-12 text-center text-sm text-ivt-ink-soft">
                        Nicio fotografie încă
                    </p>

                    <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <button
                            v-for="(media, index) in gallery"
                            :key="media.id"
                            type="button"
                            @click="openLightbox(index)"
                            class="group relative aspect-square overflow-hidden rounded-2xl bg-primary/5"
                        >
                            <img
                                v-if="media.type !== 'video'"
                                :src="media.url"
                                :alt="media.listing_title ?? provider.company_name"
                                loading="lazy"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center bg-ivt-ink">
                                <PlayCircleIcon class="h-9 w-9 text-white/80" />
                            </div>
                            <span
                                v-if="media.listing_title"
                                class="absolute inset-x-2.5 bottom-2.5 truncate rounded-full bg-ivt-ink/70 px-2.5 py-1 text-[11px] font-semibold text-white opacity-0 backdrop-blur transition-opacity duration-200 group-hover:opacity-100"
                            >{{ media.listing_title }}</span>
                        </button>
                    </div>
                </section>

                <!-- Reviews -->
                <section v-show="activeTab === 'recenzii'">
                    <div class="mb-5 flex flex-wrap items-end justify-between gap-2">
                        <h2 class="font-display text-2xl text-ivt-ink">Recenzii</h2>
                        <span v-if="reviews.length" class="text-[13px] text-ivt-ink-soft">din toate anunțurile furnizorului</span>
                    </div>

                    <div v-if="!reviews.length" class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-ivt-line bg-white px-5 py-14 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <StarIcon class="h-6 w-6" />
                        </span>
                        <p class="mt-3 text-sm font-medium text-ivt-ink">Nicio recenzie încă</p>
                        <p class="mt-1 text-sm text-ivt-ink-soft">Acest furnizor nu a primit încă recenzii.</p>
                    </div>

                    <template v-else>
                        <div class="mb-7 flex flex-wrap items-center gap-9 rounded-3xl bg-primary/5 p-7 ring-1 ring-primary/10">
                            <div class="text-center">
                                <p class="font-display text-5xl text-ivt-ink">{{ provider.rating }}</p>
                                <div class="mt-1 flex justify-center gap-0.5">
                                    <StarIcon v-for="n in 5" :key="n" class="h-4 w-4" :class="n <= Math.round(provider.rating ?? 0) ? 'text-primary' : 'text-primary/20'" />
                                </div>
                                <p class="mt-1.5 text-xs text-ivt-ink-soft">din {{ provider.reviews_count }} recenzii</p>
                            </div>
                            <div class="min-w-[220px] flex-1 space-y-2">
                                <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="flex items-center gap-2.5 text-xs text-ivt-ink-soft">
                                    <span class="w-6">{{ star }}★</span>
                                    <span class="h-2 flex-1 overflow-hidden rounded-full bg-white">
                                        <span class="block h-full rounded-full bg-primary" :style="{ width: ratingPct(star) + '%' }" />
                                    </span>
                                    <span class="w-8 text-right">{{ ratingPct(star) }}%</span>
                                </div>
                            </div>
                        </div>

                        <ul class="space-y-3">
                            <li v-for="review in visibleReviews" :key="review.id" class="rounded-3xl bg-white p-5 ring-1 ring-ivt-line">
                                <div class="mb-2 flex items-center gap-3">
                                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary-bright text-xs font-semibold text-white">
                                        {{ initials(review.author) }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-ivt-ink">{{ review.author }}</p>
                                        <p class="truncate text-xs text-ivt-ink-soft">
                                            {{ review.created_at }}<template v-if="review.listing_title"> · {{ review.listing_title }}</template>
                                        </p>
                                    </div>
                                    <span class="flex flex-none items-center gap-1 rounded-full bg-primary/5 px-2.5 py-1 text-xs font-semibold text-ivt-ink">
                                        <StarIcon class="h-3.5 w-3.5 text-primary" /> {{ review.rating }}
                                    </span>
                                </div>
                                <p v-if="review.comment" class="text-sm leading-relaxed text-ivt-ink-soft">{{ review.comment }}</p>
                                <div v-if="review.provider_reply" class="mt-3 rounded-2xl bg-primary/5 p-4">
                                    <p class="text-xs font-semibold text-ivt-ink">
                                        Răspuns de la {{ provider.company_name }}
                                        <span class="font-normal text-ivt-ink-soft">· {{ review.provider_replied_at }}</span>
                                    </p>
                                    <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-ivt-ink-soft">{{ review.provider_reply }}</p>
                                </div>
                            </li>
                        </ul>

                        <button
                            v-if="reviews.length > 3"
                            type="button"
                            @click="reviewsExpanded = !reviewsExpanded"
                            class="mt-4 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-ivt-ink ring-1 ring-ivt-line transition-colors duration-150 hover:text-primary hover:ring-primary/40"
                        >
                            {{ reviewsExpanded ? 'Arată mai puține' : `Vezi toate cele ${reviews.length} recenzii` }}
                        </button>
                    </template>
                </section>
            </div>

            <!-- Sidebar -->
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="space-y-4">
                    <div id="contact" class="scroll-mt-32 overflow-hidden rounded-3xl bg-white shadow-2xl shadow-primary/15 ring-1 ring-ivt-line">
                        <!-- Header -->
                        <div class="relative overflow-hidden bg-gradient-to-br from-primary via-primary to-primary-bright px-6 pb-12 pt-6 text-white">
                            <div class="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10 blur-2xl" />
                            <div class="pointer-events-none absolute -bottom-16 -left-8 h-36 w-36 rounded-full bg-white/10 blur-2xl" />

                            <div class="relative flex items-center gap-4">
                                <span class="flex h-14 w-14 flex-none items-center justify-center overflow-hidden rounded-2xl bg-white/15 text-base font-bold ring-2 ring-white/30 backdrop-blur">
                                    <img v-if="provider.logo_url" :src="provider.logo_url" class="h-full w-full object-cover" alt="" />
                                    <template v-else>{{ initials(provider.company_name) }}</template>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/70">Contactează furnizorul</p>
                                    <h3 class="truncate font-display text-xl leading-tight !text-white">{{ provider.company_name }}</h3>
                                    <p v-if="provider.is_verified" class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-white/85" title="CUI confirmat la ANAF">
                                        <CheckBadgeIcon class="h-4 w-4" /> Firmă verificată ANAF
                                    </p>
                                    <p v-if="provider.response_time_label" class="mt-1 flex items-center gap-1 text-xs font-medium text-white/85">
                                        <ClockIcon class="h-3.5 w-3.5" /> {{ provider.response_time_label }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="relative -mt-6 space-y-4 rounded-t-3xl bg-white p-6">
                            <div v-if="provider.phone || provider.whatsapp || provider.email" class="space-y-2.5">
                                <a
                                    v-if="provider.phone"
                                    :href="`tel:${provider.phone}`"
                                    class="group flex items-center gap-3 rounded-2xl bg-brand p-3 text-white shadow-lg shadow-primary/25 transition-all duration-200 hover:-translate-y-0.5 hover:brightness-110 hover:shadow-glow-violet hover:shadow-xl hover:shadow-primary/30"
                                >
                                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-white/15"><PhoneIcon class="h-5 w-5" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[11px] font-medium uppercase tracking-[0.08em] text-white/70">Sună acum</span>
                                        <span class="block truncate text-sm font-semibold">{{ provider.phone }}</span>
                                    </span>
                                    <ChevronRightIcon class="h-4 w-4 flex-none text-white/60 transition-transform duration-200 group-hover:translate-x-0.5" />
                                </a>
                                <a
                                    v-if="provider.whatsapp"
                                    :href="`https://wa.me/${provider.whatsapp.replace(/[^0-9]/g, '')}`"
                                    target="_blank"
                                    rel="noopener"
                                    class="group flex items-center gap-3 rounded-2xl p-3 ring-1 ring-ivt-line transition-all duration-200 hover:bg-success-50 hover:ring-success-200"
                                >
                                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-success-50 text-success-700"><ChatBubbleLeftEllipsisIcon class="h-5 w-5" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[11px] font-medium uppercase tracking-[0.08em] text-ivt-ink-soft">WhatsApp</span>
                                        <span class="block truncate text-sm font-semibold text-ivt-ink">Scrie un mesaj</span>
                                    </span>
                                    <ChevronRightIcon class="h-4 w-4 flex-none text-ivt-ink-soft/50 transition-transform duration-200 group-hover:translate-x-0.5" />
                                </a>
                                <a
                                    v-if="provider.email"
                                    :href="`mailto:${provider.email}`"
                                    class="group flex items-center gap-3 rounded-2xl p-3 ring-1 ring-ivt-line transition-all duration-200 hover:bg-primary/5 hover:ring-primary/30"
                                >
                                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-primary/10 text-primary"><EnvelopeIcon class="h-5 w-5" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[11px] font-medium uppercase tracking-[0.08em] text-ivt-ink-soft">Email</span>
                                        <span class="block truncate text-sm font-semibold text-ivt-ink">{{ provider.email }}</span>
                                    </span>
                                    <ChevronRightIcon class="h-4 w-4 flex-none text-ivt-ink-soft/50 transition-transform duration-200 group-hover:translate-x-0.5" />
                                </a>
                            </div>

                            <div v-if="socialLinks.length || provider.website" class="flex flex-wrap items-center justify-center gap-2 border-t border-ivt-line pt-4">
                                <a
                                    v-for="social in socialLinks"
                                    :key="social.platform"
                                    :href="social.url"
                                    target="_blank"
                                    rel="noopener"
                                    :aria-label="social.platform"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/5 text-[11px] font-bold text-ivt-ink-soft ring-1 ring-primary/10 transition-all duration-200 hover:bg-brand hover:text-white hover:ring-transparent"
                                >
                                    {{ social.label }}
                                </a>
                                <a
                                    v-if="provider.website"
                                    :href="provider.website"
                                    target="_blank"
                                    rel="noopener"
                                    aria-label="Website"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/5 text-ivt-ink-soft ring-1 ring-primary/10 transition-all duration-200 hover:bg-brand hover:text-white hover:ring-transparent"
                                >
                                    <GlobeAltIcon class="h-4 w-4" />
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Availability check -->
                    <div class="rounded-3xl bg-white p-6 ring-1 ring-ivt-line">
                        <p class="flex items-center gap-1.5 text-sm font-semibold text-ivt-ink"><CalendarIcon class="h-4 w-4 text-primary" /> Verifică disponibilitatea</p>
                        <div class="mt-3">
                            <DatePickerModal
                                v-model="checkDate"
                                title="Verifică disponibilitatea"
                                placeholder="Alege data evenimentului"
                                :min-date="new Date()"
                                :busy-dates="unavailableDates"
                                button-class="rounded-2xl bg-primary/5 px-4 py-3.5 text-[14.5px] font-medium text-ivt-ink ring-1 ring-primary/10 transition-all duration-150 hover:bg-primary/10 hover:ring-primary/25 focus:outline-none focus:ring-2 focus:ring-primary/40"
                            />
                        </div>
                        <p
                            v-if="checkDate"
                            class="mt-3 flex items-center gap-2 rounded-xl px-3 py-2.5 text-[13.5px] font-medium"
                            :class="dateIsAvailable ? 'bg-success-50 text-success-700' : 'bg-danger-50 text-danger-700'"
                        >
                            <CheckIcon v-if="dateIsAvailable" class="h-4 w-4" />
                            <XMarkIcon v-else class="h-4 w-4" />
                            {{ dateIsAvailable ? 'Disponibil în această dată' : 'Ocupat în această dată' }}
                        </p>
                    </div>

                    <div v-if="infoRows.length" class="rounded-3xl bg-primary/5 p-6 ring-1 ring-primary/10">
                        <div
                            v-for="(row, index) in infoRows"
                            :key="row.label"
                            class="flex justify-between gap-4 py-2.5 text-[13.5px]"
                            :class="index < infoRows.length - 1 && 'border-b border-primary/10'"
                        >
                            <span class="flex-none text-ivt-ink-soft">{{ row.label }}</span>
                            <span class="text-right font-semibold text-ivt-ink">{{ row.value }}</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        </div>

        <!-- Mobile contact bar -->
        <div class="fixed inset-x-3 bottom-[calc(var(--tabbar-h)+0.25rem)] z-30 mx-auto max-w-md rounded-[22px] bg-white/90 p-2 shadow-ivt-deep ring-1 ring-ivt-ink/[0.06] backdrop-blur-xl lg:hidden">
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
        <div class="h-20 lg:hidden" aria-hidden="true" />

        <!-- Lightbox -->
        <Teleport to="body">
            <div
                v-if="lightboxIndex !== null"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-ivt-ink/90 p-4 backdrop-blur-sm"
                @click.self="closeLightbox"
            >
                <button type="button" @click="closeLightbox" aria-label="Închide" class="absolute right-5 top-5 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white transition-colors duration-150 hover:bg-white/20">
                    <XMarkIcon class="h-6 w-6" />
                </button>
                <button
                    v-if="gallery.length > 1"
                    type="button"
                    @click="prevMedia"
                    aria-label="Fotografia anterioară"
                    class="absolute left-3 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition-colors duration-150 hover:bg-white/20 sm:left-8"
                >
                    <ChevronLeftIcon class="h-6 w-6" />
                </button>
                <button
                    v-if="gallery.length > 1"
                    type="button"
                    @click="nextMedia"
                    aria-label="Fotografia următoare"
                    class="absolute right-3 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition-colors duration-150 hover:bg-white/20 sm:right-8"
                >
                    <ChevronRightIcon class="h-6 w-6" />
                </button>
                <video v-if="activeLightboxMedia?.type === 'video'" :src="activeLightboxMedia.url" controls autoplay class="max-h-[85vh] max-w-full rounded-2xl" />
                <img v-else :src="activeLightboxMedia?.url" :alt="activeLightboxMedia?.listing_title ?? provider.company_name" class="max-h-[85vh] max-w-full rounded-2xl object-contain" />
                <div class="absolute bottom-5 left-1/2 flex -translate-x-1/2 items-center gap-2">
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white">{{ lightboxIndex + 1 }} / {{ gallery.length }}</span>
                    <Link
                        v-if="activeLightboxMedia?.listing_slug"
                        :href="route('listings.show', activeLightboxMedia.listing_slug)"
                        class="max-w-[60vw] truncate rounded-full bg-white px-3 py-1 text-xs font-semibold text-ivt-ink transition-colors hover:text-primary"
                    >
                        {{ activeLightboxMedia.listing_title }} →
                    </Link>
                </div>
            </div>
        </Teleport>
    </ClientLayout>
</template>
