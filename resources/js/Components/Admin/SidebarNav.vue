<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutDashboard, Users, Store, MessageSquareText, Star } from '@lucide/vue';

defineEmits(['navigate']);

const page = usePage();

const isUrl = (path) => {
    const current = page.url.replace(/^\//, '').split('?')[0];
    return path === '' ? current === '' : current.startsWith(path);
};

const can = computed(() => page.props.auth.can);
const pendingProviders = computed(() => page.props.adminBadges?.pendingProviders ?? 0);
const pendingQuoteRequests = computed(() => page.props.adminBadges?.pendingQuoteRequests ?? 0);
const pendingReviews = computed(() => page.props.adminBadges?.pendingReviews ?? 0);

const generalItems = computed(() => [
    {
        label: 'Dashboard',
        icon: LayoutDashboard,
        href: '/dashboard',
        show: can.value.viewDashboard,
        active: isUrl('dashboard') || isUrl(''),
    },
].filter((item) => item.show));

const adminItems = computed(() => [
    { label: 'Utilizatori', icon: Users, href: '/administration/users', show: can.value.viewUsers, active: isUrl('administration/users') },
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
    {
        label: 'Recenzii',
        icon: Star,
        href: '/administration/reviews',
        show: can.value.moderateReviews,
        active: isUrl('administration/reviews'),
        badge: pendingReviews.value || null,
    },
].filter((item) => item.show));

const sections = computed(() => [
    { title: 'Meniu', items: generalItems.value },
    { title: 'Administrare', items: can.value.viewAdministration ? adminItems.value : [] },
].filter((section) => section.items.length));
</script>

<template>
    <nav class="flex-1 overflow-y-auto -mx-1 px-1">
        <div v-for="(section, index) in sections" :key="section.title" :class="index > 0 ? 'mt-6' : ''">
            <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-ivt-ink-soft/50">{{ section.title }}</p>
            <div class="space-y-1">
                <Link
                    v-for="item in section.items"
                    :key="item.label"
                    :href="item.href"
                    @click="$emit('navigate')"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-2xl text-sm font-medium transition-all duration-200 ease-out"
                    :class="item.active
                        ? 'bg-gradient-to-b from-primary-bright to-primary text-white shadow-glow-primary'
                        : 'text-ivt-ink-soft hover:bg-ivt-paper-2 hover:text-primary'"
                >
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-xl flex-none transition-all duration-200 ease-out"
                        :class="item.active
                            ? 'bg-white/15 ring-1 ring-ivt-accent-bright/40'
                            : 'bg-ivt-paper-2 text-ivt-ink-soft group-hover:text-primary group-hover:bg-white group-hover:shadow-sm'"
                    >
                        <component :is="item.icon" class="w-4 h-4" />
                    </span>
                    <span class="flex-1">{{ item.label }}</span>
                    <span
                        v-if="item.badge"
                        class="flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-[11px] font-semibold rounded-full"
                        :class="item.active ? 'bg-white/20 text-white' : 'bg-ivt-violet/15 text-ivt-violet'"
                    >
                        {{ item.badge }}
                    </span>
                </Link>
            </div>
        </div>
    </nav>
</template>
