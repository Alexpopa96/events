<script setup>
import { computed, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import ListingPost from '@/Components/Provider/ListingPost.vue';
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

const perfById = computed(() => Object.fromEntries(props.performance.map((row) => [row.id, row])));

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
        <!-- Quota -->
        <div v-if="quota.max !== null" class="mb-4 flex flex-wrap items-center gap-x-6 gap-y-2 rounded-2xl border border-ivt-line bg-white px-5 py-3.5 shadow-sm shadow-ivt-ink/5">
            <p class="text-sm font-semibold text-ivt-ink">Anunțuri folosite</p>
            <div class="h-1.5 min-w-[8rem] flex-1 overflow-hidden rounded-full bg-ivt-paper-2">
                <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="quotaReached ? 'bg-rose-500' : 'bg-gradient-to-r from-primary-bright to-ivt-gold-bright'"
                    :style="`width: ${Math.min(100, (quota.used / quota.max) * 100)}%`"
                ></div>
            </div>
            <p class="text-xs" :class="quotaReached ? 'font-medium text-rose-600' : 'text-ivt-ink-soft'">
                {{ quota.used }} / {{ quota.max }} din planul {{ quota.plan_name }}
            </p>
            <Link v-if="quotaReached" :href="route('provider.subscription.index')" class="text-xs font-semibold text-primary hover:underline">Mărește planul</Link>
        </div>

        <!-- Filters -->
        <div v-if="listings.length" class="sticky top-14 sm:top-16 z-10 -mx-3 sm:-mx-4 lg:mx-0 mb-4 flex items-center gap-1.5 overflow-x-auto bg-ivt-paper-2/90 px-3 sm:px-4 lg:px-0 py-2 backdrop-blur">
            <button
                v-for="filter in filters"
                :key="filter.value"
                @click="activeFilter = filter.value"
                class="flex-none inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-all duration-150"
                :class="activeFilter === filter.value ? 'bg-primary text-white shadow-sm shadow-primary/25' : 'bg-white border border-ivt-line text-ivt-ink-soft hover:text-ivt-ink hover:border-ivt-gold/60'"
            >
                <component :is="filter.icon" class="h-3.5 w-3.5" />
                {{ filter.label }}
                <span
                    class="rounded-full px-1.5 text-[11px] tabular-nums"
                    :class="activeFilter === filter.value ? 'bg-white/20' : 'bg-ivt-paper'"
                >{{ counts[filter.value] ?? 0 }}</span>
            </button>
        </div>

        <!-- Performance analytics -->
        <div v-if="listings.length" class="bg-white border border-ivt-line rounded-2xl shadow-sm shadow-ivt-ink/5 mb-4 overflow-hidden">
            <button
                type="button"
                @click="showPerformance = !showPerformance"
                class="w-full flex items-center justify-between gap-3 px-4 py-3.5 text-left"
            >
                <span class="flex items-center gap-2 text-sm font-semibold text-ivt-ink">
                    <span class="w-7 h-7 rounded-full bg-ivt-paper-2 text-primary flex items-center justify-center flex-none"><ChartBarIcon class="w-4 h-4" /></span>
                    Analiză performanță (ultimele 30 de zile)
                </span>
                <ChevronDownIcon class="w-4 h-4 text-ivt-ink-soft transition-transform duration-200" :class="showPerformance && 'rotate-180'" />
            </button>

            <div v-if="showPerformance" class="px-4 pb-5 border-t border-ivt-line pt-4">
                <TrendChart :data="trend" />

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-ivt-ink-soft/70 uppercase tracking-wide">
                                <th class="pb-2 pr-4">Anunț</th>
                                <th class="pb-2 pr-4">Status</th>
                                <th class="pb-2 pr-4 text-right">Vizualizări</th>
                                <th class="pb-2 pr-4 text-right">Tel.</th>
                                <th class="pb-2 pr-4 text-right">WhatsApp</th>
                                <th class="pb-2 text-right">Conversie</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ivt-line">
                            <tr v-for="row in performance" :key="row.id">
                                <td class="py-2.5 pr-4 font-medium text-ivt-ink truncate max-w-xs">{{ row.title }}</td>
                                <td class="py-2.5 pr-4 text-ivt-ink-soft">{{ statusLabels[row.status] ?? row.status }}</td>
                                <td class="py-2.5 pr-4 text-right tabular-nums text-ivt-ink">{{ row.views_count }}</td>
                                <td class="py-2.5 pr-4 text-right tabular-nums text-ivt-ink-soft">{{ row.phone_clicks }}</td>
                                <td class="py-2.5 pr-4 text-right tabular-nums text-ivt-ink-soft">{{ row.whatsapp_clicks }}</td>
                                <td class="py-2.5 text-right tabular-nums font-medium text-primary">{{ row.conversion_rate }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="!listings.length" class="bg-white border border-ivt-line rounded-2xl py-16 px-6 text-center shadow-sm shadow-ivt-ink/5">
            <div class="w-14 h-14 rounded-full bg-ivt-paper-2 flex items-center justify-center mx-auto mb-4">
                <Squares2X2Icon class="w-7 h-7 text-primary" />
            </div>
            <p class="text-sm font-medium text-ivt-ink mb-1">Nu ai încă nicio postare.</p>
            <p class="text-sm text-ivt-ink-soft mb-5">Creează primul tău anunț ca să apari în fața clienților.</p>
            <Link
                :href="route('provider.listings.create')"
                class="inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/25 transition-all duration-200 hover:bg-primary-bright hover:shadow-md hover:shadow-primary/30"
            >
                <PlusIcon class="w-4 h-4" /> Creează primul tău anunț
            </Link>
        </div>

        <div v-else-if="!filteredListings.length" class="bg-white border border-ivt-line rounded-2xl py-14 text-center shadow-sm shadow-ivt-ink/5">
            <p class="text-sm text-ivt-ink-soft">Niciun anunț în această categorie.</p>
        </div>

        <div v-else class="grid grid-cols-1 items-start gap-4 md:grid-cols-2 xl:grid-cols-3">
            <ListingPost
                v-for="listing in filteredListings"
                :key="listing.id"
                :listing="listing"
                :stats="perfById[listing.id]"
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
