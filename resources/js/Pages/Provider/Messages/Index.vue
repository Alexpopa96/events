<script setup>
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import Inbox from '@/Components/Messages/Inbox.vue';

defineProps({
    conversations: Array,
    active: Object,
    activeId: Number,
    listings: Array,
    listingFilter: Number,
});
</script>

<template>
    <ProviderLayout title="Mesaje">
        <Inbox
            side="provider"
            :conversations="conversations"
            :active="active"
            :listings="listings"
            :listing-filter="listingFilter"
            height-class="h-[calc(100dvh-10rem)] lg:h-[calc(100vh-8rem)]"
        />
    </ProviderLayout>
</template>
