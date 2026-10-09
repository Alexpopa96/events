<script setup>
defineProps({
    eyebrow: { type: String, required: true },
    title: { type: String, required: true },
});
</script>

<template>
    <div class="flex flex-wrap items-end justify-between gap-6">
        <div>
            <p class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/70 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-primary backdrop-blur">
                <span class="h-1.5 w-1.5 rounded-full bg-brand" /> {{ eyebrow }}
            </p>
            <h2 class="mt-4 max-w-xl text-balance font-display text-[clamp(30px,3.4vw,48px)] font-bold leading-[1.05] tracking-tight text-ivt-ink">
                {{ title }}
            </h2>
        </div>
        <p v-if="$slots.default" class="max-w-[380px] text-[15px] leading-relaxed text-ivt-ink-soft">
            <slot />
        </p>
    </div>
</template>
