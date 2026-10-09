<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="btn-brand px-6 py-2.5 text-sm">
        <slot />
    </button>
</template>
