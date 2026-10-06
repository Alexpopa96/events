<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Search, MessageSquareText, ArrowUpRight } from '@lucide/vue';
import Layout from '@/Layouts/Layout.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';

const props = defineProps({
    quoteRequests: Object,
    filters: Object,
    counts: Object,
});

const search = ref(props.filters.search ?? '');

const tabs = [
    { key: 'all', label: 'Toate' },
    { key: 'pending_review', label: 'În așteptare' },
    { key: 'open', label: 'Aprobate' },
    { key: 'rejected', label: 'Respinse' },
    { key: 'closed', label: 'Închise' },
];

const applyFilters = (overrides = {}) => {
    router.get(
        route('administration.quote-requests.index'),
        { status: props.filters.status, search: search.value, ...overrides },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const setStatus = (status) => applyFilters({ status });

watch(search, debounce(() => applyFilters(), 350));
</script>

<template>
    <Head title="Cereri de ofertă" />
    <Layout title="Cereri de ofertă" :breadcrumbs="['Administrare', 'Cereri de ofertă']">
        <div class="space-y-5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        @click="setStatus(tab.key)"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-sm font-medium transition-all duration-150"
                        :class="filters.status === tab.key
                            ? 'bg-primary text-white shadow-sm shadow-primary/25'
                            : 'text-ivt-ink-soft bg-ivt-paper hover:bg-ivt-paper-2/60 hover:text-primary'"
                    >
                        {{ tab.label }}
                        <span
                            class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[11px] font-semibold"
                            :class="filters.status === tab.key ? 'bg-white/20' : 'bg-white text-ivt-ink-soft'"
                        >
                            {{ counts[tab.key] ?? 0 }}
                        </span>
                    </button>
                </div>

                <div class="relative w-full lg:w-72">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ivt-ink-soft/50" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Caută titlu, nume, email, telefon..."
                        class="w-full pl-9 pr-3 py-2 rounded-xl border-ivt-line text-sm text-ivt-ink placeholder:text-ivt-ink-soft/50 focus:border-primary focus:ring-primary"
                    />
                </div>
            </div>

            <div class="rounded-2xl border border-ivt-line overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ivt-line">
                        <thead class="bg-ivt-paper">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ivt-ink-soft/70">Cerere</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ivt-ink-soft/70">Contact</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ivt-ink-soft/70">Localitate</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ivt-ink-soft/70">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ivt-ink-soft/70">Trimisă</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ivt-line bg-white">
                            <tr v-if="quoteRequests.data.length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-ivt-ink-soft">
                                    Nu există cereri pentru acest filtru.
                                </td>
                            </tr>
                            <tr
                                v-for="quoteRequest in quoteRequests.data"
                                :key="quoteRequest.id"
                                class="hover:bg-ivt-paper/60 transition-colors cursor-pointer"
                                @click="router.get(route('administration.quote-requests.show', quoteRequest.id))"
                            >
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-ivt-paper flex-none">
                                            <MessageSquareText class="w-4 h-4 text-ivt-ink-soft/50" />
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-ivt-ink truncate">{{ quoteRequest.title }}</p>
                                            <p class="text-xs text-ivt-ink-soft">{{ quoteRequest.category ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <p class="text-ivt-ink">{{ quoteRequest.name }}</p>
                                    <p class="text-xs text-ivt-ink-soft">{{ quoteRequest.email }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-ivt-ink-soft whitespace-nowrap">
                                    {{ quoteRequest.city ?? '—' }}<template v-if="quoteRequest.county">, {{ quoteRequest.county }}</template>
                                </td>
                                <td class="px-4 py-3"><StatusBadge :status="quoteRequest.status" /></td>
                                <td class="px-4 py-3 text-sm text-ivt-ink-soft whitespace-nowrap">{{ quoteRequest.created_at }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="route('administration.quote-requests.show', quoteRequest.id)"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:text-ivt-ink"
                                        @click.stop
                                    >
                                        Vezi <ArrowUpRight class="w-3.5 h-3.5" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination :name="quoteRequests" :links="quoteRequests.links" v-if="quoteRequests.total > quoteRequests.per_page" />
        </div>
    </Layout>
</template>
