<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { CheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    plans: { type: Array, default: () => [] },
});

const page = usePage();

const isHighlighted = (plan, index) => plan.slug === 'standard' || (props.plans.length === 3 && index === 1);

/* Signed-in providers change plans from their dashboard; everyone else registers first. */
const ctaHref = computed(() => (page.props.auth.isProvider ? route('provider.subscription.index') : '/register'));
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-3 lg:items-stretch">
        <div
            v-for="(plan, index) in plans"
            :key="plan.slug"
            v-reveal="index * 100"
            class="relative flex flex-col rounded-[24px] p-8 transition-all duration-300 hover:-translate-y-1.5 lg:p-9"
            :class="isHighlighted(plan, index)
                ? 'bg-gradient-to-br from-ivt-ink-2 via-ivt-ink to-primary-night text-ivt-on-dark shadow-glow-violet ring-1 ring-ivt-violet-bright/40 lg:-my-4 lg:py-12'
                : 'ring-gradient border border-ivt-line bg-white hover:shadow-ivt-soft'"
        >
            <span
                v-if="isHighlighted(plan, index)"
                class="absolute -top-3 left-8 rounded-full bg-brand px-3.5 py-1.5 text-[11px] font-extrabold uppercase tracking-[0.06em] text-white shadow-glow-primary lg:left-9"
            >
                Recomandat
            </span>
            <h3 class="font-display text-xl font-medium" :class="isHighlighted(plan, index) ? 'text-ivt-on-dark' : 'text-ivt-ink'">
                {{ plan.name }}
            </h3>
            <p class="mt-2 min-h-9 text-[13px]" :class="isHighlighted(plan, index) ? 'text-ivt-on-dark-dim' : 'text-ivt-ink-soft'">
                {{ plan.description }}
            </p>
            <div class="mt-6 flex items-baseline gap-1.5">
                <b class="font-display text-[48px] font-bold tracking-tight">{{ plan.price > 0 ? plan.price.toLocaleString('ro-RO') : '0' }} lei</b>
                <span class="text-[13px]" :class="isHighlighted(plan, index) ? 'text-ivt-on-dark-dim' : 'text-ivt-ink-faint'">/ lună</span>
            </div>
            <ul class="mt-6 flex flex-1 flex-col gap-3 border-t pt-6" :class="isHighlighted(plan, index) ? 'border-white/10' : 'border-ivt-line'">
                <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2.5 text-sm" :class="isHighlighted(plan, index) ? 'text-ivt-on-dark-dim' : 'text-ivt-ink-soft'">
                    <span class="mt-0.5 flex h-4 w-4 flex-none items-center justify-center rounded-full" :class="isHighlighted(plan, index) ? 'bg-ivt-violet-bright/20 text-ivt-violet-bright' : 'bg-primary/10 text-primary'">
                        <CheckIcon class="h-2.5 w-2.5" stroke-width="3" />
                    </span>
                    {{ feature }}
                </li>
            </ul>
            <Link
                :href="ctaHref"
                class="mt-8 block rounded-full py-3 text-center text-sm font-semibold transition-all"
                :class="isHighlighted(plan, index)
                    ? 'btn-brand w-full'
                    : 'border border-ivt-line text-ivt-ink hover:border-ivt-ink hover:bg-ivt-ink hover:text-ivt-paper'"
            >
                Alege {{ plan.name }}
            </Link>
        </div>
    </div>
</template>
