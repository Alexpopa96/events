<script setup>
const props = defineProps({
    modelValue: {
        type: Boolean
    }
});

const emit = defineEmits(['update:modelValue']);

function toggle() {
    emit('update:modelValue', !props.modelValue);
}
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="modelValue"
        @click="toggle"
        class="relative inline-flex h-6 w-11 flex-none items-center rounded-full transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
        :class="modelValue ? 'bg-primary' : 'bg-ivt-ink-soft/25'"
    >
        <span
            class="inline-block h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-200"
            :class="modelValue ? 'translate-x-[1.375rem]' : 'translate-x-0.5'"
        ></span>
    </button>
</template>
