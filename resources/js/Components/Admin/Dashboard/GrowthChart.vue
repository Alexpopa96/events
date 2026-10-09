<script setup>
import { computed, ref } from 'vue';
import { useElementSize } from '@vueuse/core';

const props = defineProps({
    // { dates: string[], series: [{ key, values: number[] }] }
    growth: { type: Object, required: true },
    // { [key]: { label, color } }
    meta: { type: Object, required: true },
});

const showTable = ref(false);
const root = ref(null);
const { width } = useElementSize(root);

const H = 240;
const pad = { top: 12, right: 12, bottom: 26, left: 32 };

const w = computed(() => Math.max(width.value, 280));
const n = computed(() => props.growth.dates.length);
const max = computed(() => {
    const m = Math.max(...props.growth.series.flatMap((s) => s.values), 1);
    const step = Math.pow(10, Math.floor(Math.log10(m)));
    return Math.ceil(m / step) * step > m * 1.6 ? Math.ceil(m / (step / 2)) * (step / 2) : Math.ceil(m / step) * step;
});

const x = (i) => pad.left + (n.value <= 1 ? 0 : (i / (n.value - 1)) * (w.value - pad.left - pad.right));
const y = (v) => pad.top + (1 - v / max.value) * (H - pad.top - pad.bottom);

const yTicks = computed(() => [0, 1, 2, 3, 4].map((t) => (max.value / 4) * t).map((v) => ({ v: Math.round(v * 10) / 10, y: y(v) })));

const fmtDay = (d) => new Date(d).toLocaleDateString('ro-RO', { day: 'numeric', month: 'short' });
const xTicks = computed(() => {
    const count = w.value < 480 ? 3 : 6;
    return Array.from({ length: count }, (_, k) => Math.round((k / (count - 1)) * (n.value - 1)))
        .map((i) => ({ i, x: x(i), label: fmtDay(props.growth.dates[i]) }));
});

const paths = computed(() => props.growth.series.map((s) => ({
    key: s.key,
    d: s.values.map((v, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(v).toFixed(1)}`).join(' '),
})));

const hover = ref(null);
const onMove = (e) => {
    const rect = e.currentTarget.getBoundingClientRect();
    const px = e.clientX - rect.left;
    const i = Math.round(((px - pad.left) / (w.value - pad.left - pad.right)) * (n.value - 1));
    hover.value = Math.min(n.value - 1, Math.max(0, i));
};

const tipStyle = computed(() => {
    if (hover.value === null) return {};
    const left = x(hover.value);
    return left > w.value / 2
        ? { right: `${w.value - left + 12}px`, top: '8px' }
        : { left: `${left + 12}px`, top: '8px' };
});

const totals = computed(() => props.growth.series.map((s) => ({ key: s.key, total: s.values.reduce((a, b) => a + b, 0) })));
</script>

<template>
    <div>
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <ul class="flex flex-wrap items-center gap-x-5 gap-y-1">
                <li v-for="t in totals" :key="t.key" class="flex items-center gap-2 text-xs text-ivt-ink-soft">
                    <span class="w-2.5 h-2.5 rounded-full" :style="{ background: meta[t.key].color }"></span>
                    {{ meta[t.key].label }}
                    <span class="font-semibold text-ivt-ink">{{ t.total }}</span>
                </li>
            </ul>
            <button type="button" @click="showTable = !showTable" class="text-xs font-medium text-primary hover:text-ivt-ink transition-colors">
                {{ showTable ? 'Vezi graficul' : 'Vezi ca tabel' }}
            </button>
        </div>

        <div v-if="!showTable" ref="root" class="relative" :style="{ height: `${H}px` }">
            <svg v-if="width" :width="w" :height="H" class="block" role="img" aria-label="Evoluția zilnică a înregistrărilor noi" @pointermove="onMove" @pointerleave="hover = null">
                <g>
                    <line v-for="t in yTicks" :key="t.v" :x1="pad.left" :x2="w - pad.right" :y1="t.y" :y2="t.y" stroke="currentColor" class="text-ivt-ink/10" stroke-width="1" />
                    <text v-for="t in yTicks" :key="`l${t.v}`" :x="pad.left - 8" :y="t.y + 4" text-anchor="end" class="fill-ivt-ink-faint" font-size="11">{{ t.v }}</text>
                    <text v-for="t in xTicks" :key="t.i" :x="t.x" :y="H - 6" :text-anchor="t.i === 0 ? 'start' : t.i === n - 1 ? 'end' : 'middle'" class="fill-ivt-ink-faint" font-size="11">{{ t.label }}</text>
                </g>
                <path v-for="p in paths" :key="p.key" :d="p.d" fill="none" :stroke="meta[p.key].color" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                <g v-if="hover !== null">
                    <line :x1="x(hover)" :x2="x(hover)" :y1="pad.top" :y2="H - pad.bottom" stroke="currentColor" class="text-ivt-ink/25" stroke-width="1" />
                    <circle v-for="s in growth.series" :key="s.key" :cx="x(hover)" :cy="y(s.values[hover])" r="4" :fill="meta[s.key].color" stroke="white" stroke-width="2" />
                </g>
                <rect :x="pad.left" :y="pad.top" :width="w - pad.left - pad.right" :height="H - pad.top - pad.bottom" fill="transparent" />
            </svg>
            <div v-if="hover !== null" class="pointer-events-none absolute z-10 rounded-xl border border-ivt-line bg-white px-3 py-2 shadow-lg shadow-ivt-ink/10 text-xs" :style="tipStyle">
                <p class="font-semibold text-ivt-ink mb-1">{{ fmtDay(growth.dates[hover]) }}</p>
                <p v-for="s in growth.series" :key="s.key" class="flex items-center gap-2 text-ivt-ink-soft">
                    <span class="w-2 h-2 rounded-full" :style="{ background: meta[s.key].color }"></span>
                    {{ meta[s.key].label }}
                    <span class="ml-auto pl-3 font-semibold text-ivt-ink">{{ s.values[hover] }}</span>
                </p>
            </div>
        </div>

        <div v-else class="max-h-60 overflow-auto rounded-xl border border-ivt-line">
            <table class="w-full text-xs">
                <thead class="sticky top-0 bg-ivt-paper-2 text-left text-ivt-ink-soft">
                    <tr>
                        <th class="px-3 py-2 font-semibold">Data</th>
                        <th v-for="s in growth.series" :key="s.key" class="px-3 py-2 font-semibold text-right">{{ meta[s.key].label }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ivt-line">
                    <tr v-for="(d, i) in growth.dates" :key="d">
                        <td class="px-3 py-1.5 text-ivt-ink">{{ fmtDay(d) }}</td>
                        <td v-for="s in growth.series" :key="s.key" class="px-3 py-1.5 text-right text-ivt-ink-soft">{{ s.values[i] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
