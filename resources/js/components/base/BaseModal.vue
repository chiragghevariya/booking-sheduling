<script setup>
import { watch, onMounted, onBeforeUnmount } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    size: { type: String, default: 'md' }, // sm | md | lg
});

const emit = defineEmits(['close']);

const sizes = {
    sm: 'max-w-sm',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
};

function onKey(e) {
    if (e.key === 'Escape' && props.open) emit('close');
}

onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));

// Lock body scroll while open
watch(
    () => props.open,
    (open) => {
        if (typeof document === 'undefined') return;
        document.body.style.overflow = open ? 'hidden' : '';
    },
);
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                aria-modal="true"
                role="dialog"
            >
                <div
                    class="absolute inset-0 bg-ink/40 backdrop-blur-sm"
                    @click="emit('close')"
                ></div>
                <div
                    class="relative w-full bg-white rounded-2xl shadow-soft border border-line"
                    :class="sizes[size] ?? sizes.md"
                >
                    <div v-if="title || $slots.header" class="flex items-center justify-between px-6 py-4 border-b border-line">
                        <slot name="header">
                            <h3 class="text-base font-semibold text-ink">{{ title }}</h3>
                        </slot>
                        <button
                            class="text-ink-soft hover:text-ink rounded-lg p-1 transition"
                            @click="emit('close')"
                            aria-label="Close"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="px-6 py-5">
                        <slot />
                    </div>
                    <div v-if="$slots.footer" class="px-6 py-4 border-t border-line bg-surface-sunken/60 rounded-b-2xl">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 150ms ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
