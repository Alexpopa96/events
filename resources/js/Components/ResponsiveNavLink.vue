<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    active: Boolean,
    href: String,
    as: String,
});

const classes = computed(() => {
    return props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-primary-bright dark:border-primary text-start text-base font-medium text-primary dark:text-primary/30 bg-primary/5 dark:bg-primary-deep/50 focus:outline-none focus:text-primary-deep dark:focus:text-primary/20 focus:bg-primary/10 dark:focus:bg-primary-deep focus:border-primary dark:focus:border-primary/30 transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-ivt-ink-soft dark:text-ivt-ink-faint hover:text-ivt-ink dark:hover:text-ivt-on-dark-dim hover:bg-ivt-paper-2 dark:hover:bg-ivt-ink-2 hover:border-ivt-line dark:hover:border-ivt-ink-soft focus:outline-none focus:text-ivt-ink dark:focus:text-ivt-on-dark-dim focus:bg-ivt-paper-2 dark:focus:bg-ivt-ink-2 focus:border-ivt-line dark:focus:border-ivt-ink-soft transition duration-150 ease-in-out';
});
</script>

<template>
    <div>
        <button v-if="as == 'button'" :class="classes" class="w-full text-start">
            <slot />
        </button>

        <a v-else-if="as == 'a'" :class="classes" class="w-full text-start" :href="href">
            <slot />
        </a>

        <Link v-else :href="href" :class="classes">
            <slot />
        </Link>
    </div>
</template>
