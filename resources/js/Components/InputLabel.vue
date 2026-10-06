<script setup>
defineProps({
    value: String,
});
</script>

<template>
    <label class="mb-1.5 block text-sm font-medium text-ivt-ink">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
