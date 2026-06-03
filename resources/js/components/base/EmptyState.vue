<script setup>
import { Inbox } from 'lucide-vue-next';

defineProps({
    title: { type: String, default: 'Nothing here yet' },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="text-center py-12 px-6">
        <div class="mx-auto w-12 h-12 rounded-2xl bg-surface-sunken flex items-center justify-center text-ink-soft">
            <slot name="icon">
                <Inbox class="w-6 h-6" />
            </slot>
        </div>
        <h3 class="mt-4 text-sm font-semibold text-ink">{{ title }}</h3>
        <p v-if="description" class="mt-1 text-sm text-ink-soft max-w-sm mx-auto">{{ description }}</p>
        <div v-if="$slots.action" class="mt-5">
            <slot name="action" />
        </div>
    </div>
</template>
