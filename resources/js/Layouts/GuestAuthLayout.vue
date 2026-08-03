<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeftIcon } from '@heroicons/vue/24/outline';

defineProps({
    backHref: {
        type: String,
        default: '/',
    },
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    maxWidth: {
        type: String,
        default: 'max-w-md',
    },
});
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-[#EAEAEE] px-4 py-10 sm:py-14">
        <div :class="maxWidth" class="w-full rounded-[2rem] bg-white px-6 py-8 shadow-[0_24px_60px_-20px_rgba(33,28,39,0.25)] sm:px-9 sm:py-10">
            <Link
                :href="backHref"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-paper text-ink shadow-sm shadow-ink/10 transition-colors duration-150 hover:bg-line/60"
            >
                <ChevronLeftIcon class="h-5 w-5" />
            </Link>

            <div class="mt-6 text-center">
                <h1 class="font-serif text-2xl text-ink">{{ title }}</h1>
                <p v-if="subtitle" class="mx-auto mt-2 max-w-xs text-sm text-ink-soft">{{ subtitle }}</p>
            </div>

            <div class="mt-8">
                <slot />
            </div>
        </div>
    </div>
</template>
