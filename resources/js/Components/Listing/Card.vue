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
    <div class="group relative flex flex-col overflow-hidden rounded-3xl bg-white p-2 shadow-lg shadow-ink/5 ring-1 ring-line transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-primary/15 hover:ring-primary/20">
        <Link :href="route('listings.show', listing.slug)" class="relative block aspect-[4/3] overflow-hidden rounded-2xl bg-gradient-to-br from-primary/10 to-paper">
            <div v-if="listing.cover_url && !imageLoaded" class="absolute inset-0 animate-pulse bg-line/60" />
            <img
                v-if="listing.cover_url"
                :src="listing.cover_url"
                :alt="listing.title"
                class="h-full w-full object-cover transition-all duration-300 duration-500 group-hover:scale-105"
                :class="{ 'opacity-0': !imageLoaded }"
                @load="imageLoaded = true"
                @error="imageLoaded = true"
            />
            <div v-else class="flex h-full w-full items-center justify-center">
                <component :is="categoryIcon(listing.category_slug)" class="h-10 w-10 text-primary/30" />
            </div>

            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-black/30 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" />

            <span v-if="listing.is_featured" class="absolute left-3 top-3 rounded-full bg-primary/95 px-3 py-1 text-xs font-semibold text-white shadow-md backdrop-blur">
                Recomandat
            </span>
        </Link>

        <button
            v-if="showFavorite"
            type="button"
            @click="toggleFavorite"
            class="absolute right-5 top-5 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-ink-soft shadow-md backdrop-blur transition-all duration-150 hover:scale-110 hover:text-primary"
            :aria-label="isFavorited ? 'Elimină de la favorite' : 'Adaugă la favorite'"
        >
            <HeartIconSolid v-if="isFavorited" class="h-4 w-4 text-primary" />
            <HeartIcon v-else class="h-4 w-4" />
        </button>

        <div class="flex flex-1 flex-col px-3 pb-3 pt-4">
            <div class="mb-2 inline-flex w-fit items-center gap-1.5 rounded-full bg-primary/10 px-2.5 py-1 text-[11px] font-semibold text-primary">
                <component :is="categoryIcon(listing.category_slug)" class="h-3.5 w-3.5" />
                {{ listing.category }}
            </div>

            <Link :href="route('listings.show', listing.slug)" class="text-[15px] font-semibold leading-snug text-ink line-clamp-2 transition-colors duration-150 hover:text-primary-bright">
                {{ listing.title }}
            </Link>

            <p v-if="location(listing)" class="mt-1.5 flex items-center gap-1 text-xs text-ink-soft">
                <MapPinIcon class="h-3.5 w-3.5 flex-none" /> {{ location(listing) }}
            </p>

            <p v-if="listing.provider?.company_name" class="mb-4 mt-1 truncate text-xs text-ink-soft/80">
                {{ listing.provider.company_name }}
            </p>

            <div class="mt-auto flex items-center justify-between border-t border-line/70 pt-3">
                <span class="text-[15px] font-bold text-primary">{{ formatListingPrice(listing) }}</span>
                <span v-if="listing.rating" class="inline-flex items-center gap-1 rounded-full bg-primary/5 px-2 py-1 text-xs font-semibold text-ink">
                    <StarIcon class="h-3.5 w-3.5 text-primary" /> {{ listing.rating }}
                    <span v-if="listing.reviews_count" class="text-ink-soft/60">({{ listing.reviews_count }})</span>
                </span>
            </div>
        </div>
    </div>
</template>
