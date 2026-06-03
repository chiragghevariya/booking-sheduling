import { defineStore } from 'pinia';
import api, { ensureCsrf } from '../lib/api';

export const useBookingsStore = defineStore('bookings', {
    state: () => ({
        items: [],
        loading: false,
        creating: false,
    }),
    getters: {
        upcoming(state) {
            const now = new Date();
            return state.items
                .filter((b) => b.status !== 'cancelled' && b.status !== 'declined' && new Date(b.starts_at) >= now)
                .sort((a, b) => new Date(a.starts_at) - new Date(b.starts_at));
        },
        past(state) {
            const now = new Date();
            return state.items
                .filter((b) => b.status === 'cancelled' || b.status === 'declined' || new Date(b.starts_at) < now)
                .sort((a, b) => new Date(b.starts_at) - new Date(a.starts_at));
        },
    },
    actions: {
        async fetchMine() {
            this.loading = true;
            try {
                const { data } = await api.get('/api/bookings');
                this.items = data.data ?? data;
            } finally {
                this.loading = false;
            }
        },

        async create(payload) {
            await ensureCsrf();
            this.creating = true;
            try {
                const { data } = await api.post('/api/bookings', payload);
                const next = data.data ?? data;
                this.items.unshift(next);
                return next;
            } finally {
                this.creating = false;
            }
        },

        async reschedule(id, startsAt) {
            await ensureCsrf();
            const { data } = await api.put(`/api/bookings/${id}`, { starts_at: startsAt });
            const next = data.data ?? data;
            const idx = this.items.findIndex((b) => b.id === id);
            if (idx !== -1) this.items[idx] = next;
            return next;
        },

        async cancel(id) {
            await ensureCsrf();
            const { data } = await api.delete(`/api/bookings/${id}`);
            const next = data.data ?? data;
            const idx = this.items.findIndex((b) => b.id === id);
            if (idx !== -1) this.items[idx] = next;
            return next;
        },
    },
});
