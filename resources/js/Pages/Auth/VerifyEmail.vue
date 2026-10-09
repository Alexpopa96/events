<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Email Verification" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="mb-4 text-sm text-ivt-ink-soft dark:text-ivt-ink-faint">
            Before continuing, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
        </div>

        <div v-if="verificationLinkSent" class="mb-4 font-medium text-sm text-success-600 dark:text-success-400">
            Un nou link de verificare a fost trimis pe adresa de email din setările profilului.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Resend Verification Email
                </PrimaryButton>

                <div>
                    <Link
                        :href="route('profile.show')"
                        class="underline text-sm text-ivt-ink-soft dark:text-ivt-ink-faint hover:text-ivt-ink dark:hover:text-ivt-on-dark rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-bright dark:focus:ring-offset-ivt-ink-2"
                    >
                        Edit Profile</Link>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="underline text-sm text-ivt-ink-soft dark:text-ivt-ink-faint hover:text-ivt-ink dark:hover:text-ivt-on-dark rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-bright dark:focus:ring-offset-ivt-ink-2 ms-2"
                    >
                        Log Out
                    </Link>
                </div>
            </div>
        </form>
    </AuthenticationCard>
</template>
