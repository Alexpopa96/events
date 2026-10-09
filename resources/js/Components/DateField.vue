<script setup>
import { computed, ref, watch } from 'vue';
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue';
import { CalendarDaysIcon, ChevronLeftIcon, ChevronRightIcon, XMarkIcon } from '@heroicons/vue/24/outline';

/* Search-bar date field: a calendar popover styled like SearchField. The model
   is a local `YYYY-MM-DD` string ('' when empty); past days can't be picked. */
const props = defineProps({
    label: { type: String, required: true },
    placeholder: { type: String, default: 'Alege data' },
});

const model = defineModel({ type: String, default: '' });
const emit = defineEmits(['change']);

const WEEKDAYS = ['L', 'Ma', 'Mi', 'J', 'V', 'S', 'D'];

const pad = (value) => String(value).padStart(2, '0');
const toKey = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
const fromKey = (key) => {
    const [year, month, day] = key.split('-').map(Number);
    return new Date(year, month - 1, day);
};

const today = new Date();
today.setHours(0, 0, 0, 0);
const todayKey = toKey(today);

const selected = computed(() => (model.value ? fromKey(model.value) : null));

// First day of the month currently shown in the calendar.
const cursor = ref(new Date((selected.value ?? today).getFullYear(), (selected.value ?? today).getMonth(), 1));
watch(selected, (date) => date && (cursor.value = new Date(date.getFullYear(), date.getMonth(), 1)));

const monthLabel = computed(() => {
    const text = cursor.value.toLocaleDateString('ro-RO', { month: 'long', year: 'numeric' });
    return text.charAt(0).toUpperCase() + text.slice(1);
});

const canGoBack = computed(() => cursor.value > new Date(today.getFullYear(), today.getMonth(), 1));

const shiftMonth = (step) => {
    cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() + step, 1);
};

// 6×7 grid, Monday-first, padded with the neighbouring months' days.
const days = computed(() => {
    const first = cursor.value;
    const offset = (first.getDay() + 6) % 7;
    return Array.from({ length: 42 }, (_, index) => {
        const date = new Date(first.getFullYear(), first.getMonth(), index - offset + 1);
        const key = toKey(date);
        return {
            key,
            day: date.getDate(),
            inMonth: date.getMonth() === first.getMonth(),
            past: date < today,
            today: key === todayKey,
            selected: key === model.value,
            weekend: date.getDay() === 0 || date.getDay() === 6,
        };
    });
});

const nextSaturday = (weeksAhead = 0) => {
    const date = new Date(today);
    date.setDate(date.getDate() + ((6 - date.getDay() + 7) % 7) + weeksAhead * 7);
    return date;
};

const shortcuts = computed(() => [
    { label: 'Sâmbăta asta', date: nextSaturday(0) },
    { label: 'Sâmbăta viitoare', date: nextSaturday(1) },
    { label: 'Peste o lună', date: new Date(today.getFullYear(), today.getMonth() + 1, today.getDate()) },
].map((shortcut) => ({ ...shortcut, key: toKey(shortcut.date) })));

const pick = (key, close) => {
    model.value = key;
    emit('change', key);
    close?.();
};

const clear = () => pick('');

const display = computed(() =>
    selected.value
        ? selected.value.toLocaleDateString('ro-RO', { weekday: 'short', day: 'numeric', month: 'long' })
        : ''
);
</script>

