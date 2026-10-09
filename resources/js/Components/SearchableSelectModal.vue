<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { CheckIcon, MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, required: true },
    options: { type: Array, required: true },
    modelValue: { type: [Number, String], default: null },
    loading: { type: Boolean, default: false },
    searchPlaceholder: { type: String, default: 'Caută…' },
    // Short lists don't need a search box; the panel takes focus instead so the keyboard still works.
    searchable: { type: Boolean, default: true },
    // 'ivt' = public site palette (green ink / gold), 'brand' = provider dashboard palette.
    theme: { type: String, default: 'ivt' },
});

const emit = defineEmits(['close', 'select']);

const MAX_VISIBLE = 200;

const themes = {
    ivt: {
        backdrop: 'bg-ivt-ink/40',
        ink: 'text-ivt-ink',
        soft: 'text-ivt-ink-soft',
        close: 'bg-ivt-paper-2 text-ivt-ink-soft hover:bg-ivt-paper-3 hover:text-ivt-ink',
        input: 'bg-ivt-paper-2 text-ivt-ink placeholder:text-ivt-ink-faint focus:bg-white focus:ring-ivt-violet/50',
        active: 'bg-ivt-paper-2',
        mark: 'bg-ivt-accent-bright/25 text-ivt-ink',
        check: 'bg-ivt-violet text-white',
        footer: 'border-ivt-line bg-ivt-paper-2/60 text-ivt-ink-soft',
    },
    brand: {
        backdrop: 'bg-ivt-ink/40',
        ink: 'text-ivt-ink',
        soft: 'text-ivt-ink-soft',
        close: 'bg-ivt-paper-2 text-ivt-ink-soft hover:bg-ivt-paper-3 hover:text-ivt-ink',
        input: 'bg-ivt-paper-2/80 text-ivt-ink placeholder:text-ivt-ink-soft/70 focus:bg-white focus:ring-primary/60',
        active: 'bg-ivt-paper-2',
        mark: 'bg-primary/10 text-primary',
        check: 'bg-primary text-white',
        footer: 'border-ivt-line bg-ivt-paper-2/70 text-ivt-ink-soft',
    },
};

const t = computed(() => themes[props.theme] ?? themes.ivt);

const query = ref('');
const activeIndex = ref(0);
const searchInput = ref(null);
const panel = ref(null);
const listEl = ref(null);

// Diacritics-insensitive so "Iasi" finds "Iași" and "Brasov" finds "Brașov".
const normalize = (value) => String(value).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

const filtered = computed(() => {
    const needle = normalize(query.value.trim());
    if (!needle) return props.options;

    const startsWith = [];
    const contains = [];
    for (const option of props.options) {
        const index = normalize(option.name).indexOf(needle);
        if (index === 0) startsWith.push(option);
        else if (index > 0) contains.push(option);
    }
    return [...startsWith, ...contains];
});

const visible = computed(() => filtered.value.slice(0, MAX_VISIBLE));

// Splits a name into before/match/after so the typed text can be highlighted,
// mapping the match back onto the original (accented) string.
function highlight(name) {
    const needle = normalize(query.value.trim());
    if (!needle) return [{ text: name, match: false }];

    const start = normalize(name).indexOf(needle);
    if (start === -1) return [{ text: name, match: false }];

    return [
        { text: name.slice(0, start), match: false },
        { text: name.slice(start, start + needle.length), match: true },
        { text: name.slice(start + needle.length), match: false },
    ].filter((part) => part.text);
}

function scrollActiveIntoView() {
    nextTick(() => listEl.value?.querySelector('[data-active="true"]')?.scrollIntoView({ block: 'nearest' }));
}

function move(delta) {
    if (!visible.value.length) return;
    activeIndex.value = (activeIndex.value + delta + visible.value.length) % visible.value.length;
    scrollActiveIntoView();
}

function choose(option) {
    if (!option) return;
    emit('select', option.id);
}

function onKeydown(event) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        move(1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        move(-1);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        choose(visible.value[activeIndex.value]);
    } else if (event.key === 'Escape') {
        event.preventDefault();
        emit('close');
    }
}

watch(query, () => {
    activeIndex.value = 0;
    if (listEl.value) listEl.value.scrollTop = 0;
});

watch(() => props.show, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
    if (!open) return;

    query.value = '';
    const selectedIndex = props.options.findIndex((option) => option.id === props.modelValue);
    activeIndex.value = Math.max(selectedIndex, 0);
    nextTick(() => {
        (searchInput.value ?? panel.value)?.focus();
        scrollActiveIntoView();
    });
});

