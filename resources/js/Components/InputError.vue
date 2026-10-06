<script setup>
import { ExclamationCircleIcon } from '@heroicons/vue/16/solid';

defineProps({
    message: String,
});
</script>

<template>
    <div v-show="message">
        <p class="flex items-center gap-1 text-sm text-red-600" role="alert">
            <ExclamationCircleIcon class="h-4 w-4 flex-none" /> {{ message }}
        </p>
    </div>
</template>
