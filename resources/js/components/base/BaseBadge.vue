<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'neutral' }, // neutral | success | warning | danger | info | brand
    size: { type: String, default: 'md' },         // sm | md
});

const variants = {
    neutral: 'bg-surface-sunken text-ink-muted',
    success: 'bg-emerald-50 text-emerald-700',
    warning: 'bg-amber-50 text-amber-700',
    danger: 'bg-red-50 text-red-700',
    info: 'bg-sky-50 text-sky-700',
    brand: 'bg-brand-50 text-brand-700',
};

const sizes = {
    sm: 'text-[11px] px-2 py-0.5',
    md: 'text-xs px-2.5 py-1',
};

const classes = computed(() => [
    'inline-flex items-center gap-1 rounded-full font-medium',
    variants[props.variant] ?? variants.neutral,
    sizes[props.size] ?? sizes.md,
]);
</script>

<template>
    <span :class="classes">
        <slot />
    </span>
</template>
