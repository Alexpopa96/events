<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import {
    MagnifyingGlassIcon,
    XMarkIcon,
    TagIcon,
    BuildingStorefrontIcon,
    Squares2X2Icon,
    MapPinIcon,
    DocumentTextIcon,
    ClockIcon,
    ArrowRightIcon,
    ArrowTurnDownLeftIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';

const show = defineModel('show', { default: false });

const query = ref('');
const results = ref([]);
const stems = ref([]);
const popular = ref([]);
const loading = ref(false);
const searched = ref(false);
const active = ref(0);
const inputRef = ref(null);
const listRef = ref(null);

const term = computed(() => query.value.trim());
const hasTerm = computed(() => term.value.length >= 2);

const groups = {
    category: { label: 'Categorii', icon: Squares2X2Icon },
    location: { label: 'Locații', icon: MapPinIcon },
    listing: { label: 'Anunțuri', icon: TagIcon },
    provider: { label: 'Furnizori', icon: BuildingStorefrontIcon },
    page: { label: 'Pagini', icon: DocumentTextIcon },
};

/* ---------- Recent searches (per browser, best effort) ---------- */
const RECENT_KEY = 'global-search:recent';
const readRecent = () => {
    try {
        return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]').slice(0, 5);
    } catch {
        return [];
    }
};
const recent = ref(readRecent());
const remember = (value) => {
    const clean = value.trim();
    if (clean.length < 2) return;
    recent.value = [clean, ...recent.value.filter((item) => item.toLowerCase() !== clean.toLowerCase())].slice(0, 5);
    try {
        localStorage.setItem(RECENT_KEY, JSON.stringify(recent.value));
    } catch {
        /* storage unavailable */
    }
};
const clearRecent = () => {
    recent.value = [];
    try {
        localStorage.removeItem(RECENT_KEY);
    } catch {
        /* storage unavailable */
    }
};

/* ---------- Fetching ---------- */
let controller;
const fetchResults = debounce(async () => {
    controller?.abort();
    controller = new AbortController();
    const q = term.value;

    try {
        const { data } = await axios.get(route('search.suggest'), {
            params: q.length >= 2 ? { q } : {},
            signal: controller.signal,
        });
        if (q.length >= 2) {
            results.value = data.results;
            stems.value = data.stems ?? [];
            searched.value = true;
            active.value = 0;
        } else {
            popular.value = data.popular ?? [];
        }
        loading.value = false;
    } catch (error) {
        if (!axios.isCancel(error)) {
            results.value = [];
            loading.value = false;
        }
    }
}, 180);

watch(query, () => {
    if (!hasTerm.value) {
        controller?.abort();
        loading.value = false;
        results.value = [];
        searched.value = false;
        active.value = 0;
        return;
    }
    loading.value = true;
    fetchResults();
});

watch(show, async (open) => {
    if (!open) return;
    query.value = '';
    results.value = [];
    searched.value = false;
    active.value = 0;
    recent.value = readRecent();
    if (!popular.value.length) fetchResults();
    await nextTick();
    inputRef.value?.focus();
});

/* ---------- Navigable items ---------- */
// One flat list in display order so ↑/↓ moves across groups and the empty state.
const items = computed(() => {
    if (hasTerm.value) {
        const ordered = Object.keys(groups).flatMap((type) => results.value.filter((r) => r.type === type));
        return [
            ...ordered.map((result) => ({ kind: 'result', ...result })),
            { kind: 'all', type: 'all', id: 'all', title: `Toate anunțurile pentru „${term.value}”`, url: route('listings.index', { q: term.value }) },
            { kind: 'all', type: 'all', id: 'providers', title: `Toți furnizorii pentru „${term.value}”`, url: route('providers.index', { q: term.value }) },
        ];
    }

    return [
        ...recent.value.map((value) => ({ kind: 'recent', id: `recent-${value}`, title: value })),
        ...popular.value.map((category) => ({ kind: 'popular', id: `popular-${category.url}`, ...category })),
    ];
});

