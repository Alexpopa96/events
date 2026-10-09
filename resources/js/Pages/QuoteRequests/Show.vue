<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import {
    TagIcon,
    MapPinIcon,
    CalendarIcon,
    BanknotesIcon,
    ChatBubbleLeftRightIcon,
    LockClosedIcon,
    ExclamationTriangleIcon,
    PhoneIcon,
    EnvelopeIcon,
    CurrencyDollarIcon,
    ClockIcon,
    XCircleIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon, StarIcon } from '@heroicons/vue/24/solid';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import CountyLocalitySelect from '@/Components/CountyLocalitySelect.vue';
import ModalSelect from '@/Components/ModalSelect.vue';
import DatePickerModal from '@/Components/DatePickerModal.vue';
import Modal from '@/Components/Modal.vue';
import { categoryIcon } from '@/Composables/useCategoryIcon';

const props = defineProps({
    quoteRequest: Object,
    categories: { type: Array, default: () => [] },
    counties: { type: Array, default: () => [] },
    offers: { type: Array, default: () => [] },
    // `package` is a reserved word, so it's declared under that key but read via `pkg`.
    package: { type: Array, default: () => [] },
});

const pkg = computed(() => props.package);

const toast = useToast();
const page = usePage();
onMounted(() => {
    if (page.props.success?.message) toast.success(page.props.success.message);
    if (page.props.error?.message) toast.error(page.props.error.message);
});

const canEdit = computed(() => props.quoteRequest.status !== 'closed');

const requestCode = computed(() => `INV-${String(props.quoteRequest.id).padStart(5, '0')}`);

const eventTypeLabels = {
    nunta: 'Nuntă',
    botez: 'Botez',
    aniversare: 'Aniversare',
    corporate: 'Eveniment corporate',
    'petrecere-privata': 'Petrecere privată',
    concert: 'Concert / Festival',
    altul: 'Alt tip de eveniment',
};
const eventTypeLabel = computed(() => eventTypeLabels[props.quoteRequest.event_type] ?? props.quoteRequest.event_type);

const statusMetaFor = (status) => ({
    pending_review: { label: 'În așteptare', class: 'bg-ivt-violet/10 text-ivt-violet' },
    open: { label: 'Activă', class: 'bg-ivt-teal/10 text-ivt-teal' },
    rejected: { label: 'Respinsă', class: 'bg-primary/10 text-primary' },
    closed: { label: 'Închisă', class: 'bg-ivt-paper-2 text-ivt-ink-faint' },
}[status] ?? { label: status, class: 'bg-ivt-paper-2 text-ivt-ink-faint' });

const statusMeta = computed(() => statusMetaFor(props.quoteRequest.status));

const statusDescription = computed(() => ({
    pending_review: 'Cererea ta a fost trimisă și este în curs de verificare de către echipa noastră.',
    open: 'Cererea ta este activă și vizibilă furnizorilor din categoria și zona potrivită.',
    rejected: props.quoteRequest.rejection_reason
        ? `Cererea ta a fost respinsă. Motiv: ${props.quoteRequest.rejection_reason}`
        : 'Cererea ta a fost respinsă de echipa noastră.',
    closed: 'Această cerere este închisă și nu mai primește oferte noi.',
}[props.quoteRequest.status] ?? ''));

const daysLeft = computed(() => {
    if (props.quoteRequest.status !== 'open' || !props.quoteRequest.event_date_iso) return null;
    const diff = Math.ceil((new Date(props.quoteRequest.event_date_iso) - new Date()) / 86400000);
    return diff >= 0 ? diff : null;
});

// ---- Offers ----------------------------------------------------------------

const offerStatusMeta = {
    sent: { label: 'Nouă', class: 'bg-ivt-violet/15 text-ivt-violet', icon: CurrencyDollarIcon },
    viewed: { label: 'Nouă', class: 'bg-ivt-violet/15 text-ivt-violet', icon: CurrencyDollarIcon },
    accepted: { label: 'Acceptată', class: 'bg-ivt-teal/15 text-ivt-teal', icon: CheckIcon },
    declined: { label: 'Refuzată', class: 'bg-primary/10 text-primary', icon: XCircleIcon },
    expired: { label: 'Expirată', class: 'bg-ivt-paper-2 text-ivt-ink-faint', icon: ClockIcon },
};

const hasAcceptedOffer = computed(() => props.offers.some((offer) => offer.status === 'accepted'));

const busyOfferId = ref(null);

const acceptOffer = (offer) => {
    if (busyOfferId.value) return;
    busyOfferId.value = offer.id;
    router.post(route('offers.accept', offer.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Ai acceptat oferta.'),
        onFinish: () => { busyOfferId.value = null; },
    });
};

const offerToDecline = ref(null);
const declineReason = ref('');

const confirmDeclineOffer = () => {
    if (busyOfferId.value) return;
    busyOfferId.value = offerToDecline.value.id;
    router.post(route('offers.decline', offerToDecline.value.id), { reason: declineReason.value.trim() || null }, {
        preserveScroll: true,
        onSuccess: () => toast.success('Ai refuzat oferta.'),
        onFinish: () => { busyOfferId.value = null; offerToDecline.value = null; declineReason.value = ''; },
    });
};

// ---- Inline edit form ----------------------------------------------------

const editing = ref(false);

