<script setup>
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const show = defineModel('show', { default: false });

const props = defineProps({
    title: { type: String, required: true },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirmă' },
    cancelLabel: { type: String, default: 'Anulează' },
    variant: { type: String, default: 'danger' }, // 'danger' | 'primary' (any non-danger value)
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm']);

const close = () => {
    if (props.processing) return;
    show.value = false;
};
</script>

<template>
    <TransitionRoot as="template" :show="show">
        <Dialog as="div" class="relative z-50" @close="close">
            <TransitionChild
                as="template"
                enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100"
                leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-ivt-ink/40" />
            </TransitionChild>

            <div class="fixed inset-0 flex items-center justify-center p-4">
                <TransitionChild
                    as="template"
                    enter="ease-out duration-200" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100"
                    leave="ease-in duration-150" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95"
                >
                    <DialogPanel class="w-full max-w-sm rounded-3xl bg-white p-6 text-center shadow-xl shadow-ivt-ink/10">
                        <span
                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl"
                            :class="{
                                'bg-rose-50 text-rose-600': variant === 'danger',
                                'bg-primary/10 text-primary': variant !== 'danger',
                            }"
                        >
                            <ExclamationTriangleIcon class="h-6 w-6" />
                        </span>

                        <h2 class="mt-4 font-serif text-lg text-ivt-ink">{{ title }}</h2>
                        <p v-if="message" class="mt-2 text-sm leading-relaxed text-ivt-ink-soft">{{ message }}</p>

                        <div class="mt-6 flex gap-3">
                            <button
                                type="button"
                                :disabled="processing"
                                @click="close"
                                class="flex-1 rounded-xl border border-ivt-line px-4 py-2.5 text-sm font-semibold text-ivt-ink transition-colors hover:bg-ivt-paper-2 disabled:pointer-events-none disabled:opacity-50"
                            >
                                {{ cancelLabel }}
                            </button>
                            <button
                                type="button"
                                :disabled="processing"
                                @click="emit('confirm')"
                                class="flex flex-1 items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-colors disabled:pointer-events-none disabled:opacity-60"
                                :class="{
                                    'bg-rose-600 hover:bg-rose-700': variant === 'danger',
                                    'bg-primary hover:bg-primary-bright': variant !== 'danger',
                                }"
                            >
                                <svg v-if="processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                </svg>
                                {{ confirmLabel }}
                            </button>
                        </div>
                    </DialogPanel>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
