<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    HomeIcon,
    Squares2X2Icon,
    HeartIcon,
    PlusIcon,
    UserIcon,
    UserCircleIcon,
    ArrowRightStartOnRectangleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import {
    HomeIcon as HomeIconSolid,
    Squares2X2Icon as Squares2X2IconSolid,
    HeartIcon as HeartIconSolid,
} from '@heroicons/vue/24/solid';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';

defineProps({
    title: {
        type: String,
        default: 'Contul meu',
    },
    // Skip the centered max-w-7xl/padding wrapper so the page's own
    // sections (e.g. the homepage body, with its full-bleed footer) can
    // manage their own width and background edge-to-edge.
    fullBleed: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const can = computed(() => page.props.auth.can);
const user = computed(() => page.props.auth.user);
const accountSheetOpen = ref(false);

const primaryNav = computed(() => [
    { label: 'Acasă', href: route('home'), current: () => route().current('home'), icon: HomeIcon, iconActive: HomeIconSolid },
    { label: 'Anunțuri', href: route('listings.index'), current: () => route().current('listings.*'), icon: Squares2X2Icon, iconActive: Squares2X2IconSolid },
    { label: 'Favorite', href: route('favorites.index'), current: () => route().current('favorites.*'), icon: HeartIcon, iconActive: HeartIconSolid },
]);

const initials = (name) => (name || '')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

const logout = () => router.post(route('logout'));
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-paper text-ink">
        <SiteHeader />

        <!-- Page heading -->
        <header v-if="$slots.header" class="border-b border-line bg-white/70 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page content -->
        <main :class="fullBleed ? '' : 'mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8'">
            <slot />
        </main>

        <SiteFooter />

        <!-- Mobile bottom app nav -->
        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-white/90 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl lg:hidden">
            <div class="mx-auto flex max-w-md items-center justify-between px-3 pt-2">
                <Link
                    v-for="item in primaryNav.slice(0, 2)"
                    :key="item.label"
                    :href="item.href"
                    class="flex flex-1 flex-col items-center gap-1 rounded-xl py-1.5 text-[11px] font-medium transition-colors duration-150"
                    :class="item.current() ? 'text-brand-600' : 'text-ink-soft'"
                >
                    <component :is="item.current() ? item.iconActive : item.icon" class="h-6 w-6" />
                    {{ item.label }}
                </Link>

                <Link
                    v-if="can.submitQuoteRequest"
                    :href="route('quote-requests.create')"
                    class="relative -top-5 flex flex-1 flex-col items-center"
                >
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-gold-400 text-white shadow-glow-brand ring-4 ring-paper transition-transform duration-150 active:scale-95">
                        <PlusIcon class="h-6 w-6" />
                    </span>
                </Link>

                <Link
                    v-for="item in primaryNav.slice(2)"
                    :key="item.label"
                    :href="item.href"
                    class="flex flex-1 flex-col items-center gap-1 rounded-xl py-1.5 text-[11px] font-medium transition-colors duration-150"
                    :class="item.current() ? 'text-brand-600' : 'text-ink-soft'"
                >
                    <component :is="item.current() ? item.iconActive : item.icon" class="h-6 w-6" />
                    {{ item.label }}
                </Link>

                <button
                    type="button"
                    @click="accountSheetOpen = true"
                    class="flex flex-1 flex-col items-center gap-1 rounded-xl py-1.5 text-[11px] font-medium transition-colors duration-150"
                    :class="accountSheetOpen || route().current('profile.*') ? 'text-brand-600' : 'text-ink-soft'"
                >
                    <UserIcon class="h-6 w-6" />
                    Cont
                </button>
            </div>
        </nav>

        <!-- Mobile account sheet -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="accountSheetOpen" class="fixed inset-0 z-50 lg:hidden" @click="accountSheetOpen = false">
                <div class="absolute inset-0 bg-ink/40" />
            </div>
        </transition>
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="translate-y-full"
            enter-to-class="translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="translate-y-0"
            leave-to-class="translate-y-full"
        >
            <div
                v-if="accountSheetOpen"
                class="fixed inset-x-0 bottom-0 z-50 rounded-t-3xl bg-white p-5 pb-[calc(env(safe-area-inset-bottom)+1.25rem)] shadow-[0_-16px_40px_-16px_rgba(33,28,39,0.25)] lg:hidden"
            >
                <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-line" />

                <template v-if="!user">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-ink">Contul tău</p>
                        <button type="button" @click="accountSheetOpen = false" class="p-1 text-ink-soft">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-ink-soft">Autentifică-te pentru cereri de ofertă și favorite.</p>
                    <div class="mt-4 flex gap-2">
                        <Link href="/login" class="flex-1 rounded-xl bg-brand-500 px-4 py-2.5 text-center text-sm font-semibold text-white" @click="accountSheetOpen = false">Autentificare</Link>
                        <Link href="/register/client" class="flex-1 rounded-xl border border-line px-4 py-2.5 text-center text-sm font-semibold text-ink" @click="accountSheetOpen = false">Cont nou</Link>
                    </div>
                </template>
                <template v-else>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-600 text-sm font-semibold text-white shadow-sm shadow-brand-500/20">
                                {{ initials(user.name) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-ink">{{ user.name }}</p>
                                <p class="truncate text-xs text-ink-soft">{{ user.email }}</p>
                            </div>
                        </div>
                        <button type="button" @click="accountSheetOpen = false" class="p-1 text-ink-soft">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="mt-5 space-y-1">
                        <Link
                            :href="route('profile.show')"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-ink transition hover:bg-paper"
                            @click="accountSheetOpen = false"
                        >
                            <UserCircleIcon class="h-5 w-5 text-ink-soft" /> Profil
                        </Link>
                        <button
                            type="button"
                            @click="logout"
                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-rose-600 transition hover:bg-rose-50"
                        >
                            <ArrowRightStartOnRectangleIcon class="h-5 w-5" /> Deconectare
                        </button>
                    </div>
                </template>
            </div>
        </transition>
    </div>
</template>
