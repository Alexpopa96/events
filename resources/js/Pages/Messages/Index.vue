<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRightIcon } from '@heroicons/vue/24/outline';
import ClientAccountLayout from '@/Layouts/ClientAccountLayout.vue';
import Inbox from '@/Components/Messages/Inbox.vue';

const props = defineProps({
    conversations: Array,
    active: Object,
    activeId: Number,
    listings: Array,
});

const total = computed(() => props.conversations?.length ?? 0);
const unread = computed(() => (props.conversations ?? []).filter((conversation) => conversation.unread).length);
</script>

<template>
    <ClientAccountLayout title="Mesajele mele" active="messages" top-menu>
        <template #heading>
            <p class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 py-1 pl-1.5 pr-3.5 text-[12.5px] font-semibold text-ivt-ink-soft shadow-sm backdrop-blur">
                <span class="rounded-full bg-brand px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider text-white">{{ unread }}</span>
                {{ unread === 1 ? 'conversație necitită' : 'conversații necitite' }} · {{ total }} în total
            </p>

            <h1 class="mt-6 text-balance font-display text-[34px] font-bold leading-[1.05] tracking-tight text-ivt-ink sm:text-[44px] lg:text-[52px]">
                Vorbește direct cu furnizorii,
                <span class="relative whitespace-nowrap">
                    <span class="text-gradient">fără intermediari.</span>
                    <svg class="absolute -bottom-2 left-0 h-3 w-full text-ivt-accent-bright" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true">
                        <path d="M2 9 C 80 2, 200 2, 298 7" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    </svg>
                </span>
            </h1>

            <p class="mt-6 max-w-[480px] text-[15.5px] leading-relaxed text-ivt-ink-soft">
                Toate conversațiile cu furnizorii, într-un singur loc — clarifică detalii, negociază și stabilește totul pentru eveniment.
            </p>

            <Link :href="route('providers.index')" class="btn-brand mt-9 inline-flex rounded-2xl px-7 py-3.5 text-sm font-bold">
                Găsește furnizori
                <ArrowRightIcon class="h-4 w-4" stroke-width="2.5" />
            </Link>
        </template>

        <div id="mesaje" class="scroll-mt-28">
            <Inbox
                side="client"
                :conversations="conversations"
                :active="active"
                :listings="listings"
                height-class="h-[calc(100dvh-8rem)] max-h-[680px] min-h-[28rem]"
            />
        </div>
    </ClientAccountLayout>
</template>
