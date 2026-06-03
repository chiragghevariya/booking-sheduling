<script setup>
import { computed, useAttrs } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    type: { type: String, default: 'text' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    id: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const attrs = useAttrs();

const inputId = computed(() => props.id || `in-${Math.random().toString(36).slice(2, 9)}`);

function onInput(e) {
    emit('update:modelValue', e.target.value);
}
</script>

<template>
    <div class="space-y-1.5">
        <label v-if="label" :for="inputId" class="block text-sm font-medium text-ink">{{ label }}</label>
        <input
            :id="inputId"
            :type="type"
            :value="modelValue"
            @input="onInput"
            v-bind="attrs"
            class="input-base"
            :class="error ? 'border-red-400 focus:border-red-500 focus:ring-red-500/30' : ''"
        />
        <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
        <p v-else-if="hint" class="text-xs text-ink-soft">{{ hint }}</p>
    </div>
</template>
