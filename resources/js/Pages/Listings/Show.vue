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
} from '@heroicons/vue/24/outline';
import { HeartIcon as HeartIconSolid, HeartIcon, StarIcon } from '@heroicons/vue/24/solid';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import { categoryIcon } from '@/Composables/useCategoryIcon';
import { formatListingPrice } from '@/Composables/useListingPrice';
import { useFavoriteToggle } from '@/Composables/useFavoriteToggle';

const props = defineProps({
    listing: Object,
    related: Array,
    isFavorited: Boolean,
});

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

/* ---------- cover ---------- */
const coverMedia = computed(() => props.listing.media.find((m) => m.is_cover) ?? props.listing.media[0] ?? null);
const hasCoverImage = computed(() => coverMedia.value && coverMedia.value.type !== 'video');
const coverStyle = computed(() => (hasCoverImage.value
    ? { backgroundImage: `url(${coverMedia.value.url})`, backgroundSize: 'cover', backgroundPosition: 'center' }
    : {}));

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

/* ---------- gallery / lightbox ---------- */
const galleryPreview = computed(() => props.listing.media.slice(0, 5));
const extraMediaCount = computed(() => Math.max(props.listing.media.length - 5, 0));

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

/* ---------- quote request ---------- */
const quoteForm = useForm({ event_date: '', message: `Bună, sunt interesat/ă de "${props.listing.title}". Puteți să-mi trimiteți mai multe detalii?` });
const quoteSent = ref(false);

const submitQuote = () => {
    quoteForm.post(route('listings.request-quote', props.listing.slug), {
        preserveScroll: true,
        onSuccess: () => { quoteSent.value = true; },
    });
};
</script>

