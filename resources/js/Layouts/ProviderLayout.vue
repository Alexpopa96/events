<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline';
import SidebarNav from '@/Components/Provider/SidebarNav.vue';
import AccountMenu from '@/Components/Provider/AccountMenu.vue';
import Logo from '@/Components/Logo.vue';

defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
    eyebrow: {
        type: String,
        default: 'Panou furnizor',
    },
});

const sidebarOpen = ref(false);
</script>

<template>
    <Head :title="title" />

    <div class="relative min-h-screen bg-paper text-ink overflow-x-hidden">
        <!-- Decorative backdrop — subtle, same brand toolkit as the public site -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <div class="absolute -top-40 -right-24 w-[28rem] h-[28rem] rounded-full bg-brand-400/10 blur-3xl animate-float-slow"></div>
            <div class="absolute top-1/2 -left-32 w-96 h-96 rounded-full bg-gold-400/10 blur-3xl animate-float-slower"></div>
        </div>

        <!-- Mobile topbar -->
        <div class="relative lg:hidden flex items-center justify-between px-4 h-16 bg-white/90 backdrop-blur border-b border-line">
            <Link href="/"><Logo size="sm" /></Link>
            <button @click="sidebarOpen = true" class="p-2 rounded-lg text-ink-soft transition-colors duration-150 hover:bg-paper hover:text-brand-600">
                <Bars3Icon class="w-6 h-6" />
            </button>
        </div>

        <!-- Mobile sidebar overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 lg:hidden">
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
                appear
            >
                <div class="absolute inset-0 bg-ink/40" @click="sidebarOpen = false"></div>
            </transition>
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="-translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="translate-x-0"
                leave-to-class="-translate-x-full"
                appear
            >
                <aside class="absolute inset-y-0 left-0 w-72 bg-white p-5 flex flex-col">
                    <div class="flex items-center justify-between mb-8">
                        <Logo />
                        <button @click="sidebarOpen = false" class="p-1 text-ink-soft"><XMarkIcon class="w-5 h-5" /></button>
                    </div>
                    <SidebarNav @navigate="sidebarOpen = false" />
                    <AccountMenu />
                </aside>
            </transition>
        </div>

        <div class="relative lg:flex">
            <!-- Desktop sidebar -->
            <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 border-r border-line bg-white/95 backdrop-blur px-5 py-6 shadow-[1px_0_12px_rgba(33,28,39,0.03)] z-10">
                <Link href="/" class="relative block mb-10 px-1">
                    <span class="absolute -left-3 -top-3 w-16 h-16 rounded-full bg-gold-400/20 blur-2xl" aria-hidden="true"></span>
                    <Logo size="lg" class="relative" />
                </Link>

                <SidebarNav />
                <AccountMenu />
            </aside>

            <!-- Content -->
            <div class="flex-1 lg:pl-64">
                <header class="hidden lg:block sticky top-0 z-20 bg-white/75 backdrop-blur-xl">
                    <div class="flex items-center h-20 px-8">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gold-500">{{ eyebrow }}</p>
                            <h1 class="font-serif text-2xl text-ink leading-tight">{{ title }}</h1>
                        </div>
                        <div class="ml-auto flex items-center gap-3">
                            <slot name="actions" />
                        </div>
                    </div>
                    <div class="h-px bg-gradient-to-r from-brand-500/40 via-gold-400/40 to-transparent"></div>
                </header>

                <main class="relative px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
