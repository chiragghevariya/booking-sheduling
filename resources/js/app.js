import './bootstrap';

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { useThemeStore } from './stores/theme';

const app = createApp(App);

const pinia = createPinia();
app.use(pinia);
app.use(router);

// Apply the saved light/dark theme before mount (avoids a flash of the wrong theme).
useThemeStore(pinia).init();

app.mount('#app');
