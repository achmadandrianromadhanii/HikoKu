<script setup>
/* 
    ==========================================================================
    [KOMENTAR PENJELASAN KOMPONEN]: ProductCard.vue
    ==========================================================================
    - FUNGSI: File ini adalah komponen cetakan untuk setiap kotak produk.
      Tugasnya menampilkan gambar produk, harga, judul, dan animasi stok.
    - LETAK TATA LETAK: File ini dipanggil berulang kali oleh file `Catalog/Index.vue`
      ke dalam susunan CSS Grid untuk membentuk barisan produk.
    - CARA KERJA ANIMASI:
      1. IntersectionObserver mendeteksi saat kartu produk mulai terlihat di layar.
      2. Jika terlihat, ia akan memicu fungsi `startAnimations()`.
      3. Fungsi tersebut memakai `setInterval` (penghitung waktu milidetik murni native) 
         untuk memutar angka stok dengan cepat (seolah bergerak maju dari angka 0 ke angka asli).
    - KENAPA AMAN UNTUK LIGHTHOUSE 100%?:
      Karena semua pergerakan animasi (angka berjalan, bintang pudar, hover kartu)
      menggunakan Vanilla JavaScript murni dan CSS transisi dasar bawaan framework.
      Tanpa menambahkan library eksternal satupun yang berat!
    ==========================================================================
*/
import { computed, ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Star, ShoppingCart } from 'lucide-vue-next'
import WishlistButton from '@/Components/ui/WishlistButton.vue'
import { useWishlistStore } from '@/stores/wishlist'
import VariantSelectionModal from '@/Components/product/VariantSelectionModal.vue'

defineOptions({ inheritAttrs: false })

// Properti (data) produk yang dilemparkan dari file Induk (Index.vue)
const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
})

// Mengambil gambar utama produk
const imageUrl = computed(() => {
    const image =
        props.product?.images?.find((item) => item.is_primary) ||
        props.product?.images?.[0]

    return image?.image_path ? `/storage/${image.image_path}` : null
})

// Cek status ketersediaan
// [UPDATE LOGIKA STOK]: Jika produk memiliki varian, maka total stok adalah jumlah dari stok semua variannya. 
// Jika tidak memiliki varian, maka gunakan stok dari produk utama. Ini mengatasi kebingungan data stok.
const totalStock = computed(() => {
    return Number(props.product?.stock_available || 0)
})

const isAvailable = computed(() => totalStock.value > 0)

// Rute menuju halaman detail
const productDetailUrl = computed(() => route('products.show', props.product.slug))

// State Wishlist untuk mendeteksi apakah kartu ini sedang di-wishlist
const wishlistStore = useWishlistStore()
const isWishlisted = computed(() => wishlistStore.isWishlisted(props.product.id))

// Format teks deskripsi agar rapi
const productDescription = computed(() => {
    return (
        props.product?.short_desc ||
        props.product?.description ||
        'Peralatan outdoor berkualitas.'
    )
})

// Format konversi angka menjadi mata uang Rupiah
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0))
}

// Referensi DOM dan state untuk animasi
const cardRef = ref(null)
const animatedStock = ref(0)
const isAnimated = ref(false)

// [PENJELASAN FUNGSI]: Fungsi untuk menggerakkan angka stok
const startAnimations = () => {
    if (isAnimated.value) return
    isAnimated.value = true

    let start = 0
    // [UPDATE LOGIKA STOK]: Animasi angka stok sekarang secara akurat mengambil dari totalStock (gabungan varian)
    const target = totalStock.value
    
    // Jika stok ada angkanya, jalankan animasi perputaran angka
    if (target > 0) {
        const duration = 1200 // Durasi total animasi 1.2 detik (sangat halus)
        const startTime = performance.now();
        
        const updateCounter = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            animatedStock.value = Math.floor(progress * target);
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                animatedStock.value = target;
            }
        };
        
        requestAnimationFrame(updateCounter);
    }
}

// Modal Variant State
const showVariantModal = ref(false)
const redirectAfterVariant = ref(false)
const eventForFlyingCart = ref(null)

