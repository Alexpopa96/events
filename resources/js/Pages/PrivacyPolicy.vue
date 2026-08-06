<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Logo from '@/Components/Logo.vue';

defineProps({
    policy: String,
});
</script>

<template>
    <Head title="Politica de Confidențialitate" />

    <div class="min-h-screen bg-paper">
        <header class="border-b border-line bg-white/70 backdrop-blur">
            <div class="mx-auto max-w-3xl px-6 py-5">
                <Link href="/" class="inline-block w-fit"><Logo /></Link>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-6 py-12">
            <div
                class="prose max-w-none prose-headings:font-serif prose-headings:text-ink prose-a:text-brand-500 hover:prose-a:text-brand-600"
                v-html="policy"
            />

            <Link href="/" class="mt-12 inline-flex items-center gap-2 text-sm font-semibold text-brand-500 hover:text-brand-600">
                &larr; Înapoi la pagina principală
            </Link>
        </main>
    </div>
</template>
