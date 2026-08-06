<script setup>
const props = defineProps({
    counties: { type: Array, default: () => [] },
    facets: {
        type: Object,
        default: () => ({ rating: { 4: 0, '4.5': 0 }, featured: 0, price: { min: null, max: null } }),
    },
});

const selectedCounties = defineModel('selectedCounties', { type: Array, default: () => [] });
const priceMin = defineModel('priceMin', { type: [String, Number], default: '' });
const priceMax = defineModel('priceMax', { type: [String, Number], default: '' });
const rating = defineModel('rating', { type: [String, Number], default: 0 });
const featuredOnly = defineModel('featuredOnly', { type: Boolean, default: false });

const emit = defineEmits(['apply']);

const toggleCounty = (id) => {
    selectedCounties.value = selectedCounties.value.includes(id)
        ? selectedCounties.value.filter((c) => c !== id)
        : [...selectedCounties.value, id];
    emit('apply');
};

const setRating = (value) => {
    rating.value = value;
    emit('apply');
};

const toggleFeatured = () => {
    featuredOnly.value = !featuredOnly.value;
    emit('apply');
};

const ratingCount = (value) => props.facets.rating?.[value] ?? 0;
</script>

<template>
    <div class="flex flex-col gap-6">
        <div v-if="counties.length" class="flex flex-col gap-1 border-b border-ivt-line pb-5">
            <h4 class="mb-3 text-[12.5px] font-bold uppercase tracking-[0.07em] text-ivt-ink-soft">Locație</h4>
            <label
                v-for="county in counties"
                :key="county.id"
                class="flex items-center gap-2.5 py-1.5 text-sm text-ivt-ink"
                :class="county.count === 0 && !selectedCounties.includes(county.id) ? 'cursor-not-allowed opacity-40' : 'cursor-pointer'"
            >
                <input
                    type="checkbox"
                    :checked="selectedCounties.includes(county.id)"
                    :disabled="county.count === 0 && !selectedCounties.includes(county.id)"
                    @change="toggleCounty(county.id)"
                    class="h-[15px] w-[15px] cursor-pointer rounded border-ivt-line text-ivt-wine focus:ring-ivt-wine/40 disabled:cursor-not-allowed"
                />
                {{ county.name }}
                <span class="ml-auto text-xs text-ivt-ink-faint">{{ county.count }}</span>
            </label>
        </div>

        <div class="flex flex-col gap-2.5 border-b border-ivt-line pb-5">
            <h4 class="mb-1 text-[12.5px] font-bold uppercase tracking-[0.07em] text-ivt-ink-soft">Buget (lei)</h4>
            <div class="flex items-center gap-2">
                <input
                    v-model="priceMin"
                    type="number"
                    min="0"
                    placeholder="Min"
                    @keyup.enter="emit('apply')"
                    class="w-full rounded-[10px] border-ivt-line px-2.5 py-2 text-[13.5px] text-ivt-ink focus:border-ivt-gold focus:ring-ivt-gold/30"
                />
                <span class="text-[13px] text-ivt-ink-faint">–</span>
                <input
                    v-model="priceMax"
                    type="number"
                    min="0"
                    placeholder="Max"
                    @keyup.enter="emit('apply')"
                    class="w-full rounded-[10px] border-ivt-line px-2.5 py-2 text-[13.5px] text-ivt-ink focus:border-ivt-gold focus:ring-ivt-gold/30"
                />
            </div>
            <p v-if="facets.price?.min !== null && facets.price?.max !== null" class="text-[11.5px] text-ivt-ink-faint">
                Interval disponibil: {{ facets.price.min }}–{{ facets.price.max }} lei
            </p>
        </div>

        <div class="flex flex-col gap-1 border-b border-ivt-line pb-5">
            <h4 class="mb-2 text-[12.5px] font-bold uppercase tracking-[0.07em] text-ivt-ink-soft">Rating minim</h4>
            <label class="flex cursor-pointer items-center gap-2.5 py-1.5 text-sm text-ivt-ink">
                <input type="radio" name="rating" :checked="Number(rating) === 0" @change="setRating(0)" class="h-[15px] w-[15px] cursor-pointer text-ivt-wine focus:ring-ivt-wine/40" />
                Toate
            </label>
            <label
                class="flex items-center gap-2.5 py-1.5 text-sm text-ivt-ink"
                :class="ratingCount(4) === 0 && Number(rating) !== 4 ? 'cursor-not-allowed opacity-40' : 'cursor-pointer'"
            >
                <input
                    type="radio"
                    name="rating"
                    :checked="Number(rating) === 4"
                    :disabled="ratingCount(4) === 0 && Number(rating) !== 4"
                    @change="setRating(4)"
                    class="h-[15px] w-[15px] cursor-pointer text-ivt-wine focus:ring-ivt-wine/40 disabled:cursor-not-allowed"
                />
                <span class="text-[12px] tracking-widest text-ivt-gold">★★★★</span> 4.0+
                <span class="ml-auto text-xs text-ivt-ink-faint">{{ ratingCount(4) }}</span>
            </label>
            <label
                class="flex items-center gap-2.5 py-1.5 text-sm text-ivt-ink"
                :class="ratingCount('4.5') === 0 && Number(rating) !== 4.5 ? 'cursor-not-allowed opacity-40' : 'cursor-pointer'"
            >
                <input
                    type="radio"
                    name="rating"
                    :checked="Number(rating) === 4.5"
                    :disabled="ratingCount('4.5') === 0 && Number(rating) !== 4.5"
                    @change="setRating(4.5)"
                    class="h-[15px] w-[15px] cursor-pointer text-ivt-wine focus:ring-ivt-wine/40 disabled:cursor-not-allowed"
                />
                <span class="text-[12px] tracking-widest text-ivt-gold">★★★★★</span> 4.5+
                <span class="ml-auto text-xs text-ivt-ink-faint">{{ ratingCount('4.5') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-medium text-ivt-ink">Doar Premium</div>
                <div class="mt-0.5 text-[11.5px] text-ivt-ink-faint">Furnizori cu vizibilitate extinsă · {{ facets.featured ?? 0 }}</div>
            </div>
            <label
                class="relative inline-flex h-[22px] w-10 flex-none items-center"
                :class="facets.featured === 0 && !featuredOnly ? 'cursor-not-allowed opacity-40' : 'cursor-pointer'"
            >
                <input
                    type="checkbox"
                    :checked="featuredOnly"
                    :disabled="facets.featured === 0 && !featuredOnly"
                    @change="toggleFeatured"
                    class="peer sr-only"
                />
                <span class="absolute inset-0 rounded-full bg-ivt-paper-3 transition-colors duration-200 peer-checked:bg-ivt-ink" />
                <span class="absolute left-[3px] h-4 w-4 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-[18px]" />
            </label>
        </div>
    </div>
</template>
