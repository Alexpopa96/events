<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';
import {
    UserCircleIcon,
    ClipboardDocumentListIcon,
    HeartIcon,
    ArrowRightStartOnRectangleIcon,
    ChatBubbleLeftRightIcon,
    BellIcon,
    LockClosedIcon,
    XMarkIcon,
    BookmarkIcon,
    MagnifyingGlassIcon,
    Squares2X2Icon,
    BuildingStorefrontIcon,
    MapIcon,
    DocumentTextIcon,
    SparklesIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    PlusIcon,
    MegaphoneIcon,
} from '@heroicons/vue/24/outline';
import Logo from '@/Components/Logo.vue';
import PushNotificationToggle from '@/Components/PushNotificationToggle.vue';
import GlobalSearch from '@/Components/GlobalSearch.vue';
import { useLiveUnreadBadge } from '@/Composables/useLiveUnreadBadge';
import { useMobileMenu } from '@/Composables/useMobileMenu';

useLiveUnreadBadge();

const page = usePage();
const user = computed(() => page.props.auth.user);
const can = computed(() => page.props.auth.can);
const unreadMessages = computed(() => page.props.unreadMessages ?? 0);
const unreadNotifications = computed(() => page.props.unreadNotifications ?? 0);

const accountMenuOpen = ref(false);
const { open: mobileMenuOpen } = useMobileMenu();

/* Drag the sheet down by its handle to dismiss it, like a native sheet. */
const sheetDrag = ref(0);
let dragStartY = null;
const onSheetTouchStart = (event) => {
    dragStartY = event.touches[0].clientY;
};
const onSheetTouchMove = (event) => {
    if (dragStartY === null) return;
    sheetDrag.value = Math.max(0, event.touches[0].clientY - dragStartY);
};
const onSheetTouchEnd = () => {
    if (sheetDrag.value > 110) mobileMenuOpen.value = false;
    sheetDrag.value = 0;
    dragStartY = null;
};

const closeAccountMenu = () => {
    accountMenuOpen.value = false;
};

const navLinks = [
    { label: 'Categorii', icon: Squares2X2Icon, href: () => route('categories.index'), current: () => route().current('categories.*') },
    { label: 'Anunțuri', icon: MegaphoneIcon, href: () => route('listings.index'), current: () => route().current('listings.*') && !route().current('listings.map') },
    { label: 'Furnizori', icon: BuildingStorefrontIcon, href: () => route('providers.index'), current: () => route().current('providers.*') },
    { label: 'Hartă', icon: MapIcon, href: () => route('listings.map'), current: () => route().current('listings.map') },
    {
        label: 'Cereri de ofertă',
        icon: DocumentTextIcon,
        href: () => {
            if (page.props.auth.isProvider) return route('provider.leads.index');
            if (can.value.submitQuoteRequest) return route('quote-requests.index');
            return route('quote-requests.browse');
        },
        current: () => route().current('provider.leads.*') || route().current('quote-requests.*'),
    },
    { label: 'Abonamente', icon: SparklesIcon, href: () => route('subscriptions.index'), current: () => route().current('subscriptions.*'), hideForClient: true },
];

const isClient = computed(() => !!user.value && !page.props.auth.isProvider);
const visibleNavLinks = computed(() => navLinks.filter((item) => !(item.hideForClient && isClient.value)));

const postAdHref = computed(() => (can.value.submitQuoteRequest ? route('quote-requests.create') : '/register/client'));

const badge = (count) => (count > 99 ? '99+' : count);

const initials = (name) => (name || '')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

const logout = () => router.post(route('logout'));

const searchOpen = ref(false);
const isMac = typeof navigator !== 'undefined' && /Mac/.test(navigator.platform);

// Compact the bar and lift it with a shadow once the page is scrolled.
// The bar is sticky (in flow) and shrinks 16px on lg, so the browser's scroll
// anchoring shifts scrollY by that much on every toggle. A single threshold
// makes it flip back and forth (jitter) near the top; the hysteresis gap below
// is wider than the height change so a toggle can't immediately undo itself.
const scrolled = ref(false);
const onScroll = () => {
    const y = window.scrollY;
    if (!scrolled.value && y > 40) scrolled.value = true;
    else if (scrolled.value && y < 8) scrolled.value = false;
};

