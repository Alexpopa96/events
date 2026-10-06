import { ref } from 'vue';

const STORAGE_KEY = 'events.lastChat';

// Module-level so every component (inbox, floating bubble) shares one reactive value.
const chat = ref(null);

const read = () => {
    try {
        return JSON.parse(window.localStorage.getItem(STORAGE_KEY));
    } catch {
        return null;
    }
};

/**
 * Remembers the chat the client last opened, so it can be reopened from a floating
 * bubble on any other page. Persisted in localStorage; every access is guarded because
 * storage can be blocked or empty.
 */
export function useLastChat() {
    const load = () => {
        chat.value = read();
    };

    const remember = (data) => {
        chat.value = data;
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        } catch { /* storage unavailable */ }
    };

    const forget = () => {
        chat.value = null;
        try {
            window.localStorage.removeItem(STORAGE_KEY);
        } catch { /* storage unavailable */ }
    };

    return { chat, load, remember, forget };
}
