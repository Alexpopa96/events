<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronUpDownIcon, UserCircleIcon, ArrowRightStartOnRectangleIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const open = ref(false);

const logout = () => router.post(route('logout'));

const initials = (name) => (name || '')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();
</script>

<template>
    <div class="relative border-t border-line pt-4 mt-4">
        <button
            type="button"
            @click="open = !open"
            class="w-full flex items-center gap-3 px-2 py-2 rounded-2xl transition-all duration-200 hover:bg-paper hover:shadow-sm hover:shadow-ink/5"
        >
            <span class="w-9 h-9 rounded-full bg-gradient-to-br from-brand-500 to-brand-600 text-white flex items-center justify-center text-xs font-semibold flex-none shadow-sm shadow-brand-500/20">
                {{ initials(page.props.auth.user.name) }}
            </span>
            <span class="min-w-0 text-left flex-1">
                <span class="block text-sm font-medium text-ink truncate">{{ page.props.auth.user.name }}</span>
                <span class="block text-xs text-ink-soft truncate">{{ page.props.auth.user.email }}</span>
            </span>
            <ChevronUpDownIcon class="w-4 h-4 text-ink-soft flex-none" />
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
                class="absolute z-40 bottom-full mb-2 left-0 right-0 bg-white border border-line rounded-2xl shadow-glow-brand p-1.5"
            >
                <Link
                    :href="route('profile.show')"
                    class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm text-ink-soft hover:bg-paper hover:text-ink transition"
                    @click="open = false"
                >
                    <UserCircleIcon class="w-4 h-4" /> Profil
                </Link>
                <button
                    type="button"
                    @click="logout"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-sm text-rose-600 hover:bg-rose-50 transition"
                >
                    <ArrowRightStartOnRectangleIcon class="w-4 h-4" /> Deconectare
                </button>
            </div>
        </transition>
    </div>
</template>
