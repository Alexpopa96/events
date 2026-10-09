<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeftIcon, XMarkIcon, BellIcon, ArrowTopRightOnSquareIcon, HomeIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';
import SideNav from '@/Components/Provider/SideNav.vue';
import BottomNav from '@/Components/Provider/BottomNav.vue';
import ProviderRail from '@/Components/Provider/ProviderRail.vue';
import SidebarHeader from '@/Components/Provider/SidebarHeader.vue';
import AccountMenu from '@/Components/Provider/AccountMenu.vue';
import Logo from '@/Components/Logo.vue';
import { useLiveUnreadBadge } from '@/Composables/useLiveUnreadBadge';

useLiveUnreadBadge();

const props = defineProps({
    title: {
        type: String,
        default: 'Prezentare',
    },
    // Optional override: [{ label, href? }, ...]. Derived from the route when omitted.
    breadcrumbs: {
        type: Array,
        default: null,
    },
    // Feed pages get a narrow centred column plus the right rail, like a
    // social timeline. Everything else (forms, tables) keeps a wide column.
    feed: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();

// Section a page belongs to; pages that are the section itself get no middle crumb.
const sections = [
    { pattern: 'provider.listings.*', label: 'Anunțurile mele', route: 'provider.listings.index' },
    { pattern: 'provider.messages.*', label: 'Mesaje', route: 'provider.messages.index' },
    { pattern: 'provider.leads.*', label: 'Cereri clienți', route: 'provider.leads.index' },
    { pattern: 'provider.offers.*', label: 'Ofertele mele', route: 'provider.offers.index' },
    { pattern: 'provider.availability.*', label: 'Calendar', route: 'provider.availability.index' },
    { pattern: 'provider.reviews.*', label: 'Recenzii', route: 'provider.reviews.index' },
    { pattern: 'provider.subscription.*', label: 'Abonament & facturi', route: 'provider.subscription.index' },
    { pattern: 'provider.profile.*', label: 'Profil companie', route: 'provider.profile.edit' },
];

const crumbs = computed(() => {
    if (props.breadcrumbs) return props.breadcrumbs;

    const trail = [{ label: 'Panou', href: route('provider.dashboard'), home: true }];
    const section = sections.find((s) => route().current(s.pattern));

    if (section && !route().current(section.route)) {
        trail.push({ label: section.label, href: route(section.route) });
    }

    trail.push({ label: props.title });

    return trail;
});

// Every page except the dashboard gets a back button to its parent crumb.
const backHref = computed(() => {
    if (route().current('provider.dashboard')) return null;

    return crumbs.value.length > 1 ? crumbs.value[crumbs.value.length - 2].href ?? null : null;
});

const nav = computed(() => page.props.providerNav);
const unreadNotifications = computed(() => page.props.unreadNotifications ?? 0);

const initials = (name) => (name || '')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

const drawerOpen = ref(false);
</script>

<template>
    <Head :title="title" />

    <div class="relative min-h-screen bg-ivt-paper-2/50 font-invita text-ivt-ink overflow-x-clip">
        <!-- Mobile drawer (account, subscription, company profile) -->
        <div v-if="drawerOpen" class="fixed inset-0 z-50 lg:hidden">
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
                appear
            >
                <div class="absolute inset-0 bg-ivt-ink/40 backdrop-blur-[2px]" @click="drawerOpen = false"></div>
            </transition>
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="-translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="translate-x-0"
                leave-to-class="-translate-x-full"
                appear
            >
                <aside class="absolute inset-y-0 left-0 w-[19rem] max-w-[85vw] bg-white p-5 flex flex-col shadow-[0_24px_60px_-12px_rgba(26,20,51,0.35)]">
                    <div class="flex items-center justify-between mb-6">
                        <Logo variant="invita" />
                        <button @click="drawerOpen = false" class="p-1.5 rounded-full text-ivt-ink-soft hover:bg-ivt-paper" aria-label="Închide meniul"><XMarkIcon class="w-5 h-5" /></button>
                    </div>
                    <SidebarHeader @navigate="drawerOpen = false" />
                    <SideNav @navigate="drawerOpen = false" />
                    <AccountMenu />
                </aside>
            </transition>
        </div>

        <!-- Top bar -->
        <header class="sticky top-0 z-40 border-b border-ivt-line bg-white/85 backdrop-blur-xl">
            <div class="mx-auto flex h-14 sm:h-16 max-w-[100rem] items-center gap-3 px-3 sm:px-4">
                <button
                    v-if="nav"
                    @click="drawerOpen = true"
                    class="lg:hidden flex-none w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center text-xs font-semibold overflow-hidden"
                    aria-label="Deschide meniul"
                >
                    <img v-if="nav.logo_url" :src="nav.logo_url" alt="" class="w-full h-full object-cover" />
                    <span v-else>{{ initials(nav.company_name) }}</span>
                </button>

                <Link :href="route('provider.dashboard')" class="hidden lg:block flex-none w-60 px-2">
                    <Logo size="lg" variant="invita" />
                </Link>

                <Link
                    v-if="backHref"
                    :href="backHref"
                    class="flex-none flex h-9 w-9 items-center justify-center rounded-full bg-ivt-paper-2 text-ivt-ink-soft transition-colors duration-150 hover:bg-ivt-paper-3 hover:text-primary"
                    aria-label="Înapoi"
                    title="Înapoi"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>

                <div class="min-w-0 flex flex-col">
                    <nav aria-label="Breadcrumb" class="hidden sm:flex items-center gap-1 text-xs text-ivt-ink-soft min-w-0">
                        <template v-for="(crumb, i) in crumbs" :key="i">
                            <ChevronRightIcon v-if="i > 0" class="w-3 h-3 flex-none text-ivt-ink-soft/50" />
                            <Link
                                v-if="crumb.href && i < crumbs.length - 1"
                                :href="crumb.href"
                                class="flex items-center gap-1 whitespace-nowrap rounded-md transition-colors duration-150 hover:text-primary"
                            >
                                <HomeIcon v-if="crumb.home" class="w-3.5 h-3.5" />
                                {{ crumb.label }}
                            </Link>
                            <span v-else class="truncate font-medium text-ivt-violet" aria-current="page">{{ crumb.label }}</span>
                        </template>
                    </nav>
                    <h1 class="font-display text-lg sm:text-xl text-ivt-ink leading-tight truncate">{{ title }}</h1>
                </div>

                <div class="ml-auto flex flex-none items-center gap-2">
                    <slot name="actions" />

                    <Link
                        v-if="nav?.slug"
                        :href="route('providers.show', nav.slug)"
                        class="hidden md:inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-ivt-paper-2 px-3.5 py-2 text-xs font-medium text-ivt-ink-soft transition-colors duration-150 hover:bg-ivt-paper-3 hover:text-primary"
                    >
                        Profil public <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                    </Link>

                    <Link
                        v-if="nav"
                        :href="route('notifications.index')"
                        class="relative flex items-center justify-center w-10 h-10 rounded-full bg-ivt-paper-2 text-ivt-ink-soft transition-colors duration-150 hover:bg-ivt-paper-3 hover:text-primary"
                        aria-label="Notificări"
                    >
                        <BellIcon class="w-5 h-5" />
                        <span
                            v-if="unreadNotifications"
                            class="absolute -top-0.5 -right-0.5 min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-primary text-white text-[10px] font-semibold flex items-center justify-center ring-2 ring-white"
                        >{{ unreadNotifications > 99 ? '99+' : unreadNotifications }}</span>
                    </Link>

                    <div class="hidden lg:block"><AccountMenu variant="header" /></div>
                </div>
            </div>
        </header>

        <div class="mx-auto flex max-w-[100rem] gap-6 px-0 lg:px-4">
            <!-- Left rail: navigation -->
            <aside class="hidden lg:block lg:w-60 lg:flex-none sticky top-16 self-start py-5">
                <div class="flex flex-col rounded-3xl border border-ivt-line bg-white p-3">
                    <SidebarHeader compact />
                    <SideNav />
                </div>
            </aside>

            <!-- Timeline -->
            <main class="flex-1 min-w-0 px-3 sm:px-4 lg:px-0 py-4 lg:py-5 pb-28 lg:pb-10">
                <div :class="feed ? 'mx-auto max-w-4xl' : ''">
                    <slot />
                </div>
            </main>

            <!-- Right rail -->
            <aside v-if="feed || $slots.rail" class="hidden xl:block w-80 flex-none">
                <div class="sticky top-16 py-5 space-y-4 max-h-[calc(100vh-4rem)] overflow-y-auto">
                    <slot name="rail"><ProviderRail /></slot>
                </div>
            </aside>
        </div>

        <BottomNav v-if="nav" />
    </div>
</template>
