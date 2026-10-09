<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import CountyLocalitySelect from '@/Components/CountyLocalitySelect.vue';
import { fieldClass } from '@/Composables/useFieldClasses';
import {
    BuildingStorefrontIcon,
    CameraIcon,
    ChatBubbleOvalLeftEllipsisIcon,
    CheckCircleIcon,
    EnvelopeIcon,
    GlobeAltIcon,
    LinkIcon,
    MapPinIcon,
    PhoneIcon,
    PhotoIcon,
    UserCircleIcon,
} from '@heroicons/vue/24/outline';

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

const submit = () => form.post(route('provider.profile.update'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
        previews.value = { logo: null, cover: null };
        form.logo = null;
        form.cover = null;
    },
});

// Local object-URL previews so a picked image shows immediately, before saving.
const previews = ref({ logo: null, cover: null });

const onFile = (field, event) => {
    const file = event.target.files[0] ?? null;
    form[field] = file;

    if (previews.value[field]) URL.revokeObjectURL(previews.value[field]);
    previews.value[field] = file ? URL.createObjectURL(file) : null;
};

onBeforeUnmount(() => Object.values(previews.value).forEach((url) => url && URL.revokeObjectURL(url)));

const logoSrc = computed(() => previews.value.logo ?? props.profile.logo_path);
const coverSrc = computed(() => previews.value.cover ?? props.profile.cover_path);

const score = computed(() => Math.max(0, Math.min(100, Math.round(props.profile.completion_score ?? 0))));

const inputClass = fieldClass;
const iconInputClass = `${inputClass} pl-10`;
const labelClass = 'block text-sm font-medium text-ivt-ink mb-1.5';
const cardClass = 'rounded-2xl border border-ivt-line bg-white p-5 sm:p-6 shadow-sm shadow-ivt-ink/5';
const sectionTitleClass = 'flex items-center gap-2 text-sm font-semibold text-ivt-ink mb-5';
const sectionIconClass = 'w-7 h-7 rounded-full bg-ivt-paper-2 text-primary flex items-center justify-center flex-none';
</script>

