<script setup>
import { computed, ref, onMounted, onUnmounted, h } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Edit Package', subtitle: 'Ubah paket bundling rental.' }, () => page) })
import {
    ArrowLeft,
    Save,
    Package,
    Boxes,
    Plus,
    Trash2,
    Image as ImageIcon,
    UploadCloud,
    ChevronDown,
    Check
} from 'lucide-vue-next'

const props = defineProps({
    packageItem: {
        type: Object,
        required: true,
    },
    products: {
        type: Array,
        default: () => [],
    },
})

// Progressive disclosure deskripsi (Jika ada deskripsi lama, tampilkan)
const showDescription = ref(!!props.packageItem.description)

// Inisialisasi Form
const form = useForm({
    name: props.packageItem.name || '',
    description: props.packageItem.description || '',
    price_per_day: props.packageItem.price_per_day || '',
    image: null,
    is_active: !!props.packageItem.is_active,
    is_featured: !!props.packageItem.is_featured,
    items: (props.packageItem.items || []).length
        ? props.packageItem.items.map((item) => ({
            product_id: item.product_id || '',
            quantity: item.quantity || 1,
        }))
        : [
            {
                product_id: '',
                quantity: 1,
            },
        ],
    _method: 'put',
})

// Mengatur preview gambar, gunakan foto lama sebagai awalan jika ada
const imagePreview = ref(props.packageItem.primary_image_url || null)

// Update preview saat upload gambar baru
const handleImageChange = (e) => {
    const file = e.target.files[0]
    if (file) {
        form.image = file
        imagePreview.value = URL.createObjectURL(file)
    } else {
        form.image = null
        imagePreview.value = props.packageItem.primary_image_url || null
    }
}

// Logic add & remove items sama seperti create
const addItem = () => {
    form.items.push({
        product_id: '',
        quantity: 1,
    })
}

const removeItem = (index) => {
    if (form.items.length === 1) return
    form.items.splice(index, 1)
}

// Menghitung total qty otomatis (Ringkasan Pintar)
const totalItemCount = computed(() => {
    return form.items.reduce((sum, item) => sum + Number(item.quantity || 0), 0)
})

// === LOGIC CUSTOM DROPDOWN PRODUK (LEVEL DEWA) ===
// State untuk melacak dropdown mana yang sedang terbuka berdasarkan index baris
const openDropdownIndex = ref(null)

// Fungsi untuk membuka/menutup dropdown tertentu
const toggleDropdown = (index) => {
    openDropdownIndex.value = openDropdownIndex.value === index ? null : index
}

// Fungsi ketika produk dipilih dari dropdown custom
const selectProduct = (item, productId) => {
    item.product_id = productId
    openDropdownIndex.value = null // Tutup dropdown setelah memilih
}

// Event listener untuk mendeteksi klik di luar dropdown agar dropdown otomatis tertutup (Click Outside)
const closeDropdownListener = (e) => {
    if (!e.target.closest('.custom-dropdown-container')) {
        openDropdownIndex.value = null
    }
}

// Pasang listener saat komponen dimuat (mounted) dan bersihkan saat dihancurkan (unmounted) agar tidak memory leak
onMounted(() => {
    document.addEventListener('click', closeDropdownListener)
})
onUnmounted(() => {
    document.removeEventListener('click', closeDropdownListener)
})
// === END LOGIC CUSTOM DROPDOWN ===

// Submit update menggunakan forceFormData untuk upload gambar multipart
const submit = () => {
    form.post(route('admin.packages.update', props.packageItem.id), {
        preserveScroll: true,
        forceFormData: true,
    })
}
</script>

