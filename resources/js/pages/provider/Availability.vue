<script setup>
import { computed, onMounted, ref } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import BaseCard from '../../components/base/BaseCard.vue';
import BaseButton from '../../components/base/BaseButton.vue';
import BaseBadge from '../../components/base/BaseBadge.vue';
import BaseModal from '../../components/base/BaseModal.vue';
import BaseInput from '../../components/base/BaseInput.vue';
import BaseSkeleton from '../../components/base/BaseSkeleton.vue';
import EmptyState from '../../components/base/EmptyState.vue';
import { Plus, Trash2, CalendarOff, CircleX } from 'lucide-vue-next';
import { useAvailabilityStore } from '../../stores/availability';
import { useToastStore } from '../../stores/toast';

const store = useAvailabilityStore();
const toast = useToastStore();

const DAYS = [
    { idx: 1, label: 'Monday', short: 'Mon' },
    { idx: 2, label: 'Tuesday', short: 'Tue' },
    { idx: 3, label: 'Wednesday', short: 'Wed' },
    { idx: 4, label: 'Thursday', short: 'Thu' },
    { idx: 5, label: 'Friday', short: 'Fri' },
    { idx: 6, label: 'Saturday', short: 'Sat' },
    { idx: 0, label: 'Sunday', short: 'Sun' },
];

const windowsByDay = computed(() => {
    const out = {};
    for (const d of DAYS) out[d.idx] = [];
    for (const w of store.weekly) {
        if (!out[w.day_of_week]) out[w.day_of_week] = [];
        out[w.day_of_week].push(w);
    }
    return out;
});

async function addWindow(dayIdx) {
    try {
        await store.createWindow({
            day_of_week: dayIdx,
            start_time: '09:00',
            end_time: '17:00',
            is_active: true,
        });
        toast.success('Window added.');
    } catch (e) {
        toast.error(extractError(e, 'Could not add window.'));
    }
}

async function saveWindow(w) {
    try {
        await store.updateWindow(w.id, {
            start_time: w.start_time,
            end_time: w.end_time,
            is_active: w.is_active,
        });
    } catch (e) {
        toast.error(extractError(e, 'Could not save changes.'));
    }
}

async function removeWindow(w) {
    try {
        await store.deleteWindow(w.id);
        toast.success('Window removed.');
    } catch {
        toast.error('Could not remove window.');
    }
}

// --- Exceptions ---

const showExceptionModal = ref(false);
const exceptionForm = ref({ date: '', type: 'blocked', start_time: '', end_time: '', reason: '' });
const exceptionErrors = ref({});

function openExceptionModal() {
    exceptionForm.value = { date: today(), type: 'blocked', start_time: '', end_time: '', reason: '' };
    exceptionErrors.value = {};
    showExceptionModal.value = true;
}

function today() {
    // Local date (not toISOString() which is UTC). In UTC+ zones the UTC date
    // can still be "yesterday" in the early morning, defaulting the override
    // modal to the wrong day.
    const x = new Date();
    return `${x.getFullYear()}-${String(x.getMonth() + 1).padStart(2, '0')}-${String(x.getDate()).padStart(2, '0')}`;
}

async function submitException() {
    exceptionErrors.value = {};
    try {
        const payload = { ...exceptionForm.value };
        // Drop empty time fields so backend doesn't reject format
        if (!payload.start_time) delete payload.start_time;
        if (!payload.end_time) delete payload.end_time;
        if (!payload.reason) delete payload.reason;
        await store.createException(payload);
        showExceptionModal.value = false;
        toast.success('Exception added.');
    } catch (e) {
        if (e.response?.status === 422) {
            exceptionErrors.value = mapErrors(e.response.data.errors);
        } else {
            toast.error('Could not add exception.');
        }
    }
}

async function removeException(ex) {
    try {
        await store.deleteException(ex.id);
        toast.success('Exception removed.');
    } catch {
        toast.error('Could not remove.');
    }
}

