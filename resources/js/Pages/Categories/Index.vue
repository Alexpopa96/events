<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRightIcon, ArrowUpRightIcon, MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { categoryIcon } from '@/Composables/useCategoryIcon';
import { groupCategories } from '@/Composables/useCategoryGroups';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ providers: 0 }) },
});

const search = ref('');

const groups = computed(() => groupCategories(props.categories));

const filteredGroups = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return groups.value;

    return groups.value
        .map((group) => ({
            name: group.name,
            categories: group.categories.filter((category) => category.name.toLowerCase().includes(term)),
        }))
        .filter((group) => group.categories.length > 0);
});

const visibleCount = computed(() => filteredGroups.value.reduce((total, group) => total + group.categories.length, 0));

const popularCategories = computed(() =>
    [...props.categories].sort((a, b) => b.listingsCount - a.listingsCount).slice(0, 5)
);
</script>

<template>
    <Head title="Categorii — Invita" />

    <div class="overflow-x-clip bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <!-- PAGE HERO -->
            <section class="relative pb-8 pt-8 lg:pb-12 lg:pt-10">
                <div class="pointer-events-none absolute inset-0 overflow-hidden [mask-image:linear-gradient(to_bottom,#000_55%,transparent)]">
                    <div class="absolute -left-40 -top-40 h-[520px] w-[520px] rounded-full bg-primary/15 blur-3xl animate-float-slow" />
                    <div class="absolute -right-32 top-10 h-[460px] w-[460px] rounded-full bg-ivt-violet/15 blur-3xl animate-float-slower" />
                    <div class="absolute bottom-0 left-1/3 h-[320px] w-[320px] rounded-full bg-ivt-accent-bright/15 blur-3xl animate-float-slower" />
                    <div
                        class="absolute inset-0 opacity-[0.35]"
                        style="background-image: radial-gradient(rgba(26,20,51,0.12) 1px, transparent 1px); background-size: 22px 22px; mask-image: radial-gradient(ellipse 70% 60% at 30% 30%, #000 30%, transparent 75%);"
                    />
                </div>

                <div class="relative mx-auto max-w-[1600px] px-6 lg:px-8">
                    <nav class="flex items-center gap-2 text-[13px] text-ivt-ink-faint" aria-label="Breadcrumb">
                        <Link :href="route('home')" class="transition-colors hover:text-primary">Acasă</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <span class="text-ivt-ink-soft">Categorii</span>
                    </nav>

                    <div class="mt-8 lg:mt-10">
                        <div>
                            <p class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 py-1 pl-1.5 pr-3.5 text-[12.5px] font-semibold text-ivt-ink-soft shadow-sm backdrop-blur">
                                <span class="rounded-full bg-brand px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider text-white">{{ categories.length }}</span>
                                categorii active, {{ groups.length }} grupe de servicii
                            </p>

                            <h1 class="mt-6 text-balance font-display text-[34px] font-bold leading-[1.05] tracking-tight text-ivt-ink sm:text-[44px] lg:text-[52px]">
                                Fiecare detaliu al evenimentului,
                                <span class="relative whitespace-nowrap">
                                    <span class="text-gradient">într-un singur loc.</span>
                                    <svg class="absolute -bottom-2 left-0 h-3 w-full text-ivt-accent-bright" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true">
                                        <path d="M2 9 C 80 2, 200 2, 298 7" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                    </svg>
                                </span>
                            </h1>

                            <p class="mt-6 max-w-[480px] text-[15.5px] leading-relaxed text-ivt-ink-soft">
                                De la fotograf și DJ, până la candy bar și mașină de epocă — răsfoiește toate categoriile de furnizori și găsește exact ce cauți.
                            </p>

                            <div class="ring-gradient relative z-20 mt-9 grid max-w-[560px] gap-1.5 rounded-[22px] border border-ivt-line bg-white p-2 shadow-ivt-soft transition-shadow focus-within:shadow-ivt-deep hover:shadow-ivt-deep sm:grid-cols-[1fr_auto]">
                                <label class="group flex h-full cursor-text items-center gap-3 rounded-2xl px-4 py-2.5 transition-colors hover:bg-ivt-paper-2">
                                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-ivt-paper-2 text-ivt-violet transition-colors group-focus-within:bg-brand group-focus-within:text-white group-hover:bg-white">
                                        <MagnifyingGlassIcon class="h-[18px] w-[18px]" />
                                    </span>
                                    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <span class="text-[10.5px] font-bold uppercase tracking-[0.1em] text-ivt-ink-faint">Categorie</span>
                                        <input
                                            v-model="search"
                                            type="text"
                                            placeholder="ex. florărie, limuzine, DJ…"
                                            autocomplete="off"
                                            class="w-full truncate border-0 bg-transparent p-0 text-[14.5px] font-semibold text-ivt-ink placeholder:font-semibold placeholder:text-ivt-ink-faint focus:outline-none focus:ring-0"
                                        />
                                    </span>
                                    <button
                                        v-if="search"
                                        type="button"
                                        class="flex h-6 w-6 flex-none items-center justify-center rounded-full text-ivt-ink-faint transition-colors hover:bg-ivt-paper-3 hover:text-ivt-ink"
                                        aria-label="Șterge căutarea"
                                        @click.prevent="search = ''"
                                    >
                                        <XMarkIcon class="h-3.5 w-3.5" />
                                    </button>
                                </label>
                                <a href="#categorii" class="btn-brand whitespace-nowrap rounded-2xl px-7 py-3.5 text-sm font-bold">
                                    {{ search ? `${visibleCount} rezultate` : 'Vezi categoriile' }}
                                    <ArrowRightIcon class="h-4 w-4" stroke-width="2.5" />
                                </a>
                            </div>

                            <div v-if="popularCategories.length" class="mt-5 flex flex-wrap items-center gap-2">
                                <span class="mr-1 text-[12.5px] text-ivt-ink-faint">Populare:</span>
                                <Link
                                    v-for="category in popularCategories"
                                    :key="category.id"
                                    :href="route('categories.show', category.slug)"
                                    class="rounded-full border border-ivt-line bg-white/70 px-3 py-1 text-[12.5px] font-medium text-ivt-ink-soft transition-colors hover:border-ivt-violet hover:text-primary"
                                >
                                    {{ category.name }}
                                </Link>
                            </div>

                        </div>

                    </div>
                </div>
            </section>

            <!-- GROUPS -->
            <section id="categorii" class="scroll-mt-24 pt-4 pb-[110px]">
                <div class="mx-auto max-w-[1600px] px-6 lg:px-8">
                    <div v-for="group in filteredGroups" :key="group.name" class="mb-[60px] last:mb-0">
                        <div class="mb-[26px] flex items-baseline gap-4 border-b border-ivt-line pb-4">
                            <h3 class="font-display text-[22px] font-medium text-ivt-ink">{{ group.name }}</h3>
                            <span class="text-[13px] text-ivt-ink-faint">{{ group.categories.length }} categorii</span>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Link
                                v-for="category in group.categories"
                                :key="category.id"
                                :href="route('categories.show', category.slug)"
                                class="ring-gradient group relative isolate flex flex-col overflow-hidden rounded-3xl border border-ivt-line/70 bg-white/80 p-6 backdrop-blur transition-all duration-500 [transition-timing-function:cubic-bezier(.2,.8,.2,1)] hover:-translate-y-1 hover:border-transparent hover:shadow-ivt-deep"
                            >
                                <!-- Hover glow -->
                                <span
                                    class="pointer-events-none absolute -right-16 -top-16 -z-10 h-48 w-48 rounded-full bg-gradient-to-br from-primary/25 to-ivt-violet/25 opacity-0 blur-2xl transition-opacity duration-500 group-hover:opacity-100"
                                    aria-hidden="true"
                                />

                                <div class="flex items-start justify-between">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-ivt-paper-2 to-ivt-paper-3 text-primary ring-1 ring-ivt-line/60 transition-all duration-500 group-hover:scale-105 group-hover:from-primary group-hover:to-ivt-violet group-hover:text-white group-hover:shadow-glow-primary group-hover:ring-transparent">
                                        <component :is="categoryIcon(category.slug)" class="h-6 w-6" stroke-width="1.5" />
                                    </span>
                                    <span
                                        v-if="category.isNew"
                                        class="rounded-full bg-ivt-violet/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.08em] text-ivt-violet"
                                    >
                                        Nou
                                    </span>
                                </div>

                                <p class="mt-5 text-[16px] font-bold leading-snug tracking-[-0.01em] text-ivt-ink transition-colors duration-300 group-hover:text-primary">{{ category.name }}</p>
                                <p v-if="category.description" class="mt-2 line-clamp-2 text-[14px] leading-[1.65] tracking-[0.01em] [word-spacing:0.08em] text-ivt-ink-soft">{{ category.description }}</p>

                                <div class="flex items-center justify-between pt-3">
                                    <span class="text-[12.5px] text-ivt-ink-faint">
                                        <b class="font-display text-[20px] font-bold tabular-nums text-ivt-ink">{{ category.listingsCount }}</b>
                                        <span class="ml-1 text-[11px] font-semibold uppercase tracking-[0.1em]">furnizori</span>
                                    </span>
                                    <span class="flex h-8 w-8 -translate-x-1 items-center justify-center rounded-full bg-ivt-ink text-white opacity-0 transition-all duration-500 group-hover:translate-x-0 group-hover:opacity-100">
                                        <ArrowUpRightIcon class="h-4 w-4" stroke-width="2" />
                                    </span>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <div v-if="search && !filteredGroups.length" class="px-5 py-[60px] text-center text-ivt-ink-faint">
                        <p class="font-display text-[22px] text-ivt-ink">Nicio categorie găsită</p>
                        <p class="mt-2 text-[15px]">Încearcă un alt termen de căutare sau răsfoiește lista completă mai sus.</p>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-[30px] rounded-3xl border border-ivt-line bg-ivt-paper-2 p-12">
                        <div>
                            <h3 class="max-w-[420px] font-display text-2xl font-medium text-ivt-ink">Nu găsești categoria potrivită?</h3>
                            <p class="mt-2.5 max-w-[420px] text-[14.5px] text-ivt-ink-soft">Spune-ne ce serviciu lipsește — o adăugăm dacă se potrivește platformei.</p>
                        </div>
                        <a
                            href="mailto:contact@eventhub.ro"
                            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-full bg-gradient-to-b from-ivt-ink-2 to-ivt-ink px-6 py-3 text-sm font-semibold text-ivt-paper transition-transform duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(26,20,51,0.4)]"
                        >
                            Sugerează o categorie
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
