<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    counties: { type: Array, required: true },
    countyError: { type: String, default: '' },
    localityError: { type: String, default: '' },
});

const countyId = defineModel('countyId');
const localityId = defineModel('localityId');

const localities = ref([]);
const loadingLocalities = ref(false);

const selectClasses = 'w-full rounded-full border border-line bg-paper/60 px-4 py-3 text-sm text-ink transition-all duration-150 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:opacity-60 disabled:cursor-not-allowed';

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
});

if (countyId.value) {
    loadLocalities(countyId.value);
}
</script>

<template>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="county_id" class="block text-sm font-medium text-ink mb-1.5">Județ</label>
            <select
                id="county_id"
                v-model="countyId"
                :class="[selectClasses, { 'border-red-400 focus:border-red-400 focus:ring-red-400/20': countyError }]"
            >
                <option :value="null" disabled>Alege județul</option>
                <option v-for="county in counties" :key="county.id" :value="county.id">{{ county.name }}</option>
            </select>
            <InputError class="mt-1.5" :message="countyError" />
        </div>

        <div>
            <label for="locality_id" class="block text-sm font-medium text-ink mb-1.5">Localitate</label>
            <select
                id="locality_id"
                v-model="localityId"
                :disabled="!countyId || loadingLocalities"
                :class="[selectClasses, { 'border-red-400 focus:border-red-400 focus:ring-red-400/20': localityError }]"
            >
                <option :value="null" disabled>{{ loadingLocalities ? 'Se încarcă…' : 'Alege localitatea' }}</option>
                <option v-for="locality in localities" :key="locality.id" :value="locality.id">{{ locality.name }}</option>
            </select>
            <InputError class="mt-1.5" :message="localityError" />
        </div>
    </div>
</template>