const sections = computed(() => {
    const out = [];
    items.value.forEach((item, index) => {
        const key = item.kind === 'result' ? item.type : item.kind;
        let section = out[out.length - 1];
        if (!section || section.key !== key) {
            section = { key, items: [] };
            out.push(section);
        }
        section.items.push({ ...item, index });
    });
    return out;
});

const sectionLabel = (key) => groups[key]?.label ?? { recent: 'Căutări recente', popular: 'Categorii populare', all: 'Caută peste tot' }[key];

const choose = (item) => {
    if (!item) return;
    if (item.kind === 'recent') {
        query.value = item.title;
        inputRef.value?.focus();
        return;
    }
    if (hasTerm.value) remember(term.value);
    show.value = false;
    router.visit(item.url);
};

const move = async (step) => {
    const count = items.value.length;
    if (!count) return;
    active.value = (active.value + step + count) % count;
    await nextTick();
    listRef.value?.querySelector(`[data-index="${active.value}"]`)?.scrollIntoView({ block: 'nearest' });
};

const onEnter = () => {
    if (loading.value && hasTerm.value) {
        // Don't act on stale results: search everything for what's typed.
        remember(term.value);
        show.value = false;
        router.visit(route('listings.index', { q: term.value }));
        return;
    }
    choose(items.value[active.value]);
};

/* ---------- Highlighting ---------- */
// Diacritic-insensitive: normalise per character so indexes map back to the original text.
const fold = (text) => [...text].map((char) => char.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()[0] ?? char).join('');

const highlight = (text) => {
    if (!text || !stems.value.length) return [{ text, hit: false }];
    const folded = fold(text);
    const marks = new Array(text.length).fill(false);
    stems.value.forEach((stem) => {
        if (!stem) return;
        let from = 0;
        let at;
        while ((at = folded.indexOf(stem, from)) !== -1) {
            for (let i = at; i < at + stem.length; i++) marks[i] = true;
            from = at + stem.length;
        }
    });
    const parts = [];
    [...text].forEach((char, i) => {
        const last = parts[parts.length - 1];
        if (last && last.hit === marks[i]) last.text += char;
        else parts.push({ text: char, hit: marks[i] });
    });
    return parts;
};
</script>

