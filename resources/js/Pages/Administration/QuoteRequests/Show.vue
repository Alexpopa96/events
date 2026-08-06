<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    MessageSquareText,
    MapPin,
    Phone,
    Mail,
    User,
    Calendar,
    Users,
    Wallet,
    Clock,
    CheckCircle2,
    XCircle,
} from '@lucide/vue';
import Layout from '@/Layouts/Layout.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import ReasonModal from '@/Components/Admin/ReasonModal.vue';

const props = defineProps({
    quoteRequest: Object,
});

const processing = ref(false);
const showRejectModal = ref(false);

const approve = () => {
    processing.value = true;
    router.post(route('administration.quote-requests.approve', props.quoteRequest.id), {}, {
        preserveScroll: true,
        onFinish: () => { processing.value = false; },
    });
};

const reject = (reason) => {
    processing.value = true;
    router.post(route('administration.quote-requests.reject', props.quoteRequest.id), { reason }, {
        preserveScroll: true,
        onSuccess: () => { showRejectModal.value = false; },
        onFinish: () => { processing.value = false; },
    });
};

const eventTypeLabels = {
    nunta: 'Nuntă',
    botez: 'Botez',
    aniversare: 'Aniversare',
    corporate: 'Eveniment corporate',
    'petrecere-privata': 'Petrecere privată',
    concert: 'Concert / Festival',
    altul: 'Alt tip de eveniment',
};

const locationLabel = computed(() => {
    const parts = [props.quoteRequest.locality?.name, props.quoteRequest.county?.name].filter(Boolean);
    return parts.length ? parts.join(', ') : '—';
});

const history = computed(() => {
    const items = [
        { label: 'Trimisă', date: props.quoteRequest.created_at, icon: Calendar, tone: 'text-ink-soft bg-paper' },
    ];

    if (props.quoteRequest.approved_at) {
        items.push({ label: 'Aprobată', date: props.quoteRequest.approved_at, icon: CheckCircle2, tone: 'text-emerald-600 bg-emerald-50' });
    }
    if (props.quoteRequest.rejected_at) {
        items.push({ label: 'Respinsă', date: props.quoteRequest.rejected_at, reason: props.quoteRequest.rejection_reason, icon: XCircle, tone: 'text-rose-600 bg-rose-50' });
    }

    return items;
});
</script>

