<script setup>
import DetailsSection from './Sections/DetailsSection.vue';
import PriceSection from './Sections/PriceSection.vue';
import LocationSection from './Sections/LocationSection.vue';

defineProps({
    form: Object,
    categories: Array,
    counties: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="space-y-8">
        <DetailsSection :form="form" :categories="categories" />
        <PriceSection :form="form" bordered />
        <LocationSection :form="form" :counties="counties" bordered />
    </div>
</template>
