<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';

defineEmits(['submitted']);

const hasActions = computed(() => !! useSlots().actions);
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-ivt-line bg-white shadow-sm shadow-ivt-ink/5">
        <form @submit.prevent="$emit('submitted')">
            <div class="grid gap-x-10 gap-y-6 p-5 sm:p-7 lg:grid-cols-[14rem_1fr]">
                <SectionTitle>
                    <template #title>
                        <slot name="title" />
                    </template>
                    <template #description>
                        <slot name="description" />
                    </template>
                </SectionTitle>

                <div class="grid max-w-xl grid-cols-6 gap-5">
                    <slot name="form" />
                </div>
            </div>

            <div v-if="hasActions" class="flex items-center justify-end gap-4 border-t border-ivt-line bg-ivt-paper-2/50 px-5 py-4 sm:px-7">
                <slot name="actions" />
            </div>
        </form>
    </section>
</template>
