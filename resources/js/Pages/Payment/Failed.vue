<script setup lang="ts">
    import DefaultLayout from '@/Layouts/DefaultLayout.vue';
    import { Link } from '@inertiajs/vue3';
    import { XCircle, RefreshCcw, PackageCheck } from 'lucide-vue-next';
</script>

<template>
    <DefaultLayout>
        <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
            <div
                class="mx-auto max-w-3xl rounded-3xl border border-surface-200 bg-white p-6 shadow-soft sm:p-8"
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-red-600"
                >
                    <XCircle class="h-8 w-8" />
                </div>

                <div class="mt-6 text-center">
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-red-500">
                        Payment Failed
                    </p>

                    <h1 class="mt-2 text-3xl font-bold text-surface-900">
                        Pembayaran Belum Berhasil
                    </h1>

                    <p class="mx-auto mt-3 max-w-2xl text-sm leading-7 text-surface-600">
                        Pembayaran belum berhasil diproses. Kamu masih bisa mencoba lagi dari detail
                        rental atau kembali melihat riwayat rental untuk melanjutkan proses.
                    </p>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl border border-surface-200 bg-surface-50 p-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-primary-50 text-primary-600"
                            >
                                <RefreshCcw class="h-5 w-5" />
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-surface-900">
                                    Coba Lagi Pembayaran
                                </p>
                                <p class="mt-1 text-xs leading-6 text-surface-600">
                                    Lanjutkan ulang pembayaran dari detail rental kamu.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-surface-200 bg-surface-50 p-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-primary-50 text-primary-600"
                            >
                                <PackageCheck class="h-5 w-5" />
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-surface-900">
                                    Cek Riwayat Rental
                                </p>
                                <p class="mt-1 text-xs leading-6 text-surface-600">
                                    Pantau status rental dan detail transaksi dari akun kamu.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <Link :href="route('my-rentals.index')" class="user-btn-primary h-11 px-5">
                        Kembali ke Riwayat Rental
                    </Link>

                    <Link :href="route('catalog.index')" class="user-btn-secondary h-11 px-5">
                        Kembali ke Katalog
                    </Link>
                </div>
            </div>
        </section>
    </DefaultLayout>
</template>