function extractError(e, fallback) {
    if (e.response?.status === 422) {
        const errs = e.response.data.errors ?? {};
        const first = Object.values(errs)[0];
        return Array.isArray(first) ? first[0] : (first ?? fallback);
    }
    return fallback;
}

function mapErrors(payload) {
    const out = {};
    for (const k in payload) out[k] = Array.isArray(payload[k]) ? payload[k][0] : String(payload[k]);
    return out;
}

function fmtDate(iso) {
    try {
        return new Date(iso + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
    } catch {
        return iso;
    }
}

onMounted(() => store.fetchAll());
</script>

<template>
    <DashboardLayout>
        <div class="space-y-6 max-w-5xl mx-auto">
            <div>
                <h2 class="text-xl sm:text-2xl font-semibold text-ink">Availability</h2>
                <p class="mt-1 text-sm text-ink-soft">Set your weekly working hours and block off specific dates.</p>
            </div>

            <!-- Weekly hours -->
            <BaseCard :padded="false">
                <div class="px-6 pt-5 pb-4 border-b border-line">
                    <h3 class="text-sm font-semibold text-ink">Weekly hours</h3>
                    <p class="text-xs text-ink-soft mt-1">Add one or more windows per day. Customers can request slots inside these times.</p>
                </div>

                <div v-if="store.loading" class="p-6 space-y-3">
                    <BaseSkeleton v-for="i in 5" :key="i" height="2.5rem" rounded="rounded-xl" />
                </div>

                <ul v-else class="divide-y divide-line">
                    <li
                        v-for="day in DAYS"
                        :key="day.idx"
                        class="px-6 py-4 flex flex-col sm:flex-row sm:items-start gap-4"
                    >
                        <div class="sm:w-28 shrink-0 pt-1">
                            <p class="text-sm font-semibold text-ink">{{ day.label }}</p>
                            <p class="text-xs text-ink-soft mt-0.5">
                                <span v-if="windowsByDay[day.idx].length === 0">Closed</span>
                                <span v-else>{{ windowsByDay[day.idx].length }} window<span v-if="windowsByDay[day.idx].length !== 1">s</span></span>
                            </p>
                        </div>

                        <div class="flex-1 space-y-2">
                            <div
                                v-for="w in windowsByDay[day.idx]"
                                :key="w.id"
                                class="flex items-center gap-2 flex-wrap"
                            >
                                <input
                                    type="time"
                                    v-model="w.start_time"
                                    @change="saveWindow(w)"
                                    class="input-base w-28 h-9 py-1.5"
                                />
                                <span class="text-ink-soft text-sm">to</span>
                                <input
                                    type="time"
                                    v-model="w.end_time"
                                    @change="saveWindow(w)"
                                    class="input-base w-28 h-9 py-1.5"
                                />
                                <button
                                    class="ml-1 inline-flex items-center justify-center w-9 h-9 rounded-lg text-ink-soft hover:text-red-600 hover:bg-red-50 transition"
                                    @click="removeWindow(w)"
                                    aria-label="Remove window"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>

                            <BaseButton
                                variant="ghost"
                                size="sm"
                                @click="addWindow(day.idx)"
                                :loading="store.saving"
                            >
                                <Plus class="w-4 h-4" /> Add window
                            </BaseButton>
                        </div>
                    </li>
                </ul>
            </BaseCard>

            <!-- Exceptions -->
            <BaseCard :padded="false">
                <div class="px-6 pt-5 pb-4 border-b border-line flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-ink">Date overrides</h3>
                        <p class="text-xs text-ink-soft mt-1">Block off specific dates or override your hours for a one-off.</p>
                    </div>
                    <BaseButton size="sm" @click="openExceptionModal">
                        <Plus class="w-4 h-4" /> Add
                    </BaseButton>
                </div>

                <div v-if="store.loading" class="p-6 space-y-3">
                    <BaseSkeleton v-for="i in 3" :key="i" height="2.5rem" />
                </div>

                <EmptyState
                    v-else-if="store.exceptions.length === 0"
                    title="No overrides"
                    description="Add an override to block a date or set custom hours for it."
                >
                    <template #icon><CalendarOff class="w-6 h-6" /></template>
                </EmptyState>

                <ul v-else class="divide-y divide-line">
                    <li v-for="ex in store.exceptions" :key="ex.id" class="px-6 py-3 flex items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-medium text-ink">{{ fmtDate(ex.date) }}</span>
                                <BaseBadge :variant="ex.type === 'blocked' ? 'danger' : 'info'" size="sm">
                                    {{ ex.type === 'blocked' ? 'Blocked' : 'Custom hours' }}
                                </BaseBadge>
                                <span v-if="ex.start_time && ex.end_time" class="text-xs text-ink-soft">
                                    {{ ex.start_time }} – {{ ex.end_time }}
                                </span>
                                <span v-else-if="ex.type === 'blocked'" class="text-xs text-ink-soft">whole day</span>
                            </div>
                            <p v-if="ex.reason" class="text-xs text-ink-soft mt-0.5 truncate">{{ ex.reason }}</p>
                        </div>
                        <button
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-ink-soft hover:text-red-600 hover:bg-red-50 transition"
                            @click="removeException(ex)"
                            aria-label="Remove"
                        >
                            <CircleX class="w-4 h-4" />
                        </button>
                    </li>
                </ul>
            </BaseCard>
        </div>

        <BaseModal :open="showExceptionModal" @close="showExceptionModal = false" title="Add date override">
            <form @submit.prevent="submitException" class="space-y-4">
                <BaseInput
                    v-model="exceptionForm.date"
                    label="Date"
                    type="date"
                    :error="exceptionErrors.date"
                    required
                />

                <div>
                    <label class="block text-sm font-medium text-ink mb-1.5">Type</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            class="rounded-xl border px-3 py-2.5 text-left transition"
                            :class="exceptionForm.type === 'blocked' ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-line text-ink-muted hover:bg-surface-sunken'"
                            @click="exceptionForm.type = 'blocked'"
                        >
                            <p class="text-sm font-medium">Blocked</p>
                            <p class="text-[11px] mt-0.5 opacity-80">Make this date unavailable.</p>
                        </button>
                        <button
                            type="button"
                            class="rounded-xl border px-3 py-2.5 text-left transition"
                            :class="exceptionForm.type === 'custom' ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-line text-ink-muted hover:bg-surface-sunken'"
                            @click="exceptionForm.type = 'custom'"
                        >
                            <p class="text-sm font-medium">Custom hours</p>
                            <p class="text-[11px] mt-0.5 opacity-80">Replace the weekly hours for this date.</p>
                        </button>
                    </div>
                    <p v-if="exceptionErrors.type" class="text-xs text-red-600 mt-1">{{ exceptionErrors.type }}</p>
                </div>

                <div v-if="exceptionForm.type === 'custom' || exceptionForm.start_time || exceptionForm.end_time" class="grid grid-cols-2 gap-3">
                    <BaseInput
                        v-model="exceptionForm.start_time"
                        label="Start"
                        type="time"
                        :error="exceptionErrors.start_time"
                    />
                    <BaseInput
                        v-model="exceptionForm.end_time"
                        label="End"
                        type="time"
                        :error="exceptionErrors.end_time"
                    />
                </div>

                <p v-if="exceptionForm.type === 'blocked'" class="text-xs text-ink-soft">
                    Leave times blank to block the whole day, or set a range to block only part of the day.
                </p>

                <BaseInput
                    v-model="exceptionForm.reason"
                    label="Reason (optional)"
                    placeholder="e.g. Holiday"
                    :error="exceptionErrors.reason"
                />
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" @click="showExceptionModal = false">Cancel</BaseButton>
                    <BaseButton :loading="store.saving" @click="submitException">Save</BaseButton>
                </div>
            </template>
        </BaseModal>
    </DashboardLayout>
</template>
