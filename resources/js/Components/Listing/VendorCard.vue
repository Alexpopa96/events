<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { HeartIcon, MapPinIcon, PhoneIcon, EnvelopeIcon, ChatBubbleLeftEllipsisIcon } from '@heroicons/vue/24/outline';
import { HeartIcon as HeartIconSolid, StarIcon } from '@heroicons/vue/24/solid';
import { categoryIcon } from '@/Composables/useCategoryIcon';
import { formatListingPrice } from '@/Composables/useListingPrice';
import { useFavoriteToggle } from '@/Composables/useFavoriteToggle';

const props = defineProps({
    listing: { type: Object, required: true },
    favorited: { type: Boolean, default: false },
    categorySlug: { type: String, default: null },
});

const { isFavorited, toggle: toggleFavorite } = useFavoriteToggle(props.listing.slug, props.favorited, null, props.listing.cover_url);

const location = (listing) => [listing.locality, listing.county].filter(Boolean).join(', ');
const trackClick = (type) => {
    axios.post(route('listings.event', props.listing.slug), { type }).catch(() => {});
};

const imageLoaded = ref(false);
</script>

<template>
    <div class="group relative flex overflow-hidden rounded-[18px] border border-ivt-line bg-white shadow-[0_18px_36px_-30px_rgba(22,40,31,0.25)] transition-all duration-300 hover:-translate-y-1 hover:shadow-ivt-soft">
        <Link :href="route('listings.show', listing.slug)" class="relative block w-[150px] flex-none overflow-hidden bg-gradient-to-br from-ivt-paper-2 to-ivt-paper-3">
            <div v-if="listing.cover_url && !imageLoaded" class="absolute inset-0 animate-pulse bg-ivt-line/60" />
            <img
                v-if="listing.cover_url"
                :src="listing.cover_url"
                :alt="listing.title"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                :class="{ 'opacity-0': !imageLoaded }"
                @load="imageLoaded = true"
                @error="imageLoaded = true"
            />
            <div v-else class="flex h-full w-full items-center justify-center">
                <component :is="categoryIcon(categorySlug)" class="h-9 w-9 text-ivt-wine/25" />
            </div>

            <span
                v-if="listing.is_featured"
                class="absolute left-2.5 top-2.5 inline-flex items-center gap-1 rounded-full border border-white/15 bg-ivt-ink px-2.5 py-1 text-[9.5px] font-bold uppercase tracking-[0.07em] text-ivt-gold-bright"
            >
                Premium
            </span>
        </Link>

        <button
            type="button"
            @click="toggleFavorite"
            class="absolute right-2.5 top-2.5 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-ivt-wine shadow-sm backdrop-blur transition-colors duration-150 hover:text-ivt-wine-bright"
            :aria-label="isFavorited ? 'Elimină de la favorite' : 'Adaugă la favorite'"
        >
            <HeartIconSolid v-if="isFavorited" class="h-3.5 w-3.5 text-ivt-wine" />
            <HeartIcon v-else class="h-3.5 w-3.5" />
        </button>

        <div class="flex flex-1 flex-col p-[18px]">
            <Link :href="route('listings.show', listing.slug)" class="font-serif text-lg font-medium leading-snug text-ivt-ink transition-colors hover:text-ivt-wine">
                {{ listing.title }}
            </Link>
            <p v-if="location(listing)" class="mt-1 flex items-center gap-1 text-xs text-ivt-ink-faint">
                <MapPinIcon class="h-3.5 w-3.5 flex-none" /> {{ location(listing) }}
            </p>

            <div v-if="listing.rating" class="mt-2 flex items-center gap-1.5 text-[12.5px]">
                <span class="flex items-center gap-0.5 text-ivt-gold"><StarIcon class="h-3.5 w-3.5" /></span>
                <span class="text-ivt-ink-faint">{{ listing.rating }} · {{ listing.reviews_count }} recenzii</span>
            </div>

            <p v-if="listing.provider?.company_name" class="mt-1.5 truncate text-xs text-ivt-ink-faint">{{ listing.provider.company_name }}</p>

            <div class="mt-auto flex items-center justify-between border-t border-ivt-line pt-3.5">
                <span class="text-[13px] font-semibold text-ivt-ink">{{ formatListingPrice(listing) }}</span>
                <div class="flex gap-1.5">
                    <a
                        v-if="listing.provider?.phone"
                        :href="`tel:${listing.provider.phone}`"
                        @click="trackClick('phone_click')"
                        title="Sună"
                        class="flex h-[29px] w-[29px] items-center justify-center rounded-full bg-ivt-ink text-ivt-paper transition-colors duration-150 hover:bg-ivt-wine"
                    >
                        <PhoneIcon class="h-3.5 w-3.5" />
                    </a>
                    <a
                        v-if="listing.provider?.whatsapp"
                        :href="`https://wa.me/${listing.provider.whatsapp.replace(/[^0-9]/g, '')}`"
                        target="_blank"
                        rel="noopener"
                        @click="trackClick('whatsapp_click')"
                        title="WhatsApp"
                        class="flex h-[29px] w-[29px] items-center justify-center rounded-full bg-ivt-ink text-ivt-paper transition-colors duration-150 hover:bg-ivt-wine"
                    >
                        <ChatBubbleLeftEllipsisIcon class="h-3.5 w-3.5" />
                    </a>
                    <a
                        v-if="listing.provider?.email"
                        :href="`mailto:${listing.provider.email}`"
                        @click="trackClick('email_click')"
                        title="Email"
                        class="flex h-[29px] w-[29px] items-center justify-center rounded-full bg-ivt-ink text-ivt-paper transition-colors duration-150 hover:bg-ivt-wine"
                    >
                        <EnvelopeIcon class="h-3.5 w-3.5" />
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
