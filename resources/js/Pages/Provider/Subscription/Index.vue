<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { CheckIcon, DocumentTextIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

const toast = useToast();

const props = defineProps({
    currentPlanId: Number,
    subscription: Object,
    plans: Array,
    invoices: Array,
});

const form = useForm({ subscription_plan_id: null });
const planToConfirm = ref(null);

const choose = (plan) => {
    if (plan.id === props.currentPlanId) return;
    planToConfirm.value = plan;
};

const confirmChoose = () => {
    const plan = planToConfirm.value;
    form.transform(() => ({ subscription_plan_id: plan.id })).put(route('provider.subscription.update'), {
        onSuccess: () => toast.success(`Ai trecut pe planul ${plan.name}.`),
        onFinish: () => { planToConfirm.value = null; },
    });
};

const invoiceStatusMeta = {
    paid: { label: 'Plătită', class: 'bg-success-100 text-success-700' },
    pending: { label: 'În așteptare', class: 'bg-ivt-violet/20 text-ivt-violet' },
    failed: { label: 'Eșuată', class: 'bg-danger-100 text-danger-700' },
    refunded: { label: 'Rambursată', class: 'bg-ivt-paper-2 text-ivt-ink-soft' },
};
</script>

<template>
    <ProviderLayout title="Abonament & facturi">
        <div v-if="subscription" class="bg-white border border-ivt-line rounded-2xl p-5 mb-6 flex items-center gap-4 text-sm shadow-sm shadow-ivt-ink/5">
            <span class="text-ivt-ink-soft">Status abonament curent</span>
            <span class="font-medium text-ivt-ink">{{ subscription.status === 'active' ? 'Activ' : subscription.status }}</span>
            <span v-if="subscription.ends_at" class="text-ivt-ink-soft">· se reînnoiește la {{ subscription.ends_at }}</span>
        </div>

        <div class="grid sm:grid-cols-3 gap-5 mb-10">
            <div
                v-for="plan in plans"
                :key="plan.id"
                class="relative bg-white border rounded-2xl p-6 flex flex-col overflow-hidden transition-all duration-200"
                :class="plan.id === currentPlanId
                    ? 'border-primary ring-1 ring-primary/30 shadow-glow-primary'
                    : 'border-ivt-line shadow-sm shadow-ivt-ink/5 hover:shadow-glow-primary hover:-translate-y-1'"
            >
                <span v-if="plan.id === currentPlanId" class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-primary-bright via-ivt-violet to-primary"></span>

                <div class="flex items-center justify-between mb-1">
                    <h3 class="font-display text-lg text-ivt-ink">{{ plan.name }}</h3>
                    <span v-if="plan.id === currentPlanId" class="text-[10px] font-semibold uppercase tracking-wide text-primary bg-ivt-paper-2 rounded-full px-2 py-0.5">
                        Curent
                    </span>
                </div>
                <p class="font-display text-3xl text-ivt-ink tabular-nums mb-1">
                    {{ plan.price === 0 ? 'Gratuit' : `${plan.price} ${plan.currency}` }}
                    <span v-if="plan.price > 0" class="font-sans text-sm font-normal text-ivt-ink-soft">/lună</span>
                </p>
                <p class="text-sm text-ivt-ink-soft mb-4">{{ plan.description }}</p>

                <ul class="space-y-2 mb-6 flex-1">
                    <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2 text-sm text-ivt-ink">
                        <CheckIcon class="w-4 h-4 text-primary mt-0.5 flex-none" />
                        {{ feature }}
                    </li>
                </ul>

                <button
                    :disabled="plan.id === currentPlanId || form.processing"
                    @click="choose(plan)"
                    class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all duration-200"
                    :class="plan.id === currentPlanId
                        ? 'bg-ivt-paper text-ivt-ink-soft cursor-not-allowed'
                        : 'bg-primary text-white hover:bg-primary-bright shadow-sm shadow-primary/25 hover:shadow-md hover:shadow-primary/30 active:translate-y-0 disabled:opacity-60'"
                >
                    <svg v-if="form.processing && planToConfirm?.id === plan.id" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    {{ plan.id === currentPlanId ? 'Plan activ' : 'Alege acest plan' }}
                </button>
            </div>
        </div>

        <div class="bg-white border border-ivt-line rounded-2xl p-6 shadow-sm shadow-ivt-ink/5">
            <h3 class="font-display text-lg text-ivt-ink mb-4">Facturi</h3>
            <div v-if="invoices.length" class="divide-y divide-ivt-line">
                <div v-for="invoice in invoices" :key="invoice.id" class="py-3 flex items-center justify-between text-sm px-2 -mx-2 rounded-lg transition-colors duration-150 hover:bg-ivt-paper/60">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-ivt-paper-2 text-primary">
                            <DocumentTextIcon class="w-4 h-4" />
                        </span>
                        <div>
                            <p class="font-medium text-ivt-ink">{{ invoice.number }}</p>
                            <p class="text-xs text-ivt-ink-soft">{{ invoice.issued_at }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="tabular-nums text-ivt-ink">{{ invoice.amount }} {{ invoice.currency }}</span>
                        <span class="text-xs font-medium rounded-full px-2.5 py-1" :class="invoiceStatusMeta[invoice.status]?.class">
                            {{ invoiceStatusMeta[invoice.status]?.label ?? invoice.status }}
                        </span>
                        <a
                            :href="invoice.download_url"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:text-primary-bright"
                        >
                            <ArrowDownTrayIcon class="h-3.5 w-3.5" /> Descarcă
                        </a>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-ivt-ink-soft py-6 text-center">Nu există facturi încă.</p>
        </div>

        <p class="text-xs text-ivt-ink-soft mt-4">
            Schimbarea planului este instantă în această versiune de testare — procesarea reală a plăților (card bancar) va fi adăugată separat.
        </p>

        <ConfirmDialog
            :show="!!planToConfirm"
            @update:show="(v) => !v && (planToConfirm = null)"
            title="Schimbi planul de abonament?"
            :message="planToConfirm ? `Treci pe planul ${planToConfirm.name}?` : ''"
            confirm-label="Confirmă"
            variant="default"
            :processing="form.processing"
            @confirm="confirmChoose"
        />
    </ProviderLayout>
</template>
