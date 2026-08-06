<script setup>
import { computed, ref } from 'vue';
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
    Bars3Icon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import Logo from '@/Components/Logo.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const can = computed(() => page.props.auth.can);

const accountMenuOpen = ref(false);
const mobileMenuOpen = ref(false);

const closeAccountMenu = () => {
    accountMenuOpen.value = false;
};

const navLinks = [
    { label: 'Categorii', href: () => route('categories.index'), current: () => route().current('categories.*') },
    { label: 'Furnizori', href: () => route('providers.index'), current: () => route().current('providers.*') },
    { label: 'Cereri de ofertă', href: () => '/#cereri' },
    { label: 'Abonamente', href: () => '/#abonamente' },
];

const postAdHref = computed(() => (can.value.submitQuoteRequest ? route('quote-requests.create') : '/register/client'));

const initials = (name) => (name || '')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

const logout = () => router.post(route('logout'));
</script>

<template>
    <header class="sticky top-0 z-50 border-b border-ivt-line bg-white/88 font-invita backdrop-blur-md">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="flex h-20 items-center justify-between py-3">
                <Link href="/">
                    <Logo variant="invita" />
                </Link>

                <nav class="hidden lg:flex items-center gap-8 text-[14.5px] font-medium text-ivt-ink-soft">
                    <template v-for="item in navLinks" :key="item.label">
                        <Link
                            v-if="item.current"
                            :href="item.href()"
                            :class="item.current() ? 'text-ivt-ink' : 'transition-colors duration-150 hover:text-ivt-ink'"
                        >
                            {{ item.label }}
                        </Link>
                        <a v-else :href="item.href()" class="transition-colors duration-150 hover:text-ivt-ink">{{ item.label }}</a>
                    </template>
                </nav>

                <div class="flex items-center gap-1 lg:gap-3">
                    <div v-if="user" class="flex items-center gap-1">
                        <Link
                            :href="route('favorites.index')"
                            title="Favorite"
                            class="nav-favorites flex h-10 w-10 items-center justify-center rounded-xl text-ivt-ink-soft transition-colors duration-150 hover:bg-ivt-paper-2 hover:text-ivt-wine"
                        >
                            <HeartIcon class="h-6 w-6" />
                        </Link>
                        <span
                            title="În curând"
                            class="relative flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-xl text-ivt-ink-soft/40"
                        >
                            <ChatBubbleLeftRightIcon class="h-6 w-6" />
                            <LockClosedIcon class="absolute right-1.5 top-1.5 h-3 w-3 text-ivt-ink-soft/50" />
                        </span>
                        <span
                            title="În curând"
                            class="relative flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-xl text-ivt-ink-soft/40"
                        >
                            <BellIcon class="h-6 w-6" />
                            <LockClosedIcon class="absolute right-1.5 top-1.5 h-3 w-3 text-ivt-ink-soft/50" />
                        </span>
                        <div class="relative hidden lg:block">
                            <button
                                type="button"
                                title="Contul meu"
                                aria-haspopup="true"
                                :aria-expanded="accountMenuOpen"
                                @click="accountMenuOpen = !accountMenuOpen"
                                @keyup.esc="closeAccountMenu"
                                class="flex h-10 w-10 items-center justify-center rounded-xl text-ivt-ink-soft transition-colors duration-150 hover:bg-ivt-paper-2 hover:text-ivt-wine"
                            >
                                <UserCircleIcon class="h-6 w-6" />
                            </button>

                            <div v-if="accountMenuOpen" class="fixed inset-0 z-30" @click="closeAccountMenu" @keyup.esc="closeAccountMenu" />

                            <transition
                                enter-active-class="transition ease-out duration-150"
                                enter-from-class="opacity-0 -translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                            >
                                <div v-if="accountMenuOpen" @keyup.esc="closeAccountMenu" class="absolute right-0 z-40 mt-2.5 w-72 rounded-2xl border border-ivt-line bg-white p-2 shadow-xl shadow-ivt-ink/10 ring-1 ring-black/[0.03]">
                                    <div class="absolute -top-1.5 right-3 h-3 w-3 rotate-45 border-l border-t border-ivt-line bg-white" />

                                    <div class="flex items-center gap-3 rounded-xl px-2.5 py-3">
                                        <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-gradient-to-br from-ivt-ink-2 to-ivt-ink text-sm font-semibold text-ivt-on-dark shadow-sm shadow-ivt-ink/30">
                                            {{ initials(user.name) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-ivt-ink">{{ user.name }}</p>
                                            <p class="truncate text-xs text-ivt-ink-soft">{{ user.email }}</p>
                                        </div>
                                    </div>

                                    <div class="my-1 h-px bg-ivt-line" />

                                    <nav class="flex flex-col gap-0.5">
                                        <Link :href="route('profile.show')" class="group flex items-center gap-3 rounded-xl px-2.5 py-2 text-sm font-medium text-ivt-ink-soft transition-colors duration-150 hover:bg-ivt-paper-2 hover:text-ivt-ink" @click="accountMenuOpen = false">
                                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-ivt-paper-2 text-ivt-ink-soft transition-colors duration-150 group-hover:bg-white group-hover:text-ivt-wine">
                                                <UserCircleIcon class="h-5 w-5" />
                                            </span>
                                            Profil
                                        </Link>
                                        <Link v-if="can.submitQuoteRequest" :href="route('quote-requests.index')" class="group flex items-center gap-3 rounded-xl px-2.5 py-2 text-sm font-medium text-ivt-ink-soft transition-colors duration-150 hover:bg-ivt-paper-2 hover:text-ivt-ink" @click="accountMenuOpen = false">
                                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-ivt-paper-2 text-ivt-ink-soft transition-colors duration-150 group-hover:bg-white group-hover:text-ivt-wine">
                                                <ClipboardDocumentListIcon class="h-5 w-5" />
                                            </span>
                                            Cererile mele
                                        </Link>
                                    </nav>

                                    <div class="my-1 h-px bg-ivt-line" />

                                    <button type="button" @click="logout" class="group flex w-full items-center gap-3 rounded-xl px-2.5 py-2 text-sm font-medium text-ivt-wine transition-colors duration-150 hover:bg-ivt-wine/10">
                                        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-ivt-wine/10 text-ivt-wine transition-colors duration-150 group-hover:bg-ivt-wine/15">
                                            <ArrowRightStartOnRectangleIcon class="h-5 w-5" />
                                        </span>
                                        Deconectare
                                    </button>
                                </div>
                            </transition>
                        </div>
                    </div>

                    <Link
                        :href="postAdHref"
                        class="hidden rounded-full bg-gradient-to-b from-ivt-ink-2 to-ivt-ink px-5 py-2.5 text-sm font-semibold text-ivt-paper transition-transform duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(22,40,31,0.4)] lg:inline-block"
                    >
                        Postează anunț
                    </Link>

                    <Link
                        v-if="!user"
                        href="/login"
                        class="hidden rounded-full border border-ivt-line px-5 py-2 text-sm font-semibold text-ivt-ink transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine lg:inline-block"
                    >
                        Autentificare
                    </Link>

                    <button
                        type="button"
                        title="Meniu"
                        aria-haspopup="true"
                        :aria-expanded="mobileMenuOpen"
                        @click="mobileMenuOpen = true"
                        class="flex h-10 w-10 items-center justify-center rounded-xl text-ivt-ink-soft transition-colors duration-150 hover:bg-ivt-paper-2 hover:text-ivt-wine lg:hidden"
                    >
                        <Bars3Icon class="h-6 w-6" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <TransitionRoot as="template" :show="mobileMenuOpen">
            <Dialog as="div" class="relative z-50 lg:hidden" @close="mobileMenuOpen = false">
                <TransitionChild
                    as="template"
                    enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100"
                    leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-ivt-ink/40" />
                </TransitionChild>

                <div class="fixed inset-0 flex justify-end">
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-200" enter-from="translate-x-full" enter-to="translate-x-0"
                        leave="ease-in duration-150" leave-from="translate-x-0" leave-to="translate-x-full"
                    >
                        <DialogPanel class="flex h-full w-full max-w-xs flex-col overflow-y-auto bg-white font-invita shadow-xl shadow-ivt-ink/10">
                            <div class="flex flex-none items-center justify-between border-b border-ivt-line px-5 py-4">
                                <Logo variant="invita" />
                                <button type="button" @click="mobileMenuOpen = false" class="text-ivt-ink-soft hover:text-ivt-ink">
                                    <XMarkIcon class="h-5 w-5" />
                                </button>
                            </div>

                            <nav class="flex flex-1 flex-col gap-1 px-3 py-4 text-sm font-medium text-ivt-ink">
                                <template v-for="item in navLinks" :key="item.label">
                                    <Link
                                        v-if="item.current"
                                        :href="item.href()"
                                        @click="mobileMenuOpen = false"
                                        class="rounded-xl px-3 py-2.5 transition-colors duration-150"
                                        :class="item.current() ? 'bg-ivt-paper-2 text-ivt-wine' : 'hover:bg-ivt-paper-2'"
                                    >
                                        {{ item.label }}
                                    </Link>
                                    <a v-else :href="item.href()" @click="mobileMenuOpen = false" class="rounded-xl px-3 py-2.5 transition-colors duration-150 hover:bg-ivt-paper-2">
                                        {{ item.label }}
                                    </a>
                                </template>

                                <div class="my-2 h-px bg-ivt-line" />

                                <template v-if="user">
                                    <Link :href="route('profile.show')" @click="mobileMenuOpen = false" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors duration-150 hover:bg-ivt-paper-2">
                                        <UserCircleIcon class="h-5 w-5 text-ivt-ink-soft" />
                                        Profil
                                    </Link>
                                    <Link v-if="can.submitQuoteRequest" :href="route('quote-requests.index')" @click="mobileMenuOpen = false" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors duration-150 hover:bg-ivt-paper-2">
                                        <ClipboardDocumentListIcon class="h-5 w-5 text-ivt-ink-soft" />
                                        Cererile mele
                                    </Link>
                                    <Link :href="route('favorites.index')" @click="mobileMenuOpen = false" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors duration-150 hover:bg-ivt-paper-2">
                                        <HeartIcon class="h-5 w-5 text-ivt-ink-soft" />
                                        Favorite
                                    </Link>
                                    <button type="button" @click="logout" class="mt-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-left text-ivt-wine transition-colors duration-150 hover:bg-ivt-wine/10">
                                        <ArrowRightStartOnRectangleIcon class="h-5 w-5" />
                                        Deconectare
                                    </button>
                                </template>
                                <Link v-else href="/login" @click="mobileMenuOpen = false" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors duration-150 hover:bg-ivt-paper-2">
                                    <UserCircleIcon class="h-5 w-5 text-ivt-ink-soft" />
                                    Autentificare
                                </Link>
                            </nav>

                            <div class="flex-none border-t border-ivt-line p-4" style="padding-bottom: max(1rem, env(safe-area-inset-bottom))">
                                <Link
                                    :href="postAdHref"
                                    @click="mobileMenuOpen = false"
                                    class="block w-full rounded-full bg-gradient-to-b from-ivt-ink-2 to-ivt-ink px-4 py-3 text-center text-sm font-semibold text-ivt-paper transition-colors"
                                >
                                    Postează anunț
                                </Link>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>
    </header>
</template>
