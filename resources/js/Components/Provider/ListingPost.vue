<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import {
    BuildingStorefrontIcon,
    ChatBubbleLeftRightIcon,
    ChatBubbleOvalLeftEllipsisIcon,
    DocumentDuplicateIcon,
    EllipsisHorizontalIcon,
    EyeIcon,
    MapPinIcon,
    PencilIcon,
    PencilSquareIcon,
    PhoneIcon,
    PhotoIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import { categoryIcon } from '@/Composables/useCategoryIcon';
import { formatListingPrice } from '@/Composables/useListingPrice';

const props = defineProps({
    listing: { type: Object, required: true },
    // { views_count, phone_clicks, whatsapp_clicks } from the events log.
    stats: { type: Object, default: null },
    // Static render for the edit/create preview: no links, menu or reactions.
    preview: { type: Boolean, default: false },
});

const emit = defineEmits(['delete']);

const toast = useToast();

const page = usePage();
const nav = computed(() => page.props.providerNav);

const statusMeta = {
    draft: { label: 'Ciornă', class: 'bg-ivt-paper-2 text-ivt-ink-soft' },
    pending_review: { label: 'În verificare', class: 'bg-ivt-gold/15 text-ivt-gold' },
    published: { label: 'Publicat', class: 'bg-emerald-100 text-emerald-700' },
    rejected: { label: 'Respins', class: 'bg-rose-100 text-rose-700' },
    archived: { label: 'Arhivat', class: 'bg-ivt-paper-2 text-ivt-ink-soft' },
};

const status = computed(() => statusMeta[props.listing.status] ?? { label: props.listing.status, class: 'bg-ivt-paper-2 text-ivt-ink-soft' });
const icon = computed(() => categoryIcon(props.listing.category_slug));
const price = computed(() => formatListingPrice(props.listing));
const location = computed(() => [props.listing.locality?.name, props.listing.county?.name].filter(Boolean).join(', '));

const views = computed(() => props.stats?.views_count ?? props.listing.views_count ?? 0);
const metrics = computed(() => [
    { label: 'Vizualizări', value: views.value, icon: EyeIcon },
    { label: 'Apeluri', value: props.stats?.phone_clicks ?? 0, icon: PhoneIcon },
    { label: 'WhatsApp', value: props.stats?.whatsapp_clicks ?? 0, icon: ChatBubbleOvalLeftEllipsisIcon },
]);

const menuOpen = ref(false);
const expanded = ref(false);

const duplicating = ref(false);
const duplicate = () => {
    menuOpen.value = false;
    if (duplicating.value) return;
    duplicating.value = true;
    router.post(route('provider.listings.duplicate', props.listing.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Anunțul a fost duplicat.'),
        onFinish: () => { duplicating.value = false; },
    });
};

/* ---------- inline price edit ---------- */
const priceTypes = [
    { value: 'fixed', label: 'Preț fix' },
    { value: 'starting_from', label: 'De la' },
    { value: 'per_hour', label: 'Pe oră' },
    { value: 'on_request', label: 'La cerere' },
];

const editingPrice = ref(false);
const priceForm = ref({ price_type: 'on_request', price_from: '', price_to: '' });
const savingPrice = ref(false);

const startPriceEdit = () => {
    priceForm.value = {
        price_type: props.listing.price_type,
        price_from: props.listing.price_from ?? '',
        price_to: props.listing.price_to ?? '',
    };
    editingPrice.value = true;
};

const savePrice = () => {
    savingPrice.value = true;
    router.patch(route('provider.listings.price', props.listing.id), priceForm.value, {
        preserveScroll: true,
        onSuccess: () => { editingPrice.value = false; toast.success('Prețul a fost actualizat.'); },
        onFinish: () => { savingPrice.value = false; },
    });
};
</script>

<template>
    <article class="overflow-hidden rounded-2xl border border-ivt-line bg-white shadow-sm shadow-ivt-ink/5">
        <!-- Post header -->
        <header class="flex items-center gap-3 px-4 pt-3.5 pb-3">
            <span class="flex h-10 w-10 flex-none items-center justify-center overflow-hidden rounded-full border border-ivt-line bg-ivt-paper-2 text-primary">
                <img v-if="nav?.logo_url" :src="nav.logo_url" alt="" class="h-full w-full object-cover" />
                <BuildingStorefrontIcon v-else class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-ivt-ink">{{ nav?.company_name }}</p>
                <p class="flex items-center gap-1.5 text-xs text-ivt-ink-soft">
                    <span>{{ listing.posted_at }}</span>
                    <span aria-hidden="true">·</span>
                    <component :is="icon" class="h-3.5 w-3.5 flex-none" />
                    <span class="truncate">{{ listing.category }}</span>
                </p>
            </div>

            <span class="flex-none rounded-full px-2.5 py-1 text-xs font-semibold" :class="status.class">{{ status.label }}</span>

            <div v-if="!preview" class="relative flex-none">
                <button
                    type="button"
                    @click="menuOpen = !menuOpen"
                    class="flex h-9 w-9 items-center justify-center rounded-full text-ivt-ink-soft transition-colors duration-150 hover:bg-ivt-paper-2 hover:text-ivt-ink"
                    aria-label="Opțiuni anunț"
                    aria-haspopup="menu"
                    :aria-expanded="menuOpen"
                >
                    <EllipsisHorizontalIcon class="h-5 w-5" />
                </button>
                <div v-if="menuOpen" class="fixed inset-0 z-20" @click="menuOpen = false"></div>
                <div v-if="menuOpen" role="menu" class="absolute right-0 top-full z-30 mt-1 w-44 rounded-2xl border border-ivt-line bg-white p-1.5 shadow-[0_12px_32px_-8px_rgba(33,28,39,0.2)]">
                    <Link :href="route('provider.listings.edit', listing.id)" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-ivt-ink-soft transition hover:bg-ivt-paper-2 hover:text-ivt-ink">
                        <PencilSquareIcon class="h-4 w-4" /> Editează
                    </Link>
                    <button type="button" :disabled="duplicating" @click="duplicate" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm text-ivt-ink-soft transition hover:bg-ivt-paper-2 hover:text-ivt-ink disabled:opacity-50">
                        <DocumentDuplicateIcon class="h-4 w-4" /> Duplică
                    </button>
                    <button type="button" @click="menuOpen = false; emit('delete', listing)" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50">
                        <TrashIcon class="h-4 w-4" /> Șterge
                    </button>
                </div>
            </div>
        </header>

        <!-- Media -->
        <component
            :is="preview ? 'div' : Link"
            v-bind="preview ? {} : { href: route('provider.listings.edit', listing.id) }"
            class="group relative block aspect-[4/3] overflow-hidden bg-gradient-to-br from-ivt-paper-2 to-ivt-paper-3"
        >
            <img v-if="listing.cover_url" :src="listing.cover_url" :alt="listing.title" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.02]" loading="lazy" />
            <div v-else class="flex h-full w-full items-center justify-center">
                <component :is="icon" class="h-14 w-14 text-primary/25" />
            </div>
            <span v-if="listing.photos_count > 1" class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-ivt-ink/60 px-2 py-1 text-xs font-medium text-white backdrop-blur">
                <PhotoIcon class="h-3.5 w-3.5" /> {{ listing.photos_count }}
            </span>
        </component>

        <!-- Reactions -->
        <div v-if="!preview" class="flex items-center gap-5 px-4 pt-3 text-ivt-ink-soft">
            <span v-for="metric in metrics" :key="metric.label" class="inline-flex items-center gap-1.5 text-sm tabular-nums" :title="metric.label">
                <component :is="metric.icon" class="h-5 w-5" /> {{ metric.value }}
            </span>
            <Link
                :href="route('provider.messages.index', { listing: listing.id })"
                class="inline-flex items-center gap-1.5 text-sm tabular-nums transition-colors duration-150 hover:text-primary"
                title="Mesaje de la clienți"
            >
                <ChatBubbleLeftRightIcon class="h-5 w-5" /> {{ listing.conversations_count ?? 0 }}
            </Link>
            <Link :href="route('provider.listings.edit', listing.id)" class="ml-auto inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold text-primary transition-colors duration-150 hover:bg-primary/10">
                <PencilSquareIcon class="h-4 w-4" /> Editează
            </Link>
        </div>

        <!-- Caption -->
        <div class="px-4 pb-4" :class="preview ? 'pt-3' : 'pt-2'">
            <div v-if="editingPrice" class="mb-2 space-y-2 rounded-xl bg-ivt-paper-2 p-3" @click.stop>
                <select v-model="priceForm.price_type" class="w-full rounded-lg border-ivt-line py-1.5 text-sm text-ivt-ink focus:border-primary focus:ring-primary">
                    <option v-for="type in priceTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                </select>
                <div v-if="priceForm.price_type !== 'on_request'" class="flex items-center gap-2">
                    <input v-model.number="priceForm.price_from" type="number" min="0" placeholder="Preț" class="w-full rounded-lg border-ivt-line py-1.5 text-sm text-ivt-ink placeholder:text-ivt-ink-soft/50 focus:border-primary focus:ring-primary" />
                    <template v-if="priceForm.price_type === 'fixed'">
                        <span class="flex-none text-xs text-ivt-ink-soft">–</span>
                        <input v-model.number="priceForm.price_to" type="number" min="0" placeholder="până la (opțional)" class="w-full rounded-lg border-ivt-line py-1.5 text-sm text-ivt-ink placeholder:text-ivt-ink-soft/50 focus:border-primary focus:ring-primary" />
                    </template>
                </div>
                <div class="flex gap-2">
                    <button type="button" :disabled="savingPrice" class="rounded-full bg-primary px-3.5 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-primary-bright disabled:opacity-50" @click="savePrice">
                        Salvează
                    </button>
                    <button type="button" class="rounded-full px-3.5 py-1.5 text-xs font-medium text-ivt-ink-soft hover:bg-white" @click="editingPrice = false">
                        Anulează
                    </button>
                </div>
            </div>
            <button
                v-else-if="!preview"
                type="button"
                class="group/price inline-flex items-center gap-1.5 text-base font-semibold text-ivt-ink"
                @click="startPriceEdit"
            >
                {{ price }}
                <PencilIcon class="h-3.5 w-3.5 text-ivt-ink-soft opacity-0 transition-opacity group-hover/price:opacity-100" />
            </button>
            <p v-else class="text-base font-semibold text-ivt-ink">{{ price }}</p>
            <component
                :is="preview ? 'p' : Link"
                v-bind="preview ? {} : { href: route('provider.listings.edit', listing.id) }"
                class="mt-0.5 block text-sm font-medium leading-snug text-ivt-ink transition-colors duration-150"
                :class="!preview && 'hover:text-primary'"
            >
                {{ listing.title || 'Titlul anunțului tău' }}
            </component>
            <p v-if="listing.excerpt" class="mt-1 text-sm leading-relaxed text-ivt-ink-soft" :class="!expanded && 'line-clamp-2'">{{ listing.excerpt }}</p>
            <button v-if="listing.excerpt && listing.excerpt.length > 110" type="button" @click="expanded = !expanded" class="mt-0.5 text-xs font-medium text-ivt-ink-soft/80 hover:text-primary">
                {{ expanded ? 'mai puțin' : 'mai mult' }}
            </button>
            <p v-if="location" class="mt-2 flex items-center gap-1 text-xs text-ivt-ink-soft">
                <MapPinIcon class="h-3.5 w-3.5 flex-none" /> {{ location }}
            </p>
        </div>
    </article>
</template>