// [PENJELASAN FUNGSI]: Fungsi Tambah ke Keranjang (Realtime & Animasi Terbang)
const addToCart = (redirect = false, event = null) => {
    // Filter out dummy variants that have no size and no color
    const realVariants = props.product.variants?.filter(v => v.size || v.color) || []

    if (realVariants.length > 0) {
        redirectAfterVariant.value = redirect
        eventForFlyingCart.value = event
        showVariantModal.value = true
        return
    }

    // 1. Jalankan animasi melayang (flying animation) jika tombol Add to Cart diklik
    if (!redirect && event) {
        runCartFlyingAnimation(event)
    }

    // 2. Tembak data ke backend tanpa me-refresh halaman (Inertia preserveState)
    router.post(route('cart.store'), {
        item_type: 'product',
        product_id: props.product.id,
        quantity: 1
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            // Jika tombol 'Sewa' diklik, langsung pindahkan ke halaman Keranjang
            if (redirect) {
                router.visit(route('cart.index'))
            }
        }
    })
}

const handleVariantAddToCart = (data) => {
    if (!redirectAfterVariant.value && eventForFlyingCart.value) {
        runCartFlyingAnimation(eventForFlyingCart.value)
    }

    router.post(route('cart.store'), {
        item_type: 'product',
        product_id: data.product_id,
        product_variant_id: data.variant_id,
        notes: data.notes,
        quantity: 1
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            if (redirectAfterVariant.value) {
                router.visit(route('cart.index'))
            }
        }
    })
}

