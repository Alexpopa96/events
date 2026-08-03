<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    Bars3Icon,
    XMarkIcon,
    CheckIcon,
    SparklesIcon,
    ChatBubbleLeftRightIcon,
    ChartBarIcon,
    ShieldCheckIcon,
    CameraIcon,
    VideoCameraIcon,
    MusicalNoteIcon,
    MicrophoneIcon,
    BuildingStorefrontIcon,
    PaintBrushIcon,
    TagIcon,
    ChevronDownIcon,
    ArrowUpIcon,
    ArrowLongRightIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/24/solid';
import { animateCountUp } from '@/Composables/useCountUp';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    plans: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({ providers: 0, categories: 0 }),
    },
});

const mobileMenuOpen = ref(false);

/* Scroll-triggered reveal — reveals below-the-fold content as it enters the viewport. */
const vReveal = {
    mounted(el, binding) {
        if (typeof IntersectionObserver === 'undefined') {
            el.classList.add('reveal-visible');
            return;
        }

        el.classList.add('reveal');
        if (binding.value) {
            el.style.transitionDelay = `${binding.value}ms`;
        }

        const observer = new IntersectionObserver(
            (entries, obs) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        el.classList.add('reveal-visible');
                        obs.unobserve(el);
                    }
                });
            },
            { threshold: 0.15 },
        );
        observer.observe(el);
    },
};

/* Header shrink + scroll progress bar */
const scrollY = ref(0);
const scrollProgress = ref(0);

function onScroll() {
    scrollY.value = window.scrollY;
    const doc = document.documentElement;
    const max = doc.scrollHeight - doc.clientHeight;
    scrollProgress.value = max > 0 ? Math.min(100, (scrollY.value / max) * 100) : 0;
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* Hero mouse-follow spotlight */
const heroRef = ref(null);

function onHeroMouseMove(event) {
    const rect = heroRef.value.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;
    heroRef.value.style.setProperty('--spot-x', `${x}%`);
    heroRef.value.style.setProperty('--spot-y', `${y}%`);
}

/* Count-up stats */
const displayedProviders = ref(0);
const displayedCategories = ref(0);
const statsRef = ref(null);

onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    if (typeof IntersectionObserver === 'undefined' || !statsRef.value) {
        displayedProviders.value = props.stats.providers;
        displayedCategories.value = props.stats.categories;
        return;
    }

    const observer = new IntersectionObserver(
        (entries, obs) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animateCountUp(props.stats.providers, (v) => (displayedProviders.value = v));
                    animateCountUp(props.stats.categories, (v) => (displayedCategories.value = v));
                    obs.disconnect();
                }
            });
        },
        { threshold: 0.4 },
    );
    observer.observe(statsRef.value);
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
});

/* Color rotation used for category chips and benefit icons — keeps the
   page from reading as a single flat green wash while staying on-brand. */
const palette = [
    { chip: 'bg-brand-500/10 text-brand-600', ring: 'group-hover:ring-brand-400/50', solid: 'bg-brand-500' },
    { chip: 'bg-gold-500/10 text-gold-500', ring: 'group-hover:ring-gold-400/50', solid: 'bg-gold-500' },
    { chip: 'bg-sky-500/10 text-sky-600', ring: 'group-hover:ring-sky-400/50', solid: 'bg-sky-500' },
    { chip: 'bg-rose-500/10 text-rose-600', ring: 'group-hover:ring-rose-400/50', solid: 'bg-rose-500' },
    { chip: 'bg-violet-500/10 text-violet-600', ring: 'group-hover:ring-violet-400/50', solid: 'bg-violet-500' },
];

function paletteFor(index) {
    return palette[index % palette.length];
}

const categoryIcons = {
    'fotograf': CameraIcon,
    'videograf': VideoCameraIcon,
    'dj': MusicalNoteIcon,
    'formatie': MusicalNoteIcon,
    'mc': MicrophoneIcon,
    'restaurant': BuildingStorefrontIcon,
    'salon-evenimente': BuildingStorefrontIcon,
    'decor': PaintBrushIcon,
};

function iconFor(category) {
    return categoryIcons[category.slug] ?? TagIcon;
}

function formatPrice(plan) {
    if (Number(plan.price) === 0) {
        return 'Gratuit';
    }

    return `${Number(plan.price).toLocaleString('ro-RO')} ${plan.currency}`;
}

