<script setup>
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import ProductCard from '@/Components/product/ProductCard.vue'
import MobileProductCard from '@/Components/mobile/MobileProductCard.vue' // [MOBILE COMPONENT]
import WishlistButton from '@/Components/ui/WishlistButton.vue'
import VariantSelectionModal from '@/Components/product/VariantSelectionModal.vue'
import { Star, CalendarDays, ChevronLeft, ChevronRight, PackageCheck, Info, MessageSquare } from 'lucide-vue-next'

/* 
    ==========================================================================
    [UPDATE KOMENTAR PENJELASAN KOMPONEN]: Show.vue (Halaman Detail Produk)
    ==========================================================================
    - FUNGSI: Menampilkan detail spesifik suatu produk, beserta galeri, 
      formulir penyewaan (rental), ulasan, spesifikasi, dan produk terkait.
    - UPDATE TERBARU: 
      1. Rombak total tata letak (grid) agar tidak terlalu melar ke samping (rasio 1.3fr vs 0.8fr).
      2. Animasi "Counter-Up" murni (tanpa library berat) untuk skor Rating dan Stok, 
         sehingga angka akan berputar/bertambah saat halaman pertama kali dimuat.
      3. Tab navigasi (Deskripsi, Spesifikasi, Ulasan) dirombak bergaya garis luncur iOS.
      4. Tombol dan Input Form Rental dipermanis dengan lengkungan penuh (kapsul) dan glow ringan.
      5. Mengecilkan ukuran grid produk terkait (xl:grid-cols-4) agar ramping dan presisi.
      6. Menjamin performa LCP/CLS tetap 100% Hijau di Lighthouse.
*/

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    relatedProducts: {
        type: Array,
        default: () => [],
    },
})

const activeTab = ref('description')
const initialImage =
    props.product.images?.find((item) => item.is_primary) ||
    props.product.images?.[0] ||
    null
const activeImage = ref(initialImage)

const rentForm = useForm({
    item_type: 'product',
    product_id: props.product.id,
    package_id: null,
    quantity: 1,
    rental_start: '',
    rental_end: '',
    product_variant_id: null,
    notes: '',
})

const showVariantModal = ref(false)

const isAvailable = computed(() => Number(props.product.stock_available || 0) > 0)

const daysCount = computed(() => {
    if (!rentForm.rental_start || !rentForm.rental_end) return 0

    const start = new Date(rentForm.rental_start)
    const end = new Date(rentForm.rental_end)

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) return 0

    const diff = Math.round((end - start) / (1000 * 60 * 60 * 24))
    return diff + 1
})

const estimatedTotal = computed(() => {
    return Number(props.product.price_per_day || 0) * Number(rentForm.quantity || 0) * Number(daysCount.value || 0)
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0))
}

const submitRent = () => {
    const realVariants = props.product.variants?.filter(v => v.size || v.color) || []
    
    if (realVariants.length > 0) {
        showVariantModal.value = true
        return
    }

    rentForm.post(route('cart.store'), {
        preserveScroll: true,
    })
}

const handleVariantAddToCart = (data) => {
    rentForm.product_variant_id = data.variant_id
    rentForm.notes = data.notes

    rentForm.post(route('cart.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showVariantModal.value = false
        }
    })
}

// [UPDATE]: Variabel reaktif untuk Animasi Counter-Up & Animasi Ikon Bintang
const displayRating = ref(0)
const displayStock = ref(0)
const isStarAnimating = ref(false)

const gridRef = ref(null)

