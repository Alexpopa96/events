<script setup>
import { useForm } from '@inertiajs/vue3';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import { CheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    currentPlanId: Number,
    subscription: Object,
    plans: Array,
    invoices: Array,
});

const form = useForm({ subscription_plan_id: null });

const choose = (plan) => {
    if (plan.id === props.currentPlanId) return;
    if (!confirm(`Treci pe planul ${plan.name}?`)) return;
    form.transform(() => ({ subscription_plan_id: plan.id })).put(route('provider.subscription.update'));
};

const invoiceStatusMeta = {
    paid: { label: 'Plătită', class: 'bg-emerald-100 text-emerald-700' },
    pending: { label: 'În așteptare', class: 'bg-gold-400/20 text-gold-500' },
    failed: { label: 'Eșuată', class: 'bg-rose-100 text-rose-700' },
    refunded: { label: 'Rambursată', class: 'bg-line/70 text-ink-soft' },
};
</script>

<template>
    <ProviderLayout title="Abonament & facturi">
        <div v-if="subscription" class="bg-white border border-line rounded-2xl p-5 mb-6 flex items-center gap-4 text-sm shadow-sm shadow-ink/5">
            <span class="text-ink-soft">Status abonament curent</span>
            <span class="font-medium text-ink">{{ subscription.status === 'active' ? 'Activ' : subscription.status }}</span>
            <span v-if="subscription.ends_at" class="text-ink-soft">· se reînnoiește la {{ subscription.ends_at }}</span>
        </div>

        <div class="grid sm:grid-cols-3 gap-5 mb-10">
            <div
                v-for="plan in plans"
                :key="plan.id"
                class="relative bg-white border rounded-2xl p-6 flex flex-col overflow-hidden transition-all duration-200"
                :class="plan.id === currentPlanId
                    ? 'border-brand-500 ring-1 ring-brand-500/30 shadow-glow-brand'
                    : 'border-line shadow-sm shadow-ink/5 hover:shadow-glow-gold hover:-translate-y-1'"
            >
                <span v-if="plan.id === currentPlanId" class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-brand-500 via-gold-400 to-brand-500"></span>

                <div class="flex items-center justify-between mb-1">
                    <h3 class="font-serif text-lg text-ink">{{ plan.name }}</h3>
                    <span v-if="plan.id === currentPlanId" class="text-[10px] font-semibold uppercase tracking-wide text-brand-600 bg-brand-50 rounded-full px-2 py-0.5">
                        Curent
                    </span>
                </div>
                <p class="font-serif text-3xl text-ink tabular-nums mb-1">
                    {{ plan.price === 0 ? 'Gratuit' : `${plan.price} ${plan.currency}` }}
                    <span v-if="plan.price > 0" class="font-sans text-sm font-normal text-ink-soft">/lună</span>
                </p>
                <p class="text-sm text-ink-soft mb-4">{{ plan.description }}</p>

                <ul class="space-y-2 mb-6 flex-1">
                    <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2 text-sm text-ink">
                        <CheckIcon class="w-4 h-4 text-brand-500 mt-0.5 flex-none" />
                        {{ feature }}
                    </li>
                </ul>

                <button
                    :disabled="plan.id === currentPlanId || form.processing"
                    @click="choose(plan)"
                    class="rounded-xl px-4 py-2.5 text-sm font-semibold transition-all duration-200"
                    :class="plan.id === currentPlanId
                        ? 'bg-paper text-ink-soft cursor-not-allowed'
                        : 'bg-brand-500 text-white hover:bg-brand-600 shadow-sm shadow-brand-500/25 hover:shadow-md hover:shadow-brand-500/30 active:translate-y-0'"
                >
                    {{ plan.id === currentPlanId ? 'Plan activ' : 'Alege acest plan' }}
                </button>
            </div>
        </div>

        <div class="bg-white border border-line rounded-2xl p-6 shadow-sm shadow-ink/5">
            <h3 class="font-serif text-lg text-ink mb-4">Facturi</h3>
            <div v-if="invoices.length" class="divide-y divide-line">
                <div v-for="invoice in invoices" :key="invoice.id" class="py-3 flex items-center justify-between text-sm px-2 -mx-2 rounded-lg transition-colors duration-150 hover:bg-paper/60">
                    <div>
                        <p class="font-medium text-ink">{{ invoice.number }}</p>
                        <p class="text-xs text-ink-soft">{{ invoice.issued_at }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="tabular-nums text-ink">{{ invoice.amount }} {{ invoice.currency }}</span>
                        <span class="text-xs font-medium rounded-full px-2.5 py-1" :class="invoiceStatusMeta[invoice.status]?.class">
                            {{ invoiceStatusMeta[invoice.status]?.label ?? invoice.status }}
                        </span>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-ink-soft py-6 text-center">Nu există facturi încă.</p>
        </div>

        <p class="text-xs text-ink-soft mt-4">
            Schimbarea planului este instantă în această versiune de testare — procesarea reală a plăților (card bancar) va fi adăugată separat.
        </p>
    </ProviderLayout>
</template>
