<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ChevronLeftIcon, ChevronRightIcon, XMarkIcon, BriefcaseIcon } from '@heroicons/vue/24/outline';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    blocks: Array,
});

const toast = useToast();

const MONTHS = ['ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'];
const WEEKDAYS = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

const pad = (n) => String(n).padStart(2, '0');
const toIso = (y, m, d) => `${y}-${pad(m + 1)}-${pad(d)}`;

const today = new Date();
today.setHours(0, 0, 0, 0);
const todayIso = toIso(today.getFullYear(), today.getMonth(), today.getDate());

const viewYear = ref(today.getFullYear());
const viewMonth = ref(today.getMonth());

const blockByDate = computed(() => {
    const map = new Map();
    props.blocks.forEach((block) => map.set(block.date, block));
    return map;
});

// Confirmed bookings (from accepted offers) coming up, regardless of which month is shown.
const upcomingBookings = computed(() => props.blocks
    .filter((block) => block.source === 'offer' && block.date >= todayIso)
    .slice(0, 10));

const weeks = computed(() => {
    const first = new Date(viewYear.value, viewMonth.value, 1);
    // Monday-first grid: how many cells to pad before day 1.
    const lead = (first.getDay() + 6) % 7;
    const daysInMonth = new Date(viewYear.value, viewMonth.value + 1, 0).getDate();

    const cells = [];
    for (let i = 0; i < lead; i++) cells.push(null);
    for (let d = 1; d <= daysInMonth; d++) {
        const iso = toIso(viewYear.value, viewMonth.value, d);
        cells.push({ day: d, iso, block: blockByDate.value.get(iso) ?? null, past: iso < todayIso });
    }
    while (cells.length % 7 !== 0) cells.push(null);

    const rows = [];
    for (let i = 0; i < cells.length; i += 7) rows.push(cells.slice(i, i + 7));
    return rows;
});

const prevMonth = () => {
    viewMonth.value -= 1;
    if (viewMonth.value < 0) { viewMonth.value = 11; viewYear.value -= 1; }
};
const nextMonth = () => {
    viewMonth.value += 1;
    if (viewMonth.value > 11) { viewMonth.value = 0; viewYear.value += 1; }
};

const canGoPrev = computed(() => viewYear.value > today.getFullYear() || viewMonth.value > today.getMonth());

/* ---------- selection + blocking ---------- */
const selected = ref(new Set());
const busy = ref(false);

const toggleDay = (cell) => {
    if (!cell || cell.past) return;
    if (cell.block) return; // Already blocked — remove it via the trash icon instead.

    const next = new Set(selected.value);
    next.has(cell.iso) ? next.delete(cell.iso) : next.add(cell.iso);
    selected.value = next;
};

const note = ref('');

const blockSelected = () => {
    if (!selected.value.size || busy.value) return;
    busy.value = true;
    router.post(route('provider.availability.store'), { dates: [...selected.value], note: note.value.trim() || null }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(selected.value.size > 1 ? 'Zilele au fost blocate.' : 'Ziua a fost blocată.');
            selected.value = new Set();
            note.value = '';
        },
        onFinish: () => { busy.value = false; },
    });
};

/* ---------- unblocking ---------- */
const blockToRemove = ref(null);
const removing = ref(false);

const removeBlock = () => {
    removing.value = true;
    router.delete(route('provider.availability.destroy', blockToRemove.value.id), {
        preserveScroll: true,
        onSuccess: () => toast.success('Ziua a fost deblocată.'),
        onFinish: () => { removing.value = false; blockToRemove.value = null; },
    });
};

const confirmDialogOpen = computed({
    get: () => !!blockToRemove.value,
    set: (open) => { if (!open) blockToRemove.value = null; },
});

const dialogMessage = computed(() => blockToRemove.value?.source === 'offer'
    ? 'Această zi e ocupată de o ofertă acceptată. Deblocând-o, calendarul nu va mai reflecta acea rezervare.'
    : 'Clienții vor putea din nou să te vadă disponibil în această zi.');
</script>

