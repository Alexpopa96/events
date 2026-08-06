<script setup>
import { Link } from '@inertiajs/vue3';
import Logo from '@/Components/Logo.vue';
</script>

<template>
    <footer class="border-t border-ivt-line bg-ivt-paper-2 font-invita">
        <div class="mx-auto max-w-7xl px-6 pb-10 pt-14 lg:px-8">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
                <div>
                    <Logo variant="invita" />
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-ivt-ink-soft">
                        Platforma de anunțuri pentru furnizori de servicii destinate evenimentelor — nunți, botezuri, aniversări și evenimente corporate.
                    </p>
                </div>

                <div>
                    <h5 class="text-[13px] font-semibold uppercase tracking-wider text-ivt-wine">Platformă</h5>
                    <ul class="mt-4 space-y-2.5">
                        <li><Link :href="route('categories.index')" class="text-sm text-ivt-ink-soft transition-colors hover:text-ivt-ink">Categorii</Link></li>
                        <li><Link :href="route('listings.index')" class="text-sm text-ivt-ink-soft transition-colors hover:text-ivt-ink">Furnizori</Link></li>
                        <li><a href="/#cereri" class="text-sm text-ivt-ink-soft transition-colors hover:text-ivt-ink">Cereri de ofertă</a></li>
                        <li><a href="/#abonamente" class="text-sm text-ivt-ink-soft transition-colors hover:text-ivt-ink">Abonamente</a></li>
                    </ul>
                </div>

                <div>
                    <h5 class="text-[13px] font-semibold uppercase tracking-wider text-ivt-wine">Furnizori</h5>
                    <ul class="mt-4 space-y-2.5">
                        <li><Link href="/register" class="text-sm text-ivt-ink-soft transition-colors hover:text-ivt-ink">Creează cont</Link></li>
                        <li><a href="/#cum-functioneaza" class="text-sm text-ivt-ink-soft transition-colors hover:text-ivt-ink">Ghid de publicare</a></li>
                        <li><span title="În curând" class="cursor-not-allowed text-sm text-ivt-ink-faint">Statistici &amp; dashboard</span></li>
                    </ul>
                </div>

                <div>
                    <h5 class="text-[13px] font-semibold uppercase tracking-wider text-ivt-wine">Companie</h5>
                    <ul class="mt-4 space-y-2.5">
                        <li><a href="/#despre" class="text-sm text-ivt-ink-soft transition-colors hover:text-ivt-ink">Despre noi</a></li>
                        <li><a href="mailto:contact@eventhub.ro" class="text-sm text-ivt-ink-soft transition-colors hover:text-ivt-ink">Contact</a></li>
                        <li><Link :href="route('terms.show')" class="text-sm text-ivt-ink-soft transition-colors hover:text-ivt-ink">Termeni și condiții</Link></li>
                        <li><Link :href="route('policy.show')" class="text-sm text-ivt-ink-soft transition-colors hover:text-ivt-ink">Confidențialitate</Link></li>
                    </ul>
                </div>
            </div>

            <div class="mt-14 flex flex-wrap items-center justify-between gap-3 border-t border-ivt-line pt-6 text-[12.5px] text-ivt-ink-faint">
                <span>&copy; {{ new Date().getFullYear() }} EventHub. Toate drepturile rezervate.</span>
                <span>Platforma nu intermediază rezervări sau plăți între client și furnizor.</span>
            </div>
        </div>
    </footer>
</template>
