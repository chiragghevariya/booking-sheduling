<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import BaseCard from '../../components/base/BaseCard.vue';
import BaseBadge from '../../components/base/BaseBadge.vue';
import BaseButton from '../../components/base/BaseButton.vue';
import BaseSkeleton from '../../components/base/BaseSkeleton.vue';
import EmptyState from '../../components/base/EmptyState.vue';
import { CalendarDays, CheckCircle2 } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';
import { useProviderBookingsStore } from '../../stores/providerBookings';
import { fmtDateLong, fmtTime } from '../../lib/calendar';
import api from '../../lib/api';

const auth = useAuthStore();
const store = useProviderBookingsStore();
const router = useRouter();

const pendingCount = computed(() => store.items.filter((b) => b.status === 'pending').length);
const upcomingApproved = computed(() => {
    const now = new Date();
    return store.items
        .filter((b) => b.status === 'approved' && new Date(b.starts_at) >= now)
        .sort((a, b) => new Date(a.starts_at) - new Date(b.starts_at));
});
const recent = computed(() => store.items.slice(0, 5));

const statusVariant = {
    pending: 'warning',
    approved: 'success',
    declined: 'danger',
    cancelled: 'neutral',
};

const services = ref([]);
const servicesCount = computed(() => services.value.filter((s) => s.is_active).length);

async function loadServices() {
    try {
        const { data } = await api.get('/api/services');
        services.value = data.data ?? data;
    } catch {
        services.value = [];
    }
}

onMounted(async () => {
    await Promise.all([store.fetch(), loadServices()]);
});
</script>

<template>
    <DashboardLayout>
        <div class="space-y-6 max-w-7xl mx-auto">
            <div>
                <h2 class="text-xl sm:text-2xl font-semibold text-ink">Welcome back, {{ auth.user?.name?.split(' ')[0] }}</h2>
                <p class="mt-1 text-sm text-ink-soft">Here's a snapshot of your scheduling activity.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <BaseCard>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-ink-soft uppercase tracking-wide">Pending requests</p>
                            <BaseSkeleton v-if="store.loading" width="3rem" height="2rem" />
                            <p v-else class="mt-2 text-3xl font-semibold text-ink">{{ pendingCount }}</p>
                        </div>
                        <BaseBadge variant="warning">Awaiting</BaseBadge>
                    </div>
                </BaseCard>
                <BaseCard>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-ink-soft uppercase tracking-wide">Upcoming bookings</p>
                            <BaseSkeleton v-if="store.loading" width="3rem" height="2rem" />
                            <p v-else class="mt-2 text-3xl font-semibold text-ink">{{ upcomingApproved.length }}</p>
                        </div>
                        <BaseBadge variant="success">Approved</BaseBadge>
                    </div>
                </BaseCard>
                <BaseCard>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-ink-soft uppercase tracking-wide">Active services</p>
                            <p class="mt-2 text-3xl font-semibold text-ink">{{ servicesCount }}</p>
                        </div>
                        <BaseBadge variant="brand">Live</BaseBadge>
                    </div>
                </BaseCard>
            </div>

            <BaseCard :padded="false">
                <div class="px-6 pt-5 pb-3 flex items-center justify-between border-b border-line">
                    <h3 class="text-sm font-semibold text-ink">Recent bookings</h3>
                    <BaseButton size="sm" variant="ghost" @click="router.push('/bookings')">View all</BaseButton>
                </div>

                <div v-if="store.loading" class="p-6 space-y-3">
                    <BaseSkeleton v-for="i in 3" :key="i" height="3.5rem" />
                </div>

                <EmptyState
                    v-else-if="recent.length === 0"
                    title="No bookings yet"
                    description="Once customers request sessions, they'll appear here for you to approve or decline."
                >
                    <template #icon><CalendarDays class="w-6 h-6" /></template>
                </EmptyState>

                <ul v-else class="divide-y divide-line">
                    <li v-for="b in recent" :key="b.id" class="px-6 py-3 flex items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-medium text-ink truncate">{{ b.service?.name }}</p>
                                <BaseBadge :variant="statusVariant[b.status]" size="sm">{{ b.status }}</BaseBadge>
                            </div>
                            <p class="text-xs text-ink-soft mt-0.5">
                                {{ b.customer?.name }} · {{ fmtDateLong(b.starts_at) }} at {{ fmtTime(b.starts_at) }}
                            </p>
                        </div>
                    </li>
                </ul>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
