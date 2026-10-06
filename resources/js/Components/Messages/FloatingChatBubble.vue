<script setup>
import { computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ChatBubbleLeftRightIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { useLastChat } from '@/Composables/useLastChat';

const page = usePage();
const { chat, load, forget } = useLastChat();

onMounted(load);

const user = computed(() => page.props.auth.user);
const unread = computed(() => page.props.unreadMessages ?? 0);

// Only the client who opened the chat sees it, and never on the inbox itself.
const visible = computed(() => Boolean(
    chat.value
    && user.value
    && chat.value.userId === user.value.id
    && page.props.auth.can?.submitQuoteRequest
    && !route().current('messages.*'),
));

const initials = (name) => (name || '?')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();
</script>

<template>
    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-4 scale-90 opacity-0"
        enter-to-class="translate-y-0 scale-100 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="scale-90 opacity-0"
    >
        <div v-if="visible" class="group fixed bottom-24 right-4 z-40 lg:bottom-6 lg:right-6">
            <!-- Hover label (desktop) -->
            <span class="pointer-events-none absolute right-full top-1/2 mr-3 hidden -translate-y-1/2 whitespace-nowrap rounded-2xl bg-ink px-3.5 py-2 text-xs text-white opacity-0 shadow-xl transition-opacity duration-200 group-hover:opacity-100 lg:block">
                <span class="block font-semibold">{{ chat.name }}</span>
                <span class="block text-white/70">Continuă conversația</span>
            </span>

            <Link
                :href="route('messages.index', chat.id)"
                class="relative flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary-bright text-white shadow-xl shadow-primary/30 ring-4 ring-white transition-transform duration-200 hover:scale-105 active:scale-95"
                :aria-label="`Deschide conversația cu ${chat.name}`"
            >
                <img v-if="chat.logoUrl" :src="chat.logoUrl" alt="" class="h-full w-full rounded-full object-cover" />
                <span v-else class="text-base font-semibold">{{ initials(chat.name) }}</span>

                <span class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-white text-primary shadow-md ring-1 ring-line">
                    <ChatBubbleLeftRightIcon class="h-3.5 w-3.5" />
                </span>
                <span v-if="unread" class="absolute -left-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-ink px-1.5 text-[11px] font-semibold text-white ring-2 ring-white">
                    {{ unread > 99 ? '99+' : unread }}
                </span>
            </Link>

            <button
                type="button"
                @click="forget"
                aria-label="Ascunde bula de conversație"
                class="absolute -right-1 -top-3 flex h-6 w-6 items-center justify-center rounded-full bg-white text-ink-soft opacity-0 shadow-md ring-1 ring-line transition-opacity duration-150 hover:text-primary focus:opacity-100 group-hover:opacity-100"
            >
                <XMarkIcon class="h-3.5 w-3.5" />
            </button>
        </div>
    </transition>
</template>
