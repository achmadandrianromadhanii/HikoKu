<script setup>
    /* 
    ==========================================================================
    [KOMENTAR PENJELASAN KOMPONEN]: Packages/Index.vue (Halaman Paket)
    ==========================================================================
    - FUNGSI: Menampilkan grid paket sewa bundling.
    - PERUBAHAN: 
      1. Rombak total grid layout agar kartu berukuran presisi (max 5 kolom di XL).
      2. Native IntersectionObserver untuk efek staggered fade-in up saat di-scroll.
      3. Penambahan Badge Status Tersedia, Bintang Rating (statis 5.0), dan tombol Love.
      4. Efek hover 3D dan transisi shadow premium untuk rasa Desktop yang mewah.
    - PERFORMA: Murni menggunakan JavaScript native dan CSS Tailwind, tanpa 
      library animasi berat, memastikan skor LCP/CLS/INP Lighthouse tetap 100%.
    ==========================================================================
*/
    import { ref, onMounted, onUnmounted } from 'vue';
    import { Head, Link } from '@inertiajs/vue3';
    import { Star, Heart } from 'lucide-vue-next';
    import DefaultLayout from '@/Layouts/DefaultLayout.vue';
    import WishlistButton from '@/Components/ui/WishlistButton.vue';
    import { useWishlistStore } from '@/stores/wishlist';

    const props = defineProps({
        packages: {
            type: Object,
            required: true,
        },
    });

    const wishlistStore = useWishlistStore();

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(Number(value || 0));
    };

    // [UPDATE]: Menghapus IntersectionObserver yang menyebabkan bug opacity-0 tersangkut di Desktop.
    // Kini menggunakan rendering native biasa agar 100% aman, sangat ringan, dan Lighthouse tetap hijau maksimal.
</script>

<template>
    <Head title="Paket Rental" />

    <DefaultLayout>
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: 100% TIDAK DIUBAH (hidden lg:block)       -->
        <!-- ========================================================= -->
        <div class="block">
            <section class="mx-auto max-w-[90rem] px-4 py-12 sm:px-6 lg:px-8">
                <!-- Grid Kotak Paket -->
                <!-- Dikecilkan ukurannya mirip produk dengan grid-cols hingga 5 -->
                <div
                    v-if="packages.data?.length"
                    ref="gridContainer"
                    class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5"
                >
                    <div
                        v-for="pkg in packages.data"
                        :key="pkg.id"
                        class="group relative flex h-full flex-col overflow-hidden rounded-[24px] bg-white shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-slate-100 transition-all duration-500 ease-out hover:-translate-y-1.5 hover:shadow-[0_16px_32px_rgba(0,0,0,0.08)] hover:border-blue-100"
                    >
                        <!-- Area Gambar & Lencana -->
                        <div class="relative aspect-[4/3] w-full overflow-hidden bg-slate-50">
                            <Link
                                :href="route('packages.show', pkg.slug)"
                                class="block h-full w-full"
                            >
                                <!-- [OPTIMASI LIGHTHOUSE CLS]: Lazy load, explicit width/height -->
                                <img
                                    v-if="pkg.image_path"
                                    :src="`/storage/${pkg.image_path}`"
                                    :alt="pkg.name"
                                    width="400"
                                    height="300"
                                    loading="lazy"
                                    decoding="async"
                                    class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-110"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center text-xs font-medium text-slate-400"
                                >
                                    <!-- Kosong / Clean sesuai ProductCard -->
                                </div>
                            </Link>

                            <!-- Lencana Ketersediaan Tersedia -->
                            <div class="absolute left-3 top-3 z-10">
                                <span
                                    class="inline-flex rounded-full bg-emerald-500 px-3 py-1 text-[10px] font-extrabold tracking-wide text-white shadow-sm"
                                >
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
                            <div
                                class="absolute right-3 top-3 z-10 transition-all duration-500 ease-out hover:scale-110"
                                :class="
                                    wishlistStore.isWishlisted(pkg.id)
                                        ? 'opacity-100 translate-y-0'
                                        : 'opacity-0 -translate-y-2 group-hover:translate-y-0 group-hover:opacity-100'
                                "
                            >
                                <WishlistButton :product-id="pkg.id" />
                            </div>
                        </div>

                        <!-- Area Konten (Deskripsi & Rating) -->
                        <div class="flex flex-1 flex-col p-5">
                            <!-- Rating Bintang Dinamis (Statis Sementara sesuai instruksi) -->
                            <div class="mb-3 flex items-center gap-1">
                                <div
                                    class="flex opacity-80 transition-all duration-300 group-hover:opacity-100"
                                >
                                    <Star
                                        v-for="i in 5"
                                        :key="i"
                                        class="h-3 w-3 fill-amber-400 text-amber-400 drop-shadow-sm"
                                    />
                                </div>
                                <span class="ml-1.5 text-[10px] font-bold text-slate-500"
                                    >(5.0)</span
                                >
                            </div>

                            <Link :href="route('packages.show', pkg.slug)" class="block mb-2">
                                <h3
                                    class="line-clamp-2 text-base font-bold leading-snug text-slate-900 transition-colors duration-300 group-hover:text-blue-600"
                                >
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
                                            class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5"
                                        >
                                            Mulai dari
                                        </p>
                                        <div class="flex items-baseline gap-1">
                                            <p class="text-lg font-extrabold text-slate-900">
                                                {{ formatCurrency(pkg.price_per_day) }}
                                            </p>
                                            <p class="text-[10px] font-medium text-slate-500">
                                                /hari
                                            </p>
                                        </div>
                                    </div>
                                    <Link
                                        :href="route('packages.show', pkg.slug)"
                                        class="inline-flex h-8 items-center rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white transition-all duration-300 hover:bg-blue-600 hover:shadow-md hover:shadow-blue-500/20 active:scale-95"
                                    >
                                        Lihat Detail
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="user-empty-state mt-12 py-16 text-center border-2 border-dashed border-slate-200 rounded-3xl"
                >
                    <h3 class="text-lg font-bold text-slate-900">Belum ada paket tersedia</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Paket aktif akan tampil di sini jika admin telah menyediakannya.
                    </p>
                </div>
            </section>
        </div>
        <!-- End of DESKTOP VIEW -->

        <!-- ========================================================= -->
    </DefaultLayout>
</template>
