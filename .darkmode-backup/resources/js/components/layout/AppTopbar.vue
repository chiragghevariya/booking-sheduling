<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { Menu, ChevronDown, LogOut } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';

defineProps({
    pageTitle: { type: String, default: '' },
});

const emit = defineEmits(['toggle-mobile']);

const auth = useAuthStore();
const toast = useToastStore();
const router = useRouter();

const initials = computed(() => {
    const name = auth.user?.name ?? '';
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join('') || '?';
});

const menuOpen = ref(false);
const menuRef = ref(null);

function onClickOutside(e) {
    if (menuRef.value && !menuRef.value.contains(e.target)) {
        menuOpen.value = false;
    }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside));

async function logout() {
    try {
        await auth.logout();
        toast.success('Signed out.');
        router.push('/login');
    } catch {
        toast.error('Could not sign out. Try again.');
    } finally {
        menuOpen.value = false;
    }
}
</script>

<template>
    <header class="sticky top-0 z-30 h-topbar bg-white/85 backdrop-blur border-b border-line">
        <div class="h-full px-4 sm:px-6 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <button
                    class="lg:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg text-ink-muted hover:bg-surface-sunken"
                    @click="emit('toggle-mobile')"
                    aria-label="Open menu"
                >
                    <Menu class="w-5 h-5" />
                </button>
                <h1 v-if="pageTitle" class="text-sm sm:text-base font-semibold text-ink truncate">{{ pageTitle }}</h1>
            </div>

            <div class="relative" ref="menuRef">
                <button
                    class="inline-flex items-center gap-2 rounded-xl pl-1 pr-2 py-1 hover:bg-surface-sunken transition"
                    @click="menuOpen = !menuOpen"
                >
                    <span class="w-8 h-8 rounded-full bg-brand-600 text-white text-xs font-semibold flex items-center justify-center">
                        {{ initials }}
                    </span>
                    <span class="hidden sm:flex flex-col items-start leading-tight">
                        <span class="text-sm font-medium text-ink truncate max-w-[10rem]">{{ auth.user?.name }}</span>
                        <span class="text-[11px] text-ink-soft capitalize">{{ auth.user?.role }}</span>
                    </span>
                    <ChevronDown class="w-4 h-4 text-ink-soft" />
                </button>

                <Transition name="menu">
                    <div
                        v-if="menuOpen"
                        class="absolute right-0 mt-2 w-56 bg-white border border-line rounded-xl shadow-soft py-1.5 overflow-hidden"
                    >
                        <div class="px-3 py-2 border-b border-line">
                            <p class="text-sm font-semibold text-ink truncate">{{ auth.user?.name }}</p>
                            <p class="text-xs text-ink-soft truncate">{{ auth.user?.email }}</p>
                        </div>
                        <button
                            class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                            @click="logout"
                        >
                            <LogOut class="w-4 h-4" /> Sign out
                        </button>
                    </div>
                </Transition>
            </div>
        </div>
    </header>
</template>

<style scoped>
.menu-enter-active,
.menu-leave-active {
    transition: all 120ms ease;
}
.menu-enter-from,
.menu-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
