<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EnvelopeIcon, LockClosedIcon, UserIcon } from '@heroicons/vue/24/outline';
import GuestAuthLayout from '@/Layouts/GuestAuthLayout.vue';
import IconField from '@/Components/Auth/IconField.vue';
import GoogleAuthButton from '@/Components/Auth/GoogleAuthButton.vue';

const form = useForm({
    name: '',
    email: '',
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
                class="w-full rounded-full bg-gradient-to-b from-ivt-wine-bright to-ivt-wine px-4 py-3 text-sm font-semibold text-ivt-paper transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(124,46,59,0.4)] active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-ivt-wine/30 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
            >
                Creează cont
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-ivt-ink-soft">
            Ai deja un cont?
            <Link href="/login" class="font-semibold text-ivt-wine transition-colors duration-150 hover:text-ivt-wine-bright">
                Conectează-te
            </Link>
        </p>

        <GoogleAuthButton />

        <p class="mt-6 text-center text-xs text-ivt-ink-faint">
            Ești furnizor de servicii?
            <Link href="/register" class="font-semibold text-ivt-wine transition-colors duration-150 hover:text-ivt-wine-bright">
                Creează cont firmă
            </Link>
        </p>
    </GuestAuthLayout>
</template>
