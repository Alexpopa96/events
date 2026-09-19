<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { HeartIcon as HeartIconSolid } from '@heroicons/vue/24/solid';
import { categoryIcon } from '@/Composables/useCategoryIcon';
import { formatListingPrice } from '@/Composables/useListingPrice';
import { useFavoriteToggle } from '@/Composables/useFavoriteToggle';

const props = defineProps({
    listing: { type: Object, required: true },
});

const emit = defineEmits(['favorite-toggled']);

const { toggle: toggleFavorite } = useFavoriteToggle(
    props.listing.slug,
    true,
    (favorited) => emit('favorite-toggled', { id: props.listing.id, favorited }),
    props.listing.cover_url,
);

const imageLoaded = ref(false);
</script>

<template>
    <div class="flex overflow-hidden rounded-[18px] border border-ivt-line bg-white transition-all duration-300 hover:-translate-y-1 hover:shadow-ivt-soft">
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
                <component :is="categoryIcon(listing.category_slug)" class="h-9 w-9 text-ivt-wine/25" />
            </div>
        </Link>

        <div class="flex flex-1 flex-col p-[18px]">
            <p class="text-[10.5px] font-bold uppercase tracking-[0.08em] text-ivt-wine">{{ listing.category }}</p>
            <Link :href="route('listings.show', listing.slug)" class="mt-1.5 font-serif text-[17px] font-medium leading-snug text-ivt-ink transition-colors hover:text-ivt-wine">
                {{ listing.title }}
            </Link>
            <p v-if="listing.provider?.company_name" class="mt-1 text-xs text-ivt-ink-faint">{{ listing.provider.company_name }}</p>

            <div class="mt-auto flex items-center justify-between border-t border-ivt-line pt-3.5">
                <span class="text-[13.5px] font-semibold text-ivt-ink">{{ formatListingPrice(listing) }}</span>
                <button
                    type="button"
                    @click="toggleFavorite"
                    class="text-ivt-wine transition-colors duration-150 hover:text-ivt-wine-bright"
                    aria-label="Elimină de la favorite"
                >
                    <HeartIconSolid class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>
