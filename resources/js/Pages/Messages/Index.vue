<script setup>
import ClientAccountLayout from '@/Layouts/ClientAccountLayout.vue';
import Inbox from '@/Components/Messages/Inbox.vue';

defineProps({
    conversations: Array,
    active: Object,
    activeId: Number,
    listings: Array,
});
</script>

<template>
    <ClientAccountLayout title="Mesajele mele" active="messages" :sidebar="false" :hero="false">
        <Inbox
            side="client"
            :conversations="conversations"
            :active="active"
            :listings="listings"
            height-class="h-[calc(100dvh-8rem)] min-h-[28rem] lg:h-[calc(100vh-9rem)]"
        />
    </ClientAccountLayout>
</template>
