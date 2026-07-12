<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import {
    Menu,
    X,
    Heart,
    ShoppingCart,
    User,
    LogOut,
    PackageCheck,
    ChevronDown,
} from 'lucide-vue-next'
import AppAutocomplete from '@/Components/common/AppAutocomplete.vue'
import { useWishlistStore } from '@/stores/wishlist'

const page = usePage()
const wishlistStore = useWishlistStore()

const mobileOpen = ref(false)
const userMenuOpen = ref(false)
const search = ref('')
const logoError = ref(false)

// Realtime Clock State
const now = ref(new Date())
let clockTimer = null

const currentTime = computed(() => {
    return now.value.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    })
})

const currentDate = computed(() => {
    return now.value.toLocaleDateString('id-ID', {
        weekday: 'long', 
        day: '2-digit',  
        month: 'long',   
        year: 'numeric', 
    })
})

const cartCount = computed(() => page.props.cart?.count || 0)

// [KOMENTAR PENJELASAN]: Menarik data realtime jumlah produk yang di-wishlist
const wishlistCount = computed(() => wishlistStore.productIds.length)

// State khusus untuk memberikan animasi memantul ringan pada badge saat jumlah berubah
const isWishlistAnimating = ref(false)
const isCartAnimating = ref(false)

// [KOMENTAR PENJELASAN]: Sistem Badge Cerdas & Sinkronisasi Animasi untuk Keranjang (Cart)
// Badge angka hanya muncul jika ada item BARU yang ditambahkan, dan akan HILANG saat halaman cart dibuka.
// Kita menggunakan displayCartCount untuk melakukan Update Optimistik (Muncul tepat saat animasi produk masuk).
const displayCartCount = ref(0)
const isAnimating = ref(false)
const hasUnreadCart = ref(typeof window !== 'undefined' ? localStorage.getItem('cart_unread') === 'true' : false)

const handleCartAnimationStart = () => {
    isAnimating.value = true
}

const handleCartItemDropped = () => {
    isAnimating.value = false
    displayCartCount.value += 1
    
    hasUnreadCart.value = true
    if (typeof window !== 'undefined') localStorage.setItem('cart_unread', 'true')
    
    // Sinkronisasi data asli server jika ternyata sudah ter-update diam-diam
    if (cartCount.value >= displayCartCount.value) {
        displayCartCount.value = cartCount.value
    }

    // Picu efek pantulan (bounce) pada ikon keranjang
    isCartAnimating.value = true
    setTimeout(() => {
        isCartAnimating.value = false
    }, 400)
}

onMounted(() => {
    displayCartCount.value = cartCount.value
    if (typeof window !== 'undefined') {
        window.addEventListener('cart-animation-start', handleCartAnimationStart)
        window.addEventListener('cart-item-dropped', handleCartItemDropped)
    }
})

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('cart-animation-start', handleCartAnimationStart)
        window.removeEventListener('cart-item-dropped', handleCartItemDropped)
    }
})

watch(cartCount, (newVal, oldVal) => {
    // Jika tidak sedang ada animasi produk terbang, update badge segera secara normal
    if (!isAnimating.value) {
        displayCartCount.value = newVal
        if (newVal > oldVal) {
            hasUnreadCart.value = true
            if (typeof window !== 'undefined') localStorage.setItem('cart_unread', 'true')
            
            isCartAnimating.value = true
            setTimeout(() => {
                isCartAnimating.value = false
            }, 400)
        }
    }
})

// Deteksi jika user sedang berada di halaman keranjang
const isOnCartPage = computed(() => {
    try {
        return route().current('cart.index') || route().current('checkout.index')
    } catch(e) { return false }
})

watch(isOnCartPage, (isCart) => {
    // Jika user membuka halaman cart, anggap semua item sudah 'Terbaca', sembunyikan badge angka
    if (isCart) {
        hasUnreadCart.value = false
        if (typeof window !== 'undefined') localStorage.setItem('cart_unread', 'false')
    }
}, { immediate: true })

