<script setup>
defineProps(['value']);
</script>

<template>
    <label class="form-label mb-1.5 block text-sm font-medium text-ivt-ink">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