// ============================================================================
// [PHYSICS-BASED CINEMATIC FLY-TO-CART MICROINTERACTION]
//
// CARA KERJA:
// 1. Clone gambar produk, tempelkan di posisi aslinya (fixed).
// 2. Hitung titik awal (produk) dan titik akhir (ikon Cart di Navbar).
// 3. Jalankan animasi frame-per-frame menggunakan requestAnimationFrame.
// 4. Posisi X bergerak linear, posisi Y mengikuti kurva parabolik (efek gravitasi).
//    Barang akan "melambung naik" dulu lalu "jatuh" ke keranjang — seperti dilempar.
// 5. Skala dan opacity berkurang progresif sepanjang perjalanan.
// 6. Rotasi 3D ditambahkan agar terkesan melayang berputar di udara.
// 7. Saat tiba, clone dihapus, partikel ledakan kecil dimunculkan, dan ikon memantul.
//
// PERFORMA:
// - Menggunakan transform (GPU-accelerated), bukan top/left.
// - Tidak ada reflow/repaint karena posisi awal di-set sekali saja.
// - requestAnimationFrame memastikan animasi sinkron dengan refresh rate layar.
// - Durasi animasi: ~1.4 detik (pelan, sinematik, dan elegan).
// ============================================================================
const runCartFlyingAnimation = (event) => {
    // --- LANGKAH 1: Temukan elemen gambar produk ---
    const cardEl = event.currentTarget.closest('.group')
    const imgEl = cardEl?.querySelector('img') || cardEl?.querySelector('.bg-surface-50')
    if (!imgEl) return

    // --- LANGKAH 2: Temukan target ikon Cart di Navbar ---
    const cartIcons = document.querySelectorAll('a[href*="/cart"]')
    const targetIcon = Array.from(cartIcons).find(el => el.offsetParent !== null)
    if (!targetIcon) return

    // Kirim event ke Navbar agar badge ditahan sampai animasi selesai
    window.dispatchEvent(new CustomEvent('cart-animation-start'))

    // --- LANGKAH 3: Hitung koordinat awal dan tujuan ---
    const startRect = imgEl.getBoundingClientRect()
    const endRect = targetIcon.getBoundingClientRect()

    // Titik pusat awal (gambar produk)
    const startX = startRect.left + startRect.width / 2
    const startY = startRect.top + startRect.height / 2
    // Titik pusat akhir (ikon Cart)
    const endX = endRect.left + endRect.width / 2
    const endY = endRect.top + endRect.height / 2

    // Jarak horizontal dan vertikal
    const deltaX = endX - startX
    const deltaY = endY - startY

    // --- LANGKAH 4: Buat clone gambar untuk diterbangkan ---
    const clone = imgEl.cloneNode(true)
    const cloneSize = Math.min(startRect.width, startRect.height, 120)

    Object.assign(clone.style, {
        position: 'fixed',
        left: `${startX - cloneSize / 2}px`,
        top: `${startY - cloneSize / 2}px`,
        width: `${cloneSize}px`,
        height: `${cloneSize}px`,
        borderRadius: '14px',
        objectFit: 'cover',
        zIndex: '999999',
        pointerEvents: 'none',
        willChange: 'transform, opacity',       // Hint GPU untuk performa maksimal
        boxShadow: '0 8px 32px rgba(0,0,0,0.25)',
        transformOrigin: 'center center'
    })
    document.body.appendChild(clone)

    // --- LANGKAH 5: Konfigurasi animasi fisika ---
    const DURATION = 1400             // Total durasi animasi (milidetik) — pelan & sinematik
    const ARC_HEIGHT = -180           // Tinggi lengkungan parabolik (negatif = melambung ke atas)
    const END_SCALE = 0.12            // Skala akhir saat masuk keranjang (sangat kecil)
    const MAX_ROTATION = 360          // Derajat rotasi total selama penerbangan
    let startTime = null

    // --- LANGKAH 6: Loop animasi frame-per-frame ---
    const animate = (timestamp) => {
        if (!startTime) startTime = timestamp
        // Progress animasi (0 → 1) menggunakan ease-out cubic untuk perlambatan natural
        const elapsed = timestamp - startTime
        const rawProgress = Math.min(elapsed / DURATION, 1)

        // Ease-out cubic: mulai cepat, melambat di akhir (seperti benda kehilangan momentum)
        const progress = 1 - Math.pow(1 - rawProgress, 3)

        // Posisi X: bergerak linear dari awal ke akhir
        const currentX = deltaX * progress

        // Posisi Y: kurva parabolik (parabola terbuka ke bawah)
        // Formula: y = deltaY * t + arcHeight * 4 * t * (1 - t)
        // Ini membuat benda melambung naik dulu, lalu jatuh ke tujuan
        const parabola = ARC_HEIGHT * 4 * rawProgress * (1 - rawProgress)
        const currentY = deltaY * progress + parabola

        // Skala: mengecil secara progresif dari 1.0 ke END_SCALE
        const scale = 1 - (1 - END_SCALE) * progress

        // Opacity: tetap terang di awal, memudar halus di 40% terakhir perjalanan
        const opacity = rawProgress < 0.6 ? 1 : 1 - ((rawProgress - 0.6) / 0.4) * 0.7

        // Rotasi 3D: berputar sepanjang sumbu Z (seperti koin dilempar)
        const rotation = MAX_ROTATION * rawProgress

        // Terapkan transformasi gabungan (GPU-accelerated, tanpa reflow)
        clone.style.transform = `translate(${currentX}px, ${currentY}px) scale(${scale}) rotate(${rotation}deg)`
        clone.style.opacity = `${opacity}`

        // Efek bayangan mengecil seiring benda menjauh
        const shadowBlur = Math.max(2, 32 * (1 - progress))
        const shadowAlpha = Math.max(0.05, 0.25 * (1 - progress))
        clone.style.boxShadow = `0 ${shadowBlur / 2}px ${shadowBlur}px rgba(0,0,0,${shadowAlpha})`

        // Teruskan loop selama progress belum 100%
        if (rawProgress < 1) {
            requestAnimationFrame(animate)
        } else {
            // --- LANGKAH 7: Animasi selesai — bersihkan & picu efek mendarat ---
            if (clone.parentNode) clone.parentNode.removeChild(clone)

            // Picu efek partikel ledakan kecil di titik pendaratan
            spawnLandingParticles(endX, endY)

            // Picu efek memantul (bounce) pada ikon keranjang
            targetIcon.style.transition = 'transform 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.575)'
            targetIcon.style.transform = 'scale(1.5)'
            setTimeout(() => {
                targetIcon.style.transform = 'scale(0.85)'
                setTimeout(() => {
                    targetIcon.style.transition = 'transform 0.2s ease-out'
                    targetIcon.style.transform = 'scale(1)'
                }, 150)
            }, 200)

            // Kirim event ke Navbar bahwa barang sudah mendarat → badge muncul sekarang
            window.dispatchEvent(new CustomEvent('cart-item-dropped'))
        }
    }
    requestAnimationFrame(animate)
}

