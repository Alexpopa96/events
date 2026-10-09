<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { CheckCircleIcon, EnvelopeIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const toast = useToast();
const page = usePage();

const state = computed(() => page.props.emailTwoFactor);
const enabled = computed(() => state.value?.enabled);

// 'idle' | 'code' (waiting for the emailed code) — for both enabling and disabling.
const step = ref('idle');
const mode = ref('enable');
const sentTo = ref('');
const code = ref('');
const processing = ref(false);
const errors = ref({});
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

const request = async (call) => {
    processing.value = true;
    errors.value = {};

    try {
        return await call();
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = Object.fromEntries(Object.entries(error.response.data.errors).map(([key, messages]) => [key, messages[0]]));
        } else if (error.response?.status === 429) {
            errors.value = { general: 'Prea multe încercări. Așteaptă un minut și încearcă din nou.' };
        } else {
            errors.value = { general: 'Ceva n-a mers. Încearcă din nou.' };
        }

        return null;
    } finally {
        processing.value = false;
    }
};

const reset = () => {
    step.value = 'idle';
    code.value = '';
    errors.value = {};
    clearInterval(timer);
    cooldown.value = 0;
};

const sendEnableCode = async () => {
    const { data } = (await request(() => axios.post(route('email-two-factor.send')))) ?? {};
    if (!data) return;

    mode.value = 'enable';
    sentTo.value = data.email;
    step.value = 'code';
    code.value = '';
    startCooldown(data.retry_in);
};

const sendDisableCode = async () => {
    const { data } = (await request(() => axios.post(route('email-two-factor.disable-code')))) ?? {};
    if (!data) return;

    mode.value = 'disable';
    sentTo.value = data.email;
    step.value = 'code';
    code.value = '';
    startCooldown(data.retry_in);
};

const resend = () => (mode.value === 'enable' ? sendEnableCode() : sendDisableCode());

const confirm = async () => {
    const call = mode.value === 'enable'
        ? () => axios.post(route('email-two-factor.confirm'), { code: code.value })
        : () => axios.delete(route('email-two-factor.disable'), { data: { code: code.value } });

    const response = await request(call);
    if (!response) {
        code.value = '';
        return;
    }

    toast.success(mode.value === 'enable' ? 'Autentificarea în 2 pași este activă.' : 'Autentificarea în 2 pași a fost dezactivată.');
    reset();
    router.reload({ only: ['emailTwoFactor'] });
};

const onCodeInput = (value) => {
    code.value = String(value).replace(/\D/g, '').slice(0, 6);
    if (code.value.length === 6 && !processing.value) confirm();
};
</script>

<template>
    <div>
        <p v-if="errors.general" class="mb-4 rounded-xl bg-danger-50 px-4 py-3 text-sm text-danger-700">{{ errors.general }}</p>

        <!-- Enabled -->
        <div v-if="enabled && step === 'idle'">
            <div class="flex items-start gap-3 rounded-2xl bg-success-50 px-4 py-4">
                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-white text-success-600 shadow-sm"><ShieldCheckIcon class="h-5 w-5" /></span>
                <div class="min-w-0">
                    <p class="flex items-center gap-1.5 text-sm font-semibold text-success-800"><CheckCircleIcon class="h-4 w-4" /> Autentificarea în 2 pași este activă</p>
                    <p class="mt-0.5 break-all text-sm text-success-700/90">Codurile ajung pe emailul contului: {{ state.email }}.</p>
                </div>
            </div>

            <p class="mt-4 max-w-xl text-sm text-ivt-ink-soft">
                Contul tău e protejat: cineva care îți află parola nu poate intra fără codul din email.
            </p>

            <div class="mt-5">
                <DangerButton :disabled="processing" @click="sendDisableCode">Dezactivează</DangerButton>
            </div>
        </div>

        <!-- Not enabled -->
        <div v-else-if="step === 'idle'" class="max-w-xl">
            <div class="flex items-start gap-3 rounded-2xl bg-ivt-paper-2 px-4 py-4">
                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-white text-primary shadow-sm"><EnvelopeIcon class="h-5 w-5" /></span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-ivt-ink">Autentificarea în 2 pași nu este activă</p>
                    <p class="mt-0.5 text-sm text-ivt-ink-soft">
                        Codurile vor fi trimise pe emailul contului tău:
                        <strong class="break-all text-ivt-ink">{{ state.email }}</strong>.
                        Îți trimitem acum un cod ca să confirmi că ai acces la el.
                    </p>
                </div>
            </div>

            <InputError class="mt-3" :message="errors.email" />

            <div class="mt-5">
                <PrimaryButton type="button" :disabled="processing" @click="sendEnableCode">Trimite codul</PrimaryButton>
            </div>
        </div>

        <!-- Waiting for the emailed code -->
        <form v-else @submit.prevent="confirm" class="max-w-xl">
            <p class="text-sm text-ivt-ink">
                Am trimis un cod de 6 cifre la <strong>{{ sentTo }}</strong>. Este valabil 10 minute.
            </p>

            <div class="mt-4">
                <input
                    :value="code"
                    @input="onCodeInput($event.target.value)"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    placeholder="••••••"
                    aria-label="Cod de 6 cifre"
                    autofocus
                    class="w-full rounded-2xl border-2 border-transparent bg-ivt-paper-2 py-4 text-center font-mono text-3xl font-semibold tracking-[0.5em] text-primary placeholder:text-ivt-ink-soft/30 focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10"
                    :class="{ '!border-danger-400 !bg-danger-50/60': errors.code }"
                />
                <InputError class="mt-2" :message="errors.code" />
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <component :is="mode === 'enable' ? PrimaryButton : DangerButton" :disabled="processing || code.length !== 6">
                    {{ mode === 'enable' ? 'Activează' : 'Dezactivează' }}
                </component>
                <SecondaryButton :disabled="cooldown > 0 || processing" @click="resend">
                    {{ cooldown > 0 ? `Retrimite în ${cooldown}s` : 'Trimite un cod nou' }}
                </SecondaryButton>
                <button type="button" class="text-sm font-medium text-ivt-ink-soft transition-colors hover:text-ivt-ink" @click="reset">
                    Renunță
                </button>
            </div>
        </form>
    </div>
</template>