<template>
    <ClientLayout :title="listing.title">
        <!-- Breadcrumb -->
        <nav class="mb-6 flex flex-wrap items-center gap-2 text-[13px] text-ivt-ink-faint">
            <Link :href="route('home')" class="transition-colors hover:text-ivt-wine">Acasă</Link>
            <span>/</span>
            <Link :href="route('categories.index')" class="transition-colors hover:text-ivt-wine">Categorii</Link>
            <span>/</span>
            <Link :href="route('categories.show', listing.category_slug)" class="transition-colors hover:text-ivt-wine">{{ listing.category }}</Link>
            <span>/</span>
            <span class="text-ivt-ink">{{ listing.title }}</span>
        </nav>

        <!-- Hero -->
        <div class="mb-10">
            <div
                class="relative h-52 overflow-hidden rounded-[24px] sm:h-64"
                :class="hasCoverImage ? '' : 'bg-gradient-to-br from-[#E9DDBB] via-ivt-gold-bright to-ivt-wine'"
                :style="coverStyle"
            >
                <div class="absolute inset-0 bg-gradient-to-t from-ivt-ink/45 via-transparent to-transparent" />
                <div class="absolute right-4 top-4 flex gap-2 sm:right-6 sm:top-6">
                    <button
                        type="button"
                        @click="shareListing"
                        aria-label="Distribuie anunțul"
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-ivt-ink shadow-sm backdrop-blur transition-transform duration-150 hover:scale-105"
                    >
                        <ShareIcon class="h-[18px] w-[18px]" />
                    </button>
                </div>
                <span v-if="shareCopied" class="absolute bottom-4 right-4 rounded-full bg-ivt-ink px-3 py-1.5 text-xs font-medium text-ivt-on-dark">Link copiat</span>
            </div>

            <div class="relative z-10 mx-3 -mt-14 flex flex-wrap items-end justify-between gap-6 rounded-[22px] border border-ivt-line bg-white p-6 shadow-ivt-soft sm:mx-6 sm:p-7">
                <div class="flex items-end gap-5">
                    <div class="-mt-16 flex h-24 w-24 flex-none items-center justify-center overflow-hidden rounded-2xl border-4 border-white bg-gradient-to-br from-ivt-ink-2 to-ivt-ink shadow-ivt-soft sm:h-28 sm:w-28">
                        <img v-if="listing.provider.logo_url" :src="listing.provider.logo_url" class="h-full w-full object-cover" />
                        <component v-else :is="categoryIcon(listing.category_slug)" class="h-10 w-10 text-ivt-gold-bright" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="font-serif text-[26px] text-ivt-ink sm:text-[30px]">{{ listing.title }}</h1>
                            <span v-if="listing.is_featured" class="inline-flex items-center gap-1 rounded-full border border-white/15 bg-ivt-ink px-3 py-1.5 text-[10.5px] font-bold uppercase tracking-[0.07em] text-ivt-gold-bright">
                                Premium
                            </span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-ivt-ink-soft">
                            <span v-if="listing.locality || listing.county" class="inline-flex items-center gap-1">
                                <MapPinIcon class="h-4 w-4" /> {{ [listing.locality, listing.county].filter(Boolean).join(', ') }}
                            </span>
                            <span v-if="listing.rating" class="inline-flex items-center gap-1 font-medium text-ivt-ink">
                                <StarIcon class="h-4 w-4 text-ivt-gold" /> {{ listing.rating }}
                                <span class="font-normal text-ivt-ink-faint">· {{ listing.reviews_count }} recenzii</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2.5">
                    <button
                        type="button"
                        @click="toggleFavorite"
                        class="inline-flex items-center gap-1.5 rounded-full border border-ivt-line px-5 py-2.5 text-sm font-semibold text-ivt-ink transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
                    >
                        <HeartIconSolid v-if="isFavorited" class="h-4 w-4 text-ivt-wine" />
                        <HeartIcon v-else class="h-4 w-4" />
                        {{ isFavorited ? 'Salvat' : 'Salvează' }}
                    </button>
                    <a href="#contact" class="rounded-full bg-gradient-to-b from-ivt-wine-bright to-ivt-wine px-5 py-2.5 text-sm font-semibold text-white transition-transform duration-150 hover:-translate-y-0.5">
                        Trimite mesaj
                    </a>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <nav class="mb-10 flex gap-7 overflow-x-auto border-b border-ivt-line">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                @click="activeTab = tab.id"
                class="relative whitespace-nowrap pb-3 text-[14.5px] font-semibold transition-colors duration-150"
                :class="activeTab === tab.id ? 'text-ivt-ink' : 'text-ivt-ink-faint hover:text-ivt-ink-soft'"
            >
                {{ tab.label }}
                <span v-if="activeTab === tab.id" class="absolute inset-x-0 -bottom-px h-0.5 rounded-full bg-ivt-wine" />
            </button>
        </nav>

        <div class="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_360px]">
            <!-- Main column -->
            <div class="min-w-0">
                <!-- About -->
                <section v-show="activeTab === 'despre'" class="mb-14">
                    <h2 class="mb-5 font-serif text-[22px] text-ivt-ink">Despre {{ listing.provider.company_name }}</h2>

                    <p v-if="listing.description" class="whitespace-pre-line text-[15px] leading-[1.75] text-ivt-ink-soft">{{ listing.description }}</p>
                    <p v-else-if="listing.provider.description" class="whitespace-pre-line text-[15px] leading-[1.75] text-ivt-ink-soft">{{ listing.provider.description }}</p>

                    <div class="mt-6 grid grid-cols-3 gap-3.5">
                        <div class="rounded-2xl bg-ivt-paper-2 px-[18px] py-4">
                            <p class="font-serif text-xl text-ivt-ink">{{ listing.rating ?? '—' }}</p>
                            <span class="text-xs text-ivt-ink-faint">rating mediu</span>
                        </div>
                        <div class="rounded-2xl bg-ivt-paper-2 px-[18px] py-4">
                            <p class="font-serif text-xl text-ivt-ink">{{ listing.reviews_count }}</p>
                            <span class="text-xs text-ivt-ink-faint">recenzii</span>
                        </div>
                        <div class="rounded-2xl bg-ivt-paper-2 px-[18px] py-4">
                            <p class="font-serif text-xl text-ivt-ink">{{ listing.views_count }}</p>
                            <span class="text-xs text-ivt-ink-faint">vizualizări</span>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="rounded-full bg-ivt-paper-2 px-3.5 py-1.5 text-xs text-ivt-ink-soft">{{ listing.category }}</span>
                        <span v-if="listing.locality || listing.county" class="rounded-full bg-ivt-paper-2 px-3.5 py-1.5 text-xs text-ivt-ink-soft">
                            {{ [listing.locality, listing.county].filter(Boolean).join(', ') }}
                        </span>
                        <span class="rounded-full bg-ivt-paper-2 px-3.5 py-1.5 text-xs text-ivt-ink-soft">{{ formatListingPrice(listing) }}</span>
                    </div>
                </section>

                <!-- Gallery -->
                <section v-show="activeTab === 'galerie'" class="mb-14">
                    <div class="mb-5 flex items-center justify-between">
                        <h2 class="font-serif text-[22px] text-ivt-ink">Galerie</h2>
                        <span v-if="listing.media.length" class="text-[13px] text-ivt-ink-faint">{{ listing.media.length }} fotografii</span>
                    </div>

                    <p v-if="!listing.media.length" class="rounded-2xl border border-dashed border-ivt-line bg-white py-10 text-center text-sm text-ivt-ink-faint">
                        Nicio fotografie încă
                    </p>

                    <div v-else class="grid auto-rows-[110px] grid-cols-2 gap-2.5 sm:auto-rows-[130px] sm:grid-cols-4">
                        <button
                            v-for="(media, index) in galleryPreview"
                            :key="media.id"
                            type="button"
                            @click="openLightbox(index)"
                            class="group relative overflow-hidden rounded-2xl bg-ivt-paper-3"
                            :class="index === 0 ? 'col-span-2 row-span-2' : ''"
                        >
                            <img
                                v-if="media.type !== 'video'"
                                :src="media.url"
                                :alt="listing.title"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center bg-ivt-ink-2">
                                <PlayCircleIcon class="h-9 w-9 text-white/80" />
                            </div>
                            <span v-if="media.type === 'video'" class="absolute bottom-2.5 left-2.5 inline-flex items-center gap-1 rounded-full bg-ivt-ink/75 px-2.5 py-1 text-[11px] font-semibold text-white">
                                <PlayCircleIcon class="h-3.5 w-3.5" /> Video
                            </span>
                            <span v-if="index === 4 && extraMediaCount > 0" class="absolute inset-0 flex items-center justify-center bg-ivt-ink/55 font-serif text-xl italic text-white">
                                +{{ extraMediaCount }}
                            </span>
                        </button>
                    </div>
                </section>

                <!-- Services & pricing -->
                <section v-show="activeTab === 'servicii'" class="mb-14">
                    <h2 class="mb-5 font-serif text-[22px] text-ivt-ink">Servicii & prețuri</h2>

                    <div class="max-w-sm rounded-2xl border border-ivt-gold bg-ivt-paper-2 p-7">
                        <p class="text-xs font-bold uppercase tracking-[0.08em] text-ivt-wine">{{ listing.category }}</p>
                        <p class="mt-3 font-serif text-[32px] text-ivt-ink">{{ formatListingPrice(listing) }}</p>

                        <ul v-if="listing.benefits.length" class="mt-5 space-y-2.5 border-t border-ivt-line pt-5">
                            <li v-for="benefit in listing.benefits" :key="benefit" class="flex items-start gap-2.5 text-sm text-ivt-ink-soft">
                                <span class="mt-0.5 flex h-4 w-4 flex-none items-center justify-center rounded-full bg-ivt-sage/15 text-ivt-sage">
                                    <CheckIcon class="h-2.5 w-2.5" stroke-width="3" />
                                </span>
                                {{ benefit }}
                            </li>
                        </ul>

                        <a href="#contact" class="mt-6 inline-flex rounded-full bg-gradient-to-b from-ivt-ink-2 to-ivt-ink px-6 py-3 text-sm font-semibold text-white transition-transform duration-150 hover:-translate-y-0.5">
                            Cere ofertă
                        </a>
                    </div>
                    <p class="mt-3 text-xs text-ivt-ink-faint">Prețurile pot varia în funcție de dată și locație.</p>
                </section>

                <!-- Reviews -->
                <section v-show="activeTab === 'recenzii'">
                    <h2 class="mb-5 font-serif text-[22px] text-ivt-ink">Recenzii</h2>

                    <div v-if="!listing.reviews.length" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-ivt-line bg-white px-5 py-12 text-center">
                        <StarIcon class="h-6 w-6 text-ivt-ink-faint/40" />
                        <p class="mt-2 text-sm font-medium text-ivt-ink">Nicio recenzie încă</p>
                        <p class="mt-1 text-sm text-ivt-ink-faint">Acest furnizor nu a primit încă recenzii.</p>
                    </div>

                    <template v-else>
                        <div class="mb-7 flex flex-wrap items-center gap-9 rounded-2xl bg-ivt-paper-2 p-7">
                            <div class="text-center">
                                <p class="font-serif text-5xl text-ivt-ink">{{ listing.rating }}</p>
                                <div class="mt-1 flex justify-center gap-0.5 text-ivt-gold">
                                    <StarIcon v-for="n in 5" :key="n" class="h-4 w-4" />
                                </div>
                                <p class="mt-1.5 text-xs text-ivt-ink-faint">din {{ listing.reviews_count }} recenzii</p>
                            </div>
                            <div class="min-w-[220px] flex-1 space-y-1.5">
                                <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="flex items-center gap-2.5 text-xs text-ivt-ink-soft">
                                    <span class="w-6">{{ star }}★</span>
                                    <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-ivt-paper-3">
                                        <span class="block h-full rounded-full bg-ivt-gold" :style="{ width: ratingPct(star) + '%' }" />
                                    </span>
                                    <span class="w-8 text-right">{{ ratingPct(star) }}%</span>
                                </div>
                            </div>
                        </div>

                        <ul>
                            <li v-for="review in visibleReviews" :key="review.id" class="border-b border-ivt-line py-5 first:pt-0 last:border-0">
                                <div class="mb-2 flex items-center gap-3">
                                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-gradient-to-br from-ivt-gold-bright to-ivt-wine text-xs font-semibold text-white">
                                        {{ initials(review.author) }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-ivt-ink">{{ review.author }}</p>
                                        <p class="text-xs text-ivt-ink-faint">{{ review.created_at }}</p>
                                    </div>
                                    <span class="flex flex-none items-center gap-1 text-xs font-medium text-ivt-ink-soft">
                                        <StarIcon class="h-3.5 w-3.5 text-ivt-gold" /> {{ review.rating }}
                                    </span>
                                </div>
                                <p v-if="review.comment" class="text-sm leading-relaxed text-ivt-ink-soft">{{ review.comment }}</p>
                            </li>
                        </ul>

                        <button
                            v-if="listing.reviews.length > 3"
                            type="button"
                            @click="reviewsExpanded = !reviewsExpanded"
                            class="mt-4 rounded-full border border-ivt-line px-5 py-2.5 text-sm font-semibold text-ivt-ink transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
                        >
                            {{ reviewsExpanded ? 'Arată mai puține' : `Vezi toate cele ${listing.reviews.length} recenzii` }}
                        </button>
                    </template>
                </section>
            </div>

            <!-- Sidebar -->
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="space-y-4">
                    <div id="contact" class="scroll-mt-32 rounded-[18px] border border-ivt-line bg-white p-6 shadow-ivt-soft">
                        <h3 class="font-serif text-lg text-ivt-ink">Contactează {{ listing.provider.company_name }}</h3>

                        <div class="mt-4 space-y-2.5">
                            <a
                                v-if="listing.provider.phone"
                                :href="`tel:${listing.provider.phone}`"
                                @click="trackClick('phone_click')"
                                class="flex items-center justify-center gap-2 rounded-xl bg-ivt-ink px-4 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:bg-ivt-wine"
                            >
                                <PhoneIcon class="h-4 w-4" /> {{ listing.provider.phone }}
                            </a>
                            <a
                                v-if="listing.provider.whatsapp"
                                :href="`https://wa.me/${listing.provider.whatsapp.replace(/[^0-9]/g, '')}`"
                                target="_blank"
                                rel="noopener"
                                @click="trackClick('whatsapp_click')"
                                class="flex items-center justify-center gap-2 rounded-xl border border-ivt-line px-4 py-3 text-sm font-semibold text-ivt-ink transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
                            >
                                <ChatBubbleLeftEllipsisIcon class="h-4 w-4" /> Scrie pe WhatsApp
                            </a>
                            <a
                                v-if="listing.provider.email"
                                :href="`mailto:${listing.provider.email}`"
                                @click="trackClick('email_click')"
                                class="flex items-center justify-center gap-2 rounded-xl border border-ivt-line px-4 py-3 text-sm font-semibold text-ivt-ink transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
                            >
                                <EnvelopeIcon class="h-4 w-4" /> {{ listing.provider.email }}
                            </a>
                        </div>

                        <div class="mt-5 border-t border-ivt-line pt-5">
                            <p class="mb-3 text-sm font-semibold text-ivt-ink">Trimite o cerere de ofertă</p>

                            <template v-if="isGuest()">
                                <p class="text-sm text-ivt-ink-soft">Autentifică-te pentru a contacta acest furnizor.</p>
                                <div class="mt-3 flex gap-2">
                                    <Link href="/login" class="flex-1 rounded-xl bg-ivt-ink px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-ivt-wine">Conectare</Link>
                                    <Link href="/register/client" class="flex-1 rounded-xl border border-ivt-line px-4 py-2.5 text-center text-sm font-semibold text-ivt-ink hover:border-ivt-gold">Cont nou</Link>
                                </div>
                            </template>

                            <template v-else-if="!can().submitQuoteRequest">
                                <p class="text-sm text-ivt-ink-soft">Contul tău nu poate trimite cereri de ofertă.</p>
                            </template>

                            <template v-else-if="quoteSent">
                                <p class="text-sm text-ivt-sage">Cererea ta a fost trimisă furnizorului.</p>
                            </template>

                            <form v-else @submit.prevent="submitQuote" class="space-y-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium uppercase tracking-[0.04em] text-ivt-ink-faint">Data evenimentului (opțional)</label>
                                    <input v-model="quoteForm.event_date" type="date" class="w-full rounded-xl border-ivt-line text-sm text-ivt-ink focus:border-ivt-gold focus:ring-ivt-gold/20" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium uppercase tracking-[0.04em] text-ivt-ink-faint">Mesaj</label>
                                    <textarea v-model="quoteForm.message" rows="3" class="w-full rounded-xl border-ivt-line text-sm text-ivt-ink focus:border-ivt-gold focus:ring-ivt-gold/20" />
                                    <p v-if="quoteForm.errors.message" class="mt-1 text-xs text-ivt-wine">{{ quoteForm.errors.message }}</p>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="quoteForm.processing"
                                    class="w-full rounded-xl bg-gradient-to-b from-ivt-wine-bright to-ivt-wine px-4 py-3 text-sm font-semibold text-white transition-colors duration-150 disabled:opacity-60"
                                >
                                    Trimite mesajul
                                </button>
                            </form>
                        </div>

                        <div v-if="listing.provider.website" class="mt-5 border-t border-ivt-line pt-5">
                            <a :href="listing.provider.website" target="_blank" rel="noopener" class="flex items-center gap-2 text-sm font-medium text-ivt-ink-soft transition-colors duration-150 hover:text-ivt-wine">
                                <GlobeAltIcon class="h-4 w-4" /> {{ listing.provider.website }}
                            </a>
                        </div>
                    </div>

                    <div class="rounded-[18px] bg-ivt-paper-2 p-6">
                        <div class="flex justify-between border-b border-ivt-line py-2.5 text-[13.5px]">
                            <span class="text-ivt-ink-faint">Categorie</span>
                            <span class="font-semibold text-ivt-ink">{{ listing.category }}</span>
                        </div>
                        <div v-if="listing.locality || listing.county" class="flex justify-between border-b border-ivt-line py-2.5 text-[13.5px]">
                            <span class="text-ivt-ink-faint">Locație</span>
                            <span class="font-semibold text-ivt-ink">{{ [listing.locality, listing.county].filter(Boolean).join(', ') }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2.5 text-[13.5px]">
                            <span class="inline-flex items-center gap-1.5 text-ivt-ink-faint"><EyeIcon class="h-3.5 w-3.5" /> Vizualizări</span>
                            <span class="font-semibold text-ivt-ink">{{ listing.views_count }}</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Similar listings -->
        <div v-if="related.length" class="mt-16 border-t border-ivt-line pt-14">
            <h2 class="mb-6 font-serif text-2xl text-ivt-ink">Furnizori similari{{ listing.locality ? ` în ${listing.locality}` : '' }}</h2>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="item in related"
                    :key="item.id"
                    :href="route('listings.show', item.slug)"
                    class="group overflow-hidden rounded-2xl border border-ivt-line bg-white transition-all duration-300 hover:-translate-y-1 hover:shadow-ivt-soft"
                >
                    <div class="h-[130px] overflow-hidden bg-gradient-to-br from-ivt-paper-2 to-ivt-paper-3">
                        <img v-if="item.cover_url" :src="item.cover_url" :alt="item.title" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                        <div v-else class="flex h-full w-full items-center justify-center">
                            <component :is="categoryIcon(item.category_slug)" class="h-8 w-8 text-ivt-wine/25" />
                        </div>
                    </div>
                    <div class="p-4">
                        <h4 class="font-serif text-base text-ivt-ink">{{ item.title }}</h4>
                        <p class="mt-1 truncate text-xs text-ivt-ink-faint">{{ item.category }} · {{ item.provider.company_name }}</p>
                        <div class="mt-2.5 flex items-center justify-between">
                            <span v-if="item.rating" class="inline-flex items-center gap-1 text-xs text-ivt-gold"><StarIcon class="h-3.5 w-3.5" /> {{ item.rating }}</span>
                            <span v-else />
                            <span class="text-sm font-semibold text-ivt-ink">{{ formatListingPrice(item) }}</span>
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
            <div
                v-if="lightboxIndex !== null"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-ivt-ink/90 p-4"
                @click.self="closeLightbox"
            >
                <button type="button" @click="closeLightbox" aria-label="Închide" class="absolute right-5 top-5 text-white/80 transition-colors duration-150 hover:text-white">
                    <XMarkIcon class="h-7 w-7" />
                </button>
                <button
                    v-if="listing.media.length > 1"
                    type="button"
                    @click="prevMedia"
                    aria-label="Fotografia anterioară"
                    class="absolute left-3 text-white/70 transition-colors duration-150 hover:text-white sm:left-8"
                >
                    <ChevronLeftIcon class="h-8 w-8" />
                </button>
                <button
                    v-if="listing.media.length > 1"
                    type="button"
                    @click="nextMedia"
                    aria-label="Fotografia următoare"
                    class="absolute right-3 text-white/70 transition-colors duration-150 hover:text-white sm:right-8"
                >
                    <ChevronRightIcon class="h-8 w-8" />
                </button>
                <video v-if="activeLightboxMedia?.type === 'video'" :src="activeLightboxMedia.url" controls autoplay class="max-h-[85vh] max-w-full rounded-xl" />
                <img v-else :src="activeLightboxMedia?.url" :alt="listing.title" class="max-h-[85vh] max-w-full rounded-xl object-contain" />
            </div>
        </Teleport>
    </ClientLayout>
</template>
