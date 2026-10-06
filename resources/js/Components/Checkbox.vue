<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        default: false,
    },
    value: {
        type: String,
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>


<template>
    <input
        v-model="proxyChecked"
        type="checkbox"
        :value="value"
        class="h-[18px] w-[18px] cursor-pointer rounded-md border-2 border-ivt-line bg-ivt-paper-2 text-primary transition-colors duration-150 checked:border-primary focus:ring-4 focus:ring-primary/15 focus:ring-offset-0"
    >
</template>
