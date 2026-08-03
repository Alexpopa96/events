<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
});

const config = {
    pending: { label: 'În așteptare', classes: 'bg-amber-50 text-amber-600' },
    active: { label: 'Activ', classes: 'bg-emerald-50 text-emerald-600' },
    rejected: { label: 'Respins', classes: 'bg-rose-50 text-rose-600' },
    suspended: { label: 'Suspendat', classes: 'bg-slate-100 text-slate-600' },
};

const current = computed(() => config[props.status] ?? { label: props.status, classes: 'bg-slate-100 text-slate-600' });
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
        :class="current.classes"
    >
        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
        {{ current.label }}
    </span>
</template>
