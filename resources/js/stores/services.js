import { defineStore } from 'pinia';
import api, { ensureCsrf } from '../lib/api';

export const useServicesStore = defineStore('services', {
    state: () => ({
        items: [],
        loading: false,
        saving: false,
    }),
    actions: {
        async fetch() {
            this.loading = true;
            try {
                const { data } = await api.get('/api/provider/services');
                this.items = data.data ?? data;
            } finally {
                this.loading = false;
            }
        },

        async create(payload) {
            await ensureCsrf();
            this.saving = true;
            try {
                const { data } = await api.post('/api/provider/services', payload);
                this.items.push(data.data ?? data);
                this.items.sort((a, b) => a.name.localeCompare(b.name));
                return data.data ?? data;
            } finally {
                this.saving = false;
            }
        },

        async update(id, payload) {
            await ensureCsrf();
            this.saving = true;
            try {
                const { data } = await api.put(`/api/provider/services/${id}`, payload);
                const next = data.data ?? data;
                const i = this.items.findIndex((s) => s.id === id);
                if (i !== -1) this.items[i] = next;
                return next;
            } finally {
                this.saving = false;
            }
        },

        async remove(id) {
            await ensureCsrf();
            await api.delete(`/api/provider/services/${id}`);
            this.items = this.items.filter((s) => s.id !== id);
        },
    },
});