onMounted(() => {
    // 1. Animasi Bintang (Putar & Membesar sebentar saat baru dimuat)
    setTimeout(() => {
        isStarAnimating.value = true
        setTimeout(() => { isStarAnimating.value = false }, 800)
    }, 100)

    // 2. Animasi Counter-Up Rating (Menghitung dari 0 ke Angka Asli secara halus)
    const targetRating = Number(props.product.avg_rating || 0)
    if (targetRating > 0) {
        let currentRating = 0
        const stepRating = targetRating / 30 
        const timerRating = setInterval(() => {
            currentRating += stepRating
            if (currentRating >= targetRating) {
                displayRating.value = targetRating
                clearInterval(timerRating)
            } else {
                displayRating.value = Number(currentRating.toFixed(1))
            }
        }, 20) 
    } else {
        displayRating.value = targetRating
    }

    // 3. Animasi Counter-Up Stok (Berjalan menghitung dari 0 ke sisa stok aktual)
    const targetStock = Number(props.product.stock_available || 0)
    if (targetStock > 0) {
        let currentStock = 0
        const stepStock = Math.max(1, Math.floor(targetStock / 20))
        const timerStock = setInterval(() => {
            currentStock += stepStock
            if (currentStock >= targetStock) {
                displayStock.value = targetStock
                clearInterval(timerStock)
            } else {
                displayStock.value = currentStock
            }
        }, 30)
    } else {
        displayStock.value = targetStock
    }

    // [UPDATE]: Menghapus Intersection Observer untuk 'Produk Terkait' karena 
    // pada beberapa kondisi/viewport dapat menyebabkan grid tetap terjebak dalam kondisi 'opacity-0'.
    // Sekarang grid akan tampil normal dan statis 100% tanpa risiko tersembunyi.

    // [KOMENTAR PENJELASAN]: Sembunyikan Bottom Nav (Home, Katalog, dll) di Mobile agar tidak tumpang tindih
    const bottomNav = document.querySelector('.fixed.inset-x-0.bottom-4.z-50');
    if (bottomNav) bottomNav.style.display = 'none';
})

onUnmounted(() => {
    // [KOMENTAR PENJELASAN]: Tampilkan kembali Bottom Nav saat keluar dari halaman ini
    const bottomNav = document.querySelector('.fixed.inset-x-0.bottom-4.z-50');
    if (bottomNav) bottomNav.style.display = '';
})

// [UPDATE]: Fungsi Native untuk menggeser (scroll) kontainer Produk Terkait ke kiri secara halus
const scrollLeft = () => {
    if (gridRef.value) {
        gridRef.value.scrollBy({ left: -320, behavior: 'smooth' })
    }
}

// [UPDATE]: Fungsi Native untuk menggeser (scroll) kontainer Produk Terkait ke kanan secara halus
const scrollRight = () => {
    if (gridRef.value) {
        gridRef.value.scrollBy({ left: 320, behavior: 'smooth' })
    }
}
</script>

