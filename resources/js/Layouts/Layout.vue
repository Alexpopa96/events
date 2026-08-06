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
    <div class="min-h-screen bg-paper text-ink">
        <!-- Mobile topbar -->
        <div class="lg:hidden sticky top-0 z-20 flex items-center justify-between px-4 h-16 bg-white border-b border-line">
            <Link href="/dashboard"><Logo size="sm" /></Link>
            <button
                type="button"
                @click="sidebarOpen = true"
                class="p-2 rounded-lg text-ink-soft transition-colors duration-150 hover:bg-paper hover:text-brand-600"
            >
                <Menu class="w-6 h-6" />
            </button>
        </div>

        <!-- Mobile sidebar overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 lg:hidden">
            <div class="absolute inset-0 bg-ink/40" @click="sidebarOpen = false"></div>
            <aside class="absolute inset-y-0 left-0 w-72 bg-white p-5 flex flex-col">
                <div class="flex items-center justify-between mb-8">
                    <Logo />
                    <button type="button" @click="sidebarOpen = false" class="p-1 text-ink-soft">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <SidebarNav @navigate="sidebarOpen = false" />
                <AccountMenu />
            </aside>
        </div>

        <div class="lg:flex">
            <!-- Desktop sidebar -->
            <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 border-r border-line bg-white px-5 py-6 shadow-[1px_0_12px_rgba(33,28,39,0.03)] z-10">
                <Link href="/dashboard" class="mb-10 block px-1">
                    <Logo size="lg" />
                </Link>

                <SidebarNav />
                <AccountMenu />
            </aside>

            <!-- Content -->
            <div class="flex-1 lg:pl-64">
                <header v-if="title || breadcrumbs.length" class="border-b border-line bg-white/70 backdrop-blur px-4 sm:px-6 lg:px-8 py-4 lg:py-5">
                    <nav v-if="breadcrumbs.length" class="flex flex-wrap items-center gap-1.5 text-xs font-medium text-ink-soft/70 mb-1">
                        <span v-for="(crumb, i) in breadcrumbs" :key="crumb" class="flex items-center gap-1.5">
                            {{ crumb }}
                            <ChevronRight v-if="i < breadcrumbs.length - 1" class="w-3 h-3" />
                        </span>
                    </nav>
                    <h1 v-if="title" class="font-serif text-xl sm:text-2xl text-ink">{{ title }}</h1>
                </header>

                <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    <div class="max-w-[100rem] mx-auto">
                        <div class="bg-white border border-line rounded-3xl shadow-sm shadow-ink/5 p-5 sm:p-8">
                            <slot />
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
