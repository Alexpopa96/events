<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import { useToast } from 'vue-toastification';
import {
    ChevronRightIcon,
    ChevronLeftIcon,
    MagnifyingGlassIcon,
    CalendarDaysIcon,
    UserGroupIcon,
    HeartIcon,
    SparklesIcon,
    GiftIcon,
    BriefcaseIcon,
    MusicalNoteIcon,
    EllipsisHorizontalIcon,
    PhoneIcon,
    ChatBubbleLeftRightIcon,
    EnvelopeIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon } from '@heroicons/vue/24/solid';
import SiteHeader from '@/Components/SiteHeader.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import CountyLocalitySelect from '@/Components/CountyLocalitySelect.vue';
import { categoryIcon } from '@/Composables/useCategoryIcon';

const toast = useToast();
const page = usePage();

const props = defineProps({
    categories: { type: Array, default: () => [] },
    counties: { type: Array, default: () => [] },
});

const steps = [
    { title: 'Detalii despre căutare', subtitle: 'Ce serviciu cauți' },
    { title: 'Despre eveniment', subtitle: 'Tip, locație și dată' },
    { title: 'Buget & preferințe', subtitle: 'Buget și alte preferințe' },
    { title: 'Informații de contact', subtitle: 'Cum te pot contacta' },
    { title: 'Verificare & publicare', subtitle: 'Ultimul pas' },
];
const currentStep = ref(0);
const furthestStep = ref(0);

const eventTypes = [
    { value: 'nunta', label: 'Nuntă', subtitle: 'Ceremonie și petrecere', icon: HeartIcon },
    { value: 'botez', label: 'Botez', subtitle: 'Ceremonie și petrecere', icon: SparklesIcon },
    { value: 'aniversare', label: 'Aniversare', subtitle: 'Zi de naștere, jubileu', icon: GiftIcon },
    { value: 'corporate', label: 'Corporate', subtitle: 'Eveniment de companie', icon: BriefcaseIcon },
    { value: 'petrecere-privata', label: 'Petrecere privată', subtitle: 'Reuniune sau sesiune foto', icon: UserGroupIcon },
    { value: 'concert', label: 'Concert / Festival', subtitle: 'Spectacol sau festival', icon: MusicalNoteIcon },
    { value: 'altul', label: 'Altele', subtitle: 'Alt tip de eveniment', icon: EllipsisHorizontalIcon },
];

const guestCountOptions = ['Sub 50', '50 - 100', '100 - 200', 'Peste 200'];

const preferenceOptions = [
    { value: 'Preț competitiv', desc: 'Vreau oferte în bugetul stabilit' },
    { value: 'Experiență dovedită', desc: 'Portofoliu bogat, ani de activitate' },
    { value: 'Disponibilitate rapidă', desc: 'Răspuns și confirmare în cel mai scurt timp' },
    { value: 'Recenzii excelente', desc: 'Rating minim 4.5 din partea altor clienți' },
];

const budgetChips = [
    { label: 'Sub 1.500 lei', min: 0, max: 1500 },
    { label: '1.500 – 3.000 lei', min: 1500, max: 3000 },
    { label: '3.000 – 5.000 lei', min: 3000, max: 5000 },
    { label: 'Peste 5.000 lei', min: 5000, max: '' },
];

const contactMethods = [
    { value: 'Telefon', icon: PhoneIcon },
    { value: 'WhatsApp', icon: ChatBubbleLeftRightIcon },
    { value: 'Email', icon: EnvelopeIcon },
];

const DRAFT_KEY = 'quote-request-draft';

const form = useForm({
    category_id: '',
    title: '',
    message: '',
    event_type: '',
    event_date: '',
    county_id: null,
    locality_id: null,
    guest_count: '',
    budget_range: '',
    preferences: [],
    notes: '',
    name: page.props.auth.user.name,
    email: page.props.auth.user.email,
    phone: '',
    contact_method: 'Telefon',
    platform_only: false,
    terms: false,
});

const categorySearch = ref('');
const filteredCategories = computed(() => {
    const term = categorySearch.value.trim().toLowerCase();
    if (!term) return props.categories;
    return props.categories.filter((category) => category.name.toLowerCase().includes(term));
});