// The options can arrive after the modal opens (localities are fetched lazily).
watch(() => props.options, () => {
    if (!props.show) return;
    const selectedIndex = props.options.findIndex((option) => option.id === props.modelValue);
    activeIndex.value = Math.max(selectedIndex, 0);
    scrollActiveIntoView();
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[100] flex items-start justify-center px-4 pt-[7vh] sm:pt-[9vh]" @keydown="onKeydown">
                <div class="absolute inset-0 backdrop-blur-sm" :class="t.backdrop" @click="emit('close')" />

                <div
                    role="dialog"
                    aria-modal="true"
                    :aria-label="title"
                    ref="panel"
                    tabindex="-1"
                    class="modal-pop relative w-full max-w-md overflow-hidden rounded-3xl outline-none bg-white shadow-[0_40px_90px_-30px_rgba(26,20,51,0.55),0_0_0_1px_rgba(26,20,51,0.06)]"
                >
                    <div class="flex items-center gap-3 px-5 pt-5" :class="searchable ? '' : 'pb-3'">
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate text-[17px] font-semibold tracking-tight" :class="t.ink">{{ title }}</h2>
                        </div>
                        <button type="button" class="grid h-8 w-8 place-items-center rounded-full transition-colors" :class="t.close" aria-label="Închide" @click="emit('close')">
                            <XMarkIcon class="h-4 w-4" stroke-width="2" />
                        </button>
                    </div>

                    <div v-if="searchable" class="px-5 pb-3 pt-3.5">
                        <div class="relative">
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2" :class="t.soft" />
                            <input
                                ref="searchInput"
                                v-model="query"
                                type="text"
                                autocomplete="off"
                                spellcheck="false"
                                :placeholder="searchPlaceholder"
                                class="w-full rounded-2xl border-0 py-3 pl-11 pr-4 text-[15px] outline-none ring-1 ring-transparent transition-all focus:ring-2"
                                :class="t.input"
                            />
                        </div>
                    </div>

                    <ul ref="listEl" class="max-h-64 overflow-y-auto px-2.5 pb-2.5" role="listbox">
                        <li v-if="loading" class="px-4 py-8 text-center text-sm" :class="t.soft">Se încarcă…</li>
                        <li v-else-if="!visible.length" class="px-4 py-8 text-center text-sm" :class="t.soft">
                            Niciun rezultat pentru „{{ query }}”
                        </li>
                        <template v-else>
                            <li
                                v-for="(option, index) in visible"
                                :key="option.id"
                                role="option"
                                :aria-selected="option.id === modelValue"
                                :data-active="index === activeIndex"
                                class="flex cursor-pointer items-center justify-between gap-3 rounded-xl px-3.5 py-2.5 text-[14.5px] transition-colors duration-100"
                                :class="[
                                    t.ink,
                                    index === activeIndex ? t.active : '',
                                    option.id === modelValue ? 'font-semibold' : '',
                                ]"
                                @mousemove="activeIndex = index"
                                @click="choose(option)"
                            >
                                <span class="truncate">
                                    <template v-for="(part, partIndex) in highlight(option.name)" :key="partIndex">
                                        <span v-if="part.match" class="rounded-sm" :class="t.mark">{{ part.text }}</span>
                                        <template v-else>{{ part.text }}</template>
                                    </template>
                                </span>
                                <span v-if="option.id === modelValue" class="grid h-5 w-5 shrink-0 place-items-center rounded-full" :class="t.check">
                                    <CheckIcon class="h-3 w-3" stroke-width="3" />
                                </span>
                            </li>
                            <li v-if="filtered.length > MAX_VISIBLE" class="px-4 py-3 text-center text-xs" :class="t.soft">
                                Primele {{ MAX_VISIBLE }} din {{ filtered.length }} — continuă să scrii pentru a restrânge.
                            </li>
                        </template>
                    </ul>

                    <div class="hidden items-center justify-between border-t px-5 py-2.5 text-[11.5px] sm:flex" :class="t.footer">
                        <span class="flex items-center gap-3">
                            <span class="flex items-center gap-1"><kbd class="kbd">↑</kbd><kbd class="kbd">↓</kbd> navighează</span>
                            <span class="flex items-center gap-1"><kbd class="kbd">↵</kbd> alege</span>
                        </span>
                        <span class="flex items-center gap-1"><kbd class="kbd">esc</kbd> închide</span>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-pop {
    animation: modal-pop 220ms cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modal-pop {
    from {
        opacity: 0;
        transform: translateY(-14px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.kbd {
    min-width: 1.25rem;
    padding: 0.05rem 0.35rem;
    border-radius: 0.375rem;
    background: white;
    box-shadow: 0 0 0 1px rgba(26,20,51,0.12), 0 1px 0 rgba(26,20,51,0.08);
    text-align: center;
    font-family: inherit;
    font-size: 11px;
    line-height: 1.4;
}
</style>
