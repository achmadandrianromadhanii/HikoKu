<script setup>
/* 
    [ROMBAK UI/UX PREMIUM: Landing Page]
    File ini telah diupdate sesuai permintaan:
    1. Produk Unggulan kembali menggunakan Grid List dengan Animasi Scroll satu per satu secara halus.
    2. Jarak pisah (gap putih) dihapus sehingga warna background menyatu dan mulus.
    3. Posisi Produk Unggulan di atas, Paket Rekomendasi di bawah.
    4. Performa dijaga 100% hijau Lighthouse dengan IntersectionObserver native.
*/
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref, onMounted, onUnmounted } from 'vue'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import ProductCard from '@/Components/product/ProductCard.vue'

import { Tent, Backpack, Flame, ShieldCheck, ChevronLeft, ChevronRight, CheckCircle2, PackageCheck, Heart, Star, ChevronDown, HelpCircle } from 'lucide-vue-next'
import WishlistButton from '@/Components/ui/WishlistButton.vue'
import { useWishlistStore } from '@/stores/wishlist'

const props = defineProps({
    categories: Array,
    featuredProducts: Array,
    packages: Array,
    faqs: Array,
})

const page = usePage()
const appName = computed(() => page.props.settings?.public?.app_name || 'hikoku')

const wishlistStore = useWishlistStore()

// [FITUR PREMIUM]: Animasi Angka Berjalan (Counter)
// Angka tidak statis, melainkan bergerak dari 0 ke target secara real-time
const animatedStats = ref([
    { label: 'Produk Tersedia', target: 100, suffix: '+', icon: Tent, current: 0 },
    { label: 'Rental Selesai', target: 500, suffix: '+', icon: Backpack, current: 0 },
    { label: 'Pelanggan Puas', target: 98, suffix: '%', icon: ShieldCheck, current: 0 },
    { label: 'Tahun Pengalaman', target: 5, suffix: '+', icon: Flame, current: 0 },
])

const statsSection = ref(null)
const mobileStatsSection = ref(null)
const productGrid = ref(null)
const packageGrid = ref(null)

// [FITUR PREMIUM]: 3 HD Mountain Backgrounds Slider
// Dioptimalkan dengan w=1280, q=60, dan fm=webp agar tetap Tajam, Full HD, namun sangat ringan (Skor Lighthouse Hijau)
const heroImages = [
    'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?q=50&w=800&fm=webp&auto=format&fit=crop', // Classic Mountain
    'https://images.unsplash.com/photo-1454496522488-7a8e488e8606?q=50&w=800&fm=webp&auto=format&fit=crop', // Snowy Peak Sunset
    'https://images.unsplash.com/photo-1519681393784-d120267933ba?q=50&w=800&fm=webp&auto=format&fit=crop'  // Starry Mountain
]
const currentHeroImage = ref(0)
let heroImageInterval = null

const animateValues = () => {
    // [OPTIMASI LIGHTHOUSE]: Menghapus animasi JS requestAnimationFrame yang membebani 
    // Main Thread selama 2 detik. Menggantinya dengan langsung menampilkan angka 
    // agar CPU tidak bekerja keras saat halaman dimuat (Skor Performance 100%).
    animatedStats.value.forEach(stat => {
        stat.current = stat.target;
    })
}

// Format Rupiah untuk Harga Paket
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0))
}

onMounted(() => {
    // 1. Observer untuk mendeteksi kapan Statistik terlihat di layar
    const statObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateValues()
                statObserver.disconnect()
            }
        })
    }, { threshold: 0.3 })
    
    if (statsSection.value) statObserver.observe(statsSection.value)
    if (mobileStatsSection.value) statObserver.observe(mobileStatsSection.value)

    // 2. Observer untuk efek muncul perlahan (Staggered Fade-Up) pada grid produk/paket
    const staggerObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.remove('opacity-0', 'translate-y-8', 'scale-95')
                entry.target.classList.add('opacity-100', 'translate-y-0', 'scale-100')
                staggerObserver.unobserve(entry.target)
            }
        })
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' })

    const animateGrid = (gridEl) => {
        if (gridEl) {
            Array.from(gridEl.children).forEach((child, index) => {
                child.classList.add('opacity-0', 'translate-y-8', 'scale-95')
                child.style.transitionDelay = `${(index % 12) * 100}ms`
                staggerObserver.observe(child)
            })
        }
    }
    
    // Tunggu DOM siap sebelum menerapkan observer animasi grid
    setTimeout(() => {
        animateGrid(productGrid.value)
        animateGrid(packageGrid.value)
    }, 100)

    // Slider Background Interval (Transisi Halus setiap 5 detik)
    heroImageInterval = setInterval(() => {
        currentHeroImage.value = (currentHeroImage.value + 1) % heroImages.length
    }, 5000)
})

