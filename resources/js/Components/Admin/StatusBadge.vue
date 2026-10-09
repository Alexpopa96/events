<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
});

const config = {
    pending: { label: 'În așteptare', classes: 'bg-warning-50 text-warning-600' },
    active: { label: 'Activ', classes: 'bg-success-50 text-success-600' },
    rejected: { label: 'Respins', classes: 'bg-danger-50 text-danger-600' },
    suspended: { label: 'Suspendat', classes: 'bg-ivt-paper-2 text-ivt-ink-soft' },
    pending_review: { label: 'În așteptare', classes: 'bg-warning-50 text-warning-600' },
    approved: { label: 'Aprobată', classes: 'bg-success-50 text-success-600' },
    open: { label: 'Aprobată', classes: 'bg-success-50 text-success-600' },
    closed: { label: 'Închisă', classes: 'bg-ivt-paper-2 text-ivt-ink-soft' },
};

const current = computed(() => config[props.status] ?? { label: props.status, classes: 'bg-ivt-paper-2 text-ivt-ink-soft' });
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
