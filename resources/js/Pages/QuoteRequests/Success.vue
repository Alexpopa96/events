<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    CheckIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    LockClosedIcon,
    ChevronRightIcon,
    SparklesIcon,
    CalendarDaysIcon,
    MapPinIcon,
    UsersIcon,
    BanknotesIcon,
    TagIcon,
    UserIcon,
    ChatBubbleLeftRightIcon,
    DocumentDuplicateIcon,
    PencilSquareIcon,
    BellAlertIcon,
    ShieldCheckIcon,
    PlusIcon,
    HomeIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';

const props = defineProps({
    quoteRequest: Object,
    package: { type: Array, default: () => [] },
});

// `package` is a reserved word, so it's aliased for use in the template.
const pkg = computed(() => props.package);

const eventTypeLabels = {
    nunta: 'Nuntă',
    botez: 'Botez',
    aniversare: 'Aniversare',
    corporate: 'Corporate',
    'petrecere-privata': 'Petrecere privată',
    concert: 'Concert / Festival',
    altul: 'Altele',
};

const eventTypeLabel = computed(() => eventTypeLabels[props.quoteRequest.event_type] ?? props.quoteRequest.event_type ?? '–');

const locationLabel = computed(() => [props.quoteRequest.city, props.quoteRequest.county].filter(Boolean).join(', ') || '–');

const code = computed(() => `#CR${String(props.quoteRequest.id).padStart(5, '0')}`);

const statusConfig = computed(() => ({
    pending_review: {
        icon: ClockIcon,
        badge: 'from-warning-400 to-warning-600 shadow-glow-accent',
        pill: 'bg-warning-50 text-warning-700 ring-warning-200',
        dot: 'bg-warning-500',
        pillLabel: 'În așteptare aprobare',
        eyebrow: 'Cerere trimisă',
        title: 'Gata! Cererea ta',
        highlight: 'a fost trimisă.',
        description: 'O verificăm rapid ca să respecte regulile platformei — de obicei în mai puțin de 24 de ore. Primești un email imediat ce devine vizibilă furnizorilor.',
    },
    open: {
        icon: CheckCircleIcon,
        badge: 'from-success-400 to-success-600 shadow-glow-success',
        pill: 'bg-success-50 text-success-700 ring-success-200',
        dot: 'bg-success-500',
        pillLabel: 'Publicată',
        eyebrow: 'Cerere publicată',
        title: 'Cererea ta este',
        highlight: 'live acum.',
        description: 'Furnizorii din categoria și zona aleasă o pot vedea deja. Cei interesați te vor contacta direct, pe metoda de contact preferată.',
    },
    rejected: {
        icon: XCircleIcon,
        badge: 'from-danger-400 to-danger-600 shadow-glow-primary',
        pill: 'bg-danger-50 text-danger-700 ring-danger-200',
        dot: 'bg-danger-500',
        pillLabel: 'Respinsă',
        eyebrow: 'Cerere respinsă',
        title: 'Cererea ta',
        highlight: 'nu a fost aprobată.',
        description: 'Cererea nu respectă regulile platformei. Poți publica una nouă, ținând cont de motivul de mai jos.',
    },
    closed: {
        icon: LockClosedIcon,
        badge: 'from-ivt-ink-faint to-ivt-ink-soft shadow-ivt-soft',
        pill: 'bg-ivt-paper-2 text-ivt-ink-soft ring-ivt-line',
        dot: 'bg-ivt-ink-faint',
        pillLabel: 'Închisă',
        eyebrow: 'Cerere închisă',
        title: 'Cererea ta',
        highlight: 'este închisă.',
        description: 'Această cerere nu mai este activă și nu mai este vizibilă furnizorilor.',
    },
}[props.quoteRequest.status]));

const isSuccessful = computed(() => ['pending_review', 'open'].includes(props.quoteRequest.status));

