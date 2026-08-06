<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import {
    Users,
    Store,
    Clock,
    FileText,
    MessageSquare,
    KeyRound,
    ShieldCheck,
    ArrowUpRight,
} from '@lucide/vue';

defineProps({
    stats: { type: Array, default: () => [] },
    pendingProviders: { type: Array, default: () => [] },
});

const page = usePage();
const can = computed(() => page.props.auth.can);

const icons = {
    'Utilizatori': Users,
    'Furnizori activi': Store,
    'Furnizori în așteptare': Clock,
    'Anunțuri publicate': FileText,
    'Cereri de ofertă deschise': MessageSquare,
    'Cereri în așteptare': Clock,
};
const iconFor = (label) => icons[label] ?? Users;

const accents = {
    'Utilizatori': 'text-brand-600 bg-brand-50',
    'Furnizori activi': 'text-emerald-600 bg-emerald-50',
    'Furnizori în așteptare': 'text-gold-500 bg-gold-400/10',
    'Anunțuri publicate': 'text-sky-600 bg-sky-50',
    'Cereri de ofertă deschise': 'text-violet-600 bg-violet-50',
    'Cereri în așteptare': 'text-gold-500 bg-gold-400/10',
};
const accentFor = (label) => accents[label] ?? 'text-brand-600 bg-brand-50';

const quickLinks = computed(() => [
    { label: 'Utilizatori', description: 'Conturi și acces platformă', icon: Users, href: '/administration/users', show: can.value.viewUsers },
    { label: 'Roluri', description: 'Grupuri de permisiuni', icon: KeyRound, href: '/administration/roles', show: can.value.viewRoles },
    { label: 'Permisiuni', description: 'Control granular al accesului', icon: ShieldCheck, href: '/administration/permissions', show: can.value.viewPermissions },
    { label: 'Furnizori', description: 'Aprobare și moderare firme', icon: Store, href: '/administration/providers', show: can.value.moderateProviders },
].filter((item) => item.show));

const approve = (provider) => {
    if (confirm(`Aprobi firma "${provider.company_name}"?`)) {
        router.post(route('administration.providers.approve', provider.id), {}, { preserveScroll: true });
    }
};

const reject = (provider) => {
    if (confirm(`Respingi firma "${provider.company_name}"?`)) {
        router.post(route('administration.providers.reject', provider.id), {}, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Dashboard" />
    <Layout title="Dashboard" :breadcrumbs="['Dashboard']">
        <div class="space-y-8">
            <div>
                <h2 class="font-serif text-2xl text-ink">Bună, {{ page.props.auth.user.name.split(' ')[0] }} 👋</h2>
                <p class="text-sm text-ink-soft mt-1">Iată o privire de ansamblu asupra platformei.</p>
            </div>

            <div v-if="stats.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <component
                    :is="stat.href ? Link : 'div'"
                    v-for="stat in stats"
                    :key="stat.label"
                    :href="stat.href"
                    class="group relative overflow-hidden rounded-2xl border border-line bg-white p-5 shadow-sm shadow-ink/5 transition-all duration-200"
                    :class="stat.href ? 'hover:-translate-y-0.5 hover:shadow-md hover:shadow-ink/10 cursor-pointer' : ''"
                >
                    <div class="flex items-center justify-between">
                        <span class="flex items-center justify-center w-10 h-10 rounded-xl" :class="accentFor(stat.label)">
                            <component :is="iconFor(stat.label)" class="w-5 h-5" />
                        </span>
                        <ArrowUpRight
                            v-if="stat.href"
                            class="w-4 h-4 text-ink-soft/40 transition-transform duration-200 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        />
                    </div>
                    <p class="mt-4 text-2xl font-semibold text-ink">{{ stat.value }}</p>
                    <p class="text-sm text-ink-soft">{{ stat.label }}</p>
                </component>
            </div>

            <div v-if="quickLinks.length">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-ink-soft/60 mb-3">Acces rapid</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Link
                        v-for="link in quickLinks"
                        :key="link.label"
                        :href="link.href"
                        class="group flex items-start gap-3 rounded-2xl border border-line bg-white p-4 shadow-sm shadow-ink/5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:shadow-ink/10 hover:border-brand-100"
                    >
                        <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-paper text-brand-600 flex-none transition-all duration-200 group-hover:bg-brand-500 group-hover:text-white group-hover:scale-105">
                            <component :is="link.icon" class="w-4 h-4" />
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-ink">{{ link.label }}</span>
                            <span class="block text-xs text-ink-soft mt-0.5">{{ link.description }}</span>
                        </span>
                    </Link>
                </div>
            </div>

            <div v-if="pendingProviders.length" class="rounded-2xl border border-line bg-white shadow-sm shadow-ink/5 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-line">
                    <div>
                        <h3 class="text-sm font-semibold text-ink">Furnizori în așteptare de aprobare</h3>
                        <p class="text-xs text-ink-soft mt-0.5">Cele mai recente {{ pendingProviders.length }} cereri</p>
                    </div>
                    <Link href="/administration/providers" class="text-xs font-medium text-brand-600 hover:text-brand-700 inline-flex items-center gap-1">
                        Vezi toate <ArrowUpRight class="w-3.5 h-3.5" />
                    </Link>
                </div>
                <ul class="divide-y divide-line">
                    <li v-for="provider in pendingProviders" :key="provider.id" class="flex items-center justify-between gap-4 px-5 py-3.5">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink truncate">{{ provider.company_name }}</p>
                            <p class="text-xs text-ink-soft truncate">{{ provider.user?.name }} · {{ provider.user?.email }}</p>
                        </div>
                        <div class="flex items-center gap-2 flex-none">
                            <button
                                type="button"
                                @click="approve(provider)"
                                class="px-3 py-1.5 text-xs font-semibold text-white bg-brand-500 rounded-lg hover:bg-brand-600 transition-colors"
                            >
                                Aprobă
                            </button>
                            <button
                                type="button"
                                @click="reject(provider)"
                                class="px-3 py-1.5 text-xs font-semibold text-rose-600 bg-rose-50 rounded-lg hover:bg-rose-100 transition-colors"
                            >
                                Respinge
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </Layout>
</template>