<template>
    <Head :title="quoteRequest.title" />
    <Layout :title="quoteRequest.title" :breadcrumbs="['Administrare', 'Cereri de ofertă', quoteRequest.title]">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                    <Link
                        :href="route('administration.quote-requests.index')"
                        class="mt-1 flex items-center justify-center w-9 h-9 rounded-xl border border-line text-ink-soft hover:bg-paper transition-colors flex-none"
                    >
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <span class="flex items-center justify-center w-14 h-14 rounded-2xl bg-paper flex-none border border-line">
                        <MessageSquareText class="w-6 h-6 text-ink-soft/50" />
                    </span>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="font-serif text-xl text-ink">{{ quoteRequest.title }}</h2>
                            <StatusBadge :status="quoteRequest.status" />
                        </div>
                        <p class="text-sm text-ink-soft mt-1">{{ quoteRequest.category?.name ?? '—' }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        v-if="quoteRequest.status === 'pending_review' || quoteRequest.status === 'rejected'"
                        type="button"
                        :disabled="processing"
                        @click="approve"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-brand-500 hover:bg-brand-600 shadow-sm shadow-brand-500/25 transition-colors disabled:opacity-50"
                    >
                        Aprobă
                    </button>
                    <button
                        v-if="quoteRequest.status === 'pending_review'"
                        type="button"
                        :disabled="processing"
                        @click="showRejectModal = true"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors disabled:opacity-50"
                    >
                        Respinge
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main column -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-2xl border border-line bg-white p-5 sm:p-6 shadow-sm shadow-ink/5">
                        <h3 class="text-sm font-semibold text-ink mb-4">Descriere</h3>
                        <p v-if="quoteRequest.message" class="text-sm text-ink-soft leading-relaxed mb-4">{{ quoteRequest.message }}</p>
                        <p v-else class="text-sm text-ink-soft/60 italic mb-4">Fără descriere.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div class="flex items-center gap-2 text-ink-soft">
                                <Calendar class="w-4 h-4 flex-none" />
                                {{ eventTypeLabels[quoteRequest.event_type] ?? '—' }} · {{ quoteRequest.event_date ?? '—' }}
                            </div>
                            <div class="flex items-center gap-2 text-ink-soft">
                                <MapPin class="w-4 h-4 flex-none" /> {{ locationLabel }}
                            </div>
                            <div class="flex items-center gap-2 text-ink-soft">
                                <Users class="w-4 h-4 flex-none" /> {{ quoteRequest.guest_count ?? 'Nespecificat' }}
                            </div>
                            <div class="flex items-center gap-2 text-ink-soft">
                                <Wallet class="w-4 h-4 flex-none" /> {{ quoteRequest.budget_range ?? 'Nespecificat' }}
                            </div>
                        </div>

                        <div v-if="quoteRequest.preferences?.length" class="mt-4 flex flex-wrap gap-2">
                            <span v-for="pref in quoteRequest.preferences" :key="pref" class="px-2.5 py-1 rounded-full text-xs font-medium bg-paper text-ink-soft">
                                {{ pref }}
                            </span>
                        </div>

                        <p v-if="quoteRequest.notes" class="mt-4 rounded-xl bg-paper px-4 py-3 text-sm text-ink-soft">{{ quoteRequest.notes }}</p>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <div class="rounded-2xl border border-line bg-white p-5 shadow-sm shadow-ink/5">
                        <h3 class="text-sm font-semibold text-ink mb-3 flex items-center gap-2">
                            <User class="w-4 h-4 text-brand-600" /> Contact
                        </h3>
                        <p class="text-sm text-ink">{{ quoteRequest.name }}</p>
                        <div class="mt-2 space-y-1.5 text-sm text-ink-soft">
                            <p class="flex items-center gap-2"><Mail class="w-3.5 h-3.5 flex-none" /> {{ quoteRequest.email }}</p>
                            <p class="flex items-center gap-2"><Phone class="w-3.5 h-3.5 flex-none" /> {{ quoteRequest.phone }}</p>
                        </div>
                        <div v-if="quoteRequest.user" class="mt-4 pt-4 border-t border-line">
                            <p class="text-[11px] uppercase tracking-wider text-ink-soft/60 mb-1">Cont</p>
                            <p class="text-sm text-ink">{{ quoteRequest.user.name }}</p>
                            <p class="text-xs text-ink-soft">{{ quoteRequest.user.email }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-line bg-white p-5 shadow-sm shadow-ink/5">
                        <h3 class="text-sm font-semibold text-ink mb-4 flex items-center gap-2">
                            <Clock class="w-4 h-4 text-brand-600" /> Istoric
                        </h3>
                        <ul class="space-y-4">
                            <li v-for="(item, index) in history" :key="index" class="flex gap-3">
                                <span class="flex items-center justify-center w-7 h-7 rounded-lg flex-none" :class="item.tone">
                                    <component :is="item.icon" class="w-3.5 h-3.5" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-ink">{{ item.label }}</p>
                                    <p class="text-xs text-ink-soft">{{ item.date }}</p>
                                    <p v-if="item.reason" class="text-xs text-ink-soft mt-1 italic">"{{ item.reason }}"</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <ReasonModal
            :show="showRejectModal"
            title="Respinge cererea"
            description="Motivul va fi trimis clientului prin email."
            confirm-label="Respinge"
            confirm-class="bg-rose-600 hover:bg-rose-700"
            :processing="processing"
            @close="showRejectModal = false"
            @confirm="reject"
        />
    </Layout>
</template>
