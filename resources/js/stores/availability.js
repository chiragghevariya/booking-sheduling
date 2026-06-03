import { defineStore } from 'pinia';
import api, { ensureCsrf } from '../lib/api';

export const useAvailabilityStore = defineStore('availability', {
    state: () => ({
        weekly: [],         // [{ id, day_of_week, start_time, end_time, is_active }]
        exceptions: [],     // [{ id, date, start_time, end_time, type, reason }]
        loading: false,
        saving: false,
    }),
    actions: {
        async fetchAll() {
            this.loading = true;
            try {
                const [w, e] = await Promise.all([
                    api.get('/api/availability'),
                    api.get('/api/availability-exceptions'),
                ]);
                this.weekly = w.data.data ?? w.data;
                this.exceptions = e.data.data ?? e.data;
            } finally {
                this.loading = false;
            }
        },

        async createWindow(payload) {
            await ensureCsrf();
            this.saving = true;
            try {
                const { data } = await api.post('/api/availability', payload);
                this.weekly.push(data.data ?? data);
            } finally {
                this.saving = false;
            }
        },

        async updateWindow(id, payload) {
            await ensureCsrf();
            this.saving = true;
            try {
                const { data } = await api.put(`/api/availability/${id}`, payload);
                const next = data.data ?? data;
                const idx = this.weekly.findIndex((w) => w.id === id);
                if (idx !== -1) this.weekly[idx] = next;
            } finally {
                this.saving = false;
            }
        },

        async deleteWindow(id) {
            await ensureCsrf();
            await api.delete(`/api/availability/${id}`);
            this.weekly = this.weekly.filter((w) => w.id !== id);
        },

        async createException(payload) {
            await ensureCsrf();
            this.saving = true;
            try {
                const { data } = await api.post('/api/availability-exceptions', payload);
                this.exceptions.push(data.data ?? data);
                this.exceptions.sort((a, b) => a.date.localeCompare(b.date));
            } finally {
                this.saving = false;
            }
        },

        async deleteException(id) {
            await ensureCsrf();
            await api.delete(`/api/availability-exceptions/${id}`);
            this.exceptions = this.exceptions.filter((e) => e.id !== id);
        },
    },
});
