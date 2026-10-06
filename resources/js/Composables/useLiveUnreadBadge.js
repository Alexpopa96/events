import { onBeforeUnmount, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

/**
 * Keeps the header's unread-messages badge current in real time: any message
 * sent to this user (on any conversation, not just one that's open) bumps a
 * lightweight reload of just the badge counts. Safe to mount on every page
 * that shows the badge — Echo handles resubscribing across navigations.
 */
export function useLiveUnreadBadge() {
    const page = usePage();
    const userId = page.props.auth.user?.id;

    if (!userId) return;

    const refresh = () => router.reload({ only: ['unreadMessages', 'unreadNotifications'], preserveScroll: true, preserveState: true });

    onMounted(() => {
        window.Echo?.private(`App.Models.User.${userId}`).listen('.message.sent', refresh);
    });

    onBeforeUnmount(() => {
        window.Echo?.leave(`App.Models.User.${userId}`);
    });
}
