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
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const errors = ref({});

async function submit() {
    errors.value = {};
    try {
        await auth.register(form);
        toast.success('Account created — welcome aboard!');
        router.push(auth.homeRoute);
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = mapErrors(e.response.data.errors);
        } else {
            toast.error('Could not create your account. Please try again.');
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
            <h1 class="text-2xl font-semibold text-ink">Create your account</h1>
            <p class="mt-1 text-sm text-ink-soft">Book sessions with providers in minutes.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <BaseInput
                v-model="form.name"
                label="Full name"
                autocomplete="name"
                placeholder="Alex Johnson"
                :error="errors.name"
                required
            />
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
                autocomplete="new-password"
                placeholder="At least 8 characters"
                hint="Use letters and numbers, 8+ characters."
                :error="errors.password"
                required
            />
            <BaseInput
                v-model="form.password_confirmation"
                label="Confirm password"
                type="password"
                autocomplete="new-password"
                placeholder="Repeat your password"
                required
            />

            <BaseButton type="submit" :loading="auth.loading" block>Create account</BaseButton>
        </form>

        <p class="mt-6 text-sm text-ink-soft text-center">
            Already have an account?
            <RouterLink to="/login" class="font-medium text-brand-600 hover:text-brand-700">Sign in</RouterLink>
        </p>
    </AuthLayout>
</template>
