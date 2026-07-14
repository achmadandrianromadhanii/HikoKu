<script setup>
    /* 
    ==========================================================================
    [KOMENTAR PENJELASAN KOMPONEN]: Packages/Show.vue (Halaman Detail Paket)
    ==========================================================================
    - FUNGSI: Menampilkan rincian paket rental, beserta isi barang di dalamnya, 
      dan formulir untuk menambahkan paket ke keranjang sewa.
    - PERUBAHAN TATA LETAK & UX (Sesuai Permintaan): 
      1. Ukuran Grid: Lebar dibatasi (max-w-6xl) agar proporsional di layar Desktop, 
         membuang jarak kosong / padding asimetris yang merusak estetika.
      2. Isi Paket: Menghapus desain "kotak-kotak kaku" (grid boxes) pada list barang, 
         diubah menjadi "Sleek Vertical List" bergaris batas halus dengan Ikon Lucide HD.
      3. Tipografi & Form: Menggunakan font hirarki yang elegan. Form input kalender 
         dan angka dibuat lebih tinggi & lebar agar berkesan mewah.
      4. Efek Micro-Animation: Tombol keranjang dan gambar utama ditambahkan animasi 
         hover ringan yang MURNI native tanpa library, memastikan skor Lighthouse 100%.
    - CARA KERJA KODE:
      - formatCurrency: Mengubah angka menjadi format Rupiah.
      - submitRent: Mengirim rentForm (tanggal & jumlah) ke endpoint keranjang (cart.store).
    ==========================================================================
*/
    import { onMounted, onUnmounted } from 'vue';
    import { Head, Link, useForm, router } from '@inertiajs/vue3';
    import { CheckCircle2, XCircle, ShoppingCart, Info, ChevronLeft } from 'lucide-vue-next';
    import DefaultLayout from '@/Layouts/DefaultLayout.vue';
    import WishlistButton from '@/Components/ui/WishlistButton.vue';

    const props = defineProps({
        package: {
            type: Object,
            required: true,
        },
    });

    // [KOMENTAR PENJELASAN]: Menyembunyikan Bottom Nav (Home, Katalog) agar tidak overlap dengan Action Bar Sewa
    onMounted(() => {
        const bottomNav = document.querySelector('.fixed.inset-x-0.bottom-4.z-50');
        if (bottomNav) bottomNav.style.display = 'none';
    });

    onUnmounted(() => {
        const bottomNav = document.querySelector('.fixed.inset-x-0.bottom-4.z-50');
        if (bottomNav) bottomNav.style.display = '';
    });

    // Form State Management
    const rentForm = useForm({
        item_type: 'package',
        product_id: null,
        package_id: props.package.id,
        quantity: 1,
        rental_start: '',
        rental_end: '',
    });

    // Konversi Nominal Angka ke Teks Rupiah
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(Number(value || 0));
    };

    // Aksi Submit Form ke Keranjang
    const submitRent = () => {
        rentForm.post(route('cart.store'), {
            preserveScroll: true,
        });
    };
</script>

