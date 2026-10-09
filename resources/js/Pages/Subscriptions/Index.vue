<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRightIcon, CheckIcon, ChevronDownIcon, MinusIcon } from '@heroicons/vue/24/outline';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import SectionHeading from '@/Components/Home/SectionHeading.vue';
import PlanCards from '@/Components/Subscriptions/PlanCards.vue';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ providers: 0 }) },
});

const page = usePage();

const ctaHref = computed(() => (page.props.auth.isProvider ? route('provider.subscription.index') : '/register'));

/* null limit = unlimited; 0 = not included. */
const limit = (value) => {
    if (value === null || value === undefined) return 'Nelimitat';
    return value === 0 ? false : value.toLocaleString('ro-RO');
};

const comparisonRows = [
    { label: 'Preț lunar', value: (plan) => (plan.price > 0 ? `${plan.price.toLocaleString('ro-RO')} lei` : 'Gratuit') },
    { label: 'Anunțuri active', value: (plan) => limit(plan.max_listings) },
    { label: 'Fotografii per anunț', value: (plan) => limit(plan.max_photos_per_listing) },
    { label: 'Videoclipuri per anunț', value: (plan) => limit(plan.max_videos_per_listing) },
    { label: 'Poziționare prioritară în căutare', value: (plan) => plan.allows_featured_placement },
    { label: 'Profil companie public', value: () => true },
    { label: 'Contact direct de la clienți', value: () => true },
    { label: 'Fără comision pe rezervare', value: () => true },
];

const faqs = [
    {
        question: 'Plătesc comision pentru evenimentele obținute prin platformă?',
        answer: 'Nu. Plătești doar abonamentul lunar. Clienții te contactează direct, iar înțelegerea cu ei rămâne în totalitate a ta.',
    },
    {
        question: 'Pot schimba planul mai târziu?',
        answer: 'Da, oricând din dashboard, secțiunea „Abonament & facturi". Treci pe un plan superior sau inferior în câteva clicuri.',
    },
    {
        question: 'Ce se întâmplă cu anunțurile dacă trec pe un plan mai mic?',
        answer: 'Anunțurile existente nu se șterg. Limita noului plan se aplică doar când publici anunțuri noi.',
    },
    {
        question: 'Primesc factură?',
        answer: 'Da. Toate facturile le găsești în dashboard, la „Abonament & facturi", de unde le poți descărca oricând.',
    },
    {
        question: 'Pot începe gratuit?',
        answer: 'Da. Planul Gratuit îți dă un anunț activ și un profil de companie, fără card și fără perioadă limitată.',
    },
];
</script>

