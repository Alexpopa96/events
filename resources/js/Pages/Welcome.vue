<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import HomeBody from '@/Components/Home/Body.vue';

defineProps({
    categories: { type: Array, default: () => [] },
    counties: { type: Array, default: () => [] },
    listings: { type: Array, default: () => [] },
    favoriteListingIds: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ providers: 0, categories: 0, quoteRequests: 0 }) },
    quoteRequests: { type: Array, default: () => [] },
});

const searchCategory = ref('');
const searchCountyId = ref('');

const submitSearch = () => {
    router.get(route('listings.index'), {
        category: searchCategory.value || undefined,
        county_id: searchCountyId.value || undefined,
    });
};
</script>

<template>
    <Head :title="$page.props.seo?.full_title ?? 'Invita — găsește furnizorii perfecți pentru evenimentul tău'" />

    <div class="bg-ivt-paper text-ivt-ink antialiased">
        <SiteHeader />

        <HomeBody
            v-model:search-category="searchCategory"
            v-model:search-county-id="searchCountyId"
            :categories="categories"
            :counties="counties"
            :listings="listings"
            :favorite-listing-ids="favoriteListingIds"
            :stats="stats"
            :quote-requests="quoteRequests"
            @search="submitSearch"
        />

        <SiteFooter />
    </div>
</template>
