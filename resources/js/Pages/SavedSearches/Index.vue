<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { BookmarkIcon, TrashIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    savedSearches: { type: Array, default: () => [] },
});

const toast = useToast();
const searches = ref(props.savedSearches);

const searchToDelete = ref(null);
const deleting = ref(false);

const confirmDelete = () => {
    deleting.value = true;
    router.delete(route('saved-searches.destroy', searchToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            searches.value = searches.value.filter((s) => s.id !== searchToDelete.value.id);
            toast.success('Căutarea salvată a fost ștearsă.');
        },
        onFinish: () => { deleting.value = false; searchToDelete.value = null; },
    });
};

const dialogOpen = (open) => { if (!open) searchToDelete.value = null; };
</script>

<template>
    <Head title="Căutări salvate" />

    <div class="overflow-x-clip bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <section class="relative pb-8 pt-8 lg:pb-10 lg:pt-10">
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
                    <nav class="flex flex-wrap items-center gap-2 text-[13px] text-ivt-ink-faint" aria-label="Breadcrumb">
                        <Link href="/" class="transition-colors hover:text-primary">Acasă</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <Link :href="route('profile.show')" class="transition-colors hover:text-primary">Contul meu</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <span class="text-ivt-ink-soft">Căutări salvate</span>
                    </nav>

                    <div class="mt-8 lg:mt-10">
                        <p class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 py-1 pl-1.5 pr-3.5 text-[12.5px] font-semibold text-ivt-ink-soft shadow-sm backdrop-blur">
                            <span class="rounded-full bg-brand px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider text-white">{{ searches.length }}</span>
                            {{ searches.length === 1 ? 'căutare salvată' : 'căutări salvate' }} · alerte pe email
                        </p>

                        <h1 class="mt-6 text-balance font-display text-[34px] font-bold leading-[1.05] tracking-tight text-ivt-ink sm:text-[44px] lg:text-[52px]">
                            Căutările tale,
                            <span class="relative whitespace-nowrap">
                                <span class="text-gradient">mereu cu un pas înainte.</span>
                                <svg class="absolute -bottom-2 left-0 h-3 w-full text-ivt-accent-bright" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true">
                                    <path d="M2 9 C 80 2, 200 2, 298 7" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                </svg>
                            </span>
                        </h1>

                        <p class="mt-6 max-w-[480px] text-[15.5px] leading-relaxed text-ivt-ink-soft">
                            Te anunțăm prin email de îndată ce apare un anunț nou care se potrivește filtrelor salvate.
                        </p>

                        <Link :href="route('listings.index')" class="btn-brand mt-9 inline-flex rounded-2xl px-7 py-3.5 text-sm font-bold">
                            <MagnifyingGlassIcon class="h-4 w-4" stroke-width="2.5" />
                            Caută anunțuri
                        </Link>
                    </div>
                </div>
            </section>

            <section class="pb-20 pt-4">
                <div class="mx-auto max-w-[1600px] px-6 lg:px-8">
                    <div v-if="!searches.length" class="flex flex-col items-center justify-center rounded-3xl bg-white px-5 py-20 text-center ring-1 ring-ivt-line sm:py-24">
                        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <BookmarkIcon class="h-7 w-7" />
                        </span>
                        <p class="mt-5 font-display text-[24px] font-bold tracking-tight text-ivt-ink sm:text-[28px]">Nicio căutare salvată încă</p>
                        <p class="mt-2 max-w-md text-[15.5px] leading-relaxed text-ivt-ink-soft">Din pagina de anunțuri, aplică filtrele care te interesează și apasă „Salvează această căutare”.</p>
                        <Link :href="route('listings.index')" class="btn-brand mt-7 inline-flex rounded-2xl px-7 py-3.5 text-sm font-bold">
                            <MagnifyingGlassIcon class="h-4 w-4" /> Caută anunțuri
                        </Link>
                    </div>

                    <ul v-else class="max-w-4xl space-y-3.5">
                        <li v-for="search in searches" :key="search.id" class="flex items-center gap-4 rounded-2xl border border-ivt-line bg-white p-5">
                            <span class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                <BookmarkIcon class="h-5 w-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-ivt-ink">{{ search.name }}</p>
                                <p class="mt-0.5 truncate text-xs text-ivt-ink-soft">{{ search.summary }} · salvată {{ search.created_at }}</p>
                            </div>
                            <Link
                                :href="route('listings.index', search.query)"
                                class="flex-none rounded-full bg-ivt-paper-2 px-4 py-2 text-xs font-semibold text-ivt-ink transition-colors hover:bg-primary hover:text-white"
                            >
                                Vezi anunțurile
                            </Link>
                            <button
                                type="button"
                                class="flex-none rounded-full p-2 text-ivt-ink-soft transition-colors hover:bg-danger-50 hover:text-danger-600"
                                title="Șterge"
                                @click="searchToDelete = search"
                            >
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </li>
                    </ul>
                </div>
            </section>
        </main>

        <SiteFooter />

        <ConfirmDialog
            :show="!!searchToDelete"
            @update:show="dialogOpen"
            title="Ștergi căutarea salvată?"
            :message="searchToDelete ? `Nu mai primești alerte pentru „${searchToDelete.name}”.` : ''"
            confirm-label="Șterge"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
