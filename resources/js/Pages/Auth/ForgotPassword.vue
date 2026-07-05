<script setup>
    import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
    import AuthLayout from '@/Layouts/AuthLayout.vue';

    const page = usePage();

    const form = useForm({
        email: '',
    });

    const submit = () => {
        form.post(route('password.email'));
    };
</script>

<template>
    <Head title="Lupa Password" />

    <AuthLayout title="Lupa Password">
        <div class="mx-auto max-w-md">
            <div class="mb-8">
                <p class="text-sm font-semibold text-primary-600">Reset password</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-900">Lupa Password</h1>
                <p class="mt-2 text-sm leading-6 text-gray-500">
                    Masukkan email akun kamu. Kami akan kirim tautan reset password.
                </p>
            </div>

            <div
                v-if="page.props.status"
                class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
            >
                {{ page.props.status }}
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-900">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        autocomplete="username"
                        class="h-12 w-full rounded-xl border border-gray-200 px-4 text-sm outline-none transition focus:border-primary-300 focus:ring-2 focus:ring-primary-100"
                        placeholder="Masukan Email"
                    />
                    <p v-if="form.errors.email" class="mt-2 text-xs text-red-500">
                        {{ form.errors.email }}
                    </p>
                </div>

                <button
                    type="submit"
                    class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-primary-600 px-5 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Mengirim...' : 'Kirim Link Reset' }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-500">
                Sudah ingat password?
                <Link
                    :href="route('login')"
                    class="font-semibold text-primary-600 hover:text-primary-700"
                >
                    Kembali ke login
                </Link>
            </p>
        </div>
    </AuthLayout>
</template>