watch(wishlistCount, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        isWishlistAnimating.value = true
        setTimeout(() => {
            isWishlistAnimating.value = false
        }, 400) // Animasi berlangsung selama 400ms
    }
})

const user = computed(() => page.props.auth?.user || null)
const isLoggedIn = computed(() => !!user.value)

const firstName = computed(() => {
    if (!user.value?.name) return 'User'
    return String(user.value.name).split(' ')[0]
})

const brandName = 'hikoku'
const brandLogo = '/images/logo.webp'

const navLinks = [
    { label: 'Home', href: () => route('home'), current: ['home'] },
    { label: 'Produk', href: () => route('catalog.index'), current: ['catalog.index', 'products.show'] },
    { label: 'Paket', href: () => route('packages.index'), current: ['packages.index', 'packages.show'] },
    { label: 'Cara Sewa', href: () => route('how-to-rent.index'), current: ['how-to-rent.index'] },
    { label: 'FAQ', href: () => route('faq.index'), current: ['faq.index'] },
]

const profileUrl = computed(() => {
    try {
        return route('profile.edit')
    } catch (error) {
        return route('home')
    }
})

// [KOMENTAR PENJELASAN]: Helper untuk memaksa gambar Sosmed menjadi HD dan menangani path lokal dengan sempurna
const getHighResAvatar = (path) => {
    if (!path) return null;
    if (path.startsWith('http')) {
        // Paksa resolusi tinggi untuk Google
        if (path.includes('googleusercontent.com') && path.includes('=s')) {
            return path.split('=s')[0] + '=s800-c';
        }
        // Paksa resolusi tinggi untuk Github
        if (path.includes('githubusercontent.com') && !path.includes('s=')) {
            return path + (path.includes('?') ? '&s=800' : '?s=800');
        }
        return path;
    }
    return `/storage/${path}`;
}

const closeAllMenus = () => {
    mobileOpen.value = false
    userMenuOpen.value = false
}

const logout = () => {
    router.post(route('logout'))
}

const isCurrentRoute = (names) => {
    const list = Array.isArray(names) ? names : [names]

    try {
        return list.some((name) => route().current(name))
    } catch (error) {
        return false
    }
}

onMounted(() => {
    // Menjalankan jam secara realtime (setiap 1 detik update)
    clockTimer = setInterval(() => {
        now.value = new Date()
    }, 1000)
    
    // Pastikan koneksi Reverb/Echo (WebSockets) ikut aktif jika ada data broadcast
    if (typeof window !== 'undefined' && window.Echo) {
        // Echo connection test (Optional/Pasif)
    }
})

onUnmounted(() => {
    if (clockTimer) {
        clearInterval(clockTimer)
    }
})
</script>

