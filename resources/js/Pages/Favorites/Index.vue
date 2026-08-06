<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { HeartIcon } from '@heroicons/vue/24/outline';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import ListingCard from '@/Components/Listing/Card.vue';

const props = defineProps({
    favorites: Array,
});

const items = ref(props.favorites);

const onToggled = ({ id, favorited }) => {
    if (!favorited) {
        items.value = items.value.filter((item) => item.id !== id);
    }
};
</script>

<template>
    <ClientLayout title="Favorite">
        <div class="space-y-6">
            <div>
                <h1 class="font-serif text-2xl text-ink sm:text-3xl">Anunțuri favorite</h1>
                <p class="mt-1 text-sm text-ink-soft">Anunțurile pe care le-ai salvat pentru mai târziu</p>
            </div>

            <div v-if="items.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <ListingCard
                    v-for="listing in items"
                    :key="listing.id"
                    :listing="listing"
                    :favorited="true"
                    @favorite-toggled="onToggled"
                />
            </div>

            <div v-else class="rounded-2xl border border-line bg-white px-5 py-16 text-center">
                <HeartIcon class="mx-auto h-8 w-8 text-ink-soft/30" />
                <p class="mt-3 text-sm font-medium text-ink">Nu ai niciun anunț favorit încă</p>
                <p class="mt-1 text-sm text-ink-soft">Apasă pe inimioară pe orice anunț ca să-l salvezi aici.</p>
                <Link
                    :href="route('listings.index')"
                    class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-600"
                >
                    Răsfoiește anunțuri
                </Link>
            </div>
        </div>
    </ClientLayout>
</template>
