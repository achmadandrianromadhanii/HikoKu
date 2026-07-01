<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref, onMounted, watch, nextTick } from 'vue'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import ProductCard from '@/Components/product/ProductCard.vue'
import MobileProductCard from '@/Components/mobile/MobileProductCard.vue'
import MobileFilterSheet from '@/Components/mobile/MobileFilterSheet.vue'
import AppAutocomplete from '@/Components/common/AppAutocomplete.vue'
import { PackageSearch, Filter } from 'lucide-vue-next'

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const form = useForm({
    category: props.filters.category || '',
    search: props.filters.search || '',
    available_only: !!props.filters.available_only,
    condition: props.filters.condition || '',
    min_price: props.filters.min_price || '',
    max_price: props.filters.max_price || '',
    sort: props.filters.sort || 'latest',
})

const submitFilter = () => {
    router.get(route('catalog.index'), form.data(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

const resetFilter = () => {
    form.category = ''
    form.search = ''
    form.available_only = false
    form.condition = ''
    form.min_price = ''
    form.max_price = ''
    form.sort = 'latest'
    submitFilter()
}

const productData = computed(() => props.products?.data || [])

// [FITUR PREMIUM]: Animasi Murni Native Intersection Observer
const gridRef = ref(null)

onMounted(() => {
    const staggerObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.remove('opacity-0', 'translate-y-12', 'scale-95')
                entry.target.classList.add('opacity-100', 'translate-y-0', 'scale-100')
                staggerObserver.unobserve(entry.target)
            }
        })
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' })

    watch(productData, async () => {
        await nextTick()
        if (gridRef.value) {
            Array.from(gridRef.value.children).forEach((child, index) => {
                child.classList.add('opacity-0', 'translate-y-12', 'scale-95', 'transition-all', 'duration-700', 'ease-out')
                child.style.transitionDelay = `${(index % 12) * 100}ms`
                staggerObserver.observe(child)
            })
        }
    }, { immediate: true })
})

const showMobileFilter = ref(false)
</script>

