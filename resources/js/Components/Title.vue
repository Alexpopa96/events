<template>
    <div class="mb-5">
        <h2 :class="$attrs.class" class="font-display text-lg text-ivt-ink">{{ title }} <slot></slot></h2>
    </div>
</template>
<script>
export default {
    props: {
        title: String
    }
}
</script>
