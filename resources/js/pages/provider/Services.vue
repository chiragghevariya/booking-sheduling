<script setup>
import { onMounted, ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import BaseCard from '../../components/base/BaseCard.vue';
import BaseButton from '../../components/base/BaseButton.vue';
import BaseInput from '../../components/base/BaseInput.vue';
import BaseModal from '../../components/base/BaseModal.vue';
import BaseBadge from '../../components/base/BaseBadge.vue';
import BaseSkeleton from '../../components/base/BaseSkeleton.vue';
import EmptyState from '../../components/base/EmptyState.vue';
import { Briefcase, Plus, Pencil, Trash2 } from 'lucide-vue-next';
import { useServicesStore } from '../../stores/services';
import { useToastStore } from '../../stores/toast';

const store = useServicesStore();
const toast = useToastStore();

const showForm = ref(false);
const editing = ref(null);
const form = ref(emptyForm());
const errors = ref({});

function emptyForm() {
    return {
        name: '',
        description: '',
        duration_minutes: 30,
        buffer_minutes: 10,
        price: 0,
        currency: 'USD',
        is_active: true,
    };
}

function openCreate() {
    editing.value = null;
    form.value = emptyForm();
    errors.value = {};
    showForm.value = true;
}

function openEdit(s) {
    editing.value = s;
    form.value = {
        name: s.name,
        description: s.description ?? '',
        duration_minutes: s.duration_minutes,
        buffer_minutes: s.buffer_minutes ?? 0,
        price: Number(s.price ?? 0),
        currency: s.currency ?? 'USD',
        is_active: !!s.is_active,
    };
    errors.value = {};
    showForm.value = true;
}

async function submit() {
    errors.value = {};
    try {
        if (editing.value) {
            await store.update(editing.value.id, form.value);
            toast.success('Service updated.');
        } else {
            await store.create(form.value);
            toast.success('Service created.');
        }
        showForm.value = false;
    } catch (e) {
        if (e.response?.status === 422) {
            const out = {};
            for (const k in e.response.data.errors) out[k] = e.response.data.errors[k][0];
            errors.value = out;
        } else {
            toast.error('Could not save.');
        }
    }
}

const deleteTarget = ref(null);
function askDelete(s) { deleteTarget.value = s; }
async function confirmDelete() {
    if (!deleteTarget.value) return;
    try {
        await store.remove(deleteTarget.value.id);
        toast.success('Service deleted.');
        deleteTarget.value = null;
    } catch {
        toast.error('Could not delete.');
    }
}

function money(amount, currency = 'USD') {
    const n = Number(amount ?? 0);
    if (!n) return 'Free';
    try {
        return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(n);
    } catch {
        return `${currency} ${n.toFixed(2)}`;
    }
}

onMounted(() => store.fetch());
</script>

<template>
    <DashboardLayout>
        <div class="space-y-6 max-w-5xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl sm:text-2xl font-semibold text-ink">Services</h2>
                    <p class="mt-1 text-sm text-ink-soft">Define what customers can book, with duration and pricing.</p>
                </div>
                <BaseButton @click="openCreate">
                    <Plus class="w-4 h-4" /> New service
                </BaseButton>
            </div>

            <div v-if="store.loading" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <BaseSkeleton v-for="i in 4" :key="i" height="9rem" rounded="rounded-2xl" />
            </div>

            <EmptyState
                v-else-if="store.items.length === 0"
                title="No services yet"
                description="Add your first service so customers can request bookings."
            >
                <template #icon><Briefcase class="w-6 h-6" /></template>
                <template #action>
                    <BaseButton @click="openCreate"><Plus class="w-4 h-4" /> New service</BaseButton>
                </template>
            </EmptyState>

            <ul v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <li v-for="s in store.items" :key="s.id">
                    <BaseCard>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-semibold text-ink truncate">{{ s.name }}</p>
                                    <BaseBadge :variant="s.is_active ? 'success' : 'neutral'" size="sm">
                                        {{ s.is_active ? 'Active' : 'Inactive' }}
                                    </BaseBadge>
                                </div>
                                <p v-if="s.description" class="text-xs text-ink-soft mt-1 line-clamp-2">{{ s.description }}</p>
                                <p class="mt-3 text-sm text-ink-muted">
                                    {{ s.duration_minutes }} min
                                    <span v-if="s.buffer_minutes" class="text-ink-soft">· {{ s.buffer_minutes }}m buffer</span>
                                    · <span class="font-medium text-ink">{{ money(s.price, s.currency) }}</span>
                                </p>
                            </div>
                            <div class="flex flex-col gap-1.5 shrink-0">
                                <button
                                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-ink-soft hover:text-brand-600 hover:bg-brand-50"
                                    @click="openEdit(s)" aria-label="Edit"
                                ><Pencil class="w-4 h-4" /></button>
                                <button
                                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-ink-soft hover:text-red-600 hover:bg-red-50"
                                    @click="askDelete(s)" aria-label="Delete"
                                ><Trash2 class="w-4 h-4" /></button>
                            </div>
                        </div>
                    </BaseCard>
                </li>
            </ul>
        </div>

        <BaseModal :open="showForm" @close="showForm = false" :title="editing ? 'Edit service' : 'New service'">
            <form @submit.prevent="submit" class="space-y-3">
                <BaseInput v-model="form.name" label="Name" :error="errors.name" required />
                <div>
                    <label class="block text-sm font-medium text-ink mb-1.5">Description</label>
                    <textarea v-model="form.description" rows="3" class="input-base" placeholder="What's included?"></textarea>
                    <p v-if="errors.description" class="text-xs text-red-600 mt-1">{{ errors.description }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <BaseInput v-model.number="form.duration_minutes" label="Duration (min)" type="number" :error="errors.duration_minutes" required />
                    <BaseInput v-model.number="form.buffer_minutes" label="Buffer (min)" type="number" :error="errors.buffer_minutes" />
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <BaseInput v-model.number="form.price" label="Price" type="number" step="0.01" :error="errors.price" required />
                    </div>
                    <BaseInput v-model="form.currency" label="Currency" maxlength="3" :error="errors.currency" />
                </div>
                <label class="flex items-center gap-2 text-sm text-ink-muted select-none">
                    <input type="checkbox" v-model="form.is_active" class="rounded border-line text-brand-600 focus:ring-brand-500/30" />
                    Active &mdash; show to customers
                </label>
            </form>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" @click="showForm = false">Cancel</BaseButton>
                    <BaseButton :loading="store.saving" @click="submit">{{ editing ? 'Save changes' : 'Create' }}</BaseButton>
                </div>
            </template>
        </BaseModal>

        <BaseModal :open="!!deleteTarget" @close="deleteTarget = null" title="Delete this service?" size="sm">
            <p class="text-sm text-ink-muted">
                <span class="font-medium text-ink">{{ deleteTarget?.name }}</span> will be removed. Existing bookings stay intact.
            </p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" @click="deleteTarget = null">Cancel</BaseButton>
                    <BaseButton variant="danger" :loading="store.saving" @click="confirmDelete">Delete</BaseButton>
                </div>
            </template>
        </BaseModal>
    </DashboardLayout>
</template>
