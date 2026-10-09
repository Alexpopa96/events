<script setup>
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, required: true },
    description: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirmă' },
    confirmClass: { type: String, default: 'bg-danger-600 hover:bg-danger-700' },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'confirm']);

const reason = ref('');

watch(() => props.show, (show) => {
    if (show) {
        reason.value = '';
    }
});

const submit = () => {
    if (!reason.value.trim()) {
        return;
    }

    emit('confirm', reason.value.trim());
};
</script>

<template>
    <Modal :show="show" max-width="lg" @close="$emit('close')">
        <div class="p-6">
            <h3 class="font-display text-lg text-ivt-ink">{{ title }}</h3>
            <p v-if="description" class="mt-1.5 text-sm text-ivt-ink-soft">{{ description }}</p>

            <textarea
                v-model="reason"
                rows="4"
                placeholder="Scrie motivul aici..."
                class="mt-4 w-full rounded-2xl border-ivt-line text-sm text-ivt-ink placeholder:text-ivt-ink-soft/50 focus:border-primary focus:ring-primary"
            ></textarea>

            <div class="mt-5 flex items-center justify-end gap-3">
                <button
                    type="button"
                    class="px-4 py-2 rounded-xl text-sm font-medium text-ivt-ink-soft hover:bg-ivt-paper transition-colors"
                    @click="$emit('close')"
                >
                    Anulează
                </button>
                <button
                    type="button"
                    :disabled="processing || !reason.trim()"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-white shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="confirmClass"
                    @click="submit"
                >
                    {{ processing ? 'Se procesează...' : confirmLabel }}
                </button>
            </div>
        </div>
    </Modal>
</template>
