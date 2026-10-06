<script setup>
import { onMounted } from 'vue';
import { BellIcon, BellSlashIcon } from '@heroicons/vue/24/outline';
import { usePushNotifications } from '@/Composables/usePushNotifications';

// Compact ("icon only, for a nav rail") or full ("icon + label", for a menu/settings list).
defineProps({
    compact: { type: Boolean, default: false },
});

const { status, error, supported, checkStatus, subscribe, unsubscribe } = usePushNotifications();

onMounted(checkStatus);

const toggle = () => (status.value === 'subscribed' ? unsubscribe() : subscribe());
</script>

<template>
    <div v-if="supported && status !== 'unsupported'">
        <button
            v-if="status !== 'denied'"
            type="button"
            :disabled="status === 'loading'"
            @click="toggle"
            class="group flex items-center gap-3 rounded-xl px-2.5 py-2 text-sm font-medium text-ivt-ink-soft transition-colors duration-150 hover:bg-ivt-paper-2 hover:text-ivt-ink disabled:opacity-50"
            :class="{ 'justify-center px-0 py-0': compact }"
        >
            <span
                v-if="!compact"
                class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-ivt-paper-2 text-ivt-ink-soft transition-colors duration-150 group-hover:bg-white group-hover:text-ivt-wine"
            >
                <BellIcon v-if="status !== 'subscribed'" class="h-5 w-5" />
                <BellSlashIcon v-else class="h-5 w-5" />
            </span>
            <component v-else :is="status === 'subscribed' ? BellIcon : BellSlashIcon" class="h-5 w-5" />
            <span v-if="!compact">{{ status === 'subscribed' ? 'Dezactivează notificările push' : 'Activează notificările push' }}</span>
        </button>
        <p v-else-if="!compact" class="px-2.5 py-2 text-xs text-ivt-ink-soft">
            Notificările push sunt blocate din setările browserului.
        </p>
        <p v-if="error" class="px-2.5 text-xs text-rose-600">{{ error }}</p>
    </div>
</template>
