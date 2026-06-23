<script setup>
import { storeToRefs } from 'pinia';
import { useToastStore } from '../../stores/toast';
import { CheckCircle2, AlertCircle, Info, X } from 'lucide-vue-next';

const toastStore = useToastStore();
const { toasts } = storeToRefs(toastStore);

const variantClasses = {
    success: 'border-emerald-200 bg-surface-raised',
    danger: 'border-red-200 bg-surface-raised',
    info: 'border-line bg-surface-raised',
};

const iconMap = {
    success: CheckCircle2,
    danger: AlertCircle,
    info: Info,
};

const iconColor = {
    success: 'text-emerald-600',
    danger: 'text-red-600',
    info: 'text-brand-600',
};
</script>

<template>
    <div class="fixed top-4 right-4 z-[60] flex flex-col gap-2 w-full max-w-sm pointer-events-none">
        <TransitionGroup name="toast">
            <div
                v-for="t in toasts"
                :key="t.id"
                class="pointer-events-auto rounded-xl border shadow-soft px-4 py-3 flex gap-3 items-start"
                :class="variantClasses[t.variant] ?? variantClasses.info"
            >
                <component
                    :is="iconMap[t.variant] ?? Info"
                    class="w-5 h-5 mt-0.5 shrink-0"
                    :class="iconColor[t.variant] ?? 'text-brand-600'"
                />
                <div class="flex-1 min-w-0">
                    <p v-if="t.title" class="text-sm font-semibold text-ink">{{ t.title }}</p>
                    <p class="text-sm text-ink-muted">{{ t.message }}</p>
                </div>
                <button
                    class="text-ink-soft hover:text-ink p-1 -m-1 rounded-md"
                    @click="toastStore.dismiss(t.id)"
                    aria-label="Dismiss"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 200ms ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
