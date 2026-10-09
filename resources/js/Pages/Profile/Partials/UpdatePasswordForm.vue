<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { CheckIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';
import ActionMessage from '@/Components/ActionMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// One toggle reveals all three fields, so the confirmation can be compared at a glance.
const reveal = ref(false);
const inputType = computed(() => (reveal.value ? 'text' : 'password'));

/* ---------- strength hint (client-side only; the server rules still decide) ---------- */
const rules = computed(() => [
    { label: 'Minim 8 caractere', ok: form.password.length >= 8 },
    { label: 'Litere mari și mici', ok: /[a-z]/.test(form.password) && /[A-Z]/.test(form.password) },
    { label: 'Cel puțin o cifră', ok: /\d/.test(form.password) },
    { label: 'Un simbol', ok: /[^A-Za-z0-9]/.test(form.password) },
]);

const strength = computed(() => {
    if (!form.password) return null;
    const score = rules.value.filter((rule) => rule.ok).length + (form.password.length >= 14 ? 1 : 0);

    if (score >= 5) return { bars: 4, label: 'Foarte puternică', color: 'bg-success-500', text: 'text-success-600' };
    if (score === 4) return { bars: 3, label: 'Puternică', color: 'bg-success-500', text: 'text-success-600' };
    if (score >= 2) return { bars: 2, label: 'Medie', color: 'bg-warning-500', text: 'text-warning-600' };
    return { bars: 1, label: 'Slabă', color: 'bg-danger-500', text: 'text-danger-600' };
});

const confirmationMatches = computed(() => form.password_confirmation.length > 0 && form.password_confirmation === form.password);

const updatePassword = () => {
    form.put(route('user-password.update'), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }

            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <form @submit.prevent="updatePassword">
        <div class="grid max-w-xl grid-cols-6 gap-5">
            <div class="col-span-6">
                <div class="flex items-center justify-between">
                    <InputLabel for="current_password" value="Parola curentă" />
                    <button
                        type="button"
                        class="-mt-1.5 inline-flex items-center gap-1.5 text-xs font-semibold text-ivt-ink-soft transition-colors hover:text-primary"
                        :aria-pressed="reveal"
                        @click="reveal = !reveal"
                    >
                        <component :is="reveal ? EyeSlashIcon : EyeIcon" class="h-4 w-4" />
                        {{ reveal ? 'Ascunde' : 'Arată' }}
                    </button>
                </div>
                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    :type="inputType"
                    class="block w-full"
                    autocomplete="current-password"
                />
                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <div class="col-span-6">
                <InputLabel for="password" value="Parola nouă" />
                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    :type="inputType"
                    class="block w-full"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password" class="mt-2" />

                <div v-if="strength" class="mt-3">
                    <div class="flex gap-1.5">
                        <span
                            v-for="bar in 4"
                            :key="bar"
                            class="h-1.5 flex-1 rounded-full transition-colors duration-300"
                            :class="bar <= strength.bars ? strength.color : 'bg-ivt-paper-3'"
                        />
                    </div>
                    <p class="mt-1.5 text-xs font-semibold" :class="strength.text">{{ strength.label }}</p>
                </div>

                <ul class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1.5">
                    <li
                        v-for="rule in rules"
                        :key="rule.label"
                        class="flex items-center gap-1.5 text-xs transition-colors"
                        :class="rule.ok ? 'text-success-600' : 'text-ivt-ink-soft/80'"
                    >
                        <span
                            class="flex h-4 w-4 flex-none items-center justify-center rounded-full transition-colors"
                            :class="rule.ok ? 'bg-success-500 text-white' : 'bg-ivt-paper-2'"
                        ><CheckIcon v-if="rule.ok" class="h-2.5 w-2.5" stroke-width="3" /></span>
                        {{ rule.label }}
                    </li>
                </ul>
            </div>

            <div class="col-span-6">
                <InputLabel for="password_confirmation" value="Confirmă parola nouă" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    :type="inputType"
                    class="block w-full"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password_confirmation" class="mt-2" />
                <p v-if="form.password_confirmation && !form.errors.password_confirmation" class="mt-1.5 flex items-center gap-1 text-xs" :class="confirmationMatches ? 'text-success-600' : 'text-ivt-ink-soft/80'">
                    <CheckIcon v-if="confirmationMatches" class="h-3.5 w-3.5" />
                    {{ confirmationMatches ? 'Parolele coincid' : 'Parolele nu coincid încă' }}
                </p>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3 border-t border-ivt-line pt-5">
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Salvat.
            </ActionMessage>

            <PrimaryButton :disabled="form.processing || !form.current_password || !form.password">
                {{ form.processing ? 'Se salvează…' : 'Actualizează parola' }}
            </PrimaryButton>
        </div>
    </form>
</template>
