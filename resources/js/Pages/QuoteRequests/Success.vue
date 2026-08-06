<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    CheckIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    LockClosedIcon,
    InformationCircleIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';

const props = defineProps({
    quoteRequest: Object,
});

const eventTypeLabels = {
    nunta: 'Nuntă',
    botez: 'Botez',
    aniversare: 'Aniversare',
    corporate: 'Corporate',
    'petrecere-privata': 'Petrecere privată',
    concert: 'Concert / Festival',
    altul: 'Altele',
};

const eventTypeLabel = computed(() => eventTypeLabels[props.quoteRequest.event_type] ?? props.quoteRequest.event_type);

const locationLabel = computed(() => [props.quoteRequest.city, props.quoteRequest.county].filter(Boolean).join(', ') || '–');

const statusConfig = computed(() => ({
    pending_review: {
        iconBg: 'bg-amber-500',
        icon: ClockIcon,
        bannerBg: 'bg-amber-50 border-amber-200',
        pillBg: 'bg-amber-500',
        pillLabel: 'În așteptare aprobare',
        title: 'Cererea ta a fost trimisă spre aprobare',
        description: 'O verificăm rapid pentru a ne asigura că respectă regulile platformei. De obicei durează sub 24 de ore — vei primi un email de confirmare imediat ce cererea devine vizibilă furnizorilor.',
    },
    open: {
        iconBg: 'bg-emerald-500',
        icon: CheckCircleIcon,
        bannerBg: 'bg-emerald-50 border-emerald-200',
        pillBg: 'bg-emerald-500',
        pillLabel: 'Publicată',
        title: 'Cererea ta este publicată',
        description: 'Cererea ta este acum vizibilă furnizorilor din categoria și zona aleasă. Furnizorii interesați te vor contacta direct pe metoda de contact preferată.',
    },
    rejected: {
        iconBg: 'bg-ivt-wine',
        icon: XCircleIcon,
        bannerBg: 'bg-red-50 border-red-200',
        pillBg: 'bg-ivt-wine',
        pillLabel: 'Respinsă',
        title: 'Cererea ta a fost respinsă',
        description: props.quoteRequest.rejection_reason || 'Cererea nu respectă regulile platformei. Poți publica o cerere nouă, ținând cont de motivul de mai jos.',
    },
    closed: {
        iconBg: 'bg-ivt-ink-faint',
        icon: LockClosedIcon,
        bannerBg: 'bg-ivt-paper-2 border-ivt-line',
        pillBg: 'bg-ivt-ink-faint',
        pillLabel: 'Închisă',
        title: 'Cererea ta este închisă',
        description: 'Această cerere nu mai este activă și nu mai este vizibilă furnizorilor.',
    },
}[props.quoteRequest.status]));

const steps = [
    { title: 'Trimisă', description: 'Cererea a ajuns la noi' },
    { title: 'În verificare', description: 'Moderare de către echipa Invita' },
    { title: 'Publicată', description: 'Vizibilă furnizorilor potriviți' },
    { title: 'Primești oferte', description: 'Furnizorii te contactează direct' },
];

const showTimeline = computed(() => ['pending_review', 'open'].includes(props.quoteRequest.status));
const activeStepIndex = computed(() => (props.quoteRequest.status === 'open' ? 3 : 1));
</script>

