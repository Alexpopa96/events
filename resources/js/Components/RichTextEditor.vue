<script setup>
import { onBeforeUnmount, watch } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';
import Placeholder from '@tiptap/extension-placeholder';
import {
    Bold, Italic, Underline as UnderlineIcon, List, ListOrdered,
    AlignLeft, AlignCenter, AlignRight, RemoveFormatting,
} from '@lucide/vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [
        StarterKit,
        Underline,
        TextAlign.configure({ types: ['paragraph'] }),
        Placeholder.configure({ placeholder: props.placeholder }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none focus:outline-none min-h-[8rem] text-ink',
        },
    },
    onUpdate: ({ editor }) => {
        const html = editor.isEmpty ? '' : editor.getHTML();
        emit('update:modelValue', html);
    },
});

// Reset content if the form is reset externally (e.g. after a successful create redirect).
watch(() => props.modelValue, (value) => {
    if (editor.value && value === '' && !editor.value.isEmpty) {
        editor.value.commands.clearContent();
    }
});

onBeforeUnmount(() => {
    editor.value?.destroy();
});

const buttons = [
    { key: 'bold', icon: Bold, title: 'Bold', run: (e) => e.chain().focus().toggleBold().run(), active: (e) => e.isActive('bold') },
    { key: 'italic', icon: Italic, title: 'Italic', run: (e) => e.chain().focus().toggleItalic().run(), active: (e) => e.isActive('italic') },
    { key: 'underline', icon: UnderlineIcon, title: 'Subliniat', run: (e) => e.chain().focus().toggleUnderline().run(), active: (e) => e.isActive('underline') },
    { key: 'bulletList', icon: List, title: 'Listă cu puncte', run: (e) => e.chain().focus().toggleBulletList().run(), active: (e) => e.isActive('bulletList') },
    { key: 'orderedList', icon: ListOrdered, title: 'Listă numerotată', run: (e) => e.chain().focus().toggleOrderedList().run(), active: (e) => e.isActive('orderedList') },
    { key: 'alignLeft', icon: AlignLeft, title: 'Aliniere stânga', run: (e) => e.chain().focus().setTextAlign('left').run(), active: (e) => e.isActive({ textAlign: 'left' }) },
    { key: 'alignCenter', icon: AlignCenter, title: 'Aliniere centru', run: (e) => e.chain().focus().setTextAlign('center').run(), active: (e) => e.isActive({ textAlign: 'center' }) },
    { key: 'alignRight', icon: AlignRight, title: 'Aliniere dreapta', run: (e) => e.chain().focus().setTextAlign('right').run(), active: (e) => e.isActive({ textAlign: 'right' }) },
    { key: 'clear', icon: RemoveFormatting, title: 'Șterge formatarea', run: (e) => e.chain().focus().clearNodes().unsetAllMarks().run(), active: () => false },
];
</script>

<template>
    <div class="rounded-2xl border border-line bg-white shadow-sm shadow-ink/5 transition-all duration-150 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/20 focus-within:shadow-md focus-within:shadow-brand-500/10">
        <div v-if="editor" class="flex flex-wrap items-center gap-0.5 px-2 py-1.5 border-b border-line">
            <button
                v-for="button in buttons"
                :key="button.key"
                type="button"
                :title="button.title"
                @mousedown.prevent="button.run(editor)"
                class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors duration-150"
                :class="button.active(editor) ? 'bg-brand-50 text-brand-600' : 'text-ink-soft hover:bg-paper hover:text-ink'"
            >
                <component :is="button.icon" class="w-4 h-4" />
            </button>
        </div>
        <EditorContent :editor="editor" class="px-4 py-3" />
    </div>
</template>

<style>
.tiptap p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
    color: rgba(107, 99, 115, 0.6);
}

.tiptap ul {
    list-style: disc;
    padding-left: 1.25rem;
}

.tiptap ol {
    list-style: decimal;
    padding-left: 1.25rem;
}
</style>
