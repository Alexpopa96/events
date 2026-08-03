<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EnvelopeIcon, LockClosedIcon } from '@heroicons/vue/24/outline';
import GuestAuthLayout from '@/Layouts/GuestAuthLayout.vue';
import IconField from '@/Components/Auth/IconField.vue';
import GoogleAuthButton from '@/Components/Auth/GoogleAuthButton.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

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
        subtitle="Introdu adresa de email și parola pentru a-ți accesa contul în siguranță."
    >
        <div
            v-if="status"
            class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <IconField
                id="email"
                v-model="form.email"
                type="email"
                :icon="EnvelopeIcon"
                autofocus
                autocomplete="username"
                placeholder="Adresă de email"
                :error="form.errors.email"
            />

            <IconField
                id="password"
                v-model="form.password"
                type="password"
                :icon="LockClosedIcon"
                autocomplete="current-password"
                placeholder="Parolă"
                :error="form.errors.password"
            />

            <div class="flex items-center justify-between px-1 text-sm">
                <label class="flex items-center gap-2 text-ink-soft cursor-pointer select-none">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="rounded border-line text-brand-500 focus:ring-brand-500/30"
                        style="accent-color: #1F5C4E;"
                    />
                    Ține-mă minte
                </label>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="font-medium text-brand-500 transition-colors duration-150 hover:text-brand-600"
                >
                    Ai uitat parola?
                </Link>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-full bg-brand-500 px-4 py-3 text-sm font-semibold text-white shadow-sm shadow-brand-500/25 transition-all duration-200 hover:bg-brand-600 hover:shadow-md hover:shadow-brand-500/30 hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-brand-500/30 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
            >
                Conectează-te
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-ink-soft">
            Nu ai un cont?
            <Link href="/register/client" class="font-semibold text-brand-500 transition-colors duration-150 hover:text-brand-600">
                Înregistrează-te
            </Link>
        </p>

        <GoogleAuthButton />

        <p class="mt-6 text-center text-xs text-ink-soft">
            Ești furnizor de servicii?
            <Link href="/register" class="font-semibold text-brand-500 transition-colors duration-150 hover:text-brand-600">
                Creează cont firmă
            </Link>
        </p>
    </GuestAuthLayout>
</template>
