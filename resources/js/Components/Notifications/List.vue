<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ArrowRightIcon, BellIcon, BellSlashIcon, CheckCircleIcon, ExclamationTriangleIcon, SparklesIcon } from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    notifications: { type: Object, required: true },
});

const TONES = {
    success: { icon: CheckCircleIcon, tile: 'bg-success-100 text-success-600', bar: 'bg-success-500', label: 'Succes' },
    danger: { icon: ExclamationTriangleIcon, tile: 'bg-danger-50 text-danger-600', bar: 'bg-danger-500', label: 'Atenție' },
    info: { icon: SparklesIcon, tile: 'bg-ivt-paper-2 text-primary', bar: 'bg-brand', label: 'Info' },
};
const tone = (item) => TONES[item.tone] ?? TONES.info;

const filter = ref('all');
const unreadOnPage = computed(() => props.notifications.data.filter((item) => !item.read).length);
const tabs = computed(() => [
    { key: 'all', label: 'Toate', count: props.notifications.data.length },
    { key: 'unread', label: 'Necitite', count: unreadOnPage.value },
]);
const visible = computed(() => (filter.value === 'unread'
    ? props.notifications.data.filter((item) => !item.read)
    : props.notifications.data));

const open = (notification) => {
    router.post(route('notifications.read', notification.id));
};
</script>

<template>
    <div>
        <div v-if="notifications.data.length" class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-ivt-line pb-4">
            <h3 class="font-display text-[22px] font-medium text-ivt-ink">Notificările tale</h3>
            <div class="flex gap-1.5 rounded-full bg-ivt-paper-2 p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="flex items-center gap-1.5 rounded-full px-4 py-1.5 text-[13px] font-semibold transition-all"
                    :class="filter === tab.key ? 'bg-ivt-ink text-ivt-paper shadow-ivt-soft' : 'text-ivt-ink-soft hover:text-ivt-ink'"
                    @click="filter = tab.key"
                >
                    {{ tab.label }}
                    <span
                        class="rounded-full px-1.5 py-px text-[11px] tabular-nums"
                        :class="filter === tab.key ? 'bg-white/20 text-ivt-paper' : 'bg-white text-ivt-ink-soft'"
                    >{{ tab.count }}</span>
                </button>
            </div>
        </div>

        <TransitionGroup
            v-if="visible.length"
            tag="ul"
            class="space-y-3"
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-2 opacity-0"
        >
            <li v-for="item in visible" :key="item.id">
                <button
                    type="button"
                    class="group relative flex w-full items-start gap-4 overflow-hidden rounded-[22px] border-2 bg-white p-5 text-left transition-all duration-300 hover:-translate-y-0.5 sm:p-6"
                    :class="item.read
                        ? 'border-ivt-line hover:border-primary/20 hover:shadow-ivt-soft'
                        : 'border-primary/25 shadow-[0_16px_36px_-24px_rgba(124,58,237,0.45)] hover:border-primary/40 hover:shadow-glow-primary'"
                    @click="open(item)"
                >
                    <!-- Unread accent -->
                    <span v-if="!item.read" class="absolute inset-y-0 left-0 w-1.5" :class="tone(item).bar" />
                    <span
                        v-if="!item.read"
                        class="pointer-events-none absolute -right-16 -top-20 h-44 w-44 rounded-full"
                        style="background: radial-gradient(circle, rgba(124,58,237,0.10), transparent 70%);"
                    />

                    <span
                        class="relative flex h-12 w-12 flex-none items-center justify-center rounded-2xl transition-all duration-300 group-hover:rotate-[-6deg] group-hover:scale-105"
                        :class="item.read ? 'bg-ivt-paper-2 text-ivt-ink-faint' : tone(item).tile"
                    >
                        <component :is="tone(item).icon" class="h-6 w-6" stroke-width="1.5" />
                    </span>

                    <span class="relative min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span
                                v-if="!item.read"
                                class="rounded-full bg-ivt-ink px-2 py-0.5 text-[9.5px] font-extrabold uppercase tracking-[0.1em] text-ivt-accent-bright"
                            >Nou</span>
                            <span class="text-[11.5px] font-medium text-ivt-ink-faint">{{ item.created_at }}</span>
                        </span>
                        <span
                            class="mt-1 block text-[15.5px] leading-snug transition-colors group-hover:text-primary"
                            :class="item.read ? 'font-medium text-ivt-ink-soft' : 'font-semibold text-ivt-ink'"
                        >{{ item.title }}</span>
                        <span v-if="item.body" class="mt-1 line-clamp-2 block text-[13.5px] leading-relaxed text-ivt-ink-soft">{{ item.body }}</span>
                    </span>

                    <span
                        class="relative hidden flex-none items-center gap-1.5 self-center rounded-full bg-ivt-paper-2 py-2 pl-4 pr-3 text-[12.5px] font-semibold text-ivt-ink transition-all duration-300 group-hover:bg-ivt-ink group-hover:text-ivt-paper sm:inline-flex"
                    >
                        {{ item.url ? 'Deschide' : (item.read ? 'Citită' : 'Marchează citită') }}
                        <ArrowRightIcon class="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-0.5" stroke-width="2.5" />
                    </span>
                </button>
            </li>
        </TransitionGroup>

        <div v-else class="relative overflow-hidden rounded-[24px] border-2 border-dashed border-ivt-line bg-white px-6 py-20 text-center">
            <span
                class="pointer-events-none absolute left-1/2 top-0 h-56 w-56 -translate-x-1/2 -translate-y-1/2 rounded-full"
                style="background: radial-gradient(circle, rgba(124,58,237,0.12), transparent 70%);"
            />
            <span class="relative mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-ivt-paper-2 text-primary">
                <component :is="notifications.data.length ? BellIcon : BellSlashIcon" class="h-8 w-8" stroke-width="1.5" />
            </span>
            <p class="relative mt-4 font-display text-[22px] text-ivt-ink">
                {{ notifications.data.length ? 'Ești la zi cu toate' : 'Nicio notificare' }}
            </p>
            <p class="relative mt-2 text-[14px] text-ivt-ink-faint">
                {{ notifications.data.length ? 'Nu ai notificări necitite pe această pagină.' : 'Vei vedea aici actualizările despre cererile și contul tău.' }}
            </p>
        </div>

        <div class="mt-8">
            <Pagination :name="notifications" :links="notifications.links" v-if="notifications.total > notifications.per_page" />
        </div>
    </div>
</template>
