<script setup>
import DetailsSection from './Sections/DetailsSection.vue';
import PriceSection from './Sections/PriceSection.vue';
import LocationSection from './Sections/LocationSection.vue';

defineProps({
    form: Object,
    categories: Array,
    eventTypes: { type: Array, default: () => [] },
    counties: { type: Array, default: () => [] },
});

const steps = [
    { key: 'details', title: 'Detalii principale', hint: 'Cum se numește anunțul, în ce categorie intră și ce oferi clienților.' },
    { key: 'price', title: 'Preț', hint: 'Alege cum îți afișezi prețul și ce include el.' },
    { key: 'location', title: 'Locație', hint: 'Unde ești disponibil. Clienții caută după județ și localitate.' },
];
</script>

<template>
    <div class="divide-y divide-ivt-line rounded-2xl border border-ivt-line bg-white shadow-sm shadow-ivt-ink/5">
        <section v-for="(step, index) in steps" :key="step.key" class="grid gap-x-10 gap-y-5 p-5 sm:p-7 lg:grid-cols-[13rem_1fr]">
            <header class="lg:pt-1">
                <span class="mb-3 flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-semibold text-white">{{ index + 1 }}</span>
                <h3 class="text-base font-semibold text-ivt-ink">{{ step.title }}</h3>
                <p class="mt-1 text-sm leading-relaxed text-ivt-ink-soft">{{ step.hint }}</p>
            </header>

            <div class="min-w-0">
                <DetailsSection v-if="step.key === 'details'" :form="form" :categories="categories" :event-types="eventTypes" :heading="false" />
                <PriceSection v-else-if="step.key === 'price'" :form="form" :heading="false" />
                <LocationSection v-else :form="form" :counties="counties" :heading="false" />
            </div>
        </section>
    </div>
</template>
