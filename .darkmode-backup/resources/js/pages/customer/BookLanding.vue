<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import BaseCard from '../../components/base/BaseCard.vue';
import BaseButton from '../../components/base/BaseButton.vue';
import BaseModal from '../../components/base/BaseModal.vue';
import BaseInput from '../../components/base/BaseInput.vue';
import BaseSkeleton from '../../components/base/BaseSkeleton.vue';
import EmptyState from '../../components/base/EmptyState.vue';
import BookingCalendar from '../../components/booking/BookingCalendar.vue';
import { Briefcase, Clock, ArrowLeft, CheckCircle2 } from 'lucide-vue-next';
import api from '../../lib/api';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';
import { useBookingsStore } from '../../stores/bookings';
import { fmtDateLong, fmtTime } from '../../lib/calendar';

const auth = useAuthStore();
const toast = useToastStore();
const bookings = useBookingsStore();
const route = useRoute();
const router = useRouter();

const services = ref([]);
const loadingServices = ref(false);
const selectedService = ref(null);
const selectedSlot = ref(null);

const showBookingModal = ref(false);
const showSuccessModal = ref(false);
const submitting = ref(false);
const lastBooking = ref(null);
const errors = ref({});

const form = ref({
    name: auth.user?.name ?? '',
    email: auth.user?.email ?? '',
    phone: auth.user?.phone ?? '',
    notes: '',
});

const rescheduleMode = computed(() => Boolean(route.query.reschedule));
const rescheduleId = computed(() => route.query.reschedule);

async function loadServices() {
    loadingServices.value = true;
    try {
        const { data } = await api.get('/api/services');
        services.value = data.data ?? data;
    } finally {
        loadingServices.value = false;
    }
}

function pickService(svc) {
    selectedService.value = svc;
    selectedSlot.value = null;
}

function onSlotSelected(slot) {
    selectedSlot.value = slot;
    // Reschedule path: confirm immediately, no modal collection needed.
    if (rescheduleMode.value) {
        confirmReschedule(slot);
    } else {
        showBookingModal.value = true;
    }
}

async function submitBooking() {
    errors.value = {};
    submitting.value = true;
    try {
        const booking = await bookings.create({
            service_id: selectedService.value.id,
            starts_at: selectedSlot.value.starts_at,
            phone: form.value.phone || undefined,
            notes: form.value.notes || undefined,
        });
        lastBooking.value = booking;
        showBookingModal.value = false;
        showSuccessModal.value = true;
        toast.success('Request sent — we\'ll notify the provider.');
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = mapErrors(e.response.data.errors);
            // Slot taken case is a top-level field error
            if (errors.value.starts_at) {
                toast.error(errors.value.starts_at);
                showBookingModal.value = false;
                selectedSlot.value = null;
            }
        } else {
            toast.error('Could not request that slot. Please try again.');
        }
    } finally {
        submitting.value = false;
    }
}

async function confirmReschedule(slot) {
    submitting.value = true;
    try {
        await bookings.reschedule(rescheduleId.value, slot.starts_at);
        toast.success('Booking rescheduled — pending re-approval.');
        router.push('/my-bookings');
    } catch (e) {
        if (e.response?.status === 422) {
            const errs = mapErrors(e.response.data.errors);
            toast.error(errs.starts_at ?? 'Could not reschedule.');
        } else {
            toast.error('Could not reschedule.');
        }
        selectedSlot.value = null;
    } finally {
        submitting.value = false;
    }
}

function mapErrors(payload) {
    const out = {};
    for (const k in payload) out[k] = Array.isArray(payload[k]) ? payload[k][0] : String(payload[k]);
    return out;
}

function moneyFmt(amount, currency = 'USD') {
    const n = Number(amount ?? 0);
    if (!n) return 'Free';
    try {
        return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(n);
    } catch {
        return `${currency} ${n.toFixed(2)}`;
    }
}

function viewMyBookings() {
    showSuccessModal.value = false;
    router.push('/my-bookings');
}

function bookAnother() {
    showSuccessModal.value = false;
    selectedSlot.value = null;
    selectedService.value = null;
}

// If we're in reschedule mode, auto-pick the original service.
async function prefillForReschedule() {
    if (!rescheduleMode.value) return;
    try {
        const { data } = await api.get(`/api/bookings/${rescheduleId.value}`);
        const b = data.data ?? data;
        const svc = services.value.find((s) => s.id === b.service?.id);
        if (svc) selectedService.value = svc;
    } catch {
        toast.error('Could not load the booking to reschedule.');
        router.push('/my-bookings');
    }
}

onMounted(async () => {
    await loadServices();
    await prefillForReschedule();
});

watch(rescheduleId, () => prefillForReschedule());
</script>