const planIcons = {
    'gratuit': SparklesIcon,
    'standard': ChartBarIcon,
    'premium': StarIcon,
};

function planIcon(plan) {
    return planIcons[plan.slug] ?? SparklesIcon;
}

const steps = [
    {
        title: 'Creezi profilul companiei',
        text: 'Adaugi datele companiei, categoriile de servicii și datele de contact. Durează sub 5 minute.',
    },
    {
        title: 'Publici anunțuri cu fotografii',
        text: 'Încarci fotografii și videoclipuri cu lucrările tale, prețuri orientative și zona în care lucrezi.',
    },
    {
        title: 'Primești cereri de ofertă',
        text: 'Clienții te găsesc, te contactează direct pe telefon sau WhatsApp și îți trimit cereri de ofertă.',
    },
];

const benefits = [
    {
        icon: ChatBubbleLeftRightIcon,
        title: 'Lead-uri directe',
        text: 'Cereri de ofertă și click-uri pe telefon/WhatsApp trimise direct de clienți interesați din zona ta.',
    },
    {
        icon: ChartBarIcon,
        title: 'Statistici clare',
        text: 'Vezi câte persoane ți-au văzut anunțurile și de câte ori au fost contactate, direct din contul tău.',
    },
    {
        icon: ShieldCheckIcon,
        title: 'Fără comision',
        text: 'Plătești un abonament lunar fix — nu reținem niciun comision din contractele tale cu clienții.',
    },
    {
        icon: SparklesIcon,
        title: 'Profil profesionist',
        text: 'Galerie foto și video, recenzii de la clienți și un profil de companie care inspiră încredere.',
    },
];

const paymentMethods = [
    { name: 'Card bancar', image: '/assets/img/logos/visa.png' },
    { name: 'Mastercard', image: '/assets/img/logos/mastercard.png' },
];
</script>