<template>
    <Head :title="$page.props.seo?.full_title ?? 'Abonamente — Invita'" />

    <div class="overflow-x-clip bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <!-- PAGE HERO -->
            <section class="relative pb-8 pt-8 lg:pb-12 lg:pt-10">
                <div class="pointer-events-none absolute inset-0 overflow-hidden [mask-image:linear-gradient(to_bottom,#000_55%,transparent)]">
                    <div class="absolute -left-40 -top-40 h-[520px] w-[520px] rounded-full bg-primary/15 blur-3xl animate-float-slow" />
                    <div class="absolute -right-32 top-10 h-[460px] w-[460px] rounded-full bg-ivt-violet/15 blur-3xl animate-float-slower" />
                    <div
                        class="absolute inset-0 opacity-[0.35]"
                        style="background-image: radial-gradient(rgba(26,20,51,0.12) 1px, transparent 1px); background-size: 22px 22px; mask-image: radial-gradient(ellipse 70% 60% at 30% 30%, #000 30%, transparent 75%);"
                    />
                </div>

                <div class="relative mx-auto max-w-[1600px] px-6 lg:px-8">
                    <nav class="flex items-center gap-2 text-[13px] text-ivt-ink-faint" aria-label="Breadcrumb">
                        <Link :href="route('home')" class="transition-colors hover:text-primary">Acasă</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <span class="text-ivt-ink-soft">Abonamente</span>
                    </nav>

                    <div class="mx-auto mt-8 max-w-3xl text-center lg:mt-10">
                        <p class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 py-1 pl-1.5 pr-3.5 text-[12.5px] font-semibold text-ivt-ink-soft shadow-sm backdrop-blur">
                            <span class="rounded-full bg-brand px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider text-white">0%</span>
                            comision pe rezervare
                        </p>

                        <h1 class="mt-6 text-balance font-display text-[34px] font-bold leading-[1.05] tracking-tight text-ivt-ink sm:text-[44px] lg:text-[52px]">
                            Un abonament simplu,
                            <span class="text-gradient">clienți direct la tine.</span>
                        </h1>

                        <p class="mx-auto mt-6 max-w-[520px] text-[15.5px] leading-relaxed text-ivt-ink-soft">
                            Alegi planul potrivit afacerii tale, îți publici anunțurile și primești contacte direct de la clienți.
                            <template v-if="stats.providers > 0">Alătură-te celor {{ stats.providers.toLocaleString('ro-RO') }} de furnizori activi.</template>
                        </p>
                    </div>
                </div>
            </section>

            <!-- PLANS -->
            <section v-if="plans.length" class="pb-16 pt-8 lg:pb-24 lg:pt-12">
                <div class="mx-auto max-w-[1600px] px-6 lg:px-8">
                    <PlanCards :plans="plans" />
                    <p class="mt-10 text-center text-[13px] text-ivt-ink-faint">Prețurile sunt în lei, pe lună. Poți schimba sau anula planul oricând.</p>
                </div>
            </section>

            <!-- COMPARISON -->
            <section v-if="plans.length" class="bg-ivt-paper-2 py-16 lg:py-24">
                <div class="mx-auto max-w-[1100px] px-6 lg:px-8">
                    <SectionHeading v-reveal eyebrow="Comparație" title="Ce primești în fiecare plan." />

                    <div v-reveal class="mt-12 overflow-x-auto rounded-[22px] border border-ivt-line bg-white">
                        <table class="w-full min-w-[560px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-ivt-line">
                                    <th class="px-6 py-5 font-semibold text-ivt-ink-faint">Funcționalitate</th>
                                    <th v-for="plan in plans" :key="plan.slug" class="px-6 py-5 text-center font-display text-base font-medium text-ivt-ink">
                                        {{ plan.name }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in comparisonRows" :key="row.label" class="border-b border-ivt-line last:border-0">
                                    <td class="px-6 py-4 text-ivt-ink-soft">{{ row.label }}</td>
                                    <td v-for="plan in plans" :key="plan.slug" class="px-6 py-4 text-center">
                                        <template v-if="row.value(plan) === true">
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                <CheckIcon class="h-3 w-3" stroke-width="3" />
                                                <span class="sr-only">Inclus</span>
                                            </span>
                                        </template>
                                        <template v-else-if="row.value(plan) === false">
                                            <MinusIcon class="mx-auto h-4 w-4 text-ivt-ink-faint" />
                                            <span class="sr-only">Nu este inclus</span>
                                        </template>
                                        <span v-else class="font-semibold text-ivt-ink">{{ row.value(plan) }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- FAQ -->
            <section class="py-16 lg:py-24">
                <div class="mx-auto max-w-3xl px-6 lg:px-8">
                    <SectionHeading v-reveal eyebrow="Întrebări frecvente" title="Ce întreabă furnizorii înainte să aleagă." />

                    <div class="mt-12 divide-y divide-ivt-line rounded-[22px] border border-ivt-line bg-white">
                        <details v-for="faq in faqs" :key="faq.question" class="group px-6 py-5">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-ivt-ink [&::-webkit-details-marker]:hidden">
                                {{ faq.question }}
                                <ChevronDownIcon class="h-4 w-4 flex-none text-ivt-ink-faint transition-transform group-open:rotate-180" />
                            </summary>
                            <p class="mt-3 text-[14.5px] leading-relaxed text-ivt-ink-soft">{{ faq.answer }}</p>
                        </details>
                    </div>
                </div>
            </section>

            <!-- CTA -->
            <section class="mx-auto max-w-[1600px] px-6 pb-20 lg:px-8">
                <div v-reveal class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-ivt-ink-2 to-ivt-ink px-8 py-14 text-center sm:px-14 sm:py-20">
                    <div class="pointer-events-none absolute -right-[120px] -top-[160px] h-[420px] w-[420px] rounded-full animate-float-slow" style="background: radial-gradient(circle, rgba(124,58,237,0.28), transparent 70%);" />
                    <div class="pointer-events-none absolute -bottom-[180px] -left-[120px] h-[380px] w-[380px] rounded-full animate-float-slower" style="background: radial-gradient(circle, rgba(225,29,99,0.35), transparent 70%);" />
                    <h2 class="relative mx-auto max-w-2xl text-balance font-display text-[clamp(30px,3.8vw,50px)] leading-[1.08] tracking-tight text-ivt-on-dark">
                        Începe gratuit, crești când ești pregătit.
                    </h2>
                    <p class="relative mx-auto mt-4 max-w-[460px] text-[15px] text-ivt-on-dark-dim">
                        Creezi cont în câteva minute și îți publici primul anunț chiar azi.
                    </p>
                    <div class="relative mt-9 flex justify-center">
                        <Link :href="ctaHref" class="btn-brand px-7 py-3.5 text-sm">
                            {{ page.props.auth.isProvider ? 'Gestionează abonamentul' : 'Creează cont furnizor' }}
                            <ArrowRightIcon class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
