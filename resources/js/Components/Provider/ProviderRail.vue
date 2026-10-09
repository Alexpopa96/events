<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    quota: { type: Object, default: null },
});

const page = usePage();
const nav = computed(() => page.props.providerNav);
const quotaReached = computed(() => props.quota?.max != null && props.quota.used >= props.quota.max);
</script>

<template>
    <template v-if="nav">
        <!-- Quota -->
        <section v-if="quota && quota.max !== null" class="rounded-2xl border border-ivt-line bg-white p-4 shadow-sm shadow-ivt-ink/5">
            <p class="text-sm font-semibold text-ivt-ink">Anunțuri folosite</p>
            <p class="mt-0.5 text-xs" :class="quotaReached ? 'font-medium text-danger-600' : 'text-ivt-ink-soft'">
                {{ quota.used }} / {{ quota.max }} din planul {{ quota.plan_name }}
            </p>
            <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-ivt-paper-2">
                <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="quotaReached ? 'bg-danger-500' : 'bg-gradient-to-r from-primary-bright to-ivt-accent-bright'"
                    :style="`width: ${Math.min(100, (quota.used / quota.max) * 100)}%`"
                ></div>
            </div>
            <Link v-if="quotaReached" :href="route('provider.subscription.index')" class="mt-3 inline-block text-xs font-semibold text-primary hover:underline">
                Mărește planul
            </Link>
        </section>
    </template>
</template>
