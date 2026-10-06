<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center justify-center gap-2 rounded-full bg-ivt-paper-2 px-6 py-2.5 text-sm font-semibold text-ivt-ink transition-all duration-200 hover:bg-ivt-paper-3 hover:text-primary focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/15 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60">
        <slot />
    </button>
</template>
