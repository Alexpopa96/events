<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: String,
    active: Boolean,
});

const classes = computed(() => {
    return props.active
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-primary-bright dark:border-primary text-sm font-medium leading-5 text-ivt-ink dark:text-ivt-on-dark focus:outline-none focus:border-primary transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-ivt-ink-soft dark:text-ivt-ink-faint hover:text-ivt-ink dark:hover:text-ivt-on-dark-dim hover:border-ivt-line dark:hover:border-ivt-ink-2 focus:outline-none focus:text-ivt-ink dark:focus:text-ivt-on-dark-dim focus:border-ivt-line dark:focus:border-ivt-ink-2 transition duration-150 ease-in-out';
});
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
