<script setup>
import { computed } from 'vue';

const props = defineProps({
    // [{ label, value }]
    items: { type: Array, required: true },
    color: { type: String, default: '#1F3A2C' },
});

const max = computed(() => Math.max(...props.items.map((i) => i.value), 1));
</script>

<template>
    <ul class="space-y-3">
        <li v-for="item in items" :key="item.label" class="group">
            <div class="flex items-baseline justify-between gap-3 text-sm mb-1">
                <span class="text-ivt-ink truncate">{{ item.label }}</span>
                <span class="font-semibold text-ivt-ink">{{ item.value }}</span>
            </div>
            <div class="h-2 rounded-full bg-ivt-paper-2 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500 group-hover:opacity-80" :style="{ width: `${(item.value / max) * 100}%`, background: color }"></div>
            </div>
        </li>
    </ul>
</template>
