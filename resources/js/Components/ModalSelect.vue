<script setup>
import { computed, ref } from 'vue';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import SearchableSelectModal from '@/Components/SearchableSelectModal.vue';

const props = defineProps({
    // Plain strings or { id, name } objects.
    options: { type: Array, required: true },
    id: { type: String, default: undefined },
    title: { type: String, required: true },
    placeholder: { type: String, default: 'Alege…' },
    // When set, adds a first option with an empty value so the field can be cleared.
    clearLabel: { type: String, default: '' },
    searchable: { type: Boolean, default: false },
    searchPlaceholder: { type: String, default: 'Caută…' },
    // 'ivt' = public site palette, 'brand' = provider dashboard palette.
    theme: { type: String, default: 'ivt' },
    // Classes for the trigger button (page-specific field styling).
    buttonClass: { type: [String, Array, Object], default: '' },
});

const model = defineModel({ type: [String, Number], default: '' });
const open = ref(false);

const items = computed(() => {
    const list = props.options.map((option) => (typeof option === 'object' ? option : { id: option, name: option }));
    return props.clearLabel ? [{ id: '', name: props.clearLabel }, ...list] : list;
});

const selectedName = computed(() => (model.value === '' || model.value === null ? '' : items.value.find((item) => item.id === model.value)?.name ?? ''));

const muted = computed(() => (props.theme === 'ivt' ? 'text-ivt-ink-faint' : 'text-ivt-ink-soft'));

function select(id) {
    model.value = id;
    open.value = false;
}
</script>

<template>
    <button :id="id" type="button" aria-haspopup="dialog" class="flex w-full items-center justify-between gap-2 text-left" :class="buttonClass" @click="open = true">
        <span class="truncate" :class="selectedName ? '' : muted">{{ selectedName || placeholder }}</span>
        <ChevronDownIcon class="h-4 w-4 shrink-0" :class="muted" />
    </button>

    <SearchableSelectModal
        :show="open"
        :title="title"
        :options="items"
        :model-value="model"
        :searchable="searchable"
        :search-placeholder="searchPlaceholder"
        :theme="theme"
        @select="select"
        @close="open = false"
    />
</template>
