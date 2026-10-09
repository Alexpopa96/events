<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    ArrowLeft,
    Building2,
    MapPin,
    Phone,
    Mail,
    Globe,
    MessageCircle,
    Star,
    CreditCard,
    Clock,
    CheckCircle2,
    XCircle,
    ShieldCheck,
    RefreshCw,
    ExternalLink,
    AlertTriangle,
    Calendar,
} from '@lucide/vue';
import Layout from '@/Layouts/Layout.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import ReasonModal from '@/Components/Admin/ReasonModal.vue';

const props = defineProps({
    provider: Object,
    listings: Array,
    listingCounts: Object,
    reviews: Array,
    invoices: Array,
});

const processing = ref(false);
const showRejectModal = ref(false);
const showSuspendModal = ref(false);

const approve = () => {
    processing.value = true;
    router.post(route('administration.providers.approve', props.provider.id), {}, {
        preserveScroll: true,
        onFinish: () => { processing.value = false; },
    });
};

const reject = (reason) => {
    processing.value = true;
    router.post(route('administration.providers.reject', props.provider.id), { reason }, {
        preserveScroll: true,
        onSuccess: () => { showRejectModal.value = false; },
        onFinish: () => { processing.value = false; },
    });
};

const suspend = (reason) => {
    processing.value = true;
    router.post(route('administration.providers.suspend', props.provider.id), { reason }, {
        preserveScroll: true,
        onSuccess: () => { showSuspendModal.value = false; },
        onFinish: () => { processing.value = false; },
    });
};

const reactivate = () => {
    processing.value = true;
    router.post(route('administration.providers.reactivate', props.provider.id), {}, {
        preserveScroll: true,
        onFinish: () => { processing.value = false; },
    });
};

// ANAF verification
const anafLoading = ref(false);
const anafResult = ref(null);
const anafError = ref(null);

const checkAnaf = async () => {
    anafLoading.value = true;
    anafError.value = null;
    anafResult.value = null;

    try {
        const { data } = await axios.post(route('administration.providers.anaf-lookup', props.provider.id));
        anafResult.value = data;
        // The lookup just persisted this on the server — reflected here too, without a reload.
        props.provider.anaf_verified_at = data.verified_at;
        props.provider.anaf_status = data.stare_inregistrare;
    } catch (error) {
        anafError.value = error.response?.data?.message ?? 'A apărut o eroare la verificarea CUI.';
    } finally {
        anafLoading.value = false;
    }
};

const matches = (a, b) => a && b && a.trim().toLowerCase() === b.trim().toLowerCase();

// History timeline
const history = computed(() => {
    const items = [
        { label: 'Înregistrat', date: props.provider.created_at, icon: Calendar, tone: 'text-ivt-ink-soft bg-ivt-paper' },
    ];

    if (props.provider.approved_at) {
        items.push({ label: 'Aprobat', date: props.provider.approved_at, icon: CheckCircle2, tone: 'text-success-600 bg-success-50' });
    }
    if (props.provider.rejected_at) {
        items.push({ label: 'Respins', date: props.provider.rejected_at, reason: props.provider.rejection_reason, icon: XCircle, tone: 'text-danger-600 bg-danger-50' });
    }
    if (props.provider.suspended_at) {
        items.push({ label: 'Suspendat', date: props.provider.suspended_at, reason: props.provider.suspension_reason, icon: AlertTriangle, tone: 'text-ivt-ink-soft bg-ivt-paper-2' });
    }

    return items;
});

const listingStatus = {
    draft: { label: 'Ciornă', classes: 'bg-ivt-paper-2 text-ivt-ink-soft' },
    pending_review: { label: 'În verificare', classes: 'bg-warning-50 text-warning-600' },
    published: { label: 'Publicat', classes: 'bg-success-50 text-success-600' },
    rejected: { label: 'Respins', classes: 'bg-danger-50 text-danger-600' },
    archived: { label: 'Arhivat', classes: 'bg-ivt-paper-2 text-ivt-ink-soft' },
};

const formatPrice = (listing) => {
    if (!listing.price_from) return '—';
    const to = listing.price_to && listing.price_to !== listing.price_from ? ` - ${listing.price_to}` : '';
    return `${listing.price_from}${to} ${listing.currency ?? 'RON'}`;
};
</script>

