<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Kelola Produk', subtitle: 'Atur dan publikasikan katalog produk Anda ke pengguna.' }, () => page) })
import ConfirmModal from '@/Components/ui/ConfirmModal.vue'
import {
    Plus,
    Search,
    Pencil,
    Trash2,
    ChevronLeft,
    ChevronRight,
    Box,
    AlertTriangle,
    X
} from 'lucide-vue-next'

const props = defineProps({
    products: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            prev_page_url: null,
            next_page_url: null,
            current_page: 1,
            last_page: 1,
            total: 0,
            from: 0,
            to: 0,
        }),
    },
    categories: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            featured: 0,
            low_stock: 0,
        }),
    },
})

// Menyimpan state filter
const search = ref(props.filters.search || '')
const category = ref(props.filters.category || '')
const isSearchFocused = ref(false)
const searchInputRef = ref(null)

// State Modal Delete
const openDeleteModal = ref(false)
const deleteLoading = ref(false)
const selectedProduct = ref(null)

const rows = computed(() => props.products?.data || [])

// Fitur Realtime Alert Stok Menipis (<= 1)
const lowStockAlerts = ref([])

// Pantau perubahan data produk untuk notifikasi stok menipis
watch(() => props.products?.data, (newData) => {
    if (!newData) return
    // Ambil produk yang stoknya 1 atau habis
    const critical = newData.filter(p => Number(p.stock_total) <= 1)
    lowStockAlerts.value = critical
}, { immediate: true, deep: true })

// Fitur Auto Search / Debounce tanpa perlu tekan enter
let searchTimeout;
watch(search, (newValue) => {
    clearTimeout(searchTimeout)
    // Beri jeda 300ms setelah user berhenti mengetik, lalu auto-cari
    searchTimeout = setTimeout(() => {
        applyFilters()
    }, 300)
})

const clearSearch = () => {
    search.value = ''
    isSearchFocused.value = false
}

// Global click listener to close dropdown
const closeDropdown = (e) => {
    if (searchInputRef.value && !searchInputRef.value.contains(e.target)) {
        isSearchFocused.value = false
    }
}

// [PERBAIKAN BUG]: Deklarasi variabel pollInterval agar bisa dikenali oleh onMounted dan onUnmounted
let pollInterval;

onMounted(() => {
    document.addEventListener('click', closeDropdown)
    // Polling data produk terbaru setiap 10 detik tanpa me-refresh halaman (Realtime)
    pollInterval = setInterval(() => {
        router.reload({ 
            only: ['products', 'stats'], 
            preserveState: true, 
            preserveScroll: true 
        })
    }, 10000)
})

onUnmounted(() => {
    document.removeEventListener('click', closeDropdown)
    clearInterval(pollInterval)
})

// Handler hapus produk
const openDelete = (product) => {
    selectedProduct.value = product
    openDeleteModal.value = true
}

const destroyProduct = () => {
    if (!selectedProduct.value) return

    deleteLoading.value = true

    router.delete(route('admin.products.destroy', selectedProduct.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleteLoading.value = false
            openDeleteModal.value = false
            selectedProduct.value = null
        },
    })
}

// Membuat inisial singkatan nama untuk placeholder gambar (misal: "Trekking Pole" -> "TP")
const getInitials = (name) => {
    if (!name) return 'PR'
    const words = name.split(' ')
    if (words.length === 1) return words[0].substring(0, 2).toUpperCase()
    return (words[0][0] + words[1][0]).toUpperCase()
}

// Penentuan warna background gradasi berdasarkan ID
const getGradient = (id) => {
    const gradients = [
        'bg-gradient-to-br from-indigo-100 to-purple-100 text-indigo-500',
        'bg-gradient-to-br from-emerald-100 to-teal-100 text-emerald-500',
        'bg-gradient-to-br from-rose-100 to-orange-100 text-rose-500',
        'bg-gradient-to-br from-sky-100 to-blue-100 text-sky-500',
        'bg-gradient-to-br from-amber-100 to-yellow-100 text-amber-500'
    ]
    return gradients[id % gradients.length]
}