const eventTypes = [
    { value: 'nunta', label: 'Nuntă' },
    { value: 'botez', label: 'Botez' },
    { value: 'aniversare', label: 'Aniversare' },
    { value: 'corporate', label: 'Eveniment corporate' },
    { value: 'petrecere-privata', label: 'Petrecere privată' },
    { value: 'concert', label: 'Concert / Festival' },
    { value: 'altul', label: 'Alt tip de eveniment' },
];

const guestCountOptions = ['Sub 50', '50 - 100', '100 - 200', 'Peste 200'];

const preferenceOptions = [
    { value: 'Preț competitiv', desc: 'Vreau oferte în bugetul stabilit' },
    { value: 'Experiență dovedită', desc: 'Portofoliu bogat, ani de activitate' },
    { value: 'Disponibilitate rapidă', desc: 'Răspuns și confirmare în cel mai scurt timp' },
    { value: 'Recenzii excelente', desc: 'Rating minim 4.5 din partea altor clienți' },
];

const contactMethods = [
    { value: 'Telefon', icon: PhoneIcon },
    { value: 'WhatsApp', icon: ChatBubbleLeftRightIcon },
    { value: 'Email', icon: EnvelopeIcon },
];

// See App\Http\Controllers\QuoteRequests\Update: category/event type/location
// decide who the request is shown to, so changing them on an already-approved
// request sends it back through review. Budget, preferences and contact
// details apply immediately. A rejected request always goes back to review.
const noticeText = computed(() => {
    if (props.quoteRequest.status === 'rejected') {
        return 'Cererea ta a fost respinsă. Orice modificare o retrimite spre verificare înainte de a redeveni vizibilă furnizorilor.';
    }
    return 'Dacă schimbi categoria, tipul de eveniment sau locația, cererea va trece din nou prin verificare înainte de a redeveni vizibilă furnizorilor. Modificările la buget, preferințe sau contact se aplică imediat.';
});

const formFieldsFrom = (qr) => ({
    category_id: qr.category_id,
    title: qr.title,
    message: qr.message,
    event_type: qr.event_type ?? '',
    event_date: qr.event_date_iso ?? '',
    county_id: qr.county_id,
    locality_id: qr.locality_id,
    guest_count: qr.guest_count ?? '',
    budget_range: qr.budget_range ?? '',
    preferences: qr.preferences ?? [],
    notes: qr.notes ?? '',
    name: qr.name,
    email: qr.email,
    phone: qr.phone,
    contact_method: qr.contact_method ?? 'Telefon',
    platform_only: qr.platform_only ?? false,
});

const form = useForm(formFieldsFrom(props.quoteRequest));

const flexibleDate = ref(!props.quoteRequest.event_date_iso);
watch(flexibleDate, (flexible) => {
    if (flexible) form.event_date = '';
});

// The stored budget_range is a single free-form string (e.g. "3.000 – 5.000
// lei") composed from two numbers at creation time — split it back out so
// the edit form can offer the same two-input layout as the create wizard.
const parseBudget = (value) => {
    const match = /(\d[\d.,]*)\s*[–-]\s*(\d[\d.,]*)/.exec(value ?? '');
    if (!match) return { min: '', max: '' };
    const toNumber = (s) => parseInt(s.replace(/[.,]/g, ''), 10);
    return { min: toNumber(match[1]) || '', max: toNumber(match[2]) || '' };
};
const initialBudget = parseBudget(props.quoteRequest.budget_range);
const budgetMinRef = ref(initialBudget.min);
const budgetMaxRef = ref(initialBudget.max);
watch([budgetMinRef, budgetMaxRef], () => {
    if (!budgetMinRef.value && !budgetMaxRef.value) {
        form.budget_range = '';
        return;
    }
    form.budget_range = `${budgetMinRef.value || '0'} – ${budgetMaxRef.value || '∞'} lei`;
});

const togglePreference = (pref) => {
    const index = form.preferences.indexOf(pref);
    if (index === -1) form.preferences.push(pref);
    else form.preferences.splice(index, 1);
};

const openEdit = () => {
    Object.assign(form, formFieldsFrom(props.quoteRequest));
    form.clearErrors();
    const budget = parseBudget(props.quoteRequest.budget_range);
    budgetMinRef.value = budget.min;
    budgetMaxRef.value = budget.max;
    flexibleDate.value = !props.quoteRequest.event_date_iso;
    editing.value = true;
};

const closeEdit = () => {
    editing.value = false;
};

const submit = () => {
    form.put(route('quote-requests.update', props.quoteRequest.id), {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = false;
        },
    });
};

const labelClasses = 'mb-2.5 block text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft';
const fieldClasses = 'w-full rounded-xl border border-ivt-line bg-white px-4 py-3.5 text-[14.5px] text-ivt-ink outline-none transition-colors duration-150 focus:border-ivt-violet';
const minDate = new Date(Date.now() + 86400000);
</script>

