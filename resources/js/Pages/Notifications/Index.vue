<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CheckIcon } from '@heroicons/vue/24/outline';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import NotificationList from '@/Components/Notifications/List.vue';

const props = defineProps({
    notifications: { type: Object, required: true },
});

// Providers live inside their own panel; everyone else gets the public site chrome.
const page = usePage();
const inProviderPanel = computed(() => !!page.props.providerNav);
const unreadCount = computed(() => page.props.unreadNotifications ?? 0);
const hasUnread = computed(() => unreadCount.value > 0 || props.notifications.data.some((item) => !item.read));

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

    <div v-else class="overflow-x-clip bg-ivt-paper font-invita text-ivt-ink antialiased">
        <Head title="Notificări" />
        <SiteHeader />

        <main>
            <!-- PAGE HERO -->
            <section class="relative z-30 pt-8 lg:pt-10">
                <div class="pointer-events-none absolute inset-0 overflow-hidden [mask-image:linear-gradient(to_bottom,#000_55%,transparent)]">
                    <div class="absolute -left-40 -top-40 h-[520px] w-[520px] rounded-full bg-primary/15 blur-3xl animate-float-slow" />
                    <div class="absolute -right-32 top-10 h-[460px] w-[460px] rounded-full bg-ivt-violet/15 blur-3xl animate-float-slower" />
                    <div class="absolute bottom-0 left-1/3 h-[320px] w-[320px] rounded-full bg-ivt-accent-bright/15 blur-3xl animate-float-slower" />
                    <div
                        class="absolute inset-0 opacity-[0.35]"
                        style="background-image: radial-gradient(rgba(26,20,51,0.12) 1px, transparent 1px); background-size: 22px 22px; mask-image: radial-gradient(ellipse 70% 60% at 30% 30%, #000 30%, transparent 75%);"
                    />
                </div>
                <div class="relative mx-auto max-w-[1600px] px-6 pb-8 lg:px-8 lg:pb-10">
                    <nav class="flex items-center gap-2 text-[13px] text-ivt-ink-faint" aria-label="Breadcrumb">
                        <Link href="/" class="transition-colors hover:text-primary">Acasă</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <Link :href="route('profile.show')" class="transition-colors hover:text-primary">Contul meu</Link>
                        <span aria-hidden="true" class="opacity-50">/</span>
                        <span class="text-ivt-ink-soft">Notificări</span>
                    </nav>

                    <div class="mt-8 lg:mt-10">
                        <p class="inline-flex items-center gap-2 rounded-full border border-ivt-line bg-white/80 py-1 pl-1.5 pr-3.5 text-[12.5px] font-semibold text-ivt-ink-soft shadow-sm backdrop-blur">
                            <span class="rounded-full bg-brand px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wider text-white">{{ unreadCount }}</span>
                            {{ unreadCount === 1 ? 'notificare necitită' : 'notificări necitite' }} din {{ notifications.total }}
                        </p>

                        <h1 class="mt-6 text-balance font-display text-[34px] font-bold leading-[1.05] tracking-tight text-ivt-ink sm:text-[44px] lg:text-[52px]">
                            Tot ce se întâmplă în cont,
                            <span class="relative whitespace-nowrap">
                                <span class="text-gradient">mereu la zi.</span>
                                <svg class="absolute -bottom-2 left-0 h-3 w-full text-ivt-accent-bright" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true">
                                    <path d="M2 9 C 80 2, 200 2, 298 7" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                </svg>
                            </span>
                        </h1>

                        <p class="mt-6 max-w-[480px] text-[15.5px] leading-relaxed text-ivt-ink-soft">
                            Oferte noi de la furnizori, mesaje și actualizări despre cererile tale — toate într-un singur loc.
                        </p>

                        <button
                            v-if="hasUnread"
                            type="button"
                            class="btn-brand mt-9 inline-flex rounded-2xl px-7 py-3.5 text-sm font-bold"
                            @click="readAll"
                        >
                            <CheckIcon class="h-4 w-4" stroke-width="2.5" />
                            Marchează toate ca citite
                        </button>
                    </div>
                </div>

            </section>

            <section id="notificari" class="mx-auto max-w-4xl scroll-mt-24 px-6 py-12 pb-[110px] lg:px-8">
                <NotificationList :notifications="notifications" />
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
