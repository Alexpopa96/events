<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronDownIcon, ChevronUpDownIcon, UserCircleIcon, ArrowRightStartOnRectangleIcon, BellIcon, BellSlashIcon } from '@heroicons/vue/24/outline';
import { usePushNotifications } from '@/Composables/usePushNotifications';

// `sidebar` sits at the bottom of the drawer and opens upward; `header` is the
// avatar dropdown in the top bar and opens downward, right-aligned.
const props = defineProps({ variant: { type: String, default: 'sidebar' } });
const header = props.variant === 'header';

const page = usePage();
const open = ref(false);

const push = usePushNotifications();
push.checkStatus();

const nav = computed(() => page.props.providerNav);

const logout = () => router.post(route('logout'));

const initials = (name) => (name || '')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();
</script>

<template>
    <div class="relative" :class="!header && 'pt-4 mt-4 border-t border-ivt-line'">
        <button
            type="button"
            @click="open = !open"
            class="flex items-center gap-3 rounded-full transition-all duration-200"
            :class="header ? 'p-1 pr-2.5 hover:bg-ivt-paper-2' : 'w-full p-2 rounded-2xl hover:bg-ivt-paper'"
            aria-haspopup="menu"
            :aria-expanded="open"
        >
            <span class="rounded-full bg-primary text-white flex items-center justify-center text-xs font-semibold flex-none shadow-md shadow-primary/25" :class="header ? 'w-9 h-9' : 'w-10 h-10'">
                {{ initials(page.props.auth.user.name) }}
            </span>
            <span v-if="!header" class="min-w-0 text-left flex-1">
                <span class="block text-sm font-medium text-ivt-ink truncate">{{ page.props.auth.user.name }}</span>
                <span class="block text-xs text-ivt-ink-soft truncate">{{ page.props.auth.user.email }}</span>
            </span>
            <ChevronDownIcon v-if="header" class="w-4 h-4 text-ivt-ink-soft flex-none" />
            <ChevronUpDownIcon v-else class="w-4 h-4 text-ivt-ink-soft flex-none" />
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
                role="menu"
                class="absolute z-40 bg-white border border-ivt-line rounded-2xl shadow-[0_12px_32px_-8px_rgba(26,20,51,0.2)] p-1.5"
                :class="header ? 'top-full mt-2 right-0 w-64' : 'bottom-full mb-2 left-0 right-0'"
            >
                <div v-if="header" class="px-3 py-2 mb-1 border-b border-ivt-line">
                    <p class="text-sm font-medium text-ivt-ink truncate">{{ page.props.auth.user.name }}</p>
                    <p class="text-xs text-ivt-ink-soft truncate">{{ page.props.auth.user.email }}</p>
                </div>
                <Link
                    :href="route('profile.show')"
                    class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm text-ivt-ink-soft hover:bg-ivt-paper hover:text-ivt-ink transition"
                    @click="open = false"
                >
                    <UserCircleIcon class="w-4 h-4" /> Contul meu
                </Link>
                <button
                    v-if="push.supported && push.status.value !== 'unsupported' && push.status.value !== 'denied'"
                    type="button"
                    :disabled="push.status.value === 'loading'"
                    class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm text-ivt-ink-soft transition hover:bg-ivt-paper hover:text-ivt-ink disabled:opacity-50"
                    @click="push.status.value === 'subscribed' ? push.unsubscribe() : push.subscribe()"
                >
                    <BellSlashIcon v-if="push.status.value === 'subscribed'" class="h-4 w-4" />
                    <BellIcon v-else class="h-4 w-4" />
                    {{ push.status.value === 'subscribed' ? 'Dezactivează notificările push' : 'Activează notificările push' }}
                </button>
                <button
                    type="button"
                    @click="logout"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-sm text-danger-600 hover:bg-danger-50 transition"
                >
                    <ArrowRightStartOnRectangleIcon class="w-4 h-4" /> Deconectare
                </button>
            </div>
        </transition>
    </div>
</template>
