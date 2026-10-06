<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ChevronRightIcon, BookmarkIcon, TrashIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
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

    <div class="bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
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
                        <span>Căutări salvate</span>
                    </nav>

                    <div>
                        <p class="flex items-center gap-2.5 text-xs font-bold uppercase tracking-[0.18em] text-ivt-wine">
                            <span class="inline-block h-px w-[22px] bg-ivt-gold" /> Contul meu
                        </p>
                        <h1 class="mt-2.5 font-serif text-[32px] leading-tight text-ivt-ink sm:text-[36px]">Căutări salvate</h1>
                        <p class="mt-2 max-w-md text-[14.5px] text-ivt-ink-soft">Te anunțăm prin email de îndată ce apare un anunț nou care se potrivește.</p>
                    </div>
                </div>
            </section>

            <section class="py-10 sm:py-12">
                <div class="mx-auto max-w-4xl px-6 lg:px-8">
                    <div v-if="!searches.length" class="flex flex-col items-center justify-center rounded-3xl bg-white px-5 py-20 text-center ring-1 ring-ivt-line">
                        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <BookmarkIcon class="h-6 w-6" />
                        </span>
                        <p class="mt-3 text-sm font-medium text-ivt-ink">Nicio căutare salvată încă</p>
                        <p class="mt-1 max-w-sm text-sm text-ivt-ink-soft">Din pagina de anunțuri, aplică filtrele care te interesează și apasă „Salvează această căutare”.</p>
                        <Link :href="route('listings.index')" class="mt-5 inline-flex items-center gap-1.5 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/25 hover:bg-primary-bright">
                            <MagnifyingGlassIcon class="h-4 w-4" /> Caută anunțuri
                        </Link>
                    </div>

                    <ul v-else class="space-y-3.5">
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
                                class="flex-none rounded-full p-2 text-ivt-ink-soft transition-colors hover:bg-rose-50 hover:text-rose-600"
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