<template>

    <Head title="Katalog" />

    <DefaultLayout>
        
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: 100% TIDAK DIUBAH (hidden lg:block)       -->
        <!-- ========================================================= -->
        <div class="hidden lg:block">
        <!-- [UPDATE]: Mengurangi padding vertikal (py-8 menjadi py-4) agar halaman lebih naik ke atas -->
        <section class="mx-auto max-w-[1400px] px-4 py-4 sm:px-6 lg:px-8">
            <!-- [ROMBAK: Resolusi Layout] Layout diperlebar sedikit untuk memberi ruang pada 4 kolom produk -->
            <div class="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)] items-start">
                
                <!-- [FITUR PREMIUM]: STICKY FLOATING SIDEBAR 
                     Sidebar tidak akan melar ke bawah, melainkan diam mengambang saat discroll! -->
                <aside class="user-panel self-start sticky top-28 h-fit p-5 shadow-[0_4px_24px_rgba(0,0,0,0.02)] border-surface-100/50">
                    <div class="space-y-4">
                        <!-- [UPDATE]: Fitur pencarian "Cari Produk" dipindah ke Navbar, jadi dihapus dari sidebar ini. -->
                        <div>
                            <label class="mb-1.5 block text-[13px] font-extrabold text-surface-900 tracking-wide uppercase">
                                Kategori
                            </label>
                            <select v-model="form.category" class="w-full rounded-xl border-transparent bg-surface-50 px-4 py-3 text-[14px] text-surface-700 outline-none transition focus:border-cyan-300 focus:bg-white focus:ring-4 focus:ring-cyan-100">
                                <option value="">Semua kategori</option>
                                <option v-for="category in categories" :key="category.id" :value="category.slug">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                        <!-- [UPDATE]: Filter Kondisi dihapus agar tampilan lebih bersih. -->

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                            <div>
                                <label class="mb-1.5 block text-[13px] font-extrabold text-surface-900 tracking-wide uppercase">
                                    Harga Min
                                </label>
                                <input v-model="form.min_price" type="number" placeholder="Rp 0" class="w-full rounded-xl border-transparent bg-surface-50 px-4 py-3 text-[14px] text-surface-700 outline-none transition focus:border-cyan-300 focus:bg-white focus:ring-4 focus:ring-cyan-100" />
                            </div>

                            <div>
                                <label class="mb-1.5 block text-[13px] font-extrabold text-surface-900 tracking-wide uppercase">
                                    Harga Max
                                </label>
                                <input v-model="form.max_price" type="number" placeholder="Rp Max" class="w-full rounded-xl border-transparent bg-surface-50 px-4 py-3 text-[14px] text-surface-700 outline-none transition focus:border-cyan-300 focus:bg-white focus:ring-4 focus:ring-cyan-100" />
                            </div>
                        </div>

                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-transparent bg-cyan-50/50 px-4 py-3 text-[13px] font-bold text-cyan-800 transition-colors hover:bg-cyan-50">
                            <input v-model="form.available_only" type="checkbox"
                                class="h-4 w-4 rounded border-cyan-300 text-cyan-600 focus:ring-cyan-500" />
                            Tampilkan stok tersedia saja
                        </label>

                        <div class="grid gap-2.5 pt-2">
                            <!-- [ROMBAK TOMBOL]: Tinggi, lonjong, mewah -->
                            <button type="button" class="inline-flex h-11 w-full items-center justify-center rounded-full bg-gradient-to-r from-cyan-500 to-blue-500 text-[13px] font-extrabold text-white shadow-lg shadow-cyan-500/20 transition-all hover:scale-105 hover:shadow-cyan-500/40" @click="submitFilter">
                                Terapkan Filter
                            </button>

                            <button type="button" class="inline-flex h-11 w-full items-center justify-center rounded-full border border-surface-200 bg-white text-[13px] font-extrabold text-surface-600 transition-all hover:border-surface-300 hover:bg-surface-50" @click="resetFilter">
                                Reset Filter
                            </button>
                        </div>
                    </div>
                </aside>

                <div class="space-y-6">
                    <div class="user-panel flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between border-transparent shadow-sm bg-white/80 backdrop-blur-md">
                        <p class="text-[13px] text-surface-600">
                            Menampilkan
                            <span class="font-extrabold text-surface-900">{{ products.from || 0 }}</span>
                            -
                            <span class="font-extrabold text-surface-900">{{ products.to || 0 }}</span>
                            dari
                            <span class="font-extrabold text-surface-900">{{ products.total || 0 }}</span>
                            produk
                        </p>

                        <div class="flex items-center gap-2">
                            <label class="text-[13px] font-extrabold text-surface-700">Urutkan</label>

                            <select v-model="form.sort"
                                class="h-10 cursor-pointer rounded-full border border-transparent bg-surface-50 px-4 text-[13px] font-medium text-surface-800 outline-none transition hover:bg-surface-100 focus:border-cyan-300 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                                @change="submitFilter">
                                <option value="latest">Terbaru</option>
                                <option value="price_low">Harga Terendah</option>
                                <option value="price_high">Harga Tertinggi</option>
                                <option value="rating">Rating Tertinggi</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="productData.length > 0">
                        <!-- [FITUR PREMIUM]: ANIMASI SCROLL BAWAH STAGGERED MUNCUL PERLAHAN -->
                        <!-- [UPDATE]: Mengecilkan kembali ukuran kotak produk menjadi 4 kolom, disamakan dengan homepage -->
                        <div ref="gridRef" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            <ProductCard v-for="product in productData" :key="product.id" :product="product" />
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center rounded-[32px] border border-dashed border-surface-200 bg-surface-50 py-20 text-center shadow-sm">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-sm mb-4">
                            <PackageSearch class="h-8 w-8 text-surface-300" />
                        </div>
                        <h3 class="text-lg font-extrabold text-surface-900">Tidak ada produk ditemukan</h3>
                        <p class="mt-2 text-[13px] text-surface-500 max-w-sm leading-relaxed">
                            Maaf, produk dengan kriteria filter yang Anda cari sedang kosong. Coba hapus beberapa filter pencarian Anda.
                        </p>
                        <button type="button" class="mt-6 inline-flex h-11 items-center justify-center rounded-full bg-surface-900 px-6 text-[13px] font-extrabold text-white transition-all hover:bg-surface-800 hover:scale-105" @click="resetFilter">
                            Reset Semua Filter
                        </button>
                    </div>

                    <div v-if="products.links?.length > 3" class="flex flex-wrap items-center gap-2 pt-4">
                        <template v-for="(link, index) in products.links" :key="index">
                            <Link v-if="link.url" :href="link.url"
                                class="inline-flex min-w-[40px] items-center justify-center rounded-full border border-transparent px-3 py-2 text-[13px] font-bold transition-all hover:scale-105"
                                :class="link.active
                                    ? 'bg-gradient-to-r from-cyan-500 to-blue-500 text-white shadow-md shadow-cyan-500/20'
                                    : 'bg-surface-100 text-surface-700 hover:bg-surface-200'"
                                v-html="link.label" />

                            <span v-else
                                class="inline-flex min-w-[40px] items-center justify-center rounded-full border border-surface-100 bg-surface-50 px-3 py-2 text-[13px] text-surface-400"
                                v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </section>
        </div> <!-- End of DESKTOP VIEW -->


        <!-- ========================================================= -->
        <!-- [MOBILE VIEW - MAHA KARYA]: KATALOG NEON FOREST           -->
        <!-- ========================================================= -->
        <!-- [MOBILE VIEW - MAHA KARYA]: KERANJANG PREMIUM (DI-ADAPTASI UNTUK KATALOG) -->
        <!-- [UPDATE]: overflow dihapus total dari container ini.
             Alasan: overflow-hidden / overflow-x-hidden pada ancestor akan merusak
             position:sticky (sesuai spesifikasi CSS W3C Section 6.4).
             Elemen dekorasi yang meluber akan di-clip oleh user-shell di DefaultLayout
             yang sudah memiliki overflow-hidden. -->
        <div class="block lg:hidden min-h-screen pb-44 relative bg-[#0B1120] bg-gradient-to-br from-[#0B1120] via-[#0A1A2F] to-[#0B1120]">
            
            <!-- [KOMENTAR PENJELASAN]: Dekorasi Garis (Grid Pattern) & Bentuk Abstrak -->
            <!-- Latar belakang tidak lagi gelap polos, ada jaring garis modern, titik halus, dan bentuk SVG abstrak -->
            
            <!-- Pattern Garis Tipis & Titik Grid Halus (Fixed agar merata di iOS) -->
            <div class="absolute inset-0 bg-[linear-gradient(rgba(34,211,238,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(34,211,238,0.04)_1px,transparent_1px)] bg-[size:30px_30px] pointer-events-none"></div>
            <div class="absolute inset-0 opacity-[0.15] pointer-events-none" style="background-image: radial-gradient(circle at 2px 2px, rgba(34, 211, 238, 0.4) 1px, transparent 0); background-size: 24px 24px;"></div>
            
            <!-- Elemen Garis Melengkung / Bentuk Abstrak Lembut (SVG Background) -->
            <svg class="absolute top-0 right-0 w-full h-[500px] opacity-[0.03] pointer-events-none" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M 0,200 C 100,300 300,100 400,200" stroke="white" stroke-width="2" fill="none" />
                <path d="M -50,150 C 150,350 350,50 450,150" stroke="white" stroke-width="1" fill="none" />
                <circle cx="350" cy="50" r="100" stroke="white" stroke-width="1" fill="none" />
            </svg>

            <!-- Semburan warna warni (Mesh Gradient Overlay Glow) -->
            <div class="absolute -top-10 left-0 w-72 h-72 bg-cyan-600/20 rounded-full blur-[120px] pointer-events-none mix-blend-screen"></div>
            <div class="absolute top-[30%] -right-20 w-80 h-80 bg-purple-600/15 rounded-full blur-[120px] pointer-events-none mix-blend-screen"></div>
            <div class="absolute bottom-40 left-10 w-72 h-72 bg-emerald-600/20 rounded-full blur-[120px] pointer-events-none mix-blend-screen"></div>

            <!-- Mobile Header & Kategori Horizontal Snap -->
            <!-- [UPDATE]: Sticky dihapus, diganti posisi normal (static).
                 Alasan: position:sticky tidak berfungsi karena ancestor (user-shell di
                 DefaultLayout.vue) memiliki overflow:hidden yang membuat sticky gagal
                 menempel dan malah menutupi grid produk di bawahnya.
                 Sekarang header mengalir normal di atas grid tanpa tumpang tindih. -->
            <div class="relative z-10 bg-[#0B1120] border-b border-white/10 shadow-[0_2px_10px_rgba(0,0,0,0.4)]">
                <div class="px-4 pt-2 pb-2 flex items-center justify-between">
                    <div>
                        <h1 class="text-[16px] font-black tracking-tight text-white drop-shadow-md">Eksplorasi Alat</h1>
                        <p class="text-[10px] font-bold text-cyan-400 mt-0.5 uppercase tracking-widest">{{ products.total || 0 }} Item Ditemukan</p>
                    </div>
                </div>

                <!-- Kategori Cepat (Horizontal Snap) -->
                <div class="flex gap-2 overflow-x-auto px-4 pb-3 pt-1 hide-scrollbar" style="scroll-snap-type: x mandatory;">
                    <button 
                        @click="form.category = ''; submitFilter()"
                        class="shrink-0 rounded-full px-4 py-1.5 text-[11px] font-black transition-all active:scale-95 border"
                        :class="form.category === '' ? 'bg-cyan-500 border-cyan-400 text-slate-900 shadow-[0_0_12px_rgba(34,211,238,0.4)]' : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10'"
                    >
                        Semua
                    </button>
                    <button 
                        v-for="cat in categories" :key="cat.id"
                        @click="form.category = cat.slug; submitFilter()"
                        class="shrink-0 rounded-full px-4 py-1.5 text-[11px] font-black transition-all active:scale-95 border"
                        :class="form.category === cat.slug ? 'bg-cyan-500 border-cyan-400 text-slate-900 shadow-[0_0_12px_rgba(34,211,238,0.4)]' : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10'"
                    >
                        {{ cat.name }}
                    </button>
                </div>
            </div>

            <!-- Mobile Product Grid -->
            <!-- [UPDATE]: Padding atas dikurangi (pt-4) agar grid produk tidak
                 terlalu jauh dari header kategori. pb-5 untuk jarak bawah. -->
            <div class="px-4 pt-4 pb-5">
                <div v-if="productData.length > 0" class="grid grid-cols-2 gap-3">
                    <MobileProductCard v-for="product in productData" :key="product.id" :product="product" />
                </div>
                
                <div v-else class="flex flex-col items-center justify-center rounded-[20px] border border-white/10 bg-slate-900/40 py-16 text-center shadow-lg backdrop-blur-md">
                    <PackageSearch class="h-10 w-10 text-slate-500 mb-3" />
                    <h3 class="text-sm font-black text-white">Barang Tidak Ditemukan</h3>
                    <p class="mt-1 text-[11px] font-medium text-slate-400 px-6">
                        Coba hapus filter atau pilih kategori lain.
                    </p>
                    <button type="button" class="mt-4 rounded-full bg-white/10 px-6 py-2 text-xs font-bold text-cyan-300 border border-white/10 active:scale-95" @click="resetFilter">
                        Reset Filter
                    </button>
                </div>

                <!-- Mobile Pagination -->
                <div v-if="products.links?.length > 3" class="mt-8 flex flex-wrap justify-center gap-1.5">
                    <template v-for="(link, index) in products.links" :key="index">
                        <Link v-if="link.url" :href="link.url"
                              class="flex h-8 min-w-[32px] items-center justify-center rounded-lg border px-2 text-[11px] font-black transition-all active:scale-90"
                              :class="link.active ? 'bg-cyan-500 border-cyan-400 text-slate-900 shadow-[0_0_15px_rgba(34,211,238,0.3)]' : 'bg-white/5 border-white/10 text-slate-400'"
                              v-html="link.label" />
                        <span v-else
                              class="flex h-8 min-w-[32px] items-center justify-center rounded-lg border border-white/5 bg-transparent px-2 text-[11px] text-slate-600"
                              v-html="link.label" />
                    </template>
                </div>
            </div>

            <!-- [UPDATE]: Tombol Filter FAB diubah posisinya sedikit lebih tinggi (bottom-28) agar tidak terlalu berdempetan dengan MobileBottomNav Capsule -->
            <div class="fixed bottom-28 left-1/2 z-40 -translate-x-1/2">
                <button 
                    type="button"
                    class="flex h-11 items-center justify-center gap-2 rounded-full bg-slate-800/90 px-6 py-2 shadow-[0_8px_30px_rgba(0,0,0,0.5)] backdrop-blur-md border border-white/10 transition-transform active:scale-95"
                    @click="showMobileFilter = true"
                >
                    <Filter class="h-4 w-4 text-cyan-400" />
                    <span class="text-xs font-extrabold tracking-wide text-slate-200">Filter & Urutkan</span>
                    
                    <!-- Indikator filter aktif -->
                    <span v-if="form.min_price || form.condition || form.in_stock" class="ml-1 flex h-2 w-2 rounded-full bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.8)]"></span>
                </button>
            </div>

            <!-- Bottom Sheet Filter -->
            <MobileFilterSheet 
                :show="showMobileFilter"
                :categories="categories"
                v-model="form"
                @close="showMobileFilter = false"
                @apply="submitFilter"
                @reset="resetFilter"
            />
        </div>

    </DefaultLayout>
</template>