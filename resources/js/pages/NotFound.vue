<script setup>
import { RouterLink } from 'vue-router';
import BaseButton from '../components/base/BaseButton.vue';
</script>

<template>
    <main class="min-h-screen flex items-center justify-center px-6 bg-surface">
        <div class="text-center max-w-md">
            <p class="text-sm font-semibold text-brand-600 tracking-wide uppercase">404</p>
            <h1 class="mt-2 text-3xl font-semibold text-ink">Page not found</h1>
            <p class="mt-2 text-ink-soft">The page you’re looking for doesn’t exist or has moved.</p>
            <RouterLink to="/" class="mt-6 inline-block">
                <BaseButton>Back home</BaseButton>
            </RouterLink>
        </div>
    </main>
</template>
