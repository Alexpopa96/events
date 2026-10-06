<template>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h3 class="text-base font-semibold text-ivt-ink">
                <slot name="title" />
            </h3>

            <p class="mt-1 text-sm leading-relaxed text-ivt-ink-soft">
                <slot name="description" />
            </p>
        </div>

        <div>
            <slot name="aside" />
        </div>
    </div>
</template>
