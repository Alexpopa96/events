<script setup>
import { computed, onMounted, reactive } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import TrendChart from '@/Components/Provider/TrendChart.vue';
import { animateCountUp } from '@/Composables/useCountUp';
import {
    EyeIcon,
    PhoneIcon,
    ChatBubbleOvalLeftEllipsisIcon,
    ClipboardDocumentListIcon,
    CheckCircleIcon,
    DocumentTextIcon,
    BuildingStorefrontIcon,
    UserCircleIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/24/solid';
import { ivt, primary } from '@/palette';

const props = defineProps({
    profile: Object,
    subscription: Object,
    lastInvoice: Object,
    stats: Object,
    trend: Array,
    listings: Array,
    reviews: Object,
    leads: Array,
});

const page = usePage();
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);

const statCards = computed(() => [
    { key: 'views', label: 'Vizualizări anunțuri', value: props.stats?.views ?? 0, icon: EyeIcon },
    { key: 'phone_clicks', label: 'Click-uri telefon', value: props.stats?.phone_clicks ?? 0, icon: PhoneIcon },
    { key: 'whatsapp_clicks', label: 'Click-uri WhatsApp', value: props.stats?.whatsapp_clicks ?? 0, icon: ChatBubbleOvalLeftEllipsisIcon },
    { key: 'quote_interest', label: 'Interes cereri de ofertă', value: props.stats?.quote_interest ?? 0, icon: ClipboardDocumentListIcon },
]);

const displayedStats = reactive({});

onMounted(() => {
    statCards.value.forEach((card) => {
        displayedStats[card.key] = 0;
        animateCountUp(card.value, (v) => (displayedStats[card.key] = v));
    });
});

const statusMeta = {
    draft: { label: 'Ciornă', class: 'bg-ivt-paper-2 text-ivt-ink-soft' },
    pending_review: { label: 'În verificare', class: 'bg-ivt-violet/20 text-ivt-violet' },
    published: { label: 'Publicat', class: 'bg-success-100 text-success-700' },
    rejected: { label: 'Respins', class: 'bg-danger-100 text-danger-700' },
    archived: { label: 'Arhivat', class: 'bg-ivt-paper-2 text-ivt-ink-soft' },
};

const subscriptionStatusMeta = {
    active: { label: 'Activ', class: 'bg-success-100 text-success-700' },
    past_due: { label: 'Plată restantă', class: 'bg-danger-100 text-danger-700' },
    canceled: { label: 'Anulat', class: 'bg-ivt-paper-2 text-ivt-ink-soft' },
    expired: { label: 'Expirat', class: 'bg-ivt-paper-2 text-ivt-ink-soft' },
};

const invoiceStatusMeta = {
    paid: { label: 'Plătită', class: 'bg-success-100 text-success-700' },
    pending: { label: 'În așteptare', class: 'bg-ivt-violet/20 text-ivt-violet' },
    failed: { label: 'Eșuată', class: 'bg-danger-100 text-danger-700' },
    refunded: { label: 'Rambursată', class: 'bg-ivt-paper-2 text-ivt-ink-soft' },
};

const completionDeg = computed(() => Math.round((props.profile?.completion_score ?? 0) * 3.6));
</script>

