<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import BaseCard from '../../components/base/BaseCard.vue';
import BaseBadge from '../../components/base/BaseBadge.vue';
import BaseButton from '../../components/base/BaseButton.vue';
import BaseInput from '../../components/base/BaseInput.vue';
import BaseModal from '../../components/base/BaseModal.vue';
import BaseSkeleton from '../../components/base/BaseSkeleton.vue';
import EmptyState from '../../components/base/EmptyState.vue';
import { CalendarDays, CheckCircle2, XCircle, Ban, ArrowUpDown, Mail, Phone } from 'lucide-vue-next';
import { useProviderBookingsStore } from '../../stores/providerBookings';
import { useToastStore } from '../../stores/toast';
import { fmtDateLong, fmtTime } from '../../lib/calendar';

const store = useProviderBookingsStore();
const toast = useToastStore();

const STATUS_OPTIONS = [
    { value: '', label: 'All statuses' },
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'declined', label: 'Declined' },
    { value: 'cancelled', label: 'Cancelled' },
];

const SORT_OPTIONS = [
    { value: '-starts_at', label: 'Date · newest first' },
    { value: 'starts_at', label: 'Date · oldest first' },
    { value: '-created_at', label: 'Requested · newest first' },
    { value: 'created_at', label: 'Requested · oldest first' },
];

const statusVariant = {
    pending: 'warning',
    approved: 'success',
    declined: 'danger',
    cancelled: 'neutral',
};

const statusLabel = {
    pending: 'Pending',
    approved: 'Approved',
    declined: 'Declined',
    cancelled: 'Cancelled',
};

// --- approval modal ---
const approveTarget = ref(null);
function askApprove(b) { approveTarget.value = b; }
async function confirmApprove() {
    if (!approveTarget.value) return;
    try {
        const res = await store.approve(approveTarget.value.id);
        const extra = res.autoDeclined > 0
            ? ` Auto-declined ${res.autoDeclined} other request${res.autoDeclined === 1 ? '' : 's'}.`
            : '';
        toast.success('Booking approved.' + extra);
        approveTarget.value = null;
    } catch {
        toast.error('Could not approve.');
    }
}

// --- decline modal ---
const declineTarget = ref(null);
const declineReason = ref('');
function askDecline(b) {
    declineTarget.value = b;
    declineReason.value = '';
}
async function confirmDecline() {
    if (!declineTarget.value) return;
    try {
        await store.decline(declineTarget.value.id, declineReason.value);
        toast.success('Booking declined.');
        declineTarget.value = null;
    } catch {
        toast.error('Could not decline.');
    }
}

// --- cancel approved modal ---
const cancelTarget = ref(null);
function askCancel(b) { cancelTarget.value = b; }
async function confirmCancel() {
    if (!cancelTarget.value) return;
    try {
        await store.cancel(cancelTarget.value.id);
        toast.success('Booking cancelled.');
        cancelTarget.value = null;
    } catch {
        toast.error('Could not cancel.');
    }
}

const hasActiveFilters = computed(() => Boolean(store.filters.status || store.filters.from || store.filters.to));

function clearFilters() {
    store.filters.status = '';
    store.filters.from = '';
    store.filters.to = '';
    store.fetch();
}

watch(
    () => [store.filters.status, store.filters.from, store.filters.to, store.filters.sort],
    () => store.fetch(),
);

onMounted(() => store.fetch());
</script>

