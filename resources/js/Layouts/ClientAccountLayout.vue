<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronRightIcon,
    HomeIcon,
    ClipboardDocumentListIcon,
    HeartIcon,
    ChatBubbleLeftRightIcon,
    BellIcon,
    Cog6ToothIcon,
    ArrowRightStartOnRectangleIcon,
    LockClosedIcon,
} from '@heroicons/vue/24/outline';
import ClientLayout from '@/Layouts/ClientLayout.vue';

const props = defineProps({
    title: { type: String, required: true },
    active: { type: String, default: '' },
    // Set to false for pages (e.g. messages) that should use the full width without the account menu.
    sidebar: { type: Boolean, default: true },
    // Set to false to drop the breadcrumb/hero banner above the content.
    hero: { type: Boolean, default: true },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const unreadMessages = computed(() => page.props.unreadMessages ?? 0);

const initials = (name) => (name || '')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

// Items without an `href` don't have a page built for them yet — they're
// shown for layout fidelity but intentionally do nothing when clicked.
const navItems = [
    { key: 'dashboard', label: 'Panou control', icon: HomeIcon, href: route('home') },
    { key: 'requests', label: 'Cererile mele', icon: ClipboardDocumentListIcon, href: route('quote-requests.index') },
    { key: 'favorites', label: 'Favorite', icon: HeartIcon, href: route('favorites.index') },
    { key: 'messages', label: 'Mesajele mele', icon: ChatBubbleLeftRightIcon, href: route('messages.index') },
    { key: 'notifications', label: 'Notificări', icon: BellIcon, href: route('notifications.index') },
    { key: 'settings', label: 'Setări cont', icon: Cog6ToothIcon, href: route('profile.show') },
];

const logout = () => router.post(route('logout'));
</script>

<template>
    <ClientLayout :title="title" full-bleed>
        <div v-if="hero" class="relative overflow-hidden bg-gradient-to-br from-primary/5 via-white to-primary/5 py-7 sm:py-9">
            <div class="pointer-events-none absolute -right-12 -top-16 h-64 w-64 rounded-full bg-primary/10 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-24 left-1/4 h-64 w-64 rounded-full bg-primary/10 blur-3xl" />

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <nav class="mb-5 flex items-center gap-1.5 text-sm text-ink-soft">
                    <Link href="/" class="transition-colors hover:text-primary-bright">Acasă</Link>
                    <ChevronRightIcon class="h-3.5 w-3.5 text-ink-soft/50" />
                    <span>Contul meu</span>
                    <ChevronRightIcon class="h-3.5 w-3.5 text-ink-soft/50" />
                    <span class="font-medium text-ink">{{ title }}</span>
                </nav>

                <slot name="hero" />
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <div v-if="$slots.stats" class="mb-6">
                <slot name="stats" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <aside v-if="sidebar" class="lg:col-span-3">
                    <div class="space-y-4 lg:sticky lg:top-24">
                        <div class="overflow-hidden rounded-2xl border border-line bg-white shadow-[0_2px_12px_rgba(33,28,39,0.08)]">
                            <div class="h-14 bg-gradient-to-r from-primary to-primary-bright" />
                            <div class="-mt-8 px-5 pb-5">
                                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary-bright text-lg font-semibold text-white shadow-md shadow-primary/30 ring-4 ring-white">
                                    {{ initials(user?.name) }}
                                </span>
                                <p class="mt-3 truncate text-sm font-semibold text-ink">{{ user?.name }}</p>
                                <p class="truncate text-xs text-ink-soft">{{ user?.email }}</p>
                                <span class="mt-2.5 inline-flex cursor-not-allowed items-center gap-1 text-xs font-semibold text-primary-bright/70" title="În curând">
                                    Editează profil
                                    <LockClosedIcon class="h-3 w-3" />
                                </span>
                            </div>
                        </div>

                        <nav class="rounded-2xl border border-line bg-white p-2 shadow-[0_2px_12px_rgba(33,28,39,0.08)]">
                            <div class="space-y-1">
                                <template v-for="item in navItems" :key="item.key">
                                    <Link
                                        v-if="item.href"
                                        :href="item.href"
                                        class="group flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition-all duration-200 ease-out"
                                        :class="active === item.key
                                            ? 'bg-primary text-white shadow-md shadow-primary/25'
                                            : 'text-ink-soft hover:-translate-y-px hover:bg-primary/5 hover:text-primary-bright hover:shadow-[0_4px_12px_rgba(33,28,39,0.10)]'"
                                    >
                                        <span
                                            class="flex h-8 w-8 flex-none items-center justify-center rounded-xl transition-all duration-200 ease-out group-hover:scale-110"
                                            :class="active === item.key ? 'bg-white/15' : 'bg-paper group-hover:bg-white group-hover:shadow-[0_2px_6px_rgba(33,28,39,0.12)]'"
                                        >
                                            <component :is="item.icon" class="h-4 w-4" />
                                        </span>
                                        {{ item.label }}
                                        <span
                                            v-if="item.key === 'messages' && unreadMessages"
                                            class="ml-auto flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1.5 text-[11px] font-semibold"
                                            :class="active === item.key ? 'bg-white text-primary-bright' : 'bg-primary text-white'"
                                        >{{ unreadMessages > 99 ? '99+' : unreadMessages }}</span>
                                    </Link>
                                    <span
                                        v-else
                                        class="flex cursor-not-allowed items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium text-ink-soft/40"
                                        title="În curând"
                                    >
                                        <span class="flex h-8 w-8 flex-none items-center justify-center rounded-xl bg-paper/60">
                                            <component :is="item.icon" class="h-4 w-4" />
                                        </span>
                                        {{ item.label }}
                                        <LockClosedIcon class="ml-auto h-3.5 w-3.5" />
                                    </span>
                                </template>
                            </div>

                            <div class="my-2 h-px bg-line" />

                            <button
                                type="button"
                                @click="logout"
                                class="group flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium text-ink-soft transition-all duration-200 ease-out hover:-translate-y-px hover:bg-primary/5 hover:text-primary hover:shadow-[0_4px_12px_rgba(33,28,39,0.10)]"
                            >
                                <span class="flex h-8 w-8 flex-none items-center justify-center rounded-xl bg-paper transition-all duration-200 ease-out group-hover:scale-110 group-hover:bg-white group-hover:shadow-[0_2px_6px_rgba(33,28,39,0.12)]">
                                    <ArrowRightStartOnRectangleIcon class="h-4 w-4" />
                                </span>
                                Deconectare
                            </button>
                        </nav>
                    </div>
                </aside>

                <div :class="sidebar ? 'lg:col-span-9' : 'lg:col-span-12'">
                    <div class="grid grid-cols-1 gap-6" :class="$slots.aside ? 'xl:grid-cols-3' : ''">
                        <div :class="$slots.aside ? 'xl:col-span-2' : ''">
                            <slot />
                        </div>
                        <div v-if="$slots.aside" class="space-y-4">
                            <slot name="aside" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>
