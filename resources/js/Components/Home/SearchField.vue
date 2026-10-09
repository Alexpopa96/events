<script setup>
import { computed, ref } from 'vue';
import { Combobox, ComboboxButton, ComboboxInput, ComboboxOptions, ComboboxOption, TransitionRoot } from '@headlessui/vue';
import { CheckIcon, ChevronDownIcon, XMarkIcon } from '@heroicons/vue/24/outline';

/* Searchable hero search field: the field itself is the input — focusing
   it opens the list, typing filters it (diacritics-insensitive). Options are
   `{ value, label, icon?, meta? }`; an empty string means "all". */
const props = defineProps({
    label: { type: String, required: true },
    placeholder: { type: String, required: true },
    icon: { type: [Object, Function], required: true },
    options: { type: Array, default: () => [] },
    emptyLabel: { type: String, default: 'Nu am găsit nimic' },
    columns: { type: Number, default: 1 },
});

const model = defineModel({ default: '' });

const query = ref('');

const selected = computed({
    get: () => (model.value === '' ? null : model.value),
    set: (value) => {
        model.value = value ?? '';
        query.value = '';
    },
});

const normalize = (text) => String(text).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

const filtered = computed(() => {
    const needle = normalize(query.value.trim());
    return needle ? props.options.filter((option) => normalize(option.label).includes(needle)) : props.options;
});

/* Headless UI 1.7.x accepts `immediate` but hard-codes it to false, so the
   list is opened by clicking the (chevron) ComboboxButton ourselves. */
const button = ref(null);

const openList = (open) => {
    if (!open) {
        button.value?.$el.click();
    }
};

const displayValue = (value) => props.options.find((option) => option.value === value)?.label ?? '';
</script>

<template>
    <Combobox v-slot="{ open }" v-model="selected" as="div" class="relative" nullable>
        <label class="group flex h-full cursor-text items-center gap-3 rounded-2xl px-4 py-2.5 transition-colors hover:bg-ivt-paper-2">
            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-ivt-paper-2 text-ivt-violet transition-colors group-focus-within:bg-brand group-focus-within:text-white group-hover:bg-white">
                <component :is="icon" class="h-[18px] w-[18px]" />
            </span>
            <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                <span class="text-[10.5px] font-bold uppercase tracking-[0.1em] text-ivt-ink-faint">{{ label }}</span>
                <ComboboxInput
                    :display-value="displayValue"
                    :placeholder="placeholder"
                    autocomplete="off"
                    class="w-full truncate border-0 bg-transparent p-0 text-[14.5px] font-semibold text-ivt-ink placeholder:font-semibold placeholder:text-ivt-ink focus:outline-none focus:ring-0 focus:placeholder:text-ivt-ink-faint"
                    @change="query = $event.target.value"
                    @focus="$event.target.select(); openList(open)"
                    @click="openList(open)"
                />
            </span>
            <button
                v-if="selected !== null"
                type="button"
                class="flex h-6 w-6 flex-none items-center justify-center rounded-full text-ivt-ink-faint transition-colors hover:bg-ivt-paper-3 hover:text-ivt-ink"
                :aria-label="`Șterge ${label.toLowerCase()}`"
                @click.prevent="selected = null"
            >
                <XMarkIcon class="h-3.5 w-3.5" />
            </button>
            <ComboboxButton
                ref="button"
                tabindex="-1"
                class="flex h-6 w-6 flex-none items-center justify-center rounded-full text-ivt-ink-faint transition-colors hover:bg-ivt-paper-3 hover:text-ivt-ink"
            >
                <ChevronDownIcon class="h-4 w-4 transition-transform duration-200" :class="open && 'rotate-180'" />
            </ComboboxButton>
        </label>

        <!-- as="template": a wrapper div would get the transform classes and
             form a stacking context below the next field while animating. No
             fade on enter either: the list opens over "Locație" on mobile. -->
        <TransitionRoot
            as="template"
            enter="transition duration-200 ease-out"
            enter-from="translate-y-1 scale-[0.98]"
            enter-to="translate-y-0 scale-100"
            leave="transition duration-100 ease-in"
            leave-from="opacity-100"
            leave-to="opacity-0"
            @after-leave="query = ''"
        >
            <ComboboxOptions
                class="absolute left-0 top-full z-30 mt-3 max-h-[340px] w-full origin-top-left overflow-y-auto overscroll-contain rounded-[20px] border border-ivt-line bg-white p-2 shadow-ivt-deep focus:outline-none sm:w-[380px]"
            >
                <ComboboxOption v-if="!query" v-slot="{ active }" :value="null" as="template">
                    <li class="mb-1 flex cursor-pointer items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors" :class="active ? 'bg-ivt-paper-2 text-ivt-ink' : 'text-ivt-ink-soft'">
                        {{ placeholder }}
                        <CheckIcon v-if="selected === null" class="h-4 w-4 text-primary" stroke-width="2.5" />
                    </li>
                </ComboboxOption>

                <div :class="columns > 1 && 'grid grid-cols-1 gap-1 sm:grid-cols-2'">
                    <ComboboxOption v-for="option in filtered" :key="option.value" v-slot="{ active, selected: isSelected }" :value="option.value" as="template">
                        <li
                            class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 transition-colors"
                            :class="[active && 'bg-ivt-paper-2', isSelected && 'bg-primary/[0.06]']"
                        >
                            <span
                                v-if="option.icon"
                                class="flex h-8 w-8 flex-none items-center justify-center rounded-lg transition-colors"
                                :class="active || isSelected ? 'bg-ivt-ink text-ivt-accent-bright' : 'bg-ivt-paper-2 text-primary'"
                            >
                                <component :is="option.icon" class="h-4 w-4" stroke-width="1.6" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold" :class="isSelected ? 'text-primary' : 'text-ivt-ink'">{{ option.label }}</span>
                                <span v-if="option.meta" class="block text-[11.5px] text-ivt-ink-faint">{{ option.meta }}</span>
                            </span>
                            <CheckIcon v-if="isSelected" class="h-4 w-4 flex-none text-primary" stroke-width="2.5" />
                        </li>
                    </ComboboxOption>
                </div>

                <p v-if="query && !filtered.length" class="px-3 py-6 text-center text-sm text-ivt-ink-faint">
                    {{ emptyLabel }} pentru „{{ query }}”.
                </p>
            </ComboboxOptions>
        </TransitionRoot>
    </Combobox>
</template>