const onKeydown = (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        searchOpen.value = true;
    }
    if (event.key === 'Escape') closeAccountMenu();
};

let removeNavigateListener;
onMounted(() => {
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    removeNavigateListener = router.on('navigate', () => {
        accountMenuOpen.value = false;
        mobileMenuOpen.value = false;
    });
});
onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('scroll', onScroll);
    removeNavigateListener?.();
});
</script>

<template>
    <header
        class="safe-top sticky top-0 z-50 border-b border-ivt-line font-invita transition-[background-color,box-shadow] duration-300"
        :class="scrolled
            ? 'bg-white/80 shadow-[0_10px_30px_-18px_rgba(26,20,51,0.25)] backdrop-blur-xl backdrop-saturate-150'
            : 'bg-white/95 shadow-[0_1px_2px_rgba(26,20,51,0.04)] backdrop-blur-md'"
    >
        <div class="mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">
            <div
                class="flex items-center justify-between gap-4 transition-[height] duration-300"
                :class="scrolled ? 'h-14 sm:h-16' : 'h-14 sm:h-16 lg:h-20'"
            >
                <div class="flex min-w-0 items-center gap-8">
                    <Link href="/" class="flex-none rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                        <Logo variant="invita" />
                    </Link>

                    <nav class="hidden items-center gap-1 rounded-full border border-ivt-line bg-ivt-paper-2/60 p-1 text-[14px] font-medium text-ivt-ink-soft lg:flex">
                        <template v-for="item in visibleNavLinks" :key="item.label">
                            <Link
                                v-if="item.current"
                                :href="item.href()"
                                class="rounded-full px-4 py-1.5 transition-all duration-200"
                                :class="item.current()
                                    ? 'bg-white text-ivt-ink shadow-sm shadow-ivt-ink/10 ring-1 ring-ivt-line'
                                    : 'hover:bg-white/70 hover:text-ivt-ink'"
                                :aria-current="item.current() ? 'page' : undefined"
                            >
                                {{ item.label }}
                            </Link>
                            <a
                                v-else
                                :href="item.href()"
                                class="rounded-full px-4 py-1.5 transition-all duration-200 hover:bg-white/70 hover:text-ivt-ink"
                            >{{ item.label }}</a>
                        </template>
                    </nav>
                </div>

                <div class="flex flex-none items-center gap-1.5 lg:gap-2">
                    <!-- Search: field-like pill on desktop, icon on mobile -->
                    <button
                        type="button"
                        @click="searchOpen = true"
                        class="group hidden h-10 w-56 items-center gap-2.5 rounded-full border border-ivt-line bg-white pl-3.5 pr-1.5 text-sm text-ivt-ink-faint transition-all duration-200 hover:border-primary/30 hover:shadow-sm xl:flex 2xl:w-72"
                    >
                        <MagnifyingGlassIcon class="h-[18px] w-[18px] text-ivt-ink-soft transition-colors group-hover:text-primary" />
                        <span class="flex-1 truncate text-left">Caută furnizori, servicii…</span>
                        <kbd class="rounded-full border border-ivt-line bg-ivt-paper-2 px-2 py-0.5 font-invita text-[11px] font-medium text-ivt-ink-soft">{{ isMac ? '⌘K' : 'Ctrl K' }}</kbd>
                    </button>
                    <button
                        type="button"
                        title="Caută"
                        aria-label="Caută"
                        @click="searchOpen = true"
                        class="icon-btn pressable flex xl:hidden"
                    >
                        <MagnifyingGlassIcon class="h-[22px] w-[22px]" />
                    </button>

                    <template v-if="user">
                        <Link :href="route('favorites.index')" title="Favorite" aria-label="Favorite" class="nav-favorites icon-btn hidden sm:flex">
                            <HeartIcon class="h-[22px] w-[22px]" />
                        </Link>
                        <Link
                            v-if="can.submitQuoteRequest"
                            :href="route('messages.index')"
                            title="Mesaje"
                            aria-label="Mesaje"
                            class="icon-btn relative hidden sm:flex"
                        >
                            <ChatBubbleLeftRightIcon class="h-[22px] w-[22px]" />
                            <span v-if="unreadMessages" class="badge">{{ badge(unreadMessages) }}</span>
                        </Link>
                        <span
                            v-else
                            title="În curând"
                            class="relative hidden h-10 w-10 cursor-not-allowed items-center justify-center rounded-full text-ivt-ink-soft/40 sm:flex"
                        >
                            <ChatBubbleLeftRightIcon class="h-[22px] w-[22px]" />
                            <LockClosedIcon class="absolute right-1.5 top-1.5 h-3 w-3 text-ivt-ink-soft/50" />
                        </span>
                        <Link :href="route('notifications.index')" title="Notificări" aria-label="Notificări" class="icon-btn pressable relative flex">
                            <BellIcon class="h-[22px] w-[22px]" />
                            <span v-if="unreadNotifications" class="badge">{{ badge(unreadNotifications) }}</span>
                        </Link>

                        <div class="relative ml-1 hidden lg:block">
                            <button
                                type="button"
                                title="Contul meu"
                                aria-haspopup="true"
                                :aria-expanded="accountMenuOpen"
                                @click="accountMenuOpen = !accountMenuOpen"
                                class="flex h-10 items-center gap-1.5 rounded-full border border-ivt-line bg-white py-1 pl-1 pr-2.5 transition-all duration-200 hover:border-primary/30 hover:shadow-sm"
                                :class="{ 'border-primary/40 shadow-sm': accountMenuOpen }"
                            >
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-ivt-ink-2 to-ivt-ink text-xs font-semibold text-ivt-on-dark">
                                    {{ initials(user.name) }}
                                </span>
                                <ChevronDownIcon class="h-4 w-4 text-ivt-ink-soft transition-transform duration-200" :class="{ 'rotate-180': accountMenuOpen }" />
                            </button>

                            <div v-if="accountMenuOpen" class="fixed inset-0 z-30" @click="closeAccountMenu" />

                            <transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0 -translate-y-1 scale-[0.97]"
                                enter-to-class="opacity-100 translate-y-0 scale-100"
                                leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100 scale-100"
                                leave-to-class="opacity-0 scale-[0.97]"
                            >
                                <div
                                    v-if="accountMenuOpen"
                                    class="absolute right-0 z-40 mt-3 w-80 origin-top-right overflow-hidden rounded-3xl border border-ivt-line bg-white/95 shadow-ivt-deep backdrop-blur-xl"
                                >
                                    <div class="flex items-center gap-3 bg-gradient-to-br from-ivt-paper-2 to-white px-4 py-4">
                                        <span class="flex h-12 w-12 flex-none items-center justify-center rounded-2xl bg-gradient-to-br from-ivt-ink-2 to-ivt-ink text-sm font-semibold text-ivt-on-dark shadow-sm shadow-ivt-ink/30">
                                            {{ initials(user.name) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-ivt-ink">{{ user.name }}</p>
                                            <p class="truncate text-xs text-ivt-ink-soft">{{ user.email }}</p>
                                        </div>
                                    </div>

                                    <nav class="flex flex-col gap-0.5 p-2">
                                        <Link :href="route('profile.show')" class="menu-item group" @click="closeAccountMenu">
                                            <span class="menu-icon"><UserCircleIcon class="h-5 w-5" /></span>
                                            <span class="flex-1">Profil</span>
                                            <ChevronRightIcon class="menu-chevron" />
                                        </Link>
                                        <Link v-if="can.submitQuoteRequest" :href="route('quote-requests.index')" class="menu-item group" @click="closeAccountMenu">
                                            <span class="menu-icon"><ClipboardDocumentListIcon class="h-5 w-5" /></span>
                                            <span class="flex-1">Cererile mele</span>
                                            <ChevronRightIcon class="menu-chevron" />
                                        </Link>
                                        <Link :href="route('favorites.index')" class="menu-item group" @click="closeAccountMenu">
                                            <span class="menu-icon"><HeartIcon class="h-5 w-5" /></span>
                                            <span class="flex-1">Favorite</span>
                                            <ChevronRightIcon class="menu-chevron" />
                                        </Link>
                                        <Link v-if="can.submitQuoteRequest" :href="route('messages.index')" class="menu-item group" @click="closeAccountMenu">
                                            <span class="menu-icon"><ChatBubbleLeftRightIcon class="h-5 w-5" /></span>
                                            <span class="flex-1">Mesajele mele</span>
                                            <span v-if="unreadMessages" class="flex h-5 min-w-5 items-center justify-center rounded-full bg-brand px-1.5 text-[11px] font-bold tabular-nums text-white">{{ badge(unreadMessages) }}</span>
                                            <ChevronRightIcon class="menu-chevron" />
                                        </Link>
                                        <Link :href="route('notifications.index')" class="menu-item group" @click="closeAccountMenu">
                                            <span class="menu-icon"><BellIcon class="h-5 w-5" /></span>
                                            <span class="flex-1">Notificări</span>
                                            <span v-if="unreadNotifications" class="flex h-5 min-w-5 items-center justify-center rounded-full bg-brand px-1.5 text-[11px] font-bold tabular-nums text-white">{{ badge(unreadNotifications) }}</span>
                                            <ChevronRightIcon class="menu-chevron" />
                                        </Link>
                                        <Link v-if="can.saveSearch" :href="route('saved-searches.index')" class="menu-item group" @click="closeAccountMenu">
                                            <span class="menu-icon"><BookmarkIcon class="h-5 w-5" /></span>
                                            <span class="flex-1">Căutări salvate</span>
                                            <ChevronRightIcon class="menu-chevron" />
                                        </Link>
                                        <PushNotificationToggle />
                                    </nav>

                                    <div class="border-t border-ivt-line p-2">
                                        <button type="button" @click="logout" class="group flex w-full items-center gap-3 rounded-2xl px-2.5 py-2 text-sm font-medium text-primary transition-colors duration-150 hover:bg-primary/10">
                                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-primary/10 text-primary transition-colors duration-150 group-hover:bg-primary/15">
                                                <ArrowRightStartOnRectangleIcon class="h-5 w-5" />
                                            </span>
                                            Deconectare
                                        </button>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </template>

                    <Link
                        v-if="!user"
                        href="/login"
                        class="pressable rounded-full bg-ivt-paper-2 px-4 py-2 text-[13px] font-semibold text-ivt-ink transition-colors duration-150 hover:bg-ivt-paper-3 hover:text-primary lg:bg-transparent lg:text-sm lg:hover:bg-ivt-paper-2"
                    >
                        <span class="lg:hidden">Intră</span>
                        <span class="hidden lg:inline">Autentificare</span>
                    </Link>

                    <Link
                        :href="postAdHref"
                        class="ml-1 hidden items-center gap-1.5 rounded-full bg-gradient-to-b from-ivt-ink-2 to-ivt-ink py-2.5 pl-3.5 pr-5 text-sm font-semibold text-ivt-paper shadow-[0_8px_20px_-10px_rgba(26,20,51,0.6)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(26,20,51,0.5)] lg:inline-flex"
                    >
                        <PlusIcon class="h-4 w-4 stroke-[2.5]" />
                        Postează anunț
                    </Link>

                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <TransitionRoot as="template" :show="mobileMenuOpen">
            <Dialog as="div" class="relative z-50 lg:hidden" @close="mobileMenuOpen = false">
                <TransitionChild
                    as="template"
                    enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
                    leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-ivt-ink/30 backdrop-blur-sm" />
                </TransitionChild>

                <div class="fixed inset-0 flex items-end justify-center">
                    <TransitionChild
                        as="template"
                        enter="ease-[cubic-bezier(0.22,1,0.36,1)] duration-[400ms]" enter-from="translate-y-full" enter-to="translate-y-0"
                        leave="ease-in duration-200" leave-from="translate-y-0" leave-to="translate-y-full"
                    >
                        <DialogPanel
                            class="flex max-h-[92dvh] w-full max-w-lg flex-col overflow-hidden rounded-t-[28px] bg-white font-invita shadow-ivt-deep"
                            :class="sheetDrag ? '' : 'transition-transform duration-300 ease-out'"
                            :style="sheetDrag ? { transform: `translateY(${sheetDrag}px)` } : undefined"
                        >
                            <div
                                class="flex-none cursor-grab touch-none pt-2.5"
                                @touchstart.passive="onSheetTouchStart"
                                @touchmove.passive="onSheetTouchMove"
                                @touchend="onSheetTouchEnd"
                                @touchcancel="onSheetTouchEnd"
                            >
                                <div class="mx-auto h-1.5 w-10 rounded-full bg-ivt-paper-3" />
                            </div>
                            <div class="flex flex-none items-center justify-between px-5 pb-3 pt-2">
                                <Logo variant="invita" />
                                <button
                                    type="button"
                                    aria-label="Închide meniul"
                                    @click="mobileMenuOpen = false"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-ivt-paper-2 text-ivt-ink-soft transition-colors hover:bg-ivt-paper-3 hover:text-ivt-ink"
                                >
                                    <XMarkIcon class="h-5 w-5" />
                                </button>
                            </div>

                            <div class="flex min-h-0 flex-1 flex-col gap-5 overflow-y-auto overscroll-contain px-4 pb-4">
                                <!-- Account card -->
                                <div v-if="user" class="rounded-3xl bg-gradient-to-br from-ivt-ink-2 to-ivt-ink p-4 text-ivt-on-dark">
                                    <Link :href="route('profile.show')" class="flex items-center gap-3">
                                        <span class="flex h-12 w-12 flex-none items-center justify-center rounded-2xl bg-white/10 text-sm font-semibold ring-1 ring-white/15">
                                            {{ initials(user.name) }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-white">{{ user.name }}</p>
                                            <p class="truncate text-xs text-ivt-on-dark-dim">{{ user.email }}</p>
                                        </div>
                                        <ChevronRightIcon class="h-4 w-4 text-ivt-on-dark-dim" />
                                    </Link>

                                    <div class="mt-4 grid grid-cols-3 gap-2">
                                        <Link :href="route('favorites.index')" class="quick-action">
                                            <HeartIcon class="h-5 w-5" />
                                            Favorite
                                        </Link>
                                        <Link v-if="can.submitQuoteRequest" :href="route('messages.index')" class="quick-action relative">
                                            <ChatBubbleLeftRightIcon class="h-5 w-5" />
                                            Mesaje
                                            <span v-if="unreadMessages" class="badge !ring-ivt-ink">{{ badge(unreadMessages) }}</span>
                                        </Link>
                                        <span v-else class="quick-action relative cursor-not-allowed opacity-50" title="În curând">
                                            <ChatBubbleLeftRightIcon class="h-5 w-5" />
                                            Mesaje
                                            <LockClosedIcon class="absolute right-2 top-2 h-3 w-3" />
                                        </span>
                                        <Link :href="route('notifications.index')" class="quick-action relative">
                                            <BellIcon class="h-5 w-5" />
                                            Notificări
                                            <span v-if="unreadNotifications" class="badge !ring-ivt-ink">{{ badge(unreadNotifications) }}</span>
                                        </Link>
                                    </div>
                                </div>

                                <!-- Main navigation -->
                                <div>
                                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-ivt-ink-faint">Explorează</p>
                                    <nav class="flex flex-col gap-0.5 text-[15px] font-medium text-ivt-ink">
                                        <template v-for="item in visibleNavLinks" :key="item.label">
                                            <component
                                                :is="item.current ? Link : 'a'"
                                                :href="item.href()"
                                                @click="mobileMenuOpen = false"
                                                class="group flex items-center gap-3 rounded-2xl px-3 py-2.5 transition-colors duration-150"
                                                :class="item.current?.() ? 'bg-ivt-paper-2 text-primary' : 'hover:bg-ivt-paper-2'"
                                            >
                                                <span
                                                    class="flex h-9 w-9 flex-none items-center justify-center rounded-xl transition-colors"
                                                    :class="item.current?.() ? 'bg-white text-primary shadow-sm' : 'bg-ivt-paper-2 text-ivt-ink-soft group-hover:bg-white'"
                                                >
                                                    <component :is="item.icon" class="h-5 w-5" />
                                                </span>
                                                <span class="flex-1">{{ item.label }}</span>
                                                <ChevronRightIcon class="h-4 w-4 text-ivt-ink-faint" />
                                            </component>
                                        </template>
                                    </nav>
                                </div>

                                <!-- Account links -->
                                <div v-if="user">
                                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-ivt-ink-faint">Contul meu</p>
                                    <nav class="flex flex-col gap-0.5">
                                        <Link v-if="can.submitQuoteRequest" :href="route('quote-requests.index')" class="menu-item group">
                                            <span class="menu-icon"><ClipboardDocumentListIcon class="h-5 w-5" /></span>
                                            <span class="flex-1">Cererile mele</span>
                                            <ChevronRightIcon class="menu-chevron" />
                                        </Link>
                                        <Link v-if="can.saveSearch" :href="route('saved-searches.index')" class="menu-item group">
                                            <span class="menu-icon"><BookmarkIcon class="h-5 w-5" /></span>
                                            <span class="flex-1">Căutări salvate</span>
                                            <ChevronRightIcon class="menu-chevron" />
                                        </Link>
                                        <PushNotificationToggle />
                                        <button type="button" @click="logout" class="group mt-1 flex w-full items-center gap-3 rounded-2xl px-2.5 py-2 text-left text-sm font-medium text-primary transition-colors duration-150 hover:bg-primary/10">
                                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-primary/10">
                                                <ArrowRightStartOnRectangleIcon class="h-5 w-5" />
                                            </span>
                                            Deconectare
                                        </button>
                                    </nav>
                                </div>
                            </div>

                            <div class="flex flex-none flex-col gap-2 border-t border-ivt-line p-4" style="padding-bottom: max(1rem, env(safe-area-inset-bottom))">
                                <Link
                                    :href="postAdHref"
                                    @click="mobileMenuOpen = false"
                                    class="flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-b from-ivt-ink-2 to-ivt-ink px-4 py-3 text-sm font-semibold text-ivt-paper shadow-[0_8px_20px_-10px_rgba(26,20,51,0.6)]"
                                >
                                    <PlusIcon class="h-4 w-4 stroke-[2.5]" />
                                    Postează anunț
                                </Link>
                                <Link
                                    v-if="!user"
                                    href="/login"
                                    @click="mobileMenuOpen = false"
                                    class="block w-full rounded-full border border-ivt-line px-4 py-3 text-center text-sm font-semibold text-ivt-ink transition-colors hover:border-ivt-violet hover:text-primary"
                                >
                                    Autentificare
                                </Link>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>

        <GlobalSearch v-model:show="searchOpen" />
    </header>
</template>

<style scoped>
.icon-btn {
    @apply h-10 w-10 items-center justify-center rounded-full text-ivt-ink-soft transition-colors duration-150 hover:bg-ivt-paper-2 hover:text-primary;
}
.badge {
    @apply absolute right-0.5 top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold leading-none text-white ring-2 ring-white;
}
.menu-item {
    @apply flex items-center gap-3 rounded-2xl px-2.5 py-2 text-sm font-medium text-ivt-ink-soft transition-colors duration-150 hover:bg-ivt-paper-2 hover:text-ivt-ink;
}
.menu-icon {
    @apply flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-ivt-paper-2 text-ivt-ink-soft transition-colors duration-150 group-hover:bg-white group-hover:text-primary;
}
.menu-chevron {
    @apply h-4 w-4 -translate-x-1 text-ivt-ink-faint opacity-0 transition-all duration-150 group-hover:translate-x-0 group-hover:opacity-100;
}
.quick-action {
    @apply flex flex-col items-center gap-1.5 rounded-2xl bg-white/[0.07] px-2 py-3 text-xs font-medium text-ivt-on-dark ring-1 ring-white/10 transition-colors hover:bg-white/[0.12];
}
</style>
