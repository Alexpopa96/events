<template>
    <div v-if="links.length > 2" class="flex flex-col items-center justify-between gap-4 sm:flex-row">
        <p v-if="name && name.total" class="text-[13px] text-ivt-ink-soft tabular-nums">
            Afișate
            <span class="font-semibold text-ivt-ink">{{ name.from }}–{{ name.to }}</span>
            din
            <span class="font-semibold text-ivt-ink">{{ name.total }}</span>
            rezultate
        </p>

        <nav class="flex items-center gap-1 rounded-full border border-ivt-line bg-white p-1 shadow-ivt-soft" aria-label="Paginare">
            <component :is="prev.url ? linkTag : 'span'" v-bind="attrsFor(prev)" rel="prev" aria-label="Pagina anterioară"
                       class="group inline-flex h-9 items-center gap-1 rounded-full pl-2.5 pr-3 text-[13px] font-semibold transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                       :class="prev.url ? 'text-ivt-ink hover:bg-ivt-paper-2' : 'cursor-not-allowed text-ivt-ink-faint/50'"
                       @click="go($event, prev)">
                <ChevronLeft class="h-4 w-4 transition-transform duration-200" :class="prev.url && 'group-hover:-translate-x-0.5'" />
                <span class="hidden md:inline">Înapoi</span>
            </component>

            <!-- Mobile: compact indicator -->
            <span v-if="name && name.last_page" class="px-3 text-[13px] font-semibold text-ivt-ink tabular-nums sm:hidden">
                {{ name.current_page }} <span class="font-normal text-ivt-ink-faint">/ {{ name.last_page }}</span>
            </span>

            <!-- Desktop: page numbers -->
            <div class="items-center gap-0.5" :class="name && name.last_page ? 'hidden sm:flex' : 'flex'">
                <template v-for="(link, key) in pages" :key="key">
                    <span v-if="!link.url && !link.active" aria-hidden="true"
                          class="inline-flex h-9 w-7 items-center justify-center text-[13px] text-ivt-ink-faint">…</span>
                    <component v-else :is="link.active ? 'span' : linkTag" v-bind="link.active ? {} : attrsFor(link)"
                               :aria-current="link.active ? 'page' : undefined"
                               :aria-label="`Pagina ${link.label}`"
                               class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-full px-3 text-[13px] font-semibold tabular-nums transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                               :class="link.active
                                   ? 'bg-brand text-white shadow-glow-primary'
                                   : 'text-ivt-ink-soft hover:-translate-y-px hover:bg-ivt-paper-2 hover:text-ivt-ink'"
                               @click="go($event, link)">
                        {{ link.label }}
                    </component>
                </template>
            </div>

            <component :is="next.url ? linkTag : 'span'" v-bind="attrsFor(next)" rel="next" aria-label="Pagina următoare"
                       class="group inline-flex h-9 items-center gap-1 rounded-full pl-3 pr-2.5 text-[13px] font-semibold transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                       :class="next.url ? 'text-ivt-ink hover:bg-ivt-paper-2' : 'cursor-not-allowed text-ivt-ink-faint/50'"
                       @click="go($event, next)">
                <span class="hidden md:inline">Înainte</span>
                <ChevronRight class="h-4 w-4 transition-transform duration-200" :class="next.url && 'group-hover:translate-x-0.5'" />
            </component>
        </nav>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';

const props = defineProps({
    links: { type: Array, required: true },
    name: Object,
    // Optional: handle navigation yourself (e.g. partial reloads). Used via @navigate.
    onNavigate: Function,
});

const prev = computed(() => props.links[0]);
const next = computed(() => props.links[props.links.length - 1]);
const pages = computed(() => props.links.slice(1, -1));

const linkTag = computed(() => (props.onNavigate ? 'a' : Link));

const attrsFor = (link) => {
    if (!link.url) return { 'aria-disabled': 'true' };
    return props.onNavigate ? { href: link.url } : { href: link.url, preserveScroll: true };
};

const go = (event, link) => {
    if (!props.onNavigate || !link.url || link.active) return;
    event.preventDefault();
    props.onNavigate(link.url);
};
</script>
