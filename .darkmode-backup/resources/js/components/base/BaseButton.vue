<script setup>
import { computed } from 'vue';
import { Loader2 } from 'lucide-vue-next';

const props = defineProps({
    variant: { type: String, default: 'primary' }, // primary | secondary | ghost | danger
    size: { type: String, default: 'md' },          // sm | md | lg
    type: { type: String, default: 'button' },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    block: { type: Boolean, default: false },
});

const variantClasses = {
    primary: 'bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-700 shadow-sm',
    secondary: 'bg-white text-ink border border-line hover:bg-surface-sunken',
    ghost: 'bg-transparent text-ink hover:bg-surface-sunken',
    danger: 'bg-red-600 text-white hover:bg-red-700 shadow-sm',
};

const sizeClasses = {
    sm: 'h-9 px-3 text-sm gap-1.5',
    md: 'h-10 px-4 text-sm gap-2',
    lg: 'h-12 px-5 text-base gap-2',
};

const classes = computed(() => [
    'inline-flex items-center justify-center font-medium rounded-xl transition',
    'disabled:opacity-60 disabled:cursor-not-allowed select-none',
    variantClasses[props.variant] ?? variantClasses.primary,
    sizeClasses[props.size] ?? sizeClasses.md,
    props.block ? 'w-full' : '',
]);
</script>

<template>
    <button :type="type" :disabled="disabled || loading" :class="classes">
        <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
        <slot />
    </button>
</template>
