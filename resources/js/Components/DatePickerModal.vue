<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { CalendarDaysIcon, ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    id: { type: String, default: undefined },
    title: { type: String, default: 'Alege data' },
    placeholder: { type: String, default: 'Selectează data' },
    disabled: { type: Boolean, default: false },
    // Earliest selectable day, as a Date.
    minDate: { type: Date, required: true },
    // How many years past the minimum the year picker offers.
    yearSpan: { type: Number, default: 10 },
    buttonClass: { type: [String, Array, Object], default: '' },
});

// 'YYYY-MM-DD' string (or '') so it drops straight into the backend payload.
const model = defineModel({ type: String, default: '' });

const MONTHS = ['ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'];
const WEEKDAYS = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

const pad = (n) => String(n).padStart(2, '0');
const toIso = (y, m, d) => `${y}-${pad(m + 1)}-${pad(d)}`;
const dateToIso = (date) => toIso(date.getFullYear(), date.getMonth(), date.getDate());

const minIso = computed(() => dateToIso(props.minDate));
const minYear = computed(() => props.minDate.getFullYear());
const maxYear = computed(() => minYear.value + props.yearSpan);
const todayIso = dateToIso(new Date());

const open = ref(false);
const mode = ref('days'); // 'days' | 'months' | 'years'
const viewYear = ref(minYear.value);
const viewMonth = ref(props.minDate.getMonth());
const panel = ref(null);
const yearsEl = ref(null);

const label = computed(() => {
    if (!model.value) return '';
    const [y, m, d] = model.value.split('-').map(Number);
    return `${d} ${MONTHS[m - 1]} ${y}`;
});

const years = computed(() => Array.from({ length: props.yearSpan + 1 }, (_, i) => minYear.value + i));

const cells = computed(() => {
    const offset = (new Date(viewYear.value, viewMonth.value, 1).getDay() + 6) % 7; // Monday first
    const daysInMonth = new Date(viewYear.value, viewMonth.value + 1, 0).getDate();
    const result = Array.from({ length: offset }, () => null);
    for (let day = 1; day <= daysInMonth; day++) {
        const iso = toIso(viewYear.value, viewMonth.value, day);
        result.push({ day, iso, disabled: iso < minIso.value });
    }
    return result;
});

const monthDisabled = (index) => viewYear.value === minYear.value && index < props.minDate.getMonth();
const canGoPrev = computed(() => viewYear.value > minYear.value || viewMonth.value > props.minDate.getMonth());
const canGoNext = computed(() => viewYear.value < maxYear.value || viewMonth.value < 11);

function shiftMonth(delta) {
    const date = new Date(viewYear.value, viewMonth.value + delta, 1);
    if (date.getFullYear() < minYear.value || date.getFullYear() > maxYear.value) return;
    viewYear.value = date.getFullYear();
    viewMonth.value = date.getMonth();
}

function pickMonth(index) {
    if (monthDisabled(index)) return;
    viewMonth.value = index;
    mode.value = 'days';
}

function pickYear(year) {
    viewYear.value = year;
    // Keep the month valid if the user jumps back to the minimum year.
    if (year === minYear.value && viewMonth.value < props.minDate.getMonth()) viewMonth.value = props.minDate.getMonth();
    mode.value = 'months';
}

function pickDay(cell) {
    if (!cell || cell.disabled) return;
    model.value = cell.iso;
    open.value = false;
}

function openPicker() {
    if (props.disabled) return;
    const [y, m] = (model.value || minIso.value).split('-').map(Number);
    viewYear.value = y;
    viewMonth.value = m - 1;
    mode.value = 'days';
    open.value = true;
}

function clear() {
    model.value = '';
    open.value = false;
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        event.preventDefault();
        if (mode.value === 'days') open.value = false;
        else mode.value = 'days';
    }
}

watch(open, (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : '';
    if (isOpen) nextTick(() => panel.value?.focus());
});

