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
    Send,
    Tag,
    PartyPopper,
    Sparkles,
    StickyNote,
    ShieldCheck,
    ExternalLink,
    Hash,
    AlertTriangle,
} from '@lucide/vue';
import Layout from '@/Layouts/Layout.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import ReasonModal from '@/Components/Admin/ReasonModal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    quoteRequest: Object,
});

const processing = ref(false);
const showRejectModal = ref(false);
const showApproveModal = ref(false);

const approve = () => {
    processing.value = true;
    router.post(route('administration.quote-requests.approve', props.quoteRequest.id), {}, {
        preserveScroll: true,
        onSuccess: () => { showApproveModal.value = false; },
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

const initials = computed(() => (props.quoteRequest.name || '')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase());

const eventCountdown = computed(() => {
    const days = props.quoteRequest.days_until_event;
    if (days === null || days === undefined) return null;
    if (days < 0) return { text: `a trecut de ${Math.abs(days)} ${Math.abs(days) === 1 ? 'zi' : 'zile'}`, tone: 'text-ivt-ink-soft/70' };
    if (days === 0) return { text: 'chiar azi', tone: 'text-primary' };
    if (days === 1) return { text: 'mâine', tone: 'text-primary' };
    return { text: `peste ${days} zile`, tone: days <= 14 ? 'text-primary' : 'text-ivt-ink-soft/70' };
});

const facts = computed(() => [
    { label: 'Tip eveniment', value: eventTypeLabels[props.quoteRequest.event_type] ?? '—', icon: PartyPopper },
    { label: 'Data evenimentului', value: props.quoteRequest.event_date ?? '—', hint: eventCountdown.value, icon: Calendar },
    { label: 'Locație', value: locationLabel.value, icon: MapPin },
    { label: 'Invitați', value: props.quoteRequest.guest_count ?? 'Nespecificat', icon: Users },
    { label: 'Buget estimat', value: props.quoteRequest.budget_range ?? 'Nespecificat', icon: Wallet },
]);

const history = computed(() => {
    const items = [
        { label: 'Trimisă', date: props.quoteRequest.created_at, icon: Send, tone: 'text-ivt-ink-soft bg-ivt-paper-2' },
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
            <div class="relative overflow-hidden rounded-3xl border border-ivt-line bg-white shadow-sm shadow-ivt-ink/5">
                <div class="h-1.5 bg-gradient-to-r from-primary-bright via-primary to-ivt-gold"></div>
                <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4 min-w-0">
                        <Link
                            :href="route('administration.quote-requests.index')"
                            class="mt-1 flex items-center justify-center w-9 h-9 rounded-xl border border-ivt-line text-ivt-ink-soft hover:bg-ivt-paper-2 hover:text-primary transition-colors flex-none"
                            title="Înapoi la listă"
                        >
                            <ArrowLeft class="w-4 h-4" />
                        </Link>
                        <span class="hidden sm:flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-b from-primary-bright to-primary text-ivt-paper flex-none shadow-ivt-soft">
                            <MessageSquareText class="w-6 h-6" />
                        </span>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="font-serif text-xl sm:text-2xl text-ivt-ink break-words">{{ quoteRequest.title }}</h2>
                                <StatusBadge :status="quoteRequest.status" />
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ivt-ink-soft">
                                <span class="inline-flex items-center gap-1.5"><Hash class="w-3.5 h-3.5" /> Cererea {{ quoteRequest.id }}</span>
                                <span v-if="quoteRequest.category" class="inline-flex items-center gap-1.5"><Tag class="w-3.5 h-3.5" /> {{ quoteRequest.category.name }}</span>
                                <span class="inline-flex items-center gap-1.5"><Clock class="w-3.5 h-3.5" /> Trimisă {{ quoteRequest.created_at }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <button
                            v-if="quoteRequest.status === 'pending_review' || quoteRequest.status === 'rejected'"
                            type="button"
                            :disabled="processing"
                            @click="showApproveModal = true"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-primary hover:bg-primary-bright shadow-sm shadow-primary/25 transition-colors disabled:opacity-50"
                        >
                            <CheckCircle2 class="w-4 h-4" /> Aprobă
                        </button>
                        <button
                            v-if="quoteRequest.status === 'pending_review'"
                            type="button"
                            :disabled="processing"
                            @click="showRejectModal = true"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors disabled:opacity-50"
                        >
                            <XCircle class="w-4 h-4" /> Respinge
                        </button>
                    </div>
                </div>

                <!-- Status notice -->
                <div
                    v-if="quoteRequest.status === 'pending_review'"
                    class="flex items-start gap-3 border-t border-amber-100 bg-amber-50/70 px-5 py-3 sm:px-6 text-sm text-amber-700"
                >
                    <AlertTriangle class="w-4 h-4 mt-0.5 flex-none" />
                    Cererea așteaptă moderare. După aprobare devine vizibilă furnizorilor potriviți.
                </div>
                <div
                    v-else-if="quoteRequest.status === 'rejected'"
                    class="flex items-start gap-3 border-t border-rose-100 bg-rose-50/70 px-5 py-3 sm:px-6 text-sm text-rose-700"
                >
                    <XCircle class="w-4 h-4 mt-0.5 flex-none" />
                    <p>
                        Cererea a fost respinsă<template v-if="quoteRequest.rejected_at"> pe {{ quoteRequest.rejected_at }}</template>.
                        <span v-if="quoteRequest.rejection_reason" class="block italic mt-0.5">„{{ quoteRequest.rejection_reason }}”</span>
                    </p>
                </div>
            </div>

            <!-- Key facts -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
                <div
                    v-for="fact in facts"
                    :key="fact.label"
                    class="rounded-2xl border border-ivt-line bg-white p-4 shadow-sm shadow-ivt-ink/5"
                >
                    <div class="flex items-center gap-2.5">
                        <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-ivt-paper-2 text-primary flex-none">
                            <component :is="fact.icon" class="w-4 h-4" />
                        </span>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-ivt-ink-soft/70">{{ fact.label }}</p>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-ivt-ink break-words">{{ fact.value }}</p>
                    <p v-if="fact.hint" class="mt-0.5 text-xs font-medium" :class="fact.hint.tone">{{ fact.hint.text }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main column -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-2xl border border-ivt-line bg-white p-5 sm:p-6 shadow-sm shadow-ivt-ink/5">
                        <h3 class="text-sm font-semibold text-ivt-ink mb-3 flex items-center gap-2">
                            <MessageSquareText class="w-4 h-4 text-primary" /> Descrierea cererii
                        </h3>
                        <p v-if="quoteRequest.message" class="text-sm text-ivt-ink leading-relaxed whitespace-pre-line">{{ quoteRequest.message }}</p>
                        <p v-else class="text-sm text-ivt-ink-soft/60 italic">Clientul nu a adăugat o descriere.</p>
                    </div>

                    <div v-if="quoteRequest.preferences?.length" class="rounded-2xl border border-ivt-line bg-white p-5 sm:p-6 shadow-sm shadow-ivt-ink/5">
                        <h3 class="text-sm font-semibold text-ivt-ink mb-3 flex items-center gap-2">
                            <Sparkles class="w-4 h-4 text-primary" /> Preferințe
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="pref in quoteRequest.preferences"
                                :key="pref"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-ivt-paper-2 text-ivt-ink"
                            >
                                <CheckCircle2 class="w-3.5 h-3.5 text-ivt-gold" /> {{ pref }}
                            </span>
                        </div>
                    </div>

                    <div v-if="quoteRequest.notes" class="rounded-2xl border border-ivt-line bg-white p-5 sm:p-6 shadow-sm shadow-ivt-ink/5">
                        <h3 class="text-sm font-semibold text-ivt-ink mb-3 flex items-center gap-2">
                            <StickyNote class="w-4 h-4 text-primary" /> Note suplimentare
                        </h3>
                        <p class="rounded-xl bg-ivt-paper-2/70 px-4 py-3 text-sm text-ivt-ink-soft leading-relaxed whitespace-pre-line">{{ quoteRequest.notes }}</p>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <div class="rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
                        <h3 class="text-sm font-semibold text-ivt-ink mb-4 flex items-center gap-2">
                            <User class="w-4 h-4 text-primary" /> Contact
                        </h3>
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-gradient-to-br from-primary-bright to-primary text-sm font-semibold text-ivt-on-dark shadow-sm shadow-ivt-ink/30">
                                {{ initials }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-ivt-ink truncate">{{ quoteRequest.name }}</p>
                                <p v-if="quoteRequest.contact_method" class="text-xs text-ivt-ink-soft">Preferă: {{ quoteRequest.contact_method }}</p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-2">
                            <a
                                :href="`mailto:${quoteRequest.email}`"
                                class="group flex items-center gap-3 rounded-xl border border-ivt-line px-3 py-2.5 text-sm text-ivt-ink-soft transition-colors hover:border-ivt-gold hover:text-primary"
                            >
                                <Mail class="w-4 h-4 flex-none" />
                                <span class="min-w-0 flex-1 truncate">{{ quoteRequest.email }}</span>
                                <ExternalLink class="w-3.5 h-3.5 flex-none opacity-0 transition-opacity group-hover:opacity-100" />
                            </a>
                            <a
                                :href="`tel:${quoteRequest.phone}`"
                                class="group flex items-center gap-3 rounded-xl border border-ivt-line px-3 py-2.5 text-sm text-ivt-ink-soft transition-colors hover:border-ivt-gold hover:text-primary"
                            >
                                <Phone class="w-4 h-4 flex-none" />
                                <span class="min-w-0 flex-1 truncate">{{ quoteRequest.phone }}</span>
                                <ExternalLink class="w-3.5 h-3.5 flex-none opacity-0 transition-opacity group-hover:opacity-100" />
                            </a>
                        </div>

                        <p
                            v-if="quoteRequest.platform_only"
                            class="mt-3 flex items-start gap-2 rounded-xl bg-ivt-paper-2 px-3 py-2.5 text-xs text-ivt-ink-soft"
                        >
                            <ShieldCheck class="w-4 h-4 mt-px flex-none text-primary" />
                            Clientul dorește să fie contactat doar prin platformă.
                        </p>

                        <div v-if="quoteRequest.user" class="mt-4 pt-4 border-t border-ivt-line">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-ivt-ink-soft/60 mb-1.5">Cont utilizator</p>
                            <p class="text-sm font-medium text-ivt-ink">{{ quoteRequest.user.name }}</p>
                            <p class="text-xs text-ivt-ink-soft">{{ quoteRequest.user.email }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
                        <h3 class="text-sm font-semibold text-ivt-ink mb-4 flex items-center gap-2">
                            <Clock class="w-4 h-4 text-primary" /> Istoric
                        </h3>
                        <ol class="relative space-y-5">
                            <li v-for="(item, index) in history" :key="index" class="relative flex gap-3">
                                <span
                                    v-if="index < history.length - 1"
                                    class="absolute left-[13px] top-8 -bottom-5 w-px bg-ivt-line"
                                    aria-hidden="true"
                                ></span>
                                <span class="relative flex items-center justify-center w-7 h-7 rounded-lg flex-none" :class="item.tone">
                                    <component :is="item.icon" class="w-3.5 h-3.5" />
                                </span>
                                <div class="min-w-0 pt-0.5">
                                    <p class="text-sm font-semibold text-ivt-ink">{{ item.label }}</p>
                                    <p class="text-xs text-ivt-ink-soft">{{ item.date }}</p>
                                    <p v-if="item.reason" class="text-xs text-ivt-ink-soft mt-1 italic">„{{ item.reason }}”</p>
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmDialog
            v-model:show="showApproveModal"
            variant="primary"
            title="Aprobi această cerere?"
            :message="`Cererea „${quoteRequest.title}” va deveni vizibilă furnizorilor, iar clientul va primi un email de confirmare.`"
            confirm-label="Da, aprobă"
            :processing="processing"
            @confirm="approve"
        />

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
