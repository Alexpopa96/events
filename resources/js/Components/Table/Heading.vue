<template>
    <th class="px-4 py-3 text-left" :class="$attrs.class">
        <div class="flex items-center gap-1">
            <span v-if="sortable" class="text-xs font-semibold uppercase tracking-wider text-ivt-ink-soft/70">
                <slot></slot>
            </span>
            <button v-else type="button" @click="$emit('sort')"
                    class="group inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition-colors focus:outline-none"
                    :class="selected ? 'text-primary' : 'text-ivt-ink-soft/70 hover:text-ivt-ink'">
                <span><slot></slot></span>
                <ArrowUp v-if="selected && direction === 'asc'" class="h-3.5 w-3.5" />
                <ArrowDown v-else-if="selected && direction === 'desc'" class="h-3.5 w-3.5" />
                <ArrowUpDown v-else class="h-3.5 w-3.5 opacity-50 group-hover:opacity-100" />
            </button>
            <button v-if="selected" type="button" @click="$emit('removeSort')"
                    class="text-ivt-ink-soft/50 transition-colors hover:text-danger-600" title="Elimină sortarea">
                <X class="h-3.5 w-3.5" />
            </button>
        </div>
    </th>
</template>

<script>
import { ArrowUp, ArrowDown, ArrowUpDown, X } from '@lucide/vue';

export default {
    components: { ArrowUp, ArrowDown, ArrowUpDown, X },
    props: {
        sortable: null,
        direction: null,
        selected: false,
        removeSort: false,
        multiColumn: null,
    }
}
</script>