const flexibleDate = ref(false);
watch(flexibleDate, (flexible) => {
    if (flexible) form.event_date = '';
});

// Only the composed string lands in form.budget_range (what the backend
// stores), so a restored draft shows the right text even though these two
// inputs themselves reset to blank.
const budgetMin = ref('');
const budgetMax = ref('');
watch([budgetMin, budgetMax], () => {
    if (!budgetMin.value && !budgetMax.value) {
        form.budget_range = '';
        return;
    }
    form.budget_range = `${budgetMin.value || '0'} – ${budgetMax.value || '∞'} lei`;
});
const applyBudgetChip = (chip) => {
    budgetMin.value = chip.min;
    budgetMax.value = chip.max;
};

onMounted(() => {
    const saved = localStorage.getItem(DRAFT_KEY);
    if (!saved) return;

    try {
        const draft = JSON.parse(saved);
        Object.keys(draft).forEach((key) => {
            if (key in form) form[key] = draft[key];
        });
    } catch {
        localStorage.removeItem(DRAFT_KEY);
    }
});

const saveDraft = () => {
    const { terms, ...draft } = form.data();
    localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
};

const saveDraftManually = () => {
    saveDraft();
    toast.success('Ciornă salvată.');
};

const togglePreference = (pref) => {
    const index = form.preferences.indexOf(pref);
    if (index === -1) form.preferences.push(pref);
    else form.preferences.splice(index, 1);
};

const stepValid = computed(() => [
    !!form.category_id && !!form.title && !!form.message,
    !!form.event_type && (flexibleDate.value || !!form.event_date) && !!form.county_id && !!form.locality_id,
    true,
    !!form.name && !!form.email && !!form.phone,
    true,
]);

const goToStep = (index) => {
    if (index <= furthestStep.value) currentStep.value = index;
};

const nextStep = () => {
    if (!stepValid.value[currentStep.value]) return;
    saveDraft();

    if (currentStep.value === steps.length - 1) {
        if (!form.terms) {
            toast.error('Trebuie să accepți termenii și condițiile pentru a publica cererea.');
            return;
        }
        form.post(route('quote-requests.store'), {
            onSuccess: () => localStorage.removeItem(DRAFT_KEY),
        });
        return;
    }

    currentStep.value += 1;
    furthestStep.value = Math.max(furthestStep.value, currentStep.value);
};

const prevStep = () => {
    if (currentStep.value > 0) currentStep.value -= 1;
};

const categoryName = computed(() => props.categories.find((c) => c.id === form.category_id)?.name ?? '–');
const eventTypeLabel = computed(() => eventTypes.find((t) => t.value === form.event_type)?.label ?? '–');
const eventDateLabel = computed(() => {
    if (flexibleDate.value) return 'Flexibilă';
    if (!form.event_date) return '–';
    return new Intl.DateTimeFormat('ro-RO', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(form.event_date));
});

// Kept in sync with CountyLocalitySelect's own AJAX-loaded list, purely so
// the review step can display the locality name (the component itself only
// exposes the selected id via v-model).
const localityOptions = ref([]);
watch(() => form.county_id, async (countyId) => {
    localityOptions.value = [];
    if (!countyId) return;
    const { data } = await axios.get('/localitati', { params: { judet_id: countyId } });
    localityOptions.value = data;
});

const countyName = computed(() => props.counties.find((c) => c.id === form.county_id)?.name ?? null);
const localityName = computed(() => localityOptions.value.find((l) => l.id === form.locality_id)?.name ?? null);
const locationLabel = computed(() => (localityName.value && countyName.value ? `${localityName.value}, ${countyName.value}` : '–'));

watch(
    () => [form.category_id, form.title, form.message, form.event_type, form.event_date, form.county_id, form.locality_id, form.guest_count, form.budget_range, form.preferences, form.notes, form.phone, form.contact_method, form.platform_only],
    saveDraft,
    { deep: true },
);

const progressPercent = computed(() => ((currentStep.value + 1) / steps.length) * 100);

