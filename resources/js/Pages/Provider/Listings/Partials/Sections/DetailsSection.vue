<script setup>
import { computed } from 'vue';
import { CheckIcon } from '@heroicons/vue/24/solid';
import { TagIcon } from '@heroicons/vue/24/outline';
import { categoryIcon } from '@/Composables/useCategoryIcon';
import { fieldClass, fieldErrorClass } from '@/Composables/useFieldClasses';
import Field from '@/Components/Provider/Field.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';

const props = defineProps({
    form: Object,
    categories: Array,
    eventTypes: { type: Array, default: () => [] },
    heading: { type: Boolean, default: true },
    bordered: { type: Boolean, default: false },
});

const childrenOf = (parentId) => props.categories.filter((c) => c.parent_id === parentId);
const parentCategories = computed(() => props.categories.filter((c) => !c.parent_id));
const groupedParents = computed(() => parentCategories.value.filter((p) => childrenOf(p.id).length > 0));
const standaloneCategories = computed(() => parentCategories.value.filter((p) => childrenOf(p.id).length === 0));

const sectionHeadingClass = 'flex items-center gap-2 text-sm font-semibold text-ivt-ink mb-5';
const iconWrapClass = 'w-7 h-7 rounded-full bg-ivt-paper-2 text-primary flex items-center justify-center flex-none';

const selected = (category) => props.form.category_id === category.id;

const eventTypeSelected = (value) => props.form.event_types.includes(value);
const toggleEventType = (value) => {
    props.form.event_types = eventTypeSelected(value)
        ? props.form.event_types.filter((type) => type !== value)
        : [...props.form.event_types, value];
};
</script>

<template>
    <section :class="bordered && 'pt-6 border-t border-ivt-line'">
        <h3 v-if="heading" :class="sectionHeadingClass">
            <span :class="iconWrapClass"><TagIcon class="w-4 h-4" /></span>
            Detalii principale
        </h3>

        <div class="space-y-6">
            <Field label="Titlu anunț" for="listing-title" :error="form.errors.title" :count="form.title.length" :max="255" hint="Un titlu clar, cu serviciul și ce îl face special, atrage mai multe cereri.">
                <input
                    id="listing-title"
                    v-model="form.title"
                    type="text"
                    maxlength="255"
                    placeholder="Ex: Pachet foto nuntă — o zi completă"
                    :class="[fieldClass, form.errors.title && fieldErrorClass]"
                />
            </Field>

            <Field label="Categorie" :error="form.errors.category_id">
                <div class="space-y-4">
                    <div v-for="parent in groupedParents" :key="parent.id">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ivt-ink-soft/70">{{ parent.name }}</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="category in childrenOf(parent.id)"
                                :key="category.id"
                                type="button"
                                @click="form.category_id = category.id"
                                :aria-pressed="selected(category)"
                                class="inline-flex items-center gap-2 rounded-full border-2 px-3.5 py-2 text-sm transition-all duration-150"
                                :class="selected(category)
                                    ? 'border-primary bg-primary text-white shadow-sm shadow-primary/25'
                                    : 'border-transparent bg-ivt-paper-2 text-ivt-ink-soft hover:bg-ivt-paper-3/70 hover:text-ivt-ink'"
                            >
                                <CheckIcon v-if="selected(category)" class="h-4 w-4 flex-none" />
                                <component v-else :is="categoryIcon(category.slug)" class="h-4 w-4 flex-none" />
                                <span class="truncate">{{ category.name }}</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="standaloneCategories.length">
                        <p v-if="groupedParents.length" class="mb-2 text-xs font-semibold uppercase tracking-wide text-ivt-ink-soft/70">Alte categorii</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="category in standaloneCategories"
                                :key="category.id"
                                type="button"
                                @click="form.category_id = category.id"
                                :aria-pressed="selected(category)"
                                class="inline-flex items-center gap-2 rounded-full border-2 px-3.5 py-2 text-sm transition-all duration-150"
                                :class="selected(category)
                                    ? 'border-primary bg-primary text-white shadow-sm shadow-primary/25'
                                    : 'border-transparent bg-ivt-paper-2 text-ivt-ink-soft hover:bg-ivt-paper-3/70 hover:text-ivt-ink'"
                            >
                                <CheckIcon v-if="selected(category)" class="h-4 w-4 flex-none" />
                                <component v-else :is="categoryIcon(category.slug)" class="h-4 w-4 flex-none" />
                                <span class="truncate">{{ category.name }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Field>

            <Field v-if="eventTypes.length" label="Tipuri de evenimente" :error="form.errors.event_types" optional hint="Alege evenimentele la care oferi serviciul. Dacă nu alegi nimic, anunțul apare la toate tipurile de evenimente.">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="type in eventTypes"
                        :key="type.value"
                        type="button"
                        @click="toggleEventType(type.value)"
                        :aria-pressed="eventTypeSelected(type.value)"
                        class="inline-flex items-center gap-2 rounded-full border-2 px-3.5 py-2 text-sm transition-all duration-150"
                        :class="eventTypeSelected(type.value)
                            ? 'border-primary bg-primary text-white shadow-sm shadow-primary/25'
                            : 'border-transparent bg-ivt-paper-2 text-ivt-ink-soft hover:bg-ivt-paper-3/70 hover:text-ivt-ink'"
                    >
                        <CheckIcon v-if="eventTypeSelected(type.value)" class="h-4 w-4 flex-none" />
                        <span class="truncate">{{ type.label }}</span>
                    </button>
                </div>
            </Field>

            <Field label="Descriere" :error="form.errors.description" optional hint="Ce include serviciul, cum se desfășoară și de ce să te aleagă clienții.">
                <RichTextEditor
                    v-model="form.description"
                    placeholder="Descrie serviciul oferit, ce include, cum se desfășoară..."
                />
            </Field>
        </div>
    </section>
</template>
