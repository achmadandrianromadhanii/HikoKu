<script setup>
    import { Head, Link, router } from '@inertiajs/vue3';
    import DefaultLayout from '@/Layouts/DefaultLayout.vue';
    import ProductCard from '@/Components/product/ProductCard.vue';
    import { PackageSearch, ChevronLeft } from 'lucide-vue-next';
    import { onMounted } from 'vue';
    import { useWishlistStore } from '@/stores/wishlist';

    const props = defineProps({
        products: {
            type: Array,
            default: () => [],
        },
    });

    const wishlistStore = useWishlistStore();

    onMounted(() => {
        // [KOMENTAR PENJELASAN]: Matikan Notifikasi (Mark as Read)
        // Saat halaman ini berhasil dibuka, sistem akan memberitahu state manager
        // untuk menghilangkan badge merah di Navbar selamanya, sampai ada produk BARU lagi.
        wishlistStore.markAsRead();
    });
</script>

<template>
    <Head title="Wishlist" />

    <DefaultLayout>
        <!-- 
            [KOMENTAR PENJELASAN]: Wrapper Halaman Wishlist
            Padding atas ditambahkan secara signifikan (pt-16) agar halamannya diturunkan lagi 
            sesuai permintaan, sehingga memberikan ruang bernapas yang lebih lega dari atap browser.
        -->
        <section class="mx-auto max-w-7xl px-4 pt-4 lg:pt-16 pb-6 sm:px-6 lg:px-8">
            <!-- [KOMENTAR PENJELASAN]: Mobile Header Glass (Khusus Mobile) -->
            <div
                class="lg:hidden bg-slate-50 dark:bg-[#0B1120]/40 -mx-4 px-4 py-4 mb-6 shadow-sm dark:shadow-lg border-b border-slate-200 dark:border-white/10 flex items-center justify-between relative z-10 backdrop-blur-2xl"
            >
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="
                            () =>
                                window.history.length > 1
                                    ? window.history.back()
                                    : router.visit(route('home'))
                        "
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 dark:bg-white/5 border border-slate-300 dark:border-white/10 text-slate-700 dark:text-slate-300 active:scale-90 transition-transform"
                    >
                        <ChevronLeft class="h-5 w-5" />
                    </button>
                    <div>
                        <h1
                            class="text-[15px] font-black text-slate-800 dark:text-white tracking-tight"
                        >
                            Wishlist Saya
                        </h1>
                        <p class="text-[10px] font-bold text-cyan-700 dark:text-cyan-400 mt-0.5">
                            {{ products.length }} Barang Tersimpan
                        </p>
                    </div>
                </div>
            </div>

            <!-- 
                [KOMENTAR PENJELASAN]: Label "Wishlist" di Atas Samping Kiri (Desktop Saja)
                Didesain dengan font berbeda (font-serif & italic) sesuai permintaan, 
                agar memberikan sentuhan tipografi yang estetis dan berkelas.
            -->
            <div class="mb-4 hidden lg:flex justify-start">
                <span
                    class="inline-flex items-center rounded-full bg-cyan-50/50 px-4 py-1 text-[13px] tracking-[0.2em] text-cyan-800 shadow-sm ring-1 ring-inset ring-cyan-600/10 backdrop-blur-sm font-serif italic"
                >
                    Wishlist
                </span>
            </div>

            <!-- 
                [KOMENTAR PENJELASAN]: Grid Kotak Produk (Responsive & Proporsional)
                - Menggunakan grid-cols-2 untuk mobile.
                - sm:grid-cols-3, md:grid-cols-4, lg:grid-cols-5, xl:grid-cols-5 agar di layar lebar 
                  ukuran kotaknya menjadi KECIL, PAS, dan tidak melebar raksasa (proporsional).
                - `<TransitionGroup>` memberikan efek terbang mulus (fade & slide) saat produk dihapus (un-wishlist).
                - 'appear' memicu animasi elegan saat halaman pertama kali dimuat.
            -->
            <TransitionGroup
                v-if="products.length > 0"
                appear
                name="wishlist-list"
                tag="div"
                class="relative grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-5 lg:gap-5"
            >
                <div v-for="product in products" :key="product.id" class="w-full">
                    <ProductCard :product="product" />
                </div>
            </TransitionGroup>

            <!-- 
                [KOMENTAR PENJELASAN]: Empty State (Jika tidak ada produk di Wishlist)
                Didesain menggunakan gaya Premium Glassmorphism. Animasi kursor, pantulan, 
                gradasi lembut, dan tombol CTA (Call to Action) yang sangat interaktif dan mewah.
            -->
            <div v-else class="flex min-h-[50vh] items-center justify-center">
                <div
                    class="group relative flex flex-col items-center justify-center overflow-hidden rounded-[32px] border border-white/40 bg-white/30 px-6 py-16 text-center shadow-xl backdrop-blur-xl sm:px-12 transition-all duration-700 hover:bg-white/40 hover:shadow-2xl"
                >
                    <!-- Glow Latar Belakang -->
                    <div
                        class="absolute inset-0 -z-10 bg-gradient-to-br from-cyan-100/40 to-emerald-100/40 opacity-50 blur-3xl transition-opacity duration-700 group-hover:opacity-100"
                    ></div>

                    <!-- Ikon Premium dengan Animasi Goyang (Pulse/Bounce) -->
                    <div
                        class="relative mb-6 flex h-24 w-24 items-center justify-center rounded-[24px] bg-gradient-to-tr from-cyan-50 to-emerald-50 shadow-sm border border-white/60 transition-transform duration-500 group-hover:scale-110 group-hover:rotate-3"
                    >
                        <PackageSearch
                            class="h-10 w-10 text-cyan-700 drop-shadow-sm transition-transform duration-500 group-hover:text-cyan-500"
                        />
                        <div
                            class="absolute -right-1 -top-1 h-6 w-6 animate-ping rounded-full bg-teal-400 opacity-20"
                        ></div>
                    </div>

                    <h2 class="mb-3 text-[22px] font-extrabold tracking-tight text-slate-800">
                        Belum ada favorit
                    </h2>
                    <p
                        class="mb-8 max-w-[280px] text-[13px] font-medium leading-relaxed text-slate-500"
                    >
                        Koleksi produk impianmu masih kosong. Yuk jelajahi katalog kami dan simpan
                        peralatan outdoor terbaik untuk petualanganmu!
                    </p>

                    <!-- Tombol CTA Premium -->
                    <Link prefetch
                        :href="route('catalog.index')"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-gradient-to-r from-cyan-500 to-teal-500 px-8 text-[13px] font-bold tracking-wide text-white shadow-lg shadow-cyan-500/20 transition-all duration-300 hover:-translate-y-1 hover:shadow-cyan-500/40 hover:brightness-110"
                    >
                        Jelajahi Katalog
                    </Link>
                </div>
            </div>
        </section>
    </DefaultLayout>
</template>

<!-- 
    [KOMENTAR PENJELASAN]: CSS Animasi Mulus (Smooth Transitions)
    Digunakan oleh <TransitionGroup> untuk membuat kartu produk muncul dan hilang 
    dengan sangat elegan, tidak kaku, ringan, dan 100% menggunakan render GPU.
-->
<style scoped>
    /* Durasi dan jenis kelengkungan animasi pegas (bouncy) ringan */
    .wishlist-list-move,
    .wishlist-list-enter-active,
    .wishlist-list-leave-active {
        transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    /* Keadaan awal sebelum elemen masuk (terbang dari bawah) */
    .wishlist-list-enter-from {
        opacity: 0;
        transform: translateY(40px) scale(0.9);
    }

    /* Keadaan akhir setelah elemen keluar (mengecil dan pudar) */
    .wishlist-list-leave-to {
        opacity: 0;
        transform: scale(0.85);
    }

    /* Memastikan elemen yang keluar tidak mengganggu posisi elemen lain yang sedang bergeser merapat */
    .wishlist-list-leave-active {
        position: absolute;
        z-index: -10;
    }
</style>
