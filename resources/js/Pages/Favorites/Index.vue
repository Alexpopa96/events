<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRightIcon, ChevronUpDownIcon, CheckIcon } from '@heroicons/vue/24/outline';
import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from '@headlessui/vue';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import ProviderCard from '@/Components/Providers/ProviderCard.vue';
import FavoriteRow from '@/Components/Listing/FavoriteRow.vue';

const props = defineProps({
    favoriteProviders: { type: Array, default: () => [] },
    favoriteListings: { type: Array, default: () => [] },
});

const providers = ref(props.favoriteProviders);
const listings = ref(props.favoriteListings);

const tabs = computed(() => [
    { key: 'vendors', label: 'Furnizori salvați', count: providers.value.length, dot: 'bg-primary' },
    { key: 'listings', label: 'Anunțuri salvate', count: listings.value.length, dot: 'bg-ivt-violet' },
]);
const activeTab = ref('vendors');
const currentTab = computed(() => tabs.value.find((tab) => tab.key === activeTab.value) ?? tabs.value[0]);
const totalSaved = computed(() => providers.value.length + listings.value.length);

const onProviderToggled = ({ id, favorited }) => {
    if (!favorited) {
        providers.value = providers.value.filter((item) => item.id !== id);
    }
};

const onListingToggled = ({ id, favorited }) => {
    if (!favorited) {
        listings.value = listings.value.filter((item) => item.id !== id);
    }
};
</script>

