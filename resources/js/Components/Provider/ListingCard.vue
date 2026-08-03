<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { EyeIcon, MapPinIcon, PencilSquareIcon, PhotoIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { categoryIcon } from '@/Composables/useCategoryIcon';
import { formatListingPrice } from '@/Composables/useListingPrice';

const props = defineProps({
    listing: { type: Object, required: true },
    preview: { type: Boolean, default: false },
});

defineEmits(['delete']);

const statusMeta = {
    draft: { label: 'Ciornă', class: 'bg-white/90 text-ink-soft' },
    pending_review: { label: 'În verificare', class: 'bg-gold-400/90 text-white' },
    published: { label: 'Publicat', class: 'bg-emerald-500/90 text-white' },
    rejected: { label: 'Respins', class: 'bg-rose-500/90 text-white' },
    archived: { label: 'Arhivat', class: 'bg-ink/70 text-white' },
};

const status = computed(() => statusMeta[props.listing.status] ?? { label: props.listing.status, class: 'bg-white/90 text-ink-soft' });
const icon = computed(() => categoryIcon(props.listing.category_slug));
const price = computed(() => formatListingPrice(props.listing));
const location = computed(() => [props.listing.locality?.name, props.listing.county?.name].filter(Boolean).join(', '));
</script>

<template>
    <div class="group relative flex flex-col rounded-2xl border border-line bg-white shadow-sm shadow-ink/5 overflow-hidden transition-all duration-200" :class="preview ? '' : 'hover:shadow-glow-brand hover:-translate-y-1'">
        <div class="relative aspect-[4/3] bg-gradient-to-br from-brand-50 to-paper overflow-hidden">
            <img v-if="listing.cover_url" :src="listing.cover_url" :alt="listing.title" class="w-full h-full object-cover transition-transform duration-300" :class="!preview && 'group-hover:scale-105'" />
            <div v-else class="w-full h-full flex items-center justify-center">
                <component :is="icon" class="w-10 h-10 text-brand-500/30" />
            </div>

            <span class="absolute top-3 left-3 text-xs font-semibold rounded-full px-2.5 py-1 shadow-sm backdrop-blur" :class="status.class">
                {{ status.label }}
            </span>

            <span v-if="listing.photos_count" class="absolute top-3 right-3 inline-flex items-center gap-1 text-xs font-medium rounded-full px-2 py-1 bg-ink/60 text-white backdrop-blur">
                <PhotoIcon class="w-3.5 h-3.5" /> {{ listing.photos_count }}
            </span>

            <div v-if="!preview" class="absolute inset-x-0 bottom-0 flex items-center justify-end gap-1.5 p-2.5 opacity-0 translate-y-1 transition-all duration-200 group-hover:opacity-100 group-hover:translate-y-0">
                <Link :href="route('provider.listings.edit', listing.id)" class="p-2 rounded-lg bg-white/95 text-ink-soft shadow-sm transition-colors duration-150 hover:text-brand-600" title="Editează">
                    <PencilSquareIcon class="w-4 h-4" />
                </Link>
                <button @click="$emit('delete', listing)" class="p-2 rounded-lg bg-white/95 text-ink-soft shadow-sm transition-colors duration-150 hover:text-rose-600" title="Șterge">
                    <TrashIcon class="w-4 h-4" />
                </button>
            </div>
        </div>

        <div class="flex flex-col flex-1 p-4">
            <div class="flex items-center gap-1.5 text-xs font-medium text-brand-500 mb-1.5">
                <component :is="icon" class="w-3.5 h-3.5" />
                {{ listing.category }}
            </div>

            <Link v-if="!preview" :href="route('provider.listings.edit', listing.id)" class="text-sm font-semibold text-ink leading-snug line-clamp-2 transition-colors duration-150 hover:text-brand-600">
                {{ listing.title || 'Titlul anunțului tău' }}
            </Link>
            <p v-else class="text-sm font-semibold text-ink leading-snug line-clamp-2">
                {{ listing.title || 'Titlul anunțului tău' }}
            </p>

            <p v-if="location" class="mt-1.5 flex items-center gap-1 text-xs text-ink-soft">
                <MapPinIcon class="w-3.5 h-3.5 flex-none" /> {{ location }}
            </p>

            <div class="mt-auto pt-3 flex items-center justify-between">
                <span class="text-sm font-semibold text-ink">{{ price }}</span>
                <span v-if="!preview" class="inline-flex items-center gap-1 text-xs text-ink-soft tabular-nums">
                    <EyeIcon class="w-3.5 h-3.5" /> {{ listing.views_count }}
                </span>
            </div>
        </div>
    </div>
</template>