<template>
    <Head :title="provider.company_name" />
    <Layout :title="provider.company_name" :breadcrumbs="['Administrare', 'Furnizori', provider.company_name]">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                    <Link
                        :href="route('administration.providers.index')"
                        class="mt-1 flex items-center justify-center w-9 h-9 rounded-xl border border-ivt-line text-ivt-ink-soft hover:bg-ivt-paper transition-colors flex-none"
                    >
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <span class="flex items-center justify-center w-14 h-14 rounded-2xl bg-ivt-paper overflow-hidden flex-none border border-ivt-line">
                        <img v-if="provider.logo_url" :src="provider.logo_url" class="w-full h-full object-cover" />
                        <Building2 v-else class="w-6 h-6 text-ivt-ink-soft/50" />
                    </span>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="font-display text-xl text-ivt-ink">{{ provider.company_name }}</h2>
                            <StatusBadge :status="provider.status" />
                        </div>
                        <p class="text-sm text-ivt-ink-soft mt-1">
                            CUI {{ provider.cui ?? '—' }}<template v-if="provider.reg_com"> · {{ provider.reg_com }}</template>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        v-if="provider.status === 'pending' || provider.status === 'rejected'"
                        type="button"
                        :disabled="processing"
                        @click="approve"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-brand hover:brightness-110 hover:shadow-glow-violet shadow-sm shadow-primary/25 transition-colors disabled:opacity-50"
                    >
                        Aprobă
                    </button>
                    <button
                        v-if="provider.status === 'pending'"
                        type="button"
                        :disabled="processing"
                        @click="showRejectModal = true"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-danger-600 bg-danger-50 hover:bg-danger-100 transition-colors disabled:opacity-50"
                    >
                        Respinge
                    </button>
                    <button
                        v-if="provider.status === 'active'"
                        type="button"
                        :disabled="processing"
                        @click="showSuspendModal = true"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-danger-600 bg-danger-50 hover:bg-danger-100 transition-colors disabled:opacity-50"
                    >
                        Suspendă
                    </button>
                    <button
                        v-if="provider.status === 'suspended'"
                        type="button"
                        :disabled="processing"
                        @click="reactivate"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-brand hover:brightness-110 hover:shadow-glow-violet shadow-sm shadow-primary/25 transition-colors disabled:opacity-50"
                    >
                        Reactivează
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Company info -->
                    <div class="rounded-2xl border border-ivt-line bg-white p-5 sm:p-6 shadow-sm shadow-ivt-ink/5">
                        <h3 class="text-sm font-semibold text-ivt-ink mb-4">Despre firmă</h3>
                        <p v-if="provider.description" class="text-sm text-ivt-ink-soft leading-relaxed mb-4">{{ provider.description }}</p>
                        <p v-else class="text-sm text-ivt-ink-soft/60 italic mb-4">Fără descriere completată.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div class="flex items-center gap-2 text-ivt-ink-soft">
                                <MapPin class="w-4 h-4 flex-none" />
                                <span>{{ provider.address ?? '—' }}<template v-if="provider.locality">, {{ provider.locality.name }}</template><template v-if="provider.county">, {{ provider.county.name }}</template></span>
                            </div>
                            <div class="flex items-center gap-2 text-ivt-ink-soft">
                                <Phone class="w-4 h-4 flex-none" /> {{ provider.phone ?? '—' }}
                            </div>
                            <div class="flex items-center gap-2 text-ivt-ink-soft">
                                <Mail class="w-4 h-4 flex-none" /> {{ provider.email ?? '—' }}
                            </div>
                            <div class="flex items-center gap-2 text-ivt-ink-soft">
                                <MessageCircle class="w-4 h-4 flex-none" /> {{ provider.whatsapp ?? '—' }}
                            </div>
                            <div v-if="provider.website" class="flex items-center gap-2 text-ivt-ink-soft sm:col-span-2">
                                <Globe class="w-4 h-4 flex-none" />
                                <a :href="provider.website" target="_blank" class="text-primary hover:text-ivt-ink inline-flex items-center gap-1">
                                    {{ provider.website }} <ExternalLink class="w-3.5 h-3.5" />
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ANAF verification -->
                    <div class="rounded-2xl border border-ivt-line bg-white p-5 sm:p-6 shadow-sm shadow-ivt-ink/5">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-semibold text-ivt-ink flex items-center gap-2">
                                <ShieldCheck class="w-4 h-4 text-primary" /> Verificare CUI la ANAF
                            </h3>
                            <button
                                type="button"
                                :disabled="anafLoading || !provider.cui"
                                @click="checkAnaf"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-primary bg-ivt-paper-2 hover:bg-ivt-paper-3 transition-colors disabled:opacity-50"
                            >
                                <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': anafLoading }" />
                                {{ anafLoading ? 'Se verifică...' : 'Verifică la ANAF' }}
                            </button>
                        </div>
                        <p class="text-xs text-ivt-ink-soft/70 mb-3">Compară datele introduse de furnizor cu cele oficiale de la ANAF.</p>

                        <p v-if="provider.anaf_verified_at" class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-success-50 px-3 py-1.5 text-xs font-semibold text-success-700">
                            <CheckCircle2 class="w-3.5 h-3.5" /> Verificat la ANAF pe {{ provider.anaf_verified_at }}
                            <span v-if="provider.anaf_status" class="font-normal text-success-700/80">· {{ provider.anaf_status }}</span>
                        </p>
                        <p v-else class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-ivt-paper-2 px-3 py-1.5 text-xs font-semibold text-ivt-ink-soft">
                            <AlertTriangle class="w-3.5 h-3.5" /> Nu a fost verificat încă — badge-ul „Verificat” nu apare public
                        </p>

                        <p v-if="anafError" class="text-sm text-danger-600">{{ anafError }}</p>

                        <div v-if="anafResult" class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm mt-2">
                            <div class="rounded-xl bg-ivt-paper px-3 py-2.5">
                                <p class="text-[11px] uppercase tracking-wider text-ivt-ink-soft/60 mb-1">Denumire (ANAF)</p>
                                <p class="flex items-center gap-1.5 text-ivt-ink">
                                    {{ anafResult.denumire ?? '—' }}
                                    <CheckCircle2 v-if="matches(anafResult.denumire, provider.company_name)" class="w-3.5 h-3.5 text-success-500" />
                                    <AlertTriangle v-else class="w-3.5 h-3.5 text-warning-500" />
                                </p>
                            </div>
                            <div class="rounded-xl bg-ivt-paper px-3 py-2.5">
                                <p class="text-[11px] uppercase tracking-wider text-ivt-ink-soft/60 mb-1">Nr. Reg. Com.</p>
                                <p class="text-ivt-ink">{{ anafResult.reg_com ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl bg-ivt-paper px-3 py-2.5">
                                <p class="text-[11px] uppercase tracking-wider text-ivt-ink-soft/60 mb-1">Stare înregistrare</p>
                                <p class="text-ivt-ink">{{ anafResult.stare_inregistrare ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl bg-ivt-paper px-3 py-2.5">
                                <p class="text-[11px] uppercase tracking-wider text-ivt-ink-soft/60 mb-1">Adresă sediu</p>
                                <p class="text-ivt-ink">{{ anafResult.address ?? '—' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Listings -->
                    <div class="rounded-2xl border border-ivt-line bg-white p-5 sm:p-6 shadow-sm shadow-ivt-ink/5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-ivt-ink">Anunțuri</h3>
                            <div class="flex items-center gap-1.5">
                                <span
                                    v-for="(count, status) in listingCounts"
                                    :key="status"
                                    class="px-2 py-0.5 rounded-full text-[11px] font-semibold"
                                    :class="(listingStatus[status] ?? {}).classes ?? 'bg-ivt-paper-2 text-ivt-ink-soft'"
                                >
                                    {{ (listingStatus[status] ?? {}).label ?? status }}: {{ count }}
                                </span>
                            </div>
                        </div>

                        <p v-if="listings.length === 0" class="text-sm text-ivt-ink-soft/60 italic">Niciun anunț publicat încă.</p>
                        <ul v-else class="divide-y divide-ivt-line">
                            <li v-for="listing in listings" :key="listing.id" class="flex items-center justify-between gap-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-ivt-ink truncate">{{ listing.title }}</p>
                                    <p class="text-xs text-ivt-ink-soft">{{ formatPrice(listing) }} · {{ listing.views_count }} vizualizări</p>
                                </div>
                                <span
                                    class="px-2 py-0.5 rounded-full text-[11px] font-semibold flex-none"
                                    :class="(listingStatus[listing.status] ?? {}).classes ?? 'bg-ivt-paper-2 text-ivt-ink-soft'"
                                >
                                    {{ (listingStatus[listing.status] ?? {}).label ?? listing.status }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <!-- Reviews -->
                    <div class="rounded-2xl border border-ivt-line bg-white p-5 sm:p-6 shadow-sm shadow-ivt-ink/5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-ivt-ink">Recenzii</h3>
                            <div class="flex items-center gap-1.5 text-sm text-ivt-ink-soft">
                                <Star class="w-4 h-4 text-ivt-accent-bright fill-ivt-violet" />
                                <span class="font-semibold text-ivt-ink">{{ provider.average_rating || '—' }}</span>
                                <span>({{ provider.reviews_count }})</span>
                            </div>
                        </div>

                        <p v-if="reviews.length === 0" class="text-sm text-ivt-ink-soft/60 italic">Nicio recenzie încă.</p>
                        <ul v-else class="space-y-3">
                            <li v-for="review in reviews" :key="review.id" class="rounded-xl bg-ivt-paper px-4 py-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-ivt-ink">{{ review.user?.name ?? 'Anonim' }}</p>
                                    <div class="flex items-center gap-0.5">
                                        <Star
                                            v-for="i in 5" :key="i"
                                            class="w-3.5 h-3.5"
                                            :class="i <= review.rating ? 'text-ivt-violet fill-ivt-violet' : 'text-ivt-line'"
                                        />
                                    </div>
                                </div>
                                <p v-if="review.comment" class="text-sm text-ivt-ink-soft mt-1">{{ review.comment }}</p>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Contact -->
                    <div class="rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
                        <h3 class="text-sm font-semibold text-ivt-ink mb-3">Cont utilizator</h3>
                        <p class="text-sm text-ivt-ink">{{ provider.user?.name }}</p>
                        <p class="text-xs text-ivt-ink-soft">{{ provider.user?.email }}</p>
                    </div>

                    <!-- Subscription -->
                    <div class="rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
                        <h3 class="text-sm font-semibold text-ivt-ink mb-3 flex items-center gap-2">
                            <CreditCard class="w-4 h-4 text-primary" /> Abonament
                        </h3>
                        <template v-if="provider.subscription">
                            <p class="text-sm font-medium text-ivt-ink">{{ provider.subscription.plan_name }}</p>
                            <p class="text-xs text-ivt-ink-soft mt-1">
                                {{ provider.subscription.starts_at }} — {{ provider.subscription.ends_at ?? 'nedeterminat' }}
                            </p>
                        </template>
                        <p v-else class="text-sm text-ivt-ink-soft/60 italic">Fără abonament activ.</p>

                        <div v-if="invoices.length" class="mt-4 pt-4 border-t border-ivt-line space-y-2">
                            <p class="text-[11px] uppercase tracking-wider text-ivt-ink-soft/60 mb-2">Facturi recente</p>
                            <div v-for="invoice in invoices" :key="invoice.id" class="flex items-center justify-between text-sm">
                                <span class="text-ivt-ink-soft">{{ invoice.number }}</span>
                                <span class="text-ivt-ink">{{ invoice.amount }} {{ invoice.currency }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Completion score -->
                    <div class="rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold text-ivt-ink">Completare profil</h3>
                            <span class="text-sm font-semibold text-primary">{{ provider.profile_completion_score }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-ivt-paper overflow-hidden">
                            <div class="h-full bg-primary rounded-full transition-all" :style="{ width: provider.profile_completion_score + '%' }"></div>
                        </div>
                    </div>

                    <!-- History -->
                    <div class="rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
                        <h3 class="text-sm font-semibold text-ivt-ink mb-4 flex items-center gap-2">
                            <Clock class="w-4 h-4 text-primary" /> Istoric
                        </h3>
                        <ul class="space-y-4">
                            <li v-for="(item, index) in history" :key="index" class="flex gap-3">
                                <span class="flex items-center justify-center w-7 h-7 rounded-lg flex-none" :class="item.tone">
                                    <component :is="item.icon" class="w-3.5 h-3.5" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-ivt-ink">{{ item.label }}</p>
                                    <p class="text-xs text-ivt-ink-soft">{{ item.date }}</p>
                                    <p v-if="item.reason" class="text-xs text-ivt-ink-soft mt-1 italic">"{{ item.reason }}"</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <ReasonModal
            :show="showRejectModal"
            title="Respinge furnizorul"
            description="Motivul va fi trimis furnizorului prin email."
            confirm-label="Respinge"
            confirm-class="bg-danger-600 hover:bg-danger-700"
            :processing="processing"
            @close="showRejectModal = false"
            @confirm="reject"
        />

        <ReasonModal
            :show="showSuspendModal"
            title="Suspendă furnizorul"
            description="Motivul va fi trimis furnizorului prin email. Anunțurile sale nu vor mai fi vizibile public."
            confirm-label="Suspendă"
            confirm-class="bg-danger-600 hover:bg-danger-700"
            :processing="processing"
            @close="showSuspendModal = false"
            @confirm="suspend"
        />
    </Layout>
</template>
