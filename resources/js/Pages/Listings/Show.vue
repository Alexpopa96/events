<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    MapPinIcon,
    PhoneIcon,
    EnvelopeIcon,
    EyeIcon,
    ChatBubbleLeftEllipsisIcon,
    ShareIcon,
    PlayCircleIcon,
    XMarkIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    GlobeAltIcon,
    CheckIcon,
    PhotoIcon,
    CheckBadgeIcon,
    HeartIcon,
    CalendarIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline';
import { HeartIcon as HeartIconSolid, StarIcon } from '@heroicons/vue/24/solid';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import ListingCard from '@/Components/Listing/Card.vue';
import { categoryIcon } from '@/Composables/useCategoryIcon';
import { formatListingPrice } from '@/Composables/useListingPrice';
import { useFavoriteToggle } from '@/Composables/useFavoriteToggle';

const props = defineProps({
    listing: Object,
    related: Array,
    canMessage: { type: Boolean, default: true },
    reviewState: { type: Object, default: () => ({ can_review: false, my_review: null }) },
    unavailableDates: { type: Array, default: () => [] },
    isFavorited: Boolean,
});

/* ---------- availability check ---------- */
const checkDate = ref('');
const dateIsAvailable = computed(() => !props.unavailableDates.includes(checkDate.value));

const page = usePage();
const can = () => page.props.auth.can;
const isGuest = () => !page.props.auth.user;

const listingCoverUrl = (props.listing.media.find((m) => m.is_cover) ?? props.listing.media[0])?.url ?? null;
const { isFavorited, toggle: toggleFavorite } = useFavoriteToggle(props.listing.slug, props.isFavorited, null, listingCoverUrl);

const initials = (name) => (name || '')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

const trackClick = (type) => {
    axios.post(route('listings.event', props.listing.slug), { type }).catch(() => {});
};

/* ---------- hero mosaic ---------- */
const heroMedia = computed(() => props.listing.media.slice(0, 5));

// Grid-cell spans so the mosaic has no holes for any number of photos (1-5+).
const heroSpan = (index) => {
    const count = heroMedia.value.length;
    if (index === 0) return count === 1 ? 'col-span-4 row-span-2' : 'col-span-4 row-span-2 sm:col-span-2';
    if (count === 2) return 'hidden sm:block sm:col-span-2 sm:row-span-2';
    if (count === 3 || (count === 4 && index === 1)) return 'hidden sm:block sm:col-span-2';
    return 'hidden sm:block';
};

const shareCopied = ref(false);
const shareListing = async () => {
    const shareData = { title: props.listing.title, url: window.location.href };
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
    { id: 'despre', label: 'Despre' },
    { id: 'galerie', label: `Galerie${props.listing.media.length ? ` (${props.listing.media.length})` : ''}` },
    { id: 'servicii', label: 'Servicii & prețuri' },
    { id: 'recenzii', label: `Recenzii (${props.listing.reviews_count})` },
]);
const activeTab = ref('despre');

// Links from notifications (e.g. "vezi răspunsul") land straight on the reviews tab.
onMounted(() => {
    if (window.location.hash === '#recenzii') activeTab.value = 'recenzii';
});

/* ---------- gallery / lightbox ---------- */

const lightboxIndex = ref(null);
const activeLightboxMedia = computed(() => (lightboxIndex.value !== null ? props.listing.media[lightboxIndex.value] : null));
const openLightbox = (index) => { lightboxIndex.value = index; };
const closeLightbox = () => { lightboxIndex.value = null; };
const nextMedia = () => { lightboxIndex.value = (lightboxIndex.value + 1) % props.listing.media.length; };
const prevMedia = () => { lightboxIndex.value = (lightboxIndex.value - 1 + props.listing.media.length) % props.listing.media.length; };

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

const reviewForm = useForm({ rating: 0, comment: '' });
const hoverRating = ref(0);
const ratingLabels = ['', 'Slab', 'Așa și așa', 'Bine', 'Foarte bine', 'Excelent'];
const shownRating = computed(() => hoverRating.value || reviewForm.rating);

const submitReview = () => {
    reviewForm.post(route('listings.reviews.store', props.listing.slug), {
        preserveScroll: true,
        onSuccess: () => reviewForm.reset(),
    });
};

