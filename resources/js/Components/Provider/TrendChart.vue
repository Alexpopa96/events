<script setup>
import { computed, ref } from 'vue';
import { ChartBarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    data: {
        type: Array,
        required: true, // [{ date, views, phone_clicks, whatsapp_clicks }]
    },
});

const series = [
    { key: 'views', label: 'Vizualizări', color: '#047857' },
    { key: 'phone_clicks', label: 'Click-uri telefon', color: '#F59E0B' },
    { key: 'whatsapp_clicks', label: 'Click-uri WhatsApp', color: '#10B981' },
];

const width = 720;
const height = 220;
const padding = { top: 12, right: 8, bottom: 24, left: 30 };
const innerWidth = width - padding.left - padding.right;
const innerHeight = height - padding.top - padding.bottom;

const maxValue = computed(() => {
    const max = Math.max(1, ...props.data.flatMap((d) => series.map((s) => d[s.key])));
    const step = max <= 10 ? 2 : max <= 50 ? 10 : max <= 200 ? 25 : 50;
    return Math.ceil(max / step) * step;
});

const yTicks = computed(() => {
    const count = 4;
    return Array.from({ length: count + 1 }, (_, i) => Math.round((maxValue.value / count) * i));
});

const xFor = (index) => padding.left + (innerWidth * index) / Math.max(props.data.length - 1, 1);
const yFor = (value) => padding.top + innerHeight - (innerHeight * value) / maxValue.value;

const linePath = (key) =>
    props.data.map((d, i) => `${i === 0 ? 'M' : 'L'} ${xFor(i).toFixed(1)} ${yFor(d[key]).toFixed(1)}`).join(' ');

const areaPath = (key) => {
    if (!props.data.length) return '';
    const baseline = height - padding.bottom;
    return `${linePath(key)} L ${xFor(props.data.length - 1).toFixed(1)} ${baseline} L ${xFor(0).toFixed(1)} ${baseline} Z`;
};

const gradientId = `trend-${Math.random().toString(36).slice(2, 9)}`;

const hasData = computed(() => props.data.length > 0 && props.data.some((d) => series.some((s) => d[s.key] > 0)));

const xLabelIndexes = computed(() => {
    const n = props.data.length;
    const count = Math.min(6, n);
    return Array.from({ length: count }, (_, i) => Math.round((i * (n - 1)) / (count - 1)));
});

const svgEl = ref(null);
const hoverIndex = ref(null);

const onPointerMove = (event) => {
    if (!svgEl.value || !props.data.length) return;

    const rect = svgEl.value.getBoundingClientRect();
    const relX = ((event.clientX - rect.left) / rect.width) * width;
    const clamped = Math.min(Math.max(relX, padding.left), padding.left + innerWidth);
    const index = Math.round(((clamped - padding.left) / innerWidth) * (props.data.length - 1));
    hoverIndex.value = Math.min(Math.max(index, 0), props.data.length - 1);
};

const onPointerLeave = () => {
    hoverIndex.value = null;
};

const hoverX = computed(() => (hoverIndex.value === null ? 0 : xFor(hoverIndex.value)));
const hoverPoint = computed(() => (hoverIndex.value === null ? null : props.data[hoverIndex.value]));

const tooltipStyle = computed(() => {
    if (hoverIndex.value === null) return {};
    const leftPct = (hoverX.value / width) * 100;
    const flip = leftPct > 62;
    return {
        left: `${leftPct}%`,
        top: '0px',
        transform: flip ? 'translate(calc(-100% - 10px), 0)' : 'translate(10px, 0)',
    };
});
</script>

