<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
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
    [...props.categories].sort((a, b) => b.listingsCount - a.listingsCount).slice(0, 4)
);
</script>

<template>
    <Head title="Categorii — Invita" />

    <div class="bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <!-- PAGE HERO -->
            <section class="relative overflow-hidden border-b border-ivt-line bg-ivt-paper-2 py-14 sm:py-16">
                <div
                    class="pointer-events-none absolute inset-x-[-10%] -top-[30%] h-[130%]"
                    style="background: radial-gradient(50% 60% at 20% 10%, rgba(168,127,46,0.10), transparent 60%), radial-gradient(45% 45% at 90% 0%, rgba(124,46,59,0.08), transparent 60%);"
                />

                <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
                    <p class="mb-5 flex items-center gap-2 text-[13px] text-ivt-ink-faint">
                        <Link :href="route('home')" class="text-ivt-ink-soft transition-colors hover:text-ivt-wine">Acasă</Link>
                        <span>›</span>
                        <span>Categorii</span>
                    </p>

                    <p class="flex items-center gap-2.5 text-xs font-bold uppercase tracking-[0.18em] text-ivt-wine">
                        <span class="inline-block h-px w-[22px] bg-ivt-gold" />
                        Categorii
                    </p>

                    <h1 class="mt-3 max-w-2xl font-serif text-[34px] font-medium leading-[1.05] tracking-[-0.01em] text-ivt-ink sm:text-[44px]">
                        Fiecare detaliu al evenimentului, într-o singură vitrină.
                    </h1>
                    <p class="mt-4 max-w-lg text-[17px] leading-relaxed text-ivt-ink-soft">
                        De la fotograf și DJ, până la candy bar și mașină de epocă — răsfoiește toate categoriile de furnizori și găsește exact ce cauți pentru eveniment.
                    </p>

                    <div class="relative mt-8 flex max-w-[480px] items-center gap-1.5 rounded-2xl border border-ivt-line bg-white p-1.5 shadow-ivt-soft">
                        <MagnifyingGlassIcon class="ml-3.5 h-[18px] w-[18px] flex-none text-ivt-ink-faint" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Caută o categorie — ex. florărie, limuzine, DJ…"
                            class="w-full border-none bg-transparent px-1 py-3 text-[14.5px] font-medium text-ivt-ink placeholder:text-ivt-ink-faint focus:outline-none focus:ring-0"
                        />
                        <span class="whitespace-nowrap rounded-full bg-ivt-ink px-4 py-2 text-xs font-bold text-ivt-paper">
                            {{ search ? `${visibleCount} rezultate` : `${categories.length} categorii` }}
                        </span>
                    </div>

                    <div class="mt-9 flex flex-wrap gap-9">
                        <div>
                            <b class="block font-serif text-2xl font-semibold text-ivt-ink">{{ categories.length }}</b>
                            <span class="text-[12.5px] text-ivt-ink-faint">categorii active</span>
                        </div>
                        <div>
                            <b class="block font-serif text-2xl font-semibold text-ivt-ink">{{ stats.providers }}+</b>
                            <span class="text-[12.5px] text-ivt-ink-faint">furnizori listați</span>
                        </div>
                        <div>
                            <b class="block font-serif text-2xl font-semibold text-ivt-ink">{{ groups.length }}</b>
                            <span class="text-[12.5px] text-ivt-ink-faint">grupe de servicii</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- POPULARE -->
            <section v-if="!search" class="py-16 pb-5">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
                        <h2 class="font-serif text-[26px] font-medium text-ivt-ink">Cele mai căutate</h2>
                        <p class="text-[13.5px] text-ivt-ink-faint">Pe baza numărului de anunțuri active</p>
                    </div>
                    <div class="grid grid-cols-2 gap-[18px] sm:grid-cols-2 lg:grid-cols-4">
                        <Link
                            v-for="category in popularCategories"
                            :key="category.id"
                            :href="route('categories.show', category.slug)"
                            class="group relative flex min-h-[150px] flex-col justify-between overflow-hidden rounded-2xl bg-gradient-to-br from-ivt-ink-2 to-ivt-ink p-6 text-ivt-on-dark transition-all duration-300 hover:-translate-y-1.5 hover:shadow-ivt-deep"
                        >
                            <span
                                class="pointer-events-none absolute -right-[70px] -top-[90px] h-[180px] w-[180px] rounded-full"
                                style="background: radial-gradient(circle, rgba(201,162,79,0.18), transparent 70%);"
                            />
                            <component :is="categoryIcon(category.slug)" class="relative h-7 w-7 text-ivt-gold-bright" stroke-width="1.4" />
                            <div class="relative mt-auto pt-4">
                                <p class="font-serif text-[19px] font-medium">{{ category.name }}</p>
                                <p class="mt-1 text-xs text-ivt-on-dark-dim">{{ category.listingsCount }} furnizori</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- GROUPS -->
            <section class="py-10 pb-[110px]">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div v-for="group in filteredGroups" :key="group.name" class="mb-[60px] last:mb-0">
                        <div class="mb-[26px] flex items-baseline gap-4 border-b border-ivt-line pb-4">
                            <h3 class="font-serif text-[22px] font-medium text-ivt-ink">{{ group.name }}</h3>
                            <span class="text-[13px] text-ivt-ink-faint">{{ group.categories.length }} categorii</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                            <Link
                                v-for="category in group.categories"
                                :key="category.id"
                                :href="route('categories.show', category.slug)"
                                class="group flex flex-col gap-3 rounded-2xl border border-ivt-line bg-white p-5 transition-all duration-300 hover:-translate-y-1 hover:border-ivt-gold hover:shadow-ivt-soft"
                            >
                                <div class="flex items-start justify-between">
                                    <component :is="categoryIcon(category.slug)" class="h-[30px] w-[30px] text-ivt-wine" stroke-width="1.4" />
                                    <span
                                        v-if="category.isNew"
                                        class="rounded-full border border-ivt-gold px-2.5 py-0.5 text-[9.5px] font-extrabold uppercase tracking-[0.08em] text-ivt-gold"
                                    >
                                        Nou
                                    </span>
                                </div>
                                <p class="text-[15.5px] font-semibold text-ivt-ink">{{ category.name }}</p>
                                <p v-if="category.description" class="text-[12.5px] leading-relaxed text-ivt-ink-faint">{{ category.description }}</p>
                                <p class="mt-auto flex items-center gap-1.5 border-t border-ivt-line pt-2 text-xs font-semibold text-ivt-sage">
                                    {{ category.listingsCount }} furnizori
                                </p>
                            </Link>
                        </div>
                    </div>

                    <div v-if="search && !filteredGroups.length" class="px-5 py-[60px] text-center text-ivt-ink-faint">
                        <p class="font-serif text-[22px] italic text-ivt-ink">Nicio categorie găsită</p>
                        <p class="mt-2 text-[15px]">Încearcă un alt termen de căutare sau răsfoiește lista completă mai sus.</p>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-[30px] rounded-3xl border border-ivt-line bg-ivt-paper-2 p-12">
                        <div>
                            <h3 class="max-w-[420px] font-serif text-2xl font-medium text-ivt-ink">Nu găsești categoria potrivită?</h3>
                            <p class="mt-2.5 max-w-[420px] text-[14.5px] text-ivt-ink-soft">Spune-ne ce serviciu lipsește — o adăugăm dacă se potrivește platformei.</p>
                        </div>
                        <a
                            href="mailto:contact@eventhub.ro"
                            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-full bg-gradient-to-b from-ivt-ink-2 to-ivt-ink px-6 py-3 text-sm font-semibold text-ivt-paper transition-transform duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(22,40,31,0.4)]"
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
