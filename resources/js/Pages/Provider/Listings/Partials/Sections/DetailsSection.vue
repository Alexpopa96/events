<script setup>
import { computed } from 'vue';
import { DocumentTextIcon, TagIcon } from '@heroicons/vue/24/outline';
import { categoryIcon } from '@/Composables/useCategoryIcon';
import RichTextEditor from '@/Components/RichTextEditor.vue';

const props = defineProps({
    form: Object,
    categories: Array,
    heading: { type: Boolean, default: true },
    bordered: { type: Boolean, default: false },
});

const childrenOf = (parentId) => props.categories.filter((c) => c.parent_id === parentId);
const parentCategories = computed(() => props.categories.filter((c) => !c.parent_id));
const groupedParents = computed(() => parentCategories.value.filter((p) => childrenOf(p.id).length > 0));
const standaloneCategories = computed(() => parentCategories.value.filter((p) => childrenOf(p.id).length === 0));

const inputClass = 'w-full rounded-2xl border border-line bg-white px-4 py-2.5 text-sm text-ink placeholder:text-ink-soft/60 shadow-sm shadow-ink/5 transition-all duration-150 hover:border-brand-300/70 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:shadow-md focus:shadow-brand-500/10';
const errorClass = 'border-red-400';

const sectionHeadingClass = 'flex items-center gap-2 text-sm font-semibold text-ink mb-4';
const iconWrapClass = 'w-7 h-7 rounded-lg bg-brand-50 text-brand-500 flex items-center justify-center flex-none';

const categoryButtonClass = (category) => [
    'flex items-center gap-2 rounded-2xl border px-3 py-2.5 text-sm text-left transition-all duration-150',
    props.form.category_id === category.id
        ? 'border-brand-500 bg-brand-50 text-brand-600 shadow-sm shadow-brand-500/10'
        : 'border-line bg-white text-ink-soft hover:border-brand-300 hover:text-ink hover:-translate-y-px',
];
</script>

<template>
    <section :class="bordered && 'pt-6 border-t border-line'">
        <h3 v-if="heading" :class="sectionHeadingClass">
            <span :class="iconWrapClass"><TagIcon class="w-4 h-4" /></span>
            Detalii principale
        </h3>

        <div class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-ink mb-1.5">Titlu anunț</label>
                <input
                    v-model="form.title"
                    type="text"
                    placeholder="Ex: Pachet foto nuntă — o zi completă"
                    :class="[inputClass, form.errors.title && errorClass]"
                />
                <p v-if="form.errors.title" class="mt-1.5 text-sm text-red-500">{{ form.errors.title }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1.5">Categorie</label>

                <div class="space-y-4">
                    <div v-for="parent in groupedParents" :key="parent.id">
                        <p class="text-xs font-semibold text-ink-soft/70 uppercase tracking-wide mb-2">{{ parent.name }}</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <button
                                v-for="category in childrenOf(parent.id)"
                                :key="category.id"
                                type="button"
                                @click="form.category_id = category.id"
                                :class="categoryButtonClass(category)"
                            >
                                <component :is="categoryIcon(category.slug)" class="w-4 h-4 flex-none" />
                                <span class="truncate">{{ category.name }}</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="standaloneCategories.length">
                        <p v-if="groupedParents.length" class="text-xs font-semibold text-ink-soft/70 uppercase tracking-wide mb-2">Alte categorii</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <button
                                v-for="category in standaloneCategories"
                                :key="category.id"
                                type="button"
                                @click="form.category_id = category.id"
                                :class="categoryButtonClass(category)"
                            >
                                <component :is="categoryIcon(category.slug)" class="w-4 h-4 flex-none" />
                                <span class="truncate">{{ category.name }}</span>
                            </button>
                        </div>
                    </div>
                </div>
                <p v-if="form.errors.category_id" class="mt-1.5 text-sm text-red-500">{{ form.errors.category_id }}</p>
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm font-medium text-ink mb-1.5">
                    <DocumentTextIcon class="w-4 h-4 text-ink-soft" /> Descriere
                </label>
                <RichTextEditor
                    v-model="form.description"
                    placeholder="Descrie serviciul oferit, ce include, cum se desfășoară..."
                />
                <p v-if="form.errors.description" class="mt-1.5 text-sm text-red-500">{{ form.errors.description }}</p>
            </div>
        </div>
    </section>
</template>
