<template>
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3" v-if="links.length > 2">
        <p class="text-sm text-ivt-ink-soft">
            De la
            <span class="font-semibold text-ivt-ink">{{ name.from }}</span>
            la
            <span class="font-semibold text-ivt-ink">{{ name.to }}</span>
            din
            <span class="font-semibold text-ivt-ink">{{ name.total }}</span>
            înregistrări
        </p>
        <nav class="flex items-center gap-1" aria-label="Pagination">
            <Link v-if="links[0].url" :href="links[0].url" preserve-scroll
                  class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-ivt-line bg-white text-ivt-ink-soft transition-colors hover:border-ivt-violet hover:text-primary">
                <ChevronLeft class="h-4 w-4" />
            </Link>
            <template v-for="(link, key) in links" :key="key">
                <template v-if="key > 0 && key < links.length - 1">
                    <Link v-if="link.url" :href="link.url" preserve-scroll
                          :aria-current="link.active ? 'page' : undefined"
                          class="inline-flex h-8 min-w-[2rem] items-center justify-center rounded-lg border px-2 text-sm font-medium transition-colors"
                          :class="link.active
                              ? 'border-primary bg-primary text-white shadow-sm shadow-primary/25'
                              : 'border-ivt-line bg-white text-ivt-ink-soft hover:border-ivt-violet hover:text-primary'"
                          v-html="link.label"></Link>
                    <span v-else class="inline-flex h-8 min-w-[2rem] items-center justify-center px-2 text-sm text-ivt-ink-soft/50" v-html="link.label"></span>
                </template>
            </template>
            <Link v-if="links[links.length - 1].url" :href="links[links.length - 1].url" preserve-scroll
                  class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-ivt-line bg-white text-ivt-ink-soft transition-colors hover:border-ivt-violet hover:text-primary">
                <ChevronRight class="h-4 w-4" />
            </Link>
        </nav>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight } from '@lucide/vue';

export default {
    components: {
        Link,
        ChevronLeft,
        ChevronRight,
    },
    props: {
        links: Array,
        name: Object
    },
}
</script>
