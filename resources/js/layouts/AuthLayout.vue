<script setup>
import { CalendarCheck } from 'lucide-vue-next';
</script>

<template>
    <main class="min-h-screen bg-surface flex">
        <!-- Visual side panel (desktop only) -->
        <aside class="hidden lg:flex w-1/2 bg-gradient-to-br from-brand-600 to-brand-700 text-white p-12 flex-col justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/15 backdrop-blur">
                    <CalendarCheck class="w-5 h-5" />
                </span>
                <span class="text-lg font-semibold tracking-tight">Booking & Scheduling</span>
            </div>
            <div class="max-w-md">
                <h2 class="text-3xl font-semibold leading-tight">Premium scheduling, with provider approval baked in.</h2>
                <p class="mt-4 text-white/80 leading-relaxed">
                    Calendly-grade booking flows, customer-friendly availability, and tools your team will actually
                    enjoy using every day.
                </p>
            </div>
            <p class="text-sm text-white/60">© {{ new Date().getFullYear() }} Booking & Scheduling</p>
        </aside>

        <!-- Form side -->
        <section class="flex-1 flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-md">
                <div class="lg:hidden flex items-center gap-3 mb-8">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-brand-50 text-brand-600">
                        <CalendarCheck class="w-5 h-5" />
                    </span>
                    <span class="text-lg font-semibold text-ink">Booking & Scheduling</span>
                </div>
                <slot />
            </div>
        </section>
    </main>
</template>
