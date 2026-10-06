<template>
    <Layout title="Roluri" :breadcrumbs="['Administrare', 'Roluri']">
        <Head title="Roluri" />
        <div class="space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative w-full sm:w-72">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ivt-ink-soft/50" />
                        <input
                            v-model="params.search"
                            type="text"
                            placeholder="Caută rol..."
                            class="w-full pl-9 pr-3 py-2 rounded-xl border-ivt-line bg-white text-sm text-ivt-ink placeholder:text-ivt-ink-soft/50 focus:border-primary focus:ring-primary"
                        />
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-ivt-paper-2 text-primary text-xs font-semibold">
                        {{ totalRoles }} roluri
                    </span>
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
                    <Link
                        href="/administration/roles/create"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary text-sm font-semibold text-white shadow-sm shadow-primary/25 transition-colors hover:bg-primary-bright whitespace-nowrap"
                    >
                        <Plus class="w-4 h-4" />
                        Adaugă rol
                    </Link>
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
                                    Denumire rol
                                </Heading>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ivt-line bg-white">
                            <tr v-if="roles.data.length === 0">
                                <td colspan="2" class="px-4 py-10 text-center text-sm text-ivt-ink-soft">
                                    Nu au fost găsite înregistrări.
                                </td>
                            </tr>
                            <tr v-else v-for="role in roles.data" :key="role.id" class="hover:bg-ivt-paper-2/30 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-ivt-paper-2 text-primary flex-none">
                                            <KeyRound class="w-4 h-4" />
                                        </span>
                                        <span class="text-sm font-semibold text-ivt-ink">{{ role.name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <Link
                                        :href="`/administration/roles/${role.id}/edit`"
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

            <Pagination :name="roles" :links="roles.links" v-if="roles.total > perPage" />
        </div>
    </Layout>
</template>

<script>
import Layout from '@/Layouts/Layout.vue';
import Heading from '@/Components/Table/Heading.vue'
import Pagination from "@/Components/Pagination.vue"
import {Head, Link} from '@inertiajs/vue3'
import pickBy from "lodash/pickBy";
import throttle from "lodash/throttle";
import { Search, Plus, Pencil, KeyRound } from '@lucide/vue';
import mapValues from 'lodash/mapValues'

export default {
    components: {
        Head, Link, Pagination, Heading, Layout, Search, Plus, Pencil, KeyRound
    },
    data() {
        return {
            params: {
                search: this.filters.search,
                field: this.filters.field,
                direction: this.filters.direction,
                perPage: this.perPage
            },
            isEditable: null
        };
    },

    props: {
        roles: Object,
        filters: Object,
        perPage: Number,
        totalRoles: Number
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
        reset() {
            this.params = mapValues(this.params, () => null)
        },
    },
    watch: {
        params: {
            deep: true,
            handler: throttle(function() {
                let query = pickBy(this.params)
                this.$inertia.get('roles',Object.keys(query).length ? query : { remember: 'forget' }, { preserveState: true })
            },150 ),
        },
    },
};
</script>
