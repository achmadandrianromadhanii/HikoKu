<!--
    ==========================================================================
    [KOMPONEN MOBILE]: MobileNavbar.vue
    ==========================================================================
    - FUNGSI: Header atas khusus tampilan Mobile (layar < 1024px).
      Menggantikan AppNavbar yang hanya tampil di Desktop.
    - TATA LETAK: Fixed di bagian atas layar, tinggi 56px (h-14).
      Berisi logo kecil, kotak pencarian (AppAutocomplete), dan ikon user.
    - CARA KERJA: Komponen ini hanya dimuat oleh DefaultLayout.vue
      di dalam blok "flex lg:hidden" sehingga TIDAK PERNAH tampil di Desktop.
    - KEAMANAN: Tidak mengubah logika/data apapun. Hanya menampilkan UI.
    ==========================================================================
-->
<script setup>
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { User, ShoppingCart } from 'lucide-vue-next'
import AppAutocomplete from '@/Components/common/AppAutocomplete.vue'
import { useWishlistStore } from '@/stores/wishlist'

const page = usePage()
const wishlistStore = useWishlistStore()

// Komentar: Mengambil data user dari props Inertia (sama seperti AppNavbar Desktop)
const user = computed(() => page.props.auth?.user || null)
const isLoggedIn = computed(() => !!user.value)

// Komentar: State untuk kotak pencarian autocomplete
const search = ref('')

// Komentar: Mengambil jumlah item keranjang untuk badge (jika dibutuhkan)
const cartCount = computed(() => page.props.cart?.count || 0)

// Komentar: Logo website
const brandLogo = '/images/logo.png'
const logoError = ref(false)

// Komentar: Fungsi untuk mendapatkan avatar user dengan resolusi tinggi
const getHighResAvatar = (path) => {
    if (!path) return null
    if (path.startsWith('http')) {
        if (path.includes('googleusercontent.com') && path.includes('=s')) {
            return path.split('=s')[0] + '=s400-c'
        }
        return path
    }
    return `/storage/${path}`
}

// Komentar: URL profil dengan error handling
const profileUrl = computed(() => {
    try { return route('profile.edit') }
    catch { return route('home') }
})
</script>

<template>
    <!--
        [MOBILE NAVBAR]: Header atas fixed, tinggi 56px.
        Background gelap transparan dengan efek kaca (backdrop-blur-md transform-gpu).
        Konsisten secara visual dengan tema Desktop (Dark Navy).
    -->
    <header class="fixed inset-x-0 top-0 z-50 h-14 border-b border-white/10 bg-[#031632]/95 backdrop-blur-md transform-gpu will-change-transform">
        <div class="flex h-full items-center gap-3 px-3">

            <!-- Komentar: Logo kecil di pojok kiri -->
            <Link :href="route('home')" class="shrink-0">
                <img
                    v-if="!logoError"
                    :src="brandLogo"
                    alt="Logo"
                    class="h-8 w-auto object-contain drop-shadow-[0_2px_8px_rgba(0,0,0,0.5)]"
                    @error="logoError = true"
                />
                <div v-else class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-cyan-500 to-blue-500 text-xs font-bold text-white">
                    H
                </div>
            </Link>

            <!-- Komentar: Kotak pencarian smart autocomplete (menggunakan komponen yang sudah ada) -->
            <div class="relative z-[90] min-w-0 flex-1">
                <AppAutocomplete v-model="search" placeholder="Cari produk..." />
            </div>

            <!-- Komentar: Ikon user / avatar di pojok kanan -->
            <Link :href="isLoggedIn ? profileUrl : route('login')" class="shrink-0">
                <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full border-2 border-white/20 bg-slate-700 transition-all duration-200 active:scale-95">
                    <img
                        v-if="isLoggedIn && user?.avatar"
                        :src="getHighResAvatar(user.avatar)"
                        alt="Avatar"
                        class="h-full w-full object-cover"
                    />
                    <User v-else class="h-4 w-4 text-white/70" />
                </div>
            </Link>
        </div>
    </header>
</template>
