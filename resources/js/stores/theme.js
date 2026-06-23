import { defineStore } from 'pinia';

// Light/dark theme with a user toggle. Default is LIGHT (current look); the
// choice is persisted in localStorage so it survives reloads. A `dark` class
// on <html> flips the CSS variables defined in resources/css/app.css.

const STORAGE_KEY = 'theme';

export const useThemeStore = defineStore('theme', {
    state: () => ({
        mode: 'light', // 'light' | 'dark'
    }),
    getters: {
        isDark: (state) => state.mode === 'dark',
    },
    actions: {
        apply() {
            document.documentElement.classList.toggle('dark', this.mode === 'dark');
        },
        init() {
            const saved = localStorage.getItem(STORAGE_KEY);
            this.mode = saved === 'dark' ? 'dark' : 'light';
            this.apply();
        },
        setMode(mode) {
            this.mode = mode === 'dark' ? 'dark' : 'light';
            localStorage.setItem(STORAGE_KEY, this.mode);
            this.apply();
        },
        toggle() {
            this.setMode(this.mode === 'dark' ? 'light' : 'dark');
        },
    },
});
