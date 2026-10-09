<script setup>
import { computed, ref } from 'vue';
import { CurrencyDollarIcon, InformationCircleIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { fieldClass, fieldErrorClass } from '@/Composables/useFieldClasses';
import Field from '@/Components/Provider/Field.vue';

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

// Benefits are typed into one box and become removable chips (Enter or comma adds).
const draft = ref('');

const addBenefit = () => {
    const value = draft.value.trim().replace(/,+$/, '').trim();
    draft.value = '';

    if (value && !props.form.benefits.includes(value)) props.form.benefits.push(value.slice(0, 120));
};

const onBenefitKey = (event) => {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        addBenefit();
    } else if (event.key === 'Backspace' && !draft.value && props.form.benefits.length) {
        props.form.benefits.pop();
    }
};

const removeBenefit = (index) => props.form.benefits.splice(index, 1);

const sectionHeadingClass = 'flex items-center gap-2 text-sm font-semibold text-ivt-ink mb-5';
const iconWrapClass = 'w-7 h-7 rounded-full bg-ivt-paper-2 text-primary flex items-center justify-center flex-none';
</script>

<template>
    <section :class="bordered && 'pt-6 border-t border-ivt-line'">
        <h3 v-if="heading" :class="sectionHeadingClass">
            <span :class="iconWrapClass"><CurrencyDollarIcon class="w-4 h-4" /></span>
            Preț
        </h3>

        <div class="space-y-6">
            <Field label="Tip preț">
                <div class="grid grid-cols-2 gap-1 rounded-2xl bg-ivt-paper-2 p-1.5 sm:inline-flex sm:rounded-full" role="radiogroup" aria-label="Tip preț">
                    <button
                        v-for="type in priceTypes"
                        :key="type.value"
                        type="button"
                        role="radio"
                        :aria-checked="form.price_type === type.value"
                        @click="form.price_type = type.value"
                        class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-medium transition-all duration-150"
                        :class="form.price_type === type.value
                            ? 'bg-white text-primary shadow-sm shadow-ivt-ink/10'
                            : 'text-ivt-ink-soft hover:text-ivt-ink'"
                    >
                        {{ type.label }}
                    </button>
                </div>
            </Field>

            <div v-if="showPriceFrom" class="grid gap-4 sm:grid-cols-2">
                <Field :label="priceFromLabel" for="price-from" :error="form.errors.price_from">
                    <div class="relative">
                        <input id="price-from" v-model="form.price_from" type="number" min="0" inputmode="decimal" placeholder="0" :class="[fieldClass, 'pr-14', form.errors.price_from && fieldErrorClass]" />
                        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-xs font-semibold text-ivt-ink-soft/60">RON</span>
                    </div>
                </Field>
                <Field v-if="showPriceTo" label="Preț până la" for="price-to" :error="form.errors.price_to" optional>
                    <div class="relative">
                        <input id="price-to" v-model="form.price_to" type="number" min="0" inputmode="decimal" placeholder="0" :class="[fieldClass, 'pr-14', form.errors.price_to && fieldErrorClass]" />
                        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-xs font-semibold text-ivt-ink-soft/60">RON</span>
                    </div>
                </Field>
            </div>

            <div v-else class="flex items-start gap-2.5 rounded-xl bg-ivt-paper-2/70 px-4 py-3">
                <InformationCircleIcon class="mt-0.5 h-4 w-4 flex-none text-primary" />
                <p class="text-sm text-ivt-ink-soft">
                    Anunțul va afișa „Preț la cerere”, iar clienții te vor putea contacta direct pentru a primi o ofertă personalizată.
                </p>
            </div>

            <Field label="Ce include prețul" for="benefit-draft" optional hint="Scrie un beneficiu și apasă Enter — ex. „Album foto printat”, „2 fotografi”, „Editare inclusă”.">
                <div
                    class="flex min-h-[3rem] flex-wrap items-center gap-1.5 rounded-xl border-2 border-transparent bg-ivt-paper-2 px-2.5 py-2 transition-[background-color,border-color,box-shadow] duration-150 hover:bg-ivt-paper-3/70 focus-within:border-primary focus-within:bg-white focus-within:ring-4 focus-within:ring-primary/10"
                    @click="$refs.benefitInput?.focus()"
                >
                    <span
                        v-for="(benefit, index) in form.benefits"
                        :key="`${benefit}-${index}`"
                        class="inline-flex max-w-full items-center gap-1 rounded-full bg-white py-1 pl-3 pr-1.5 text-sm text-ivt-ink shadow-sm shadow-ivt-ink/10"
                    >
                        <span class="truncate">{{ benefit }}</span>
                        <button
                            type="button"
                            @click.stop="removeBenefit(index)"
                            class="flex h-5 w-5 flex-none items-center justify-center rounded-full text-ivt-ink-soft transition-colors duration-150 hover:bg-danger-100 hover:text-danger-600"
                            :aria-label="`Elimină „${benefit}”`"
                        >
                            <XMarkIcon class="h-3.5 w-3.5" />
                        </button>
                    </span>
                    <input
                        id="benefit-draft"
                        ref="benefitInput"
                        v-model="draft"
                        type="text"
                        maxlength="120"
                        :placeholder="form.benefits.length ? 'Adaugă încă unul…' : 'Ex: Album foto printat'"
                        @keydown="onBenefitKey"
                        @blur="addBenefit"
                        class="min-w-[9rem] flex-1 border-0 bg-transparent px-1.5 py-0.5 text-sm text-ivt-ink placeholder:text-ivt-ink-soft/50 focus:outline-none focus:ring-0"
                    />
                    <button
                        v-if="draft.trim()"
                        type="button"
                        @mousedown.prevent="addBenefit"
                        class="inline-flex items-center gap-1 rounded-full bg-primary px-2.5 py-1 text-xs font-semibold text-white"
                    >
                        <PlusIcon class="h-3.5 w-3.5" /> Adaugă
                    </button>
                </div>
            </Field>
        </div>
    </section>
</template>
