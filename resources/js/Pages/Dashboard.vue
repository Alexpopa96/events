<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import Sparkline from '@/Components/Admin/Dashboard/Sparkline.vue';
import GrowthChart from '@/Components/Admin/Dashboard/GrowthChart.vue';
import StatusBar from '@/Components/Admin/Dashboard/StatusBar.vue';
import BarList from '@/Components/Admin/Dashboard/BarList.vue';
import RevenueChart from '@/Components/Admin/Dashboard/RevenueChart.vue';
import {
    Users,
    Store,
    Clock,
    FileText,
    MessageSquare,
    ArrowUpRight,
    TrendingUp,
    TrendingDown,
    Star,
    UserPlus,
    Inbox,
    CircleCheck,
} from '@lucide/vue';

const props = defineProps({
    range: { type: Number, default: 30 },
    kpis: { type: Array, default: () => [] },
    attention: { type: Array, default: () => [] },
    growth: { type: Object, default: null },
    providerStatus: { type: Array, default: () => [] },
    quoteStatus: { type: Array, default: () => [] },
    topCategories: { type: Array, default: () => [] },
    revenue: { type: Object, default: null },
    activity: { type: Array, default: () => [] },
    pendingProviders: { type: Array, default: () => [] },
});

const page = usePage();

// Categorical slots from the validated data-viz palette (identity), not brand colors.
const SERIES = {
    users: { label: 'Utilizatori', color: '#2a78d6' },
    providers: { label: 'Furnizori', color: '#eb6834' },
    quote_requests: { label: 'Cereri de ofertă', color: '#1baf7a' },
    listings: { label: 'Anunțuri publicate', color: '#4a3aa7' },
};

const kpiIcons = { users: Users, providers: Store, quote_requests: MessageSquare, listings: FileText };
const kpiTint = {
    users: 'bg-[#2a78d6]/10 text-[#2a78d6]',
    providers: 'bg-[#eb6834]/10 text-[#c2491a]',
    quote_requests: 'bg-[#1baf7a]/10 text-[#0f7f57]',
    listings: 'bg-[#4a3aa7]/10 text-[#4a3aa7]',
};

const providerColors = { active: '#1f8a4c', pending: '#c98500', suspended: '#8a9186', rejected: '#e34948' };
const quoteColors = { open: '#1f8a4c', pending_review: '#c98500', closed: '#8a9186', rejected: '#e34948' };

const rangeOptions = [
    { value: 7, label: '7 zile' },
    { value: 30, label: '30 zile' },
    { value: 90, label: '90 zile' },
];

const setRange = (value) => {
    router.get('/', { range: value }, { preserveScroll: true, preserveState: true, replace: true });
};

const activityIcons = { user: UserPlus, provider: Store, quote_request: Inbox, listing: FileText, review: Star };
const activityTint = {
    user: 'bg-[#2a78d6]/10 text-[#2a78d6]',
    provider: 'bg-[#eb6834]/10 text-[#c2491a]',
    quote_request: 'bg-[#1baf7a]/10 text-[#0f7f57]',
    listing: 'bg-[#4a3aa7]/10 text-[#4a3aa7]',
    review: 'bg-ivt-gold/10 text-ivt-gold',
};

const timeAgo = (iso) => {
    const seconds = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000);
    const units = [[86400, 'zi', 'zile'], [3600, 'oră', 'ore'], [60, 'minut', 'minute']];
    for (const [size, one, many] of units) {
        const n = Math.floor(seconds / size);
        if (n >= 1) return `acum ${n} ${n === 1 ? one : many}`;
    }
    return 'chiar acum';
};

