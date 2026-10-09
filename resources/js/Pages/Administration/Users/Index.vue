<template>
    <Layout title="Utilizatori" :breadcrumbs="['Administrare', 'Utilizatori']">
        <Head title="Utilizatori" />
        <div class="space-y-5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="tab in statusTabs"
                        :key="tab.label"
                        type="button"
                        @click.prevent="params.status = tab.value"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-sm font-medium transition-all duration-150"
                        :class="(params.status ?? null) === tab.value
                            ? 'bg-primary text-white shadow-sm shadow-primary/25'
                            : 'text-ivt-ink-soft bg-white border border-ivt-line hover:bg-ivt-paper-2 hover:text-primary'"
                    >
                        {{ tab.label }}
                    </button>
                    <span class="ml-1 inline-flex items-center px-2.5 py-1 rounded-full bg-ivt-paper-2 text-primary text-xs font-semibold">
                        {{ totalUsers }} utilizatori
                    </span>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="relative w-full sm:w-72">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ivt-ink-soft/50" />
                        <input
                            v-model="params.search"
                            type="text"
                            placeholder="Caută utilizator..."
                            class="w-full pl-9 pr-3 py-2 rounded-xl border-ivt-line bg-white text-sm text-ivt-ink placeholder:text-ivt-ink-soft/50 focus:border-primary focus:ring-primary"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <select
                            v-model.number="params.perPage"
                            class="py-2 pl-3 pr-8 rounded-xl border-ivt-line bg-white text-sm text-ivt-ink focus:border-primary focus:ring-primary"
                            title="Rânduri pe pagină"
                        >
                            <option :value="10">10</option>
                            <option :value="15">15</option>
                            <option :value="25">25</option>
                        </select>
                        <button
                            type="button"
                            @click.prevent="exportExcel"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-ivt-line bg-white text-sm font-semibold text-ivt-ink-soft transition-colors hover:border-ivt-violet hover:text-primary"
                        >
                            <Loader2 v-if="exporting" class="w-4 h-4 animate-spin" />
                            <Download v-else class="w-4 h-4" />
                            Export
                        </button>
                        <Link
                            href="/administration/users/create"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand text-sm font-semibold text-white shadow-sm shadow-primary/25 transition-colors hover:brightness-110 hover:shadow-glow-violet whitespace-nowrap"
                        >
                            <Plus class="w-4 h-4" />
                            Adaugă utilizator
                        </Link>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-ivt-line bg-white overflow-hidden shadow-sm shadow-ivt-ink/5">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ivt-line">
                        <thead class="bg-ivt-paper-2/40">
                            <tr>
                                <Heading
                                    multi-column
                                    :direction="filters.direction"
                                    :selected="filters.field === 'name'"
                                    @removeSort="removeSort"
                                    @sort="sort('name')"
                                >
                                    Nume
                                </Heading>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ivt-ink-soft/70">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ivt-ink-soft/70">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ivt-ink-soft/70">Telefon</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ivt-ink-soft/70">Rol</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ivt-line bg-white">
                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-ivt-ink-soft">
                                    Nu au fost găsite înregistrări.
                                </td>
                            </tr>
                            <tr v-else v-for="user in users.data" :key="user.id" class="hover:bg-ivt-paper-2/30 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="w-2 h-2 rounded-full flex-none"
                                            :class="cacheUsers.includes(user.id) ? 'bg-success-500' : 'bg-ivt-ink-soft/25'"
                                            :title="cacheUsers.includes(user.id) ? 'Online' : 'Offline'"
                                        ></span>
                                        <button
                                            v-if="$page.props.auth.user.roles[0].id == 1"
                                            type="button"
                                            @click.prevent="impersonate(user.id)"
                                            class="text-sm font-semibold text-ivt-ink hover:text-primary transition-colors"
                                            title="Impersonează"
                                        >{{ user.name }}</button>
                                        <span v-else class="text-sm font-semibold text-ivt-ink">{{ user.name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                        :class="user.status == 1 ? 'bg-success-50 text-success-600' : 'bg-danger-50 text-danger-600'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        {{ user.status == 1 ? 'Activ' : 'Inactiv' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-ivt-ink-soft whitespace-nowrap">{{ user.email }}</td>
                                <td class="px-4 py-3 text-sm text-ivt-ink-soft whitespace-nowrap">{{ user.phone ? user.phone : '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span v-if="user.role" class="inline-flex px-2.5 py-1 rounded-full bg-ivt-paper-2 text-primary text-xs font-semibold">{{ user.role }}</span>
                                    <span v-else class="text-sm text-ivt-ink-soft">—</span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <Link
                                        :href="`/administration/users/${user.id}/edit`"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:text-ivt-ink"
                                    >
                                        <Pencil class="w-3.5 h-3.5" /> Editează
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination :name="users" :links="users.links" v-if="users.total > perPage" />
        </div>
    </Layout>
</template>

<script>
import Layout from '@/Layouts/Layout.vue';
import Heading from '@/Components/Table/Heading.vue'
import Pagination from "@/Components/Pagination.vue"
import { Head, Link } from '@inertiajs/vue3'
import pickBy from "lodash/pickBy";
import throttle from "lodash/throttle";
import axiosLink from '@/axiosLink';
import { Search, Download, Plus, Pencil, Loader2 } from '@lucide/vue';

export default {
    components: {
        Head, Link, Pagination, Heading, Layout, Search, Download, Plus, Pencil, Loader2
    },
    data() {
        return {
            params: {
                search: this.filters.search,
                field: this.filters.field,
                direction: this.filters.direction,
                perPage: this.perPage,
                status: this.filters.status
            },
            exporting: false,
            statusTabs: [
                { label: 'Toți', value: null },
                { label: 'Activi', value: 1 },
                { label: 'Inactivi', value: 2 },
            ],
        };
    },

    props: {
        users: Object,
        filters: Object,
        perPage: Number,
        cacheUsers: Array,
        totalUsers: Number
    },

    methods: {
        sort(field) {
            this.params.field = field;
            this.params.direction = this.params.direction === 'asc' ? 'desc' : 'asc';
        },
        removeSort() {
            this.filters.direction = null;
            this.filters.field = null;
            this.params.direction = null;
            this.params.field = null;

        },
        async exportExcel() {
            this.exporting = true;
            await axiosLink('/administration/users/export', 'users.xlsx');
            this.exporting = false;
        },
        impersonate(id) {
            this.$inertia.post('/impersonate/' + id, { id: id }, {

            });
        }
    },
    watch: {
        params: {
            deep: true,
            handler: throttle(function () {
                let query = pickBy(this.params)
                this.$inertia.get('users', Object.keys(query).length ? query : { remember: 'forget' }, { preserveState: true })
            }, 150),
        },
    },
};
</script>
