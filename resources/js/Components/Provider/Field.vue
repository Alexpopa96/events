<script setup>
import { ExclamationCircleIcon } from '@heroicons/vue/16/solid';

defineProps({
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    optional: { type: Boolean, default: false },
    // Renders "count / max" on the right of the label, e.g. for a title length.
    count: { type: Number, default: null },
    max: { type: Number, default: null },
    for: { type: String, default: null },
});
</script>

<template>
    <div>
        <div v-if="label || count !== null" class="mb-1.5 flex items-baseline justify-between gap-3">
            <label :for="$props.for" class="text-sm font-medium text-ivt-ink">
                {{ label }}
                <span v-if="optional" class="ml-1 text-xs font-normal text-ivt-ink-soft/70">(opțional)</span>
            </label>
            <span
                v-if="count !== null && max"
                class="text-xs tabular-nums"
                :class="count > max * 0.9 ? 'text-amber-600' : 'text-ivt-ink-soft/60'"
            >{{ count }}/{{ max }}</span>
        </div>

        <slot />

        <p v-if="error" class="mt-1.5 flex items-center gap-1 text-sm text-red-600" role="alert">
            <ExclamationCircleIcon class="h-4 w-4 flex-none" /> {{ error }}
        </p>
        <p v-else-if="hint" class="mt-1.5 text-xs leading-relaxed text-ivt-ink-soft/80">{{ hint }}</p>
    </div>
</template>
