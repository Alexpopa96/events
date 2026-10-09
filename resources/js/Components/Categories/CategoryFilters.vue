<script setup>
import {
    MapPinIcon,
    TagIcon,
    BanknotesIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/24/solid';

defineProps({
    filters: Object,
    eventTypes: Array,
    priceRanges: Array,
    ratingOptions: Array,
});

const emit = defineEmits(['toggle-event-type']);

const inputClasses = 'w-full rounded-xl border-ivt-line text-sm text-ivt-ink focus:border-primary focus:ring-primary/20';
</script>

<template>
    <div class="divide-y divide-ivt-line px-5">
        <div class="py-5">
            <label class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-ivt-ink-soft">
                <MapPinIcon class="h-3.5 w-3.5" />
                Locație
            </label>
            <div class="relative">
                <MapPinIcon class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ivt-ink-soft/40" />
                <input v-model="filters.location" type="text" placeholder="Orice locație" :class="[inputClasses, 'rounded-full pl-9']" />
            </div>
        </div>

        <div class="py-5">
            <p class="mb-3 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-ivt-ink-soft">
                <TagIcon class="h-3.5 w-3.5" />
                Tip eveniment
            </p>
            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="type in eventTypes"
                    :key="type"
                    type="button"
                    @click="emit('toggle-event-type', type)"
                    class="rounded-full border px-3 py-1.5 text-xs font-medium transition-all duration-150"
                    :class="filters.eventTypes.includes(type)
                        ? 'border-transparent bg-primary text-white shadow-sm shadow-primary/25'
                        : 'border-ivt-line text-ivt-ink-soft hover:border-primary/40 hover:text-primary-bright'"
                >
                    {{ type }}
                </button>
            </div>
        </div>

        <div class="py-5">
            <p class="mb-3 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-ivt-ink-soft">
                <BanknotesIcon class="h-3.5 w-3.5" />
                Preț
            </p>
            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="range in priceRanges"
                    :key="range"
                    type="button"
                    @click="filters.priceRange = range"
                    class="rounded-full border px-3 py-1.5 text-xs font-medium transition-all duration-150"
                    :class="filters.priceRange === range
                        ? 'border-transparent bg-primary text-white shadow-sm shadow-primary/25'
                        : 'border-ivt-line text-ivt-ink-soft hover:border-primary/40 hover:text-primary-bright'"
                >
                    {{ range }}
                </button>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <input v-model="filters.minPrice" type="number" placeholder="Min" class="w-full rounded-full border-ivt-line text-xs text-ivt-ink focus:border-primary focus:ring-primary/20" />
                <span class="flex-none text-ivt-ink-soft/50">–</span>
                <input v-model="filters.maxPrice" type="number" placeholder="Max" class="w-full rounded-full border-ivt-line text-xs text-ivt-ink focus:border-primary focus:ring-primary/20" />
                <span class="flex-none text-xs text-ivt-ink-soft">RON</span>
            </div>
        </div>

        <div class="py-5">
            <p class="mb-3 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-ivt-ink-soft">
                <StarIcon class="h-3.5 w-3.5" />
                Rating furnizor
            </p>
            <div class="space-y-1">
                <button
                    v-for="option in ratingOptions"
                    :key="option.value"
                    type="button"
                    @click="filters.rating = option.value"
                    class="flex w-full items-center gap-2 rounded-xl px-2.5 py-2 text-sm transition-colors duration-150"
                    :class="filters.rating === option.value ? 'bg-primary/10 font-medium text-primary' : 'text-ivt-ink-soft hover:bg-ivt-paper'"
                >
                    <span v-if="option.stars" class="flex items-center gap-0.5">
                        <StarIcon v-for="n in option.stars" :key="n" class="h-3.5 w-3.5 text-primary" />
                    </span>
                    {{ option.label }}
                </button>
            </div>
        </div>
    </div>
</template>
