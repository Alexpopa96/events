<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@heroicons/vue/24/outline';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import NotificationList from '@/Components/Notifications/List.vue';

const props = defineProps({
    notifications: { type: Object, required: true },
});

// Providers live inside their own panel; everyone else gets the public site chrome.
const inProviderPanel = computed(() => !!usePage().props.providerNav);
const hasUnread = computed(() => props.notifications.data.some((item) => !item.read));

const readAll = () => {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
};
</script>

<template>
    <ProviderLayout v-if="inProviderPanel" title="Notificări">
        <div class="mb-4 flex justify-end" v-if="hasUnread">
            <button
                type="button"
                class="rounded-full border border-ivt-line bg-white px-4 py-2 text-sm font-medium text-ivt-ink-soft transition-colors hover:border-primary hover:text-primary"
                @click="readAll"
            >
                Marchează toate ca citite
            </button>
        </div>
        <NotificationList :notifications="notifications" />
    </ProviderLayout>

    <div v-else class="bg-ivt-paper font-invita text-ivt-ink antialiased">
        <Head title="Notificări" />
        <SiteHeader />

        <main>
            <section class="relative overflow-hidden border-b border-ivt-line bg-ivt-paper-2 py-12">
                <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
                    <nav class="mb-5 flex flex-wrap items-center gap-1.5 text-[13px] text-ivt-ink-faint">
                        <Link href="/" class="text-ivt-ink-soft transition-colors hover:text-ivt-wine">Acasă</Link>
                        <ChevronRightIcon class="h-3.5 w-3.5" />
                        <Link :href="route('profile.show')" class="text-ivt-ink-soft transition-colors hover:text-ivt-wine">Contul meu</Link>
                        <ChevronRightIcon class="h-3.5 w-3.5" />
                        <span>Notificări</span>
                    </nav>

                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <h1 class="font-serif text-[34px] italic text-ivt-ink">Notificări</h1>
                        <button
                            v-if="hasUnread"
                            type="button"
                            class="rounded-full border border-ivt-line bg-white px-4 py-2 text-sm font-medium text-ivt-ink-soft transition-colors hover:border-ivt-wine hover:text-ivt-wine"
                            @click="readAll"
                        >
                            Marchează toate ca citite
                        </button>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-3xl px-6 py-10 lg:px-8">
                <NotificationList :notifications="notifications" />
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
