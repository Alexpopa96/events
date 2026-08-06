<script setup>
import { computed, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import ListingCard from '@/Components/Provider/ListingCard.vue';
import TrendChart from '@/Components/Provider/TrendChart.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import {
    PlusIcon,
    Squares2X2Icon,
    ChartBarIcon,
    ChevronDownIcon,
    PencilSquareIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArchiveBoxIcon,
} from '@heroicons/vue/24/outline';

const toast = useToast();

const props = defineProps({
    listings: Array,
    quota: Object,
    trend: Array,
    performance: Array,
});

const filters = [
    { value: 'all', label: 'Toate', icon: Squares2X2Icon },
    { value: 'draft', label: 'Ciornă', icon: PencilSquareIcon },
    { value: 'pending_review', label: 'În verificare', icon: ClockIcon },
    { value: 'published', label: 'Publicate', icon: CheckCircleIcon },
    { value: 'rejected', label: 'Respinse', icon: XCircleIcon },
    { value: 'archived', label: 'Arhivate', icon: ArchiveBoxIcon },
];

const activeFilter = ref('all');
const showPerformance = ref(false);

const statusLabels = {
    draft: 'Ciornă',
    pending_review: 'În verificare',
    published: 'Publicat',
    rejected: 'Respins',
    archived: 'Arhivat',
};

const counts = computed(() => {
    const map = { all: props.listings.length };
    for (const listing of props.listings) {
        map[listing.status] = (map[listing.status] ?? 0) + 1;
    }
    return map;
});

const filteredListings = computed(() => {
    if (activeFilter.value === 'all') {
        return props.listings;
    }
    return props.listings.filter((listing) => listing.status === activeFilter.value);
});

const quotaReached = computed(() => props.quota.max !== null && props.quota.used >= props.quota.max);

const listingToDelete = ref(null);
const deleting = ref(false);

const destroy = (listing) => {
    listingToDelete.value = listing;
};

const confirmDestroy = () => {
    deleting.value = true;
    router.delete(route('provider.listings.destroy', listingToDelete.value.id), {
        onSuccess: () => toast.success('Anunțul a fost șters.'),
        onFinish: () => {
            deleting.value = false;
            listingToDelete.value = null;
        },
    });
};
</script>

