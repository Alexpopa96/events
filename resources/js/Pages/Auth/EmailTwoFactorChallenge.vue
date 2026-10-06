<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestAuthLayout from '@/Layouts/GuestAuthLayout.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    email: { type: String, required: true },
    validForMinutes: { type: Number, default: 10 },
    cooldown: { type: Number, default: 0 },
    status: String,
});

const form = useForm({ code: '' });
const resendForm = useForm({});

const remaining = ref(props.cooldown);
let timer = null;

const startCooldown = (seconds) => {
    remaining.value = seconds;
    clearInterval(timer);
    timer = setInterval(() => {
        remaining.value -= 1;
        if (remaining.value <= 0) clearInterval(timer);
    }, 1000);
};

onMounted(() => startCooldown(props.cooldown));
onBeforeUnmount(() => clearInterval(timer));

const codeSent = computed(() => props.status === 'verification-code-sent');

const submit = () => {
    form.post(route('email-two-factor.verify'), {
        onError: () => form.reset('code'),
    });
};

// Submit as soon as the sixth digit is in.
watch(() => form.code, (value) => {
    const digits = value.replace(/\D/g, '').slice(0, 6);

    if (digits !== value) {
        form.code = digits;
        return;
    }

    if (digits.length === 6 && !form.processing) submit();
});

const resend = () => {
    resendForm.post(route('email-two-factor.resend'), {
        preserveScroll: true,
        onSuccess: () => startCooldown(60),
    });
};
</script>

<template>
    <Head title="Autentificare în 2 pași" />

    <GuestAuthLayout
        title="Introdu codul din email"
        :subtitle="`Ți-am trimis un cod de 6 cifre la ${email}. Este valabil ${validForMinutes} minute.`"
    >
        <div v-if="codeSent" class="mb-6 rounded-xl bg-ivt-sage/10 px-4 py-3 text-sm font-medium text-ivt-sage">
            Ți-am trimis un cod nou. Verifică-ți emailul.
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <input
                    id="code"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    autofocus
                    placeholder="••••••"
                    aria-label="Cod de autentificare"
                    class="w-full rounded-2xl border border-ivt-line bg-white py-4 text-center font-mono text-3xl font-semibold tracking-[0.5em] text-ivt-wine placeholder:text-ivt-line focus:border-ivt-gold focus:outline-none focus:ring-2 focus:ring-ivt-gold/20"
                    :class="{ 'border-red-400 focus:border-red-400 focus:ring-red-400/20': form.errors.code }"
                />
                <InputError class="mt-1.5 px-1 text-center" :message="form.errors.code" />
            </div>

            <button
                type="submit"
                :disabled="form.processing || form.code.length !== 6"
                class="w-full rounded-full bg-gradient-to-b from-ivt-wine-bright to-ivt-wine px-4 py-3 text-sm font-semibold text-ivt-paper transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(124,46,59,0.4)] active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-ivt-wine/30 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
            >
                Conectează-mă
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-ivt-ink-soft">
            Nu ai primit codul?
            <button
                type="button"
                class="font-semibold text-ivt-wine transition-colors duration-150 hover:text-ivt-wine-bright disabled:cursor-not-allowed disabled:text-ivt-ink-faint disabled:hover:text-ivt-ink-faint"
                :disabled="remaining > 0 || resendForm.processing"
                @click="resend"
            >
                {{ remaining > 0 ? `Retrimite în ${remaining}s` : 'Trimite un cod nou' }}
            </button>
        </div>

        <p class="mt-6 text-center text-xs text-ivt-ink-faint">
            <Link :href="route('login')" class="font-medium text-ivt-wine transition-colors duration-150 hover:text-ivt-wine-bright">Înapoi la conectare</Link>
        </p>
    </GuestAuthLayout>
</template>