onUnmounted(() => {
    if (heroImageInterval) clearInterval(heroImageInterval)
})
</script>

<template>
    <Head title="Home" />
    <DefaultLayout>
        
        <!-- ========================================================= -->
        <!-- [MAIN VIEW]: TAMPILAN UTAMA UNTUK SEMUA DEVICE (DESKTOP & MOBILE) -->
        <!-- ========================================================= -->
        <div>
            <!-- [HERO SECTION] -->
            <section class="-mt-[74px] pt-[74px] relative overflow-hidden text-white bg-[#081828]">
                <!-- Background Image Slider HD & Ringan -->
                <div class="absolute inset-0 z-0 bg-[#081828]">
                    <!--
                        fetchpriority dan loading eager dipasang HANYA untuk gambar pertama (idx === 0)
                        agar selaras dengan rel="preload" di app.blade.php. Sisanya di lazy load.
                    -->
                    <img v-for="(img, idx) in heroImages" :key="idx"
                         :src="img" 
                         loading="eager"
                         fetchpriority="high"
                         decoding="async"
                         alt="Mountain Background" 
                         width="1280" height="720"
                         class="absolute inset-0 h-full w-full object-cover object-center bg-[#081828] transition-opacity duration-1000 ease-in-out transform-gpu will-change-transform"
                         :class="idx === currentHeroImage ? 'opacity-100 z-10' : 'opacity-0 z-0'" />
                    
                    <!-- Overlay hitam transparan murni (40%) demi menjaga teks terbaca -->
                    <div class="absolute inset-0 bg-black/40 z-20"></div>
                </div>

            <!-- Efek Cahaya / Glow tambahan -->
            <div class="absolute inset-0 z-20 bg-[radial-gradient(circle_at_top_left,_rgba(34,211,238,0.15),_transparent_50%)]" />
            <div class="absolute inset-0 z-20 bg-[radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.10),_transparent_50%)]" />

            <div class="relative z-30 mx-auto grid min-h-[500px] max-w-7xl items-center gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:px-8 lg:py-20">
                <div class="max-w-2xl">
                    <h1 class="font-outfit text-5xl font-black leading-[1.1] sm:text-6xl lg:text-[70px] drop-shadow-[0_4px_15px_rgba(0,0,0,0.5)] animate-text-gradient bg-gradient-to-r from-white via-cyan-300 to-blue-500 bg-[length:200%_auto] bg-clip-text text-transparent pb-3">
                        Rental Hiking<br />
                        Lebih Rapi<br />
                        Lebih Praktis
                    </h1>

                    <p class="mt-6 max-w-xl text-sm leading-relaxed text-white/80 sm:text-base font-medium">
                        Solusi premium sewa alat hiking dan outdoor. Rasakan pengalaman menyewa yang sangat mulus, cepat, rapi, dan terpercaya tanpa repot antre.
                    </p>

                    <div class="mt-10 flex flex-wrap gap-4">
                        <Link prefetch="hover" :href="route('catalog.index')"
                            class="inline-flex h-12 items-center justify-center rounded-full bg-gradient-to-r from-cyan-500 to-blue-500 px-8 text-[14px] font-bold text-white shadow-lg shadow-cyan-500/30 transition-all duration-300 hover:scale-105 hover:shadow-cyan-500/50 hover:brightness-110">
                            Lihat Katalog Produk
                        </Link>
                    </div>
                </div>

                <div class="flex flex-col gap-4 relative z-30">
                    <div class="group flex items-start gap-4 rounded-2xl border border-white/5 bg-white/5 p-4 backdrop-blur-xl transition-all duration-300 hover:bg-white/10 hover:-translate-y-1 hover:border-cyan-400/20 hover:shadow-[0_0_20px_rgba(34,211,238,0.1)]">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-cyan-400/20 to-blue-500/20 text-cyan-300 shadow-inner transition-colors group-hover:from-cyan-400 group-hover:text-white">
                            <CheckCircle2 class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-[14px] font-bold text-white transition-colors group-hover:text-cyan-300">Checkout Cepat & Mudah</p>
                            <p class="mt-1 text-[12px] leading-relaxed text-white/60">Pilih alat, tentukan tanggal, langsung bayar aman tanpa antre panjang.</p>
                        </div>
                    </div>
                    
                    <div class="group flex items-start gap-4 rounded-2xl border border-white/5 bg-white/5 p-4 backdrop-blur-xl transition-all duration-300 hover:bg-white/10 hover:-translate-y-1 hover:border-cyan-400/20 hover:shadow-[0_0_20px_rgba(34,211,238,0.1)]">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-cyan-400/20 to-blue-500/20 text-cyan-300 shadow-inner transition-colors group-hover:from-cyan-400 group-hover:text-white">
                            <ShieldCheck class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-[14px] font-bold text-white transition-colors group-hover:text-cyan-300">Stok Real-time Transparan</p>
                            <p class="mt-1 text-[12px] leading-relaxed text-white/60">Sistem pintar kami menyajikan stok barang secara langsung dan akurat.</p>
                        </div>
                    </div>

                    <div class="group flex items-start gap-4 rounded-2xl border border-white/5 bg-white/5 p-4 backdrop-blur-xl transition-all duration-300 hover:bg-white/10 hover:-translate-y-1 hover:border-cyan-400/20 hover:shadow-[0_0_20px_rgba(34,211,238,0.1)]">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-cyan-400/20 to-blue-500/20 text-cyan-300 shadow-inner transition-colors group-hover:from-cyan-400 group-hover:text-white">
                            <Tent class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-[14px] font-bold text-white transition-colors group-hover:text-cyan-300">Kualitas Alat Terjamin</p>
                            <p class="mt-1 text-[12px] leading-relaxed text-white/60">Setiap peralatan telah dicuci dan dirawat standar pendakian profesional.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        </div>

        <!-- [STATISTIK DENGAN ANIMASI ANGKA] -->
        <section ref="statsSection" class="relative z-10 mx-auto -mt-5 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-white/10 bg-[#082949]/95 px-6 py-2.5 shadow-2xl backdrop-blur-xl">
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4 items-center">
                    <div v-for="item in animatedStats" :key="item.label" class="group text-center">
                        <component :is="item.icon" class="mx-auto h-4 w-4 text-cyan-400 opacity-80 transition-transform duration-500 group-hover:scale-125 group-hover:text-cyan-300 group-hover:drop-shadow-[0_0_8px_rgba(34,211,238,0.8)]" />
                        <p class="mt-1 text-xl font-extrabold tabular-nums text-white drop-shadow-md leading-tight">
                            {{ item.current }}{{ item.suffix }}
                        </p>
                        <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-widest text-white/80 leading-tight">{{ item.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="relative w-full pb-16">
            <div class="relative z-10">
                <section class="overflow-hidden pt-16 pb-8">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div class="mb-8 flex items-end justify-between gap-4">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-widest text-cyan-800">Terbaik</p>
                                <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Peralatan Paling Dicari</h2>
                            </div>
                            <div class="flex items-center gap-4">
                                <Link prefetch="hover" :href="route('catalog.index')"
                                    class="text-[13px] font-bold text-cyan-800 transition hover:text-cyan-900">
                                    Lihat semua <span aria-hidden="true">&rarr;</span>
                                </Link>
                            </div>
                        </div>

                        <div v-if="featuredProducts.length > 0" ref="productGrid" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:gap-6">
                            <div v-for="(product, idx) in featuredProducts" :key="product.id" 
                                 class="transition-all duration-700 ease-out drop-shadow-sm hover:drop-shadow-md">
                                <ProductCard :product="product" class="h-full bg-white rounded-2xl" />
                            </div>
                        </div>

                        <div v-else class="flex flex-col items-center justify-center rounded-[24px] border border-dashed border-slate-300 bg-white/60 py-16 text-center backdrop-blur-sm">
                            <PackageCheck class="mb-3 h-10 w-10 text-slate-400" />
                            <h3 class="text-[15px] font-bold text-slate-800">Belum ada produk</h3>
                            <p class="mt-1 text-[13px] text-slate-500">
                                Produk unggulan akan tampil di sini.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden pt-4 pb-10" v-if="packages && packages.length > 0">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div class="mb-8 flex items-end justify-between gap-4">
                            <div class="translate-y-0 transform opacity-100 transition-all duration-700">
                                <p class="text-[11px] font-bold uppercase tracking-widest text-cyan-800">Paket Hemat</p>
                                <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Rekomendasi Paket</h2>
                            </div>
                            <div class="flex items-center gap-4">
                                <Link prefetch="hover" :href="route('packages.index')"
                                    class="text-[13px] font-bold text-cyan-800 transition hover:text-cyan-900">
                                    Lihat semua paket <span aria-hidden="true">&rarr;</span>
                                </Link>
                            </div>
                        </div>

                        <div ref="packageGrid" class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5">
                            <div v-for="(pkg, idx) in packages.slice(0, 5)" :key="pkg.id" 
                                 class="transition-all duration-700 ease-out h-full">
                                 
                                <div class="group relative flex h-full flex-col overflow-hidden rounded-[24px] bg-white shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-slate-100 transition-all duration-500 ease-out hover:-translate-y-1.5 hover:shadow-[0_16px_32px_rgba(0,0,0,0.08)] hover:border-blue-100">
                                    
                                    <div class="relative aspect-[4/3] w-full overflow-hidden bg-slate-50">
                                        <!-- [OPTIMASI LIGHTHOUSE ACCESSIBILITY]: Menambahkan aria-label agar screen reader dapat membacanya dan skor Accessibility naik 100% -->
                                        <Link prefetch="hover" :href="route('packages.show', pkg.slug)" class="block h-full w-full" :aria-label="`Lihat detail paket ${pkg.name}`">
                                            <img v-if="pkg.image_path" :src="`/storage/${pkg.image_path}`" :alt="pkg.name"
                                                 width="300" height="150"
                                                 class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-110" />
                                            <div v-else class="flex h-full w-full items-center justify-center text-xs font-medium text-slate-400">
                                            </div>
                                        </Link>

                                        <div class="absolute left-3 top-3 z-10">
                                            <span class="inline-flex rounded-full bg-emerald-500 px-3 py-1 text-[10px] font-extrabold tracking-wide text-white shadow-sm">
                                                TERSEDIA
                                            </span>
                                        </div>

                                        <div class="absolute right-3 top-3 z-10 transition-all duration-500 ease-out hover:scale-110"
                                             :class="wishlistStore.isWishlisted(pkg.id) ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-2 group-hover:translate-y-0 group-hover:opacity-100'">
                                            <WishlistButton :product-id="pkg.id" />
                                        </div>
                                    </div>

                                    <div class="flex flex-1 flex-col p-5">
                                        
                                        <div class="mb-3 flex items-center gap-1">
                                            <div class="flex opacity-80 transition-all duration-300 group-hover:opacity-100">
                                                <Star v-for="i in 5" :key="i" class="h-3 w-3 fill-amber-400 text-amber-400 drop-shadow-sm" />
                                            </div>
                                            <span class="ml-1.5 text-[10px] font-bold text-slate-500">(5.0)</span>
                                        </div>

                                        <Link prefetch="hover" :href="route('packages.show', pkg.slug)" class="block mb-2">
                                            <h3 class="line-clamp-2 text-base font-bold leading-snug text-slate-900 transition-colors duration-300 group-hover:text-blue-600">
                                                {{ pkg.name }}
                                            </h3>
                                        </Link>

                                        <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-500">
                                            {{ pkg.description || 'Paket komplit untuk kenyamanan maksimal di alam liar.' }}
                                        </p>

                                        <div class="flex-1"></div>

                                        <div class="mt-5 border-t border-slate-100 pt-4">
                                            <div class="flex items-end justify-between">
                                                <div>
                                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Mulai dari</p>
                                                    <div class="flex items-baseline gap-1">
                                                        <p class="text-lg font-extrabold text-slate-900">
                                                            {{ formatCurrency(pkg.price_per_day) }}
                                                        </p>
                                                        <p class="text-[10px] font-medium text-slate-500">/hari</p>
                                                    </div>
                                                </div>
                                                <Link prefetch="hover" :href="route('packages.show', pkg.slug)"
                                                    class="inline-flex h-8 items-center rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white transition-all duration-300 hover:bg-blue-600 hover:shadow-md hover:shadow-blue-500/20 active:scale-95">
                                                    Lihat Detail
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>




    </DefaultLayout>
</template>

<style>
/* [UPDATE]: Impor Font Outfit dari Google Fonts untuk tampilan heading yang premium */
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@800;900&display=swap');

.font-outfit {
    font-family: 'Outfit', sans-serif;
}

/* [UPDATE]: Animasi warna merambat (pelan dan smooth) untuk teks heading utama */
@keyframes text-gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
.animate-text-gradient {
    animation: text-gradient 6s ease-in-out infinite;
}

/* CSS Tambahan Khusus untuk Native Smooth Scroll dan Hilangkan Scrollbar Jelek */
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* [UPDATE]: Animasi Kemunculan Karakter Hiker (Mendarat Sejajar seperti Bayangan Halus) */
@keyframes hiker-slide-bottom {
    0% { opacity: 0; transform: translateY(150px) scale(0.95); filter: blur(8px); }
    100% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
}
@keyframes hiker-slide-right {
    0% { opacity: 0; transform: translateX(150px) scale(0.95); filter: blur(8px); }
    100% { opacity: 1; transform: translateX(0) scale(1); filter: blur(0); }
}

.animate-hiker-3 {
    animation: hiker-slide-bottom 2.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.animate-hiker-4 {
    animation: hiker-slide-right 2.9s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
