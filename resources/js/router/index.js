import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const Login = () => import('../pages/auth/Login.vue');
const Register = () => import('../pages/auth/Register.vue');
const Dashboard = () => import('../pages/dashboard/Dashboard.vue');
const BookLanding = () => import('../pages/customer/BookLanding.vue');
const MyBookings = () => import('../pages/customer/MyBookings.vue');
const Availability = () => import('../pages/provider/Availability.vue');
const ProviderBookings = () => import('../pages/provider/Bookings.vue');
const ProviderServices = () => import('../pages/provider/Services.vue');
const NotFound = () => import('../pages/NotFound.vue');

// `requiresAuth` gates a route behind a signed-in user.
// `roles` (array) restricts to the listed roles.
// `guestOnly` redirects authed users away (login/register pages).
const routes = [
    { path: '/', redirect: () => '/login', meta: { guestOnly: true } },

    { path: '/login', name: 'login', component: Login, meta: { guestOnly: true } },
    { path: '/register', name: 'register', component: Register, meta: { guestOnly: true } },

    {
        path: '/dashboard',
        name: 'dashboard',
        component: Dashboard,
        meta: { requiresAuth: true, roles: ['provider', 'admin'], title: 'Dashboard' },
    },
    {
        path: '/book',
        name: 'book',
        component: BookLanding,
        meta: { requiresAuth: true, roles: ['customer'], title: 'Book a service' },
    },
    {
        path: '/my-bookings',
        name: 'my-bookings',
        component: MyBookings,
        meta: { requiresAuth: true, roles: ['customer'], title: 'My bookings' },
    },
    {
        path: '/availability',
        name: 'availability',
        component: Availability,
        meta: { requiresAuth: true, roles: ['provider', 'admin'], title: 'Availability' },
    },
    {
        path: '/bookings',
        name: 'provider-bookings',
        component: ProviderBookings,
        meta: { requiresAuth: true, roles: ['provider', 'admin'], title: 'Bookings' },
    },
    {
        path: '/services',
        name: 'provider-services',
        component: ProviderServices,
        meta: { requiresAuth: true, roles: ['provider', 'admin'], title: 'Services' },
    },

    { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFound },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Global guard: lazy-loads /me on first navigation, then enforces auth + role rules.
router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.initialized) {
        await auth.fetchMe();
    }

    if (to.meta.guestOnly && auth.isAuthenticated) {
        return auth.homeRoute;
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { path: '/login', query: { redirect: to.fullPath } };
    }

    if (to.meta.roles && auth.isAuthenticated && !to.meta.roles.includes(auth.role)) {
        // Role mismatch — bounce to the correct landing page.
        return auth.homeRoute;
    }

    return true;
});

export default router;
