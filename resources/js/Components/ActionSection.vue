<script setup>
import SectionTitle from './SectionTitle.vue';
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5 sm:p-7">
        <div class="grid gap-x-10 gap-y-6 lg:grid-cols-[14rem_1fr]">
            <SectionTitle>
                <template #title>
                    <slot name="title" />
                </template>
                <template #description>
                    <slot name="description" />
                </template>
            </SectionTitle>

            <div class="min-w-0">
                <slot name="content" />
            </div>
        </div>
    </section>
</template>
