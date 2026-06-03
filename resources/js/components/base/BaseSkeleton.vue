<script setup>
defineProps({
    width: { type: String, default: '100%' },
    height: { type: String, default: '1rem' },
    rounded: { type: String, default: 'rounded-md' },
});
</script>

<template>
    <div
        class="bg-surface-sunken animate-pulse"
        :class="rounded"
        :style="{ width, height }"
    ></div>
</template>
