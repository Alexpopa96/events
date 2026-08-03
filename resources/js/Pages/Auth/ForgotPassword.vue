<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { EnvelopeIcon } from '@heroicons/vue/24/outline';
import GuestAuthLayout from '@/Layouts/GuestAuthLayout.vue';
import IconField from '@/Components/Auth/IconField.vue';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Recuperare parolă" />

    <GuestAuthLayout
        back-href="/login"
        title="Ai uitat parola?"
        subtitle="Introdu adresa de email pentru a primi un link de resetare și a-ți recăpăta accesul la cont."
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

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-full bg-brand-500 px-4 py-3 text-sm font-semibold text-white shadow-sm shadow-brand-500/25 transition-all duration-200 hover:bg-brand-600 hover:shadow-md hover:shadow-brand-500/30 hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-brand-500/30 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
            >
                Continuă
            </button>
        </form>
    </GuestAuthLayout>
</template>
