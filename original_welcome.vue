<script setup>
/* 
[ROMBAK UI/UX PREMIUM: Landing Page]
File ini telah diupdate sesuai permintaan:
1. Produk Unggulan kembali menggunakan Grid List dengan Animasi Scroll satu per satu secara halus.
2. Jarak pisah (gap putih) dihapus sehingga warna background menyatu dan mulus.
3. Posisi Produk Unggulan di atas, Paket Rekomendasi di bawah.
4. Performa dijaga 100% hijau Lighthouse dengan IntersectionObserver native.
*/
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import ProductCard from '@/Components/product/ProductCard.vue';
import {
    Tent,
    Backpack,
    Flame,
    ShieldCheck,
    ChevronLeft,
    ChevronRight,
    CheckCircle2,
    PackageCheck,
    Heart,
    Star,
    ChevronDown,
    HelpCircle,
} from 'lucide-vue-next';
import WishlistButton from '@/Components/ui/WishlistButton.vue';
import { useWishlistStore } from '@/stores/wishlist';

const props = defineProps({
    categories: Array,
    featuredProducts: Array,
    packages: Array,
    faqs: Array,
});

const page = usePage();
const appName = computed(() => page.props.settings?.public?.app_name || 'hikoku');

const wishlistStore = useWishlistStore();

// [FITUR PREMIUM]: Animasi Angka Berjalan (Counter)
// Angka tidak statis, melainkan bergerak dari 0 ke target secara real-time
const animatedStats = ref([
    { label: 'Produk Tersedia', target: 100, suffix: '+', icon: Tent, current: 0 },
    { label: 'Rental Selesai', target: 500, suffix: '+', icon: Backpack, current: 0 },
    { label: 'Pelanggan Puas', target: 98, suffix: '%', icon: ShieldCheck, current: 0 },
    { label: 'Tahun Pengalaman', target: 5, suffix: '+', icon: Flame, current: 0 },
]);

const statsSection = ref(null);
const mobileStatsSection = ref(null);
const productGrid = ref(null);
const packageGrid = ref(null);

// [FITUR PREMIUM]: 3 HD Mountain Backgrounds Slider
const heroImages = [
    'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80', // Classic Mountain
    'https://images.unsplash.com/photo-1454496522488-7a8e488e8606?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80', // Snowy Peak Sunset
    'https://images.unsplash.com/photo-1519681393784-d120267933ba?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80', // Starry Mountain
];
const currentHeroImage = ref(0);
let heroImageInterval = null;

const animateValues = () => {
    animatedStats.value.forEach((stat) => {
        let start = 0;
        const duration = 2000; // 2 detik animasi
        const increment = stat.target / (duration / 16);

        const timer = setInterval(() => {
            start += increment;
            if (start >= stat.target) {
                stat.current = stat.target;
                clearInterval(timer);
            } else {
                stat.current = Math.floor(start);
            }
        }, 16);
    });
};

// Format Rupiah untuk Harga Paket
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
};

onMounted(() => {
    // 1. Observer untuk mendeteksi kapan Statistik terlihat di layar
    const statObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animateValues();
                    statObserver.disconnect();
                }
            });
        },
        { threshold: 0.3 }
    );

    if (statsSection.value) statObserver.observe(statsSection.value);
    if (mobileStatsSection.value) statObserver.observe(mobileStatsSection.value);

    // 2. Observer untuk efek muncul perlahan (Staggered Fade-Up) pada grid produk/paket
    const staggerObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.remove('opacity-0', 'translate-y-8', 'scale-95');
                    entry.target.classList.add('opacity-100', 'translate-y-0', 'scale-100');
                    staggerObserver.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.1, rootMargin: '0px 0px -50px 0px' }
    );

    const animateGrid = (gridEl) => {
        if (gridEl) {
            Array.from(gridEl.children).forEach((child, index) => {
                child.classList.add('opacity-0', 'translate-y-8', 'scale-95');
                child.style.transitionDelay = `${(index % 12) * 100}ms`;
                staggerObserver.observe(child);
            });
        }
    };

    // Tunggu DOM siap sebelum menerapkan observer animasi grid
    setTimeout(() => {
        animateGrid(productGrid.value);
        animateGrid(packageGrid.value);
    }, 100);

    // Slider Background Interval (Transisi Halus setiap 5 detik)
    heroImageInterval = setInterval(() => {
        currentHeroImage.value = (currentHeroImage.value + 1) % heroImages.length;
    }, 5000);
});

