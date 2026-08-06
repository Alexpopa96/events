<script setup>
import { computed, ref } from 'vue';
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    id: { type: String, required: true },
    icon: { type: [Object, Function], required: true },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    autocomplete: { type: String, default: 'off' },
    autofocus: { type: Boolean, default: false },
    inputmode: { type: String, default: undefined },
    error: { type: String, default: '' },
    readonly: { type: Boolean, default: false },
});

const modelValue = defineModel({ type: String, default: '' });

const showPassword = ref(false);

const resolvedType = computed(() => {
    if (props.type !== 'password') {
        return props.type;
    }

    return showPassword.value ? 'text' : 'password';
});
</script>

<template>
    <div>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-ivt-ink-faint">
                <component :is="icon" class="h-5 w-5" />
            </span>

            <input
                :id="id"
                v-model="modelValue"
                :type="resolvedType"
                :placeholder="placeholder"
                :autocomplete="autocomplete"
                :autofocus="autofocus"
                :inputmode="inputmode"
                :readonly="readonly"
                :tabindex="readonly ? -1 : undefined"
                class="w-full rounded-full border border-ivt-line bg-white py-3 pl-11 pr-4 text-sm text-ivt-ink placeholder:text-ivt-ink-faint transition-all duration-150 focus:border-ivt-gold focus:outline-none focus:ring-2 focus:ring-ivt-gold/20"
                :class="[
                    type === 'password' ? 'pr-11' : 'pr-4',
                    { 'border-red-400 focus:border-red-400 focus:ring-red-400/20': error },
                    { 'cursor-default bg-ivt-paper-2 text-ivt-ink-soft focus:bg-ivt-paper-2 focus:ring-0': readonly },
                ]"
            />

            <button
                v-if="type === 'password'"
                type="button"
                class="absolute inset-y-0 right-4 flex items-center text-ivt-ink-faint transition-colors duration-150 hover:text-ivt-ink"
                tabindex="-1"
                @click="showPassword = !showPassword"
            >
                <EyeSlashIcon v-if="showPassword" class="h-5 w-5" />
                <EyeIcon v-else class="h-5 w-5" />
            </button>
        </div>
        <InputError class="mt-1.5 px-4" :message="error" />
    </div>
</template>
