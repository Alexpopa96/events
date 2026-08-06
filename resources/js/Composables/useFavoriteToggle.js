import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useFavoriteFly } from '@/Composables/useFavoriteFly';

export function useFavoriteToggle(listingSlug, initial, onToggled, imageUrl = null) {
    const isFavorited = ref(initial);
    const toggling = ref(false);
    const { fly } = useFavoriteFly();

    const toggle = (event) => {
        if (toggling.value) {
            return;
        }

        const next = !isFavorited.value;
        const sourceEl = event?.currentTarget ?? null;
        isFavorited.value = next;
        toggling.value = true;

        router.post(route('listings.favorite', listingSlug), {}, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                if (next && sourceEl) {
                    fly(sourceEl, imageUrl);
                }
                onToggled?.(next);
            },
            onError: () => { isFavorited.value = !next; },
            onFinish: () => { toggling.value = false; },
        });
    };

    return { isFavorited, toggling, toggle };
}
