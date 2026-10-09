<script setup>
import { Head } from '@inertiajs/vue3';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import FloatingChatBubble from '@/Components/Messages/FloatingChatBubble.vue';

defineProps({
    title: {
        type: String,
        default: 'Contul meu',
    },
    // Skip the centered max-w-[1600px]/padding wrapper so the page's own
    // sections (e.g. the homepage body, with its full-bleed footer) can
    // manage their own width and background edge-to-edge.
    fullBleed: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-ivt-paper text-ivt-ink">
        <SiteHeader />

        <!-- Page heading -->
        <header v-if="$slots.header" class="border-b border-ivt-line bg-white/70 backdrop-blur">
            <div class="mx-auto max-w-[1600px] px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page content -->
        <main :class="fullBleed ? '' : 'mx-auto max-w-[1600px] px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8'">
            <slot />
        </main>

        <SiteFooter />

        <FloatingChatBubble />
    </div>
</template>
