<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Menu, X, ChevronRight } from '@lucide/vue';
import SidebarNav from '@/Components/Admin/SidebarNav.vue';
import AccountMenu from '@/Components/Admin/AccountMenu.vue';
import Logo from '@/Components/Logo.vue';

defineProps({
    breadcrumbs: { type: Array, default: () => [] },
    title: { type: String, default: '' },
});

const sidebarOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-ivt-paper-2/40 font-invita text-ivt-ink">
        <!-- Mobile topbar -->
        <div class="lg:hidden sticky top-0 z-20 flex items-center justify-between px-4 h-16 bg-white/90 backdrop-blur border-b border-ivt-line">
            <Link href="/dashboard"><Logo size="sm" variant="invita" /></Link>
            <button
                type="button"
                @click="sidebarOpen = true"
                class="p-2 rounded-xl text-ivt-ink-soft transition-colors duration-150 hover:bg-ivt-paper-2 hover:text-primary"
            >
                <Menu class="w-6 h-6" />
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
                <div class="absolute inset-0 bg-ivt-ink/40 backdrop-blur-[2px]" @click="sidebarOpen = false"></div>
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
                <aside class="absolute inset-y-0 left-0 w-72 bg-white p-5 flex flex-col shadow-xl shadow-ivt-ink/10">
                    <div class="flex items-center justify-between mb-8">
                        <Logo variant="invita" />
                        <button type="button" @click="sidebarOpen = false" class="p-1.5 rounded-lg text-ivt-ink-soft hover:bg-ivt-paper-2 hover:text-primary transition-colors">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    <SidebarNav @navigate="sidebarOpen = false" />
                    <AccountMenu />
                </aside>
            </transition>
        </div>

        <div class="lg:flex">
            <!-- Desktop sidebar -->
            <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 border-r border-ivt-line bg-white px-4 py-6 z-10">
                <Link href="/dashboard" class="mb-9 block px-2">
                    <Logo size="lg" variant="invita" />
                </Link>

                <SidebarNav />
                <AccountMenu />
            </aside>

            <!-- Content -->
            <div class="flex-1 min-w-0 lg:pl-64">
                <header
                    v-if="title || breadcrumbs.length"
                    class="lg:sticky lg:top-0 z-20 bg-white/80 backdrop-blur-xl"
                >
                    <div class="px-4 sm:px-6 lg:px-8 py-4 lg:py-5">
                        <nav v-if="breadcrumbs.length" class="flex flex-wrap items-center gap-1.5 text-xs font-medium text-ivt-ink-soft/70 mb-1">
                            <span v-for="(crumb, i) in breadcrumbs" :key="crumb" class="flex items-center gap-1.5">
                                <span :class="i === breadcrumbs.length - 1 ? 'text-primary' : ''">{{ crumb }}</span>
                                <ChevronRight v-if="i < breadcrumbs.length - 1" class="w-3 h-3" />
                            </span>
                        </nav>
                        <h1 v-if="title" class="font-serif text-xl sm:text-2xl text-ivt-ink leading-tight">{{ title }}</h1>
                    </div>
                    <div class="h-px bg-gradient-to-r from-ivt-gold/50 via-ivt-gold/20 to-transparent"></div>
                </header>

                <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    <div class="max-w-[100rem] mx-auto">
                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
