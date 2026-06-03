<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import BaseCard from '../../components/base/BaseCard.vue';
import BaseBadge from '../../components/base/BaseBadge.vue';
import BaseButton from '../../components/base/BaseButton.vue';
import BaseModal from '../../components/base/BaseModal.vue';
import BaseSkeleton from '../../components/base/BaseSkeleton.vue';
import EmptyState from '../../components/base/EmptyState.vue';
import { CalendarPlus, Clock, MapPin, RefreshCcw, XCircle } from 'lucide-vue-next';
import { useBookingsStore } from '../../stores/bookings';
import { useToastStore } from '../../stores/toast';
import { fmtDateLong, fmtTime } from '../../lib/calendar';

const store = useBookingsStore();
const toast = useToastStore();
const router = useRouter();

const cancelTarget = ref(null);
const cancelling = ref(false);

const statusVariant = {
    pending: 'warning',
    approved: 'success',
    declined: 'danger',
    cancelled: 'neutral',
};

const statusLabel = {
    pending: 'Pending approval',
    approved: 'Approved',
    declined: 'Declined',
    cancelled: 'Cancelled',
};

function bookingActions(b) {
    const out = [];
    if (b.status === 'pending') {
        out.push({ key: 'reschedule', label: 'Reschedule', icon: RefreshCcw, variant: 'secondary' });
    }
    if (b.status === 'pending' || b.status === 'approved') {
        out.push({ key: 'cancel', label: 'Cancel', icon: XCircle, variant: 'ghost' });
    }
    return out;
}

function reschedule(b) {
    router.push({ path: '/book', query: { reschedule: b.id } });
}

function askCancel(b) {
    cancelTarget.value = b;
}

async function confirmCancel() {
    if (!cancelTarget.value) return;
    cancelling.value = true;
    try {
        await store.cancel(cancelTarget.value.id);
        toast.success('Booking cancelled.');
        cancelTarget.value = null;
    } catch {
        toast.error('Could not cancel.');
    } finally {
        cancelling.value = false;
    }
}

onMounted(() => store.fetchMine());
</script>

<template>
    <DashboardLayout>
        <div class="space-y-6 max-w-4xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl sm:text-2xl font-semibold text-ink">My bookings</h2>
                    <p class="mt-1 text-sm text-ink-soft">Track your requests and upcoming sessions.</p>
                </div>
                <BaseButton @click="router.push('/book')">
                    <CalendarPlus class="w-4 h-4" /> Book new
                </BaseButton>
            </div>

            <div v-if="store.loading" class="space-y-3">
                <BaseSkeleton v-for="i in 3" :key="i" height="6rem" rounded="rounded-2xl" />
            </div>

            <template v-else>
                <!-- Upcoming -->
                <section>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-ink-soft mb-3">Upcoming &amp; pending</h3>
                    <EmptyState
                        v-if="store.upcoming.length === 0"
                        title="Nothing upcoming"
                        description="When you request a booking, it'll show up here."
                    >
                        <template #icon><Clock class="w-6 h-6" /></template>
                    </EmptyState>
                    <ul v-else class="space-y-3">
                        <li v-for="b in store.upcoming" :key="b.id">
                            <BaseCard>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-sm font-semibold text-ink truncate">{{ b.service?.name }}</p>
                                            <BaseBadge :variant="statusVariant[b.status]">{{ statusLabel[b.status] }}</BaseBadge>
                                        </div>
                                        <p class="text-sm text-ink-muted mt-1">
                                            {{ fmtDateLong(b.starts_at) }} · {{ fmtTime(b.starts_at) }}
                                        </p>
                                        <p class="text-xs text-ink-soft mt-0.5">
                                            with {{ b.provider?.name }} · ref {{ b.reference?.slice(0, 8) }}
                                        </p>
                                        <p v-if="b.notes" class="text-xs text-ink-soft mt-2 line-clamp-2">"{{ b.notes }}"</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2 shrink-0">
                                        <BaseButton
                                            v-for="a in bookingActions(b)"
                                            :key="a.key"
                                            :variant="a.variant"
                                            size="sm"
                                            @click="a.key === 'reschedule' ? reschedule(b) : askCancel(b)"
                                        >
                                            <component :is="a.icon" class="w-4 h-4" /> {{ a.label }}
                                        </BaseButton>
                                    </div>
                                </div>
                            </BaseCard>
                        </li>
                    </ul>
                </section>

                <!-- Past / cancelled / declined -->
                <section v-if="store.past.length > 0">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-ink-soft mb-3 mt-2">History</h3>
                    <ul class="space-y-3">
                        <li v-for="b in store.past" :key="b.id">
                            <BaseCard>
                                <div class="flex items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-sm font-semibold text-ink truncate">{{ b.service?.name }}</p>
                                            <BaseBadge :variant="statusVariant[b.status]">{{ statusLabel[b.status] }}</BaseBadge>
                                        </div>
                                        <p class="text-sm text-ink-muted mt-1">
                                            {{ fmtDateLong(b.starts_at) }} · {{ fmtTime(b.starts_at) }}
                                        </p>
                                        <p v-if="b.decline_reason" class="text-xs text-red-600 mt-1">{{ b.decline_reason }}</p>
                                    </div>
                                </div>
                            </BaseCard>
                        </li>
                    </ul>
                </section>
            </template>
        </div>

        <BaseModal :open="!!cancelTarget" @close="cancelTarget = null" title="Cancel this booking?" size="sm">
            <p class="text-sm text-ink-muted">
                You're about to cancel
                <span class="font-medium text-ink">{{ cancelTarget?.service?.name }}</span>
                on {{ cancelTarget ? fmtDateLong(cancelTarget.starts_at) : '' }}.
                This can't be undone.
            </p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" @click="cancelTarget = null">Keep booking</BaseButton>
                    <BaseButton variant="danger" :loading="cancelling" @click="confirmCancel">Cancel booking</BaseButton>
                </div>
            </template>
        </BaseModal>
    </DashboardLayout>
</template>
