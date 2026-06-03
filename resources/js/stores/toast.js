import { defineStore } from 'pinia';

let nextId = 1;

export const useToastStore = defineStore('toast', {
    state: () => ({
        toasts: [],
    }),
    actions: {
        push({ message, title = '', variant = 'info', timeout = 4000 }) {
            const id = nextId++;
            this.toasts.push({ id, message, title, variant });
            if (timeout > 0) {
                setTimeout(() => this.dismiss(id), timeout);
            }
            return id;
        },
        success(message, opts = {}) {
            return this.push({ ...opts, message, variant: 'success' });
        },
        error(message, opts = {}) {
            return this.push({ ...opts, message, variant: 'danger', timeout: 6000 });
        },
        info(message, opts = {}) {
            return this.push({ ...opts, message, variant: 'info' });
        },
        dismiss(id) {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        },
    },
});