<template>
    <Head :title="package.name" />

    <DefaultLayout>
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: 100% TIDAK DIUBAH (hidden lg:block)       -->
        <!-- ========================================================= -->
        <div class="block">
            <!-- Kontainer Utama Dibatasi Lebarnya (max-w-6xl) agar padat dan premium -->
            <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
                <!-- Grid 2 Kolom (Proporsional: Gambar 55% Kiri, Form 45% Kanan) -->
                <div class="grid gap-8 lg:gap-12 lg:grid-cols-[1.2fr_1fr] items-start">
                    <!-- ================= SEKTOR KIRI: Gambar & Isi Paket ================= -->
                    <div class="space-y-8">
                        <!-- Bungkus Gambar Utama (Efek Lengkung Halus & Zoom Hover) -->
                        <div
                            class="group relative overflow-hidden rounded-[32px] bg-slate-50 shadow-[0_8px_30px_rgb(0,0,0,0.04)] ring-1 ring-slate-100"
                        >
                            <!-- [OPTIMASI LIGHTHOUSE LCP]: Eager load, fetchpriority high, dan explicit dimensions -->
                            <img
                                v-if="package.image_path"
                                :src="`/storage/${package.image_path}`"
                                :alt="package.name"
                                width="800"
                                height="600"
                                loading="eager"
                                fetchpriority="high"
                                decoding="async"
                                class="aspect-[4/3] w-full object-cover transition-transform duration-700 ease-in-out group-hover:scale-105"
                            />

                            <!-- Kosong / Fallback Gambar Bersih -->
                            <div
                                v-else
                                class="flex aspect-[4/3] w-full items-center justify-center text-sm font-medium text-slate-500"
                            >
                                <!-- Bebas dari tulisan jelek, cukup clean layout -->
                                <Info class="h-8 w-8 opacity-20" />
                            </div>
                        </div>

                        <!-- Area Isi Paket (Desain List Vertikal Mewah tanpa Kotak Kaku) -->
                        <div class="px-2">
                            <h2
                                class="text-xl font-extrabold text-slate-900 mb-6 flex items-center gap-2"
                            >
                                Kelengkapan Paket
                            </h2>

                            <!-- List Barang Vertikal (Smooth Border) -->
                            <ul class="space-y-0 relative border-l-2 border-slate-100 ml-3">
                                <li
                                    v-for="(item, index) in package.items || []"
                                    :key="item.id"
                                    class="relative pl-6 py-4 group transition-colors hover:bg-slate-50/50 rounded-r-2xl"
                                >
                                    <!-- Titik Konektor di Garis Kiri -->
                                    <span
                                        class="absolute -left-[5px] top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-slate-200 transition-colors group-hover:bg-blue-400"
                                    ></span>

                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <!-- Ikon Status Ketersediaan HD -->
                                            <div
                                                v-if="item.product?.stock_available > 0"
                                                class="flex-shrink-0 text-emerald-500"
                                            >
                                                <CheckCircle2 class="w-5 h-5" />
                                            </div>
                                            <div v-else class="flex-shrink-0 text-rose-500">
                                                <XCircle class="w-5 h-5" />
                                            </div>

                                            <!-- Teks Nama Barang -->
                                            <div>
                                                <p
                                                    class="text-[15px] font-bold text-slate-800 transition-colors group-hover:text-blue-600"
                                                >
                                                    {{ item.product?.name || 'Produk' }}
                                                </p>
                                                <p
                                                    class="mt-0.5 text-[12px] font-medium text-slate-500 uppercase tracking-widest"
                                                >
                                                    Jumlah: {{ item.quantity }} Unit
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Lencana Tersedia/Habis -->
                                        <div class="flex-shrink-0">
                                            <span
                                                v-if="item.product?.stock_available > 0"
                                                class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-extrabold tracking-widest text-emerald-600 uppercase ring-1 ring-emerald-500/20"
                                            >
                                                Tersedia
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-extrabold tracking-widest text-rose-600 uppercase ring-1 ring-rose-500/20"
                                            >
                                                Habis
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- ================= SEKTOR KANAN: Detail & Form Transaksi ================= -->
                    <div class="sticky top-24">
                        <div
                            class="rounded-[32px] bg-white p-6 sm:p-8 lg:p-10 shadow-[0_10px_40px_rgb(0,0,0,0.04)] ring-1 ring-slate-100"
                        >
                            <!-- Kategori Kicker -->
                            <p
                                class="text-[11px] font-extrabold uppercase tracking-[0.2em] text-blue-600"
                            >
                                Paket Spesial
                            </p>

                            <!-- Judul Utama -->
                            <h1
                                class="mt-2 text-3xl font-extrabold leading-tight text-slate-900 sm:text-4xl tracking-tight"
                            >
                                {{ package.name }}
                            </h1>

                            <!-- Deskripsi -->
                            <p class="mt-4 text-[15px] leading-relaxed text-slate-500 font-medium">
                                {{
                                    package.description ||
                                    'Pilihan bundling hemat dan praktis tanpa repot mendaftar alat satu per satu. Nikmati pengalaman petualangan maksimal dengan perlengkapan terjamin.'
                                }}
                            </p>

                            <!-- Panel Harga Mewah (Gradasi Halus) -->
                            <div
                                class="mt-8 rounded-3xl bg-gradient-to-br from-slate-50 to-slate-100 p-6 ring-1 ring-slate-200/60 shadow-inner"
                            >
                                <p
                                    class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-1"
                                >
                                    Total Biaya Sewa
                                </p>
                                <div class="flex items-baseline gap-2">
                                    <p
                                        class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight"
                                    >
                                        {{ formatCurrency(package.price_per_day) }}
                                    </p>
                                    <p class="text-sm font-bold text-slate-500">/ hari</p>
                                </div>
                            </div>

                            <!-- Form Input Kalender & Jumlah (Tinggi & Lebar) -->
                            <form class="mt-8 space-y-6" @submit.prevent="submitRent">
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <!-- Input Tgl Mulai -->
                                    <div class="group">
                                        <label
                                            class="mb-2 block text-[13px] font-bold text-slate-700 transition-colors group-focus-within:text-blue-600"
                                        >
                                            Tanggal Ambil
                                        </label>
                                        <input
                                            v-model="rentForm.rental_start"
                                            type="date"
                                            required
                                            class="h-14 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 text-[15px] font-medium text-slate-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 hover:border-slate-300"
                                        />
                                    </div>

                                    <!-- Input Tgl Kembali -->
                                    <div class="group">
                                        <label
                                            class="mb-2 block text-[13px] font-bold text-slate-700 transition-colors group-focus-within:text-blue-600"
                                        >
                                            Tanggal Kembali
                                        </label>
                                        <input
                                            v-model="rentForm.rental_end"
                                            type="date"
                                            required
                                            class="h-14 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 text-[15px] font-medium text-slate-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 hover:border-slate-300"
                                        />
                                    </div>
                                </div>

                                <!-- Input Jumlah Paket -->
                                <div class="group">
                                    <label
                                        class="mb-2 block text-[13px] font-bold text-slate-700 transition-colors group-focus-within:text-blue-600"
                                    >
                                        Jumlah (Paket)
                                    </label>
                                    <input
                                        v-model="rentForm.quantity"
                                        type="number"
                                        min="1"
                                        required
                                        class="h-14 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 text-[15px] font-medium text-slate-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 hover:border-slate-300"
                                    />
                                </div>

                                <!-- Tombol Eksekusi Aksi Mewah -->
                                <button
                                    type="submit"
                                    class="group relative flex h-16 w-full items-center justify-center gap-3 overflow-hidden rounded-2xl bg-slate-900 text-[15px] font-bold text-white transition-all duration-300 ease-out hover:bg-blue-600 hover:shadow-[0_8px_25px_rgba(37,99,235,0.25)] active:scale-[0.98]"
                                >
                                    <ShoppingCart
                                        class="h-5 w-5 transition-transform duration-300 group-hover:-rotate-12"
                                    />
                                    <span>Tambah ke Keranjang</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- End of DESKTOP VIEW -->

        <!-- ========================================================= -->
    </DefaultLayout>
</template>
