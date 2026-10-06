<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import InputError from '@/Components/InputError.vue';
import SearchableSelectModal from '@/Components/SearchableSelectModal.vue';

const props = defineProps({
    counties: { type: Array, required: true },
    countyError: { type: String, default: '' },
    localityError: { type: String, default: '' },
    // 'ivt' = public site palette, 'brand' = provider dashboard palette.
    theme: { type: String, default: 'ivt' },
});

const countyId = defineModel('countyId');
const localityId = defineModel('localityId');

const localities = ref([]);
const loadingLocalities = ref(false);

const countyModalOpen = ref(false);
const localityModalOpen = ref(false);
// Set when the user picks a county so the locality picker opens right after
// its options finish loading, instead of making them click a second time.
const openLocalityAfterLoad = ref(false);

const selectedCountyName = computed(() => props.counties.find((county) => county.id === countyId.value)?.name ?? '');
const selectedLocalityName = computed(() => localities.value.find((locality) => locality.id === localityId.value)?.name ?? '');

const triggerThemes = {
    ivt: {
        base: 'border-ivt-line bg-white text-ivt-ink hover:border-ivt-gold/60 focus:border-ivt-gold focus:ring-ivt-gold/20',
        placeholder: 'text-ivt-ink-faint',
        chevron: 'text-ivt-ink-faint',
        label: 'text-ivt-ink',
    },
    brand: {
        base: 'border-transparent bg-ivt-paper-2 text-ivt-ink hover:bg-ivt-paper-3/70 focus:border-primary focus:bg-white focus:ring-primary/10',
        placeholder: 'text-ivt-ink-soft/60',
        chevron: 'text-ivt-ink-soft',
        label: 'text-ivt-ink',
    },
};

const trigger = computed(() => triggerThemes[props.theme] ?? triggerThemes.ivt);

const triggerClasses = computed(() => [
    'flex w-full items-center justify-between gap-2 border px-4 text-left text-sm transition-all duration-150 focus:outline-none disabled:opacity-60 disabled:cursor-not-allowed',
    // The provider area uses the same rounded-xl fields as every other form; the public site keeps pills.
    props.theme === 'brand' ? 'rounded-xl border-2 py-3 focus:ring-4' : 'rounded-full py-3 focus:ring-2',
]);

function selectCounty(id) {
    countyModalOpen.value = false;
    if (id === countyId.value) return;
    openLocalityAfterLoad.value = true;
    countyId.value = id;
}

function selectLocality(id) {
    localityId.value = id;
    localityModalOpen.value = false;
}

async function loadLocalities(judetId) {
    if (!judetId) {
        localities.value = [];
        return;
    }

    loadingLocalities.value = true;
    try {
        const { data } = await axios.get('/localitati', { params: { judet_id: judetId } });
        localities.value = data;
    } finally {
        loadingLocalities.value = false;
    }
}

watch(countyId, async (value) => {
    await loadLocalities(value);

    if (! localities.value.some((locality) => locality.id === localityId.value)) {
        localityId.value = null;
    }

    if (openLocalityAfterLoad.value && value) {
        localityModalOpen.value = true;
    }
    openLocalityAfterLoad.value = false;
});

if (countyId.value) {
    loadLocalities(countyId.value);
}
</script>

<template>
    <div class="grid gap-4" :class="theme === 'brand' ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-2'">
        <div>
            <label for="county_id" class="block text-sm font-medium mb-1.5" :class="trigger.label">Județ</label>
            <button
                id="county_id"
                type="button"
                aria-haspopup="dialog"
                :class="[triggerClasses, trigger.base, { 'border-red-400 focus:border-red-400 focus:ring-red-400/20': countyError }]"
                @click="countyModalOpen = true"
            >
                <span class="truncate" :class="selectedCountyName ? '' : trigger.placeholder">{{ selectedCountyName || 'Alege județul' }}</span>
                <ChevronDownIcon class="h-4 w-4 shrink-0" :class="trigger.chevron" />
            </button>
            <InputError class="mt-1.5" :message="countyError" />
        </div>

        <div>
            <label for="locality_id" class="block text-sm font-medium mb-1.5" :class="trigger.label">Localitate</label>
            <button
                id="locality_id"
                type="button"
                aria-haspopup="dialog"
                :disabled="!countyId || loadingLocalities"
                :class="[triggerClasses, trigger.base, { 'border-red-400 focus:border-red-400 focus:ring-red-400/20': localityError }]"
                @click="localityModalOpen = true"
            >
                <span class="truncate" :class="selectedLocalityName ? '' : trigger.placeholder">
                    {{ loadingLocalities ? 'Se încarcă…' : (selectedLocalityName || 'Alege localitatea') }}
                </span>
                <ChevronDownIcon class="h-4 w-4 shrink-0" :class="trigger.chevron" />
            </button>
            <InputError class="mt-1.5" :message="localityError" />
        </div>
    </div>

    <SearchableSelectModal
        :show="countyModalOpen"
        title="Alege județul"
        search-placeholder="Caută județul…"
        :options="counties"
        :model-value="countyId"
        :theme="theme"
        @select="selectCounty"
        @close="countyModalOpen = false"
    />

    <SearchableSelectModal
        :show="localityModalOpen"
        :title="selectedCountyName ? `Alege localitatea — ${selectedCountyName}` : 'Alege localitatea'"
        search-placeholder="Caută localitatea…"
        :options="localities"
        :model-value="localityId"
        :theme="theme"
        :loading="loadingLocalities"
        @select="selectLocality"
        @close="localityModalOpen = false"
    />
</template>
