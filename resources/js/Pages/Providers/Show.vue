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
} from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/24/solid';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { categoryIcon } from '@/Composables/useCategoryIcon';
import { formatListingPrice } from '@/Composables/useListingPrice';

const props = defineProps({
    provider: { type: Object, required: true },
    listings: { type: Array, default: () => [] },
    reviews: { type: Array, default: () => [] },
});

const location = computed(() => [props.provider.locality, props.provider.county].filter(Boolean).join(', '));
const categoryNames = computed(() => props.provider.categories.slice(0, 3).map((c) => c.name).join(', '));

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
    { id: 'anunturi', label: `Anunțuri (${listings.value.length})` },
    { id: 'despre', label: 'Despre' },
    { id: 'recenzii', label: `Recenzii (${props.provider.reviews_count})` },
]);
const activeTab = ref('anunturi');

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
    'from-[#E9DDBB] via-[#B8923F] to-[#7C2E3B]',
    'from-[#93A88B] to-[#1A3329]',
    'from-[#D9B673] via-[#7C2E3B] to-[#3a1219]',
    'from-[#C9A24F] to-[#22402F]',
];
const listingGradient = (index) => gradients[index % gradients.length];

/* ---------- reviews ---------- */
const reviewsExpanded = ref(false);
const visibleReviews = computed(() => (reviewsExpanded.value ? props.reviews : props.reviews.slice(0, 3)));

const ratingCounts = computed(() => {
    const counts = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
    props.reviews.forEach((review) => { counts[review.rating] = (counts[review.rating] ?? 0) + 1; });
    return counts;
});
const ratingPct = (star) => (props.reviews.length ? Math.round((ratingCounts.value[star] / props.reviews.length) * 100) : 0);

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
</script>