<template>
    <Popover v-slot="{ open, close }" class="relative">
        <div class="group flex h-full items-center gap-3 rounded-2xl px-4 py-2.5 transition-colors hover:bg-ivt-paper-2" :class="open && 'bg-ivt-paper-2'">
            <PopoverButton class="flex min-w-0 flex-1 items-center gap-3 text-left focus:outline-none">
                <span
                    class="flex h-9 w-9 flex-none items-center justify-center rounded-xl transition-colors"
                    :class="open ? 'bg-ivt-ink text-ivt-accent-bright' : 'bg-ivt-paper-2 text-ivt-violet group-hover:bg-white'"
                >
                    <CalendarDaysIcon class="h-[18px] w-[18px]" />
                </span>
                <span class="flex min-w-0 flex-col gap-0.5">
                    <span class="text-[10.5px] font-bold uppercase tracking-[0.1em] text-ivt-ink-faint">{{ label }}</span>
                    <span class="truncate text-[14.5px] font-semibold text-ivt-ink">
                        {{ display || placeholder }}
                    </span>
                </span>
            </PopoverButton>
            <button
                v-if="model"
                type="button"
                class="flex h-6 w-6 flex-none items-center justify-center rounded-full text-ivt-ink-faint transition-colors hover:bg-ivt-paper-3 hover:text-ivt-ink"
                :aria-label="`Șterge ${label.toLowerCase()}`"
                @click="clear"
            >
                <XMarkIcon class="h-3.5 w-3.5" />
            </button>
        </div>

        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-1 scale-[0.98]"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <PopoverPanel
                class="absolute left-0 top-full z-30 mt-3 w-[320px] origin-top-left rounded-[20px] border border-ivt-line bg-white/95 p-4 font-invita text-ivt-ink shadow-ivt-deep backdrop-blur-xl sm:left-auto sm:right-0 sm:origin-top-right"
            >
                <div class="flex items-center justify-between">
                    <button
                        type="button"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-ivt-ink-soft transition-colors hover:bg-ivt-paper-2 hover:text-ivt-ink disabled:pointer-events-none disabled:opacity-30"
                        :disabled="!canGoBack"
                        aria-label="Luna anterioară"
                        @click="shiftMonth(-1)"
                    >
                        <ChevronLeftIcon class="h-4 w-4" stroke-width="2.5" />
                    </button>
                    <p class="font-display text-[17px] tracking-tight">{{ monthLabel }}</p>
                    <button
                        type="button"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-ivt-ink-soft transition-colors hover:bg-ivt-paper-2 hover:text-ivt-ink"
                        aria-label="Luna următoare"
                        @click="shiftMonth(1)"
                    >
                        <ChevronRightIcon class="h-4 w-4" stroke-width="2.5" />
                    </button>
                </div>

                <div class="mt-3 grid grid-cols-7 text-center text-[10.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-faint">
                    <span v-for="weekday in WEEKDAYS" :key="weekday" class="py-1.5">{{ weekday }}</span>
                </div>

                <div class="grid grid-cols-7 gap-y-1">
                    <button
                        v-for="day in days"
                        :key="day.key"
                        type="button"
                        :disabled="day.past"
                        :aria-pressed="day.selected"
                        :aria-label="day.key"
                        class="relative mx-auto flex h-9 w-9 items-center justify-center rounded-full text-[13px] font-semibold tabular-nums transition-colors"
                        :class="[
                            day.selected
                                ? 'bg-ivt-ink text-ivt-accent-bright shadow-ivt-soft'
                                : day.past
                                    ? 'cursor-not-allowed text-ivt-ink-faint/40'
                                    : 'hover:bg-ivt-paper-2',
                            !day.selected && !day.past && (day.inMonth ? (day.weekend ? 'text-primary' : 'text-ivt-ink') : 'text-ivt-ink-faint'),
                        ]"
                        @click="pick(day.key, close)"
                    >
                        {{ day.day }}
                        <span v-if="day.today && !day.selected" class="absolute bottom-1 h-1 w-1 rounded-full bg-ivt-violet" />
                    </button>
                </div>

                <div class="mt-4 flex flex-wrap gap-1.5 border-t border-ivt-line pt-3">
                    <button
                        v-for="shortcut in shortcuts"
                        :key="shortcut.label"
                        type="button"
                        class="rounded-full border px-3 py-1 text-[12px] font-semibold transition-colors"
                        :class="model === shortcut.key
                            ? 'border-transparent bg-ivt-ink text-ivt-accent-bright'
                            : 'border-ivt-line text-ivt-ink-soft hover:border-primary/30 hover:text-primary'"
                        @click="pick(shortcut.key, close)"
                    >{{ shortcut.label }}</button>
                </div>
            </PopoverPanel>
        </transition>
    </Popover>
</template>
