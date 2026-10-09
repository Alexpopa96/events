<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EnvelopeIcon, LockClosedIcon, PhoneIcon, UserIcon } from '@heroicons/vue/24/outline';
import GuestAuthLayout from '@/Layouts/GuestAuthLayout.vue';
import IconField from '@/Components/Auth/IconField.vue';
import GoogleAuthButton from '@/Components/Auth/GoogleAuthButton.vue';

const props = defineProps({
    prefill: { type: Object, default: () => ({}) },
});

const form = useForm({
    name: '',
    email: props.prefill.email ?? '',
    phone: props.prefill.phone ?? '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register.client.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Creare cont" />

    <GuestAuthLayout
        back-href="/login"
        title="Creează cont"
        subtitle="Creează-ți contul gratuit și descoperă furnizori pentru evenimentul tău."
    >
        <div
            v-if="prefill.email || prefill.phone"
            class="mb-6 rounded-xl bg-ivt-teal/10 px-4 py-3 text-sm font-medium text-ivt-teal"
        >
            Nu am găsit niciun cont cu {{ prefill.email ? 'această adresă de email' : 'acest număr de telefon' }}. Completează datele de mai jos pentru a-ți crea unul.
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <IconField
                id="name"
                v-model="form.name"
                type="text"
                :icon="UserIcon"
                autofocus
                autocomplete="name"
                placeholder="Nume"
                :error="form.errors.name"
            />

            <IconField
                id="email"
                v-model="form.email"
                type="email"
                :icon="EnvelopeIcon"
                autocomplete="username"
                placeholder="Adresă de email"
                :error="form.errors.email"
            />

            <IconField
                id="phone"
                v-model="form.phone"
                type="tel"
                :icon="PhoneIcon"
                autocomplete="tel"
                placeholder="Telefon, ex. 0722 123 456 (opțional)"
                :error="form.errors.phone"
            />

            <IconField
                id="password"
                v-model="form.password"
                type="password"
                :icon="LockClosedIcon"
                autocomplete="new-password"
                placeholder="Parolă"
                :error="form.errors.password"
            />

            <IconField
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                :icon="LockClosedIcon"
                autocomplete="new-password"
                placeholder="Confirmă parola"
                :error="form.errors.password_confirmation"
            />

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-full bg-gradient-to-b from-primary-bright to-primary px-4 py-3 text-sm font-semibold text-ivt-paper transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(225,29,99,0.4)] active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
            >
                Creează cont
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-ivt-ink-soft">
            Ai deja un cont?
            <Link href="/login" class="font-semibold text-primary transition-colors duration-150 hover:text-primary-bright">
                Conectează-te
            </Link>
        </p>

        <GoogleAuthButton />

        <p class="mt-6 text-center text-xs text-ivt-ink-faint">
            Ești furnizor de servicii?
            <Link href="/register" class="font-semibold text-primary transition-colors duration-150 hover:text-primary-bright">
                Creează cont firmă
            </Link>
        </p>
    </GuestAuthLayout>
</template>