<template>
    <Head title="Cererea ta a fost trimisă — Invita" />

    <div class="bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <!-- STATUS BANNER -->
            <section class="pt-10 pb-4">
                <div class="mx-auto max-w-4xl px-6 lg:px-8">
                    <p class="mb-1.5 flex flex-wrap items-center gap-2 text-[13px] text-ivt-ink-faint">
                        <Link :href="route('home')" class="text-ivt-ink-soft transition-colors hover:text-ivt-wine">Acasă</Link>
                        <span>›</span>
                        <Link :href="route('quote-requests.create')" class="text-ivt-ink-soft transition-colors hover:text-ivt-wine">Cereri de ofertă</Link>
                        <span>›</span>
                        <span>Cererea mea</span>
                    </p>

                    <div class="flex flex-col items-start gap-5 rounded-[22px] border p-8 sm:flex-row" :class="statusConfig.bannerBg">
                        <span class="flex h-14 w-14 flex-none items-center justify-center rounded-2xl text-white" :class="statusConfig.iconBg">
                            <component :is="statusConfig.icon" class="h-6 w-6" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <h1 class="font-serif text-2xl font-medium text-ivt-ink">{{ statusConfig.title }}</h1>
                            <p class="mt-2 max-w-xl text-[14.5px] leading-relaxed text-ivt-ink-soft">{{ statusConfig.description }}</p>
                            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-1.5 text-[12.5px] text-ivt-ink-soft">
                                <span>Trimisă pe <b class="text-ivt-ink">{{ quoteRequest.created_at }}</b></span>
                                <span>Cod cerere <b class="text-ivt-ink">#CR{{ String(quoteRequest.id).padStart(5, '0') }}</b></span>
                            </div>
                        </div>
                        <span class="flex-none rounded-full px-4 py-2 text-[11.5px] font-bold uppercase tracking-[0.05em] text-white" :class="statusConfig.pillBg">
                            {{ statusConfig.pillLabel }}
                        </span>
                    </div>
                </div>
            </section>

            <!-- TIMELINE -->
            <section v-if="showTimeline" class="py-10">
                <div class="mx-auto max-w-4xl px-6 lg:px-8">
                    <div class="relative flex items-start justify-between">
                        <span class="pointer-events-none absolute left-[34px] right-[34px] top-[17px] h-px bg-ivt-line" />
                        <div v-for="(step, index) in steps" :key="step.title" class="relative z-10 flex flex-1 flex-col items-center gap-2.5 text-center">
                            <span
                                class="flex h-[34px] w-[34px] items-center justify-center rounded-full border text-sm font-bold"
                                :class="index < activeStepIndex
                                    ? 'border-ivt-gold-bright bg-ivt-gold-bright text-[#221708]'
                                    : index === activeStepIndex
                                        ? 'border-amber-500 bg-amber-500 text-white'
                                        : 'border-ivt-line bg-white text-ivt-ink-faint'"
                            >
                                <CheckIcon v-if="index < activeStepIndex" class="h-4 w-4" />
                                <template v-else>{{ index + 1 }}</template>
                            </span>
                            <b class="text-[13px] text-ivt-ink">{{ step.title }}</b>
                            <span class="max-w-[120px] text-[11.5px] text-ivt-ink-faint">{{ step.description }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- DETAILS -->
            <section class="py-10 pb-24">
                <div class="mx-auto max-w-4xl px-6 lg:px-8">
                    <div class="rounded-[22px] border border-ivt-line bg-white p-9 shadow-ivt-soft">
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2.5">
                            <h2 class="font-serif text-[22px] font-medium text-ivt-ink">Detaliile cererii tale</h2>
                            <span class="text-[12.5px] text-ivt-ink-faint">Cod cerere <b class="font-bold text-ivt-ink">#CR{{ String(quoteRequest.id).padStart(5, '0') }}</b></span>
                        </div>
                        <p class="mb-7 text-[15px] text-ivt-ink-soft">{{ quoteRequest.title }}</p>

                        <div class="mb-4 rounded-2xl border border-ivt-line p-5">
                            <h4 class="mb-3.5 text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Căutare</h4>
                            <div class="flex justify-between gap-4 border-b border-ivt-line py-2 text-sm"><span class="text-ivt-ink-faint">Categorie</span><span class="text-right font-semibold text-ivt-ink">{{ quoteRequest.category }}</span></div>
                            <div v-if="quoteRequest.message" class="flex justify-between gap-4 py-2 text-sm"><span class="flex-none text-ivt-ink-faint">Descriere</span><span class="text-right font-semibold text-ivt-ink">{{ quoteRequest.message }}</span></div>
                        </div>

                        <div class="mb-4 rounded-2xl border border-ivt-line p-5">
                            <h4 class="mb-3.5 text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Eveniment</h4>
                            <div class="flex justify-between gap-4 border-b border-ivt-line py-2 text-sm"><span class="text-ivt-ink-faint">Tip eveniment</span><span class="text-right font-semibold text-ivt-ink">{{ eventTypeLabel }}</span></div>
                            <div class="flex justify-between gap-4 border-b border-ivt-line py-2 text-sm"><span class="text-ivt-ink-faint">Locație</span><span class="text-right font-semibold text-ivt-ink">{{ locationLabel }}</span></div>
                            <div class="flex justify-between gap-4 border-b border-ivt-line py-2 text-sm"><span class="text-ivt-ink-faint">Dată</span><span class="text-right font-semibold text-ivt-ink">{{ quoteRequest.event_date || 'Flexibilă' }}</span></div>
                            <div class="flex justify-between gap-4 py-2 text-sm"><span class="text-ivt-ink-faint">Nr. invitați</span><span class="text-right font-semibold text-ivt-ink">{{ quoteRequest.guest_count || '–' }}</span></div>
                        </div>

                        <div class="mb-4 rounded-2xl border border-ivt-line p-5">
                            <h4 class="mb-3.5 text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Buget & preferințe</h4>
                            <div class="flex justify-between gap-4 border-b border-ivt-line py-2 text-sm"><span class="text-ivt-ink-faint">Buget estimat</span><span class="text-right font-semibold text-ivt-ink">{{ quoteRequest.budget_range || 'Nespecificat' }}</span></div>
                            <div class="flex items-start justify-between gap-4 py-2 text-sm">
                                <span class="flex-none pt-1 text-ivt-ink-faint">Preferințe</span>
                                <span class="flex flex-wrap justify-end gap-2">
                                    <template v-if="quoteRequest.preferences && quoteRequest.preferences.length">
                                        <span v-for="pref in quoteRequest.preferences" :key="pref" class="rounded-full bg-ivt-paper-2 px-3 py-1.5 text-xs text-ivt-ink-soft">{{ pref }}</span>
                                    </template>
                                    <span v-else class="pt-1 font-semibold text-ivt-ink">Niciuna selectată</span>
                                </span>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-ivt-line p-5">
                            <h4 class="mb-3.5 text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Contact</h4>
                            <div class="flex justify-between gap-4 border-b border-ivt-line py-2 text-sm"><span class="text-ivt-ink-faint">Nume</span><span class="text-right font-semibold text-ivt-ink">{{ quoteRequest.name || '–' }}</span></div>
                            <div class="flex justify-between gap-4 py-2 text-sm"><span class="text-ivt-ink-faint">Metodă preferată</span><span class="text-right font-semibold text-ivt-ink">{{ quoteRequest.contact_method || '–' }}</span></div>
                        </div>

                        <div class="mt-6 flex items-start gap-3.5 rounded-2xl bg-ivt-paper-2 p-5">
                            <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-ivt-ink text-ivt-gold-bright">
                                <InformationCircleIcon class="h-4 w-4" />
                            </span>
                            <p class="text-[13.5px] leading-relaxed text-ivt-ink-soft">
                                <b class="text-ivt-ink">Poți edita cererea</b> cât timp este în așteptare aprobare, din contul tău — secțiunea „Cererile mele”. După publicare, orice modificare majoră trece din nou prin verificare.
                            </p>
                        </div>

                        <div class="mt-8 flex flex-wrap gap-3">
                            <Link
                                :href="route('quote-requests.index')"
                                class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-b from-ivt-ink-2 to-ivt-ink px-6 py-3 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(22,40,31,0.4)]"
                            >
                                Vezi cererile mele
                                <ChevronRightIcon class="h-4 w-4" />
                            </Link>
                            <Link
                                :href="route('quote-requests.create')"
                                class="inline-flex items-center gap-1.5 rounded-full border border-ivt-line px-6 py-3 text-sm font-semibold text-ivt-ink transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
                            >
                                Publică o altă cerere
                            </Link>
                            <Link
                                :href="route('home')"
                                class="inline-flex items-center gap-1.5 rounded-full border border-ivt-line px-6 py-3 text-sm font-semibold text-ivt-ink transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
                            >
                                Înapoi la pagina principală
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
