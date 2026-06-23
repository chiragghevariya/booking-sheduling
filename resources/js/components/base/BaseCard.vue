<script setup>
defineProps({
    padded: { type: Boolean, default: true },
});
</script>

<template>
    <div class="bg-surface-raised border border-line rounded-2xl shadow-soft" :class="padded ? 'p-6' : ''">
        <slot />
    </div>
</template>
