<script setup>
import { computed } from 'vue';
import { CalendarDaysIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    size: { type: String, default: 'md' }, // 'sm' | 'md' | 'lg'
    dark: { type: Boolean, default: false }, // use on dark backgrounds (e.g. provider sidebar hero, footers)
    // 'brand' (default, emerald marketplace look) | 'invita' (paper/wine/gold
    // mockup look used only on the public SiteHeader/SiteFooter)
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
    <span v-if="variant === 'invita'" class="inline-flex items-center gap-2">
        <span
            class="flex flex-none items-center justify-center rounded-full border text-ivt-wine"
            :class="[sizing.box, dark ? 'border-ivt-gold-bright/50 text-ivt-gold-bright' : 'border-ivt-gold']"
        >
            <CalendarDaysIcon :class="sizing.icon" />
        </span>
        <span class="font-serif tracking-tight" :class="[sizing.text, dark ? 'text-ivt-on-dark' : 'text-ivt-ink']">
            Event<span :class="dark ? 'text-ivt-gold-bright' : 'text-ivt-wine'">Hub</span>
        </span>
    </span>
    <span v-else class="inline-flex items-center gap-2">
        <span
            class="flex flex-none items-center justify-center bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-sm shadow-brand-500/30"
            :class="sizing.box"
        >
            <CalendarDaysIcon :class="sizing.icon" />
        </span>
        <span class="font-serif tracking-tight" :class="[sizing.text, dark ? 'text-white' : 'text-ink']">
            Event<span class="text-brand-500">Hub</span>
        </span>
    </span>
</template>
