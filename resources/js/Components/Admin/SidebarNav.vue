<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutDashboard, Users, KeyRound, ShieldCheck, Store, MessageSquareText } from '@lucide/vue';

defineEmits(['navigate']);

const page = usePage();

const isUrl = (path) => {
    const current = page.url.replace(/^\//, '').split('?')[0];
    return path === '' ? current === '' : current.startsWith(path);
};

const can = computed(() => page.props.auth.can);
const pendingProviders = computed(() => page.props.adminBadges?.pendingProviders ?? 0);
const pendingQuoteRequests = computed(() => page.props.adminBadges?.pendingQuoteRequests ?? 0);

const dashboardItem = computed(() => ({
    label: 'Dashboard',
    icon: LayoutDashboard,
    href: '/dashboard',
    active: isUrl('dashboard') || isUrl(''),
}));

const adminItems = computed(() => [
    { label: 'Utilizatori', icon: Users, href: '/administration/users', show: can.value.viewUsers, active: isUrl('administration/users') },
    { label: 'Roluri', icon: KeyRound, href: '/administration/roles', show: can.value.viewRoles, active: isUrl('administration/roles') },
    { label: 'Permisiuni', icon: ShieldCheck, href: '/administration/permissions', show: can.value.viewPermissions, active: isUrl('administration/permissions') },
    {
        label: 'Furnizori',
        icon: Store,
        href: '/administration/providers',
        show: can.value.moderateProviders,
        active: isUrl('administration/providers'),
        badge: pendingProviders.value || null,
    },
    {
        label: 'Cereri de ofertă',
        icon: MessageSquareText,
        href: '/administration/quote-requests',
        show: can.value.moderateQuoteRequests,
        active: isUrl('administration/quote-requests'),
        badge: pendingQuoteRequests.value || null,
    },
].filter((item) => item.show));
</script>

<template>
    <nav class="flex-1">
        <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-ink-soft/50 mb-2">Meniu</p>
        <div class="space-y-1">
            <Link
                v-if="can.viewDashboard"
                :href="dashboardItem.href"
                @click="$emit('navigate')"
                class="group flex items-center gap-3 px-3 py-2.5 rounded-2xl text-sm font-medium transition-all duration-200 ease-out"
                :class="dashboardItem.active
                    ? 'bg-brand-500 text-white shadow-md shadow-brand-500/25'
                    : 'text-ink-soft hover:bg-brand-50/60 hover:text-brand-600 hover:shadow-sm hover:shadow-ink/5 hover:-translate-y-px'"
            >
                <span
                    class="flex items-center justify-center w-8 h-8 rounded-xl transition-all duration-200 ease-out flex-none group-hover:scale-110"
                    :class="dashboardItem.active ? 'bg-white/15' : 'bg-paper group-hover:bg-white group-hover:shadow-sm'"
                >
                    <component :is="dashboardItem.icon" class="w-4 h-4" />
                </span>
                {{ dashboardItem.label }}
            </Link>
        </div>

        <template v-if="can.viewAdministration && adminItems.length">
            <p class="px-3 mt-6 text-[11px] font-semibold uppercase tracking-wider text-ink-soft/50 mb-2">Administrare</p>
            <div class="space-y-1">
                <Link
                    v-for="item in adminItems"
                    :key="item.label"
                    :href="item.href"
                    @click="$emit('navigate')"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-2xl text-sm font-medium transition-all duration-200 ease-out"
                    :class="item.active
                        ? 'bg-brand-500 text-white shadow-md shadow-brand-500/25'
                        : 'text-ink-soft hover:bg-brand-50/60 hover:text-brand-600 hover:shadow-sm hover:shadow-ink/5 hover:-translate-y-px'"
                >
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-xl transition-all duration-200 ease-out flex-none group-hover:scale-110"
                        :class="item.active ? 'bg-white/15' : 'bg-paper group-hover:bg-white group-hover:shadow-sm'"
                    >
                        <component :is="item.icon" class="w-4 h-4" />
                    </span>
                    <span class="flex-1">{{ item.label }}</span>
                    <span
                        v-if="item.badge"
                        class="flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-[11px] font-semibold rounded-full"
                        :class="item.active ? 'bg-white/20 text-white' : 'bg-gold-400/15 text-gold-500'"
                    >
                        {{ item.badge }}
                    </span>
                </Link>
            </div>
        </template>
    </nav>
</template>
