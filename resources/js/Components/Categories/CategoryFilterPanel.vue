<script setup>
import { computed } from 'vue';
import { categoryIcon } from '@/Composables/useCategoryIcon';

const props = defineProps({
    // Optional: when provided, the panel also renders the service-type and event-type filters.
    categories: { type: Array, default: () => [] },
    eventTypes: { type: Array, default: () => [] },
    counties: { type: Array, default: () => [] },
    facets: {
        type: Object,
        default: () => ({ rating: { 4: 0, '4.5': 0 }, featured: 0, price: { min: null, max: null } }),
    },
});

const selectedCategories = defineModel('selectedCategories', { type: Array, default: () => [] });
const selectedEventTypes = defineModel('selectedEventTypes', { type: Array, default: () => [] });
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

const toggleIn = (list, value) => (list.includes(value) ? list.filter((v) => v !== value) : [...list, value]);

const toggleCategory = (slug) => {
    selectedCategories.value = toggleIn(selectedCategories.value, slug);
    emit('apply');
};

const toggleEventType = (value) => {
    selectedEventTypes.value = toggleIn(selectedEventTypes.value, value);
    emit('apply');
};

const categoryCount = (id) => props.facets.categories?.[id] ?? 0;

const setRating = (value) => {
    rating.value = value;
    emit('apply');
};

const toggleFeatured = () => {
    featuredOnly.value = !featuredOnly.value;
    emit('apply');
};

const ratingCount = (value) => props.facets.rating?.[value] ?? 0;

// Only offer options that have results; keep a selected one visible so it can be cleared.
const visibleCategories = computed(() => props.categories.filter((cat) => categoryCount(cat.id) > 0 || selectedCategories.value.includes(cat.slug)));
const eventTypeCount = (value) => props.facets.event_types?.[value] ?? 0;
const visibleEventTypes = computed(() => props.eventTypes.filter((type) => eventTypeCount(type.value) > 0 || selectedEventTypes.value.includes(type.value)));
const visibleCounties = computed(() => props.counties.filter((county) => county.count > 0 || selectedCounties.value.includes(county.id)));
</script>

