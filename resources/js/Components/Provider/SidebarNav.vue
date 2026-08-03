<script setup>
import { Link } from '@inertiajs/vue3';
import {
    Squares2X2Icon,
    RectangleStackIcon,
    BuildingStorefrontIcon,
    CreditCardIcon,
    ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline';

defineEmits(['navigate']);

const items = [
    { label: 'Dashboard', icon: Squares2X2Icon, href: () => route('provider.dashboard'), current: () => route().current('provider.dashboard') },
    { label: 'Anunțurile mele', icon: RectangleStackIcon, href: () => route('provider.listings.index'), current: () => route().current('provider.listings.*') },
    { label: 'Profil companie', icon: BuildingStorefrontIcon, href: () => route('provider.profile.edit'), current: () => route().current('provider.profile.*') },
    { label: 'Abonament & facturi', icon: CreditCardIcon, href: () => route('provider.subscription.index'), current: () => route().current('provider.subscription.*') },
    { label: 'Cereri de ofertă', icon: ClipboardDocumentListIcon, href: () => route('provider.leads.index'), current: () => route().current('provider.leads.*') },
];
</script>

<template>
    <nav class="flex-1">
        <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-ink-soft/50 mb-2">Meniu</p>
        <div class="space-y-1">
            <Link
                v-for="item in items"
                :key="item.label"
                :href="item.href()"
                @click="$emit('navigate')"
                class="group flex items-center gap-3 px-3 py-2.5 rounded-2xl text-sm font-medium transition-all duration-200 ease-out"
                :class="item.current()
                    ? 'bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-glow-brand'
                    : 'text-ink-soft hover:bg-brand-50/60 hover:text-brand-600 hover:shadow-sm hover:shadow-ink/5 hover:-translate-y-px'"
            >
                <span
                    class="flex items-center justify-center w-8 h-8 rounded-xl transition-all duration-200 ease-out flex-none group-hover:scale-110"
                    :class="item.current() ? 'bg-white/15 ring-1 ring-gold-400/30' : 'bg-paper group-hover:bg-white group-hover:shadow-sm'"
                >
                    <component :is="item.icon" class="w-4 h-4" />
                </span>
                {{ item.label }}
            </Link>
        </div>
    </nav>
</template>