<template>
    <ProviderLayout title="Anunțurile mele">
        <template #actions>
            <Link
                :href="route('provider.listings.create')"
                class="group relative inline-flex items-center gap-2 overflow-hidden rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-500/25 transition-all duration-200 hover:bg-brand-600 hover:shadow-glow-brand hover:-translate-y-0.5 active:translate-y-0"
            >
                <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/25 to-transparent transition-transform duration-700 group-hover:translate-x-full"></span>
                <PlusIcon class="relative w-4 h-4" /> <span class="relative">Anunț nou</span>
            </Link>
        </template>

        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div class="lg:hidden">
                <Link
                    :href="route('provider.listings.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-500/25 transition-all duration-200 hover:bg-brand-600 hover:shadow-glow-brand hover:-translate-y-0.5 active:translate-y-0"
                >
                    <PlusIcon class="w-4 h-4" /> Anunț nou
                </Link>
            </div>
            <div class="min-w-[14rem]">
                <p class="text-sm text-ink-soft">{{ listings.length }} {{ listings.length === 1 ? 'anunț' : 'anunțuri' }} în total</p>
                <template v-if="quota.max !== null">
                    <p class="text-xs mt-0.5" :class="quotaReached ? 'text-rose-600 font-medium' : 'text-ink-soft'">
                        {{ quota.used }} / {{ quota.max }} folosite din planul {{ quota.plan_name }}
                    </p>
                    <div class="mt-1.5 h-1.5 w-full max-w-[14rem] rounded-full bg-line/70 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="quotaReached ? 'bg-rose-500' : 'bg-gradient-to-r from-brand-500 to-gold-400'"
                            :style="`width: ${Math.min(100, (quota.used / quota.max) * 100)}%`"
                        ></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Performance analytics -->
        <div v-if="listings.length" class="bg-white border border-line rounded-2xl shadow-sm shadow-ink/5 mb-6 overflow-hidden">
            <button
                type="button"
                @click="showPerformance = !showPerformance"
                class="w-full flex items-center justify-between gap-3 px-6 py-4 text-left"
            >
                <span class="flex items-center gap-2 text-sm font-semibold text-ink">
                    <span class="w-7 h-7 rounded-lg bg-brand-50 text-brand-500 flex items-center justify-center flex-none"><ChartBarIcon class="w-4 h-4" /></span>
                    Analiză performanță (ultimele 30 de zile)
                </span>
                <ChevronDownIcon class="w-4 h-4 text-ink-soft transition-transform duration-200" :class="showPerformance && 'rotate-180'" />
            </button>

            <div v-if="showPerformance" class="px-6 pb-6 border-t border-line pt-5">
                <TrendChart :data="trend" />

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-ink-soft/70 uppercase tracking-wide">
                                <th class="pb-2 pr-4">Anunț</th>
                                <th class="pb-2 pr-4">Status</th>
                                <th class="pb-2 pr-4 text-right">Vizualizări</th>
                                <th class="pb-2 pr-4 text-right">Tel.</th>
                                <th class="pb-2 pr-4 text-right">WhatsApp</th>
                                <th class="pb-2 text-right">Conversie</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <tr v-for="row in performance" :key="row.id">
                                <td class="py-2.5 pr-4 font-medium text-ink truncate max-w-xs">{{ row.title }}</td>
                                <td class="py-2.5 pr-4 text-ink-soft">{{ statusLabels[row.status] ?? row.status }}</td>
                                <td class="py-2.5 pr-4 text-right tabular-nums text-ink">{{ row.views_count }}</td>
                                <td class="py-2.5 pr-4 text-right tabular-nums text-ink-soft">{{ row.phone_clicks }}</td>
                                <td class="py-2.5 pr-4 text-right tabular-nums text-ink-soft">{{ row.whatsapp_clicks }}</td>
                                <td class="py-2.5 text-right tabular-nums font-medium text-brand-600">{{ row.conversion_rate }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="listings.length" class="flex items-center gap-1.5 mb-6 overflow-x-auto pb-1">
            <button
                v-for="filter in filters"
                :key="filter.value"
                @click="activeFilter = filter.value"
                class="flex-none inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-all duration-150"
                :class="activeFilter === filter.value ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-sm shadow-brand-500/25' : 'bg-white border border-line text-ink-soft hover:text-ink hover:border-gold-400/60'"
            >
                <component :is="filter.icon" class="h-3.5 w-3.5" />
                {{ filter.label }}
                <span
                    class="rounded-full px-1.5 text-[11px] tabular-nums"
                    :class="activeFilter === filter.value ? 'bg-white/20' : 'bg-paper'"
                >{{ counts[filter.value] ?? 0 }}</span>
            </button>
        </div>

        <div v-if="!listings.length" class="bg-white border border-line rounded-2xl py-20 text-center shadow-sm shadow-ink/5">
            <div class="w-14 h-14 rounded-2xl bg-brand-50 flex items-center justify-center mx-auto mb-4">
                <Squares2X2Icon class="w-7 h-7 text-brand-500" />
            </div>
            <p class="text-sm font-medium text-ink mb-1">Nu ai încă niciun anunț.</p>
            <p class="text-sm text-ink-soft mb-5">Creează primul tău anunț ca să apari în fața clienților.</p>
            <Link
                :href="route('provider.listings.create')"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-500/25 transition-all duration-200 hover:bg-brand-600 hover:shadow-md hover:shadow-brand-500/30"
            >
                <PlusIcon class="w-4 h-4" /> Creează primul tău anunț
            </Link>
        </div>

        <div v-else-if="!filteredListings.length" class="bg-white border border-line rounded-2xl py-16 text-center shadow-sm shadow-ink/5">
            <p class="text-sm text-ink-soft">Niciun anunț în această categorie.</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            <ListingCard
                v-for="listing in filteredListings"
                :key="listing.id"
                :listing="listing"
                @delete="destroy"
            />
        </div>

        <ConfirmDialog
            :show="!!listingToDelete"
            @update:show="(v) => !v && (listingToDelete = null)"
            title="Ștergi acest anunț?"
            :message="listingToDelete ? `Ștergi anunțul „${listingToDelete.title}”? Nu poate fi anulat.` : ''"
            confirm-label="Șterge"
            :processing="deleting"
            @confirm="confirmDestroy"
        />
    </ProviderLayout>
</template>
