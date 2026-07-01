<!--
    ==========================================================================
    [KOMPONEN MOBILE]: MobileBottomNav.vue
    ==========================================================================
    - FUNGSI: Navigation bar tetap di bawah layar, layaknya aplikasi mobile asli.
      Berisi 5 tab navigasi: Home, Katalog, Keranjang, Wishlist, Akun.
    - TATA LETAK: Fixed di bottom layar, tinggi 64px (h-16).
      Latar belakang kaca (Glassmorphism) dengan bayangan halus ke atas.
    - CARA KERJA: Mendeteksi route aktif melalui Inertia.js route().current()
      untuk menyalakan warna tab yang sedang dibuka.
    - KEAMANAN: Tidak mengubah logika/data apapun. Hanya navigasi UI.
    ==========================================================================
-->
<script setup>
import { computed, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Home, PackageSearch, ShoppingCart, Heart, User } from 'lucide-vue-next'
import { useWishlistStore } from '@/stores/wishlist'

const page = usePage()
const wishlistStore = useWishlistStore()

// Komentar: Mengambil data user untuk menentukan link tab Akun (profil atau login)
const user = computed(() => page.props.auth?.user || null)
const isLoggedIn = computed(() => !!user.value)

// Komentar: Badge angka untuk keranjang dan wishlist
const cartCount = computed(() => page.props.cart?.count || 0)
const wishlistCount = computed(() => wishlistStore.productIds.length)

// Komentar: State animasi bounce saat badge berubah
const isCartBounce = ref(false)
const isWishBounce = ref(false)

watch(cartCount, (n, o) => {
    if (n > o) {
        isCartBounce.value = true
        setTimeout(() => { isCartBounce.value = false }, 400)
    }
})

watch(wishlistCount, (n, o) => {
    if (n !== o) {
        isWishBounce.value = true
        setTimeout(() => { isWishBounce.value = false }, 400)
    }
})

// Komentar: Fungsi untuk mendeteksi apakah tab sedang aktif
// berdasarkan nama route Inertia.js yang sedang ditampilkan
const isActive = (routeNames) => {
    try {
        return routeNames.some(name => route().current(name))
    } catch {
        return false
    }
}

// Komentar: Konfigurasi 5 tab bottom navigation
const tabs = computed(() => [
    {
        label: 'Home',
        icon: Home,
        href: route('home'),
        active: isActive(['home']),
        badge: 0,
    },
    {
        label: 'Katalog',
        icon: PackageSearch,
        href: route('catalog.index'),
        active: isActive(['catalog.index', 'products.show']),
        badge: 0,
    },
    {
        label: 'Keranjang',
        icon: ShoppingCart,
        href: route('cart.index'),
        active: isActive(['cart.index']),
        badge: cartCount.value,
        bounce: isCartBounce.value,
    },
    {
        label: 'Wishlist',
        icon: Heart,
        href: route('wishlist.index'),
        active: isActive(['wishlist.index']),
        badge: wishlistCount.value,
        bounce: isWishBounce.value,
    },
    {
        label: 'Akun',
        icon: User,
        href: isLoggedIn.value ? route('profile.edit') : route('login'),
        active: isActive(['profile.edit', 'my-rentals.index', 'my-rentals.show']),
        badge: 0,
    },
])
</script>

<template>
    <!--
        ======================================================================
        [MOBILE BOTTOM NAV - MAHA KARYA]: Kapsul Melayang (Floating Glass Capsule)
        ======================================================================
        - Desain dinaikkan (bottom-4) agar melayang indah dari layar bawah.
        - Memiliki batas melengkung penuh (rounded-[24px]).
        - Glassmorphism level tinggi: bg-slate-900/80 dengan backdrop-blur-2xl.
        - Lebar 100% namun ada margin di pinggir (mx-4).
    -->
    <div class="fixed inset-x-0 bottom-4 z-50 px-4 pointer-events-none" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
        <nav class="mx-auto flex h-[60px] max-w-lg items-stretch justify-around rounded-[24px] border border-white/10 bg-slate-900/80 shadow-[0_8px_32px_rgba(0,0,0,0.25)] backdrop-blur-2xl pointer-events-auto">

            <!-- Loop 5 Tab Navigasi -->
            <Link
                v-for="tab in tabs"
                :key="tab.label"
                :href="tab.href"
                class="relative flex flex-1 flex-col items-center justify-center gap-0.5 transition-all duration-300 ease-[cubic-bezier(0.175,0.885,0.32,1.275)] active:scale-[0.92]"
                :class="tab.active ? 'text-white' : 'text-slate-400 hover:text-slate-200'"
            >
                <div class="relative">
                    <component
                        :is="tab.icon"
                        class="h-[22px] w-[22px] transition-all duration-300"
                        :class="[
                            tab.active ? 'scale-110 drop-shadow-[0_0_8px_rgba(34,211,238,0.6)] text-cyan-400' : 'scale-100',
                            tab.bounce ? 'animate-bounce-badge' : ''
                        ]"
                    />

                    <!-- Badge Merah untuk Keranjang / Wishlist -->
                    <span
                        v-if="tab.badge > 0"
                        class="absolute -right-2.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-extrabold leading-none text-white shadow-[0_2px_4px_rgba(244,63,94,0.4)]"
                        :class="tab.bounce ? 'animate-bounce-badge' : ''"
                    >
                        {{ tab.badge > 99 ? '99+' : tab.badge }}
                    </span>
                </div>

                <span class="text-[9px] font-semibold tracking-wide transition-all duration-300" :class="tab.active ? 'text-cyan-300 opacity-100' : 'opacity-70'">
                    {{ tab.label }}
                </span>

                <!-- Titik Neon Nyala untuk Tab Aktif -->
                <span
                    v-if="tab.active"
                    class="absolute -bottom-1 h-1 w-1 rounded-full bg-cyan-400 shadow-[0_0_8px_rgba(34,211,238,0.8)] transition-transform duration-300"
                />
            </Link>
        </nav>
    </div>
</template>

<style scoped>
/* Komentar: Animasi bounce ringan untuk badge saat jumlah berubah */
@keyframes bounce-badge {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.25); }
}
.animate-bounce-badge {
    animation: bounce-badge 0.4s ease-out;
}
</style>
