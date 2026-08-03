<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ClockIcon } from '@heroicons/vue/24/outline';

defineProps({
    status: { type: String, default: 'pending' },
    reason: { type: String, default: null },
});

const logout = () => router.post(route('logout'));
</script>

<template>
    <Head title="Cont în așteptare de validare" />

    <div class="relative min-h-screen flex items-center justify-center overflow-hidden bg-paper px-4 py-10">
        <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <div class="absolute -top-32 -right-24 w-[28rem] h-[28rem] rounded-full bg-brand-400/15 blur-3xl animate-float-slow"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 rounded-full bg-gold-400/20 blur-3xl animate-float-slower"></div>
        </div>

        <div class="relative w-full max-w-md rounded-[2rem] bg-white px-6 py-10 text-center shadow-[0_24px_60px_-20px_rgba(33,28,39,0.25)] sm:px-9">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-sm shadow-brand-500/25">
                <ClockIcon class="h-7 w-7" />
            </div>

            <h1 class="mt-6 font-serif text-2xl text-ink">
                <template v-if="status === 'rejected'">Cererea ta nu a fost aprobată</template>
                <template v-else-if="status === 'suspended'">Contul tău a fost suspendat</template>
                <template v-else>Contul tău este în curs de validare</template>
            </h1>

            <p class="mt-3 text-sm leading-relaxed text-ink-soft">
                <template v-if="status === 'rejected'">
                    Ne pare rău, dar cererea ta de înregistrare nu a putut fi aprobată de echipa noastră.
                    Dacă crezi că este o eroare, te rugăm să ne contactezi.
                </template>
                <template v-else-if="status === 'suspended'">
                    Contul tău de furnizor a fost suspendat, iar anunțurile tale nu mai sunt vizibile public.
                    Dacă crezi că este o eroare, te rugăm să ne contactezi.
                </template>
                <template v-else>
                    Am primit datele firmei tale și le verificăm în acest moment. Vei primi un email imediat
                    ce contul tău este aprobat și vei putea publica anunțuri.
                </template>
            </p>

            <p v-if="reason" class="mt-4 rounded-2xl bg-paper px-4 py-3 text-left text-sm text-ink-soft">
                <span class="block text-xs font-semibold uppercase tracking-wider text-ink-soft/60 mb-1">Motiv</span>
                {{ reason }}
            </p>

            <button
                type="button"
                class="mt-8 w-full rounded-full border border-line px-4 py-3 text-sm font-semibold text-ink transition-all duration-150 hover:bg-paper"
                @click="logout"
            >
                Deconectează-te
            </button>
        </div>
    </div>
</template>