<template>
    <DashboardLayout>
        <div class="space-y-6 max-w-6xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl sm:text-2xl font-semibold text-ink">Bookings</h2>
                    <p class="mt-1 text-sm text-ink-soft">Approve, decline, or cancel requests from your customers.</p>
                </div>
            </div>

            <!-- Filters -->
            <BaseCard :padded="false">
                <div class="px-6 py-4 flex flex-col lg:flex-row lg:items-end gap-3 flex-wrap">
                    <div class="flex-1 min-w-[10rem]">
                        <label class="block text-xs font-medium text-ink-soft mb-1.5 uppercase tracking-wide">Status</label>
                        <select v-model="store.filters.status" class="input-base">
                            <option v-for="o in STATUS_OPTIONS" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                    </div>
                    <BaseInput v-model="store.filters.from" label="From" type="date" class="flex-1 min-w-[9rem]" />
                    <BaseInput v-model="store.filters.to" label="To" type="date" class="flex-1 min-w-[9rem]" />
                    <div class="flex-1 min-w-[12rem]">
                        <label class="block text-xs font-medium text-ink-soft mb-1.5 uppercase tracking-wide">Sort</label>
                        <select v-model="store.filters.sort" class="input-base">
                            <option v-for="o in SORT_OPTIONS" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                    </div>
                    <BaseButton v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearFilters">Clear</BaseButton>
                </div>
            </BaseCard>

            <!-- List -->
            <div v-if="store.loading" class="space-y-3">
                <BaseSkeleton v-for="i in 4" :key="i" height="6.5rem" rounded="rounded-2xl" />
            </div>

            <EmptyState
                v-else-if="store.items.length === 0"
                title="No bookings here"
                :description="hasActiveFilters ? 'Try widening your filters.' : 'You\'ll see customer requests show up here.'"
            >
                <template #icon><CalendarDays class="w-6 h-6" /></template>
            </EmptyState>

            <ul v-else class="space-y-3">
                <li v-for="b in store.items" :key="b.id">
                    <BaseCard>
                        <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-semibold text-ink truncate">{{ b.service?.name }}</p>
                                    <BaseBadge :variant="statusVariant[b.status]">{{ statusLabel[b.status] }}</BaseBadge>
                                </div>
                                <p class="text-sm text-ink-muted mt-1">
                                    {{ fmtDateLong(b.starts_at) }} · {{ fmtTime(b.starts_at) }}
                                    <span class="text-ink-soft">— {{ b.service?.duration_minutes }} min</span>
                                </p>
                                <p class="text-xs text-ink-soft mt-1 flex flex-wrap gap-x-3 gap-y-1">
                                    <span class="font-medium text-ink-muted">{{ b.customer?.name }}</span>
                                    <span class="inline-flex items-center gap-1"><Mail class="w-3.5 h-3.5" /> {{ b.customer?.email }}</span>
                                    <span v-if="b.customer?.phone" class="inline-flex items-center gap-1"><Phone class="w-3.5 h-3.5" /> {{ b.customer.phone }}</span>
                                    <span>ref {{ b.reference?.slice(0, 8) }}</span>
                                </p>
                                <p v-if="b.notes" class="text-xs text-ink-soft mt-2 line-clamp-2">"{{ b.notes }}"</p>
                                <p v-if="b.decline_reason" class="text-xs text-red-600 mt-1">{{ b.decline_reason }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2 shrink-0">
                                <template v-if="b.status === 'pending'">
                                    <BaseButton size="sm" @click="askApprove(b)">
                                        <CheckCircle2 class="w-4 h-4" /> Approve
                                    </BaseButton>
                                    <BaseButton size="sm" variant="secondary" @click="askDecline(b)">
                                        <XCircle class="w-4 h-4" /> Decline
                                    </BaseButton>
                                </template>
                                <BaseButton
                                    v-else-if="b.status === 'approved'"
                                    size="sm"
                                    variant="ghost"
                                    @click="askCancel(b)"
                                >
                                    <Ban class="w-4 h-4" /> Cancel
                                </BaseButton>
                            </div>
                        </div>
                    </BaseCard>
                </li>
            </ul>
        </div>

        <!-- Approve modal -->
        <BaseModal :open="!!approveTarget" @close="approveTarget = null" title="Approve this booking?" size="sm">
            <p class="text-sm text-ink-muted">
                You'll confirm
                <span class="font-medium text-ink">{{ approveTarget?.service?.name }}</span>
                for {{ approveTarget?.customer?.name }}
                on {{ approveTarget ? fmtDateLong(approveTarget.starts_at) : '' }} at {{ approveTarget ? fmtTime(approveTarget.starts_at) : '' }}.
            </p>
            <p class="text-xs text-ink-soft mt-2">
                Any other pending requests for the same time will be automatically declined.
            </p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" @click="approveTarget = null">Cancel</BaseButton>
                    <BaseButton :loading="store.acting" @click="confirmApprove">Approve booking</BaseButton>
                </div>
            </template>
        </BaseModal>

        <!-- Decline modal -->
        <BaseModal :open="!!declineTarget" @close="declineTarget = null" title="Decline this booking?" size="sm">
            <p class="text-sm text-ink-muted">
                Letting <span class="font-medium text-ink">{{ declineTarget?.customer?.name }}</span> know their request couldn't be confirmed.
            </p>
            <div class="mt-3">
                <label class="block text-sm font-medium text-ink mb-1.5">Reason (optional)</label>
                <textarea
                    v-model="declineReason"
                    rows="3"
                    class="input-base"
                    placeholder="e.g. I have a conflict at that time."
                ></textarea>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" @click="declineTarget = null">Cancel</BaseButton>
                    <BaseButton variant="danger" :loading="store.acting" @click="confirmDecline">Decline</BaseButton>
                </div>
            </template>
        </BaseModal>

        <!-- Cancel-approved modal -->
        <BaseModal :open="!!cancelTarget" @close="cancelTarget = null" title="Cancel this approved booking?" size="sm">
            <p class="text-sm text-ink-muted">
                The customer will be notified that
                <span class="font-medium text-ink">{{ cancelTarget?.service?.name }}</span>
                on {{ cancelTarget ? fmtDateLong(cancelTarget.starts_at) : '' }} has been cancelled.
            </p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" @click="cancelTarget = null">Keep booking</BaseButton>
                    <BaseButton variant="danger" :loading="store.acting" @click="confirmCancel">Cancel booking</BaseButton>
                </div>
            </template>
        </BaseModal>
    </DashboardLayout>
</template>
