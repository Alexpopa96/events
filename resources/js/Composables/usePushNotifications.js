import { ref } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

// Converts the VAPID public key (URL-safe base64, as Laravel's webpush:vapid
// command generates it) into the raw byte array pushManager.subscribe() expects.
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

/**
 * Drives the "enable push notifications" toggle: subscribing talks to the
 * browser's Push API and saves the resulting subscription server-side;
 * unsubscribing does the reverse. State is re-derived from the browser's own
 * subscription on mount, so it stays correct across devices/tabs.
 */
export function usePushNotifications() {
    const page = usePage();

    const supported = 'serviceWorker' in navigator && 'PushManager' in window;
    const status = ref('idle'); // idle | loading | subscribed | unsubscribed | unsupported | denied
    const error = ref(null);

    const checkStatus = async () => {
        if (!supported) {
            status.value = 'unsupported';
            return;
        }
        if (Notification.permission === 'denied') {
            status.value = 'denied';
            return;
        }

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        status.value = subscription ? 'subscribed' : 'unsubscribed';
    };

    const subscribe = async () => {
        if (!supported) return;
        status.value = 'loading';
        error.value = null;

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                status.value = permission === 'denied' ? 'denied' : 'unsubscribed';
                return;
            }

            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(page.props.vapidPublicKey),
            });

            await axios.post(route('push-subscriptions.store'), subscription.toJSON());
            status.value = 'subscribed';
        } catch (e) {
            error.value = 'Nu am putut activa notificările. Încearcă din nou.';
            status.value = 'unsubscribed';
        }
    };

    const unsubscribe = async () => {
        status.value = 'loading';
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await axios.delete(route('push-subscriptions.destroy'), { data: { endpoint: subscription.endpoint } });
                await subscription.unsubscribe();
            }

            status.value = 'unsubscribed';
        } catch (e) {
            error.value = 'Nu am putut dezactiva notificările.';
            status.value = 'subscribed';
        }
    };

    return { status, error, supported, checkStatus, subscribe, unsubscribe };
}