</script>

<template>
    <!-- Persistent AdminLayout -->
        
    <!-- Komentar: Mengatur layout menjadi h-full agar scrollbar utama website hilang dan diganti dengan scrollbar pada tabel. -->
    <div class="flex flex-col h-full gap-4">
        <!-- Header Aksi Utama -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3 text-sm font-medium text-slate-500">
                    <div class="flex items-center gap-1.5 px-3 py-1 bg-slate-100 rounded-full">
                        <Box class="w-4 h-4 text-slate-400" />
                        <span class="text-slate-700 font-bold">{{ stats.total }}</span> Total
                    </div>
                    <span class="text-slate-300">•</span>
                    <span class="text-emerald-600 font-semibold">{{ stats.active }} Aktif</span>
                </div>
                
                <Link :href="route('admin.products.create')"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-cyan-500/30 transition-all hover:bg-cyan-500 hover:shadow-cyan-500/40">
                    <Plus class="h-4 w-4" stroke-width="3" />
                    Tambah Produk
                </Link>
            </div>

            <!-- Notifikasi Peringatan Stok Menipis Realtime -->
            <div v-if="lowStockAlerts.length > 0" 
                class="flex items-start gap-3 p-4 rounded-2xl bg-rose-50 border border-rose-100 text-rose-600 shadow-sm animate-pulse">
                <AlertTriangle class="w-5 h-5 shrink-0 mt-0.5" />
                <div class="flex-1">
                    <h4 class="text-sm font-bold">Peringatan Stok Menipis!</h4>
                    <p class="text-xs font-medium mt-1 opacity-90">
                        Terdapat {{ lowStockAlerts.length }} produk yang stoknya habis atau tersisa 1. Harap segera periksa dan tambah stok.
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span v-for="item in lowStockAlerts" :key="item.id" 
                            class="inline-flex items-center px-2 py-1 rounded-md bg-white/60 text-[11px] font-bold border border-rose-100">
                            {{ item.name }} (Sisa: {{ item.stock_total }})
                        </span>
                    </div>
                </div>
            </div>

            <!-- Sleek Data Grid Container -->
            <div class="flex-1 min-h-0 flex flex-col rounded-3xl border border-slate-100 bg-white shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                
                <!-- Filter Bar -->
                <div class="shrink-0 border-b border-slate-100/60 p-4 bg-white/50 backdrop-blur-sm relative z-20">
                    <div class="flex flex-col md:flex-row md:items-center gap-4">
                        <!-- Seamless Search Bar (Dipersempit menjadi max-w-sm) -->
                        <div class="relative w-full md:max-w-sm" ref="searchInputRef">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <Search class="h-4 w-4 text-slate-400" />
                            </div>
                            <input v-model="search" type="text"
                                @focus="isSearchFocused = true"
                                class="block w-full rounded-xl border-none bg-slate-50 py-2.5 pl-10 pr-8 text-sm text-slate-900 focus:bg-white focus:ring-2 focus:ring-cyan-500/20 focus:outline-none transition-all"
                                placeholder="Ketik 1 huruf mencari..." />
                            
                            <button v-if="search" @click="clearSearch" class="absolute inset-y-0 right-2 flex items-center p-1 rounded-full hover:bg-slate-200 text-slate-400 transition">
                                <X class="h-3.5 w-3.5" />
                            </button>

                            <!-- Floating Pop-up Dropdown (Live Search Results) -->
                            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
                                <div v-if="isSearchFocused && search.length > 0" 
                                    class="absolute top-full mt-2 w-full sm:w-80 left-0 bg-white rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15)] border border-slate-100 z-50 overflow-hidden">
                                    <div class="px-3 py-2 border-b border-slate-50 bg-slate-50/50">
                                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Hasil Pencarian Cepat</span>
                                    </div>
                                    <div class="max-h-64 overflow-y-auto p-1">
                                        <div v-if="rows.length === 0" class="p-4 text-center text-xs text-slate-500">
                                            Tidak ada hasil untuk "{{ search }}"
                                        </div>
                                        <Link v-for="row in rows.slice(0,5)" :key="row.id" :href="route('admin.products.edit', row.id)"
                                            class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-xl transition-colors group">
                                            <div class="h-8 w-8 rounded-full flex items-center justify-center flex-shrink-0" :class="getGradient(row.id)">
                                                <span class="text-[10px] font-black">{{ getInitials(row.name) }}</span>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs font-semibold text-slate-900 truncate">{{ row.name }}</p>
                                                <p class="text-[10px] text-slate-500 truncate">{{ row.category_name || '-' }}</p>
                                            </div>
                                            <ChevronRight class="h-4 w-4 text-slate-300 group-hover:text-cyan-500 transition-colors" />
                                        </Link>
                                    </div>
                                    <div class="p-2 border-t border-slate-50 text-center" v-if="rows.length > 5">
                                        <span class="text-[10px] text-slate-400">Terdapat {{ rows.length }} hasil.</span>
                                    </div>
                                </div>
                            </transition>
                        </div>
                        
                        <!-- Slim Select Filters (Hanya Kategori, tanpa icon filter) -->
                        <div class="flex items-center gap-2">
                            <select v-model="category" @change="applyFilters"
                                class="h-10 rounded-xl border-none bg-slate-50 pl-4 pr-10 py-2 text-xs font-semibold text-slate-600 focus:ring-2 focus:ring-cyan-500/20 focus:outline-none cursor-pointer hover:bg-slate-100 transition-colors appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%2394a3b8%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E')] bg-[length:16px_16px] bg-[position:right_12px_center] bg-no-repeat">
                                <option value="">Semua Kategori</option>
                                <option v-for="item in categories" :key="item.id" :value="item.slug || item.id">
                                    {{ item.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table Area -->
                <div class="flex-1 min-h-0 overflow-auto custom-scrollbar relative z-10">
                    <table class="w-full text-left text-sm text-slate-600 whitespace-nowrap">
                        <thead class="sticky top-0 z-20 bg-white text-[10px] font-bold uppercase tracking-widest text-slate-400 shadow-[0_1px_2px_rgba(0,0,0,0.05)] after:absolute after:inset-x-0 after:bottom-0 after:border-b after:border-slate-100/60">
                            <tr>
                                <th class="px-6 py-4">Produk</th>
                                <th class="px-6 py-4">Kategori</th>
                                <th class="px-6 py-4 text-right">Harga / Hari</th>
                                <th class="px-6 py-4 text-center">Stok</th>
                                <th class="px-6 py-4 center">Label</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50/50">
                            <!-- Empty State -->
                            <tr v-if="rows.length === 0">
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center">
                                            <Search class="w-8 h-8 text-slate-300" />
                                        </div>
                                        <h3 class="text-sm font-bold text-slate-700">Tidak ada produk ditemukan</h3>
                                        <p class="text-xs text-slate-500">Coba sesuaikan kata kunci atau hapus filter Anda.</p>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Data Rows (Zebra Striping) -->
                            <tr v-for="row in rows" :key="row.id" class="transition-colors hover:bg-slate-100/50 group even:bg-slate-50/80">
                                <!-- Col 1: Thumbnail & Name -->
                                <td class="px-6 py-3 min-w-[250px]">
                                    <div class="flex items-center gap-4">
                                        <!-- Thumbnail Sleek -->
                                        <div class="relative shrink-0">
                                            <img v-if="row.primary_image_url" :src="row.primary_image_url" :alt="row.name"
                                                class="h-10 w-10 rounded-[14px] object-cover shadow-sm border border-slate-100" />
                                            <div v-else :class="['flex h-10 w-10 items-center justify-center rounded-[14px] text-xs font-black tracking-tight shadow-sm border border-white/50', getGradient(row.id)]">
                                                {{ getInitials(row.name) }}
                                            </div>
                                            <!-- Green dot indicator if active -->
                                            <div v-if="row.is_active" class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-500 border-2 border-white rounded-full"></div>
                                        </div>
                                        <!-- Text -->
                                        <div class="flex flex-col overflow-hidden">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-[13px] text-slate-900 truncate">{{ row.name }}</span>
                                                <!-- Komentar: Menampilkan badge jumlah varian yang tersimpan agar admin tahu data sudah tersinkron tanpa harus buka edit -->
                                                <span v-if="row.variants_count > 0" class="inline-flex items-center justify-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-cyan-50 text-cyan-600 border border-cyan-100">{{ row.variants_count }} Varian</span>
                                            </div>
                                            <span class="text-[11px] font-medium text-slate-400 truncate mt-0.5">{{ row.slug || row.sku || '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Col 2: Category -->
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center text-[12px] font-medium text-slate-600 bg-slate-50 px-2 py-1 rounded-md">
                                        {{ row.category_name }}
                                    </span>
                                </td>
                                
                                <!-- Col 3: Price (Tabular nums) -->
                                <td class="px-6 py-3 text-right tabular-nums font-bold text-[13px] text-slate-700">
                                    {{ row.price_label }}
                                </td>
                                
                                <!-- Col 4: Stock -->
                                <td class="px-6 py-3 text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="tabular-nums font-bold text-[13px]" :class="Number(row.stock_total) <= 1 ? 'text-rose-600 animate-pulse' : 'text-slate-700'">
                                            {{ row.stock_total }}
                                        </span>
                                    </div>
                                </td>
                                
                                <!-- Col 5: Badges (Glassmorphism) -->
                                <td class="px-6 py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <!-- Status Badge -->
                                        <span v-if="!row.is_active" class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-500">
                                            Draft
                                        </span>
                                        <!-- Featured Badge -->
                                        <span v-if="row.is_featured" class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-amber-50 text-amber-600 border border-amber-100/50">
                                            ⭐ Top
                                        </span>
                                    </div>
                                </td>
                                
                                <!-- Col 6: Actions (Subtle hover) -->
                                <td class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                        <!-- Edit -->
                                        <Link :href="route('admin.products.edit', row.id)" title="Edit"
                                            class="p-2 text-slate-400 hover:text-cyan-600 hover:bg-cyan-50 rounded-lg transition-colors">
                                            <Pencil class="w-4 h-4" />
                                        </Link>
                                        <!-- Delete -->
                                        <button @click="openDelete(row)" title="Hapus"
                                            class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Custom Minimalist Pagination (< > icon saja) -->
                <div v-if="products.total > 0" class="shrink-0 border-t border-slate-100/60 p-4 flex items-center justify-between bg-white/30 relative z-20">
                    <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                        Hal <span class="text-slate-700">{{ products.current_page }}</span> / {{ products.last_page }}
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <!-- Tombol Prev -->
                        <Link v-if="products.prev_page_url" :href="products.prev_page_url" preserve-scroll
                            class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition-colors">
                            <ChevronLeft class="w-4 h-4" stroke-width="2.5" />
                        </Link>
                        <div v-else class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50/50 text-slate-300 cursor-not-allowed">
                            <ChevronLeft class="w-4 h-4" stroke-width="2.5" />
                        </div>
                        
                        <!-- Tombol Next -->
                        <Link v-if="products.next_page_url" :href="products.next_page_url" preserve-scroll
                            class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition-colors">
                            <ChevronRight class="w-4 h-4" stroke-width="2.5" />
                        </Link>
                        <div v-else class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50/50 text-slate-300 cursor-not-allowed">
                            <ChevronRight class="w-4 h-4" stroke-width="2.5" />
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus -->
        <ConfirmModal :open="openDeleteModal" title="Hapus Produk" message="Yakin ingin menghapus produk ini secara permanen dari sistem?"
            confirm-text="Ya, Hapus" :loading="deleteLoading" variant="danger" @close="openDeleteModal = false"
            @confirm="destroyProduct" />
    
</template>

<style scoped>
/* Komentar: Kustomisasi scrollbar halus di dalam tabel agar terlihat elegan (Mac-like) */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>