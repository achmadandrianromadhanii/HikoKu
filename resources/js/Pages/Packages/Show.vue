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
import { onMounted, onUnmounted } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { CheckCircle2, XCircle, ShoppingCart, Info, ChevronLeft } from 'lucide-vue-next'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import WishlistButton from '@/Components/ui/WishlistButton.vue'

const props = defineProps({
    package: {
        type: Object,
        required: true,
    },
})

// [KOMENTAR PENJELASAN]: Menyembunyikan Bottom Nav (Home, Katalog) agar tidak overlap dengan Action Bar Sewa
onMounted(() => {
    const bottomNav = document.querySelector('.fixed.inset-x-0.bottom-4.z-50');
    if (bottomNav) bottomNav.style.display = 'none';
})

onUnmounted(() => {
    const bottomNav = document.querySelector('.fixed.inset-x-0.bottom-4.z-50');
    if (bottomNav) bottomNav.style.display = '';
})

// Form State Management
const rentForm = useForm({
    item_type: 'package',
    product_id: null,
    package_id: props.package.id,
    quantity: 1,
    rental_start: '',
    rental_end: '',
})

// Konversi Nominal Angka ke Teks Rupiah
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0))
}

// Aksi Submit Form ke Keranjang
const submitRent = () => {
    rentForm.post(route('cart.store'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head :title="package.name" />

    <DefaultLayout>
        
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: 100% TIDAK DIUBAH (hidden lg:block)       -->
        <!-- ========================================================= -->
        <div class="hidden lg:block">
        <!-- Kontainer Utama Dibatasi Lebarnya (max-w-6xl) agar padat dan premium -->
        <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
            
            <!-- Grid 2 Kolom (Proporsional: Gambar 55% Kiri, Form 45% Kanan) -->
            <div class="grid gap-8 lg:gap-12 lg:grid-cols-[1.2fr_1fr] items-start">
                
                <!-- ================= SEKTOR KIRI: Gambar & Isi Paket ================= -->
                <div class="space-y-8">
                    <!-- Bungkus Gambar Utama (Efek Lengkung Halus & Zoom Hover) -->
                    <div class="group relative overflow-hidden rounded-[32px] bg-slate-50 shadow-[0_8px_30px_rgb(0,0,0,0.04)] ring-1 ring-slate-100">
                        <img v-if="package.image_path" :src="`/storage/${package.image_path}`" :alt="package.name"
                            class="aspect-[4/3] w-full object-cover transition-transform duration-700 ease-in-out group-hover:scale-105" />
                        
                        <!-- Kosong / Fallback Gambar Bersih -->
                        <div v-else class="flex aspect-[4/3] w-full items-center justify-center text-sm font-medium text-slate-400">
                            <!-- Bebas dari tulisan jelek, cukup clean layout -->
                            <Info class="h-8 w-8 opacity-20" />
                        </div>
                    </div>

                    <!-- Area Isi Paket (Desain List Vertikal Mewah tanpa Kotak Kaku) -->
                    <div class="px-2">
                        <h2 class="text-xl font-extrabold text-slate-900 mb-6 flex items-center gap-2">
                            Kelengkapan Paket
                        </h2>

                        <!-- List Barang Vertikal (Smooth Border) -->
                        <ul class="space-y-0 relative border-l-2 border-slate-100 ml-3">
                            <li v-for="(item, index) in package.items || []" :key="item.id"
                                class="relative pl-6 py-4 group transition-colors hover:bg-slate-50/50 rounded-r-2xl">
                                
                                <!-- Titik Konektor di Garis Kiri -->
                                <span class="absolute -left-[5px] top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-slate-200 transition-colors group-hover:bg-blue-400"></span>

                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <!-- Ikon Status Ketersediaan HD -->
                                        <div v-if="item.product?.stock_available > 0" class="flex-shrink-0 text-emerald-500">
                                            <CheckCircle2 class="w-5 h-5" />
                                        </div>
                                        <div v-else class="flex-shrink-0 text-rose-500">
                                            <XCircle class="w-5 h-5" />
                                        </div>
                                        
                                        <!-- Teks Nama Barang -->
                                        <div>
                                            <p class="text-[15px] font-bold text-slate-800 transition-colors group-hover:text-blue-600">
                                                {{ item.product?.name || 'Produk' }}
                                            </p>
                                            <p class="mt-0.5 text-[12px] font-medium text-slate-500 uppercase tracking-widest">
                                                Jumlah: {{ item.quantity }} Unit
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Lencana Tersedia/Habis -->
                                    <div class="flex-shrink-0">
                                        <span v-if="item.product?.stock_available > 0"
                                            class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-extrabold tracking-widest text-emerald-600 uppercase ring-1 ring-emerald-500/20">
                                            Tersedia
                                        </span>
                                        <span v-else
                                            class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-extrabold tracking-widest text-rose-600 uppercase ring-1 ring-rose-500/20">
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
                    <div class="rounded-[32px] bg-white p-6 sm:p-8 lg:p-10 shadow-[0_10px_40px_rgb(0,0,0,0.04)] ring-1 ring-slate-100">
                        
                        <!-- Kategori Kicker -->
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.2em] text-blue-600">
                            Paket Spesial
                        </p>

                        <!-- Judul Utama -->
                        <h1 class="mt-2 text-3xl font-extrabold leading-tight text-slate-900 sm:text-4xl tracking-tight">
                            {{ package.name }}
                        </h1>

                        <!-- Deskripsi -->
                        <p class="mt-4 text-[15px] leading-relaxed text-slate-500 font-medium">
                            {{ package.description || 'Pilihan bundling hemat dan praktis tanpa repot mendaftar alat satu per satu. Nikmati pengalaman petualangan maksimal dengan perlengkapan terjamin.' }}
                        </p>

                        <!-- Panel Harga Mewah (Gradasi Halus) -->
                        <div class="mt-8 rounded-3xl bg-gradient-to-br from-slate-50 to-slate-100 p-6 ring-1 ring-slate-200/60 shadow-inner">
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Total Biaya Sewa</p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
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
                                    <label class="mb-2 block text-[13px] font-bold text-slate-700 transition-colors group-focus-within:text-blue-600">
                                        Tanggal Ambil
                                    </label>
                                    <input v-model="rentForm.rental_start" type="date" required
                                        class="h-14 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 text-[15px] font-medium text-slate-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 hover:border-slate-300" />
                                </div>

                                <!-- Input Tgl Kembali -->
                                <div class="group">
                                    <label class="mb-2 block text-[13px] font-bold text-slate-700 transition-colors group-focus-within:text-blue-600">
                                        Tanggal Kembali
                                    </label>
                                    <input v-model="rentForm.rental_end" type="date" required
                                        class="h-14 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 text-[15px] font-medium text-slate-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 hover:border-slate-300" />
                                </div>
                            </div>

                            <!-- Input Jumlah Paket -->
                            <div class="group">
                                <label class="mb-2 block text-[13px] font-bold text-slate-700 transition-colors group-focus-within:text-blue-600">
                                    Jumlah (Paket)
                                </label>
                                <input v-model="rentForm.quantity" type="number" min="1" required
                                    class="h-14 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 text-[15px] font-medium text-slate-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 hover:border-slate-300" />
                            </div>

                            <!-- Tombol Eksekusi Aksi Mewah -->
                            <button type="submit"
                                class="group relative flex h-16 w-full items-center justify-center gap-3 overflow-hidden rounded-2xl bg-slate-900 text-[15px] font-bold text-white transition-all duration-300 ease-out hover:bg-blue-600 hover:shadow-[0_8px_25px_rgba(37,99,235,0.25)] active:scale-[0.98]">
                                <ShoppingCart class="h-5 w-5 transition-transform duration-300 group-hover:-rotate-12" />
                                <span>Tambah ke Keranjang</span>
                            </button>
                        </form>

                    </div>
                </div>
            </div>
            
        </section>
        </div> <!-- End of DESKTOP VIEW -->


        <!-- ========================================================= -->
        <!-- [MOBILE VIEW]: TAMPILAN BARU KHUSUS MOBILE (block lg:hidden)-->
        <!-- ========================================================= -->
        <div class="block lg:hidden bg-slate-50 min-h-screen relative pb-28">
            
            <!-- [KOMENTAR PENJELASAN]: Mobile Hero Image Full Width (Edge-to-Edge) -->
            <!-- Tidak ada lagi kotak margin. Foto menyentuh ujung layar untuk kesan luas & mewah -->
            <div class="relative w-full aspect-[4/3] bg-slate-900 overflow-hidden">
                <img v-if="package.image_path" :src="`/storage/${package.image_path}`" :alt="package.name" class="w-full h-full object-cover" />
                <div v-else class="flex w-full h-full items-center justify-center">
                    <Info class="h-10 w-10 text-slate-700" />
                </div>
                
                <!-- [KOMENTAR PENJELASAN]: Immersive Header (Tombol Kaca Melayang) -->
                <!-- Tombol back dan wishlist mengambang di atas foto dengan efek blur murni -->
                <div class="absolute top-0 inset-x-0 z-20 flex items-start justify-between px-4 pt-4 pb-10 bg-gradient-to-b from-black/50 to-transparent">
                    <button type="button" @click="() => window.history.length > 1 ? window.history.back() : router.visit(route('packages.index'))" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur-md shadow-sm border border-white/30 transition active:scale-90">
                        <ChevronLeft class="h-6 w-6" />
                    </button>
                    <!-- Menggunakan komponen WishlistButton dengan sedikit modifikasi class native jika diperlukan, tapi size sm cukup bagus -->
                    <div class="bg-white/20 backdrop-blur-md rounded-full shadow-sm border border-white/30 flex items-center justify-center p-1">
                        <WishlistButton :product-id="package.id" size="sm" />
                    </div>
                </div>
            </div>

            <!-- [KOMENTAR PENJELASAN]: Mobile Info Section (Floating Panel) -->
            <!-- Panel diangkat ke atas sedikit (negative margin) agar menimpa gambar bawah -->
            <div class="relative z-30 -mt-6 rounded-t-3xl bg-white px-5 py-6 shadow-[0_-4px_20px_rgba(0,0,0,0.05)] border-b border-slate-100">
                <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-cyan-600 mb-1">Paket Hemat</p>
                <h1 class="text-2xl font-black text-slate-900 leading-tight tracking-tight">{{ package.name }}</h1>
                <p class="mt-2 text-[13px] leading-relaxed text-slate-500">{{ package.description || 'Pilihan bundling hemat dan praktis tanpa repot mendaftar alat satu per satu.' }}</p>
                
                <!-- Harga Sewa Mewah -->
                <div class="mt-5 flex items-center justify-between rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 p-4 border border-slate-100/50 shadow-inner">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Harga Sewa</p>
                    <div class="flex items-baseline gap-1">
                        <p class="text-2xl font-black text-cyan-600">{{ formatCurrency(package.price_per_day) }}</p>
                        <p class="text-xs font-bold text-slate-400">/hari</p>
                    </div>
                </div>
            </div>

            <!-- [KOMENTAR PENJELASAN]: Mobile Form (Dates & Quantity) - Desain Ringkas (Compact) -->
            <div class="bg-white px-5 py-6 shadow-sm border-b border-slate-100">
                <h3 class="mb-5 text-[14px] font-extrabold text-slate-900 flex items-center gap-2">
                    <ShoppingCart class="h-4 w-4 text-cyan-500" /> Atur Penyewaan
                </h3>
                <form @submit.prevent="submitRent" class="space-y-5" id="mobileRentForm">
                    <!-- Grid Tanggal Bersebelahan -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Tgl Mulai</label>
                            <input v-model="rentForm.rental_start" type="date" required class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-[13px] font-bold text-slate-800 outline-none transition-all focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Tgl Selesai</label>
                            <input v-model="rentForm.rental_end" type="date" required class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-[13px] font-bold text-slate-800 outline-none transition-all focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" />
                        </div>
                    </div>
                    
                    <!-- [KOMENTAR PENJELASAN]: Stepper Jumlah Paket (Desain Pil/Kapsul Menyatu) -->
                    <div>
                        <label class="mb-2 block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Jumlah Paket</label>
                        <div class="inline-flex h-12 w-full sm:w-[200px] items-center justify-between rounded-full border border-slate-200 bg-slate-50 p-1 shadow-inner">
                            <button type="button" class="flex h-10 w-12 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm border border-slate-100 active:scale-95 transition-all" @click="rentForm.quantity > 1 ? rentForm.quantity-- : null">-</button>
                            <input v-model="rentForm.quantity" type="number" min="1" required class="h-full w-16 border-none bg-transparent px-2 text-center text-[15px] font-black text-slate-900 outline-none focus:ring-0" />
                            <button type="button" class="flex h-10 w-12 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm border border-slate-100 active:scale-95 transition-all" @click="rentForm.quantity++">+</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- [KOMENTAR PENJELASAN]: Mobile Package Items (Grid 2 Kolom, Bukan List Kaku) -->
            <div class="mt-2 bg-white px-5 py-6 shadow-sm border-y border-slate-100">
                <h3 class="mb-4 text-[14px] font-extrabold text-slate-900">Kelengkapan Dalam Paket</h3>
                <!-- Diubah dari ul -> div grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div v-for="item in package.items || []" :key="item.id" class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-slate-50 p-3 transition-colors hover:border-cyan-200 hover:bg-cyan-50/30">
                        <div class="flex items-start justify-between mb-2">
                            <!-- Indikator Status Ketersediaan -->
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white shadow-sm border border-slate-100">
                                <CheckCircle2 v-if="item.product?.stock_available > 0" class="h-3 w-3 text-emerald-500" />
                                <XCircle v-else class="h-3 w-3 text-rose-500" />
                            </div>
                            <!-- Lencana Ada/Habis -->
                            <span v-if="item.product?.stock_available > 0" class="rounded-full bg-emerald-100 px-2 py-0.5 text-[8px] font-black uppercase tracking-wider text-emerald-600">Ada</span>
                            <span v-else class="rounded-full bg-rose-100 px-2 py-0.5 text-[8px] font-black uppercase tracking-wider text-rose-600">Habis</span>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-800 line-clamp-2 leading-snug">{{ item.product?.name || 'Produk' }}</p>
                            <p class="mt-1 text-[9px] font-extrabold text-cyan-600">{{ item.quantity }} Unit</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- [KOMENTAR PENJELASAN]: Mobile Floating Action Bar (Glow Shadow & Premium Feel) -->
            <!-- Memiliki jarak dari bawah (bottom-4) karena bottom nav utama sudah disembunyikan -->
            <div class="fixed bottom-4 inset-x-4 z-40 rounded-2xl bg-slate-900 px-4 py-3 shadow-[0_12px_30px_rgba(15,23,42,0.4)] border border-slate-700/50 backdrop-blur-xl flex items-center justify-between gap-4">
                <div class="flex-1">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Estimasi Total</p>
                    <div class="flex items-baseline gap-1">
                        <p class="text-base font-black text-white">{{ formatCurrency(package.price_per_day) }}</p>
                        <p class="text-[9px] text-slate-400">/hari</p>
                    </div>
                </div>
                <!-- Tombol trigger form diluar tag form -->
                <button type="button" onclick="document.getElementById('mobileRentForm').requestSubmit()" class="h-11 px-6 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-500 text-[13px] font-extrabold text-white transition-all active:scale-95 flex items-center justify-center gap-2 shadow-[0_0_15px_rgba(34,211,238,0.4)]" :disabled="rentForm.processing">
                    <ShoppingCart class="h-4 w-4" /> Tambahkan
                </button>
            </div>

        </div>

    </DefaultLayout>
</template>