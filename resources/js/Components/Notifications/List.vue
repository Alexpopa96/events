<script setup>
import { router } from '@inertiajs/vue3';
import { BellIcon } from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';

defineProps({
    notifications: { type: Object, required: true },
});

const toneDot = {
    success: 'bg-emerald-500',
    danger: 'bg-red-500',
    info: 'bg-ivt-gold',
};

const open = (notification) => {
    router.post(route('notifications.read', notification.id));
};
</script>

<template>
    <div>
        <ul v-if="notifications.data.length" class="space-y-3">
            <li v-for="item in notifications.data" :key="item.id">
                <button
                    type="button"
                    class="flex w-full items-start gap-4 rounded-2xl border bg-white p-5 text-left transition-colors hover:border-ivt-wine"
                    :class="item.read ? 'border-ivt-line' : 'border-ivt-wine/30 bg-ivt-paper-2/60'"
                    @click="open(item)"
                >
                    <span class="mt-1.5 h-2.5 w-2.5 flex-none rounded-full" :class="item.read ? 'bg-ivt-line' : (toneDot[item.tone] ?? toneDot.info)" />
                    <span class="min-w-0 flex-1">
                        <span class="flex items-baseline justify-between gap-3">
                            <span class="text-[15px]" :class="item.read ? 'font-medium text-ivt-ink-soft' : 'font-semibold text-ivt-ink'">{{ item.title }}</span>
                            <span class="flex-none text-xs text-ivt-ink-faint">{{ item.created_at }}</span>
                        </span>
                        <span v-if="item.body" class="mt-1 block text-[14px] text-ivt-ink-soft">{{ item.body }}</span>
                    </span>
                </button>
            </li>
        </ul>

        <div v-else class="rounded-2xl border border-ivt-line bg-white px-6 py-20 text-center">
            <BellIcon class="mx-auto h-9 w-9 text-ivt-ink-faint" />
            <p class="mt-3 font-serif text-[22px] italic text-ivt-ink">Nicio notificare</p>
            <p class="mt-2 text-[14px] text-ivt-ink-faint">Vei vedea aici actualizările despre cererile și contul tău.</p>
        </div>

        <div class="mt-8">
            <Pagination :name="notifications" :links="notifications.links" v-if="notifications.total > notifications.per_page" />
        </div>
    </div>
</template>