<template>
    <DashboardLayout>
        <div class="space-y-6 max-w-6xl mx-auto">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl sm:text-2xl font-semibold text-ink">
                        <template v-if="rescheduleMode">Reschedule your booking</template>
                        <template v-else>Book a service</template>
                    </h2>
                    <p class="mt-1 text-sm text-ink-soft">
                        <template v-if="rescheduleMode">Pick a new time below — it'll go back to the provider for approval.</template>
                        <template v-else>Pick a service, then a time that works for you.</template>
                    </p>
                </div>
                <BaseButton v-if="selectedService" variant="ghost" size="sm" @click="selectedService = null; selectedSlot = null">
                    <ArrowLeft class="w-4 h-4" /> Change service
                </BaseButton>
            </div>

            <!-- Service picker -->
            <div v-if="!selectedService">
                <div v-if="loadingServices" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <BaseSkeleton v-for="i in 3" :key="i" height="9rem" rounded="rounded-2xl" />
                </div>
                <EmptyState
                    v-else-if="services.length === 0"
                    title="No services available"
                    description="Check back soon — providers haven't published anything bookable yet."
                >
                    <template #icon><Briefcase class="w-6 h-6" /></template>
                </EmptyState>
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <button
                        v-for="svc in services"
                        :key="svc.id"
                        type="button"
                        class="text-left rounded-2xl border border-line bg-white p-5 shadow-soft hover:border-brand-300 hover:shadow-md transition"
                        @click="pickService(svc)"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-brand-600">{{ svc.provider?.name }}</p>
                        <p class="mt-1 text-base font-semibold text-ink">{{ svc.name }}</p>
                        <p v-if="svc.description" class="mt-1 text-sm text-ink-soft line-clamp-2">{{ svc.description }}</p>
                        <div class="mt-4 flex items-center gap-4 text-sm">
                            <span class="inline-flex items-center gap-1.5 text-ink-muted">
                                <Clock class="w-4 h-4" /> {{ svc.duration_minutes }} min
                            </span>
                            <span class="font-medium text-ink">{{ moneyFmt(svc.price, svc.currency) }}</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Calendar -->
            <BaseCard v-else>
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-brand-600">{{ selectedService.provider?.name }}</p>
                        <h3 class="text-base font-semibold text-ink">{{ selectedService.name }}</h3>
                        <p class="text-xs text-ink-soft mt-0.5">{{ selectedService.duration_minutes }} min · {{ moneyFmt(selectedService.price, selectedService.currency) }}</p>
                    </div>
                </div>

                <BookingCalendar
                    :service="selectedService"
                    :selected-slot="selectedSlot"
                    @select="onSlotSelected"
                />
            </BaseCard>
        </div>

        <!-- Booking form modal -->
        <BaseModal :open="showBookingModal" @close="showBookingModal = false" title="Confirm your booking">
            <div v-if="selectedSlot" class="rounded-xl bg-surface-sunken px-4 py-3 mb-4">
                <p class="text-sm font-semibold text-ink">{{ selectedService.name }}</p>
                <p class="text-xs text-ink-muted mt-0.5">{{ fmtDateLong(selectedSlot.starts_at) }} at {{ fmtTime(selectedSlot.starts_at) }}</p>
                <p class="text-xs text-ink-soft mt-0.5">with {{ selectedService.provider?.name }} · {{ selectedService.duration_minutes }} min</p>
            </div>

            <form @submit.prevent="submitBooking" class="space-y-3">
                <BaseInput v-model="form.name" label="Name" disabled />
                <BaseInput v-model="form.email" label="Email" type="email" disabled />
                <BaseInput v-model="form.phone" label="Phone (optional)" placeholder="+1 555 0123" :error="errors.phone" />
                <div>
                    <label class="block text-sm font-medium text-ink mb-1.5">Notes (optional)</label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        class="input-base"
                        placeholder="Anything the provider should know?"
                    ></textarea>
                    <p v-if="errors.notes" class="text-xs text-red-600 mt-1">{{ errors.notes }}</p>
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" @click="showBookingModal = false">Cancel</BaseButton>
                    <BaseButton :loading="submitting" @click="submitBooking">Request booking</BaseButton>
                </div>
            </template>
        </BaseModal>

        <!-- Success modal -->
        <BaseModal :open="showSuccessModal" @close="bookAnother" size="sm">
            <div class="text-center py-2">
                <div class="mx-auto w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <CheckCircle2 class="w-6 h-6" />
                </div>
                <h3 class="mt-4 text-base font-semibold text-ink">Request received</h3>
                <p class="mt-1 text-sm text-ink-soft">
                    Your booking is <strong class="text-ink">pending approval</strong>. You'll get an email as soon as the provider responds.
                </p>
                <div v-if="lastBooking" class="mt-4 rounded-xl bg-surface-sunken px-4 py-3 text-left">
                    <p class="text-xs text-ink-soft">Reference</p>
                    <p class="text-sm font-mono text-ink">{{ lastBooking.reference }}</p>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" @click="bookAnother">Book another</BaseButton>
                    <BaseButton @click="viewMyBookings">View my bookings</BaseButton>
                </div>
            </template>
        </BaseModal>
    </DashboardLayout>
</template>
