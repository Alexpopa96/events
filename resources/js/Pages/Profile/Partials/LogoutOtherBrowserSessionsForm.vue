<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { ArrowRightStartOnRectangleIcon, ComputerDesktopIcon, DevicePhoneMobileIcon } from '@heroicons/vue/24/outline';
import ActionMessage from '@/Components/ActionMessage.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    sessions: Array,
});

const confirmingLogout = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmLogout = () => {
    confirmingLogout.value = true;

    setTimeout(() => passwordInput.value.focus(), 250);
};

const logoutOtherBrowserSessions = () => {
    form.delete(route('other-browser-sessions.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingLogout.value = false;

    form.reset();
};
</script>

<template>
    <div>
        <p class="max-w-xl text-sm text-ivt-ink-soft">
            Lista poate să nu fie completă. Dacă bănuiești că cineva îți folosește contul, deconectează celelalte sesiuni și schimbă parola.
        </p>

        <ul v-if="sessions.length > 0" class="mt-4 divide-y divide-ivt-line overflow-hidden rounded-2xl border border-ivt-line">
            <li v-for="(session, i) in sessions" :key="i" class="flex items-center gap-3 px-4 py-3" :class="session.is_current_device ? 'bg-success-50/50' : 'bg-white'">
                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl" :class="session.is_current_device ? 'bg-success-100 text-success-700' : 'bg-ivt-paper-2 text-ivt-ink-soft'">
                    <component :is="session.agent.is_desktop ? ComputerDesktopIcon : DevicePhoneMobileIcon" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-ivt-ink">
                        {{ session.agent.browser || 'Browser necunoscut' }}
                        <span class="font-normal text-ivt-ink-soft">pe {{ session.agent.platform || 'sistem necunoscut' }}</span>
                    </p>
                    <p class="truncate text-xs text-ivt-ink-soft">
                        <span class="font-mono">{{ session.ip_address }}</span>
                        <template v-if="!session.is_current_device"> · activ {{ session.last_active }}</template>
                    </p>
                </div>
                <span v-if="session.is_current_device" class="inline-flex flex-none items-center gap-1.5 rounded-full bg-success-500 px-2.5 py-1 text-[11px] font-semibold text-white">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white" />
                    Acest dispozitiv
                </span>
            </li>
        </ul>

        <div class="mt-5 flex items-center gap-3">
            <SecondaryButton :disabled="sessions.length <= 1" @click="confirmLogout">
                <ArrowRightStartOnRectangleIcon class="h-4 w-4" />
                Deconectează celelalte sesiuni
            </SecondaryButton>

            <ActionMessage :on="form.recentlySuccessful">
                Gata.
            </ActionMessage>
        </div>

        <!-- Log Out Other Devices Confirmation Modal -->
        <DialogModal :show="confirmingLogout" @close="closeModal">
            <template #title>
                Deconectează celelalte sesiuni
            </template>

            <template #content>
                Introdu parola pentru a confirma deconectarea de pe celelalte browsere și dispozitive.

                <div class="mt-4">
                    <TextInput
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="block w-full"
                        placeholder="Parola"
                        autocomplete="current-password"
                        @keyup.enter="logoutOtherBrowserSessions"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">
                    Anulează
                </SecondaryButton>

                <PrimaryButton
                    class="ms-3"
                    :disabled="form.processing"
                    @click="logoutOtherBrowserSessions"
                >
                    Deconectează celelalte sesiuni
                </PrimaryButton>
            </template>
        </DialogModal>
    </div>
</template>
