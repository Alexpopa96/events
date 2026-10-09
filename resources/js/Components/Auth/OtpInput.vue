<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    length: { type: Number, default: 6 },
    error: { type: String, default: '' },
    disabled: Boolean,
    autofocus: Boolean,
});

const emit = defineEmits(['update:modelValue', 'complete']);

const inputs = ref([]);

const digits = computed(() => Array.from({ length: props.length }, (_, i) => props.modelValue[i] ?? ''));

const focusAt = (index) => {
    const target = inputs.value[Math.max(0, Math.min(index, props.length - 1))];

    target?.focus();
    target?.select();
};

const update = (value) => {
    emit('update:modelValue', value);

    if (value.length === props.length) {
        emit('complete', value);
    }
};

// Writes the given digits starting at `index` and moves focus past the last one written.
const fill = (index, raw) => {
    const incoming = raw.replace(/\D/g, '');

    if (!incoming) {
        return;
    }

    const next = digits.value.slice();
    const chars = incoming.slice(0, props.length - index).split('');

    chars.forEach((char, i) => {
        next[index + i] = char;
    });

    update(next.join('').slice(0, props.length));
    focusAt(index + chars.length);
};

const onInput = (index, event) => {
    const value = event.target.value;

    // Re-sync the DOM in case the typed value was rejected (non-digit) or is longer than one char.
    event.target.value = digits.value[index];

    if (value === '') {
        return;
    }

    fill(index, value);
};

const onKeydown = (index, event) => {
    if (event.key === 'Backspace') {
        event.preventDefault();

        const next = digits.value.slice();

        if (next[index]) {
            next[index] = '';
            update(next.join(''));
        } else if (index > 0) {
            next[index - 1] = '';
            update(next.join(''));
            focusAt(index - 1);
        }
    } else if (event.key === 'ArrowLeft') {
        event.preventDefault();
        focusAt(index - 1);
    } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        focusAt(index + 1);
    }
};

const onPaste = (index, event) => {
    event.preventDefault();
    fill(index, event.clipboardData.getData('text'));
};

defineExpose({ focus: () => focusAt(0) });
</script>

<template>
    <div>
        <div class="flex justify-center gap-2 sm:gap-3">
            <input
                v-for="(digit, index) in digits"
                :key="index"
                :ref="(el) => (inputs[index] = el)"
                :value="digit"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                :maxlength="length"
                :autofocus="autofocus && index === 0"
                :disabled="disabled"
                :aria-label="`Cifra ${index + 1} din ${length}`"
                class="h-14 w-11 rounded-xl border border-ivt-line bg-white text-center font-mono text-2xl font-semibold text-primary focus:border-ivt-violet focus:outline-none focus:ring-2 focus:ring-ivt-violet/20 disabled:opacity-60 sm:h-16 sm:w-12"
                :class="{ 'border-danger-400 focus:border-danger-400 focus:ring-danger-400/20': error }"
                @input="onInput(index, $event)"
                @keydown="onKeydown(index, $event)"
                @paste="onPaste(index, $event)"
                @focus="$event.target.select()"
            />
        </div>

        <p v-if="error" class="mt-2 px-1 text-center text-sm text-danger-600">{{ error }}</p>
    </div>
</template>