const steps = [
    { title: 'Trimisă', description: 'Cererea a ajuns la noi', icon: CheckIcon },
    { title: 'În verificare', description: 'Moderare de echipa Invita', icon: ShieldCheckIcon },
    { title: 'Publicată', description: 'Vizibilă furnizorilor potriviți', icon: BellAlertIcon },
    { title: 'Primești oferte', description: 'Furnizorii te contactează', icon: ChatBubbleLeftRightIcon },
];

const activeStepIndex = computed(() => (props.quoteRequest.status === 'open' ? 3 : 1));
const progressPercent = computed(() => (activeStepIndex.value / (steps.length - 1)) * 100);

const facts = computed(() => [
    { label: 'Tip eveniment', value: eventTypeLabel.value, icon: SparklesIcon },
    { label: 'Dată', value: props.quoteRequest.event_date || 'Flexibilă', icon: CalendarDaysIcon },
    { label: 'Locație', value: locationLabel.value, icon: MapPinIcon },
    { label: 'Nr. invitați', value: props.quoteRequest.guest_count || '–', icon: UsersIcon },
    { label: 'Buget estimat', value: props.quoteRequest.budget_range || 'Nespecificat', icon: BanknotesIcon },
    { label: 'Contact preferat', value: props.quoteRequest.contact_method || '–', icon: ChatBubbleLeftRightIcon },
]);

const nextSteps = computed(() => (props.quoteRequest.status === 'open'
    ? [
        { icon: BellAlertIcon, title: 'Fii atent la notificări', text: 'Furnizorii interesați te contactează direct, pe metoda aleasă.' },
        { icon: ChatBubbleLeftRightIcon, title: 'Compară ofertele', text: 'Le găsești pe toate în „Cererile mele”, alături de cerere.' },
        { icon: CheckCircleIcon, title: 'Alege furnizorul', text: 'Închide cererea când ai găsit ce căutai.' },
    ]
    : [
        { icon: ShieldCheckIcon, title: 'Verificăm cererea', text: 'De obicei în mai puțin de 24 de ore.' },
        { icon: BellAlertIcon, title: 'Primești un email', text: 'Imediat ce cererea devine vizibilă furnizorilor.' },
        { icon: ChatBubbleLeftRightIcon, title: 'Vin ofertele', text: 'Furnizorii potriviți te contactează direct.' },
    ]));

const copied = ref(false);
let copiedTimer = null;

async function copyCode() {
    try {
        await navigator.clipboard.writeText(code.value);
        copied.value = true;
        clearTimeout(copiedTimer);
        copiedTimer = setTimeout(() => (copied.value = false), 1800);
    } catch {
        // Clipboard can be unavailable (insecure context); the code stays visible anyway.
    }
}

// Decorative confetti around the success badge: [left %, top %, size px, colour, delay s].
const confetti = [
    [6, 18, 8, 'bg-primary', 0], [16, 70, 6, 'bg-ivt-violet', 0.4], [28, 8, 5, 'bg-ivt-accent-bright', 0.8],
    [72, 12, 7, 'bg-ivt-violet', 0.2], [86, 62, 9, 'bg-primary', 0.6], [94, 22, 5, 'bg-ivt-accent-bright', 1],
    [60, 86, 6, 'bg-ivt-teal', 0.3], [38, 90, 5, 'bg-primary-bright', 0.9],
];
</script>

