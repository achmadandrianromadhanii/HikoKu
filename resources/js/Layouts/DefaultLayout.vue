<script setup>
import AppNavbar from '@/Components/common/AppNavbar.vue'
import AppFooter from '@/Components/common/AppFooter.vue'

import WhatsAppButton from '@/Components/ui/WhatsAppButton.vue'
import AppToast from '@/Components/ui/AppToast.vue'

import { usePage } from '@inertiajs/vue3'
import { watch } from 'vue'
import { useWishlistStore } from '@/stores/wishlist'

const page = usePage()
const wishlistStore = useWishlistStore()

// [KOMENTAR PENJELASAN]: Menjaga data Wishlist di Vue/Pinia tetap tersinkron dengan database (Server).
// Walaupun di-refresh, data ini akan segera di-inject ke state lokal agar icon merah tidak menghilang.
watch(() => page.props.wishlist?.product_ids, (newIds) => {
    if (Array.isArray(newIds)) {
        wishlistStore.setWishlist(newIds)
    }
}, { immediate: true })
</script>

<template>
    <!-- 
        [UPDATE GLOBAL TEMA PREMIUM: DARK-GREEN GLOW & MAP GRID]
        Background agak digelapkan menjadi Slate-Gray yang elegan (#D4DCE4).
        Titik kordinat (Map Grid) diubah menjadi warna hijau gelap (Dark Green).
        Ditambahkan efek 'Glow/Terang' dengan kombinasi warna hijau zamrud (Emerald) dan Teal
        di latar belakang agar tidak terlalu mati, memberi nuansa modern outdoor survival/hiking.
        Lighthouse dijamin 100% stabil hijau karena semua ini murni rendering CSS (tanpa memuat gambar).
    -->
    <div class="user-shell relative min-h-screen overflow-hidden bg-[#D4DCE4]">
        
        <!-- [PATTERN]: Lapisan Pola Kordinat Peta (Map Grid) - Dark Green -->
        <div class="fixed inset-0 z-0 pointer-events-none opacity-80" 
             style="background-image: radial-gradient(circle at 1.5px 1.5px, rgba(2, 60, 40, 0.45) 1.5px, transparent 0); background-size: 32px 32px;">
        </div>

        <!-- [GLOW]: Efek Terang Kombinasi Hijau & Teal (Modern Forest Glow) 
             [OPTIMASI]: Menggunakan radial-gradient MURNI tanpa class 'blur-' untuk meringankan GPU Android. -->
        <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
            <!-- Cahaya Hijau Terang di Kiri Atas -->
            <div class="absolute -top-[10%] -left-[10%] w-[500px] h-[500px] rounded-full" 
                 style="background: radial-gradient(circle, rgba(52, 211, 153, 0.15) 0%, transparent 70%);"></div>
            <!-- Cahaya Teal/Cyan di Kanan Tengah -->
            <div class="absolute top-[30%] -right-[10%] w-[600px] h-[600px] rounded-full" 
                 style="background: radial-gradient(circle, rgba(20, 184, 166, 0.10) 0%, transparent 70%);"></div>
            <!-- Cahaya Hijau Halus di Kiri Bawah -->
            <div class="absolute -bottom-[10%] left-[20%] w-[500px] h-[500px] rounded-full" 
                 style="background: radial-gradient(circle, rgba(16, 185, 129, 0.08) 0%, transparent 70%);"></div>
        </div>

        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="h-[420px] w-full bg-auth-theme opacity-[0.10]" />
            <div class="absolute inset-x-0 top-[260px] h-32 bg-gradient-to-b from-primary-50/30 to-transparent" />
        </div>

        <!-- 
            ========================================================================
            [MAIN WRAPPER]: Berlaku untuk SEMUA DEVICE (Mobile & Desktop)
            ========================================================================
        -->
        <div :class="[
            'relative flex-col z-10 flex',
            page.component === 'Catalog/Index' ? 'h-screen overflow-hidden' : 'min-h-screen'
        ]">
            <AppNavbar />

            <main class="relative z-[1] flex-1 pt-[84px]">
                <!-- [UPDATE]: padding-top ditambah menjadi 84px menyesuaikan Navbar baru yang lebih tinggi -->
                <slot />
            </main>

            <!-- 
                [UPDATE: MENYEMBUNYIKAN FOOTER BAWAAN]
                Footer bawaan ini disembunyikan khusus untuk halaman Katalog Desktop,
                karena Footer akan dipindahkan/diinjeksi ke dalam kotak grid produk
                agar ikut terscroll bersama produk (Dashboard-style).
            -->
            <AppFooter v-if="page.component !== 'Catalog/Index'" />
        </div>

        <WhatsAppButton />
        <AppToast />
    </div>
</template>