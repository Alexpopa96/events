<script setup>
import { computed } from 'vue';
import { CurrencyDollarIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    form: Object,
    heading: { type: Boolean, default: true },
    bordered: { type: Boolean, default: false },
});

const priceTypes = [
    { value: 'on_request', label: 'La cerere' },
    { value: 'fixed', label: 'Preț fix' },
    { value: 'starting_from', label: 'Începând de la' },
    { value: 'per_hour', label: 'Pe oră' },
];

const showPriceFrom = computed(() => props.form.price_type !== 'on_request');
const showPriceTo = computed(() => props.form.price_type === 'fixed' || props.form.price_type === 'starting_from');
const priceFromLabel = computed(() => (props.form.price_type === 'starting_from' ? 'Preț de la' : 'Preț'));

const inputClass = 'w-full rounded-2xl border border-line bg-white px-4 py-2.5 text-sm text-ink placeholder:text-ink-soft/60 shadow-sm shadow-ink/5 transition-all duration-150 hover:border-brand-300/70 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:shadow-md focus:shadow-brand-500/10';
const errorClass = 'border-red-400';

const sectionHeadingClass = 'flex items-center gap-2 text-sm font-semibold text-ink mb-4';
const iconWrapClass = 'w-7 h-7 rounded-lg bg-brand-50 text-brand-500 flex items-center justify-center flex-none';
</script>

<template>
    <section :class="bordered && 'pt-6 border-t border-line'">
        <h3 v-if="heading" :class="sectionHeadingClass">
            <span :class="iconWrapClass"><CurrencyDollarIcon class="w-4 h-4" /></span>
            Preț
        </h3>

        <div class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-ink mb-1.5">Tip preț</label>
                <div class="inline-flex flex-wrap gap-1 p-1 rounded-2xl bg-paper border border-line">
                    <button
                        v-for="type in priceTypes"
                        :key="type.value"
                        type="button"
                        @click="form.price_type = type.value"
                        class="px-3.5 py-2 rounded-xl text-sm font-medium transition-all duration-150"
                        :class="form.price_type === type.value
                            ? 'bg-white text-brand-600 shadow-sm shadow-ink/10'
                            : 'text-ink-soft hover:text-ink'"
                    >
                        {{ type.label }}
                    </button>
                </div>
            </div>

            <div v-if="showPriceFrom" class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink mb-1.5">{{ priceFromLabel }}</label>
                    <div class="relative">
                        <input v-model="form.price_from" type="number" min="0" :class="[inputClass, 'pr-14']" />
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-medium text-ink-soft/60 pointer-events-none">RON</span>
                    </div>
                </div>
                <div v-if="showPriceTo">
                    <label class="block text-sm font-medium text-ink mb-1.5">Preț până la</label>
                    <div class="relative">
                        <input v-model="form.price_to" type="number" min="0" :class="[inputClass, 'pr-14', form.errors.price_to && errorClass]" />
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-medium text-ink-soft/60 pointer-events-none">RON</span>
                    </div>
                    <p v-if="form.errors.price_to" class="mt-1.5 text-sm text-red-500">{{ form.errors.price_to }}</p>
                </div>
            </div>

            <div v-else class="flex items-start gap-2.5 rounded-2xl border border-brand-100 bg-brand-50/60 px-4 py-3">
                <InformationCircleIcon class="w-4 h-4 text-brand-500 flex-none mt-0.5" />
                <p class="text-sm text-brand-700">
                    Anunțul va afișa „Preț la cerere”, iar clienții te vor putea contacta direct pentru a primi o ofertă personalizată.
                </p>
            </div>
        </div>
    </section>
</template>
