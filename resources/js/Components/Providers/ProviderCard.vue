<script setup>
import { Link } from '@inertiajs/vue3';
import { MapPinIcon, PhoneIcon, EnvelopeIcon, ChatBubbleLeftEllipsisIcon, HeartIcon } from '@heroicons/vue/24/outline';
import { HeartIcon as HeartIconSolid, StarIcon } from '@heroicons/vue/24/solid';
import { categoryIcon } from '@/Composables/useCategoryIcon';
import { formatListingPrice } from '@/Composables/useListingPrice';
import { useProviderFavoriteToggle } from '@/Composables/useProviderFavoriteToggle';

const props = defineProps({
    provider: { type: Object, required: true },
    favorited: { type: Boolean, default: false },
    showFavorite: { type: Boolean, default: true },
});

const emit = defineEmits(['favorite-toggled']);

const { isFavorited, toggle: toggleFavorite } = useProviderFavoriteToggle(
    props.provider.slug,
    props.favorited,
    (favorited) => emit('favorite-toggled', { id: props.provider.id, favorited }),
    props.provider.logo_url ?? props.provider.cover_url,
);

const location = (provider) => [provider.locality, provider.county].filter(Boolean).join(', ');
</script>

<template>
    <div class="group relative flex overflow-hidden rounded-[18px] border border-ivt-line bg-white shadow-[0_18px_36px_-30px_rgba(22,40,31,0.25)] transition-all duration-300 hover:-translate-y-1 hover:shadow-ivt-soft">
        <Link :href="route('providers.show', provider.slug)" class="relative block w-[150px] flex-none overflow-hidden bg-gradient-to-br from-ivt-paper-2 to-ivt-paper-3">
            <img
                v-if="provider.cover_url || provider.logo_url"
                :src="provider.cover_url || provider.logo_url"
                :alt="provider.company_name"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
            <div v-else class="flex h-full w-full items-center justify-center">
                <component :is="categoryIcon(provider.category_slug)" class="h-9 w-9 text-ivt-wine/25" />
            </div>

            <span
                v-if="provider.is_featured"
                class="absolute left-2.5 top-2.5 inline-flex items-center gap-1 rounded-full border border-white/15 bg-ivt-ink px-2.5 py-1 text-[9.5px] font-bold uppercase tracking-[0.07em] text-ivt-gold-bright"
            >
                Premium
            </span>
        </Link>

        <button
            v-if="showFavorite"
            type="button"
            @click="toggleFavorite"
            class="absolute right-2.5 top-2.5 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-ivt-wine shadow-sm backdrop-blur transition-colors duration-150 hover:text-ivt-wine-bright"
            :aria-label="isFavorited ? 'Elimină de la favorite' : 'Adaugă la favorite'"
        >
            <HeartIconSolid v-if="isFavorited" class="h-3.5 w-3.5 text-ivt-wine" />
            <HeartIcon v-else class="h-3.5 w-3.5" />
        </button>

        <div class="flex flex-1 flex-col p-[18px]">
            <Link :href="route('providers.show', provider.slug)" class="font-serif text-lg font-medium leading-snug text-ivt-ink transition-colors hover:text-ivt-wine">
                {{ provider.company_name }}
            </Link>
            <p v-if="location(provider)" class="mt-1 flex items-center gap-1 text-xs text-ivt-ink-faint">
                <MapPinIcon class="h-3.5 w-3.5 flex-none" /> {{ location(provider) }}
            </p>

            <div v-if="provider.rating" class="mt-2 flex items-center gap-1.5 text-[12.5px]">
                <span class="flex items-center gap-0.5 text-ivt-gold"><StarIcon class="h-3.5 w-3.5" /></span>
                <span class="text-ivt-ink-faint">{{ provider.rating }} · {{ provider.reviews_count }} recenzii</span>
            </div>

            <p v-if="provider.category" class="mt-1.5 truncate text-xs text-ivt-ink-faint">{{ provider.category }}</p>

            <div class="mt-auto flex items-center justify-between border-t border-ivt-line pt-3.5">
                <span class="text-[13px] font-semibold text-ivt-ink">{{ formatListingPrice(provider) }}</span>
                <div class="flex gap-1.5">
                    <a
                        v-if="provider.phone"
                        :href="`tel:${provider.phone}`"
                        title="Sună"
                        class="flex h-[29px] w-[29px] items-center justify-center rounded-full bg-ivt-ink text-ivt-paper transition-colors duration-150 hover:bg-ivt-wine"
                    >
                        <PhoneIcon class="h-3.5 w-3.5" />
                    </a>
                    <a
                        v-if="provider.whatsapp"
                        :href="`https://wa.me/${provider.whatsapp.replace(/[^0-9]/g, '')}`"
                        target="_blank"
                        rel="noopener"
                        title="WhatsApp"
                        class="flex h-[29px] w-[29px] items-center justify-center rounded-full bg-ivt-ink text-ivt-paper transition-colors duration-150 hover:bg-ivt-wine"
                    >
                        <ChatBubbleLeftEllipsisIcon class="h-3.5 w-3.5" />
                    </a>
                    <a
                        v-if="provider.email"
                        :href="`mailto:${provider.email}`"
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
