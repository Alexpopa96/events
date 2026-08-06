<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import {
    BuildingOfficeIcon,
    EnvelopeIcon,
    LockClosedIcon,
    MapPinIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';
import GuestAuthLayout from '@/Layouts/GuestAuthLayout.vue';
import IconField from '@/Components/Auth/IconField.vue';
import InputError from '@/Components/InputError.vue';
import CountyLocalitySelect from '@/Components/CountyLocalitySelect.vue';

defineProps({
    counties: { type: Array, required: true },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    cui: '',
    company_name: '',
    address: '',
    county_id: null,
    locality_id: null,
    terms: false,
});

const anafStatus = ref('idle'); // idle | loading | success | error
const anafMessage = ref('');
const anafCheckedCui = ref(null);

const verifyCui = async () => {
    const cui = form.cui.trim();

    if (!cui) {
        return;
    }

    anafStatus.value = 'loading';
    anafMessage.value = '';

    try {
        const { data } = await axios.post(route('register.anaf-lookup'), { cui });

        form.company_name = data.denumire ?? form.company_name;
        form.address = data.address ?? form.address;
        form.county_id = data.county?.id ?? null;
        form.locality_id = data.locality?.id ?? null;

        anafStatus.value = 'success';
        anafCheckedCui.value = cui;
        anafMessage.value = data.county && data.locality
            ? `Firmă găsită: ${data.denumire}.`
            : `Firmă găsită: ${data.denumire}. Alege manual județul și localitatea.`;
    } catch (error) {
        anafStatus.value = 'error';
        anafCheckedCui.value = null;
        anafMessage.value = error.response?.data?.message ?? 'Nu am putut valida CUI-ul la ANAF.';
    }
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Creare cont firmă" />

    <GuestAuthLayout
        back-href="/login"
        title="Creează cont firmă"
        subtitle="Publică-ți serviciile și primește cereri de ofertă direct de la clienți."
        max-width="max-w-xl"
    >
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-ivt-ink-faint">
                        <BuildingOfficeIcon class="h-5 w-5" />
                    </span>
                    <input
                        id="cui"
                        v-model="form.cui"
                        type="text"
                        inputmode="numeric"
                        autofocus
                        placeholder="CUI firmă (ex: RO12345678)"
                        class="w-full rounded-full border border-ivt-line bg-white py-3 pl-11 pr-28 text-sm text-ivt-ink placeholder:text-ivt-ink-faint transition-all duration-150 focus:border-ivt-gold focus:outline-none focus:ring-2 focus:ring-ivt-gold/20"
                        :class="{ 'border-red-400 focus:border-red-400 focus:ring-red-400/20': form.errors.cui || anafStatus === 'error' }"
                        @blur="verifyCui"
                    />
                    <button
                        type="button"
                        :disabled="anafStatus === 'loading' || !form.cui.trim()"
                        class="absolute inset-y-1.5 right-1.5 flex-none rounded-full bg-ivt-ink px-4 text-xs font-semibold text-ivt-paper transition-all duration-150 hover:bg-ivt-ink-2 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="verifyCui"
                    >
                        {{ anafStatus === 'loading' ? 'Se verifică…' : 'Verifică' }}
                    </button>
                </div>
                <p v-if="anafStatus === 'success'" class="mt-1.5 px-4 text-sm text-ivt-sage">{{ anafMessage }}</p>
                <p v-else-if="anafStatus === 'error'" class="mt-1.5 px-4 text-sm text-red-600">{{ anafMessage }}</p>
                <InputError class="mt-1.5 px-4" :message="form.errors.cui" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <IconField
                    id="company_name"
                    v-model="form.company_name"
                    type="text"
                    :icon="BuildingOfficeIcon"
                    placeholder="Denumire firmă (automat)"
                    :error="form.errors.company_name"
                    readonly
                />

                <IconField
                    id="address"
                    v-model="form.address"
                    type="text"
                    :icon="MapPinIcon"
                    placeholder="Adresă (automat)"
                    :error="form.errors.address"
                    readonly
                />
            </div>

            <CountyLocalitySelect
                v-model:county-id="form.county_id"
                v-model:locality-id="form.locality_id"
                :counties="counties"
                :county-error="form.errors.county_id"
                :locality-error="form.errors.locality_id"
            />

            <IconField
                id="name"
                v-model="form.name"
                type="text"
                :icon="UserIcon"
                autocomplete="name"
                placeholder="Persoană de contact"
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

            <div v-if="$page.props.jetstream?.hasTermsAndPrivacyPolicyFeature">
                <label class="flex items-start gap-2.5 px-1 text-sm text-ivt-ink-soft cursor-pointer select-none">
                    <input
                        v-model="form.terms"
                        type="checkbox"
                        class="mt-0.5 rounded border-ivt-line text-ivt-wine focus:ring-ivt-wine/30"
                        style="accent-color: #7C2E3B;"
                    />
                    <span>
                        Sunt de acord cu <span class="font-semibold text-ivt-ink">Termenii și Condițiile</span>
                        și <span class="font-semibold text-ivt-ink">Politica de Confidențialitate</span>
                    </span>
                </label>
                <InputError class="mt-1.5 px-4" :message="form.errors.terms" />
            </div>

            <button
                type="submit"
                :disabled="form.processing || anafCheckedCui !== form.cui.trim()"
                class="w-full rounded-full bg-gradient-to-b from-ivt-wine-bright to-ivt-wine px-4 py-3 text-sm font-semibold text-ivt-paper transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-12px_rgba(124,46,59,0.4)] active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-ivt-wine/30 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
            >
                Creează cont firmă
            </button>
            <p v-if="anafCheckedCui !== form.cui.trim()" class="text-center text-xs text-ivt-ink-faint">
                Verifică CUI-ul firmei pentru a continua.
            </p>
        </form>

        <p class="mt-6 text-center text-sm text-ivt-ink-soft">
            Ai deja un cont?
            <Link href="/login" class="font-semibold text-ivt-wine transition-colors duration-150 hover:text-ivt-wine-bright">
                Conectează-te
            </Link>
        </p>

        <p class="mt-4 text-center text-xs text-ivt-ink-faint">
            Cauți furnizori pentru evenimentul tău?
            <Link href="/register/client" class="font-semibold text-ivt-wine transition-colors duration-150 hover:text-ivt-wine-bright">
                Creează cont client
            </Link>
        </p>
    </GuestAuthLayout>
</template>
