<script setup>
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

const page = usePage()

const resendForm = useForm({})

const resendVerification = () => {
    resendForm.post(route('verification.send'))
}

const logout = () => {
    router.post(route('logout'))
}
</script>

<template>

    <Head title="Verifikasi Email" />

    <AuthLayout title="Verifikasi Email">
        <div class="mx-auto max-w-md">
            <div class="mb-8">
                <p class="text-sm font-semibold text-primary-600">Verifikasi akun</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-900">Cek Email Kamu</h1>
                <p class="mt-2 text-sm leading-6 text-gray-500">
                    Sebelum melanjutkan, silakan verifikasi email melalui tautan yang sudah kami kirim.
                </p>
            </div>

            <div v-if="page.props.status === 'verification-link-sent'"
                class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                Link verifikasi baru sudah dikirim ke email kamu.
            </div>

            <div class="space-y-4">
                <button type="button"
                    class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-primary-600 px-5 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="resendForm.processing" @click="resendVerification">
                    {{ resendForm.processing ? 'Mengirim ulang...' : 'Kirim Ulang Email Verifikasi' }}
                </button>

                <button type="button"
                    class="inline-flex h-12 w-full items-center justify-center rounded-xl border border-gray-200 px-5 text-sm font-semibold text-gray-700 transition hover:border-primary-200 hover:text-primary-600"
                    @click="logout">
                    Keluar
                </button>
            </div>

            <p class="mt-6 text-center text-sm text-gray-500">
                Salah email?
                <Link :href="route('profile.edit')" class="font-semibold text-primary-600 hover:text-primary-700">
                    Ubah profil
                </Link>
            </p>
        </div>
    </AuthLayout>
</template>