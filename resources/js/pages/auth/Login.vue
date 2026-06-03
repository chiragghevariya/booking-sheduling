<script setup>
import { reactive, ref } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import AuthLayout from '../../layouts/AuthLayout.vue';
import BaseInput from '../../components/base/BaseInput.vue';
import BaseButton from '../../components/base/BaseButton.vue';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';

const auth = useAuthStore();
const toast = useToastStore();
const router = useRouter();

const form = reactive({
    email: '',
    password: '',
    remember: false,
});

const errors = ref({});

async function submit() {
    errors.value = {};
    try {
        await auth.login(form);
        toast.success(`Welcome back, ${auth.user.name}.`);
        router.push(auth.homeRoute);
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = mapErrors(e.response.data.errors);
        } else {
            toast.error('Could not sign you in. Please try again.');
        }
    }
}

function mapErrors(payload) {
    const out = {};
    for (const key in payload) {
        out[key] = Array.isArray(payload[key]) ? payload[key][0] : String(payload[key]);
    }
    return out;
}
</script>

<template>
    <AuthLayout>
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-ink">Sign in</h1>
            <p class="mt-1 text-sm text-ink-soft">Welcome back. Enter your details to continue.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <BaseInput
                v-model="form.email"
                label="Email"
                type="email"
                autocomplete="email"
                placeholder="you@example.com"
                :error="errors.email"
                required
            />
            <BaseInput
                v-model="form.password"
                label="Password"
                type="password"
                autocomplete="current-password"
                placeholder="••••••••"
                :error="errors.password"
                required
            />

            <label class="flex items-center gap-2 text-sm text-ink-muted select-none">
                <input
                    type="checkbox"
                    v-model="form.remember"
                    class="rounded border-line text-brand-600 focus:ring-brand-500/30"
                />
                Remember me on this device
            </label>

            <BaseButton type="submit" :loading="auth.loading" block>Sign in</BaseButton>
        </form>

        <p class="mt-6 text-sm text-ink-soft text-center">
            New here?
            <RouterLink to="/register" class="font-medium text-brand-600 hover:text-brand-700">Create an account</RouterLink>
        </p>
    </AuthLayout>
</template>