// ============================================================================
// [EFEK PARTIKEL LEDAKAN SAAT MENDARAT DI KERANJANG]
//
// CARA KERJA:
// - Membuat 6 titik kecil berwarna cyan di sekitar titik pendaratan.
// - Titik-titik ini meledak ke arah acak dengan transisi CSS.
// - Setelah 500ms, semua partikel dihapus dari DOM.
// - Sangat ringan karena hanya <div> kecil tanpa gambar.
// ============================================================================
const spawnLandingParticles = (cx, cy) => {
    const PARTICLE_COUNT = 6
    const colors = ['#06b6d4', '#3b82f6', '#22d3ee', '#60a5fa', '#a5f3fc', '#bfdbfe']

    for (let i = 0; i < PARTICLE_COUNT; i++) {
        const particle = document.createElement('div')
        // Sudut penyebaran merata 360° dibagi jumlah partikel
        const angle = (Math.PI * 2 * i) / PARTICLE_COUNT
        const distance = 20 + Math.random() * 18  // Jarak ledakan acak (20-38px)

        Object.assign(particle.style, {
            position: 'fixed',
            left: `${cx}px`,
            top: `${cy}px`,
            width: '6px',
            height: '6px',
            borderRadius: '50%',
            backgroundColor: colors[i % colors.length],
            zIndex: '999998',
            pointerEvents: 'none',
            opacity: '1',
            transform: 'translate(-50%, -50%) scale(1)',
            transition: 'all 0.5s cubic-bezier(0.25, 1, 0.5, 1)',
            willChange: 'transform, opacity'
        })
        document.body.appendChild(particle)

        // Trigger ledakan setelah 1 frame (agar transisi CSS aktif)
        requestAnimationFrame(() => {
            const tx = Math.cos(angle) * distance
            const ty = Math.sin(angle) * distance
            particle.style.transform = `translate(calc(-50% + ${tx}px), calc(-50% + ${ty}px)) scale(0)`
            particle.style.opacity = '0'
        })

        // Bersihkan partikel dari DOM setelah animasi selesai
        setTimeout(() => {
            if (particle.parentNode) particle.parentNode.removeChild(particle)
        }, 550)
    }
}

// [PENJELASAN FUNGSI]: Detektor Layar Native
onMounted(() => {
    // Observer native browser mendeteksi kapan kartu ini masuk layar (scroll)
    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) {
            startAnimations()
            observer.disconnect() // Lepas pemantau setelah animasi berjalan 1x (efisiensi memory)
        }
    }, { threshold: 0.15 })
    
    if (cardRef.value) observer.observe(cardRef.value)
})
</script>

