<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import {
    CalendarCheck,
    LayoutDashboard,
    CalendarDays,
    Clock,
    Briefcase,
    ChevronLeft,
    X,
} from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';

const props = defineProps({
    collapsed: { type: Boolean, default: false }, // desktop collapse
    mobileOpen: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle-collapsed', 'close-mobile']);

const auth = useAuthStore();

const nav = computed(() => {
    const role = auth.role;
    if (role === 'customer') {
        return [
            { to: '/book', label: 'Book a service', icon: CalendarDays },
            { to: '/my-bookings', label: 'My bookings', icon: Clock },
        ];
    }
    // provider / admin share the same shell
    return [
        { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },
        { to: '/bookings', label: 'Bookings', icon: CalendarDays },
        { to: '/availability', label: 'Availability', icon: Clock },
        { to: '/services', label: 'Services', icon: Briefcase },
    ];
});
</script>

<template>
    <!-- Mobile backdrop -->
    <Transition name="fade">
        <div
            v-if="mobileOpen"
            class="lg:hidden fixed inset-0 z-40 bg-ink/40 backdrop-blur-sm"
            @click="emit('close-mobile')"
        ></div>
    </Transition>

    <aside
        class="fixed lg:sticky top-0 left-0 z-50 h-screen bg-white border-r border-line flex flex-col
               transition-[width,transform] duration-200 ease-out"
        :class="[
            collapsed ? 'lg:w-sidebar-collapsed' : 'lg:w-sidebar',
            'w-sidebar',
            mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        ]"
    >
        <!-- Brand -->
        <div class="h-topbar px-4 flex items-center justify-between border-b border-line">
            <RouterLink to="/" class="flex items-center gap-2.5 min-w-0">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-brand-50 text-brand-600 shrink-0">
                    <CalendarCheck class="w-5 h-5" />
                </span>
                <span
                    v-if="!collapsed"
                    class="text-sm font-semibold text-ink truncate"
                >Booking & Sched.</span>
            </RouterLink>
            <button
                class="lg:hidden text-ink-soft hover:text-ink p-1 rounded-md"
                @click="emit('close-mobile')"
                aria-label="Close menu"
            >
                <X class="w-5 h-5" />
            </button>
        </div>

        <!-- Nav -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <RouterLink
                v-for="item in nav"
                :key="item.to"
                :to="item.to"
                v-slot="{ isActive }"
                custom
            >
                <a
                    :href="item.to"
                    @click.prevent="$router.push(item.to); emit('close-mobile')"
                    class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
                    :class="[
                        isActive
                            ? 'bg-brand-50 text-brand-700'
                            : 'text-ink-muted hover:bg-surface-sunken hover:text-ink',
                        collapsed ? 'lg:justify-center lg:px-2' : '',
                    ]"
                >
                    <component :is="item.icon" class="w-5 h-5 shrink-0" />
                    <span v-if="!collapsed">{{ item.label }}</span>
                </a>
            </RouterLink>
        </nav>

        <!-- Collapse toggle (desktop) -->
        <div class="hidden lg:flex border-t border-line p-3">
            <button
                class="w-full inline-flex items-center justify-center gap-2 rounded-xl text-xs font-medium
                       text-ink-soft hover:text-ink hover:bg-surface-sunken py-2 transition"
                @click="emit('toggle-collapsed')"
            >
                <ChevronLeft
                    class="w-4 h-4 transition-transform"
                    :class="collapsed ? 'rotate-180' : ''"
                />
                <span v-if="!collapsed">Collapse</span>
            </button>
        </div>
    </aside>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 200ms ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
