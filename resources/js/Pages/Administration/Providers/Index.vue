<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Search, Building2, ArrowUpRight } from '@lucide/vue';
import Layout from '@/Layouts/Layout.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';

const props = defineProps({
    providers: Object,
    filters: Object,
    counts: Object,
});

const search = ref(props.filters.search ?? '');

const tabs = [
    { key: 'all', label: 'Toate' },
    { key: 'pending', label: 'În așteptare' },
    { key: 'active', label: 'Active' },
    { key: 'rejected', label: 'Respinse' },
    { key: 'suspended', label: 'Suspendate' },
];

const applyFilters = (overrides = {}) => {
    router.get(
        route('administration.providers.index'),
        { status: props.filters.status, search: search.value, ...overrides },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const setStatus = (status) => applyFilters({ status });

watch(search, debounce(() => applyFilters(), 350));
</script>

<template>
    <Head title="Furnizori" />
    <Layout title="Furnizori" :breadcrumbs="['Administrare', 'Furnizori']">
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
                            ? 'bg-brand-500 text-white shadow-sm shadow-brand-500/25'
                            : 'text-ink-soft bg-paper hover:bg-brand-50/60 hover:text-brand-600'"
                    >
                        {{ tab.label }}
                        <span
                            class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[11px] font-semibold"
                            :class="filters.status === tab.key ? 'bg-white/20' : 'bg-white text-ink-soft'"
                        >
                            {{ counts[tab.key] ?? 0 }}
                        </span>
                    </button>
                </div>

                <div class="relative w-full lg:w-72">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-soft/50" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Caută firmă, CUI, email..."
                        class="w-full pl-9 pr-3 py-2 rounded-xl border-line text-sm text-ink placeholder:text-ink-soft/50 focus:border-brand-400 focus:ring-brand-400"
                    />
                </div>
            </div>

            <div class="rounded-2xl border border-line overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line">
                        <thead class="bg-paper">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-soft/70">Firmă</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-soft/70">Contact</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-soft/70">Localitate</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-soft/70">Anunțuri</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-soft/70">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-soft/70">Înregistrat</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line bg-white">
                            <tr v-if="providers.data.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-ink-soft">
                                    Nu există furnizori pentru acest filtru.
                                </td>
                            </tr>
                            <tr
                                v-for="provider in providers.data"
                                :key="provider.id"
                                class="hover:bg-paper/60 transition-colors cursor-pointer"
                                @click="router.get(route('administration.providers.show', provider.id))"
                            >
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-paper overflow-hidden flex-none">
                                            <img v-if="provider.logo_url" :src="provider.logo_url" class="w-full h-full object-cover" />
                                            <Building2 v-else class="w-4 h-4 text-ink-soft/50" />
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-ink truncate">{{ provider.company_name }}</p>
                                            <p class="text-xs text-ink-soft">{{ provider.cui ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <p class="text-ink">{{ provider.user?.name }}</p>
                                    <p class="text-xs text-ink-soft">{{ provider.user?.email }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-ink-soft whitespace-nowrap">
                                    {{ provider.locality?.name ?? '—' }}<template v-if="provider.county">, {{ provider.county.name }}</template>
                                </td>
                                <td class="px-4 py-3 text-sm text-ink-soft">{{ provider.listings_count }}</td>
                                <td class="px-4 py-3"><StatusBadge :status="provider.status" /></td>
                                <td class="px-4 py-3 text-sm text-ink-soft whitespace-nowrap">{{ provider.created_at }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="route('administration.providers.show', provider.id)"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:text-brand-700"
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

            <Pagination :name="providers" :links="providers.links" v-if="providers.total > providers.per_page" />
        </div>
    </Layout>
</template>