const fieldClasses = 'w-full rounded-xl border border-ivt-line bg-white px-4 py-3 text-[14.5px] text-ivt-ink outline-none transition-colors duration-150 focus:border-ivt-gold focus:ring-2 focus:ring-ivt-gold/20';
const minDate = new Date(Date.now() + 86400000);
</script>

<template>
    <Head title="Publică o cerere de ofertă — Invita" />

    <div class="bg-ivt-paper font-invita text-ivt-ink antialiased">
        <SiteHeader />

        <main>
            <!-- PAGE HERO -->
            <section class="relative overflow-hidden border-b border-ivt-line bg-ivt-paper-2 py-12">
                <div
                    class="pointer-events-none absolute inset-x-[-10%] -top-[40%] h-[150%]"
                    style="background: radial-gradient(50% 60% at 15% 0%, rgba(168,127,46,0.10), transparent 60%), radial-gradient(45% 45% at 95% 10%, rgba(124,46,59,0.08), transparent 60%);"
                />

                <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
                    <p class="flex flex-wrap items-center gap-2 text-[13px] text-ivt-ink-faint">
                        <Link :href="route('home')" class="text-ivt-ink-soft transition-colors hover:text-ivt-wine">Acasă</Link>
                        <span>›</span>
                        <span>Cereri de ofertă</span>
                        <span>›</span>
                        <span>Publică o cerere</span>
                    </p>

                    <p class="mt-4 flex items-center gap-2.5 text-xs font-bold uppercase tracking-[0.18em] text-ivt-wine">
                        <span class="inline-block h-px w-[22px] bg-ivt-gold" /> Cerere de ofertă
                    </p>
                    <h1 class="mt-3.5 max-w-3xl font-serif text-[clamp(28px,3.4vw,38px)] font-medium leading-[1.05] tracking-[-0.01em] text-ivt-ink">
                        Spune-ne ce cauți, iar furnizorii potriviți te contactează direct.
                    </h1>
                    <p class="mt-2.5 max-w-xl text-[15px] text-ivt-ink-soft">
                        Durează sub 3 minute. Cererea ta va fi vizibilă furnizorilor din categoria și zona aleasă — tu alegi cu cine discuți mai departe.
                    </p>

                    <!-- Mobile progress -->
                    <div class="mt-6 lg:hidden">
                        <div class="h-1.5 overflow-hidden rounded-full bg-ivt-paper-3">
                            <div class="h-full rounded-full bg-gradient-to-r from-ivt-gold to-ivt-wine transition-all duration-300" :style="{ width: progressPercent + '%' }" />
                        </div>
                        <p class="mt-2 text-[12.5px] text-ivt-ink-faint">Pasul {{ currentStep + 1 }} din {{ steps.length }} — {{ steps[currentStep].title }}</p>
                    </div>
                </div>
            </section>

            <!-- WIZARD -->
            <section class="py-14 pb-28">
                <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-6 lg:grid-cols-[250px_1fr] lg:px-8">

                    <!-- STEPPER -->
                    <aside class="hidden flex-col lg:sticky lg:top-24 lg:flex">
                        <div v-for="(step, index) in steps" :key="step.title" class="relative flex gap-3.5 py-3.5">
                            <span v-if="index < steps.length - 1" class="absolute bottom-[-14px] left-[15px] top-11 w-px" :class="index < currentStep ? 'bg-ivt-gold-bright' : 'bg-ivt-line'" />

                            <button
                                type="button"
                                :disabled="index > furthestStep"
                                @click="goToStep(index)"
                                class="flex flex-1 items-start gap-3.5 text-left"
                                :class="index > furthestStep ? 'cursor-not-allowed' : 'cursor-pointer'"
                            >
                                <span
                                    class="z-10 flex h-8 w-8 flex-none items-center justify-center rounded-full border text-[13px] font-bold transition-colors duration-150"
                                    :class="index === currentStep
                                        ? 'border-ivt-ink bg-ivt-ink text-white'
                                        : index < currentStep
                                            ? 'border-ivt-gold-bright bg-ivt-gold-bright text-[#221708]'
                                            : 'border-ivt-line bg-white text-ivt-ink-faint'"
                                >
                                    <CheckIcon v-if="index < currentStep" class="h-4 w-4" />
                                    <template v-else>{{ index + 1 }}</template>
                                </span>
                                <span class="min-w-0 pt-0.5">
                                    <span class="block text-sm font-semibold" :class="index === currentStep ? 'text-ivt-ink' : 'text-ivt-ink-faint'">{{ step.title }}</span>
                                    <span class="block text-xs text-ivt-ink-faint">{{ step.subtitle }}</span>
                                </span>
                            </button>
                        </div>
                    </aside>

                    <!-- PANEL -->
                    <div class="rounded-[22px] border border-ivt-line bg-white p-6 shadow-ivt-soft sm:p-10">
                        <form novalidate @submit.prevent="nextStep">

                            <!-- STEP 1 -->
                            <template v-if="currentStep === 0">
                                <p class="text-xs font-bold uppercase tracking-[0.08em] text-ivt-sage">Pasul 1 din 5</p>
                                <h2 class="mt-2 font-serif text-2xl font-medium text-ivt-ink">Ce serviciu cauți?</h2>
                                <p class="mt-2 max-w-lg text-[14.5px] text-ivt-ink-soft">Alege categoria principală pentru care vrei oferte. Poți publica o cerere separată pentru fiecare serviciu.</p>

                                <div class="mt-7 space-y-6">
                                    <div>
                                        <label class="mb-2.5 block text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Categorie</label>
                                        <div class="relative mb-4">
                                            <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-ivt-ink-faint" />
                                            <input v-model="categorySearch" type="text" placeholder="Caută o categorie…" :class="[fieldClasses, 'pl-11']" />
                                        </div>
                                        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-4">
                                            <label v-for="category in filteredCategories" :key="category.id" class="relative cursor-pointer">
                                                <input v-model="form.category_id" type="radio" name="category" :value="category.id" class="peer sr-only" />
                                                <span class="flex flex-col items-center gap-2 rounded-xl border border-ivt-line px-2.5 py-4 text-center transition-all duration-150 peer-checked:-translate-y-0.5 peer-checked:border-ivt-gold peer-checked:bg-ivt-paper-2 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-ivt-gold">
                                                    <component :is="categoryIcon(category.slug)" class="h-[22px] w-[22px] text-ivt-wine" stroke-width="1.4" />
                                                    <span class="text-[12.5px] font-semibold text-ivt-ink">{{ category.name }}</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-2.5 block text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Titlul cererii</label>
                                        <input v-model="form.title" type="text" maxlength="150" placeholder="Ex. Caut fotograf pentru nuntă în Cluj" :class="fieldClasses" />
                                        <p class="mt-1.5 text-xs text-ivt-ink-faint">Un titlu scurt și clar — apare primul în lista văzută de furnizori.</p>
                                    </div>

                                    <div>
                                        <label class="mb-2.5 block text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Descriere</label>
                                        <textarea v-model="form.message" rows="4" maxlength="1000" placeholder="Adaugă orice detaliu ajută furnizorii să înțeleagă ce cauți — stil dorit, durata evenimentului, cerințe speciale…" :class="[fieldClasses, 'resize-none']" />
                                        <p class="mt-1 text-right text-xs text-ivt-ink-faint/80">{{ form.message.length }}/1000</p>
                                    </div>
                                </div>
                            </template>

                            <!-- STEP 2 -->
                            <template v-else-if="currentStep === 1">
                                <p class="text-xs font-bold uppercase tracking-[0.08em] text-ivt-sage">Pasul 2 din 5</p>
                                <h2 class="mt-2 font-serif text-2xl font-medium text-ivt-ink">Despre eveniment</h2>
                                <p class="mt-2 max-w-lg text-[14.5px] text-ivt-ink-soft">Aceste detalii ajută furnizorii să vadă rapid dacă sunt disponibili și potriviți.</p>

                                <div class="mt-7 space-y-6">
                                    <div>
                                        <label class="mb-2.5 block text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Tip eveniment</label>
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                            <label v-for="type in eventTypes" :key="type.value" class="relative cursor-pointer">
                                                <input v-model="form.event_type" type="radio" name="eventType" :value="type.value" class="peer sr-only" />
                                                <span class="flex flex-col gap-2.5 rounded-2xl border border-ivt-line p-5 transition-all duration-150 peer-checked:-translate-y-0.5 peer-checked:border-ivt-gold peer-checked:bg-ivt-paper-2 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-ivt-gold">
                                                    <component :is="type.icon" class="h-[26px] w-[26px] text-ivt-wine" stroke-width="1.4" />
                                                    <span class="text-[14.5px] font-semibold text-ivt-ink">{{ type.label }}</span>
                                                    <span class="text-xs text-ivt-ink-faint">{{ type.subtitle }}</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-2.5 block text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Locația evenimentului</label>
                                        <CountyLocalitySelect v-model:county-id="form.county_id" v-model:locality-id="form.locality_id" :counties="counties" />
                                    </div>

                                    <div class="grid gap-6 sm:grid-cols-2">
                                        <div class="brand-datepicker">
                                            <label class="mb-2.5 block text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Data evenimentului</label>
                                            <DatePicker v-model="form.event_date" mode="date" :model-config="{ type: 'string', mask: 'YYYY-MM-DD' }" :min-date="minDate" :first-day-of-week="2" locale="ro" :disabled="flexibleDate">
                                                <template #default="{ inputValue, inputEvents }">
                                                    <div class="relative">
                                                        <CalendarDaysIcon class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-ivt-wine" />
                                                        <input
                                                            readonly
                                                            :value="flexibleDate ? '' : inputValue"
                                                            v-on="flexibleDate ? {} : inputEvents"
                                                            :disabled="flexibleDate"
                                                            placeholder="Selectează data evenimentului"
                                                            :class="[fieldClasses, 'cursor-pointer pl-11 disabled:cursor-not-allowed disabled:opacity-50']"
                                                        />
                                                    </div>
                                                </template>
                                            </DatePicker>
                                            <label class="mt-3 flex items-center gap-2.5 text-[13.5px] text-ivt-ink-soft">
                                                <input v-model="flexibleDate" type="checkbox" class="h-[15px] w-[15px] rounded border-ivt-line text-ivt-wine focus:ring-ivt-wine/30" />
                                                Data este flexibilă / încă nu am stabilit-o
                                            </label>
                                        </div>

                                        <div>
                                            <label class="mb-2.5 block text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Număr aproximativ de invitați</label>
                                            <select v-model="form.guest_count" :class="fieldClasses">
                                                <option value="">Selectează numărul invitaților</option>
                                                <option v-for="option in guestCountOptions" :key="option" :value="option">{{ option }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- STEP 3 -->
                            <template v-else-if="currentStep === 2">
                                <p class="text-xs font-bold uppercase tracking-[0.08em] text-ivt-sage">Pasul 3 din 5</p>
                                <h2 class="mt-2 font-serif text-2xl font-medium text-ivt-ink">Buget & preferințe</h2>
                                <p class="mt-2 max-w-lg text-[14.5px] text-ivt-ink-soft">Un buget orientativ ajută furnizorii să îți trimită oferte relevante, fără timp pierdut.</p>

                                <div class="mt-7 space-y-6">
                                    <div>
                                        <label class="mb-2.5 block text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Buget estimat (lei)</label>
                                        <div class="flex max-w-md items-center gap-3">
                                            <input v-model.number="budgetMin" type="number" min="0" placeholder="Minim" :class="fieldClasses" />
                                            <span class="text-ivt-ink-faint">–</span>
                                            <input v-model.number="budgetMax" type="number" min="0" placeholder="Maxim" :class="fieldClasses" />
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <button
                                                v-for="chip in budgetChips"
                                                :key="chip.label"
                                                type="button"
                                                @click="applyBudgetChip(chip)"
                                                class="rounded-full border border-ivt-line px-3.5 py-1.5 text-[12.5px] font-semibold text-ivt-ink-soft transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
                                                :class="{ 'border-ivt-gold bg-ivt-paper-2 text-ivt-wine': budgetMin === chip.min && budgetMax === chip.max }"
                                            >
                                                {{ chip.label }}
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-2.5 block text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Ce este important pentru tine?</label>
                                        <div class="flex flex-col gap-2.5">
                                            <label v-for="pref in preferenceOptions" :key="pref.value" class="relative cursor-pointer">
                                                <input type="checkbox" :checked="form.preferences.includes(pref.value)" class="peer sr-only" @change="togglePreference(pref.value)" />
                                                <span class="flex items-center gap-3.5 rounded-xl border border-ivt-line px-4 py-3.5 transition-colors duration-150 peer-checked:border-ivt-gold peer-checked:bg-ivt-paper-2">
                                                    <span
                                                        class="flex h-5 w-5 flex-none items-center justify-center rounded-md border border-ivt-line text-transparent transition-colors duration-150"
                                                        :class="{ 'border-ivt-ink bg-ivt-ink text-ivt-gold-bright': form.preferences.includes(pref.value) }"
                                                    >
                                                        <CheckIcon class="h-3.5 w-3.5" />
                                                    </span>
                                                    <span>
                                                        <span class="block text-sm font-semibold text-ivt-ink">{{ pref.value }}</span>
                                                        <span class="block text-xs text-ivt-ink-faint">{{ pref.desc }}</span>
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-2.5 block text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Alte preferințe sau detalii</label>
                                        <textarea v-model="form.notes" rows="3" maxlength="500" placeholder="Ex. prefer un stil de fotografie natural, sau am nevoie de flexibilitate pe orar…" :class="[fieldClasses, 'resize-none']" />
                                        <p class="mt-1 text-right text-xs text-ivt-ink-faint/80">{{ (form.notes ?? '').length }}/500</p>
                                    </div>
                                </div>
                            </template>

                            <!-- STEP 4 -->
                            <template v-else-if="currentStep === 3">
                                <p class="text-xs font-bold uppercase tracking-[0.08em] text-ivt-sage">Pasul 4 din 5</p>
                                <h2 class="mt-2 font-serif text-2xl font-medium text-ivt-ink">Informații de contact</h2>
                                <p class="mt-2 max-w-lg text-[14.5px] text-ivt-ink-soft">Aceste date sunt vizibile doar furnizorilor care răspund la cererea ta.</p>

                                <div class="mt-7 space-y-6">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-2.5 block text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Nume complet</label>
                                            <input v-model="form.name" type="text" placeholder="Ex. Ioana Popescu" :class="fieldClasses" />
                                        </div>
                                        <div>
                                            <label class="mb-2.5 block text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Telefon</label>
                                            <input v-model="form.phone" type="tel" placeholder="Ex. 0712 345 678" :class="fieldClasses" />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-2.5 block text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Email</label>
                                        <input v-model="form.email" type="email" placeholder="Ex. ioana@email.com" :class="fieldClasses" />
                                    </div>

                                    <div>
                                        <label class="mb-2.5 block text-[12.5px] font-bold uppercase tracking-[0.06em] text-ivt-ink-soft">Metodă preferată de contact</label>
                                        <div class="flex flex-wrap gap-2.5">
                                            <label v-for="method in contactMethods" :key="method.value" class="relative cursor-pointer">
                                                <input v-model="form.contact_method" type="radio" name="method" :value="method.value" class="peer sr-only" />
                                                <span class="flex items-center gap-2 rounded-full border border-ivt-line px-5 py-2.5 text-[13.5px] font-semibold text-ivt-ink-soft transition-all duration-150 peer-checked:border-ivt-ink peer-checked:bg-ivt-ink peer-checked:text-white">
                                                    <component :is="method.icon" class="h-4 w-4" />
                                                    {{ method.value }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    <label class="flex items-center gap-2.5 text-[13.5px] text-ivt-ink-soft">
                                        <input v-model="form.platform_only" type="checkbox" class="h-[15px] w-[15px] rounded border-ivt-line text-ivt-wine focus:ring-ivt-wine/30" />
                                        Prefer să primesc ofertele doar prin mesaje în platformă, nu direct pe telefon
                                    </label>
                                </div>
                            </template>

                            <!-- STEP 5 -->
                            <template v-else>
                                <p class="text-xs font-bold uppercase tracking-[0.08em] text-ivt-sage">Pasul 5 din 5</p>
                                <h2 class="mt-2 font-serif text-2xl font-medium text-ivt-ink">Verifică și publică</h2>
                                <p class="mt-2 max-w-lg text-[14.5px] text-ivt-ink-soft">Aruncă o ultimă privire — poți edita orice secțiune înainte de a publica cererea.</p>

                                <div class="mt-7 space-y-4">
                                    <div class="rounded-2xl border border-ivt-line p-5">
                                        <div class="mb-3.5 flex items-center justify-between">
                                            <h4 class="text-[13px] font-bold uppercase tracking-[0.05em] text-ivt-ink-soft">Căutare</h4>
                                            <button type="button" @click="goToStep(0)" class="text-[12.5px] font-semibold text-ivt-wine hover:underline">Editează</button>
                                        </div>
                                        <div class="flex justify-between gap-4 border-b border-ivt-line py-2 text-sm"><span class="text-ivt-ink-faint">Categorie</span><span class="text-right font-semibold text-ivt-ink">{{ categoryName }}</span></div>
                                        <div class="flex justify-between gap-4 py-2 text-sm"><span class="text-ivt-ink-faint">Titlu</span><span class="text-right font-semibold text-ivt-ink">{{ form.title || '–' }}</span></div>
                                    </div>

                                    <div class="rounded-2xl border border-ivt-line p-5">
                                        <div class="mb-3.5 flex items-center justify-between">
                                            <h4 class="text-[13px] font-bold uppercase tracking-[0.05em] text-ivt-ink-soft">Eveniment</h4>
                                            <button type="button" @click="goToStep(1)" class="text-[12.5px] font-semibold text-ivt-wine hover:underline">Editează</button>
                                        </div>
                                        <div class="flex justify-between gap-4 border-b border-ivt-line py-2 text-sm"><span class="text-ivt-ink-faint">Tip eveniment</span><span class="text-right font-semibold text-ivt-ink">{{ eventTypeLabel }}</span></div>
                                        <div class="flex justify-between gap-4 border-b border-ivt-line py-2 text-sm"><span class="text-ivt-ink-faint">Locație</span><span class="text-right font-semibold text-ivt-ink">{{ locationLabel }}</span></div>
                                        <div class="flex justify-between gap-4 py-2 text-sm"><span class="text-ivt-ink-faint">Dată</span><span class="text-right font-semibold text-ivt-ink">{{ eventDateLabel }}</span></div>
                                    </div>

                                    <div class="rounded-2xl border border-ivt-line p-5">
                                        <div class="mb-3.5 flex items-center justify-between">
                                            <h4 class="text-[13px] font-bold uppercase tracking-[0.05em] text-ivt-ink-soft">Buget & preferințe</h4>
                                            <button type="button" @click="goToStep(2)" class="text-[12.5px] font-semibold text-ivt-wine hover:underline">Editează</button>
                                        </div>
                                        <div class="flex justify-between gap-4 border-b border-ivt-line py-2 text-sm"><span class="text-ivt-ink-faint">Buget</span><span class="text-right font-semibold text-ivt-ink">{{ form.budget_range || 'Nespecificat' }}</span></div>
                                        <div class="flex justify-between gap-4 py-2 text-sm"><span class="text-ivt-ink-faint">Preferințe</span><span class="text-right font-semibold text-ivt-ink">{{ form.preferences.length ? form.preferences.join(', ') : 'Niciuna selectată' }}</span></div>
                                    </div>

                                    <div class="rounded-2xl border border-ivt-line p-5">
                                        <div class="mb-3.5 flex items-center justify-between">
                                            <h4 class="text-[13px] font-bold uppercase tracking-[0.05em] text-ivt-ink-soft">Contact</h4>
                                            <button type="button" @click="goToStep(3)" class="text-[12.5px] font-semibold text-ivt-wine hover:underline">Editează</button>
                                        </div>
                                        <div class="flex justify-between gap-4 border-b border-ivt-line py-2 text-sm"><span class="text-ivt-ink-faint">Nume</span><span class="text-right font-semibold text-ivt-ink">{{ form.name || '–' }}</span></div>
                                        <div class="flex justify-between gap-4 border-b border-ivt-line py-2 text-sm"><span class="text-ivt-ink-faint">Telefon</span><span class="text-right font-semibold text-ivt-ink">{{ form.phone || '–' }}</span></div>
                                        <div class="flex justify-between gap-4 py-2 text-sm"><span class="text-ivt-ink-faint">Metodă preferată</span><span class="text-right font-semibold text-ivt-ink">{{ form.contact_method || '–' }}</span></div>
                                    </div>
                                </div>

                                <div class="mt-6 flex items-start gap-3 border-t border-ivt-line pt-6">
                                    <input v-model="form.terms" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-ivt-line text-ivt-wine focus:ring-ivt-wine/30" />
                                    <label class="text-[13.5px] leading-relaxed text-ivt-ink-soft">
                                        Sunt de acord cu <Link :href="route('terms.show')" class="text-ivt-wine underline">Termenii și condițiile</Link> platformei și confirm că datele introduse sunt corecte. Înțeleg că Invita nu intermediază rezervări sau plăți.
                                    </label>
                                </div>

                                <div class="mt-6 flex items-start gap-3.5 rounded-2xl bg-ivt-paper-2 p-5">
                                    <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-ivt-ink text-ivt-gold-bright"><CheckIcon class="h-4 w-4" /></span>
                                    <p class="text-[13.5px] leading-relaxed text-ivt-ink-soft">
                                        <b class="text-ivt-ink">Ce urmează:</b> cererea ta devine vizibilă imediat furnizorilor din categoria și zona aleasă. Vei primi oferte direct pe metoda de contact preferată, de obicei în câteva ore.
                                    </p>
                                </div>
                            </template>

                            <!-- NAV -->
                            <div class="mt-8 flex items-center justify-between border-t border-ivt-line pt-6">
                                <button
                                    v-if="currentStep > 0"
                                    type="button"
                                    @click="prevStep"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-ivt-line px-6 py-3 text-sm font-semibold text-ivt-ink transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
                                >
                                    <ChevronLeftIcon class="h-4 w-4" /> Înapoi
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    @click="saveDraftManually"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-ivt-line px-6 py-3 text-sm font-semibold text-ivt-ink transition-colors duration-150 hover:border-ivt-gold hover:text-ivt-wine"
                                >
                                    Salvează ciornă
                                </button>

                                <span class="hidden text-[12.5px] text-ivt-ink-faint sm:inline">Pasul {{ currentStep + 1 }} din {{ steps.length }}</span>

                                <button
                                    type="submit"
                                    :disabled="!stepValid[currentStep] || form.processing"
                                    class="inline-flex items-center gap-1.5 rounded-full px-6 py-3 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 disabled:pointer-events-none disabled:opacity-45 disabled:hover:translate-y-0"
                                    :class="currentStep === steps.length - 1
                                        ? 'bg-gradient-to-b from-ivt-wine-bright to-ivt-wine hover:shadow-[0_14px_28px_-12px_rgba(124,46,59,0.35)]'
                                        : 'bg-gradient-to-b from-ivt-ink-2 to-ivt-ink hover:shadow-[0_14px_28px_-12px_rgba(22,40,31,0.4)]'"
                                >
                                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                    </svg>
                                    {{ currentStep === steps.length - 1 ? 'Publică cererea' : 'Continuă' }}
                                    <ChevronRightIcon v-if="!form.processing && currentStep < steps.length - 1" class="h-4 w-4" />
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>

<style scoped>
.brand-datepicker :deep(.vc-container) {
    --vc-accent-50: #F6F3EB;
    --vc-accent-100: #EFEADB;
    --vc-accent-200: #E4D6B8;
    --vc-accent-300: #D9C08C;
    --vc-accent-400: #C9A24F;
    --vc-accent-500: #A87F2E;
    --vc-accent-600: #8C6825;
    --vc-accent-700: #6F511D;
    --vc-accent-800: #523C16;
    --vc-accent-900: #38290F;
    --vc-font-family: inherit;
    border: none;
    border-radius: 1rem;
    box-shadow: 0 20px 50px -12px rgba(22, 40, 31, 0.25);
}
</style>
