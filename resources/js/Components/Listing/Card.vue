<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { HeartIcon, MapPinIcon } from '@heroicons/vue/24/outline';
import { HeartIcon as HeartIconSolid, StarIcon } from '@heroicons/vue/24/solid';
import { categoryIcon } from '@/Composables/useCategoryIcon';
import { formatListingPrice } from '@/Composables/useListingPrice';
import { useFavoriteToggle } from '@/Composables/useFavoriteToggle';

const props = defineProps({
    listing: { type: Object, required: true },
    favorited: { type: Boolean, default: false },
    showFavorite: { type: Boolean, default: true },
});

const emit = defineEmits(['favorite-toggled']);

const { isFavorited, toggle: toggleFavorite } = useFavoriteToggle(
    props.listing.slug,
    props.favorited,
    (favorited) => emit('favorite-toggled', { id: props.listing.id, favorited }),
    props.listing.cover_url,
);

const location = (listing) => [listing.locality, listing.county].filter(Boolean).join(', ');

const imageLoaded = ref(false);
</script>

<template>
    <div class="group relative flex flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-lg shadow-ink/10 transition-all duration-200 hover:-translate-y-1 hover:shadow-glow-brand">
        <Link :href="route('listings.show', listing.slug)" class="relative block aspect-[4/3] overflow-hidden bg-gradient-to-br from-brand-50 to-paper">
            <div v-if="listing.cover_url && !imageLoaded" class="absolute inset-0 animate-pulse bg-line/60" />
            <img
                v-if="listing.cover_url"
                :src="listing.cover_url"
                :alt="listing.title"
                class="h-full w-full object-cover transition-all duration-300 group-hover:scale-105"
                :class="{ 'opacity-0': !imageLoaded }"
                @load="imageLoaded = true"
                @error="imageLoaded = true"
            />
            <div v-else class="flex h-full w-full items-center justify-center">
                <component :is="categoryIcon(listing.category_slug)" class="h-10 w-10 text-brand-500/30" />
            </div>

            <span v-if="listing.is_featured" class="absolute left-3 top-3 rounded-full bg-gold-400 px-2.5 py-1 text-xs font-semibold text-white shadow-sm">
                Recomandat
            </span>
        </Link>

        <button
            v-if="showFavorite"
            type="button"
            @click="toggleFavorite"
            class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-ink-soft shadow-sm backdrop-blur transition-colors duration-150 hover:text-rose-500"
            :aria-label="isFavorited ? 'Elimină de la favorite' : 'Adaugă la favorite'"
        >
            <HeartIconSolid v-if="isFavorited" class="h-4 w-4 text-rose-500" />
            <HeartIcon v-else class="h-4 w-4" />
        </button>

        <div class="flex flex-1 flex-col p-4">
            <div class="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-brand-500">
                <component :is="categoryIcon(listing.category_slug)" class="h-3.5 w-3.5" />
                {{ listing.category }}
            </div>

            <Link :href="route('listings.show', listing.slug)" class="text-sm font-semibold leading-snug text-ink line-clamp-2 transition-colors duration-150 hover:text-brand-600">
                {{ listing.title }}
            </Link>

            <p v-if="location(listing)" class="mt-1.5 flex items-center gap-1 text-xs text-ink-soft">
                <MapPinIcon class="h-3.5 w-3.5 flex-none" /> {{ location(listing) }}
            </p>

            <p v-if="listing.provider?.company_name" class="mt-1 truncate text-xs text-ink-soft/80">
                {{ listing.provider.company_name }}
            </p>

            <div class="mt-auto flex items-center justify-between pt-3">
                <span class="text-sm font-semibold text-ink">{{ formatListingPrice(listing) }}</span>
                <span v-if="listing.rating" class="inline-flex items-center gap-1 text-xs font-medium text-ink-soft">
                    <StarIcon class="h-3.5 w-3.5 text-gold-400" /> {{ listing.rating }}
                    <span v-if="listing.reviews_count" class="text-ink-soft/60">({{ listing.reviews_count }})</span>
                </span>
            </div>
        </div>
    </div>
</template>
