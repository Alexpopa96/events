<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronsUpDown, CircleUserRound, LogOut, ArrowLeftToLine } from '@lucide/vue';

const page = usePage();
const open = ref(false);

const logout = () => router.post(route('logout'));
const exitImpersonate = () => router.post('/exitimpersonate');
</script>

<template>
    <div>
        <button
            v-if="page.props.impersonate"
            type="button"
            @click="exitImpersonate"
            class="w-full flex items-center justify-center gap-2 mb-3 px-3 py-2 rounded-2xl text-xs font-semibold text-white bg-gold-500 hover:bg-gold-500/90 shadow-sm shadow-gold-500/30 transition-colors"
        >
            <ArrowLeftToLine class="w-4 h-4" /> Ieși din impersonare
        </button>

        <div class="relative border-t border-line pt-4">
            <button
                type="button"
                @click="open = !open"
                class="w-full flex items-center gap-3 px-2 py-2 rounded-2xl transition-all duration-200 hover:bg-paper hover:shadow-sm hover:shadow-ink/5"
            >
                <img
                    :src="page.props.auth.user.profile_photo_url"
                    :alt="page.props.auth.user.name"
                    class="w-9 h-9 rounded-full object-cover flex-none shadow-sm shadow-ink/5"
                />
                <span class="min-w-0 text-left flex-1">
                    <span class="block text-sm font-medium text-ink truncate">{{ page.props.auth.user.name }}</span>
                    <span class="block text-xs text-ink-soft truncate">{{ page.props.auth.user.email }}</span>
                </span>
                <ChevronsUpDown class="w-4 h-4 text-ink-soft flex-none" />
            </button>

            <div v-if="open" class="fixed inset-0 z-30" @click="open = false"></div>

            <transition
                enter-active-class="transition ease-out duration-150"
                enter-from-class="opacity-0 translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="open"
                    class="absolute z-40 bottom-full mb-2 left-0 right-0 bg-white border border-line rounded-2xl shadow-lg shadow-ink/5 p-1.5"
                >
                    <Link
                        href="/user/profile"
                        class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm text-ink-soft hover:bg-paper hover:text-ink transition"
                        @click="open = false"
                    >
                        <CircleUserRound class="w-4 h-4" /> Profil
                    </Link>
                    <button
                        type="button"
                        @click="logout"
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-sm text-rose-600 hover:bg-rose-50 transition"
                    >
                        <LogOut class="w-4 h-4" /> Deconectare
                    </button>
                </div>
            </transition>
        </div>
    </div>
</template>
