<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { HomeIcon, RectangleStackIcon, InboxArrowDownIcon, ChatBubbleLeftRightIcon, PlusIcon } from '@heroicons/vue/24/outline';
import {
    HomeIcon as HomeSolid,
    RectangleStackIcon as RectangleStackSolid,
    InboxArrowDownIcon as InboxSolid,
    ChatBubbleLeftRightIcon as ChatSolid,
} from '@heroicons/vue/24/solid';

const page = usePage();
const newLeads = computed(() => page.props.providerNav?.new_leads ?? 0);
const unreadMessages = computed(() => page.props.unreadMessages ?? 0);

const items = computed(() => [
    { label: 'Acasă', icon: HomeIcon, active: HomeSolid, href: route('provider.dashboard'), current: route().current('provider.dashboard') },
    { label: 'Anunțuri', icon: RectangleStackIcon, active: RectangleStackSolid, href: route('provider.listings.index'), current: route().current('provider.listings.index') },
    { create: true },
    { label: 'Cereri', icon: InboxArrowDownIcon, active: InboxSolid, href: route('provider.leads.index'), current: route().current('provider.leads.*'), badge: newLeads.value },
    { label: 'Mesaje', icon: ChatBubbleLeftRightIcon, active: ChatSolid, href: route('provider.messages.index'), current: route().current('provider.messages.*'), badge: unreadMessages.value },
]);
</script>

<template>
    <nav class="lg:hidden fixed inset-x-0 bottom-0 z-40 border-t border-ivt-line bg-white/95 backdrop-blur-xl pb-[env(safe-area-inset-bottom)]" aria-label="Navigare rapidă">
        <div class="mx-auto grid h-16 max-w-md grid-cols-5 items-center">
            <template v-for="(item, i) in items" :key="i">
                <div v-if="item.create" class="flex justify-center">
                    <Link
                        :href="route('provider.listings.create')"
                        class="flex h-12 w-12 -mt-5 items-center justify-center rounded-full bg-primary text-white shadow-glow-primary ring-4 ring-white transition-transform duration-150 active:scale-95"
                        aria-label="Anunț nou"
                    >
                        <PlusIcon class="h-6 w-6" />
                    </Link>
                </div>
                <Link
                    v-else
                    :href="item.href"
                    class="relative flex flex-col items-center gap-0.5 py-1.5 text-[11px] font-medium transition-colors duration-150"
                    :class="item.current ? 'text-primary' : 'text-ivt-ink-soft'"
                >
                    <span class="relative">
                        <component :is="item.current ? item.active : item.icon" class="h-6 w-6" />
                        <span
                            v-if="item.badge"
                            class="absolute -top-1 -right-2 min-w-[1rem] h-4 px-1 rounded-full bg-primary text-white text-[9px] font-semibold flex items-center justify-center ring-2 ring-white"
                        >{{ item.badge > 99 ? '99+' : item.badge }}</span>
                    </span>
                    {{ item.label }}
                </Link>
            </template>
        </div>
    </nav>
</template>
