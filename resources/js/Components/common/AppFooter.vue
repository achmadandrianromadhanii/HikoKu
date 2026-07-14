<script setup>
/* 
    [ROMBAK UI/UX PREMIUM: App Footer]
    Menghapus grid kaku, menambahkan animasi List berurutan (Staggered fade-in).
    Dan mengintegrasikan Peta Asli Leaflet.js yang elegan secara dinamis tanpa library berat NPM.
*/
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import { Phone, Mail, MapPin, Instagram, Facebook, Music2 } from 'lucide-vue-next'

const page = usePage()



// [UPDATE]: Menyiapkan array gambar pegunungan HD untuk background footer
const footerImages = [
    'https://images.unsplash.com/photo-1542332213-9b5a5a3fad35?q=50&w=800&fm=webp&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?q=50&w=800&fm=webp&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1519681393784-d120267933ba?q=50&w=800&fm=webp&auto=format&fit=crop'
]
const currentFooterImage = ref(0)
let footerImageInterval = null

const settings = computed(() => page.props.settings?.public || {})

const brandName = computed(() => settings.value.app_name || 'Hiko')
const brandTagline = computed(() => settings.value.app_tagline || 'Rental Hiking & Outdoor')
const brandLogo = '/images/logo.webp'

const mapLink = computed(() => {
    const lat = settings.value.store_lat
    const lng = settings.value.store_lng
    if (!lat || !lng) return '#'
    return `https://maps.google.com/?q=${lat},${lng}`
})

const mapContainer = ref(null)

onMounted(() => {
    // [FITUR PREMIUM]: Intersection Observer untuk Animasi List Menu Muncul Satu-persatu
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('opacity-100', 'translate-y-0')
                entry.target.classList.remove('opacity-0', 'translate-y-4')
                observer.unobserve(entry.target)
            }
        })
    }, { threshold: 0.1 })
    
    // Terapkan observer ke semua item navigasi footer
    setTimeout(() => {
        const navItems = document.querySelectorAll('.footer-nav-item')
        navItems.forEach((item, index) => {
            item.style.transitionDelay = `${index * 100}ms`
            observer.observe(item)
        })
    }, 100)
    
    // [UPDATE]: Slider interval untuk background foto berganti setiap 5 detik dengan transisi halus
    footerImageInterval = setInterval(() => {
        currentFooterImage.value = (currentFooterImage.value + 1) % footerImages.length
    }, 5000)
})

onUnmounted(() => {
    if (footerImageInterval) clearInterval(footerImageInterval)
})

const socialLinks = computed(() => [
    { label: 'Instagram', href: settings.value.instagram_url || '#', icon: Instagram },
    { label: 'Facebook', href: settings.value.facebook_url || '#', icon: Facebook },
    { label: 'TikTok', href: settings.value.tiktok_url || '#', icon: Music2 },
].filter(item => item.href))
</script>

