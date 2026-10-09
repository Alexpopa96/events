<template>
    <form action="#" method="POST">
        <div class="rounded-2xl border border-ivt-line bg-white shadow-sm shadow-ivt-ink/5 overflow-hidden">
            <div class="p-5 sm:p-6">
                <slot></slot>
            </div>
            <div v-if="buttons" class="flex items-center justify-between gap-3 border-t border-ivt-line bg-ivt-paper-2/40 px-5 py-4 sm:px-6">
                <button type="button" @click="back"
                        class="inline-flex items-center gap-2 rounded-full bg-ivt-paper-2 px-5 py-2.5 text-sm font-semibold text-ivt-ink-soft transition-colors hover:bg-ivt-paper-3 hover:text-ivt-ink">
                    <ArrowLeft class="h-4 w-4" />
                    Înapoi
                </button>
                <button v-if="save" type="submit" :disabled="loading"
                        class="inline-flex items-center gap-2 rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/25 transition-all duration-200 hover:brightness-110 hover:shadow-glow-violet hover:shadow-glow-primary active:scale-[0.98] disabled:pointer-events-none disabled:opacity-60">
                    <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
                    <Check v-else class="h-4 w-4" />
                    Salvează
                </button>
            </div>
        </div>
    </form>
</template>

<script>
import { ArrowLeft, Check, Loader2 } from '@lucide/vue';

export default {
    components: {
        ArrowLeft,
        Check,
        Loader2,
    },
    props: {
        type: {
            type: String,
            default: 'submit',
        },
        buttons: {
            type: Boolean,
            default: true,
        },
        save: {
            type: Boolean,
            default: true,
        },
        loading: Boolean

    },
    methods: {
        back() {
            window.history.back();
        }
    }
}
</script>
