<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import DetailsSection from './Partials/Sections/DetailsSection.vue';
import PriceSection from './Partials/Sections/PriceSection.vue';
import LocationSection from './Partials/Sections/LocationSection.vue';
import MediaManager from './Partials/MediaManager.vue';
import ListingPost from '@/Components/Provider/ListingPost.vue';
import TrendChart from '@/Components/Provider/TrendChart.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import {
    CalendarIcon, EyeIcon, ExclamationTriangleIcon, PhotoIcon, SparklesIcon,
    PhoneIcon, ChatBubbleLeftRightIcon, ChartBarIcon, TagIcon, CurrencyDollarIcon,
    MapPinIcon, CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    listing: Object,
    categories: Array,
    counties: Array,
    eventTypes: { type: Array, default: () => [] },
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
    benefits: props.listing.benefits ?? [],
    event_types: props.listing.event_types ?? [],
    county_id: props.listing.county_id,
    locality_id: props.listing.locality_id,
    action: 'save',
});

const submitWithAction = (action) => {
    form.transform((data) => ({ ...data, action })).put(route('provider.listings.update', props.listing.id));
};

const confirmUnpublishOpen = ref(false);
const confirmUnpublish = () => {
    confirmUnpublishOpen.value = false;
    submitWithAction('unpublish');
};

const statusMeta = {
    draft: { label: 'Ciornă', class: 'bg-ivt-paper-2 text-ivt-ink-soft' },
    pending_review: { label: 'În verificare', class: 'bg-ivt-violet/20 text-ivt-violet' },
    published: { label: 'Publicat', class: 'bg-success-100 text-success-700' },
    rejected: { label: 'Respins', class: 'bg-danger-100 text-danger-700' },
    archived: { label: 'Arhivat', class: 'bg-ivt-paper-2 text-ivt-ink-soft' },
};

const status = computed(() => statusMeta[props.listing.status] ?? { label: props.listing.status, class: 'bg-ivt-paper-2 text-ivt-ink-soft' });

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
    posted_at: 'Previzualizare',
    excerpt: form.description.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 220),
}));