<template>
    <Head :title="quoteRequest.title" />

    <div class="overflow-x-clip bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <!-- PAGE HERO -->
            <section class="relative z-30 pt-8 lg:pt-10">
                <div class="pointer-events-none absolute inset-0 overflow-hidden [mask-image:linear-gradient(to_bottom,#000_55%,transparent)]">
                    <div class="absolute -left-40 -top-40 h-[520px] w-[520px] rounded-full bg-primary/15 blur-3xl animate-float-slow" />
                    <div class="absolute -right-32 top-10 h-[460px] w-[460px] rounded-full bg-ivt-violet/15 blur-3xl animate-float-slower" />
                    <div class="absolute bottom-0 left-1/3 h-[320px] w-[320px] rounded-full bg-ivt-accent-bright/15 blur-3xl animate-float-slower" />
                    <div
                        class="absolute inset-0 opacity-[0.35]"
                        style="background-image: radial-gradient(rgba(26,20,51,0.12) 1px, transparent 1px); background-size: 22px 22px; mask-image: radial-gradient(ellipse 70% 60% at 30% 30%, #000 30%, transparent 75%);"
                    />
                </div>

                <div class="relative mx-auto max-w-[1600px] px-6 pb-8 lg:px-8 lg:pb-10">
                    <nav class="flex flex-wrap items-center gap-2 text-[13px] text-ivt-ink-faint" aria-label="Breadcrumb">
                        <Link href="/" class="transition-colors hover:text-primary">Acasă</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <Link :href="route('profile.show')" class="transition-colors hover:text-primary">Contul meu</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <Link :href="route('quote-requests.index')" class="transition-colors hover:text-primary">Cererile mele</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <span class="max-w-[16rem] truncate text-ivt-ink-soft">{{ quoteRequest.title }}</span>
                    </nav>

                    <div class="mt-8 grid gap-10 lg:mt-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
                        <div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <p class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-primary shadow-sm backdrop-blur">
                                    <span class="relative flex h-1.5 w-1.5">
                                        <span v-if="quoteRequest.status === 'open'" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-60" />
                                        <span class="relative h-1.5 w-1.5 rounded-full bg-primary" />
                                    </span>
                                    {{ statusMeta.label }}
                                </p>
                                <span class="font-mono text-[12px] tracking-tight text-ivt-ink-faint">{{ requestCode }}</span>
                            </div>

                            <div class="mt-4 flex items-start gap-4">
                                <span class="hidden h-14 w-14 flex-none items-center justify-center rounded-2xl bg-brand text-white shadow-glow-primary sm:flex">
                                    <component :is="categoryIcon(quoteRequest.category_slug)" class="h-7 w-7" stroke-width="1.5" />
                                </span>
                                <h1 class="max-w-xl text-balance font-display text-[30px] font-bold leading-[1.05] tracking-tight text-ivt-ink sm:text-[38px] lg:text-[46px]">
                                    {{ quoteRequest.title }}
                                </h1>
                            </div>
                            <p class="mt-4 max-w-[480px] text-[15.5px] leading-relaxed text-ivt-ink-soft">{{ statusDescription }}</p>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-ivt-line bg-white/80 px-3 py-1.5 text-[12.5px] font-medium text-ivt-ink-soft">
                                    <TagIcon class="h-3.5 w-3.5 text-ivt-violet" /> {{ quoteRequest.category }}
                                </span>
                                <span v-if="quoteRequest.city" class="inline-flex items-center gap-1.5 rounded-full border border-ivt-line bg-white/80 px-3 py-1.5 text-[12.5px] font-medium text-ivt-ink-soft">
                                    <MapPinIcon class="h-3.5 w-3.5 text-ivt-violet" /> {{ quoteRequest.city }}
                                </span>
                                <span v-if="quoteRequest.event_date" class="inline-flex items-center gap-1.5 rounded-full border border-ivt-line bg-white/80 px-3 py-1.5 text-[12.5px] font-medium text-ivt-ink-soft">
                                    <CalendarIcon class="h-3.5 w-3.5 text-ivt-violet" /> {{ quoteRequest.event_date }}
                                </span>
                            </div>

                            <div class="mt-6 flex flex-wrap items-center gap-2.5">
                                <button
                                    v-if="canEdit && !editing"
                                    type="button"
                                    @click="openEdit"
                                    class="btn-brand rounded-2xl px-5 py-3 text-sm font-bold"
                                >
                                    Editează cererea
                                </button>
                                <span v-else-if="!editing" title="Cererea este închisă" class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-2xl border border-ivt-line px-5 py-3 text-sm font-semibold text-ivt-ink-faint">
                                    Editează cererea <LockClosedIcon class="h-3.5 w-3.5" />
                                </span>
                                <span v-if="!editing" title="În curând" class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-2xl border border-ivt-line px-5 py-3 text-sm font-semibold text-ivt-ink-faint">
                                    Închide cererea <LockClosedIcon class="h-3.5 w-3.5" />
                                </span>
                                <Link v-if="!editing" :href="route('quote-requests.index')" class="inline-flex items-center rounded-2xl border border-ivt-line bg-white px-5 py-3 text-sm font-semibold text-ivt-ink transition-colors hover:border-ivt-violet hover:text-primary">
                                    ← Toate cererile
                                </Link>
                                <button v-else type="button" @click="closeEdit" class="inline-flex items-center rounded-2xl border border-ivt-line bg-white px-5 py-3 text-sm font-semibold text-ivt-ink transition-colors hover:border-ivt-violet hover:text-primary">
                                    ← Renunță la editare
                                </button>
                            </div>
                        </div>

                        <!-- Stats panel -->
                        <div class="rounded-[24px] border border-ivt-line bg-white/80 p-5 shadow-ivt-soft backdrop-blur-xl sm:p-6">
                            <div class="flex items-center gap-6">
                                <div class="relative flex h-24 w-24 flex-none flex-col items-center justify-center rounded-full border-[3px] border-primary/60 text-ivt-ink">
                                    <span class="font-display text-[26px] leading-none tabular-nums">{{ quoteRequest.status === 'pending_review' ? '—' : offers.length }}</span>
                                    <span class="mt-0.5 text-[9px] font-bold uppercase tracking-[0.12em] text-ivt-ink-faint">{{ offers.length === 1 ? 'ofertă' : 'oferte' }}</span>
                                </div>
                                <div>
                                    <p class="font-display text-[18px] leading-tight text-ivt-ink">
                                        <template v-if="hasAcceptedOffer">Ai ales un furnizor</template>
                                        <template v-else-if="quoteRequest.status === 'pending_review'">În verificare</template>
                                        <template v-else-if="offers.length">{{ offers.length }} {{ offers.length === 1 ? 'ofertă primită' : 'oferte primite' }}</template>
                                        <template v-else>Așteptăm oferte</template>
                                    </p>
                                    <p class="mt-1 text-[13px] leading-relaxed text-ivt-ink-soft">
                                        <template v-if="hasAcceptedOffer">oferta acceptată e mai jos, cu datele de contact.</template>
                                        <template v-else-if="quoteRequest.status === 'pending_review'">ofertele apar după aprobarea cererii.</template>
                                        <template v-else-if="offers.length">compară-le și acceptă-o pe cea potrivită.</template>
                                        <template v-else>furnizorii potriviți au fost anunțați.</template>
                                    </p>
                                    <a v-if="offers.length" href="#oferte" class="mt-2 inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-primary hover:underline">Vezi ofertele ↓</a>
                                </div>
                            </div>

                            <div class="mt-6 grid grid-cols-3 gap-px overflow-hidden rounded-2xl bg-ivt-line">
                                <div class="bg-white p-4">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-ivt-ink-faint">Până la eveniment</p>
                                    <p class="mt-1.5 font-display text-[20px] leading-none tabular-nums text-ivt-ink">{{ daysLeft !== null ? `${daysLeft} zile` : '—' }}</p>
                                </div>
                                <div class="bg-white p-4">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-ivt-ink-faint">Buget</p>
                                    <p class="mt-1.5 truncate font-display text-[16px] leading-tight text-ivt-ink">{{ quoteRequest.budget_range || '—' }}</p>
                                </div>
                                <div class="bg-white p-4">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-ivt-ink-faint">Invitați</p>
                                    <p class="mt-1.5 truncate font-display text-[16px] leading-tight text-ivt-ink">{{ quoteRequest.guest_count || '—' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </section>

            <!-- Read-only view -->
            <section v-if="!editing" class="py-10 sm:py-12">
                <div class="mx-auto grid max-w-[1600px] grid-cols-1 gap-9 px-6 lg:grid-cols-[1fr_300px] lg:px-8">

                    <div class="min-w-0">
                        <!-- Package -->
                        <div v-if="pkg.length" class="mb-8 rounded-[18px] border border-ivt-violet/40 bg-ivt-violet/5 p-6">
                            <h3 class="mb-1 flex items-center gap-1.5 text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-violet">
                                <SparklesIcon class="h-3.5 w-3.5" /> Parte dintr-un pachet
                            </h3>
                            <p class="mb-3.5 text-[13.5px] text-ivt-ink-soft">Ai mai publicat cereri pentru același eveniment, către alte categorii de furnizori.</p>
                            <div class="flex flex-wrap gap-2">
                                <Link
                                    v-for="item in pkg"
                                    :key="item.id"
                                    :href="route('quote-requests.show', item.id)"
                                    class="inline-flex items-center gap-2 rounded-full bg-white py-1 pl-3 pr-1 text-xs font-semibold text-ivt-ink ring-1 ring-ivt-line transition-colors hover:border-ivt-violet hover:text-primary"
                                >
                                    {{ item.category }}
                                    <span class="rounded-full px-2 py-1 text-[10.5px] font-bold uppercase tracking-wide" :class="statusMetaFor(item.status).class">{{ statusMetaFor(item.status).label }}</span>
                                </Link>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="mb-11">
                            <div class="mb-5 flex flex-wrap items-center justify-between gap-2">
                                <h2 class="font-display text-[21px] font-medium text-ivt-ink">Detaliile cererii</h2>
                                <span class="text-[13px] text-ivt-ink-faint">Publicată pe {{ quoteRequest.created_at }}</span>
                            </div>

                            <div class="rounded-[18px] border border-ivt-line p-7">
                                <div class="flex justify-between gap-4 border-b border-ivt-line py-2.5 text-[14.5px]">
                                    <span class="text-ivt-ink-faint">Categorie</span>
                                    <span class="font-semibold text-ivt-ink">{{ quoteRequest.category }}</span>
                                </div>
                                <div v-if="quoteRequest.event_type" class="flex justify-between gap-4 border-b border-ivt-line py-2.5 text-[14.5px]">
                                    <span class="text-ivt-ink-faint">Tip eveniment</span>
                                    <span class="font-semibold text-ivt-ink">{{ eventTypeLabel }}</span>
                                </div>
                                <div v-if="quoteRequest.city || quoteRequest.county" class="flex justify-between gap-4 border-b border-ivt-line py-2.5 text-[14.5px]">
                                    <span class="text-ivt-ink-faint">Locație</span>
                                    <span class="font-semibold text-ivt-ink">{{ [quoteRequest.city, quoteRequest.county].filter(Boolean).join(', ') }}</span>
                                </div>
                                <div v-if="quoteRequest.event_date" class="flex justify-between gap-4 border-b border-ivt-line py-2.5 text-[14.5px]">
                                    <span class="text-ivt-ink-faint">Dată eveniment</span>
                                    <span class="font-semibold text-ivt-ink">{{ quoteRequest.event_date }}</span>
                                </div>
                                <div v-if="quoteRequest.guest_count" class="flex justify-between gap-4 border-b border-ivt-line py-2.5 text-[14.5px]">
                                    <span class="text-ivt-ink-faint">Număr invitați</span>
                                    <span class="font-semibold text-ivt-ink">~{{ quoteRequest.guest_count }} persoane</span>
                                </div>
                                <div v-if="quoteRequest.budget_range" class="flex justify-between gap-4 border-b border-ivt-line py-2.5 text-[14.5px]">
                                    <span class="text-ivt-ink-faint">Buget estimat</span>
                                    <span class="font-semibold text-ivt-ink">{{ quoteRequest.budget_range }}</span>
                                </div>
                                <div v-if="quoteRequest.preferences?.length" class="flex flex-wrap items-start justify-between gap-4 border-b border-ivt-line py-2.5 text-[14.5px]">
                                    <span class="text-ivt-ink-faint">Preferințe</span>
                                    <span class="flex flex-wrap justify-end gap-2">
                                        <span v-for="pref in quoteRequest.preferences" :key="pref" class="rounded-full bg-ivt-paper-2 px-3 py-1.5 text-xs font-medium text-ivt-ink-soft">{{ pref }}</span>
                                    </span>
                                </div>
                                <div v-if="quoteRequest.message" class="flex flex-col items-start gap-2 py-2.5 text-[14.5px]">
                                    <span class="text-ivt-ink-faint">Descriere</span>
                                    <p class="whitespace-pre-line text-[14.5px] leading-relaxed text-ivt-ink-soft">{{ quoteRequest.message }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Offers -->
                        <div id="oferte" class="scroll-mt-28">
                            <div class="mb-5 flex flex-wrap items-center justify-between gap-2">
                                <h2 class="font-display text-[21px] font-medium text-ivt-ink">Oferte primite</h2>
                                <span class="text-[13px] text-ivt-ink-faint">{{ quoteRequest.offers_count }} furnizori au răspuns</span>
                            </div>

                            <div v-if="!offers.length" class="rounded-2xl border border-dashed border-ivt-line px-5 py-12 text-center">
                                <ChatBubbleLeftRightIcon class="mx-auto h-7 w-7 text-ivt-ink-faint" />
                                <p class="mt-3 font-display text-lg text-ivt-ink">Nicio ofertă primită încă</p>
                                <p class="mt-1.5 text-[13.5px] text-ivt-ink-faint">Te vom notifica imediat ce un furnizor răspunde la cererea ta.</p>
                            </div>

                            <ul v-else class="space-y-4">
                                <li
                                    v-for="offer in offers"
                                    :key="offer.id"
                                    class="rounded-2xl border p-6"
                                    :class="offer.status === 'accepted' ? 'border-ivt-teal/40 bg-ivt-teal/5' : 'border-ivt-line'"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <Link :href="route('providers.show', offer.provider.slug)" class="flex min-w-0 items-center gap-3 group">
                                            <span class="flex h-11 w-11 flex-none items-center justify-center overflow-hidden rounded-full bg-ivt-paper-2 text-sm font-semibold text-ivt-ink">
                                                <img v-if="offer.provider.logo_url" :src="offer.provider.logo_url" :alt="offer.provider.company_name" class="h-full w-full object-cover" />
                                                <span v-else>{{ offer.provider.company_name[0] }}</span>
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-semibold text-ivt-ink group-hover:text-primary">{{ offer.provider.company_name }}</span>
                                                <span v-if="offer.listing" class="block truncate text-xs text-ivt-ink-faint">{{ offer.listing.title }}</span>
                                            </span>
                                        </Link>
                                        <span class="inline-flex flex-none items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold" :class="offerStatusMeta[offer.status].class">
                                            <component :is="offerStatusMeta[offer.status].icon" class="h-3.5 w-3.5" /> {{ offerStatusMeta[offer.status].label }}
                                        </span>
                                    </div>

                                    <div class="mt-4 flex flex-wrap items-baseline gap-x-4 gap-y-1">
                                        <span class="font-display text-[26px] font-semibold text-ivt-ink">{{ offer.price.toLocaleString('ro-RO') }} lei</span>
                                        <span class="text-xs text-ivt-ink-faint">valabilă până la {{ offer.valid_until }}</span>
                                    </div>

                                    <ul v-if="offer.includes.length" class="mt-3 space-y-1.5">
                                        <li v-for="line in offer.includes" :key="line" class="flex items-start gap-1.5 text-sm text-ivt-ink-soft">
                                            <CheckIcon class="mt-0.5 h-3.5 w-3.5 flex-none text-ivt-teal" /> {{ line }}
                                        </li>
                                    </ul>

                                    <p v-if="offer.message" class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ivt-ink-soft">{{ offer.message }}</p>
                                    <p v-if="offer.decline_reason" class="mt-3 text-sm text-ivt-ink-faint">Motivul tău: {{ offer.decline_reason }}</p>

                                    <div v-if="offer.status === 'sent' || offer.status === 'viewed'" class="mt-5 flex flex-wrap gap-2.5">
                                        <button
                                            type="button"
                                            :disabled="busyOfferId === offer.id || hasAcceptedOffer"
                                            @click="acceptOffer(offer)"
                                            class="inline-flex items-center gap-1.5 rounded-full bg-ivt-teal px-5 py-2 text-[13px] font-semibold text-white shadow-sm transition-colors hover:bg-ivt-teal/90 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <CheckIcon class="h-4 w-4" /> Acceptă oferta
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="busyOfferId === offer.id || hasAcceptedOffer"
                                            @click="offerToDecline = offer"
                                            class="inline-flex items-center gap-1.5 rounded-full border border-ivt-line px-5 py-2 text-[13px] font-semibold text-ivt-ink-soft transition-colors hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            Refuză
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <aside class="flex flex-col gap-4 lg:sticky lg:top-24 lg:self-start">
                        <div class="rounded-2xl bg-gradient-to-br from-ivt-ink-2 to-ivt-ink p-[22px] text-ivt-on-dark">
                            <h4 class="text-[13px] font-bold text-ivt-accent-bright">Status cerere</h4>
                            <p class="mt-2 text-[12.5px] leading-snug text-ivt-on-dark-dim">{{ statusDescription }}</p>
                            <div v-if="daysLeft !== null" class="mt-3 font-display text-[28px] font-semibold text-ivt-on-dark">
                                {{ daysLeft }} zile
                                <span class="block font-invita text-xs font-normal text-ivt-on-dark-dim">rămase până la eveniment</span>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-ivt-line p-[22px]">
                            <h4 class="text-[13px] font-bold uppercase tracking-wide text-ivt-ink-soft">Statistici</h4>
                            <div class="flex justify-between border-b border-ivt-line py-2.5 text-[13.5px]">
                                <span class="text-ivt-ink-faint">Oferte primite</span>
                                <span class="font-semibold text-ivt-ink">{{ quoteRequest.offers_count }}</span>
                            </div>
                            <div class="flex justify-between py-2.5 text-[13.5px]">
                                <span class="text-ivt-ink-faint">Publicată</span>
                                <span class="font-semibold text-ivt-ink">{{ quoteRequest.created_at }}</span>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-ivt-line p-[22px]">
                            <h4 class="text-[13px] font-bold uppercase tracking-wide text-ivt-ink-soft">Acțiuni</h4>
                            <div class="mt-3.5 flex flex-col gap-2">
                                <button
                                    v-if="canEdit"
                                    type="button"
                                    @click="openEdit"
                                    class="w-full rounded-full border border-ivt-line px-4 py-2 text-center text-[13px] font-semibold text-ivt-ink transition-colors hover:border-ivt-violet hover:text-primary"
                                >
                                    Editează cererea
                                </button>
                                <span v-else title="Cererea este închisă" class="flex w-full cursor-not-allowed items-center justify-center gap-1.5 rounded-full border border-ivt-line px-4 py-2 text-[13px] font-semibold text-ivt-ink-faint">
                                    Editează cererea <LockClosedIcon class="h-3.5 w-3.5" />
                                </span>
                                <Link :href="route('quote-requests.create')" class="w-full rounded-full border border-ivt-line px-4 py-2 text-center text-[13px] font-semibold text-ivt-ink transition-colors hover:border-ivt-violet hover:text-primary">
                                    Publică o cerere similară
                                </Link>
                                <span title="În curând" class="flex w-full cursor-not-allowed items-center justify-center gap-1.5 rounded-full bg-primary/10 px-4 py-2 text-[13px] font-semibold text-primary/50">
                                    Închide cererea <LockClosedIcon class="h-3.5 w-3.5" />
                                </span>
                            </div>
                        </div>
                    </aside>

                </div>
            </section>

            <!-- Inline edit form -->
            <section v-else class="pt-11">
                <div class="mx-auto max-w-[1600px] px-6 lg:px-8">
                    <div class="mb-8 flex items-start gap-3 rounded-2xl border border-ivt-violet/35 bg-ivt-accent-soft p-4">
                        <span class="flex h-[26px] w-[26px] flex-none items-center justify-center rounded-full bg-ivt-violet text-white">
                            <ExclamationTriangleIcon class="h-3.5 w-3.5" />
                        </span>
                        <p class="text-[13.5px] leading-relaxed text-ivt-ink-soft"><b class="text-ivt-ink">Atenție la modificările majore.</b> {{ noticeText }}</p>
                    </div>
                </div>

                <form novalidate @submit.prevent="submit" class="mx-auto max-w-[1600px] px-6 lg:px-8">

                    <!-- SECTION 1 -->
                    <div class="mb-6 rounded-[20px] border border-ivt-line p-6 sm:p-8">
                        <div class="mb-[22px] flex items-center gap-3 border-b border-ivt-line pb-[18px]">
                            <span class="flex h-[26px] w-[26px] flex-none items-center justify-center rounded-full bg-ivt-paper-2 text-xs font-bold text-ivt-ink-soft">1</span>
                            <h2 class="font-display text-[19px] font-medium text-ivt-ink">Ce serviciu cauți</h2>
                        </div>

                        <div class="mb-[22px]">
                            <label :class="labelClasses">Categorie</label>
                            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-4">
                                <label v-for="category in categories" :key="category.id" class="relative cursor-pointer">
                                    <input v-model="form.category_id" type="radio" name="category" :value="category.id" class="peer sr-only" />
                                    <span class="flex flex-col items-center gap-2 rounded-xl border border-ivt-line px-2.5 py-4 text-center transition-all duration-150 peer-checked:-translate-y-0.5 peer-checked:border-ivt-violet peer-checked:bg-ivt-paper-2">
                                        <component :is="categoryIcon(category.slug)" class="h-[22px] w-[22px] text-primary" stroke-width="1.4" />
                                        <span class="text-[12.5px] font-semibold text-ivt-ink">{{ category.name }}</span>
                                    </span>
                                </label>
                            </div>
                            <p v-if="form.errors.category_id" class="mt-1.5 text-xs text-primary">{{ form.errors.category_id }}</p>
                        </div>

                        <div class="mb-[22px]">
                            <label :class="labelClasses" for="reqTitle">Titlul cererii</label>
                            <input id="reqTitle" v-model="form.title" type="text" maxlength="150" required :class="fieldClasses" />
                            <p v-if="form.errors.title" class="mt-1.5 text-xs text-primary">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label :class="labelClasses" for="reqDesc">Descriere</label>
                            <textarea id="reqDesc" v-model="form.message" rows="4" maxlength="2000" :class="[fieldClasses, 'resize-none']" />
                            <p v-if="form.errors.message" class="mt-1.5 text-xs text-primary">{{ form.errors.message }}</p>
                        </div>
                    </div>

                    <!-- SECTION 2 -->
                    <div class="mb-6 rounded-[20px] border border-ivt-line p-6 sm:p-8">
                        <div class="mb-[22px] flex items-center gap-3 border-b border-ivt-line pb-[18px]">
                            <span class="flex h-[26px] w-[26px] flex-none items-center justify-center rounded-full bg-ivt-paper-2 text-xs font-bold text-ivt-ink-soft">2</span>
                            <h2 class="font-display text-[19px] font-medium text-ivt-ink">Despre eveniment</h2>
                        </div>

                        <div class="mb-[22px]">
                            <label :class="labelClasses">Tip eveniment</label>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <label v-for="type in eventTypes" :key="type.value" class="relative cursor-pointer">
                                    <input v-model="form.event_type" type="radio" name="eventType" :value="type.value" class="peer sr-only" />
                                    <span class="flex flex-col gap-1.5 rounded-2xl border border-ivt-line p-4 transition-all duration-150 peer-checked:-translate-y-0.5 peer-checked:border-ivt-violet peer-checked:bg-ivt-paper-2">
                                        <b class="text-[13.5px] font-semibold text-ivt-ink">{{ type.label }}</b>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="mb-[22px]">
                            <label :class="labelClasses">Locația evenimentului</label>
                            <CountyLocalitySelect
                                v-model:county-id="form.county_id"
                                v-model:locality-id="form.locality_id"
                                :counties="counties"
                                :county-error="form.errors.county_id"
                                :locality-error="form.errors.locality_id"
                            />
                        </div>

                        <div class="mb-[22px] grid gap-[18px] sm:grid-cols-2">
                            <div>
                                <label :class="labelClasses" for="guestCount">Număr aproximativ de invitați</label>
                                <ModalSelect
                                    id="guestCount"
                                    v-model="form.guest_count"
                                    :options="guestCountOptions"
                                    title="Număr aproximativ de invitați"
                                    placeholder="Nespecificat"
                                    clear-label="Nespecificat"
                                    :button-class="fieldClasses"
                                />
                            </div>
                        </div>

                        <div>
                            <label :class="labelClasses" for="reqDate">Data evenimentului</label>
                            <DatePickerModal
                                id="reqDate"
                                v-model="form.event_date"
                                title="Data evenimentului"
                                placeholder="Selectează data"
                                :min-date="minDate"
                                :disabled="flexibleDate"
                                :button-class="fieldClasses"
                            />
                            <p v-if="form.errors.event_date" class="mt-1.5 text-xs text-primary">{{ form.errors.event_date }}</p>
                            <label class="mt-3 flex items-center gap-2.5 text-[13.5px] text-ivt-ink-soft">
                                <input v-model="flexibleDate" type="checkbox" class="h-[15px] w-[15px] rounded border-ivt-line text-primary focus:ring-primary/30" />
                                Data este flexibilă / încă nu am stabilit-o
                            </label>
                        </div>
                    </div>

                    <!-- SECTION 3 -->
                    <div class="mb-6 rounded-[20px] border border-ivt-line p-6 sm:p-8">
                        <div class="mb-[22px] flex items-center gap-3 border-b border-ivt-line pb-[18px]">
                            <span class="flex h-[26px] w-[26px] flex-none items-center justify-center rounded-full bg-ivt-paper-2 text-xs font-bold text-ivt-ink-soft">3</span>
                            <h2 class="font-display text-[19px] font-medium text-ivt-ink">Buget & preferințe</h2>
                        </div>

                        <div class="mb-[22px]">
                            <label :class="labelClasses">Buget estimat (lei)</label>
                            <div class="flex max-w-[420px] items-center gap-3">
                                <input v-model.number="budgetMinRef" type="number" min="0" placeholder="Minim" :class="fieldClasses" />
                                <span class="text-[13px] text-ivt-ink-faint">–</span>
                                <input v-model.number="budgetMaxRef" type="number" min="0" placeholder="Maxim" :class="fieldClasses" />
                            </div>
                        </div>

                        <div>
                            <label :class="labelClasses">Ce este important pentru tine?</label>
                            <div class="flex flex-col gap-2.5">
                                <label v-for="pref in preferenceOptions" :key="pref.value" class="relative cursor-pointer">
                                    <input type="checkbox" :checked="form.preferences.includes(pref.value)" class="peer sr-only" @change="togglePreference(pref.value)" />
                                    <span class="flex items-center gap-3.5 rounded-xl border border-ivt-line px-4 py-3.5 transition-colors duration-150 peer-checked:border-ivt-violet peer-checked:bg-ivt-paper-2">
                                        <span
                                            class="flex h-5 w-5 flex-none items-center justify-center rounded-md border border-ivt-line text-transparent transition-colors duration-150"
                                            :class="{ 'border-ivt-ink bg-ivt-ink text-ivt-accent-bright': form.preferences.includes(pref.value) }"
                                        >
                                            <CheckIcon class="h-3.5 w-3.5" />
                                        </span>
                                        <span>
                                            <span class="block text-sm font-semibold text-ivt-ink">{{ pref.value }}</span>
                                            <span class="block text-xs text-ivt-ink-faint">{{ pref.desc }}</span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4 -->
                    <div class="mb-6 rounded-[20px] border border-ivt-line p-6 sm:p-8">
                        <div class="mb-[22px] flex items-center gap-3 border-b border-ivt-line pb-[18px]">
                            <span class="flex h-[26px] w-[26px] flex-none items-center justify-center rounded-full bg-ivt-paper-2 text-xs font-bold text-ivt-ink-soft">4</span>
                            <h2 class="font-display text-[19px] font-medium text-ivt-ink">Informații de contact</h2>
                        </div>

                        <div class="mb-[22px] grid gap-[18px] sm:grid-cols-2">
                            <div>
                                <label :class="labelClasses" for="contactName">Nume complet</label>
                                <input id="contactName" v-model="form.name" type="text" required :class="fieldClasses" />
                                <p v-if="form.errors.name" class="mt-1.5 text-xs text-primary">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label :class="labelClasses" for="contactPhone">Telefon</label>
                                <input id="contactPhone" v-model="form.phone" type="tel" required :class="fieldClasses" />
                                <p v-if="form.errors.phone" class="mt-1.5 text-xs text-primary">{{ form.errors.phone }}</p>
                            </div>
                        </div>

                        <div class="mb-[22px]">
                            <label :class="labelClasses" for="contactEmail">Email</label>
                            <input id="contactEmail" v-model="form.email" type="email" required :class="fieldClasses" />
                            <p v-if="form.errors.email" class="mt-1.5 text-xs text-primary">{{ form.errors.email }}</p>
                        </div>

                        <div class="mb-[22px]">
                            <label :class="labelClasses">Metodă preferată de contact</label>
                            <div class="flex flex-wrap gap-2.5">
                                <label v-for="method in contactMethods" :key="method.value" class="relative cursor-pointer">
                                    <input v-model="form.contact_method" type="radio" name="method" :value="method.value" class="peer sr-only" />
                                    <span class="flex items-center gap-2 rounded-full border border-ivt-line px-5 py-2.5 text-[13.5px] font-semibold text-ivt-ink-soft transition-all duration-150 peer-checked:border-ivt-ink peer-checked:bg-ivt-ink peer-checked:text-white">
                                        <component :is="method.icon" class="h-4 w-4" />
                                        {{ method.value }}
                                    </span>
                                </label>
                            </div>
                        </div>

                        <label class="flex items-center gap-2.5 text-[13.5px] text-ivt-ink-soft">
                            <input v-model="form.platform_only" type="checkbox" class="h-[15px] w-[15px] rounded border-ivt-line text-primary focus:ring-primary/30" />
                            Prefer să primesc ofertele doar prin mesaje în platformă, nu direct pe telefon
                        </label>
                    </div>

                    <!-- SAVE BAR -->
                    <div class="sticky bottom-0 mt-2 flex flex-wrap items-center justify-between gap-3 bg-gradient-to-t from-ivt-paper from-30% to-transparent py-[18px] pb-10">
                        <span class="text-[12.5px] text-ivt-ink-faint">Ultima modificare: {{ quoteRequest.updated_at }}</span>
                        <div class="ml-auto flex gap-2.5">
                            <button type="button" @click="closeEdit" class="rounded-full border border-ivt-line px-6 py-3 text-sm font-semibold text-ivt-ink transition-colors duration-150 hover:border-ivt-violet hover:text-primary">
                                Anulează
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-b from-primary-bright to-primary px-6 py-3 text-sm font-semibold text-white shadow-sm transition-transform duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(225,29,99,0.35)] disabled:pointer-events-none disabled:opacity-45"
                            >
                                <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                </svg>
                                Salvează modificările
                            </button>
                        </div>
                    </div>
                </form>
            </section>
        </main>

        <SiteFooter />

        <Modal :show="!!offerToDecline" max-width="md" @close="offerToDecline = null">
            <div class="p-6">
                <h3 class="font-display text-lg text-ivt-ink">Refuzi oferta de la {{ offerToDecline?.provider.company_name }}?</h3>
                <p class="mt-1.5 text-sm text-ivt-ink-soft">Poți spune pe scurt de ce, ca furnizorul să înțeleagă (opțional).</p>

                <textarea
                    v-model="declineReason"
                    rows="3"
                    placeholder="Ex: am ales altă ofertă, bugetul nu se potrivește..."
                    class="mt-4 w-full rounded-2xl border-ivt-line text-sm text-ivt-ink placeholder:text-ivt-ink-soft/50 focus:border-primary focus:ring-primary"
                ></textarea>

                <div class="mt-5 flex items-center justify-end gap-3">
                    <button type="button" class="rounded-xl px-4 py-2 text-sm font-medium text-ivt-ink-soft transition-colors hover:bg-ivt-paper" @click="offerToDecline = null; declineReason = '';">
                        Renunță
                    </button>
                    <button
                        type="button"
                        :disabled="busyOfferId === offerToDecline?.id"
                        class="rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:brightness-110 hover:shadow-glow-violet disabled:cursor-not-allowed disabled:opacity-50"
                        @click="confirmDeclineOffer"
                    >
                        Refuză oferta
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>
