<script setup>
import { nextTick, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeftIcon, LockClosedIcon, UserIcon } from '@heroicons/vue/24/outline';
import GuestAuthLayout from '@/Layouts/GuestAuthLayout.vue';
import IconField from '@/Components/Auth/IconField.vue';
import GoogleAuthButton from '@/Components/Auth/GoogleAuthButton.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const step = ref(1);
const identifier = ref('');
const identifierError = ref('');
const checking = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const identify = async () => {
    identifierError.value = '';
    checking.value = true;

    try {
        const { data } = await axios.post(route('login.identify'), { identifier: identifier.value });

        if (data.exists) {
            form.email = data.identifier;
            identifier.value = data.identifier;
            step.value = 2;
            await nextTick();
            document.getElementById('password')?.focus();
        } else {
            router.get(route('register.client'), {
                [data.type]: data.identifier,
            });
        }
    } catch (error) {
        identifierError.value = error.response?.data?.errors?.identifier?.[0]
            ?? (error.response?.status === 429
                ? 'Prea multe încercări. Încearcă din nou peste un minut.'
                : 'A apărut o eroare. Încearcă din nou.');
    } finally {
        checking.value = false;
    }
};

const back = () => {
    form.reset('password');
    form.clearErrors();
    step.value = 1;
    nextTick(() => document.getElementById('identifier')?.focus());
};

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Conectare" />

    <GuestAuthLayout
        title="Conectare"
        :subtitle="step === 1
            ? 'Introdu adresa de email sau numărul de telefon pentru a continua.'
            : 'Introdu parola pentru a-ți accesa contul în siguranță.'"
    >
        <div
            v-if="status"
            class="mb-6 rounded-xl bg-ivt-teal/10 px-4 py-3 text-sm font-medium text-ivt-teal"
        >
            {{ status }}
        </div>

        <form v-if="step === 1" @submit.prevent="identify" class="space-y-4">
            <IconField
                id="identifier"
                v-model="identifier"
                type="text"
                :icon="UserIcon"
                autofocus
                autocomplete="username"
                placeholder="Email sau număr de telefon"
                :error="identifierError"
            />

            <button
                type="submit"
                :disabled="checking || !identifier.trim()"
                class="w-full rounded-full bg-gradient-to-b from-primary-bright to-primary px-4 py-3 text-sm font-semibold text-ivt-paper transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(225,29,99,0.4)] active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
            >
                Continuă
            </button>
        </form>

        <form v-else @submit.prevent="submit" class="space-y-4">
            <button
                type="button"
                class="flex w-full items-center gap-2 rounded-full border border-ivt-line bg-ivt-paper-2 px-4 py-3 text-left text-sm text-ivt-ink-soft transition-colors duration-150 hover:border-ivt-violet"
                @click="back"
            >
                <ArrowLeftIcon class="h-4 w-4 shrink-0 text-ivt-ink-faint" />
                <span class="truncate">{{ form.email }}</span>
                <span class="ml-auto shrink-0 font-medium text-primary">Schimbă</span>
            </button>

            <IconField
                id="password"
                v-model="form.password"
                type="password"
                :icon="LockClosedIcon"
                autocomplete="current-password"
                placeholder="Parolă"
                :error="form.errors.password || form.errors.email"
            />

            <div class="flex items-center justify-between px-1 text-sm">
                <label class="flex items-center gap-2 text-ivt-ink-soft cursor-pointer select-none">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="rounded border-ivt-line text-primary focus:ring-primary/30"
                    />
                    Ține-mă minte
                </label>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request', { identifier: form.email })"
                    class="font-medium text-primary transition-colors duration-150 hover:text-primary-bright"
                >
                    Ai uitat parola?
                </Link>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-full bg-gradient-to-b from-primary-bright to-primary px-4 py-3 text-sm font-semibold text-ivt-paper transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(225,29,99,0.4)] active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
            >
                Conectează-te
            </button>
        </form>

        <p v-if="step === 1" class="mt-6 text-center text-sm text-ivt-ink-soft">
            Nu ai un cont?
            <Link href="/register/client" class="font-semibold text-primary transition-colors duration-150 hover:text-primary-bright">
                Înregistrează-te
            </Link>
        </p>

        <GoogleAuthButton v-if="step === 1" />

        <p class="mt-6 text-center text-xs text-ivt-ink-faint">
            Ești furnizor de servicii?
            <Link href="/register" class="font-semibold text-primary transition-colors duration-150 hover:text-primary-bright">
                Creează cont firmă
            </Link>
        </p>
    </GuestAuthLayout>
</template>
