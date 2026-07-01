<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

const props = defineProps({
    email: {
        type: String,
        default: '',
    },
    token: {
        type: String,
        required: true,
    },
})

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation')
        },
    })
}
</script>

<template>

    <Head title="Reset Password" />

    <AuthLayout title="Reset Password">
        <div class="mx-auto max-w-md">
            <div class="mb-8">
                <p class="text-sm font-semibold text-primary-600">Password baru</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-900">Reset Password</h1>
                <p class="mt-2 text-sm leading-6 text-gray-500">
                    Masukkan password baru untuk akun kamu.
                </p>
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-900">Email</label>
                    <input v-model="form.email" type="email" autocomplete="username"
                        class="h-12 w-full rounded-xl border border-gray-200 px-4 text-sm outline-none transition focus:border-primary-300 focus:ring-2 focus:ring-primary-100"
                        placeholder="Masukan Email" />
                    <p v-if="form.errors.email" class="mt-2 text-xs text-red-500">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-900">Password Baru</label>
                    <input v-model="form.password" type="password" autocomplete="new-password"
                        class="h-12 w-full rounded-xl border border-gray-200 px-4 text-sm outline-none transition focus:border-primary-300 focus:ring-2 focus:ring-primary-100"
                        placeholder="Masukkan password baru" />
                    <p v-if="form.errors.password" class="mt-2 text-xs text-red-500">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-900">Konfirmasi Password</label>
                    <input v-model="form.password_confirmation" type="password" autocomplete="new-password"
                        class="h-12 w-full rounded-xl border border-gray-200 px-4 text-sm outline-none transition focus:border-primary-300 focus:ring-2 focus:ring-primary-100"
                        placeholder="Ulangi password baru" />
                    <p v-if="form.errors.password_confirmation" class="mt-2 text-xs text-red-500">
                        {{ form.errors.password_confirmation }}
                    </p>
                </div>

                <button type="submit"
                    class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-primary-600 px-5 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Password Baru' }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-500">
                <Link :href="route('login')" class="font-semibold text-primary-600 hover:text-primary-700">
                    Kembali ke login
                </Link>
            </p>
        </div>
    </AuthLayout>
</template>