<template>
    <Head title="Platforma pentru furnizori de servicii pentru evenimente" />

    <div class="bg-paper text-ink antialiased">
        <!-- Scroll progress bar -->
        <div class="fixed inset-x-0 top-0 z-[60] h-1 bg-transparent">
            <div
                class="h-full bg-gradient-to-r from-brand-500 via-gold-400 to-brand-500 transition-[width] duration-150 ease-out"
                :style="{ width: scrollProgress + '%' }"
            ></div>
        </div>

        <!-- Header -->
        <header
            class="sticky top-0 z-50 border-b bg-paper/80 backdrop-blur-md transition-all duration-300"
            :class="scrollY > 8 ? 'border-line shadow-sm shadow-ink/5' : 'border-transparent'"
        >
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="flex items-center justify-between transition-all duration-300" :class="scrollY > 8 ? 'h-16' : 'h-20'">
                    <Link href="/" class="font-serif text-xl text-brand-600 transition-transform duration-200 hover:scale-105">
                        evenimente<span class="text-gold-500">.</span>
                    </Link>

                    <nav class="hidden lg:flex items-center gap-8 text-sm font-medium text-ink-soft">
                        <a href="#cum-functioneaza" class="relative py-1 transition-colors duration-150 hover:text-brand-600 after:absolute after:-bottom-0.5 after:left-0 after:h-0.5 after:w-0 after:bg-gradient-to-r after:from-brand-500 after:to-gold-400 after:transition-all after:duration-300 hover:after:w-full">Cum funcționează</a>
                        <a href="#categorii" class="relative py-1 transition-colors duration-150 hover:text-brand-600 after:absolute after:-bottom-0.5 after:left-0 after:h-0.5 after:w-0 after:bg-gradient-to-r after:from-brand-500 after:to-gold-400 after:transition-all after:duration-300 hover:after:w-full">Categorii</a>
                        <a href="#preturi" class="relative py-1 transition-colors duration-150 hover:text-brand-600 after:absolute after:-bottom-0.5 after:left-0 after:h-0.5 after:w-0 after:bg-gradient-to-r after:from-brand-500 after:to-gold-400 after:transition-all after:duration-300 hover:after:w-full">Prețuri</a>
                        <a href="#despre" class="relative py-1 transition-colors duration-150 hover:text-brand-600 after:absolute after:-bottom-0.5 after:left-0 after:h-0.5 after:w-0 after:bg-gradient-to-r after:from-brand-500 after:to-gold-400 after:transition-all after:duration-300 hover:after:w-full">Despre noi</a>
                    </nav>

                    <div class="hidden lg:flex items-center gap-3">
                        <template v-if="$page.props.auth.user">
                            <Link
                                :href="route('dashboard')"
                                class="rounded-xl px-4 py-2 text-sm font-semibold text-ink transition-colors duration-150 hover:text-brand-600"
                            >
                                Contul meu
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                href="/login"
                                class="rounded-xl px-4 py-2 text-sm font-semibold text-ink transition-colors duration-150 hover:text-brand-600"
                            >
                                Conectare
                            </Link>
                            <Link
                                href="/register"
                                class="group relative overflow-hidden rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-500/25 transition-all duration-200 hover:bg-brand-600 hover:-translate-y-0.5 hover:shadow-md hover:shadow-brand-500/30"
                            >
                                <span class="relative z-10">Adaugă-ți afacerea</span>
                                <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/25 to-transparent transition-transform duration-700 group-hover:translate-x-full"></span>
                            </Link>
                        </template>
                    </div>

                    <button
                        class="lg:hidden p-2 text-ink-soft transition-transform duration-200 active:scale-90"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        <Bars3Icon v-if="!mobileMenuOpen" class="w-6 h-6" />
                        <XMarkIcon v-else class="w-6 h-6" />
                    </button>
                </div>

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <div v-if="mobileMenuOpen" class="lg:hidden pb-6 space-y-4">
                        <nav class="flex flex-col gap-1 text-sm font-medium text-ink-soft">
                            <a href="#cum-functioneaza" class="rounded-lg px-3 py-2 transition-colors hover:bg-white hover:text-brand-600" @click="mobileMenuOpen = false">Cum funcționează</a>
                            <a href="#categorii" class="rounded-lg px-3 py-2 transition-colors hover:bg-white hover:text-brand-600" @click="mobileMenuOpen = false">Categorii</a>
                            <a href="#preturi" class="rounded-lg px-3 py-2 transition-colors hover:bg-white hover:text-brand-600" @click="mobileMenuOpen = false">Prețuri</a>
                            <a href="#despre" class="rounded-lg px-3 py-2 transition-colors hover:bg-white hover:text-brand-600" @click="mobileMenuOpen = false">Despre noi</a>
                        </nav>
                        <div class="flex flex-col gap-2 px-3">
                            <template v-if="$page.props.auth.user">
                                <Link :href="route('dashboard')" class="rounded-xl bg-brand-500 px-4 py-2.5 text-center text-sm font-semibold text-white">
                                    Contul meu
                                </Link>
                            </template>
                            <template v-else>
                                <Link href="/login" class="rounded-xl border border-line bg-white px-4 py-2.5 text-center text-sm font-semibold text-ink">
                                    Conectare
                                </Link>
                                <Link href="/register" class="rounded-xl bg-brand-500 px-4 py-2.5 text-center text-sm font-semibold text-white">
                                    Adaugă-ți afacerea
                                </Link>
                            </template>
                        </div>
                    </div>
                </Transition>
            </div>
        </header>

        <!-- Hero -->
        <section ref="heroRef" class="relative overflow-hidden" @mousemove="onHeroMouseMove">
            <div
                class="pointer-events-none absolute inset-0 opacity-70 transition-opacity duration-500"
                style="background: radial-gradient(500px circle at var(--spot-x, 50%) var(--spot-y, 20%), rgba(31,92,78,0.08), transparent 70%);"
            ></div>

            <div class="absolute -top-32 -right-32 w-[32rem] h-[32rem] rounded-full bg-brand-400/20 blur-3xl animate-float-slow"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 rounded-full bg-gold-400/25 blur-3xl animate-float-slower"></div>
            <div class="absolute top-1/3 left-1/2 w-72 h-72 rounded-full bg-sky-400/10 blur-3xl animate-float-slow"></div>
            <div
                class="absolute inset-0 opacity-[0.05]"
                style="background-image: radial-gradient(circle, #211C27 1px, transparent 1px); background-size: 24px 24px;"
            ></div>

            <div class="relative mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-28">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-brand-600 ring-1 ring-brand-500/10">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-400 opacity-75"></span>
                            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                        </span>
                        Marketplace evenimente
                    </p>
                    <h1 class="mt-6 font-serif text-4xl leading-tight text-ink text-balance sm:text-5xl lg:text-6xl">
                        Fiecare eveniment merită
                        <span class="animate-gradient-x bg-gradient-to-r from-brand-500 via-gold-500 to-brand-600 bg-clip-text text-transparent">
                            furnizorul potrivit.
                        </span>
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed text-ink-soft text-balance">
                        Platforma unde furnizorii de servicii pentru evenimente — fotografi, DJ, formații, locații, decoratori și mulți alții — își construiesc profilul și primesc cereri de ofertă direct de la clienți, fără comisioane.
                    </p>
                    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link
                            href="/register"
                            class="group relative w-full sm:w-auto overflow-hidden rounded-xl bg-brand-500 px-6 py-3.5 text-sm font-semibold text-white shadow-sm shadow-brand-500/25 transition-all duration-200 hover:bg-brand-600 hover:shadow-lg hover:shadow-brand-500/30 hover:-translate-y-0.5"
                        >
                            <span class="relative z-10">Adaugă-ți afacerea gratuit</span>
                            <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/25 to-transparent transition-transform duration-700 group-hover:translate-x-full"></span>
                        </Link>
                        <a
                            href="#preturi"
                            class="w-full sm:w-auto rounded-xl border border-line bg-white px-6 py-3.5 text-sm font-semibold text-ink transition-all duration-200 hover:border-brand-400 hover:text-brand-600 hover:-translate-y-0.5"
                        >
                            Vezi planurile și prețurile
                        </a>
                    </div>

                    <div ref="statsRef" class="mt-14 flex items-center justify-center gap-10 sm:gap-16">
                        <div class="text-center">
                            <p class="font-serif text-3xl text-ink">{{ displayedProviders }}+</p>
                            <p class="mt-1 text-xs font-medium uppercase tracking-wide text-ink-soft">Furnizori activi</p>
                        </div>
                        <div class="h-10 w-px bg-line"></div>
                        <div class="text-center">
                            <p class="font-serif text-3xl text-ink">{{ displayedCategories }}+</p>
                            <p class="mt-1 text-xs font-medium uppercase tracking-wide text-ink-soft">Categorii de servicii</p>
                        </div>
                        <div class="h-10 w-px bg-line"></div>
                        <div class="text-center">
                            <p class="font-serif text-3xl text-ink">0%</p>
                            <p class="mt-1 text-xs font-medium uppercase tracking-wide text-ink-soft">Comision reținut</p>
                        </div>
                    </div>
                </div>

                <a href="#cum-functioneaza" class="mt-16 hidden sm:flex justify-center text-ink-soft/60 transition-colors hover:text-brand-500">
                    <ChevronDownIcon class="h-6 w-6 animate-bounce" />
                </a>
            </div>
        </section>

        <!-- How it works -->
        <section id="cum-functioneaza" class="py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div v-reveal class="mx-auto max-w-2xl text-center">
                    <p class="text-xs font-semibold uppercase tracking-widest text-brand-500">Cum funcționează</p>
                    <h2 class="mt-3 font-serif text-3xl text-ink sm:text-4xl">De la înregistrare la primul lead, în trei pași</h2>
                </div>

                <div class="mt-14 grid gap-8 sm:grid-cols-3 sm:gap-6 lg:gap-4 items-start">
                    <template v-for="(step, index) in steps" :key="step.title">
                        <div
                            v-reveal="index * 100"
                            class="group relative flex-1 rounded-2xl bg-white p-8 shadow-sm shadow-ink/5 ring-1 ring-line transition-all duration-300 hover:-translate-y-1.5 hover:shadow-lg hover:shadow-ink/10"
                        >
                            <span
                                class="flex h-10 w-10 items-center justify-center rounded-full font-serif text-lg text-white transition-transform duration-300 group-hover:scale-110"
                                :class="paletteFor(index).solid"
                            >
                                {{ index + 1 }}
                            </span>
                            <h3 class="mt-5 font-serif text-xl text-ink">{{ step.title }}</h3>
                            <p class="mt-3 text-sm leading-relaxed text-ink-soft">{{ step.text }}</p>
                        </div>
                        <div v-if="index < steps.length - 1" class="hidden lg:flex items-center justify-center text-line">
                            <ArrowLongRightIcon class="h-6 w-6" />
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <!-- Categories -->
        <section id="categorii" class="bg-white py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div v-reveal class="mx-auto max-w-2xl text-center">
                    <p class="text-xs font-semibold uppercase tracking-widest text-brand-500">Categorii</p>
                    <h2 class="mt-3 font-serif text-3xl text-ink sm:text-4xl">Ce tipuri de servicii poți promova</h2>
                    <p class="mt-4 text-ink-soft">De la fotografi și DJ, la locații și decor — platforma acoperă toate categoriile importante pentru un eveniment reușit.</p>
                </div>

                <div class="mt-12 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <div
                        v-for="(category, index) in categories"
                        :key="category.id"
                        v-reveal="(index % 8) * 40"
                        class="group flex items-center gap-3 rounded-2xl border border-line bg-paper px-5 py-4 ring-1 ring-transparent transition-all duration-300 hover:-translate-y-1 hover:border-transparent hover:shadow-md hover:shadow-ink/5"
                        :class="paletteFor(index).ring"
                    >
                        <span
                            class="flex h-10 w-10 flex-none items-center justify-center rounded-xl transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3"
                            :class="paletteFor(index).chip"
                        >
                            <component :is="iconFor(category)" class="h-5 w-5" />
                        </span>
                        <span class="text-sm font-medium text-ink">{{ category.name }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Benefits -->
        <section class="py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div v-reveal class="mx-auto max-w-2xl text-center">
                    <p class="text-xs font-semibold uppercase tracking-widest text-brand-500">De ce evenimente.</p>
                    <h2 class="mt-3 font-serif text-3xl text-ink sm:text-4xl">Construit pentru furnizori, nu pentru comisioane</h2>
                </div>

                <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="(benefit, index) in benefits"
                        :key="benefit.title"
                        v-reveal="index * 80"
                        class="group rounded-2xl bg-white p-7 shadow-sm shadow-ink/5 ring-1 ring-line transition-all duration-300 hover:-translate-y-1.5 hover:shadow-lg hover:shadow-ink/10"
                    >
                        <span
                            class="flex h-12 w-12 items-center justify-center rounded-xl transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6"
                            :class="paletteFor(index).chip"
                        >
                            <component :is="benefit.icon" class="h-6 w-6" />
                        </span>
                        <h3 class="mt-5 font-serif text-lg text-ink">{{ benefit.title }}</h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-ink-soft">{{ benefit.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing -->
        <section id="preturi" class="bg-gradient-to-b from-white to-paper py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div v-reveal class="mx-auto max-w-2xl text-center">
                    <p class="text-xs font-semibold uppercase tracking-widest text-brand-500">Abonamente</p>
                    <h2 class="mt-3 font-serif text-3xl text-ink sm:text-4xl">Prețuri simple, fără costuri ascunse</h2>
                    <p class="mt-4 text-ink-soft">Alege planul potrivit pentru afacerea ta. Poți schimba sau anula abonamentul oricând din contul tău.</p>
                </div>

                <div class="mt-14 grid gap-8 lg:grid-cols-3 lg:items-start">
                    <div
                        v-for="(plan, index) in plans"
                        :key="plan.slug"
                        v-reveal="index * 100"
                        class="relative rounded-2xl bg-white p-8 shadow-sm shadow-ink/5 ring-1 ring-line transition-all duration-300 hover:-translate-y-1.5"
                        :class="plan.allows_featured_placement ? 'lg:-translate-y-3 lg:hover:-translate-y-4 ring-2 ring-brand-500 shadow-lg shadow-brand-500/10 animate-pulse-glow' : 'hover:shadow-lg hover:shadow-ink/10'"
                    >
                        <span
                            v-if="plan.allows_featured_placement"
                            class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-gold-500 px-4 py-1 text-xs font-semibold uppercase tracking-wide text-white shadow-sm shadow-gold-500/40"
                        >
                            Cel mai popular
                        </span>

                        <span
                            class="flex h-11 w-11 items-center justify-center rounded-xl"
                            :class="paletteFor(index).chip"
                        >
                            <component :is="planIcon(plan)" class="h-5 w-5" />
                        </span>

                        <h3 class="mt-5 font-serif text-xl text-ink">{{ plan.name }}</h3>
                        <p class="mt-2 text-sm text-ink-soft">{{ plan.description }}</p>

                        <p class="mt-6 flex items-baseline gap-1.5">
                            <span class="font-serif text-4xl text-ink">{{ formatPrice(plan) }}</span>
                            <span v-if="Number(plan.price) > 0" class="text-sm text-ink-soft">/ lună</span>
                        </p>

                        <Link
                            href="/register"
                            class="group relative mt-7 block overflow-hidden rounded-xl px-4 py-3 text-center text-sm font-semibold transition-all duration-200"
                            :class="plan.allows_featured_placement
                                ? 'bg-brand-500 text-white shadow-sm shadow-brand-500/25 hover:bg-brand-600 hover:-translate-y-0.5'
                                : 'border border-line text-ink hover:border-brand-400 hover:text-brand-600'"
                        >
                            <span class="relative z-10">Alege {{ plan.name }}</span>
                            <span
                                v-if="plan.allows_featured_placement"
                                class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/25 to-transparent transition-transform duration-700 group-hover:translate-x-full"
                            ></span>
                        </Link>

                        <ul class="mt-7 space-y-3">
                            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2.5 text-sm text-ink-soft">
                                <CheckIcon class="mt-0.5 h-4 w-4 flex-none text-brand-500" />
                                <span>{{ feature }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- About -->
        <section id="despre" class="py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                    <div v-reveal>
                        <p class="text-xs font-semibold uppercase tracking-widest text-brand-500">Despre noi</p>
                        <h2 class="mt-3 font-serif text-3xl text-ink sm:text-4xl">O platformă locală, făcută pentru piața de evenimente din România</h2>
                        <p class="mt-6 text-ink-soft leading-relaxed">
                            evenimente. este locul unde furnizorii de servicii pentru nunți, botezuri și evenimente corporate își prezintă portofoliul și sunt găsiți de clienți care caută exact ce oferă ei — fotografi, videografi, DJ, formații, locații, decoratori, floriști și multe altele.
                        </p>
                        <p class="mt-4 text-ink-soft leading-relaxed">
                            Nu suntem o agenție și nu ne implicăm în negocierile dintre furnizor și client. Rolul nostru este să te ajutăm să fii vizibil, să primești cereri de ofertă calificate și să îți administrezi prezența online dintr-un singur loc.
                        </p>
                        <ul class="mt-8 space-y-3">
                            <li class="flex items-start gap-2.5 text-sm text-ink">
                                <CheckIcon class="mt-0.5 h-4 w-4 flex-none text-brand-500" />
                                <span>Fără comision din contractele tale cu clienții</span>
                            </li>
                            <li class="flex items-start gap-2.5 text-sm text-ink">
                                <CheckIcon class="mt-0.5 h-4 w-4 flex-none text-brand-500" />
                                <span>Plan gratuit disponibil, fără card bancar necesar</span>
                            </li>
                            <li class="flex items-start gap-2.5 text-sm text-ink">
                                <CheckIcon class="mt-0.5 h-4 w-4 flex-none text-brand-500" />
                                <span>Suport dedicat pentru furnizori la configurarea profilului</span>
                            </li>
                        </ul>
                    </div>

                    <div v-reveal="120" class="relative">
                        <div class="absolute -inset-4 rounded-3xl border border-dashed border-brand-400/20 animate-spin-slow"></div>
                        <div class="relative rounded-2xl bg-gradient-to-br from-brand-600 to-brand-700 p-10 text-white shadow-xl shadow-brand-500/20">
                            <p class="font-serif text-2xl leading-snug text-balance">
                                „Ne dorim ca fiecare furnizor bun să poată fi găsit ușor de clienții potriviți, indiferent de bugetul de marketing.”
                            </p>
                            <p class="mt-6 text-sm text-brand-50/80">Echipa evenimente.</p>
                        </div>
                        <div class="absolute -bottom-6 -right-6 w-32 h-32 rounded-full bg-gold-400/25 blur-2xl animate-float-slower"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Payment methods -->
        <section class="border-y border-line bg-white py-14">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div v-reveal class="flex flex-col items-center justify-between gap-8 sm:flex-row">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-brand-500">Modalități de plată</p>
                        <p class="mt-2 text-sm text-ink-soft">Plătești abonamentul lunar simplu și în siguranță.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-6">
                        <div
                            v-for="method in paymentMethods"
                            :key="method.name"
                            class="flex items-center gap-2 rounded-xl border border-line px-4 py-2.5 transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-400 hover:shadow-sm"
                        >
                            <img :src="method.image" :alt="method.name" class="h-6 w-auto object-contain grayscale transition-all duration-300 hover:grayscale-0" />
                        </div>
                        <div class="flex items-center gap-2 rounded-xl border border-line px-4 py-2.5 text-sm font-medium text-ink-soft transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-400 hover:text-brand-600">
                            Transfer bancar
                        </div>
                        <div class="flex items-center gap-2 rounded-xl border border-line px-4 py-2.5 text-sm font-medium text-ink-soft transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-400 hover:text-brand-600">
                            Factură cu TVA
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="relative overflow-hidden py-20 sm:py-24">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[40rem] h-64 rounded-full bg-brand-400/10 blur-3xl"></div>
            <div v-reveal class="relative mx-auto max-w-4xl px-6 text-center lg:px-8">
                <h2 class="font-serif text-3xl text-ink sm:text-4xl text-balance">Pregătit să primești primele cereri de ofertă?</h2>
                <p class="mt-4 text-ink-soft">Creează-ți profilul gratuit în câteva minute — fără card bancar necesar.</p>
                <Link
                    href="/register"
                    class="group relative mt-8 inline-flex overflow-hidden rounded-xl bg-brand-500 px-7 py-3.5 text-sm font-semibold text-white shadow-sm shadow-brand-500/25 transition-all duration-200 hover:bg-brand-600 hover:shadow-lg hover:shadow-brand-500/30 hover:-translate-y-0.5"
                >
                    <span class="relative z-10">Adaugă-ți afacerea gratuit</span>
                    <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/25 to-transparent transition-transform duration-700 group-hover:translate-x-full"></span>
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-line bg-white">
            <div class="mx-auto max-w-7xl px-6 py-14 lg:px-8">
                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <Link href="/" class="font-serif text-xl text-brand-600">
                            evenimente<span class="text-gold-500">.</span>
                        </Link>
                        <p class="mt-4 text-sm leading-relaxed text-ink-soft">
                            Platformă pentru furnizori de servicii pentru evenimente din România.
                        </p>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-ink">Platformă</h4>
                        <ul class="mt-4 space-y-2.5 text-sm text-ink-soft">
                            <li><a href="#cum-functioneaza" class="transition-colors duration-150 hover:text-brand-600">Cum funcționează</a></li>
                            <li><a href="#categorii" class="transition-colors duration-150 hover:text-brand-600">Categorii</a></li>
                            <li><a href="#preturi" class="transition-colors duration-150 hover:text-brand-600">Prețuri</a></li>
                            <li><a href="#despre" class="transition-colors duration-150 hover:text-brand-600">Despre noi</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-ink">Cont</h4>
                        <ul class="mt-4 space-y-2.5 text-sm text-ink-soft">
                            <li><Link href="/login" class="transition-colors duration-150 hover:text-brand-600">Conectare</Link></li>
                            <li><Link href="/register" class="transition-colors duration-150 hover:text-brand-600">Creează cont furnizor</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-ink">Legal</h4>
                        <ul class="mt-4 space-y-2.5 text-sm text-ink-soft">
                            <li><Link :href="route('terms.show')" class="transition-colors duration-150 hover:text-brand-600">Termeni și condiții</Link></li>
                            <li><Link :href="route('policy.show')" class="transition-colors duration-150 hover:text-brand-600">Politica de confidențialitate</Link></li>
                            <li><a href="mailto:contact@evenimente.ro" class="transition-colors duration-150 hover:text-brand-600">contact@evenimente.ro</a></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-12 flex flex-col-reverse items-center justify-between gap-4 border-t border-line pt-8 sm:flex-row">
                    <p class="text-xs text-ink-soft">&copy; {{ new Date().getFullYear() }} evenimente. Toate drepturile rezervate.</p>
                    <p class="text-xs text-ink-soft">Platformă pentru furnizori de servicii pentru evenimente</p>
                </div>
            </div>
        </footer>

        <!-- Back to top -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <button
                v-if="scrollY > 500"
                @click="scrollToTop"
                class="fixed bottom-6 right-6 z-40 flex h-11 w-11 items-center justify-center rounded-full bg-brand-500 text-white shadow-lg shadow-brand-500/30 transition-all duration-200 hover:bg-brand-600 hover:-translate-y-1"
                aria-label="Înapoi sus"
            >
                <ArrowUpIcon class="h-5 w-5" />
            </button>
        </Transition>
    </div>
</template>