<template>
    <header
        class="fixed inset-x-0 top-0 z-50 border-b border-white/5 bg-[#031632]/85 backdrop-blur-xl shadow-sm transition-all duration-300">
        <!-- 
            [ROMBAK UI/UX: NAVBAR HEADER] 
            Background linear-gradient dihapus, diganti dengan Solid Dark Blue berpadu Backdrop Blur (Kaca).
            Ini menghasilkan efek transparan elegan ala Apple / Glassmorphism.
        -->
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- 
                [UPDATE TATA LETAK & LOGO NAVBAR] 
                Tinggi navbar dikembalikan ke asalnya (h-20) agar rapi dan proporsional seperti semula.
            -->
            <div class="flex h-20 items-center justify-between gap-4">
                <!-- 
                    KOLOM 1: Identitas Logo 
                -->
                <div class="flex min-w-0 items-center gap-6 xl:gap-8">
                    <Link :href="route('home')" class="inline-flex shrink-0 items-center transition-transform duration-300 hover:scale-105" @click="closeAllMenus" aria-label="Beranda">
                        <!-- 
                            [UPDATE UKURAN & KEJERNIHAN LOGO]
                            - Container dikembalikan jadi h-16
                            - Logo dikembalikan jadi h-16 (Sesuai asalnya)
                            - TAPI shadow hitam elegan TETAP dipertahankan agar logo menonjol.
                            - Filter image-rendering DIHAPUS karena ternyata membuat gambar PNG/JPG menjadi pecah (pixelated).
                              Browser modern akan merender gambar secara otomatis dengan mulus (anti-aliasing).
                        -->
                        <div class="flex items-center justify-center h-16 w-auto overflow-visible">
                            <!-- Logo Navbar yang dikembalikan ke ukuran normal tapi sangat tajam karena shadow -->
                            <img :src="brandLogo" :alt="brandName" width="64" height="64"
                                 class="h-16 w-auto object-contain drop-shadow-[0_4px_12px_rgba(0,0,0,0.5)]" />
                        </div>
                    </Link>

                    <!-- Navigasi Teks Murni (Tanpa Grid/Background) -->
                    <nav class="hidden items-center gap-6 lg:flex">
                        <Link prefetch="hover" v-for="link in navLinks" :key="link.label" :href="link.href()"
                            class="text-[13px] font-medium tracking-wide transition-all duration-300" :class="isCurrentRoute(link.current)
                                ? 'text-cyan-400 drop-shadow-[0_0_8px_rgba(34,211,238,0.4)]'
                                : 'text-white/80 hover:text-cyan-300 hover:drop-shadow-[0_0_8px_rgba(34,211,238,0.3)]'
                                ">
                            {{ link.label }}
                        </Link>
                    </nav>
                </div>

                <!-- 
                    [FITUR PREMIUM]: Smart Search Area
                    [UPDATE]: Kotak pencarian kini diizinkan tampil di semua halaman.
                -->
                <div class="hidden min-w-0 flex-1 justify-center lg:flex">
                    <!-- Komentar: Lebar search dibatasi maksimal 250px secara statis sesuai permintaan agar tidak terlalu melar/lebar -->
                    <div class="w-full max-w-[250px] relative z-[90]">
                        <AppAutocomplete v-model="search" placeholder="Cari perlengkapan..." />
                    </div>
                </div>

                <!-- 
                    [REALTIME CLOCK WIDGET]
                    Ditambahkan widget jam realtime khusus untuk User/Guest persis seperti Admin.
                    Berfungsi sebagai referensi waktu yang konstan.
                -->
                <div class="hidden shrink-0 items-center border-l border-white/10 pl-5 lg:flex mr-2">
                    <div class="text-right">
                        <p class="text-[13px] font-bold text-cyan-400 drop-shadow-[0_0_8px_rgba(34,211,238,0.4)]">{{ currentTime }}</p>
                        <p class="text-[10px] uppercase tracking-widest text-white/60">{{ currentDate }}</p>
                    </div>
                </div>

                <!-- 
                    KOLOM 3: Aksi, Ikon, & Autentikasi 
                    Semua ikon dibebaskan dari kotak. Menggunakan Naked Icon dengan Hover Glow.
                -->
                <div class="hidden shrink-0 items-center gap-5 lg:flex">
                    <Link :href="route('wishlist.index')"
                        class="group relative text-white/70 transition-all duration-300 hover:scale-110 hover:text-cyan-400" aria-label="Wishlist">
                        <!-- Naked Wishlist Icon dengan Hover Glow -->
                        <Heart class="h-5 w-5 drop-shadow-sm group-hover:drop-shadow-[0_0_8px_rgba(34,211,238,0.6)]" />
                        
                        <!-- [KOMENTAR PENJELASAN]: Badge angka notifikasi cerdas.
                             Hanya muncul saat ada produk wishlist BARU (hasUnread = true). 
                             Akan hilang selamanya setelah user membuka halaman wishlist, persis seperti notifikasi. -->
                        <span v-if="wishlistCount > 0 && wishlistStore.hasUnread"
                            :class="{'animate-bounce': isWishlistAnimating}"
                            class="absolute -right-2 -top-2 inline-flex h-4 min-w-[16px] items-center justify-center rounded-full bg-gradient-to-r from-red-500 to-rose-500 px-1 text-[9px] font-bold text-white shadow-sm transition-all duration-300">
                            {{ wishlistCount }}
                        </span>
                    </Link>

                    <Link :href="route('cart.index')"
                        class="group relative text-white/70 transition-all duration-300 hover:scale-110 hover:text-cyan-400" aria-label="Keranjang">
                        <!-- Naked Cart Icon dengan Hover Glow -->
                        <ShoppingCart class="h-5 w-5 drop-shadow-sm group-hover:drop-shadow-[0_0_8px_rgba(34,211,238,0.6)]" />

                        <!-- Badge Cart Ramping & Modern (Animasi Memantul & Unread) -->
                        <span v-if="displayCartCount > 0 && hasUnreadCart && !isOnCartPage"
                            :class="{'animate-bounce': isCartAnimating}"
                            class="absolute -right-2 -top-2 inline-flex h-4 min-w-[16px] items-center justify-center rounded-full bg-gradient-to-r from-cyan-500 to-blue-500 px-1 text-[9px] font-bold text-white shadow-sm transition-all duration-300">
                            {{ displayCartCount }}
                        </span>
                    </Link>

                    <!-- Auth Section & Dropdown -->
                    <div class="ml-2 flex items-center gap-4 border-l border-white/10 pl-5">
                        <template v-if="isLoggedIn">
                            <div class="relative flex items-center">
                                <!-- Tombol User (Hanya Avatar Bulat Saja) -->
                                <!-- [KOMENTAR PENJELASAN]: Latar belakang avatar diubah menjadi bg-slate-100 (putih terang) agar sama persis seperti di halaman profil. 
                                     Ini mencegah logo transparan terlihat gelap/buram/menyatu dengan navbar yang gelap. 
                                     Juga menghapus transform-gpu yang kadang membuat gambar menjadi soft/blur di Chrome. -->
                                <button type="button"
                                    class="group relative inline-flex h-12 w-12 items-center justify-center overflow-hidden rounded-full border-2 border-slate-300 bg-slate-100 transition-all duration-300 hover:border-cyan-400 hover:shadow-[0_0_15px_rgba(34,211,238,0.4)] hover:scale-105 active:scale-95"
                                    @click="userMenuOpen = !userMenuOpen"
                                    aria-label="Menu Pengguna">
                                    <img v-if="user?.avatar" 
                                         :src="getHighResAvatar(user.avatar)" 
                                         alt="User Avatar" 
                                         width="48" height="48"
                                         class="h-full w-full object-cover" />
                                    <User v-else class="h-6 w-6 text-slate-400 transition-colors duration-300 group-hover:text-slate-600" stroke-width="2" />
                                </button>

                                <!-- Dropdown Menu Profile dengan Transisi Lembut Vue -->
                                <Transition
                                    enter-active-class="transition ease-out duration-400"
                                    enter-from-class="transform opacity-0 scale-95 -translate-y-4"
                                    enter-to-class="transform opacity-100 scale-100 translate-y-0"
                                    leave-active-class="transition ease-in duration-300"
                                    leave-from-class="transform opacity-100 scale-100 translate-y-0"
                                    leave-to-class="transform opacity-0 scale-95 -translate-y-4"
                                >
                                    <!-- [KOMENTAR PENJELASAN]: Lebar dropdown disesuaikan (w-44) namun padding, teks, dan ukuran ikon dikecilkan agar terlihat padat, mini, dan tidak ada teks yang turun ke baris baru (wrap). -->
                                    <div v-if="userMenuOpen" 
                                         class="absolute right-0 top-[calc(100%+12px)] z-[60] w-44 overflow-hidden rounded-2xl border border-white/10 bg-[#031632]/75 p-1 shadow-[0_20px_40px_rgba(0,0,0,0.3)] backdrop-blur-xl origin-top-right">
                                        
                                        <!-- [KOMENTAR PENJELASAN]: Bagian Header Profil dalam Dropdown dihapus sesuai permintaan agar kotak navigasi lebih kecil dan rapi -->

                                        <div class="grid gap-0.5">
                                            <Link :href="profileUrl"
                                                class="group flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-white/80 transition-all duration-300 hover:bg-white/10 hover:text-white"
                                                @click="closeAllMenus">
                                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/5 transition-all duration-300 group-hover:bg-cyan-500/20 group-hover:shadow-[0_0_12px_rgba(34,211,238,0.3)]">
                                                    <User class="h-4 w-4 text-cyan-400 transition-transform duration-300 group-hover:scale-110" stroke-width="2" />
                                                </div>
                                                <span class="truncate">Profil Saya</span>
                                            </Link>

                                            <Link :href="route('my-rentals.index')"
                                                class="group flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-white/80 transition-all duration-300 hover:bg-white/10 hover:text-white"
                                                @click="closeAllMenus">
                                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/5 transition-all duration-300 group-hover:bg-cyan-500/20 group-hover:shadow-[0_0_12px_rgba(34,211,238,0.3)]">
                                                    <PackageCheck class="h-4 w-4 text-cyan-400 transition-transform duration-300 group-hover:scale-110" stroke-width="2" />
                                                </div>
                                                <span class="truncate">E-Ticket Saya</span>
                                            </Link>

                                            <div class="my-1 h-px bg-white/5"></div>

                                            <button type="button"
                                                class="group flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-[13px] font-semibold text-red-300 transition-all duration-300 hover:bg-red-500/10 hover:text-red-200"
                                                @click="logout">
                                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-red-500/5 transition-all duration-300 group-hover:bg-red-500/20">
                                                    <LogOut class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" stroke-width="2" />
                                                </div>
                                                <span class="truncate">Keluar</span>
                                            </button>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </template>

                        <template v-else>
                            <!-- Tombol Login (Ghost / Text Only Minimalis) -->
                            <Link :href="route('login')"
                                class="text-[13px] font-medium text-white/80 transition-all duration-300 hover:text-cyan-300 hover:-translate-y-0.5">
                                Masuk
                            </Link>

                            <!-- Tombol Daftar (Pill Button Premium) -->
                            <Link :href="route('register')"
                                class="inline-flex h-8 items-center rounded-full bg-gradient-to-r from-cyan-500 to-blue-500 px-5 text-[13px] font-bold text-white shadow-md shadow-cyan-500/20 transition-all duration-300 hover:scale-105 hover:shadow-cyan-500/40">
                                Daftar
                            </Link>
                        </template>
                    </div>
                </div>

                <!-- Mobile Menu Toggles (Responsive) -->
                <div class="flex shrink-0 items-center gap-4 lg:hidden">
                    <Link :href="route('cart.index')"
                        class="relative text-white/80 transition-transform active:scale-95" aria-label="Keranjang Belanja">
                        <ShoppingCart class="h-5 w-5" />
                        <span v-if="displayCartCount > 0 && hasUnreadCart && !isOnCartPage"
                            :class="{'animate-bounce': isCartAnimating}"
                            class="absolute -right-2 -top-2 inline-flex h-4 min-w-[16px] items-center justify-center rounded-full bg-cyan-500 px-1 text-[9px] font-bold text-white shadow-sm transition-all duration-300">
                            {{ displayCartCount }}
                        </span>
                    </Link>

                    <button type="button"
                        class="text-white/80 transition-transform active:scale-95"
                        @click="mobileOpen = !mobileOpen"
                        aria-label="Menu Navigasi Mobile">
                        <Menu v-if="!mobileOpen" class="h-5 w-5" />
                        <X v-else class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Panel (Animasi Accordion) -->
        <div class="grid transition-all duration-300 ease-in-out lg:hidden"
             :class="mobileOpen ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'">
            <div class="overflow-hidden bg-[#051e38]/95 backdrop-blur-xl">
                <div class="mx-auto max-w-7xl space-y-4 px-4 py-4 sm:px-6">
                    <!-- Search Input Mobile -->
                    <!-- Komentar: Diberi z-index 90 agar ketika dropdown autocomplete terbuka, list-nya menimpa tautan menu di bawahnya -->
                    <div class="relative z-[90]">
                        <AppAutocomplete v-model="search" placeholder="Cari perlengkapan..." />
                    </div>

                    <!-- Link Mobile -->
                    <div class="grid gap-1">
                        <Link prefetch="hover" v-for="link in navLinks" :key="`mobile-${link.label}`" :href="link.href()"
                            class="rounded-xl px-4 py-2.5 text-[13px] font-medium transition-colors" :class="isCurrentRoute(link.current)
                                ? 'bg-cyan-500/10 text-cyan-300'
                                : 'text-white/80 hover:bg-white/5 hover:text-white'" @click="closeAllMenus">
                            {{ link.label }}
                        </Link>
                    </div>

                    <!-- Akses Tambahan Mobile -->
                    <div class="grid gap-1 border-t border-white/10 pt-3">
                        <Link :href="route('wishlist.index')"
                            class="inline-flex items-center gap-3 rounded-xl px-4 py-2.5 text-[13px] font-medium text-white/80 transition-colors hover:bg-white/5 hover:text-white"
                            @click="closeAllMenus">
                            <div class="relative">
                                <Heart class="h-4 w-4 text-cyan-400" />
                                <!-- [KOMENTAR PENJELASAN]: Badge versi mobile (cerdas dengan hasUnread) -->
                                <span v-if="wishlistCount > 0 && wishlistStore.hasUnread"
                                    :class="{'animate-bounce': isWishlistAnimating}"
                                    class="absolute -right-2 -top-2 flex h-3.5 min-w-[14px] items-center justify-center rounded-full bg-red-500 px-1 text-[8px] font-bold text-white shadow-sm">
                                    {{ wishlistCount }}
                                </span>
                            </div>
                            Wishlist
                        </Link>
                    </div>

                    <div class="border-t border-white/10 pt-3">
                        <template v-if="isLoggedIn">
                            <div class="rounded-2xl border border-white/5 bg-white/5 p-4">
                                <p class="truncate text-[13px] font-bold text-white">{{ user.name }}</p>
                                <p class="mt-0.5 truncate text-[11px] text-white/50">{{ user.email }}</p>

                                <div class="mt-4 grid gap-1.5">
                                    <Link :href="profileUrl"
                                        class="inline-flex h-9 items-center justify-center rounded-xl border border-white/10 text-[13px] font-medium text-white/90 transition hover:bg-white/10"
                                        @click="closeAllMenus">
                                        Profil Saya
                                    </Link>
                                    <Link :href="route('my-rentals.index')"
                                        class="inline-flex h-9 items-center justify-center rounded-xl border border-white/10 text-[13px] font-medium text-white/90 transition hover:bg-white/10"
                                        @click="closeAllMenus">
                                        E-Ticket Saya
                                    </Link>
                                    <button type="button"
                                        class="inline-flex h-9 items-center justify-center rounded-xl bg-red-500/10 text-[13px] font-medium text-red-300 transition hover:bg-red-500/20"
                                        @click="logout">
                                        Logout
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <Link :href="route('login')"
                                    class="inline-flex h-9 items-center justify-center rounded-xl border border-white/10 text-[13px] font-medium text-white transition hover:bg-white/10"
                                    @click="closeAllMenus">
                                    Masuk
                                </Link>
                                <Link :href="route('register')"
                                    class="inline-flex h-9 items-center justify-center rounded-xl bg-gradient-to-r from-cyan-500 to-blue-500 text-[13px] font-bold text-white shadow-lg shadow-cyan-500/20 transition hover:brightness-110"
                                    @click="closeAllMenus">
                                    Daftar
                                </Link>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
