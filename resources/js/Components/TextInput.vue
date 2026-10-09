<template>
    <div :class="$attrs.class">
        <label v-if="label" class="mb-1.5 block text-sm font-medium text-ivt-ink" :for="id">{{ label }}</label>
        <input :id="id" ref="input" v-bind="{ ...$attrs, class: null }" :class="[fieldClass, error && fieldErrorClass]" :type="type" :placeholder="placeholder"
               :value="modelValue" @input="$emit('update:modelValue', $event.target.value)">
        <slot/>
        <div v-if="error" class="mt-1.5 text-sm text-danger-600" role="alert">{{ error }}</div>
    </div>
</template>

<script>
import { fieldClass, fieldErrorClass } from '@/Composables/useFieldClasses';

export default {
    data() {
        return { fieldClass, fieldErrorClass };
    },
    props: {
        id: {
            type: String,
            default() {
                return `select-input-${Math.random() * 1000}`;
            },
        },
        type: {
            type: String,
            default: 'text',
        },
        modelValue: String|Number,
        label: String,
        error: String,
        placeholder: {
            type: String,
            default: ''
        }
    },
    methods: {
        focus() {
            this.$refs.input.focus()
        },
        select() {
            this.$refs.input.select()
        },
        setSelectionRange(start, end) {
            this.$refs.input.setSelectionRange(start, end)
        },
    },
}
</script>
