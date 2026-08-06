<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import HomeBody from '@/Components/Home/Body.vue';

defineProps({
    categories: { type: Array, default: () => [] },
    counties: { type: Array, default: () => [] },
    listings: { type: Array, default: () => [] },
    favoriteListingIds: { type: Array, default: () => [] },
});

const searchQuery = ref('');
const searchCountyId = ref('');

const submitSearch = () => {
    router.get(route('listings.index'), {
        q: searchQuery.value || undefined,
        county_id: searchCountyId.value || undefined,
    });
};
</script>

<template>
    <ClientLayout title="Acasă" full-bleed>
        <HomeBody
            v-model:search-query="searchQuery"
            v-model:search-county-id="searchCountyId"
            :categories="categories"
            :counties="counties"
            :listings="listings"
            :favorite-listing-ids="favoriteListingIds"
            @search="submitSearch"
        />
    </ClientLayout>
</template>
