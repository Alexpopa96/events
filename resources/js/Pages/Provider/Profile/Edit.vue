<script setup>
import { useForm } from '@inertiajs/vue3';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import CountyLocalitySelect from '@/Components/CountyLocalitySelect.vue';
import { CameraIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    profile: Object,
    counties: Array,
});

const form = useForm({
    company_name: props.profile.company_name,
    description: props.profile.description ?? '',
    phone: props.profile.phone ?? '',
    whatsapp: props.profile.whatsapp ?? '',
    email: props.profile.email ?? '',
    website: props.profile.website ?? '',
    address: props.profile.address ?? '',
    county_id: props.profile.county_id ?? null,
    locality_id: props.profile.locality_id ?? null,
    facebook: props.profile.social_links?.facebook ?? '',
    instagram: props.profile.social_links?.instagram ?? '',
    tiktok: props.profile.social_links?.tiktok ?? '',
    logo: null,
    cover: null,
});

const submit = () => form.post(route('provider.profile.update'), { forceFormData: true });

const onFile = (field, event) => {
    form[field] = event.target.files[0] ?? null;
};

const inputClass = 'w-full rounded-2xl border border-line bg-white px-4 py-2.5 text-sm text-ink placeholder:text-ink-soft/60 shadow-sm shadow-ink/5 transition-all duration-150 hover:border-brand-300/70 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:shadow-md focus:shadow-brand-500/10';
</script>

<template>
    <ProviderLayout title="Profil companie">
        <div class="max-w-2xl">
            <div class="bg-white border border-line rounded-2xl p-6 mb-6 shadow-sm shadow-ink/5">
                <div class="flex items-center gap-5">
                    <label class="group relative w-20 h-20 rounded-2xl bg-paper border-2 border-dashed border-line flex-none overflow-hidden flex items-center justify-center shadow-sm shadow-ink/5 transition-all duration-200 cursor-pointer hover:border-gold-400 hover:shadow-glow-gold">
                        <img v-if="profile.logo_path" :src="profile.logo_path" class="w-full h-full object-cover" alt="Logo" />
                        <CameraIcon v-else class="w-6 h-6 text-ink-soft/50 transition-colors duration-200 group-hover:text-gold-500" />
                        <input type="file" accept="image/*" class="hidden" @change="onFile('logo', $event)" />
                    </label>
                    <div>
                        <label class="inline-block text-sm font-semibold text-brand-500 transition-colors duration-150 hover:text-brand-600 cursor-pointer">
                            Schimbă logo
                            <input type="file" accept="image/*" class="hidden" @change="onFile('logo', $event)" />
                        </label>
                        <p class="text-xs text-ink-soft mt-1">PNG sau JPG, max 2MB.</p>
                        <p v-if="form.errors.logo" class="text-sm text-red-500 mt-1">{{ form.errors.logo }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-line rounded-2xl p-6 shadow-sm shadow-ink/5">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Nume companie</label>
                        <input v-model="form.company_name" type="text" :class="inputClass" />
                        <p v-if="form.errors.company_name" class="mt-1.5 text-sm text-red-500">{{ form.errors.company_name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Descriere</label>
                        <textarea v-model="form.description" rows="4" :class="inputClass" placeholder="Ce te diferențiază, ce include serviciul tău..."></textarea>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-ink mb-1.5">Telefon</label>
                            <input v-model="form.phone" type="text" :class="inputClass" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink mb-1.5">WhatsApp</label>
                            <input v-model="form.whatsapp" type="text" :class="inputClass" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink mb-1.5">Email de contact</label>
                            <input v-model="form.email" type="email" :class="inputClass" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink mb-1.5">Website</label>
                            <input v-model="form.website" type="text" placeholder="https://" :class="inputClass" />
                            <p v-if="form.errors.website" class="mt-1.5 text-sm text-red-500">{{ form.errors.website }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Adresă</label>
                        <input v-model="form.address" type="text" :class="inputClass" />
                    </div>

                    <CountyLocalitySelect
                        v-model:county-id="form.county_id"
                        v-model:locality-id="form.locality_id"
                        :counties="counties"
                        :county-error="form.errors.county_id"
                        :locality-error="form.errors.locality_id"
                    />

                    <div class="pt-2 border-t border-line">
                        <p class="text-xs font-medium text-ink-soft uppercase tracking-wide mb-3 mt-5">Rețele sociale</p>
                        <div class="grid sm:grid-cols-3 gap-4">
                            <input v-model="form.facebook" type="text" placeholder="Facebook URL" :class="inputClass" />
                            <input v-model="form.instagram" type="text" placeholder="Instagram URL" :class="inputClass" />
                            <input v-model="form.tiktok" type="text" placeholder="TikTok URL" :class="inputClass" />
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-6 border-t border-line">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-500/25 transition-all duration-200 hover:bg-brand-600 hover:shadow-md hover:shadow-brand-500/30 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60 disabled:pointer-events-none"
                        >
                            Salvează profilul
                        </button>
                        <span v-if="form.recentlySuccessful" class="inline-flex items-center gap-1.5 text-sm text-emerald-600">
                            <CheckCircleIcon class="h-4 w-4" /> Salvat.
                        </span>
                    </div>
                </form>
            </div>
        </div>
    </ProviderLayout>
</template>