watch(mode, (value) => {
    if (value !== 'years') return;
    nextTick(() => yearsEl.value?.querySelector('[data-selected="true"]')?.scrollIntoView({ block: 'center' }));
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <div class="relative">
        <CalendarDaysIcon class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-ivt-wine" />
        <button
            :id="id"
            type="button"
            aria-haspopup="dialog"
            :disabled="disabled"
            class="flex w-full items-center justify-between gap-2 pl-11 text-left disabled:cursor-not-allowed disabled:opacity-50"
            :class="buttonClass"
            @click="openPicker"
        >
            <span class="truncate" :class="label && !disabled ? '' : 'text-ivt-ink-faint'">{{ disabled ? placeholder : (label || placeholder) }}</span>
            <ChevronDownIcon class="h-4 w-4 shrink-0 text-ivt-ink-faint" />
        </button>
    </div>

    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-[100] flex items-start justify-center px-4 pt-[7vh] sm:pt-[9vh]" @keydown="onKeydown">
                <div class="absolute inset-0 bg-ivt-ink/40 backdrop-blur-sm" @click="open = false" />

                <div
                    ref="panel"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="title"
                    tabindex="-1"
                    class="date-pop relative w-full max-w-sm overflow-hidden rounded-3xl bg-white shadow-[0_40px_90px_-30px_rgba(33,28,39,0.55),0_0_0_1px_rgba(33,28,39,0.06)] outline-none"
                >
                    <div class="flex items-center justify-between gap-3 px-5 pt-5">
                        <h2 class="text-[17px] font-semibold tracking-tight text-ivt-ink">{{ title }}</h2>
                        <button type="button" class="grid h-8 w-8 place-items-center rounded-full bg-ivt-paper-2 text-ivt-ink-soft transition-colors hover:bg-ivt-paper-3 hover:text-ivt-ink" aria-label="Închide" @click="open = false">
                            <XMarkIcon class="h-4 w-4" stroke-width="2" />
                        </button>
                    </div>

                    <!-- Month / year switcher: both are real buttons so the year is one tap away -->
                    <div class="flex items-center justify-between gap-2 px-4 pb-2 pt-4">
                        <button type="button" :disabled="mode !== 'days' || !canGoPrev" class="grid h-9 w-9 place-items-center rounded-full text-ivt-ink transition-colors hover:bg-ivt-paper-2 disabled:opacity-30 disabled:hover:bg-transparent" aria-label="Luna precedentă" @click="shiftMonth(-1)">
                            <ChevronLeftIcon class="h-4 w-4" stroke-width="2" />
                        </button>

                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-[14.5px] font-semibold capitalize transition-colors"
                                :class="mode === 'months' ? 'bg-ivt-ink text-white' : 'bg-ivt-paper-2 text-ivt-ink hover:bg-ivt-paper-3'"
                                @click="mode = mode === 'months' ? 'days' : 'months'"
                            >
                                {{ MONTHS[viewMonth] }}
                                <ChevronDownIcon class="h-3.5 w-3.5 transition-transform" :class="mode === 'months' ? 'rotate-180' : ''" stroke-width="2.5" />
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-[14.5px] font-semibold transition-colors"
                                :class="mode === 'years' ? 'bg-ivt-ink text-white' : 'bg-ivt-paper-2 text-ivt-ink hover:bg-ivt-paper-3'"
                                @click="mode = mode === 'years' ? 'days' : 'years'"
                            >
                                {{ viewYear }}
                                <ChevronDownIcon class="h-3.5 w-3.5 transition-transform" :class="mode === 'years' ? 'rotate-180' : ''" stroke-width="2.5" />
                            </button>
                        </div>

                        <button type="button" :disabled="mode !== 'days' || !canGoNext" class="grid h-9 w-9 place-items-center rounded-full text-ivt-ink transition-colors hover:bg-ivt-paper-2 disabled:opacity-30 disabled:hover:bg-transparent" aria-label="Luna următoare" @click="shiftMonth(1)">
                            <ChevronRightIcon class="h-4 w-4" stroke-width="2" />
                        </button>
                    </div>

                    <div class="px-4 pb-4">
                        <!-- Days -->
                        <template v-if="mode === 'days'">
                            <div class="grid grid-cols-7 pb-1 text-center text-[11.5px] font-bold uppercase tracking-wide text-ivt-ink-faint">
                                <span v-for="(weekday, index) in WEEKDAYS" :key="index" class="py-1.5">{{ weekday }}</span>
                            </div>
                            <div class="grid grid-cols-7 gap-y-0.5">
                                <template v-for="(cell, index) in cells" :key="index">
                                    <span v-if="!cell" />
                                    <button
                                        v-else
                                        type="button"
                                        :disabled="cell.disabled"
                                        class="mx-auto grid h-10 w-10 place-items-center rounded-full text-[14px] transition-colors"
                                        :class="[
                                            cell.iso === model ? 'bg-ivt-gold font-semibold text-white shadow-sm' : 'text-ivt-ink hover:bg-ivt-paper-2',
                                            cell.iso === todayIso && cell.iso !== model ? 'ring-1 ring-inset ring-ivt-gold/60' : '',
                                            cell.disabled ? 'cursor-not-allowed text-ivt-ink-faint/50 hover:bg-transparent' : '',
                                        ]"
                                        @click="pickDay(cell)"
                                    >
                                        {{ cell.day }}
                                    </button>
                                </template>
                            </div>
                        </template>

                        <!-- Months -->
                        <div v-else-if="mode === 'months'" class="grid grid-cols-3 gap-2 pt-1">
                            <button
                                v-for="(month, index) in MONTHS"
                                :key="month"
                                type="button"
                                :disabled="monthDisabled(index)"
                                class="rounded-xl py-3 text-[14px] capitalize transition-colors"
                                :class="[
                                    index === viewMonth ? 'bg-ivt-gold font-semibold text-white' : 'text-ivt-ink hover:bg-ivt-paper-2',
                                    monthDisabled(index) ? 'cursor-not-allowed text-ivt-ink-faint/50 hover:bg-transparent' : '',
                                ]"
                                @click="pickMonth(index)"
                            >
                                {{ month.slice(0, 3) }}
                            </button>
                        </div>

                        <!-- Years -->
                        <div v-else ref="yearsEl" class="grid max-h-60 grid-cols-3 gap-2 overflow-y-auto pt-1">
                            <button
                                v-for="year in years"
                                :key="year"
                                type="button"
                                :data-selected="year === viewYear"
                                class="rounded-xl py-3 text-[14px] transition-colors"
                                :class="year === viewYear ? 'bg-ivt-gold font-semibold text-white' : 'text-ivt-ink hover:bg-ivt-paper-2'"
                                @click="pickYear(year)"
                            >
                                {{ year }}
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-ivt-line bg-ivt-paper-2/60 px-5 py-2.5 text-[12.5px]">
                        <button type="button" class="font-semibold text-ivt-ink-soft transition-colors hover:text-ivt-wine disabled:opacity-40 disabled:hover:text-ivt-ink-soft" :disabled="!model" @click="clear">Șterge data</button>
                        <span class="text-ivt-ink-faint">{{ label || 'Nicio dată aleasă' }}</span>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.date-pop {
    animation: date-pop 220ms cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes date-pop {
    from {
        opacity: 0;
        transform: translateY(-14px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>
