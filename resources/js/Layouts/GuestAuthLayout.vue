<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeftIcon } from '@heroicons/vue/24/outline';
import Logo from '@/Components/Logo.vue';

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
    <div class="relative min-h-screen overflow-hidden bg-ivt-paper-2 font-invita">
        <div class="pointer-events-none absolute inset-x-[-10%] -top-[20%] h-[70%]">
            <div class="absolute inset-0" style="background: radial-gradient(55% 55% at 25% 10%, rgba(168,127,46,0.09), transparent 60%), radial-gradient(45% 45% at 90% 5%, rgba(124,46,59,0.08), transparent 60%);" />
        </div>

        <div class="relative flex min-h-screen flex-col items-center justify-center px-4 py-10 sm:py-14">
            <Link href="/" class="mb-8">
                <Logo variant="invita" />
            </Link>

            <div :class="maxWidth" class="w-full rounded-[24px] border border-ivt-line bg-white px-6 py-8 shadow-ivt-soft sm:px-9 sm:py-10">
                <Link
                    :href="backHref"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-ivt-line text-ivt-ink transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
                >
                    <ChevronLeftIcon class="h-5 w-5" />
                </Link>

                <div class="mt-6 text-center">
                    <h1 class="font-serif text-2xl text-ivt-ink">{{ title }}</h1>
                    <p v-if="subtitle" class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-ivt-ink-soft">{{ subtitle }}</p>
                </div>

                <div class="mt-8">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
