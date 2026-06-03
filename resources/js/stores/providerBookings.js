import { defineStore } from 'pinia';
import api, { ensureCsrf } from '../lib/api';

export const useProviderBookingsStore = defineStore('providerBookings', {
    state: () => ({
        items: [],
        loading: false,
        acting: false,
        filters: {
            status: '',
            from: '',
            to: '',
            sort: '-starts_at',
        },
    }),
    getters: {
        counts(state) {
            const c = { pending: 0, approved: 0, declined: 0, cancelled: 0 };
            for (const b of state.items) c[b.status] = (c[b.status] ?? 0) + 1;
            return c;
        },
    },
    actions: {
        async fetch() {
            this.loading = true;
            try {
                const { data } = await api.get('/api/provider/bookings', {
                    params: Object.fromEntries(
                        Object.entries(this.filters).filter(([, v]) => v !== '' && v !== null && v !== undefined),
                    ),
                });
                this.items = data.data ?? data;
            } finally {
                this.loading = false;
            }
        },

        async approve(id) {
            await ensureCsrf();
            this.acting = true;
            try {
                const { data } = await api.post(`/api/provider/bookings/${id}/approve`);
                const next = data.data;
                const autoIds = data.meta?.auto_declined_ids ?? [];
                this.replace(next);
                // Refresh affected pending rows in-place so the list reflects auto-declines without a full reload.
                for (const otherId of autoIds) {
                    const i = this.items.findIndex((b) => b.id === otherId);
                    if (i !== -1) this.items[i] = { ...this.items[i], status: 'declined', decline_reason: 'Slot no longer available — another request was approved.' };
                }
                return { booking: next, autoDeclined: data.meta?.auto_declined ?? 0 };
            } finally {
                this.acting = false;
            }
        },

        async decline(id, reason) {
            await ensureCsrf();
            this.acting = true;
            try {
                const { data } = await api.post(`/api/provider/bookings/${id}/decline`, { reason: reason || undefined });
                this.replace(data.data);
                return data.data;
            } finally {
                this.acting = false;
            }
        },

        async cancel(id) {
            await ensureCsrf();
            this.acting = true;
            try {
                const { data } = await api.delete(`/api/provider/bookings/${id}`);
                this.replace(data.data);
                return data.data;
            } finally {
                this.acting = false;
            }
        },

        replace(b) {
            const i = this.items.findIndex((x) => x.id === b.id);
            if (i !== -1) this.items[i] = b;
        },
    },
});
