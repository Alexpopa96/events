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
                <label class="flex items-center gap-2 text-ivt-ink-soft cursor-pointer select-none">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="rounded border-ivt-line text-ivt-wine focus:ring-ivt-wine/30"
                        style="accent-color: #7C2E3B;"
                    />
                    Ține-mă minte
                </label>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="font-medium text-ivt-wine transition-colors duration-150 hover:text-ivt-wine-bright"
                >
                    Ai uitat parola?
                </Link>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-full bg-gradient-to-b from-ivt-wine-bright to-ivt-wine px-4 py-3 text-sm font-semibold text-ivt-paper transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(124,46,59,0.4)] active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-ivt-wine/30 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
            >
                Conectează-te
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-ivt-ink-soft">
            Nu ai un cont?
            <Link href="/register/client" class="font-semibold text-ivt-wine transition-colors duration-150 hover:text-ivt-wine-bright">
                Înregistrează-te
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
