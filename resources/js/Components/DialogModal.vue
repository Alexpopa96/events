<script setup>
import Modal from './Modal.vue';

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="close"
    >
        <div class="px-6 py-5">
            <div class="font-display text-xl text-ivt-ink">
                <slot name="title" />
            </div>

            <div class="mt-3 text-sm text-ivt-ink-soft">
                <slot name="content" />
            </div>
        </div>

        <div class="flex flex-row justify-end gap-3 border-t border-ivt-line bg-ivt-paper-2/40 px-6 py-4 text-end">
            <slot name="footer" />
        </div>
    </Modal>
</template>