const coverUrl = computed(() => previewListing.value.cover_url);

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
    { key: 'price', label: 'Preț', icon: CurrencyDollarIcon, hasError: !!(form.errors.price_from || form.errors.price_to || Object.keys(form.errors).some((key) => key.startsWith('benefits'))) },
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
    <ProviderLayout title="Editează anunț">
        <!-- Hero -->
        <div class="mb-5 flex items-center gap-4 rounded-2xl border border-ivt-line bg-white p-4 shadow-sm shadow-ivt-ink/5">
            <div class="h-20 w-24 sm:h-24 sm:w-32 flex-none overflow-hidden rounded-xl bg-gradient-to-br from-ivt-paper-2 to-ivt-paper-3">
                <img v-if="coverUrl" :src="coverUrl" alt="" class="h-full w-full object-cover" />
                <div v-else class="flex h-full w-full items-center justify-center"><PhotoIcon class="h-8 w-8 text-primary/30" /></div>
            </div>
            <div class="min-w-0 flex-1">
                <span class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold" :class="status.class">{{ status.label }}</span>
                <h2 class="mt-1.5 truncate font-display text-xl sm:text-2xl text-ivt-ink">{{ listing.title || 'Anunț fără titlu' }}</h2>
                <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ivt-ink-soft">
                    <span class="flex items-center gap-1"><EyeIcon class="w-3.5 h-3.5" /> {{ listing.views_count }} vizualizări</span>
                    <span class="flex items-center gap-1"><CalendarIcon class="w-3.5 h-3.5" /> creat pe {{ listing.created_at }}</span>
                    <span v-if="listing.published_at" class="flex items-center gap-1"><SparklesIcon class="w-3.5 h-3.5" /> publicat pe {{ listing.published_at }}</span>
                </div>
            </div>
        </div>

        <div v-if="listing.status === 'rejected' && listing.rejection_reason" class="flex items-start gap-3 rounded-2xl border border-danger-200 bg-danger-50 px-5 py-4 mb-5">
            <ExclamationTriangleIcon class="w-5 h-5 text-danger-500 flex-none mt-0.5" />
            <div>
                <p class="text-sm font-semibold text-danger-700">Anunțul a fost respins</p>
                <p class="text-sm text-danger-600 mt-0.5">{{ listing.rejection_reason }}</p>
            </div>
        </div>

        <div class="grid xl:grid-cols-[1fr_22rem] gap-5 items-start">
            <div class="space-y-5 min-w-0">
                <!-- Tabbed edit form -->
                <div class="rounded-2xl border border-ivt-line bg-white shadow-sm shadow-ivt-ink/5">
                    <div class="p-3 border-b border-ivt-line">
                        <div class="flex items-center gap-1 rounded-full bg-ivt-paper-2 p-1 overflow-x-auto" role="tablist">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                type="button"
                                role="tab"
                                :aria-selected="activeTab === tab.key"
                                @click="activeTab = tab.key"
                                class="flex flex-1 items-center justify-center gap-2 rounded-full px-4 py-2 text-sm font-medium whitespace-nowrap transition-all duration-150"
                                :class="activeTab === tab.key ? 'bg-white text-primary shadow-sm shadow-ivt-ink/10' : 'text-ivt-ink-soft hover:text-ivt-ink'"
                            >
                                <component :is="tab.icon" class="w-4 h-4" />
                                {{ tab.label }}
                                <span v-if="tab.hasError" class="w-1.5 h-1.5 rounded-full bg-danger-500"></span>
                            </button>
                        </div>
                    </div>

                    <form @submit.prevent="submitWithAction('save')">
                        <div class="p-5 sm:p-6">
                            <div v-show="activeTab === 'details'">
                                <DetailsSection :form="form" :categories="categories" :event-types="eventTypes" :heading="false" />
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

                        <!-- Sticky action bar -->
                        <div class="sticky bottom-[4.5rem] lg:bottom-0 z-10 flex flex-wrap items-center gap-3 rounded-b-2xl border-t border-ivt-line bg-white/90 px-5 sm:px-6 py-4 backdrop-blur-xl">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white px-5 py-2.5 text-sm font-semibold text-ivt-ink shadow-sm shadow-ivt-ink/5 transition-all duration-200 hover:bg-ivt-paper-2 disabled:opacity-60 disabled:pointer-events-none"
                            >
                                <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                </svg>
                                Salvează modificările
                            </button>

                            <button
                                v-if="['draft', 'rejected'].includes(listing.status)"
                                type="button"
                                :disabled="form.processing"
                                @click="submitWithAction('submit')"
                                class="inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/25 transition-all duration-200 hover:brightness-110 hover:shadow-glow-violet hover:shadow-glow-primary active:scale-[0.98] disabled:opacity-60 disabled:pointer-events-none"
                            >
                                Trimite spre verificare
                            </button>

                            <button
                                v-if="['published', 'pending_review'].includes(listing.status)"
                                type="button"
                                :disabled="form.processing"
                                @click="confirmUnpublishOpen = true"
                                class="rounded-full px-4 py-2.5 text-sm font-semibold text-ivt-ink-soft transition-colors duration-150 hover:bg-ivt-paper-2 hover:text-ivt-ink"
                            >
                                Retrage la ciornă
                            </button>

                            <span v-if="form.recentlySuccessful" class="inline-flex items-center gap-1.5 text-sm text-success-600">
                                <CheckCircleIcon class="h-4 w-4" /> Salvat.
                            </span>
                        </div>
                    </form>
                </div>

                <!-- Performance -->
                <div class="rounded-2xl border border-ivt-line bg-white p-5 sm:p-6 shadow-sm shadow-ivt-ink/5">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-ivt-ink mb-4">
                        <span class="w-7 h-7 rounded-full bg-ivt-paper-2 text-primary flex items-center justify-center flex-none"><ChartBarIcon class="w-4 h-4" /></span>
                        Performanță (ultimele 30 de zile)
                    </h3>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                        <div v-for="stat in statCards" :key="stat.label" class="rounded-2xl bg-ivt-paper-2/70 px-4 py-3">
                            <span class="mb-2 flex h-8 w-8 items-center justify-center rounded-full bg-white text-primary shadow-sm shadow-ivt-ink/5">
                                <component :is="stat.icon" class="w-4 h-4" />
                            </span>
                            <p class="text-xl font-semibold text-ivt-ink tabular-nums">{{ stat.value }}</p>
                            <p class="text-xs text-ivt-ink-soft">{{ stat.label }}</p>
                        </div>
                    </div>

                    <TrendChart :data="trend" />
                </div>
            </div>

            <div class="xl:sticky xl:top-20 space-y-3">
                <p class="flex items-center gap-1.5 text-xs font-semibold text-ivt-ink-soft uppercase tracking-wide px-1">
                    <SparklesIcon class="w-3.5 h-3.5" /> Previzualizare live
                </p>
                <ListingPost :listing="previewListing" preview />
            </div>
        </div>

        <ConfirmDialog
            v-model:show="confirmUnpublishOpen"
            title="Retragi anunțul la ciornă?"
            message="Anunțul nu va mai fi vizibil publicului până când îl trimiți din nou spre verificare."
            confirm-label="Retrage"
            variant="default"
            :processing="form.processing"
            @confirm="confirmUnpublish"
        />
    </ProviderLayout>
</template>