<template>
    <ProviderLayout title="Profil companie">
        <form @submit.prevent="submit" class="grid xl:grid-cols-[1fr_20rem] gap-5 items-start">
            <div class="space-y-5 min-w-0">
                <!-- Identity: cover + logo -->
                <div class="overflow-hidden rounded-2xl border border-ivt-line bg-white shadow-sm shadow-ivt-ink/5">
                    <label class="group relative block h-36 sm:h-48 cursor-pointer overflow-hidden bg-gradient-to-r from-primary to-primary-bright">
                        <img v-if="coverSrc" :src="coverSrc" alt="" class="h-full w-full object-cover" />
                        <span class="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-ivt-ink/60 px-3 py-1.5 text-xs font-medium text-white backdrop-blur transition-colors duration-150 group-hover:bg-ivt-ink/80">
                            <PhotoIcon class="h-4 w-4" /> {{ coverSrc ? 'Schimbă coperta' : 'Adaugă copertă' }}
                        </span>
                        <input type="file" accept="image/*" class="hidden" @change="onFile('cover', $event)" />
                    </label>

                    <div class="flex flex-wrap items-end gap-4 px-5 sm:px-6 pb-5">
                        <label class="group relative -mt-12 sm:-mt-14 flex h-24 w-24 sm:h-28 sm:w-28 flex-none cursor-pointer items-center justify-center overflow-hidden rounded-full border-4 border-white bg-ivt-paper-2 shadow-md shadow-ivt-ink/10">
                            <img v-if="logoSrc" :src="logoSrc" alt="Logo" class="h-full w-full object-cover" />
                            <BuildingStorefrontIcon v-else class="h-9 w-9 text-primary/40" />
                            <span class="absolute inset-0 flex items-center justify-center bg-ivt-ink/50 opacity-0 transition-opacity duration-150 group-hover:opacity-100">
                                <CameraIcon class="h-6 w-6 text-white" />
                            </span>
                            <input type="file" accept="image/*" class="hidden" @change="onFile('logo', $event)" />
                        </label>
                        <div class="min-w-0 flex-1 pt-3">
                            <p class="truncate font-display text-xl sm:text-2xl text-ivt-ink">{{ form.company_name || 'Numele companiei' }}</p>
                            <p class="text-xs text-ivt-ink-soft mt-0.5">Logo: PNG sau JPG, max 2MB · Copertă: max 4MB</p>
                        </div>
                    </div>
                    <p v-if="form.errors.logo || form.errors.cover" class="px-6 pb-4 text-sm text-danger-500">{{ form.errors.logo || form.errors.cover }}</p>
                </div>

                <div class="divide-y divide-ivt-line rounded-2xl border border-ivt-line bg-white shadow-sm shadow-ivt-ink/5">
                <!-- About -->
                <section class="grid gap-x-10 gap-y-5 p-5 sm:p-7 lg:grid-cols-[13rem_1fr]">
                    <header>
                        <span class="mb-3 flex h-9 w-9 items-center justify-center rounded-full bg-ivt-paper-2 text-primary"><UserCircleIcon class="h-5 w-5" /></span>
                        <h3 class="text-base font-semibold text-ivt-ink">Despre companie</h3>
                        <p class="mt-1 text-sm leading-relaxed text-ivt-ink-soft">Numele și descrierea care apar pe profilul tău public.</p>
                    </header>
                    <div class="min-w-0">
                    <div class="space-y-5">
                        <div>
                            <label :class="labelClass">Nume companie</label>
                            <input v-model="form.company_name" type="text" :class="inputClass" />
                            <p v-if="form.errors.company_name" class="mt-1.5 text-sm text-danger-500">{{ form.errors.company_name }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">Descriere</label>
                            <textarea v-model="form.description" rows="5" :class="inputClass" placeholder="Ce te diferențiază, ce include serviciul tău..."></textarea>
                            <p v-if="form.errors.description" class="mt-1.5 text-sm text-danger-500">{{ form.errors.description }}</p>
                        </div>
                    </div>
                    </div>
                </section>

                <section class="grid gap-x-10 gap-y-5 p-5 sm:p-7 lg:grid-cols-[13rem_1fr]">
                    <header>
                        <span class="mb-3 flex h-9 w-9 items-center justify-center rounded-full bg-ivt-paper-2 text-primary"><PhoneIcon class="h-5 w-5" /></span>
                        <h3 class="text-base font-semibold text-ivt-ink">Contact</h3>
                        <p class="mt-1 text-sm leading-relaxed text-ivt-ink-soft">Cum te pot găsi clienții direct.</p>
                    </header>
                    <div class="min-w-0">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label :class="labelClass">Telefon</label>
                            <div class="relative">
                                <PhoneIcon class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ivt-ink-soft/60" />
                                <input v-model="form.phone" type="tel" placeholder="Ex. 0722 123 456" :class="iconInputClass" />
                            </div>
                            <p v-if="form.errors.phone" class="mt-1.5 text-sm text-danger-500">{{ form.errors.phone }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">WhatsApp</label>
                            <div class="relative">
                                <ChatBubbleOvalLeftEllipsisIcon class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ivt-ink-soft/60" />
                                <input v-model="form.whatsapp" type="tel" placeholder="Ex. 0722 123 456" :class="iconInputClass" />
                            </div>
                            <p v-if="form.errors.whatsapp" class="mt-1.5 text-sm text-danger-500">{{ form.errors.whatsapp }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">Email de contact</label>
                            <div class="relative">
                                <EnvelopeIcon class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ivt-ink-soft/60" />
                                <input v-model="form.email" type="email" :class="iconInputClass" />
                            </div>
                            <p v-if="form.errors.email" class="mt-1.5 text-sm text-danger-500">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">Website</label>
                            <div class="relative">
                                <GlobeAltIcon class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ivt-ink-soft/60" />
                                <input v-model="form.website" type="text" placeholder="https://" :class="iconInputClass" />
                            </div>
                            <p v-if="form.errors.website" class="mt-1.5 text-sm text-danger-500">{{ form.errors.website }}</p>
                        </div>
                    </div>
                    </div>
                </section>

                <section class="grid gap-x-10 gap-y-5 p-5 sm:p-7 lg:grid-cols-[13rem_1fr]">
                    <header>
                        <span class="mb-3 flex h-9 w-9 items-center justify-center rounded-full bg-ivt-paper-2 text-primary"><MapPinIcon class="h-5 w-5" /></span>
                        <h3 class="text-base font-semibold text-ivt-ink">Locație</h3>
                        <p class="mt-1 text-sm leading-relaxed text-ivt-ink-soft">Adresa și zona în care activezi.</p>
                    </header>
                    <div class="min-w-0">
                    <div class="space-y-5">
                        <div>
                            <label :class="labelClass">Adresă</label>
                            <input v-model="form.address" type="text" :class="inputClass" />
                        </div>
                        <CountyLocalitySelect
                            v-model:county-id="form.county_id"
                            v-model:locality-id="form.locality_id"
                            :counties="counties"
                            theme="brand"
                            :county-error="form.errors.county_id"
                            :locality-error="form.errors.locality_id"
                        />
                    </div>
                    </div>
                </section>

                <section class="grid gap-x-10 gap-y-5 p-5 sm:p-7 lg:grid-cols-[13rem_1fr]">
                    <header>
                        <span class="mb-3 flex h-9 w-9 items-center justify-center rounded-full bg-ivt-paper-2 text-primary"><LinkIcon class="h-5 w-5" /></span>
                        <h3 class="text-base font-semibold text-ivt-ink">Rețele sociale</h3>
                        <p class="mt-1 text-sm leading-relaxed text-ivt-ink-soft">Linkuri către paginile tale, opțional.</p>
                    </header>
                    <div class="min-w-0">
                    <div class="grid sm:grid-cols-3 gap-4">
                        <div v-for="network in [{ key: 'facebook', label: 'Facebook' }, { key: 'instagram', label: 'Instagram' }, { key: 'tiktok', label: 'TikTok' }]" :key="network.key">
                            <label :class="labelClass">{{ network.label }}</label>
                            <input v-model="form[network.key]" type="text" :placeholder="`${network.label} URL`" :class="inputClass" />
                            <p v-if="form.errors[network.key]" class="mt-1.5 text-sm text-danger-500">{{ form.errors[network.key] }}</p>
                        </div>
                    </div>
                    </div>
                </section>
                </div>

                <!-- Sticky action bar -->
                <div class="sticky bottom-[4.5rem] lg:bottom-3 z-10 flex items-center gap-3 rounded-2xl border border-ivt-line bg-white/90 px-5 py-3.5 shadow-[0_8px_30px_-10px_rgba(26,20,51,0.2)] backdrop-blur-xl">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/25 transition-all duration-200 hover:brightness-110 hover:shadow-glow-violet hover:shadow-glow-primary active:scale-[0.98] disabled:opacity-60 disabled:pointer-events-none"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        Salvează profilul
                    </button>
                    <span v-if="form.recentlySuccessful" class="inline-flex items-center gap-1.5 text-sm text-success-600">
                        <CheckCircleIcon class="h-4 w-4" /> Salvat.
                    </span>
                </div>
            </div>

            <!-- Completion -->
            <aside class="xl:sticky xl:top-20 rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
                <p class="text-sm font-semibold text-ivt-ink">Completitudinea profilului</p>
                <p class="mt-3 text-3xl font-semibold tabular-nums text-ivt-ink">{{ score }}%</p>
                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-ivt-paper-2">
                    <div class="h-full rounded-full bg-gradient-to-r from-primary-bright to-ivt-accent-bright transition-all duration-500" :style="`width: ${score}%`"></div>
                </div>
                <p class="mt-3 text-xs leading-relaxed text-ivt-ink-soft">
                    {{ score >= 100 ? 'Profilul tău e complet.' : 'Un profil complet, cu logo, copertă și date de contact, inspiră mai multă încredere clienților.' }}
                </p>
            </aside>
        </form>
    </ProviderLayout>
</template>