<template>
    <!-- Persistent AdminLayout -->
        <div class="mx-auto max-w-5xl space-y-4">
            
            <!-- Header Ringkas -->
            <div class="flex items-center justify-between rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                <div>
                    <h1 class="text-xl font-black text-slate-900">Edit Paket Bundling</h1>
                    <p class="mt-0.5 text-[11px] font-medium text-slate-500">Perbarui spesifikasi dan harga paket.</p>
                </div>
                <Link :href="route('admin.packages.index')"
                    class="inline-flex h-8 items-center gap-2 rounded-lg bg-slate-50 px-3 text-[11px] font-bold text-slate-600 transition hover:bg-slate-100">
                    <ArrowLeft class="h-3.5 w-3.5" />
                    Kembali
                </Link>
            </div>

            <!-- Layout 7:5 -->
            <form class="grid gap-4 lg:grid-cols-12" @submit.prevent="submit">
                
                <!-- Kolom Kiri: Identitas & Isi Paket -->
                <div class="space-y-4 lg:col-span-7">
                    
                    <!-- Box: Identitas Paket -->
                    <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                        <div class="mb-4 flex items-center gap-2 border-b border-slate-50 pb-3">
                            <Package class="h-4 w-4 text-cyan-600" />
                            <h3 class="text-sm font-bold text-slate-900">Identitas Paket</h3>
                        </div>

                        <div class="space-y-4">
                            <!-- Nama Paket -->
                            <div>
                                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Nama Paket</label>
                                <input v-model="form.name" type="text"
                                    class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-[12px] font-medium outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20"
                                    placeholder="Contoh: Paket Camping 2 Orang" />
                                <p v-if="form.errors.name" class="mt-1 text-[10px] text-rose-500">{{ form.errors.name }}</p>
                            </div>

                            <!-- Progressive Disclosure Deskripsi -->
                            <div>
                                <label class="group flex cursor-pointer items-center gap-2">
                                    <div class="relative inline-flex h-4 w-7 items-center rounded-full transition-colors" :class="showDescription ? 'bg-cyan-500' : 'bg-slate-200'">
                                        <input type="checkbox" v-model="showDescription" class="sr-only" />
                                        <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow-sm transition-transform" :class="showDescription ? 'translate-x-3.5' : 'translate-x-0.5'"></span>
                                    </div>
                                    <span class="text-[11px] font-bold text-slate-600 transition-colors group-hover:text-slate-900">Tambahkan Deskripsi Detail?</span>
                                </label>
                                
                                <!-- Animasi v-show -->
                                <div v-show="showDescription" class="mt-3">
                                    <textarea v-model="form.description" rows="3"
                                        class="w-full rounded-lg border-slate-200 bg-slate-50 p-3 text-[12px] outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20"
                                        placeholder="Tuliskan spesifikasi, syarat, atau fasilitas paket..."></textarea>
                                    <p v-if="form.errors.description" class="mt-1 text-[10px] text-rose-500">{{ form.errors.description }}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Box: Isi Paket -->
                    <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                        <div class="mb-4 flex items-center justify-between border-b border-slate-50 pb-3">
                            <div class="flex items-center gap-2">
                                <Boxes class="h-4 w-4 text-amber-500" />
                                <h3 class="text-sm font-bold text-slate-900">Isi Paket</h3>
                            </div>
                            <button type="button" @click="addItem"
                                class="inline-flex h-7 items-center gap-1.5 rounded-md bg-amber-50 px-2 text-[10px] font-bold uppercase tracking-wider text-amber-600 transition hover:bg-amber-100">
                                <Plus class="h-3 w-3" />
                                Tambah Item
                            </button>
                        </div>

                        <div class="space-y-2">
                            <div v-for="(item, index) in form.items" :key="index"
                                class="grid grid-cols-[1fr_90px_auto] items-center gap-3 rounded-xl bg-slate-50 p-2.5 ring-1 ring-slate-100/80">
                                
                                <!-- Komponen Custom Dropdown (Pengganti Native Select yang Kaku) -->
                                <div class="relative w-full custom-dropdown-container">
                                    <!-- Trigger Button Dropdown -->
                                    <button type="button" @click="toggleDropdown(index)"
                                        class="flex h-9 w-full items-center justify-between rounded-lg border border-slate-200 bg-white px-3 text-left outline-none transition-all hover:bg-slate-50 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                                        :class="{ 'border-amber-500 ring-2 ring-amber-500/20 shadow-sm': openDropdownIndex === index }">
                                        <span class="truncate text-[12px] font-semibold" :class="item.product_id ? 'text-slate-800' : 'text-slate-400'">
                                            {{ products.find(p => p.id === item.product_id)?.name || 'Pilih produk untuk paket...' }}
                                        </span>
                                        <!-- Animasi Panah Berputar -->
                                        <ChevronDown class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180 text-amber-500': openDropdownIndex === index }" />
                                    </button>

                                    <!-- Area Menu Dropdown dengan Animasi Transisi Halus -->
                                    <transition
                                        enter-active-class="transition duration-200 ease-out"
                                        enter-from-class="translate-y-1 opacity-0"
                                        enter-to-class="translate-y-0 opacity-100"
                                        leave-active-class="transition duration-150 ease-in"
                                        leave-from-class="translate-y-0 opacity-100"
                                        leave-to-class="translate-y-1 opacity-0">
                                        <div v-show="openDropdownIndex === index"
                                            class="absolute left-0 top-full z-50 mt-1.5 w-full max-h-52 overflow-y-auto rounded-xl border border-slate-100 bg-white p-1.5 shadow-xl shadow-slate-200/60 outline-none">
                                            
                                            <div v-if="products.length === 0" class="p-3 text-center text-[11px] font-medium text-slate-400">
                                                Tidak ada produk tersedia.
                                            </div>
                                            
                                            <!-- Looping Opsi Produk -->
                                            <button v-for="product in products" :key="product.id" type="button"
                                                @click="selectProduct(item, product.id)"
                                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left transition-colors hover:bg-amber-50 hover:text-amber-700"
                                                :class="item.product_id === product.id ? 'bg-amber-50 text-amber-700' : 'text-slate-600'">
                                                <span class="truncate text-[12px] font-semibold">{{ product.name }}</span>
                                                <!-- Ikon Centang Jika Dipilih -->
                                                <Check v-if="item.product_id === product.id" class="h-4 w-4 text-amber-600" />
                                            </button>
                                        </div>
                                    </transition>
                                </div>

                                <!-- Input Qty (Lebih Lega) -->
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-[10px] font-bold text-slate-400">Qty</span>
                                    <input v-model="item.quantity" type="number" min="1"
                                        class="h-9 w-full rounded-lg border-slate-200 bg-white pl-9 pr-2 text-[12px] font-bold text-slate-700 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20" />
                                </div>

                                <!-- Tombol Hapus (Lebih Besar Proporsional) -->
                                <button type="button" @click="removeItem(index)"
                                    class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-100 hover:text-rose-600">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors.items" class="mt-2 text-[10px] text-rose-500">{{ form.errors.items }}</p>
                    </section>
                </div>

                <!-- Kolom Kanan: Foto, Harga, Status -->
                <div class="space-y-4 lg:col-span-5">
                    
                    <!-- Box: Upload Foto -->
                    <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                        <div class="mb-4 flex items-center gap-2 border-b border-slate-50 pb-3">
                            <ImageIcon class="h-4 w-4 text-purple-500" />
                            <h3 class="text-sm font-bold text-slate-900">Foto Thumbnail</h3>
                        </div>

                        <!-- Area Upload Estetik & Kecil -->
                        <div class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 p-4 transition-colors hover:border-purple-400 hover:bg-purple-50/50">
                            <!-- Preview Image Jika Ada -->
                            <div v-if="imagePreview" class="relative mb-3 h-32 w-32 overflow-hidden rounded-lg shadow-sm">
                                <img :src="imagePreview" alt="Preview" class="h-full w-full object-cover" />
                            </div>
                            
                            <!-- Ikon Default Jika Belum Ada -->
                            <div v-else class="mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-sm">
                                <UploadCloud class="h-5 w-5 text-purple-500" />
                            </div>
                            
                            <label class="cursor-pointer text-center">
                                <span class="block text-[12px] font-bold text-purple-600 hover:underline">Ganti Gambar</span>
                                <span class="mt-1 block text-[10px] text-slate-400">JPG, PNG, WEBP (Otomatis Kompresi)</span>
                                <input type="file" accept="image/*" class="hidden" @change="handleImageChange" />
                            </label>
                        </div>
                        <p v-if="form.errors.image" class="mt-2 text-center text-[10px] text-rose-500">{{ form.errors.image }}</p>
                    </section>

                    <!-- Box: Tarif Sewa -->
                    <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                        <div>
                            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Harga / Hari</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3 flex items-center text-[11px] font-bold text-slate-400">Rp</span>
                                <input v-model="form.price_per_day" type="number" min="0"
                                    class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 pl-8 pr-3 text-[12px] font-bold outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20" />
                            </div>
                            <p v-if="form.errors.price_per_day" class="mt-1 text-[10px] text-rose-500">{{ form.errors.price_per_day }}</p>
                        </div>
                    </section>

                    <!-- Box: Status & Simpan -->
                    <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                        <div class="mb-4 flex items-center gap-3">
                            <label class="group flex cursor-pointer items-center gap-2">
                                <div class="relative inline-flex h-4 w-7 items-center rounded-full transition-colors" :class="form.is_active ? 'bg-emerald-500' : 'bg-slate-200'">
                                    <input type="checkbox" v-model="form.is_active" class="sr-only" />
                                    <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow-sm transition-transform" :class="form.is_active ? 'translate-x-3.5' : 'translate-x-0.5'"></span>
                                </div>
                                <span class="text-[11px] font-bold text-slate-600 transition-colors group-hover:text-slate-900">Aktif</span>
                            </label>
                            
                            <label class="group flex cursor-pointer items-center gap-2">
                                <div class="relative inline-flex h-4 w-7 items-center rounded-full transition-colors" :class="form.is_featured ? 'bg-amber-400' : 'bg-slate-200'">
                                    <input type="checkbox" v-model="form.is_featured" class="sr-only" />
                                    <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow-sm transition-transform" :class="form.is_featured ? 'translate-x-3.5' : 'translate-x-0.5'"></span>
                                </div>
                                <span class="text-[11px] font-bold text-slate-600 transition-colors group-hover:text-slate-900">Featured</span>
                            </label>
                        </div>

                        <!-- Ringkasan Pintar Bawah -->
                        <div class="mb-4 flex items-center justify-between rounded-lg bg-slate-50 p-2 ring-1 ring-slate-100/50">
                            <span class="text-[11px] font-bold text-slate-500">Total Item</span>
                            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-white text-[11px] font-black text-slate-900 shadow-sm">{{ totalItemCount }}</span>
                        </div>

                        <!-- Tombol Submit -->
                        <button type="submit"
                            class="flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 px-4 text-[13px] font-black text-white shadow-md shadow-cyan-500/20 transition-all hover:scale-[1.02] hover:shadow-cyan-500/30 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="form.processing">
                            <Save class="h-4 w-4" />
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                    </section>
                </div>
            </form>
        </div>
    
</template>

<style>
/* 
  [BUG FIX & OPTIMASI CLS]
  Memaksa scrollbar vertikal browser agar selalu tampil stabil.
  Ini adalah teknik ampuh untuk mencegah halaman bergeser (layout shift/jump) ke kiri atau kanan
  ketika dropdown menu yang panjang terbuka dan secara tiba-tiba menambah tinggi halaman 
  yang memicu muncul/hilangnya scrollbar Windows secara dinamis.
*/
html {
    overflow-y: scroll !important;
}
</style>