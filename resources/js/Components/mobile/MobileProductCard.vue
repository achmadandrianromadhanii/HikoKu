<!--
    ==========================================================================
    [KOMPONEN MOBILE]: MobileProductCard.vue
    ==========================================================================
    - FUNGSI: Kartu produk versi kompak untuk grid 2 kolom di layar Mobile.
      Menampilkan gambar, nama (max 2 baris), harga, rating, stok, dan wishlist.
    - TATA LETAK: Kartu kecil dengan gambar rasio 4:3, padding ringkas (p-2.5).
    - CARA KERJA: Menerima props `product` (Object) yang identik dengan
      ProductCard.vue Desktop. Data yang sama, tampilan berbeda.
    - KEAMANAN: Tidak mengubah data/logika. WishlistButton yang dipakai
      adalah komponen yang sudah ada (tidak membuat baru).
    ==========================================================================
-->
<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Star } from 'lucide-vue-next'
import WishlistButton from '@/Components/ui/WishlistButton.vue'

// Komentar: Props produk, identik dengan ProductCard.vue Desktop
const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
})

// Komentar: Mengambil gambar utama produk (prioritas is_primary, fallback gambar pertama)
const imageUrl = computed(() => {
    const image =
        props.product?.images?.find((item) => item.is_primary) ||
        props.product?.images?.[0]
    return image?.image_path ? `/storage/${image.image_path}` : null
})

// Komentar: Menghitung total stok (jika ada varian, jumlahkan semua stok varian)
const totalStock = computed(() => {
    if (props.product?.variants?.length > 0) {
        return props.product.variants.reduce((sum, v) => sum + Number(v.stock || 0), 0)
    }
    return Number(props.product?.stock || 0)
})

// Komentar: Status ketersediaan berdasarkan stok
const isAvailable = computed(() => totalStock.value > 0)

// Komentar: Format harga ke Rupiah Indonesia
const formattedPrice = computed(() => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(props.product?.price_per_day || 0))
})

// Komentar: Rata-rata rating produk
const averageRating = computed(() => {
    const reviews = props.product?.reviews || []
    if (reviews.length === 0) return 0
    const total = reviews.reduce((sum, r) => sum + Number(r.rating || 0), 0)
    return (total / reviews.length).toFixed(1)
})

// Komentar: Fallback jika gambar gagal dimuat
const imgError = ref(false)

// [UPDATE]: Fungsi untuk langsung Add to Cart dari tampilan grid mobile
const addToCart = () => {
    // Mengecek apakah produk memiliki varian (ukuran/warna)
    const realVariants = props.product?.variants?.filter(v => v.size || v.color) || []
    
    // Jika ada varian, arahkan ke halaman detail produk agar user bisa memilih varian
    if (realVariants.length > 0) {
        router.visit(route('products.show', props.product.slug))
        return
    }

    // Jika tidak ada varian, langsung tembak data ke backend keranjang (Cart)
    router.post(route('cart.store'), {
        item_type: 'product',
        product_id: props.product.id,
        quantity: 1
    }, {
        preserveScroll: true,
        preserveState: true,
    })
}
</script>

<template>
    <!--
        ======================================================================
        [MOBILE PRODUCT CARD - MAHA KARYA]: Kartu Ringkas & Premium (Dark Glass)
        ======================================================================
        - Desain: Dark Glassmorphism (bg-slate-800/90 backdrop-blur-sm transform-gpu) dengan border tipis.
        - Gambar: Proporsi 1:1 (Square) agar gambar terlihat padat tanpa memakan tinggi layar.
        - Typography: Nama produk 1 baris (line-clamp-1) text-[11px].
        - Interaksi: Active scale [0.96] memberi efek haptic 60fps.
    -->
    <div class="group relative overflow-hidden rounded-[16px] border border-white/10 bg-slate-800/90 shadow-[0_4px_16px_rgba(0,0,0,0.4)] backdrop-blur-sm transform-gpu will-change-transform transition-all duration-300 ease-[cubic-bezier(0.175,0.885,0.32,1.275)] active:scale-[0.96]">
        
        <!-- Bagian Atas: Gambar Produk (Rasio 1:1) -->
        <!-- [UPDATE]: Menggunakan aspect-square agar tidak terlalu tinggi -->
        <Link :href="route('products.show', product.slug)" class="relative block aspect-square w-full overflow-hidden bg-slate-900">
            <img
                v-if="imageUrl && !imgError"
                :src="imageUrl"
                :alt="product.name"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                loading="lazy"
                @error="imgError = true"
            />
            <div v-else class="flex h-full w-full items-center justify-center text-[10px] font-medium text-slate-300">
                No Image
            </div>

            <!-- Overlay Gelap Halus di Bawah Gambar untuk Keterbacaan -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>

            <!-- Tombol Wishlist Melayang Kanan Atas -->
            <div class="absolute right-1.5 top-1.5 z-10">
                <WishlistButton :product-id="product.id" size="sm" />
            </div>

            <!-- Badge Stok Habis Kiri Atas -->
            <span
                v-if="!isAvailable"
                class="absolute left-1.5 top-1.5 rounded-[6px] bg-rose-500/95 px-2 py-0.5 text-[9px] font-black tracking-widest text-white shadow-sm backdrop-blur-sm"
            >
                HABIS
            </span>
            
            <!-- Badge Rating Kiri Bawah (Di Atas Overlay Gambar) -->
            <div v-if="Number(averageRating) > 0" class="absolute left-1.5 bottom-1.5 flex items-center gap-0.5 rounded-full bg-black/40 px-1.5 py-0.5 backdrop-blur-md">
                <Star class="h-[10px] w-[10px] fill-amber-400 text-amber-400" />
                <span class="text-[9px] font-bold text-white">{{ averageRating }}</span>
            </div>
        </Link>

        <!-- Bagian Bawah: Informasi Singkat (Padat) -->
        <!-- [UPDATE]: Padding dikurangi dari p-2.5 menjadi p-2 agar lebih compact -->
        <div class="p-2">
            <!-- Nama Produk (Hanya 1 Baris) -->
            <Link :href="route('products.show', product.slug)">
                <!-- [UPDATE]: Warna teks disesuaikan dengan Dark Mode -->
                <h3 class="line-clamp-1 text-[11px] font-extrabold tracking-tight text-slate-200 transition-colors group-hover:text-cyan-400">
                    {{ product.name }}
                </h3>
            </Link>

            <div class="mt-1.5 flex items-end justify-between">
                <!-- Info Harga & Stok -->
                <div class="flex flex-col leading-none">
                    <p class="text-[9px] font-medium text-slate-500 mb-0.5">Mulai</p>
                    <div class="flex items-baseline gap-0.5">
                        <span class="text-[13px] font-black text-cyan-400">{{ formattedPrice }}</span>
                    </div>
                </div>

                <!-- Tombol Add to Cart (+) Langsung dari Grid -->
                <!-- [UPDATE]: Menggunakan @click.prevent.stop agar benar-benar terisolasi dari link kartu produk -->
                <!-- [UPDATE]: Membesarkan ukuran tombol menjadi h-9 w-9 (36px) agar lebih mudah disentuh (touch-friendly) dan tidak tumpang tindih -->
                <button @click.prevent.stop="addToCart" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyan-500 text-slate-900 shadow-[0_0_15px_rgba(6,182,212,0.6)] transition-transform active:scale-90">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
                </button>
            </div>
        </div>
    </div>
</template>