<template>
    <div class="flex flex-col gap-6">
        <div v-if="visibleCategories.length" class="flex flex-col gap-1 border-b border-ivt-line pb-5">
            <h4 class="mb-2 text-[12.5px] font-bold uppercase tracking-[0.07em] text-ivt-ink-soft">Tip serviciu</h4>
            <div class="-mx-1 flex max-h-56 flex-col overflow-y-auto pl-1 pr-3 [scrollbar-width:thin]">
                <label
                    v-for="cat in visibleCategories"
                    :key="cat.slug"
                    class="flex cursor-pointer items-center gap-2.5 py-1.5 text-sm text-ivt-ink"
                >
                    <input
                        type="checkbox"
                        :checked="selectedCategories.includes(cat.slug)"
                        @change="toggleCategory(cat.slug)"
                        class="h-[15px] w-[15px] flex-none cursor-pointer rounded border-ivt-line text-primary focus:ring-primary/40"
                    />
                    <component :is="categoryIcon(cat.slug)" class="h-4 w-4 flex-none text-primary" />
                    {{ cat.name }}
                    <span class="ml-auto text-xs text-ivt-ink-faint">{{ categoryCount(cat.id) }}</span>
                </label>
            </div>
        </div>

        <div v-if="visibleEventTypes.length" class="flex flex-col gap-1 border-b border-ivt-line pb-5">
            <h4 class="mb-2 text-[12.5px] font-bold uppercase tracking-[0.07em] text-ivt-ink-soft">Tip eveniment</h4>
            <div class="-mx-1 flex max-h-56 flex-col overflow-y-auto pl-1 pr-3 [scrollbar-width:thin]">
                <label
                    v-for="type in visibleEventTypes"
                    :key="type.value"
                    class="flex cursor-pointer items-center gap-2.5 py-1.5 text-sm text-ivt-ink"
                >
                    <input
                        type="checkbox"
                        :checked="selectedEventTypes.includes(type.value)"
                        @change="toggleEventType(type.value)"
                        class="h-[15px] w-[15px] flex-none cursor-pointer rounded border-ivt-line text-primary focus:ring-primary/40"
                    />
                    {{ type.label }}
                    <span class="ml-auto text-xs text-ivt-ink-faint">{{ eventTypeCount(type.value) }}</span>
                </label>
            </div>
        </div>

        <div v-if="visibleCounties.length" class="flex flex-col gap-1 border-b border-ivt-line pb-5">
            <h4 class="mb-3 text-[12.5px] font-bold uppercase tracking-[0.07em] text-ivt-ink-soft">Locație</h4>
            <div class="-mx-1 flex max-h-56 flex-col overflow-y-auto pl-1 pr-3 [scrollbar-width:thin]">
                <label
                    v-for="county in visibleCounties"
                    :key="county.id"
                    class="flex cursor-pointer items-center gap-2.5 py-1.5 text-sm text-ivt-ink"
                >
                    <input
                        type="checkbox"
                        :checked="selectedCounties.includes(county.id)"
                        @change="toggleCounty(county.id)"
                        class="h-[15px] w-[15px] flex-none cursor-pointer rounded border-ivt-line text-primary focus:ring-primary/40"
                    />
                    {{ county.name }}
                    <span class="ml-auto text-xs text-ivt-ink-faint">{{ county.count }}</span>
                </label>
            </div>
        </div>

        <div class="flex flex-col gap-2.5 border-b border-ivt-line pb-5">
            <h4 class="mb-1 text-[12.5px] font-bold uppercase tracking-[0.07em] text-ivt-ink-soft">Buget (lei)</h4>
            <div class="flex items-center gap-2">
                <input
                    v-model="priceMin"
                    type="number"
                    min="0"
                    placeholder="Min"
                    @change="emit('apply')"
                    class="w-full rounded-[10px] border-ivt-line px-2.5 py-2 text-[13.5px] text-ivt-ink focus:border-primary focus:ring-primary/20"
                />
                <span class="text-[13px] text-ivt-ink-faint">–</span>
                <input
                    v-model="priceMax"
                    type="number"
                    min="0"
                    placeholder="Max"
                    @change="emit('apply')"
                    class="w-full rounded-[10px] border-ivt-line px-2.5 py-2 text-[13.5px] text-ivt-ink focus:border-primary focus:ring-primary/20"
                />
            </div>
            <p v-if="facets.price?.min !== null && facets.price?.max !== null" class="text-[11.5px] text-ivt-ink-faint">
                Interval disponibil: {{ facets.price.min }}–{{ facets.price.max }} lei
            </p>
        </div>

        <div class="flex flex-col gap-1 border-b border-ivt-line pb-5">
            <h4 class="mb-2 text-[12.5px] font-bold uppercase tracking-[0.07em] text-ivt-ink-soft">Rating minim</h4>
            <label class="flex cursor-pointer items-center gap-2.5 py-1.5 text-sm text-ivt-ink">
                <input type="radio" name="rating" :checked="Number(rating) === 0" @change="setRating(0)" class="h-[15px] w-[15px] flex-none cursor-pointer text-primary focus:ring-primary/40" />
                Toate
            </label>
            <label v-if="ratingCount(4) > 0 || Number(rating) === 4" class="flex cursor-pointer items-center gap-2.5 py-1.5 text-sm text-ivt-ink">
                <input
                    type="radio"
                    name="rating"
                    :checked="Number(rating) === 4"
                    @change="setRating(4)"
                    class="h-[15px] w-[15px] flex-none cursor-pointer text-primary focus:ring-primary/40"
                />
                <span class="text-[12px] tracking-widest text-primary">★★★★</span> 4.0+
                <span class="ml-auto text-xs text-ivt-ink-faint">{{ ratingCount(4) }}</span>
            </label>
            <label v-if="ratingCount('4.5') > 0 || Number(rating) === 4.5" class="flex cursor-pointer items-center gap-2.5 py-1.5 text-sm text-ivt-ink">
                <input
                    type="radio"
                    name="rating"
                    :checked="Number(rating) === 4.5"
                    @change="setRating(4.5)"
                    class="h-[15px] w-[15px] flex-none cursor-pointer text-primary focus:ring-primary/40"
                />
                <span class="text-[12px] tracking-widest text-primary">★★★★★</span> 4.5+
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
                <span class="absolute inset-0 rounded-full bg-ivt-paper-3 transition-colors duration-200 peer-checked:bg-primary" />
                <span class="absolute left-[3px] h-4 w-4 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-[18px]" />
            </label>
        </div>
    </div>
</template>