<template>
    <footer class="relative mt-16 overflow-hidden border-t border-white/5 bg-black text-white">
        
        <!-- [UPDATE]: Gambar Mountain HD Slider dengan Loading Lazy & HD+ (Dikembalikan) -->
        <div class="absolute inset-0 z-0">
            <img v-for="(img, idx) in footerImages" :key="idx"
                 :src="img" 
                 alt="Mountain Footer Background"
                 loading="lazy"
                 decoding="async" 
                 width="1280" height="720"
                 class="absolute inset-0 h-full w-full object-cover object-center transition-opacity duration-1000 ease-in-out"
                 :class="idx === currentFooterImage ? 'opacity-100 z-10' : 'opacity-0 z-0'" />
            
            <!-- [UPDATE]: Efek blur dihapus total agar 'TIDAK BURAM', dan opasitas overlay warna diturunkan drastis dari 85% menjadi 40% (bg-black/40) persis seperti Hero Banner agar foto HD sangat terang dan jelas terlihat. -->
            <div class="absolute inset-0 bg-black/40 z-20"></div>
        </div>

        <div class="pointer-events-none absolute inset-0 z-20">
            <!-- Efek Cahaya / Glow tambahan -->
            <!-- [OPTIMASI GPU]: Gunakan radial gradient murni -->
            <div class="absolute -left-12 top-8 h-48 w-48 rounded-full" style="background: radial-gradient(circle, rgba(6, 182, 212, 0.2) 0%, transparent 70%);" />
            <div class="absolute bottom-0 right-0 h-56 w-56 rounded-full" style="background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, transparent 70%);" />
        </div>

        <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="grid gap-12 xl:grid-cols-[1.2fr_0.8fr_1fr_1.2fr]">
                
                <!-- Kolom 1: Identitas -->
                <div>
                    <!-- 
                        [UPDATE TATA LETAK & LOGO FOOTER] 
                        Tata letak logo dikembalikan ke asalnya agar rapi dan tidak terlalu mendominasi ruang.
                    -->
                    <Link prefetch="mount" :href="route('home')" class="inline-flex flex-col items-start gap-4 transition-transform duration-300 hover:scale-105">
                        <!-- 
                            [UPDATE UKURAN & KEJERNIHAN LOGO]
                            - Container dikembalikan ke h-28
                            - Logo dikembalikan ke h-28 (Sesuai asalnya)
                            - TAPI shadow hitam tegas TETAP dipertahankan
                              agar logo tetap menonjol dan tidak bercampur dengan background.
                            - Kode image-rendering (crisp-edges) DIBUANG karena algoritma browser justru membuatnya 'pecah/pixelated' seperti game 8-bit.
                        -->
                        <div class="flex items-center justify-start h-28 w-auto overflow-visible">
                            <!-- Gambar Logo Footer yang dikembalikan ke ukuran normal tapi sangat mulus tanpa pixelation -->
                            <img :src="brandLogo" :alt="brandName" width="160" height="112"
                                   class="h-28 max-w-[280px] object-contain drop-shadow-[0_8px_20px_rgba(0,0,0,0.6)]" />
                        </div>

                        <!-- Tagline dipertahankan sebagai teks mini di bawah logo raksasa -->
                        <div class="min-w-0">
                            <p class="text-[12px] font-bold uppercase tracking-widest text-cyan-300/80">
                                {{ brandTagline }}
                            </p>
                        </div>
                    </Link>

                    <p class="mt-6 max-w-md text-[13px] leading-relaxed text-white/70">
                        Solusi rental alat hiking dan outdoor premium. Praktis, bersih, aman, dan siap membawa pengalaman mendaki Anda ke level selanjutnya.
                    </p>

                    <!-- Naked Social Icons -->
                    <div v-if="socialLinks.length > 0" class="mt-8 flex flex-wrap items-center gap-4">
                        <a v-for="(item, idx) in socialLinks" :key="item.label" :href="item.href" target="_blank" rel="noopener noreferrer"
                           :aria-label="item.label"
                           class="footer-nav-item opacity-0 translate-y-4 group text-white/60 transition-all duration-300 hover:text-cyan-400 hover:-translate-y-1 hover:drop-shadow-[0_0_8px_rgba(34,211,238,0.6)]">
                            <component :is="item.icon" class="h-5 w-5" />
                        </a>
                    </div>
                </div>

                <!-- Kolom 2: Navigasi Menu Animasi Mulus -->
                <div>
                    <h3 class="text-[11px] font-bold uppercase tracking-widest text-cyan-400">Navigasi</h3>
                    <div class="mt-6 flex flex-col gap-3.5 text-[13px] font-medium text-white/70">
                        <Link prefetch="mount" :href="route('home')" class="footer-nav-item opacity-0 translate-y-4 w-fit transition-colors hover:text-cyan-300">Home</Link>
                        <Link prefetch="mount" :href="route('catalog.index')" class="footer-nav-item opacity-0 translate-y-4 w-fit transition-colors hover:text-cyan-300">Katalog Produk</Link>
                        <Link prefetch="mount" :href="route('packages.index')" class="footer-nav-item opacity-0 translate-y-4 w-fit transition-colors hover:text-cyan-300">Paket Hemat</Link>
                        <Link prefetch="mount" :href="route('faq.index')" class="footer-nav-item opacity-0 translate-y-4 w-fit transition-colors hover:text-cyan-300">Bantuan (FAQ)</Link>
                    </div>
                </div>

                <!-- Kolom 3: Kontak -->
                <div>
                    <h3 class="text-[11px] font-bold uppercase tracking-widest text-cyan-400">Kontak Kami</h3>
                    <div class="mt-6 space-y-4 text-[13px] font-medium text-white/70">
                        <div class="footer-nav-item opacity-0 translate-y-4 flex items-start gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/5 text-cyan-400">
                                <Phone class="h-4 w-4" />
                            </div>
                            <span class="mt-1.5">{{ settings.store_phone || '-' }}</span>
                        </div>

                        <div class="footer-nav-item opacity-0 translate-y-4 flex items-start gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/5 text-cyan-400">
                                <Mail class="h-4 w-4" />
                            </div>
                            <span class="mt-1.5">{{ settings.store_email || '-' }}</span>
                        </div>

                        <div class="footer-nav-item opacity-0 translate-y-4 flex items-start gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/5 text-cyan-400">
                                <MapPin class="h-4 w-4" />
                            </div>
                            <span class="mt-1.5 leading-relaxed">{{ settings.store_address || '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Kolom 4: Lokasi Toko (Leaflet.js) -->
                <div>
                    <h3 class="text-[11px] font-bold uppercase tracking-widest text-cyan-400">Lokasi Toko</h3>
                    <div class="footer-nav-item opacity-0 translate-y-4 mt-6 overflow-hidden rounded-2xl border border-white/10 bg-white/5 shadow-2xl backdrop-blur-sm group relative">
                        <!-- [OPTIMASI LIGHTHOUSE]: Menggunakan static placeholder alih-alih Leaflet JS yang berat -->
                        <div class="h-40 w-full bg-gradient-to-br from-[#071f36] to-[#082949] flex flex-col items-center justify-center p-4 text-center">
                            <MapPin class="h-8 w-8 text-cyan-400 mb-2 opacity-80 group-hover:scale-110 group-hover:opacity-100 transition-all duration-300" />
                            <p class="text-white/70 text-xs font-medium">Klik untuk membuka Google Maps</p>
                        </div>
                        <!-- Overlay Link -->
                        <a :href="mapLink" target="_blank" rel="noopener noreferrer" aria-label="Buka Google Maps" class="absolute inset-0 z-10"></a>
                    </div>

                    <a :href="mapLink" target="_blank" rel="noopener noreferrer"
                        class="footer-nav-item opacity-0 translate-y-4 mt-4 inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-white/10 px-4 text-[13px] font-bold text-white transition-all duration-300 hover:bg-cyan-700 hover:shadow-[0_0_15px_rgba(34,211,238,0.4)]">
                        <MapPin class="h-4 w-4" /> Buka di Google Maps
                    </a>
                </div>
            </div>

            <!-- Bagian Bawah Footer -->
            <div class="mt-16 flex flex-col gap-4 border-t border-white/10 pt-6 text-[12px] font-medium text-white/40 sm:flex-row sm:items-center sm:justify-between">
                <p>© 2026 {{ brandName }}. All rights reserved. Dirancang Mewah Khusus Untuk Anda.</p>

                <div class="flex flex-wrap items-center gap-6">
                    <a href="#" class="transition-colors hover:text-cyan-400">Kebijakan Privasi</a>
                    <a href="#" class="transition-colors hover:text-cyan-400">Syarat & Ketentuan</a>
                </div>
            </div>
        </div>
    </footer>
</template>
