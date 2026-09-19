<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@heroicons/vue/24/outline';
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
    { key: 'vendors', label: 'Furnizori salvați', count: providers.value.length },
    { key: 'listings', label: 'Anunțuri salvate', count: listings.value.length },
]);
const activeTab = ref('vendors');

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

    <div class="bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <!-- Page header -->
            <section class="relative overflow-hidden border-b border-ivt-line bg-ivt-paper-2 py-12">
                <div
                    class="pointer-events-none absolute inset-x-[-10%] -top-[40%] h-[150%]"
                    style="background: radial-gradient(50% 60% at 15% 0%, rgba(168,127,46,0.10), transparent 60%), radial-gradient(45% 45% at 95% 10%, rgba(124,46,59,0.08), transparent 60%);"
                />
                <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
                    <nav class="mb-5 flex flex-wrap items-center gap-1.5 text-[13px] text-ivt-ink-faint">
                        <Link href="/" class="text-ivt-ink-soft transition-colors hover:text-ivt-wine">Acasă</Link>
                        <ChevronRightIcon class="h-3.5 w-3.5" />
                        <Link :href="route('profile.show')" class="text-ivt-ink-soft transition-colors hover:text-ivt-wine">Contul meu</Link>
                        <ChevronRightIcon class="h-3.5 w-3.5" />
                        <span>Favorite</span>
                    </nav>

                    <div>
                        <p class="flex items-center gap-2.5 text-xs font-bold uppercase tracking-[0.18em] text-ivt-wine">
                            <span class="inline-block h-px w-[22px] bg-ivt-gold" /> Contul meu
                        </p>
                        <h1 class="mt-2.5 font-serif text-[32px] leading-tight text-ivt-ink sm:text-[36px]">Favoritele mele</h1>
                        <p class="mt-2 max-w-md text-[14.5px] text-ivt-ink-soft">Furnizorii și anunțurile pe care le-ai salvat, ca să le compari mai ușor și să revii la ele oricând.</p>
                    </div>
                </div>
            </section>

            <section class="py-10 sm:py-12">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">

                    <div class="mb-7 flex flex-wrap gap-1.5 border-b border-ivt-line pb-5">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            @click="activeTab = tab.key"
                            class="flex items-center gap-1.5 rounded-full px-4 py-2 text-[13.5px] font-semibold transition-all"
                            :class="activeTab === tab.key ? 'bg-ivt-ink text-ivt-paper' : 'text-ivt-ink-soft hover:bg-ivt-paper-2'"
                        >
                            {{ tab.label }}
                            <span
                                class="rounded-full px-2 py-0.5 text-[11px]"
                                :class="activeTab === tab.key ? 'bg-white/20 text-ivt-paper' : 'bg-ivt-paper-3 text-ivt-ink-soft'"
                            >{{ tab.count }}</span>
                        </button>
                    </div>

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
                            <p class="mt-3 font-serif text-[22px] italic text-ivt-ink">Niciun furnizor salvat</p>
                            <p class="mt-2 text-[14px] text-ivt-ink-faint">Apasă pe inimioară pe cardul unui furnizor ca să îl adaugi aici.</p>
                            <Link
                                :href="route('providers.index')"
                                class="mt-6 inline-flex items-center gap-2 rounded-full bg-gradient-to-b from-ivt-wine-bright to-ivt-wine px-6 py-3 text-sm font-semibold text-ivt-paper transition-transform hover:-translate-y-0.5"
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
                            <p class="mt-3 font-serif text-[22px] italic text-ivt-ink">Niciun anunț salvat</p>
                            <p class="mt-2 text-[14px] text-ivt-ink-faint">Apasă pe inimioară pe un anunț ca să îl adaugi aici.</p>
                            <Link
                                :href="route('listings.index')"
                                class="mt-6 inline-flex items-center gap-2 rounded-full bg-gradient-to-b from-ivt-wine-bright to-ivt-wine px-6 py-3 text-sm font-semibold text-ivt-paper transition-transform hover:-translate-y-0.5"
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