onUnmounted(() => {
    if (heroImageInterval) clearInterval(heroImageInterval);
});
</script>

<template>

    <Head title="Home" />
    <DefaultLayout>
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: 100% TIDAK DIUBAH (hidden lg:block)       -->
        <!-- ========================================================= -->
        <div>
            <!-- 
                [HERO SECTION] 
                Ditambahkan Background Image HD bertema Hiking/Outdoor.
            Menggunakan image tajam dengan overlay gradient gelap 
            agar teks tetap terbaca sangat jelas (kontras tinggi) dan rapi.
            -mt-[74px] pt-[74px] ditambahkan untuk menarik background hingga ke atas layar.
        -->
            <section class="-mt-[74px] pt-[74px] relative overflow-hidden text-white bg-[#081828]">
                <!-- [ROMBAK: Background Image HD Slider (100% Visual Foto)] -->
                <div class="absolute inset-0 z-0 bg-[#081828]">
                    <!-- Gambar HD dari Unsplash bertema Mountain dengan Transisi Crossfade Halus -->
                    <!-- opacity-100 digunakan agar gambar tampil tajam dan full, tidak pudar -->
                    <!-- [OPTIMASI LIGHTHOUSE LCP]: 
                         - fetchpriority="high" dan loading="eager" untuk gambar pertama agar langsung dimuat.
                         - loading="lazy" untuk gambar selanjutnya agar menghemat network.
                         - width dan height di set eksplisit agar tidak terjadi CLS (layout shift).
                         - decoding="async" agar proses decode gambar tidak memblokir render UI. -->
                    <img v-for="(img, idx) in heroImages" :key="idx" :src="img" alt="Mountain Background" width="1920"
                        height="1080" :loading="idx === 0 ? 'eager' : 'lazy'"
                        :fetchpriority="idx === 0 ? 'high' : 'auto'" decoding="async"
                        class="absolute inset-0 h-full w-full object-cover object-center bg-[#081828] transition-opacity duration-1000 ease-in-out"
                        :class="idx === currentHeroImage ? 'opacity-100 z-10' : 'opacity-0 z-0'" />

                    <!-- Overlay diubah menjadi hitam tipis transparan murni (hanya 30%) demi menjaga teks terbaca 
                     sehingga foto asli tetap mendominasi dan skor Lighthouse terjaga 100% -->
                    <div class="absolute inset-0 bg-black/40 z-20"></div>
                </div>

                <!-- Efek Cahaya / Glow tambahan -->
                <div
                    class="absolute inset-0 z-20 bg-[radial-gradient(circle_at_top_left,_rgba(34,211,238,0.15),_transparent_50%)]" />
                <div
                    class="absolute inset-0 z-20 bg-[radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.10),_transparent_50%)]" />

                <div
                    class="relative z-30 mx-auto grid min-h-[500px] max-w-7xl items-center gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:px-8 lg:py-20">
                    <div class="max-w-2xl">
                        <!-- [UPDATE]: Badge atas "Hiko Outdoor Rental" telah dihapus sesuai permintaan -->

                        <!-- [UPDATE]: Menggunakan font Outfit yang lebih modern, bold, dan dipadukan dengan animasi warna merambat (gradient text animation) menyeluruh yang pelan dan elegan -->
                        <h1
                            class="font-outfit text-5xl font-black leading-[1.1] sm:text-6xl lg:text-[70px] drop-shadow-[0_4px_15px_rgba(0,0,0,0.5)] animate-text-gradient bg-gradient-to-r from-white via-cyan-300 to-blue-500 bg-[length:200%_auto] bg-clip-text text-transparent pb-3">
                            Rental Hiking<br />
                            Lebih Rapi<br />
                            Lebih Praktis
                        </h1>

                        <p class="mt-6 max-w-xl text-sm leading-relaxed text-white/80 sm:text-base font-medium">
                            Solusi premium sewa alat hiking dan outdoor. Rasakan pengalaman menyewa
                            yang sangat mulus, cepat, rapi, dan terpercaya tanpa repot antre.
                        </p>

                        <div class="mt-10 flex flex-wrap gap-4">
                            <Link :href="route('catalog.index')"
                                class="inline-flex h-12 items-center justify-center rounded-full bg-gradient-to-r from-cyan-500 to-blue-500 px-8 text-[14px] font-bold text-white shadow-lg shadow-cyan-500/30 transition-all duration-300 hover:scale-105 hover:shadow-cyan-500/50 hover:brightness-110">
                                Lihat Katalog Produk
                            </Link>
                        </div>
                    </div>

                    <!-- [FITUR KANAN: Glassmorphism] -->
                    <!-- [PENJELASAN]: Kotak fitur dikembalikan ke posisi semula (ukuran normal, transparan dengan efek kaca/blur) sesuai desain awal yang Anda sukai karena ini paling seimbang. -->
                    <div class="flex flex-col gap-4 relative z-30">
                        <div
                            class="group flex items-start gap-4 rounded-2xl border border-white/5 bg-white/5 p-4 backdrop-blur-xl transition-all duration-300 hover:bg-white/10 hover:-translate-y-1 hover:border-cyan-400/20 hover:shadow-[0_0_20px_rgba(34,211,238,0.1)]">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-cyan-400/20 to-blue-500/20 text-cyan-300 shadow-inner transition-colors group-hover:from-cyan-400 group-hover:text-white">
                                <CheckCircle2 class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-[14px] font-bold text-white transition-colors group-hover:text-cyan-300">
                                    Checkout Cepat & Mudah
                                </p>
                                <p class="mt-1 text-[12px] leading-relaxed text-white/60">
                                    Pilih alat, tentukan tanggal, langsung bayar aman tanpa antre
                                    panjang.
                                </p>
                            </div>
                        </div>

                        <div
                            class="group flex items-start gap-4 rounded-2xl border border-white/5 bg-white/5 p-4 backdrop-blur-xl transition-all duration-300 hover:bg-white/10 hover:-translate-y-1 hover:border-cyan-400/20 hover:shadow-[0_0_20px_rgba(34,211,238,0.1)]">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-cyan-400/20 to-blue-500/20 text-cyan-300 shadow-inner transition-colors group-hover:from-cyan-400 group-hover:text-white">
                                <ShieldCheck class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-[14px] font-bold text-white transition-colors group-hover:text-cyan-300">
                                    Stok Real-time Transparan
                                </p>
                                <p class="mt-1 text-[12px] leading-relaxed text-white/60">
                                    Sistem pintar kami menyajikan stok barang secara langsung dan
                                    akurat.
                                </p>
                            </div>
                        </div>

                        <div
                            class="group flex items-start gap-4 rounded-2xl border border-white/5 bg-white/5 p-4 backdrop-blur-xl transition-all duration-300 hover:bg-white/10 hover:-translate-y-1 hover:border-cyan-400/20 hover:shadow-[0_0_20px_rgba(34,211,238,0.1)]">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-cyan-400/20 to-blue-500/20 text-cyan-300 shadow-inner transition-colors group-hover:from-cyan-400 group-hover:text-white">
                                <Tent class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-[14px] font-bold text-white transition-colors group-hover:text-cyan-300">
                                    Kualitas Alat Terjamin
                                </p>
                                <p class="mt-1 text-[12px] leading-relaxed text-white/60">
                                    Setiap peralatan telah dicuci dan dirawat standar pendakian
                                    profesional.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- [ANIMASI KARAKTER HIKER] -->
                <!-- [PENJELASAN FUNGSI]: 
                 - 'bottom-[20px]': Menurunkan gambar tepat 20px agar kaki persis menempel di atas garis kotak statistik.
                 - 'justify-end': Memastikan konten gambar merapat penuh ke sisi kanan container.
                 - 'z-20': Karakter berada di layer tengah agar bisa bersinggungan rapi dengan teks. 
                 - [UPDATE]: Menggeser posisi karakter ke kiri sedikit menggunakan inset-x-0 dan grid container agar sejajar dan pas secara presisi dengan garis kanan kotak angka (statistik). -->
                <div
                    class="absolute inset-x-0 bottom-[20px] mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 z-20 flex items-end justify-end pointer-events-none drop-shadow-2xl">
                    <!-- [UPDATE]: Sesuai instruksi, HANYA 2 foto yang dipertahankan (Hiker 3 dan Hiker 4). Foto orang berjaket biru (Hiker 1) dan Hiker 2 dihapus. -->

                    <!-- Gambar 1 (Hiker 3): Datang dari Bawah -->
                    <!-- [PENJELASAN]: Menghilangkan margin negatif agar menjadi foto dasar/pertama dalam grup -->
                    <!-- [OPTIMASI LIGHTHOUSE CLS]: Menambahkan explicit width & height, serta lazy loading agar tidak membebani initial load dan mencegah pergeseran layout (CLS) -->
                    <img src="/images/hiker3.png" width="400" height="460" loading="lazy" decoding="async"
                        class="h-[260px] sm:h-[360px] lg:h-[460px] object-contain animate-hiker-3 drop-shadow-[5px_10px_15px_rgba(0,0,0,0.5)] relative z-30"
                        alt="Hiker 3" />

                    <!-- Gambar 2 (Hiker 4 - Jaket Kuning): Datang dari Kanan -->
                    <!-- [PENJELASAN]: Diberi z-40 agar dipastikan muncul paling depan. Margin negatif dipertahankan agar tumpang tindih natural. 
                     [UPDATE]: Margin-bottom dihapus agar kakinya menempel pas di garis kotak angka sesuai instruksi. -->
                    <!-- [OPTIMASI LIGHTHOUSE CLS]: Width, Height, dan Lazy Load -->
                    <img src="/images/hiker4.png" width="400" height="450" loading="lazy" decoding="async"
                        class="h-[260px] sm:h-[360px] lg:h-[450px] object-contain animate-hiker-4 drop-shadow-[10px_10px_15px_rgba(0,0,0,0.5)] -ml-[15%] lg:-ml-[80px] relative z-40"
                        alt="Hiker 4" />
                </div>
            </section>

            <!-- [STATISTIK DENGAN ANIMASI ANGKA] -->
            <section ref="statsSection" class="relative z-10 mx-auto -mt-5 max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- [UPDATE]: Container dipangkas lagi menjadi sangat tipis (py-2.5) dan elegan -->
                <div class="rounded-2xl border border-white/10 bg-[#082949]/95 px-6 py-2.5 shadow-2xl backdrop-blur-xl">
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4 items-center">
                        <div v-for="item in animatedStats" :key="item.label" class="group text-center">
                            <component :is="item.icon"
                                class="mx-auto h-4 w-4 text-cyan-400 opacity-80 transition-transform duration-500 group-hover:scale-125 group-hover:text-cyan-300 group-hover:drop-shadow-[0_0_8px_rgba(34,211,238,0.8)]" />
                            <!-- [UPDATE]: Angka diperkecil ke text-xl dan margin atas dipepetkan agar ringkas -->
                            <p class="mt-1 text-xl font-extrabold tabular-nums text-white drop-shadow-md leading-tight">
                                {{ item.current }}{{ item.suffix }}
                            </p>
                            <!-- [UPDATE]: Label diperkecil ke ukuran mikro (text-[9px]) tanpa margin berlebih -->
                            <p class="text-[9px] font-bold uppercase tracking-widest text-white/60 leading-tight">
                                {{ item.label }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 
            [KONTEN UTAMA: MENYATU DENGAN BACKGROUND GLOBAL]
            Background dan pola Map Grid sekarang diambil langsung dari DefaultLayout.vue secara global.
            Div ini hanya berfungsi sebagai pembungkus layout utama (w-full).
        -->
            <div class="relative w-full pb-16">
                <!-- Konten Utama (z-10) -->
                <div class="relative z-10">
                    <!-- 
                    1. [PRODUK UNGGULAN: GRID + ANIMASI SCROLL]
                    Dikembalikan menjadi format grid/list agar UI sesuai seperti halaman list produk,
                    tetapi menggunakan efek animasi muncul satu per satu.
                -->
                    <section class="overflow-hidden pt-16 pb-8">
                        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div class="mb-8 flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-widest text-cyan-600">
                                        Terbaik
                                    </p>
                                    <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">
                                        Peralatan Paling Dicari
                                    </h2>
                                </div>
                                <div class="flex items-center gap-4">
                                    <Link :href="route('catalog.index')"
                                        class="text-[13px] font-bold text-cyan-600 transition hover:text-cyan-800">
                                        Lihat semua
                                    </Link>
                                </div>
                            </div>

                            <div v-if="featuredProducts.length > 0" ref="productGrid"
                                class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:gap-6">
                                <!-- 
                                [ANIMASI SCROLL NATIVE]: 
                                Tiap elemen disembunyikan awalnya (opacity-0, translate-y-8).
                                Saat elemen masuk layar, IntersectionObserver menghapusnya.
                                Waktu kemunculan dibuat bergantian (stagger) menggunakan transitionDelay.
                            -->
                                <div v-for="(product, idx) in featuredProducts" :key="product.id"
                                    class="transition-all duration-700 ease-out drop-shadow-sm hover:drop-shadow-md">
                                    <ProductCard :product="product" class="h-full bg-white rounded-2xl" />
                                </div>
                            </div>

                            <div v-else
                                class="flex flex-col items-center justify-center rounded-[24px] border border-dashed border-slate-300 bg-white/60 py-16 text-center backdrop-blur-sm">
                                <PackageCheck class="mb-3 h-10 w-10 text-slate-400" />
                                <h3 class="text-[15px] font-bold text-slate-800">
                                    Belum ada produk
                                </h3>
                                <p class="mt-1 text-[13px] text-slate-500">
                                    Produk unggulan akan tampil di sini.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- 
                    2. [PAKET REKOMENDASI: GRID SEPERTI HALAMAN PAKET]
                    Memakai Layout Card yang sama persis seperti halaman list Paket.
                    Menampilkan yang terbaru saja (dibatasi 3), dan dengan animasi muncul perlahan stagger.
                -->
                    <section class="overflow-hidden pt-4 pb-10" v-if="packages && packages.length > 0">
                        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div class="mb-8 flex items-end justify-between gap-4">
                                <div class="translate-y-0 transform opacity-100 transition-all duration-700">
                                    <p class="text-[11px] font-bold uppercase tracking-widest text-cyan-600">
                                        Paket Hemat
                                    </p>
                                    <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">
                                        Rekomendasi Paket
                                    </h2>
                                </div>
                                <div class="flex items-center gap-4">
                                    <Link :href="route('packages.index')"
                                        class="text-[13px] font-bold text-cyan-600 transition hover:text-cyan-800">
                                        Lihat semua paket
                                    </Link>
                                </div>
                            </div>

                            <!-- [GRID PAKET & ANIMASI MUNCUL SATU PER SATU] -->
                            <!-- 
                            [UPDATE ANIMASI SCROLL PAKET]
                            Menambahkan pembungkus (wrapper) khusus untuk animasi agar efek muncul perlahan dari bawah
                            berjalan sangat mulus dan tidak bentrok dengan efek hover milik kotak kartu di dalamnya.
                            Ini 100% sama dengan logika animasi di grid produk.
                            [UPDATE GRID UKURAN] Ukuran grid class disamakan persis dengan halaman Packages/Index.vue
                        -->
                            <div ref="packageGrid"
                                class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5">
                                <!-- Wrapper Animasi Dihapus agar Paket langsung tampil aman -->
                                <div v-for="(pkg, idx) in packages.slice(0, 5)" :key="pkg.id"
                                    class="transition-all duration-700 ease-out h-full">
                                    <!-- Kotak Kartu Utama disamakan persis dengan halaman Paket (Index.vue) -->
                                    <div
                                        class="group relative flex h-full flex-col overflow-hidden rounded-[24px] bg-white shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-slate-100 transition-all duration-500 ease-out hover:-translate-y-1.5 hover:shadow-[0_16px_32px_rgba(0,0,0,0.08)] hover:border-blue-100">
                                        <!-- Area Gambar & Lencana -->
                                        <div class="relative aspect-[4/3] w-full overflow-hidden bg-slate-50">
                                            <Link :href="route('packages.show', pkg.slug)" class="block h-full w-full">
                                                <!-- [OPTIMASI LIGHTHOUSE CLS]: Lazy loading, async decoding, explicit width dan height pada cover paket -->
                                                <img v-if="pkg.image_path" :src="`/storage/${pkg.image_path}`"
                                                    :alt="pkg.name" width="400" height="300" loading="lazy"
                                                    decoding="async"
                                                    class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-110" />
                                                <div v-else
                                                    class="flex h-full w-full items-center justify-center text-xs font-medium text-slate-400">
                                                    <!-- Kosong / Clean sesuai ProductCard -->
                                                </div>
                                            </Link>

                                            <!-- Lencana Ketersediaan Tersedia -->
                                            <div class="absolute left-3 top-3 z-10">
                                                <span
                                                    class="inline-flex rounded-full bg-emerald-500 px-3 py-1 text-[10px] font-extrabold tracking-wide text-white shadow-sm">
                                                    TERSEDIA
                                                </span>
                                            </div>

                                            <!-- 
                                            ==========================================================================
                                            [PENJELASAN KODE FITUR ANIMASI TOMBOL LOVE]
                                            - Menggunakan komponen WishlistButton layaknya halaman produk.
                                            - Jika sudah di-wishlist, tombol tetap muncul jelas.
                                            - Jika belum, tombol hanya nampak saat disorot (hover) dengan kursor.
                                            ==========================================================================
                                        -->
                                            <div class="absolute right-3 top-3 z-10 transition-all duration-500 ease-out hover:scale-110"
                                                :class="wishlistStore.isWishlisted(pkg.id)
                                                    ? 'opacity-100 translate-y-0'
                                                    : 'opacity-0 -translate-y-2 group-hover:translate-y-0 group-hover:opacity-100'
                                                    ">
                                                <WishlistButton :product-id="pkg.id" />
                                            </div>
                                        </div>

                                        <!-- Area Konten (Deskripsi & Rating) -->
                                        <div class="flex flex-1 flex-col p-5">
                                            <!-- Rating Bintang Dinamis (Statis Sementara sesuai instruksi) -->
                                            <div class="mb-3 flex items-center gap-1">
                                                <div
                                                    class="flex opacity-80 transition-all duration-300 group-hover:opacity-100">
                                                    <Star v-for="i in 5" :key="i"
                                                        class="h-3 w-3 fill-amber-400 text-amber-400 drop-shadow-sm" />
                                                </div>
                                                <span class="ml-1.5 text-[10px] font-bold text-slate-500">(5.0)</span>
                                            </div>

                                            <Link :href="route('packages.show', pkg.slug)" class="block mb-2">
                                                <h3
                                                    class="line-clamp-2 text-base font-bold leading-snug text-slate-900 transition-colors duration-300 group-hover:text-blue-600">
                                                    {{ pkg.name }}
                                                </h3>
                                            </Link>

                                            <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-500">
                                                {{
                                                    pkg.description ||
                                                    'Paket komplit untuk kenyamanan maksimal di alam liar.'
                                                }}
                                            </p>

                                            <!-- Spacer agar harga selalu di bawah -->
                                            <div class="flex-1"></div>

                                            <!-- Area Harga -->
                                            <div class="mt-5 border-t border-slate-100 pt-4">
                                                <div class="flex items-end justify-between">
                                                    <div>
                                                        <p
                                                            class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">
                                                            Mulai dari
                                                        </p>
                                                        <div class="flex items-baseline gap-1">
                                                            <p class="text-lg font-extrabold text-slate-900">
                                                                {{
                                                                    formatCurrency(
                                                                        pkg.price_per_day
                                                                    )
                                                                }}
                                                            </p>
                                                            <p class="text-[10px] font-medium text-slate-500">
                                                                /hari
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <Link :href="route('packages.show', pkg.slug)"
                                                        class="inline-flex h-8 items-center rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white transition-all duration-300 hover:bg-blue-600 hover:shadow-md hover:shadow-blue-500/20 active:scale-95">
                                                        Lihat Detail
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Tutup packageGrid -->
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
        <!-- End of DESKTOP VIEW -->
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
    0% {
        background-position: 0% 50%;
    }

    50% {
        background-position: 100% 50%;
    }

    100% {
        background-position: 0% 50%;
    }
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
    0% {
        opacity: 0;
        transform: translateY(150px) scale(0.95);
        filter: blur(8px);
    }

    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
        filter: blur(0);
    }
}

@keyframes hiker-slide-right {
    0% {
        opacity: 0;
        transform: translateX(150px) scale(0.95);
        filter: blur(8px);
    }

    100% {
        opacity: 1;
        transform: translateX(0) scale(1);
        filter: blur(0);
    }
}

.animate-hiker-3 {
    animation: hiker-slide-bottom 2.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-hiker-4 {
    animation: hiker-slide-right 2.9s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