<template>
    <ProviderLayout title="Calendar disponibilitate">
        <div class="grid gap-5 lg:grid-cols-[1fr_20rem]">
            <div class="rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
                <div class="mb-4 flex items-center justify-between">
                    <p class="font-serif text-lg capitalize text-ivt-ink">{{ MONTHS[viewMonth] }} {{ viewYear }}</p>
                    <div class="flex gap-1">
                        <button
                            type="button"
                            :disabled="!canGoPrev"
                            @click="prevMonth"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-ivt-ink-soft transition-colors hover:bg-ivt-paper-2 disabled:cursor-not-allowed disabled:opacity-30"
                        >
                            <ChevronLeftIcon class="h-4 w-4" />
                        </button>
                        <button type="button" @click="nextMonth" class="flex h-8 w-8 items-center justify-center rounded-full text-ivt-ink-soft transition-colors hover:bg-ivt-paper-2">
                            <ChevronRightIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold text-ivt-ink-soft">
                    <span v-for="(d, i) in WEEKDAYS" :key="i">{{ d }}</span>
                </div>

                <div class="mt-1 space-y-1">
                    <div v-for="(row, ri) in weeks" :key="ri" class="grid grid-cols-7 gap-1">
                        <template v-for="(cell, ci) in row" :key="ci">
                            <div v-if="!cell" class="aspect-square" />
                            <button
                                v-else
                                type="button"
                                :disabled="cell.past"
                                @click="cell.block ? (blockToRemove = cell.block) : toggleDay(cell)"
                                class="relative flex aspect-square flex-col items-center justify-center rounded-xl text-sm font-medium transition-colors duration-100"
                                :class="[
                                    cell.past ? 'cursor-not-allowed text-ivt-ink-soft/30' :
                                    cell.block?.source === 'offer' ? 'bg-primary text-white' :
                                    cell.block ? 'bg-rose-100 text-rose-700 hover:bg-rose-200' :
                                    selected.has(cell.iso) ? 'bg-ivt-gold/20 text-ivt-ink ring-2 ring-ivt-gold' :
                                    'text-ivt-ink hover:bg-ivt-paper-2',
                                    cell.iso === todayIso ? 'font-bold' : '',
                                ]"
                                :title="cell.block?.note ?? undefined"
                            >
                                {{ cell.day }}
                                <XMarkIcon v-if="cell.block && !cell.past" class="absolute bottom-0.5 h-2.5 w-2.5 opacity-70" />
                            </button>
                        </template>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-4 text-xs text-ivt-ink-soft">
                    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-full bg-primary" /> Rezervare confirmată</span>
                    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-full bg-rose-200" /> Blocată manual</span>
                    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-full bg-ivt-gold/30 ring-1 ring-ivt-gold" /> Selectată</span>
                </div>

                <div v-if="selected.size" class="mt-5 rounded-xl bg-ivt-paper-2/60 p-4">
                    <p class="text-sm font-medium text-ivt-ink">{{ selected.size }} {{ selected.size === 1 ? 'zi selectată' : 'zile selectate' }}</p>
                    <input
                        v-model="note"
                        type="text"
                        maxlength="150"
                        placeholder="Motiv (opțional) — ex. „Concediu”"
                        class="mt-2.5 w-full rounded-xl border-ivt-line text-sm text-ivt-ink placeholder:text-ivt-ink-soft/50 focus:border-primary focus:ring-primary"
                    />
                    <div class="mt-3 flex gap-2">
                        <button
                            type="button"
                            :disabled="busy"
                            @click="blockSelected"
                            class="rounded-full bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm shadow-primary/25 transition-colors hover:bg-primary-bright disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Blochează
                        </button>
                        <button type="button" class="rounded-full px-4 py-2 text-sm font-medium text-ivt-ink-soft hover:bg-white" @click="selected = new Set(); note = '';">
                            Renunță
                        </button>
                    </div>
                </div>
                <p v-else class="mt-5 text-xs text-ivt-ink-soft">Selectează una sau mai multe zile ca să le blochezi. O zi deja blocată se eliberează dacă o apeși.</p>
            </div>

            <div class="rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
                <p class="text-sm font-semibold text-ivt-ink">Rezervări confirmate</p>
                <p class="mt-1 text-xs text-ivt-ink-soft">Create automat când accepți o ofertă cu dată de eveniment.</p>

                <div v-if="!upcomingBookings.length" class="mt-5 flex flex-col items-center py-6 text-center">
                    <BriefcaseIcon class="h-6 w-6 text-ivt-ink-soft/40" />
                    <p class="mt-2 text-xs text-ivt-ink-soft">Nicio rezervare confirmată momentan.</p>
                </div>

                <ul v-else class="mt-4 space-y-3">
                    <li v-for="block in upcomingBookings" :key="block.id" class="flex items-start justify-between gap-2 border-b border-ivt-line pb-3 last:border-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-ivt-ink">{{ new Date(block.date).toLocaleDateString('ro-RO', { day: 'numeric', month: 'short', year: 'numeric' }) }}</p>
                            <p class="mt-0.5 truncate text-xs text-ivt-ink-soft">{{ block.lead_title ?? block.note ?? '—' }}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <ConfirmDialog
            v-model:show="confirmDialogOpen"
            title="Deblochezi această zi?"
            :message="dialogMessage"
            confirm-label="Deblochează"
            :processing="removing"
            @confirm="removeBlock"
        />
    </ProviderLayout>
</template>
