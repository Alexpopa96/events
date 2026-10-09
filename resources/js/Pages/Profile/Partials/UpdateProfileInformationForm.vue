<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import { EnvelopeIcon } from '@heroicons/vue/24/outline';
import ActionMessage from '@/Components/ActionMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    email: props.user.email,
    email_change_code: '',
});

/* ---------- changing the email needs a code sent to the current address ---------- */
const emailChanged = computed(() => form.email.trim().toLowerCase() !== props.user.email.toLowerCase());

const codeSent = ref(false);
const sending = ref(false);
const sendError = ref('');
const cooldown = ref(0);
let timer = null;

const startCooldown = (seconds) => {
    cooldown.value = seconds;
    clearInterval(timer);
    if (seconds <= 0) return;
    timer = setInterval(() => {
        cooldown.value -= 1;
        if (cooldown.value <= 0) clearInterval(timer);
    }, 1000);
};

onBeforeUnmount(() => clearInterval(timer));

const resetChange = () => {
    codeSent.value = false;
    sendError.value = '';
    form.email_change_code = '';
    form.clearErrors('email_change_code');
    clearInterval(timer);
    cooldown.value = 0;
};

// The code is bound to the address it was requested for, so editing the address starts over.
watch(() => form.email, resetChange);

const sendCode = async () => {
    sending.value = true;
    sendError.value = '';

    try {
        const { data } = await axios.post(route('email-change.send'), { email: form.email });
        codeSent.value = true;
        startCooldown(data.retry_in);
    } catch (error) {
        sendError.value = error.response?.status === 422
            ? Object.values(error.response.data.errors)[0][0]
            : error.response?.status === 429
                ? 'Prea multe încercări. Așteaptă un minut și încearcă din nou.'
                : 'Ceva n-a mers. Încearcă din nou.';
    } finally {
        sending.value = false;
    }
};

const onCodeInput = (event) => {
    form.email_change_code = event.target.value.replace(/\D/g, '').slice(0, 6);
};

const canSave = computed(() => !form.processing && (!emailChanged.value || form.email_change_code.length === 6));

const updateProfileInformation = () => {
    form.transform((data) => (emailChanged.value ? data : { ...data, email_change_code: undefined })).post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => {
            resetChange();
            form.email = props.user.email;
        },
    });
};
</script>

<template>
    <form @submit.prevent="updateProfileInformation">
        <div class="grid max-w-xl grid-cols-6 gap-5">
            <!-- Name -->
            <div class="col-span-6">
                <InputLabel for="name" value="Nume" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="block w-full"
                    required
                    autocomplete="name"
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <!-- Email -->
            <div class="col-span-6">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="block w-full"
                    required
                    autocomplete="username"
                />
                <InputError :message="form.errors.email" class="mt-2" />
                <p v-if="!emailChanged" class="mt-1.5 text-xs leading-relaxed text-ivt-ink-soft/80">
                    Ca să schimbi adresa, îți trimitem un cod de verificare pe adresa curentă.
                </p>
            </div>

            <!-- Verification for a changed email -->
            <div v-if="emailChanged" class="col-span-6">
                <div class="rounded-2xl bg-ivt-paper-2 px-4 py-4">
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-white text-primary shadow-sm"><EnvelopeIcon class="h-5 w-5" /></span>
                        <p class="text-sm text-ivt-ink-soft">
                            <template v-if="!codeSent">
                                Pentru a schimba adresa în <strong class="break-all text-ivt-ink">{{ form.email }}</strong>, confirmă cu un cod trimis pe adresa curentă,
                                <strong class="break-all text-ivt-ink">{{ user.email }}</strong>.
                            </template>
                            <template v-else>
                                Am trimis un cod de 6 cifre pe <strong class="break-all text-ivt-ink">{{ user.email }}</strong>. Este valabil 10 minute.
                            </template>
                        </p>
                    </div>

                    <div v-if="codeSent" class="mt-4">
                        <input
                            :value="form.email_change_code"
                            @input="onCodeInput"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            placeholder="••••••"
                            aria-label="Cod de verificare"
                            class="w-full rounded-2xl border-2 border-transparent bg-white py-3.5 text-center font-mono text-2xl font-semibold tracking-[0.5em] text-primary placeholder:text-ivt-ink-soft/30 focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10"
                            :class="{ '!border-danger-400 !bg-danger-50/60': form.errors.email_change_code }"
                        />
                        <InputError class="mt-2" :message="form.errors.email_change_code" />
                        <p class="mt-2 text-xs text-ivt-ink-soft">Apoi apasă „Salvează” ca să schimbi adresa.</p>
                    </div>

                    <InputError v-if="sendError" class="mt-3" :message="sendError" />

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <SecondaryButton v-if="!codeSent" :disabled="sending" @click="sendCode">
                            {{ sending ? 'Se trimite…' : 'Trimite codul' }}
                        </SecondaryButton>
                        <SecondaryButton v-else :disabled="cooldown > 0 || sending" @click="sendCode">
                            {{ cooldown > 0 ? `Retrimite în ${cooldown}s` : 'Trimite un cod nou' }}
                        </SecondaryButton>
                        <button type="button" class="text-sm font-medium text-ivt-ink-soft transition-colors hover:text-ivt-ink" @click="form.email = user.email">
                            Renunță la schimbare
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3 border-t border-ivt-line pt-5">
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Salvat.
            </ActionMessage>

            <PrimaryButton :disabled="!canSave">
                Salvează
            </PrimaryButton>
        </div>
    </form>
</template>
