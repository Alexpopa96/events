<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    HomeIcon,
    Squares2X2Icon,
    ChatBubbleLeftRightIcon,
    HeartIcon,
    Bars3Icon,
    PlusIcon,
} from '@heroicons/vue/24/outline';
import {
    HomeIcon as HomeIconSolid,
    Squares2X2Icon as Squares2X2IconSolid,
    ChatBubbleLeftRightIcon as ChatBubbleLeftRightIconSolid,
    HeartIcon as HeartIconSolid,
} from '@heroicons/vue/24/solid';
import { useMobileMenu } from '@/Composables/useMobileMenu';

const page = usePage();
const user = computed(() => page.props.auth.user);
const can = computed(() => page.props.auth.can ?? {});
const isProvider = computed(() => !!page.props.auth.isProvider);
const unreadMessages = computed(() => page.props.unreadMessages ?? 0);
const unreadNotifications = computed(() => page.props.unreadNotifications ?? 0);

const { open: menuOpen } = useMobileMenu();

const tabs = computed(() => {
    const items = [
        {
            label: 'Acasă',
            href: route('home'),
            current: route().current('home'),
            icon: HomeIcon,
            iconActive: HomeIconSolid,
        },
        {
            label: 'Explorează',
            href: route('categories.index'),
            current: ['categories.*', 'listings.*', 'providers.*'].some((name) => route().current(name)),
            icon: Squares2X2Icon,
            iconActive: Squares2X2IconSolid,
        },
    ];

    items.push(can.value.submitQuoteRequest
        ? {
            label: 'Mesaje',
            href: route('messages.index'),
            current: route().current('messages.*'),
            icon: ChatBubbleLeftRightIcon,
            iconActive: ChatBubbleLeftRightIconSolid,
            badge: unreadMessages.value,
        }
        : {
            label: 'Favorite',
            href: route('favorites.index'),
            current: route().current('favorites.*'),
            icon: HeartIcon,
            iconActive: HeartIconSolid,
        });

    return items;
});

const action = computed(() => {
    if (isProvider.value) return { label: 'Anunț nou', href: route('provider.listings.create') };
    if (can.value.submitQuoteRequest) return { label: 'Cerere nouă', href: route('quote-requests.create') };
    return { label: 'Postează', href: '/register/client' };
});

const initials = computed(() => (user.value?.name || '')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase());

const badge = (count) => (count > 99 ? '99+' : count);
</script>

<template>
    <!-- Keeps page content clear of the floating bar. -->
    <div class="h-[var(--tabbar-h)] lg:hidden" aria-hidden="true" />

    <nav
        class="fixed inset-x-3 bottom-[max(0.75rem,env(safe-area-inset-bottom))] z-40 mx-auto max-w-md font-invita lg:hidden"
        aria-label="Navigare principală"
    >
        <div class="glass grid h-16 grid-cols-5 items-center rounded-[26px] px-1.5 shadow-[0_18px_40px_-18px_rgba(26,20,51,0.45)] ring-1 ring-ivt-ink/[0.06]">
            <Link
                v-for="tab in tabs.slice(0, 2)"
                :key="tab.label"
                :href="tab.href"
                class="tab pressable"
                :class="{ 'tab-active': tab.current }"
                :aria-current="tab.current ? 'page' : undefined"
            >
                <span class="tab-icon">
                    <component :is="tab.current ? tab.iconActive : tab.icon" class="h-[22px] w-[22px]" />
                </span>
                {{ tab.label }}
            </Link>

            <!-- Primary action -->
            <Link :href="action.href" class="pressable flex flex-col items-center justify-center" :aria-label="action.label">
                <span class="flex h-12 w-12 -translate-y-1 items-center justify-center rounded-[18px] bg-brand text-white shadow-glow-primary ring-4 ring-white/90">
                    <PlusIcon class="h-6 w-6 stroke-[2.25]" />
                </span>
            </Link>

            <Link
                v-for="tab in tabs.slice(2)"
                :key="tab.label"
                :href="tab.href"
                class="tab pressable"
                :class="{ 'tab-active': tab.current }"
                :aria-current="tab.current ? 'page' : undefined"
            >
                <span class="tab-icon relative">
                    <component :is="tab.current ? tab.iconActive : tab.icon" class="h-[22px] w-[22px]" />
                    <span v-if="tab.badge" class="tab-badge">{{ badge(tab.badge) }}</span>
                </span>
                {{ tab.label }}
            </Link>

            <button
                type="button"
                class="tab pressable"
                :class="{ 'tab-active': menuOpen }"
                aria-haspopup="dialog"
                :aria-expanded="menuOpen"
                @click="menuOpen = true"
            >
                <span class="tab-icon relative">
                    <span
                        v-if="user"
                        class="flex h-[26px] w-[26px] items-center justify-center rounded-full bg-gradient-to-br from-ivt-ink-2 to-ivt-ink text-[10px] font-semibold text-ivt-on-dark"
                    >{{ initials }}</span>
                    <Bars3Icon v-else class="h-[22px] w-[22px]" />
                    <span v-if="unreadNotifications" class="tab-badge">{{ badge(unreadNotifications) }}</span>
                </span>
                {{ user ? 'Cont' : 'Meniu' }}
            </button>
        </div>
    </nav>
</template>

<style scoped>
.tab {
    @apply flex h-full flex-col items-center justify-center gap-0.5 text-[10.5px] font-semibold text-ivt-ink-soft transition-colors duration-200;
}
.tab-icon {
    @apply flex h-8 w-12 items-center justify-center rounded-full transition-all duration-300;
}
.tab-active {
    @apply text-primary;
}
.tab-active .tab-icon {
    @apply bg-primary/10;
}
.tab-badge {
    @apply absolute -top-0.5 right-1 flex h-[17px] min-w-[17px] items-center justify-center rounded-full bg-primary px-1 text-[9.5px] font-bold leading-none text-white ring-2 ring-white;
}
</style>
