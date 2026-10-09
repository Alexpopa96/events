<script setup>
import { computed, nextTick, onBeforeUnmount, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { LockClosedIcon, UserIcon } from '@heroicons/vue/24/outline';
import GuestAuthLayout from '@/Layouts/GuestAuthLayout.vue';
import IconField from '@/Components/Auth/IconField.vue';
import OtpInput from '@/Components/Auth/OtpInput.vue';

const RESEND_SECONDS = 60;

const step = ref(1);
const identifier = ref(
    typeof window === 'undefined' ? '' : new URLSearchParams(window.location.search).get('identifier') ?? '',
);
const code = ref('');
const otp = ref(null);
const token = ref('');
const email = ref('');
const error = ref('');
const busy = ref(false);
const cooldown = ref(0);
let timer = null;

const passwordForm = useForm({
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const subtitle = computed(() => ({
    1: 'Introdu adresa de email sau numărul de telefon al contului. Îți trimitem un cod pe email.',
    2: 'Am trimis un cod de 6 cifre pe adresa de email a contului. Este valabil 10 minute.',
    3: 'Codul este corect. Alege o parolă nouă pentru contul tău.',
}[step.value]));

const startCooldown = () => {
    cooldown.value = RESEND_SECONDS;
    clearInterval(timer);
    timer = setInterval(() => {
        cooldown.value -= 1;
        if (cooldown.value <= 0) {
            clearInterval(timer);
        }
    }, 1000);
};

onBeforeUnmount(() => clearInterval(timer));

const request = async (callback) => {
    error.value = '';
    busy.value = true;

    try {
        return await callback();
    } catch (e) {
        error.value = e.response?.data?.errors?.code?.[0]
            ?? e.response?.data?.errors?.identifier?.[0]
            ?? (e.response?.status === 429
                ? 'Prea multe încercări. Încearcă din nou peste un minut.'
                : 'A apărut o eroare. Încearcă din nou.');

        return null;
    } finally {
        busy.value = false;
    }
};

const sendCode = async () => {
    const sent = await request(() => axios.post(route('password.code.send'), { identifier: identifier.value }));

    if (sent) {
        code.value = '';
        startCooldown();
        step.value = 2;
        await nextTick();
        otp.value?.focus();
    }
};

const verifyCode = async () => {
    if (busy.value || code.value.length !== 6) {
        return;
    }

    const response = await request(() => axios.post(route('password.code.verify'), {
        identifier: identifier.value,
        code: code.value,
    }));

    if (!response) {
        code.value = '';
        await nextTick();
        otp.value?.focus();

        return;
    }

    token.value = response.data.token;
    email.value = response.data.email;
    step.value = 3;
    await nextTick();
    document.getElementById('password')?.focus();
};

const resetPassword = () => {
    passwordForm.transform((data) => ({
        ...data,
        token: token.value,
        email: email.value,
    })).post(route('password.update'), {
        onFinish: () => passwordForm.reset('password', 'password_confirmation'),
    });
};

const back = () => {
    error.value = '';
    step.value = 1;
};

const buttonClass = 'w-full rounded-full bg-gradient-to-b from-primary-bright to-primary px-4 py-3 text-sm font-semibold text-ivt-paper transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(225,29,99,0.4)] active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0';
</script>

<template>
    <Head title="Recuperare parolă" />

    <GuestAuthLayout
        back-href="/login"
        title="Ai uitat parola?"
        :subtitle="subtitle"
    >
        <form v-if="step === 1" @submit.prevent="sendCode" class="space-y-4">
            <IconField
                id="identifier"
                v-model="identifier"
                type="text"
                :icon="UserIcon"
                autofocus
                autocomplete="username"
                placeholder="Email sau număr de telefon"
                :error="error"
            />

            <button type="submit" :disabled="busy || !identifier.trim()" :class="buttonClass">
                Trimite codul
            </button>
        </form>

        <form v-else-if="step === 2" @submit.prevent="verifyCode" class="space-y-4">
            <OtpInput
                ref="otp"
                v-model="code"
                autofocus
                :disabled="busy"
                :error="error"
                @complete="verifyCode"
            />

            <button type="submit" :disabled="busy || code.trim().length !== 6" :class="buttonClass">
                Verifică codul
            </button>

            <div class="flex items-center justify-between px-1 text-sm">
                <button
                    type="button"
                    class="font-medium text-ivt-ink-soft transition-colors duration-150 hover:text-ivt-ink"
                    @click="back"
                >
                    Schimbă contul
                </button>
                <button
                    type="button"
                    :disabled="busy || cooldown > 0"
                    class="font-medium text-primary transition-colors duration-150 hover:text-primary-bright disabled:cursor-not-allowed disabled:text-ivt-ink-faint"
                    @click="sendCode"
                >
                    {{ cooldown > 0 ? `Retrimite codul (${cooldown}s)` : 'Retrimite codul' }}
                </button>
            </div>
        </form>

        <form v-else @submit.prevent="resetPassword" class="space-y-4">
            <IconField
                id="password"
                v-model="passwordForm.password"
                type="password"
                :icon="LockClosedIcon"
                autocomplete="new-password"
                placeholder="Parolă nouă"
                :error="passwordForm.errors.password || passwordForm.errors.email"
            />

            <IconField
                id="password_confirmation"
                v-model="passwordForm.password_confirmation"
                type="password"
                :icon="LockClosedIcon"
                autocomplete="new-password"
                placeholder="Confirmă parola nouă"
                :error="passwordForm.errors.password_confirmation"
            />

            <button type="submit" :disabled="passwordForm.processing" :class="buttonClass">
                Resetează parola
            </button>
        </form>
    </GuestAuthLayout>
</template>