<template>
    <Head :title="`${provider.company_name} — Invita`" />

    <div class="bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <div class="border-b border-ivt-line bg-ivt-paper-2 py-[18px]">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <p class="flex flex-wrap items-center gap-2 text-[13px] text-ivt-ink-faint">
                    <Link :href="route('home')" class="text-ivt-ink-soft transition-colors hover:text-ivt-wine">Acasă</Link>
                    <span>›</span>
                    <Link :href="route('providers.index')" class="text-ivt-ink-soft transition-colors hover:text-ivt-wine">Furnizori</Link>
                    <span>›</span>
                    <span>{{ provider.company_name }}</span>
                </p>
            </div>
        </div>

        <main>
            <!-- PROFILE HERO -->
            <section class="relative">
                <div
                    class="relative h-[220px] overflow-hidden sm:h-[260px]"
                    :class="provider.cover_url ? '' : 'bg-gradient-to-br from-[#E9DDBB] via-[#B8923F] to-[#7C2E3B]'"
                    :style="provider.cover_url ? { backgroundImage: `url(${provider.cover_url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}"
                >
                    <div class="absolute inset-0 bg-gradient-to-t from-ivt-ink/40 via-transparent to-transparent" />
                    <div class="absolute right-6 top-5 flex gap-2.5 lg:right-8">
                        <button
                            type="button"
                            @click="shareProvider"
                            aria-label="Distribuie profilul"
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-ivt-ink shadow-sm backdrop-blur transition-transform duration-150 hover:scale-105"
                        >
                            <ShareIcon class="h-[18px] w-[18px]" />
                        </button>
                    </div>
                    <span v-if="shareCopied" class="absolute bottom-4 right-6 rounded-full bg-ivt-ink px-3 py-1.5 text-xs font-medium text-ivt-on-dark">Link copiat</span>
                </div>

                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="relative z-10 -mt-[58px] flex flex-wrap items-end justify-between gap-6 rounded-[22px] border border-ivt-line bg-white p-7 shadow-ivt-soft">
                        <div class="flex items-end gap-5">
                            <div class="-mt-12 flex h-[88px] w-[88px] flex-none items-center justify-center overflow-hidden rounded-2xl border-4 border-white bg-gradient-to-br from-ivt-ink-2 to-ivt-ink shadow-ivt-soft">
                                <img v-if="provider.logo_url" :src="provider.logo_url" :alt="provider.company_name" class="h-full w-full object-cover" />
                                <component v-else :is="categoryIcon(provider.categories[0]?.slug)" class="h-9 w-9 text-ivt-gold-bright" />
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h1 class="font-serif text-[26px] text-ivt-ink">{{ provider.company_name }}</h1>
                                    <span v-if="provider.is_featured" class="inline-flex items-center gap-1 rounded-full border border-white/15 bg-ivt-ink px-3 py-1.5 text-[10.5px] font-bold uppercase tracking-[0.07em] text-ivt-gold-bright">
                                        Premium
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full border border-ivt-sage px-3 py-1.5 text-[10.5px] font-bold uppercase tracking-[0.05em] text-ivt-sage">
                                        <CheckBadgeIcon class="h-3.5 w-3.5" /> Verificat
                                    </span>
                                </div>
                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-ivt-ink-soft">
                                    <span v-if="categoryNames">{{ categoryNames }}</span>
                                    <span v-if="location" class="inline-flex items-center gap-1">
                                        <MapPinIcon class="h-4 w-4" /> {{ location }}
                                    </span>
                                    <span v-if="provider.rating" class="inline-flex items-center gap-1 font-medium text-ivt-ink">
                                        <StarIcon class="h-4 w-4 text-ivt-gold" /> {{ provider.rating }}
                                        <span class="font-normal text-ivt-ink-faint">· {{ provider.reviews_count }} recenzii</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <a href="#contact" class="rounded-full bg-gradient-to-b from-ivt-wine-bright to-ivt-wine px-5 py-2.5 text-sm font-semibold text-white transition-transform duration-150 hover:-translate-y-0.5">
                            Trimite mesaj
                        </a>
                    </div>

                    <div class="mt-5 flex flex-wrap overflow-hidden rounded-2xl border border-ivt-line">
                        <div class="flex-1 border-r border-ivt-line px-5 py-4 text-center">
                            <b class="block font-serif text-[22px] font-semibold text-ivt-ink">{{ provider.listings_count }}</b>
                            <span class="text-[11.5px] text-ivt-ink-faint">anunțuri active</span>
                        </div>
                        <div class="flex-1 border-r border-ivt-line px-5 py-4 text-center">
                            <b class="block font-serif text-[22px] font-semibold text-ivt-ink">{{ provider.reviews_count }}</b>
                            <span class="text-[11.5px] text-ivt-ink-faint">recenzii</span>
                        </div>
                        <div class="flex-1 border-r border-ivt-line px-5 py-4 text-center">
                            <b class="block font-serif text-[22px] font-semibold text-ivt-ink">{{ provider.rating ?? '—' }}</b>
                            <span class="text-[11.5px] text-ivt-ink-faint">rating mediu</span>
                        </div>
                        <div class="flex-1 px-5 py-4 text-center">
                            <b class="block font-serif text-[22px] font-semibold text-ivt-ink">{{ provider.member_since ?? '—' }}</b>
                            <span class="text-[11.5px] text-ivt-ink-faint">furnizor din</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CONTENT -->
            <section class="py-11 pb-[100px]">
                <div class="mx-auto grid max-w-7xl grid-cols-1 gap-9 px-6 lg:grid-cols-[1fr_300px] lg:px-8">

                    <div class="min-w-0">
                        <nav class="mb-8 flex flex-wrap gap-2 border-b border-ivt-line">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                type="button"
                                @click="activeTab = tab.id"
                                class="relative mr-6 whitespace-nowrap pb-3 text-[14.5px] font-semibold transition-colors duration-150"
                                :class="activeTab === tab.id ? 'text-ivt-ink' : 'text-ivt-ink-faint hover:text-ivt-ink-soft'"
                            >
                                {{ tab.label }}
                                <span v-if="activeTab === tab.id" class="absolute inset-x-0 -bottom-px h-0.5 rounded-full bg-ivt-wine" />
                            </button>
                        </nav>

                        <!-- LISTINGS -->
                        <section v-show="activeTab === 'anunturi'" class="mb-[52px]">
                            <div class="mb-5 flex flex-wrap items-center justify-between gap-2.5">
                                <h2 class="font-serif text-[21px] text-ivt-ink">Anunțurile publicate de {{ provider.company_name }}</h2>
                                <span class="text-[13px] text-ivt-ink-faint">{{ listings.length }} anunțuri active</span>
                            </div>

                            <div v-if="categoryChips.length > 1" class="mb-[22px] flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    @click="activeCategory = null"
                                    class="whitespace-nowrap rounded-full border px-4 py-2 text-[13px] font-semibold transition-colors duration-150"
                                    :class="activeCategory === null ? 'border-ivt-ink bg-ivt-ink text-white' : 'border-ivt-line text-ivt-ink-soft hover:border-ivt-gold hover:text-ivt-wine'"
                                >
                                    Toate ({{ listings.length }})
                                </button>
                                <button
                                    v-for="category in categoryChips"
                                    :key="category.id"
                                    type="button"
                                    @click="activeCategory = category.slug"
                                    class="whitespace-nowrap rounded-full border px-4 py-2 text-[13px] font-semibold transition-colors duration-150"
                                    :class="activeCategory === category.slug ? 'border-ivt-ink bg-ivt-ink text-white' : 'border-ivt-line text-ivt-ink-soft hover:border-ivt-gold hover:text-ivt-wine'"
                                >
                                    {{ category.name }} ({{ category.count }})
                                </button>
                            </div>

                            <div v-if="filteredListings.length" class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <Link
                                    v-for="(item, index) in filteredListings"
                                    :key="item.id"
                                    :href="route('listings.show', item.slug)"
                                    class="group flex overflow-hidden rounded-[18px] border border-ivt-line transition-all duration-300 hover:-translate-y-1 hover:shadow-ivt-soft"
                                >
                                    <div class="relative w-[140px] flex-none overflow-hidden bg-gradient-to-br" :class="item.cover_url ? '' : listingGradient(index)">
                                        <img v-if="item.cover_url" :src="item.cover_url" :alt="item.title" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                                        <span v-if="item.is_featured" class="absolute left-2.5 top-2.5 rounded-full bg-ivt-ink px-2.5 py-1 text-[9px] font-bold uppercase tracking-[0.06em] text-ivt-gold-bright">
                                            Premium
                                        </span>
                                    </div>
                                    <div class="flex flex-1 flex-col p-[18px]">
                                        <p class="text-[10.5px] font-bold uppercase tracking-[0.08em] text-ivt-wine">{{ item.category }}</p>
                                        <h4 class="mt-1.5 font-serif text-base font-medium text-ivt-ink">{{ item.title }}</h4>
                                        <p v-if="item.description" class="mt-1.5 line-clamp-2 text-[12.5px] leading-relaxed text-ivt-ink-faint">{{ item.description }}</p>
                                        <div class="mt-auto flex items-center justify-between pt-3.5">
                                            <span class="text-sm font-bold text-ivt-ink">{{ formatListingPrice(item) }}</span>
                                            <div class="flex items-center gap-2.5 text-[11.5px] text-ivt-ink-faint">
                                                <span v-if="item.rating" class="inline-flex items-center gap-1"><StarIcon class="h-3 w-3 text-ivt-gold" /> {{ item.rating }}</span>
                                                <span class="inline-flex items-center gap-1"><EyeIcon class="h-3.5 w-3.5" /> {{ item.views_count }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                            <p v-else class="rounded-2xl border border-dashed border-ivt-line bg-white py-10 text-center text-sm text-ivt-ink-faint">
                                Niciun anunț în această categorie.
                            </p>
                        </section>

                        <!-- ABOUT -->
                        <section v-show="activeTab === 'despre'" class="mb-[52px]">
                            <h2 class="mb-5 font-serif text-[21px] text-ivt-ink">Despre {{ provider.company_name }}</h2>

                            <p v-if="provider.description" class="whitespace-pre-line text-[15px] leading-[1.75] text-ivt-ink-soft">{{ provider.description }}</p>
                            <p v-else class="text-[15px] leading-[1.75] text-ivt-ink-faint">Acest furnizor nu a adăugat încă o descriere.</p>

                            <div v-if="provider.categories.length || location" class="mt-5 flex flex-wrap gap-2">
                                <span v-for="category in provider.categories" :key="category.id" class="rounded-full bg-ivt-paper-2 px-3.5 py-1.5 text-xs text-ivt-ink-soft">
                                    {{ category.name }}
                                </span>
                                <span v-if="location" class="rounded-full bg-ivt-paper-2 px-3.5 py-1.5 text-xs text-ivt-ink-soft">{{ location }}</span>
                            </div>
                        </section>

                        <!-- REVIEWS -->
                        <section v-show="activeTab === 'recenzii'">
                            <div class="mb-5 flex flex-wrap items-center justify-between gap-2.5">
                                <h2 class="font-serif text-[21px] text-ivt-ink">Recenzii</h2>
                                <span class="text-[13px] text-ivt-ink-faint">{{ provider.reviews_count }} recenzii verificate, din toate anunțurile</span>
                            </div>

                            <div v-if="!reviews.length" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-ivt-line bg-white px-5 py-12 text-center">
                                <FaceFrownIcon class="h-6 w-6 text-ivt-ink-faint/40" />
                                <p class="mt-2 text-sm font-medium text-ivt-ink">Nicio recenzie încă</p>
                                <p class="mt-1 text-sm text-ivt-ink-faint">Acest furnizor nu a primit încă recenzii.</p>
                            </div>

                            <template v-else>
                                <div class="mb-6 flex flex-wrap items-center gap-10 rounded-2xl bg-ivt-paper-2 p-[26px]">
                                    <div class="text-center">
                                        <b class="block font-serif text-[42px] font-semibold text-ivt-ink">{{ provider.rating }}</b>
                                        <div class="mt-1 flex justify-center gap-0.5 text-ivt-gold">
                                            <StarIcon v-for="n in 5" :key="n" class="h-4 w-4" />
                                        </div>
                                        <span class="mt-1.5 block text-xs text-ivt-ink-faint">din {{ provider.reviews_count }} recenzii</span>
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
                                        <div class="mb-2.5 flex items-center gap-3">
                                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-gradient-to-br from-ivt-gold-bright to-ivt-wine text-xs font-semibold text-white">
                                                {{ initials(review.author) }}
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-[13.5px] font-semibold text-ivt-ink">{{ review.author }}</p>
                                                <p class="text-[11.5px] text-ivt-ink-faint">
                                                    {{ review.created_at }}<span v-if="review.listing_title"> · {{ review.listing_title }}</span>
                                                </p>
                                            </div>
                                            <span class="ml-auto flex flex-none items-center gap-1 text-xs text-ivt-gold">
                                                <StarIcon class="h-3 w-3" /> {{ review.rating }}
                                            </span>
                                        </div>
                                        <p v-if="review.comment" class="text-sm leading-relaxed text-ivt-ink-soft">{{ review.comment }}</p>
                                    </li>
                                </ul>

                                <button
                                    v-if="reviews.length > 3"
                                    type="button"
                                    @click="reviewsExpanded = !reviewsExpanded"
                                    class="mt-4 rounded-full border border-ivt-line px-5 py-2.5 text-sm font-semibold text-ivt-ink transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
                                >
                                    {{ reviewsExpanded ? 'Arată mai puține' : `Vezi toate cele ${reviews.length} recenzii` }}
                                </button>
                            </template>
                        </section>
                    </div>

                    <!-- SIDEBAR -->
                    <aside class="lg:sticky lg:top-24 lg:self-start">
                        <div class="flex flex-col gap-4">
                            <div id="contact" class="scroll-mt-32 rounded-[18px] border border-ivt-line bg-white p-6 shadow-ivt-soft">
                                <h3 class="font-serif text-lg text-ivt-ink">Contactează {{ provider.company_name }}</h3>

                                <div class="mt-4 space-y-2.5">
                                    <a
                                        v-if="provider.phone"
                                        :href="`tel:${provider.phone}`"
                                        class="flex items-center justify-center gap-2 rounded-xl bg-ivt-ink px-4 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:bg-ivt-wine"
                                    >
                                        <PhoneIcon class="h-4 w-4" /> {{ provider.phone }}
                                    </a>
                                    <a
                                        v-if="provider.whatsapp"
                                        :href="`https://wa.me/${provider.whatsapp.replace(/[^0-9]/g, '')}`"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex items-center justify-center gap-2 rounded-xl border border-ivt-line px-4 py-3 text-sm font-semibold text-ivt-ink transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
                                    >
                                        <ChatBubbleLeftEllipsisIcon class="h-4 w-4" /> Scrie pe WhatsApp
                                    </a>
                                    <a
                                        v-if="provider.email"
                                        :href="`mailto:${provider.email}`"
                                        class="flex items-center justify-center gap-2 rounded-xl border border-ivt-line px-4 py-3 text-sm font-semibold text-ivt-ink transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
                                    >
                                        <EnvelopeIcon class="h-4 w-4" /> {{ provider.email }}
                                    </a>
                                </div>

                                <div v-if="socialLinks.length || provider.website" class="mt-[18px] flex gap-2 border-t border-ivt-line pt-[18px]">
                                    <a
                                        v-for="social in socialLinks"
                                        :key="social.platform"
                                        :href="social.url"
                                        target="_blank"
                                        rel="noopener"
                                        :aria-label="social.platform"
                                        class="flex h-[34px] w-[34px] items-center justify-center rounded-full border border-ivt-line text-[11px] font-bold text-ivt-ink-soft transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
                                    >
                                        {{ social.label }}
                                    </a>
                                    <a
                                        v-if="provider.website"
                                        :href="provider.website"
                                        target="_blank"
                                        rel="noopener"
                                        aria-label="Website"
                                        class="flex h-[34px] w-[34px] items-center justify-center rounded-full border border-ivt-line text-ivt-ink-soft transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
                                    >
                                        <GlobeAltIcon class="h-4 w-4" />
                                    </a>
                                </div>
                            </div>

                            <div class="rounded-[18px] bg-ivt-paper-2 p-[22px]">
                                <div v-if="location" class="flex justify-between border-b border-ivt-line py-2.5 text-[13.5px]">
                                    <span class="text-ivt-ink-faint">Locație</span>
                                    <span class="font-semibold text-ivt-ink">{{ location }}</span>
                                </div>
                                <div v-if="provider.address" class="flex justify-between border-b border-ivt-line py-2.5 text-[13.5px]">
                                    <span class="text-ivt-ink-faint">Adresă</span>
                                    <span class="font-semibold text-ivt-ink">{{ provider.address }}</span>
                                </div>
                                <div v-if="provider.member_since" class="flex justify-between border-b border-ivt-line py-2.5 text-[13.5px]">
                                    <span class="text-ivt-ink-faint">Membru din</span>
                                    <span class="font-semibold text-ivt-ink">{{ provider.member_since }}</span>
                                </div>
                                <div class="flex items-center justify-between py-2.5 text-[13.5px]">
                                    <span class="text-ivt-ink-faint">Anunțuri active</span>
                                    <span class="font-semibold text-ivt-ink">{{ provider.listings_count }}</span>
                                </div>
                            </div>
                        </div>
                    </aside>

                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
