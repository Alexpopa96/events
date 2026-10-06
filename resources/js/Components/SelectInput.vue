<template>
    <div :class="$attrs.class">
        <label v-if="label" class="mb-1.5 block text-sm font-medium text-ivt-ink" :for="id">{{ label }}</label>
        <select :id="id" ref="input" v-model="selected" v-bind="{ ...$attrs, class: null }" class="cursor-pointer pr-10" :class="[fieldClass, error && fieldErrorClass]">
            <slot/>
        </select>
        <div v-if="error" class="mt-1.5 text-sm text-red-600" role="alert">{{ error }}</div>
    </div>
</template>

<script>
import { v4 as uuid } from 'uuid'
import { fieldClass, fieldErrorClass } from '@/Composables/useFieldClasses'

export default {
    inheritAttrs: false,
    props: {
        id: {
            type: String,
            default() {
                return `select-input-${uuid()}`
            },
        },
        error: String,
        label: String,
        modelValue: [String, Number, Boolean],

    },
    emits: ['update:modelValue'],
    data() {
        return {
            selected: this.modelValue,
            fieldClass,
            fieldErrorClass,
        }
    },
    watch: {
        selected(selected) {
            this.$emit('update:modelValue', selected)
        },
    },
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