<template>
    <ProviderLayout title="Prezentare">
        <div v-if="!profile" class="max-w-lg mx-auto text-center py-20">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-ivt-paper-2 text-primary">
                <BuildingStorefrontIcon class="h-6 w-6" />
            </span>
            <h2 class="font-display text-2xl text-ivt-ink mt-4 mb-2">Profilul tău e aproape gata</h2>
            <p class="text-sm text-ivt-ink-soft">Nu am găsit încă un profil de companie asociat contului tău. Contactează-ne dacă vezi acest mesaj — ar trebui să fie creat automat la înregistrare.</p>
        </div>

        <template v-else>
            <div class="relative rounded-3xl mb-8 overflow-hidden">
                <div class="absolute -top-16 -left-10 w-72 h-72 rounded-full bg-ivt-violet/10 blur-3xl pointer-events-none" aria-hidden="true"></div>

                <div class="relative flex flex-wrap items-start justify-between gap-5">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-ivt-violet mb-1">{{ profile.company_name }}</p>
                        <h2 class="font-display text-3xl sm:text-4xl text-ivt-ink leading-tight">Bună, {{ firstName }}</h2>
                        <span class="mt-3 block h-0.5 w-16 rounded-full bg-gradient-to-r from-primary-bright to-ivt-accent-bright"></span>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <div v-if="subscription" class="flex items-center gap-3 bg-gradient-to-br from-white to-ivt-paper-2/40 border border-ivt-line rounded-2xl px-4 py-3 shadow-sm shadow-ivt-ink/5">
                            <div>
                                <p class="text-xs text-ivt-ink-soft">Abonament</p>
                                <p class="text-sm font-semibold text-ivt-ink">{{ subscription.plan_name }}</p>
                            </div>
                            <span class="text-xs font-medium rounded-full px-2.5 py-1" :class="subscriptionStatusMeta[subscription.status]?.class">
                                {{ subscriptionStatusMeta[subscription.status]?.label ?? subscription.status }}
                            </span>
                            <p v-if="subscription.ends_at" class="text-xs text-ivt-ink-soft border-l border-ivt-line pl-3 ml-1">
                                Se reînnoiește la {{ subscription.ends_at }}
                            </p>
                        </div>
                        <div v-else class="bg-white border border-ivt-line rounded-2xl px-4 py-3 text-sm text-ivt-ink-soft shadow-sm shadow-ivt-ink/5">
                            Niciun abonament activ
                        </div>

                        <div v-if="lastInvoice" class="flex items-center gap-3 bg-gradient-to-br from-white to-ivt-paper-2/40 border border-ivt-line rounded-2xl px-4 py-3 shadow-sm shadow-ivt-ink/5">
                            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-bright to-primary text-white flex items-center justify-center flex-none shadow-sm shadow-primary/20">
                                <DocumentTextIcon class="w-5 h-5" />
                            </span>
                            <div>
                                <p class="text-xs text-ivt-ink-soft">Ultima factură · {{ lastInvoice.number }}</p>
                                <p class="text-sm font-semibold text-ivt-ink">{{ lastInvoice.amount }} {{ lastInvoice.currency }}</p>
                            </div>
                            <span class="text-xs font-medium rounded-full px-2.5 py-1" :class="invoiceStatusMeta[lastInvoice.status]?.class">
                                {{ invoiceStatusMeta[lastInvoice.status]?.label ?? lastInvoice.status }}
                            </span>
                            <p v-if="lastInvoice.issued_at" class="text-xs text-ivt-ink-soft border-l border-ivt-line pl-3 ml-1">
                                {{ lastInvoice.issued_at }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div
                    v-for="card in statCards"
                    :key="card.label"
                    class="bg-white border border-ivt-line rounded-2xl p-5 shadow-sm shadow-ivt-ink/5 transition-all duration-200 hover:shadow-glow-primary hover:-translate-y-0.5"
                >
                    <div class="flex items-center justify-between mb-3">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-bright to-primary text-white flex items-center justify-center shadow-sm shadow-primary/20">
                            <component :is="card.icon" class="w-5 h-5" />
                        </span>
                    </div>
                    <p class="text-2xl font-semibold text-ivt-ink tabular-nums">{{ displayedStats[card.key] ?? 0 }}</p>
                    <p class="text-xs text-ivt-ink-soft mt-1">{{ card.label }}</p>
                </div>
            </div>

            <!-- Trend chart -->
            <div class="bg-white border border-ivt-line rounded-2xl p-6 mb-8 shadow-sm shadow-ivt-ink/5 transition-shadow duration-200 hover:shadow-glow-primary">
                <h3 class="font-display text-lg text-ivt-ink mb-4">Evoluție — ultimele 30 de zile</h3>
                <TrendChart :data="trend" />
            </div>

            <div class="grid lg:grid-cols-3 gap-6 mb-6">
                <!-- Listing performance -->
                <div class="lg:col-span-2 bg-white border border-ivt-line rounded-2xl p-6 shadow-sm shadow-ivt-ink/5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-display text-lg text-ivt-ink">Performanță anunțuri</h3>
                        <span class="text-xs text-ivt-ink-soft">{{ listings.length }} total</span>
                    </div>

                    <div v-if="listings.length" class="overflow-x-auto -mx-2">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-[11px] text-ivt-ink-soft uppercase tracking-wide">
                                    <th class="px-2 py-2 font-medium">Anunț</th>
                                    <th class="px-2 py-2 font-medium text-right">Vizualizări</th>
                                    <th class="px-2 py-2 font-medium text-right">Telefon</th>
                                    <th class="px-2 py-2 font-medium text-right">WhatsApp</th>
                                    <th class="px-2 py-2 font-medium text-right">Conversie</th>
                                    <th class="px-2 py-2 font-medium text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ivt-line">
                                <tr v-for="listing in listings" :key="listing.id" class="transition-colors duration-150 hover:bg-ivt-paper">
                                    <td class="px-2 py-3 max-w-[12rem]">
                                        <p class="font-medium text-ivt-ink truncate">{{ listing.title }}</p>
                                        <p class="text-xs text-ivt-ink-soft">{{ listing.category }}</p>
                                    </td>
                                    <td class="px-2 py-3 text-right tabular-nums text-ivt-ink-soft">{{ listing.views_count }}</td>
                                    <td class="px-2 py-3 text-right tabular-nums text-ivt-ink-soft">{{ listing.phone_clicks }}</td>
                                    <td class="px-2 py-3 text-right tabular-nums text-ivt-ink-soft">{{ listing.whatsapp_clicks }}</td>
                                    <td class="px-2 py-3 text-right tabular-nums text-ivt-ink font-medium">{{ listing.conversion_rate }}%</td>
                                    <td class="px-2 py-3 text-right">
                                        <span class="text-xs font-medium rounded-full px-2.5 py-1 whitespace-nowrap" :class="statusMeta[listing.status]?.class">
                                            {{ statusMeta[listing.status]?.label ?? listing.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-sm text-ivt-ink-soft py-8 text-center">Nu ai încă niciun anunț publicat.</p>
                </div>

                <!-- Profile completion -->
                <div class="bg-white border border-ivt-line rounded-2xl p-6 shadow-sm shadow-ivt-ink/5">
                    <h3 class="font-display text-lg text-ivt-ink mb-4">Scor profil</h3>

                    <div class="flex items-center gap-5">
                        <div
                            class="relative w-20 h-20 rounded-full flex-none"
                            :style="`background: conic-gradient(from -90deg, ${primary.DEFAULT}, ${ivt['accent-bright']} ${completionDeg}deg, ${ivt['paper-3']} 0)`"
                        >
                            <div class="absolute inset-1.5 rounded-full bg-white flex items-center justify-center">
                                <span class="text-lg font-semibold text-ivt-ink tabular-nums">{{ profile.completion_score }}%</span>
                            </div>
                        </div>
                        <p class="text-xs text-ivt-ink-soft">
                            Un profil complet primește mai multe click-uri pe telefon și WhatsApp din partea clienților.
                        </p>
                    </div>

                    <div v-if="profile.missing_fields.length" class="mt-5 pt-5 border-t border-ivt-line">
                        <p class="text-xs font-medium text-ivt-ink-soft uppercase tracking-wide mb-2">De completat</p>
                        <ul class="space-y-1.5">
                            <li v-for="field in profile.missing_fields" :key="field" class="text-sm text-ivt-ink flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-ivt-violet"></span>
                                {{ field }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                <!-- Recent leads -->
                <div class="bg-white border border-ivt-line rounded-2xl p-6 shadow-sm shadow-ivt-ink/5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-display text-lg text-ivt-ink">Cereri de ofertă recente</h3>
                        <Link :href="route('provider.leads.index')" class="text-xs font-medium text-primary hover:underline">Vezi toate</Link>
                    </div>

                    <div v-if="leads.length" class="space-y-3">
                        <div v-for="lead in leads" :key="lead.id" class="border border-ivt-line rounded-xl p-3 transition-all duration-200 hover:shadow-glow-primary">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <p class="text-sm font-medium text-ivt-ink truncate">{{ lead.name }}</p>
                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-primary bg-ivt-paper-2 rounded-full px-2 py-0.5 flex-none">
                                        {{ lead.category }}
                                    </span>
                                </div>
                                <span v-if="lead.contacted" class="flex items-center gap-1 text-xs font-medium text-success-700 flex-none">
                                    <CheckCircleIcon class="w-4 h-4" /> Contactat
                                </span>
                            </div>
                            <p class="text-xs text-ivt-ink-soft mt-1.5 line-clamp-2">{{ lead.message }}</p>
                            <p class="text-[11px] text-ivt-ink-soft mt-1.5">
                                <template v-if="lead.city">{{ lead.city }} · </template>{{ lead.created_at }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-ivt-ink-soft py-8 text-center">Nicio cerere deschisă momentan.</p>
                </div>

                <!-- Reviews & rating -->
                <div class="bg-white border border-ivt-line rounded-2xl p-6 shadow-sm shadow-ivt-ink/5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-display text-lg text-ivt-ink">Recenzii</h3>
                        <div v-if="reviews.average_rating" class="flex items-center gap-1 text-sm font-semibold text-ivt-ink">
                            <StarIcon class="w-4 h-4 text-ivt-accent-bright" />
                            {{ reviews.average_rating }}
                            <span class="text-xs font-normal text-ivt-ink-soft">({{ reviews.count }})</span>
                        </div>
                    </div>

                    <div v-if="reviews.recent.length" class="space-y-4 divide-y divide-ivt-line">
                        <div v-for="review in reviews.recent" :key="review.id" class="pt-4 first:pt-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="flex items-center gap-1.5 text-sm font-medium text-ivt-ink">
                                    <UserCircleIcon class="h-5 w-5 flex-none text-ivt-ink-soft/40" />
                                    {{ review.author }}
                                </p>
                                <span class="flex items-center gap-0.5">
                                    <StarIcon
                                        v-for="n in 5"
                                        :key="n"
                                        class="w-3.5 h-3.5"
                                        :class="n <= review.rating ? 'text-ivt-violet' : 'text-ivt-paper-3'"
                                    />
                                </span>
                            </div>
                            <p v-if="review.comment" class="text-xs text-ivt-ink-soft mt-1 line-clamp-2">{{ review.comment }}</p>
                            <p class="text-[11px] text-ivt-ink-soft mt-1.5">{{ review.listing_title }} · {{ review.created_at }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-ivt-ink-soft py-8 text-center">Nicio recenzie încă.</p>
                </div>
            </div>
        </template>
    </ProviderLayout>
</template>
