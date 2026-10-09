/* App-wide interaction directives (styles live in app.css). All of them are
   no-ops during SSR and for users who prefer reduced motion. */

const reducedMotion = () => typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

/* v-reveal="delayMs" — fades an element in as it scrolls into view. Elements
   already on screen at mount are left alone so the page never flickers after
   SSR hydration. */
const reveal = {
    mounted(el, { value: delay = 0 }) {
        if (typeof IntersectionObserver === 'undefined' || reducedMotion() || el.getBoundingClientRect().top < window.innerHeight) {
            return;
        }

        el.classList.add('reveal');
        el.style.transitionDelay = `${delay}ms`;
        el._revealObserver = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) {
                el.classList.add('reveal-visible');
                el._revealObserver.disconnect();
                // Hand transitions back to the element's own classes (hover effects).
                el.addEventListener('transitionend', () => {
                    el.classList.remove('reveal', 'reveal-visible');
                    el.style.transitionDelay = '';
                }, { once: true });
            }
        }, { rootMargin: '0px 0px -8% 0px' });
        el._revealObserver.observe(el);
    },
    unmounted(el) {
        el._revealObserver?.disconnect();
    },
    getSSRProps: () => ({}),
};

/* v-spotlight — a soft glow that follows the cursor across a card. Adds the
   .spotlight class and feeds it the pointer position as CSS variables. */
const spotlight = {
    mounted(el) {
        el.classList.add('spotlight');
        el._spotlightMove = (event) => {
            const rect = el.getBoundingClientRect();
            el.style.setProperty('--mx', `${event.clientX - rect.left}px`);
            el.style.setProperty('--my', `${event.clientY - rect.top}px`);
        };
        el.addEventListener('pointermove', el._spotlightMove);
    },
    unmounted(el) {
        el.removeEventListener('pointermove', el._spotlightMove);
    },
    getSSRProps: () => ({ class: 'spotlight' }),
};

/* v-tilt="maxDegrees" — tilts an element towards the cursor in 3D. Mouse only,
   so touch scrolling never fights with it. */
const tilt = {
    mounted(el, { value: max = 6 }) {
        if (reducedMotion()) {
            return;
        }

        el.classList.add('tilt');
        el._tiltMove = (event) => {
            if (event.pointerType !== 'mouse') {
                return;
            }
            const rect = el.getBoundingClientRect();
            const x = (event.clientX - rect.left) / rect.width - 0.5;
            const y = (event.clientY - rect.top) / rect.height - 0.5;
            el.style.transform = `perspective(900px) rotateX(${(-y * max).toFixed(2)}deg) rotateY(${(x * max).toFixed(2)}deg) translateY(-4px)`;
        };
        el._tiltLeave = () => {
            el.style.transform = '';
        };
        el.addEventListener('pointermove', el._tiltMove);
        el.addEventListener('pointerleave', el._tiltLeave);
    },
    unmounted(el) {
        el.removeEventListener('pointermove', el._tiltMove);
        el.removeEventListener('pointerleave', el._tiltLeave);
    },
    getSSRProps: () => ({}),
};

export default {
    install(app) {
        app.directive('reveal', reveal);
        app.directive('spotlight', spotlight);
        app.directive('tilt', tilt);
    },
};
