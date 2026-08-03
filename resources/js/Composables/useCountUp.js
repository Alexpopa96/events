/**
 * Animates a number from 0 to `target` via requestAnimationFrame, calling
 * `setter` on every frame with an eased, rounded value. Used for stat
 * counters that should feel alive on mount/scroll-into-view rather than
 * appearing as static numbers.
 */
export function animateCountUp(target, setter, duration = 1400) {
    const start = performance.now();

    function tick(now) {
        const progress = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - progress, 3);
        setter(Math.round(eased * target));
        if (progress < 1) {
            requestAnimationFrame(tick);
        }
    }

    requestAnimationFrame(tick);
}
