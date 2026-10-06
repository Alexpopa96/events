<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { PlusIcon, BuildingStorefrontIcon } from '@heroicons/vue/24/outline';

defineEmits(['navigate']);

// Desktop shows the company in the right rail, so only the create button remains.
defineProps({ compact: { type: Boolean, default: false } });

const page = usePage();
const nav = computed(() => page.props.providerNav);
const active = computed(() => nav.value?.plan_status === 'active');
</script>

<template>
    <div v-if="nav" class="mb-4 space-y-3">
        <!-- Company + plan -->
        <Link
            v-if="!compact"
            :href="route('provider.subscription.index')"
            @click="$emit('navigate')"
            class="flex items-center gap-3 p-3 rounded-2xl bg-gradient-to-br from-ivt-paper-2/70 to-white border border-ivt-line/70 shadow-sm shadow-ivt-ink/5 transition-all duration-200 hover:shadow-md hover:shadow-ivt-ink/10 hover:-translate-y-px"
        >
            <span class="w-11 h-11 rounded-xl bg-white border border-ivt-line flex items-center justify-center overflow-hidden flex-none shadow-sm shadow-ivt-ink/5 text-primary">
                <img v-if="nav.logo_url" :src="nav.logo_url" alt="" class="w-full h-full object-cover" />
                <BuildingStorefrontIcon v-else class="w-5 h-5" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-sm font-semibold text-ivt-ink truncate">{{ nav.company_name }}</span>
                <span v-if="nav.plan_name" class="mt-0.5 flex items-center gap-1.5 text-xs" :class="active ? 'text-ivt-ink-soft' : 'text-rose-600'">
                    <span class="w-1.5 h-1.5 rounded-full flex-none" :class="active ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                    <span class="truncate">Plan {{ nav.plan_name }}</span>
                </span>
            </span>
        </Link>

        <Link
            :href="route('provider.listings.create')"
            @click="$emit('navigate')"
            class="flex items-center justify-center gap-2 w-full rounded-full bg-primary px-4 py-3 text-sm font-semibold text-white shadow-glow-primary transition-all duration-200 hover:bg-primary-bright hover:-translate-y-0.5 active:translate-y-0"
        >
            <PlusIcon class="w-4 h-4" /> Anunț nou
        </Link>
    </div>
</template>
