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
            class="mb-6 rounded-xl bg-ivt-sage/10 px-4 py-3 text-sm font-medium text-ivt-sage"
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
                class="w-full rounded-full bg-gradient-to-b from-ivt-wine-bright to-ivt-wine px-4 py-3 text-sm font-semibold text-ivt-paper transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(124,46,59,0.4)] active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-ivt-wine/30 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
            >
                Continuă
            </button>
        </form>
    </GuestAuthLayout>
</template>
