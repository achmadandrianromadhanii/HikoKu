<script setup>
    import { Head, Link } from '@inertiajs/vue3';
    import DefaultLayout from '@/Layouts/DefaultLayout.vue';
</script>

<template>
    <Head title="Kebijakan Privasi" />
    <DefaultLayout>
        <div class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-100 sm:p-12">
                <h1 class="mb-8 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                    Kebijakan Privasi
                </h1>

                <div
                    class="prose prose-slate max-w-none prose-headings:text-slate-900 prose-a:text-cyan-700 hover:prose-a:text-cyan-800"
                >
                    <p class="lead">
                        Privasi Anda sangat penting bagi kami. Kebijakan ini menjelaskan bagaimana
                        kami mengumpulkan, menggunakan, dan melindungi informasi pribadi Anda.
                    </p>

                    <h3>1. Informasi yang Kami Kumpulkan</h3>
                    <p>
                        Kami mengumpulkan informasi yang Anda berikan langsung kepada kami saat
                        mendaftar, seperti nama, alamat email, dan informasi profil lainnya.
                    </p>

                    <h3>2. Penggunaan Informasi</h3>
                    <p>
                        Informasi Anda digunakan untuk menyediakan, memelihara, dan meningkatkan
                        layanan kami, serta untuk berkomunikasi dengan Anda mengenai pembaruan
                        keamanan dan penawaran.
                    </p>

                    <h3>3. Keamanan Data</h3>
                    <p>
                        Kami menerapkan langkah-langkah keamanan standar industri (seperti enkripsi
                        dan proteksi Cloudflare) untuk melindungi informasi pribadi Anda dari akses
                        yang tidak sah.
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
    </DefaultLayout>
</template>