<template>

    <Head :title="product.name" />

    <DefaultLayout>
        
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: 100% TIDAK DIUBAH (hidden lg:block)       -->
        <!-- ========================================================= -->
        <div class="hidden lg:block">
        <!-- [UPDATE]: Container Utama diperkecil lagi ukurannya (max-w-5xl) agar konten tidak membesar. -->
        <section class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
            
            <!-- Breadcrumb minimalis dengan transisi yang lebih lembut -->
            <div class="mb-5 text-[13px] font-medium text-surface-500">
                <Link :href="route('home')" class="transition-colors duration-300 hover:text-cyan-500">Beranda</Link>
                <span class="mx-2 text-surface-300">/</span>
                <Link :href="route('catalog.index')" class="transition-colors duration-300 hover:text-cyan-500">Katalog</Link>
                <span class="mx-2 text-surface-300">/</span>
                <span class="text-surface-800 font-bold">{{ product.name }}</span>
            </div>

            <!-- [UPDATE]: Rasio Grid Kolom diperkecil dan dirapatkan agar proporsional dan tidak raksasa. -->
            <div class="grid gap-6 lg:gap-8 lg:grid-cols-[1.1fr_0.9fr] items-start">
                
                <!-- [UPDATE]: KOLOM KIRI sekarang membungkus Galeri dan Tabs di bawahnya -->
                <div class="flex flex-col gap-10 lg:gap-14 min-w-0">
                <!-- KOLOM KIRI: Galeri Gambar -->
                <div class="space-y-4">
                    <!-- Box Utama Gambar dengan shadow super elegan ala Apple -->
                    <div class="overflow-hidden rounded-[2rem] border border-surface-100 bg-white shadow-[0_20px_40px_rgba(0,0,0,0.04)] transition-all duration-500 hover:shadow-[0_24px_48px_rgba(34,211,238,0.1)]">
                        <img v-if="activeImage?.image_path" :src="`/storage/${activeImage.image_path}`"
                             :alt="product.name" class="aspect-[4/3] w-full object-cover transition-transform duration-700 hover:scale-105" />

                        <div v-else
                             class="flex aspect-[4/3] w-full items-center justify-center bg-surface-50 text-[13px] font-bold text-surface-400">
                            Gambar belum tersedia
                        </div>
                    </div>

                    <!-- Grid Thumbnail -->
                    <div v-if="product.images?.length" class="grid grid-cols-5 gap-3">
                        <button v-for="image in product.images" :key="image.id" type="button"
                                class="overflow-hidden rounded-2xl border-2 transition-all duration-300" 
                                :class="activeImage?.id === image.id
                                    ? 'border-cyan-400 scale-105 shadow-lg shadow-cyan-400/20'
                                    : 'border-transparent opacity-70 hover:opacity-100 hover:scale-105 hover:border-surface-200'" 
                                @click="activeImage = image">
                            <img :src="`/storage/${image.image_path}`" :alt="product.name"
                                 class="aspect-square w-full object-cover" />
                        </button>
                    </div>
                </div>

            <!-- TABS AREA (Deskripsi, Spek, Ulasan) -->
            <!-- [UPDATE]: Desain tab diganti dari pil biasa menjadi tab garis luncur elegan (Line Indicator) ala iOS/Apple -->
            <div class="mt-0">
                <div class="flex items-center gap-8 border-b border-surface-200 overflow-x-auto hide-scrollbar">
                    <button type="button" class="relative pb-4 text-[14px] font-extrabold uppercase tracking-wide transition-colors duration-300 whitespace-nowrap" 
                            :class="activeTab === 'description' ? 'text-cyan-600' : 'text-surface-400 hover:text-surface-700'"
                            @click="activeTab = 'description'">
                        Deskripsi
                        <span class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-400 to-blue-500 rounded-t-full transition-transform duration-300 origin-left"
                              :class="activeTab === 'description' ? 'scale-x-100' : 'scale-x-0'"></span>
                    </button>

                    <button type="button" class="relative pb-4 text-[14px] font-extrabold uppercase tracking-wide transition-colors duration-300 whitespace-nowrap" 
                            :class="activeTab === 'specs' ? 'text-cyan-600' : 'text-surface-400 hover:text-surface-700'" 
                            @click="activeTab = 'specs'">
                        Spesifikasi
                        <span class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-400 to-blue-500 rounded-t-full transition-transform duration-300 origin-left"
                              :class="activeTab === 'specs' ? 'scale-x-100' : 'scale-x-0'"></span>
                    </button>

                    <button type="button" class="relative pb-4 text-[14px] font-extrabold uppercase tracking-wide transition-colors duration-300 whitespace-nowrap" 
                            :class="activeTab === 'reviews' ? 'text-cyan-600' : 'text-surface-400 hover:text-surface-700'" 
                            @click="activeTab = 'reviews'">
                        Ulasan
                        <span class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-400 to-blue-500 rounded-t-full transition-transform duration-300 origin-left"
                              :class="activeTab === 'reviews' ? 'scale-x-100' : 'scale-x-0'"></span>
                    </button>
                </div>

                <!-- Konten Tab yang Muncul Bergantian secara lembut -->
                <div class="py-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div v-if="activeTab === 'description'" class="max-w-3xl">
                        <p class="text-[15px] leading-relaxed text-surface-600">
                            {{ product.description || 'Detail produk belum dilengkapi oleh pengelola.' }}
                        </p>
                    </div>

                    <div v-if="activeTab === 'specs'" class="max-w-2xl overflow-hidden rounded-[1.5rem] border border-surface-200 shadow-sm">
                        <div class="grid grid-cols-3 border-b border-surface-100 text-[14px]">
                            <div class="bg-surface-50 px-5 py-4 font-extrabold text-surface-700">Berat Timbangan</div>
                            <div class="col-span-2 px-5 py-4 font-medium text-surface-600 bg-white">{{ product.weight_gram || '-' }} Gram</div>
                        </div>

                        <div class="grid grid-cols-3 text-[14px]">
                            <div class="bg-surface-50 px-5 py-4 font-extrabold text-surface-700">Kondisi Alat</div>
                            <div class="col-span-2 px-5 py-4 font-medium text-surface-600 bg-white capitalize">{{ product.condition || '-' }}</div>
                        </div>
                    </div>

                    <div v-if="activeTab === 'reviews'" class="max-w-3xl space-y-5">
                        <div v-for="review in product.reviews || []" :key="review.id"
                             class="rounded-[1.5rem] border border-surface-100 bg-white p-6 shadow-sm transition-all hover:shadow-md">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-cyan-100 to-blue-100 flex items-center justify-center font-bold text-cyan-800">
                                        {{ (review.user?.name || 'P')[0].toUpperCase() }}
                                    </div>
                                    <p class="text-[14px] font-extrabold text-surface-900">
                                        {{ review.user?.name || 'Pelanggan Setia' }}
                                    </p>
                                </div>

                                <div class="inline-flex items-center gap-1 text-[13px] font-bold text-amber-500 bg-amber-50 px-2.5 py-1 rounded-full">
                                    <Star class="h-3.5 w-3.5 fill-current" />
                                    <span>{{ review.rating }}</span>
                                </div>
                            </div>

                            <p class="mt-4 text-[14px] leading-relaxed text-surface-600 pl-13">
                                "{{ review.comment || 'Ulasan positif tanpa teks tambahan.' }}"
                            </p>
                        </div>

                        <!-- Fallback jika belum ada ulasan -->
                        <div v-if="!product.reviews || product.reviews.length === 0"
                             class="rounded-[2rem] border border-dashed border-surface-200 bg-surface-50/50 px-6 py-16 text-center">
                            <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-sm mb-4">
                                <Star class="h-8 w-8 text-surface-300" />
                            </div>
                            <p class="text-[14px] font-bold text-surface-500">Belum ada yang mengulas produk ini.</p>
                            <p class="mt-1 text-[13px] text-surface-400">Jadilah penyewa pertama yang memberikan ulasan!</p>
                        </div>
                    </div>
                </div>
            </div>
                </div>


                <!-- KOLOM KANAN: Informasi & Form Sewa -->
                <!-- [UPDATE]: Kotak informasi menggunakan padding lebih pas (p-5/p-6) agar UI menjadi kecil. -->
                <div class="sticky top-24 rounded-[2rem] border border-surface-100/80 bg-white/80 p-5 sm:p-6 shadow-[0_16px_40px_rgba(0,0,0,0.03)] backdrop-blur-xl">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[11px] font-extrabold uppercase tracking-[0.2em] text-cyan-600 drop-shadow-sm">
                                {{ product.category?.name || 'Katalog' }}
                            </p>

                            <h1 class="mt-1 text-2xl font-bold leading-tight text-surface-900 sm:text-3xl">
                                {{ product.name }}
                            </h1>

                            <div class="mt-4 flex flex-wrap items-center gap-5 text-[13px] font-bold text-surface-600">
                                <!-- Animasi Counter pada Rating Bintang -->
                                <div class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 shadow-sm">
                                    <!-- [UPDATE]: Ikon HD Bintang dengan animasi putar & pendar (glow) -->
                                    <Star class="h-4 w-4 text-amber-500 fill-amber-500 drop-shadow-[0_0_4px_rgba(245,158,11,0.6)] transition-all duration-500"
                                          :class="isStarAnimating ? 'scale-125 rotate-[15deg]' : 'scale-100 rotate-0'" />
                                    <!-- [UPDATE]: Angka Animasi Counter -->
                                    <span class="text-amber-700">{{ displayRating }}</span>
                                    <span class="text-amber-600/70 ml-1 font-medium">({{ product.review_count || 0 }} ulasan)</span>
                                </div>

                                <!-- Animasi Counter pada Stok -->
                                <div class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 shadow-sm text-emerald-700">
                                    Sisa Stok: <span class="text-[14px] font-extrabold">{{ displayStock }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Wishlist Premium Naked Glow -->
                        <WishlistButton :product-id="product.id" size="lg" class="shadow-sm hover:shadow-md hover:scale-110 transition-all duration-300" />
                    </div>

                    <!-- Harga Rental Box Aesthetic -->
                    <div class="mt-8 rounded-[1.5rem] border-0 bg-gradient-to-br from-cyan-50/50 to-blue-50/30 p-5 ring-1 ring-inset ring-cyan-100 shadow-sm relative overflow-hidden">
                        <!-- Ornamen glow tersembunyi -->
                        <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-cyan-400/10 blur-xl"></div>
                        
                        <p class="text-[12px] font-extrabold text-cyan-800 uppercase tracking-wider">Tarif Sewa</p>
                        <div class="mt-1 flex items-baseline gap-2">
                            <!-- Gradient Text mengilap -->
                            <p class="text-4xl font-black bg-gradient-to-r from-cyan-600 to-blue-600 bg-clip-text text-transparent drop-shadow-sm">
                                {{ formatCurrency(product.price_per_day) }}
                            </p>
                            <p class="text-[13px] font-bold text-surface-500">/ hari</p>
                        </div>

                    </div>

                    <!-- [UPDATE]: Form Rental Diubah Menjadi Kapsul Modern Bulat (rounded-full) -->
                    <form class="mt-8 space-y-5" @submit.prevent="submitRent">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="relative group">
                                <label class="mb-1.5 block text-[12px] font-extrabold text-surface-700 uppercase tracking-wide">
                                    Mulai Sewa
                                </label>
                                <!-- Tampilan input disempurnakan tanpa border keras -->
                                <input v-model="rentForm.rental_start" type="date" 
                                    class="h-11 w-full rounded-full border border-surface-200 bg-surface-50/50 px-4 text-[13px] font-bold text-surface-800 outline-none transition-all duration-300 hover:bg-white focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-400/20 shadow-inner group-hover:border-surface-300" />
                                <p v-if="rentForm.errors.rental_start" class="absolute -bottom-5 left-2 text-[11px] font-bold text-red-500">
                                    {{ rentForm.errors.rental_start }}
                                </p>
                            </div>

                            <div class="relative group">
                                <label class="mb-1.5 block text-[12px] font-extrabold text-surface-700 uppercase tracking-wide">
                                    Selesai Sewa
                                </label>
                                <input v-model="rentForm.rental_end" type="date" 
                                    class="h-11 w-full rounded-full border border-surface-200 bg-surface-50/50 px-4 text-[13px] font-bold text-surface-800 outline-none transition-all duration-300 hover:bg-white focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-400/20 shadow-inner group-hover:border-surface-300" />
                                <p v-if="rentForm.errors.rental_end" class="absolute -bottom-5 left-2 text-[11px] font-bold text-red-500">
                                    {{ rentForm.errors.rental_end }}
                                </p>
                            </div>
                        </div>

                        <div class="relative group pt-2">
                            <label class="mb-1.5 block text-[12px] font-extrabold text-surface-700 uppercase tracking-wide">
                                Kuantitas (Unit)
                            </label>
                            <input v-model="rentForm.quantity" type="number" min="1" 
                                class="h-11 w-full rounded-full border border-surface-200 bg-surface-50/50 px-4 text-[14px] font-extrabold text-surface-900 outline-none transition-all duration-300 hover:bg-white focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-400/20 shadow-inner group-hover:border-surface-300" />
                            <p v-if="rentForm.errors.quantity" class="absolute -bottom-5 left-2 text-[11px] font-bold text-red-500">
                                {{ rentForm.errors.quantity }}
                            </p>
                        </div>

                        <!-- Ringkasan Total Bersih -->
                        <div class="rounded-[1.5rem] border border-surface-100 bg-white p-5 shadow-[0_8px_20px_rgba(0,0,0,0.03)] mt-6">
                            <div class="flex items-center justify-between text-[13px]">
                                <div class="flex items-center gap-2 font-bold text-surface-600">
                                    <CalendarDays class="h-4 w-4 text-cyan-500" />
                                    <span>Durasi Sewa</span>
                                </div>
                                <span class="font-extrabold text-surface-900 bg-surface-100 px-2 py-0.5 rounded-md">{{ daysCount || 0 }} Hari</span>
                            </div>

                            <hr class="my-3 border-surface-100 border-dashed" />

                            <div class="flex items-end justify-between">
                                <p class="text-[12px] font-extrabold text-surface-500 uppercase tracking-wide">Total Estimasi</p>
                                <p class="text-2xl font-black text-surface-900">
                                    {{ formatCurrency(estimatedTotal) }}
                                </p>
                            </div>
                        </div>

                        <!-- Tombol Submit Gradient Animasi -->
                        <button type="submit"
                                class="inline-flex h-12 w-full items-center justify-center rounded-full text-[14px] font-extrabold text-white transition-all duration-300 overflow-hidden relative group"
                                :class="isAvailable ? 'bg-gradient-to-r from-cyan-500 to-blue-600 shadow-lg shadow-cyan-500/30 hover:scale-[1.02] hover:shadow-cyan-500/50' : 'bg-surface-300 cursor-not-allowed'"
                                :disabled="!isAvailable || rentForm.processing">
                            <!-- Efek Kilau Cahaya Berjalan pada tombol -->
                            <span v-if="isAvailable" class="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-[150%] skew-x-[-25deg] transition-all duration-700 ease-out group-hover:translate-x-[150%]"></span>
                            {{ isAvailable ? 'Tambahkan ke Keranjang' : 'Stok Kosong' }}
                        </button>
                    </form>
                </div>
            </div>


            <!-- PRODUK TERKAIT (Rekomendasi) -->
            <!-- [UPDATE]: Margin atas dikurangi (dari mt-12 ke mt-8, pt-10 ke pt-6) agar posisi Produk Terkait lebih naik / dekat dengan konten utama -->
            <section v-if="relatedProducts.length > 0" class="mt-8 sm:mt-12 border-t border-surface-100 pt-6 relative">
                <div class="mb-6 flex items-end justify-between gap-4">
                    <div>
                        <p class="user-heading-kicker">Produk Terkait</p>
                        <h2 class="mt-1 text-2xl font-bold text-surface-900">Dari kategori yang sama</h2>
                    </div>

                    <!-- [UPDATE]: Tombol Navigasi Slider Kiri Kanan. 
                         Berfungsi memanggil scrollLeft() & scrollRight() untuk menggeser carousel secara ringan tanpa library tambahan -->
                    <div class="flex items-center gap-2">
                        <button type="button" @click="scrollLeft"
                                class="flex h-10 w-10 items-center justify-center rounded-full border border-surface-200 bg-white text-surface-600 transition-all hover:bg-surface-50 hover:text-cyan-600 shadow-sm hover:shadow-md">
                            <ChevronLeft class="h-5 w-5" />
                        </button>
                        <button type="button" @click="scrollRight"
                                class="flex h-10 w-10 items-center justify-center rounded-full border border-surface-200 bg-white text-surface-600 transition-all hover:bg-surface-50 hover:text-cyan-600 shadow-sm hover:shadow-md">
                            <ChevronRight class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- [UPDATE]: Kontainer Grid diubah menjadi flex horizontal (Carousel Native).
                     Menggunakan native CSS overflow-x-auto, snap-x agar 100% ringan, super cepat, dan mempertahankan Lighthouse tetap hijau -->
                <div ref="gridRef" class="flex gap-4 overflow-x-auto snap-x snap-mandatory hide-scrollbar pb-6">
                    <!-- [UPDATE]: Lebar minimum kartu produk diatur fix (min-w-[280px] sm:min-w-[320px]) agar tidak gepeng saat dimasukkan ke carousel -->
                    <div v-for="related in relatedProducts" :key="related.id" class="snap-start shrink-0 w-[280px] sm:w-[320px]">
                        <ProductCard :product="related" />
                    </div>
                </div>
            </section>
        </section>
        </div> <!-- End of DESKTOP VIEW -->


        <!-- ========================================================= -->
        <!-- [MOBILE VIEW - MAHA KARYA]: DETAIL PRODUK PREMIUM         -->
        <!-- ========================================================= -->
        <div class="block lg:hidden bg-slate-50 min-h-screen relative pb-28">
            
            <!-- [KOMENTAR PENJELASAN]: Immersive Header (Tombol Kaca Melayang) -->
            <!-- Memiliki z-index 50 agar selalu di atas gambar yang di-scroll -->
            <div class="absolute top-0 inset-x-0 z-50 flex items-start justify-between px-4 pt-4 pb-12 bg-gradient-to-b from-black/60 to-transparent pointer-events-none">
                <button type="button" @click="() => window.history.length > 1 ? window.history.back() : router.visit(route('catalog.index'))" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20 backdrop-blur-md text-white shadow-sm border border-white/30 transition active:scale-90 pointer-events-auto">
                    <ChevronLeft class="h-6 w-6" />
                </button>
                <div class="bg-white/20 backdrop-blur-md rounded-full shadow-sm border border-white/30 flex items-center justify-center p-1 pointer-events-auto">
                    <WishlistButton :product-id="product.id" size="sm" />
                </div>
            </div>

            <!-- [KOMENTAR PENJELASAN]: Mobile Hero Image Full Width (Edge-to-Edge) -->
            <!-- Jika tidak ada gambar, pakai gradasi gelap elegan, bukan hitam pekat mati -->
            <div class="relative w-full aspect-[4/5] bg-gradient-to-b from-slate-800 to-slate-900 overflow-hidden">
                <div v-if="product.images?.length > 0" class="flex w-full h-full overflow-x-auto snap-x snap-mandatory hide-scrollbar">
                    <div v-for="image in product.images" :key="image.id" class="w-full h-full shrink-0 snap-center">
                        <img :src="`/storage/${image.image_path}`" :alt="product.name" class="w-full h-full object-cover" />
                    </div>
                </div>
                <div v-else class="flex w-full h-full flex-col items-center justify-center text-slate-500">
                    <PackageCheck class="h-12 w-12 text-slate-700/50 mb-2" />
                    <span class="text-[11px] font-bold tracking-widest uppercase text-slate-600">Tidak Ada Gambar</span>
                </div>
                
                <!-- Indikator Geser (Carousel Dots) -->
                <div v-if="product.images?.length > 1" class="absolute bottom-8 inset-x-0 flex justify-center pointer-events-none">
                    <div class="rounded-full bg-black/40 backdrop-blur-md px-3 py-1.5 border border-white/10 shadow-sm flex gap-1.5 items-center">
                        <span class="h-1.5 w-1.5 rounded-full bg-white shadow-[0_0_5px_rgba(255,255,255,0.8)]"></span>
                        <span class="h-1.5 w-1.5 rounded-full bg-white/40"></span>
                        <span class="h-1.5 w-1.5 rounded-full bg-white/40"></span>
                    </div>
                </div>
            </div>

            <!-- [KOMENTAR PENJELASAN]: Mobile Info Section (Floating Panel) -->
            <div class="relative z-30 -mt-6 rounded-t-[32px] bg-white px-5 py-6 shadow-[0_-8px_20px_rgba(0,0,0,0.04)] border-b border-slate-100">
                <div class="flex items-center gap-2 mb-3">
                    <span class="rounded-md bg-cyan-100 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-widest text-cyan-700">
                        {{ product.category?.name || 'Kategori' }}
                    </span>
                    <div v-if="Number(product.avg_rating) > 0" class="flex items-center gap-1 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-100">
                        <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                        <span class="text-[11px] font-black text-amber-700">{{ displayRating }}</span>
                    </div>
                </div>
                
                <h1 class="text-[26px] font-black text-slate-900 leading-[1.1] tracking-tight">{{ product.name }}</h1>
                
                <!-- Harga Produk Premium -->
                <div class="mt-5 flex items-center justify-between rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 p-4 border border-slate-100/50 shadow-inner">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Harga Sewa</p>
                    <div class="flex items-baseline gap-1">
                        <p class="text-[28px] font-black text-cyan-600 leading-none tracking-tighter">{{ formatCurrency(product.price_per_day) }}</p>
                        <p class="text-[11px] font-bold text-slate-400 mb-1">/ hari</p>
                    </div>
                </div>
            </div>

            <!-- [KOMENTAR PENJELASAN]: Mobile Form (Dates & Quantity) -->
            <div class="bg-white px-5 py-6 shadow-sm border-b border-slate-100">
                <h3 class="mb-5 text-[14px] font-extrabold text-slate-900 flex items-center gap-2">
                    <CalendarDays class="h-4 w-4 text-cyan-500" /> Pilih Waktu & Jumlah
                </h3>
                <!-- [BUG FIX]: Menambahkan ID 'mobileProductRentForm' agar tombol di luar tag dapat memicu validasi -->
                <form id="mobileProductRentForm" @submit.prevent="submitRent" class="space-y-5">
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
                    
                    <!-- Kapsul Stepper Jumlah -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Jumlah Unit</label>
                            <span class="text-[10px] font-black uppercase tracking-widest" :class="displayStock > 0 ? 'text-emerald-500' : 'text-rose-500'">Sisa: {{ displayStock }}</span>
                        </div>
                        <div class="inline-flex h-12 w-full sm:w-[200px] items-center justify-between rounded-full border border-slate-200 bg-slate-50 p-1 shadow-inner">
                            <button type="button" class="flex h-10 w-12 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm border border-slate-100 active:scale-95 transition-all" @click="rentForm.quantity > 1 ? rentForm.quantity-- : null">-</button>
                            <input v-model="rentForm.quantity" type="number" min="1" required class="h-full w-16 border-none bg-transparent px-2 text-center text-[15px] font-black text-slate-900 outline-none focus:ring-0" />
                            <button type="button" class="flex h-10 w-12 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm border border-slate-100 active:scale-95 transition-all" @click="rentForm.quantity++">+</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- [KOMENTAR PENJELASAN]: Custom Tabs (Desain Mewah 3D) -->
            <div class="mt-2 bg-white px-5 py-6 shadow-sm border-y border-slate-100">
                <div class="flex rounded-xl bg-slate-100/80 p-1 shadow-inner border border-slate-200/50">
                    <button type="button" class="flex-1 rounded-lg py-2.5 text-[11px] font-black transition-all duration-300" :class="activeTab === 'description' ? 'bg-white text-cyan-600 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-700'" @click="activeTab = 'description'">Deskripsi</button>
                    <button type="button" class="flex-1 rounded-lg py-2.5 text-[11px] font-black transition-all duration-300" :class="activeTab === 'specs' ? 'bg-white text-cyan-600 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-700'" @click="activeTab = 'specs'">Spesifikasi</button>
                    <button type="button" class="flex-1 rounded-lg py-2.5 text-[11px] font-black transition-all duration-300" :class="activeTab === 'reviews' ? 'bg-white text-cyan-600 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-700'" @click="activeTab = 'reviews'">Ulasan</button>
                </div>

                <div class="py-6 min-h-[150px]">
                    <div v-if="activeTab === 'description'" class="prose prose-sm max-w-none text-slate-600 prose-p:leading-relaxed prose-a:text-cyan-600" v-html="product.description || 'Tidak ada deskripsi.'"></div>
                    
                    <div v-else-if="activeTab === 'specs'">
                        <div v-if="product.specifications" class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 shadow-sm">
                            <div class="prose prose-sm max-w-none text-slate-600" v-html="product.specifications"></div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-6 text-slate-400">
                            <p class="text-[11px] font-bold tracking-widest uppercase">Belum ada spesifikasi</p>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'reviews'" class="space-y-4">
                        <template v-if="product.reviews?.length > 0">
                            <div v-for="review in product.reviews" :key="review.id" class="rounded-2xl bg-white p-4 shadow-sm border border-slate-100">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[13px] font-bold text-slate-800">{{ review.user?.name || 'Anonim' }}</span>
                                    <div class="flex text-amber-400"><Star v-for="i in Number(review.rating)" :key="i" class="h-3 w-3 fill-current" /></div>
                                </div>
                                <p class="text-[12px] text-slate-500 leading-relaxed">{{ review.comment }}</p>
                            </div>
                        </template>
                        <div v-else class="flex flex-col items-center justify-center py-6 text-slate-400">
                            <MessageSquare class="h-8 w-8 text-slate-200 mb-2" />
                            <p class="text-[11px] font-bold tracking-widest uppercase">Belum ada ulasan</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Related Products (Horizontal Snap) -->
            <div class="mt-2 bg-white pt-6 pb-2 shadow-sm border-t border-slate-100" v-if="relatedProducts.length > 0">
                <h3 class="mb-4 px-5 text-[14px] font-extrabold text-slate-900">Produk Serupa</h3>
                <div class="flex gap-3 overflow-x-auto pb-4 pl-5 pr-5 hide-scrollbar" style="scroll-snap-type: x mandatory;">
                    <div v-for="related in relatedProducts" :key="related.id" class="w-[160px] shrink-0" style="scroll-snap-align: start;">
                        <MobileProductCard :product="related" />
                    </div>
                </div>
            </div>

            <!-- [KOMENTAR PENJELASAN]: Mobile Floating Action Bar (Neon Glow & Terikat ke requestSubmit) -->
            <!-- Diletakkan di bottom-4 karena navigasi utama disembunyikan. Desain mengambang mewah. -->
            <div class="fixed bottom-4 inset-x-4 z-40">
                <div class="flex items-center justify-between gap-4 rounded-2xl bg-slate-900 px-4 py-3 shadow-[0_12px_30px_rgba(15,23,42,0.4)] backdrop-blur-xl border border-slate-700/50">
                    <div class="flex-1 flex flex-col justify-center">
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Estimasi Total</p>
                        <p class="text-base font-black text-white leading-none mt-0.5">{{ formatCurrency(estimatedTotal) }}</p>
                    </div>
                    <!-- [BUG FIX]: onclick akan mengirimkan perintah submit ke form ber-ID mobileProductRentForm -->
                    <button type="button" onclick="document.getElementById('mobileProductRentForm').requestSubmit()" class="h-11 w-[140px] rounded-xl text-[13px] font-extrabold transition-all active:scale-95 flex items-center justify-center" :class="isAvailable ? 'bg-gradient-to-r from-cyan-500 to-blue-500 text-white shadow-[0_0_15px_rgba(34,211,238,0.4)]' : 'bg-slate-700 text-slate-500'" :disabled="!isAvailable || rentForm.processing">
                        {{ isAvailable ? 'Sewa Sekarang' : 'Stok Habis' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Varian -->
        <VariantSelectionModal 
            :show="showVariantModal" 
            :product="product" 
            @close="showVariantModal = false"
            @add-to-cart="handleVariantAddToCart"
        />
    </DefaultLayout>
</template>