const reviewStatusCopy = {
    pending: 'Recenzia ta a fost trimisă și apare public după verificare.',
    approved: 'Recenzia ta este publică. Îți mulțumim!',
    rejected: 'Recenzia ta nu a îndeplinit regulile de publicare.',
};
const visibleReviews = computed(() => (
    reviewsExpanded.value ? props.listing.reviews : props.listing.reviews.slice(0, 3)
));

const ratingCounts = computed(() => {
    const counts = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
    props.listing.reviews.forEach((review) => { counts[review.rating] = (counts[review.rating] ?? 0) + 1; });
    return counts;
});
const ratingPct = (star) => (props.listing.reviews.length
    ? Math.round((ratingCounts.value[star] / props.listing.reviews.length) * 100)
    : 0);

/* ---------- contact panel ---------- */
// Message button: clients who can message open the in-app chat, guests go to login,
// anyone else falls back to the contact card.
const canDirectMessage = computed(() => !isGuest() && can().submitQuoteRequest && props.canMessage);
const messageHref = computed(() => {
    if (canDirectMessage.value) return route('listings.messages.open', props.listing.slug);
    return isGuest() ? '/login' : '#contact';
});
</script>

<template>
    <ClientLayout :title="listing.title">
        <!-- Breadcrumb -->
        <nav class="mb-5 flex flex-wrap items-center gap-2 text-[13px] text-ink-soft">
            <Link :href="route('home')" class="transition-colors hover:text-primary">Acasă</Link>
            <span class="text-ink-soft/40">/</span>
            <Link :href="route('categories.index')" class="transition-colors hover:text-primary">Categorii</Link>
            <span class="text-ink-soft/40">/</span>
            <Link :href="route('categories.show', listing.category_slug)" class="transition-colors hover:text-primary">{{ listing.category }}</Link>
        </nav>

        <!-- Photo mosaic -->
        <section class="relative">
            <div v-if="listing.media.length" class="grid h-[260px] grid-cols-4 grid-rows-2 gap-2 overflow-hidden rounded-3xl sm:h-[440px]">
                <button
                    v-for="(media, index) in heroMedia"
                    :key="media.id"
                    type="button"
                    @click="openLightbox(index)"
                    class="group relative overflow-hidden bg-primary/5"
                    :class="heroSpan(index)"
                >
                    <img
                        v-if="media.type !== 'video'"
                        :src="media.url"
                        :alt="listing.title"
                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                    <div v-else class="flex h-full w-full items-center justify-center bg-ink">
                        <PlayCircleIcon class="h-12 w-12 text-white/80" />
                    </div>
                    <span class="absolute inset-0 bg-black/0 transition-colors duration-300 group-hover:bg-black/10" />
                </button>
            </div>
            <div v-else class="flex h-[220px] items-center justify-center rounded-3xl bg-gradient-to-br from-primary/15 via-primary/5 to-white ring-1 ring-primary/10 sm:h-[300px]">
                <component :is="categoryIcon(listing.category_slug)" class="h-16 w-16 text-primary/30" />
            </div>

            <div class="absolute right-4 top-4 flex gap-2">
                <button
                    type="button"
                    @click="shareListing"
                    aria-label="Distribuie anunțul"
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-ink shadow-md backdrop-blur transition-transform duration-150 hover:scale-105"
                >
                    <ShareIcon class="h-[18px] w-[18px]" />
                </button>
                <button
                    type="button"
                    @click="toggleFavorite"
                    :aria-label="isFavorited ? 'Elimină de la favorite' : 'Salvează'"
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-white/90 shadow-md backdrop-blur transition-transform duration-150 hover:scale-105"
                    :class="isFavorited ? 'text-primary' : 'text-ink'"
                >
                    <HeartIconSolid v-if="isFavorited" class="h-[18px] w-[18px]" />
                    <HeartIcon v-else class="h-[18px] w-[18px]" />
                </button>
            </div>

            <button
                v-if="listing.media.length > 1"
                type="button"
                @click="openLightbox(0)"
                class="absolute bottom-4 right-4 inline-flex items-center gap-2 rounded-full bg-white/95 px-4 py-2 text-[13px] font-semibold text-ink shadow-lg backdrop-blur transition-transform duration-150 hover:scale-105"
            >
                <PhotoIcon class="h-4 w-4 text-primary" /> Vezi toate cele {{ listing.media.length }} fotografii
            </button>
            <span v-if="shareCopied" class="absolute bottom-4 left-4 rounded-full bg-ink px-3 py-1.5 text-xs font-medium text-white">Link copiat</span>
        </section>

        <!-- Title block -->
        <header class="mt-7 flex flex-wrap items-start justify-between gap-6">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                        <component :is="categoryIcon(listing.category_slug)" class="h-3.5 w-3.5" /> {{ listing.category }}
                    </span>
                    <span v-if="listing.is_featured" class="rounded-full bg-primary px-3 py-1 text-[10.5px] font-bold uppercase tracking-[0.07em] text-white">Premium</span>
                </div>
                <h1 class="mt-3 font-serif text-3xl leading-tight text-ink sm:text-4xl">{{ listing.title }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-ink-soft">
                    <span v-if="listing.locality || listing.county" class="inline-flex items-center gap-1.5">
                        <MapPinIcon class="h-4 w-4 text-primary" /> {{ [listing.locality, listing.county].filter(Boolean).join(', ') }}
                    </span>
                    <span v-if="listing.rating" class="inline-flex items-center gap-1.5 font-medium text-ink">
                        <StarIcon class="h-4 w-4 text-primary" /> {{ listing.rating }}
                        <span class="font-normal text-ink-soft">· {{ listing.reviews_count }} recenzii</span>
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <span class="flex h-6 w-6 flex-none items-center justify-center overflow-hidden rounded-full bg-primary/10 text-[10px] font-bold text-primary">
                            <img v-if="listing.provider.logo_url" :src="listing.provider.logo_url" class="h-full w-full object-cover" alt="" />
                            <template v-else>{{ initials(listing.provider.company_name) }}</template>
                        </span>
                        {{ listing.provider.company_name }}
                    </span>
                </div>
            </div>

            <div class="flex flex-none items-center gap-4 rounded-3xl bg-white p-4 pl-6 shadow-xl shadow-ink/5 ring-1 ring-line">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-ink-soft">Preț</p>
                    <p class="text-xl font-bold text-primary">{{ formatListingPrice(listing) }}</p>
                </div>
                <component
                    :is="canDirectMessage || isGuest() ? Link : 'a'"
                    :href="messageHref"
                    class="rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white shadow-md shadow-primary/25 transition-all duration-200 hover:-translate-y-0.5 hover:bg-primary-bright hover:shadow-lg hover:shadow-primary/30"
                >
                    Trimite mesaj
                </component>
            </div>
        </header>

        <!-- Tabs -->
        <nav class="mt-8 mb-8 flex overflow-x-auto">
            <div class="inline-flex gap-1 rounded-full bg-primary/5 p-1 ring-1 ring-primary/10">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    @click="activeTab = tab.id"
                    class="whitespace-nowrap rounded-full px-4 py-2 text-[13.5px] font-semibold transition-all duration-200"
                    :class="activeTab === tab.id ? 'bg-primary text-white shadow-sm shadow-primary/25' : 'text-ink-soft hover:bg-white hover:text-primary'"
                >
                    {{ tab.label }}
                </button>
            </div>
        </nav>

        <div class="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_380px]">
            <!-- Main column -->
            <div class="min-w-0">
                <!-- About -->
                <section v-show="activeTab === 'despre'" class="mb-14">
                    <h2 class="mb-4 font-serif text-2xl text-ink">Despre {{ listing.provider.company_name }}</h2>

                    <p v-if="listing.description" class="whitespace-pre-line text-[15px] leading-[1.8] text-ink-soft">{{ listing.description }}</p>
                    <p v-else-if="listing.provider.description" class="whitespace-pre-line text-[15px] leading-[1.8] text-ink-soft">{{ listing.provider.description }}</p>

                    <div class="mt-7 grid grid-cols-3 gap-3 sm:gap-4">
                        <div class="rounded-3xl bg-primary/5 px-5 py-5 ring-1 ring-primary/10">
                            <StarIcon class="h-5 w-5 text-primary" />
                            <p class="mt-2 font-serif text-2xl text-ink">{{ listing.rating ?? '—' }}</p>
                            <span class="text-xs text-ink-soft">rating mediu</span>
                        </div>
                        <div class="rounded-3xl bg-primary/5 px-5 py-5 ring-1 ring-primary/10">
                            <ChatBubbleLeftEllipsisIcon class="h-5 w-5 text-primary" />
                            <p class="mt-2 font-serif text-2xl text-ink">{{ listing.reviews_count }}</p>
                            <span class="text-xs text-ink-soft">recenzii</span>
                        </div>
                        <div class="rounded-3xl bg-primary/5 px-5 py-5 ring-1 ring-primary/10">
                            <EyeIcon class="h-5 w-5 text-primary" />
                            <p class="mt-2 font-serif text-2xl text-ink">{{ listing.views_count }}</p>
                            <span class="text-xs text-ink-soft">vizualizări</span>
                        </div>
                    </div>
                </section>

                <!-- Gallery -->
                <section v-show="activeTab === 'galerie'" class="mb-14">
                    <div class="mb-5 flex items-center justify-between">
                        <h2 class="font-serif text-2xl text-ink">Galerie</h2>
                        <span v-if="listing.media.length" class="text-[13px] text-ink-soft">{{ listing.media.length }} fotografii</span>
                    </div>

                    <p v-if="!listing.media.length" class="rounded-3xl border border-dashed border-line bg-white py-12 text-center text-sm text-ink-soft">
                        Nicio fotografie încă
                    </p>

                    <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <button
                            v-for="(media, index) in listing.media"
                            :key="media.id"
                            type="button"
                            @click="openLightbox(index)"
                            class="group relative aspect-square overflow-hidden rounded-2xl bg-primary/5"
                        >
                            <img
                                v-if="media.type !== 'video'"
                                :src="media.url"
                                :alt="listing.title"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center bg-ink">
                                <PlayCircleIcon class="h-9 w-9 text-white/80" />
                            </div>
                            <span v-if="media.type === 'video'" class="absolute bottom-2.5 left-2.5 inline-flex items-center gap-1 rounded-full bg-ink/75 px-2.5 py-1 text-[11px] font-semibold text-white">
                                <PlayCircleIcon class="h-3.5 w-3.5" /> Video
                            </span>
                        </button>
                    </div>
                </section>

                <!-- Services & pricing -->
                <section v-show="activeTab === 'servicii'" class="mb-14">
                    <h2 class="mb-5 font-serif text-2xl text-ink">Servicii & prețuri</h2>

                    <div class="max-w-md rounded-3xl bg-white p-7 shadow-xl shadow-ink/5 ring-1 ring-primary/20">
                        <p class="text-xs font-bold uppercase tracking-[0.08em] text-primary">{{ listing.category }}</p>
                        <p class="mt-3 font-serif text-4xl text-ink">{{ formatListingPrice(listing) }}</p>

                        <ul v-if="listing.benefits.length" class="mt-6 space-y-3 border-t border-line pt-6">
                            <li v-for="benefit in listing.benefits" :key="benefit" class="flex items-start gap-3 text-sm text-ink-soft">
                                <span class="mt-0.5 flex h-5 w-5 flex-none items-center justify-center rounded-full bg-primary/10 text-primary">
                                    <CheckIcon class="h-3 w-3" stroke-width="3" />
                                </span>
                                {{ benefit }}
                            </li>
                        </ul>

                        <component :is="canDirectMessage || isGuest() ? Link : 'a'" :href="messageHref" class="mt-7 inline-flex rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white shadow-md shadow-primary/25 transition-all duration-200 hover:-translate-y-0.5 hover:bg-primary-bright">
                            Trimite mesaj
                        </component>
                    </div>
                    <p class="mt-3 text-xs text-ink-soft">Prețurile pot varia în funcție de dată și locație.</p>
                </section>

                <!-- Reviews -->
                <section v-show="activeTab === 'recenzii'">
                    <h2 class="mb-5 font-serif text-2xl text-ink">Recenzii</h2>

                    <!-- Leave a review -->
                    <form
                        v-if="reviewState.can_review"
                        class="mb-7 rounded-3xl bg-white p-6 ring-1 ring-primary/20"
                        @submit.prevent="submitReview"
                    >
                        <p class="font-serif text-lg text-ink">Cum a fost experiența ta?</p>
                        <p class="mt-1 text-sm text-ink-soft">Ai discutat cu {{ listing.provider.company_name }}, deci ne poți spune cum a decurs.</p>

                        <div class="mt-4 flex items-center gap-3" role="radiogroup" aria-label="Rating">
                            <div class="flex gap-1" @mouseleave="hoverRating = 0">
                                <button
                                    v-for="n in 5"
                                    :key="n"
                                    type="button"
                                    role="radio"
                                    :aria-checked="reviewForm.rating === n"
                                    :aria-label="`${n} din 5 stele`"
                                    class="rounded-md p-0.5 transition-transform duration-100 hover:scale-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                    @mouseenter="hoverRating = n"
                                    @click="reviewForm.rating = n"
                                >
                                    <StarIcon class="h-8 w-8 transition-colors duration-100" :class="n <= shownRating ? 'text-primary' : 'text-line'" />
                                </button>
                            </div>
                            <span class="text-sm font-medium text-ink-soft">{{ ratingLabels[shownRating] }}</span>
                        </div>
                        <p v-if="reviewForm.errors.rating" class="mt-1 text-xs text-rose-600">{{ reviewForm.errors.rating }}</p>

                        <label for="review-comment" class="sr-only">Comentariu</label>
                        <textarea
                            id="review-comment"
                            v-model="reviewForm.comment"
                            rows="4"
                            maxlength="2000"
                            placeholder="Povestește pe scurt ce ți-a plăcut sau ce ar putea fi mai bine (opțional)"
                            class="mt-4 w-full rounded-2xl border-line text-sm text-ink placeholder:text-ink-soft/60 focus:border-primary focus:ring-primary"
                        ></textarea>
                        <p v-if="reviewForm.errors.comment" class="mt-1 text-xs text-rose-600">{{ reviewForm.errors.comment }}</p>

                        <div class="mt-4 flex items-center justify-between gap-3">
                            <span class="text-xs text-ink-soft">Recenzia apare public după o scurtă verificare.</span>
                            <button
                                type="submit"
                                :disabled="reviewForm.processing || !reviewForm.rating"
                                class="rounded-full bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary/25 transition-all duration-200 hover:bg-primary-bright disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
                            >
                                Trimite recenzia
                            </button>
                        </div>
                    </form>

                    <div v-else-if="reviewState.my_review" class="mb-7 flex items-start gap-3 rounded-3xl bg-primary/5 p-5 ring-1 ring-primary/10">
                        <span class="flex flex-none items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-ink ring-1 ring-line">
                            <StarIcon class="h-3.5 w-3.5 text-primary" /> {{ reviewState.my_review.rating }}
                        </span>
                        <p class="text-sm text-ink-soft">{{ reviewStatusCopy[reviewState.my_review.status] }}</p>
                    </div>

                    <div v-if="!listing.reviews.length" class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-line bg-white px-5 py-14 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <StarIcon class="h-6 w-6" />
                        </span>
                        <p class="mt-3 text-sm font-medium text-ink">Nicio recenzie încă</p>
                        <p class="mt-1 text-sm text-ink-soft">Acest furnizor nu a primit încă recenzii.</p>
                    </div>

                    <template v-else>
                        <div class="mb-7 flex flex-wrap items-center gap-9 rounded-3xl bg-primary/5 p-7 ring-1 ring-primary/10">
                            <div class="text-center">
                                <p class="font-serif text-5xl text-ink">{{ listing.rating }}</p>
                                <div class="mt-1 flex justify-center gap-0.5 text-primary">
                                    <StarIcon v-for="n in 5" :key="n" class="h-4 w-4" />
                                </div>
                                <p class="mt-1.5 text-xs text-ink-soft">din {{ listing.reviews_count }} recenzii</p>
                            </div>
                            <div class="min-w-[220px] flex-1 space-y-2">
                                <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="flex items-center gap-2.5 text-xs text-ink-soft">
                                    <span class="w-6">{{ star }}★</span>
                                    <span class="h-2 flex-1 overflow-hidden rounded-full bg-white">
                                        <span class="block h-full rounded-full bg-primary" :style="{ width: ratingPct(star) + '%' }" />
                                    </span>
                                    <span class="w-8 text-right">{{ ratingPct(star) }}%</span>
                                </div>
                            </div>
                        </div>

                        <ul class="space-y-3">
                            <li v-for="review in visibleReviews" :key="review.id" class="rounded-3xl bg-white p-5 ring-1 ring-line">
                                <div class="mb-2 flex items-center gap-3">
                                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary-bright text-xs font-semibold text-white">
                                        {{ initials(review.author) }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-ink">{{ review.author }}</p>
                                        <p class="text-xs text-ink-soft">{{ review.created_at }}</p>
                                    </div>
                                    <span class="flex flex-none items-center gap-1 rounded-full bg-primary/5 px-2.5 py-1 text-xs font-semibold text-ink">
                                        <StarIcon class="h-3.5 w-3.5 text-primary" /> {{ review.rating }}
                                    </span>
                                </div>
                                <p v-if="review.comment" class="text-sm leading-relaxed text-ink-soft">{{ review.comment }}</p>
                                <div v-if="review.provider_reply" class="mt-3 rounded-2xl bg-primary/5 p-4">
                                    <p class="text-xs font-semibold text-ink">
                                        Răspuns de la {{ listing.provider.company_name }}
                                        <span class="font-normal text-ink-soft">· {{ review.provider_replied_at }}</span>
                                    </p>
                                    <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-ink-soft">{{ review.provider_reply }}</p>
                                </div>
                            </li>
                        </ul>

                        <button
                            v-if="listing.reviews.length > 3"
                            type="button"
                            @click="reviewsExpanded = !reviewsExpanded"
                            class="mt-4 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-ink ring-1 ring-line transition-colors duration-150 hover:text-primary hover:ring-primary/40"
                        >
                            {{ reviewsExpanded ? 'Arată mai puține' : `Vezi toate cele ${listing.reviews.length} recenzii` }}
                        </button>
                    </template>
                </section>
            </div>

            <!-- Sidebar -->
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="space-y-4">
                    <div id="contact" class="scroll-mt-32 overflow-hidden rounded-3xl bg-white shadow-2xl shadow-primary/15 ring-1 ring-line">
                        <!-- Header -->
                        <div class="relative overflow-hidden bg-gradient-to-br from-primary via-primary to-primary-bright px-6 pb-12 pt-6 text-white">
                            <div class="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10 blur-2xl" />
                            <div class="pointer-events-none absolute -bottom-16 -left-8 h-36 w-36 rounded-full bg-white/10 blur-2xl" />

                            <div class="relative flex items-center gap-4">
                                <span class="flex h-14 w-14 flex-none items-center justify-center overflow-hidden rounded-2xl bg-white/15 text-base font-bold ring-2 ring-white/30 backdrop-blur">
                                    <img v-if="listing.provider.logo_url" :src="listing.provider.logo_url" class="h-full w-full object-cover" alt="" />
                                    <template v-else>{{ initials(listing.provider.company_name) }}</template>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/70">Contactează furnizorul</p>
                                    <h3 class="truncate font-serif text-xl leading-tight !text-white">{{ listing.provider.company_name }}</h3>
                                    <p v-if="listing.provider.is_verified" class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-white/85" title="CUI confirmat la ANAF">
                                        <CheckBadgeIcon class="h-4 w-4" /> Firmă verificată ANAF
                                    </p>
                                    <p v-if="listing.provider.response_time_label" class="mt-1 flex items-center gap-1 text-xs font-medium text-white/85">
                                        <ClockIcon class="h-3.5 w-3.5" /> {{ listing.provider.response_time_label }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="relative -mt-6 space-y-4 rounded-t-3xl bg-white p-6">
                            <div v-if="listing.provider.phone || listing.provider.email" class="space-y-2.5">
                                <a
                                    v-if="listing.provider.phone"
                                    :href="`tel:${listing.provider.phone}`"
                                    @click="trackClick('phone_click')"
                                    class="group flex items-center gap-3 rounded-2xl bg-primary p-3 text-white shadow-lg shadow-primary/25 transition-all duration-200 hover:-translate-y-0.5 hover:bg-primary-bright hover:shadow-xl hover:shadow-primary/30"
                                >
                                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-white/15"><PhoneIcon class="h-5 w-5" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[11px] font-medium uppercase tracking-[0.08em] text-white/70">Sună acum</span>
                                        <span class="block truncate text-sm font-semibold">{{ listing.provider.phone }}</span>
                                    </span>
                                    <ChevronRightIcon class="h-4 w-4 flex-none text-white/60 transition-transform duration-200 group-hover:translate-x-0.5" />
                                </a>
                                <a
                                    v-if="listing.provider.email"
                                    :href="`mailto:${listing.provider.email}`"
                                    @click="trackClick('email_click')"
                                    class="group flex items-center gap-3 rounded-2xl p-3 ring-1 ring-line transition-all duration-200 hover:bg-primary/5 hover:ring-primary/30"
                                >
                                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-primary/10 text-primary"><EnvelopeIcon class="h-5 w-5" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[11px] font-medium uppercase tracking-[0.08em] text-ink-soft">Email</span>
                                        <span class="block truncate text-sm font-semibold text-ink">{{ listing.provider.email }}</span>
                                    </span>
                                    <ChevronRightIcon class="h-4 w-4 flex-none text-ink-soft/50 transition-transform duration-200 group-hover:translate-x-0.5" />
                                </a>
                            </div>

                            <a v-if="listing.provider.website" :href="listing.provider.website" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 text-sm font-medium text-ink-soft transition-colors duration-150 hover:text-primary">
                                <GlobeAltIcon class="h-4 w-4" /> {{ listing.provider.website }}
                            </a>
                        </div>
                    </div>

                    <!-- Availability check -->
                    <div class="rounded-3xl bg-white p-6 ring-1 ring-line">
                        <p class="flex items-center gap-1.5 text-sm font-semibold text-ink"><CalendarIcon class="h-4 w-4 text-primary" /> Verifică disponibilitatea</p>
                        <input
                            v-model="checkDate"
                            type="date"
                            :min="new Date().toISOString().slice(0, 10)"
                            class="mt-3 w-full rounded-xl border-line text-sm text-ink focus:border-primary focus:ring-primary"
                        />
                        <p
                            v-if="checkDate"
                            class="mt-2.5 flex items-center gap-1.5 text-sm font-medium"
                            :class="dateIsAvailable ? 'text-emerald-600' : 'text-rose-600'"
                        >
                            <CheckIcon v-if="dateIsAvailable" class="h-4 w-4" />
                            <XMarkIcon v-else class="h-4 w-4" />
                            {{ dateIsAvailable ? 'Disponibil în această dată' : 'Ocupat în această dată' }}
                        </p>
                    </div>

                    <div class="rounded-3xl bg-primary/5 p-6 ring-1 ring-primary/10">
                        <div class="flex justify-between border-b border-primary/10 py-2.5 text-[13.5px]">
                            <span class="text-ink-soft">Categorie</span>
                            <span class="font-semibold text-ink">{{ listing.category }}</span>
                        </div>
                        <div v-if="listing.locality || listing.county" class="flex justify-between border-b border-primary/10 py-2.5 text-[13.5px]">
                            <span class="text-ink-soft">Locație</span>
                            <span class="font-semibold text-ink">{{ [listing.locality, listing.county].filter(Boolean).join(', ') }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2.5 text-[13.5px]">
                            <span class="inline-flex items-center gap-1.5 text-ink-soft"><EyeIcon class="h-3.5 w-3.5" /> Vizualizări</span>
                            <span class="font-semibold text-ink">{{ listing.views_count }}</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Similar listings -->
        <div v-if="related.length" class="mt-16 border-t border-line pt-14">
            <h2 class="mb-6 font-serif text-2xl text-ink">Furnizori similari{{ listing.locality ? ` în ${listing.locality}` : '' }}</h2>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <ListingCard v-for="item in related" :key="item.id" :listing="item" :show-favorite="false" />
            </div>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
            <div
                v-if="lightboxIndex !== null"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-ink/90 p-4 backdrop-blur-sm"
                @click.self="closeLightbox"
            >
                <button type="button" @click="closeLightbox" aria-label="Închide" class="absolute right-5 top-5 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white transition-colors duration-150 hover:bg-white/20">
                    <XMarkIcon class="h-6 w-6" />
                </button>
                <button
                    v-if="listing.media.length > 1"
                    type="button"
                    @click="prevMedia"
                    aria-label="Fotografia anterioară"
                    class="absolute left-3 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition-colors duration-150 hover:bg-white/20 sm:left-8"
                >
                    <ChevronLeftIcon class="h-6 w-6" />
                </button>
                <button
                    v-if="listing.media.length > 1"
                    type="button"
                    @click="nextMedia"
                    aria-label="Fotografia următoare"
                    class="absolute right-3 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition-colors duration-150 hover:bg-white/20 sm:right-8"
                >
                    <ChevronRightIcon class="h-6 w-6" />
                </button>
                <video v-if="activeLightboxMedia?.type === 'video'" :src="activeLightboxMedia.url" controls autoplay class="max-h-[85vh] max-w-full rounded-2xl" />
                <img v-else :src="activeLightboxMedia?.url" :alt="listing.title" class="max-h-[85vh] max-w-full rounded-2xl object-contain" />
                <span class="absolute bottom-5 left-1/2 -translate-x-1/2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white">{{ lightboxIndex + 1 }} / {{ listing.media.length }}</span>
            </div>
        </Teleport>
    </ClientLayout>
</template>
