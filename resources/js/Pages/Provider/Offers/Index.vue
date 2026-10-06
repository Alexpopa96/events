<script setup>
import { Link, router } from '@inertiajs/vue3';
import { CurrencyDollarIcon, CheckCircleIcon, XCircleIcon, ClockIcon, PhoneIcon, EnvelopeIcon } from '@heroicons/vue/24/outline';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';

const props = defineProps({
    offers: Array,
    filter: String,
    counts: Object,
});

const tabs = [
    { key: 'all', label: 'Toate' },
    { key: 'awaiting', label: 'În așteptare' },
    { key: 'accepted', label: 'Acceptate' },
    { key: 'declined', label: 'Refuzate / expirate' },
];

const setFilter = (filter) => router.get(route('provider.offers.index'), { filter }, { preserveScroll: true, replace: true });

const statusMeta = {
    sent: { label: 'Trimisă', class: 'bg-primary/10 text-primary', icon: CurrencyDollarIcon },
    viewed: { label: 'Văzută', class: 'bg-primary/10 text-primary', icon: CurrencyDollarIcon },
    accepted: { label: 'Acceptată', class: 'bg-emerald-100 text-emerald-700', icon: CheckCircleIcon },
    declined: { label: 'Refuzată', class: 'bg-rose-100 text-rose-700', icon: XCircleIcon },
    withdrawn: { label: 'Retrasă', class: 'bg-ivt-paper-2 text-ivt-ink-soft', icon: XCircleIcon },
    expired: { label: 'Expirată', class: 'bg-ivt-paper-2 text-ivt-ink-soft', icon: ClockIcon },
};
</script>

<template>
    <ProviderLayout title="Ofertele mele">
        <div class="mb-5 flex flex-wrap gap-2">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                @click="setFilter(tab.key)"
                class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-2 text-sm font-medium transition-all duration-150"
                :class="filter === tab.key
                    ? 'bg-primary text-white shadow-sm shadow-primary/25'
                    : 'bg-white text-ivt-ink-soft ring-1 ring-ivt-line hover:text-primary'"
            >
                {{ tab.label }}
                <span
                    class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1 text-[11px] font-semibold"
                    :class="filter === tab.key ? 'bg-white/20' : 'bg-ivt-paper text-ivt-ink-soft'"
                >{{ counts[tab.key] ?? (tab.key === 'all' ? counts.all : 0) }}</span>
            </button>
        </div>

        <div v-if="!offers.length" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-ivt-line bg-white px-5 py-14 text-center">
            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                <CurrencyDollarIcon class="h-6 w-6" />
            </span>
            <p class="mt-3 text-sm font-medium text-ivt-ink">Nicio ofertă aici</p>
            <p class="mt-1 max-w-sm text-sm text-ivt-ink-soft">Trimite oferte direct din „Cereri clienți”, la cererea care te interesează.</p>
            <Link :href="route('provider.leads.index')" class="mt-4 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/25 hover:bg-primary-bright">
                Vezi cererile clienților
            </Link>
        </div>

        <ul v-else class="space-y-4">
            <li v-for="offer in offers" :key="offer.id" class="rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-ivt-ink">{{ offer.lead.title }}</p>
                        <p class="text-xs text-ivt-ink-soft">{{ offer.lead.category }} · trimisă {{ offer.created_at }}</p>
                    </div>
                    <span class="inline-flex flex-none items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusMeta[offer.status].class">
                        <component :is="statusMeta[offer.status].icon" class="h-3.5 w-3.5" /> {{ statusMeta[offer.status].label }}
                    </span>
                </div>

                <div class="mt-3 flex flex-wrap items-baseline gap-x-4 gap-y-1">
                    <span class="font-serif text-2xl text-ivt-ink">{{ offer.price.toLocaleString('ro-RO') }} lei</span>
                    <span class="text-xs text-ivt-ink-soft">valabilă până la {{ offer.valid_until }}</span>
                    <span v-if="offer.listing" class="text-xs text-ivt-ink-soft">· din anunțul „{{ offer.listing.title }}”</span>
                </div>

                <ul v-if="offer.includes.length" class="mt-2 flex flex-wrap gap-1.5">
                    <li v-for="line in offer.includes" :key="line" class="rounded-full bg-ivt-paper px-2.5 py-1 text-xs text-ivt-ink-soft">{{ line }}</li>
                </ul>

                <p v-if="offer.decline_reason" class="mt-3 text-sm text-ivt-ink-soft">Motiv refuz: {{ offer.decline_reason }}</p>

                <div v-if="offer.client" class="mt-4 flex flex-wrap items-center gap-3 rounded-xl bg-emerald-50 px-4 py-3">
                    <p class="text-sm font-semibold text-emerald-800">{{ offer.client.name }}</p>
                    <a v-if="offer.client.phone" :href="`tel:${offer.client.phone}`" class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 hover:text-emerald-900">
                        <PhoneIcon class="h-4 w-4" /> {{ offer.client.phone }}
                    </a>
                    <a :href="`mailto:${offer.client.email}`" class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 hover:text-emerald-900">
                        <EnvelopeIcon class="h-4 w-4" /> {{ offer.client.email }}
                    </a>
                </div>

                <Link
                    :href="route('provider.leads.index', { cerere: offer.lead.id, tab: 'offer' })"
                    class="mt-3 inline-block text-xs font-semibold text-primary hover:text-ivt-ink"
                >
                    Vezi cererea
                </Link>
            </li>
        </ul>
    </ProviderLayout>
</template>
