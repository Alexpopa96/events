<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import ListingFields from './Partials/ListingFields.vue';
import ListingCard from '@/Components/Provider/ListingCard.vue';
import { SparklesIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    categories: Array,
    counties: Array,
    quota: Object,
});

const form = useForm({
    title: '',
    category_id: '',
    description: '',
    price_type: 'on_request',
    price_from: '',
    price_to: '',
    benefits: [],
    county_id: null,
    locality_id: null,
});

const quotaReached = computed(() => props.quota.max !== null && props.quota.used >= props.quota.max);

const submit = () => form.post(route('provider.listings.store'));

const previewListing = computed(() => ({
    title: form.title,
    category: props.categories.find((c) => c.id === form.category_id)?.name ?? 'Categorie',
    category_slug: props.categories.find((c) => c.id === form.category_id)?.slug ?? null,
    price_type: form.price_type,
    price_from: form.price_from,
    price_to: form.price_to,
    county: props.counties.find((c) => c.id === form.county_id) ?? null,
    locality: null,
    status: 'draft',
    cover_url: null,
    photos_count: 0,
}));
</script>

<template>
    <ProviderLayout title="Anunț nou">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="font-serif text-2xl text-ink">Creează un anunț nou</h2>
                <p class="text-sm text-ink-soft mt-1">Completează detaliile de bază acum — poți adăuga fotografii și rafina descrierea imediat după salvare.</p>
            </div>
            <div v-if="quota.max !== null" class="flex-none text-right">
                <p class="text-xs text-ink-soft">Plan {{ quota.plan_name }}</p>
                <p class="text-sm font-semibold" :class="quotaReached ? 'text-rose-600' : 'text-ink'">{{ quota.used }} / {{ quota.max }} anunțuri</p>
            </div>
        </div>

        <div v-if="quotaReached" class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 mb-6">
            <ExclamationTriangleIcon class="w-5 h-5 text-amber-500 flex-none mt-0.5" />
            <div>
                <p class="text-sm font-semibold text-amber-700">Ai atins limita planului {{ quota.plan_name }}</p>
                <p class="text-sm text-amber-700/80 mt-0.5">Arhivează un anunț existent sau treci la un plan superior pentru a mai publica anunțuri noi.</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-[1fr_320px] gap-6 items-start">
            <div class="bg-white border border-line rounded-2xl p-6 shadow-sm shadow-ink/5">
                <form @submit.prevent="submit">
                    <ListingFields :form="form" :categories="categories" :counties="counties" />

                    <div class="flex items-center gap-3 mt-8 pt-6 border-t border-line">
                        <button
                            type="submit"
                            :disabled="form.processing || quotaReached"
                            class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-500/25 transition-all duration-200 hover:bg-brand-600 hover:shadow-md hover:shadow-brand-500/30 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60 disabled:pointer-events-none"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            Salvează ca ciornă
                        </button>
                        <Link :href="route('provider.listings.index')" class="text-sm font-medium text-ink-soft transition-colors duration-150 hover:text-ink">
                            Anulează
                        </Link>
                    </div>
                </form>
            </div>

            <div class="lg:sticky lg:top-6 space-y-3">
                <p class="flex items-center gap-1.5 text-xs font-semibold text-ink-soft uppercase tracking-wide px-1">
                    <SparklesIcon class="w-3.5 h-3.5" /> Previzualizare live
                </p>
                <ListingCard :listing="previewListing" :preview="true" />
                <p class="text-xs text-ink-soft px-1">Așa va arăta anunțul tău în listări. Poți adăuga fotografii după ce salvezi.</p>
            </div>
        </div>
    </ProviderLayout>
</template>
