<script setup>
    import { Head, Link, router } from '@inertiajs/vue3';
    import { ChevronLeft } from 'lucide-vue-next';
    import DefaultLayout from '@/Layouts/DefaultLayout.vue';
</script>

<template>
    <Head title="Syarat & Ketentuan" />
    <DefaultLayout>
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: 100% TIDAK DIUBAH (hidden lg:block)       -->
        <!-- ========================================================= -->
        <div class="block">
            <div class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-100 sm:p-12">
                    <h1 class="mb-8 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                        Syarat & Ketentuan
                    </h1>

                    <div
                        class="prose prose-slate max-w-none prose-headings:text-slate-900 prose-a:text-cyan-700 hover:prose-a:text-cyan-800"
                    >
                        <p class="lead">
                            Selamat datang di Hiko. Dengan mengakses atau menggunakan layanan kami,
                            Anda menyetujui untuk terikat oleh syarat dan ketentuan berikut ini.
                        </p>

                        <h3>1. Penggunaan Layanan</h3>
                        <p>
                            Anda setuju untuk menggunakan layanan kami hanya untuk tujuan yang sah
                            dan dengan cara yang tidak melanggar hak atau membatasi penggunaan orang
                            lain terhadap layanan ini.
                        </p>

                        <h3>2. Akun Pengguna</h3>
                        <p>
                            Anda bertanggung jawab untuk menjaga kerahasiaan kredensial akun Anda
                            dan atas semua aktivitas yang terjadi di bawah akun Anda.
                        </p>

                        <h3>3. Perubahan Ketentuan</h3>
                        <p>
                            Kami berhak untuk mengubah atau mengganti syarat dan ketentuan ini kapan
                            saja. Perubahan material akan diberitahukan kepada Anda melalui
                            pemberitahuan di aplikasi kami.
                        </p>
                    </div>

                    <div class="mt-12 pt-8 border-t border-slate-100">
                        <Link prefetch
                            :href="route('home')"
                            class="inline-flex items-center text-sm font-medium text-cyan-700 transition hover:text-cyan-800"
                        >
                            <svg
                                class="mr-2 h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"
                                />
                            </svg>
                            Kembali ke Beranda
                        </Link>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of DESKTOP VIEW -->

        <!-- ========================================================= -->
    </DefaultLayout>
</template>
