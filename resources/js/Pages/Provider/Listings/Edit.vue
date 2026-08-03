<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import DetailsSection from './Partials/Sections/DetailsSection.vue';
import PriceSection from './Partials/Sections/PriceSection.vue';
import LocationSection from './Partials/Sections/LocationSection.vue';
import MediaManager from './Partials/MediaManager.vue';
import ListingCard from '@/Components/Provider/ListingCard.vue';
import TrendChart from '@/Components/Provider/TrendChart.vue';
import {
    CalendarIcon, EyeIcon, ExclamationTriangleIcon, PhotoIcon, SparklesIcon,
    PhoneIcon, ChatBubbleLeftRightIcon, ChartBarIcon, TagIcon, CurrencyDollarIcon,
    MapPinIcon, ArrowLeftIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    listing: Object,
    categories: Array,
    counties: Array,
    mediaLimits: Object,
    stats: Object,
    trend: Array,
});

const form = useForm({
    title: props.listing.title,
    category_id: props.listing.category_id,
    description: props.listing.description ?? '',
    price_type: props.listing.price_type,
    price_from: props.listing.price_from ?? '',
    price_to: props.listing.price_to ?? '',
    county_id: props.listing.county_id,
    locality_id: props.listing.locality_id,
    action: 'save',
});

const submitWithAction = (action) => {
    form.transform((data) => ({ ...data, action })).put(route('provider.listings.update', props.listing.id));
};

const statusMeta = {
    draft: { label: 'Ciornă', class: 'bg-line/70 text-ink-soft' },
    pending_review: { label: 'În verificare', class: 'bg-gold-400/20 text-gold-500' },
    published: { label: 'Publicat', class: 'bg-emerald-100 text-emerald-700' },
    rejected: { label: 'Respins', class: 'bg-rose-100 text-rose-700' },
    archived: { label: 'Arhivat', class: 'bg-line/70 text-ink-soft' },
};

const status = computed(() => statusMeta[props.listing.status] ?? { label: props.listing.status, class: 'bg-line/70 text-ink-soft' });

const previewListing = computed(() => ({
    title: form.title,
    category: props.categories.find((c) => c.id === form.category_id)?.name ?? 'Categorie',
    category_slug: props.categories.find((c) => c.id === form.category_id)?.slug ?? null,
    price_type: form.price_type,
    price_from: form.price_from,
    price_to: form.price_to,
    county: props.counties.find((c) => c.id === form.county_id) ?? props.listing.county ?? null,
    locality: props.listing.locality_id === form.locality_id ? props.listing.locality : null,
    status: props.listing.status,
    cover_url: props.listing.media.find((m) => m.is_cover)?.url ?? props.listing.media[0]?.url ?? null,
    photos_count: props.listing.media.filter((m) => m.type === 'photo').length,
}));

const statCards = computed(() => [
    { label: 'Vizualizări', value: props.stats.views, icon: EyeIcon },
    { label: 'Click-uri telefon', value: props.stats.phone_clicks, icon: PhoneIcon },
    { label: 'Click-uri WhatsApp', value: props.stats.whatsapp_clicks, icon: ChatBubbleLeftRightIcon },
    { label: 'Rată de conversie', value: `${props.stats.conversion_rate}%`, icon: ChartBarIcon },
]);

// Tabs
const activeTab = ref('details');
const tabs = computed(() => [
    { key: 'details', label: 'Detalii', icon: TagIcon, hasError: !!(form.errors.title || form.errors.category_id) },
    { key: 'price', label: 'Preț', icon: CurrencyDollarIcon, hasError: !!(form.errors.price_from || form.errors.price_to) },
    { key: 'location', label: 'Locație', icon: MapPinIcon, hasError: !!(form.errors.county_id || form.errors.locality_id) },
    { key: 'media', label: 'Media', icon: PhotoIcon, hasError: false },
]);

watch(() => form.errors, (errors) => {
    if (!Object.keys(errors).length) return;
    const firstErrorTab = tabs.value.find((tab) => tab.hasError);
    if (firstErrorTab) activeTab.value = firstErrorTab.key;
});
</script>