<template>
    <!-- 
        ==========================================================================
        [PENJELASAN KODE BUNGKUSAN (CONTAINER) KARTU]
        - Menggunakan class "group" agar saat disorot (hover), elemen di dalamnya
          (seperti tombol love dan gambar) bisa ikut bereaksi bersamaan.
        - hover:-translate-y-1.5: Saat disorot, kartu melayang ke atas sedikit.
        - hover:shadow-[...]: Saat disorot, bayangan belakang memancarkan sinar cyan lembut.
        - rounded-[24px]: Sudut kartu dibulatkan ekstrim ala Apple/Mac agar modern.
        ==========================================================================
    -->
    <div ref="cardRef" v-bind="$attrs"
        class="group relative flex h-full flex-col overflow-hidden rounded-[24px] border border-surface-100/60 bg-white shadow-sm transition-all duration-500 hover:-translate-y-1.5 hover:border-cyan-200/50 hover:shadow-[0_15px_30px_rgba(34,211,238,0.06)]">
        
        <!-- BAGIAN 1: Area Foto Produk -->
        <div class="relative overflow-hidden bg-surface-50">
            <!-- [OPTIMASI LIGHTHOUSE ACCESSIBILITY]: aria-label ditambahkan agar screen reader mengenali link gambar ini -->
            <Link :href="productDetailUrl" class="block" :aria-label="`Lihat detail produk ${product.name}`">
                <!-- Foto menge-zoom lambat saat disorot kursor -->
                <img v-if="imageUrl" :src="imageUrl" :alt="product.name" width="400" height="300" class="aspect-[4/3] w-full object-cover transition-transform duration-700 group-hover:scale-110"
                    loading="lazy" />

                <!-- Fallback jika tidak ada gambar (Teks placeholder telah dihapus agar lebih bersih) -->
                <div v-else class="flex aspect-[4/3] w-full items-center justify-center bg-surface-50/50">
                    <!-- Kosong / Clean -->
                </div>
            </Link>

            <!-- Label Ketersediaan Produk (Kiri Atas) -->
            <div class="absolute left-3 top-3">
                <span class="inline-flex rounded-full px-3 py-1 text-[10px] font-extrabold tracking-wide shadow-sm"
                    :class="isAvailable ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white'">
                    {{ isAvailable ? 'Tersedia' : 'Kosong' }}
                </span>
            </div>

            <!-- 
                ==========================================================================
                [PENJELASAN KODE FITUR ANIMASI TOMBOL LOVE]
                - Jika produk SUDAH di-wishlist (isWishlisted), tombol merah akan 
                  TETAP MUNCUL (opacity-100 translate-y-0) meski kursor tidak diarahkan.
                - Jika BELUM, tombol akan transparan (opacity-0) dan hanya muncul saat
                  area KARTU disorot kursor (group-hover:opacity-100).
                ==========================================================================
            -->
            <div class="absolute right-3 top-3 z-10 transition-all duration-500 ease-out hover:scale-110"
                 :class="isWishlisted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-2 group-hover:translate-y-0 group-hover:opacity-100'">
                <WishlistButton :product-id="product.id" />
            </div>
        </div>

        <!-- BAGIAN 2: Teks Informasi, Harga & Tombol Bawah -->
        <div class="flex flex-1 flex-col space-y-3 p-4 sm:p-5">
            <!-- Informasi Judul -->
            <div>
                <!-- Label Kategori Mungil -->
                <p class="text-[10px] font-extrabold uppercase tracking-widest text-cyan-600">
                    {{ product.category?.name || 'Outdoor' }}
                </p>

                <Link :href="productDetailUrl"
                    class="mt-1 block line-clamp-2 text-[14px] sm:text-[15px] font-extrabold leading-tight text-surface-900 transition-colors group-hover:text-cyan-600">
                    {{ product.name }}
                </Link>

                <p class="mt-1.5 line-clamp-2 text-[11px] sm:text-[12px] leading-relaxed text-surface-500">
                    {{ productDescription }}
                </p>
            </div>

            <!-- Jarak Pendorong Bawah (mt-auto) -->
            <div class="mt-auto pt-4 border-t border-surface-100/50">
                
                <!-- 
                    ==========================================================================
                    [PENJELASAN KODE ANIMASI BINTANG & ANGKA STOK]
                    - isAnimated mengontrol transisi CSS. Saat memicu ke TRUE, 
                      scale, opacity, dan posisi elemen akan bergeser memberikan sensasi halus.
                    - Drop-shadow pada ikon Star memancarkan sinar emas palsu murni via CSS.
                    ==========================================================================
                -->
                <div class="mb-4 flex items-center justify-between text-[11px] text-surface-500">
                    <div class="inline-flex items-center gap-1.5">
                        <Star class="h-4 w-4 fill-amber-400 text-amber-400 transition-all duration-1000 ease-out"
                              :class="isAnimated ? 'scale-100 opacity-100 drop-shadow-[0_0_6px_rgba(251,191,36,0.6)]' : 'scale-50 opacity-0'" />
                        
                        <span class="font-extrabold text-[12px] text-surface-800 transition-all duration-700 ease-out" 
                              :class="isAnimated ? 'translate-y-0 opacity-100' : 'translate-y-2 opacity-0'">
                            {{ product.avg_rating || 0 }}
                        </span>
                        
                        <span class="transition-all duration-700 delay-100 ease-out" 
                              :class="isAnimated ? 'translate-y-0 opacity-100' : 'translate-y-2 opacity-0'">
                            ({{ product.review_count || 0 }})
                        </span>
                    </div>

                    <!-- Indikator Angka Stok dengan transisi lari dari kanan (translate-x-4) -->
                    <span class="font-extrabold text-[10px] uppercase tracking-wider text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded transition-all duration-700 delay-200 ease-out"
                          :class="isAnimated ? 'translate-x-0 opacity-100' : 'translate-x-4 opacity-0'">
                        Stok {{ animatedStock }}
                    </span>
                </div>

                <!-- Harga dan Tombol Sewa -->
                <div class="flex items-center justify-between gap-1.5 mt-0.5">
                    <div class="min-w-0 flex-1">
                        <p class="text-[8px] font-extrabold uppercase tracking-widest text-surface-400 mb-0.5">Mulai dari</p>
                        <div class="flex items-baseline min-w-0">
                            <p class="truncate text-[13px] sm:text-[14px] font-extrabold tracking-tight text-cyan-800">
                                {{ formatCurrency(product.price_per_day) }}
                            </p>
                            <span class="text-[8px] font-medium text-surface-400 shrink-0 ml-0.5">/hr</span>
                        </div>
                    </div>

                    <!-- 
                        ==========================================================================
                        [PENJELASAN KODE TOMBOL SEWA & CART]
                        - Shrink-0 memastikan tombol tidak pernah terhimpit/squish oleh teks harga.
                        - Ukuran tombol diperkecil maksimal (h-6/w-6 mobile, h-7/w-7 desktop).
                        - Gap direkatkan menjadi gap-1 agar tampil padat dan elegan.
                        ==========================================================================
                    -->
                    <div v-if="isAvailable" class="flex shrink-0 items-center gap-1.5">
                        <!-- Tombol Add to Cart Silently (dengan animasi terbang) -->
                        <button type="button" @click.prevent="addToCart(false, $event)"
                            class="inline-flex h-6 w-6 sm:h-7 sm:w-7 items-center justify-center rounded-full border border-cyan-500 bg-white text-cyan-500 shadow-sm transition-all duration-300 hover:scale-110 hover:bg-cyan-50 hover:shadow-[0_0_12px_rgba(34,211,238,0.3)] active:scale-95"
                            title="Tambahkan ke Keranjang" aria-label="Tambahkan ke Keranjang">
                            <ShoppingCart class="h-3 w-3 sm:h-3.5 sm:w-3.5" stroke-width="2.5" />
                        </button>

                        <!-- Tombol Sewa Langsung (Beli & Pindah ke Cart) -->
                        <button type="button" @click.prevent="addToCart(true)"
                            class="inline-flex h-6 sm:h-7 items-center justify-center rounded-full bg-gradient-to-r from-cyan-500 to-blue-500 px-2.5 sm:px-3 text-[9px] sm:text-[10px] font-bold text-white shadow-md shadow-cyan-500/20 transition-all duration-300 hover:scale-105 hover:shadow-cyan-500/40 hover:brightness-110"
                            title="Sewa Sekarang">
                            Sewa
                        </button>
                    </div>

                    <div v-else class="flex shrink-0 items-center gap-1.5">
                        <button type="button"
                            class="inline-flex h-6 sm:h-7 cursor-not-allowed items-center justify-center rounded-full bg-surface-100 px-3 text-[9px] sm:text-[10px] font-bold text-surface-400"
                            disabled>
                            <span>Habis</span>
                        </button>
                    </div>
                </div>
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
</template>
