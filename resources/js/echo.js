import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// So broadcast(...)->toOthers() on the backend can skip echoing an event back
// to the very tab that triggered it (axios carries this on every request,
// including Inertia visits, since Inertia's HTTP client is this same axios).
// Re-bound on every (re)connect, since the socket id changes each time.
window.Echo.connector.pusher.connection.bind('connected', () => {
    window.axios.defaults.headers.common['X-Socket-ID'] = window.Echo.socketId();
});
