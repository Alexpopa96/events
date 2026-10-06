import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Calls `fn` every `ms` while the tab is visible, and once more when the tab
 * becomes visible again. `enabled` (optional) can pause it.
 */
export function usePolling(fn, ms = 5000, enabled = () => true) {
    let timer = null;

    const tick = () => {
        if (document.hidden || !enabled()) return;
        fn();
    };

    onMounted(() => {
        timer = setInterval(tick, ms);
        document.addEventListener('visibilitychange', tick);
    });

    onBeforeUnmount(() => {
        clearInterval(timer);
        document.removeEventListener('visibilitychange', tick);
    });
}
