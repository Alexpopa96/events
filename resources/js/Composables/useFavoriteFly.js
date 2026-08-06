/**
 * Fly-to-favorites animation.
 * Flies a circular clone of the listing image (or a heart icon) from the
 * clicked source element to the favorites icon in the header, then bumps it.
 * Uses the Web Animations API directly — no Vue state or extra deps needed.
 */
const HEART_PATH = 'm11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z';

export function useFavoriteFly() {
    function fly(sourceEl, imageUrl = null, targetSelector = '.nav-favorites') {
        if (typeof document === 'undefined') {
            return;
        }

        const targetEl = document.querySelector(targetSelector);
        if (!targetEl || !sourceEl) {
            return;
        }

        const from = sourceEl.getBoundingClientRect();
        const to = targetEl.getBoundingClientRect();

        const fromX = from.left + from.width / 2;
        const fromY = from.top + from.height / 2;
        const toX = to.left + to.width / 2;
        const toY = to.top + to.height / 2;

        const SIZE = 46;
        const half = SIZE / 2;

        const el = document.createElement('div');
        Object.assign(el.style, {
            position: 'fixed',
            width: SIZE + 'px',
            height: SIZE + 'px',
            borderRadius: '50%',
            overflow: 'hidden',
            pointerEvents: 'none',
            zIndex: '99999',
            boxShadow: '0 6px 20px rgba(0,0,0,.22)',
            border: '2px solid #7C2E3B',
            background: '#fff',
            top: '0',
            left: '0',
            willChange: 'transform',
        });

        if (imageUrl) {
            const img = document.createElement('img');
            Object.assign(img.style, { width: '100%', height: '100%', objectFit: 'cover', display: 'block' });
            img.src = imageUrl;
            el.appendChild(img);
        } else {
            el.innerHTML = `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#F6E9EB;">
                <svg viewBox="0 0 24 24" fill="#7C2E3B" style="width:20px;height:20px;"><path d="${HEART_PATH}" /></svg>
            </div>`;
        }

        document.body.appendChild(el);

        const arcLift = Math.max(80, Math.abs(toY - fromY) * 0.45 + 60);
        const ctrlX = fromX + (toX - fromX) * 0.35;
        const ctrlY = Math.min(fromY, toY) - arcLift;

        const steps = 24;
        const kf = [];

        for (let i = 0; i <= steps; i++) {
            const t = i / steps;
            const mt = 1 - t;

            const x = mt * mt * fromX + 2 * mt * t * ctrlX + t * t * toX;
            const y = mt * mt * fromY + 2 * mt * t * ctrlY + t * t * toY;
            const sc = 1 - t * 0.78;
            const op = t > 0.85 ? 1 - ((t - 0.85) / 0.15) * 0.5 : 1;

            kf.push({
                transform: `translate(${x - half}px, ${y - half}px) scale(${sc})`,
                opacity: op,
                offset: t,
            });
        }

        const anim = el.animate(kf, {
            duration: 720,
            easing: 'ease-in',
            fill: 'forwards',
        });

        anim.onfinish = () => {
            el.remove();
            bumpTarget(targetEl);
        };
    }

    function bumpTarget(targetEl) {
        targetEl.animate(
            [
                { transform: 'scale(1)', offset: 0 },
                { transform: 'scale(1.45)', offset: 0.3 },
                { transform: 'scale(0.88)', offset: 0.65 },
                { transform: 'scale(1)', offset: 1 },
            ],
            { duration: 380, easing: 'cubic-bezier(.36,.07,.19,.97)' },
        );
    }

    return { fly };
}
