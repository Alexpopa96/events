<template>
    <div :class="$attrs.class" class="flex flex-col">
        <label v-if="label" class="mb-1.5 block text-sm font-medium text-ivt-ink" :for="id">{{ label }}</label>
        <textarea :id="id" ref="input" v-bind="{ ...$attrs, class: null }" class="resize-y" :class="[fieldClass, error && fieldErrorClass]" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" />
        <div v-if="error" class="mt-1.5 text-sm text-danger-600" role="alert">{{ error }}</div>
    </div>
</template>

<script>
import { v4 as uuid } from 'uuid'
import { fieldClass, fieldErrorClass } from '@/Composables/useFieldClasses'

export default {
    inheritAttrs: false,
    data() {
        return { fieldClass, fieldErrorClass }
    },
    props: {
        id: {
            type: String,
            default() {
                return `textarea-input-${uuid()}`
            },
        },
        error: String,
        label: String,
        modelValue: String,
    },
    emits: ['update:modelValue'],
    methods: {
        focus() {
            this.$refs.input.focus()
        },
        select() {
            this.$refs.input.select()
        },
    },
}
</script>
