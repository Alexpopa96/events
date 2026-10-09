<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center justify-center gap-2 rounded-full bg-danger-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-danger-600/25 transition-all duration-200 hover:bg-danger-500 focus:outline-none focus-visible:ring-4 focus-visible:ring-danger-500/25 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60 disabled:shadow-none">
        <slot />
    </button>
</template>
