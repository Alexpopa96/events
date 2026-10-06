<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
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
    <ActionSection>
        <template #title>
            Șterge contul
        </template>

        <template #description>
            Șterge definitiv contul tău.
        </template>

        <template #content>
            <div class="max-w-xl text-sm text-ivt-ink-soft">
                După ștergerea contului, toate resursele și datele asociate vor fi șterse definitiv. Înainte de ștergere, salvează datele pe care vrei să le păstrezi.
            </div>

            <div class="mt-5">
                <DangerButton @click="confirmUserDeletion">
                    Șterge contul
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
                            class="block w-3/4"
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
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Șterge contul
                    </DangerButton>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>
