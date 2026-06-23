<script setup>
/**
 * Calendar surface for choosing a slot for a given service.
 *
 * Props:
 *   - service: { id, duration_minutes, ... }
 * Emits:
 *   - 'select' (slot: { starts_at, ends_at })
 *
 * Loads slots from /api/slots and renders a month/week/day toggle. On mobile
 * the grid collapses to a stacked day list regardless of view.
 */
import { computed, ref, watch, onMounted } from 'vue';
import api from '../../lib/api';
import {
    addDays,
    fmtDateLong,
    fmtDateShort,
    fmtMonth,
    fmtTime,
    isSameDay,
    monthGrid,
    startOfDay,
    toISODate,
    weekDays,
} from '../../lib/calendar';
import { ChevronLeft, ChevronRight, Clock } from 'lucide-vue-next';
import BaseButton from '../base/BaseButton.vue';
import BaseSkeleton from '../base/BaseSkeleton.vue';
import EmptyState from '../base/EmptyState.vue';

const props = defineProps({
    service: { type: Object, required: true },
    selectedSlot: { type: Object, default: null },
});
const emit = defineEmits(['select']);

const view = ref('week'); // 'month' | 'week' | 'day'
const cursor = ref(startOfDay(new Date()));
const slots = ref([]);
const loading = ref(false);

// Range covering the current view â€” we always fetch a bit extra so day-clicks within month view are pre-loaded.
const range = computed(() => {
    if (view.value === 'day') {
        return { from: cursor.value, to: cursor.value };
    }
    if (view.value === 'week') {
        const week = weekDays(cursor.value);
        return { from: week[0], to: week[6] };
    }
    // month view: fetch the full visible grid (6 weeks)
    const grid = monthGrid(cursor.value);
    return { from: grid[0].date, to: grid[grid.length - 1].date };
});

async function fetchSlots() {
    if (!props.service?.id) return;
    loading.value = true;
    try {
        const { data } = await api.get('/api/slots', {
            params: {
                service_id: props.service.id,
                from: toISODate(range.value.from),
                to: toISODate(range.value.to),
            },
        });
        slots.value = data.data ?? [];
    } finally {
        loading.value = false;
    }
}

// Map slots to ISO date for quick lookup by day.
const slotsByDay = computed(() => {
    const map = {};
    for (const s of slots.value) {
        const key = toISODate(new Date(s.starts_at));
        (map[key] ??= []).push(s);
    }
    return map;
});

function slotsForDate(d) {
    return slotsByDay.value[toISODate(d)] ?? [];
}

function selectSlot(slot) {
    emit('select', slot);
}

function shift(deltaUnits) {
    if (view.value === 'day') cursor.value = addDays(cursor.value, deltaUnits);
    else if (view.value === 'week') cursor.value = addDays(cursor.value, deltaUnits * 7);
    else {
        const x = new Date(cursor.value);
        x.setMonth(x.getMonth() + deltaUnits);
        cursor.value = startOfDay(x);
    }
}

function goToday() {
    cursor.value = startOfDay(new Date());
}

const headerLabel = computed(() => {
    if (view.value === 'day') return fmtDateLong(cursor.value);
    if (view.value === 'month') return fmtMonth(cursor.value);
    const w = weekDays(cursor.value);
    return `${fmtDateShort(w[0])} â€“ ${fmtDateShort(w[6])}`;
});

watch(() => [props.service?.id, view.value, cursor.value], () => fetchSlots(), { deep: false });
onMounted(fetchSlots);

const weekdayShort = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

function isSelected(slot) {
    return props.selectedSlot && props.selectedSlot.starts_at === slot.starts_at;
}
</script>

