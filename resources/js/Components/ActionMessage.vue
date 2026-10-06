<script setup>
import { CheckCircleIcon } from '@heroicons/vue/16/solid';

defineProps({
    on: Boolean,
});
</script>

<template>
    <div>
        <transition leave-active-class="transition ease-in duration-1000" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-show="on" class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600">
                <CheckCircleIcon class="h-4 w-4" />
                <slot />
            </div>
        </transition>
    </div>
</template>