<template>
    <TransitionRoot as="template" :show="show">
        <Dialog as="div" class="relative z-[60]" @close="show = false">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-ivt-ink/40 backdrop-blur-md" />
            </TransitionChild>

            <div class="fixed inset-0 flex items-start justify-center p-0 sm:px-4 sm:pt-[10vh]">
                <TransitionChild
                    as="template"
                    enter="ease-out duration-200"
                    enter-from="opacity-0 -translate-y-2 sm:translate-y-0 sm:scale-[0.97]"
                    enter-to="opacity-100 translate-y-0 sm:scale-100"
                    leave="ease-in duration-150"
                    leave-from="opacity-100 sm:scale-100"
                    leave-to="opacity-0 sm:scale-[0.97]"
                >
                    <DialogPanel class="flex max-h-[100dvh] w-full flex-col overflow-hidden bg-white font-invita shadow-ivt-deep ring-1 ring-ivt-line sm:max-h-[75vh] sm:max-w-2xl sm:rounded-3xl">
                        <!-- Input -->
                        <div class="relative flex items-center gap-3 px-5 py-4 sm:px-6">
                            <MagnifyingGlassIcon class="h-5 w-5 flex-none text-primary" />
                            <input
                                ref="inputRef"
                                v-model="query"
                                type="text"
                                role="combobox"
                                aria-autocomplete="list"
                                :aria-expanded="items.length > 0"
                                autocomplete="off"
                                spellcheck="false"
                                placeholder="Caută servicii, furnizori, orașe, pagini…"
                                class="w-full border-0 bg-transparent p-0 text-[17px] text-ivt-ink placeholder:text-ivt-ink-faint focus:outline-none focus:ring-0"
                                @keydown.down.prevent="move(1)"
                                @keydown.up.prevent="move(-1)"
                                @keydown.enter.prevent="onEnter"
                            />
                            <button
                                v-if="query"
                                type="button"
                                class="flex-none rounded-full px-2 py-1 text-xs font-medium text-ivt-ink-soft hover:bg-ivt-paper-2 hover:text-ivt-ink"
                                @click="query = ''; inputRef?.focus()"
                            >
                                Șterge
                            </button>
                            <button type="button" aria-label="Închide" class="flex-none rounded-full p-1.5 text-ivt-ink-soft hover:bg-ivt-paper-2 hover:text-ivt-ink" @click="show = false">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                            <!-- Thin progress line while a request is in flight -->
                            <div class="absolute inset-x-0 bottom-0 h-px bg-ivt-line">
                                <div v-if="loading" class="progress h-px w-1/3 bg-brand" />
                            </div>
                        </div>

                        <!-- Body -->
                        <div ref="listRef" class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-2 sm:p-3" role="listbox">
                            <!-- First load skeleton -->
                            <div v-if="loading && hasTerm && !searched" class="space-y-1 p-1">
                                <div v-for="n in 5" :key="n" class="flex items-center gap-3 rounded-2xl px-3 py-2.5">
                                    <div class="h-11 w-11 flex-none animate-pulse rounded-xl bg-ivt-paper-2" />
                                    <div class="flex-1 space-y-2">
                                        <div class="h-3 animate-pulse rounded bg-ivt-paper-2" :style="{ width: `${40 + n * 9}%` }" />
                                        <div class="h-2.5 w-1/4 animate-pulse rounded bg-ivt-paper-2" />
                                    </div>
                                </div>
                            </div>

                            <template v-else>
                                <!-- No matches -->
                                <div v-if="hasTerm && searched && !results.length" class="flex flex-col items-center px-6 pb-4 pt-8 text-center">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-ivt-violet-soft text-ivt-violet">
                                        <MagnifyingGlassIcon class="h-6 w-6" />
                                    </span>
                                    <p class="mt-3 text-[15px] font-semibold text-ivt-ink">Nimic direct pentru „{{ term }}”</p>
                                    <p class="mt-1 text-sm text-ivt-ink-soft">Încearcă un cuvânt mai scurt sau caută în toate anunțurile.</p>
                                </div>

                                <section v-for="section in sections" :key="section.key" class="mb-1.5 last:mb-0">
                                    <div class="flex items-center justify-between px-3 pb-1 pt-2.5">
                                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ivt-ink-faint">{{ sectionLabel(section.key) }}</h3>
                                        <button
                                            v-if="section.key === 'recent'"
                                            type="button"
                                            class="text-[11px] font-medium text-ivt-ink-faint hover:text-primary"
                                            @click="clearRecent"
                                        >
                                            Șterge istoricul
                                        </button>
                                    </div>

                                    <!-- Popular categories as chips -->
                                    <div v-if="section.key === 'popular'" class="flex flex-wrap gap-2 px-3 pb-2 pt-1">
                                        <button
                                            v-for="item in section.items"
                                            :key="item.id"
                                            type="button"
                                            :data-index="item.index"
                                            class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-[13px] font-medium transition-colors"
                                            :class="active === item.index
                                                ? 'border-primary/30 bg-ivt-sand text-primary'
                                                : 'border-ivt-line bg-white text-ivt-ink-soft hover:border-primary/30 hover:text-primary'"
                                            @mouseenter="active = item.index"
                                            @click="choose(item)"
                                        >
                                            <SparklesIcon class="h-3.5 w-3.5" />
                                            {{ item.title }}
                                        </button>
                                    </div>

                                    <ul v-else>
                                        <li v-for="item in section.items" :key="item.id">
                                            <button
                                                type="button"
                                                role="option"
                                                :aria-selected="active === item.index"
                                                :data-index="item.index"
                                                class="group flex w-full items-center gap-3 rounded-2xl px-3 py-2 text-left transition-colors duration-100"
                                                :class="active === item.index ? 'bg-ivt-paper-2' : ''"
                                                @mouseenter="active = item.index"
                                                @click="choose(item)"
                                            >
                                                <!-- Recent search -->
                                                <template v-if="item.kind === 'recent'">
                                                    <ClockIcon class="h-4.5 w-4.5 flex-none text-ivt-ink-faint" />
                                                    <span class="flex-1 truncate text-sm text-ivt-ink">{{ item.title }}</span>
                                                </template>

                                                <!-- "Search everywhere" actions -->
                                                <template v-else-if="item.kind === 'all'">
                                                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-ivt-sand text-primary">
                                                        <MagnifyingGlassIcon class="h-4.5 w-4.5" />
                                                    </span>
                                                    <span class="flex-1 truncate text-sm font-medium text-ivt-ink">{{ item.title }}</span>
                                                    <ArrowRightIcon class="h-4 w-4 flex-none text-ivt-ink-faint transition-transform group-hover:translate-x-0.5" />
                                                </template>

                                                <!-- Result -->
                                                <template v-else>
                                                    <span
                                                        class="flex flex-none items-center justify-center overflow-hidden text-ivt-ink-soft ring-1 ring-ivt-line"
                                                        :class="[
                                                            item.type === 'listing' ? 'h-11 w-11 rounded-xl' : 'h-9 w-9 rounded-xl',
                                                            item.type === 'provider' ? 'rounded-full' : '',
                                                            item.image ? 'bg-white' : 'bg-ivt-paper-2',
                                                        ]"
                                                    >
                                                        <img v-if="item.image" :src="item.image" alt="" class="h-full w-full object-cover" loading="lazy" />
                                                        <component :is="groups[item.type].icon" v-else class="h-4.5 w-4.5" />
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block truncate text-sm font-medium text-ivt-ink">
                                                            <template v-for="(part, i) in highlight(item.title)" :key="i">
                                                                <mark v-if="part.hit" class="rounded-[3px] bg-ivt-sand px-px text-primary">{{ part.text }}</mark>
                                                                <template v-else>{{ part.text }}</template>
                                                            </template>
                                                        </span>
                                                        <span class="block truncate text-xs text-ivt-ink-soft">{{ item.subtitle }}</span>
                                                    </span>
                                                    <span v-if="item.meta" class="hidden flex-none text-xs font-medium text-ivt-ink-soft sm:block">{{ item.meta }}</span>
                                                    <ArrowTurnDownLeftIcon
                                                        class="h-4 w-4 flex-none text-ivt-ink-faint transition-opacity"
                                                        :class="active === item.index ? 'opacity-100' : 'opacity-0'"
                                                    />
                                                </template>
                                            </button>
                                        </li>
                                    </ul>
                                </section>

                                <!-- Empty state without history / popular -->
                                <div v-if="!hasTerm && !items.length" class="px-6 py-10 text-center text-sm text-ivt-ink-soft">
                                    Caută după serviciu, furnizor, oraș sau pagină — de ex. „fotografi Cluj”.
                                </div>
                            </template>
                        </div>

                        <!-- Footer hints -->
                        <div class="hidden items-center justify-between border-t border-ivt-line bg-ivt-paper-2/60 px-5 py-2.5 text-[11px] text-ivt-ink-faint sm:flex">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center gap-1"><kbd class="kbd">↑</kbd><kbd class="kbd">↓</kbd> navighează</span>
                                <span class="flex items-center gap-1"><kbd class="kbd">↵</kbd> deschide</span>
                                <span class="flex items-center gap-1"><kbd class="kbd">esc</kbd> închide</span>
                            </div>
                            <span>Nu contează diacriticele sau pluralul</span>
                        </div>
                    </DialogPanel>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<style scoped>
.kbd {
    @apply inline-flex min-w-[20px] items-center justify-center rounded-md border border-ivt-line bg-white px-1 py-px font-invita text-[10px] font-medium text-ivt-ink-soft shadow-[0_1px_0_rgba(26,20,51,0.08)];
}

.progress {
    animation: search-progress 1s ease-in-out infinite;
}

@keyframes search-progress {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(300%); }
}
</style>
