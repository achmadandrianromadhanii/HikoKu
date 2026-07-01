<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import { Search, CalendarDays, CreditCard, PackageCheck, ChevronLeft } from 'lucide-vue-next'

const steps = [
    {
        title: 'Pilih Produk atau Paket',
        description: 'Jelajahi katalog lalu pilih alat hiking atau paket bundling yang kamu butuhkan.',
        icon: Search,
    },
    {
        title: 'Tentukan Tanggal Rental',
        description: 'Masukkan tanggal mulai dan selesai untuk menghitung estimasi biaya sewa.',
        icon: CalendarDays,
    },
    {
        title: 'Lanjut ke Pembayaran',
        description: 'Checkout pesanan lalu selesaikan pembayaran dengan metode yang tersedia di Midtrans.',
        icon: CreditCard,
    },
    {
        title: 'Ambil dan Gunakan Alat',
        description: 'Datang ke toko, lakukan verifikasi, lalu alat siap dipakai untuk perjalananmu.',
        icon: PackageCheck,
    },
]
</script>

<template>

    <Head title="Cara Sewa" />

    <DefaultLayout>
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: 100% TIDAK DIUBAH (hidden lg:block)       -->
        <!-- ========================================================= -->
        <div class="hidden lg:block">
        <!-- [UPDATE CONTAINER] Lebar maksimal diperkecil dari max-w-6xl menjadi max-w-5xl agar grid lebih proporsional dan tidak meregang terlalu jauh -->
        <section class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-12 text-center">
                <!-- Judul Kicker & Judul Utama -->
                <p class="user-heading-kicker">Cara Sewa</p>
                <h1 class="user-page-title drop-shadow-sm">Alur rental dalam 4 langkah</h1>
                <p class="user-page-desc mx-auto max-w-2xl text-surface-500/90">
                    Proses dibuat sederhana agar kamu bisa fokus ke persiapan trip, bukan ribet urusan sewa.
                </p>
            </div>

            <!-- [UPDATE GRID] Menyesuaikan gap agar pas dan seragam -->
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <!-- [UPDATE KARTU LANGKAH] Penambahan transisi halus, bayangan melayang (hover:shadow-2xl), dan translasi sumbu Y -->
                <div v-for="(step, index) in steps" :key="step.title"
                    class="group rounded-[2rem] border border-slate-100/50 bg-white/70 p-6 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl hover:shadow-cyan-500/10 hover:border-cyan-100">
                    
                    <!-- [UPDATE IKON HD] Menghapus kotak background kaku, menggantinya dengan gaya Naked Icon bersinar (Drop-shadow Glow) -->
                    <div class="mb-5 inline-flex transform transition-transform duration-300 group-hover:scale-110">
                        <component :is="step.icon" class="h-10 w-10 text-cyan-500 drop-shadow-[0_0_8px_rgba(6,182,212,0.4)]" />
                    </div>

                    <div>
                        <!-- Tipografi langkah yang dipertajam dengan Gradient & Spacing ekstra -->
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] bg-gradient-to-r from-cyan-500 to-blue-500 bg-clip-text text-transparent">
                            Langkah {{ index + 1 }}
                        </p>

                        <!-- Judul Langkah -->
                        <h2 class="mt-2 text-lg font-bold text-slate-800 transition-colors group-hover:text-cyan-900">
                            {{ step.title }}
                        </h2>

                        <!-- Deskripsi Langkah -->
                        <p class="mt-2 text-[13px] leading-relaxed text-slate-500">
                            {{ step.description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- [UPDATE BANNER CTA] Mengganti panel gradasi kaku menjadi gaya Glassmorphism Elegan kelas eksklusif -->
            <div class="relative mt-16 overflow-hidden rounded-[2.5rem] bg-[#031632] px-8 py-12 shadow-2xl border border-slate-800/50">
                <!-- Latar Belakang Lingkaran Abstrak -->
                <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-cyan-500/20 blur-[80px]"></div>
                <div class="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-blue-500/20 blur-[80px]"></div>

                <div class="relative z-10 text-center sm:text-left sm:flex sm:items-center sm:justify-between">
                    <div class="max-w-xl">
                        <h2 class="text-3xl font-extrabold text-white drop-shadow-md">Siap mulai rental?</h2>
                        <p class="mt-3 text-[14px] leading-relaxed text-white/70">
                            Lihat katalog produk dan pilih alat yang paling cocok untuk kebutuhan pendakian kamu.
                        </p>
                    </div>

                    <!-- [UPDATE TOMBOL] Tombol lebih tinggi (h-14), melengkung sempurna (rounded-full), dengan shadow memancar -->
                    <div class="mt-8 sm:mt-0 sm:shrink-0 flex justify-center">
                        <Link :href="route('catalog.index')"
                            class="inline-flex h-14 items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-blue-500 px-8 text-[14px] font-bold tracking-wide text-white shadow-[0_0_15px_rgba(34,211,238,0.4)] transition-all duration-300 hover:scale-105 hover:shadow-[0_0_25px_rgba(34,211,238,0.6)]">
                            Lihat Katalog Sekarang
                        </Link>
                    </div>
                </div>
            </div>
        </section>
        </div> <!-- End of DESKTOP VIEW -->


        <!-- ========================================================= -->
        <!-- [MOBILE VIEW]: TAMPILAN BARU KHUSUS MOBILE (block lg:hidden)-->
        <!-- ========================================================= -->
        <div class="block lg:hidden bg-slate-50 min-h-screen relative pb-10">
            
            <!-- Mobile Header -->
            <div class="bg-white px-4 py-4 shadow-sm border-b border-slate-100 flex items-center gap-3 sticky top-14 z-20">
                <button type="button" @click="() => window.history.length > 1 ? window.history.back() : router.visit(route('home'))" class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-600 active:scale-95">
                    <ChevronLeft class="h-5 w-5" />
                </button>
                <div>
                    <h1 class="text-sm font-extrabold text-slate-800">Cara Sewa</h1>
                    <p class="text-[10px] text-slate-500 mt-0.5">Panduan rental alat</p>
                </div>
            </div>

            <div class="px-4 py-5 space-y-4">
                
                <div v-for="(step, index) in steps" :key="'mobile-'+step.title" class="flex gap-4 rounded-3xl bg-white p-5 shadow-sm border border-slate-100">
                    <div class="flex-shrink-0">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-600">
                            <component :is="step.icon" class="h-6 w-6" />
                        </div>
                    </div>
                    <div>
                        <span class="text-[9px] font-extrabold text-cyan-500 uppercase tracking-widest">Langkah {{ index + 1 }}</span>
                        <h2 class="mt-0.5 text-sm font-bold text-slate-800">{{ step.title }}</h2>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-500">{{ step.description }}</p>
                    </div>
                </div>

                <!-- Mobile CTA Banner -->
                <div class="mt-8 rounded-3xl bg-slate-900 p-6 shadow-lg border border-slate-800 relative overflow-hidden">
                    <!-- Decor -->
                    <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-cyan-500/20 blur-2xl"></div>
                    
                    <div class="relative z-10 text-center">
                        <h2 class="text-base font-extrabold text-white">Siap mulai rental?</h2>
                        <p class="mt-2 text-[11px] leading-relaxed text-white/70 px-4">
                            Lihat katalog produk dan pilih alat yang cocok.
                        </p>
                        
                        <Link :href="route('catalog.index')" class="mt-6 flex w-full items-center justify-center rounded-full bg-cyan-500 py-3.5 text-xs font-bold text-white shadow-md active:scale-95 transition-transform">
                            Lihat Katalog
                        </Link>
                    </div>
                </div>

            </div>
        </div>

    </DefaultLayout>
</template>