<template>
    <ProviderLayout title="Editează anunț" eyebrow="Anunțurile mele">
        <template #actions>
            <Link
                :href="route('provider.listings.index')"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-soft transition-colors duration-150 hover:text-ink"
            >
                <ArrowLeftIcon class="w-4 h-4" /> Înapoi la listă
            </Link>
        </template>

        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h2 class="font-serif text-2xl text-ink truncate max-w-md">{{ listing.title || 'Anunț fără titlu' }}</h2>
                    <span class="text-xs font-medium rounded-full px-2.5 py-1 flex-none" :class="status.class">{{ status.label }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-xs text-ink-soft">
                    <span class="flex items-center gap-1"><EyeIcon class="w-3.5 h-3.5" /> {{ listing.views_count }} vizualizări</span>
                    <span class="flex items-center gap-1"><CalendarIcon class="w-3.5 h-3.5" /> creat pe {{ listing.created_at }}</span>
                    <span v-if="listing.published_at" class="flex items-center gap-1"><SparklesIcon class="w-3.5 h-3.5" /> publicat pe {{ listing.published_at }}</span>
                </div>
            </div>
            <Link
                :href="route('provider.listings.index')"
                class="lg:hidden inline-flex items-center gap-1.5 text-sm font-medium text-ink-soft transition-colors duration-150 hover:text-ink"
            >
                <ArrowLeftIcon class="w-4 h-4" /> Înapoi la listă
            </Link>
        </div>

        <div v-if="listing.status === 'rejected' && listing.rejection_reason" class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 mb-6">
            <ExclamationTriangleIcon class="w-5 h-5 text-rose-500 flex-none mt-0.5" />
            <div>
                <p class="text-sm font-semibold text-rose-700">Anunțul a fost respins</p>
                <p class="text-sm text-rose-600 mt-0.5">{{ listing.rejection_reason }}</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-[1fr_320px] gap-6 items-start">
            <div class="space-y-6">
                <!-- Performance -->
                <div class="bg-white border border-line rounded-2xl p-6 shadow-sm shadow-ink/5">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-ink mb-4">
                        <span class="w-7 h-7 rounded-lg bg-brand-50 text-brand-500 flex items-center justify-center flex-none"><ChartBarIcon class="w-4 h-4" /></span>
                        Performanță (ultimele 30 de zile)
                    </h3>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                        <div v-for="stat in statCards" :key="stat.label" class="rounded-xl bg-paper px-3 py-3">
                            <component :is="stat.icon" class="w-4 h-4 text-ink-soft/60 mb-1.5" />
                            <p class="text-lg font-semibold text-ink tabular-nums">{{ stat.value }}</p>
                            <p class="text-xs text-ink-soft">{{ stat.label }}</p>
                        </div>
                    </div>

                    <TrendChart :data="trend" />
                </div>

                <!-- Tabbed edit form -->
                <div class="bg-white border border-line rounded-2xl shadow-sm shadow-ink/5 overflow-hidden">
                    <div class="flex items-center gap-1 border-b border-line px-3 overflow-x-auto">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            @click="activeTab = tab.key"
                            class="relative flex items-center gap-2 px-4 py-3.5 text-sm font-medium whitespace-nowrap transition-colors duration-150"
                            :class="activeTab === tab.key ? 'text-brand-600' : 'text-ink-soft hover:text-ink'"
                        >
                            <component :is="tab.icon" class="w-4 h-4" />
                            {{ tab.label }}
                            <span v-if="tab.hasError" class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            <span
                                v-if="activeTab === tab.key"
                                class="absolute inset-x-3 -bottom-px h-0.5 rounded-full bg-brand-500"
                            ></span>
                        </button>
                    </div>

                    <form @submit.prevent="submitWithAction('save')">
                        <div class="p-6">
                            <div v-show="activeTab === 'details'">
                                <DetailsSection :form="form" :categories="categories" :heading="false" />
                            </div>
                            <div v-show="activeTab === 'price'">
                                <PriceSection :form="form" :heading="false" />
                            </div>
                            <div v-show="activeTab === 'location'">
                                <LocationSection :form="form" :counties="counties" :heading="false" />
                            </div>
                            <div v-show="activeTab === 'media'">
                                <MediaManager :listing-id="listing.id" :media="listing.media" :limits="mediaLimits" />
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 px-6 py-5 border-t border-line bg-paper/40">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-xl bg-white border border-line px-5 py-2.5 text-sm font-semibold text-ink shadow-sm shadow-ink/5 transition-all duration-200 hover:bg-paper hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60 disabled:pointer-events-none"
                            >
                                Salvează modificările
                            </button>

                            <button
                                v-if="['draft', 'rejected'].includes(listing.status)"
                                type="button"
                                :disabled="form.processing"
                                @click="submitWithAction('submit')"
                                class="rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-500/25 transition-all duration-200 hover:bg-brand-600 hover:shadow-glow-brand hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60 disabled:pointer-events-none"
                            >
                                Trimite spre verificare
                            </button>

                            <button
                                v-if="['published', 'pending_review'].includes(listing.status)"
                                type="button"
                                :disabled="form.processing"
                                @click="submitWithAction('unpublish')"
                                class="rounded-xl px-5 py-2.5 text-sm font-semibold text-ink-soft transition-colors duration-150 hover:text-ink"
                            >
                                Retrage la ciornă
                            </button>

                            <span v-if="form.recentlySuccessful" class="text-sm text-emerald-600">Salvat.</span>
                        </div>
                    </form>
                </div>
            </div>

            <div class="lg:sticky lg:top-6 space-y-3">
                <p class="flex items-center gap-1.5 text-xs font-semibold text-ink-soft uppercase tracking-wide px-1">
                    <SparklesIcon class="w-3.5 h-3.5" /> Previzualizare live
                </p>
                <ListingCard :listing="previewListing" :preview="true" />
            </div>
        </div>
    </ProviderLayout>
</template>
