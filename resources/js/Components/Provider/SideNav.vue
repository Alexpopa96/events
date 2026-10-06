<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    HomeIcon,
    RectangleStackIcon,
    CreditCardIcon,
    InboxArrowDownIcon,
    BuildingStorefrontIcon,
    CalendarDaysIcon,
    ChatBubbleLeftRightIcon,
    CurrencyDollarIcon,
    StarIcon,
} from '@heroicons/vue/24/outline';
import {
    HomeIcon as HomeSolid,
    RectangleStackIcon as RectangleStackSolid,
    CreditCardIcon as CreditCardSolid,
    InboxArrowDownIcon as InboxSolid,
    ChatBubbleLeftRightIcon as ChatSolid,
    BuildingStorefrontIcon as StoreSolid,
    StarIcon as StarSolid,
    CurrencyDollarIcon as CurrencyDollarSolid,
    CalendarDaysIcon as CalendarDaysSolid,
} from '@heroicons/vue/24/solid';

defineEmits(['navigate']);

const page = usePage();
const newLeads = computed(() => page.props.providerNav?.new_leads ?? 0);
const unreadMessages = computed(() => page.props.unreadMessages ?? 0);
const unansweredReviews = computed(() => page.props.providerNav?.unanswered_reviews ?? 0);
const awaitingOffers = computed(() => page.props.providerNav?.awaiting_offers ?? 0);

const items = computed(() => [
    { label: 'Prezentare', icon: HomeIcon, active: HomeSolid, href: route('provider.dashboard'), current: route().current('provider.dashboard') },
    { label: 'Anunțurile mele', icon: RectangleStackIcon, active: RectangleStackSolid, href: route('provider.listings.index'), current: route().current('provider.listings.*') },
    { label: 'Mesaje', icon: ChatBubbleLeftRightIcon, active: ChatSolid, href: route('provider.messages.index'), current: route().current('provider.messages.*'), badge: unreadMessages.value },
    { label: 'Cereri clienți', icon: InboxArrowDownIcon, active: InboxSolid, href: route('provider.leads.index'), current: route().current('provider.leads.*'), badge: newLeads.value },
    { label: 'Ofertele mele', icon: CurrencyDollarIcon, active: CurrencyDollarSolid, href: route('provider.offers.index'), current: route().current('provider.offers.*'), badge: awaitingOffers.value },
    { label: 'Calendar', icon: CalendarDaysIcon, active: CalendarDaysSolid, href: route('provider.availability.index'), current: route().current('provider.availability.*') },
    { label: 'Recenzii', icon: StarIcon, active: StarSolid, href: route('provider.reviews.index'), current: route().current('provider.reviews.*'), badge: unansweredReviews.value },
    { label: 'Abonament & facturi', icon: CreditCardIcon, active: CreditCardSolid, href: route('provider.subscription.index'), current: route().current('provider.subscription.*') },
    { label: 'Profil companie', icon: BuildingStorefrontIcon, active: StoreSolid, href: route('provider.profile.edit'), current: route().current('provider.profile.*') },
]);
</script>

<template>
    <nav class="flex-1 space-y-1 overflow-y-auto" aria-label="Navigare principală">
        <Link
            v-for="item in items"
            :key="item.label"
            :href="item.href"
            @click="$emit('navigate')"
            class="group flex items-center gap-4 rounded-2xl px-4 py-3 text-[15px] transition-colors duration-150"
            :class="item.current
                ? 'font-semibold text-white bg-primary shadow-glow-primary'
                : 'font-medium text-ivt-ink hover:bg-ivt-paper-2'"
            :aria-current="item.current ? 'page' : undefined"
        >
            <span class="relative flex-none">
                <component :is="item.current ? item.active : item.icon" class="h-6 w-6" />
                <span
                    v-if="item.badge"
                    class="absolute -top-1.5 -right-2 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full text-[10px] font-semibold flex items-center justify-center ring-2"
                    :class="item.current ? 'bg-white text-primary ring-primary' : 'bg-primary text-white ring-white'"
                >{{ item.badge > 99 ? '99+' : item.badge }}</span>
            </span>
            <span class="flex-1 truncate">{{ item.label }}</span>
        </Link>
    </nav>
</template>
