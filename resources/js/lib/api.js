import axios from 'axios';

// Single shared Axios client. Sanctum SPA auth uses cookies, so
// `withCredentials` is required on every API call.
const api = axios.create({
    baseURL: '/',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

/**
 * Prime Sanctum's CSRF cookie before any state-changing request from the SPA.
 * Idempotent — safe to call repeatedly.
 */
export async function ensureCsrf() {
    await api.get('/sanctum/csrf-cookie');
}

export default api;
