<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestAuthLayout from '@/Layouts/GuestAuthLayout.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    email: { type: String, required: true },
    validForMinutes: { type: Number, default: 10 },
    status: String,
});

const RESEND_COOLDOWN = 60;

const form = useForm({ code: '' });
const resendForm = useForm({});

const cooldown = ref(RESEND_COOLDOWN);
let timer = null;

const startCooldown = () => {
    cooldown.value = RESEND_COOLDOWN;
    clearInterval(timer);
    timer = setInterval(() => {
        cooldown.value -= 1;

        if (cooldown.value <= 0) {
            clearInterval(timer);
        }
    }, 1000);
};

onMounted(startCooldown);
onBeforeUnmount(() => clearInterval(timer));

const codeSent = computed(() => props.status === 'verification-code-sent');

const submit = () => {
    form.post(route('verification.code.verify'), {
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

    if (digits.length === 6 && !form.processing) {
        submit();
    }
});

const resend = () => {
    resendForm.post(route('verification.code.resend'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('code');
            form.clearErrors();
            startCooldown();
        },
    });
};
</script>

<template>
    <Head title="Verifică emailul" />

    <GuestAuthLayout
        title="Verifică-ți emailul"
        :subtitle="`Am trimis un cod de 6 cifre la ${email}. Este valabil ${validForMinutes} minute.`"
    >
        <div
            v-if="codeSent"
            class="mb-6 rounded-xl bg-ivt-teal/10 px-4 py-3 text-sm font-medium text-ivt-teal"
        >
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
                    aria-label="Cod de verificare"
                    class="w-full rounded-2xl border border-ivt-line bg-white py-4 text-center font-mono text-3xl font-semibold tracking-[0.5em] text-primary placeholder:text-ivt-line focus:border-ivt-violet focus:outline-none focus:ring-2 focus:ring-ivt-violet/20"
                    :class="{ 'border-danger-400 focus:border-danger-400 focus:ring-danger-400/20': form.errors.code }"
                />
                <InputError class="mt-1.5 px-1 text-center" :message="form.errors.code" />
            </div>

            <button
                type="submit"
                :disabled="form.processing || form.code.length !== 6"
                class="w-full rounded-full bg-gradient-to-b from-primary-bright to-primary px-4 py-3 text-sm font-semibold text-ivt-paper transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(225,29,99,0.4)] active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
            >
                Verifică emailul
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-ivt-ink-soft">
            Nu ai primit codul?
            <button
                type="button"
                class="font-semibold text-primary transition-colors duration-150 hover:text-primary-bright disabled:cursor-not-allowed disabled:text-ivt-ink-faint disabled:hover:text-ivt-ink-faint"
                :disabled="cooldown > 0 || resendForm.processing"
                @click="resend"
            >
                {{ cooldown > 0 ? `Retrimite în ${cooldown}s` : 'Trimite un cod nou' }}
            </button>
        </div>

        <p class="mt-6 text-center text-xs text-ivt-ink-faint">
            Ai greșit adresa?
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="font-medium text-primary transition-colors duration-150 hover:text-primary-bright"
            >
                Folosește alt cont
            </Link>
        </p>
    </GuestAuthLayout>
</template>
