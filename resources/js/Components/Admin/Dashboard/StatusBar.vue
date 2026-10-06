<script setup>
import { computed } from 'vue';

const props = defineProps({
    // [{ key, label, value }]
    items: { type: Array, required: true },
    colors: { type: Object, default: () => ({}) },
});

const total = computed(() => props.items.reduce((sum, i) => sum + i.value, 0));
const pct = (v) => (total.value ? Math.round((v / total.value) * 100) : 0);
</script>

<template>
    <div>
        <div class="flex items-end justify-between mb-3">
            <p class="text-3xl font-semibold text-ivt-ink leading-none">{{ total }}</p>
            <p class="text-xs text-ivt-ink-soft">în total</p>
        </div>
        <div class="flex h-2.5 gap-0.5 rounded-full overflow-hidden bg-ivt-paper-2" role="img" :aria-label="items.map((i) => `${i.label}: ${i.value}`).join(', ')">
            <template v-for="item in items" :key="item.key">
                <div
                    v-if="item.value"
                    :title="`${item.label}: ${item.value}`"
                    class="h-full first:rounded-l-full last:rounded-r-full transition-[flex-grow] duration-500"
                    :style="{ flexGrow: item.value, flexBasis: 0, background: colors[item.key] }"
                ></div>
            </template>
        </div>
        <ul class="mt-4 space-y-2">
            <li v-for="item in items" :key="item.key" class="flex items-center gap-2.5 text-sm">
                <span class="w-2.5 h-2.5 rounded-sm flex-none" :style="{ background: colors[item.key] }"></span>
                <span class="text-ivt-ink-soft">{{ item.label }}</span>
                <span class="ml-auto font-semibold text-ivt-ink">{{ item.value }}</span>
                <span class="w-9 text-right text-xs text-ivt-ink-faint">{{ pct(item.value) }}%</span>
            </li>
        </ul>
    </div>
</template>