<template>
    <Head title="Favorite" />

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

                <div class="relative mx-auto max-w-[1600px] px-6 pb-6 lg:px-8 lg:pb-6">
                    <nav class="flex items-center gap-2 text-[13px] text-ivt-ink-faint" aria-label="Breadcrumb">
                        <Link href="/" class="transition-colors hover:text-primary">Acasă</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <Link :href="route('profile.show')" class="transition-colors hover:text-primary">Contul meu</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <span class="text-ivt-ink-soft">Favorite</span>
                    </nav>

                    <div class="mt-8 lg:mt-10">
                        <p class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 py-1 pl-1.5 pr-3.5 text-[12.5px] font-semibold text-ivt-ink-soft shadow-sm backdrop-blur">
                            <span class="rounded-full bg-brand px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider text-white">{{ totalSaved }}</span>
                            salvate · {{ providers.length }} furnizori, {{ listings.length }} anunțuri
                        </p>

                        <h1 class="mt-6 text-balance font-display text-[34px] font-bold leading-[1.05] tracking-tight text-ivt-ink sm:text-[44px] lg:text-[52px]">
                            Tot ce ți-a plăcut,
                            <span class="relative whitespace-nowrap">
                                <span class="text-gradient">la un click distanță.</span>
                                <svg class="absolute -bottom-2 left-0 h-3 w-full text-ivt-accent-bright" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true">
                                    <path d="M2 9 C 80 2, 200 2, 298 7" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                </svg>
                            </span>
                        </h1>

                        <p class="mt-6 max-w-[480px] text-[15.5px] leading-relaxed text-ivt-ink-soft">
                            Furnizorii și anunțurile pe care le-ai salvat, ca să le compari mai ușor și să revii la ele oricând.
                        </p>

                        <div class="mt-9 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Listbox v-model="activeTab" as="div" class="relative z-20 w-full sm:w-80">
                                <ListboxButton
                                    v-slot="{ open }"
                                    class="group flex w-full items-center gap-2.5 rounded-2xl border border-ivt-line bg-white px-4 py-3.5 text-left text-sm shadow-ivt-soft transition-all hover:shadow-ivt-deep focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                                >
                                    <span class="h-2.5 w-2.5 flex-none rounded-full" :class="currentTab.dot" />
                                    <span class="min-w-0 flex-1 truncate font-bold leading-5 text-ivt-ink">{{ currentTab.label }}</span>
                                    <span class="rounded-full bg-ivt-ink px-2 text-[11px] font-bold leading-5 tabular-nums text-ivt-paper">{{ currentTab.count }}</span>
                                    <ChevronUpDownIcon class="h-5 w-5 flex-none text-ivt-ink-soft transition-colors group-hover:text-primary" :class="{ 'text-primary': open }" />
                                </ListboxButton>

                                <transition
                                    enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 -translate-y-1 scale-[0.98]"
                                    enter-to-class="opacity-100 translate-y-0 scale-100"
                                    leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0"
                                >
                                    <ListboxOptions class="absolute left-0 z-30 mt-2 w-full origin-top overflow-hidden rounded-2xl border border-ivt-line bg-white p-1.5 shadow-[0_20px_45px_-15px_rgba(26,20,51,0.35)] focus:outline-none">
                                        <ListboxOption
                                            v-for="tab in tabs"
                                            :key="tab.key"
                                            :value="tab.key"
                                            v-slot="{ active, selected }"
                                            as="template"
                                        >
                                            <li
                                                class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-[13.5px] transition-colors"
                                                :class="active ? 'bg-ivt-paper-2 text-ivt-ink' : 'text-ivt-ink-soft'"
                                            >
                                                <span class="h-2 w-2 flex-none rounded-full" :class="tab.dot" />
                                                <span class="flex-1 truncate" :class="selected ? 'font-semibold text-ivt-ink' : 'font-medium'">{{ tab.label }}</span>
                                                <span
                                                    class="min-w-[1.5rem] rounded-full px-1.5 py-0.5 text-center text-[11px] font-bold tabular-nums"
                                                    :class="selected ? 'bg-ivt-ink text-ivt-paper' : 'bg-ivt-paper-3 text-ivt-ink-faint'"
                                                >{{ tab.count }}</span>
                                                <CheckIcon class="h-4 w-4 flex-none text-primary" :class="selected ? 'opacity-100' : 'opacity-0'" stroke-width="2.5" />
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                            <Link :href="route('providers.index')" class="btn-brand inline-flex flex-none justify-center rounded-2xl px-7 py-3.5 text-sm font-bold">
                                Descoperă furnizori
                                <ArrowRightIcon class="h-4 w-4" stroke-width="2.5" />
                            </Link>
                        </div>
                    </div>
                </div>

            </section>

            <section id="favorite" class="scroll-mt-24 pb-10 sm:pb-12">
                <div class="mx-auto max-w-[1600px] px-6 lg:px-8">

                    <!-- VENDORS PANEL -->
                    <div v-show="activeTab === 'vendors'">
                        <TransitionGroup
                            v-if="providers.length"
                            tag="div"
                            class="grid grid-cols-1 gap-5 sm:grid-cols-2"
                            enter-active-class="transition duration-300 ease-out"
                            enter-from-class="opacity-0 scale-95"
                            leave-active-class="transition duration-300 ease-in absolute"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <ProviderCard
                                v-for="item in providers"
                                :key="item.id"
                                :provider="item"
                                :favorited="true"
                                @favorite-toggled="onProviderToggled"
                            />
                        </TransitionGroup>

                        <div v-else class="rounded-2xl border border-ivt-line bg-white px-6 py-20 text-center">
                            <p class="text-[34px]">🤍</p>
                            <p class="mt-3 font-display text-[22px] text-ivt-ink">Niciun furnizor salvat</p>
                            <p class="mt-2 text-[14px] text-ivt-ink-faint">Apasă pe inimioară pe cardul unui furnizor ca să îl adaugi aici.</p>
                            <Link
                                :href="route('providers.index')"
                                class="mt-6 inline-flex items-center gap-2 rounded-full bg-gradient-to-b from-primary-bright to-primary px-6 py-3 text-sm font-semibold text-ivt-paper transition-transform hover:-translate-y-0.5"
                            >
                                Răsfoiește furnizorii
                            </Link>
                        </div>
                    </div>

                    <!-- LISTINGS PANEL -->
                    <div v-show="activeTab === 'listings'">
                        <TransitionGroup
                            v-if="listings.length"
                            tag="div"
                            class="grid grid-cols-1 gap-5 sm:grid-cols-2"
                            enter-active-class="transition duration-300 ease-out"
                            enter-from-class="opacity-0 scale-95"
                            leave-active-class="transition duration-300 ease-in absolute"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <FavoriteRow
                                v-for="item in listings"
                                :key="item.id"
                                :listing="item"
                                @favorite-toggled="onListingToggled"
                            />
                        </TransitionGroup>

                        <div v-else class="rounded-2xl border border-ivt-line bg-white px-6 py-20 text-center">
                            <p class="text-[34px]">🤍</p>
                            <p class="mt-3 font-display text-[22px] text-ivt-ink">Niciun anunț salvat</p>
                            <p class="mt-2 text-[14px] text-ivt-ink-faint">Apasă pe inimioară pe un anunț ca să îl adaugi aici.</p>
                            <Link
                                :href="route('listings.index')"
                                class="mt-6 inline-flex items-center gap-2 rounded-full bg-gradient-to-b from-primary-bright to-primary px-6 py-3 text-sm font-semibold text-ivt-paper transition-transform hover:-translate-y-0.5"
                            >
                                Răsfoiește anunțurile
                            </Link>
                        </div>
                    </div>

                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
