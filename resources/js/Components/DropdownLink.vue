<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: String,
    as: String,
});
</script>

<template>
    <div>
        <button v-if="as == 'button'" type="submit" class="block w-full px-4 py-2 text-start text-sm leading-5 text-ivt-ink dark:text-ivt-on-dark-dim hover:bg-ivt-paper-2 dark:hover:bg-ivt-ink-2 focus:outline-none focus:bg-ivt-paper-2 dark:focus:bg-ivt-ink-2 transition duration-150 ease-in-out">
            <slot />
        </button>

        <a v-else-if="as =='a'" :href="href" class="block px-4 py-2 text-sm leading-5 text-ivt-ink dark:text-ivt-on-dark-dim hover:bg-ivt-paper-2 dark:hover:bg-ivt-ink-2 focus:outline-none focus:bg-ivt-paper-2 dark:focus:bg-ivt-ink-2 transition duration-150 ease-in-out">
            <slot />
        </a>

        <Link v-else :href="href" class="block px-4 py-2 text-sm leading-5 text-ivt-ink dark:text-ivt-on-dark-dim hover:bg-ivt-paper-2 dark:hover:bg-ivt-ink-2 focus:outline-none focus:bg-ivt-paper-2 dark:focus:bg-ivt-ink-2 transition duration-150 ease-in-out">
            <slot />
        </Link>
    </div>
</template>
