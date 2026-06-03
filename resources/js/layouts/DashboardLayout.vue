<script setup>
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import AppSidebar from '../components/layout/AppSidebar.vue';
import AppTopbar from '../components/layout/AppTopbar.vue';

const collapsed = ref(false);
const mobileOpen = ref(false);
const route = useRoute();

const pageTitle = computed(() => route.meta.title ?? '');
</script>

<template>
    <div class="min-h-screen bg-surface flex">
        <AppSidebar
            :collapsed="collapsed"
            :mobile-open="mobileOpen"
            @toggle-collapsed="collapsed = !collapsed"
            @close-mobile="mobileOpen = false"
        />

        <div class="flex-1 min-w-0 flex flex-col">
            <AppTopbar
                :page-title="pageTitle"
                @toggle-mobile="mobileOpen = true"
            />
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