<template>
    <div v-if="!hasData" class="flex h-56 flex-col items-center justify-center text-center">
        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand-500">
            <ChartBarIcon class="h-5 w-5" />
        </span>
        <p class="mt-3 text-sm font-medium text-ink">Încă nu sunt date suficiente</p>
        <p class="mt-1 text-xs text-ink-soft">Graficul va apărea imediat ce anunțurile tale primesc vizualizări.</p>
    </div>

    <div v-else class="relative select-none">
        <svg
            ref="svgEl"
            :viewBox="`0 0 ${width} ${height}`"
            preserveAspectRatio="none"
            class="w-full h-56"
            @pointermove="onPointerMove"
            @pointerleave="onPointerLeave"
        >
            <defs>
                <linearGradient v-for="s in series" :id="`${gradientId}-${s.key}`" :key="`grad-${s.key}`" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" :stop-color="s.color" stop-opacity="0.22" />
                    <stop offset="100%" :stop-color="s.color" stop-opacity="0" />
                </linearGradient>
            </defs>

            <!-- area fills -->
            <path
                v-for="s in series"
                :key="`area-${s.key}`"
                :d="areaPath(s.key)"
                :fill="`url(#${gradientId}-${s.key})`"
                stroke="none"
            />

            <!-- gridlines -->
            <line
                v-for="tick in yTicks"
                :key="`grid-${tick}`"
                :x1="padding.left"
                :x2="width - padding.right"
                :y1="yFor(tick)"
                :y2="yFor(tick)"
                stroke="#E6DFE7"
                stroke-width="1"
            />

            <!-- y-axis labels -->
            <text
                v-for="tick in yTicks"
                :key="`ylabel-${tick}`"
                :x="padding.left - 8"
                :y="yFor(tick) + 3"
                text-anchor="end"
                class="fill-ink-soft"
                font-size="10"
            >{{ tick }}</text>

            <!-- x-axis labels -->
            <text
                v-for="index in xLabelIndexes"
                :key="`xlabel-${index}`"
                :x="xFor(index)"
                :y="height - 6"
                text-anchor="middle"
                class="fill-ink-soft"
                font-size="10"
            >{{ data[index]?.date }}</text>

            <!-- series lines -->
            <path
                v-for="s in series"
                :key="s.key"
                :d="linePath(s.key)"
                fill="none"
                :stroke="s.color"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            />

            <!-- crosshair + hover markers -->
            <template v-if="hoverPoint">
                <line
                    :x1="hoverX"
                    :x2="hoverX"
                    :y1="padding.top"
                    :y2="height - padding.bottom"
                    stroke="#B7AFC0"
                    stroke-width="1"
                />
                <circle
                    v-for="s in series"
                    :key="`marker-${s.key}`"
                    :cx="hoverX"
                    :cy="yFor(hoverPoint[s.key])"
                    r="4"
                    :fill="s.color"
                    stroke="#FAF8FB"
                    stroke-width="2"
                />
            </template>
        </svg>

        <!-- tooltip -->
        <div
            v-if="hoverPoint"
            class="absolute top-0 z-10 pointer-events-none bg-white border border-line rounded-xl shadow-lg px-3 py-2 min-w-[9rem]"
            :style="tooltipStyle"
        >
            <p class="text-xs font-semibold text-ink mb-1.5">{{ hoverPoint.date }}</p>
            <div v-for="s in series" :key="`tt-${s.key}`" class="flex items-center justify-between gap-3 text-xs py-0.5">
                <span class="flex items-center gap-1.5 text-ink-soft">
                    <span class="inline-block w-2.5 h-0.5 rounded-full" :style="{ backgroundColor: s.color }"></span>
                    {{ s.label }}
                </span>
                <span class="font-semibold text-ink tabular-nums">{{ hoverPoint[s.key] }}</span>
            </div>
        </div>

        <!-- legend -->
        <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 mt-3 pt-3 border-t border-line">
            <span v-for="s in series" :key="`legend-${s.key}`" class="flex items-center gap-1.5 text-xs text-ink-soft">
                <span class="inline-block w-2.5 h-0.5 rounded-full" :style="{ backgroundColor: s.color }"></span>
                {{ s.label }}
            </span>
        </div>
    </div>
</template>
