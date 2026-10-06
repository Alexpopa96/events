<script setup>
import { computed } from 'vue';

const props = defineProps({
    values: { type: Array, default: () => [] },
    color: { type: String, default: 'currentColor' },
});

const W = 100;
const H = 32;

const points = computed(() => {
    const v = props.values;
    if (v.length < 2) return [];
    const max = Math.max(...v, 1);
    return v.map((n, i) => [(i / (v.length - 1)) * W, H - 3 - (n / max) * (H - 6)]);
});

const line = computed(() => points.value.map(([x, y]) => `${x.toFixed(2)},${y.toFixed(2)}`).join(' '));
const area = computed(() => (points.value.length ? `0,${H} ${line.value} ${W},${H}` : ''));
</script>

<template>
    <svg :viewBox="`0 0 ${W} ${H}`" preserveAspectRatio="none" class="w-full h-8" aria-hidden="true">
        <polygon :points="area" :fill="color" fill-opacity="0.12" />
        <polyline :points="line" fill="none" :stroke="color" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
    </svg>
</template>
