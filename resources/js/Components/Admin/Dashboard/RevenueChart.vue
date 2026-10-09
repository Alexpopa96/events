<script setup>
import { computed, ref } from 'vue';
import { ivt } from '@/palette';

const props = defineProps({
    // [{ label: 'YYYY-MM', value }]
    months: { type: Array, required: true },
    currency: { type: String, default: 'RON' },
    color: { type: String, default: ivt['ink-2'] },
});

const hover = ref(null);
const max = computed(() => Math.max(...props.months.map((m) => m.value), 1));
const monthName = (label) => new Date(`${label}-01`).toLocaleDateString('ro-RO', { month: 'short' });
const money = (v) => new Intl.NumberFormat('ro-RO', { style: 'currency', currency: props.currency, maximumFractionDigits: 0 }).format(v);
</script>

<template>
    <div class="flex items-end gap-3 h-44" @pointerleave="hover = null">
        <div v-for="(m, i) in months" :key="m.label" class="relative flex-1 h-full flex flex-col justify-end items-center gap-2" @pointerenter="hover = i">
            <div v-if="hover === i" class="pointer-events-none absolute -top-1 z-10 -translate-y-full whitespace-nowrap rounded-lg border border-ivt-line bg-white px-2.5 py-1 text-xs font-semibold text-ivt-ink shadow-lg shadow-ivt-ink/10">
                {{ money(m.value) }}
            </div>
            <div class="w-full flex-1 flex items-end">
                <div
                    class="w-full rounded-t-md transition-all duration-500"
                    :class="hover !== null && hover !== i ? 'opacity-40' : ''"
                    :style="{ height: `${Math.max((m.value / max) * 100, m.value ? 3 : 1)}%`, background: color }"
                ></div>
            </div>
            <span class="text-xs text-ivt-ink-faint capitalize">{{ monthName(m.label) }}</span>
        </div>
    </div>
</template>
