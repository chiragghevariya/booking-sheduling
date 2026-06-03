import { defineStore } from 'pinia';
import api, { ensureCsrf } from '../lib/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        initialized: false, // true once we've attempted to load /me at boot
        loading: false,
    }),
    getters: {
        isAuthenticated: (state) => state.user !== null,
        role: (state) => state.user?.role ?? null,
        isCustomer: (state) => state.user?.role === 'customer',
        isProvider: (state) => state.user?.role === 'provider',
        isAdmin: (state) => state.user?.role === 'admin',
        homeRoute() {
            // Customers head to the booking flow; staff land on the dashboard.
            if (!this.user) return '/login';
            return this.user.role === 'customer' ? '/book' : '/dashboard';
        },
    },
    actions: {
        async fetchMe() {
            try {
                const { data } = await api.get('/api/auth/me');
                this.user = data.data ?? data;
            } catch {
                this.user = null;
            } finally {
                this.initialized = true;
            }
            return this.user;
        },

        async login(payload) {
            this.loading = true;
            try {
                await ensureCsrf();
                const { data } = await api.post('/api/auth/login', payload);
                this.user = data.data;
                return this.user;
            } finally {
                this.loading = false;
            }
        },

        async register(payload) {
            this.loading = true;
            try {
                await ensureCsrf();
                const { data } = await api.post('/api/auth/register', payload);
                this.user = data.data;
                return this.user;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                await api.post('/api/auth/logout');
            } finally {
                this.user = null;
            }
        },
    },
});
