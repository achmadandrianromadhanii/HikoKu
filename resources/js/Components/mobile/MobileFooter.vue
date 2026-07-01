<!--
    ==========================================================================
    [KOMPONEN MOBILE]: MobileFooter.vue
    ==========================================================================
    - FUNGSI: Footer ringkas khusus untuk tampilan Mobile (layar < 1024px).
      Menggantikan AppFooter Desktop yang memuat elemen berat (Peta Leaflet).
    - TATA LETAK: Berada di paling bawah halaman, memiliki padding-bottom ekstra
      (pb-24) agar kontennya tidak tertutup oleh MobileBottomNav.
    - CARA KERJA: Hanya dirender di dalam blok "flex lg:hidden".
      Tidak memuat library peta pihak ketiga demi menjaga performa (LCP) Mobile.
    - KEAMANAN: Link navigasi mengarah ke rute yang valid.
    ==========================================================================
-->
<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Instagram, Facebook, Music2 } from 'lucide-vue-next'

const page = usePage()
const settings = computed(() => page.props.settings?.public || {})

// Komentar: Mengambil data nama dan tagline dari setting global (sama seperti Desktop)
const brandName = computed(() => settings.value.app_name || 'Hiko')
const brandTagline = computed(() => settings.value.app_tagline || 'Outdoor Equipment Rental')
const brandLogo = '/images/logo.png'
const logoError = ref(false)

// [UPDATE]: Menyiapkan array gambar pegunungan HD untuk background footer agar sama persis dengan Desktop
const footerImages = [
    'https://images.unsplash.com/photo-1542332213-9b5a5a3fad35?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80',
    'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80',
    'https://images.unsplash.com/photo-1519681393784-d120267933ba?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80'
]
const currentFooterImage = ref(0)
let footerImageInterval = null

onMounted(() => {
    // Slider interval untuk background foto berganti setiap 5 detik
    footerImageInterval = setInterval(() => {
        currentFooterImage.value = (currentFooterImage.value + 1) % footerImages.length
    }, 5000)
})

onUnmounted(() => {
    if (footerImageInterval) clearInterval(footerImageInterval)
})

// Komentar: Tahun dinamis untuk copyright
const currentYear = new Date().getFullYear()
</script>

<template>
    <!--
        [MOBILE FOOTER]: Footer statis yang sangat ringan.
        - bg-[#031632]: Warna Dark Navy (konsisten dengan Navbar & Desktop).
        - pb-24: Padding ekstra di bawah agar tidak tertutup BottomNav (64px) + Safe Area.
        - Hanya berisi Logo, Sosmed, Copyright, dan Link statis.
    -->
    <!--
        [PENJELASAN KODE UPDATE]:
        - `z-[15]`: Footer ditaruh di z-index 15, lebih tinggi dari mobile wrapper (z-10)
          dan jauh di atas lapisan dekoratif DefaultLayout (z-0). Ini memastikan TIDAK ADA
          warna/glow/pattern dari layout yang bisa menutupi footer di halaman manapun
          (Home, Katalog, Keranjang, dll).
        - `isolate`: Membuat stacking context baru agar z-index internal footer
          (gambar, teks) tidak bentrok dengan elemen lain di luar footer.
        - `bg-black`: Warna dasar hitam paling bawah sebagai blocker. Warna ini
          100% tertutup oleh gambar slider pegunungan di atasnya.
    -->
    <footer class="relative isolate z-[15] px-5 pb-24 pt-10 text-white/90 overflow-hidden bg-black">
        <!-- [PENJELASAN KODE]: Container gambar background pegunungan slider -->
        <div class="absolute inset-0 z-[1]">
            <!--
                [PENJELASAN KODE UPDATE]:
                Menggunakan array 3 gambar pegunungan (footerImages) dengan v-for.
                Transisi opacity (duration-1000) membuat efek fade halus saat berganti gambar.
                TIDAK ADA overlay warna (bg-black/40 dihapus) agar gambar 100% jernih.
            -->
            <img v-for="(img, index) in footerImages" 
                 :key="index"
                 :src="img" 
                 alt="Mountain" 
                 class="absolute inset-0 h-full w-full object-cover object-center transition-opacity duration-1000"
                 :class="currentFooterImage === index ? 'opacity-100' : 'opacity-0'" />
        </div>
        
        <!-- [PENJELASAN KODE]: Container konten footer (logo, tagline, sosmed, copyright) -->
        <div class="relative z-[2] flex flex-col items-center text-center">

            <!-- Komentar: Logo dan Nama Brand -->
            <Link :href="route('home')" class="mb-4 inline-block">
                <img
                    v-if="!logoError"
                    :src="brandLogo"
                    alt="Logo"
                    class="h-8 w-auto object-contain opacity-100 drop-shadow-md transition-opacity active:opacity-100"
                    @error="logoError = true"
                />
                <span v-else class="text-xl font-bold tracking-wider text-white">{{ brandName }}</span>
            </Link>

            <p class="mb-6 max-w-[250px] text-[13px] font-semibold leading-relaxed text-white drop-shadow-sm">
                {{ brandTagline }}
            </p>

            <!-- Komentar: Ikon Sosial Media (Ringkas) -->
            <div class="mb-8 flex items-center justify-center gap-5">
                <!-- [PENJELASAN KODE UPDATE]: Menggunakan bg-white/10 dan border border-white/20 agar bulatannya terlihat jelas seperti di screenshot -->
                <a href="#" aria-label="Instagram" class="rounded-full border border-white/20 bg-white/10 p-2.5 text-white backdrop-blur-sm transition active:scale-95 active:bg-white/20 active:text-cyan-400">
                    <Instagram class="h-4 w-4" />
                </a>
                <a href="#" aria-label="Facebook" class="rounded-full border border-white/20 bg-white/10 p-2.5 text-white backdrop-blur-sm transition active:scale-95 active:bg-white/20 active:text-cyan-400">
                    <Facebook class="h-4 w-4" />
                </a>
                <a href="#" aria-label="TikTok" class="rounded-full border border-white/20 bg-white/10 p-2.5 text-white backdrop-blur-sm transition active:scale-95 active:bg-white/20 active:text-cyan-400">
                    <Music2 class="h-4 w-4" />
                </a>
            </div>

            <!-- Komentar: Link Kebijakan dan Ketentuan -->
            <div class="mb-6 flex flex-wrap justify-center gap-x-4 gap-y-2 text-[11px] font-bold tracking-wide text-white drop-shadow-sm">
                <Link href="#" class="transition active:text-cyan-400">Syarat & Ketentuan</Link>
                <span>&bull;</span>
                <Link href="#" class="transition active:text-cyan-400">Kebijakan Privasi</Link>
            </div>

            <!-- Komentar: Copyright -->
            <p class="text-[10px] font-medium text-white/70">
                &copy; {{ currentYear }} {{ brandName }}. All rights reserved.
            </p>

        </div>
    </footer>
</template>
