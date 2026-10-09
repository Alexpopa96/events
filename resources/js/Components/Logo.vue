<script setup>
import { computed } from 'vue';
import { CalendarDaysIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    size: { type: String, default: 'md' }, // 'sm' | 'md' | 'lg'
    dark: { type: Boolean, default: false }, // use on dark backgrounds (e.g. provider sidebar hero, footers)
    // 'brand' (default, filled gradient mark) | 'invita' (same mark with a
    // gradient wordmark, used on the public SiteHeader/SiteFooter)
    variant: { type: String, default: 'brand' },
});

const sizes = {
    sm: { box: 'h-7 w-7 rounded-lg', icon: 'h-4 w-4', text: 'text-lg' },
    md: { box: 'h-8 w-8 rounded-lg', icon: 'h-4 w-4', text: 'text-xl' },
    lg: { box: 'h-10 w-10 rounded-xl', icon: 'h-5 w-5', text: 'text-2xl' },
};

const sizing = computed(() => sizes[props.size] ?? sizes.md);
</script>

<template>
    <span v-if="variant === 'invita'" class="group inline-flex items-center gap-2">
        <span
            class="flex flex-none -rotate-6 items-center justify-center bg-brand text-white shadow-glow-primary transition-transform duration-300 group-hover:rotate-0 group-hover:scale-110"
            :class="sizing.box"
        >
            <CalendarDaysIcon :class="sizing.icon" />
        </span>
        <span class="font-display font-bold tracking-tight" :class="[sizing.text, dark ? 'text-ivt-on-dark' : 'text-ivt-ink']">
            Event<span :class="dark ? 'text-ivt-violet-bright' : 'text-gradient'">Hub</span>
        </span>
    </span>
    <span v-else class="inline-flex items-center gap-2">
        <span
            class="flex flex-none items-center justify-center bg-brand text-white shadow-sm shadow-primary/30"
            :class="sizing.box"
        >
            <CalendarDaysIcon :class="sizing.icon" />
        </span>
        <span class="font-display font-bold tracking-tight" :class="[sizing.text, dark ? 'text-white' : 'text-ivt-ink']">
            Event<span :class="dark ? 'text-ivt-violet-bright' : 'text-primary'">Hub</span>
        </span>
    </span>
</template>