<template>
    <div class="space-y-4">
        <!-- Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-line text-ink-muted hover:bg-surface-sunken"
                    @click="shift(-1)"
                    aria-label="Previous"
                >
                    <ChevronLeft class="w-4 h-4" />
                </button>
                <button
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-line text-ink-muted hover:bg-surface-sunken"
                    @click="shift(1)"
                    aria-label="Next"
                >
                    <ChevronRight class="w-4 h-4" />
                </button>
                <button
                    class="text-sm font-medium text-ink-muted hover:text-ink rounded-lg px-2 py-1.5 hover:bg-surface-sunken"
                    @click="goToday"
                >
                    Today
                </button>
                <span class="text-sm font-semibold text-ink ml-1">{{ headerLabel }}</span>
            </div>

            <div class="inline-flex rounded-xl border border-line p-1 bg-surface-raised text-sm self-start">
                <button
                    v-for="v in ['month', 'week', 'day']"
                    :key="v"
                    class="px-3 py-1.5 rounded-lg capitalize transition"
                    :class="view === v ? 'bg-brand-50 text-brand-700 font-medium' : 'text-ink-muted hover:bg-surface-sunken'"
                    @click="view = v"
                >
                    {{ v }}
                </button>
            </div>
        </div>

        <!-- MONTH (desktop grid) -->
        <div v-if="view === 'month'" class="hidden md:block">
            <div v-if="loading" class="grid grid-cols-7 gap-2">
                <BaseSkeleton v-for="i in 42" :key="i" height="5rem" rounded="rounded-xl" />
            </div>
            <div v-else>
                <div class="grid grid-cols-7 gap-2 mb-2 text-xs font-medium text-ink-soft">
                    <div v-for="d in weekdayShort" :key="d" class="text-center">{{ d }}</div>
                </div>
                <div class="grid grid-cols-7 gap-2">
                    <button
                        v-for="cell in monthGrid(cursor)"
                        :key="cell.date.toISOString()"
                        type="button"
                        class="aspect-square sm:aspect-auto sm:h-20 rounded-xl border text-left p-2 transition"
                        :class="[
                            cell.inMonth ? 'border-line bg-surface-raised' : 'border-transparent bg-surface-sunken/50 text-ink-soft',
                            isSameDay(cell.date, cursor) ? 'ring-2 ring-brand-500/40' : '',
                            slotsForDate(cell.date).length > 0 ? 'hover:bg-brand-50 hover:border-brand-300 cursor-pointer' : 'cursor-default',
                        ]"
                        @click="slotsForDate(cell.date).length > 0 ? (cursor = cell.date, view = 'day') : null"
                    >
                        <div class="text-xs font-semibold" :class="cell.inMonth ? 'text-ink' : 'text-ink-soft'">{{ cell.date.getDate() }}</div>
                        <div v-if="slotsForDate(cell.date).length > 0" class="mt-1">
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-700">
                                <Clock class="w-3 h-3" /> {{ slotsForDate(cell.date).length }}
                            </span>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- WEEK (desktop) -->
        <div v-if="view === 'week'" class="hidden md:block">
            <div v-if="loading" class="grid grid-cols-7 gap-3">
                <BaseSkeleton v-for="i in 7" :key="i" height="16rem" rounded="rounded-xl" />
            </div>
            <div v-else class="grid grid-cols-7 gap-3">
                <div
                    v-for="d in weekDays(cursor)"
                    :key="d.toISOString()"
                    class="rounded-xl border border-line bg-surface-raised"
                >
                    <div class="px-3 pt-3 pb-2 border-b border-line">
                        <p class="text-[11px] uppercase tracking-wide font-semibold text-ink-soft">{{ d.toLocaleDateString(undefined, { weekday: 'short' }) }}</p>
                        <p class="text-sm font-semibold text-ink">{{ d.getDate() }}</p>
                    </div>
                    <div class="p-2 space-y-1.5 max-h-72 overflow-y-auto">
                        <button
                            v-for="s in slotsForDate(d)"
                            :key="s.starts_at"
                            type="button"
                            class="w-full text-xs rounded-lg px-2 py-1.5 font-medium transition"
                            :class="isSelected(s) ? 'bg-brand-600 text-white' : 'bg-brand-50 text-brand-700 hover:bg-brand-100'"
                            @click="selectSlot(s)"
                        >
                            {{ fmtTime(s.starts_at) }}
                        </button>
                        <p v-if="slotsForDate(d).length === 0" class="text-xs text-ink-soft text-center py-3">â€”</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- DAY view + MOBILE fallback (always stacked day-list) -->
        <div :class="view === 'day' ? '' : 'md:hidden'">
            <div v-if="loading" class="space-y-3">
                <BaseSkeleton v-for="i in 6" :key="i" height="2.5rem" />
            </div>
            <div v-else class="space-y-4">
                <!-- If month/week on mobile: show each day with its slots stacked -->
                <div v-if="view !== 'day'" class="space-y-4">
                    <div
                        v-for="d in (view === 'week' ? weekDays(cursor) : monthGrid(cursor).filter(c => c.inMonth).map(c => c.date))"
                        :key="d.toISOString()"
                    >
                        <p class="text-xs uppercase tracking-wide font-semibold text-ink-soft px-1">{{ fmtDateLong(d) }}</p>
                        <div v-if="slotsForDate(d).length === 0" class="text-xs text-ink-soft py-2 px-1">No times available</div>
                        <div v-else class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="s in slotsForDate(d)"
                                :key="s.starts_at"
                                type="button"
                                class="text-sm rounded-lg px-3 py-1.5 font-medium transition"
                                :class="isSelected(s) ? 'bg-brand-600 text-white' : 'bg-brand-50 text-brand-700 hover:bg-brand-100'"
                                @click="selectSlot(s)"
                            >
                                {{ fmtTime(s.starts_at) }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Day view (universal) -->
                <div v-else>
                    <EmptyState
                        v-if="slotsForDate(cursor).length === 0"
                        title="No times available"
                        description="Try another day or a different service."
                    />
                    <div v-else class="flex flex-wrap gap-2">
                        <button
                            v-for="s in slotsForDate(cursor)"
                            :key="s.starts_at"
                            type="button"
                            class="text-sm rounded-xl px-4 py-2 font-medium transition"
                            :class="isSelected(s) ? 'bg-brand-600 text-white shadow-sm' : 'bg-brand-50 text-brand-700 hover:bg-brand-100'"
                            @click="selectSlot(s)"
                        >
                            {{ fmtTime(s.starts_at) }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