const totalAttention = computed(() => props.attention.reduce((sum, a) => sum + a.value, 0));
const revenueLabel = computed(() => props.revenue
    ? new Intl.NumberFormat('ro-RO', { style: 'currency', currency: props.revenue.currency, maximumFractionDigits: 0 }).format(props.revenue.total)
    : '');

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
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="font-serif text-2xl text-ivt-ink">Bună, {{ page.props.auth.user.name.split(' ')[0] }} 👋</h2>
                    <p class="text-sm text-ivt-ink-soft mt-1">Iată o privire de ansamblu asupra platformei.</p>
                </div>
                <div class="inline-flex rounded-xl border border-ivt-line bg-white p-1 shadow-sm shadow-ivt-ink/5" role="group" aria-label="Interval de timp">
                    <button
                        v-for="option in rangeOptions"
                        :key="option.value"
                        type="button"
                        @click="setRange(option.value)"
                        class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors"
                        :class="range === option.value ? 'bg-primary text-white' : 'text-ivt-ink-soft hover:text-ivt-ink'"
                    >
                        {{ option.label }}
                    </button>
                </div>
            </div>

            <!-- Attention strip -->
            <div v-if="attention.length" class="flex flex-wrap gap-3">
                <template v-if="totalAttention">
                    <Link
                        v-for="item in attention.filter((a) => a.value)"
                        :key="item.label"
                        :href="item.href"
                        class="group inline-flex items-center gap-3 rounded-2xl border border-ivt-gold/30 bg-ivt-gold/10 pl-3 pr-4 py-2.5 transition-colors hover:bg-ivt-gold/15"
                    >
                        <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-white text-ivt-gold"><Clock class="w-4 h-4" /></span>
                        <span class="text-sm text-ivt-ink"><span class="font-semibold">{{ item.value }}</span> {{ item.label.toLowerCase() }}</span>
                        <ArrowUpRight class="w-4 h-4 text-ivt-ink-soft/50 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                    </Link>
                </template>
                <div v-else class="inline-flex items-center gap-2.5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-800">
                    <CircleCheck class="w-4 h-4" /> Nimic de moderat acum. Ești la zi.
                </div>
            </div>

            <!-- KPI cards -->
            <div v-if="kpis.length" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <component
                    :is="kpi.href ? Link : 'div'"
                    v-for="kpi in kpis"
                    :key="kpi.key"
                    :href="kpi.href"
                    class="group rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5 transition-all duration-200"
                    :class="kpi.href ? 'hover:-translate-y-0.5 hover:shadow-md hover:shadow-ivt-ink/10' : ''"
                >
                    <div class="flex items-center justify-between">
                        <span class="flex items-center justify-center w-10 h-10 rounded-xl" :class="kpiTint[kpi.key]">
                            <component :is="kpiIcons[kpi.key]" class="w-5 h-5" />
                        </span>
                        <span
                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold"
                            :class="kpi.change === null ? 'bg-ivt-paper-2 text-ivt-ink-soft' : kpi.change >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
                        >
                            <template v-if="kpi.change === null">+{{ kpi.value }} noi</template>
                            <template v-else>
                                <component :is="kpi.change >= 0 ? TrendingUp : TrendingDown" class="w-3 h-3" />
                                {{ kpi.change > 0 ? '+' : '' }}{{ kpi.change }}%
                            </template>
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold text-ivt-ink leading-none">{{ kpi.total }}</p>
                    <p class="mt-1 text-sm text-ivt-ink-soft">{{ kpi.label }}</p>
                    <div class="mt-3">
                        <Sparkline :values="kpi.spark" :color="SERIES[kpi.key].color" />
                    </div>
                    <p class="mt-1 text-xs text-ivt-ink-faint">+{{ kpi.value }} în ultimele {{ range }} zile</p>
                </component>
            </div>

            <!-- Growth + provider status -->
            <div v-if="growth || providerStatus.length" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <section v-if="growth" class="lg:col-span-2 rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
                    <h3 class="text-sm font-semibold text-ivt-ink">Creștere platformă</h3>
                    <p class="text-xs text-ivt-ink-soft mt-0.5 mb-4">Înregistrări noi pe zi, ultimele {{ range }} zile</p>
                    <GrowthChart :growth="growth" :meta="SERIES" />
                </section>
                <section v-if="providerStatus.length" class="rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5" :class="growth ? '' : 'lg:col-span-3'">
                    <h3 class="text-sm font-semibold text-ivt-ink">Furnizori după status</h3>
                    <p class="text-xs text-ivt-ink-soft mt-0.5 mb-4">Toate firmele înregistrate</p>
                    <StatusBar :items="providerStatus" :colors="providerColors" />
                </section>
            </div>

            <!-- Quote requests, categories, revenue -->
            <div v-if="quoteStatus.length || revenue" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <section v-if="quoteStatus.length" class="rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
                    <h3 class="text-sm font-semibold text-ivt-ink">Cereri de ofertă</h3>
                    <p class="text-xs text-ivt-ink-soft mt-0.5 mb-4">Distribuție după status</p>
                    <StatusBar :items="quoteStatus" :colors="quoteColors" />
                </section>
                <section v-if="topCategories.length" class="rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
                    <h3 class="text-sm font-semibold text-ivt-ink">Categorii cerute</h3>
                    <p class="text-xs text-ivt-ink-soft mt-0.5 mb-4">Cele mai multe cereri, ultimele {{ range }} zile</p>
                    <BarList :items="topCategories" color="#1baf7a" />
                </section>
                <section v-else-if="quoteStatus.length" class="rounded-2xl border border-dashed border-ivt-line bg-white/60 p-5 flex items-center justify-center text-sm text-ivt-ink-faint">
                    Nicio cerere în ultimele {{ range }} zile.
                </section>
                <section v-if="revenue" class="rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-ivt-ink">Venituri din abonamente</h3>
                            <p class="text-xs text-ivt-ink-soft mt-0.5 mb-4">Facturi plătite, ultimele 6 luni</p>
                        </div>
                        <p class="text-lg font-semibold text-ivt-ink">{{ revenueLabel }}</p>
                    </div>
                    <RevenueChart :months="revenue.months" :currency="revenue.currency" />
                </section>
            </div>

            <!-- Activity + pending providers -->
            <div v-if="activity.length || pendingProviders.length" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <section v-if="activity.length" class="rounded-2xl border border-ivt-line bg-white shadow-sm shadow-ivt-ink/5" :class="pendingProviders.length ? 'lg:col-span-2' : 'lg:col-span-3'">
                    <div class="px-5 py-4 border-b border-ivt-line">
                        <h3 class="text-sm font-semibold text-ivt-ink">Activitate recentă</h3>
                        <p class="text-xs text-ivt-ink-soft mt-0.5">Ultimele evenimente de pe platformă</p>
                    </div>
                    <ol class="px-5 py-2">
                        <li v-for="(item, i) in activity" :key="i" class="relative flex items-center gap-3 py-2.5">
                            <span v-if="i < activity.length - 1" class="absolute left-4 top-[calc(50%+18px)] h-[calc(100%-20px)] w-px bg-ivt-line" aria-hidden="true"></span>
                            <span class="relative flex items-center justify-center w-8 h-8 rounded-full flex-none" :class="activityTint[item.type]">
                                <component :is="activityIcons[item.type]" class="w-4 h-4" />
                            </span>
                            <component :is="item.href ? Link : 'div'" :href="item.href" class="min-w-0 flex-1" :class="item.href ? 'group' : ''">
                                <p class="text-sm text-ivt-ink truncate" :class="item.href ? 'group-hover:text-primary' : ''">
                                    <span class="font-medium">{{ item.title }}</span>
                                    <span class="text-ivt-ink-soft"> · {{ item.subject }}</span>
                                </p>
                            </component>
                            <time :datetime="item.at" class="text-xs text-ivt-ink-faint flex-none">{{ timeAgo(item.at) }}</time>
                        </li>
                    </ol>
                </section>

                <section v-if="pendingProviders.length" class="rounded-2xl border border-ivt-line bg-white shadow-sm shadow-ivt-ink/5 overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-ivt-line">
                        <div>
                            <h3 class="text-sm font-semibold text-ivt-ink">De aprobat</h3>
                            <p class="text-xs text-ivt-ink-soft mt-0.5">Cele mai recente {{ pendingProviders.length }} cereri</p>
                        </div>
                        <Link href="/administration/providers" class="text-xs font-medium text-primary hover:text-ivt-ink inline-flex items-center gap-1">
                            Vezi toate <ArrowUpRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                    <ul class="divide-y divide-ivt-line">
                        <li v-for="provider in pendingProviders" :key="provider.id" class="px-5 py-3.5">
                            <p class="text-sm font-medium text-ivt-ink truncate">{{ provider.company_name }}</p>
                            <p class="text-xs text-ivt-ink-soft truncate">{{ provider.user?.name }} · {{ provider.user?.email }}</p>
                            <div class="flex items-center gap-2 mt-2.5">
                                <button type="button" @click="approve(provider)" class="px-3 py-1.5 text-xs font-semibold text-white bg-primary rounded-lg hover:bg-primary-bright transition-colors">Aprobă</button>
                                <button type="button" @click="reject(provider)" class="px-3 py-1.5 text-xs font-semibold text-rose-600 bg-rose-50 rounded-lg hover:bg-rose-100 transition-colors">Respinge</button>
                            </div>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </Layout>
</template>
