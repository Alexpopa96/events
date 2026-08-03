<script setup>
import { MapPinIcon } from '@heroicons/vue/24/outline';
import CountyLocalitySelect from '@/Components/CountyLocalitySelect.vue';

defineProps({
    form: Object,
    counties: { type: Array, default: () => [] },
    heading: { type: Boolean, default: true },
    bordered: { type: Boolean, default: false },
});

const sectionHeadingClass = 'flex items-center gap-2 text-sm font-semibold text-ink mb-4';
const iconWrapClass = 'w-7 h-7 rounded-lg bg-brand-50 text-brand-500 flex items-center justify-center flex-none';
</script>

<template>
    <section :class="bordered && 'pt-6 border-t border-line'">
        <h3 v-if="heading" :class="sectionHeadingClass">
            <span :class="iconWrapClass"><MapPinIcon class="w-4 h-4" /></span>
            Locație
        </h3>

        <CountyLocalitySelect
            v-model:county-id="form.county_id"
            v-model:locality-id="form.locality_id"
            :counties="counties"
            :county-error="form.errors.county_id"
            :locality-error="form.errors.locality_id"
        />
    </section>
</template>
