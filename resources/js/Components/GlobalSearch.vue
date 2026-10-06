<script setup>
import { ref, watch, nextTick } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import { MagnifyingGlassIcon, XMarkIcon, TagIcon, BuildingStorefrontIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';

const show = defineModel('show', { default: false });

const query = ref('');
const results = ref([]);
const loading = ref(false);
const inputRef = ref(null);

const icons = { listing: TagIcon, provider: BuildingStorefrontIcon, category: Squares2X2Icon };

const fetchResults = debounce(async () => {
    const term = query.value.trim();
    if (term.length < 2) {
        results.value = [];
        loading.value = false;
        return;
    }

    try {
        const { data } = await axios.get(route('search.suggest'), { params: { q: term } });
        results.value = data.results;
    } catch {
        results.value = [];
    } finally {
        loading.value = false;
    }
}, 250);

watch(query, () => {
    loading.value = true;
    fetchResults();
});

watch(show, async (open) => {
    if (open) {
        query.value = '';
        results.value = [];
        await nextTick();
        inputRef.value?.focus();
    }
});

const goToResult = (result) => {
    show.value = false;
    router.visit(result.url);
};

const seeAllResults = () => {
    show.value = false;
    router.visit(route('listings.index', { q: query.value.trim() }));
};
</script>

<template>
    <TransitionRoot as="template" :show="show">
        <Dialog as="div" class="relative z-[60]" @close="show = false">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-ivt-ink/50 backdrop-blur-sm" />
            </TransitionChild>

            <div class="fixed inset-0 flex items-start justify-center px-4 pt-[12vh]">
                <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100" leave="ease-in duration-150" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                    <DialogPanel class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl shadow-ivt-ink/20">
                        <div class="flex items-center gap-3 border-b border-ivt-line px-5 py-4">
                            <MagnifyingGlassIcon class="h-5 w-5 flex-none text-ivt-ink-soft" />
                            <input
                                ref="inputRef"
                                v-model="query"
                                type="text"
                                placeholder="Caută furnizori, anunțuri, categorii…"
                                class="w-full border-0 p-0 text-[15px] text-ivt-ink placeholder:text-ivt-ink-soft/60 focus:outline-none focus:ring-0"
                                @keyup.enter="results.length ? goToResult(results[0]) : seeAllResults()"
                            />
                            <button type="button" class="flex-none text-ivt-ink-soft hover:text-ivt-ink" @click="show = false">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="max-h-[60vh] overflow-y-auto">
                            <div v-if="loading" class="px-5 py-8 text-center text-sm text-ivt-ink-soft">Se caută…</div>

                            <ul v-else-if="results.length" class="p-2">
                                <li v-for="result in results" :key="`${result.type}-${result.id}`">
                                    <Link
                                        :href="result.url"
                                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors duration-150 hover:bg-ivt-paper-2"
                                        @click="show = false"
                                    >
                                        <span class="flex h-10 w-10 flex-none items-center justify-center overflow-hidden rounded-xl bg-ivt-paper-2 text-ivt-ink-soft">
                                            <img v-if="result.image" :src="result.image" alt="" class="h-full w-full object-cover" />
                                            <component :is="icons[result.type]" v-else class="h-4.5 w-4.5" />
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-ivt-ink">{{ result.title }}</span>
                                            <span class="block truncate text-xs text-ivt-ink-soft">{{ result.subtitle }}</span>
                                        </span>
                                    </Link>
                                </li>
                            </ul>

                            <div v-else-if="query.trim().length >= 2" class="px-5 py-8 text-center text-sm text-ivt-ink-soft">
                                Niciun rezultat pentru „{{ query.trim() }}”.
                            </div>

                            <div v-else class="px-5 py-8 text-center text-sm text-ivt-ink-soft">
                                Scrie cel puțin 2 caractere ca să căutăm.
                            </div>
                        </div>

                        <button
                            v-if="query.trim().length >= 2"
                            type="button"
                            class="flex w-full items-center justify-center gap-1.5 border-t border-ivt-line px-5 py-3 text-[13px] font-semibold text-primary transition-colors hover:bg-ivt-paper-2"
                            @click="seeAllResults"
                        >
                            Vezi toate rezultatele pentru „{{ query.trim() }}”
                        </button>
                    </DialogPanel>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
