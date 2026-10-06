<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { StarIcon, ChatBubbleLeftEllipsisIcon, ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/solid';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    reviews: Object,
    filter: String,
    counts: Object,
    summary: Object,
});

const tabs = [
    { key: 'all', label: 'Toate' },
    { key: 'unanswered', label: 'Fără răspuns' },
];

const setFilter = (filter) => router.get(route('provider.reviews.index'), { filter }, { preserveScroll: true, replace: true });

const initials = (name) => (name || '')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

/* ---------- reply editor (one at a time) ---------- */
const editingId = ref(null);
const form = useForm({ reply: '' });

const startReply = (review) => {
    editingId.value = review.id;
    form.reply = review.provider_reply ?? '';
    form.clearErrors();
};

const cancelReply = () => {
    editingId.value = null;
    form.reset();
};

const submitReply = (review) => {
    form.put(route('provider.reviews.reply', review.id), {
        preserveScroll: true,
        onSuccess: () => cancelReply(),
    });
};

/* ---------- delete reply ---------- */
const replyToDelete = ref(null);

const confirmDelete = () => {
    router.delete(route('provider.reviews.reply.destroy', replyToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => { replyToDelete.value = null; },
    });
};
</script>

<template>
    <ProviderLayout title="Recenzii">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
            <div class="flex items-center gap-4">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 font-serif text-2xl font-semibold text-primary">
                    {{ summary.rating ?? '—' }}
                </span>
                <div>
                    <p class="text-sm font-semibold text-ivt-ink">Rating mediu</p>
                    <p class="text-sm text-ivt-ink-soft">din {{ summary.total }} {{ summary.total === 1 ? 'recenzie publicată' : 'recenzii publicate' }}</p>
                </div>
            </div>
            <p class="max-w-md text-sm text-ivt-ink-soft">
                Recenziile noi apar aici după ce sunt verificate. Un răspuns politicos și rapid crește încrederea viitorilor clienți.
            </p>
        </div>

        <div class="mb-5 flex flex-wrap gap-2">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                @click="setFilter(tab.key)"
                class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-2 text-sm font-medium transition-all duration-150"
                :class="filter === tab.key
                    ? 'bg-primary text-white shadow-sm shadow-primary/25'
                    : 'bg-white text-ivt-ink-soft ring-1 ring-ivt-line hover:text-primary'"
            >
                {{ tab.label }}
                <span
                    class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1 text-[11px] font-semibold"
                    :class="filter === tab.key ? 'bg-white/20' : 'bg-ivt-paper text-ivt-ink-soft'"
                >{{ counts[tab.key] }}</span>
            </button>
        </div>

        <div v-if="!reviews.data.length" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-ivt-line bg-white px-5 py-14 text-center">
            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                <StarIcon class="h-6 w-6" />
            </span>
            <p class="mt-3 text-sm font-medium text-ivt-ink">
                {{ filter === 'unanswered' ? 'Ai răspuns la toate recenziile' : 'Nicio recenzie încă' }}
            </p>
            <p class="mt-1 max-w-sm text-sm text-ivt-ink-soft">
                {{ filter === 'unanswered' ? 'Felicitări, nu ai nicio recenzie în așteptare de răspuns.' : 'După ce discuți cu un client, acesta poate lăsa o recenzie pe anunțul tău.' }}
            </p>
        </div>

        <ul v-else class="space-y-4">
            <li v-for="review in reviews.data" :key="review.id" class="rounded-2xl border border-ivt-line bg-white p-5 shadow-sm shadow-ivt-ink/5">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary-bright text-xs font-semibold text-white">
                        {{ initials(review.author) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-ivt-ink">{{ review.author }}</p>
                        <p class="truncate text-xs text-ivt-ink-soft">
                            {{ review.created_at }}
                            <template v-if="review.listing_title"> · {{ review.listing_title }}</template>
                        </p>
                    </div>
                    <span class="flex flex-none items-center gap-1 rounded-full bg-primary/5 px-2.5 py-1 text-xs font-semibold text-ivt-ink">
                        <StarIcon class="h-3.5 w-3.5 text-primary" /> {{ review.rating }}
                    </span>
                </div>

                <p v-if="review.comment" class="mt-3 text-sm leading-relaxed text-ivt-ink-soft">{{ review.comment }}</p>
                <p v-else class="mt-3 text-sm italic text-ivt-ink-soft/70">Clientul nu a lăsat un comentariu.</p>

                <!-- existing reply -->
                <div v-if="review.provider_reply && editingId !== review.id" class="mt-4 rounded-xl bg-ivt-paper p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold text-ivt-ink">Răspunsul tău <span class="font-normal text-ivt-ink-soft">· {{ review.provider_replied_at }}</span></p>
                        <div class="flex gap-3 text-xs font-semibold">
                            <button type="button" class="text-primary hover:text-ivt-ink" @click="startReply(review)">Editează</button>
                            <button type="button" class="text-rose-600 hover:text-rose-700" @click="replyToDelete = review">Șterge</button>
                        </div>
                    </div>
                    <p class="mt-1.5 whitespace-pre-line text-sm text-ivt-ink-soft">{{ review.provider_reply }}</p>
                </div>

                <!-- editor -->
                <form v-if="editingId === review.id" class="mt-4" @submit.prevent="submitReply(review)">
                    <label :for="`reply-${review.id}`" class="sr-only">Răspunsul tău</label>
                    <textarea
                        :id="`reply-${review.id}`"
                        v-model="form.reply"
                        rows="3"
                        maxlength="1000"
                        placeholder="Mulțumește-i clientului și răspunde politicos..."
                        class="w-full rounded-2xl border-ivt-line text-sm text-ivt-ink placeholder:text-ivt-ink-soft/50 focus:border-primary focus:ring-primary"
                    ></textarea>
                    <p v-if="form.errors.reply" class="mt-1 text-xs text-rose-600">{{ form.errors.reply }}</p>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <span class="text-xs text-ivt-ink-soft">{{ form.reply.length }}/1000</span>
                        <div class="flex gap-2">
                            <button type="button" class="rounded-xl px-4 py-2 text-sm font-medium text-ivt-ink-soft transition-colors hover:bg-ivt-paper" @click="cancelReply">Anulează</button>
                            <button
                                type="submit"
                                :disabled="form.processing || !form.reply.trim()"
                                class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary-bright disabled:cursor-not-allowed disabled:opacity-50"
                            >Publică răspunsul</button>
                        </div>
                    </div>
                </form>

                <div v-else-if="!review.provider_reply" class="mt-4 flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary/25 transition-colors hover:bg-primary-bright"
                        @click="startReply(review)"
                    >
                        <ChatBubbleLeftEllipsisIcon class="h-4 w-4" /> Răspunde
                    </button>
                </div>

                <Link
                    v-if="review.listing_slug"
                    :href="route('listings.show', review.listing_slug)"
                    class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-ivt-ink-soft hover:text-primary"
                >
                    Vezi anunțul <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5" />
                </Link>
            </li>
        </ul>

        <Pagination v-if="reviews.total > reviews.per_page" class="mt-6" :name="reviews" :links="reviews.links" />

        <ConfirmDialog
            :show="!!replyToDelete"
            title="Ștergi răspunsul?"
            message="Răspunsul nu va mai fi vizibil pe anunț. Poți scrie unul nou oricând."
            confirm-label="Șterge"
            @update:show="(v) => !v && (replyToDelete = null)"
            @confirm="confirmDelete"
        />
    </ProviderLayout>
</template>
