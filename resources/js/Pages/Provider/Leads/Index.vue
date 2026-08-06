<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import { PhoneIcon, EnvelopeIcon, MapPinIcon, CalendarIcon, CheckCircleIcon, InboxIcon, BuildingStorefrontIcon } from '@heroicons/vue/24/outline';

defineProps({
    leads: Array,
    hasCategories: Boolean,
});

const markingContacted = ref(null);

const markContacted = (lead) => {
    if (markingContacted.value) return;
    markingContacted.value = lead.id;
    router.post(route('provider.leads.contacted', lead.id), {}, {
        preserveScroll: true,
        onFinish: () => { markingContacted.value = null; },
    });
};
</script>

<template>
    <ProviderLayout title="Cereri de ofertă">
        <p class="text-sm text-ink-soft mb-6">
            Cereri publicate de clienți în categoriile în care ai anunțuri active. Contactează-i direct — platforma nu intermediază rezervarea.
        </p>

        <div v-if="!hasCategories" class="bg-white border border-line rounded-2xl p-10 text-center shadow-sm shadow-ink/5">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                <BuildingStorefrontIcon class="h-6 w-6" />
            </span>
            <p class="mt-3 text-sm font-medium text-ink">Publică un anunț mai întâi</p>
            <p class="mt-1 text-sm text-ink-soft">Publică cel puțin un anunț ca să vezi cererile de ofertă din categoria ta.</p>
        </div>

        <div v-else-if="!leads.length" class="bg-white border border-line rounded-2xl p-10 text-center shadow-sm shadow-ink/5">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                <InboxIcon class="h-6 w-6" />
            </span>
            <p class="mt-3 text-sm font-medium text-ink">Nicio cerere deschisă momentan</p>
            <p class="mt-1 text-sm text-ink-soft">Te anunțăm imediat ce apare o cerere nouă în categoriile tale.</p>
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="lead in leads"
                :key="lead.id"
                class="bg-white border border-line rounded-2xl p-5 shadow-sm shadow-ink/5 transition-all duration-200 hover:shadow-glow-brand"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-ink">{{ lead.name }}</p>
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-white bg-gradient-to-r from-brand-500 to-brand-600 rounded-full px-2 py-0.5">
                                {{ lead.category }}
                            </span>
                        </div>
                        <p class="text-xs text-ink-soft mt-0.5">{{ lead.created_at }}</p>
                    </div>
                    <span v-if="lead.contacted" class="flex items-center gap-1 text-xs font-medium text-emerald-700">
                        <CheckCircleIcon class="w-4 h-4" /> Contactat
                    </span>
                </div>

                <p class="text-sm text-ink mt-3">{{ lead.message }}</p>

                <div class="flex flex-wrap items-center gap-4 mt-4 text-xs text-ink-soft">
                    <span v-if="lead.city" class="flex items-center gap-1"><MapPinIcon class="w-4 h-4" /> {{ lead.city }}<template v-if="lead.county">, {{ lead.county }}</template></span>
                    <span v-if="lead.event_date" class="flex items-center gap-1"><CalendarIcon class="w-4 h-4" /> {{ lead.event_date }}</span>
                    <span v-if="lead.budget_range" class="flex items-center gap-1">Buget: {{ lead.budget_range }}</span>
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-4 pt-4 border-t border-line">
                    <a
                        v-if="lead.phone"
                        :href="`tel:${lead.phone}`"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-500/25 transition-all duration-200 hover:bg-brand-600 hover:shadow-md hover:shadow-brand-500/30 hover:-translate-y-0.5 active:translate-y-0"
                    >
                        <PhoneIcon class="w-4 h-4" /> {{ lead.phone }}
                    </a>
                    <a
                        :href="`mailto:${lead.email}`"
                        class="inline-flex items-center gap-2 rounded-xl bg-white border border-line px-4 py-2 text-sm font-medium text-ink shadow-sm shadow-ink/5 transition-all duration-200 hover:bg-paper hover:shadow-md hover:-translate-y-0.5 active:translate-y-0"
                    >
                        <EnvelopeIcon class="w-4 h-4" /> Email
                    </a>
                    <button
                        v-if="!lead.contacted"
                        :disabled="markingContacted === lead.id"
                        @click="markContacted(lead)"
                        class="ml-auto inline-flex items-center gap-1.5 text-sm font-medium text-ink-soft transition-colors duration-150 hover:text-brand-600 disabled:pointer-events-none disabled:opacity-50"
                    >
                        <svg v-if="markingContacted === lead.id" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        Marchează ca și contactat
                    </button>
                </div>
            </div>
        </div>
    </ProviderLayout>
</template>
