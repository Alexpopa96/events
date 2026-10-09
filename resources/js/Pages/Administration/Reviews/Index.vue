<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Search, Star, Check, X, ArrowUpRight } from '@lucide/vue';
import Layout from '@/Layouts/Layout.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';

const props = defineProps({
    reviews: Object,
    filters: Object,
    counts: Object,
});

const search = ref(props.filters.search ?? '');

const tabs = [
    { key: 'pending', label: 'În așteptare' },
    { key: 'approved', label: 'Aprobate' },
    { key: 'rejected', label: 'Respinse' },
    { key: 'all', label: 'Toate' },
];

const applyFilters = (overrides = {}) => {
    router.get(
        route('administration.reviews.index'),
        { status: props.filters.status, search: search.value, ...overrides },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

watch(search, debounce(() => applyFilters(), 350));

const busyId = ref(null);

const moderate = (review, action) => {
    busyId.value = review.id;
    router.post(route(`administration.reviews.${action}`, review.id), {}, {
        preserveScroll: true,
        onFinish: () => { busyId.value = null; },
    });
};
</script>

<template>
    <Head title="Recenzii" />
    <Layout title="Recenzii" :breadcrumbs="['Administrare', 'Recenzii']">
        <div class="space-y-5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        @click="applyFilters({ status: tab.key })"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-sm font-medium transition-all duration-150"
                        :class="filters.status === tab.key
                            ? 'bg-primary text-white shadow-sm shadow-primary/25'
                            : 'text-ivt-ink-soft bg-ivt-paper hover:bg-ivt-paper-2/60 hover:text-primary'"
                    >
                        {{ tab.label }}
                        <span
                            class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[11px] font-semibold"
                            :class="filters.status === tab.key ? 'bg-white/20' : 'bg-white text-ivt-ink-soft'"
                        >{{ counts[tab.key] ?? 0 }}</span>
                    </button>
                </div>

                <div class="relative w-full lg:w-72">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ivt-ink-soft/50" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Caută autor, anunț, furnizor, text..."
                        class="w-full pl-9 pr-3 py-2 rounded-xl border-ivt-line text-sm text-ivt-ink placeholder:text-ivt-ink-soft/50 focus:border-primary focus:ring-primary"
                    />
                </div>
            </div>

            <div v-if="reviews.data.length === 0" class="rounded-2xl border border-dashed border-ivt-line bg-white px-5 py-12 text-center text-sm text-ivt-ink-soft">
                Nu există recenzii pentru acest filtru.
            </div>

            <ul v-else class="space-y-3">
                <li v-for="review in reviews.data" :key="review.id" class="rounded-2xl border border-ivt-line bg-white p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <p class="text-sm font-semibold text-ivt-ink">{{ review.author }}</p>
                                <span class="inline-flex items-center gap-1 rounded-full bg-ivt-violet/15 px-2 py-0.5 text-xs font-semibold text-ivt-violet">
                                    <Star class="h-3 w-3 fill-current" /> {{ review.rating }}/5
                                </span>
                                <StatusBadge :status="review.status" />
                            </div>
                            <p class="mt-0.5 text-xs text-ivt-ink-soft">
                                {{ review.author_email }} · {{ review.created_at }}
                            </p>
                        </div>

                        <div class="flex flex-none gap-2">
                            <button
                                v-if="review.status !== 'approved'"
                                type="button"
                                :disabled="busyId === review.id"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-brand px-3.5 py-2 text-xs font-semibold text-white transition-colors hover:brightness-110 hover:shadow-glow-violet disabled:opacity-50"
                                @click="moderate(review, 'approve')"
                            >
                                <Check class="h-3.5 w-3.5" /> Aprobă
                            </button>
                            <button
                                v-if="review.status !== 'rejected'"
                                type="button"
                                :disabled="busyId === review.id"
                                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold text-danger-600 ring-1 ring-danger-200 transition-colors hover:bg-danger-50 disabled:opacity-50"
                                @click="moderate(review, 'reject')"
                            >
                                <X class="h-3.5 w-3.5" /> Respinge
                            </button>
                        </div>
                    </div>

                    <p v-if="review.comment" class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ivt-ink">{{ review.comment }}</p>
                    <p v-else class="mt-3 text-sm italic text-ivt-ink-soft/70">Fără comentariu, doar rating.</p>

                    <p class="mt-3 flex flex-wrap items-center gap-x-1.5 text-xs text-ivt-ink-soft">
                        Pentru
                        <Link
                            v-if="review.listing_slug"
                            :href="route('listings.show', review.listing_slug)"
                            class="inline-flex items-center gap-0.5 font-semibold text-primary hover:text-ivt-ink"
                        >{{ review.listing_title }} <ArrowUpRight class="h-3 w-3" /></Link>
                        <span v-else class="font-semibold">{{ review.listing_title ?? '—' }}</span>
                        <span v-if="review.provider">· {{ review.provider }}</span>
                    </p>
                </li>
            </ul>

            <Pagination :name="reviews" :links="reviews.links" v-if="reviews.total > reviews.per_page" />
        </div>
    </Layout>
</template>