<template>
    <Head title="Cererea ta a fost trimisă — Invita" />

    <div class="overflow-x-clip bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <!-- HERO -->
            <section class="relative pt-8 lg:pt-10">
                <div class="pointer-events-none absolute inset-0 overflow-hidden [mask-image:linear-gradient(to_bottom,#000_60%,transparent)]" aria-hidden="true">
                    <div class="absolute -left-40 -top-40 h-[520px] w-[520px] rounded-full bg-primary/15 blur-3xl animate-float-slow" />
                    <div class="absolute -right-32 top-10 h-[460px] w-[460px] rounded-full bg-ivt-violet/15 blur-3xl animate-float-slower" />
                    <div class="absolute bottom-0 left-1/3 h-[300px] w-[300px] rounded-full bg-ivt-accent-bright/15 blur-3xl animate-float-slower" />
                    <div
                        class="absolute inset-0 opacity-[0.35]"
                        style="background-image: radial-gradient(rgba(26,20,51,0.12) 1px, transparent 1px); background-size: 22px 22px; mask-image: radial-gradient(ellipse 60% 55% at 50% 35%, #000 30%, transparent 75%);"
                    />
                </div>

                <div class="relative mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <nav class="flex flex-wrap items-center gap-2 text-[13px] text-ivt-ink-faint" aria-label="Breadcrumb">
                        <Link :href="route('home')" class="transition-colors hover:text-primary">Acasă</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <Link :href="route('quote-requests.index')" class="transition-colors hover:text-primary">Cererile mele</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <span class="text-ivt-ink-soft">{{ code }}</span>
                    </nav>

                    <div class="mx-auto flex max-w-2xl flex-col items-center pb-12 pt-10 text-center lg:pb-16 lg:pt-14">
                        <!-- Badge with confetti -->
                        <div v-reveal class="relative h-28 w-40">
                            <template v-if="isSuccessful">
                                <span
                                    v-for="([left, top, size, color, delay], i) in confetti"
                                    :key="i"
                                    class="absolute rounded-full opacity-80 animate-float-slow"
                                    :class="[color, i % 3 === 0 ? 'rounded-sm rotate-45' : '']"
                                    :style="{ left: `${left}%`, top: `${top}%`, width: `${size}px`, height: `${size}px`, animationDelay: `${delay}s` }"
                                    aria-hidden="true"
                                />
                            </template>
                            <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                                <span class="absolute -inset-3 rounded-full bg-gradient-to-br from-primary/25 via-ivt-violet/20 to-ivt-accent-bright/25 blur-md" aria-hidden="true" />
                                <span class="absolute -inset-1.5 rounded-full border border-dashed border-ivt-violet/30 animate-spin-slow" aria-hidden="true" />
                                <span class="relative flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br text-white ring-4 ring-white" :class="statusConfig.badge">
                                    <component :is="statusConfig.icon" class="h-10 w-10" stroke-width="1.75" />
                                </span>
                            </div>
                        </div>

                        <p class="mt-6 inline-flex items-center gap-2 rounded-full px-3.5 py-1 text-[12px] font-bold uppercase tracking-[0.08em] ring-1" :class="statusConfig.pill">
                            <span class="relative flex h-2 w-2">
                                <span v-if="isSuccessful" class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-60" :class="statusConfig.dot" />
                                <span class="relative inline-flex h-2 w-2 rounded-full" :class="statusConfig.dot" />
                            </span>
                            {{ statusConfig.pillLabel }}
                        </p>

                        <h1 class="mt-5 text-balance font-display text-[34px] font-bold leading-[1.08] tracking-tight text-ivt-ink sm:text-[44px] lg:text-[52px]">
                            {{ statusConfig.title }}
                            <span class="relative whitespace-nowrap">
                                <span class="text-gradient">{{ statusConfig.highlight }}</span>
                                <svg class="absolute -bottom-2 left-0 h-3 w-full text-ivt-accent-bright" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true">
                                    <path d="M2 9 C 80 2, 200 2, 298 7" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                </svg>
                            </span>
                        </h1>

                        <p class="mt-6 max-w-xl text-[15.5px] leading-relaxed text-ivt-ink-soft">{{ statusConfig.description }}</p>

                        <div
                            v-if="quoteRequest.status === 'rejected' && quoteRequest.rejection_reason"
                            class="mt-6 flex w-full max-w-xl items-start gap-3 rounded-2xl border border-danger-200 bg-danger-50 p-4 text-left"
                        >
                            <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 flex-none text-danger-600" />
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.08em] text-danger-700">Motivul respingerii</p>
                                <p class="mt-1 text-sm leading-relaxed text-ivt-ink">{{ quoteRequest.rejection_reason }}</p>
                            </div>
                        </div>

                        <div class="mt-7 flex flex-wrap items-center justify-center gap-2.5 text-[13px]">
                            <span class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 px-3.5 py-1.5 text-ivt-ink-soft shadow-sm backdrop-blur">
                                <ClockIcon class="h-4 w-4 text-ivt-violet" />
                                Trimisă pe <b class="font-semibold text-ivt-ink">{{ quoteRequest.created_at }}</b>
                            </span>
                            <button
                                type="button"
                                class="group inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 px-3.5 py-1.5 text-ivt-ink-soft shadow-sm backdrop-blur transition-colors hover:border-ivt-violet/40 hover:text-ivt-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                                :aria-label="`Copiază codul cererii ${code}`"
                                @click="copyCode"
                            >
                                <TagIcon class="h-4 w-4 text-ivt-violet" />
                                Cod <b class="font-semibold text-ivt-ink">{{ code }}</b>
                                <CheckIcon v-if="copied" class="h-3.5 w-3.5 text-success-600" />
                                <DocumentDuplicateIcon v-else class="h-3.5 w-3.5 text-ivt-ink-faint transition-colors group-hover:text-primary" />
                            </button>
                        </div>
                    </div>

                    <!-- PROGRESS -->
                    <div v-if="isSuccessful" v-reveal class="mx-auto max-w-4xl rounded-[26px] border border-ivt-line bg-white/90 p-5 shadow-ivt-soft backdrop-blur sm:p-7">
                        <div class="relative">
                            <!-- Track (desktop) -->
                            <div class="absolute left-[12.5%] right-[12.5%] top-5 hidden h-1 rounded-full bg-ivt-paper-3 sm:block" aria-hidden="true">
                                <div class="h-full rounded-full bg-brand transition-[width] duration-1000" :style="{ width: `${progressPercent}%` }" />
                            </div>

                            <ol class="relative grid gap-4 sm:grid-cols-4 sm:gap-2">
                                <li v-for="(step, index) in steps" :key="step.title" class="flex items-center gap-4 sm:flex-col sm:gap-3 sm:text-center">
                                    <span
                                        class="relative flex h-10 w-10 flex-none items-center justify-center rounded-full text-sm font-bold ring-4 ring-white transition-all"
                                        :class="index < activeStepIndex
                                            ? 'bg-brand text-white shadow-glow-primary'
                                            : index === activeStepIndex
                                                ? 'bg-white text-primary ring-primary/20 animate-pulse-glow'
                                                : 'bg-ivt-paper-2 text-ivt-ink-faint'"
                                    >
                                        <CheckIcon v-if="index < activeStepIndex" class="h-5 w-5" stroke-width="2.5" />
                                        <component :is="step.icon" v-else class="h-5 w-5" />
                                    </span>
                                    <span class="min-w-0">
                                        <b class="block text-[13.5px]" :class="index <= activeStepIndex ? 'text-ivt-ink' : 'text-ivt-ink-faint'">{{ step.title }}</b>
                                        <span class="block text-[12px] text-ivt-ink-faint">
                                            <template v-if="index === activeStepIndex">
                                                <span class="font-semibold text-primary">Acum · </span>
                                            </template>{{ step.description }}
                                        </span>
                                    </span>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>

            <!-- DETAILS + NEXT STEPS -->
            <section class="py-12 pb-24 lg:py-16 lg:pb-28">
                <div class="mx-auto grid max-w-6xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8">
                    <!-- Details -->
                    <div v-reveal class="space-y-6">
                        <div class="overflow-hidden rounded-[26px] border border-ivt-line bg-white shadow-ivt-soft">
                            <div class="relative border-b border-ivt-line bg-brand-soft px-6 py-6 sm:px-8">
                                <p class="inline-flex items-center gap-1.5 rounded-full bg-white/80 px-3 py-1 text-[11.5px] font-bold uppercase tracking-[0.08em] text-ivt-violet">
                                    <TagIcon class="h-3.5 w-3.5" /> {{ quoteRequest.category }}
                                </p>
                                <h2 class="mt-3 font-display text-[22px] font-bold leading-snug text-ivt-ink sm:text-[26px]">{{ quoteRequest.title }}</h2>
                                <p v-if="quoteRequest.message" class="mt-2 whitespace-pre-line text-[14.5px] leading-relaxed text-ivt-ink-soft">{{ quoteRequest.message }}</p>
                            </div>

                            <dl class="grid gap-px bg-ivt-line sm:grid-cols-2 lg:grid-cols-3">
                                <div v-for="fact in facts" :key="fact.label" class="group flex items-center gap-3.5 bg-white px-6 py-5 transition-colors hover:bg-ivt-paper-2">
                                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-ivt-violet-soft text-ivt-violet transition-all group-hover:scale-105 group-hover:bg-brand group-hover:text-white">
                                        <component :is="fact.icon" class="h-5 w-5" />
                                    </span>
                                    <div class="min-w-0">
                                        <dt class="text-[11px] font-bold uppercase tracking-[0.08em] text-ivt-ink-faint">{{ fact.label }}</dt>
                                        <dd class="truncate text-[14.5px] font-semibold text-ivt-ink" :title="String(fact.value)">{{ fact.value }}</dd>
                                    </div>
                                </div>
                            </dl>

                            <div class="flex flex-col gap-4 border-t border-ivt-line px-6 py-5 sm:flex-row sm:items-start sm:px-8">
                                <div class="flex flex-none items-center gap-2 text-[11px] font-bold uppercase tracking-[0.08em] text-ivt-ink-faint sm:w-32 sm:pt-1.5">
                                    <UserIcon class="h-4 w-4" /> Contact
                                </div>
                                <p class="text-[14.5px] font-semibold text-ivt-ink">{{ quoteRequest.name || '–' }}</p>
                            </div>

                            <div class="flex flex-col gap-3 border-t border-ivt-line px-6 py-5 sm:flex-row sm:items-start sm:px-8">
                                <div class="flex flex-none items-center gap-2 text-[11px] font-bold uppercase tracking-[0.08em] text-ivt-ink-faint sm:w-32 sm:pt-1.5">
                                    <SparklesIcon class="h-4 w-4" /> Preferințe
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <template v-if="quoteRequest.preferences && quoteRequest.preferences.length">
                                        <span v-for="pref in quoteRequest.preferences" :key="pref" class="rounded-full bg-ivt-paper-2 px-3 py-1.5 text-xs font-semibold text-ivt-ink-soft ring-1 ring-ivt-line">{{ pref }}</span>
                                    </template>
                                    <span v-else class="pt-1 text-[14px] text-ivt-ink-faint">Niciuna selectată</span>
                                </div>
                            </div>
                        </div>

                        <!-- Package -->
                        <div v-if="pkg.length" class="relative overflow-hidden rounded-[26px] bg-gradient-to-br from-ivt-ink-deep via-ivt-ink to-ivt-ink-2 p-6 text-ivt-on-dark shadow-ivt-deep sm:p-8">
                            <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-ivt-violet/30 blur-3xl" aria-hidden="true" />
                            <div class="pointer-events-none absolute -bottom-20 left-10 h-48 w-48 rounded-full bg-primary/25 blur-3xl" aria-hidden="true" />
                            <div class="relative">
                                <p class="inline-flex items-center gap-1.5 text-[11.5px] font-bold uppercase tracking-[0.1em] text-ivt-accent-bright">
                                    <SparklesIcon class="h-4 w-4" /> Cerere de tip pachet
                                </p>
                                <h3 class="mt-2 font-display text-xl font-bold">
                                    <span class="text-gradient-light">{{ pkg.length + 1 }} servicii</span>, o singură cerere
                                </h3>
                                <p class="mt-2 max-w-xl text-[14px] leading-relaxed text-ivt-on-dark-dim">
                                    Am publicat câte o cerere pentru fiecare serviciu, ca fiecare furnizor s-o vadă în categoria lui. Toate au aceleași detalii de eveniment și contact.
                                </p>
                                <div class="mt-5 flex flex-wrap gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand px-3.5 py-1.5 text-xs font-semibold text-white shadow-glow-primary">
                                        <CheckIcon class="h-3.5 w-3.5" stroke-width="2.5" /> {{ quoteRequest.category }}
                                    </span>
                                    <span v-for="item in pkg" :key="item.id" class="rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-semibold text-ivt-on-dark ring-1 ring-white/15 backdrop-blur">
                                        {{ item.category }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <aside v-reveal class="space-y-6 lg:sticky lg:top-24 lg:self-start">
                        <div v-if="isSuccessful" class="rounded-[26px] border border-ivt-line bg-white p-6 shadow-ivt-soft">
                            <h3 class="font-display text-lg font-bold text-ivt-ink">Ce urmează?</h3>
                            <ol class="relative mt-5 space-y-5 before:absolute before:bottom-3 before:left-[17px] before:top-3 before:w-px before:bg-gradient-to-b before:from-primary/40 before:to-ivt-violet/10">
                                <li v-for="item in nextSteps" :key="item.title" class="relative flex gap-3.5">
                                    <span class="relative flex h-9 w-9 flex-none items-center justify-center rounded-full bg-ivt-violet-soft text-ivt-violet ring-4 ring-white">
                                        <component :is="item.icon" class="h-[18px] w-[18px]" />
                                    </span>
                                    <div class="pt-0.5">
                                        <p class="text-[14px] font-semibold text-ivt-ink">{{ item.title }}</p>
                                        <p class="mt-0.5 text-[13px] leading-relaxed text-ivt-ink-soft">{{ item.text }}</p>
                                    </div>
                                </li>
                            </ol>
                        </div>

                        <div class="rounded-[26px] border border-ivt-line bg-white p-6 shadow-ivt-soft">
                            <div class="flex flex-col gap-2.5">
                                <Link :href="route('quote-requests.index')" class="btn-brand px-6 py-3.5 text-sm">
                                    Vezi cererile mele
                                    <ChevronRightIcon class="h-4 w-4" />
                                </Link>
                                <Link
                                    :href="route('quote-requests.create')"
                                    class="inline-flex items-center justify-center gap-2 rounded-full border border-ivt-line px-6 py-3 text-sm font-semibold text-ivt-ink transition-colors hover:border-ivt-violet/50 hover:bg-ivt-paper-2 hover:text-primary"
                                >
                                    <PlusIcon class="h-4 w-4" /> Publică o altă cerere
                                </Link>
                                <Link
                                    :href="route('home')"
                                    class="inline-flex items-center justify-center gap-2 rounded-full px-6 py-2.5 text-sm font-semibold text-ivt-ink-soft transition-colors hover:text-primary"
                                >
                                    <HomeIcon class="h-4 w-4" /> Înapoi acasă
                                </Link>
                            </div>
                        </div>

                        <div v-if="quoteRequest.status === 'pending_review'" class="flex items-start gap-3 rounded-2xl bg-ivt-accent-soft p-5">
                            <PencilSquareIcon class="mt-0.5 h-5 w-5 flex-none text-ivt-accent" />
                            <p class="text-[13px] leading-relaxed text-ivt-ink-soft">
                                <b class="text-ivt-ink">Ai uitat ceva?</b> Poți
                                <Link :href="route('quote-requests.show', quoteRequest.id)" class="font-semibold text-primary underline decoration-primary/30 underline-offset-2 hover:decoration-primary">edita cererea</Link>
                                cât timp e în așteptare. După publicare, modificările majore trec din nou prin verificare.
                            </p>
                        </div>
                    </aside>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
