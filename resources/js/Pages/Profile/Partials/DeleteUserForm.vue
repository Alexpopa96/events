<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { ExclamationTriangleIcon, TrashIcon } from '@heroicons/vue/24/outline';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    setTimeout(() => passwordInput.value.focus(), 250);
};

const deleteUser = () => {
    form.delete(route('current-user.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.reset();
};
</script>

<template>
    <div>
        <div class="flex items-start gap-3 rounded-2xl border border-danger-200 bg-danger-50/60 px-4 py-4">
            <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 flex-none text-danger-600" />
            <div class="min-w-0">
                <p class="text-sm font-semibold text-danger-800">Acțiunea nu poate fi anulată</p>
                <p class="mt-0.5 text-sm text-danger-700/90">
                    Contul și toate datele asociate lui vor fi șterse definitiv. Salvează înainte ce vrei să păstrezi.
                </p>
            </div>
        </div>

        <div class="mt-5">
            <DangerButton @click="confirmUserDeletion">
                <TrashIcon class="h-4 w-4" />
                Șterg contul definitiv
            </DangerButton>
        </div>

        <!-- Delete Account Confirmation Modal -->
        <DialogModal :show="confirmingUserDeletion" @close="closeModal">
            <template #title>
                Șterge contul
            </template>

            <template #content>
                Sigur vrei să îți ștergi contul? După ștergere, toate resursele și datele asociate vor fi șterse definitiv. Introdu parola pentru a confirma.

                <div class="mt-4">
                    <TextInput
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="block w-full"
                        placeholder="Parola"
                        autocomplete="current-password"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">
                    Anulează
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    :disabled="form.processing"
                    @click="deleteUser"
                >
                    Șterge contul
                </DangerButton>
            </template>
        </DialogModal>
    </div>
</template>
